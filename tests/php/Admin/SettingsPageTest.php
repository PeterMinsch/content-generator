<?php
/**
 * Tests for SettingsPage
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Admin;

use SEOGenerator\Admin\SettingsPage;
use WP_UnitTestCase;

/**
 * SettingsPage test case.
 */
class SettingsPageTest extends WP_UnitTestCase {
	/**
	 * Settings page instance.
	 *
	 * @var SettingsPage
	 */
	private $settings_page;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->settings_page = new SettingsPage();
		$this->settings_page->register();

		// Trigger admin_init to register settings.
		do_action( 'admin_init' );
	}

	/**
	 * Test that settings are registered.
	 */
	public function test_settings_are_registered() {
		global $wp_registered_settings;

		$option_name = SettingsPage::getOptionName();

		$this->assertArrayHasKey( $option_name, $wp_registered_settings, 'Settings should be registered' );
	}

	/**
	 * Test that option group is correct.
	 */
	public function test_option_group_is_correct() {
		global $wp_registered_settings;

		$option_name = SettingsPage::getOptionName();

		$this->assertEquals(
			SettingsPage::getOptionGroup(),
			$wp_registered_settings[ $option_name ]['group'],
			'Option group should match'
		);
	}

	/**
	 * Test settings sanitization callback is registered.
	 */
	public function test_sanitization_callback_is_registered() {
		global $wp_registered_settings;

		$option_name = SettingsPage::getOptionName();

		$this->assertIsArray( $wp_registered_settings[ $option_name ]['sanitize_callback'] );
	}

	/**
	 * Test settings page render requires manage_options capability.
	 */
	public function test_settings_page_capability_check() {
		// Set up user without manage_options capability.
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		// Expect wp_die to be called.
		$this->expectException( 'WPDieException' );
		$this->settings_page->render();
	}

	/**
	 * Test settings page renders without errors for admin.
	 */
	public function test_settings_page_renders_for_admin() {
		// Set up admin user.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Set active tab.
		$_GET['tab'] = 'api';

		// Capture output.
		ob_start();
		$this->settings_page->render();
		$output = ob_get_clean();

		// Check that output contains expected elements.
		$this->assertStringContainsString( 'class="wrap"', $output );
		$this->assertStringContainsString( 'nav-tab-wrapper', $output );
		$this->assertStringContainsString( 'API Configuration', $output );
	}

	/**
	 * Test all 5 tabs are present in output.
	 */
	public function test_all_tabs_are_present() {
		// Set up admin user.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Capture output.
		ob_start();
		$this->settings_page->render();
		$output = ob_get_clean();

		// Check for all tab labels.
		$this->assertStringContainsString( 'API Configuration', $output );
		$this->assertStringContainsString( 'Default Content', $output );
		$this->assertStringContainsString( 'Prompt Templates', $output );
		$this->assertStringContainsString( 'Image Library', $output );
		$this->assertStringContainsString( 'Limits & Tracking', $output );
	}

	/**
	 * Test active tab is highlighted.
	 */
	public function test_active_tab_is_highlighted() {
		// Set up admin user.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Set active tab to 'prompts'.
		$_GET['tab'] = 'prompts';

		// Capture output.
		ob_start();
		$this->settings_page->render();
		$output = ob_get_clean();

		// Check that prompts tab is active.
		$this->assertStringContainsString( 'nav-tab-active', $output );
	}

	/**
	 * Test default tab is 'api' when no tab specified.
	 */
	public function test_default_tab_is_api() {
		// Set up admin user.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Don't set $_GET['tab'].
		unset( $_GET['tab'] );

		// Capture output.
		ob_start();
		$this->settings_page->render();
		$output = ob_get_clean();

		// Check that API tab content is displayed.
		$this->assertStringContainsString( 'API Configuration', $output );
	}

	/**
	 * Test save button is present.
	 */
	public function test_save_button_is_present() {
		// Set up admin user.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Capture output.
		ob_start();
		$this->settings_page->render();
		$output = ob_get_clean();

		// Check for submit button.
		$this->assertStringContainsString( 'type="submit"', $output );
		$this->assertStringContainsString( 'submit', $output );
	}

	/**
	 * Test nonce field is present.
	 */
	public function test_nonce_field_is_present() {
		// Set up admin user.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Capture output.
		ob_start();
		$this->settings_page->render();
		$output = ob_get_clean();

		// Check for nonce field (WordPress Settings API adds this automatically).
		$this->assertStringContainsString( '_wpnonce', $output );
	}

	/**
	 * Test sanitization handles non-array input.
	 */
	public function test_sanitization_handles_non_array() {
		$result = $this->settings_page->sanitizeSettings( 'not an array' );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test sanitization preserves other settings.
	 */
	public function test_sanitization_preserves_other_settings() {
		// First set some existing settings.
		update_option(
			'seo_generator_settings',
			array(
				'some_other_setting' => 'preserved value',
			)
		);

		$input = array(
			'model'       => 'gpt-4',
			'temperature' => 0.8,
		);

		$result = $this->settings_page->sanitizeSettings( $input );

		$this->assertIsArray( $result );
		$this->assertEquals( 'gpt-4', $result['model'] );
		$this->assertEquals( 0.8, $result['temperature'] );
		// Other settings should be preserved.
		$this->assertEquals( 'preserved value', $result['some_other_setting'] );

		// Clean up.
		delete_option( 'seo_generator_settings' );
	}

	/**
	 * Test placeholder content is shown for non-API tabs.
	 */
	public function test_placeholder_content_for_non_api_tabs() {
		// Set up admin user.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Only check tabs that still have placeholders (not 'api').
		$tabs = array( 'defaults', 'prompts', 'images', 'limits' );

		foreach ( $tabs as $tab ) {
			$_GET['tab'] = $tab;

			// Capture output.
			ob_start();
			$this->settings_page->render();
			$output = ob_get_clean();

			// Check for "Coming Soon" placeholder.
			$this->assertStringContainsString( 'Coming Soon:', $output, "Tab '{$tab}' should have placeholder content" );
		}
	}

	/**
	 * Test admin notice displays after settings saved.
	 */
	public function test_admin_notice_after_save() {
		// Set up admin user.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Simulate settings saved.
		$_GET['settings-updated'] = 'true';

		// Mock the screen.
		set_current_screen( 'settings_page_seo-generator-settings' );

		// Capture output.
		ob_start();
		$this->settings_page->displayAdminNotices();
		$output = ob_get_clean();

		// Check for success notice.
		$this->assertStringContainsString( 'notice-success', $output );
		$this->assertStringContainsString( 'Settings saved successfully', $output );
	}

	/**
	 * Test option name getter.
	 */
	public function test_get_option_name() {
		$this->assertEquals( 'seo_generator_settings', SettingsPage::getOptionName() );
	}

	/**
	 * Test option group getter.
	 */
	public function test_get_option_group() {
		$this->assertEquals( 'seo_generator_options', SettingsPage::getOptionGroup() );
	}

	/**
	 * Test page slug getter.
	 */
	public function test_get_page_slug() {
		$this->assertEquals( 'seo-generator-settings', SettingsPage::getPageSlug() );
	}

	/**
	 * Test API key encryption during sanitization.
	 */
	public function test_api_key_encryption() {
		$input = array(
			'openai_api_key' => 'sk-test1234567890',
			'model'          => 'gpt-4',
			'temperature'    => 0.7,
			'max_tokens'     => 1000,
		);

		$result = $this->settings_page->sanitizeSettings( $input );

		// API key should be encrypted (not the same as input).
		$this->assertNotEquals( 'sk-test1234567890', $result['openai_api_key'] );
		// API key should not be empty.
		$this->assertNotEmpty( $result['openai_api_key'] );
	}

	/**
	 * Test API key encryption rejects invalid format.
	 */
	public function test_api_key_validation_rejects_invalid_format() {
		$input = array(
			'openai_api_key' => 'invalid-key-format',
		);

		$result = $this->settings_page->sanitizeSettings( $input );

		// Invalid key should not be saved, existing should be preserved.
		$this->assertEmpty( $result['openai_api_key'] );
	}

	/**
	 * Test masked API key is preserved during save.
	 */
	public function test_masked_api_key_is_preserved() {
		// First, save a valid API key.
		$encrypted = seo_generator_encrypt_api_key( 'sk-original-key' );
		update_option( 'seo_generator_settings', array( 'openai_api_key' => $encrypted ) );

		// Now try to save with masked value.
		$input = array(
			'openai_api_key' => '****************************************',
		);

		$result = $this->settings_page->sanitizeSettings( $input );

		// Original encrypted key should be preserved.
		$this->assertEquals( $encrypted, $result['openai_api_key'] );

		// Clean up.
		delete_option( 'seo_generator_settings' );
	}

	/**
	 * Test model validation accepts valid models.
	 */
	public function test_model_validation_accepts_valid_models() {
		$valid_models = array( 'gpt-4-turbo-preview', 'gpt-4', 'gpt-3.5-turbo' );

		foreach ( $valid_models as $model ) {
			$input = array(
				'model' => $model,
			);

			$result = $this->settings_page->sanitizeSettings( $input );

			$this->assertEquals( $model, $result['model'] );
		}
	}

	/**
	 * Test model validation rejects invalid models.
	 */
	public function test_model_validation_rejects_invalid_models() {
		$input = array(
			'model' => 'invalid-model',
		);

		$result = $this->settings_page->sanitizeSettings( $input );

		// Should fall back to default.
		$this->assertEquals( 'gpt-4-turbo-preview', $result['model'] );
	}

	/**
	 * Test temperature validation enforces range.
	 */
	public function test_temperature_validation_enforces_range() {
		// Test too low.
		$input  = array( 'temperature' => 0.05 );
		$result = $this->settings_page->sanitizeSettings( $input );
		$this->assertEquals( 0.1, $result['temperature'] );

		// Test too high.
		$input  = array( 'temperature' => 1.5 );
		$result = $this->settings_page->sanitizeSettings( $input );
		$this->assertEquals( 1.0, $result['temperature'] );

		// Test valid value.
		$input  = array( 'temperature' => 0.7 );
		$result = $this->settings_page->sanitizeSettings( $input );
		$this->assertEquals( 0.7, $result['temperature'] );
	}

	/**
	 * Test max tokens validation enforces range.
	 */
	public function test_max_tokens_validation_enforces_range() {
		// Test too low.
		$input  = array( 'max_tokens' => 50 );
		$result = $this->settings_page->sanitizeSettings( $input );
		$this->assertEquals( 100, $result['max_tokens'] );

		// Test too high.
		$input  = array( 'max_tokens' => 5000 );
		$result = $this->settings_page->sanitizeSettings( $input );
		$this->assertEquals( 4000, $result['max_tokens'] );

		// Test valid value.
		$input  = array( 'max_tokens' => 1500 );
		$result = $this->settings_page->sanitizeSettings( $input );
		$this->assertEquals( 1500, $result['max_tokens'] );
	}

	/**
	 * Test Test Connection requires manage_options capability.
	 */
	public function test_test_connection_requires_capability() {
		// Set up user without manage_options capability.
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		// Mock AJAX request.
		$_POST['api_key'] = 'sk-test';
		$_POST['nonce']   = wp_create_nonce( 'seo_generator_nonce' );

		// Capture output.
		ob_start();
		$this->settings_page->testConnection();
		$output = ob_get_clean();

		// Should return error JSON.
		$response = json_decode( $output, true );
		$this->assertFalse( $response['success'] );
	}

	/**
	 * Test Test Connection validates API key format.
	 */
	public function test_test_connection_validates_api_key_format() {
		// Set up admin user.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Mock AJAX request with invalid key.
		$_POST['api_key'] = 'invalid-key';
		$_POST['nonce']   = wp_create_nonce( 'seo_generator_nonce' );

		// Capture output.
		ob_start();
		$this->settings_page->testConnection();
		$output = ob_get_clean();

		// Should return error JSON.
		$response = json_decode( $output, true );
		$this->assertFalse( $response['success'] );
		$this->assertStringContainsString( 'Invalid API key format', $response['data']['message'] );
	}

	/**
	 * Test API configuration fields are rendered.
	 */
	public function test_api_configuration_fields_are_rendered() {
		// Set up admin user.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$_GET['tab'] = 'api';

		// Capture output.
		ob_start();
		$this->settings_page->render();
		$output = ob_get_clean();

		// Check for API configuration fields.
		$this->assertStringContainsString( 'api_key', $output );
		$this->assertStringContainsString( 'model', $output );
		$this->assertStringContainsString( 'temperature', $output );
		$this->assertStringContainsString( 'max_tokens', $output );
		$this->assertStringContainsString( 'test-connection-btn', $output );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		unset( $_GET['tab'] );
		unset( $_GET['settings-updated'] );
		unset( $_POST['api_key'] );
		unset( $_POST['nonce'] );
		parent::tearDown();
	}
}
