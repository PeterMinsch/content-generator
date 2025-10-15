<?php
/**
 * Tests for Plugin Activation
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests;

use SEOGenerator\Activation;
use SEOGenerator\Taxonomies\SEOTopic;
use SEOGenerator\Taxonomies\ImageTag;
use WP_UnitTestCase;

/**
 * Activation test case.
 */
class ActivationTest extends WP_UnitTestCase {
	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Register taxonomies so terms can be created.
		$seo_topic = new SEOTopic();
		$seo_topic->register();

		$image_tag = new ImageTag();
		$image_tag->register();
	}

	/**
	 * Test that generation log table is created.
	 */
	public function test_generation_log_table_created() {
		global $wpdb;

		// Activate plugin (creates tables).
		Activation::activate();

		$table_name = $wpdb->prefix . 'seo_generation_log';

		// Check if table exists.
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;

		$this->assertTrue( $table_exists, 'Generation log table should be created' );
	}

	/**
	 * Test that default SEO Topic terms are created.
	 */
	public function test_default_seo_topic_terms_created() {
		// Activate plugin (creates terms).
		Activation::activate();

		$expected_topics = array(
			'Engagement Rings',
			'Wedding Bands',
			"Men's Wedding Bands",
			"Women's Wedding Bands",
			'Education',
			'Comparisons',
		);

		foreach ( $expected_topics as $topic ) {
			$term = term_exists( $topic, SEOTopic::TAXONOMY );
			$this->assertNotNull( $term, "Term '{$topic}' should exist" );
		}
	}

	/**
	 * Test that default Image Tag terms are created.
	 */
	public function test_default_image_tag_terms_created() {
		// Activate plugin (creates terms).
		Activation::activate();

		$expected_tags = array(
			'platinum',
			'gold',
			'white-gold',
			'rose-gold',
			'tungsten',
			'titanium',
			'wedding-band',
			'engagement-ring',
			'fashion',
			'mens',
			'womens',
			'unisex',
			'classic',
			'modern',
			'vintage',
			'minimalist',
			'polished',
			'brushed',
			'hammered',
			'matte',
		);

		foreach ( $expected_tags as $tag ) {
			$term = term_exists( $tag, ImageTag::TAXONOMY );
			$this->assertNotNull( $term, "Term '{$tag}' should exist" );
		}
	}

	/**
	 * Test that plugin version is stored.
	 */
	public function test_plugin_version_stored() {
		Activation::activate();

		$version = get_option( 'seo_generator_version' );

		$this->assertEquals( SEO_GENERATOR_VERSION, $version );
	}

	/**
	 * Test that duplicate terms are not created on repeated activation.
	 */
	public function test_duplicate_terms_not_created() {
		// Activate twice.
		Activation::activate();
		Activation::activate();

		// Check that there's only one "platinum" term.
		$terms = get_terms(
			array(
				'taxonomy'   => ImageTag::TAXONOMY,
				'name'       => 'platinum',
				'hide_empty' => false,
			)
		);

		$this->assertCount( 1, $terms, 'Should not create duplicate terms' );
	}

	/**
	 * Test that default plugin settings are created.
	 */
	public function test_default_settings_created() {
		// Delete option first to ensure clean test.
		delete_option( 'seo_generator_settings' );

		// Activate plugin.
		Activation::activate();

		$settings = get_option( 'seo_generator_settings' );

		$this->assertIsArray( $settings, 'Settings should be an array' );
		$this->assertArrayHasKey( 'openai_api_key', $settings );
		$this->assertArrayHasKey( 'openai_model', $settings );
		$this->assertArrayHasKey( 'max_tokens', $settings );
		$this->assertArrayHasKey( 'temperature', $settings );
		$this->assertArrayHasKey( 'enable_cost_tracking', $settings );
	}

	/**
	 * Test that existing settings are not overwritten on reactivation.
	 */
	public function test_existing_settings_not_overwritten() {
		// Set custom settings.
		$custom_settings = array(
			'openai_api_key' => 'test-key-123',
			'openai_model'   => 'gpt-3.5-turbo',
		);
		update_option( 'seo_generator_settings', $custom_settings );

		// Activate plugin.
		Activation::activate();

		$settings = get_option( 'seo_generator_settings' );

		$this->assertEquals( 'test-key-123', $settings['openai_api_key'], 'Custom API key should not be overwritten' );
		$this->assertEquals( 'gpt-3.5-turbo', $settings['openai_model'], 'Custom model should not be overwritten' );
	}

	/**
	 * Test that generation log table has correct schema.
	 */
	public function test_generation_log_table_schema() {
		global $wpdb;

		// Activate plugin.
		Activation::activate();

		$table_name = $wpdb->prefix . 'seo_generation_log';

		// Check columns exist.
		$columns = $wpdb->get_results( "DESCRIBE {$table_name}" );

		$column_names = wp_list_pluck( $columns, 'Field' );

		$this->assertContains( 'id', $column_names );
		$this->assertContains( 'post_id', $column_names );
		$this->assertContains( 'block_type', $column_names );
		$this->assertContains( 'prompt_tokens', $column_names );
		$this->assertContains( 'completion_tokens', $column_names );
		$this->assertContains( 'total_tokens', $column_names );
		$this->assertContains( 'cost', $column_names );
		$this->assertContains( 'model', $column_names );
		$this->assertContains( 'status', $column_names );
		$this->assertContains( 'error_message', $column_names );
		$this->assertContains( 'user_id', $column_names );
		$this->assertContains( 'created_at', $column_names );
	}
}
