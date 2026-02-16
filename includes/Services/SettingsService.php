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
	 * Get business name.
	 *
	 * @return string Business name.
	 */
	public function getBusinessName(): string {
		return (string) $this->get( 'business_name', '' );
	}

	/**
	 * Get business type.
	 *
	 * @return string Business type.
	 */
	public function getBusinessType(): string {
		return (string) $this->get( 'business_type', '' );
	}

	/**
	 * Get business description.
	 *
	 * @return string Business description.
	 */
	public function getBusinessDescription(): string {
		return (string) $this->get( 'business_description', '' );
	}

	/**
	 * Get business address.
	 *
	 * @return string Business address.
	 */
	public function getBusinessAddress(): string {
		return (string) $this->get( 'business_address', '' );
	}

	/**
	 * Get service area.
	 *
	 * @return string Service area.
	 */
	public function getServiceArea(): string {
		return (string) $this->get( 'service_area', '' );
	}

	/**
	 * Get business phone.
	 *
	 * @return string Business phone.
	 */
	public function getBusinessPhone(): string {
		return (string) $this->get( 'business_phone', '' );
	}

	/**
	 * Get business email.
	 *
	 * @return string Business email.
	 */
	public function getBusinessEmail(): string {
		return (string) $this->get( 'business_email', '' );
	}

	/**
	 * Get business URL.
	 *
	 * @return string Business URL.
	 */
	public function getBusinessUrl(): string {
		return (string) $this->get( 'business_url', '' );
	}

	/**
	 * Get years in business.
	 *
	 * @return string Years in business.
	 */
	public function getYearsInBusiness(): string {
		return (string) $this->get( 'years_in_business', '' );
	}

	/**
	 * Get unique selling points as array.
	 *
	 * @return array USPs split by newline.
	 */
	public function getUsps(): array {
		$raw = (string) $this->get( 'usps', '' );
		if ( empty( $raw ) ) {
			return array();
		}
		return array_filter( array_map( 'trim', explode( "\n", $raw ) ) );
	}

	/**
	 * Get certifications as array.
	 *
	 * @return array Certifications split by newline.
	 */
	public function getCertifications(): array {
		$raw = (string) $this->get( 'certifications', '' );
		if ( empty( $raw ) ) {
			return array();
		}
		return array_filter( array_map( 'trim', explode( "\n", $raw ) ) );
	}

	/**
	 * Get all business context as a flat array for template substitution.
	 *
	 * @return array Business context values.
	 */
	public function getBusinessContext(): array {
		return array(
			'business_name'        => $this->getBusinessName(),
			'business_type'        => $this->getBusinessType(),
			'business_description' => $this->getBusinessDescription(),
			'business_address'     => $this->getBusinessAddress(),
			'service_area'         => $this->getServiceArea(),
			'business_phone'       => $this->getBusinessPhone(),
			'business_email'       => $this->getBusinessEmail(),
			'business_url'         => $this->getBusinessUrl(),
			'years_in_business'    => $this->getYearsInBusiness(),
			'usps'                 => implode( ', ', $this->getUsps() ),
			'certifications'       => implode( ', ', $this->getCertifications() ),
		);
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
			'business_name'            => '',
			'business_type'            => '',
			'business_description'     => '',
			'years_in_business'        => '',
			'usps'                     => '',
			'certifications'           => '',
			'business_address'         => '',
			'service_area'             => '',
			'business_phone'           => '',
			'business_email'           => '',
			'business_url'             => '',
			'default_cta_heading'      => '',
			'default_cta_text'         => '',
			'default_cta_button_label' => '',
			'default_cta_button_url'   => '',
			'default_warranty_text'    => '',
			'default_care_text'        => '',
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
