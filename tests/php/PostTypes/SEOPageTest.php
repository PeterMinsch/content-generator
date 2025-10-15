<?php
/**
 * Tests for SEOPage Custom Post Type
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\PostTypes;

use SEOGenerator\PostTypes\SEOPage;
use WP_UnitTestCase;

/**
 * SEOPage test case.
 */
class SEOPageTest extends WP_UnitTestCase {
	/**
	 * Test that the post type is registered.
	 */
	public function test_post_type_is_registered() {
		$seo_page = new SEOPage();
		$seo_page->register();

		$this->assertTrue( post_type_exists( SEOPage::POST_TYPE ) );
	}

	/**
	 * Test that the post type has correct configuration.
	 */
	public function test_post_type_configuration() {
		$seo_page = new SEOPage();
		$seo_page->register();

		$post_type_object = get_post_type_object( SEOPage::POST_TYPE );

		// Test supports.
		$this->assertTrue( post_type_supports( SEOPage::POST_TYPE, 'title' ) );
		$this->assertTrue( post_type_supports( SEOPage::POST_TYPE, 'editor' ) );
		$this->assertTrue( post_type_supports( SEOPage::POST_TYPE, 'thumbnail' ) );
		$this->assertTrue( post_type_supports( SEOPage::POST_TYPE, 'revisions' ) );

		// Test REST API support.
		$this->assertTrue( $post_type_object->show_in_rest );
		$this->assertEquals( 'seo-pages', $post_type_object->rest_base );

		// Test public visibility.
		$this->assertTrue( $post_type_object->public );
		$this->assertTrue( $post_type_object->publicly_queryable );

		// Test hierarchical.
		$this->assertFalse( $post_type_object->hierarchical );

		// Test archive.
		$this->assertFalse( $post_type_object->has_archive );
	}

	/**
	 * Test creating an SEO page post.
	 */
	public function test_create_seo_page() {
		$seo_page = new SEOPage();
		$seo_page->register();

		$post_id = $this->factory->post->create(
			array(
				'post_type'  => SEOPage::POST_TYPE,
				'post_title' => 'Test SEO Page',
			)
		);

		$this->assertIsInt( $post_id );
		$this->assertGreaterThan( 0, $post_id );

		$post = get_post( $post_id );
		$this->assertEquals( SEOPage::POST_TYPE, $post->post_type );
		$this->assertEquals( 'Test SEO Page', $post->post_title );
	}

	/**
	 * Test that post type has correct labels.
	 */
	public function test_post_type_labels() {
		$seo_page = new SEOPage();
		$seo_page->register();

		$post_type_object = get_post_type_object( SEOPage::POST_TYPE );

		$this->assertEquals( 'SEO Pages', $post_type_object->labels->name );
		$this->assertEquals( 'SEO Page', $post_type_object->labels->singular_name );
	}
}
