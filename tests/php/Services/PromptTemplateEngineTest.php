<?php
/**
 * Tests for Prompt Template Engine
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Services;

use SEOGenerator\Services\PromptTemplateEngine;
use SEOGenerator\Data\DefaultPrompts;
use SEOGenerator\Taxonomies\SEOTopic;
use WP_UnitTestCase;

/**
 * Prompt Template Engine test case.
 */
class PromptTemplateEngineTest extends WP_UnitTestCase {
	/**
	 * Template engine instance.
	 *
	 * @var PromptTemplateEngine
	 */
	private $engine;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->engine = new PromptTemplateEngine();

		// Register SEO Topic taxonomy for buildContext tests.
		$seo_topic = new SEOTopic();
		$seo_topic->register();

		// Clear any existing custom templates.
		delete_option( 'seo_generator_prompt_templates' );

		// Clear cache.
		wp_cache_flush();
	}

	/**
	 * Test renderPrompt replaces all variables correctly.
	 */
	public function test_render_prompt_replaces_variables() {
		$context = array(
			'page_title'    => 'Platinum Wedding Bands',
			'page_topic'    => 'Wedding Bands',
			'focus_keyword' => 'platinum wedding rings',
			'page_type'     => 'collection',
		);

		$result = $this->engine->renderPrompt( 'hero', $context );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'system', $result );
		$this->assertArrayHasKey( 'user', $result );
		$this->assertStringContainsString( 'Platinum Wedding Bands', $result['user'] );
		$this->assertStringContainsString( 'Wedding Bands', $result['user'] );
		$this->assertStringContainsString( 'platinum wedding rings', $result['user'] );
		$this->assertStringContainsString( 'collection', $result['user'] );
	}

	/**
	 * Test renderPrompt handles missing context variables.
	 */
	public function test_render_prompt_handles_missing_variables() {
		$context = array(
			'page_title' => 'Test Page',
		);

		$result = $this->engine->renderPrompt( 'hero', $context );

		// Should not throw error, just replace with empty strings.
		$this->assertIsArray( $result );
		$this->assertStringContainsString( 'Test Page', $result['user'] );
	}

	/**
	 * Test renderPrompt throws exception for invalid block type.
	 */
	public function test_render_prompt_throws_exception_for_invalid_block() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Template not found' );

		$this->engine->renderPrompt( 'invalid_block', array() );
	}

	/**
	 * Test getTemplate retrieves default template.
	 */
	public function test_get_template_retrieves_default() {
		$template = $this->engine->getTemplate( 'hero' );

		$this->assertIsArray( $template );
		$this->assertArrayHasKey( 'system', $template );
		$this->assertArrayHasKey( 'user', $template );
		$this->assertNotEmpty( $template['system'] );
		$this->assertNotEmpty( $template['user'] );
	}

	/**
	 * Test getTemplate retrieves custom template from database.
	 */
	public function test_get_template_retrieves_custom() {
		// Save custom template.
		$custom_template = array(
			'system' => 'Custom system message',
			'user'   => 'Custom user message',
		);

		update_option(
			'seo_generator_prompt_templates',
			array(
				'hero' => $custom_template,
			)
		);

		$template = $this->engine->getTemplate( 'hero' );

		$this->assertEquals( 'Custom system message', $template['system'] );
		$this->assertEquals( 'Custom user message', $template['user'] );
	}

	/**
	 * Test getTemplate returns null for invalid block type.
	 */
	public function test_get_template_returns_null_for_invalid_block() {
		$template = $this->engine->getTemplate( 'invalid_block' );

		$this->assertNull( $template );
	}

	/**
	 * Test updateTemplate saves custom template.
	 */
	public function test_update_template_saves_custom() {
		$custom_template = array(
			'system' => 'Updated system message',
			'user'   => 'Updated user message with {page_title}',
		);

		$result = $this->engine->updateTemplate( 'hero', $custom_template );

		$this->assertTrue( $result );

		// Verify saved to database.
		$saved_templates = get_option( 'seo_generator_prompt_templates' );
		$this->assertArrayHasKey( 'hero', $saved_templates );
		$this->assertEquals( 'Updated system message', $saved_templates['hero']['system'] );
	}

	/**
	 * Test updateTemplate validates template.
	 */
	public function test_update_template_validates() {
		$invalid_template = array(
			'system' => 'Only system message',
		);

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Template validation failed' );

		$this->engine->updateTemplate( 'hero', $invalid_template );
	}

	/**
	 * Test updateTemplate clears cache.
	 */
	public function test_update_template_clears_cache() {
		// Get template to populate cache.
		$this->engine->getTemplate( 'hero' );

		// Update template.
		$custom_template = array(
			'system' => 'New system message',
			'user'   => 'New user message',
		);

		$this->engine->updateTemplate( 'hero', $custom_template );

		// Get template again - should retrieve new version, not cached.
		$template = $this->engine->getTemplate( 'hero' );

		$this->assertEquals( 'New system message', $template['system'] );
	}

	/**
	 * Test resetTemplate restores default.
	 */
	public function test_reset_template_restores_default() {
		// Save custom template.
		$custom_template = array(
			'system' => 'Custom system',
			'user'   => 'Custom user',
		);

		$this->engine->updateTemplate( 'hero', $custom_template );

		// Reset to default.
		$result = $this->engine->resetTemplate( 'hero' );

		$this->assertTrue( $result );

		// Verify custom template removed.
		$saved_templates = get_option( 'seo_generator_prompt_templates' );
		$this->assertArrayNotHasKey( 'hero', $saved_templates );

		// Verify default template is now returned.
		$template       = $this->engine->getTemplate( 'hero' );
		$default_template = DefaultPrompts::get( 'hero' );

		$this->assertEquals( $default_template, $template );
	}

	/**
	 * Test resetTemplate returns false if no custom template exists.
	 */
	public function test_reset_template_returns_false_if_no_custom() {
		$result = $this->engine->resetTemplate( 'hero' );

		$this->assertFalse( $result );
	}

	/**
	 * Test buildContext retrieves all data from post.
	 */
	public function test_build_context_retrieves_post_data() {
		// Create a post with topic.
		$post_id = $this->factory->post->create(
			array(
				'post_type'  => 'seo-page',
				'post_title' => 'Test Wedding Bands',
			)
		);

		// Assign topic term.
		$term = wp_insert_term( 'Wedding Bands', 'seo-topic' );
		wp_set_object_terms( $post_id, $term['term_id'], 'seo-topic' );

		// Mock ACF field (since ACF not available in tests).
		add_filter(
			'acf/load_value/name=seo_focus_keyword',
			function ( $value, $post_id, $field ) {
				return 'platinum bands';
			},
			10,
			3
		);

		$context = $this->engine->buildContext( $post_id );

		$this->assertEquals( 'Test Wedding Bands', $context['page_title'] );
		$this->assertEquals( 'Wedding Bands', $context['page_topic'] );
		$this->assertEquals( 'collection', $context['page_type'] );
	}

	/**
	 * Test buildContext merges additional context.
	 */
	public function test_build_context_merges_additional() {
		$post_id = $this->factory->post->create(
			array(
				'post_type'  => 'seo-page',
				'post_title' => 'Test Page',
			)
		);

		$additional = array(
			'custom_field' => 'custom_value',
		);

		$context = $this->engine->buildContext( $post_id, $additional );

		$this->assertEquals( 'Test Page', $context['page_title'] );
		$this->assertEquals( 'custom_value', $context['custom_field'] );
	}

	/**
	 * Test page type inference for comparison.
	 */
	public function test_page_type_inference_comparison() {
		$post_id = $this->factory->post->create(
			array(
				'post_type' => 'seo-page',
			)
		);

		$term = wp_insert_term( 'Comparisons', 'seo-topic' );
		wp_set_object_terms( $post_id, $term['term_id'], 'seo-topic' );

		$context = $this->engine->buildContext( $post_id );

		$this->assertEquals( 'comparison', $context['page_type'] );
	}

	/**
	 * Test page type inference for education.
	 */
	public function test_page_type_inference_education() {
		$post_id = $this->factory->post->create(
			array(
				'post_type' => 'seo-page',
			)
		);

		$term = wp_insert_term( 'Education', 'seo-topic' );
		wp_set_object_terms( $post_id, $term['term_id'], 'seo-topic' );

		$context = $this->engine->buildContext( $post_id );

		$this->assertEquals( 'education', $context['page_type'] );
	}

	/**
	 * Test validateTemplate accepts valid template.
	 */
	public function test_validate_template_accepts_valid() {
		$template = array(
			'system' => 'System message',
			'user'   => 'User message',
		);

		$errors = $this->engine->validateTemplate( $template, 'hero' );

		$this->assertEmpty( $errors );
	}

	/**
	 * Test validateTemplate rejects missing system message.
	 */
	public function test_validate_template_rejects_missing_system() {
		$template = array(
			'user' => 'User message',
		);

		$errors = $this->engine->validateTemplate( $template, 'hero' );

		$this->assertNotEmpty( $errors );
		$this->assertContains( 'Template missing "system" message', $errors );
	}

	/**
	 * Test validateTemplate rejects missing user message.
	 */
	public function test_validate_template_rejects_missing_user() {
		$template = array(
			'system' => 'System message',
		);

		$errors = $this->engine->validateTemplate( $template, 'hero' );

		$this->assertNotEmpty( $errors );
		$this->assertContains( 'Template missing "user" message', $errors );
	}

	/**
	 * Test validateTemplate rejects empty messages.
	 */
	public function test_validate_template_rejects_empty_messages() {
		$template = array(
			'system' => '   ',
			'user'   => '',
		);

		$errors = $this->engine->validateTemplate( $template, 'hero' );

		$this->assertNotEmpty( $errors );
		$this->assertCount( 2, $errors );
	}

	/**
	 * Test validateTemplate rejects non-string messages.
	 */
	public function test_validate_template_rejects_non_string() {
		$template = array(
			'system' => array( 'not', 'a', 'string' ),
			'user'   => 123,
		);

		$errors = $this->engine->validateTemplate( $template, 'hero' );

		$this->assertNotEmpty( $errors );
		$this->assertGreaterThanOrEqual( 2, count( $errors ) );
	}

	/**
	 * Test template caching reduces database queries.
	 */
	public function test_template_caching() {
		// First call - should hit database.
		$template1 = $this->engine->getTemplate( 'hero' );

		// Second call - should use cache.
		$template2 = $this->engine->getTemplate( 'hero' );

		$this->assertEquals( $template1, $template2 );
	}

	/**
	 * Test all 12 default templates exist.
	 */
	public function test_all_default_templates_exist() {
		$block_types = array(
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

		foreach ( $block_types as $block_type ) {
			$template = $this->engine->getTemplate( $block_type );

			$this->assertNotNull( $template, "Template for {$block_type} should exist" );
			$this->assertArrayHasKey( 'system', $template, "{$block_type} template missing system message" );
			$this->assertArrayHasKey( 'user', $template, "{$block_type} template missing user message" );
			$this->assertNotEmpty( $template['system'], "{$block_type} template has empty system message" );
			$this->assertNotEmpty( $template['user'], "{$block_type} template has empty user message" );
		}
	}

	/**
	 * Test all default templates are valid.
	 */
	public function test_all_default_templates_are_valid() {
		$block_types = $this->engine->getAvailableBlockTypes();

		foreach ( $block_types as $block_type ) {
			$template = $this->engine->getTemplate( $block_type );
			$errors   = $this->engine->validateTemplate( $template, $block_type );

			$this->assertEmpty( $errors, "Template for {$block_type} should be valid" );
		}
	}

	/**
	 * Test getAvailableBlockTypes returns all block types.
	 */
	public function test_get_available_block_types() {
		$block_types = $this->engine->getAvailableBlockTypes();

		$this->assertIsArray( $block_types );
		$this->assertCount( 12, $block_types );
		$this->assertContains( 'hero', $block_types );
		$this->assertContains( 'cta', $block_types );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		delete_option( 'seo_generator_prompt_templates' );
		wp_cache_flush();
		parent::tearDown();
	}
}
