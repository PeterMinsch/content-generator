<?php
/**
 * Slot Content Generator Service
 *
 * Generates AI content for content slots of Next.js widget blocks.
 * Batches blocks into groups to minimize API calls.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

class SlotContentGenerator {

	/**
	 * Maximum blocks per API call batch.
	 */
	private const BATCH_SIZE = 10;

	/**
	 * @var OpenAIService
	 */
	private OpenAIService $openai;

	/**
	 * @var NextJSPageGenerator
	 */
	private NextJSPageGenerator $page_generator;

	/**
	 * @var BlockRuleService|null
	 */
	private ?BlockRuleService $rule_service;

	/**
	 * Prompt templates loaded from config.
	 *
	 * @var array
	 */
	private array $prompts;

	/**
	 * Constructor.
	 *
	 * @param OpenAIService           $openai         OpenAI service instance.
	 * @param NextJSPageGenerator     $page_generator Page generator for slot schemas.
	 * @param BlockRuleService|null   $rule_service   Block rule service (optional for backward compat).
	 */
	public function __construct( OpenAIService $openai, NextJSPageGenerator $page_generator, ?BlockRuleService $rule_service = null ) {
		$this->openai         = $openai;
		$this->page_generator = $page_generator;
		$this->rule_service   = $rule_service;
		$this->prompts        = require SEO_GENERATOR_PLUGIN_DIR . 'config/slot-prompt-template.php';
	}

	/**
	 * Generate slot content for all blocks on a page.
	 *
	 * @param array    $block_order Block IDs on this page.
	 * @param array    $context     Generation context:
	 *                              - focus_keyword (required)
	 *                              - page_title (optional, derived from keyword if empty)
	 *                              - business_name, business_description, service_area (from settings)
	 * @param int|null $template_id Template ID for block rule overrides (optional).
	 * @return array { block_id => { slot_name => generated_value } }
	 */
	public function generateForPage( array $block_order, array $context, ?int $template_id = null ): array {
		// Build context with business settings.
		$context = $this->buildContext( $context );

		// Collect blocks that have content slots + image specs.
		$blocks_with_slots = [];
		$block_images_map  = [];
		foreach ( $block_order as $block_id ) {
			// Use rule service when available, else fallback to config.
			if ( $this->rule_service ) {
				$schema = $this->rule_service->getResolvedSlotSchema( $block_id, $template_id );
				$images = $this->rule_service->getResolvedImageSpecs( $block_id, $template_id );
			} else {
				$schema = $this->page_generator->getSlotSchema( $block_id );
				$images = $this->page_generator->getBlockImages( $block_id );
			}

			if ( ! empty( $schema ) ) {
				$blocks_with_slots[ $block_id ] = $schema;
				if ( ! empty( $images ) ) {
					$block_images_map[ $block_id ] = $images;
				}
			}
		}

		if ( empty( $blocks_with_slots ) ) {
			return [];
		}

		// Split into batches.
		$batches     = array_chunk( $blocks_with_slots, self::BATCH_SIZE, true );
		$all_content = [];

		foreach ( $batches as $batch ) {
			$batch_result = $this->generateBatch( $batch, $context, $block_images_map );
			$all_content  = array_merge( $all_content, $batch_result );
		}

		// Post-generation auto-fix via ValidationService when rule service is available.
		if ( $this->rule_service && ! empty( $all_content ) ) {
			$validation_service = new ValidationService( $this->rule_service );
			$fix_result   = $validation_service->autoFix( $all_content, $block_order, $template_id );
			$all_content  = $fix_result['fixed'];
		}

		return $all_content;
	}

	/**
	 * Generate SEO metadata (title + description) for a page.
	 *
	 * @param array $context Generation context with focus_keyword.
	 * @return array|null { title: string, description: string } or null on failure.
	 */
	public function generateMetadata( array $context ): ?array {
		$context = $this->buildContext( $context );

		$prompt = $this->substituteVariables( $this->prompts['metadata'], $context );

		try {
			$result  = $this->openai->generateContent( $prompt, [
				'system_message' => $this->substituteVariables( $this->prompts['system'], $context ),
				'temperature'    => 0.7,
				'max_tokens'     => 200,
			] );
			$content = $result->getContent();
			$parsed  = $this->parseJson( $content );

			if ( isset( $parsed['title'], $parsed['description'] ) ) {
				return [
					'title'       => substr( $parsed['title'], 0, 70 ),
					'description' => substr( $parsed['description'], 0, 170 ),
				];
			}
		} catch ( \Exception $e ) {
			error_log( '[SEO Generator] Metadata generation failed: ' . $e->getMessage() );
		}

		return null;
	}

	/**
	 * Generate content for a batch of blocks.
	 *
	 * @param array $batch        { block_id => slot_schema }
	 * @param array $context      Generation context.
	 * @param array $block_images { block_id => images_array } Image specs per block.
	 * @return array { block_id => { slot_name => value } }
	 */
	private function generateBatch( array $batch, array $context, array $block_images = [] ): array {
		// Build the blocks schema for the prompt.
		$schema_for_prompt = [];
		foreach ( $batch as $block_id => $slots ) {
			$slot_details = [];
			foreach ( $slots as $slot_name => $slot_def ) {
				$slot_details[ $slot_name ] = [
					'type'              => $slot_def['type'] ?? 'text',
					'max_length'        => $slot_def['max_length'] ?? 100,
					'mobile_max_length' => $slot_def['mobile_max_length'] ?? ( $slot_def['max_length'] ?? 100 ),
					'hint'              => $slot_def['ai_hint'] ?? '',
				];
				if ( ! empty( $slot_def['mobile_hidden'] ) ) {
					$slot_details[ $slot_name ]['mobile_hidden'] = true;
				}
				// Include rule-service fields when available.
				if ( isset( $slot_def['required'] ) ) {
					$slot_details[ $slot_name ]['required'] = $slot_def['required'];
				}
				if ( isset( $slot_def['over_limit_action'] ) ) {
					$slot_details[ $slot_name ]['over_limit_action'] = $slot_def['over_limit_action'];
				}
				$validation = $slot_def['validation'] ?? [];
				if ( ! empty( $validation['min_length'] ) ) {
					$slot_details[ $slot_name ]['min_length'] = $validation['min_length'];
				}
			}
			$schema_for_prompt[ $block_id ] = $slot_details;

			// Append image specs if available for this block.
			if ( ! empty( $block_images[ $block_id ] ) ) {
				$schema_for_prompt[ $block_id ]['_image_specs'] = $block_images[ $block_id ];
			}
		}

		$blocks_json = wp_json_encode( $schema_for_prompt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		// Build the user prompt.
		$context['blocks_json_schema'] = $blocks_json;
		$user_prompt   = $this->substituteVariables( $this->prompts['user'], $context );
		$system_prompt = $this->substituteVariables( $this->prompts['system'], $context );

		try {
			$result  = $this->openai->generateContent( $user_prompt, [
				'system_message' => $system_prompt,
				'temperature'    => 0.7,
				'max_tokens'     => 2000,
			] );
			$content = $result->getContent();
			$parsed  = $this->parseJson( $content );

			if ( ! is_array( $parsed ) ) {
				error_log( '[SEO Generator] Slot generation returned non-array' );
				return [];
			}

			// Validate and enforce max_length constraints (respecting over_limit_action).
			$validated = [];
			foreach ( $batch as $block_id => $slots ) {
				if ( ! isset( $parsed[ $block_id ] ) || ! is_array( $parsed[ $block_id ] ) ) {
					continue;
				}
				$validated[ $block_id ] = [];
				foreach ( $slots as $slot_name => $slot_def ) {
					if ( isset( $parsed[ $block_id ][ $slot_name ] ) ) {
						$value  = (string) $parsed[ $block_id ][ $slot_name ];
						$max    = $slot_def['max_length'] ?? 500;
						$action = $slot_def['over_limit_action'] ?? 'truncate';

						// Only truncate here if action is 'truncate'; other actions are handled by ValidationService.
						if ( $action === 'truncate' && mb_strlen( $value ) > $max ) {
							$value = mb_substr( $value, 0, $max );
						}
						$validated[ $block_id ][ $slot_name ] = $value;
					}
				}
			}

			return $validated;
		} catch ( \Exception $e ) {
			error_log( '[SEO Generator] Slot batch generation failed: ' . $e->getMessage() );
			return [];
		}
	}

	/**
	 * Build full context by merging in business settings.
	 *
	 * @param array $context Incoming context (must include focus_keyword).
	 * @return array Merged context.
	 */
	private function buildContext( array $context ): array {
		$settings        = get_option( 'seo_generator_settings', [] );
		$business_fields = [
			'business_name', 'business_type', 'business_description',
			'business_address', 'service_area', 'business_phone',
			'business_email', 'business_url', 'years_in_business',
		];

		$defaults = [];
		foreach ( $business_fields as $field ) {
			$defaults[ $field ] = $settings[ $field ] ?? '';
		}

		// Derive page_title from keyword if not provided.
		if ( empty( $context['page_title'] ) && ! empty( $context['focus_keyword'] ) ) {
			$context['page_title'] = ucwords( $context['focus_keyword'] );
		}

		return array_merge( $defaults, $context );
	}

	/**
	 * Substitute {variable} placeholders in a template string.
	 *
	 * @param string $template Template with {placeholder} markers.
	 * @param array  $context  Key-value replacements.
	 * @return string Rendered template.
	 */
	private function substituteVariables( string $template, array $context ): string {
		$replacements = [];
		foreach ( $context as $key => $value ) {
			if ( is_string( $value ) || is_numeric( $value ) ) {
				$replacements[ '{' . $key . '}' ] = (string) $value;
			}
		}
		return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
	}

	/**
	 * Parse JSON from AI response, handling markdown code fences.
	 *
	 * @param string $content Raw AI response.
	 * @return array|null Parsed array or null on failure.
	 */
	private function parseJson( string $content ): ?array {
		// Strip markdown code fences if present.
		$content = trim( $content );
		if ( preg_match( '/```(?:json)?\s*([\s\S]*?)```/', $content, $matches ) ) {
			$content = trim( $matches[1] );
		}

		$data = json_decode( $content, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			error_log( '[SEO Generator] JSON parse error: ' . json_last_error_msg() . ' — Content: ' . substr( $content, 0, 200 ) );
			return null;
		}

		return $data;
	}
}
