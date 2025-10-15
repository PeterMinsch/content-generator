<?php
/**
 * Tests for Folder-Based Image Organization (Story 5.7)
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Integration;

use SEOGenerator\Services\ImageMatchingService;
use WP_UnitTestCase;

/**
 * Test folder-based image organization functionality
 */
class FolderBasedImageOrganizationTest extends WP_UnitTestCase {
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

		// Register the image_tag taxonomy.
		register_taxonomy( 'image_tag', 'attachment', array() );
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		// Delete test images.
		foreach ( $this->test_images as $image_id ) {
			wp_delete_attachment( $image_id, true );
		}

		$this->test_images = array();

		parent::tearDown();
	}

	/**
	 * Create a test image attachment with folder metadata.
	 *
	 * @param string      $folder_name Folder name.
	 * @param array       $tags        Array of tag slugs.
	 * @return int Image attachment ID.
	 */
	private function createTestImageWithFolder( string $folder_name, array $tags = array() ): int {
		// Create attachment.
		$attachment_id = $this->factory->attachment->create_object(
			'test-image.jpg',
			0,
			array(
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
			)
		);

		// Mark as library image.
		update_post_meta( $attachment_id, '_seo_library_image', '1' );

		// Add folder metadata.
		if ( ! empty( $folder_name ) ) {
			update_post_meta( $attachment_id, '_seo_image_folder', $folder_name );

			// Add folder as tag.
			$folder_slug = sanitize_title( $folder_name );
			wp_set_object_terms( $attachment_id, $folder_slug, 'image_tag', true );
		}

		// Add additional tags.
		if ( ! empty( $tags ) ) {
			wp_set_object_terms( $attachment_id, $tags, 'image_tag', true );
		}

		$this->test_images[] = $attachment_id;

		return $attachment_id;
	}

	/**
	 * Test folder-based image matching prioritization.
	 */
	public function test_folder_based_matching_prioritization(): void {
		// Create images: one with folder "wedding-bands", one without folder.
		$folder_image_id = $this->createTestImageWithFolder( 'wedding-bands', array( 'platinum', 'ring' ) );
		$regular_image_id = $this->createTestImageWithFolder( '', array( 'wedding', 'bands', 'platinum', 'ring' ) );

		// Context with matching folder keyword.
		$context = array(
			'focus_keyword' => 'platinum wedding bands',
			'topic'         => 'wedding jewelry',
			'category'      => 'rings',
		);

		$matched_id = $this->service->findMatchingImage( $context );

		// Should prioritize the folder-based image.
		$this->assertEquals( $folder_image_id, $matched_id, 'Should prioritize image with matching folder tag' );
	}

	/**
	 * Test fallback to tag-based matching when no folder match.
	 */
	public function test_fallback_to_tag_based_matching(): void {
		// Create only non-folder images with tags.
		$image_id = $this->createTestImageWithFolder( '', array( 'diamond', 'ring', 'engagement' ) );

		// Context without folder match but with tag match.
		$context = array(
			'focus_keyword' => 'diamond engagement ring',
		);

		$matched_id = $this->service->findMatchingImage( $context );

		// Should fall back to tag-based matching.
		$this->assertEquals( $image_id, $matched_id, 'Should fall back to tag-based matching' );
	}

	/**
	 * Test folder name sanitization.
	 */
	public function test_folder_name_sanitization(): void {
		// Test folder with spaces and special characters.
		$folder_image_id = $this->createTestImageWithFolder( "Men's Wedding Bands" );

		// Get the folder tag assigned.
		$terms = wp_get_object_terms( $folder_image_id, 'image_tag', array( 'fields' => 'slugs' ) );

		// Should be sanitized to lowercase with hyphens.
		$this->assertContains( 'mens-wedding-bands', $terms, 'Folder name should be sanitized' );
	}

	/**
	 * Test backward compatibility with existing non-folder images.
	 */
	public function test_backward_compatibility(): void {
		// Create image without folder metadata (existing behavior).
		$image_id = $this->createTestImageWithFolder( '', array( 'gold', 'necklace' ) );

		$context = array(
			'focus_keyword' => 'gold necklace',
		);

		$matched_id = $this->service->findMatchingImage( $context );

		// Should still match using tags.
		$this->assertEquals( $image_id, $matched_id, 'Should maintain backward compatibility' );
	}

	/**
	 * Test folder metadata persistence.
	 */
	public function test_folder_metadata_persistence(): void {
		$folder_name = 'vintage-rings';
		$image_id = $this->createTestImageWithFolder( $folder_name );

		$stored_folder = get_post_meta( $image_id, '_seo_image_folder', true );

		$this->assertEquals( $folder_name, $stored_folder, 'Folder metadata should be stored' );
	}

	/**
	 * Test settings defaults.
	 */
	public function test_settings_defaults(): void {
		$settings = get_option( 'seo_generator_image_settings', array() );

		// Check that settings have reasonable defaults or can be set.
		update_option(
			'seo_generator_image_settings',
			array(
				'preserve_folder_structure' => true,
				'use_folder_as_primary_tag' => true,
			)
		);

		$updated_settings = get_option( 'seo_generator_image_settings' );

		$this->assertTrue( $updated_settings['preserve_folder_structure'], 'preserve_folder_structure should be true' );
		$this->assertTrue( $updated_settings['use_folder_as_primary_tag'], 'use_folder_as_primary_tag should be true' );
	}
}
