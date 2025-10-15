<?php
/**
 * Tests for Plugin Class
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests;

use SEOGenerator\Plugin;
use WP_UnitTestCase;

/**
 * Plugin test case.
 */
class PluginTest extends WP_UnitTestCase {
	/**
	 * Test that getInstance returns same instance (singleton).
	 */
	public function test_singleton_returns_same_instance() {
		$instance1 = Plugin::getInstance();
		$instance2 = Plugin::getInstance();

		$this->assertSame( $instance1, $instance2, 'getInstance should return the same instance' );
	}

	/**
	 * Test that getInstance returns Plugin instance.
	 */
	public function test_get_instance_returns_plugin_instance() {
		$instance = Plugin::getInstance();

		$this->assertInstanceOf( Plugin::class, $instance );
	}

	/**
	 * Test that container is available.
	 */
	public function test_container_is_available() {
		$plugin    = Plugin::getInstance();
		$container = $plugin->getContainer();

		$this->assertInstanceOf( 'SEOGenerator\Container', $container );
	}

	/**
	 * Test that post type is registered in container.
	 */
	public function test_post_type_registered_in_container() {
		$plugin    = Plugin::getInstance();
		$container = $plugin->getContainer();

		$this->assertTrue( $container->has( 'post_type.seo_page' ), 'SEO Page post type should be registered' );
	}

	/**
	 * Test that taxonomies are registered in container.
	 */
	public function test_taxonomies_registered_in_container() {
		$plugin    = Plugin::getInstance();
		$container = $plugin->getContainer();

		$this->assertTrue( $container->has( 'taxonomy.seo_topic' ), 'SEO Topic taxonomy should be registered' );
		$this->assertTrue( $container->has( 'taxonomy.image_tag' ), 'Image Tag taxonomy should be registered' );
	}

	/**
	 * Test that ACF fields are registered in container.
	 */
	public function test_acf_fields_registered_in_container() {
		$plugin    = Plugin::getInstance();
		$container = $plugin->getContainer();

		$this->assertTrue( $container->has( 'acf.fields' ), 'ACF fields should be registered' );
	}

	/**
	 * Test that settings page is registered in container.
	 */
	public function test_settings_page_registered_in_container() {
		$plugin    = Plugin::getInstance();
		$container = $plugin->getContainer();

		$this->assertTrue( $container->has( 'admin.settings' ), 'Settings page should be registered' );
	}

	/**
	 * Test that admin menu is registered in container.
	 */
	public function test_admin_menu_registered_in_container() {
		$plugin    = Plugin::getInstance();
		$container = $plugin->getContainer();

		$this->assertTrue( $container->has( 'admin.menu' ), 'Admin menu should be registered' );
	}

	/**
	 * Test that post types and taxonomies hooks are registered.
	 */
	public function test_init_hook_registered() {
		$this->assertGreaterThan( 0, has_action( 'init', array( Plugin::getInstance(), 'registerPostTypesAndTaxonomies' ) ) );
	}

	/**
	 * Test that ACF hooks are registered.
	 */
	public function test_acf_hooks_registered() {
		$this->assertNotFalse( has_filter( 'acf/settings/save_json' ) );
		$this->assertNotFalse( has_filter( 'acf/settings/load_json' ) );
	}

	/**
	 * Test ACF JSON save path filter.
	 */
	public function test_acf_json_save_path() {
		$plugin  = Plugin::getInstance();
		$path    = $plugin->acfJsonSavePath( '' );
		$expected = SEO_GENERATOR_PLUGIN_DIR . 'acf-json';

		$this->assertEquals( $expected, $path );
	}

	/**
	 * Test ACF JSON load path filter.
	 */
	public function test_acf_json_load_path() {
		$plugin   = Plugin::getInstance();
		$paths    = $plugin->acfJsonLoadPath( array() );
		$expected = SEO_GENERATOR_PLUGIN_DIR . 'acf-json';

		$this->assertContains( $expected, $paths );
	}

	/**
	 * Test that post types are registered after init.
	 */
	public function test_post_types_registered_after_init() {
		// Trigger init action.
		do_action( 'init' );

		$this->assertTrue( post_type_exists( 'seo-page' ), 'SEO Page post type should exist after init' );
	}

	/**
	 * Test that taxonomies are registered after init.
	 */
	public function test_taxonomies_registered_after_init() {
		// Trigger init action.
		do_action( 'init' );

		$this->assertTrue( taxonomy_exists( 'seo-topic' ), 'SEO Topic taxonomy should exist after init' );
		$this->assertTrue( taxonomy_exists( 'image-tag' ), 'Image Tag taxonomy should exist after init' );
	}
}
