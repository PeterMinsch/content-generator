<?php
/**
 * DALL-E Service
 *
 * Handles DALL-E 3 API integration for generating jewelry images.
 * This is Stage 2 of the two-stage AI image generation system.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

/**
 * Class DalleService
 *
 * Generates images using DALL-E 3 API.
 */
class DalleService {

	/**
	 * OpenAI API key
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * OpenAI Images API endpoint
	 *
	 * @var string
	 */
	private $api_endpoint = 'https://api.openai.com/v1/images/generations';

	/**
	 * Image download service
	 *
	 * @var ImageDownloadService
	 */
	private $download_service;

	/**
	 * Settings service
	 *
	 * @var SettingsService
	 */
	private $settings_service;

	/**
	 * Constructor
	 *
	 * @param SettingsService|null $settings_service Optional settings service (auto-creates if not provided).
	 */
	public function __construct( ?SettingsService $settings_service = null ) {
		$this->settings_service = $settings_service ?? new SettingsService();
		$this->api_key          = $this->settings_service->getApiKey();
		$this->download_service = new ImageDownloadService();
	}

	/**
	 * Generate an image using DALL-E 3
	 *
	 * @param string $prompt       The DALL-E prompt.
	 * @param string $filename     Desired filename (without extension).
	 * @param int    $post_id      WordPress post ID to attach image to.
	 * @return int|false Attachment ID on success, false on failure
	 * @throws \Exception If API call fails.
	 */
	public function generateImage( string $prompt, string $filename, int $post_id = 0 ) {
		if ( empty( $this->api_key ) ) {
			throw new \Exception( 'OpenAI API key not configured.' );
		}

		$image_url = $this->callDalleAPI( $prompt );

		if ( ! $image_url ) {
			return false;
		}

		// Download and save to WordPress Media Library
		$attachment_id = $this->download_service->downloadAndAttach( $image_url, $post_id, $filename );

		if ( is_wp_error( $attachment_id ) ) {
			error_log( '[DalleService] Failed to download generated image: ' . $attachment_id->get_error_message() );
			return false;
		}

		// Log success
		error_log( sprintf(
			'[DalleService] Successfully generated and saved image (attachment_id: %d) for prompt: %s',
			$attachment_id,
			substr( $prompt, 0, 100 ) . '...'
		) );

		return $attachment_id;
	}

	/**
	 * Call DALL-E API to generate image
	 *
	 * @param string $prompt The image prompt.
	 * @return string|false Image URL on success, false on failure
	 * @throws \Exception If API call fails.
	 */
	private function callDalleAPI( string $prompt ) {
		$body = array(
			'model'   => 'dall-e-3',
			'prompt'  => $prompt,
			'n'       => 1,
			'size'    => '1024x1024', // DALL-E 3 supports: 1024x1024, 1024x1792, 1792x1024
			'quality' => 'standard',  // 'standard' or 'hd' (hd costs 2x)
		);

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
			'timeout' => 60, // DALL-E can take longer than regular API calls
		);

		error_log( '[DalleService] Calling DALL-E API with prompt: ' . substr( $prompt, 0, 100 ) . '...' );

		$response = wp_remote_post( $this->api_endpoint, $args );

		if ( is_wp_error( $response ) ) {
			error_log( '[DalleService] API error: ' . $response->get_error_message() );
			throw new \Exception( 'Failed to generate image: ' . $response->get_error_message() );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 200 !== $response_code ) {
			error_log( '[DalleService] API returned code ' . $response_code . ': ' . $response_body );

			// Parse error message if available
			$error_data = json_decode( $response_body, true );
			$error_msg  = isset( $error_data['error']['message'] ) ? $error_data['error']['message'] : 'Unknown error';

			throw new \Exception( 'DALL-E API error: ' . $error_msg . ' (code ' . $response_code . ')' );
		}

		$data = json_decode( $response_body, true );

		if ( ! isset( $data['data'][0]['url'] ) ) {
			error_log( '[DalleService] Invalid API response: ' . $response_body );
			throw new \Exception( 'Invalid response from DALL-E API' );
		}

		$image_url = $data['data'][0]['url'];

		error_log( '[DalleService] Successfully generated image: ' . $image_url );

		return $image_url;
	}

	/**
	 * Get the cost of a DALL-E 3 generation
	 *
	 * @param string $size    Image size (1024x1024, 1024x1792, 1792x1024).
	 * @param string $quality Quality level (standard, hd).
	 * @return float Cost in USD
	 */
	public static function getCost( string $size = '1024x1024', string $quality = 'standard' ): float {
		// DALL-E 3 pricing (as of 2024)
		if ( 'hd' === $quality ) {
			return ( '1024x1024' === $size ) ? 0.080 : 0.120;
		}

		// Standard quality
		return ( '1024x1024' === $size ) ? 0.040 : 0.080;
	}
}
