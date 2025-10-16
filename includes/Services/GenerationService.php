<?php
/**
 * Generation Service
 *
 * Handles content generation for queued posts.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Generation Service
 *
 * Processes queued posts for background content generation,
 * generates all block types, and updates post status.
 *
 * Usage:
 * ```php
 * $service = new GenerationService();
 * $service->processQueuedPage($post_id);
 * ```
 */
class GenerationService {
	/**
	 * GenerationQueue instance.
	 *
	 * @var GenerationQueue
	 */
	private $queue;

	/**
	 * ContentGenerationService instance.
	 *
	 * @var ContentGenerationService
	 */
	private $content_generation_service;

	/**
	 * All block types to generate.
	 *
	 * @var array
	 */
	private $block_types = array(
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
	 * Constructor.
	 */
	public function __construct() {
		$this->queue = new GenerationQueue();

		// Initialize the real content generation service
		$settings_service = new SettingsService();
		$openai_service = new OpenAIService( $settings_service );
		$prompt_engine = new PromptTemplateEngine();
		$content_parser = new BlockContentParser();
		$image_matching = new ImageMatchingService( $openai_service );
		$log_repository = new \SEOGenerator\Repositories\GenerationLogRepository();
		$cost_tracking = new CostTrackingService( $log_repository );

		$this->content_generation_service = new ContentGenerationService(
			$openai_service,
			$prompt_engine,
			$content_parser,
			$cost_tracking,
			$image_matching
		);
	}

	/**
	 * Process queued page (WordPress Cron handler).
	 *
	 * @param int $post_id Post ID to process.
	 * @return void
	 */
	public function processQueuedPage( int $post_id ): void {
		// Check if queue is paused.
		if ( $this->queue->isPaused() ) {
			error_log( "Generation queue is paused, rescheduling post {$post_id}" );

			// Reschedule for 5 minutes later.
			wp_schedule_single_event( time() + 300, 'seo_generate_queued_page', array( $post_id ) );
			return;
		}

		// Enforce rate limit protection.
		if ( ! $this->enforceRateLimit( $post_id ) ) {
			return; // Rescheduled by enforceRateLimit().
		}

		// Verify post exists and is correct type.
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'seo-page' ) {
			error_log( "Post {$post_id} not found or invalid type" );
			$this->queue->updateQueueStatus( $post_id, 'failed', 'Post not found or invalid type' );
			return;
		}

		// Suspend cache to prevent memory bloat during background processing.
		wp_suspend_cache_addition( true );

		// Get blocks configuration from queue item.
		$blocks_to_generate = $this->getBlocksConfigFromQueue( $post_id );

		// Update status to processing.
		$this->queue->updateQueueStatus( $post_id, 'processing' );

		try {
			// Generate blocks (all or specific blocks based on configuration).
			$result = $this->generateAllBlocks( $post_id, $blocks_to_generate );

			if ( $result['success'] ) {
				// Update post status to pending review.
				wp_update_post(
					array(
						'ID'          => $post_id,
						'post_status' => 'pending',
					)
				);

				// Add generation metadata.
				update_post_meta( $post_id, '_auto_generated', true );
				update_post_meta( $post_id, '_generation_date', current_time( 'mysql' ) );
				update_post_meta( $post_id, '_blocks_generated', $result['blocks_generated'] );
				update_post_meta( $post_id, '_blocks_failed', $result['blocks_failed'] );

				// Sync to Yoast SEO if available.
				$yoast = new YoastIntegrationService();
				if ( $yoast->isYoastActive() ) {
					$yoast->syncToYoast( $post_id );
					error_log( "[Yoast Integration] Synced SEO data for queued post {$post_id}" );
				}

				// Calculate and store internal links.
				$linking_service = new InternalLinkingService();
				$linking_service->refreshLinks( $post_id );
				error_log( "[Internal Linking] Calculated related links for post {$post_id}" );

				// Update queue status.
				$this->queue->updateQueueStatus( $post_id, 'completed' );

				error_log( "Successfully generated content for post {$post_id}: {$result['blocks_generated']} blocks" );
			} else {
				throw new \Exception( 'Generation failed: ' . implode( ', ', $result['errors'] ) );
			}
		} catch ( \Exception $e ) {
			error_log( "Failed to generate post {$post_id}: " . $e->getMessage() );
			$this->queue->updateQueueStatus( $post_id, 'failed', $e->getMessage() );
		} finally {
			// Always clean up cache regardless of success/failure.
			wp_cache_flush();
			wp_suspend_cache_addition( false );
		}
	}

	/**
	 * Generate all blocks for a post.
	 *
	 * @param int        $post_id Post ID.
	 * @param array|null $blocks  Optional array of specific blocks to generate (default: all blocks).
	 * @return array Generation result with success status and counts.
	 */
	public function generateAllBlocks( int $post_id, ?array $blocks = null ): array {
		$generated = 0;
		$failed    = 0;
		$errors    = array();

		// Determine which blocks to generate with proper fallback priority:
		// 1. Use $blocks parameter if provided (from queue item)
		// 2. Check post meta _seo_block_order for custom block order
		// 3. Fall back to all blocks as default
		if ( $blocks !== null ) {
			$blocks_to_generate = $blocks;
			error_log( "[SEO Generator] Using blocks from queue parameter for post {$post_id}: " . wp_json_encode( $blocks ) );
		} else {
			// Check post meta for custom block order.
			$custom_order_json = get_post_meta( $post_id, '_seo_block_order', true );
			if ( ! empty( $custom_order_json ) ) {
				$custom_order = json_decode( $custom_order_json, true );
				if ( is_array( $custom_order ) && ! empty( $custom_order ) ) {
					$blocks_to_generate = $custom_order;
					error_log( "[SEO Generator] Using post meta block order for post {$post_id}: " . wp_json_encode( $custom_order ) );
				} else {
					$blocks_to_generate = $this->block_types;
					error_log( "[SEO Generator] Invalid post meta block order, using all blocks for post {$post_id}" );
				}
			} else {
				$blocks_to_generate = $this->block_types;
				error_log( "[SEO Generator] No custom block order found, using all blocks for post {$post_id}" );
			}
		}

		// ALWAYS ensure seo_metadata is generated first (if not already in list).
		if ( ! in_array( 'seo_metadata', $blocks_to_generate, true ) ) {
			array_unshift( $blocks_to_generate, 'seo_metadata' );
			error_log( "[SEO Generator] Auto-adding seo_metadata block to generation list for post {$post_id}" );
		}

		foreach ( $blocks_to_generate as $block_type ) {
			try {
				$result = $this->generateBlock( $post_id, $block_type );

				if ( $result['success'] ) {
					$generated++;
				} else {
					$failed++;
					$errors[] = "{$block_type}: " . $result['error'];
					error_log( "Block generation failed for {$block_type} (post {$post_id}): " . $result['error'] );
				}
			} catch ( \Exception $e ) {
				$failed++;
				$errors[] = "{$block_type}: " . $e->getMessage();
				error_log( "Block generation exception for {$block_type} (post {$post_id}): " . $e->getMessage() );
			}
		}

		return array(
			'success'          => $failed === 0,
			'blocks_generated' => $generated,
			'blocks_failed'    => $failed,
			'errors'           => $errors,
		);
	}

	/**
	 * Generate a single block using ContentGenerationService.
	 *
	 * @param int    $post_id    Post ID.
	 * @param string $block_type Block type to generate.
	 * @return array Result with success status and optional error.
	 */
	private function generateBlock( int $post_id, string $block_type ): array {
		try {
			// Use the real ContentGenerationService to generate content.
			// This handles OpenAI API calls, parsing, ACF field updates, and auto-image assignment.
			$result = $this->content_generation_service->generateSingleBlock( $post_id, $block_type );

			return array(
				'success' => true,
				'metadata' => $result['metadata'] ?? array(),
			);

		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'error'   => $e->getMessage(),
			);
		}
	}

	/**
	 * Get blocks configuration from queue item.
	 *
	 * @param int $post_id Post ID.
	 * @return array|null Array of blocks to generate, or null for all blocks.
	 */
	private function getBlocksConfigFromQueue( int $post_id ): ?array {
		$queue = get_option( 'seo_generation_queue', array() );

		foreach ( $queue as $item ) {
			if ( isset( $item['post_id'] ) && $item['post_id'] === $post_id ) {
				return isset( $item['blocks'] ) ? $item['blocks'] : null;
			}
		}

		return null;
	}

	/**
	 * Enforce rate limit protection.
	 *
	 * @param int $post_id Post ID being generated.
	 * @return bool True if rate limit allows processing, false if should reschedule.
	 */
	private function enforceRateLimit( int $post_id ): bool {
		$last_generation = get_option( 'seo_last_generation_time', 0 );
		$current_time    = time();
		$elapsed         = $current_time - $last_generation;

		if ( $elapsed < GenerationQueue::RATE_LIMIT_SECONDS ) {
			$wait_time = GenerationQueue::RATE_LIMIT_SECONDS - $elapsed;
			error_log( "Rate limit: waiting {$wait_time} seconds before generating post {$post_id}" );

			// Reschedule for later.
			wp_schedule_single_event( $current_time + $wait_time, 'seo_generate_queued_page', array( $post_id ) );
			return false;
		}

		// Update last generation time.
		update_option( 'seo_last_generation_time', $current_time );
		return true;
	}
}
