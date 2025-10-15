<?php
/**
 * Import Log Repository
 *
 * Handles data persistence for import history logs.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Repositories;

defined( 'ABSPATH' ) || exit;

/**
 * Import Log Repository
 *
 * Manages import log records in custom database table.
 * Provides methods for saving, retrieving, and cleaning up import logs.
 *
 * Database Table: wp_seo_import_log
 * - id: Primary key
 * - timestamp: Import date/time
 * - filename: CSV filename
 * - total_rows: Total rows processed
 * - success_count: Successfully imported posts
 * - error_count: Failed imports
 * - user_id: WordPress user who performed import
 * - error_log: Serialized array of error messages (TEXT)
 * - created_posts: Serialized array of created post IDs/titles (TEXT)
 * - image_stats: Serialized array of image download statistics (TEXT)
 *
 * Usage:
 * ```php
 * $repository = new ImportLogRepository();
 *
 * // Save new import log
 * $log_id = $repository->save([
 *     'filename' => 'keywords.csv',
 *     'total_rows' => 100,
 *     'success_count' => 98,
 *     'error_count' => 2,
 *     'user_id' => get_current_user_id(),
 *     'error_log' => ['Row 5: Missing title', 'Row 10: Invalid URL'],
 *     'created_posts' => [['id' => 123, 'title' => 'Post Title']],
 *     'image_stats' => ['downloaded' => 95, 'failed' => 3],
 * ]);
 *
 * // Get all logs with pagination
 * $logs = $repository->findAll(10, 0); // 10 per page, offset 0
 *
 * // Get single log by ID
 * $log = $repository->findById($log_id);
 *
 * // Delete old logs
 * $deleted = $repository->deleteOlderThan(90); // Delete logs older than 90 days
 * ```
 */
class ImportLogRepository {
	/**
	 * Table name (without prefix).
	 *
	 * @var string
	 */
	private const TABLE_NAME = 'seo_import_log';

	/**
	 * Database version for migrations.
	 *
	 * @var string
	 */
	private const DB_VERSION = '1.0';

	/**
	 * Option name for storing database version.
	 *
	 * @var string
	 */
	private const DB_VERSION_OPTION = 'seo_import_log_db_version';

	/**
	 * Get table name with WordPress prefix.
	 *
	 * @return string Full table name with prefix.
	 */
	private function getTableName(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Create database table.
	 *
	 * Called on plugin activation or when table doesn't exist.
	 * Uses dbDelta for safe table creation/updates.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function createTable(): bool {
		global $wpdb;

		$table_name      = $this->getTableName();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			filename VARCHAR(255) NOT NULL,
			total_rows INT UNSIGNED NOT NULL DEFAULT 0,
			success_count INT UNSIGNED NOT NULL DEFAULT 0,
			error_count INT UNSIGNED NOT NULL DEFAULT 0,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			error_log TEXT NULL,
			created_posts TEXT NULL,
			image_stats TEXT NULL,
			PRIMARY KEY (id),
			INDEX idx_timestamp (timestamp),
			INDEX idx_user_id (user_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Update database version.
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );

		// Verify table was created.
		return $this->tableExists();
	}

	/**
	 * Check if table exists.
	 *
	 * @return bool True if table exists, false otherwise.
	 */
	public function tableExists(): bool {
		global $wpdb;
		$table_name = $this->getTableName();
		$result     = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
		return $result === $table_name;
	}

	/**
	 * Save import log.
	 *
	 * @param array $log_data Import log data.
	 *                        Required keys: filename, total_rows, success_count, error_count, user_id
	 *                        Optional keys: error_log (array), created_posts (array), image_stats (array)
	 * @return int|false Import log ID on success, false on failure.
	 */
	public function save( array $log_data ) {
		global $wpdb;

		// Ensure table exists.
		if ( ! $this->tableExists() ) {
			$this->createTable();
		}

		// Serialize arrays for storage.
		$error_log     = isset( $log_data['error_log'] ) ? maybe_serialize( $log_data['error_log'] ) : null;
		$created_posts = isset( $log_data['created_posts'] ) ? maybe_serialize( $log_data['created_posts'] ) : null;
		$image_stats   = isset( $log_data['image_stats'] ) ? maybe_serialize( $log_data['image_stats'] ) : null;

		$inserted = $wpdb->insert(
			$this->getTableName(),
			array(
				'timestamp'      => current_time( 'mysql' ),
				'filename'       => sanitize_text_field( $log_data['filename'] ),
				'total_rows'     => absint( $log_data['total_rows'] ),
				'success_count'  => absint( $log_data['success_count'] ),
				'error_count'    => absint( $log_data['error_count'] ),
				'user_id'        => absint( $log_data['user_id'] ),
				'error_log'      => $error_log,
				'created_posts'  => $created_posts,
				'image_stats'    => $image_stats,
			),
			array(
				'%s', // timestamp.
				'%s', // filename.
				'%d', // total_rows.
				'%d', // success_count.
				'%d', // error_count.
				'%d', // user_id.
				'%s', // error_log.
				'%s', // created_posts.
				'%s', // image_stats.
			)
		);

		if ( false === $inserted ) {
			error_log( 'Failed to save import log: ' . $wpdb->last_error );
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Find all import logs with pagination.
	 *
	 * @param int $limit  Number of logs to retrieve (default 10).
	 * @param int $offset Offset for pagination (default 0).
	 * @return array Array of import log records.
	 */
	public function findAll( int $limit = 10, int $offset = 0 ): array {
		global $wpdb;

		// Ensure table exists.
		if ( ! $this->tableExists() ) {
			return array();
		}

		$table_name = $this->getTableName();

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d OFFSET %d",
				$limit,
				$offset
			),
			ARRAY_A
		);

		if ( ! $results ) {
			return array();
		}

		// Unserialize arrays.
		return array_map( array( $this, 'unserializeLogData' ), $results );
	}

	/**
	 * Find import log by ID.
	 *
	 * @param int $id Import log ID.
	 * @return array|null Import log data or null if not found.
	 */
	public function findById( int $id ): ?array {
		global $wpdb;

		// Ensure table exists.
		if ( ! $this->tableExists() ) {
			return null;
		}

		$table_name = $this->getTableName();

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE id = %d",
				$id
			),
			ARRAY_A
		);

		if ( ! $result ) {
			return null;
		}

		return $this->unserializeLogData( $result );
	}

	/**
	 * Get total count of import logs.
	 *
	 * @return int Total number of import logs.
	 */
	public function count(): int {
		global $wpdb;

		// Ensure table exists.
		if ( ! $this->tableExists() ) {
			return 0;
		}

		$table_name = $this->getTableName();

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
	}

	/**
	 * Delete import logs older than specified days.
	 *
	 * @param int $days Number of days (default 90).
	 * @return int Number of rows deleted.
	 */
	public function deleteOlderThan( int $days = 90 ): int {
		global $wpdb;

		// Ensure table exists.
		if ( ! $this->tableExists() ) {
			return 0;
		}

		$table_name = $this->getTableName();
		$cutoff     = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $table_name WHERE timestamp < %s",
				$cutoff
			)
		);

		return $deleted ? (int) $deleted : 0;
	}

	/**
	 * Unserialize log data arrays.
	 *
	 * @param array $log_data Raw log data from database.
	 * @return array Log data with unserialized arrays.
	 */
	private function unserializeLogData( array $log_data ): array {
		if ( isset( $log_data['error_log'] ) && ! empty( $log_data['error_log'] ) ) {
			$log_data['error_log'] = maybe_unserialize( $log_data['error_log'] );
		} else {
			$log_data['error_log'] = array();
		}

		if ( isset( $log_data['created_posts'] ) && ! empty( $log_data['created_posts'] ) ) {
			$log_data['created_posts'] = maybe_unserialize( $log_data['created_posts'] );
		} else {
			$log_data['created_posts'] = array();
		}

		if ( isset( $log_data['image_stats'] ) && ! empty( $log_data['image_stats'] ) ) {
			$log_data['image_stats'] = maybe_unserialize( $log_data['image_stats'] );
		} else {
			$log_data['image_stats'] = array();
		}

		return $log_data;
	}

	/**
	 * Delete all import logs.
	 *
	 * WARNING: This will delete ALL import history.
	 * Use with caution - primarily for testing.
	 *
	 * @return int Number of rows deleted.
	 */
	public function deleteAll(): int {
		global $wpdb;

		// Ensure table exists.
		if ( ! $this->tableExists() ) {
			return 0;
		}

		$table_name = $this->getTableName();
		$deleted    = $wpdb->query( "DELETE FROM $table_name" );

		return $deleted ? (int) $deleted : 0;
	}

	/**
	 * Drop the import log table.
	 *
	 * WARNING: This will permanently delete the table and all data.
	 * Use with caution - primarily for plugin uninstall.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function dropTable(): bool {
		global $wpdb;

		$table_name = $this->getTableName();
		$result     = $wpdb->query( "DROP TABLE IF EXISTS $table_name" );

		// Delete version option.
		delete_option( self::DB_VERSION_OPTION );

		return false !== $result;
	}
}
