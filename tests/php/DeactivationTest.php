<?php
/**
 * Tests for Plugin Deactivation
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests;

use SEOGenerator\Activation;
use SEOGenerator\Deactivation;
use WP_UnitTestCase;

/**
 * Deactivation test case.
 */
class DeactivationTest extends WP_UnitTestCase {
	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Activate plugin first to set up data.
		Activation::activate();
	}

	/**
	 * Test that deactivation does not delete posts.
	 */
	public function test_deactivation_does_not_delete_posts() {
		// Create a test SEO page.
		$post_id = $this->factory->post->create(
			array(
				'post_type' => 'seo-page',
				'post_title' => 'Test SEO Page',
			)
		);

		// Deactivate plugin.
		Deactivation::deactivate();

		// Check that post still exists.
		$post = get_post( $post_id );

		$this->assertNotNull( $post, 'Post should still exist after deactivation' );
	}

	/**
	 * Test that deactivation does not delete options.
	 */
	public function test_deactivation_does_not_delete_options() {
		// Set plugin settings.
		$settings = array( 'test_key' => 'test_value' );
		update_option( 'seo_generator_settings', $settings );

		// Deactivate plugin.
		Deactivation::deactivate();

		// Check that options still exist.
		$saved_settings = get_option( 'seo_generator_settings' );

		$this->assertEquals( $settings, $saved_settings, 'Settings should not be deleted on deactivation' );
	}

	/**
	 * Test that deactivation does not delete database tables.
	 */
	public function test_deactivation_does_not_delete_tables() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'seo_generation_log';

		// Deactivate plugin.
		Deactivation::deactivate();

		// Check that table still exists.
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;

		$this->assertTrue( $table_exists, 'Database table should not be deleted on deactivation' );
	}

	/**
	 * Test that deactivation does not delete taxonomy terms.
	 */
	public function test_deactivation_does_not_delete_terms() {
		// Deactivate plugin.
		Deactivation::deactivate();

		// Check that terms still exist.
		$term = term_exists( 'platinum', 'image-tag' );

		$this->assertNotNull( $term, 'Taxonomy terms should not be deleted on deactivation' );
	}

	/**
	 * Test that deactivation clears plugin transients.
	 */
	public function test_deactivation_clears_transients() {
		// Set a plugin transient.
		set_transient( 'seo_gen_test_transient', 'test_value', HOUR_IN_SECONDS );

		// Deactivate plugin.
		Deactivation::deactivate();

		// Check that transient is deleted.
		$transient = get_transient( 'seo_gen_test_transient' );

		$this->assertFalse( $transient, 'Plugin transients should be cleared on deactivation' );
	}

	/**
	 * Test that deactivation preserves user data integrity.
	 */
	public function test_deactivation_preserves_user_data() {
		global $wpdb;

		// Create test data in generation log.
		$table_name = $wpdb->prefix . 'seo_generation_log';

		$wpdb->insert(
			$table_name,
			array(
				'post_id'           => 1,
				'block_type'        => 'hero_section',
				'prompt_tokens'     => 100,
				'completion_tokens' => 200,
				'total_tokens'      => 300,
				'cost'              => 0.05,
				'model'             => 'gpt-4',
				'status'            => 'success',
				'user_id'           => 1,
				'created_at'        => current_time( 'mysql' ),
			)
		);

		$inserted_id = $wpdb->insert_id;

		// Deactivate plugin.
		Deactivation::deactivate();

		// Check that data still exists.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $inserted_id ) );

		$this->assertNotNull( $row, 'Generation log data should be preserved on deactivation' );
		$this->assertEquals( 'hero_section', $row->block_type );
	}

	/**
	 * Test that version option is preserved.
	 */
	public function test_deactivation_preserves_version() {
		$version = get_option( 'seo_generator_version' );

		// Deactivate plugin.
		Deactivation::deactivate();

		$saved_version = get_option( 'seo_generator_version' );

		$this->assertEquals( $version, $saved_version, 'Plugin version should be preserved on deactivation' );
	}
}
