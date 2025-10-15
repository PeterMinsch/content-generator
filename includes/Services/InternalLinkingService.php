<?php
/**
 * Internal Linking Service
 *
 * Finds and stores related pages for automated internal linking.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Internal Linking Service
 *
 * Uses KeywordMatcher to find semantically related pages and stores
 * relationships in post meta for efficient retrieval.
 */
class InternalLinkingService {

	/**
	 * KeywordMatcher instance
	 *
	 * @var KeywordMatcher
	 */
	private $keyword_matcher;

	/**
	 * Maximum number of related links to store
	 *
	 * @var int
	 */
	private $max_links = 5;

	/**
	 * Minimum score threshold for related pages
	 *
	 * @var float
	 */
	private $min_score = 5.0;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->keyword_matcher = new KeywordMatcher();
	}

	/**
	 * Find related pages for a given post
	 *
	 * @param int $post_id Post ID to find related pages for.
	 * @return array Array of related page data sorted by score.
	 */
	public function findRelatedPages( int $post_id ): array {
		// Get source page data.
		$source_post = get_post( $post_id );
		if ( ! $source_post || 'seo-page' !== $source_post->post_type ) {
			error_log( "[Internal Linking] Post {$post_id} is not a valid seo-page" );
			return array();
		}

		// Get source focus keyword.
		$source_focus = get_field( 'seo_focus_keyword', $post_id );
		if ( empty( $source_focus ) ) {
			error_log( "[Internal Linking] Post {$post_id} has no focus keyword, cannot find related pages" );
			return array();
		}

		// Extract source keywords.
		$source_keywords = $this->keyword_matcher->extractKeywords( $source_focus );

		// Get source topic for filtering.
		$source_topics = get_the_terms( $post_id, 'seo-topic' );
		$source_topic  = is_array( $source_topics ) && ! empty( $source_topics ) ? $source_topics[0] : null;

		// Query candidate pages.
		$candidates = $this->queryCandidatePages( $post_id, $source_topic );

		if ( empty( $candidates ) ) {
			error_log( "[Internal Linking] No candidate pages found for post {$post_id}" );
			return array();
		}

		// Score each candidate.
		$scored = array();
		foreach ( $candidates as $candidate_id ) {
			$score_data = $this->keyword_matcher->scoreCandidatePage(
				$post_id,
				$candidate_id,
				$source_keywords,
				$source_focus,
				$source_topic
			);

			// Debug logging
			error_log( sprintf(
				'[Internal Linking] Page %d -> Candidate %d: Score %.1f (threshold: %.1f) - %s',
				$post_id,
				$candidate_id,
				$score_data['score'],
				$this->min_score,
				$score_data['score'] >= $this->min_score ? 'INCLUDED' : 'EXCLUDED'
			) );

			// Only include pages that meet minimum score threshold.
			if ( $score_data['score'] >= $this->min_score ) {
				$scored[] = $score_data;
			}
		}

		// Sort by score (highest first).
		usort(
			$scored,
			function ( $a, $b ) {
				return $b['score'] <=> $a['score'];
			}
		);

		// Limit to top N results.
		$scored = array_slice( $scored, 0, $this->max_links );

		error_log( "[Internal Linking] Found " . count( $scored ) . " related pages for post {$post_id}" );

		return $scored;
	}

	/**
	 * Query candidate pages for linking
	 *
	 * @param int    $exclude_id Post ID to exclude from results.
	 * @param object $topic      Topic term object for filtering (optional).
	 * @return array Array of post IDs.
	 */
	private function queryCandidatePages( int $exclude_id, $topic = null ): array {
		$args = array(
			'post_type'      => 'seo-page',
			'post_status'    => array( 'publish', 'pending', 'draft' ), // Include all statuses for testing.
			'posts_per_page' => 100, // Limit to prevent performance issues.
			'post__not_in'   => array( $exclude_id ),
			'fields'         => 'ids', // Only get IDs for efficiency.
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// Filter by same topic if available (performance optimization).
		if ( $topic ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'seo-topic',
					'field'    => 'term_id',
					'terms'    => $topic->term_id,
				),
			);
		}

		$query = new \WP_Query( $args );

		// If same-topic query returns too few results, expand to all topics.
		if ( $topic && $query->found_posts < 10 ) {
			error_log( "[Internal Linking] Only {$query->found_posts} pages in same topic, expanding search" );
			unset( $args['tax_query'] );
			$query = new \WP_Query( $args );
		}

		return $query->posts;
	}

	/**
	 * Store related links in post meta
	 *
	 * @param int   $post_id       Post ID to store links for.
	 * @param array $related_pages Array of related page data from findRelatedPages().
	 * @return void
	 */
	public function storeRelatedLinks( int $post_id, array $related_pages ): void {
		if ( empty( $related_pages ) ) {
			delete_post_meta( $post_id, '_related_links' );
			delete_post_meta( $post_id, '_related_links_timestamp' );
			error_log( "[Internal Linking] Cleared related links for post {$post_id} (no related pages found)" );
			return;
		}

		// Store data.
		$data = array(
			'links'     => $related_pages,
			'timestamp' => time(),
		);

		update_post_meta( $post_id, '_related_links', $data );
		update_post_meta( $post_id, '_related_links_timestamp', time() );

		error_log( "[Internal Linking] Stored " . count( $related_pages ) . " related links for post {$post_id}" );
	}

	/**
	 * Get stored related links for a post
	 *
	 * @param int $post_id Post ID to get links for.
	 * @return array|null Array of related link data or null if not found/expired.
	 */
	public function getRelatedLinks( int $post_id ): ?array {
		$data = get_post_meta( $post_id, '_related_links', true );

		if ( empty( $data ) || ! is_array( $data ) || ! isset( $data['links'] ) ) {
			return null;
		}

		// Check if data is stale (older than 7 days).
		$timestamp = isset( $data['timestamp'] ) ? $data['timestamp'] : 0;
		$age_days  = ( time() - $timestamp ) / DAY_IN_SECONDS;

		if ( $age_days > 7 ) {
			error_log( "[Internal Linking] Related links for post {$post_id} are stale ({$age_days} days old)" );
			return null;
		}

		// Verify linked pages still exist and are published.
		$valid_links = array();
		foreach ( $data['links'] as $link ) {
			$linked_post = get_post( $link['id'] );
			if ( $linked_post && 'publish' === $linked_post->post_status ) {
				$valid_links[] = $link;
			}
		}

		if ( empty( $valid_links ) ) {
			return null;
		}

		return $valid_links;
	}

	/**
	 * Refresh links for a specific post
	 *
	 * Finds and stores new related pages.
	 *
	 * @param int $post_id Post ID to refresh links for.
	 * @return void
	 */
	public function refreshLinks( int $post_id ): void {
		error_log( "[Internal Linking] Refreshing links for post {$post_id}" );

		$related = $this->findRelatedPages( $post_id );
		$this->storeRelatedLinks( $post_id, $related );
	}

	/**
	 * Refresh links for all published seo-pages
	 *
	 * Processes in batches to avoid memory issues.
	 *
	 * @return array Summary of refresh operation.
	 */
	public function refreshAllLinks(): array {
		error_log( '[Internal Linking] Starting refresh of all links' );

		$args = array(
			'post_type'      => 'seo-page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		$query = new \WP_Query( $args );
		$total = $query->found_posts;

		error_log( "[Internal Linking] Found {$total} pages to refresh" );

		$processed = 0;
		$errors    = 0;

		foreach ( $query->posts as $post_id ) {
			try {
				$this->refreshLinks( $post_id );
				$processed++;
			} catch ( \Exception $e ) {
				error_log( "[Internal Linking] Error refreshing post {$post_id}: " . $e->getMessage() );
				$errors++;
			}

			// Prevent memory issues on large sites.
			if ( 0 === $processed % 20 ) {
				error_log( "[Internal Linking] Progress: {$processed}/{$total} pages processed" );
			}
		}

		$summary = array(
			'total'     => $total,
			'processed' => $processed,
			'errors'    => $errors,
		);

		error_log( '[Internal Linking] Refresh complete: ' . wp_json_encode( $summary ) );

		return $summary;
	}

	/**
	 * Check if related links need refresh
	 *
	 * @param int $post_id Post ID to check.
	 * @return bool True if links need refresh.
	 */
	public function needsRefresh( int $post_id ): bool {
		$timestamp = get_post_meta( $post_id, '_related_links_timestamp', true );

		if ( empty( $timestamp ) ) {
			return true;
		}

		$age_days = ( time() - $timestamp ) / DAY_IN_SECONDS;

		return $age_days > 7;
	}
}
