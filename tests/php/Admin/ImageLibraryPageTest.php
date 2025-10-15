<?php
/**
 * Tests for ImageLibraryPage class
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Admin;

use SEOGenerator\Admin\ImageLibraryPage;
use WP_UnitTestCase;

/**
 * Test ImageLibraryPage functionality
 */
class ImageLibraryPageTest extends WP_UnitTestCase {
	/**
	 * ImageLibraryPage instance.
	 *
	 * @var ImageLibraryPage
	 */
	private $library_page;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->library_page = new ImageLibraryPage();

		// Register image_tag taxonomy for tests.
		register_taxonomy(
			'image_tag',
			'attachment',
			array(
				'hierarchical' => false,
				'public'       => true,
			)
		);
	}

	/**
	 * Test getLibraryImages returns only images with meta flag.
	 */
	public function test_getLibraryImages_returns_only_flagged_images() {
		// Create library image (with meta flag).
		$library_image_id = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $library_image_id, '_seo_library_image', '1' );

		// Create regular image (without meta flag).
		$regular_image_id = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );

		// Get library images.
		$query = $this->library_page->getLibraryImages( 1 );

		// Assert only library image is returned.
		$this->assertEquals( 1, $query->post_count );
		$this->assertEquals( $library_image_id, $query->posts[0]->ID );
	}

	/**
	 * Test pagination works correctly.
	 */
	public function test_pagination_limits_results() {
		// Create 25 library images.
		for ( $i = 0; $i < 25; $i++ ) {
			$image_id = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
			update_post_meta( $image_id, '_seo_library_image', '1' );
		}

		// Get first page (should have 20 images).
		$query_page_1 = $this->library_page->getLibraryImages( 1 );
		$this->assertEquals( 20, $query_page_1->post_count );

		// Get second page (should have 5 images).
		$query_page_2 = $this->library_page->getLibraryImages( 2 );
		$this->assertEquals( 5, $query_page_2->post_count );

		// Total should be 25.
		$this->assertEquals( 25, $query_page_1->found_posts );
	}

	/**
	 * Test getImageTags returns correct taxonomy terms.
	 */
	public function test_getImageTags_returns_terms() {
		// Create library image.
		$image_id = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $image_id, '_seo_library_image', '1' );

		// Create and assign tags.
		$tag1 = wp_insert_term( 'Platinum', 'image_tag' );
		$tag2 = wp_insert_term( 'Mens', 'image_tag' );
		wp_set_object_terms( $image_id, array( $tag1['term_id'], $tag2['term_id'] ), 'image_tag' );

		// Get tags.
		$tags = $this->library_page->getImageTags( $image_id );

		// Assert correct tags returned.
		$this->assertCount( 2, $tags );
		$tag_names = array_column( $tags, 'name' );
		$this->assertContains( 'Platinum', $tag_names );
		$this->assertContains( 'Mens', $tag_names );
	}

	/**
	 * Test getImageTags returns empty array for image without tags.
	 */
	public function test_getImageTags_returns_empty_for_no_tags() {
		// Create library image without tags.
		$image_id = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $image_id, '_seo_library_image', '1' );

		// Get tags.
		$tags = $this->library_page->getImageTags( $image_id );

		// Assert empty array.
		$this->assertIsArray( $tags );
		$this->assertEmpty( $tags );
	}

	/**
	 * Test search functionality filters by filename.
	 */
	public function test_searchImages_filters_by_filename() {
		// Create images with specific filenames.
		$image1 = $this->factory->attachment->create_object(
			array(
				'post_title'     => 'platinum-ring',
				'post_mime_type' => 'image/jpeg',
				'file'           => '/path/to/platinum-ring.jpg',
			)
		);
		update_post_meta( $image1, '_seo_library_image', '1' );

		$image2 = $this->factory->attachment->create_object(
			array(
				'post_title'     => 'gold-necklace',
				'post_mime_type' => 'image/jpeg',
				'file'           => '/path/to/gold-necklace.jpg',
			)
		);
		update_post_meta( $image2, '_seo_library_image', '1' );

		// Search for "platinum".
		$query = $this->library_page->searchImages( 'platinum', 1 );

		// Assert only platinum image is returned.
		$this->assertEquals( 1, $query->post_count );
		$this->assertEquals( $image1, $query->posts[0]->ID );
	}

	/**
	 * Test tag filter functionality.
	 */
	public function test_filterByTag_returns_tagged_images() {
		// Create images.
		$image1 = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $image1, '_seo_library_image', '1' );

		$image2 = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_post_meta( $image2, '_seo_library_image', '1' );

		// Create tag and assign to image1 only.
		$tag = wp_insert_term( 'Wedding Band', 'image_tag' );
		wp_set_object_terms( $image1, array( $tag['term_id'] ), 'image_tag' );

		// Filter by tag.
		$query = $this->library_page->filterByTag( 'wedding-band', 1 );

		// Assert only image1 is returned.
		$this->assertEquals( 1, $query->post_count );
		$this->assertEquals( $image1, $query->posts[0]->ID );
	}

	/**
	 * Test getAllTags returns all image tags.
	 */
	public function test_getAllTags_returns_all_terms() {
		// Create tags.
		wp_insert_term( 'Platinum', 'image_tag' );
		wp_insert_term( 'Gold', 'image_tag' );
		wp_insert_term( 'Mens', 'image_tag' );

		// Get all tags.
		$tags = $this->library_page->getAllTags();

		// Assert all tags returned.
		$this->assertCount( 3, $tags );
		$tag_names = array_column( $tags, 'name' );
		$this->assertContains( 'Platinum', $tag_names );
		$this->assertContains( 'Gold', $tag_names );
		$this->assertContains( 'Mens', $tag_names );
	}

	/**
	 * Test render method requires correct capability.
	 */
	public function test_render_requires_edit_posts_capability() {
		// Create subscriber user (no edit_posts capability).
		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		// Expect wp_die to be called.
		$this->expectException( \WPDieException::class );

		// Try to render.
		$this->library_page->render();
	}

	/**
	 * Test render method works for authorized user.
	 */
	public function test_render_works_for_authorized_user() {
		// Create editor user (has edit_posts capability).
		$editor = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		// Create template file mock.
		add_filter( 'plugin_dir_path', function() {
			return __DIR__ . '/../../fixtures/';
		} );

		// Render should not throw exception.
		ob_start();
		try {
			$this->library_page->render();
			$output = ob_get_clean();
			// Template should load (or show error if template missing).
			$this->assertNotEmpty( $output );
		} catch ( \Exception $e ) {
			ob_end_clean();
			$this->fail( 'Render should not throw exception for authorized user: ' . $e->getMessage() );
		}
	}
}
