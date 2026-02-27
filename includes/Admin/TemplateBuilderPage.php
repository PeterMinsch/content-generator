<?php
/**
 * Template Builder Admin Page
 *
 * 3-column interface: Block Library | Template Canvas | Preview.
 * Replaces the old 2-tab Page Builder with unlimited DB-backed templates.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Admin;

defined( 'ABSPATH' ) || exit;

use SEOGenerator\Services\TemplateService;
use SEOGenerator\Services\NextJSPageGenerator;
use SEOGenerator\Services\BlockRuleService;
use SEOGenerator\Repositories\BlockRuleProfileRepository;
use SEOGenerator\Repositories\TemplateBlockOverrideRepository;

class TemplateBuilderPage {

	private TemplateService $template_service;
	private NextJSPageGenerator $generator;

	public function __construct( TemplateService $template_service ) {
		$this->template_service = $template_service;
		$this->generator        = new NextJSPageGenerator();
	}

	public function register(): void {
		// Template CRUD.
		add_action( 'wp_ajax_tb_list_templates', [ $this, 'ajaxListTemplates' ] );
		add_action( 'wp_ajax_tb_get_template', [ $this, 'ajaxGetTemplate' ] );
		add_action( 'wp_ajax_tb_create_template', [ $this, 'ajaxCreateTemplate' ] );
		add_action( 'wp_ajax_tb_update_template', [ $this, 'ajaxUpdateTemplate' ] );
		add_action( 'wp_ajax_tb_delete_template', [ $this, 'ajaxDeleteTemplate' ] );
		add_action( 'wp_ajax_tb_clone_template', [ $this, 'ajaxCloneTemplate' ] );

		// Block order + publish.
		add_action( 'wp_ajax_tb_save_block_order', [ $this, 'ajaxSaveBlockOrder' ] );
		add_action( 'wp_ajax_tb_publish_page', [ $this, 'ajaxPublishPage' ] );

		// Settings + dynamic route (carried over from PageBuilder).
		add_action( 'wp_ajax_tb_save_settings', [ $this, 'ajaxSaveSettings' ] );
		add_action( 'wp_ajax_tb_setup_dynamic', [ $this, 'ajaxSetupDynamic' ] );

		// Block rule overrides (Stage 2).
		add_action( 'wp_ajax_tb_get_block_override', [ $this, 'ajaxGetBlockOverride' ] );
		add_action( 'wp_ajax_tb_save_block_override', [ $this, 'ajaxSaveBlockOverride' ] );
		add_action( 'wp_ajax_tb_delete_block_override', [ $this, 'ajaxDeleteBlockOverride' ] );
		add_action( 'wp_ajax_tb_get_block_resolved_rules', [ $this, 'ajaxGetBlockResolvedRules' ] );
	}

	private function getBlockRuleService(): BlockRuleService {
		$profile_repo  = new BlockRuleProfileRepository();
		$override_repo = new TemplateBlockOverrideRepository();
		return new BlockRuleService( $profile_repo, $override_repo );
	}

	public function render(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'seo-generator' ) );
		}

		$this->enqueueAssets();
		include SEO_GENERATOR_PLUGIN_DIR . 'templates/admin/template-builder.php';
	}

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
			'seo-template-builder',
			SEO_GENERATOR_PLUGIN_URL . 'assets/css/admin-template-builder.css',
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
			'seo-template-builder',
			SEO_GENERATOR_PLUGIN_URL . 'assets/js/template-builder.js',
			[ 'sortablejs' ],
			SEO_GENERATOR_VERSION,
			true
		);

		// Build block groups for JS.
		$groups_for_js = [];
		foreach ( $this->generator->getBlockGroups() as $group_id => $group ) {
			$blocks_js = [];
			foreach ( $group['blocks'] as $id => $block ) {
				$blocks_js[ $id ] = [
					'id'          => $id,
					'label'       => $block['label'],
					'description' => $block['description'],
					'group'       => $group_id,
				];
			}
			$groups_for_js[ $group_id ] = [
				'label'  => $group['label'],
				'blocks' => $blocks_js,
			];
		}

		// Get all templates from DB.
		$all_templates    = $this->template_service->getAll();
		$templates_for_js = [];
		foreach ( $all_templates as $t ) {
			$templates_for_js[ $t['id'] ] = $t;
		}

		$first_id = ! empty( $all_templates ) ? $all_templates[0]['id'] : null;

		wp_localize_script( 'seo-template-builder', 'templateBuilderData', [
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'nonce'            => wp_create_nonce( 'template-builder' ),
			'previewBase'      => get_option( 'seo_nextjs_preview_url', 'http://contentgeneratorwpplugin.local:3000' ),
			'blockGroups'      => $groups_for_js,
			'templates'        => $templates_for_js,
			'activeTemplateId' => $first_id,
			'projectPath'      => $this->generator->getProjectPath(),
			'reservedSlugs'    => $this->generator->getReservedSlugs(),
			'dynamicSetupDone' => (bool) get_option( 'seo_nextjs_dynamic_setup_done', false ),
			'slotSchemas'      => $this->generator->getAllSlotSchemas(),
			'categories'       => TemplateService::CATEGORIES,
		] );
	}

	// ─── Template CRUD AJAX ──────────────────────────────────────

	public function ajaxListTemplates(): void {
		check_ajax_referer( 'template-builder', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$status   = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$category = isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : '';

		wp_send_json_success( [ 'templates' => $this->template_service->getAll( $status, $category ) ] );
	}

	public function ajaxGetTemplate(): void {
		check_ajax_referer( 'template-builder', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$id       = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
		$template = $this->template_service->getById( $id );

		if ( ! $template ) {
			wp_send_json_error( [ 'message' => 'Template not found.' ] );
		}

		wp_send_json_success( [ 'template' => $template ] );
	}

	public function ajaxCreateTemplate(): void {
		check_ajax_referer( 'template-builder', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$data = [
			'name'        => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'slug'        => isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '',
			'category'    => isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : 'city',
			'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
			'block_order' => [],
			'status'      => 'draft',
		];

		if ( empty( $data['slug'] ) ) {
			$data['slug'] = $data['name'];
		}

		$result = $this->template_service->create( $data );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	public function ajaxUpdateTemplate(): void {
		check_ajax_referer( 'template-builder', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
		if ( ! $id ) {
			wp_send_json_error( [ 'message' => 'Invalid template ID.' ] );
		}

		$data = [];
		if ( isset( $_POST['name'] ) ) {
			$data['name'] = sanitize_text_field( wp_unslash( $_POST['name'] ) );
		}
		if ( isset( $_POST['slug'] ) ) {
			$data['slug'] = sanitize_title( wp_unslash( $_POST['slug'] ) );
		}
		if ( isset( $_POST['category'] ) ) {
			$data['category'] = sanitize_key( wp_unslash( $_POST['category'] ) );
		}
		if ( isset( $_POST['description'] ) ) {
			$data['description'] = sanitize_textarea_field( wp_unslash( $_POST['description'] ) );
		}
		if ( isset( $_POST['status'] ) ) {
			$data['status'] = sanitize_key( wp_unslash( $_POST['status'] ) );
		}
		if ( isset( $_POST['block_order'] ) ) {
			$data['block_order'] = json_decode( sanitize_text_field( wp_unslash( $_POST['block_order'] ) ), true );
		}
		if ( isset( $_POST['wrapper_config'] ) ) {
			$data['wrapper_config'] = json_decode( wp_unslash( $_POST['wrapper_config'] ), true );
		}
		if ( isset( $_POST['default_metadata'] ) ) {
			$data['default_metadata'] = json_decode( wp_unslash( $_POST['default_metadata'] ), true );
		}

		$result = $this->template_service->update( $id, $data );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	public function ajaxDeleteTemplate(): void {
		check_ajax_referer( 'template-builder', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$id     = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
		$result = $this->template_service->delete( $id );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	public function ajaxCloneTemplate(): void {
		check_ajax_referer( 'template-builder', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$id       = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
		$new_name = isset( $_POST['new_name'] ) ? sanitize_text_field( wp_unslash( $_POST['new_name'] ) ) : '';

		if ( empty( $new_name ) ) {
			$existing = $this->template_service->getById( $id );
			$new_name = $existing ? $existing['name'] . ' (Copy)' : 'Copy';
		}

		$result = $this->template_service->cloneTemplate( $id, $new_name );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	// ─── Block Order + Publish ───────────────────────────────────

	public function ajaxSaveBlockOrder(): void {
		check_ajax_referer( 'template-builder', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
		$order       = isset( $_POST['block_order'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['block_order'] ) ), true ) : [];

		if ( ! $template_id ) {
			wp_send_json_error( [ 'message' => 'Invalid template ID.' ] );
		}

		// Validate block IDs against the shared catalog.
		$valid_ids = array_keys( $this->generator->getAllBlocks() );
		$order     = array_values( array_intersect( (array) $order, $valid_ids ) );

		$result = $this->template_service->update( $template_id, [ 'block_order' => $order ] );

		if ( $result['success'] ) {
			wp_send_json_success( [
				'message'    => 'Block order saved.',
				'blockOrder' => $order,
				'template'   => $result['template'],
			] );
		} else {
			wp_send_json_error( $result );
		}
	}

	public function ajaxPublishPage(): void {
		check_ajax_referer( 'template-builder', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
		$output_slug = isset( $_POST['output_slug'] ) ? sanitize_title( wp_unslash( $_POST['output_slug'] ) ) : '';
		$order       = isset( $_POST['block_order'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['block_order'] ) ), true ) : [];

		$template = $this->template_service->getById( $template_id );
		if ( ! $template ) {
			wp_send_json_error( [ 'message' => 'Template not found.' ] );
		}

		// Validate against shared catalog.
		$valid_ids = array_keys( $this->generator->getAllBlocks() );
		$order     = array_values( array_intersect( (array) $order, $valid_ids ) );

		// Use template slug as the page_slug for the generator.
		$result = $this->generator->publish( $template['slug'], $order, $output_slug );

		if ( $result['success'] ) {
			// Also save block order to the template.
			$this->template_service->update( $template_id, [ 'block_order' => $order ] );
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	// ─── Settings / Dynamic Route ────────────────────────────────

	public function ajaxSaveSettings(): void {
		check_ajax_referer( 'template-builder', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$project_path = isset( $_POST['project_path'] ) ? sanitize_text_field( wp_unslash( $_POST['project_path'] ) ) : '';
		$preview_url  = isset( $_POST['preview_url'] ) ? esc_url_raw( wp_unslash( $_POST['preview_url'] ) ) : '';

		update_option( 'seo_nextjs_project_path', $project_path );
		update_option( 'seo_nextjs_preview_url', $preview_url );

		wp_send_json_success( [ 'message' => 'Settings saved.' ] );
	}

	public function ajaxSetupDynamic(): void {
		check_ajax_referer( 'template-builder', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$result = $this->generator->setupDynamicRoute();

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	// ─── Block Rule Override AJAX (Stage 2) ──────────────────────

	public function ajaxGetBlockOverride(): void {
		check_ajax_referer( 'template-builder', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
		$block_id    = isset( $_POST['block_id'] ) ? sanitize_text_field( wp_unslash( $_POST['block_id'] ) ) : '';

		if ( ! $template_id || empty( $block_id ) ) {
			wp_send_json_error( [ 'message' => 'Template ID and Block ID required.' ] );
		}

		$override_repo = new TemplateBlockOverrideRepository();
		$override      = $override_repo->findByTemplateAndBlock( $template_id, $block_id );

		wp_send_json_success( [
			'override'     => $override ? $override['override_json'] : null,
			'has_override' => ! empty( $override ),
		] );
	}

	public function ajaxSaveBlockOverride(): void {
		check_ajax_referer( 'template-builder', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$template_id   = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
		$block_id      = isset( $_POST['block_id'] ) ? sanitize_text_field( wp_unslash( $_POST['block_id'] ) ) : '';
		$override_raw  = isset( $_POST['override_json'] ) ? wp_unslash( $_POST['override_json'] ) : '';

		if ( ! $template_id || empty( $block_id ) ) {
			wp_send_json_error( [ 'message' => 'Template ID and Block ID required.' ] );
		}

		$override = json_decode( $override_raw, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( [ 'message' => 'Invalid JSON.' ] );
		}

		$override_repo = new TemplateBlockOverrideRepository();
		$user_id       = get_current_user_id() ?: 1;
		$result        = $override_repo->saveOverride( $template_id, $block_id, $override, $user_id );

		if ( $result ) {
			wp_send_json_success( [ 'message' => 'Override saved.' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Failed to save override.' ] );
		}
	}

	public function ajaxDeleteBlockOverride(): void {
		check_ajax_referer( 'template-builder', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
		$block_id    = isset( $_POST['block_id'] ) ? sanitize_text_field( wp_unslash( $_POST['block_id'] ) ) : '';

		if ( ! $template_id || empty( $block_id ) ) {
			wp_send_json_error( [ 'message' => 'Template ID and Block ID required.' ] );
		}

		$override_repo = new TemplateBlockOverrideRepository();
		$result        = $override_repo->deleteOverride( $template_id, $block_id );

		wp_send_json_success( [ 'message' => 'Override cleared.' ] );
	}

	public function ajaxGetBlockResolvedRules(): void {
		check_ajax_referer( 'template-builder', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
		$block_id    = isset( $_POST['block_id'] ) ? sanitize_text_field( wp_unslash( $_POST['block_id'] ) ) : '';

		if ( empty( $block_id ) ) {
			wp_send_json_error( [ 'message' => 'Block ID required.' ] );
		}

		$rule_service = $this->getBlockRuleService();
		$rules        = $rule_service->getResolvedRules( $block_id, $template_id ?: null );

		$override_repo = new TemplateBlockOverrideRepository();
		$has_override  = false;
		if ( $template_id ) {
			$override = $override_repo->findByTemplateAndBlock( $template_id, $block_id );
			$has_override = ! empty( $override );
		}

		wp_send_json_success( [
			'rules'        => $rules,
			'has_override' => $has_override,
		] );
	}
}
