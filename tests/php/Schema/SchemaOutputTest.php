<?php
/**
 * Schema Output Tests
 *
 * Tests for schema.org JSON-LD structured data output.
 *
 * @package SEOGenerator
 * @subpackage Tests
 */

namespace SEOGenerator\Tests\Schema;

use WP_UnitTestCase;
use WP_Term;

/**
 * Test schema output functionality.
 */
class SchemaOutputTest extends WP_UnitTestCase {

	/**
	 * Test post ID.
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Test topic term.
	 *
	 * @var WP_Term
	 */
	private $topic_term;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Create test SEO page.
		$this->post_id = $this->factory->post->create(
			array(
				'post_type'   => 'seo-page',
				'post_title'  => 'Test SEO Page',
				'post_status' => 'publish',
			)
		);

		// Create topic term.
		$term             = $this->factory->term->create(
			array(
				'taxonomy' => 'seo-topic',
				'name'     => 'Wedding Bands',
			)
		);
		$this->topic_term = get_term( $term, 'seo-topic' );

		// Set ACF fields (mocked).
		update_field( 'hero_title', 'Platinum Wedding Bands', $this->post_id );
		update_field( 'seo_meta_description', 'Complete guide to platinum wedding bands', $this->post_id );
	}

	/**
	 * Test Article schema builder.
	 *
	 * @return void
	 */
	public function test_article_schema_is_generated() {
		$schema = seo_generator_build_article_schema( $this->post_id );

		$this->assertIsArray( $schema );
		$this->assertEquals( 'Article', $schema['@type'] );
		$this->assertEquals( 'Platinum Wedding Bands', $schema['headline'] );
		$this->assertEquals( 'Complete guide to platinum wedding bands', $schema['description'] );
		$this->assertArrayHasKey( 'author', $schema );
		$this->assertEquals( 'Organization', $schema['author']['@type'] );
		$this->assertArrayHasKey( 'datePublished', $schema );
		$this->assertArrayHasKey( 'dateModified', $schema );
	}

	/**
	 * Test Article schema with image.
	 *
	 * @return void
	 */
	public function test_article_schema_includes_image() {
		// Create test attachment.
		$attachment_id = $this->factory->attachment->create_upload_object( __DIR__ . '/../../fixtures/test-image.jpg' );
		update_field( 'hero_image', $attachment_id, $this->post_id );

		$schema = seo_generator_build_article_schema( $this->post_id );

		$this->assertArrayHasKey( 'image', $schema );
		$this->assertStringContainsString( 'http', $schema['image'] );
	}

	/**
	 * Test Article schema fallback to post title.
	 *
	 * @return void
	 */
	public function test_article_schema_fallback_to_post_title() {
		delete_field( 'hero_title', $this->post_id );

		$schema = seo_generator_build_article_schema( $this->post_id );

		$this->assertEquals( 'Test SEO Page', $schema['headline'] );
	}

	/**
	 * Test FAQPage schema with FAQ content.
	 *
	 * @return void
	 */
	public function test_faq_schema_is_generated_with_content() {
		$faq_items = array(
			array(
				'question' => 'What is platinum?',
				'answer'   => 'Platinum is a precious metal.',
			),
			array(
				'question' => 'How durable is platinum?',
				'answer'   => 'Platinum is very durable.',
			),
		);

		update_field( 'faq_items', $faq_items, $this->post_id );

		$schema = seo_generator_build_faq_schema( $this->post_id );

		$this->assertIsArray( $schema );
		$this->assertEquals( 'FAQPage', $schema['@type'] );
		$this->assertArrayHasKey( 'mainEntity', $schema );
		$this->assertCount( 2, $schema['mainEntity'] );

		// Check first question.
		$this->assertEquals( 'Question', $schema['mainEntity'][0]['@type'] );
		$this->assertEquals( 'What is platinum?', $schema['mainEntity'][0]['name'] );
		$this->assertEquals( 'Answer', $schema['mainEntity'][0]['acceptedAnswer']['@type'] );
		$this->assertEquals( 'Platinum is a precious metal.', $schema['mainEntity'][0]['acceptedAnswer']['text'] );
	}

	/**
	 * Test FAQPage schema returns null when no FAQ content.
	 *
	 * @return void
	 */
	public function test_faq_schema_returns_null_when_no_content() {
		delete_field( 'faq_items', $this->post_id );

		$schema = seo_generator_build_faq_schema( $this->post_id );

		$this->assertNull( $schema );
	}

	/**
	 * Test FAQPage schema skips invalid questions.
	 *
	 * @return void
	 */
	public function test_faq_schema_skips_invalid_questions() {
		$faq_items = array(
			array(
				'question' => 'Valid question?',
				'answer'   => 'Valid answer',
			),
			array(
				'question' => 'Missing answer?',
				'answer'   => '',
			),
			array(
				'question' => '',
				'answer'   => 'Missing question',
			),
		);

		update_field( 'faq_items', $faq_items, $this->post_id );

		$schema = seo_generator_build_faq_schema( $this->post_id );

		$this->assertIsArray( $schema );
		$this->assertCount( 1, $schema['mainEntity'] ); // Only 1 valid question.
	}

	/**
	 * Test BreadcrumbList schema with topic.
	 *
	 * @return void
	 */
	public function test_breadcrumb_schema_with_topic() {
		wp_set_object_terms( $this->post_id, array( $this->topic_term->term_id ), 'seo-topic' );

		$schema = seo_generator_build_breadcrumb_schema( $this->post_id );

		$this->assertIsArray( $schema );
		$this->assertEquals( 'BreadcrumbList', $schema['@type'] );
		$this->assertArrayHasKey( 'itemListElement', $schema );
		$this->assertCount( 3, $schema['itemListElement'] );

		// Check positions.
		$this->assertEquals( 1, $schema['itemListElement'][0]['position'] );
		$this->assertEquals( 'Home', $schema['itemListElement'][0]['name'] );

		$this->assertEquals( 2, $schema['itemListElement'][1]['position'] );
		$this->assertEquals( 'Wedding Bands', $schema['itemListElement'][1]['name'] );

		$this->assertEquals( 3, $schema['itemListElement'][2]['position'] );
		$this->assertEquals( 'Test SEO Page', $schema['itemListElement'][2]['name'] );
	}

	/**
	 * Test BreadcrumbList schema without topic.
	 *
	 * @return void
	 */
	public function test_breadcrumb_schema_without_topic() {
		// Don't assign topic.
		$schema = seo_generator_build_breadcrumb_schema( $this->post_id );

		$this->assertIsArray( $schema );
		$this->assertCount( 2, $schema['itemListElement'] ); // Home + Page only.

		// Check positions.
		$this->assertEquals( 1, $schema['itemListElement'][0]['position'] );
		$this->assertEquals( 'Home', $schema['itemListElement'][0]['name'] );

		$this->assertEquals( 2, $schema['itemListElement'][1]['position'] );
		$this->assertEquals( 'Test SEO Page', $schema['itemListElement'][1]['name'] );
	}

	/**
	 * Test complete schema output includes @graph.
	 *
	 * @return void
	 */
	public function test_complete_schema_output_includes_graph() {
		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_output_schema();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<script type="application/ld+json">', $output );
		$this->assertStringContainsString( '@context', $output );
		$this->assertStringContainsString( '@graph', $output );
		$this->assertStringContainsString( 'Article', $output );
		$this->assertStringContainsString( 'BreadcrumbList', $output );
	}

	/**
	 * Test complete schema includes FAQPage when FAQ content exists.
	 *
	 * @return void
	 */
	public function test_complete_schema_includes_faqpage_when_content_exists() {
		$faq_items = array(
			array(
				'question' => 'Test question?',
				'answer'   => 'Test answer',
			),
		);

		update_field( 'faq_items', $faq_items, $this->post_id );

		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_output_schema();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'FAQPage', $output );
	}

	/**
	 * Test complete schema excludes FAQPage when no FAQ content.
	 *
	 * @return void
	 */
	public function test_complete_schema_excludes_faqpage_when_no_content() {
		delete_field( 'faq_items', $this->post_id );

		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_output_schema();
		$output = ob_get_clean();

		$this->assertStringNotContainsString( 'FAQPage', $output );
	}

	/**
	 * Test schema output only on seo-page post type.
	 *
	 * @return void
	 */
	public function test_schema_output_only_on_seo_page_post_type() {
		// Create regular post.
		$regular_post_id = $this->factory->post->create(
			array(
				'post_type' => 'post',
			)
		);

		$this->go_to( get_permalink( $regular_post_id ) );

		ob_start();
		seo_generator_output_schema();
		$output = ob_get_clean();

		$this->assertEmpty( $output ); // No schema for regular posts.
	}

	/**
	 * Test Article schema filter hook.
	 *
	 * @return void
	 */
	public function test_article_schema_filter_hook() {
		add_filter(
			'seo_generator_article_schema',
			function ( $schema, $post_id ) {
				$schema['custom_field'] = 'custom_value';
				return $schema;
			},
			10,
			2
		);

		$schema = seo_generator_build_article_schema( $this->post_id );

		$this->assertEquals( 'custom_value', $schema['custom_field'] );
	}

	/**
	 * Test FAQPage schema filter hook.
	 *
	 * @return void
	 */
	public function test_faq_schema_filter_hook() {
		$faq_items = array(
			array(
				'question' => 'Test?',
				'answer'   => 'Answer',
			),
		);

		update_field( 'faq_items', $faq_items, $this->post_id );

		add_filter(
			'seo_generator_faq_schema',
			function ( $schema, $post_id ) {
				$schema['custom_field'] = 'custom_value';
				return $schema;
			},
			10,
			2
		);

		$schema = seo_generator_build_faq_schema( $this->post_id );

		$this->assertEquals( 'custom_value', $schema['custom_field'] );
	}

	/**
	 * Test BreadcrumbList schema filter hook.
	 *
	 * @return void
	 */
	public function test_breadcrumb_schema_filter_hook() {
		add_filter(
			'seo_generator_breadcrumb_schema',
			function ( $schema, $post_id ) {
				$schema['custom_field'] = 'custom_value';
				return $schema;
			},
			10,
			2
		);

		$schema = seo_generator_build_breadcrumb_schema( $this->post_id );

		$this->assertEquals( 'custom_value', $schema['custom_field'] );
	}

	/**
	 * Test complete schema filter hook.
	 *
	 * @return void
	 */
	public function test_complete_schema_filter_hook() {
		add_filter(
			'seo_generator_complete_schema',
			function ( $schema, $post_id ) {
				$schema['custom_field'] = 'custom_value';
				return $schema;
			},
			10,
			2
		);

		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_output_schema();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'custom_field', $output );
		$this->assertStringContainsString( 'custom_value', $output );
	}

	/**
	 * Test schema handles special characters.
	 *
	 * @return void
	 */
	public function test_schema_handles_special_characters() {
		update_field( 'hero_title', 'Test "Quoted" & Ampersand\'s Title', $this->post_id );

		$schema = seo_generator_build_article_schema( $this->post_id );

		// esc_html will encode special characters.
		$this->assertStringContainsString( 'Quoted', $schema['headline'] );
	}

	/**
	 * Test schema JSON encoding is valid.
	 *
	 * @return void
	 */
	public function test_schema_json_encoding_is_valid() {
		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_output_schema();
		$output = ob_get_clean();

		// Extract JSON from script tag.
		preg_match( '/<script type="application\/ld\+json">(.*?)<\/script>/s', $output, $matches );

		$this->assertNotEmpty( $matches[1] );

		$json = json_decode( $matches[1], true );

		$this->assertNotNull( $json ); // Valid JSON.
		$this->assertEquals( 'https://schema.org', $json['@context'] );
		$this->assertArrayHasKey( '@graph', $json );
	}
}
