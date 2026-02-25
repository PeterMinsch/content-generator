<?php
/**
 * Page Builder Admin Page
 *
 * Tabbed interface — one tab per page template (Homepage, About Us).
 * Shared block catalog — any block from any group can be added to any tab.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Admin;

defined( 'ABSPATH' ) || exit;

use SEOGenerator\Services\NextJSPageGenerator;

class PageBuilderPage {

	/**
	 * @var NextJSPageGenerator
	 */
	private $generator;

	public function __construct() {
		$this->generator = new NextJSPageGenerator();
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'wp_ajax_nextjs_save_block_order', [ $this, 'ajaxSaveBlockOrder' ] );
		add_action( 'wp_ajax_nextjs_publish_page', [ $this, 'ajaxPublishPage' ] );
		add_action( 'wp_ajax_nextjs_reset_order', [ $this, 'ajaxResetOrder' ] );
		add_action( 'wp_ajax_nextjs_save_settings', [ $this, 'ajaxSaveSettings' ] );
	}

	/**
	 * Render the admin page.
	 */
	public function render(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'seo-generator' ) );
		}

		$this->enqueueAssets();
		include SEO_GENERATOR_PLUGIN_DIR . 'templates/admin/page-builder.php';
	}

	/**
	 * Enqueue CSS + JS.
	 */
	private function enqueueAssets(): void {
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

		// Build block groups for JS (shared catalog).
		$groups_for_js = [];
		foreach ( $this->generator->getBlockGroups() as $group_id => $group ) {
			$blocks_js = [];
			foreach ( $group['blocks'] as $id => $block ) {
				$blocks_js[ $id ] = [
					'id'          => $id,
					'label'       => $block['label'],
					'description' => $block['description'],
				];
			}
			$groups_for_js[ $group_id ] = [
				'label'  => $group['label'],
				'blocks' => $blocks_js,
			];
		}

		// Build per-page data for JS.
		$pages_data = [];
		foreach ( $this->generator->getPages() as $slug => $page ) {
			$saved_order   = get_option( "seo_nextjs_block_order_{$slug}", null );
			$default_order = $page['default_order'] ?? [];
			$current_order = is_array( $saved_order ) ? $saved_order : $default_order;

			$pages_data[ $slug ] = [
				'label'        => $page['label'],
				'currentOrder' => $current_order,
				'defaultOrder' => $default_order,
				'previewRoute' => $page['preview_route'] ?? '/preview',
				'outputSlug'   => $this->generator->getSavedSlug( $slug ),
			];
		}

		wp_localize_script( 'seo-page-builder', 'nextjsPageBuilder', [
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'nextjs-page-builder' ),
			'previewBase'   => get_option( 'seo_nextjs_preview_url', 'http://contentgeneratorwpplugin.local:3000' ),
			'blockGroups'   => $groups_for_js,
			'pages'         => $pages_data,
			'projectPath'   => $this->generator->getProjectPath(),
			'reservedSlugs' => $this->generator->getReservedSlugs(),
		] );
	}

	// ─── AJAX Handlers ────────────────────────────────────────────

	/**
	 * Save block order (draft — no file writing).
	 * Accepts ANY block ID from the shared catalog.
	 */
	public function ajaxSaveBlockOrder(): void {
		check_ajax_referer( 'nextjs-page-builder', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$page_slug = isset( $_POST['page_slug'] ) ? sanitize_key( wp_unslash( $_POST['page_slug'] ) ) : '';
		$order     = isset( $_POST['block_order'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['block_order'] ) ), true ) : [];

		if ( ! $this->generator->getPageConfig( $page_slug ) ) {
			wp_send_json_error( [ 'message' => 'Unknown page.' ] );
		}

		// Validate block IDs against the ENTIRE shared catalog.
		$valid_ids = array_keys( $this->generator->getAllBlocks() );
		$order     = array_values( array_intersect( (array) $order, $valid_ids ) );

		update_option( "seo_nextjs_block_order_{$page_slug}", $order );

		wp_send_json_success( [
			'message'    => 'Block order saved.',
			'blockOrder' => $order,
		] );
	}

	/**
	 * Publish page to disk at the given output slug.
	 */
	public function ajaxPublishPage(): void {
		check_ajax_referer( 'nextjs-page-builder', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$page_slug   = isset( $_POST['page_slug'] ) ? sanitize_key( wp_unslash( $_POST['page_slug'] ) ) : '';
		$output_slug = isset( $_POST['output_slug'] ) ? sanitize_title( wp_unslash( $_POST['output_slug'] ) ) : '';
		$order       = isset( $_POST['block_order'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['block_order'] ) ), true ) : [];

		if ( ! $this->generator->getPageConfig( $page_slug ) ) {
			wp_send_json_error( [ 'message' => 'Unknown page.' ] );
		}

		// Validate against shared catalog.
		$valid_ids = array_keys( $this->generator->getAllBlocks() );
		$order     = array_values( array_intersect( (array) $order, $valid_ids ) );

		$result = $this->generator->publish( $page_slug, $order, $output_slug );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * Reset block order to defaults.
	 */
	public function ajaxResetOrder(): void {
		check_ajax_referer( 'nextjs-page-builder', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$page_slug = isset( $_POST['page_slug'] ) ? sanitize_key( wp_unslash( $_POST['page_slug'] ) ) : '';
		$defaults  = $this->generator->getDefaultOrder( $page_slug );

		if ( empty( $defaults ) ) {
			wp_send_json_error( [ 'message' => 'Unknown page.' ] );
		}

		update_option( "seo_nextjs_block_order_{$page_slug}", $defaults );

		wp_send_json_success( [
			'message'    => 'Order reset to defaults.',
			'blockOrder' => $defaults,
		] );
	}

	/**
	 * Save settings.
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
}
