<?php
/**
 * CSV Import Page Manager
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Manages the CSV Import admin page functionality.
 */
class ImportPage {
	/**
	 * Register AJAX hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_ajax_seo_upload_csv', array( $this, 'handleUploadAjax' ) );
		add_action( 'wp_ajax_seo_get_column_mapping', array( $this, 'handleColumnMapping' ) );
		add_action( 'wp_ajax_seo_validate_mapping', array( $this, 'handleMappingValidation' ) );
		add_action( 'wp_ajax_seo_parse_csv', array( $this, 'handleCSVParsing' ) );
		add_action( 'wp_ajax_seo_import_batch', array( $this, 'handleBatchImport' ) );
		add_action( 'wp_ajax_seo_import_progress', array( $this, 'handleImportProgress' ) );
	}

	/**
	 * Render the import page.
	 *
	 * @return void
	 */
	public function render(): void {
		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'seo-generator' ) );
		}

		// Load template.
		$template_path = plugin_dir_path( dirname( __DIR__ ) ) . 'templates/admin/import.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			echo '<div class="wrap">';
			echo '<h1>' . esc_html__( 'Import Keywords', 'seo-generator' ) . '</h1>';
			echo '<p>' . esc_html__( 'Template file not found.', 'seo-generator' ) . '</p>';
			echo '</div>';
		}
	}

	/**
	 * Handle AJAX CSV file upload.
	 *
	 * @return void
	 */
	public function handleUploadAjax(): void {
		// Verify nonce.
		check_ajax_referer( 'seo_csv_upload', 'nonce' );

		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have sufficient permissions.', 'seo-generator' ) )
			);
		}

		// Check if file was uploaded.
		if ( ! isset( $_FILES['csv_file'] ) ) {
			wp_send_json_error(
				array( 'message' => __( 'No file was uploaded.', 'seo-generator' ) )
			);
		}

		// Check for upload errors.
		if ( $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json_error(
				array( 'message' => __( 'File upload error occurred.', 'seo-generator' ) )
			);
		}

		// Validate file extension.
		$filename  = sanitize_file_name( $_FILES['csv_file']['name'] );
		$extension = pathinfo( $filename, PATHINFO_EXTENSION );

		if ( strtolower( $extension ) !== 'csv' ) {
			wp_send_json_error(
				array( 'message' => __( 'Only .csv files are allowed.', 'seo-generator' ) )
			);
		}

		// Check file size.
		$max_size = wp_max_upload_size();
		if ( $_FILES['csv_file']['size'] > $max_size ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s: maximum file size */
						__( 'File size exceeds the maximum limit of %s.', 'seo-generator' ),
						size_format( $max_size )
					),
				)
			);
		}

		// Validate file type using WordPress function.
		$filetype = wp_check_filetype( $filename );
		if ( ! in_array( $filetype['ext'], array( 'csv' ), true ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid file type.', 'seo-generator' ) )
			);
		}

		// Move uploaded file to WordPress uploads directory.
		$upload_dir = wp_upload_dir();
		$temp_file  = $upload_dir['path'] . '/' . 'import_' . time() . '.csv';

		if ( ! move_uploaded_file( $_FILES['csv_file']['tmp_name'], $temp_file ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Failed to move uploaded file.', 'seo-generator' ) )
			);
		}

		// Store temp file path in transient for later processing.
		$user_id       = get_current_user_id();
		$transient_key = 'import_file_' . $user_id;
		set_transient( $transient_key, $temp_file, HOUR_IN_SECONDS );

		wp_send_json_success(
			array(
				'message'   => __( 'File uploaded successfully.', 'seo-generator' ),
				'file_path' => $temp_file,
			)
		);
	}

	/**
	 * Handle CSV file upload.
	 *
	 * @return array Upload result with success/error status.
	 */
	public function handleUpload(): array {
		// Verify nonce.
		check_admin_referer( 'seo_csv_upload', 'seo_csv_nonce' );

		// Check if file was uploaded.
		if ( ! isset( $_FILES['csv_file'] ) ) {
			return array(
				'success' => false,
				'error'   => __( 'No file was uploaded.', 'seo-generator' ),
			);
		}

		// Check for upload errors.
		if ( $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK ) {
			return array(
				'success' => false,
				'error'   => __( 'File upload error occurred.', 'seo-generator' ),
			);
		}

		// Validate file extension.
		$filename  = sanitize_file_name( $_FILES['csv_file']['name'] );
		$extension = pathinfo( $filename, PATHINFO_EXTENSION );

		if ( strtolower( $extension ) !== 'csv' ) {
			return array(
				'success' => false,
				'error'   => __( 'Only .csv files are allowed.', 'seo-generator' ),
			);
		}

		// Check file size.
		$max_size = wp_max_upload_size();
		if ( $_FILES['csv_file']['size'] > $max_size ) {
			return array(
				'success' => false,
				'error'   => sprintf(
					/* translators: %s: maximum file size */
					__( 'File size exceeds the maximum limit of %s.', 'seo-generator' ),
					size_format( $max_size )
				),
			);
		}

		// Validate file type using WordPress function.
		$filetype = wp_check_filetype( $filename );
		if ( ! in_array( $filetype['ext'], array( 'csv' ), true ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Invalid file type.', 'seo-generator' ),
			);
		}

		// Move uploaded file to WordPress uploads directory.
		$upload_dir = wp_upload_dir();
		$temp_file  = $upload_dir['path'] . '/' . 'import_' . time() . '.csv';

		if ( ! move_uploaded_file( $_FILES['csv_file']['tmp_name'], $temp_file ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Failed to move uploaded file.', 'seo-generator' ),
			);
		}

		// Store temp file path in transient for later processing.
		$user_id        = get_current_user_id();
		$transient_key  = 'import_file_' . $user_id;
		set_transient( $transient_key, $temp_file, HOUR_IN_SECONDS );

		return array(
			'success'   => true,
			'file_path' => $temp_file,
			'message'   => __( 'File uploaded successfully.', 'seo-generator' ),
		);
	}

	/**
	 * Parse CSV headers and preview rows.
	 *
	 * @param string $file_path Path to CSV file.
	 * @return array Array with headers and preview rows, or error.
	 */
	public function parseCSVHeaders( string $file_path ): array {
		// Check if file exists.
		if ( ! file_exists( $file_path ) ) {
			return array(
				'error' => __( 'CSV file not found.', 'seo-generator' ),
			);
		}

		// Check if file is readable.
		if ( ! is_readable( $file_path ) ) {
			return array(
				'error' => __( 'CSV file is not readable.', 'seo-generator' ),
			);
		}

		// Open file for reading.
		$file = fopen( $file_path, 'r' );
		if ( ! $file ) {
			return array(
				'error' => __( 'Could not open CSV file.', 'seo-generator' ),
			);
		}

		// Read header row.
		$headers = fgetcsv( $file );
		if ( $headers === false || empty( $headers ) ) {
			fclose( $file );
			return array(
				'error' => __( 'CSV file is empty or has no headers.', 'seo-generator' ),
			);
		}

		// Sanitize header values.
		$headers = array_map( 'sanitize_text_field', $headers );

		// Read preview rows (up to 3).
		$preview_rows = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$row = fgetcsv( $file );
			if ( $row === false ) {
				break; // End of file or error.
			}
			// Sanitize row data.
			$preview_rows[] = array_map( 'sanitize_text_field', $row );
		}

		// Close file handle.
		fclose( $file );

		return array(
			'headers'      => $headers,
			'preview_rows' => $preview_rows,
		);
	}

	/**
	 * Auto-detect column mappings based on header names.
	 *
	 * @param array $headers CSV column headers.
	 * @return array Mapping of column names to field names.
	 */
	public function detectColumnMappings( array $headers ): array {
		$mappings = array();

		// Define mapping rules (case-insensitive).
		$rules = array(
			'page_title'     => array( 'keyword', 'title', 'query' ),
			'focus_keyword'  => array( 'focus_keyword', 'search_query' ),
			'topic_category' => array( 'intent', 'category', 'topic' ),
			'image_url'      => array( 'image_url', 'image' ),
			'skip'           => array( 'search_volume', 'volume', 'searches' ),
		);

		foreach ( $headers as $header ) {
			$lower  = strtolower( trim( $header ) );
			$mapped = false;

			// Try to match against known patterns.
			foreach ( $rules as $field => $patterns ) {
				if ( in_array( $lower, $patterns, true ) ) {
					$mappings[ $header ] = $field;
					$mapped              = true;
					break;
				}
			}

			// Default unmapped columns to 'skip'.
			if ( ! $mapped ) {
				$mappings[ $header ] = 'skip';
			}
		}

		return $mappings;
	}

	/**
	 * Handle AJAX request for column mapping.
	 *
	 * @return void
	 */
	public function handleColumnMapping(): void {
		// Verify nonce.
		check_ajax_referer( 'seo_csv_upload', 'nonce' );

		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have sufficient permissions.', 'seo-generator' ) )
			);
		}

		// Get file path from transient.
		$user_id       = get_current_user_id();
		$transient_key = 'import_file_' . $user_id;
		$file_path     = get_transient( $transient_key );

		if ( ! $file_path ) {
			wp_send_json_error(
				array( 'message' => __( 'No uploaded file found. Please upload a CSV file first.', 'seo-generator' ) )
			);
		}

		// Parse CSV headers and preview rows.
		$parse_result = $this->parseCSVHeaders( $file_path );

		if ( isset( $parse_result['error'] ) ) {
			wp_send_json_error(
				array( 'message' => $parse_result['error'] )
			);
		}

		// Auto-detect column mappings.
		$headers  = $parse_result['headers'];
		$mappings = $this->detectColumnMappings( $headers );

		// Store mappings in transient.
		$mapping_key = 'import_mapping_' . $user_id;
		set_transient( $mapping_key, $mappings, HOUR_IN_SECONDS );

		// Send success response.
		wp_send_json_success(
			array(
				'headers'      => $headers,
				'mappings'     => $mappings,
				'preview_rows' => $parse_result['preview_rows'],
			)
		);
	}

	/**
	 * Validate column mapping configuration.
	 *
	 * @param array $mapping Column to field mapping.
	 * @return array Validation result with valid status and optional error message.
	 */
	public function validateMapping( array $mapping ): array {
		// Check if at least one column is mapped to 'page_title'.
		$has_page_title = false;

		foreach ( $mapping as $column => $field ) {
			if ( $field === 'page_title' ) {
				$has_page_title = true;
				break;
			}
		}

		if ( ! $has_page_title ) {
			return array(
				'valid' => false,
				'error' => __( 'At least one column must be mapped to "Page Title".', 'seo-generator' ),
			);
		}

		return array( 'valid' => true );
	}

	/**
	 * Handle AJAX request for mapping validation.
	 *
	 * @return void
	 */
	public function handleMappingValidation(): void {
		// Verify nonce.
		check_ajax_referer( 'seo_csv_upload', 'nonce' );

		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have sufficient permissions.', 'seo-generator' ) )
			);
		}

		// Get mappings from request.
		$mappings_json = isset( $_POST['mappings'] ) ? sanitize_text_field( wp_unslash( $_POST['mappings'] ) ) : '';
		$mappings      = json_decode( $mappings_json, true );

		if ( ! is_array( $mappings ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid mapping data.', 'seo-generator' ) )
			);
		}

		// Validate mappings.
		$validation = $this->validateMapping( $mappings );

		if ( ! $validation['valid'] ) {
			wp_send_json_error(
				array( 'message' => $validation['error'] )
			);
		}

		// Save validated mapping to transient.
		$user_id     = get_current_user_id();
		$mapping_key = 'import_mapping_' . $user_id;
		set_transient( $mapping_key, $mappings, HOUR_IN_SECONDS );

		// Get and save import options.
		$generation_mode  = isset( $_POST['generation_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['generation_mode'] ) ) : 'drafts_only';
		$check_duplicates = isset( $_POST['check_duplicates'] ) && $_POST['check_duplicates'] === '1';

		// Get blocks to generate (array of block type strings).
		$blocks_to_generate = null;
		if ( isset( $_POST['blocks_to_generate'] ) ) {
			$blocks_json = sanitize_text_field( wp_unslash( $_POST['blocks_to_generate'] ) );
			$blocks_array = json_decode( $blocks_json, true );
			if ( is_array( $blocks_array ) && ! empty( $blocks_array ) ) {
				$blocks_to_generate = $blocks_array;
			}
		}

		$import_options = array(
			'generation_mode'    => $generation_mode,
			'check_duplicates'   => $check_duplicates,
			'blocks_to_generate' => $blocks_to_generate,
		);

		set_transient( 'import_options_' . $user_id, $import_options, HOUR_IN_SECONDS );

		// Send success response.
		wp_send_json_success(
			array(
				'message'  => __( 'Mapping validated successfully.', 'seo-generator' ),
				'mappings' => $mappings,
				'options'  => $import_options,
			)
		);
	}

	/**
	 * Handle AJAX request for CSV parsing.
	 *
	 * Uses CSVParser service to parse and validate the uploaded CSV file.
	 *
	 * @return void
	 */
	public function handleCSVParsing(): void {
		// Verify nonce.
		check_ajax_referer( 'seo_csv_upload', 'nonce' );

		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have sufficient permissions.', 'seo-generator' ) )
			);
		}

		// Get file path and column mapping from transients.
		$user_id         = get_current_user_id();
		$file_path       = get_transient( 'import_file_' . $user_id );
		$column_mapping  = get_transient( 'import_mapping_' . $user_id );

		if ( ! $file_path ) {
			wp_send_json_error(
				array( 'message' => __( 'No uploaded file found. Please upload a CSV file first.', 'seo-generator' ) )
			);
		}

		if ( ! $column_mapping ) {
			wp_send_json_error(
				array( 'message' => __( 'No column mapping found. Please map columns first.', 'seo-generator' ) )
			);
		}

		// Parse CSV using CSVParser service.
		$parser = new \SEOGenerator\Services\CSVParser( array( 'max_rows' => 1000 ) );
		$result = $parser->parse( $file_path, $column_mapping );

		// Check if parsing failed.
		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array( 'message' => $result->get_error_message() )
			);
		}

		// Store parsed data in transient for batch processing (Story 6.4).
		set_transient( 'import_data_' . $user_id, $result, HOUR_IN_SECONDS );

		// Send success response with parsing results.
		wp_send_json_success(
			array(
				'message'  => __( 'CSV parsed successfully.', 'seo-generator' ),
				'metadata' => $result['metadata'],
				'errors'   => $result['errors'],
			)
		);
	}

	/**
	 * Handle AJAX request for batch import.
	 *
	 * Processes a single batch of CSV rows and creates posts.
	 *
	 * @return void
	 */
	public function handleBatchImport(): void {
		// Verify nonce.
		check_ajax_referer( 'seo_csv_upload', 'nonce' );

		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have sufficient permissions.', 'seo-generator' ) )
			);
		}

		// Get batch index.
		$batch_index = isset( $_POST['batch_index'] ) ? intval( $_POST['batch_index'] ) : 0;

		// Get data from transients.
		$user_id         = get_current_user_id();
		$parsed_data     = get_transient( 'import_data_' . $user_id );
		$column_mapping  = get_transient( 'import_mapping_' . $user_id );
		$import_options  = get_transient( 'import_options_' . $user_id );

		if ( ! $parsed_data || ! $column_mapping ) {
			wp_send_json_error(
				array( 'message' => __( 'Import data not found. Please start the import process again.', 'seo-generator' ) )
			);
		}

		// Get options (with defaults if not set).
		$generation_mode    = isset( $import_options['generation_mode'] ) ? $import_options['generation_mode'] : 'drafts_only';
		$check_duplicates   = isset( $import_options['check_duplicates'] ) ? $import_options['check_duplicates'] : true;
		$blocks_to_generate = isset( $import_options['blocks_to_generate'] ) ? $import_options['blocks_to_generate'] : null;

		// Split rows into batches.
		$batches = array_chunk( $parsed_data['rows'], 10 );

		if ( ! isset( $batches[ $batch_index ] ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid batch index.', 'seo-generator' ) )
			);
		}

		// Process batch using ImportService.
		$import_service = new \SEOGenerator\Services\ImportService(
			array(
				'batch_size'         => 10,
				'check_duplicates'   => $check_duplicates,
				'generation_mode'    => $generation_mode,
				'blocks_to_generate' => $blocks_to_generate,
			)
		);

		$batch_result = $import_service->processSingleBatch(
			$batches[ $batch_index ],
			$parsed_data['headers'],
			$column_mapping
		);

		// Update cumulative results.
		$cumulative_results = $this->updateImportResults( $user_id, $batch_result );

		// Update progress.
		$rows_processed = ( $batch_index + 1 ) * 10;
		$import_service->updateProgress(
			$batch_index + 1,
			count( $batches ),
			min( $rows_processed, count( $parsed_data['rows'] ) ),
			count( $parsed_data['rows'] )
		);

		// Check if complete.
		$completed = ( $batch_index + 1 ) >= count( $batches );

		// Send success response.
		wp_send_json_success(
			array(
				'batch_index'    => $batch_index,
				'total_batches'  => count( $batches ),
				'batch_result'   => $batch_result,
				'cumulative'     => $cumulative_results,
				'completed'      => $completed,
			)
		);
	}

	/**
	 * Handle AJAX request for import progress.
	 *
	 * Returns current progress of the import process.
	 *
	 * @return void
	 */
	public function handleImportProgress(): void {
		// Verify nonce.
		check_ajax_referer( 'seo_csv_upload', 'nonce' );

		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have sufficient permissions.', 'seo-generator' ) )
			);
		}

		$user_id  = get_current_user_id();
		$progress = get_transient( 'import_progress_' . $user_id );

		if ( ! $progress ) {
			wp_send_json_error(
				array( 'message' => __( 'No import in progress.', 'seo-generator' ) )
			);
		}

		wp_send_json_success( $progress );
	}

	/**
	 * Update cumulative import results.
	 *
	 * @param int   $user_id      User ID.
	 * @param array $batch_result Results from current batch.
	 * @return array Cumulative results.
	 */
	private function updateImportResults( int $user_id, array $batch_result ): array {
		$cumulative = get_transient( 'import_results_' . $user_id );

		if ( ! $cumulative ) {
			$cumulative = array(
				'created' => array(),
				'skipped' => array(),
				'errors'  => array(),
			);
		}

		// Merge batch results into cumulative.
		$cumulative['created'] = array_merge( $cumulative['created'], $batch_result['created'] );
		$cumulative['skipped'] = array_merge( $cumulative['skipped'], $batch_result['skipped'] );
		$cumulative['errors']  = array_merge( $cumulative['errors'], $batch_result['errors'] );

		// Store updated results.
		set_transient( 'import_results_' . $user_id, $cumulative, HOUR_IN_SECONDS );

		return $cumulative;
	}
}
