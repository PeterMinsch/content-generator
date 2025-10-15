<?php
/**
 * Tests for TemplateLoader
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Templates;

use SEOGenerator\Templates\TemplateLoader;
use WP_UnitTestCase;

/**
 * TemplateLoader test case.
 */
class TemplateLoaderTest extends WP_UnitTestCase {
	/**
	 * Template loader instance.
	 *
	 * @var TemplateLoader
	 */
	private $loader;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->loader = new TemplateLoader();
		$this->loader->register();
	}

	/**
	 * Test template loader registers filter.
	 */
	public function test_registers_single_template_filter() {
		$this->assertNotFalse( has_filter( 'single_template', array( $this->loader, 'loadSingleTemplate' ) ) );
	}

	/**
	 * Test template loads for seo-page post type.
	 */
	public function test_loads_template_for_seo_page() {
		// Create a seo-page post.
		$post_id = $this->factory->post->create(
			array(
				'post_type' => 'seo-page',
			)
		);

		// Set up query for single seo-page.
		global $wp_query;
		$wp_query->is_singular = true;
		$wp_query->is_single   = true;
		$wp_query->queried_object_id = $post_id;
		set_query_var( 'post_type', 'seo-page' );

		// Mock is_singular check.
		add_filter(
			'single_template',
			function ( $template ) {
				return $this->loader->loadSingleTemplate( $template );
			}
		);

		$default_template = '/path/to/default/single.php';
		$loaded_template  = apply_filters( 'single_template', $default_template );

		// Should return our custom template.
		$expected_template = SEO_GENERATOR_PLUGIN_DIR . 'templates/frontend/single-seo-page.php';
		$this->assertEquals( $expected_template, $loaded_template );

		// Clean up.
		wp_delete_post( $post_id, true );
	}

	/**
	 * Test template does not load for other post types.
	 */
	public function test_does_not_load_template_for_other_post_types() {
		// Create a regular post.
		$post_id = $this->factory->post->create(
			array(
				'post_type' => 'post',
			)
		);

		// Set up query for single post.
		global $wp_query;
		$wp_query->is_singular = true;
		$wp_query->is_single   = true;
		$wp_query->queried_object_id = $post_id;
		set_query_var( 'post_type', 'post' );

		$default_template = '/path/to/default/single.php';
		$loaded_template  = $this->loader->loadSingleTemplate( $default_template );

		// Should return default template.
		$this->assertEquals( $default_template, $loaded_template );

		// Clean up.
		wp_delete_post( $post_id, true );
	}

	/**
	 * Test template file exists.
	 */
	public function test_template_file_exists() {
		$exists = $this->loader->templateExists( 'single-seo-page.php' );

		$this->assertTrue( $exists );
	}

	/**
	 * Test template file path is correct.
	 */
	public function test_template_file_path() {
		$expected_path = SEO_GENERATOR_PLUGIN_DIR . 'templates/frontend/single-seo-page.php';

		$this->assertFileExists( $expected_path );
	}

	/**
	 * Test template contains required WordPress functions.
	 */
	public function test_template_contains_required_functions() {
		$template_path = SEO_GENERATOR_PLUGIN_DIR . 'templates/frontend/single-seo-page.php';
		$template_content = file_get_contents( $template_path );

		// Check for get_header().
		$this->assertStringContainsString( 'get_header()', $template_content );

		// Check for get_footer().
		$this->assertStringContainsString( 'get_footer()', $template_content );

		// Check for the_post().
		$this->assertStringContainsString( 'the_post()', $template_content );

		// Check for the_ID().
		$this->assertStringContainsString( 'the_ID()', $template_content );
	}

	/**
	 * Test template contains all 12 block calls.
	 */
	public function test_template_contains_all_blocks() {
		$template_path = SEO_GENERATOR_PLUGIN_DIR . 'templates/frontend/single-seo-page.php';
		$template_content = file_get_contents( $template_path );

		$blocks = array(
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

		foreach ( $blocks as $block ) {
			$this->assertStringContainsString(
				"seo_generator_render_block( '$block' )",
				$template_content,
				"Template should contain block: $block"
			);
		}
	}

	/**
	 * Test template contains breadcrumbs call.
	 */
	public function test_template_contains_breadcrumbs() {
		$template_path = SEO_GENERATOR_PLUGIN_DIR . 'templates/frontend/single-seo-page.php';
		$template_content = file_get_contents( $template_path );

		$this->assertStringContainsString( 'seo_generator_breadcrumbs()', $template_content );
	}

	/**
	 * Test template contains schema output call.
	 */
	public function test_template_contains_schema_output() {
		$template_path = SEO_GENERATOR_PLUGIN_DIR . 'templates/frontend/single-seo-page.php';
		$template_content = file_get_contents( $template_path );

		$this->assertStringContainsString( 'seo_generator_output_schema()', $template_content );
	}

	/**
	 * Test template has article wrapper with post ID.
	 */
	public function test_template_has_article_wrapper() {
		$template_path = SEO_GENERATOR_PLUGIN_DIR . 'templates/frontend/single-seo-page.php';
		$template_content = file_get_contents( $template_path );

		// Check for article tag with post ID.
		$this->assertStringContainsString( '<article id="post-', $template_content );
		$this->assertStringContainsString( 'post_class', $template_content );
	}

	/**
	 * Test template has ABSPATH guard.
	 */
	public function test_template_has_abspath_guard() {
		$template_path = SEO_GENERATOR_PLUGIN_DIR . 'templates/frontend/single-seo-page.php';
		$template_content = file_get_contents( $template_path );

		$this->assertStringContainsString( "defined( 'ABSPATH' ) || exit", $template_content );
	}

	/**
	 * Test template has proper HTML structure.
	 */
	public function test_template_has_proper_html_structure() {
		$template_path = SEO_GENERATOR_PLUGIN_DIR . 'templates/frontend/single-seo-page.php';
		$template_content = file_get_contents( $template_path );

		// Check for WordPress loop.
		$this->assertStringContainsString( 'while ( have_posts() )', $template_content );
		$this->assertStringContainsString( 'endwhile', $template_content );

		// Check for article wrapper.
		$this->assertStringContainsString( '<article', $template_content );
		$this->assertStringContainsString( '</article>', $template_content );
	}

	/**
	 * Test placeholder functions exist.
	 */
	public function test_placeholder_functions_exist() {
		$this->assertTrue( function_exists( 'seo_generator_render_block' ) );
		$this->assertTrue( function_exists( 'seo_generator_breadcrumbs' ) );
		$this->assertTrue( function_exists( 'seo_generator_output_schema' ) );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		global $wp_query;
		$wp_query = null;
		parent::tearDown();
	}
}
