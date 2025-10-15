<?php
/**
 * Tests for SettingsService
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Services;

use SEOGenerator\Services\SettingsService;
use WP_UnitTestCase;

/**
 * SettingsService test case.
 */
class SettingsServiceTest extends WP_UnitTestCase {
	/**
	 * Settings service instance.
	 *
	 * @var SettingsService
	 */
	private $service;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->service = new SettingsService();

		// Clear any existing settings.
		delete_option( 'seo_generator_settings' );
		wp_cache_flush();
	}

	/**
	 * Test getApiSettings returns defaults when no settings exist.
	 */
	public function test_get_api_settings_returns_defaults() {
		$settings = $this->service->getApiSettings();

		$this->assertIsArray( $settings );
		$this->assertEquals( '', $settings['openai_api_key'] );
		$this->assertEquals( 'gpt-4-turbo-preview', $settings['model'] );
		$this->assertEquals( 0.7, $settings['temperature'] );
		$this->assertEquals( 1000, $settings['max_tokens'] );
		$this->assertTrue( $settings['enable_cost_tracking'] );
		$this->assertEquals( 100.00, $settings['monthly_budget'] );
		$this->assertEquals( 80, $settings['alert_threshold_percent'] );
	}

	/**
	 * Test getApiSettings returns saved settings.
	 */
	public function test_get_api_settings_returns_saved_settings() {
		$encrypted_key = seo_generator_encrypt_api_key( 'sk-test-key' );

		update_option(
			'seo_generator_settings',
			array(
				'openai_api_key' => $encrypted_key,
				'model'          => 'gpt-4',
				'temperature'    => 0.5,
				'max_tokens'     => 2000,
			)
		);

		wp_cache_flush();

		$settings = $this->service->getApiSettings();

		$this->assertEquals( $encrypted_key, $settings['openai_api_key'] );
		$this->assertEquals( 'gpt-4', $settings['model'] );
		$this->assertEquals( 0.5, $settings['temperature'] );
		$this->assertEquals( 2000, $settings['max_tokens'] );
	}

	/**
	 * Test getApiKey returns decrypted API key.
	 */
	public function test_get_api_key_returns_decrypted_key() {
		$original_key  = 'sk-test-key-123';
		$encrypted_key = seo_generator_encrypt_api_key( $original_key );

		update_option(
			'seo_generator_settings',
			array(
				'openai_api_key' => $encrypted_key,
			)
		);

		wp_cache_flush();

		$api_key = $this->service->getApiKey();

		$this->assertEquals( $original_key, $api_key );
	}

	/**
	 * Test getApiKey returns empty string when no key set.
	 */
	public function test_get_api_key_returns_empty_when_not_set() {
		$api_key = $this->service->getApiKey();

		$this->assertEquals( '', $api_key );
	}

	/**
	 * Test getModel returns saved model.
	 */
	public function test_get_model_returns_saved_model() {
		update_option(
			'seo_generator_settings',
			array(
				'model' => 'gpt-3.5-turbo',
			)
		);

		wp_cache_flush();

		$model = $this->service->getModel();

		$this->assertEquals( 'gpt-3.5-turbo', $model );
	}

	/**
	 * Test getModel returns default when not set.
	 */
	public function test_get_model_returns_default() {
		$model = $this->service->getModel();

		$this->assertEquals( 'gpt-4-turbo-preview', $model );
	}

	/**
	 * Test getTemperature returns saved temperature.
	 */
	public function test_get_temperature_returns_saved_temperature() {
		update_option(
			'seo_generator_settings',
			array(
				'temperature' => 0.9,
			)
		);

		wp_cache_flush();

		$temperature = $this->service->getTemperature();

		$this->assertEquals( 0.9, $temperature );
	}

	/**
	 * Test getTemperature returns default when not set.
	 */
	public function test_get_temperature_returns_default() {
		$temperature = $this->service->getTemperature();

		$this->assertEquals( 0.7, $temperature );
	}

	/**
	 * Test getMaxTokens returns saved value.
	 */
	public function test_get_max_tokens_returns_saved_value() {
		update_option(
			'seo_generator_settings',
			array(
				'max_tokens' => 1500,
			)
		);

		wp_cache_flush();

		$max_tokens = $this->service->getMaxTokens();

		$this->assertEquals( 1500, $max_tokens );
	}

	/**
	 * Test getMaxTokens returns default when not set.
	 */
	public function test_get_max_tokens_returns_default() {
		$max_tokens = $this->service->getMaxTokens();

		$this->assertEquals( 1000, $max_tokens );
	}

	/**
	 * Test getMonthlyBudget returns saved value.
	 */
	public function test_get_monthly_budget_returns_saved_value() {
		update_option(
			'seo_generator_settings',
			array(
				'monthly_budget' => 200.00,
			)
		);

		wp_cache_flush();

		$budget = $this->service->getMonthlyBudget();

		$this->assertEquals( 200.00, $budget );
	}

	/**
	 * Test getMonthlyBudget returns default when not set.
	 */
	public function test_get_monthly_budget_returns_default() {
		$budget = $this->service->getMonthlyBudget();

		$this->assertEquals( 100.00, $budget );
	}

	/**
	 * Test isCostTrackingEnabled returns saved value.
	 */
	public function test_is_cost_tracking_enabled_returns_saved_value() {
		update_option(
			'seo_generator_settings',
			array(
				'enable_cost_tracking' => false,
			)
		);

		wp_cache_flush();

		$enabled = $this->service->isCostTrackingEnabled();

		$this->assertFalse( $enabled );
	}

	/**
	 * Test isCostTrackingEnabled returns default when not set.
	 */
	public function test_is_cost_tracking_enabled_returns_default() {
		$enabled = $this->service->isCostTrackingEnabled();

		$this->assertTrue( $enabled );
	}

	/**
	 * Test getAlertThresholdPercent returns saved value.
	 */
	public function test_get_alert_threshold_percent_returns_saved_value() {
		update_option(
			'seo_generator_settings',
			array(
				'alert_threshold_percent' => 90,
			)
		);

		wp_cache_flush();

		$threshold = $this->service->getAlertThresholdPercent();

		$this->assertEquals( 90, $threshold );
	}

	/**
	 * Test getAlertThresholdPercent returns default when not set.
	 */
	public function test_get_alert_threshold_percent_returns_default() {
		$threshold = $this->service->getAlertThresholdPercent();

		$this->assertEquals( 80, $threshold );
	}

	/**
	 * Test updateSettings saves settings.
	 */
	public function test_update_settings_saves_settings() {
		$settings = array(
			'model'       => 'gpt-4',
			'temperature' => 0.8,
		);

		$updated = $this->service->updateSettings( $settings );

		$this->assertTrue( $updated );

		wp_cache_flush();

		$saved = $this->service->getApiSettings();

		$this->assertEquals( 'gpt-4', $saved['model'] );
		$this->assertEquals( 0.8, $saved['temperature'] );
	}

	/**
	 * Test updateSettings clears cache.
	 */
	public function test_update_settings_clears_cache() {
		// Set initial settings.
		update_option(
			'seo_generator_settings',
			array(
				'model' => 'gpt-4',
			)
		);

		// Get settings to cache them.
		$this->service->getApiSettings();

		// Update settings.
		$this->service->updateSettings(
			array(
				'model' => 'gpt-3.5-turbo',
			)
		);

		// Get settings again - should be updated, not cached.
		$settings = $this->service->getApiSettings();

		$this->assertEquals( 'gpt-3.5-turbo', $settings['model'] );
	}

	/**
	 * Test clearCache clears the cache.
	 */
	public function test_clear_cache() {
		// Set settings.
		update_option(
			'seo_generator_settings',
			array(
				'model' => 'gpt-4',
			)
		);

		// Get settings to cache them.
		$this->service->getApiSettings();

		// Update option directly (bypassing service).
		update_option(
			'seo_generator_settings',
			array(
				'model' => 'gpt-3.5-turbo',
			)
		);

		// Clear cache.
		$this->service->clearCache();

		// Get settings - should reflect the direct update.
		$settings = $this->service->getApiSettings();

		$this->assertEquals( 'gpt-3.5-turbo', $settings['model'] );
	}

	/**
	 * Test isApiConfigured returns true when API key set.
	 */
	public function test_is_api_configured_returns_true_when_key_set() {
		$encrypted_key = seo_generator_encrypt_api_key( 'sk-test-key' );

		update_option(
			'seo_generator_settings',
			array(
				'openai_api_key' => $encrypted_key,
			)
		);

		wp_cache_flush();

		$configured = $this->service->isApiConfigured();

		$this->assertTrue( $configured );
	}

	/**
	 * Test isApiConfigured returns false when API key not set.
	 */
	public function test_is_api_configured_returns_false_when_key_not_set() {
		$configured = $this->service->isApiConfigured();

		$this->assertFalse( $configured );
	}

	/**
	 * Test get retrieves specific setting.
	 */
	public function test_get_retrieves_specific_setting() {
		update_option(
			'seo_generator_settings',
			array(
				'model' => 'gpt-4',
			)
		);

		wp_cache_flush();

		$value = $this->service->get( 'model' );

		$this->assertEquals( 'gpt-4', $value );
	}

	/**
	 * Test get returns default when setting not found.
	 */
	public function test_get_returns_default_when_not_found() {
		$value = $this->service->get( 'nonexistent_key', 'default_value' );

		$this->assertEquals( 'default_value', $value );
	}

	/**
	 * Test set saves specific setting.
	 */
	public function test_set_saves_specific_setting() {
		$updated = $this->service->set( 'model', 'gpt-3.5-turbo' );

		$this->assertTrue( $updated );

		wp_cache_flush();

		$value = $this->service->get( 'model' );

		$this->assertEquals( 'gpt-3.5-turbo', $value );
	}

	/**
	 * Test getAllSettings returns all settings.
	 */
	public function test_get_all_settings() {
		update_option(
			'seo_generator_settings',
			array(
				'model'       => 'gpt-4',
				'temperature' => 0.8,
			)
		);

		wp_cache_flush();

		$all_settings = $this->service->getAllSettings();

		$this->assertIsArray( $all_settings );
		$this->assertArrayHasKey( 'model', $all_settings );
		$this->assertArrayHasKey( 'temperature', $all_settings );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		delete_option( 'seo_generator_settings' );
		wp_cache_flush();
		parent::tearDown();
	}
}
