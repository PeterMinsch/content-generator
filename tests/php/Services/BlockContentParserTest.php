<?php
/**
 * Tests for Block Content Parser
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Services;

use SEOGenerator\Services\BlockContentParser;
use WP_UnitTestCase;

/**
 * Block Content Parser test case.
 */
class BlockContentParserTest extends WP_UnitTestCase {
	/**
	 * Parser instance.
	 *
	 * @var BlockContentParser
	 */
	private $parser;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->parser = new BlockContentParser();
	}

	/**
	 * Test parsing hero block.
	 */
	public function test_parse_hero_block() {
		$json_content = wp_json_encode(
			array(
				'headline'    => 'Test Headline',
				'subheadline' => 'Test Subheadline',
			)
		);

		$result = $this->parser->parse( 'hero', $json_content );

		$this->assertArrayHasKey( 'hero_headline', $result );
		$this->assertArrayHasKey( 'hero_subheadline', $result );
		$this->assertEquals( 'Test Headline', $result['hero_headline'] );
		$this->assertEquals( 'Test Subheadline', $result['hero_subheadline'] );
	}

	/**
	 * Test parsing hero block from markdown code block.
	 */
	public function test_parse_json_from_markdown() {
		$markdown_content = "```json\n" . wp_json_encode(
			array(
				'headline'    => 'Test Headline',
				'subheadline' => 'Test Subheadline',
			)
		) . "\n```";

		$result = $this->parser->parse( 'hero', $markdown_content );

		$this->assertEquals( 'Test Headline', $result['hero_headline'] );
	}

	/**
	 * Test parsing SERP answer (plain text).
	 */
	public function test_parse_serp_answer() {
		$content = 'This is a plain text SERP answer.';

		$result = $this->parser->parse( 'serp_answer', $content );

		$this->assertArrayHasKey( 'serp_answer', $result );
		$this->assertEquals( 'This is a plain text SERP answer.', $result['serp_answer'] );
	}

	/**
	 * Test parsing product criteria with array.
	 */
	public function test_parse_product_criteria() {
		$json_content = wp_json_encode(
			array(
				'criteria' => array(
					array(
						'title'       => 'Criterion 1',
						'explanation' => 'Explanation 1',
					),
					array(
						'title'       => 'Criterion 2',
						'explanation' => 'Explanation 2',
					),
				),
			)
		);

		$result = $this->parser->parse( 'product_criteria', $json_content );

		$this->assertArrayHasKey( 'criteria_items', $result );
		$this->assertCount( 2, $result['criteria_items'] );
		$this->assertEquals( 'Criterion 1', $result['criteria_items'][0]['criterion_title'] );
	}

	/**
	 * Test parsing materials block.
	 */
	public function test_parse_materials() {
		$json_content = wp_json_encode(
			array(
				'introduction' => 'Materials introduction',
				'materials'    => array(
					array(
						'name'        => 'Platinum',
						'description' => 'Platinum description',
					),
				),
			)
		);

		$result = $this->parser->parse( 'materials', $json_content );

		$this->assertArrayHasKey( 'materials_introduction', $result );
		$this->assertArrayHasKey( 'materials_items', $result );
		$this->assertEquals( 'Materials introduction', $result['materials_introduction'] );
		$this->assertCount( 1, $result['materials_items'] );
	}

	/**
	 * Test parsing FAQs block.
	 */
	public function test_parse_faqs() {
		$json_content = wp_json_encode(
			array(
				'faqs' => array(
					array(
						'question' => 'Question 1?',
						'answer'   => 'Answer 1',
					),
					array(
						'question' => 'Question 2?',
						'answer'   => 'Answer 2',
					),
				),
			)
		);

		$result = $this->parser->parse( 'faqs', $json_content );

		$this->assertArrayHasKey( 'faqs_items', $result );
		$this->assertCount( 2, $result['faqs_items'] );
		$this->assertEquals( 'Question 1?', $result['faqs_items'][0]['faq_question'] );
		$this->assertEquals( 'Answer 1', $result['faqs_items'][0]['faq_answer'] );
	}

	/**
	 * Test parsing CTA block.
	 */
	public function test_parse_cta() {
		$json_content = wp_json_encode(
			array(
				'heading' => 'CTA Heading',
				'body'    => 'CTA body text',
			)
		);

		$result = $this->parser->parse( 'cta', $json_content );

		$this->assertArrayHasKey( 'cta_heading', $result );
		$this->assertArrayHasKey( 'cta_body', $result );
		$this->assertEquals( 'CTA Heading', $result['cta_heading'] );
	}

	/**
	 * Test parsing comparison block.
	 */
	public function test_parse_comparison() {
		$json_content = wp_json_encode(
			array(
				'introduction' => 'Comparison intro',
				'factors'      => array( 'Price', 'Durability' ),
				'options'      => array(
					array(
						'name'     => 'Option 1',
						'values'   => array( '$100', 'High' ),
						'best_for' => 'Budget buyers',
					),
				),
			)
		);

		$result = $this->parser->parse( 'comparison', $json_content );

		$this->assertArrayHasKey( 'comparison_introduction', $result );
		$this->assertArrayHasKey( 'comparison_factors', $result );
		$this->assertArrayHasKey( 'comparison_options', $result );
		$this->assertCount( 2, $result['comparison_factors'] );
	}

	/**
	 * Test invalid block type throws exception.
	 */
	public function test_invalid_block_type_throws_exception() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Unknown block type' );

		$this->parser->parse( 'invalid_block', 'content' );
	}

	/**
	 * Test invalid hero format throws exception.
	 */
	public function test_invalid_hero_format_throws_exception() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid hero content format' );

		$json_content = wp_json_encode(
			array(
				'headline' => 'Only headline',
			)
		);

		$this->parser->parse( 'hero', $json_content );
	}

	/**
	 * Test sanitization of parsed content.
	 */
	public function test_content_is_sanitized() {
		$json_content = wp_json_encode(
			array(
				'headline'    => '<script>alert("xss")</script>Test',
				'subheadline' => '<b>Bold text</b>',
			)
		);

		$result = $this->parser->parse( 'hero', $json_content );

		$this->assertStringNotContainsString( '<script>', $result['hero_headline'] );
		$this->assertStringNotContainsString( '<b>', $result['hero_subheadline'] );
	}
}
