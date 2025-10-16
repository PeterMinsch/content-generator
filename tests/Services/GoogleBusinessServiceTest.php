<?php
/**
 * GoogleBusinessService (Apify) Tests
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Services;

use SEOGenerator\Services\GoogleBusinessService;
use WP_UnitTestCase;

/**
 * Test GoogleBusinessService with Apify integration
 */
class GoogleBusinessServiceTest extends WP_UnitTestCase {

	/**
	 * Service instance.
	 *
	 * @var GoogleBusinessService
	 */
	private $service;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create service instance.
		$this->service = new GoogleBusinessService();
	}

	/**
	 * Test formatReviewDate() converts various date formats to MySQL datetime.
	 */
	public function testFormatReviewDateConvertsToMySql(): void {
		$method = new \ReflectionMethod( GoogleBusinessService::class, 'formatReviewDate' );
		$method->setAccessible( true );

		// ISO 8601 format.
		$result = $method->invoke( $this->service, '2025-09-20T14:30:00Z' );
		$this->assertEquals( '2025-09-20 14:30:00', $result );

		// Relative date (Apify sometimes uses these).
		$result2 = $method->invoke( $this->service, '2 weeks ago' );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result2 );
	}

	/**
	 * Test formatReviewDate() handles empty date.
	 */
	public function testFormatReviewDateHandlesEmptyDate(): void {
		$method = new \ReflectionMethod( GoogleBusinessService::class, 'formatReviewDate' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->service, '' );

		// Should return current time, so just verify it's a valid MySQL datetime.
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result );
	}

	/**
	 * Test normalizeReviews() transforms Apify review data.
	 */
	public function testNormalizeReviewsTransformsApifyData(): void {
		$method = new \ReflectionMethod( GoogleBusinessService::class, 'normalizeReviews' );
		$method->setAccessible( true );

		$apify_reviews = array(
			array(
				'name'             => 'John Doe',
				'profilePhotoUrl'  => 'https://example.com/avatar.jpg',
				'stars'            => 5,
				'text'             => 'Excellent service!',
				'publishedAtDate'  => '2025-09-15',
			),
			array(
				'name'             => 'Jane Smith',
				'stars'            => 4,
				'text'             => 'Great experience!',
				'publishedAtDate'  => '2025-09-16',
			),
		);

		$result = $method->invoke( $this->service, $apify_reviews );

		$this->assertCount( 2, $result );

		// Check first review.
		$this->assertEquals( 'google', $result[0]['source'] );
		$this->assertNotEmpty( $result[0]['external_review_id'] ); // Generated hash.
		$this->assertEquals( 'John Doe', $result[0]['reviewer_name'] );
		$this->assertEquals( 'https://example.com/avatar.jpg', $result[0]['reviewer_avatar_url'] );
		$this->assertEquals( 5.0, $result[0]['rating'] );
		$this->assertEquals( 'Excellent service!', $result[0]['review_text'] );

		// Check second review.
		$this->assertEquals( 'google', $result[1]['source'] );
		$this->assertEquals( 'Jane Smith', $result[1]['reviewer_name'] );
		$this->assertEquals( '', $result[1]['reviewer_avatar_url'] ); // Missing avatar.
		$this->assertEquals( 4.0, $result[1]['rating'] );
	}

	/**
	 * Test normalizeReviews() handles missing fields.
	 */
	public function testNormalizeReviewsHandlesMissingFields(): void {
		$method = new \ReflectionMethod( GoogleBusinessService::class, 'normalizeReviews' );
		$method->setAccessible( true );

		$apify_reviews = array(
			array(
				// Most fields missing.
				'name' => 'Minimal User',
			),
		);

		$result = $method->invoke( $this->service, $apify_reviews );

		$this->assertCount( 1, $result );
		$this->assertEquals( 'google', $result[0]['source'] );
		$this->assertEquals( 'Minimal User', $result[0]['reviewer_name'] );
		$this->assertEquals( '', $result[0]['reviewer_avatar_url'] );
		$this->assertEquals( 5.0, $result[0]['rating'] ); // Default rating.
		$this->assertEquals( '', $result[0]['review_text'] );
	}

	/**
	 * Test generateReviewId() creates unique IDs.
	 */
	public function testGenerateReviewIdCreatesUniqueIds(): void {
		$method = new \ReflectionMethod( GoogleBusinessService::class, 'generateReviewId' );
		$method->setAccessible( true );

		$review1 = array(
			'name'            => 'John Doe',
			'publishedAtDate' => '2025-09-15',
			'text'            => 'Great service! Highly recommend.',
		);

		$review2 = array(
			'name'            => 'Jane Smith',
			'publishedAtDate' => '2025-09-15',
			'text'            => 'Great service! Highly recommend.',
		);

		$id1 = $method->invoke( $this->service, $review1 );
		$id2 = $method->invoke( $this->service, $review2 );

		// IDs should be different (different names).
		$this->assertNotEquals( $id1, $id2 );

		// Same review should generate same ID.
		$id3 = $method->invoke( $this->service, $review1 );
		$this->assertEquals( $id1, $id3 );
	}

	/**
	 * Test fetchReviews() returns empty array when Apify API fails to start run.
	 */
	public function testFetchReviewsReturnsEmptyArrayWhenApifyStartFails(): void {
		// Mock Apify API failure.
		add_filter(
			'pre_http_request',
			function( $preempt, $args, $url ) {
				if ( strpos( $url, 'apify.com' ) !== false && strpos( $url, '/runs?' ) !== false ) {
					return array(
						'response' => array( 'code' => 400 ),
						'body'     => wp_json_encode( array( 'error' => 'Invalid input' ) ),
					);
				}
				return $preempt;
			},
			10,
			3
		);

		$result = $this->service->fetchReviews();

		$this->assertEquals( array(), $result );

		// Clean up filter.
		remove_all_filters( 'pre_http_request' );
	}
}
