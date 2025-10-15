<?php
/**
 * Content Generation Service
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

use SEOGenerator\Models\GenerationResult;
use SEOGenerator\Models\BulkGenerationResult;
use SEOGenerator\Exceptions\OpenAIException;
use SEOGenerator\Exceptions\RateLimitException;
use SEOGenerator\Exceptions\BudgetExceededException;

defined( 'ABSPATH' ) || exit;

/**
 * Orchestrates content generation for individual blocks.
 */
class ContentGenerationService {

	/**
	 * Block processing order for bulk generation.
	 */
	private const BLOCK_ORDER = array(
		'seo_metadata',
		'hero',
		'serp_answer',
		'product_criteria',
		'materials',
		'process',
		'comparison',
		'product_showcase',
		'size_fit',
		'care_warranty',
		'ethics',
		'faqs',
		'cta',
	);

	/**
	 * Maximum concurrent bulk generations per user.
	 */
	private const MAX_CONCURRENT_BULK = 3;

	/**
	 * OpenAI service instance.
	 *
	 * @var OpenAIService
	 */
	private OpenAIService $openai_service;

	/**
	 * Prompt template engine instance.
	 *
	 * @var PromptTemplateEngine
	 */
	private PromptTemplateEngine $prompt_engine;

	/**
	 * Block content parser instance.
	 *
	 * @var BlockContentParser
	 */
	private BlockContentParser $content_parser;

	/**
	 * Cost tracking service instance.
	 *
	 * @var CostTrackingService
	 */
	private CostTrackingService $cost_tracking;

	/**
	 * Image matching service instance.
	 *
	 * @var ImageMatchingService
	 */
	private ImageMatchingService $image_matching;

	/**
	 * Constructor.
	 *
	 * @param OpenAIService         $openai_service OpenAI service.
	 * @param PromptTemplateEngine  $prompt_engine Prompt template engine.
	 * @param BlockContentParser    $content_parser Content parser.
	 * @param CostTrackingService   $cost_tracking Cost tracking service.
	 * @param ImageMatchingService  $image_matching Image matching service.
	 */
	public function __construct(
		OpenAIService $openai_service,
		PromptTemplateEngine $prompt_engine,
		BlockContentParser $content_parser,
		CostTrackingService $cost_tracking,
		ImageMatchingService $image_matching
	) {
		$this->openai_service  = $openai_service;
		$this->prompt_engine   = $prompt_engine;
		$this->content_parser  = $content_parser;
		$this->cost_tracking   = $cost_tracking;
		$this->image_matching  = $image_matching;
	}

	/**
	 * Generate content for a single block.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $block_type Block type to generate.
	 * @param array  $additional_context Additional context data.
	 * @return array Generation result with content and metadata.
	 * @throws \Exception If generation fails.
	 */
	public function generateSingleBlock( int $post_id, string $block_type, array $additional_context = array() ): array {
		// Validate post exists and is correct type.
		$post = get_post( $post_id );

		if ( ! $post || 'seo-page' !== $post->post_type ) {
			throw new \Exception( 'Invalid post ID or post type. Must be seo-page.' );
		}

		// Check budget limit before generation.
		$this->cost_tracking->checkBudgetLimit();

		// Build full context.
		$context = $this->prompt_engine->buildContext( $post_id, $additional_context );

		// Render prompt.
		$prompt = $this->prompt_engine->renderPrompt( $block_type, $context );

		// Record start time.
		$start_time = microtime( true );

		try {
			// Generate content.
			$result = $this->openai_service->generateContent(
				$prompt['user'],
				array(
					'system_message' => $prompt['system'],
				)
			);

			// Calculate generation time.
			$generation_time = round( microtime( true ) - $start_time, 2 );

			// Parse generated content.
			$parsed_content = $this->content_parser->parse( $block_type, $result->getContent() );

			// Update ACF fields.
			$this->updateACFFields( $post_id, $block_type, $parsed_content );

			// Auto-assign images if enabled (adds image IDs to $parsed_content by reference).
			$assigned_images = $this->autoAssignImages( $post_id, $block_type, $context, $parsed_content );

			// Add assigned image IDs to parsed_content for frontend preview
			if ( ! empty( $assigned_images ) ) {
				$parsed_content = array_merge( $parsed_content, $assigned_images );
			}

			// Update Block Editor content for hero block
			if ( 'hero' === $block_type ) {
				$this->updateBlockEditorContent( $post_id, $parsed_content, $assigned_images );
			}

			// Calculate cost.
			$cost = $this->cost_tracking->calculateCost(
				$result->getPromptTokens(),
				$result->getCompletionTokens(),
				$result->getModel()
			);

			// Log successful generation.
			$this->cost_tracking->logGeneration(
				array(
					'post_id'           => $post_id,
					'block_type'        => $block_type,
					'prompt_tokens'     => $result->getPromptTokens(),
					'completion_tokens' => $result->getCompletionTokens(),
					'total_tokens'      => $result->getTotalTokens(),
					'cost'              => $cost,
					'model'             => $result->getModel(),
					'status'            => 'success',
					'error_message'     => null,
					'user_id'           => get_current_user_id(),
				)
			);

			// Return response.
			return array(
				'success' => true,
				'content' => $parsed_content,
				'metadata' => array(
					'promptTokens'     => $result->getPromptTokens(),
					'completionTokens' => $result->getCompletionTokens(),
					'totalTokens'      => $result->getTotalTokens(),
					'cost'             => $cost,
					'generationTime'   => $generation_time,
					'model'            => $result->getModel(),
					'timestamp'        => current_time( 'mysql' ),
				),
			);

		} catch ( \Exception $e ) {
			// Log failed generation.
			$this->cost_tracking->logGeneration(
				array(
					'post_id'           => $post_id,
					'block_type'        => $block_type,
					'prompt_tokens'     => 0,
					'completion_tokens' => 0,
					'total_tokens'      => 0,
					'cost'              => 0,
					'model'             => '',
					'status'            => 'failed',
					'error_message'     => $e->getMessage(),
					'user_id'           => get_current_user_id(),
				)
			);

			// Re-throw exception.
			throw $e;
		}
	}

	/**
	 * Update ACF fields with generated content.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $block_type Block type.
	 * @param array  $parsed_content Parsed content data.
	 * @return void
	 * @throws \Exception If ACF function not available.
	 */
	private function updateACFFields( int $post_id, string $block_type, array $parsed_content ): void {
		if ( ! function_exists( 'update_field' ) ) {
			throw new \Exception( 'ACF plugin not available. Cannot update fields.' );
		}

		// DEBUG: Log the complete context before attempting to save
		error_log( '========== ACF FIELD UPDATE DEBUG START ==========' );
		error_log( 'Post ID: ' . $post_id );
		error_log( 'Post Type: ' . get_post_type( $post_id ) );
		error_log( 'Block Type: ' . $block_type );
		error_log( 'Parsed Content: ' . wp_json_encode( $parsed_content ) );

		// Check if ACF field groups are loaded
		if ( function_exists( 'acf_get_field_groups' ) ) {
			$field_groups = acf_get_field_groups( array( 'post_id' => $post_id ) );
			error_log( 'ACF Field Groups for this post: ' . count( $field_groups ) );
			foreach ( $field_groups as $group ) {
				error_log( '  - Group: ' . $group['key'] . ' (' . $group['title'] . ')' );
			}
		}

		// Update each field.
		foreach ( $parsed_content as $field_name => $field_value ) {
			error_log( '--- Attempting to update field: ' . $field_name );

			// Check if field exists
			if ( function_exists( 'acf_get_field' ) ) {
				$field_object = acf_get_field( $field_name );
				if ( $field_object ) {
					error_log( '  Field exists: ' . $field_object['key'] . ' (type: ' . $field_object['type'] . ')' );
				} else {
					error_log( '  WARNING: Field not found in ACF configuration!' );
				}
			}

			// Attempt update
			$result = update_field( $field_name, $field_value, $post_id );

			// Log result
			if ( false === $result ) {
				error_log( '  FAILED: update_field returned FALSE' );
			} elseif ( null === $result ) {
				error_log( '  WARNING: update_field returned NULL (field may not exist)' );
			} else {
				error_log( '  SUCCESS: Field updated' );

				// Verify the save by reading it back
				$saved_value = get_field( $field_name, $post_id );
				error_log( '  Verified saved value: ' . wp_json_encode( $saved_value ) );
			}
		}

		// Update generation metadata.
		$meta_result_1 = update_post_meta( $post_id, "_seo_gen_{$block_type}_generated", true );
		$meta_result_2 = update_post_meta( $post_id, "_seo_gen_{$block_type}_timestamp", time() );

		error_log( 'Generation metadata saved: ' . ( $meta_result_1 ? 'YES' : 'NO' ) );
		error_log( '========== ACF FIELD UPDATE DEBUG END ==========' );
	}

	/**
	 * Auto-assign images to content blocks.
	 *
	 * @param int    $post_id        Post ID.
	 * @param string $block_type     Block type.
	 * @param array  $context        Generation context.
	 * @param array  $parsed_content Parsed content data.
	 * @return array Array of assigned image field names and IDs.
	 */
	private function autoAssignImages( int $post_id, string $block_type, array $context, array $parsed_content ): array {
		// Check if auto-assignment is enabled.
		if ( ! $this->isAutoAssignmentEnabled() ) {
			return array();
		}

		// Only auto-assign for blocks with image fields.
		if ( 'hero' === $block_type ) {
			return $this->assignHeroImage( $post_id, $context );
		} elseif ( 'process' === $block_type ) {
			return $this->assignProcessImages( $post_id, $context, $parsed_content );
		}

		return array();
	}

	/**
	 * Assign image to hero block.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $context Generation context.
	 * @return array Array with 'hero_image' key if assigned, empty otherwise.
	 */
	private function assignHeroImage( int $post_id, array $context ): array {
		// Build context for image matching.
		$image_context = $this->buildImageContext( $post_id, $context );

		// DEBUG: Log what context we're using for image matching
		error_log( '[SEO Generator - Hero Image Assignment DEBUG]' );
		error_log( 'Post ID: ' . $post_id );
		error_log( 'Generation context: ' . wp_json_encode( $context ) );
		error_log( 'Image context: ' . wp_json_encode( $image_context ) );

		// Find matching image.
		$image_id = $this->image_matching->findMatchingImage( $image_context );

		if ( $image_id ) {
			// Generate alt text and clear unnecessary metadata.
			$this->image_matching->assignImageWithMetadata( $image_id, $post_id, $image_context );

			// Update hero_image field.
			if ( function_exists( 'update_field' ) ) {
				error_log( '[DIAGNOSTIC] About to call update_field for hero_image' );
				error_log( '[DIAGNOSTIC] Image ID: ' . $image_id );
				error_log( '[DIAGNOSTIC] Post ID: ' . $post_id );

				// Check if field exists
				if ( function_exists( 'acf_get_field' ) ) {
					$field_object = acf_get_field( 'hero_image' );
					if ( $field_object ) {
						error_log( '[DIAGNOSTIC] Field exists: ' . $field_object['key'] . ' (type: ' . $field_object['type'] . ')' );
					} else {
						error_log( '[DIAGNOSTIC] WARNING: hero_image field NOT FOUND in ACF!' );
					}
				}

				// Attempt update and capture result
				$update_result = update_field( 'hero_image', $image_id, $post_id );

				error_log( '[DIAGNOSTIC] update_field returned: ' . var_export( $update_result, true ) );

				// Verify by reading back
				if ( function_exists( 'get_field' ) ) {
					$saved_value = get_field( 'hero_image', $post_id );
					error_log( '[DIAGNOSTIC] Verified saved value: ' . var_export( $saved_value, true ) );
				}

				// Log auto-assignment.
				error_log(
					sprintf(
						'[SEO Generator - Auto-Assignment] Post: %d | Block: hero | Image: %d | Context: %s',
						$post_id,
						$image_id,
						wp_json_encode( $image_context )
					)
				);

				// Return image ID for frontend preview
				return array( 'hero_image' => $image_id );
			} else {
				error_log( '[DIAGNOSTIC] CRITICAL: update_field function does NOT exist!' );
			}
		} else {
			// Log no image found.
			error_log(
				sprintf(
					'[SEO Generator - Auto-Assignment] Post: %d | Block: hero | Image: none | Context: %s',
					$post_id,
					wp_json_encode( $image_context )
				)
			);
		}

		return array();
	}

	/**
	 * Assign images to process block steps.
	 *
	 * @param int   $post_id        Post ID.
	 * @param array $context        Generation context.
	 * @param array $parsed_content Parsed content data.
	 * @return array Array of assigned images (currently empty for process blocks).
	 */
	private function assignProcessImages( int $post_id, array $context, array $parsed_content ): array {
		if ( ! isset( $parsed_content['process_steps'] ) || ! is_array( $parsed_content['process_steps'] ) ) {
			return array();
		}

		// Build base context.
		$base_context = $this->buildImageContext( $post_id, $context );

		// Assign image to each step.
		foreach ( $parsed_content['process_steps'] as $index => $step ) {
			// Add step title to context for better matching.
			$step_context = $base_context;
			if ( ! empty( $step['step_title'] ) ) {
				$step_context['topic'] = $step['step_title'];
			}

			// Find matching image.
			$image_id = $this->image_matching->findMatchingImage( $step_context );

			if ( $image_id && function_exists( 'update_sub_field' ) ) {
				// Generate alt text and clear unnecessary metadata.
				$this->image_matching->assignImageWithMetadata( $image_id, $post_id, $step_context );

				// Update step_image field in repeater.
				update_sub_field( array( 'process_steps', $index + 1, 'step_image' ), $image_id, $post_id );

				// Log auto-assignment.
				error_log(
					sprintf(
						'[SEO Generator - Auto-Assignment] Post: %d | Block: process | Step: %d | Image: %d | Context: %s',
						$post_id,
						$index + 1,
						$image_id,
						wp_json_encode( $step_context )
					)
				);
			}
		}

		// Note: Process block images are embedded in the repeater, not returned separately
		return array();
	}

	/**
	 * Build image matching context from generation context.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $context Generation context.
	 * @return array Image matching context.
	 */
	private function buildImageContext( int $post_id, array $context ): array {
		$image_context = array();

		// Try to get focus_keyword from ACF first (in case seo_metadata block already generated it).
		if ( function_exists( 'get_field' ) ) {
			$saved_focus_keyword = get_field( 'seo_focus_keyword', $post_id );
			if ( ! empty( $saved_focus_keyword ) ) {
				$image_context['focus_keyword'] = $saved_focus_keyword;
			}
		}

		// Fall back to context focus_keyword if not in ACF yet.
		if ( empty( $image_context['focus_keyword'] ) && ! empty( $context['focus_keyword'] ) ) {
			$image_context['focus_keyword'] = $context['focus_keyword'];
		}

		// Use topic if available (check both 'page_topic' and 'topic' keys for compatibility).
		if ( ! empty( $context['page_topic'] ) ) {
			$image_context['topic'] = $context['page_topic'];
		} elseif ( ! empty( $context['topic'] ) ) {
			$image_context['topic'] = $context['topic'];
		}

		// Get SEO topic from post terms (seo-page uses seo-topic taxonomy, not category).
		$topics = get_the_terms( $post_id, 'seo-topic' );
		if ( $topics && ! is_wp_error( $topics ) && ! empty( $topics ) ) {
			// If we don't already have a topic, use the taxonomy term.
			if ( empty( $image_context['topic'] ) ) {
				$image_context['topic'] = $topics[0]->name;
			}
		}

		return $image_context;
	}

	/**
	 * Check if auto-assignment is enabled in settings.
	 *
	 * @return bool True if enabled, false otherwise.
	 */
	private function isAutoAssignmentEnabled(): bool {
		$settings = get_option( 'seo_generator_settings', array() );
		return $settings['enable_auto_assignment'] ?? true; // Default enabled.
	}

	/**
	 * Generate all blocks for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return BulkGenerationResult Bulk generation result.
	 * @throws RateLimitException If rate limit exceeded.
	 */
	public function generateAllBlocks( int $post_id ): BulkGenerationResult {
		$start_time = microtime( true );
		$user_id    = get_current_user_id();

		// Rate limiting.
		$this->checkBulkRateLimit( $user_id );
		$this->startBulkGeneration( $post_id, $user_id );

		// Suspend cache addition during bulk generation to prevent memory bloat.
		wp_suspend_cache_addition( true );

		$results = array(
			'successful'  => array(),
			'failed'      => array(),
			'totalTokens' => 0,
			'totalCost'   => 0.0,
		);

		// Process each block sequentially.
		foreach ( self::BLOCK_ORDER as $index => $block_type ) {
			try {
				$result = $this->generateSingleBlock( $post_id, $block_type );

				$results['successful'][]  = $block_type;
				$results['totalTokens']  += $result['metadata']['totalTokens'];
				$results['totalCost']    += $result['metadata']['cost'];

				// Free up memory from API response - we only need the metadata now.
				unset( $result );

			} catch ( RateLimitException $e ) {
				// Wait and retry once for rate limit.
				error_log( '[SEO Generator] Rate limit hit on block: ' . $block_type . ', waiting 60 seconds' );
				sleep( 60 );

				try {
					$result = $this->generateSingleBlock( $post_id, $block_type );

					$results['successful'][]  = $block_type;
					$results['totalTokens']  += $result['metadata']['totalTokens'];
					$results['totalCost']    += $result['metadata']['cost'];

					// Free up memory from retry.
					unset( $result );

				} catch ( \Exception $e ) {
					error_log( '[SEO Generator] Block failed after retry: ' . $block_type . ' - ' . $e->getMessage() );
					$results['failed'][] = array(
						'block' => $block_type,
						'error' => $e->getMessage(),
					);
				}
			} catch ( \Exception $e ) {
				error_log( '[SEO Generator] Block failed: ' . $block_type . ' - ' . $e->getMessage() );
				$results['failed'][] = array(
					'block' => $block_type,
					'error' => $e->getMessage(),
				);
			}

			// Flush cache every 4 blocks to prevent memory buildup.
			if ( 0 === ( $index + 1 ) % 4 ) {
				wp_cache_flush();
			}

			// Update progress.
			$time_elapsed = microtime( true ) - $start_time;
			$this->updateProgress(
				$post_id,
				$user_id,
				array(
					'currentBlock'            => $block_type,
					'currentBlockIndex'       => $index + 1,
					'totalBlocks'             => 13,
					'completionPercentage'    => round( ( ( $index + 1 ) / 13 ) * 100, 2 ),
					'timeElapsed'             => round( $time_elapsed, 2 ),
					'estimatedTimeRemaining'  => $this->calculateETA( $index + 1, $start_time ),
					'completedBlocks'         => $results['successful'],
					'failedBlocks'            => array_column( $results['failed'], 'block' ),
				)
			);
		}

		// Clean up.
		$this->endBulkGeneration( $post_id, $user_id );
		$this->clearProgress( $post_id, $user_id );

		// Sync to Yoast SEO if available.
		$yoast = new YoastIntegrationService();
		if ( $yoast->isYoastActive() ) {
			$yoast->syncToYoast( $post_id );
		}

		// Final cache flush and re-enable cache addition.
		wp_cache_flush();
		wp_suspend_cache_addition( false );

		$total_time = round( microtime( true ) - $start_time, 2 );

		return new BulkGenerationResult(
			13,
			count( $results['successful'] ),
			$results['failed'],
			$results['totalTokens'],
			$results['totalCost'],
			$total_time
		);
	}

	/**
	 * Check bulk generation rate limit.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 * @throws RateLimitException If rate limit exceeded.
	 */
	private function checkBulkRateLimit( int $user_id ): void {
		$transient_key = 'seo_gen_bulk_active_' . $user_id;
		$active        = get_transient( $transient_key );

		if ( false === $active ) {
			$active = array();
		}

		if ( count( $active ) >= self::MAX_CONCURRENT_BULK ) {
			throw new RateLimitException(
				'Maximum ' . self::MAX_CONCURRENT_BULK . ' concurrent bulk generations allowed. Please wait for an existing generation to complete.'
			);
		}
	}

	/**
	 * Start bulk generation tracking.
	 *
	 * @param int $post_id Post ID.
	 * @param int $user_id User ID.
	 * @return void
	 */
	private function startBulkGeneration( int $post_id, int $user_id ): void {
		$transient_key = 'seo_gen_bulk_active_' . $user_id;
		$active        = get_transient( $transient_key );

		if ( false === $active ) {
			$active = array();
		}

		$active[] = $post_id;
		set_transient( $transient_key, $active, 10 * MINUTE_IN_SECONDS );
	}

	/**
	 * End bulk generation tracking.
	 *
	 * @param int $post_id Post ID.
	 * @param int $user_id User ID.
	 * @return void
	 */
	private function endBulkGeneration( int $post_id, int $user_id ): void {
		$transient_key = 'seo_gen_bulk_active_' . $user_id;
		$active        = get_transient( $transient_key );

		if ( false === $active ) {
			return;
		}

		$active = array_diff( $active, array( $post_id ) );
		set_transient( $transient_key, $active, 10 * MINUTE_IN_SECONDS );
	}

	/**
	 * Update bulk generation progress.
	 *
	 * @param int   $post_id Post ID.
	 * @param int   $user_id User ID.
	 * @param array $progress Progress data.
	 * @return void
	 */
	private function updateProgress( int $post_id, int $user_id, array $progress ): void {
		$transient_key = 'seo_gen_progress_' . $post_id . '_' . $user_id;
		set_transient( $transient_key, $progress, 10 * MINUTE_IN_SECONDS );
	}

	/**
	 * Get bulk generation progress.
	 *
	 * @param int $post_id Post ID.
	 * @param int $user_id User ID.
	 * @return array|null Progress data or null if not found.
	 */
	public function getProgress( int $post_id, int $user_id ): ?array {
		$transient_key = 'seo_gen_progress_' . $post_id . '_' . $user_id;
		$progress      = get_transient( $transient_key );

		return false !== $progress ? $progress : null;
	}

	/**
	 * Clear bulk generation progress.
	 *
	 * @param int $post_id Post ID.
	 * @param int $user_id User ID.
	 * @return void
	 */
	private function clearProgress( int $post_id, int $user_id ): void {
		$transient_key = 'seo_gen_progress_' . $post_id . '_' . $user_id;
		delete_transient( $transient_key );
	}

	/**
	 * Calculate estimated time remaining.
	 *
	 * @param int   $completed_blocks Number of completed blocks.
	 * @param float $start_time Generation start time.
	 * @return int Estimated seconds remaining.
	 */
	private function calculateETA( int $completed_blocks, float $start_time ): int {
		if ( 0 === $completed_blocks ) {
			return 0;
		}

		$time_elapsed          = microtime( true ) - $start_time;
		$average_time_per_block = $time_elapsed / $completed_blocks;
		$remaining_blocks      = 13 - $completed_blocks;

		return (int) ceil( $average_time_per_block * $remaining_blocks );
	}

	/**
	 * Update Block Editor content with AI-generated data.
	 *
	 * This syncs ACF field data to the Block Editor's post_content so that:
	 * 1. The Block Editor shows the AI-generated content
	 * 2. Users can edit it visually with drag-and-drop
	 * 3. The frontend template can simply display the blocks
	 *
	 * @param int   $post_id Post ID.
	 * @param array $parsed_content Parsed content from AI.
	 * @param array $assigned_images Assigned image IDs.
	 * @return void
	 */
	private function updateBlockEditorContent( int $post_id, array $parsed_content, array $assigned_images ): void {
		// Extract hero content
		$title = $parsed_content['hero_title'] ?? get_the_title( $post_id );
		$subtitle = $parsed_content['hero_subtitle'] ?? 'In a world where the delicate and the dainty often take center stage...';
		$description = $parsed_content['hero_description'] ?? 'These rings, with their generous bands and captivating diamonds...';

		// Get image URL
		$image_html = '';
		if ( ! empty( $assigned_images['hero_image'] ) ) {
			$image_id = $assigned_images['hero_image'];
			$image_url = wp_get_attachment_image_url( $image_id, 'full' );
			$image_alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );

			if ( $image_url ) {
				$image_html = sprintf(
					'<img src="%s" alt="%s" class="wp-image-%d"/>',
					esc_url( $image_url ),
					esc_attr( $image_alt ),
					$image_id
				);
			}
		}

		// Build block markup
		$block_content = sprintf(
			'<!-- wp:columns {"className":"hero-section"} -->
<div class="wp-block-columns hero-section"><!-- wp:column {"width":"50%%"} -->
<div class="wp-block-column" style="flex-basis:50%%"><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">%s</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>%s</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>%s</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"50%%"} -->
<div class="wp-block-column" style="flex-basis:50%%"><!-- wp:image {"align":"center"%s} -->
<figure class="wp-block-image aligncenter">%s</figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->',
			esc_html( $title ),
			esc_html( $subtitle ),
			esc_html( $description ),
			! empty( $image_html ) ? ',"id":' . $assigned_images['hero_image'] : '',
			$image_html ?: '<img alt=""/>'
		);

		// Update post content
		$result = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $block_content,
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			error_log( '[SEO Generator] Failed to update Block Editor content: ' . $result->get_error_message() );
		} else {
			error_log( '[SEO Generator] Successfully updated Block Editor content for post ' . $post_id );
		}
	}
}
