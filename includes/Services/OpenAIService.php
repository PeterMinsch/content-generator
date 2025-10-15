<?php
/**
 * OpenAI Service
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

use SEOGenerator\Models\GenerationResult;
use SEOGenerator\Exceptions\OpenAIException;
use SEOGenerator\Exceptions\RateLimitException;
use SEOGenerator\Exceptions\NetworkException;
use SEOGenerator\Exceptions\TimeoutException;
use SEOGenerator\Exceptions\InvalidResponseException;

defined( 'ABSPATH' ) || exit;

/**
 * Service for communicating with OpenAI API.
 */
class OpenAIService {
	/**
	 * OpenAI API endpoint.
	 */
	private const API_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

	/**
	 * Default model to use.
	 */
	private const DEFAULT_MODEL = 'gpt-4-turbo-preview';

	/**
	 * Fallback model.
	 */
	private const FALLBACK_MODEL = 'gpt-4';

	/**
	 * API request timeout in seconds.
	 */
	private const TIMEOUT = 60;

	/**
	 * API key for OpenAI.
	 *
	 * @var string|null
	 */
	private ?string $api_key = null;

	/**
	 * Settings service.
	 *
	 * @var SettingsService
	 */
	private SettingsService $settings_service;

	/**
	 * Constructor.
	 *
	 * @param SettingsService $settings_service Settings service instance.
	 */
	public function __construct( SettingsService $settings_service ) {
		$this->settings_service = $settings_service;
		$this->api_key          = $this->settings_service->getApiKey();
	}

	/**
	 * Generate content using OpenAI API.
	 *
	 * @param string $prompt The prompt to send to OpenAI.
	 * @param array  $options Optional parameters for generation.
	 * @return GenerationResult Generation result with content and metadata.
	 * @throws OpenAIException If API request fails.
	 * @throws RateLimitException If rate limit is exceeded.
	 */
	public function generateContent( string $prompt, array $options = array() ): GenerationResult {
		if ( empty( $this->api_key ) ) {
			throw new OpenAIException( 'OpenAI API key not configured. Please add your API key in plugin settings.' );
		}

		// Merge options with defaults from settings.
		$options = wp_parse_args(
			$options,
			array(
				'model'             => $this->settings_service->getModel(),
				'temperature'       => $this->settings_service->getTemperature(),
				'max_tokens'        => $this->settings_service->getMaxTokens(),
				'top_p'             => 1,
				'frequency_penalty' => 0.3,
				'presence_penalty'  => 0.3,
				'system_message'    => 'You are a helpful assistant that generates SEO-optimized content for jewelry e-commerce.',
			)
		);

		// Build request body.
		$request_body = array(
			'model'             => $options['model'],
			'temperature'       => $options['temperature'],
			'max_tokens'        => $options['max_tokens'],
			'top_p'             => $options['top_p'],
			'frequency_penalty' => $options['frequency_penalty'],
			'presence_penalty'  => $options['presence_penalty'],
			'messages'          => array(
				array(
					'role'    => 'system',
					'content' => $options['system_message'],
				),
				array(
					'role'    => 'user',
					'content' => $prompt,
				),
			),
		);

		// Make API request with retry logic.
		$response = $this->makeRequest( $request_body );

		// Parse response and return result.
		return $this->parseResponse( $response );
	}

	/**
	 * Make API request to OpenAI.
	 *
	 * @param array $request_body Request body.
	 * @param int   $retry_count Current retry attempt.
	 * @return array Decoded response data.
	 * @throws OpenAIException If API request fails.
	 * @throws RateLimitException If rate limit is exceeded.
	 */
	private function makeRequest( array $request_body, int $retry_count = 0 ): array {
		$response = wp_remote_post(
			self::API_ENDPOINT,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $request_body ),
				'timeout' => self::TIMEOUT,
			)
		);

		// Handle WordPress HTTP errors.
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();

			// Check for timeout errors.
			if ( strpos( $error_message, 'timed out' ) !== false || strpos( $error_message, 'timeout' ) !== false ) {
				error_log( sprintf( '[SEO Generator] Timeout error: %s', $error_message ) );

				// Retry once on timeout.
				if ( $retry_count < 1 ) {
					sleep( 5 );
					return $this->makeRequest( $request_body, $retry_count + 1 );
				}

				throw new TimeoutException( 'Generation timed out. Please try again.' );
			}

			// Check for network errors.
			if ( strpos( $error_message, 'cURL error' ) !== false || strpos( $error_message, 'connection' ) !== false ) {
				error_log( sprintf( '[SEO Generator] Network error: %s', $error_message ) );

				// Retry up to 2 times with exponential backoff.
				if ( $retry_count < 2 ) {
					$wait_time = pow( 2, $retry_count + 1 ); // 2, 4 seconds.
					sleep( $wait_time );
					return $this->makeRequest( $request_body, $retry_count + 1 );
				}

				throw new NetworkException( 'Unable to connect to OpenAI. Check your internet connection.' );
			}

			// General error.
			error_log( sprintf( '[SEO Generator] API request failed: %s', $error_message ) );
			throw new OpenAIException( sprintf( 'API request failed: %s', $error_message ) );
		}

		$status_code   = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Handle HTTP errors.
		if ( $status_code >= 400 ) {
			return $this->handleHttpError( $status_code, $response_body, $request_body, $retry_count );
		}

		// Parse JSON response.
		$data = json_decode( $response_body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			error_log(
				sprintf(
					'[SEO Generator] Failed to parse OpenAI response - %s. Response: %s',
					json_last_error_msg(),
					substr( $response_body, 0, 200 )
				)
			);

			throw new InvalidResponseException( 'Failed to parse AI response. Please try regenerating.' );
		}

		return $data;
	}

	/**
	 * Handle HTTP error responses.
	 *
	 * @param int    $status_code HTTP status code.
	 * @param string $response_body Response body.
	 * @param array  $request_body Original request body.
	 * @param int    $retry_count Current retry attempt.
	 * @return array Response data (if retry succeeds).
	 * @throws OpenAIException If API request fails.
	 * @throws RateLimitException If rate limit is exceeded.
	 */
	private function handleHttpError( int $status_code, string $response_body, array $request_body, int $retry_count ): array {
		// Parse error response.
		$error_data = json_decode( $response_body, true );
		$error_message = $error_data['error']['message'] ?? 'Unknown error';

		// Log error with context.
		error_log(
			sprintf(
				'SEO Generator: OpenAI API error - Status: %d, Message: %s, Response: %s',
				$status_code,
				$error_message,
				$response_body
			)
		);

		// Handle specific error codes.
		switch ( $status_code ) {
			case 401:
				throw new OpenAIException(
					'Invalid API key. Please check your OpenAI API key in plugin settings.',
					401,
					null,
					401,
					$response_body
				);

			case 429:
				// Extract retry-after header if available.
				$retry_after = null;
				if ( isset( $error_data['error']['retry_after'] ) ) {
					$retry_after = (int) $error_data['error']['retry_after'];
				}

				throw new RateLimitException(
					'OpenAI API rate limit exceeded. Please try again later.',
					429,
					null,
					$retry_after
				);

			case 500:
			case 502:
			case 503:
			case 504:
				// Retry once on server errors.
				if ( $retry_count < 1 ) {
					sleep( 2 ); // Wait 2 seconds before retry.
					return $this->makeRequest( $request_body, $retry_count + 1 );
				}

				throw new OpenAIException(
					'OpenAI API server error. Please try again later.',
					$status_code,
					null,
					$status_code,
					$response_body
				);

			default:
				throw new OpenAIException(
					sprintf( 'API request failed: %s', $error_message ),
					$status_code,
					null,
					$status_code,
					$response_body
				);
		}
	}

	/**
	 * Generate alt text for an image using AI.
	 *
	 * @param array $metadata Image metadata including filename, folder, tags, and context.
	 * @return string Generated alt text.
	 * @throws OpenAIException If API request fails.
	 */
	public function generateAltText( array $metadata ): string {
		if ( empty( $this->api_key ) ) {
			throw new OpenAIException( 'OpenAI API key not configured. Please add your API key in plugin settings.' );
		}

		// Build prompt from metadata.
		$prompt = $this->buildAltTextPrompt( $metadata );

		// Use gpt-4o-mini for cost efficiency.
		$model = $metadata['model'] ?? 'gpt-4o-mini';

		$options = array(
			'model'          => $model,
			'temperature'    => 0.7,
			'max_tokens'     => 50,
			'system_message' => 'You are an expert at writing SEO-friendly, descriptive alt text for images. Keep responses concise and under 125 characters.',
		);

		$result = $this->generateContent( $prompt, $options );

		// Clean and validate alt text.
		$alt_text = trim( $result->getContent() );
		$alt_text = str_replace( array( '"', "'" ), '', $alt_text ); // Remove quotes.

		// Enforce 125 character limit.
		if ( strlen( $alt_text ) > 125 ) {
			$alt_text = substr( $alt_text, 0, 125 );
			$alt_text = substr( $alt_text, 0, strrpos( $alt_text, ' ' ) ); // Cut at last space.
		}

		return $alt_text;
	}

	/**
	 * Build alt text prompt from image metadata.
	 *
	 * @param array $metadata Image metadata.
	 * @return string Prompt for AI.
	 */
	private function buildAltTextPrompt( array $metadata ): string {
		$filename     = $metadata['filename'] ?? '';
		$folder_name  = $metadata['folder_name'] ?? '';
		$tags         = $metadata['tags'] ?? array();
		$focus_keyword = $metadata['focus_keyword'] ?? '';
		$page_title   = $metadata['page_title'] ?? '';

		$prompt = "Generate a concise, SEO-friendly alt text (max 125 characters) for an image with:\n\n";

		if ( ! empty( $filename ) ) {
			$prompt .= "File name: {$filename}\n";
		}

		if ( ! empty( $folder_name ) ) {
			$prompt .= "Folder: {$folder_name}\n";
		}

		if ( ! empty( $tags ) ) {
			$prompt .= 'Image tags: ' . implode( ', ', $tags ) . "\n";
		}

		if ( ! empty( $focus_keyword ) ) {
			$prompt .= "Focus keyword: {$focus_keyword}\n";
		}

		if ( ! empty( $page_title ) ) {
			$prompt .= "Context: {$page_title}\n";
		}

		$prompt .= "\nRequirements:\n";
		$prompt .= "- Describe the image naturally\n";
		$prompt .= "- Include the focus keyword if provided\n";
		$prompt .= "- Reference visual elements suggested by filename/folder\n";
		$prompt .= "- Stay under 125 characters\n";
		$prompt .= "- Use plain language, no quotes or special formatting\n\n";
		$prompt .= "Output only the alt text, nothing else.";

		return $prompt;
	}

	/**
	 * Parse API response and extract generation result.
	 *
	 * @param array $data Decoded API response.
	 * @return GenerationResult Generation result object.
	 * @throws InvalidResponseException If response is invalid.
	 */
	private function parseResponse( array $data ): GenerationResult {
		// Validate response structure.
		if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
			error_log( '[SEO Generator] Invalid API response: missing content field' );
			throw new InvalidResponseException( 'Invalid response format from OpenAI. Please try again.' );
		}

		if ( ! isset( $data['usage'] ) ) {
			error_log( '[SEO Generator] Invalid API response: missing usage data' );
			throw new InvalidResponseException( 'Invalid response format from OpenAI. Please try again.' );
		}

		$content = $data['choices'][0]['message']['content'];
		$usage   = $data['usage'];
		$model   = $data['model'] ?? 'unknown';

		return new GenerationResult(
			$content,
			$usage['prompt_tokens'] ?? 0,
			$usage['completion_tokens'] ?? 0,
			$usage['total_tokens'] ?? 0,
			$model
		);
	}

}
