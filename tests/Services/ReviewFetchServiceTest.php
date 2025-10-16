<?php
/**
 * ReviewFetchService Tests
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Services;

use SEOGenerator\Services\ReviewFetchService;
use SEOGenerator\Services\GoogleBusinessService;
use SEOGenerator\Repositories\ReviewRepository;
use WP_UnitTestCase;

/**
 * Test ReviewFetchService
 */
class ReviewFetchServiceTest extends WP_UnitTestCase {

	/**
	 * Test getReviews() returns cached reviews when cache is fresh.
	 */
	public function testGetReviewsReturnsCachedWhenFresh(): void {
		// Mock repository: cache is fresh (10 days old).
		$repository = $this->createMock( ReviewRepository::class );
		$repository->method( 'getCacheAge' )->willReturn( 10 );
		$repository->method( 'needsRefresh' )->willReturn( false );
		$repository->method( 'getTopRated' )->willReturn(
			array(
				array(
					'id'            => 1,
					'rating'        => 5.0,
					'reviewer_name' => 'John Doe',
					'review_text'   => 'Great service!',
				),
			)
		);

		// Mock Google service (should not be called).
		$googleService = $this->createMock( GoogleBusinessService::class );
		$googleService->expects( $this->never() )->method( 'fetchReviews' );

		$service = new ReviewFetchService( $googleService, $repository );
		$reviews = $service->getReviews( 5 );

		$this->assertCount( 1, $reviews );
		$this->assertEquals( 'John Doe', $reviews[0]['reviewer_name'] );
	}

	/**
	 * Test getReviews() fetches from API when cache is stale.
	 */
	public function testGetReviewsFetchesWhenStale(): void {
		// Mock repository: cache is stale (35 days old).
		$repository = $this->createMock( ReviewRepository::class );
		$repository->method( 'getCacheAge' )->willReturn( 35 );
		$repository->method( 'needsRefresh' )->willReturn( true );
		$repository->method( 'save' )->willReturn( 123 ); // Successful save.
		$repository->method( 'getTopRated' )->willReturn(
			array(
				array(
					'id'            => 123,
					'rating'        => 5.0,
					'reviewer_name' => 'Fresh Review',
					'review_text'   => 'Excellent!',
				),
			)
		);

		// Mock Google service: returns fresh reviews.
		$googleService = $this->createMock( GoogleBusinessService::class );
		$googleService->method( 'fetchReviews' )->willReturn(
			array(
				array(
					'source'             => 'google',
					'external_review_id' => 'fresh123',
					'reviewer_name'      => 'Fresh Review',
					'rating'             => 5.0,
					'review_text'        => 'Excellent!',
					'review_date'        => '2025-09-20 10:00:00',
				),
			)
		);

		$service = new ReviewFetchService( $googleService, $repository );
		$reviews = $service->getReviews( 5 );

		$this->assertCount( 1, $reviews );
		$this->assertEquals( 'Fresh Review', $reviews[0]['reviewer_name'] );
	}

	/**
	 * Test getReviews() forces API fetch when force_refresh is true.
	 */
	public function testForceRefreshBypassesCache(): void {
		// Mock repository: cache is fresh but force_refresh=true.
		$repository = $this->createMock( ReviewRepository::class );
		$repository->method( 'getCacheAge' )->willReturn( 5 );
		$repository->method( 'save' )->willReturn( 124 );
		$repository->method( 'getTopRated' )->willReturn(
			array(
				array(
					'id'            => 124,
					'rating'        => 4.5,
					'reviewer_name' => 'Forced Fetch',
					'review_text'   => 'Good!',
				),
			)
		);

		// Mock Google service: should be called despite fresh cache.
		$googleService = $this->createMock( GoogleBusinessService::class );
		$googleService->expects( $this->once() )->method( 'fetchReviews' )->willReturn(
			array(
				array(
					'source'             => 'google',
					'external_review_id' => 'forced123',
					'reviewer_name'      => 'Forced Fetch',
					'rating'             => 4.5,
					'review_text'        => 'Good!',
					'review_date'        => '2025-09-21 11:00:00',
				),
			)
		);

		$service = new ReviewFetchService( $googleService, $repository );
		$reviews = $service->getReviews( 5, true ); // force_refresh=true.

		$this->assertCount( 1, $reviews );
		$this->assertEquals( 'Forced Fetch', $reviews[0]['reviewer_name'] );
	}

	/**
	 * Test getReviews() returns stale cache when API fails.
	 */
	public function testApiFailsReturnsStaleCache(): void {
		// Mock repository: cache stale, but has data.
		$repository = $this->createMock( ReviewRepository::class );
		$repository->method( 'getCacheAge' )->willReturn( 40 );
		$repository->method( 'needsRefresh' )->willReturn( true );
		$repository->method( 'getAll' )->willReturn( array( array( 'id' => 1 ) ) ); // Cache exists.
		$repository->method( 'getTopRated' )->willReturn(
			array(
				array(
					'id'            => 1,
					'rating'        => 5.0,
					'reviewer_name' => 'Stale Review',
					'review_text'   => 'Old but good!',
				),
			)
		);

		// Mock Google service: API fails (returns empty).
		$googleService = $this->createMock( GoogleBusinessService::class );
		$googleService->method( 'fetchReviews' )->willReturn( array() );

		$service = new ReviewFetchService( $googleService, $repository );
		$reviews = $service->getReviews( 5 );

		// Should return stale cache instead of empty.
		$this->assertCount( 1, $reviews );
		$this->assertEquals( 'Stale Review', $reviews[0]['reviewer_name'] );
	}

	/**
	 * Test getReviews() returns empty when API fails and no cache exists.
	 */
	public function testApiFailsNoCacheReturnsEmpty(): void {
		// Mock repository: no cache exists.
		$repository = $this->createMock( ReviewRepository::class );
		$repository->method( 'getCacheAge' )->willReturn( 0 );
		$repository->method( 'needsRefresh' )->willReturn( true );
		$repository->method( 'getAll' )->willReturn( array() ); // No cache.

		// Mock Google service: API fails.
		$googleService = $this->createMock( GoogleBusinessService::class );
		$googleService->method( 'fetchReviews' )->willReturn( array() );

		$service = new ReviewFetchService( $googleService, $repository );
		$reviews = $service->getReviews( 5 );

		$this->assertEmpty( $reviews );
	}

	/**
	 * Test fetchAndStoreReviews() saves new reviews and skips duplicates.
	 */
	public function testSaveSkipsDuplicates(): void {
		// Mock repository: save() returns 0 for duplicates.
		$repository = $this->createMock( ReviewRepository::class );
		$repository->method( 'getCacheAge' )->willReturn( 35 );
		$repository->method( 'needsRefresh' )->willReturn( true );
		$repository->method( 'save' )->will(
			$this->onConsecutiveCalls(
				125, // First review: new.
				0,   // Second review: duplicate.
				126  // Third review: new.
			)
		);
		$repository->method( 'getTopRated' )->willReturn(
			array(
				array( 'id' => 125 ),
				array( 'id' => 126 ),
			)
		);

		// Mock Google service: returns 3 reviews.
		$googleService = $this->createMock( GoogleBusinessService::class );
		$googleService->method( 'fetchReviews' )->willReturn(
			array(
				array(
					'source'             => 'google',
					'external_review_id' => 'abc1',
					'reviewer_name'      => 'User 1',
					'rating'             => 5.0,
				),
				array(
					'source'             => 'google',
					'external_review_id' => 'abc2',
					'reviewer_name'      => 'User 2',
					'rating'             => 4.0,
				), // Duplicate.
				array(
					'source'             => 'google',
					'external_review_id' => 'abc3',
					'reviewer_name'      => 'User 3',
					'rating'             => 5.0,
				),
			)
		);

		$service = new ReviewFetchService( $googleService, $repository );
		$reviews = $service->getReviews( 5 );

		// Should return 2 reviews (1 duplicate skipped).
		$this->assertCount( 2, $reviews );
	}

	/**
	 * Test request-scoped caching prevents duplicate calls.
	 */
	public function testRequestScopedCachingPreventsDuplicateCalls(): void {
		$repository = $this->createMock( ReviewRepository::class );
		$repository->method( 'getCacheAge' )->willReturn( 10 );
		$repository->method( 'needsRefresh' )->willReturn( false );

		// Repository should only be called once.
		$repository->expects( $this->once() )->method( 'getTopRated' )->willReturn(
			array(
				array(
					'id'            => 1,
					'rating'        => 5.0,
					'reviewer_name' => 'Cached',
				),
			)
		);

		$googleService = $this->createMock( GoogleBusinessService::class );

		$service = new ReviewFetchService( $googleService, $repository );

		// Call getReviews multiple times.
		$reviews1 = $service->getReviews( 5 );
		$reviews2 = $service->getReviews( 5 );
		$reviews3 = $service->getReviews( 5 );

		// All should return same cached data.
		$this->assertCount( 1, $reviews1 );
		$this->assertCount( 1, $reviews2 );
		$this->assertCount( 1, $reviews3 );
		$this->assertEquals( 'Cached', $reviews1[0]['reviewer_name'] );
		$this->assertEquals( 'Cached', $reviews2[0]['reviewer_name'] );
		$this->assertEquals( 'Cached', $reviews3[0]['reviewer_name'] );
	}

	/**
	 * Test getReviews() respects limit parameter.
	 */
	public function testGetReviewsRespectsLimitParameter(): void {
		$repository = $this->createMock( ReviewRepository::class );
		$repository->method( 'getCacheAge' )->willReturn( 10 );
		$repository->method( 'needsRefresh' )->willReturn( false );
		$repository->method( 'getTopRated' )->willReturn(
			array(
				array( 'id' => 1, 'rating' => 5.0, 'reviewer_name' => 'User 1' ),
				array( 'id' => 2, 'rating' => 4.5, 'reviewer_name' => 'User 2' ),
				array( 'id' => 3, 'rating' => 4.0, 'reviewer_name' => 'User 3' ),
			)
		);

		$googleService = $this->createMock( GoogleBusinessService::class );

		$service = new ReviewFetchService( $googleService, $repository );

		// First call: get 2 reviews.
		$reviews = $service->getReviews( 2 );
		$this->assertCount( 2, $reviews );

		// Clear request cache for second test.
		$service2 = new ReviewFetchService( $googleService, $repository );

		// Second call: get 1 review.
		$reviews2 = $service2->getReviews( 1 );
		$this->assertCount( 1, $reviews2 );
	}

	/**
	 * Test getReviews() with empty cache and successful API fetch.
	 */
	public function testGetReviewsWithEmptyCacheAndSuccessfulFetch(): void {
		// Mock repository: cache empty (age = 0).
		$repository = $this->createMock( ReviewRepository::class );
		$repository->method( 'getCacheAge' )->willReturn( 0 );
		$repository->method( 'needsRefresh' )->willReturn( true );
		$repository->method( 'save' )->willReturn( 200 ); // Successful save.
		$repository->method( 'getTopRated' )->willReturn(
			array(
				array(
					'id'            => 200,
					'rating'        => 5.0,
					'reviewer_name' => 'First Review',
					'review_text'   => 'Amazing!',
				),
			)
		);

		// Mock Google service: returns new reviews.
		$googleService = $this->createMock( GoogleBusinessService::class );
		$googleService->method( 'fetchReviews' )->willReturn(
			array(
				array(
					'source'             => 'google',
					'external_review_id' => 'first123',
					'reviewer_name'      => 'First Review',
					'rating'             => 5.0,
					'review_text'        => 'Amazing!',
					'review_date'        => '2025-10-15 14:00:00',
				),
			)
		);

		$service = new ReviewFetchService( $googleService, $repository );
		$reviews = $service->getReviews( 5 );

		$this->assertCount( 1, $reviews );
		$this->assertEquals( 'First Review', $reviews[0]['reviewer_name'] );
	}

	/**
	 * Test getReviews() with default parameters.
	 */
	public function testGetReviewsWithDefaultParameters(): void {
		$repository = $this->createMock( ReviewRepository::class );
		$repository->method( 'getCacheAge' )->willReturn( 5 );
		$repository->method( 'needsRefresh' )->willReturn( false );
		$repository->method( 'getTopRated' )->willReturn(
			array(
				array( 'id' => 1, 'rating' => 5.0 ),
			)
		);

		$googleService = $this->createMock( GoogleBusinessService::class );

		$service = new ReviewFetchService( $googleService, $repository );

		// Call with default parameters (limit=10, force_refresh=false).
		$reviews = $service->getReviews();

		$this->assertIsArray( $reviews );
	}
}
