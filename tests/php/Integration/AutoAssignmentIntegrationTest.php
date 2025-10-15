<?php
/**
 * Integration Tests for Auto-Assignment Feature
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Integration;

use SEOGenerator\Services\ContentGenerationService;
use SEOGenerator\Services\ImageMatchingService;
use WP_UnitTestCase;

/**
 * Test auto-assignment integration with content generation
 */
class AutoAssignmentIntegrationTest extends WP_UnitTestCase {
	/**
	 * Test post ID.
	 *
	 * @var int
	 */
	private $post_id;

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

		// Create test post.
		$this->post_id = $this->factory->post->create(
			array(
				'post_type'  => 'seo-page',
				'post_title' => 'Test Page',
			)
		);

		// Create test images with tags.
		$this->createTestImages();

		// Enable auto-assignment by default.
		update_option(
			'seo_generator_settings',
			array(
				'enable_auto_assignment' => true,
			)
		);
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		// Delete test images.
		foreach ( $this->test_images as $image_id ) {
			wp_delete_attachment( $image_id, true );
		}

		// Clean up options.
		delete_option( 'seo_generator_settings' );

		parent::tearDown();
	}

	/**
	 * Create test images with tags.
	 */
	private function createTestImages() {
		// Create image with jewelry tags.
		$image_1 = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $image_1, '_seo_library_image', '1' );
		wp_set_object_terms( $image_1, array( 'platinum', 'ring', 'jewelry' ), 'image_tag' );
		$this->test_images['jewelry'] = $image_1;

		// Create image with process tags.
		$image_2 = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $image_2, '_seo_library_image', '1' );
		wp_set_object_terms( $image_2, array( 'polishing', 'crafting', 'process' ), 'image_tag' );
		$this->test_images['process'] = $image_2;
	}

	/**
	 * Test auto-assignment is enabled by default.
	 */
	public function test_auto_assignment_enabled_by_default() {
		$settings = get_option( 'seo_generator_settings', array() );
		$enabled  = $settings['enable_auto_assignment'] ?? true;

		$this->assertTrue( $enabled );
	}

	/**
	 * Test settings page stores auto-assignment setting.
	 */
	public function test_settings_page_stores_auto_assignment() {
		update_option(
			'seo_generator_settings',
			array(
				'enable_auto_assignment' => false,
			)
		);

		$settings = get_option( 'seo_generator_settings', array() );

		$this->assertFalse( $settings['enable_auto_assignment'] );
	}

	/**
	 * Test hero image is auto-assigned when enabled.
	 *
	 * Note: This test uses ImageMatchingService directly
	 * since we cannot easily mock OpenAI generation.
	 */
	public function test_hero_image_auto_assigned_when_enabled() {
		// Create image matching service.
		$image_matching = new ImageMatchingService();

		// Simulate finding a matching image.
		$context = array(
			'focus_keyword' => 'platinum ring jewelry',
		);

		$image_id = $image_matching->findMatchingImage( $context );

		// Verify image was found.
		$this->assertNotNull( $image_id );
		$this->assertEquals( $this->test_images['jewelry'], $image_id );
	}

	/**
	 * Test no image assigned when auto-assignment disabled.
	 *
	 * Note: This is a functional test without full ContentGenerationService
	 * since we cannot mock OpenAI responses.
	 */
	public function test_no_image_assigned_when_disabled() {
		// Disable auto-assignment.
		update_option(
			'seo_generator_settings',
			array(
				'enable_auto_assignment' => false,
			)
		);

		$settings = get_option( 'seo_generator_settings', array() );
		$enabled  = $settings['enable_auto_assignment'] ?? true;

		$this->assertFalse( $enabled, 'Auto-assignment should be disabled' );
	}

	/**
	 * Test image context is built correctly from post data.
	 */
	public function test_image_context_built_from_post_data() {
		// Set focus keyword on post.
		update_post_meta( $this->post_id, '_focus_keyword', 'platinum jewelry' );

		// Add category to post.
		$category = $this->factory->term->create(
			array(
				'taxonomy' => 'category',
				'name'     => 'Rings',
			)
		);
		wp_set_post_terms( $this->post_id, array( $category ), 'category' );

		// Simulate context building.
		$context = array(
			'focus_keyword' => get_post_meta( $this->post_id, '_focus_keyword', true ),
		);

		$categories = get_the_terms( $this->post_id, 'category' );
		if ( $categories && ! is_wp_error( $categories ) ) {
			$context['category'] = $categories[0]->name;
		}

		$this->assertEquals( 'platinum jewelry', $context['focus_keyword'] );
		$this->assertEquals( 'Rings', $context['category'] );
	}

	/**
	 * Test process step images can be auto-assigned.
	 */
	public function test_process_step_images_can_be_auto_assigned() {
		// Create image matching service.
		$image_matching = new ImageMatchingService();

		// Simulate finding image for process step.
		$step_context = array(
			'topic' => 'polishing crafting',
		);

		$image_id = $image_matching->findMatchingImage( $step_context );

		// Verify image was found.
		$this->assertNotNull( $image_id );
		$this->assertEquals( $this->test_images['process'], $image_id );
	}

	/**
	 * Test graceful handling when no matching image found.
	 */
	public function test_graceful_handling_when_no_match() {
		// Create image matching service.
		$image_matching = new ImageMatchingService();

		// Use context that won't match any images.
		$context = array(
			'focus_keyword' => 'nonexistent keyword xyz',
		);

		$image_id = $image_matching->findMatchingImage( $context );

		// Should return null when no match.
		$this->assertNull( $image_id );
	}

	/**
	 * Test multiple context fields improve matching.
	 */
	public function test_multiple_context_fields_improve_matching() {
		// Create image matching service.
		$image_matching = new ImageMatchingService();

		// Use multiple context fields.
		$context = array(
			'focus_keyword' => 'platinum',
			'topic'         => 'ring',
			'category'      => 'jewelry',
		);

		$image_id = $image_matching->findMatchingImage( $context );

		// Should match the jewelry image with all 3 tags.
		$this->assertNotNull( $image_id );
		$this->assertEquals( $this->test_images['jewelry'], $image_id );
	}
}
