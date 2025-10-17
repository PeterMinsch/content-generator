<?php
/**
 * Template Loader
 *
 * Handles loading custom templates for the plugin's custom post types.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Templates;

defined( 'ABSPATH' ) || exit;

/**
 * Loads custom templates for seo-page post type.
 */
class TemplateLoader {
	/**
	 * Register hooks for template loading.
	 *
	 * @return void
	 */
	public function register(): void {
		error_log( '[TemplateLoader] register() called' );
		add_filter( 'single_template', array( $this, 'loadSingleTemplate' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueueFrontendStyles' ) );
		error_log( '[TemplateLoader] Hooks registered: single_template filter and wp_enqueue_scripts action' );
	}

	/**
	 * Load custom template for single seo-page posts.
	 *
	 * @param string $template The path to the template being loaded.
	 * @return string Modified template path.
	 */
	public function loadSingleTemplate( string $template ): string {
		// Check if we're viewing a single seo-page post.
		if ( ! is_singular( 'seo-page' ) ) {
			return $template;
		}

		// Get the custom template path.
		$plugin_template = $this->getTemplatePath( 'single-seo-page.php' );

		// Use custom template if it exists.
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		// Fallback to default template.
		return $template;
	}

	/**
	 * Get the path to a template file.
	 *
	 * @param string $template_name Template filename.
	 * @return string Full path to template file.
	 */
	private function getTemplatePath( string $template_name ): string {
		return SEO_GENERATOR_PLUGIN_DIR . 'templates/frontend/' . $template_name;
	}

	/**
	 * Check if a template file exists.
	 *
	 * @param string $template_name Template filename.
	 * @return bool True if template exists, false otherwise.
	 */
	public function templateExists( string $template_name ): bool {
		$template_path = $this->getTemplatePath( $template_name );
		return file_exists( $template_path );
	}

	/**
	 * Enqueue frontend styles for seo-page post type.
	 *
	 * Uses minified CSS in production, unminified when SCRIPT_DEBUG is true.
	 *
	 * @return void
	 */
	public function enqueueFrontendStyles(): void {
		// Only enqueue on single seo-page posts.
		if ( ! is_singular( 'seo-page' ) ) {
			error_log( '[TemplateLoader] Not enqueueing frontend styles - not on seo-page' );
			return;
		}

		// Use minified CSS in production, unminified when debugging.
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		$css_url = plugins_url( "assets/css/frontend{$suffix}.css", SEO_GENERATOR_PLUGIN_FILE );
		$css_path = SEO_GENERATOR_PLUGIN_DIR . "assets/css/frontend{$suffix}.css";

		error_log( '[TemplateLoader] Enqueueing frontend styles' );
		error_log( '[TemplateLoader] CSS URL: ' . $css_url );
		error_log( '[TemplateLoader] CSS file exists: ' . ( file_exists( $css_path ) ? 'YES' : 'NO' ) );

		wp_enqueue_style(
			'seo-generator-frontend',
			$css_url,
			array(),
			SEO_GENERATOR_VERSION,
			'all'
		);

		error_log( '[TemplateLoader] Frontend styles enqueued successfully' );
	}
}
