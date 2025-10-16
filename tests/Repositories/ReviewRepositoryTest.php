<?php
/**
 * ReviewRepository Tests
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Repositories;

use SEOGenerator\Repositories\ReviewRepository;
use WP_UnitTestCase;

/**
 * Test ReviewRepository
 */
class ReviewRepositoryTest extends WP_UnitTestCase {

	/**
	 * Repository instance.
	 *
	 * @var ReviewRepository
	 */
	private $repository;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create repository instance.
		$this->repository = new ReviewRepository();

		// Clean table before each test.
		$this->repository->deleteAll();
	}

	/**
	 * Tear down test.
	 */
	public function tearDown(): void {
		// Clean up after test.
		$this->repository->deleteAll();

		parent::tearDown();
	}

	/**
	 * Test getAll() returns all reviews ordered by rating.
	 */
	public function testGetAllReturnsReviewsOrderedByRating(): void {
		// Insert test reviews.
		$this->repository->save(
			array(
				'source'             => 'google',
				'external_review_id' => 'review1',
				'reviewer_name'      => 'John Doe',
				'rating'             => 4.5,
				'review_text'        => 'Great service!',
				'review_date'        => '2025-09-15 10:00:00',
			)
		);
		$this->repository->save(
			array(
				'source'             => 'google',
				'external_review_id' => 'review2',
				'reviewer_name'      => 'Jane Smith',
				'rating'             => 5.0,
				'review_text'        => 'Excellent!',
				'review_date'        => '2025-09-16 11:00:00',
			)
		);
		$this->repository->save(
			array(
				'source'             => 'google',
				'external_review_id' => 'review3',
				'reviewer_name'      => 'Bob Johnson',
				'rating'             => 4.0,
				'review_text'        => 'Good experience.',
				'review_date'        => '2025-09-17 12:00:00',
			)
		);

		$results = $this->repository->getAll( 10 );

		$this->assertCount( 3, $results );
		$this->assertEquals( 5.0, $results[0]['rating'] ); // Highest first.
		$this->assertEquals( 4.5, $results[1]['rating'] );
		$this->assertEquals( 4.0, $results[2]['rating'] );
	}

	/**
	 * Test getAll() returns empty array when no reviews.
	 */
	public function testGetAllReturnsEmptyArrayWhenNoReviews(): void {
		$results = $this->repository->getAll( 10 );

		$this->assertCount( 0, $results );
		$this->assertEquals( array(), $results );
	}

	/**
	 * Test getByRating() filters reviews correctly.
	 */
	public function testGetByRatingFiltersCorrectly(): void {
		// Insert test reviews.
		$this->repository->save(
			array(
				'source'             => 'google',
				'external_review_id' => 'review1',
				'rating'             => 3.5,
			)
		);
		$this->repository->save(
			array(
				'source'             => 'google',
				'external_review_id' => 'review2',
				'rating'             => 4.5,
			)
		);
		$this->repository->save(
			array(
				'source'             => 'google',
				'external_review_id' => 'review3',
				'rating'             => 5.0,
			)
		);

		$results = $this->repository->getByRating( 4.0, 10 );

		$this->assertCount( 2, $results ); // Only 4.5 and 5.0.
		$this->assertEquals( 5.0, $results[0]['rating'] );
		$this->assertEquals( 4.5, $results[1]['rating'] );
	}

	/**
	 * Test getTopRated() returns top N reviews.
	 */
	public function testGetTopRatedReturnsTopNReviews(): void {
		// Insert test reviews.
		$this->repository->save(
			array(
				'source'             => 'google',
				'external_review_id' => 'review1',
				'rating'             => 4.0,
			)
		);
		$this->repository->save(
			array(
				'source'             => 'google',
				'external_review_id' => 'review2',
				'rating'             => 5.0,
			)
		);
		$this->repository->save(
			array(
				'source'             => 'google',
				'external_review_id' => 'review3',
				'rating'             => 4.5,
			)
		);

		$results = $this->repository->getTopRated( 2 );

		$this->assertCount( 2, $results );
		$this->assertEquals( 5.0, $results[0]['rating'] );
		$this->assertEquals( 4.5, $results[1]['rating'] );
	}

	/**
	 * Test save() inserts new review and returns ID.
	 */
	public function testSaveInsertsNewReviewAndReturnsId(): void {
		$review_data = array(
			'source'              => 'google',
			'external_review_id'  => 'abc123',
			'reviewer_name'       => 'Sarah Johnson',
			'reviewer_avatar_url' => 'https://example.com/avatar.jpg',
			'rating'              => 5.0,
			'review_text'         => 'Excellent service! Beautiful engagement ring.',
			'review_date'         => '2025-09-15 10:30:00',
		);

		$insert_id = $this->repository->save( $review_data );

		$this->assertGreaterThan( 0, $insert_id );

		// Verify review was saved.
		$all = $this->repository->getAll( 10 );
		$this->assertCount( 1, $all );
		$this->assertEquals( 'abc123', $all[0]['external_review_id'] );
		$this->assertEquals( 'Sarah Johnson', $all[0]['reviewer_name'] );
	}

	/**
	 * Test save() skips duplicate review.
	 */
	public function testSaveSkipsDuplicateReview(): void {
		$review_data = array(
			'source'             => 'google',
			'external_review_id' => 'abc123',
			'reviewer_name'      => 'John Doe',
			'rating'             => 5.0,
		);

		// First save should succeed.
		$id1 = $this->repository->save( $review_data );
		$this->assertGreaterThan( 0, $id1 );

		// Second save should return 0 (duplicate).
		$id2 = $this->repository->save( $review_data );
		$this->assertEquals( 0, $id2 );

		// Only 1 review should exist.
		$all = $this->repository->getAll( 10 );
		$this->assertCount( 1, $all );
	}

	/**
	 * Test deleteAll() clears all reviews.
	 */
	public function testDeleteAllClearsAllReviews(): void {
		// Insert test reviews.
		$this->repository->save(
			array(
				'source'             => 'google',
				'external_review_id' => 'review1',
				'rating'             => 4.5,
			)
		);
		$this->repository->save(
			array(
				'source'             => 'google',
				'external_review_id' => 'review2',
				'rating'             => 5.0,
			)
		);

		$deleted = $this->repository->deleteAll();

		$this->assertEquals( 2, $deleted );

		// Verify table is empty.
		$all = $this->repository->getAll( 10 );
		$this->assertCount( 0, $all );
	}

	/**
	 * Test getCacheAge() returns 0 when table is empty.
	 */
	public function testGetCacheAgeReturnsZeroWhenTableEmpty(): void {
		$age = $this->repository->getCacheAge();

		$this->assertEquals( 0, $age );
	}

	/**
	 * Test getCacheAge() calculates age correctly.
	 */
	public function testGetCacheAgeCalculatesAgeCorrectly(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'seo_reviews';

		// Insert review with old last_fetched_at date.
		$wpdb->insert(
			$table_name,
			array(
				'source'             => 'google',
				'external_review_id' => 'old123',
				'last_fetched_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-15 days' ) ),
			),
			array( '%s', '%s', '%s' )
		);

		$age = $this->repository->getCacheAge();

		// Age should be approximately 15 days.
		$this->assertGreaterThanOrEqual( 14, $age );
		$this->assertLessThanOrEqual( 16, $age );
	}

	/**
	 * Test needsRefresh() returns true when cache is empty.
	 */
	public function testNeedsRefreshReturnsTrueWhenCacheEmpty(): void {
		$needs_refresh = $this->repository->needsRefresh( 30 );

		$this->assertTrue( $needs_refresh );
	}

	/**
	 * Test needsRefresh() returns true when cache is stale.
	 */
	public function testNeedsRefreshReturnsTrueWhenCacheStale(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'seo_reviews';

		// Insert old review (35 days ago).
		$wpdb->insert(
			$table_name,
			array(
				'source'             => 'google',
				'external_review_id' => 'stale123',
				'last_fetched_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-35 days' ) ),
			),
			array( '%s', '%s', '%s' )
		);

		$needs_refresh = $this->repository->needsRefresh( 30 );

		$this->assertTrue( $needs_refresh );
	}

	/**
	 * Test needsRefresh() returns false when cache is fresh.
	 */
	public function testNeedsRefreshReturnsFalseWhenCacheFresh(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'seo_reviews';

		// Insert recent review (5 days ago).
		$wpdb->insert(
			$table_name,
			array(
				'source'             => 'google',
				'external_review_id' => 'fresh123',
				'last_fetched_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-5 days' ) ),
			),
			array( '%s', '%s', '%s' )
		);

		$needs_refresh = $this->repository->needsRefresh( 30 );

		$this->assertFalse( $needs_refresh );
	}

	/**
	 * Test full review lifecycle.
	 */
	public function testFullReviewLifecycle(): void {
		// Save multiple reviews.
		$this->repository->save(
			array(
				'source'             => 'google',
				'external_review_id' => 'review1',
				'rating'             => 5.0,
			)
		);
		$this->repository->save(
			array(
				'source'             => 'google',
				'external_review_id' => 'review2',
				'rating'             => 4.5,
			)
		);
		$this->repository->save(
			array(
				'source'             => 'google',
				'external_review_id' => 'review3',
				'rating'             => 3.5,
			)
		);

		// Get by rating.
		$high_rated = $this->repository->getByRating( 4.0, 10 );
		$this->assertCount( 2, $high_rated ); // Only 5.0 and 4.5.

		// Get top rated.
		$top = $this->repository->getTopRated( 1 );
		$this->assertCount( 1, $top );
		$this->assertEquals( 5.0, $top[0]['rating'] );

		// Delete all.
		$deleted = $this->repository->deleteAll();
		$this->assertEquals( 3, $deleted );

		// Verify empty.
		$all = $this->repository->getAll( 10 );
		$this->assertCount( 0, $all );
	}
}
