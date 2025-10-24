<?php
/**
 * Prompt Generator Service
 *
 * Uses GPT-4 to generate optimized DALL-E prompts for jewelry images.
 * This is Stage 1 of the two-stage AI image generation system.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

/**
 * Class PromptGeneratorService
 *
 * Generates optimized image prompts using GPT-4 based on page context.
 */
class PromptGeneratorService {

	/**
	 * OpenAI API key
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * OpenAI API endpoint
	 *
	 * @var string
	 */
	private $api_endpoint = 'https://api.openai.com/v1/chat/completions';

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
	}

	/**
	 * Generate an optimized DALL-E prompt for a related link
	 *
	 * @param string $page_title     The main page title (context).
	 * @param string $link_title     The related link title.
	 * @param string $link_description Description of the link.
	 * @param string $link_category  Category tag (e.g., "Rings", "Bands").
	 * @return string Generated DALL-E prompt
	 * @throws \Exception If API call fails.
	 */
	public function generatePrompt( string $page_title, string $link_title, string $link_description, string $link_category ): string {
		if ( empty( $this->api_key ) ) {
			throw new \Exception( 'OpenAI API key not configured.' );
		}

		$system_prompt = $this->getSystemPrompt();
		$user_prompt   = $this->buildUserPrompt( $page_title, $link_title, $link_description, $link_category );

		$response = $this->callOpenAI( $system_prompt, $user_prompt );

		// Log for debugging
		error_log( sprintf(
			'[PromptGenerator] Generated prompt for "%s": %s',
			$link_title,
			substr( $response, 0, 100 ) . '...'
		) );

		return $response;
	}

	/**
	 * Get the system prompt for GPT-4
	 *
	 * @return string
	 */
	private function getSystemPrompt(): string {
		return "You are an expert at crafting DALL-E prompts for luxury jewelry photography. Your prompts should:

1. Create elegant, high-end product photography
2. Use professional jewelry photography lighting and composition
3. Focus on the jewelry as the main subject
4. Include relevant context (display, background, styling)
5. Maintain brand consistency with luxury aesthetics
6. Be concise but detailed (50-100 words)
7. Specify photography style, lighting, and composition

Output ONLY the DALL-E prompt text. Do not include explanations or metadata.";
	}

	/**
	 * Build the user prompt with context
	 *
	 * @param string $page_title       Main page title.
	 * @param string $link_title       Related link title.
	 * @param string $link_description Link description.
	 * @param string $link_category    Category tag.
	 * @return string
	 */
	private function buildUserPrompt( string $page_title, string $link_title, string $link_description, string $link_category ): string {
		return "Generate a DALL-E prompt for a luxury jewelry product image.

Context:
- Main page: {$page_title}
- Product category: {$link_title}
- Description: {$link_description}
- Category tag: {$link_category}

Requirements:
- Professional jewelry photography style
- Clean, elegant composition
- Focus on the jewelry product
- Luxury brand aesthetic
- Suitable for e-commerce category page

Generate the DALL-E prompt:";
	}

	/**
	 * Call OpenAI API
	 *
	 * @param string $system_prompt System message.
	 * @param string $user_prompt   User message.
	 * @return string Generated prompt
	 * @throws \Exception If API call fails.
	 */
	private function callOpenAI( string $system_prompt, string $user_prompt ): string {
		$body = array(
			'model'       => 'gpt-4',
			'messages'    => array(
				array(
					'role'    => 'system',
					'content' => $system_prompt,
				),
				array(
					'role'    => 'user',
					'content' => $user_prompt,
				),
			),
			'temperature' => 0.7,
			'max_tokens'  => 200,
		);

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
			'timeout' => 30,
		);

		$response = wp_remote_post( $this->api_endpoint, $args );

		if ( is_wp_error( $response ) ) {
			error_log( '[PromptGenerator] API error: ' . $response->get_error_message() );
			throw new \Exception( 'Failed to generate prompt: ' . $response->get_error_message() );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 200 !== $response_code ) {
			error_log( '[PromptGenerator] API returned code ' . $response_code . ': ' . $response_body );
			throw new \Exception( 'OpenAI API error (code ' . $response_code . ')' );
		}

		$data = json_decode( $response_body, true );

		if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
			error_log( '[PromptGenerator] Invalid API response: ' . $response_body );
			throw new \Exception( 'Invalid response from OpenAI API' );
		}

		$generated_prompt = trim( $data['choices'][0]['message']['content'] );

		return $generated_prompt;
	}
}
