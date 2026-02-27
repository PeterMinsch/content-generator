<?php
/**
 * Bulk Publish Admin Page
 *
 * Admin interface for CSV → AI slot content → dynamic page publishing.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Admin;

defined( 'ABSPATH' ) || exit;

use SEOGenerator\Services\BulkPublishService;
use SEOGenerator\Services\CSVParser;
use SEOGenerator\Services\GenerationQueue;
use SEOGenerator\Services\NextJSPageGenerator;
use SEOGenerator\Services\SlotContentGenerator;
use SEOGenerator\Services\OpenAIService;
use SEOGenerator\Services\SettingsService;

class BulkPublishPage {

	/**
	 * @var NextJSPageGenerator
	 */
	private NextJSPageGenerator $page_generator;

	public function __construct() {
		$this->page_generator = new NextJSPageGenerator();
	}

	/**
	 * Register AJAX handlers.
	 */
	public function register(): void {
		add_action( 'wp_ajax_bulk_publish_upload', [ $this, 'ajaxUpload' ] );
		add_action( 'wp_ajax_bulk_publish_process', [ $this, 'ajaxProcess' ] );
		add_action( 'wp_ajax_bulk_publish_status', [ $this, 'ajaxStatus' ] );
	}

	/**
	 * Render the admin page.
	 */
	public function render(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'seo-generator' ) );
		}

		$this->enqueueAssets();
		include SEO_GENERATOR_PLUGIN_DIR . 'templates/admin/bulk-publish.php';
	}

	/**
	 * Enqueue page-specific assets.
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
			[],
			SEO_GENERATOR_VERSION
		);

		wp_enqueue_script(
			'seo-bulk-publish',
			SEO_GENERATOR_PLUGIN_URL . 'assets/js/bulk-publish.js',
			[ 'jquery' ],
			SEO_GENERATOR_VERSION,
			true
		);

		// Load templates from DB first, fallback to config.
		$templates = [];

		$template_repo    = new \SEOGenerator\Repositories\TemplateRepository();
		$template_service = new \SEOGenerator\Services\TemplateService( $template_repo );
		$db_templates     = $template_service->getAll();

		if ( ! empty( $db_templates ) ) {
			foreach ( $db_templates as $t ) {
				$templates[ $t['slug'] ] = [
					'label'        => $t['name'],
					'defaultOrder' => $t['block_order'] ?? [],
				];
			}
		} else {
			// Fallback to config pages.
			foreach ( $this->page_generator->getPages() as $slug => $page ) {
				$templates[ $slug ] = [
					'label'        => $page['label'],
					'defaultOrder' => $page['default_order'] ?? [],
				];
			}
		}

		wp_localize_script( 'seo-bulk-publish', 'bulkPublishData', [
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'nonce'            => wp_create_nonce( 'bulk-publish' ),
			'pageTemplates'    => $templates,
			'dynamicSetupDone' => (bool) get_option( 'seo_nextjs_dynamic_setup_done', false ),
			'slotSchemas'      => $this->page_generator->getAllSlotSchemas(),
			'siteUrl'          => rtrim( get_option( 'seo_nextjs_preview_url', '' ), '/' ),
		] );
	}

	// ─── AJAX Handlers ────────────────────────────────────────────

	/**
	 * Handle CSV upload and return preview data.
	 */
	public function ajaxUpload(): void {
		check_ajax_referer( 'bulk-publish', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		if ( empty( $_FILES['csv_file'] ) ) {
			wp_send_json_error( [ 'message' => 'No file uploaded.' ] );
		}

		$file = $_FILES['csv_file'];

		// Validate file type.
		$mime = wp_check_filetype( $file['name'] );
		if ( ! in_array( $mime['ext'], [ 'csv', 'txt' ], true ) ) {
			wp_send_json_error( [ 'message' => 'Invalid file type. Please upload a CSV file.' ] );
		}

		// Move to current month's upload directory (CSVParser validates path is within it).
		$upload_dir = wp_upload_dir();
		$tmp_path   = $upload_dir['path'] . '/';

		if ( ! is_dir( $tmp_path ) ) {
			wp_mkdir_p( $tmp_path );
		}

		$dest = $tmp_path . 'bulk-publish-' . wp_generate_uuid4() . '.csv';

		if ( ! move_uploaded_file( $file['tmp_name'], $dest ) ) {
			wp_send_json_error( [ 'message' => 'Failed to save uploaded file.' ] );
		}

		// Parse CSV.
		$parser = new CSVParser();
		$result = $parser->parse( $dest );

		if ( is_wp_error( $result ) ) {
			@unlink( $dest );
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		// Store file path in transient for later processing.
		set_transient( 'bulk_publish_csv_path', $dest, HOUR_IN_SECONDS );

		wp_send_json_success( [
			'headers'  => $result['headers'],
			'rows'     => array_slice( $result['rows'], 0, 50 ), // Preview first 50.
			'metadata' => $result['metadata'],
			'filePath' => $dest,
		] );
	}

	/**
	 * Process bulk publish — either immediately or queued.
	 */
	public function ajaxProcess(): void {
		check_ajax_referer( 'bulk-publish', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		if ( ! get_option( 'seo_nextjs_dynamic_setup_done', false ) ) {
			wp_send_json_error( [ 'message' => 'Dynamic routing is not set up. Run Setup Dynamic Route first in Page Builder.' ] );
		}

		$mode           = sanitize_key( $_POST['mode'] ?? 'immediate' );
		$page_template  = sanitize_key( $_POST['page_template'] ?? 'homepage' );
		$column_mapping = isset( $_POST['column_mapping'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['column_mapping'] ) ), true ) : [];
		$file_path      = get_transient( 'bulk_publish_csv_path' );

		if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
			wp_send_json_error( [ 'message' => 'CSV file not found. Please upload again.' ] );
		}

		// Re-parse CSV.
		$parser = new CSVParser();
		$parsed = $parser->parse( $file_path );

		if ( is_wp_error( $parsed ) ) {
			wp_send_json_error( [ 'message' => $parsed->get_error_message() ] );
		}

		// Map columns to expected fields.
		$rows = $this->mapColumns( $parsed['rows'], $parsed['headers'], $column_mapping, $page_template );

		if ( empty( $rows ) ) {
			wp_send_json_error( [ 'message' => 'No valid rows found after column mapping.' ] );
		}

		// Build global context from business settings.
		$settings = get_option( 'seo_generator_settings', [] );
		$global_context = [];
		$business_fields = [
			'business_name', 'business_type', 'business_description',
			'business_address', 'service_area', 'business_phone',
			'business_email', 'business_url', 'years_in_business',
		];
		foreach ( $business_fields as $field ) {
			$global_context[ $field ] = $settings[ $field ] ?? '';
		}

		if ( 'immediate' === $mode ) {
			$this->processImmediate( $rows, $global_context );
		} else {
			$this->processQueued( $rows, $global_context );
		}
	}

	/**
	 * Process all rows immediately (synchronous AJAX, up to ~10 rows).
	 */
	private function processImmediate( array $rows, array $global_context ): void {
		$settings_service = new SettingsService();
		$openai_service   = new OpenAIService( $settings_service );
		$page_generator   = new NextJSPageGenerator();
		$slot_generator   = new SlotContentGenerator( $openai_service, $page_generator );
		$template_repo    = new \SEOGenerator\Repositories\TemplateRepository();
		$template_service = new \SEOGenerator\Services\TemplateService( $template_repo );
		$bulk_service     = new BulkPublishService( $slot_generator, $page_generator, $template_service );

		$results   = [];
		$succeeded = 0;
		$failed    = 0;

		foreach ( $rows as $row ) {
			$result = $bulk_service->processRow( $row, $global_context );
			$results[] = [
				'keyword' => $row['keyword'],
				'slug'    => $row['slug'] ?? sanitize_title( $row['keyword'] ),
				'success' => $result['success'],
				'message' => $result['message'],
			];

			if ( $result['success'] ) {
				$succeeded++;
			} else {
				$failed++;
			}
		}

		// Clean up CSV.
		$file_path = get_transient( 'bulk_publish_csv_path' );
		if ( $file_path && file_exists( $file_path ) ) {
			@unlink( $file_path );
		}
		delete_transient( 'bulk_publish_csv_path' );

		wp_send_json_success( [
			'message'   => sprintf( '%d pages published, %d failed.', $succeeded, $failed ),
			'results'   => $results,
			'succeeded' => $succeeded,
			'failed'    => $failed,
		] );
	}

	/**
	 * Queue rows for background processing.
	 */
	private function processQueued( array $rows, array $global_context ): void {
		$settings_service = new SettingsService();
		$openai_service   = new OpenAIService( $settings_service );
		$page_generator   = new NextJSPageGenerator();
		$slot_generator   = new SlotContentGenerator( $openai_service, $page_generator );
		$template_repo    = new \SEOGenerator\Repositories\TemplateRepository();
		$template_service = new \SEOGenerator\Services\TemplateService( $template_repo );
		$bulk_service     = new BulkPublishService( $slot_generator, $page_generator, $template_service );

		$result = $bulk_service->queueBatch( $rows, $global_context );

		wp_send_json_success( [
			'message' => sprintf( '%d pages queued for background processing.', $result['queued'] ),
			'queued'  => $result['queued'],
			'errors'  => $result['errors'],
		] );
	}

	/**
	 * Return current queue status.
	 */
	public function ajaxStatus(): void {
		check_ajax_referer( 'bulk-publish', 'nonce' );

		$queue = new GenerationQueue();
		$stats = $queue->getDynamicPublishStats();

		wp_send_json_success( [ 'stats' => $stats ] );
	}

	/**
	 * Map CSV columns to expected row format.
	 *
	 * @param array  $rows           Parsed CSV rows.
	 * @param array  $headers        CSV column headers.
	 * @param array  $column_mapping { expected_field => csv_column_index }
	 * @param string $page_template  Default page template.
	 * @return array Mapped rows.
	 */
	private function mapColumns( array $rows, array $headers, array $column_mapping, string $page_template ): array {
		$mapped = [];

		// If no explicit mapping, try to auto-detect.
		if ( empty( $column_mapping ) ) {
			$column_mapping = $this->autoDetectColumns( $headers );
		}

		foreach ( $rows as $row ) {
			$keyword = '';
			$slug    = '';
			$blocks  = '';

			// Extract keyword.
			if ( isset( $column_mapping['keyword'] ) && isset( $row[ $column_mapping['keyword'] ] ) ) {
				$keyword = trim( $row[ $column_mapping['keyword'] ] );
			} elseif ( isset( $row[0] ) ) {
				$keyword = trim( $row[0] ); // Fallback: first column.
			}

			// Extract slug.
			if ( isset( $column_mapping['slug'] ) && isset( $row[ $column_mapping['slug'] ] ) ) {
				$slug = trim( $row[ $column_mapping['slug'] ] );
			}

			// Extract blocks.
			if ( isset( $column_mapping['blocks'] ) && isset( $row[ $column_mapping['blocks'] ] ) ) {
				$blocks = trim( $row[ $column_mapping['blocks'] ] );
			}

			if ( empty( $keyword ) ) {
				continue;
			}

			$mapped[] = [
				'keyword'       => $keyword,
				'slug'          => $slug ?: sanitize_title( $keyword ),
				'page_template' => $page_template,
				'blocks'        => $blocks,
			];
		}

		return $mapped;
	}

	/**
	 * Auto-detect column mapping from header names.
	 *
	 * @param array $headers CSV headers.
	 * @return array { field => column_index }
	 */
	private function autoDetectColumns( array $headers ): array {
		$mapping    = [];
		$normalized = array_map( function ( $h ) {
			return strtolower( trim( $h ) );
		}, $headers );

		$keyword_names = [ 'keyword', 'focus_keyword', 'focus keyword', 'title', 'page_title', 'page title', 'keywords' ];
		$slug_names    = [ 'slug', 'url', 'path', 'page_slug', 'page slug' ];
		$blocks_names  = [ 'blocks', 'block_order', 'block order' ];

		foreach ( $normalized as $index => $header ) {
			if ( in_array( $header, $keyword_names, true ) && ! isset( $mapping['keyword'] ) ) {
				$mapping['keyword'] = $index;
			}
			if ( in_array( $header, $slug_names, true ) && ! isset( $mapping['slug'] ) ) {
				$mapping['slug'] = $index;
			}
			if ( in_array( $header, $blocks_names, true ) && ! isset( $mapping['blocks'] ) ) {
				$mapping['blocks'] = $index;
			}
		}

		return $mapping;
	}
}
