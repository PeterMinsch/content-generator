<?php
/**
 * Page Builder Admin Page
 *
 * Admin interface for building Next.js pages with drag-and-drop blocks.
 * Supports multiple pages via tabs (Homepage, About Us, etc.).
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Admin;

defined( 'ABSPATH' ) || exit;

use SEOGenerator\Services\NextJSPageGenerator;

class PageBuilderPage {

	/**
	 * Page generator service.
	 *
	 * @var NextJSPageGenerator
	 */
	private $generator;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->generator = new NextJSPageGenerator();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_ajax_nextjs_save_block_order', [ $this, 'ajaxSaveBlockOrder' ] );
		add_action( 'wp_ajax_nextjs_publish_page', [ $this, 'ajaxPublishPage' ] );
		add_action( 'wp_ajax_nextjs_get_block_order', [ $this, 'ajaxGetBlockOrder' ] );
		add_action( 'wp_ajax_nextjs_save_settings', [ $this, 'ajaxSaveSettings' ] );
	}

	/**
	 * Render the page builder admin page.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'seo-generator' ) );
		}

		$this->enqueueAssets();

		include SEO_GENERATOR_PLUGIN_DIR . 'templates/admin/page-builder.php';
	}

	/**
	 * Enqueue page builder CSS and JS.
	 *
	 * @return void
	 */
	private function enqueueAssets(): void {
		// Reuse existing sortable + preview styles.
		wp_enqueue_style(
			'seo-admin-import',
			SEO_GENERATOR_PLUGIN_URL . 'assets/css/admin-import.css',
			[ 'seo-generator-design-system' ],
			SEO_GENERATOR_VERSION
		);

		wp_enqueue_style(
			'seo-admin-block-preview',
			SEO_GENERATOR_PLUGIN_URL . 'assets/css/admin-block-preview.css',
			[ 'seo-admin-import' ],
			SEO_GENERATOR_VERSION
		);

		wp_enqueue_style(
			'seo-page-builder',
			SEO_GENERATOR_PLUGIN_URL . 'assets/css/admin-page-builder.css',
			[ 'seo-admin-block-preview' ],
			SEO_GENERATOR_VERSION
		);

		wp_enqueue_script(
			'sortablejs',
			'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js',
			[],
			'1.15.0',
			true
		);

		wp_enqueue_script(
			'seo-page-builder',
			SEO_GENERATOR_PLUGIN_URL . 'assets/js/page-builder.js',
			[ 'sortablejs' ],
			SEO_GENERATOR_VERSION,
			true
		);

		// Build per-page data for JavaScript.
		$pages_data = [];
		foreach ( $this->generator->getPages() as $slug => $page_config ) {
			$pages_data[ $slug ] = [
				'label'        => $page_config['label'],
				'previewRoute' => $page_config['preview_route'] ?? '/preview',
				'blocks'       => $this->getBlocksForJS( $slug ),
				'currentOrder' => $this->getSavedBlockOrder( $slug ),
				'defaultOrder' => $this->generator->getDefaultOrder( $slug ),
			];
		}

		wp_localize_script( 'seo-page-builder', 'nextjsPageBuilder', [
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'nextjs-page-builder' ),
			'previewBase' => get_option( 'seo_nextjs_preview_url', 'http://localhost:3000' ),
			'pages'       => $pages_data,
			'activePage'  => 'homepage',
		] );
	}

	/**
	 * Get block definitions formatted for JavaScript.
	 *
	 * @param string $page_slug The page slug.
	 * @return array
	 */
	private function getBlocksForJS( string $page_slug = 'homepage' ): array {
		$blocks = [];
		foreach ( $this->generator->getBlockDefinitions( $page_slug ) as $id => $block ) {
			$blocks[ $id ] = [
				'id'          => $id,
				'label'       => $block['label'],
				'description' => $block['description'],
			];
		}
		return $blocks;
	}

	/**
	 * Get the saved block order from WordPress options.
	 *
	 * @param string $page_slug The page slug.
	 * @return array
	 */
	private function getSavedBlockOrder( string $page_slug = 'homepage' ): array {
		$option_key = 'seo_nextjs_block_order_' . $page_slug;
		$saved      = get_option( $option_key, '' );

		if ( ! empty( $saved ) && is_string( $saved ) ) {
			$decoded = json_decode( $saved, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		return $this->generator->getDefaultOrder( $page_slug );
	}

	/**
	 * AJAX: Save block order to WordPress options.
	 *
	 * @return void
	 */
	public function ajaxSaveBlockOrder(): void {
		check_ajax_referer( 'nextjs-page-builder', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$page_slug   = isset( $_POST['page_slug'] ) ? sanitize_key( wp_unslash( $_POST['page_slug'] ) ) : 'homepage';
		$block_order = isset( $_POST['block_order'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['block_order'] ) ), true ) : [];

		if ( ! is_array( $block_order ) ) {
			wp_send_json_error( [ 'message' => 'Invalid block order data.' ] );
		}

		$valid_ids   = array_keys( $this->generator->getBlockDefinitions( $page_slug ) );
		$block_order = array_filter( $block_order, function( $id ) use ( $valid_ids ) {
			return in_array( $id, $valid_ids, true );
		} );

		$option_key = 'seo_nextjs_block_order_' . $page_slug;
		update_option( $option_key, wp_json_encode( array_values( $block_order ) ) );

		wp_send_json_success( [
			'message'     => 'Block order saved.',
			'block_order' => array_values( $block_order ),
		] );
	}

	/**
	 * AJAX: Publish — generate and write page.tsx.
	 *
	 * @return void
	 */
	public function ajaxPublishPage(): void {
		check_ajax_referer( 'nextjs-page-builder', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$page_slug   = isset( $_POST['page_slug'] ) ? sanitize_key( wp_unslash( $_POST['page_slug'] ) ) : 'homepage';
		$block_order = isset( $_POST['block_order'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['block_order'] ) ), true ) : [];

		if ( ! is_array( $block_order ) || empty( $block_order ) ) {
			wp_send_json_error( [ 'message' => 'Invalid or empty block order.' ] );
		}

		// Save the order.
		$option_key = 'seo_nextjs_block_order_' . $page_slug;
		update_option( $option_key, wp_json_encode( $block_order ) );

		// Publish.
		$result = $this->generator->publish( $block_order, $page_slug );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * AJAX: Save page builder settings.
	 *
	 * @return void
	 */
	public function ajaxSaveSettings(): void {
		check_ajax_referer( 'nextjs-page-builder', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$project_path = isset( $_POST['project_path'] ) ? sanitize_text_field( wp_unslash( $_POST['project_path'] ) ) : '';
		$preview_url  = isset( $_POST['preview_url'] ) ? esc_url_raw( wp_unslash( $_POST['preview_url'] ) ) : '';

		update_option( 'seo_nextjs_project_path', $project_path );
		update_option( 'seo_nextjs_preview_url', $preview_url );

		wp_send_json_success( [ 'message' => 'Settings saved.' ] );
	}

	/**
	 * AJAX: Get current block order.
	 *
	 * @return void
	 */
	public function ajaxGetBlockOrder(): void {
		check_ajax_referer( 'nextjs-page-builder', 'nonce' );

		$page_slug = isset( $_GET['page_slug'] ) ? sanitize_key( wp_unslash( $_GET['page_slug'] ) ) : 'homepage';

		wp_send_json_success( [
			'block_order' => $this->getSavedBlockOrder( $page_slug ),
			'blocks'      => $this->getBlocksForJS( $page_slug ),
		] );
	}
}
