<?php
/**
 * Generation Queue Service
 *
 * Manages background content generation queue with WordPress Cron.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Generation Queue Service
 *
 * Handles queuing of posts for background generation, scheduling via
 * WordPress Cron, and queue management (pause/resume/clear).
 *
 * Usage:
 * ```php
 * $queue = new GenerationQueue();
 * $queue->queuePost($post_id, 0); // Queue with index 0 (immediate)
 * $queue->queuePost($post_id_2, 1); // Queue with index 1 (3 min later)
 * ```
 */
class GenerationQueue {
	/**
	 * Option key for queue storage.
	 */
	const QUEUE_OPTION = 'seo_generation_queue';

	/**
	 * Option key for paused status.
	 */
	const PAUSED_OPTION = 'seo_queue_paused';

	/**
	 * Rate limit in seconds (30 seconds).
	 * Optimized from 180s to 30s for faster bulk generation while staying well within OpenAI rate limits.
	 */
	const RATE_LIMIT_SECONDS = 30;

	/**
	 * Queue a post for background generation.
	 *
	 * @param int        $post_id Post ID to queue.
	 * @param int        $index   Queue index for scheduling offset (default 0).
	 * @param array|null $blocks  Optional array of specific blocks to generate (default: all blocks).
	 * @return bool True on success, false on failure.
	 */
	public function queuePost( int $post_id, int $index = 0, ?array $blocks = null ): bool {
		// Get current queue.
		$queue = get_option( self::QUEUE_OPTION, array() );

		// Check if already queued.
		foreach ( $queue as $item ) {
			if ( $item['post_id'] === $post_id && $item['status'] === 'pending' ) {
				return false; // Already queued.
			}
		}

		// Calculate scheduled time (30 seconds apart).
		$base_time      = time();
		$scheduled_time = $base_time + ( $index * self::RATE_LIMIT_SECONDS );

		// Add to queue.
		$queue_item = array(
			'post_id'        => $post_id,
			'scheduled_time' => $scheduled_time,
			'status'         => 'pending',
			'queued_at'      => current_time( 'mysql' ),
		);

		// Store blocks configuration if provided.
		if ( $blocks !== null ) {
			$queue_item['blocks'] = $blocks;
		}

		$queue[] = $queue_item;

		update_option( self::QUEUE_OPTION, $queue );

		// Schedule WordPress Cron event.
		// For the first job, we still respect rate limiting by scheduling it normally.
		wp_schedule_single_event( $scheduled_time, 'seo_generate_queued_page', array( $post_id ) );

		return true;
	}

	/**
	 * Get queued posts, optionally filtered by status.
	 *
	 * @param string|null $status Optional status filter (pending, processing, completed, failed).
	 * @return array Array of queued post items.
	 */
	public function getQueuedPosts( ?string $status = null ): array {
		$queue = get_option( self::QUEUE_OPTION, array() );

		if ( $status ) {
			return array_filter(
				$queue,
				function ( $item ) use ( $status ) {
					return $item['status'] === $status;
				}
			);
		}

		return $queue;
	}

	/**
	 * Update queue status for a specific post.
	 *
	 * @param int         $post_id Post ID.
	 * @param string      $status  New status (pending, processing, completed, failed).
	 * @param string|null $error   Optional error message.
	 * @return bool True if updated, false if not found.
	 */
	public function updateQueueStatus( int $post_id, string $status, ?string $error = null ): bool {
		$queue   = get_option( self::QUEUE_OPTION, array() );
		$updated = false;

		foreach ( $queue as &$item ) {
			if ( $item['post_id'] === $post_id ) {
				$item['status']     = $status;
				$item['updated_at'] = current_time( 'mysql' );

				if ( $error ) {
					$item['error'] = $error;
				}

				$updated = true;
				break;
			}
		}

		if ( $updated ) {
			update_option( self::QUEUE_OPTION, $queue );
		}

		return $updated;
	}

	/**
	 * Clear the entire queue.
	 *
	 * Removes all pending jobs and cancels scheduled cron events.
	 *
	 * @return void
	 */
	public function clearQueue(): void {
		$queue = get_option( self::QUEUE_OPTION, array() );

		// Cancel all scheduled cron events for pending jobs.
		foreach ( $queue as $item ) {
			if ( $item['status'] === 'pending' ) {
				wp_clear_scheduled_hook( 'seo_generate_queued_page', array( $item['post_id'] ) );
			}
		}

		// Clear queue.
		delete_option( self::QUEUE_OPTION );
	}

	/**
	 * Pause the queue.
	 *
	 * Prevents cron jobs from processing until resumed.
	 *
	 * @return void
	 */
	public function pauseQueue(): void {
		update_option( self::PAUSED_OPTION, true );
	}

	/**
	 * Resume the queue.
	 *
	 * Allows cron jobs to process again.
	 *
	 * @return void
	 */
	public function resumeQueue(): void {
		delete_option( self::PAUSED_OPTION );
	}

	/**
	 * Check if queue is paused.
	 *
	 * @return bool True if paused, false otherwise.
	 */
	public function isPaused(): bool {
		return (bool) get_option( self::PAUSED_OPTION, false );
	}

	/**
	 * Get queue statistics.
	 *
	 * @return array Queue statistics with counts by status.
	 */
	public function getQueueStats(): array {
		$queue = get_option( self::QUEUE_OPTION, array() );

		return array(
			'pending'    => count(
				array_filter(
					$queue,
					function ( $item ) {
						return $item['status'] === 'pending';
					}
				)
			),
			'processing' => count(
				array_filter(
					$queue,
					function ( $item ) {
						return $item['status'] === 'processing';
					}
				)
			),
			'completed'  => count(
				array_filter(
					$queue,
					function ( $item ) {
						return $item['status'] === 'completed';
					}
				)
			),
			'failed'     => count(
				array_filter(
					$queue,
					function ( $item ) {
						return $item['status'] === 'failed';
					}
				)
			),
			'total'      => count( $queue ),
		);
	}

	/**
	 * Get estimated completion time.
	 *
	 * @return string|null Estimated completion datetime or null if no pending jobs.
	 */
	public function getEstimatedCompletion(): ?string {
		$pending = $this->getQueuedPosts( 'pending' );

		if ( empty( $pending ) ) {
			return null;
		}

		// Get last scheduled time.
		$scheduled_times = array_column( $pending, 'scheduled_time' );
		$last_scheduled  = max( $scheduled_times );

		// Add average generation time (5 minutes per post).
		$estimated = $last_scheduled + ( 5 * 60 );

		return gmdate( 'Y-m-d H:i:s', $estimated );
	}

	/**
	 * Remove a specific job from the queue.
	 *
	 * @param int $post_id Post ID to remove.
	 * @return bool True if removed, false if not found.
	 */
	public function removeJob( int $post_id ): bool {
		$queue   = get_option( self::QUEUE_OPTION, array() );
		$removed = false;

		foreach ( $queue as $index => $item ) {
			if ( $item['post_id'] === $post_id ) {
				// Cancel scheduled cron event if pending.
				if ( $item['status'] === 'pending' ) {
					wp_clear_scheduled_hook( 'seo_generate_queued_page', array( $post_id ) );
				}

				unset( $queue[ $index ] );
				$removed = true;
				break;
			}
		}

		if ( $removed ) {
			// Re-index array.
			$queue = array_values( $queue );
			update_option( self::QUEUE_OPTION, $queue );
		}

		return $removed;
	}
}
