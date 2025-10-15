<?php
/**
 * Image Download Integration Tests
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Integration;

use SEOGenerator\Services\ImageDownloadService;
use SEOGenerator\Services\ImportService;
use WP_UnitTestCase;

/**
 * Integration tests for image download workflow.
 *
 * These tests verify the full image download flow including
 * ImportService integration and Media Library operations.
 */
class ImageDownloadTest extends WP_UnitTestCase {
	/**
	 * ImageDownloadService instance.
	 *
	 * @var ImageDownloadService
	 */
	private $service;

	/**
	 * Test post ID.
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Setup before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->service = new ImageDownloadService();

		// Create test post.
		$this->post_id = $this->factory->post->create(
			array(
				'post_type'  => 'seo-page',
				'post_title' => 'Integration Test Post',
			)
		);
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		// Clean up test post and attachments.
		if ( $this->post_id ) {
			// Delete all attachments for this post.
			$attachments = get_posts(
				array(
					'post_type'      => 'attachment',
					'post_parent'    => $this->post_id,
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);

			foreach ( $attachments as $attachment_id ) {
				wp_delete_attachment( $attachment_id, true );
			}

			wp_delete_post( $this->post_id, true );
		}
		parent::tearDown();
	}

	/**
	 * Test that attachment is created in Media Library.
	 */
	public function test_attachment_created_in_media_library() {
		// Create a fake attachment to simulate download.
		$attachment_id = $this->factory->attachment->create_object(
			array(
				'file'           => '/path/to/test-integration.jpg',
				'post_parent'    => $this->post_id,
				'post_mime_type' => 'image/jpeg',
				'post_title'     => 'Integration Test Image',
			)
		);

		// Verify attachment exists.
		$attachment = get_post( $attachment_id );
		$this->assertNotNull( $attachment, 'Attachment should exist' );
		$this->assertEquals( 'attachment', $attachment->post_type, 'Should be attachment post type' );
		$this->assertEquals( 'image/jpeg', $attachment->post_mime_type, 'Should have image MIME type' );
	}

	/**
	 * Test that image is attached to correct post.
	 */
	public function test_image_attached_to_post() {
		// Create attachment.
		$attachment_id = $this->factory->attachment->create_object(
			array(
				'file'           => '/path/to/test-attached.jpg',
				'post_parent'    => $this->post_id,
				'post_mime_type' => 'image/jpeg',
				'post_title'     => 'Attached Image',
			)
		);

		// Verify post_parent is set.
		$attachment = get_post( $attachment_id );
		$this->assertEquals( $this->post_id, $attachment->post_parent, 'Attachment should be attached to post' );
	}

	/**
	 * Test ACF field is updated correctly.
	 */
	public function test_acf_field_updated() {
		if ( ! function_exists( 'update_field' ) ) {
			$this->markTestSkipped( 'ACF is not active' );
			return;
		}

		// Create attachment.
		$attachment_id = $this->factory->attachment->create_object(
			array(
				'file'           => '/path/to/test-acf.jpg',
				'post_mime_type' => 'image/jpeg',
				'post_title'     => 'ACF Test Image',
			)
		);

		// Update ACF field.
		update_field( 'hero_image', $attachment_id, $this->post_id );

		// Verify ACF field.
		$hero_image = get_field( 'hero_image', $this->post_id );
		$this->assertEquals( $attachment_id, $hero_image, 'ACF hero_image field should be set' );
	}

	/**
	 * Test featured image is set correctly.
	 */
	public function test_featured_image_set() {
		// Create attachment.
		$attachment_id = $this->factory->attachment->create_object(
			array(
				'file'           => '/path/to/test-featured.jpg',
				'post_mime_type' => 'image/jpeg',
				'post_title'     => 'Featured Image',
			)
		);

		// Set as featured image.
		set_post_thumbnail( $this->post_id, $attachment_id );

		// Verify.
		$thumbnail_id = get_post_thumbnail_id( $this->post_id );
		$this->assertEquals( $attachment_id, $thumbnail_id, 'Featured image should be set' );

		// Verify post meta.
		$meta_thumbnail = get_post_meta( $this->post_id, '_thumbnail_id', true );
		$this->assertEquals( $attachment_id, $meta_thumbnail, '_thumbnail_id meta should be set' );
	}

	/**
	 * Test error recovery - import continues after failed download.
	 */
	public function test_error_recovery_continues_import() {
		$import_service = new ImportService(
			array(
				'batch_size'       => 10,
				'check_duplicates' => false,
			)
		);

		// Create CSV data with invalid image URL.
		$rows    = array(
			array( 'Test Post 1', 'keyword1', 'Topic A', 'invalid-url' ),
			array( 'Test Post 2', 'keyword2', 'Topic B', 'https://example.com/404.jpg' ),
		);
		$headers = array( 'Page Title', 'Focus Keyword', 'Topic Category', 'Image URL' );
		$mapping = array(
			'Page Title'     => 'page_title',
			'Focus Keyword'  => 'focus_keyword',
			'Topic Category' => 'topic_category',
			'Image URL'      => 'image_url',
		);

		// Mock wp_remote_head to fail for 404.jpg.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				if ( strpos( $url, '404.jpg' ) !== false ) {
					return array(
						'response' => array( 'code' => 404 ),
						'headers'  => array(),
						'body'     => '',
					);
				}
				return $preempt;
			},
			10,
			3
		);

		// Process batch.
		$result = $import_service->processSingleBatch( $rows, $headers, $mapping );

		// Verify both posts were created despite image errors.
		$this->assertCount( 2, $result['created'], 'Both posts should be created' );
		$this->assertCount( 2, $result['images']['failed'], 'Both image downloads should fail' );

		// Verify posts exist.
		$post1 = get_page_by_title( 'Test Post 1', OBJECT, 'seo-page' );
		$post2 = get_page_by_title( 'Test Post 2', OBJECT, 'seo-page' );

		$this->assertNotNull( $post1, 'Post 1 should exist' );
		$this->assertNotNull( $post2, 'Post 2 should exist' );

		// Clean up.
		if ( $post1 ) {
			wp_delete_post( $post1->ID, true );
		}
		if ( $post2 ) {
			wp_delete_post( $post2->ID, true );
		}
	}

	/**
	 * Test ImportService tracks image statistics.
	 */
	public function test_import_service_tracks_image_stats() {
		$import_service = new ImportService(
			array(
				'batch_size'       => 10,
				'check_duplicates' => false,
			)
		);

		// Create CSV data with image URLs.
		$rows    = array(
			array( 'Post With Image', 'keyword1', 'Topic A', 'https://example.com/image1.jpg' ),
		);
		$headers = array( 'Page Title', 'Focus Keyword', 'Topic Category', 'Image URL' );
		$mapping = array(
			'Page Title'     => 'page_title',
			'Focus Keyword'  => 'focus_keyword',
			'Topic Category' => 'topic_category',
			'Image URL'      => 'image_url',
		);

		// Mock wp_remote_head to fail (to test tracking).
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				if ( strpos( $url, 'example.com' ) !== false ) {
					return array(
						'response' => array( 'code' => 404 ),
						'headers'  => array(),
						'body'     => '',
					);
				}
				return $preempt;
			},
			10,
			3
		);

		// Process batch.
		$result = $import_service->processSingleBatch( $rows, $headers, $mapping );

		// Verify image tracking structure.
		$this->assertArrayHasKey( 'images', $result, 'Result should have images key' );
		$this->assertArrayHasKey( 'downloaded', $result['images'], 'Should track downloaded' );
		$this->assertArrayHasKey( 'reused', $result['images'], 'Should track reused' );
		$this->assertArrayHasKey( 'failed', $result['images'], 'Should track failed' );

		// Should have 1 failed image.
		$this->assertCount( 1, $result['images']['failed'], 'Should have 1 failed image' );

		// Clean up.
		$post = get_page_by_title( 'Post With Image', OBJECT, 'seo-page' );
		if ( $post ) {
			wp_delete_post( $post->ID, true );
		}
	}

	/**
	 * Test duplicate detection with ImportService.
	 */
	public function test_duplicate_detection_integration() {
		$image_url = 'https://example.com/duplicate-integration.jpg';

		// Create existing attachment with source URL.
		$existing_id = $this->factory->attachment->create_object(
			array(
				'file'           => '/path/to/duplicate-integration.jpg',
				'post_mime_type' => 'image/jpeg',
				'post_title'     => 'Existing Image',
			)
		);
		update_post_meta( $existing_id, '_source_url', $image_url );
		update_post_meta( $existing_id, '_seo_imported_image', 1 );
		update_post_meta( $existing_id, '_import_date', '2025-10-01 10:00:00' ); // Old date.

		// Mock wp_remote_head to pass validation.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( $image_url ) {
				if ( strpos( $url, 'example.com' ) !== false ) {
					return array(
						'response' => array( 'code' => 200 ),
						'headers'  => array(
							'content-type'   => 'image/jpeg',
							'content-length' => '1024',
						),
						'body'     => '',
					);
				}
				return $preempt;
			},
			10,
			3
		);

		// Import CSV with same image URL.
		$import_service = new ImportService(
			array(
				'batch_size'       => 10,
				'check_duplicates' => false,
			)
		);

		$rows    = array(
			array( 'New Post', 'keyword', 'Topic', $image_url ),
		);
		$headers = array( 'Page Title', 'Focus Keyword', 'Topic Category', 'Image URL' );
		$mapping = array(
			'Page Title'     => 'page_title',
			'Focus Keyword'  => 'focus_keyword',
			'Topic Category' => 'topic_category',
			'Image URL'      => 'image_url',
		);

		$result = $import_service->processSingleBatch( $rows, $headers, $mapping );

		// Should have reused existing image.
		$this->assertCount( 1, $result['images']['reused'], 'Should reuse existing image' );
		$this->assertEquals( $existing_id, $result['images']['reused'][0]['attachment_id'], 'Should reuse correct attachment' );

		// Clean up.
		$post = get_page_by_title( 'New Post', OBJECT, 'seo-page' );
		if ( $post ) {
			wp_delete_post( $post->ID, true );
		}
		wp_delete_attachment( $existing_id, true );
	}

	/**
	 * Test metadata is saved correctly.
	 */
	public function test_metadata_saved() {
		$source_url    = 'https://example.com/metadata-test.jpg';
		$attachment_id = $this->factory->attachment->create_object(
			array(
				'file'           => '/path/to/metadata-test.jpg',
				'post_mime_type' => 'image/jpeg',
				'post_title'     => 'Metadata Test',
			)
		);

		// Set metadata.
		update_post_meta( $attachment_id, '_source_url', $source_url );
		update_post_meta( $attachment_id, '_seo_imported_image', 1 );
		update_post_meta( $attachment_id, '_import_date', current_time( 'mysql' ) );
		update_post_meta( $attachment_id, '_imported_for_post', $this->post_id );

		// Verify all metadata.
		$saved_source = get_post_meta( $attachment_id, '_source_url', true );
		$is_imported  = get_post_meta( $attachment_id, '_seo_imported_image', true );
		$import_date  = get_post_meta( $attachment_id, '_import_date', true );
		$for_post     = get_post_meta( $attachment_id, '_imported_for_post', true );

		$this->assertEquals( $source_url, $saved_source, '_source_url should match' );
		$this->assertEquals( '1', $is_imported, '_seo_imported_image should be 1' );
		$this->assertNotEmpty( $import_date, '_import_date should be set' );
		$this->assertEquals( $this->post_id, $for_post, '_imported_for_post should match' );

		// Clean up.
		wp_delete_attachment( $attachment_id, true );
	}
}
