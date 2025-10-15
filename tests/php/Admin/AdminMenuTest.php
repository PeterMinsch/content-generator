<?php
/**
 * Tests for AdminMenu
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Admin;

use SEOGenerator\Admin\AdminMenu;
use WP_UnitTestCase;

/**
 * AdminMenu test case.
 */
class AdminMenuTest extends WP_UnitTestCase {
	/**
	 * Admin menu instance.
	 *
	 * @var AdminMenu
	 */
	private $admin_menu;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->admin_menu = new AdminMenu();
		$this->admin_menu->register();

		// Trigger admin_menu action to add menu items.
		do_action( 'admin_menu' );
	}

	/**
	 * Test that admin menu is registered.
	 */
	public function test_admin_menu_is_registered() {
		global $menu;

		// Find the Content Generator menu.
		$found = false;
		if ( is_array( $menu ) ) {
			foreach ( $menu as $menu_item ) {
				if ( isset( $menu_item[0] ) && $menu_item[0] === 'Content Generator' ) {
					$found = true;
					break;
				}
			}
		}

		$this->assertTrue( $found, 'Content Generator menu should be registered' );
	}

	/**
	 * Test that menu has correct icon.
	 */
	public function test_menu_has_correct_icon() {
		global $menu;

		// Find the Content Generator menu and check icon.
		if ( is_array( $menu ) ) {
			foreach ( $menu as $menu_item ) {
				if ( isset( $menu_item[0] ) && $menu_item[0] === 'Content Generator' ) {
					$this->assertEquals( 'dashicons-edit-large', $menu_item[6], 'Menu should have dashicons-edit-large icon' );
					return;
				}
			}
		}

		$this->fail( 'Content Generator menu not found' );
	}

	/**
	 * Test that menu has correct position.
	 */
	public function test_menu_has_correct_position() {
		global $menu;

		// Find the Content Generator menu and check position.
		if ( is_array( $menu ) ) {
			foreach ( $menu as $position => $menu_item ) {
				if ( isset( $menu_item[0] ) && $menu_item[0] === 'Content Generator' ) {
					$this->assertEquals( 30, $position, 'Menu should be at position 30' );
					return;
				}
			}
		}

		$this->fail( 'Content Generator menu not found' );
	}

	/**
	 * Test that submenu items are registered.
	 */
	public function test_submenu_items_are_registered() {
		global $submenu;

		$parent_slug = 'seo-content-generator';

		$this->assertArrayHasKey( $parent_slug, $submenu, 'Submenu items should be registered' );

		$submenu_items = $submenu[ $parent_slug ];

		// Check that we have the expected number of submenu items.
		$this->assertGreaterThanOrEqual( 5, count( $submenu_items ), 'Should have at least 5 submenu items' );

		// Collect menu titles.
		$menu_titles = array_column( $submenu_items, 0 );

		// Check for expected submenu items.
		$this->assertContains( 'New Page', $menu_titles, 'Should have "New Page" submenu' );
		$this->assertContains( 'All SEO Pages', $menu_titles, 'Should have "All SEO Pages" submenu' );
		$this->assertContains( 'Image Library', $menu_titles, 'Should have "Image Library" submenu' );
		$this->assertContains( 'Settings', $menu_titles, 'Should have "Settings" submenu' );
		$this->assertContains( 'Analytics', $menu_titles, 'Should have "Analytics" submenu' );
	}

	/**
	 * Test that menu requires correct capability.
	 */
	public function test_menu_requires_edit_posts_capability() {
		global $menu;

		// Find the Content Generator menu and check capability.
		if ( is_array( $menu ) ) {
			foreach ( $menu as $menu_item ) {
				if ( isset( $menu_item[0] ) && $menu_item[0] === 'Content Generator' ) {
					$this->assertEquals( 'edit_posts', $menu_item[1], 'Menu should require edit_posts capability' );
					return;
				}
			}
		}

		$this->fail( 'Content Generator menu not found' );
	}

	/**
	 * Test that Settings submenu requires manage_options capability.
	 */
	public function test_settings_requires_manage_options_capability() {
		global $submenu;

		$parent_slug = 'seo-content-generator';

		$this->assertArrayHasKey( $parent_slug, $submenu, 'Submenu items should be registered' );

		$submenu_items = $submenu[ $parent_slug ];

		// Find Settings submenu.
		foreach ( $submenu_items as $item ) {
			if ( $item[0] === 'Settings' ) {
				$this->assertEquals( 'manage_options', $item[1], 'Settings should require manage_options capability' );
				return;
			}
		}

		$this->fail( 'Settings submenu not found' );
	}

	/**
	 * Test placeholder page output for Image Library.
	 */
	public function test_image_library_placeholder_output() {
		// Set up admin user with edit_posts capability.
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		// Capture output.
		ob_start();
		$this->admin_menu->renderImageLibraryPage();
		$output = ob_get_clean();

		// Check that output contains expected elements.
		$this->assertStringContainsString( 'Image Library Manager - Coming Soon', $output );
		$this->assertStringContainsString( 'class="wrap"', $output );
		$this->assertStringContainsString( 'notice notice-info', $output );
	}

	/**
	 * Test placeholder page output for Settings.
	 */
	public function test_settings_placeholder_output() {
		// Set up admin user with manage_options capability.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Capture output.
		ob_start();
		$this->admin_menu->renderSettingsPage();
		$output = ob_get_clean();

		// Check that output contains expected elements.
		$this->assertStringContainsString( 'Plugin Settings - Coming Soon', $output );
		$this->assertStringContainsString( 'class="wrap"', $output );
		$this->assertStringContainsString( 'notice notice-info', $output );
		$this->assertStringContainsString( 'OpenAI API settings', $output );
	}

	/**
	 * Test placeholder page output for Analytics.
	 */
	public function test_analytics_placeholder_output() {
		// Set up admin user with edit_posts capability.
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		// Capture output.
		ob_start();
		$this->admin_menu->renderAnalyticsPage();
		$output = ob_get_clean();

		// Check that output contains expected elements.
		$this->assertStringContainsString( 'Analytics & Reporting - Coming Soon', $output );
		$this->assertStringContainsString( 'class="wrap"', $output );
		$this->assertStringContainsString( 'notice notice-info', $output );
	}

	/**
	 * Test that Settings page checks user capabilities.
	 */
	public function test_settings_page_capability_check() {
		// Set up user without manage_options capability.
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		// Expect wp_die to be called.
		$this->expectException( 'WPDieException' );
		$this->admin_menu->renderSettingsPage();
	}

	/**
	 * Test that Image Library page checks user capabilities.
	 */
	public function test_image_library_capability_check() {
		// Set up user without edit_posts capability.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		// Expect wp_die to be called.
		$this->expectException( 'WPDieException' );
		$this->admin_menu->renderImageLibraryPage();
	}

	/**
	 * Test that Analytics page checks user capabilities.
	 */
	public function test_analytics_capability_check() {
		// Set up user without edit_posts capability.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		// Expect wp_die to be called.
		$this->expectException( 'WPDieException' );
		$this->admin_menu->renderAnalyticsPage();
	}

	/**
	 * Test that "All SEO Pages" submenu links to correct URL.
	 */
	public function test_all_pages_submenu_link() {
		global $submenu;

		$parent_slug = 'seo-content-generator';
		$submenu_items = $submenu[ $parent_slug ];

		// Find "All SEO Pages" submenu.
		foreach ( $submenu_items as $item ) {
			if ( $item[0] === 'All SEO Pages' ) {
				$this->assertEquals( 'edit.php?post_type=seo-page', $item[2], 'All SEO Pages should link to CPT list' );
				return;
			}
		}

		$this->fail( 'All SEO Pages submenu not found' );
	}
}
