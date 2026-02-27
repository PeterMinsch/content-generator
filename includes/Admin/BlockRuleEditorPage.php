<?php
/**
 * Block Rule Editor Admin Page
 *
 * Two-panel interface for editing block rule profiles.
 * Left: block list with search. Right: rule editor with version history.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Admin;

defined( 'ABSPATH' ) || exit;

use SEOGenerator\Services\BlockRuleService;
use SEOGenerator\Services\NextJSPageGenerator;

class BlockRuleEditorPage {

	private BlockRuleService $rule_service;

	public function __construct( BlockRuleService $rule_service ) {
		$this->rule_service = $rule_service;
	}

	public function register(): void {
		add_action( 'wp_ajax_br_list_profiles', [ $this, 'ajaxListProfiles' ] );
		add_action( 'wp_ajax_br_get_profile', [ $this, 'ajaxGetProfile' ] );
		add_action( 'wp_ajax_br_save_profile', [ $this, 'ajaxSaveProfile' ] );
		add_action( 'wp_ajax_br_revert_profile', [ $this, 'ajaxRevertProfile' ] );
		add_action( 'wp_ajax_br_reset_to_factory', [ $this, 'ajaxResetToFactory' ] );
		add_action( 'wp_ajax_br_get_version_history', [ $this, 'ajaxGetVersionHistory' ] );
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'seo-generator' ) );
		}

		$this->enqueueAssets();
		include SEO_GENERATOR_PLUGIN_DIR . 'templates/admin/block-rule-editor.php';
	}

	private function enqueueAssets(): void {
		wp_enqueue_style(
			'seo-admin-block-rule-editor',
			SEO_GENERATOR_PLUGIN_URL . 'assets/css/admin-block-rule-editor.css',
			[ 'seo-generator-design-system' ],
			SEO_GENERATOR_VERSION
		);

		wp_enqueue_script(
			'seo-block-rule-editor',
			SEO_GENERATOR_PLUGIN_URL . 'assets/js/block-rule-editor.js',
			[],
			SEO_GENERATOR_VERSION,
			true
		);

		$generator = new NextJSPageGenerator();

		// Build block list with group info.
		$blocks_for_js = [];
		foreach ( $generator->getBlockGroups() as $group_id => $group ) {
			foreach ( $group['blocks'] as $block_id => $block ) {
				$slot_count = count( $block['content_slots'] ?? [] );
				$blocks_for_js[ $block_id ] = [
					'id'          => $block_id,
					'label'       => $block['label'],
					'description' => $block['description'],
					'group'       => $group_id,
					'group_label' => $group['label'],
					'slot_count'  => $slot_count,
				];
			}
		}

		// Get all current profiles to show edited/version badges.
		$profiles = $this->rule_service->getAllCurrentProfiles();
		$profile_map = [];
		foreach ( $profiles as $p ) {
			$profile_map[ $p['block_id'] ] = [
				'version' => $p['version'],
				'source'  => $p['source'],
			];
		}

		wp_localize_script( 'seo-block-rule-editor', 'blockRuleEditorData', [
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'block-rule-editor' ),
			'blocks'     => $blocks_for_js,
			'profiles'   => $profile_map,
		] );
	}

	// ─── AJAX Endpoints ──────────────────────────────────────────

	public function ajaxListProfiles(): void {
		check_ajax_referer( 'block-rule-editor', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$profiles = $this->rule_service->getAllCurrentProfiles();
		$map = [];
		foreach ( $profiles as $p ) {
			$map[ $p['block_id'] ] = [
				'version' => $p['version'],
				'source'  => $p['source'],
			];
		}

		wp_send_json_success( [ 'profiles' => $map ] );
	}

	public function ajaxGetProfile(): void {
		check_ajax_referer( 'block-rule-editor', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$block_id = isset( $_POST['block_id'] ) ? sanitize_text_field( wp_unslash( $_POST['block_id'] ) ) : '';
		if ( empty( $block_id ) ) {
			wp_send_json_error( [ 'message' => 'Block ID is required.' ] );
		}

		$rules = $this->rule_service->getResolvedRules( $block_id );

		wp_send_json_success( [
			'block_id' => $block_id,
			'rules'    => $rules,
		] );
	}

	public function ajaxSaveProfile(): void {
		check_ajax_referer( 'block-rule-editor', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$block_id    = isset( $_POST['block_id'] ) ? sanitize_text_field( wp_unslash( $_POST['block_id'] ) ) : '';
		$schema_raw  = isset( $_POST['schema_json'] ) ? wp_unslash( $_POST['schema_json'] ) : '';

		if ( empty( $block_id ) ) {
			wp_send_json_error( [ 'message' => 'Block ID is required.' ] );
		}

		$schema = json_decode( $schema_raw, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( [ 'message' => 'Invalid JSON schema.' ] );
		}

		$result = $this->rule_service->updateProfile( $block_id, $schema );

		if ( false === $result ) {
			wp_send_json_error( [ 'message' => 'Failed to save profile.' ] );
		}

		wp_send_json_success( [
			'message' => 'Profile saved (new version created).',
			'rules'   => $this->rule_service->getResolvedRules( $block_id ),
		] );
	}

	public function ajaxRevertProfile(): void {
		check_ajax_referer( 'block-rule-editor', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$block_id = isset( $_POST['block_id'] ) ? sanitize_text_field( wp_unslash( $_POST['block_id'] ) ) : '';
		$version  = isset( $_POST['version'] ) ? absint( $_POST['version'] ) : 0;

		if ( empty( $block_id ) || ! $version ) {
			wp_send_json_error( [ 'message' => 'Block ID and version are required.' ] );
		}

		$success = $this->rule_service->revertProfile( $block_id, $version );

		if ( ! $success ) {
			wp_send_json_error( [ 'message' => 'Failed to revert.' ] );
		}

		wp_send_json_success( [
			'message' => "Reverted to version {$version}.",
			'rules'   => $this->rule_service->getResolvedRules( $block_id ),
		] );
	}

	public function ajaxResetToFactory(): void {
		check_ajax_referer( 'block-rule-editor', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$block_id = isset( $_POST['block_id'] ) ? sanitize_text_field( wp_unslash( $_POST['block_id'] ) ) : '';

		if ( empty( $block_id ) ) {
			wp_send_json_error( [ 'message' => 'Block ID is required.' ] );
		}

		$success = $this->rule_service->resetToFactory( $block_id );

		if ( ! $success ) {
			wp_send_json_error( [ 'message' => 'Failed to reset.' ] );
		}

		wp_send_json_success( [
			'message' => 'Reset to factory defaults.',
			'rules'   => $this->rule_service->getResolvedRules( $block_id ),
		] );
	}

	public function ajaxGetVersionHistory(): void {
		check_ajax_referer( 'block-rule-editor', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		$block_id = isset( $_POST['block_id'] ) ? sanitize_text_field( wp_unslash( $_POST['block_id'] ) ) : '';

		if ( empty( $block_id ) ) {
			wp_send_json_error( [ 'message' => 'Block ID is required.' ] );
		}

		$versions = $this->rule_service->getVersionHistory( $block_id );

		// Strip schema_json from version list to keep payload small.
		$summary = array_map( function( $v ) {
			return [
				'version'    => $v['version'],
				'is_current' => $v['is_current'],
				'source'     => $v['source'],
				'notes'      => $v['notes'] ?? '',
				'created_at' => $v['created_at'],
			];
		}, $versions );

		wp_send_json_success( [ 'versions' => $summary ] );
	}
}
