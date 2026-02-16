<?php
/**
 * Apify Google Maps Review Integration
 *
 * Fetches reviews from Google Maps via Apify scraper.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Apify Google Maps Scraper integration
 *
 * Fetches review data from Google Maps using Apify's Google Maps Scraper actor.
 * Simpler setup than official Google API - just needs Apify API token and Place URL.
 */
class GoogleBusinessService {

	// ============================================================================
	// CONFIGURATION
	// ============================================================================

	/**
	 * Get business name from settings for logging/debugging.
	 *
	 * @return string Business name or 'Business'.
	 */
	private function getBusinessName(): string {
		$settings = get_option( 'seo_generator_settings', array() );
		return ! empty( $settings['business_name'] ) ? $settings['business_name'] : 'Business';
	}

	/**
	 * Apify Actor ID for Google Maps Scraper
	 * Using nwua9Gu5YrADL7ZDj - official Google Maps Scraper by Apify
	 * This is the most popular and reliable Google Maps scraper on Apify
	 */
	const APIFY_ACTOR_ID = 'nwua9Gu5YrADL7ZDj';

	// ============================================================================

	/**
	 * Apify API base URL
	 *
	 * @var string
	 */
	private const APIFY_API_BASE = 'https://api.apify.com/v2';

	/**
	 * Get Apify API token from settings (decrypted).
	 *
	 * @return string|null Decrypted API token or null if not configured.
	 */
	private function getApifyApiToken(): ?string {
		$settings = get_option( 'seo_generator_settings', array() );

		if ( empty( $settings['apify_api_token'] ) ) {
			error_log( '[Apify Reviews] Apify API token not configured in settings' );
			return null;
		}

		// Decrypt the token.
		$decrypted = seo_generator_decrypt_api_key( $settings['apify_api_token'] );

		if ( false === $decrypted || empty( $decrypted ) ) {
			error_log( '[Apify Reviews] Failed to decrypt Apify API token' );
			return null;
		}

		return $decrypted;
	}

	/**
	 * Get Google Maps Place URL from settings.
	 *
	 * @return string|null Place URL or null if not configured.
	 */
	private function getPlaceUrl(): ?string {
		$settings = get_option( 'seo_generator_settings', array() );

		if ( empty( $settings['place_url'] ) ) {
			error_log( '[Apify Reviews] Place URL not configured in settings' );
			return null;
		}

		return $settings['place_url'];
	}

	/**
	 * Get maximum reviews from settings.
	 *
	 * @return int Maximum number of reviews to fetch.
	 */
	private function getMaxReviews(): int {
		$settings = get_option( 'seo_generator_settings', array() );
		return isset( $settings['max_reviews'] ) ? intval( $settings['max_reviews'] ) : 50;
	}

	/**
	 * Fetch all reviews via Apify Google Maps Scraper
	 *
	 * @return array Array of normalized review data (empty if error).
	 */
	public function fetchReviews(): array {
		error_log( '========================================' );
		error_log( '🚨 APIFY API CALL BEING MADE NOW! 🚨' );
		error_log( '========================================' );
		error_log( '[Apify Reviews] Starting review fetch for ' . $this->getBusinessName() );

		// Step 1: Start the Apify actor run.
		$run_id = $this->startApifyRun();
		if ( ! $run_id ) {
			error_log( '[Apify Reviews] Failed to start actor run' );
			return array();
		}

		error_log( '[Apify Reviews] Actor run started: ' . $run_id );
		error_log( '⏳ Waiting for Apify scraper to complete...' );

		// Step 2: Wait for the run to complete (with timeout).
		$run_data = $this->waitForRunCompletion( $run_id, 120 ); // 2 minute timeout.
		if ( ! $run_data ) {
			error_log( '[Apify Reviews] Run did not complete in time or failed' );
			return array();
		}

		// Step 3: Fetch the results from the dataset.
		$reviews = $this->fetchRunResults( $run_data );
		if ( empty( $reviews ) ) {
			error_log( '[Apify Reviews] No reviews found in results' );
			return array();
		}

		// Normalize review data.
		$normalized = $this->normalizeReviews( $reviews );

		error_log( sprintf( '[Apify Reviews] Fetched %d reviews for %s', count( $normalized ), $this->getBusinessName() ) );
		error_log( '========================================' );
		error_log( '✅ APIFY API CALL COMPLETED' );
		error_log( '========================================' );

		return $normalized;
	}

	/**
	 * Start Apify actor run
	 *
	 * @return string|null Run ID or null on failure.
	 */
	private function startApifyRun(): ?string {
		// Get settings.
		$api_token = $this->getApifyApiToken();
		$place_url = $this->getPlaceUrl();
		$max_reviews = $this->getMaxReviews();

		// Validate required settings.
		if ( null === $api_token || null === $place_url ) {
			error_log( '[Apify Reviews] Missing required settings (API token or Place URL)' );
			return null;
		}

		$url = sprintf(
			'%s/acts/%s/runs?token=%s',
			self::APIFY_API_BASE,
			self::APIFY_ACTOR_ID,
			$api_token
		);

		// Build input for the actor.
		$input = array(
			'startUrls'        => array(
				array( 'url' => $place_url ),
			),
			'maxReviews'       => $max_reviews,
			'reviewsSort'      => 'newest', // Get newest reviews first.
			'language'         => 'en',
			'scrapeReviewerName' => true,
			'scrapeReviewerUrl' => false,
			'scrapeResponseFromOwnerText' => false,
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $input ),
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( '[Apify Reviews] HTTP error: ' . $response->get_error_message() );
			return null;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 201 ) {
			error_log( '[Apify Reviews] Failed to start run. Status: ' . $status_code );
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! isset( $data['data']['id'] ) ) {
			error_log( '[Apify Reviews] Run ID not found in response' );
			return null;
		}

		return $data['data']['id'];
	}

	/**
	 * Wait for Apify run to complete
	 *
	 * @param string $run_id Run ID.
	 * @param int    $timeout_seconds Maximum wait time in seconds.
	 * @return array|null Run data or null on failure/timeout.
	 */
	private function waitForRunCompletion( string $run_id, int $timeout_seconds = 120 ): ?array {
		$api_token = $this->getApifyApiToken();
		if ( null === $api_token ) {
			return null;
		}

		$url = sprintf(
			'%s/acts/%s/runs/%s?token=%s',
			self::APIFY_API_BASE,
			self::APIFY_ACTOR_ID,
			$run_id,
			$api_token
		);

		$start_time = time();
		$wait_interval = 5; // Check every 5 seconds.

		while ( ( time() - $start_time ) < $timeout_seconds ) {
			$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

			if ( is_wp_error( $response ) ) {
				error_log( '[Apify Reviews] Error checking run status: ' . $response->get_error_message() );
				return null;
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if ( ! isset( $data['data']['status'] ) ) {
				error_log( '[Apify Reviews] Invalid run status response' );
				return null;
			}

			$status = $data['data']['status'];

			// Check if run completed successfully.
			if ( 'SUCCEEDED' === $status ) {
				return $data['data'];
			}

			// Check if run failed.
			if ( in_array( $status, array( 'FAILED', 'ABORTED', 'TIMED-OUT' ), true ) ) {
				error_log( '[Apify Reviews] Run failed with status: ' . $status );
				return null;
			}

			// Still running, wait before next check.
			sleep( $wait_interval );
		}

		error_log( '[Apify Reviews] Run timed out after ' . $timeout_seconds . ' seconds' );
		return null;
	}

	/**
	 * Fetch results from Apify run dataset
	 *
	 * @param array $run_data Run data from waitForRunCompletion.
	 * @return array Array of reviews from dataset.
	 */
	private function fetchRunResults( array $run_data ): array {
		if ( ! isset( $run_data['defaultDatasetId'] ) ) {
			error_log( '[Apify Reviews] No dataset ID in run data' );
			return array();
		}

		$api_token = $this->getApifyApiToken();
		if ( null === $api_token ) {
			return array();
		}

		$dataset_id = $run_data['defaultDatasetId'];
		$url = sprintf(
			'%s/datasets/%s/items?token=%s',
			self::APIFY_API_BASE,
			$dataset_id,
			$api_token
		);

		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) ) {
			error_log( '[Apify Reviews] Error fetching results: ' . $response->get_error_message() );
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || empty( $data ) ) {
			return array();
		}

		// Extract reviews from the first item (place data).
		if ( isset( $data[0]['reviews'] ) && is_array( $data[0]['reviews'] ) ) {
			return $data[0]['reviews'];
		}

		return array();
	}

	/**
	 * Normalize Apify review data to plugin's standard format
	 *
	 * @param array $apify_reviews Raw reviews from Apify scraper.
	 * @return array Normalized review data.
	 */
	private function normalizeReviews( array $apify_reviews ): array {
		$normalized = array();

		foreach ( $apify_reviews as $review ) {
			// Generate a unique review ID from available data.
			$review_id = $this->generateReviewId( $review );

			$normalized[] = array(
				'source'               => 'google',
				'external_review_id'   => $review_id,
				'reviewer_name'        => $review['name'] ?? 'Anonymous',
				'reviewer_avatar_url'  => $review['reviewerPhotoUrl'] ?? '', // FIXED: was 'profilePhotoUrl'
				'reviewer_profile_url' => $review['reviewerUrl'] ?? '',
				'rating'               => (float) ( $review['stars'] ?? 5.0 ),
				'review_text'          => $review['text'] ?? '',
				'review_date'          => $this->formatReviewDate( $review['publishedAtDate'] ?? '' ),
			);
		}

		return $normalized;
	}

	/**
	 * Generate unique review ID from review data
	 *
	 * Apify doesn't provide a stable review ID, so we create one from
	 * reviewer name + date + first 20 chars of text.
	 *
	 * @param array $review Review data.
	 * @return string Unique review ID.
	 */
	private function generateReviewId( array $review ): string {
		$name = $review['name'] ?? 'anonymous';
		$date = $review['publishedAtDate'] ?? '';
		$text = $review['text'] ?? '';

		// Use first 20 chars of text.
		$text_snippet = substr( $text, 0, 20 );

		// Create hash to ensure uniqueness.
		return md5( $name . $date . $text_snippet );
	}

	/**
	 * Convert date string to MySQL datetime
	 *
	 * Handles various date formats from Apify (ISO 8601, relative dates, etc.)
	 *
	 * @param string $date_string Date in various formats.
	 * @return string Date in format "2025-09-20 14:30:00".
	 */
	private function formatReviewDate( string $date_string ): string {
		if ( empty( $date_string ) ) {
			return current_time( 'mysql' );
		}

		$timestamp = strtotime( $date_string );

		// If strtotime failed, return current time.
		if ( false === $timestamp ) {
			return current_time( 'mysql' );
		}

		return gmdate( 'Y-m-d H:i:s', $timestamp );
	}
}
