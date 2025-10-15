<?php
/**
 * CSV Parser Service
 *
 * Robust CSV parsing with encoding detection, delimiter auto-detection,
 * and comprehensive error handling.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

/**
 * CSV Parser Service
 *
 * Handles parsing of CSV files with support for:
 * - Multiple encodings (UTF-8, ISO-8859-1)
 * - Multiple delimiters (comma, semicolon, tab)
 * - BOM removal
 * - Row validation
 * - Error collection
 *
 * Usage:
 * ```php
 * $parser = new CSVParser(['max_rows' => 1000]);
 * $result = $parser->parse($file_path, $column_mapping);
 *
 * if (is_wp_error($result)) {
 *     // Handle error
 *     echo $result->get_error_message();
 * } else {
 *     // Process parsed data
 *     $headers = $result['headers'];
 *     $rows = $result['rows'];
 *     $errors = $result['errors'];
 * }
 * ```
 */
class CSVParser {
	/**
	 * Maximum rows per import.
	 *
	 * @var int
	 */
	private $max_rows = 1000;

	/**
	 * Allowed encodings.
	 *
	 * @var array
	 */
	private $allowed_encodings = array( 'UTF-8', 'ISO-8859-1' );

	/**
	 * Allowed delimiters.
	 *
	 * @var array
	 */
	private $allowed_delimiters = array( ',', ';', "\t" );

	/**
	 * Collected errors during parsing.
	 *
	 * @var array
	 */
	private $errors = array();

	/**
	 * Constructor.
	 *
	 * @param array $options Optional configuration options.
	 *                       - max_rows: Maximum rows to parse (default: 1000)
	 */
	public function __construct( array $options = array() ) {
		$this->max_rows = isset( $options['max_rows'] ) ? (int) $options['max_rows'] : 1000;
	}

	/**
	 * Parse CSV file.
	 *
	 * @param string $file_path    Absolute path to CSV file.
	 * @param array  $column_mapping Column to field mapping from Story 6.2.
	 * @return array|\WP_Error Parsed data or WP_Error on failure.
	 */
	public function parse( string $file_path, array $column_mapping = array() ) {
		// Reset errors for this parse operation.
		$this->errors = array();

		// Validate file.
		$validation = $this->validate_file( $file_path );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Detect encoding.
		$encoding = $this->detect_encoding( $file_path );

		// Convert encoding if necessary.
		$working_file = $file_path;
		if ( $encoding !== 'UTF-8' ) {
			$working_file = $this->convert_encoding( $file_path, $encoding );
			if ( is_wp_error( $working_file ) ) {
				return $working_file;
			}
		}

		// Open file for parsing.
		$file = fopen( $working_file, 'r' );
		if ( ! $file ) {
			return new \WP_Error(
				'file_open_failed',
				__( 'Could not open CSV file for reading.', 'seo-generator' )
			);
		}

		// Detect delimiter.
		$delimiter = $this->detect_delimiter( $file );

		// Remove BOM if present and read headers.
		$first_line = fgets( $file );
		if ( substr( $first_line, 0, 3 ) === "\xEF\xBB\xBF" ) {
			$first_line = substr( $first_line, 3 );
		}

		// Parse headers.
		$headers = str_getcsv( $first_line, $delimiter );
		$headers = array_map( 'trim', $headers );

		// Parse data rows.
		$rows        = array();
		$row_number  = 2; // Start at 2 (1 is header row).
		$total_rows  = 0;
		$valid_rows  = 0;

		while ( ( $row = fgetcsv( $file, 0, $delimiter ) ) !== false ) {
			$total_rows++;

			// Skip empty rows.
			if ( $this->is_empty_row( $row ) ) {
				continue;
			}

			// Check row limit.
			if ( $total_rows > $this->max_rows ) {
				fclose( $file );

				// Clean up converted file if it exists.
				if ( $working_file !== $file_path && file_exists( $working_file ) ) {
					unlink( $working_file );
				}

				return new \WP_Error(
					'row_limit_exceeded',
					sprintf(
						/* translators: %d: maximum row limit */
						__( 'CSV file exceeds the maximum limit of %d rows.', 'seo-generator' ),
						$this->max_rows
					)
				);
			}

			// Trim whitespace from all fields.
			$row = array_map( 'trim', $row );

			// Validate row if column mapping provided.
			if ( ! empty( $column_mapping ) ) {
				$row_errors = $this->validate_row( $row, $row_number, $headers, $column_mapping );
				if ( ! empty( $row_errors ) ) {
					$this->errors = array_merge( $this->errors, $row_errors );
					$row_number++;
					continue; // Skip invalid row but continue parsing.
				}
			}

			$rows[] = $row;
			$valid_rows++;
			$row_number++;
		}

		fclose( $file );

		// Clean up converted file if it exists.
		if ( $working_file !== $file_path && file_exists( $working_file ) ) {
			unlink( $working_file );
		}

		// Check minimum rows requirement.
		if ( $valid_rows === 0 ) {
			return new \WP_Error(
				'no_valid_rows',
				__( 'CSV file has no valid data rows.', 'seo-generator' )
			);
		}

		// Log errors if any.
		if ( ! empty( $this->errors ) ) {
			error_log( 'CSV Parser Errors: ' . implode( ', ', $this->errors ) );
		}

		// Build result structure.
		return array(
			'headers'  => $headers,
			'rows'     => $rows,
			'errors'   => $this->errors,
			'metadata' => array(
				'total_rows'   => $total_rows,
				'valid_rows'   => $valid_rows,
				'invalid_rows' => count( $this->errors ),
				'encoding'     => $encoding,
				'delimiter'    => $delimiter === "\t" ? 'tab' : $delimiter,
			),
		);
	}

	/**
	 * Validate file exists and is readable.
	 *
	 * @param string $file_path Path to file.
	 * @return bool|\WP_Error True if valid, WP_Error otherwise.
	 */
	private function validate_file( string $file_path ) {
		// Check file exists.
		if ( ! file_exists( $file_path ) ) {
			return new \WP_Error(
				'file_not_found',
				__( 'CSV file not found.', 'seo-generator' )
			);
		}

		// Check file is readable.
		if ( ! is_readable( $file_path ) ) {
			return new \WP_Error(
				'file_not_readable',
				__( 'CSV file is not readable.', 'seo-generator' )
			);
		}

		// Check file is not empty.
		if ( filesize( $file_path ) === 0 ) {
			return new \WP_Error(
				'file_empty',
				__( 'CSV file is empty.', 'seo-generator' )
			);
		}

		// Validate file is within upload directory (security).
		$upload_dir      = wp_upload_dir();
		$normalized_path = wp_normalize_path( $file_path );
		$upload_path     = wp_normalize_path( $upload_dir['path'] );

		if ( strpos( $normalized_path, $upload_path ) !== 0 ) {
			return new \WP_Error(
				'invalid_file_path',
				__( 'Invalid file path.', 'seo-generator' )
			);
		}

		// Prevent directory traversal.
		if ( strpos( $file_path, '..' ) !== false ) {
			return new \WP_Error(
				'invalid_path',
				__( 'Invalid file path detected.', 'seo-generator' )
			);
		}

		return true;
	}

	/**
	 * Detect file encoding.
	 *
	 * @param string $file_path Path to file.
	 * @return string Detected encoding (UTF-8 or ISO-8859-1).
	 */
	private function detect_encoding( string $file_path ): string {
		// Read first 10KB for encoding detection.
		$sample = file_get_contents( $file_path, false, null, 0, 10000 );

		$encoding = mb_detect_encoding( $sample, $this->allowed_encodings, true );

		return $encoding ? $encoding : 'UTF-8';
	}

	/**
	 * Convert file encoding to UTF-8.
	 *
	 * @param string $file_path    Original file path.
	 * @param string $from_encoding Source encoding.
	 * @return string|\WP_Error Path to converted file or WP_Error.
	 */
	private function convert_encoding( string $file_path, string $from_encoding ) {
		$content = file_get_contents( $file_path );
		if ( $content === false ) {
			return new \WP_Error(
				'encoding_read_failed',
				__( 'Failed to read file for encoding conversion.', 'seo-generator' )
			);
		}

		$converted = mb_convert_encoding( $content, 'UTF-8', $from_encoding );

		// Write to temporary file.
		$temp_file = wp_tempnam( 'import_converted_' );
		$result    = file_put_contents( $temp_file, $converted );

		if ( $result === false ) {
			return new \WP_Error(
				'encoding_write_failed',
				__( 'Failed to write converted file.', 'seo-generator' )
			);
		}

		return $temp_file;
	}

	/**
	 * Detect CSV delimiter.
	 *
	 * @param resource $file_handle Open file handle.
	 * @return string Detected delimiter.
	 */
	private function detect_delimiter( $file_handle ): string {
		$first_line = fgets( $file_handle );
		rewind( $file_handle ); // Reset file pointer.

		$counts = array();

		foreach ( $this->allowed_delimiters as $delimiter ) {
			$counts[ $delimiter ] = substr_count( $first_line, $delimiter );
		}

		// Return delimiter with highest count.
		$max_delimiter = array_search( max( $counts ), $counts, true );

		return $max_delimiter !== false ? $max_delimiter : ',';
	}

	/**
	 * Check if row is empty.
	 *
	 * @param array $row CSV row.
	 * @return bool True if all values are empty.
	 */
	private function is_empty_row( array $row ): bool {
		foreach ( $row as $value ) {
			if ( trim( $value ) !== '' ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate a data row.
	 *
	 * @param array  $row            CSV row data.
	 * @param int    $row_number     Row number for error messages.
	 * @param array  $headers        CSV headers.
	 * @param array  $column_mapping Column to field mapping.
	 * @return array Array of error messages.
	 */
	private function validate_row( array $row, int $row_number, array $headers, array $column_mapping ): array {
		$errors = array();

		// Find the column that maps to 'page_title' (required).
		$page_title_column = null;
		foreach ( $column_mapping as $column => $field ) {
			if ( $field === 'page_title' ) {
				$page_title_column = $column;
				break;
			}
		}

		// If page_title mapping exists, validate it has a value.
		if ( $page_title_column !== null ) {
			$column_index = array_search( $page_title_column, $headers, true );

			if ( $column_index === false || ! isset( $row[ $column_index ] ) || trim( $row[ $column_index ] ) === '' ) {
				$errors[] = sprintf(
					/* translators: %d: row number */
					__( 'Row %d: Missing required value for page title.', 'seo-generator' ),
					$row_number
				);
			}
		}

		return $errors;
	}
}
