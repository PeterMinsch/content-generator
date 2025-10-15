<?php
/**
 * Tests for SEOTopic Taxonomy
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Taxonomies;

use SEOGenerator\Taxonomies\SEOTopic;
use SEOGenerator\PostTypes\SEOPage;
use WP_UnitTestCase;

/**
 * SEOTopic test case.
 */
class SEOTopicTest extends WP_UnitTestCase {
	/**
	 * Test that the taxonomy is registered.
	 */
	public function test_taxonomy_is_registered() {
		$seo_topic = new SEOTopic();
		$seo_topic->register();

		$this->assertTrue( taxonomy_exists( SEOTopic::TAXONOMY ) );
	}

	/**
	 * Test that the taxonomy is non-hierarchical.
	 */
	public function test_taxonomy_is_non_hierarchical() {
		$seo_topic = new SEOTopic();
		$seo_topic->register();

		$taxonomy_object = get_taxonomy( SEOTopic::TAXONOMY );

		$this->assertFalse( $taxonomy_object->hierarchical );
	}

	/**
	 * Test that the taxonomy shows in REST API.
	 */
	public function test_taxonomy_shows_in_rest() {
		$seo_topic = new SEOTopic();
		$seo_topic->register();

		$taxonomy_object = get_taxonomy( SEOTopic::TAXONOMY );

		$this->assertTrue( $taxonomy_object->show_in_rest );
		$this->assertEquals( 'seo-topics', $taxonomy_object->rest_base );
	}

	/**
	 * Test that the taxonomy is assigned to seo-page post type.
	 */
	public function test_taxonomy_assigned_to_seo_page() {
		// Register post type first.
		$seo_page = new SEOPage();
		$seo_page->register();

		// Register taxonomy.
		$seo_topic = new SEOTopic();
		$seo_topic->register();

		$taxonomy_object = get_taxonomy( SEOTopic::TAXONOMY );
		$object_types    = $taxonomy_object->object_type;

		$this->assertContains( 'seo-page', $object_types );
	}

	/**
	 * Test creating terms in the taxonomy.
	 */
	public function test_create_seo_topic_term() {
		$seo_topic = new SEOTopic();
		$seo_topic->register();

		$term = wp_insert_term( 'Engagement Rings', SEOTopic::TAXONOMY );

		$this->assertIsArray( $term );
		$this->assertArrayHasKey( 'term_id', $term );

		$term_object = get_term( $term['term_id'], SEOTopic::TAXONOMY );
		$this->assertEquals( 'Engagement Rings', $term_object->name );
	}

	/**
	 * Test that taxonomy has correct labels.
	 */
	public function test_taxonomy_labels() {
		$seo_topic = new SEOTopic();
		$seo_topic->register();

		$taxonomy_object = get_taxonomy( SEOTopic::TAXONOMY );

		$this->assertEquals( 'SEO Topics', $taxonomy_object->labels->name );
		$this->assertEquals( 'SEO Topic', $taxonomy_object->labels->singular_name );
	}
}
