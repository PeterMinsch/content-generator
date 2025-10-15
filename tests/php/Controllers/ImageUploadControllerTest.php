<?php
/**
 * Tests for ImageUploadController class
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Controllers;

use SEOGenerator\Controllers\ImageUploadController;
use WP_UnitTestCase;
use WP_REST_Request;

/**
 * Test ImageUploadController functionality
 */
class ImageUploadControllerTest extends WP_UnitTestCase {
	/**
	 * ImageUploadController instance.
	 *
	 * @var ImageUploadController
	 */
	private $controller;

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	private $editor_id;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->controller = new ImageUploadController();
		$this->controller->register_routes();

		// Create editor user (has edit_posts capability).
		$this->editor_id = $this->factory->user->create( array( 'role' => 'editor' ) );
	}

	/**
	 * Test routes are registered.
	 */
	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/seo-generator/v1/images', $routes );
		$this->assertCount( 1, $routes['/seo-generator/v1/images'] );
	}

	/**
	 * Test upload endpoint requires authentication.
	 */
	public function test_upload_requires_authentication() {
		$request = new WP_REST_Request( 'POST', '/seo-generator/v1/images' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test upload endpoint requires edit_posts capability.
	 */
	public function test_upload_requires_edit_posts_capability() {
		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$request = new WP_REST_Request( 'POST', '/seo-generator/v1/images' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test upload without file returns error.
	 */
	public function test_upload_without_file_returns_error() {
		wp_set_current_user( $this->editor_id );

		$request = new WP_REST_Request( 'POST', '/seo-generator/v1/images' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'no_file', $data['code'] );
	}

	/**
	 * Test valid file upload succeeds.
	 *
	 * @runInSeparateProcess
	 */
	public function test_valid_file_upload_succeeds() {
		wp_set_current_user( $this->editor_id );

		// Create temporary test image.
		$upload_dir = wp_upload_dir();
		$tmp_file   = tempnam( $upload_dir['tmp'], 'test' );
		$image      = imagecreatetruecolor( 100, 100 );
		imagejpeg( $image, $tmp_file );
		imagedestroy( $image );

		// Mock $_FILES.
		$_FILES['file'] = array(
			'name'     => 'test-image.jpg',
			'type'     => 'image/jpeg',
			'tmp_name' => $tmp_file,
			'error'    => UPLOAD_ERR_OK,
			'size'     => filesize( $tmp_file ),
		);

		$request = new WP_REST_Request( 'POST', '/seo-generator/v1/images' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertArrayHasKey( 'id', $data['data'] );

		// Verify attachment was created.
		$attachment_id = $data['data']['id'];
		$this->assertNotFalse( get_post( $attachment_id ) );

		// Verify meta flag was set.
		$meta = get_post_meta( $attachment_id, '_seo_library_image', true );
		$this->assertEquals( '1', $meta );

		// Cleanup.
		wp_delete_attachment( $attachment_id, true );
		if ( file_exists( $tmp_file ) ) {
			unlink( $tmp_file );
		}
	}

	/**
	 * Test invalid file type is rejected.
	 *
	 * @runInSeparateProcess
	 */
	public function test_invalid_file_type_rejected() {
		wp_set_current_user( $this->editor_id );

		// Create temporary PDF file.
		$tmp_file = tempnam( sys_get_temp_dir(), 'test' );
		file_put_contents( $tmp_file, 'fake PDF content' );

		// Mock $_FILES with PDF.
		$_FILES['file'] = array(
			'name'     => 'document.pdf',
			'type'     => 'application/pdf',
			'tmp_name' => $tmp_file,
			'error'    => UPLOAD_ERR_OK,
			'size'     => filesize( $tmp_file ),
		);

		$request = new WP_REST_Request( 'POST', '/seo-generator/v1/images' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'invalid_file_type', $data['code'] );

		// Cleanup.
		if ( file_exists( $tmp_file ) ) {
			unlink( $tmp_file );
		}
	}

	/**
	 * Test file too large is rejected.
	 *
	 * @runInSeparateProcess
	 */
	public function test_file_too_large_rejected() {
		wp_set_current_user( $this->editor_id );

		// Create temporary large file.
		$tmp_file = tempnam( sys_get_temp_dir(), 'test' );
		file_put_contents( $tmp_file, str_repeat( 'x', 1024 ) );

		// Mock $_FILES with size larger than max.
		$max_size = wp_max_upload_size();

		$_FILES['file'] = array(
			'name'     => 'large-image.jpg',
			'type'     => 'image/jpeg',
			'tmp_name' => $tmp_file,
			'error'    => UPLOAD_ERR_OK,
			'size'     => $max_size + 1,
		);

		$request = new WP_REST_Request( 'POST', '/seo-generator/v1/images' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'file_too_large', $data['code'] );

		// Cleanup.
		if ( file_exists( $tmp_file ) ) {
			unlink( $tmp_file );
		}
	}

	/**
	 * Test check_upload_permission method.
	 */
	public function test_check_upload_permission() {
		wp_set_current_user( $this->editor_id );

		$request = new WP_REST_Request( 'POST', '/seo-generator/v1/images' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$permission = $this->controller->check_upload_permission( $request );

		$this->assertTrue( $permission );
	}

	/**
	 * Test invalid nonce is rejected.
	 */
	public function test_invalid_nonce_rejected() {
		wp_set_current_user( $this->editor_id );

		$request = new WP_REST_Request( 'POST', '/seo-generator/v1/images' );
		$request->set_header( 'X-WP-Nonce', 'invalid_nonce' );

		$permission = $this->controller->check_upload_permission( $request );

		$this->assertInstanceOf( 'WP_Error', $permission );
		$this->assertEquals( 'invalid_nonce', $permission->get_error_code() );
	}

	/**
	 * Test prepare_attachment_response returns correct data.
	 */
	public function test_prepare_attachment_response() {
		// Create test attachment.
		$attachment_id = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->controller );
		$method     = $reflection->getMethod( 'prepare_attachment_response' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->controller, $attachment_id );

		$this->assertArrayHasKey( 'id', $result );
		$this->assertArrayHasKey( 'title', $result );
		$this->assertArrayHasKey( 'filename', $result );
		$this->assertArrayHasKey( 'url', $result );
		$this->assertArrayHasKey( 'thumbnail', $result );
		$this->assertArrayHasKey( 'mime_type', $result );
		$this->assertArrayHasKey( 'size', $result );

		$this->assertEquals( $attachment_id, $result['id'] );

		// Cleanup.
		wp_delete_attachment( $attachment_id, true );
	}

	/**
	 * Test tag management routes are registered.
	 */
	public function test_tag_routes_registered() {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/seo-generator/v1/images/(?P<id>\d+)/tags', $routes );
		$this->assertArrayHasKey( '/seo-generator/v1/images/bulk-tags', $routes );
	}

	/**
	 * Test update_image_tags requires authentication.
	 */
	public function test_update_tags_requires_authentication() {
		$attachment_id = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );

		$request = new WP_REST_Request( 'PUT', '/seo-generator/v1/images/' . $attachment_id . '/tags' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );

		// Cleanup.
		wp_delete_attachment( $attachment_id, true );
	}

	/**
	 * Test update_image_tags adds tags to image.
	 */
	public function test_update_tags_adds_tags() {
		wp_set_current_user( $this->editor_id );

		$attachment_id = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );

		$request = new WP_REST_Request( 'PUT', '/seo-generator/v1/images/' . $attachment_id . '/tags' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'add', array( 'platinum', 'mens' ) );
		$request->set_param( 'remove', array() );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'tags', $data );
		$this->assertCount( 2, $data['tags'] );

		// Verify tags were assigned.
		$tags = wp_get_object_terms( $attachment_id, 'image_tag', array( 'fields' => 'slugs' ) );
		$this->assertContains( 'platinum', $tags );
		$this->assertContains( 'mens', $tags );

		// Cleanup.
		wp_delete_attachment( $attachment_id, true );
	}

	/**
	 * Test update_image_tags removes tags from image.
	 */
	public function test_update_tags_removes_tags() {
		wp_set_current_user( $this->editor_id );

		$attachment_id = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );

		// Assign initial tags.
		wp_set_object_terms( $attachment_id, array( 'platinum', 'mens', 'classic' ), 'image_tag' );

		$request = new WP_REST_Request( 'PUT', '/seo-generator/v1/images/' . $attachment_id . '/tags' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'add', array() );
		$request->set_param( 'remove', array( 'mens' ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertCount( 2, $data['tags'] );

		// Verify tag was removed.
		$tags = wp_get_object_terms( $attachment_id, 'image_tag', array( 'fields' => 'slugs' ) );
		$this->assertNotContains( 'mens', $tags );
		$this->assertContains( 'platinum', $tags );
		$this->assertContains( 'classic', $tags );

		// Cleanup.
		wp_delete_attachment( $attachment_id, true );
	}

	/**
	 * Test update_image_tags with invalid image ID returns 404.
	 */
	public function test_update_tags_invalid_image_id() {
		wp_set_current_user( $this->editor_id );

		$request = new WP_REST_Request( 'PUT', '/seo-generator/v1/images/999999/tags' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'add', array( 'platinum' ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'invalid_image', $data['code'] );
	}

	/**
	 * Test update_image_tags adds and removes in same request.
	 */
	public function test_update_tags_add_and_remove() {
		wp_set_current_user( $this->editor_id );

		$attachment_id = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );

		// Assign initial tags.
		wp_set_object_terms( $attachment_id, array( 'gold', 'womens' ), 'image_tag' );

		$request = new WP_REST_Request( 'PUT', '/seo-generator/v1/images/' . $attachment_id . '/tags' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'add', array( 'platinum', 'mens' ) );
		$request->set_param( 'remove', array( 'gold', 'womens' ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertCount( 2, $data['tags'] );

		// Verify correct tags remain.
		$tags = wp_get_object_terms( $attachment_id, 'image_tag', array( 'fields' => 'slugs' ) );
		$this->assertContains( 'platinum', $tags );
		$this->assertContains( 'mens', $tags );
		$this->assertNotContains( 'gold', $tags );
		$this->assertNotContains( 'womens', $tags );

		// Cleanup.
		wp_delete_attachment( $attachment_id, true );
	}

	/**
	 * Test bulk_update_tags requires authentication.
	 */
	public function test_bulk_update_tags_requires_authentication() {
		$request = new WP_REST_Request( 'POST', '/seo-generator/v1/images/bulk-tags' );
		$request->set_param( 'image_ids', array( 1, 2, 3 ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test bulk_update_tags adds tags to multiple images.
	 */
	public function test_bulk_update_tags_adds_tags() {
		wp_set_current_user( $this->editor_id );

		// Create 3 test attachments.
		$attachment_id_1 = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		$attachment_id_2 = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		$attachment_id_3 = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );

		$request = new WP_REST_Request( 'POST', '/seo-generator/v1/images/bulk-tags' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'image_ids', array( $attachment_id_1, $attachment_id_2, $attachment_id_3 ) );
		$request->set_param( 'add', array( 'wedding', 'ring' ) );
		$request->set_param( 'remove', array() );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertEquals( 3, $data['updated'] );

		// Verify tags were assigned to all images.
		foreach ( array( $attachment_id_1, $attachment_id_2, $attachment_id_3 ) as $id ) {
			$tags = wp_get_object_terms( $id, 'image_tag', array( 'fields' => 'slugs' ) );
			$this->assertContains( 'wedding', $tags );
			$this->assertContains( 'ring', $tags );
		}

		// Cleanup.
		wp_delete_attachment( $attachment_id_1, true );
		wp_delete_attachment( $attachment_id_2, true );
		wp_delete_attachment( $attachment_id_3, true );
	}

	/**
	 * Test bulk_update_tags removes tags from multiple images.
	 */
	public function test_bulk_update_tags_removes_tags() {
		wp_set_current_user( $this->editor_id );

		// Create 2 test attachments with tags.
		$attachment_id_1 = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		$attachment_id_2 = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );

		wp_set_object_terms( $attachment_id_1, array( 'platinum', 'mens', 'classic' ), 'image_tag' );
		wp_set_object_terms( $attachment_id_2, array( 'platinum', 'womens', 'modern' ), 'image_tag' );

		$request = new WP_REST_Request( 'POST', '/seo-generator/v1/images/bulk-tags' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'image_ids', array( $attachment_id_1, $attachment_id_2 ) );
		$request->set_param( 'add', array() );
		$request->set_param( 'remove', array( 'platinum' ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 2, $data['updated'] );

		// Verify platinum was removed from both.
		$tags_1 = wp_get_object_terms( $attachment_id_1, 'image_tag', array( 'fields' => 'slugs' ) );
		$tags_2 = wp_get_object_terms( $attachment_id_2, 'image_tag', array( 'fields' => 'slugs' ) );

		$this->assertNotContains( 'platinum', $tags_1 );
		$this->assertNotContains( 'platinum', $tags_2 );
		$this->assertContains( 'mens', $tags_1 );
		$this->assertContains( 'womens', $tags_2 );

		// Cleanup.
		wp_delete_attachment( $attachment_id_1, true );
		wp_delete_attachment( $attachment_id_2, true );
	}

	/**
	 * Test bulk_update_tags with empty image_ids returns error.
	 */
	public function test_bulk_update_tags_empty_images() {
		wp_set_current_user( $this->editor_id );

		$request = new WP_REST_Request( 'POST', '/seo-generator/v1/images/bulk-tags' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'image_ids', array() );
		$request->set_param( 'add', array( 'platinum' ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'missing_images', $data['code'] );
	}

	/**
	 * Test bulk_update_tags handles invalid image IDs gracefully.
	 */
	public function test_bulk_update_tags_with_invalid_ids() {
		wp_set_current_user( $this->editor_id );

		$valid_attachment = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );

		$request = new WP_REST_Request( 'POST', '/seo-generator/v1/images/bulk-tags' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'image_ids', array( $valid_attachment, 999999, 888888 ) );
		$request->set_param( 'add', array( 'platinum' ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 1, $data['updated'] ); // Only 1 valid image.
		$this->assertCount( 2, $data['errors'] ); // 2 invalid IDs.

		// Verify valid image got the tag.
		$tags = wp_get_object_terms( $valid_attachment, 'image_tag', array( 'fields' => 'slugs' ) );
		$this->assertContains( 'platinum', $tags );

		// Cleanup.
		wp_delete_attachment( $valid_attachment, true );
	}
}
