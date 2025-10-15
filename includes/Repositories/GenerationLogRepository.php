<?php
/**
 * Generation Log Repository
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Repositories;

defined( 'ABSPATH' ) || exit;

/**
 * Repository for generation log database operations.
 */
class GenerationLogRepository {
	/**
	 * Table name.
	 *
	 * @var string
	 */
	private string $table_name;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'seo_generation_log';
	}

	/**
	 * Insert a new log entry.
	 *
	 * @param array $data Log data.
	 * @return int Insert ID, or 0 on failure.
	 */
	public function insert( array $data ): int {
		global $wpdb;

		$wpdb->insert(
			$this->table_name,
			$data,
			array(
				'%d', // post_id
				'%s', // block_type
				'%d', // prompt_tokens
				'%d', // completion_tokens
				'%d', // total_tokens
				'%f', // cost
				'%s', // model
				'%s', // status
				'%s', // error_message
				'%d', // user_id
				'%s', // created_at
			)
		);

		if ( ! empty( $wpdb->last_error ) ) {
			error_log( 'SEO Generator: Failed to insert log - ' . $wpdb->last_error );
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Get logs for a specific post.
	 *
	 * @param int $post_id Post ID.
	 * @return array Log entries.
	 */
	public function getByPostId( int $post_id ): array {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE post_id = %d ORDER BY created_at DESC",
				$post_id
			),
			ARRAY_A
		);

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Get logs for the current month.
	 *
	 * @return array Log entries.
	 */
	public function getCurrentMonthLogs(): array {
		global $wpdb;

		$start_date = gmdate( 'Y-m-01 00:00:00' );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE created_at >= %s ORDER BY created_at DESC",
				$start_date
			),
			ARRAY_A
		);

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Get total cost for the current month.
	 *
	 * @return float Total cost in USD.
	 */
	public function getCurrentMonthCost(): float {
		global $wpdb;

		$start_date = gmdate( 'Y-m-01 00:00:00' );

		$cost = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(cost) FROM {$this->table_name} WHERE created_at >= %s AND status = 'success'",
				$start_date
			)
		);

		return $cost ? (float) $cost : 0.0;
	}

	/**
	 * Get logs older than specified days.
	 *
	 * @param int $days Number of days.
	 * @return array Log entries.
	 */
	public function getOldLogs( int $days = 30 ): array {
		global $wpdb;

		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id FROM {$this->table_name} WHERE created_at < %s",
				$cutoff_date
			),
			ARRAY_A
		);

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Delete logs by IDs.
	 *
	 * @param array $ids Log IDs to delete.
	 * @return int Number of rows deleted.
	 */
	public function delete( array $ids ): int {
		global $wpdb;

		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table_name} WHERE id IN ($placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				...$ids
			)
		);

		return $deleted !== false ? (int) $deleted : 0;
	}

	/**
	 * Get logs by date range.
	 *
	 * @param string $start_date Start date (Y-m-d H:i:s format).
	 * @param string $end_date End date (Y-m-d H:i:s format).
	 * @return array Log entries.
	 */
	public function getByDateRange( string $start_date, string $end_date ): array {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE created_at BETWEEN %s AND %s ORDER BY created_at DESC",
				$start_date,
				$end_date
			),
			ARRAY_A
		);

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Get total statistics for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array Statistics array with totals.
	 */
	public function getPostStatistics( int $post_id ): array {
		global $wpdb;

		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COUNT(*) as total_generations,
					SUM(total_tokens) as total_tokens,
					SUM(cost) as total_cost,
					AVG(cost) as avg_cost
				FROM {$this->table_name}
				WHERE post_id = %d AND status = 'success'",
				$post_id
			),
			ARRAY_A
		);

		if ( ! $stats ) {
			return array(
				'total_generations' => 0,
				'total_tokens'      => 0,
				'total_cost'        => 0.0,
				'avg_cost'          => 0.0,
			);
		}

		return array(
			'total_generations' => (int) $stats['total_generations'],
			'total_tokens'      => (int) $stats['total_tokens'],
			'total_cost'        => (float) $stats['total_cost'],
			'avg_cost'          => (float) $stats['avg_cost'],
		);
	}
}
