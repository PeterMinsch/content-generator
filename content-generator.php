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

// ============================================================================
// ADMIN UI STYLES
// ============================================================================

/**
 * Enqueue macOS-inspired design system styles for admin pages.
 *
 * @since 1.0.0
 */
function seo_generator_enqueue_admin_styles( $hook ) {
	// Check if we're on a plugin page or SEO Pages list.
	$is_plugin_page = str_contains( $hook, 'seo-generator' ) || str_contains( $hook, 'seo-' );
	$screen = get_current_screen();
	$is_seo_page_list = $screen && $screen->post_type === 'seo-page';

	// Only load on our plugin pages.
	if ( ! $is_plugin_page && ! $is_seo_page_list ) {
		return;
	}

	// Enqueue design system CSS (custom properties, typography, utilities).
	wp_enqueue_style(
		'seo-generator-design-system',
		SEO_GENERATOR_PLUGIN_URL . 'assets/css/design-system.css',
		array(),
		SEO_GENERATOR_VERSION,
		'all'
	);

	// Enqueue animations CSS (keyframes, transitions, micro-interactions).
	wp_enqueue_style(
		'seo-generator-animations',
		SEO_GENERATOR_PLUGIN_URL . 'assets/css/animations.css',
		array( 'seo-generator-design-system' ),
		SEO_GENERATOR_VERSION,
		'all'
	);

	// Enqueue components CSS.
	if ( file_exists( SEO_GENERATOR_PLUGIN_DIR . 'assets/css/components.css' ) ) {
		wp_enqueue_style(
			'seo-generator-components',
			SEO_GENERATOR_PLUGIN_URL . 'assets/css/components.css',
			array( 'seo-generator-design-system', 'seo-generator-animations' ),
			SEO_GENERATOR_VERSION,
			'all'
		);
	}

	// Enqueue responsive CSS (mobile, tablet, desktop breakpoints).
	if ( file_exists( SEO_GENERATOR_PLUGIN_DIR . 'assets/css/responsive.css' ) ) {
		wp_enqueue_style(
			'seo-generator-responsive',
			SEO_GENERATOR_PLUGIN_URL . 'assets/css/responsive.css',
			array( 'seo-generator-components' ),
			SEO_GENERATOR_VERSION,
			'all'
		);
	}

	// Enqueue accessibility CSS (WCAG 2.1 AA compliance, focus management).
	if ( file_exists( SEO_GENERATOR_PLUGIN_DIR . 'assets/css/accessibility.css' ) ) {
		wp_enqueue_style(
			'seo-generator-accessibility',
			SEO_GENERATOR_PLUGIN_URL . 'assets/css/accessibility.css',
			array( 'seo-generator-responsive' ),
			SEO_GENERATOR_VERSION,
			'all'
		);
	}
}
add_action( 'admin_enqueue_scripts', 'seo_generator_enqueue_admin_styles' );

// ============================================================================
// END ADMIN UI STYLES
// ============================================================================

// ============================================================================
// ADMIN UI SCRIPTS
// ============================================================================

/**
 * Enqueue macOS-inspired UI scripts for admin pages.
 *
 * @since 1.0.0
 */
function seo_generator_enqueue_admin_scripts( $hook ) {
	// Check if we're on a plugin page or SEO Pages list.
	$is_plugin_page = str_contains( $hook, 'seo-generator' ) || str_contains( $hook, 'seo-' );
	$screen = get_current_screen();
	$is_seo_page_list = $screen && $screen->post_type === 'seo-page';

	// Only load on our plugin pages.
	if ( ! $is_plugin_page && ! $is_seo_page_list ) {
		return;
	}

	// Enqueue interactions JS (UI component behaviors).
	wp_enqueue_script(
		'seo-generator-interactions',
		SEO_GENERATOR_PLUGIN_URL . 'assets/js/interactions.js',
		array(),
		SEO_GENERATOR_VERSION,
		true
	);

	// Enqueue progress tracking JS (AI generation, file uploads).
	wp_enqueue_script(
		'seo-generator-progress-tracking',
		SEO_GENERATOR_PLUGIN_URL . 'assets/js/progress-tracking.js',
		array(),
		SEO_GENERATOR_VERSION,
		true
	);

	// Enqueue column mapping JS (CSV import).
	wp_enqueue_script(
		'seo-generator-column-mapping',
		SEO_GENERATOR_PLUGIN_URL . 'assets/js/build/column-mapping.js',
		array(),
		SEO_GENERATOR_VERSION,
		true
	);

	// Localize script with AJAX URL and nonce.
	wp_localize_script(
		'seo-generator-interactions',
		'seoGeneratorData',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'seo-generator-nonce' ),
		)
	);

	// Check for pre-loaded file from geographic title generator.
	$user_id       = get_current_user_id();
	$transient_key = 'import_file_' . $user_id;
	$preloaded_file = get_transient( $transient_key );

	// Localize column-mapping script with import-specific data.
	wp_localize_script(
		'seo-generator-column-mapping',
		'seoImportData',
		array(
			'nonce'          => wp_create_nonce( 'seo_csv_upload' ),
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'maxUploadSize'  => wp_max_upload_size(),
			'preloadedFile'  => $preloaded_file ? $preloaded_file : null,
		)
	);
}
add_action( 'admin_enqueue_scripts', 'seo_generator_enqueue_admin_scripts' );

// ============================================================================
// END ADMIN UI SCRIPTS
// ============================================================================

// ============================================================================
// FRONTEND STYLES
// ============================================================================

/**
 * Enqueue frontend styles for seo-page post type.
 *
 * @since 1.0.0
 */
function seo_generator_enqueue_frontend_styles() {
	// Only enqueue on single seo-page posts.
	if ( ! is_singular( 'seo-page' ) ) {
		return;
	}

	// TEMPORARY: Use unminified CSS because frontend.min.css is empty
	$suffix = '';

	wp_enqueue_style(
		'seo-generator-frontend',
		SEO_GENERATOR_PLUGIN_URL . "assets/css/frontend{$suffix}.css",
		array(),
		SEO_GENERATOR_VERSION . '-' . time(), // Force reload
		'all'
	);

	// Add inline test CSS to verify styles are loading
	$inline_css = '
		.seo-block--about-section {
			background-color: #FEF9F4 !important;
			padding: 4rem 1.5rem !important;
		}
		.about-section__heading {
			font-size: 2.5rem !important;
			color: #272521 !important;
			text-align: center !important;
		}
		.about-section__features {
			display: grid !important;
			grid-template-columns: repeat(4, 1fr) !important;
			gap: 2rem !important;
			margin-top: 3rem !important;
		}
	';
	wp_add_inline_style( 'seo-generator-frontend', $inline_css );
}
add_action( 'wp_enqueue_scripts', 'seo_generator_enqueue_frontend_styles', 99 );

// ============================================================================
// END FRONTEND STYLES
// ============================================================================

// Load debug script (temporary - for troubleshooting image assignment).
if ( is_admin() ) {
	require_once SEO_GENERATOR_PLUGIN_DIR . 'debug-image-assignment.php';
	require_once SEO_GENERATOR_PLUGIN_DIR . 'check-field-groups.php';
	require_once SEO_GENERATOR_PLUGIN_DIR . 'check-acf-hooks.php';
}
