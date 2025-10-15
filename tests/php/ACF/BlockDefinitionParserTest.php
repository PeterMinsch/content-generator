<?php
/**
 * Tests for BlockDefinitionParser
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\ACF;

use SEOGenerator\ACF\BlockDefinitionParser;
use WP_UnitTestCase;

/**
 * BlockDefinitionParser test case.
 */
class BlockDefinitionParserTest extends WP_UnitTestCase {
	/**
	 * Parser instance.
	 *
	 * @var BlockDefinitionParser
	 */
	private $parser;

	/**
	 * Set up before tests.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->parser = new BlockDefinitionParser();
	}

	/**
	 * Test that parser loads block definitions from config.
	 */
	public function test_loads_block_definitions() {
		$blocks = $this->parser->getEnabledBlocks();

		$this->assertIsArray( $blocks );
		$this->assertNotEmpty( $blocks, 'Should load at least one block from config' );
	}

	/**
	 * Test that parser returns enabled blocks only.
	 */
	public function test_returns_enabled_blocks_only() {
		$blocks = $this->parser->getEnabledBlocks();

		foreach ( $blocks as $block_id => $block ) {
			$enabled = $block['enabled'] ?? true;
			$this->assertTrue( $enabled, "Block {$block_id} should be enabled" );
		}
	}

	/**
	 * Test that blocks are sorted by order.
	 */
	public function test_sorts_blocks_by_order() {
		$blocks = $this->parser->getSortedBlocks();

		$previous_order = -1;
		foreach ( $blocks as $block_id => $block ) {
			$current_order = $block['order'] ?? 999;
			$this->assertGreaterThanOrEqual(
				$previous_order,
				$current_order,
				"Blocks should be sorted by order (found {$block_id} out of order)"
			);
			$previous_order = $current_order;
		}
	}

	/**
	 * Test converting all blocks to ACF fields.
	 */
	public function test_converts_all_blocks_to_acf_fields() {
		$acf_fields = $this->parser->convertAllBlocksToACFFields();

		$this->assertIsArray( $acf_fields );
		$this->assertNotEmpty( $acf_fields, 'Should generate ACF fields from config' );

		// Each field should have required ACF properties.
		foreach ( $acf_fields as $field ) {
			$this->assertArrayHasKey( 'key', $field, 'ACF field must have key' );
			$this->assertArrayHasKey( 'label', $field, 'ACF field must have label' );
			$this->assertArrayHasKey( 'name', $field, 'ACF field must have name' );
			$this->assertArrayHasKey( 'type', $field, 'ACF field must have type' );
		}
	}

	/**
	 * Test converting a single block to ACF fields.
	 */
	public function test_converts_single_block_to_acf_fields() {
		$block_config = array(
			'label'             => 'Test Block',
			'enabled'           => true,
			'order'             => 1,
			'acf_wrapper_class' => 'acf-block-test',
			'fields'            => array(
				'test_field' => array(
					'label'    => 'Test Field',
					'type'     => 'text',
					'required' => true,
				),
			),
		);

		$acf_fields = $this->parser->convertBlockToACFFields( 'test', $block_config );

		$this->assertIsArray( $acf_fields );
		$this->assertCount( 1, $acf_fields );

		$field = $acf_fields[0];
		$this->assertEquals( 'field_test_field', $field['key'] );
		$this->assertEquals( 'Test Field', $field['label'] );
		$this->assertEquals( 'test_field', $field['name'] );
		$this->assertEquals( 'text', $field['type'] );
		$this->assertTrue( $field['required'] );

		// First field should have wrapper class from block config.
		$this->assertArrayHasKey( 'wrapper', $field );
		$this->assertEquals( 'acf-block-test', $field['wrapper']['class'] );
	}

	/**
	 * Test that repeater fields are converted with sub_fields.
	 */
	public function test_converts_repeater_fields() {
		$block_config = array(
			'label'  => 'Test Block',
			'fields' => array(
				'test_repeater' => array(
					'label'      => 'Test Repeater',
					'type'       => 'repeater',
					'layout'     => 'table',
					'sub_fields' => array(
						'sub_field_1' => array(
							'label' => 'Sub Field 1',
							'type'  => 'text',
						),
						'sub_field_2' => array(
							'label' => 'Sub Field 2',
							'type'  => 'textarea',
							'rows'  => 3,
						),
					),
				),
			),
		);

		$acf_fields = $this->parser->convertBlockToACFFields( 'test', $block_config );

		$this->assertCount( 1, $acf_fields );

		$repeater = $acf_fields[0];
		$this->assertEquals( 'repeater', $repeater['type'] );
		$this->assertEquals( 'table', $repeater['layout'] );
		$this->assertArrayHasKey( 'sub_fields', $repeater );
		$this->assertCount( 2, $repeater['sub_fields'] );

		// Verify sub-fields.
		$this->assertEquals( 'field_sub_field_1', $repeater['sub_fields'][0]['key'] );
		$this->assertEquals( 'Sub Field 1', $repeater['sub_fields'][0]['label'] );
		$this->assertEquals( 'text', $repeater['sub_fields'][0]['type'] );

		$this->assertEquals( 'field_sub_field_2', $repeater['sub_fields'][1]['key'] );
		$this->assertEquals( 'textarea', $repeater['sub_fields'][1]['type'] );
		$this->assertEquals( 3, $repeater['sub_fields'][1]['rows'] );
	}

	/**
	 * Test getting block by ID.
	 */
	public function test_gets_block_by_id() {
		$block_ids = $this->parser->getBlockIds();

		if ( ! empty( $block_ids ) ) {
			$first_block_id = $block_ids[0];
			$block          = $this->parser->getBlock( $first_block_id );

			$this->assertIsArray( $block );
			$this->assertArrayHasKey( 'label', $block );
			$this->assertArrayHasKey( 'fields', $block );
		}

		// Test non-existent block.
		$null_block = $this->parser->getBlock( 'non_existent_block' );
		$this->assertNull( $null_block );
	}

	/**
	 * Test getting AI prompt for block.
	 */
	public function test_gets_ai_prompt() {
		$block_ids = $this->parser->getBlockIds();

		if ( ! empty( $block_ids ) ) {
			$first_block_id = $block_ids[0];
			$ai_prompt      = $this->parser->getAIPrompt( $first_block_id );

			// Some blocks may have prompts, some may not.
			if ( null !== $ai_prompt ) {
				$this->assertIsString( $ai_prompt );
			}
		}

		// Test non-existent block.
		$null_prompt = $this->parser->getAIPrompt( 'non_existent_block' );
		$this->assertNull( $null_prompt );
	}

	/**
	 * Test getting frontend template for block.
	 */
	public function test_gets_frontend_template() {
		$block_ids = $this->parser->getBlockIds();

		if ( ! empty( $block_ids ) ) {
			$first_block_id = $block_ids[0];
			$template       = $this->parser->getFrontendTemplate( $first_block_id );

			// Some blocks may have templates, some may not.
			if ( null !== $template ) {
				$this->assertIsString( $template );
			}
		}

		// Test non-existent block.
		$null_template = $this->parser->getFrontendTemplate( 'non_existent_block' );
		$this->assertNull( $null_template );
	}

	/**
	 * Test getting block count.
	 */
	public function test_gets_block_count() {
		$count = $this->parser->getBlockCount();

		$this->assertIsInt( $count );
		$this->assertGreaterThan( 0, $count, 'Should have at least one enabled block' );
	}

	/**
	 * Test allow custom blocks setting.
	 */
	public function test_allow_custom_blocks_setting() {
		$allow_custom = $this->parser->allowCustomBlocks();

		$this->assertIsBool( $allow_custom );
	}

	/**
	 * Test that required blocks from original implementation exist.
	 */
	public function test_has_required_blocks() {
		$required_blocks = array(
			'hero',
			'serp_answer',
			'product_criteria',
			'materials',
			'process',
			'comparison',
			'product_showcase',
			'size_fit',
			'care_warranty',
			'ethics',
			'faqs',
			'cta',
		);

		$enabled_block_ids = $this->parser->getBlockIds();

		foreach ( $required_blocks as $required_block_id ) {
			$this->assertContains(
				$required_block_id,
				$enabled_block_ids,
				"Required block '{$required_block_id}' should be present in config"
			);
		}
	}

	/**
	 * Test that wrapper class is only added to first field.
	 */
	public function test_wrapper_class_only_on_first_field() {
		$block_config = array(
			'label'             => 'Test Block',
			'acf_wrapper_class' => 'acf-block-test',
			'fields'            => array(
				'field_1' => array(
					'label' => 'Field 1',
					'type'  => 'text',
				),
				'field_2' => array(
					'label' => 'Field 2',
					'type'  => 'text',
				),
				'field_3' => array(
					'label' => 'Field 3',
					'type'  => 'text',
				),
			),
		);

		$acf_fields = $this->parser->convertBlockToACFFields( 'test', $block_config );

		$this->assertCount( 3, $acf_fields );

		// First field should have wrapper class.
		$this->assertArrayHasKey( 'wrapper', $acf_fields[0] );
		$this->assertEquals( 'acf-block-test', $acf_fields[0]['wrapper']['class'] );

		// Second and third fields should not have wrapper class from block.
		$this->assertArrayNotHasKey( 'wrapper', $acf_fields[1] );
		$this->assertArrayNotHasKey( 'wrapper', $acf_fields[2] );
	}
}
