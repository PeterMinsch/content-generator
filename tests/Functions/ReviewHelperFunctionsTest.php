<?php
/**
 * Review Helper Functions Tests
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Functions;

use WP_UnitTestCase;

/**
 * Test Review Helper Functions
 */
class ReviewHelperFunctionsTest extends WP_UnitTestCase {

	/**
	 * Test seo_get_page_reviews() retrieves stored reviews.
	 */
	public function testSeoGetPageReviewsRetrievesStoredReviews(): void {
		$post_id = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );

		// Store test reviews.
		$reviews = array(
			array(
				'id'            => 1,
				'reviewer_name' => 'Alice Johnson',
				'rating'        => '5.0',
				'review_text'   => 'Amazing service!',
			),
			array(
				'id'            => 2,
				'reviewer_name' => 'Bob Smith',
				'rating'        => '4.0',
				'review_text'   => 'Very good!',
			),
		);
		update_post_meta( $post_id, '_seo_reviews_data', wp_json_encode( $reviews ) );

		// Call helper function.
		$result = seo_get_page_reviews( $post_id );

		$this->assertCount( 2, $result );
		$this->assertEquals( 'Alice Johnson', $result[0]['reviewer_name'] );
		$this->assertEquals( 'Bob Smith', $result[1]['reviewer_name'] );
	}

	/**
	 * Test seo_get_page_reviews() returns empty when no reviews.
	 */
	public function testSeoGetPageReviewsReturnsEmptyWhenNoMeta(): void {
		$post_id = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );

		$result = seo_get_page_reviews( $post_id );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test seo_get_page_reviews() handles invalid JSON gracefully.
	 */
	public function testSeoGetPageReviewsHandlesInvalidJson(): void {
		$post_id = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );

		// Store invalid JSON.
		update_post_meta( $post_id, '_seo_reviews_data', 'invalid json{' );

		$result = seo_get_page_reviews( $post_id );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test seo_format_review_rating() formats full stars correctly.
	 */
	public function testSeoFormatReviewRatingFormatsFullStars(): void {
		$html = seo_format_review_rating( 5.0 );

		$this->assertStringContainsString( '⭐⭐⭐⭐⭐', $html );
		$this->assertStringNotContainsString( '☆', $html );
		$this->assertStringContainsString( 'seo-review-stars', $html );
	}

	/**
	 * Test seo_format_review_rating() formats 4 stars correctly.
	 */
	public function testSeoFormatReviewRatingFormatsFourStars(): void {
		$html = seo_format_review_rating( 4.0 );

		$this->assertStringContainsString( '⭐⭐⭐⭐', $html );
		$this->assertStringContainsString( '☆', $html );
	}

	/**
	 * Test seo_format_review_rating() formats half stars correctly.
	 */
	public function testSeoFormatReviewRatingFormatsHalfStars(): void {
		$html = seo_format_review_rating( 4.5 );

		$this->assertStringContainsString( '⭐⭐⭐⭐', $html );
		$this->assertStringContainsString( 'half-star', $html );
	}

	/**
	 * Test seo_format_review_rating() includes aria-label.
	 */
	public function testSeoFormatReviewRatingIncludesAriaLabel(): void {
		$html = seo_format_review_rating( 4.0 );

		$this->assertStringContainsString( 'aria-label="4 out of 5 stars"', $html );
	}

	/**
	 * Test seo_format_review_rating() handles 1 star.
	 */
	public function testSeoFormatReviewRatingHandlesOneStar(): void {
		$html = seo_format_review_rating( 1.0 );

		// Count stars.
		$full_count = substr_count( $html, '⭐' );
		$empty_count = substr_count( $html, '☆' );

		$this->assertEquals( 1, $full_count );
		$this->assertEquals( 4, $empty_count );
	}

	/**
	 * Test seo_get_review_avatar() returns img tag.
	 */
	public function testSeoGetReviewAvatarReturnsImgTag(): void {
		$url = 'https://example.com/avatar.jpg';

		$html = seo_get_review_avatar( $url, 80 );

		$this->assertStringContainsString( '<img', $html );
		$this->assertStringContainsString( 'src="https://example.com/avatar.jpg"', $html );
		$this->assertStringContainsString( 'width="80"', $html );
		$this->assertStringContainsString( 'height="80"', $html );
		$this->assertStringContainsString( 'class="seo-review-avatar"', $html );
		$this->assertStringContainsString( 'loading="lazy"', $html );
	}

	/**
	 * Test seo_get_review_avatar() uses default when empty URL.
	 */
	public function testSeoGetReviewAvatarUsesDefaultWhenEmpty(): void {
		$html = seo_get_review_avatar( '', 64 );

		$this->assertStringContainsString( '<img', $html );
		$this->assertStringContainsString( 'width="64"', $html );
		// Should contain a gravatar URL or WordPress default.
		$this->assertStringContainsString( 'src=', $html );
	}

	/**
	 * Test seo_get_review_avatar() handles default size parameter.
	 */
	public function testSeoGetReviewAvatarHandlesDefaultSize(): void {
		$url = 'https://example.com/avatar.jpg';

		$html = seo_get_review_avatar( $url );

		$this->assertStringContainsString( 'width="64"', $html );
		$this->assertStringContainsString( 'height="64"', $html );
	}

	/**
	 * Test seo_get_review_avatar() includes alt text.
	 */
	public function testSeoGetReviewAvatarIncludesAltText(): void {
		$url = 'https://example.com/avatar.jpg';

		$html = seo_get_review_avatar( $url, 64 );

		$this->assertStringContainsString( 'alt="Reviewer avatar"', $html );
	}
}
