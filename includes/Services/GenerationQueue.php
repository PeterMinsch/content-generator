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
	 * Rate limit in seconds (10 seconds).
	 * Optimized for Tier 3+ OpenAI accounts (10,000+ RPM).
	 * For lower tiers, increase to 15-30 seconds to avoid rate limit errors.
	 */
	const RATE_LIMIT_SECONDS = 10;

	/**
	 * Maximum number of retry attempts for failed jobs.
	 */
	const MAX_RETRIES = 3;

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
			'retry_count'    => 0,
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
	 * Retry a failed job with exponential backoff.
	 *
	 * @param int         $post_id Post ID.
	 * @param string|null $error   Error message from previous attempt.
	 * @return bool True if job was retried, false if max retries reached or job not found.
	 */
	public function retryFailedJob( int $post_id, ?string $error = null ): bool {
		$queue = get_option( self::QUEUE_OPTION, array() );
		$retried = false;

		foreach ( $queue as &$item ) {
			if ( $item['post_id'] === $post_id ) {
				// Initialize retry_count if it doesn't exist (backward compatibility).
				if ( ! isset( $item['retry_count'] ) ) {
					$item['retry_count'] = 0;
				}

				// Check if we've exceeded max retries.
				if ( $item['retry_count'] >= self::MAX_RETRIES ) {
					// Mark as permanently failed.
					$item['status'] = 'failed';
					$item['error'] = sprintf(
						'Max retries (%d) exceeded. Last error: %s',
						self::MAX_RETRIES,
						$error ?? 'Unknown error'
					);
					$item['updated_at'] = current_time( 'mysql' );
					error_log( "[Queue] Post {$post_id} failed permanently after " . self::MAX_RETRIES . ' retries' );
					break;
				}

				// Increment retry count.
				$item['retry_count']++;

				// Calculate exponential backoff delay: 30s, 60s, 120s.
				$backoff_multiplier = pow( 2, $item['retry_count'] - 1 );
				$delay = self::RATE_LIMIT_SECONDS * $backoff_multiplier;

				// Reschedule the job.
				$scheduled_time = time() + $delay;
				$item['scheduled_time'] = $scheduled_time;
				$item['status'] = 'pending';
				$item['updated_at'] = current_time( 'mysql' );

				// Store the previous error for reference.
				if ( $error ) {
					$item['last_error'] = $error;
				}

				// Schedule WordPress Cron event.
				wp_schedule_single_event( $scheduled_time, 'seo_generate_queued_page', array( $post_id ) );

				error_log( "[Queue] Retrying post {$post_id} (attempt {$item['retry_count']}/{" . self::MAX_RETRIES . '}) in ' . $delay . 's' );

				$retried = true;
				break;
			}
		}

		if ( $retried || isset( $item ) ) {
			update_option( self::QUEUE_OPTION, $queue );
		}

		return $retried;
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

	// ─── Dynamic Publish Queue ──────────────────────────────────

	/**
	 * Option key for dynamic publish queue storage.
	 */
	const DYNAMIC_QUEUE_OPTION = 'seo_dynamic_publish_queue';

	/**
	 * Queue a dynamic page publish job for background processing.
	 *
	 * @param int   $index    Queue index for scheduling offset.
	 * @param array $job_data Job data: keyword, slug, page_template, blocks, context.
	 * @return bool True on success.
	 */
	public function queueDynamicPublish( int $index, array $job_data ): bool {
		$queue = get_option( self::DYNAMIC_QUEUE_OPTION, [] );

		// Check for duplicate by slug.
		foreach ( $queue as $item ) {
			if ( $item['slug'] === $job_data['slug'] && $item['status'] === 'pending' ) {
				return false;
			}
		}

		$scheduled_time = time() + ( $index * self::RATE_LIMIT_SECONDS );

		$queue_item = [
			'slug'           => $job_data['slug'],
			'job_data'       => $job_data,
			'scheduled_time' => $scheduled_time,
			'status'         => 'pending',
			'queued_at'      => current_time( 'mysql' ),
			'retry_count'    => 0,
		];

		$queue[] = $queue_item;
		update_option( self::DYNAMIC_QUEUE_OPTION, $queue );

		// Schedule WordPress Cron event.
		wp_schedule_single_event( $scheduled_time, 'seo_process_dynamic_publish', [ $job_data['slug'] ] );

		return true;
	}

	/**
	 * Get dynamic publish queue items, optionally filtered by status.
	 *
	 * @param string|null $status Optional status filter.
	 * @return array Array of queue items.
	 */
	public function getDynamicPublishQueue( ?string $status = null ): array {
		$queue = get_option( self::DYNAMIC_QUEUE_OPTION, [] );

		if ( $status ) {
			return array_filter( $queue, function ( $item ) use ( $status ) {
				return $item['status'] === $status;
			} );
		}

		return $queue;
	}

	/**
	 * Update dynamic publish queue status for a specific slug.
	 *
	 * @param string      $slug   Page slug.
	 * @param string      $status New status.
	 * @param string|null $error  Optional error message.
	 * @return bool True if updated.
	 */
	public function updateDynamicPublishStatus( string $slug, string $status, ?string $error = null ): bool {
		$queue   = get_option( self::DYNAMIC_QUEUE_OPTION, [] );
		$updated = false;

		foreach ( $queue as &$item ) {
			if ( $item['slug'] === $slug ) {
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
			update_option( self::DYNAMIC_QUEUE_OPTION, $queue );
		}

		return $updated;
	}

	/**
	 * Get the job data for a dynamic publish queue item by slug.
	 *
	 * @param string $slug Page slug.
	 * @return array|null Job data or null if not found.
	 */
	public function getDynamicPublishJob( string $slug ): ?array {
		$queue = get_option( self::DYNAMIC_QUEUE_OPTION, [] );

		foreach ( $queue as $item ) {
			if ( $item['slug'] === $slug ) {
				return $item;
			}
		}

		return null;
	}

	/**
	 * Get dynamic publish queue statistics.
	 *
	 * @return array Stats by status.
	 */
	public function getDynamicPublishStats(): array {
		$queue = get_option( self::DYNAMIC_QUEUE_OPTION, [] );

		$stats = [ 'pending' => 0, 'processing' => 0, 'completed' => 0, 'failed' => 0, 'total' => count( $queue ) ];
		foreach ( $queue as $item ) {
			$s = $item['status'] ?? 'pending';
			if ( isset( $stats[ $s ] ) ) {
				$stats[ $s ]++;
			}
		}

		return $stats;
	}

	/**
	 * Clear the dynamic publish queue.
	 *
	 * @return void
	 */
	public function clearDynamicPublishQueue(): void {
		$queue = get_option( self::DYNAMIC_QUEUE_OPTION, [] );

		foreach ( $queue as $item ) {
			if ( $item['status'] === 'pending' ) {
				wp_clear_scheduled_hook( 'seo_process_dynamic_publish', [ $item['slug'] ] );
			}
		}

		delete_option( self::DYNAMIC_QUEUE_OPTION );
	}

	/**
	 * Clean up old completed/failed jobs from the queue.
	 *
	 * Removes jobs that have been completed or failed for more than the specified number of days.
	 * This prevents the queue from growing indefinitely and slowing down queries.
	 *
	 * @param int $days_old Number of days to keep completed/failed jobs (default: 7).
	 * @return int Number of jobs cleaned up.
	 */
	public function cleanupOldJobs( int $days_old = 7 ): int {
		$queue  = get_option( self::QUEUE_OPTION, array() );
		$cutoff = time() - ( $days_old * DAY_IN_SECONDS );
		$count  = 0;

		$cleaned_queue = array();

		foreach ( $queue as $item ) {
			$should_keep = true;

			// Only remove completed or failed jobs.
			if ( in_array( $item['status'], array( 'completed', 'failed' ), true ) ) {
				// Check if job is older than cutoff.
				if ( isset( $item['scheduled_time'] ) && $item['scheduled_time'] < $cutoff ) {
					$should_keep = false;
					$count++;
				}
			}

			if ( $should_keep ) {
				$cleaned_queue[] = $item;
			}
		}

		// Update queue if we removed anything.
		if ( $count > 0 ) {
			update_option( self::QUEUE_OPTION, $cleaned_queue );
		}

		return $count;
	}
}
