<?php
/**
 * Plugin Name: SEO Content Generator
 * Plugin URI: https://github.com/your-org/content-generator
 * Description: WordPress plugin that generates structured, SEO-optimized content pages for jewelry e-commerce using OpenAI's GPT-4 API.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Development Team
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: seo-generator
 * Domain Path: /languages
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

// Define plugin constants.
define( 'SEO_GENERATOR_VERSION', '1.0.0' );
define( 'SEO_GENERATOR_PLUGIN_FILE', __FILE__ );
define( 'SEO_GENERATOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SEO_GENERATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SEO_GENERATOR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Require Composer autoloader.
if ( file_exists( SEO_GENERATOR_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once SEO_GENERATOR_PLUGIN_DIR . 'vendor/autoload.php';
}

// Require helper functions.
require_once SEO_GENERATOR_PLUGIN_DIR . 'includes/functions.php';

// ============================================================================
// IMAGE QUALITY SETTINGS
// ============================================================================

/**
 * Increase JPEG image quality to 95% (default is 82%).
 * Higher quality = better images but larger file sizes.
 */
add_filter( 'jpeg_quality', function() {
	return 95;
} );

/**
 * Also apply high quality to image editor (for WordPress 5.3+).
 */
add_filter( 'wp_editor_set_quality', function( $quality, $mime_type ) {
	if ( 'image/jpeg' === $mime_type ) {
		return 95;
	}
	return $quality;
}, 10, 2 );

/**
 * Prevent WordPress from scaling down large images automatically.
 * Default threshold is 2560px - we increase it to 3000px.
 * Set to false to disable automatic scaling entirely.
 */
add_filter( 'big_image_size_threshold', function() {
	return 3000; // Max width/height before WordPress scales down
} );

// ============================================================================
// END IMAGE QUALITY SETTINGS
// ============================================================================

// Activation hook.
register_activation_hook( __FILE__, array( 'SEOGenerator\Activation', 'activate' ) );

// Deactivation hook.
register_deactivation_hook( __FILE__, array( 'SEOGenerator\Deactivation', 'deactivate' ) );

// Initialize the plugin.
add_action( 'plugins_loaded', array( 'SEOGenerator\Plugin', 'getInstance' ) );

// Load debug script (temporary - for troubleshooting image assignment).
if ( is_admin() ) {
	require_once SEO_GENERATOR_PLUGIN_DIR . 'debug-image-assignment.php';
	require_once SEO_GENERATOR_PLUGIN_DIR . 'check-field-groups.php';
	require_once SEO_GENERATOR_PLUGIN_DIR . 'check-acf-hooks.php';
}
