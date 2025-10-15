<?php
/**
 * Integration Tests for Default Image Settings
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Integration;

use SEOGenerator\Services\ImageMatchingService;
use WP_UnitTestCase;

/**
 * Test default image settings and integration with ImageMatchingService
 */
class DefaultImageSettingsTest extends WP_UnitTestCase {
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

		// Create test images.
		$this->createTestImages();

		// Clear any existing image settings.
		delete_option( 'seo_generator_image_settings' );
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
		delete_option( 'seo_generator_image_settings' );

		parent::tearDown();
	}

	/**
	 * Create test images.
	 */
	private function createTestImages() {
		// Create default image.
		$default_image = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $default_image, '_seo_library_image', '1' );
		$this->test_images['default'] = $default_image;

		// Create tagged image.
		$tagged_image = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $tagged_image, '_seo_library_image', '1' );
		wp_set_object_terms( $tagged_image, array( 'platinum', 'ring' ), 'image_tag' );
		$this->test_images['tagged'] = $tagged_image;
	}

	/**
	 * Test default image setting saves correctly.
	 */
	public function test_default_image_setting_saves() {
		$default_id = $this->test_images['default'];

		update_option(
			'seo_generator_image_settings',
			array(
				'default_image_id' => $default_id,
			)
		);

		$settings = get_option( 'seo_generator_image_settings', array() );

		$this->assertEquals( $default_id, $settings['default_image_id'] );
	}

	/**
	 * Test ImageMatchingService retrieves default image.
	 */
	public function test_image_matching_service_retrieves_default() {
		$default_id = $this->test_images['default'];

		update_option(
			'seo_generator_image_settings',
			array(
				'default_image_id' => $default_id,
			)
		);

		$service = new ImageMatchingService();

		// Use context that won't match any tags.
		$context = array(
			'focus_keyword' => 'nonexistent keyword xyz',
		);

		$result = $service->findMatchingImage( $context );

		$this->assertEquals( $default_id, $result, 'Should return default image when no matches found' );
	}

	/**
	 * Test null default handled gracefully.
	 */
	public function test_null_default_handled_gracefully() {
		// Don't set any default image.
		$service = new ImageMatchingService();

		// Use context that won't match any tags.
		$context = array(
			'focus_keyword' => 'nonexistent keyword xyz',
		);

		$result = $service->findMatchingImage( $context );

		$this->assertNull( $result, 'Should return null when no matches and no default' );
	}

	/**
	 * Test invalid attachment ID handled gracefully.
	 */
	public function test_invalid_attachment_id_handled_gracefully() {
		// Set a non-existent image ID.
		update_option(
			'seo_generator_image_settings',
			array(
				'default_image_id' => 9999999,
			)
		);

		$service = new ImageMatchingService();

		// Use context that won't match any tags.
		$context = array(
			'focus_keyword' => 'nonexistent keyword xyz',
		);

		$result = $service->findMatchingImage( $context );

		$this->assertNull( $result, 'Should return null when default image does not exist' );
	}

	/**
	 * Test default used when no matches found.
	 */
	public function test_default_used_when_no_matches() {
		$default_id = $this->test_images['default'];

		update_option(
			'seo_generator_image_settings',
			array(
				'default_image_id' => $default_id,
			)
		);

		$service = new ImageMatchingService();

		// Use context that won't match any tags.
		$context = array(
			'focus_keyword' => 'diamond sapphire emerald',
		);

		$result = $service->findMatchingImage( $context );

		$this->assertEquals( $default_id, $result, 'Should return default when no tag matches' );
	}

	/**
	 * Test tag match preferred over default.
	 */
	public function test_tag_match_preferred_over_default() {
		$default_id = $this->test_images['default'];
		$tagged_id  = $this->test_images['tagged'];

		update_option(
			'seo_generator_image_settings',
			array(
				'default_image_id' => $default_id,
			)
		);

		$service = new ImageMatchingService();

		// Use context that matches tags on tagged image.
		$context = array(
			'focus_keyword' => 'platinum ring',
		);

		$result = $service->findMatchingImage( $context );

		$this->assertEquals( $tagged_id, $result, 'Should prefer tagged image over default' );
	}

	/**
	 * Test clearing default image.
	 */
	public function test_clearing_default_image() {
		// First set a default.
		update_option(
			'seo_generator_image_settings',
			array(
				'default_image_id' => $this->test_images['default'],
			)
		);

		// Verify it's set.
		$settings = get_option( 'seo_generator_image_settings', array() );
		$this->assertNotNull( $settings['default_image_id'] );

		// Clear it.
		update_option(
			'seo_generator_image_settings',
			array(
				'default_image_id' => null,
			)
		);

		// Verify it's cleared.
		$settings = get_option( 'seo_generator_image_settings', array() );
		$this->assertNull( $settings['default_image_id'] );

		// Test ImageMatchingService returns null.
		$service = new ImageMatchingService();
		$context = array(
			'focus_keyword' => 'nonexistent',
		);
		$result = $service->findMatchingImage( $context );

		$this->assertNull( $result, 'Should return null when default is cleared' );
	}

	/**
	 * Test empty context uses default.
	 */
	public function test_empty_context_uses_default() {
		$default_id = $this->test_images['default'];

		update_option(
			'seo_generator_image_settings',
			array(
				'default_image_id' => $default_id,
			)
		);

		$service = new ImageMatchingService();

		// Empty context.
		$context = array();

		$result = $service->findMatchingImage( $context );

		$this->assertEquals( $default_id, $result, 'Should return default with empty context' );
	}

	/**
	 * Test settings sanitization validates attachment.
	 */
	public function test_settings_sanitization_validates_attachment() {
		// This test verifies the sanitization logic in SettingsPage.
		$settings_page = new \SEOGenerator\Admin\SettingsPage();

		// Test with valid image ID.
		$input = array(
			'default_image_id' => $this->test_images['default'],
		);

		$sanitized = $settings_page->sanitizeImageSettings( $input );

		$this->assertEquals( $this->test_images['default'], $sanitized['default_image_id'], 'Valid image ID should pass sanitization' );

		// Test with invalid image ID.
		$input = array(
			'default_image_id' => 9999999,
		);

		$sanitized = $settings_page->sanitizeImageSettings( $input );

		$this->assertNull( $sanitized['default_image_id'], 'Invalid image ID should be sanitized to null' );
	}
}
