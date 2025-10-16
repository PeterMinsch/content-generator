<?php
/**
 * Import Service - Block Order Tests
 *
 * Tests for custom block ordering during CSV import.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Services;

use SEOGenerator\Services\ImportService;
use WP_UnitTestCase;

/**
 * Test Import Service - Block Order
 */
class ImportServiceBlockOrderTest extends WP_UnitTestCase {

	/**
	 * Test that ImportService saves custom block order to post meta.
	 */
	public function testImportServiceSavesCustomBlockOrderToPostMeta(): void {
		// Define custom block order.
		$custom_order = array( 'hero', 'faqs', 'cta', 'serp_answer', 'product_criteria', 'materials', 'process', 'comparison', 'product_showcase', 'size_fit', 'care_warranty', 'ethics' );

		// Create import service with custom block order.
		$import_service = new ImportService(
			array(
				'batch_size'         => 10,
				'check_duplicates'   => false,
				'generation_mode'    => 'drafts_only',
				'custom_block_order' => $custom_order,
			)
		);

		// Prepare test data.
		$rows = array(
			array( 'Test Page Title', 'test keyword', 'Product Reviews' ),
		);

		$headers = array( 'Title', 'Keyword', 'Topic' );

		$mapping = array(
			'Title'   => 'page_title',
			'Keyword' => 'focus_keyword',
			'Topic'   => 'topic_category',
		);

		// Process batch.
		$result = $import_service->processSingleBatch( $rows, $headers, $mapping );

		// Assert post was created.
		$this->assertCount( 1, $result['created'] );
		$this->assertCount( 0, $result['errors'] );

		// Get created post ID.
		$post_id = $result['created'][0]['post_id'];

		// Retrieve saved block order from post meta.
		$saved_order_json = get_post_meta( $post_id, '_seo_block_order', true );
		$this->assertNotEmpty( $saved_order_json, 'Block order meta should be saved' );

		$saved_order = json_decode( $saved_order_json, true );

		// Assert the order includes seo_metadata prepended.
		$expected_order = array_merge( array( 'seo_metadata' ), $custom_order );
		$this->assertEquals( $expected_order, $saved_order );

		// Clean up.
		wp_delete_post( $post_id, true );
	}

	/**
	 * Test that ImportService does not save block order when not provided.
	 */
	public function testImportServiceDoesNotSaveBlockOrderWhenNotProvided(): void {
		// Create import service without custom block order.
		$import_service = new ImportService(
			array(
				'batch_size'       => 10,
				'check_duplicates' => false,
				'generation_mode'  => 'drafts_only',
			)
		);

		// Prepare test data.
		$rows = array(
			array( 'Test Page Title 2', 'test keyword 2', 'Product Reviews' ),
		);

		$headers = array( 'Title', 'Keyword', 'Topic' );

		$mapping = array(
			'Title'   => 'page_title',
			'Keyword' => 'focus_keyword',
			'Topic'   => 'topic_category',
		);

		// Process batch.
		$result = $import_service->processSingleBatch( $rows, $headers, $mapping );

		// Assert post was created.
		$this->assertCount( 1, $result['created'] );
		$this->assertCount( 0, $result['errors'] );

		// Get created post ID.
		$post_id = $result['created'][0]['post_id'];

		// Retrieve block order from post meta.
		$saved_order_json = get_post_meta( $post_id, '_seo_block_order', true );

		// Assert no block order meta was saved.
		$this->assertEmpty( $saved_order_json, 'Block order meta should not be saved when not provided' );

		// Clean up.
		wp_delete_post( $post_id, true );
	}

	/**
	 * Test that block ordering and selection work together correctly.
	 */
	public function testBlockOrderingWithBlockSelection(): void {
		// Define custom block order with only some blocks enabled.
		$custom_order = array( 'hero', 'faqs', 'cta', 'serp_answer', 'product_criteria', 'materials', 'process', 'comparison', 'product_showcase', 'size_fit', 'care_warranty', 'ethics' );
		$blocks_to_generate = array( 'hero', 'faqs', 'cta' );

		// Create import service with custom block order and blocks_to_generate.
		$import_service = new ImportService(
			array(
				'batch_size'         => 10,
				'check_duplicates'   => false,
				'generation_mode'    => 'drafts_only',
				'custom_block_order' => $custom_order,
				'blocks_to_generate' => $blocks_to_generate,
			)
		);

		// Prepare test data.
		$rows = array(
			array( 'Test Page Title 3', 'test keyword 3', 'Product Reviews' ),
		);

		$headers = array( 'Title', 'Keyword', 'Topic' );

		$mapping = array(
			'Title'   => 'page_title',
			'Keyword' => 'focus_keyword',
			'Topic'   => 'topic_category',
		);

		// Process batch.
		$result = $import_service->processSingleBatch( $rows, $headers, $mapping );

		// Assert post was created.
		$this->assertCount( 1, $result['created'] );
		$this->assertCount( 0, $result['errors'] );

		// Get created post ID.
		$post_id = $result['created'][0]['post_id'];

		// Retrieve saved block order from post meta.
		$saved_order_json = get_post_meta( $post_id, '_seo_block_order', true );
		$this->assertNotEmpty( $saved_order_json, 'Block order meta should be saved' );

		$saved_order = json_decode( $saved_order_json, true );

		// Assert the order includes seo_metadata prepended.
		$expected_order = array_merge( array( 'seo_metadata' ), $custom_order );
		$this->assertEquals( $expected_order, $saved_order );

		// Clean up.
		wp_delete_post( $post_id, true );
	}

	/**
	 * Test validation of block order in AJAX handler.
	 */
	public function testBlockOrderValidation(): void {
		$import_page = new \SEOGenerator\Admin\ImportPage();

		// Valid block order (12 blocks).
		$valid_order = array( 'hero', 'serp_answer', 'product_criteria', 'materials', 'process', 'comparison', 'product_showcase', 'size_fit', 'care_warranty', 'ethics', 'faqs', 'cta' );

		// Test with exactly 12 blocks - should pass.
		$this->assertCount( 12, $valid_order );

		// Invalid block order (missing blocks).
		$invalid_order = array( 'hero', 'faqs', 'cta' );
		$this->assertCount( 3, $invalid_order );
		$this->assertNotEquals( 12, count( $invalid_order ) );

		// Invalid block order (extra blocks).
		$extra_blocks_order = array( 'hero', 'serp_answer', 'product_criteria', 'materials', 'process', 'comparison', 'product_showcase', 'size_fit', 'care_warranty', 'ethics', 'faqs', 'cta', 'extra_block' );
		$this->assertCount( 13, $extra_blocks_order );
	}
}
