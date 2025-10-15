<?php
/**
 * Tests for ImageTag Taxonomy
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Taxonomies;

use SEOGenerator\Taxonomies\ImageTag;
use WP_UnitTestCase;

/**
 * ImageTag test case.
 */
class ImageTagTest extends WP_UnitTestCase {
	/**
	 * Test that the taxonomy is registered.
	 */
	public function test_taxonomy_is_registered() {
		$image_tag = new ImageTag();
		$image_tag->register();

		$this->assertTrue( taxonomy_exists( ImageTag::TAXONOMY ) );
	}

	/**
	 * Test that the taxonomy is non-hierarchical.
	 */
	public function test_taxonomy_is_non_hierarchical() {
		$image_tag = new ImageTag();
		$image_tag->register();

		$taxonomy_object = get_taxonomy( ImageTag::TAXONOMY );

		$this->assertFalse( $taxonomy_object->hierarchical );
	}

	/**
	 * Test that the taxonomy shows in REST API.
	 */
	public function test_taxonomy_shows_in_rest() {
		$image_tag = new ImageTag();
		$image_tag->register();

		$taxonomy_object = get_taxonomy( ImageTag::TAXONOMY );

		$this->assertTrue( $taxonomy_object->show_in_rest );
		$this->assertEquals( 'image-tags', $taxonomy_object->rest_base );
	}

	/**
	 * Test that the taxonomy is assigned to attachment post type.
	 */
	public function test_taxonomy_assigned_to_attachment() {
		$image_tag = new ImageTag();
		$image_tag->register();

		$taxonomy_object = get_taxonomy( ImageTag::TAXONOMY );
		$object_types    = $taxonomy_object->object_type;

		$this->assertContains( 'attachment', $object_types );
	}

	/**
	 * Test creating terms in the taxonomy.
	 */
	public function test_create_image_tag_term() {
		$image_tag = new ImageTag();
		$image_tag->register();

		$term = wp_insert_term( 'platinum', ImageTag::TAXONOMY );

		$this->assertIsArray( $term );
		$this->assertArrayHasKey( 'term_id', $term );

		$term_object = get_term( $term['term_id'], ImageTag::TAXONOMY );
		$this->assertEquals( 'platinum', $term_object->name );
	}

	/**
	 * Test that taxonomy has correct labels.
	 */
	public function test_taxonomy_labels() {
		$image_tag = new ImageTag();
		$image_tag->register();

		$taxonomy_object = get_taxonomy( ImageTag::TAXONOMY );

		$this->assertEquals( 'Image Tags', $taxonomy_object->labels->name );
		$this->assertEquals( 'Image Tag', $taxonomy_object->labels->singular_name );
	}

	/**
	 * Test attaching image tags to an attachment.
	 */
	public function test_assign_tags_to_attachment() {
		$image_tag = new ImageTag();
		$image_tag->register();

		// Create a dummy attachment.
		$attachment_id = $this->factory->attachment->create();

		// Create and assign terms.
		$term = wp_insert_term( 'gold', ImageTag::TAXONOMY );
		wp_set_object_terms( $attachment_id, $term['term_id'], ImageTag::TAXONOMY );

		$assigned_terms = wp_get_object_terms( $attachment_id, ImageTag::TAXONOMY );

		$this->assertCount( 1, $assigned_terms );
		$this->assertEquals( 'gold', $assigned_terms[0]->name );
	}
}
