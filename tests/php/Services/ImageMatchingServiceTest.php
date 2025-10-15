<?php
/**
 * Tests for ImageMatchingService class
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Services;

use SEOGenerator\Services\ImageMatchingService;
use WP_UnitTestCase;

/**
 * Test ImageMatchingService functionality
 */
class ImageMatchingServiceTest extends WP_UnitTestCase {
	/**
	 * ImageMatchingService instance.
	 *
	 * @var ImageMatchingService
	 */
	private $service;

	/**
	 * Test image IDs.
	 *
	 * @var array
	 */
	private $test_images = array();

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->service = new ImageMatchingService();

		// Create test images with different tag combinations.
		$this->createTestImages();
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		// Delete test images.
		foreach ( $this->test_images as $image_id ) {
			wp_delete_attachment( $image_id, true );
		}

		// Clear image settings.
		delete_option( 'seo_generator_image_settings' );

		parent::tearDown();
	}

	/**
	 * Create test images with various tag combinations.
	 */
	private function createTestImages() {
		// Image with 3 tags: platinum, mens, ring.
		$image_1 = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $image_1, '_seo_library_image', '1' );
		wp_set_object_terms( $image_1, array( 'platinum', 'mens', 'ring' ), 'image_tag' );
		$this->test_images['3_tags'] = $image_1;

		// Image with 2 tags: platinum, womens.
		$image_2 = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $image_2, '_seo_library_image', '1' );
		wp_set_object_terms( $image_2, array( 'platinum', 'womens' ), 'image_tag' );
		$this->test_images['2_tags'] = $image_2;

		// Image with 1 tag: gold.
		$image_3 = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $image_3, '_seo_library_image', '1' );
		wp_set_object_terms( $image_3, array( 'gold' ), 'image_tag' );
		$this->test_images['1_tag'] = $image_3;

		// Image with no tags.
		$image_4 = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $image_4, '_seo_library_image', '1' );
		$this->test_images['no_tags'] = $image_4;

		// Image NOT in library (no meta flag).
		$image_5 = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		wp_set_object_terms( $image_5, array( 'silver' ), 'image_tag' );
		$this->test_images['not_library'] = $image_5;
	}

	/**
	 * Test finding image with all 3 matching tags.
	 */
	public function test_find_image_with_all_tags() {
		$context = array(
			'focus_keyword' => 'platinum',
			'topic'         => 'mens',
			'category'      => 'ring',
		);

		$result = $this->service->findMatchingImage( $context );

		$this->assertEquals( $this->test_images['3_tags'], $result );
	}

	/**
	 * Test fallback to 2 tags when 3 tags don't match.
	 */
	public function test_fallback_to_2_tags() {
		$context = array(
			'focus_keyword' => 'platinum',
			'topic'         => 'womens',
			'category'      => 'necklace', // No image has all 3.
		);

		$result = $this->service->findMatchingImage( $context );

		// Should match image with platinum + womens.
		$this->assertEquals( $this->test_images['2_tags'], $result );
	}

	/**
	 * Test fallback to 1 tag when 2 tags don't match.
	 */
	public function test_fallback_to_1_tag() {
		$context = array(
			'focus_keyword' => 'gold',
			'topic'         => 'unisex',
			'category'      => 'bracelet',
		);

		$result = $this->service->findMatchingImage( $context );

		// Should match image with gold tag.
		$this->assertEquals( $this->test_images['1_tag'], $result );
	}

	/**
	 * Test returns null when no matches and no default image.
	 */
	public function test_returns_null_when_no_matches() {
		$context = array(
			'focus_keyword' => 'diamond',
			'topic'         => 'vintage',
			'category'      => 'earring',
		);

		$result = $this->service->findMatchingImage( $context );

		$this->assertNull( $result );
	}

	/**
	 * Test returns default image when no matches.
	 */
	public function test_returns_default_image_when_no_matches() {
		// Set default image.
		update_option(
			'seo_generator_image_settings',
			array( 'default_image_id' => $this->test_images['no_tags'] )
		);

		$context = array(
			'focus_keyword' => 'diamond',
			'topic'         => 'vintage',
			'category'      => 'earring',
		);

		$result = $this->service->findMatchingImage( $context );

		$this->assertEquals( $this->test_images['no_tags'], $result );
	}

	/**
	 * Test returns default image when context is empty.
	 */
	public function test_returns_default_image_when_empty_context() {
		// Set default image.
		update_option(
			'seo_generator_image_settings',
			array( 'default_image_id' => $this->test_images['no_tags'] )
		);

		$context = array();

		$result = $this->service->findMatchingImage( $context );

		$this->assertEquals( $this->test_images['no_tags'], $result );
	}

	/**
	 * Test returns null when context is empty and no default.
	 */
	public function test_returns_null_when_empty_context_and_no_default() {
		$context = array();

		$result = $this->service->findMatchingImage( $context );

		$this->assertNull( $result );
	}

	/**
	 * Test keyword extraction from multi-word strings.
	 */
	public function test_keyword_extraction_from_multiword_strings() {
		// Create image with "wedding" and "band" tags.
		$image = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $image, '_seo_library_image', '1' );
		wp_set_object_terms( $image, array( 'wedding', 'band' ), 'image_tag' );

		$context = array(
			'focus_keyword' => 'wedding band', // Multi-word keyword.
		);

		$result = $this->service->findMatchingImage( $context );

		// Should extract "wedding" and "band" as separate tags.
		$this->assertEquals( $image, $result );

		// Cleanup.
		wp_delete_attachment( $image, true );
	}

	/**
	 * Test ignores images not in library (no meta flag).
	 */
	public function test_ignores_images_not_in_library() {
		$context = array(
			'focus_keyword' => 'silver',
		);

		$result = $this->service->findMatchingImage( $context );

		// Should not match image without library meta flag.
		$this->assertNull( $result );
	}

	/**
	 * Test random selection with multiple matches.
	 */
	public function test_random_selection_with_multiple_matches() {
		// Create 3 images with same tags.
		$image_a = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $image_a, '_seo_library_image', '1' );
		wp_set_object_terms( $image_a, array( 'classic' ), 'image_tag' );

		$image_b = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $image_b, '_seo_library_image', '1' );
		wp_set_object_terms( $image_b, array( 'classic' ), 'image_tag' );

		$image_c = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $image_c, '_seo_library_image', '1' );
		wp_set_object_terms( $image_c, array( 'classic' ), 'image_tag' );

		$context = array(
			'focus_keyword' => 'classic',
		);

		// Run multiple times to verify it returns one of the matches.
		$results = array();
		for ( $i = 0; $i < 10; $i++ ) {
			$result = $this->service->findMatchingImage( $context );
			$results[] = $result;

			// Should return one of the 3 images.
			$this->assertContains( $result, array( $image_a, $image_b, $image_c ) );
		}

		// Verify randomness (not always same image).
		// Note: This could theoretically fail if random always picks same, but very unlikely with 10 iterations.
		$unique_results = array_unique( $results );
		$this->assertGreaterThan( 1, count( $unique_results ), 'Random selection should return different images' );

		// Cleanup.
		wp_delete_attachment( $image_a, true );
		wp_delete_attachment( $image_b, true );
		wp_delete_attachment( $image_c, true );
	}

	/**
	 * Test keyword extraction ignores very short words.
	 */
	public function test_keyword_extraction_ignores_short_words() {
		// Create image with "ring" tag only.
		$image = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $image, '_seo_library_image', '1' );
		wp_set_object_terms( $image, array( 'ring' ), 'image_tag' );

		$context = array(
			'focus_keyword' => 'a ring', // "a" should be ignored (too short).
		);

		$result = $this->service->findMatchingImage( $context );

		// Should match "ring" and ignore "a".
		$this->assertEquals( $image, $result );

		// Cleanup.
		wp_delete_attachment( $image, true );
	}

	/**
	 * Test handles hyphens and underscores in keywords.
	 */
	public function test_handles_hyphens_and_underscores_in_keywords() {
		// Create image with "white" and "gold" tags.
		$image = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $image, '_seo_library_image', '1' );
		wp_set_object_terms( $image, array( 'white', 'gold' ), 'image_tag' );

		$context = array(
			'focus_keyword' => 'white-gold', // Hyphenated keyword.
		);

		$result = $this->service->findMatchingImage( $context );

		// Should split "white-gold" into "white" and "gold".
		$this->assertEquals( $image, $result );

		// Cleanup.
		wp_delete_attachment( $image, true );
	}

	/**
	 * Test prioritizes images with more matching tags.
	 */
	public function test_prioritizes_more_matching_tags() {
		// Image with 1 tag: vintage.
		$image_1_tag = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $image_1_tag, '_seo_library_image', '1' );
		wp_set_object_terms( $image_1_tag, array( 'vintage' ), 'image_tag' );

		// Image with 3 tags: vintage, classic, elegant.
		$image_3_tags = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $image_3_tags, '_seo_library_image', '1' );
		wp_set_object_terms( $image_3_tags, array( 'vintage', 'classic', 'elegant' ), 'image_tag' );

		$context = array(
			'focus_keyword' => 'vintage',
			'topic'         => 'classic',
			'category'      => 'elegant',
		);

		$result = $this->service->findMatchingImage( $context );

		// Should match image with all 3 tags, not just 1.
		$this->assertEquals( $image_3_tags, $result );

		// Cleanup.
		wp_delete_attachment( $image_1_tag, true );
		wp_delete_attachment( $image_3_tags, true );
	}

	/**
	 * Test handles duplicate keywords in context.
	 */
	public function test_handles_duplicate_keywords() {
		// Create image with "modern" tag.
		$image = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $image, '_seo_library_image', '1' );
		wp_set_object_terms( $image, array( 'modern' ), 'image_tag' );

		$context = array(
			'focus_keyword' => 'modern',
			'topic'         => 'modern', // Duplicate.
			'category'      => 'modern', // Duplicate.
		);

		$result = $this->service->findMatchingImage( $context );

		// Should handle duplicates and match the image.
		$this->assertEquals( $image, $result );

		// Cleanup.
		wp_delete_attachment( $image, true );
	}
}
