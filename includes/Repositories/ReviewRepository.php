<?php
/**
 * Review Repository
 *
 * Handles database operations for cached review data.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Repositories;

defined( 'ABSPATH' ) || exit;

/**
 * ReviewRepository Class
 *
 * Provides data access layer for review cache table.
 */
class ReviewRepository {
	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Review table name.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb       = $wpdb;
		$this->table_name = $wpdb->prefix . 'seo_reviews';
	}

	/**
	 * Get all reviews ordered by rating DESC
	 *
	 * @param int $limit Maximum number of reviews to return.
	 * @return array Associative arrays of review data.
	 */
	public function getAll( int $limit = 10 ): array {
		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$this->table_name} ORDER BY rating DESC, review_date DESC LIMIT %d",
			$limit
		);

		$results = $this->wpdb->get_results( $sql, ARRAY_A );
		return $results ?: array();
	}

	/**
	 * Get reviews with minimum rating
	 *
	 * @param float $min_rating Minimum rating (e.g., 4.0).
	 * @param int   $limit Maximum number of reviews.
	 * @return array Associative arrays of review data.
	 */
	public function getByRating( float $min_rating, int $limit = 10 ): array {
		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE rating >= %f ORDER BY rating DESC, review_date DESC LIMIT %d",
			$min_rating,
			$limit
		);

		$results = $this->wpdb->get_results( $sql, ARRAY_A );
		return $results ?: array();
	}

	/**
	 * Get top-rated reviews
	 *
	 * @param int $limit Number of top reviews to return.
	 * @return array Associative arrays of review data.
	 */
	public function getTopRated( int $limit = 5 ): array {
		return $this->getAll( $limit );
	}

	/**
	 * Save a new review to database (skip duplicates)
	 *
	 * @param array $review_data Review data (source, external_review_id, etc.).
	 * @return int Insert ID if successful, 0 if duplicate.
	 */
	public function save( array $review_data ): int {
		$result = $this->wpdb->insert(
			$this->table_name,
			array(
				'source'               => $review_data['source'],
				'external_review_id'   => $review_data['external_review_id'],
				'reviewer_name'        => $review_data['reviewer_name'] ?? null,
				'reviewer_avatar_url'  => $review_data['reviewer_avatar_url'] ?? null,
				'reviewer_profile_url' => $review_data['reviewer_profile_url'] ?? null,
				'rating'               => $review_data['rating'] ?? null,
				'review_text'          => $review_data['review_text'] ?? null,
				'review_date'          => $review_data['review_date'] ?? null,
				'last_fetched_at'      => current_time( 'mysql' ),
			),
			array(
				'%s', // source
				'%s', // external_review_id
				'%s', // reviewer_name
				'%s', // reviewer_avatar_url
				'%s', // reviewer_profile_url
				'%f', // rating
				'%s', // review_text
				'%s', // review_date
				'%s', // last_fetched_at
			)
		);

		// Return insert ID on success, 0 if duplicate (handled by UNIQUE constraint).
		return $result ? $this->wpdb->insert_id : 0;
	}

	/**
	 * Delete all reviews from cache
	 *
	 * @return int Number of rows deleted.
	 */
	public function deleteAll(): int {
		$this->wpdb->query( "DELETE FROM {$this->table_name}" );
		return $this->wpdb->rows_affected;
	}

	/**
	 * Get age of oldest review in cache (in days)
	 *
	 * @return int Number of days since oldest review fetched.
	 */
	public function getCacheAge(): int {
		$sql = "SELECT DATEDIFF(NOW(), MIN(last_fetched_at)) as age FROM {$this->table_name}";
		$age = $this->wpdb->get_var( $sql );
		return (int) ( $age ?: 0 );
	}

	/**
	 * Check if cache needs refresh
	 *
	 * @param int $max_days Maximum cache age in days (default: 30).
	 * @return bool True if cache is stale or empty.
	 */
	public function needsRefresh( int $max_days = 30 ): bool {
		$age = $this->getCacheAge();
		return $age === 0 || $age >= $max_days;
	}
}
