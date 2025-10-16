<?php
/**
 * Review Fetch Service with Automatic Caching and Refresh
 *
 * Orchestrates review fetching from Google Business Profile API with intelligent
 * caching to minimize API calls while keeping data reasonably fresh.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

use SEOGenerator\Repositories\ReviewRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Review Fetch Service
 *
 * High-level orchestrator that coordinates review fetching from Google API
 * with database caching. Implements cache-first strategy with graceful degradation.
 */
class ReviewFetchService {

	/**
	 * Google Business API integration service.
	 *
	 * @var GoogleBusinessService
	 */
	private $googleService;

	/**
	 * Review data repository.
	 *
	 * @var ReviewRepository
	 */
	private $repository;

	/**
	 * Request-scoped cache (prevents duplicate fetches in same request).
	 *
	 * @var array|null
	 */
	private $cached_reviews = null;

	/**
	 * Constructor
	 *
	 * @param GoogleBusinessService $googleService Google API integration.
	 * @param ReviewRepository      $repository Review data repository.
	 */
	public function __construct(
		GoogleBusinessService $googleService,
		ReviewRepository $repository
	) {
		$this->googleService = $googleService;
		$this->repository    = $repository;
	}

	/**
	 * Get reviews (from cache or fresh from API)
	 *
	 * Implements intelligent caching with 30-day cache lifetime.
	 * Returns cached reviews if fresh, fetches from API if stale.
	 * Falls back to stale cache if API fails.
	 *
	 * @param int  $limit Maximum number of reviews to return (default: 10).
	 * @param bool $force_refresh Force API fetch even if cache fresh (default: false).
	 * @return array Array of review data (empty if no reviews available).
	 */
	public function getReviews( int $limit = 10, bool $force_refresh = false ): array {
		// Return request-scoped cache if available.
		if ( $this->cached_reviews !== null ) {
			error_log( '📦 [Review Fetch] Using request-scoped cache (NO API CALL)' );
			return array_slice( $this->cached_reviews, 0, $limit );
		}

		// Check cache age.
		$cache_age = $this->repository->getCacheAge();
		error_log( sprintf( '[Review Fetch] Cache age: %d days', $cache_age ) );

		// Determine if refresh needed.
		$needs_refresh = $force_refresh || $this->repository->needsRefresh( 30 );

		if ( $force_refresh ) {
			error_log( '[Review Fetch] Force refresh requested' );
		}

		// Use cached reviews if fresh.
		if ( ! $needs_refresh ) {
			error_log( '✅ [Review Fetch] Using database cached reviews (NO API CALL)' );
			$reviews                = $this->repository->getTopRated( $limit );
			$this->cached_reviews   = $reviews;
			return $reviews;
		}

		// Fetch fresh reviews from API.
		error_log( '⚠️  [Review Fetch] Cache expired! Fetching from Apify API...' );

		$new_count = $this->fetchAndStoreReviews();

		// Graceful degradation: if fetch failed, use stale cache.
		if ( $new_count === 0 ) {
			error_log( '[Review Fetch] API fetch failed, checking for stale cache' );

			$stale_reviews = $this->repository->getAll( 1 );
			if ( ! empty( $stale_reviews ) ) {
				error_log( sprintf( '[Review Fetch] Using stale cache (%d days old)', $cache_age ) );
				$reviews              = $this->repository->getTopRated( $limit );
				$this->cached_reviews = $reviews;
				return $reviews;
			}

			error_log( '[Review Fetch] No cache available, returning empty array' );
			return array();
		}

		// Return fresh reviews.
		$reviews              = $this->repository->getTopRated( $limit );
		$this->cached_reviews = $reviews;

		return $reviews;
	}

	/**
	 * Fetch reviews from Google API and store in database
	 *
	 * Calls Google Business Service to fetch reviews, then saves each
	 * review to database. Tracks new vs duplicate reviews.
	 *
	 * @return int Number of new reviews saved (0 if fetch failed).
	 */
	private function fetchAndStoreReviews(): int {
		// Fetch from Google API.
		$reviews = $this->googleService->fetchReviews();

		if ( empty( $reviews ) ) {
			error_log( '[Review Fetch] Google API returned no reviews' );
			return 0;
		}

		// Save to database.
		$new_count       = 0;
		$duplicate_count = 0;

		foreach ( $reviews as $review ) {
			$result = $this->repository->save( $review );

			if ( $result > 0 ) {
				$new_count++;
			} else {
				$duplicate_count++;
			}
		}

		error_log(
			sprintf(
				'[Review Fetch] Saved %d new reviews, skipped %d duplicates',
				$new_count,
				$duplicate_count
			)
		);

		return $new_count;
	}
}
