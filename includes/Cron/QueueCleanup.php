<?php
/**
 * Queue Cleanup Cron Job
 *
 * Automatically removes old completed and failed queue items.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Cron;

defined( 'ABSPATH' ) || exit;

/**
 * Scheduled job to clean up old queue items.
 */
class QueueCleanup {
	/**
	 * Cron hook name.
	 */
	const HOOK_NAME = 'seo_generator_queue_cleanup';

	/**
	 * Maximum age for completed/failed queue items (7 days).
	 */
	const MAX_AGE_DAYS = 7;

	/**
	 * Register cron job.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( self::HOOK_NAME, array( $this, 'cleanupOldQueueItems' ) );

		// Schedule if not already scheduled.
		if ( ! wp_next_scheduled( self::HOOK_NAME ) ) {
			wp_schedule_event( time(), 'daily', self::HOOK_NAME );
		}
	}

	/**
	 * Remove old completed and failed queue items.
	 *
	 * @return void
	 */
	public function cleanupOldQueueItems(): void {
		$queue = get_option( 'seo_generation_queue', array() );

		if ( empty( $queue ) ) {
			return;
		}

		$cutoff_time = strtotime( '-' . self::MAX_AGE_DAYS . ' days' );
		$removed     = 0;
		$cleaned_queue = array();

		foreach ( $queue as $item ) {
			$should_remove = false;

			// Remove completed items older than MAX_AGE_DAYS.
			if ( isset( $item['status'] ) && 'completed' === $item['status'] ) {
				$updated_at = isset( $item['updated_at'] ) ? strtotime( $item['updated_at'] ) : 0;
				if ( $updated_at > 0 && $updated_at < $cutoff_time ) {
					$should_remove = true;
				}
			}

			// Remove failed items older than MAX_AGE_DAYS.
			if ( isset( $item['status'] ) && 'failed' === $item['status'] ) {
				$updated_at = isset( $item['updated_at'] ) ? strtotime( $item['updated_at'] ) : 0;
				if ( $updated_at > 0 && $updated_at < $cutoff_time ) {
					$should_remove = true;
				}
			}

			if ( $should_remove ) {
				$removed++;
			} else {
				$cleaned_queue[] = $item;
			}
		}

		if ( $removed > 0 ) {
			update_option( 'seo_generation_queue', $cleaned_queue );
			error_log( "[SEO Generator] Queue cleanup: Removed {$removed} old items (older than " . self::MAX_AGE_DAYS . " days)" );
		}
	}

	/**
	 * Unschedule the cron job (called on plugin deactivation).
	 *
	 * @return void
	 */
	public static function unschedule(): void {
		$timestamp = wp_next_scheduled( self::HOOK_NAME );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK_NAME );
		}
	}
}
