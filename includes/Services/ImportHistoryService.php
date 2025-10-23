<?php
/**
 * Import History Service
 *
 * Manages import history tracking and logging.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Service for managing import history.
 */
class ImportHistoryService {
	/**
	 * Option name for storing import history.
	 */
	const HISTORY_OPTION = 'seo_import_history';

	/**
	 * Maximum age of history records in days.
	 */
	const MAX_HISTORY_AGE_DAYS = 90;

	/**
	 * Add a new import to the history.
	 *
	 * @param array $import_data Import data.
	 * @return string Import ID.
	 */
	public function addImport( array $import_data ): string {
		$history = $this->getHistory( 0, 0 ); // Get all history without pagination.

		// Generate unique ID.
		$import_id = time() . '_' . wp_generate_password( 8, false );

		// Prepare import entry.
		$import_entry = array(
			'id'            => $import_id,
			'user_id'       => isset( $import_data['user_id'] ) ? $import_data['user_id'] : get_current_user_id(),
			'timestamp'     => current_time( 'timestamp' ),
			'filename'      => isset( $import_data['filename'] ) ? $import_data['filename'] : '',
			'total_rows'    => isset( $import_data['total_rows'] ) ? (int) $import_data['total_rows'] : 0,
			'success_count' => isset( $import_data['success_count'] ) ? (int) $import_data['success_count'] : 0,
			'error_count'   => isset( $import_data['error_count'] ) ? (int) $import_data['error_count'] : 0,
			'skipped_count' => isset( $import_data['skipped_count'] ) ? (int) $import_data['skipped_count'] : 0,
			'import_type'   => isset( $import_data['import_type'] ) ? $import_data['import_type'] : 'csv_upload',
			'errors'        => isset( $import_data['errors'] ) && is_array( $import_data['errors'] ) ? $import_data['errors'] : array(),
			'logs'          => isset( $import_data['logs'] ) && is_array( $import_data['logs'] ) ? $import_data['logs'] : array(),
		);

		// Add to beginning of history array.
		array_unshift( $history, $import_entry );

		// Save to database.
		update_option( self::HISTORY_OPTION, $history, false );

		return $import_id;
	}

	/**
	 * Get import history with optional pagination.
	 *
	 * @param int $page  Page number (1-indexed). 0 for all.
	 * @param int $limit Items per page. 0 for all.
	 * @return array Array of import history entries.
	 */
	public function getHistory( int $page = 1, int $limit = 10 ): array {
		$history = get_option( self::HISTORY_OPTION, array() );

		if ( ! is_array( $history ) ) {
			return array();
		}

		// Return all if page/limit is 0.
		if ( $page === 0 || $limit === 0 ) {
			return $history;
		}

		// Calculate offset.
		$offset = ( $page - 1 ) * $limit;

		// Return paginated slice.
		return array_slice( $history, $offset, $limit );
	}

	/**
	 * Get total count of import history entries.
	 *
	 * @return int Total count.
	 */
	public function getHistoryCount(): int {
		$history = get_option( self::HISTORY_OPTION, array() );

		if ( ! is_array( $history ) ) {
			return 0;
		}

		return count( $history );
	}

	/**
	 * Get details for a specific import.
	 *
	 * @param string $import_id Import ID.
	 * @return array|null Import data or null if not found.
	 */
	public function getImportDetails( string $import_id ): ?array {
		$history = $this->getHistory( 0, 0 );

		foreach ( $history as $import ) {
			if ( isset( $import['id'] ) && $import['id'] === $import_id ) {
				return $import;
			}
		}

		return null;
	}

	/**
	 * Clean up imports older than MAX_HISTORY_AGE_DAYS.
	 *
	 * @return int Number of imports removed.
	 */
	public function cleanupOldImports(): int {
		$history = $this->getHistory( 0, 0 );
		$cutoff_timestamp = current_time( 'timestamp' ) - ( self::MAX_HISTORY_AGE_DAYS * DAY_IN_SECONDS );

		$initial_count = count( $history );

		// Filter out old imports.
		$history = array_filter(
			$history,
			function ( $import ) use ( $cutoff_timestamp ) {
				return isset( $import['timestamp'] ) && $import['timestamp'] >= $cutoff_timestamp;
			}
		);

		// Re-index array.
		$history = array_values( $history );

		// Save updated history.
		update_option( self::HISTORY_OPTION, $history, false );

		$removed_count = $initial_count - count( $history );

		if ( $removed_count > 0 ) {
			error_log( '[ImportHistory] Cleaned up ' . $removed_count . ' old import records' );
		}

		return $removed_count;
	}

	/**
	 * Delete all import history.
	 *
	 * @return bool True on success.
	 */
	public function clearHistory(): bool {
		return delete_option( self::HISTORY_OPTION );
	}

	/**
	 * Get formatted summary for an import.
	 *
	 * @param array $import Import data.
	 * @return string Formatted summary.
	 */
	public function getImportSummary( array $import ): string {
		$success = isset( $import['success_count'] ) ? $import['success_count'] : 0;
		$errors  = isset( $import['error_count'] ) ? $import['error_count'] : 0;
		$skipped = isset( $import['skipped_count'] ) ? $import['skipped_count'] : 0;
		$total   = isset( $import['total_rows'] ) ? $import['total_rows'] : 0;

		$parts = array();

		if ( $success > 0 ) {
			$parts[] = sprintf( '%d created', $success );
		}

		if ( $errors > 0 ) {
			$parts[] = sprintf( '%d errors', $errors );
		}

		if ( $skipped > 0 ) {
			$parts[] = sprintf( '%d skipped', $skipped );
		}

		if ( empty( $parts ) ) {
			return sprintf( '%d rows processed', $total );
		}

		return implode( ', ', $parts );
	}
}
