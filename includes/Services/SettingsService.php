<?php
/**
 * Settings Service
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Service for retrieving and managing plugin settings.
 */
class SettingsService {
	/**
	 * Settings option name.
	 */
	private const OPTION_NAME = 'seo_generator_settings';

	/**
	 * Cache duration in seconds (1 hour).
	 */
	private const CACHE_DURATION = 3600;

	/**
	 * Cache key for settings.
	 */
	private const CACHE_KEY = 'seo_gen_settings';

	/**
	 * Get all API settings.
	 *
	 * @return array Settings array.
	 */
	public function getApiSettings(): array {
		// Try to get from cache.
		$cached = wp_cache_get( self::CACHE_KEY );

		if ( false !== $cached ) {
			return $cached;
		}

		// Get from database.
		$settings = get_option( self::OPTION_NAME, $this->getDefaults() );

		// Cache the result.
		wp_cache_set( self::CACHE_KEY, $settings, '', self::CACHE_DURATION );

		return $settings;
	}

	/**
	 * Get decrypted API key.
	 *
	 * @return string Decrypted API key or empty string if not set.
	 */
	public function getApiKey(): string {
		$settings = $this->getApiSettings();

		if ( empty( $settings['openai_api_key'] ) ) {
			return '';
		}

		$decrypted = seo_generator_decrypt_api_key( $settings['openai_api_key'] );

		return $decrypted ?: '';
	}

	/**
	 * Get selected model.
	 *
	 * @return string Model name.
	 */
	public function getModel(): string {
		$settings = $this->getApiSettings();

		return $settings['model'] ?? 'gpt-4-turbo-preview';
	}

	/**
	 * Get temperature setting.
	 *
	 * @return float Temperature value.
	 */
	public function getTemperature(): float {
		$settings = $this->getApiSettings();

		return floatval( $settings['temperature'] ?? 0.7 );
	}

	/**
	 * Get max tokens setting.
	 *
	 * @return int Max tokens value.
	 */
	public function getMaxTokens(): int {
		$settings = $this->getApiSettings();

		return intval( $settings['max_tokens'] ?? 4096 );
	}

	/**
	 * Get monthly budget setting.
	 *
	 * @return float Monthly budget in USD.
	 */
	public function getMonthlyBudget(): float {
		$settings = $this->getApiSettings();

		return floatval( $settings['monthly_budget'] ?? 100.00 );
	}

	/**
	 * Get cost tracking enabled setting.
	 *
	 * @return bool Whether cost tracking is enabled.
	 */
	public function isCostTrackingEnabled(): bool {
		$settings = $this->getApiSettings();

		return (bool) ( $settings['enable_cost_tracking'] ?? true );
	}

	/**
	 * Get alert threshold percentage.
	 *
	 * @return int Alert threshold percentage (0-100).
	 */
	public function getAlertThresholdPercent(): int {
		$settings = $this->getApiSettings();

		return intval( $settings['alert_threshold_percent'] ?? 80 );
	}

	/**
	 * Clear settings cache.
	 *
	 * Should be called when settings are updated.
	 *
	 * @return void
	 */
	public function clearCache(): void {
		wp_cache_delete( self::CACHE_KEY );
	}

	/**
	 * Update settings.
	 *
	 * @param array $settings Settings to update.
	 * @return bool True if successful, false otherwise.
	 */
	public function updateSettings( array $settings ): bool {
		$updated = update_option( self::OPTION_NAME, $settings );

		if ( $updated ) {
			$this->clearCache();
		}

		return $updated;
	}

	/**
	 * Get default settings.
	 *
	 * @return array Default settings.
	 */
	private function getDefaults(): array {
		return array(
			'openai_api_key'           => '',
			'model'                    => 'gpt-4-turbo-preview',
			'temperature'              => 0.7,
			'max_tokens'               => 4096,
			'enable_cost_tracking'     => true,
			'monthly_budget'           => 100.00,
			'alert_threshold_percent'  => 80,
		);
	}

	/**
	 * Check if API is configured.
	 *
	 * @return bool True if API key is set, false otherwise.
	 */
	public function isApiConfigured(): bool {
		return ! empty( $this->getApiKey() );
	}

	/**
	 * Get all settings (including non-API settings).
	 *
	 * @return array All settings.
	 */
	public function getAllSettings(): array {
		return $this->getApiSettings();
	}

	/**
	 * Get a specific setting value.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value if setting not found.
	 * @return mixed Setting value.
	 */
	public function get( string $key, $default = null ) {
		$settings = $this->getApiSettings();

		return $settings[ $key ] ?? $default;
	}

	/**
	 * Set a specific setting value.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $value Setting value.
	 * @return bool True if successful, false otherwise.
	 */
	public function set( string $key, $value ): bool {
		$settings         = $this->getApiSettings();
		$settings[ $key ] = $value;

		return $this->updateSettings( $settings );
	}
}
