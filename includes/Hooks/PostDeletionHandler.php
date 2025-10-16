<?php
/**
 * Post Deletion Handler
 *
 * Handles cleanup when SEO pages are deleted.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Hooks;

use SEOGenerator\Services\GenerationQueue;

defined( 'ABSPATH' ) || exit;

/**
 * Handles cleanup when posts are deleted to prevent orphaned queue items and cron events.
 */
class PostDeletionHandler {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// Handle permanent deletion.
		add_action( 'before_delete_post', array( $this, 'cleanupQueueOnDelete' ), 10, 2 );

		// Handle trash action (when user clicks "Move to Trash").
		add_action( 'wp_trash_post', array( $this, 'cleanupQueueOnTrash' ), 10, 1 );
	}

	/**
	 * Clean up queue and cron events when a post is deleted.
	 *
	 * @param int      $post_id Post ID being deleted.
	 * @param \WP_Post $post    Post object being deleted.
	 * @return void
	 */
	public function cleanupQueueOnDelete( int $post_id, $post ): void {
		// Only handle seo-page post type.
		if ( ! $post || 'seo-page' !== $post->post_type ) {
			return;
		}

		// Check if this post is in the generation queue.
		$queue_service = new GenerationQueue();
		$queue         = get_option( 'seo_generation_queue', array() );
		$was_queued    = false;

		foreach ( $queue as $item ) {
			if ( isset( $item['post_id'] ) && $item['post_id'] === $post_id ) {
				$was_queued = true;

				// Cancel any scheduled cron event for this post.
				if ( 'pending' === $item['status'] ) {
					$timestamp = wp_next_scheduled( 'seo_generate_queued_page', array( $post_id ) );
					if ( $timestamp ) {
						wp_unschedule_event( $timestamp, 'seo_generate_queued_page', array( $post_id ) );
						error_log( "[SEO Generator] Cancelled scheduled generation for deleted post {$post_id}" );
					}
				}

				break;
			}
		}

		if ( $was_queued ) {
			// Remove from queue.
			$queue_service->removeJob( $post_id );
			error_log( "[SEO Generator] Removed post {$post_id} from generation queue (post deleted)" );
		}
	}

	/**
	 * Clean up queue and cron events when a post is trashed.
	 *
	 * @param int $post_id Post ID being trashed.
	 * @return void
	 */
	public function cleanupQueueOnTrash( int $post_id ): void {
		// Verify post type.
		$post = get_post( $post_id );
		if ( ! $post || 'seo-page' !== $post->post_type ) {
			return;
		}

		// Check if this post is in the generation queue.
		$queue_service = new GenerationQueue();
		$queue         = get_option( 'seo_generation_queue', array() );
		$was_queued    = false;

		foreach ( $queue as $item ) {
			if ( isset( $item['post_id'] ) && $item['post_id'] === $post_id ) {
				$was_queued = true;

				// Cancel any scheduled cron event for this post.
				if ( 'pending' === $item['status'] ) {
					$timestamp = wp_next_scheduled( 'seo_generate_queued_page', array( $post_id ) );
					if ( $timestamp ) {
						wp_unschedule_event( $timestamp, 'seo_generate_queued_page', array( $post_id ) );
						error_log( "[SEO Generator] Cancelled scheduled generation for trashed post {$post_id}" );
					}
				}

				break;
			}
		}

		if ( $was_queued ) {
			// Remove from queue.
			$queue_service->removeJob( $post_id );
			error_log( "[SEO Generator] Removed post {$post_id} from generation queue (post trashed)" );
		}
	}
}
