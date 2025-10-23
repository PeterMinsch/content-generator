<?php
/**
 * Queue Status Page Manager
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Admin;

use SEOGenerator\Services\GenerationQueue;

defined( 'ABSPATH' ) || exit;

/**
 * Manages the Generation Queue Status admin page functionality.
 */
class QueueStatusPage {
	/**
	 * GenerationQueue service.
	 *
	 * @var GenerationQueue
	 */
	private $queue;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->queue = new GenerationQueue();
	}

	/**
	 * Register AJAX hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_ajax_seo_queue_status', array( $this, 'handleQueueStatus' ) );
		add_action( 'wp_ajax_seo_queue_pause', array( $this, 'handleQueuePause' ) );
		add_action( 'wp_ajax_seo_queue_resume', array( $this, 'handleQueueResume' ) );
		add_action( 'wp_ajax_seo_queue_clear', array( $this, 'handleQueueClear' ) );
		add_action( 'wp_ajax_seo_queue_cancel_job', array( $this, 'handleCancelJob' ) );
		add_action( 'wp_ajax_seo_process_queue_cron', array( $this, 'handleProcessQueueCron' ) );
	}

	/**
	 * Render the queue status page.
	 *
	 * @return void
	 */
	public function render(): void {
		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'seo-generator' ) );
		}

		// Get queue data.
		$stats     = $this->queue->getQueueStats();
		$queue     = $this->queue->getQueuedPosts();
		$estimated = $this->queue->getEstimatedCompletion();
		$is_paused = $this->queue->isPaused();

		// Load template.
		$template_path = plugin_dir_path( dirname( __DIR__ ) ) . 'templates/admin/queue-status.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			echo '<div class="wrap">';
			echo '<h1>' . esc_html__( 'Generation Queue', 'seo-generator' ) . '</h1>';
			echo '<p>' . esc_html__( 'Template file not found.', 'seo-generator' ) . '</p>';
			echo '</div>';
		}
	}

	/**
	 * Handle AJAX request for queue status.
	 *
	 * @return void
	 */
	public function handleQueueStatus(): void {
		// Verify nonce.
		check_ajax_referer( 'seo_queue_nonce', 'nonce' );

		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have sufficient permissions.', 'seo-generator' ) )
			);
		}

		$stats       = $this->queue->getQueueStats();
		$estimated   = $this->queue->getEstimatedCompletion();
		$is_paused   = $this->queue->isPaused();
		$queue_items = $this->queue->getQueuedPosts();

		// Format queue items for frontend.
		$formatted_items = array();
		foreach ( $queue_items as $item ) {
			$formatted_items[] = array(
				'post_id'        => $item['post_id'],
				'status'         => $item['status'],
				'scheduled_time' => isset( $item['scheduled_time'] ) ? date( 'Y-m-d H:i:s', $item['scheduled_time'] ) : '',
				'updated_at'     => $item['updated_at'] ?? '',
			);
		}

		wp_send_json_success(
			array(
				'stats'                => $stats,
				'estimated_completion' => $estimated,
				'is_paused'            => $is_paused,
				'queue_items'          => $formatted_items,
			)
		);
	}

	/**
	 * Handle AJAX request to pause queue.
	 *
	 * @return void
	 */
	public function handleQueuePause(): void {
		// Verify nonce.
		check_ajax_referer( 'seo_queue_nonce', 'nonce' );

		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have sufficient permissions.', 'seo-generator' ) )
			);
		}

		$this->queue->pauseQueue();

		wp_send_json_success(
			array( 'message' => __( 'Queue paused successfully.', 'seo-generator' ) )
		);
	}

	/**
	 * Handle AJAX request to resume queue.
	 *
	 * @return void
	 */
	public function handleQueueResume(): void {
		// Verify nonce.
		check_ajax_referer( 'seo_queue_nonce', 'nonce' );

		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have sufficient permissions.', 'seo-generator' ) )
			);
		}

		$this->queue->resumeQueue();

		wp_send_json_success(
			array( 'message' => __( 'Queue resumed successfully.', 'seo-generator' ) )
		);
	}

	/**
	 * Handle AJAX request to clear queue.
	 *
	 * @return void
	 */
	public function handleQueueClear(): void {
		// Verify nonce.
		check_ajax_referer( 'seo_queue_nonce', 'nonce' );

		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have sufficient permissions.', 'seo-generator' ) )
			);
		}

		$this->queue->clearQueue();

		wp_send_json_success(
			array( 'message' => __( 'Queue cleared successfully.', 'seo-generator' ) )
		);
	}

	/**
	 * Handle AJAX request to cancel individual job.
	 *
	 * @return void
	 */
	public function handleCancelJob(): void {
		// Verify nonce.
		check_ajax_referer( 'seo_queue_nonce', 'nonce' );

		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have sufficient permissions.', 'seo-generator' ) )
			);
		}

		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid post ID.', 'seo-generator' ) )
			);
		}

		$removed = $this->queue->removeJob( $post_id );

		if ( $removed ) {
			wp_send_json_success(
				array( 'message' => __( 'Job cancelled successfully.', 'seo-generator' ) )
			);
		} else {
			wp_send_json_error(
				array( 'message' => __( 'Failed to cancel job.', 'seo-generator' ) )
			);
		}
	}

	/**
	 * Handle AJAX request to manually process queue cron jobs.
	 *
	 * This spawns WordPress Cron to process ready jobs asynchronously in the background.
	 * Useful in local development environments where WP-Cron doesn't run automatically.
	 *
	 * @return void
	 */
	public function handleProcessQueueCron(): void {
		// Verify nonce.
		check_ajax_referer( 'seo_queue_nonce', 'nonce' );

		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have sufficient permissions.', 'seo-generator' ) )
			);
		}

		// Check if queue is paused.
		if ( $this->queue->isPaused() ) {
			wp_send_json_error(
				array( 'message' => __( 'Queue is paused. Please resume the queue first.', 'seo-generator' ) )
			);
		}

		// Get all pending jobs.
		$pending_jobs = $this->queue->getQueuedPosts( 'pending' );

		if ( empty( $pending_jobs ) ) {
			wp_send_json_success(
				array( 'message' => __( 'No pending jobs to process.', 'seo-generator' ) )
			);
		}

		// Filter jobs that are ready to process (scheduled time has passed).
		$ready_jobs   = array();
		$current_time = time();

		foreach ( $pending_jobs as $job ) {
			if ( isset( $job['scheduled_time'] ) && $job['scheduled_time'] <= $current_time ) {
				$ready_jobs[] = $job;
			}
		}

		if ( empty( $ready_jobs ) ) {
			wp_send_json_success(
				array(
					'message' => sprintf(
						/* translators: %d: number of pending jobs */
						__( 'No jobs ready to process yet. %d job(s) scheduled for later.', 'seo-generator' ),
						count( $pending_jobs )
					),
				)
			);
		}

		// Spawn WordPress Cron asynchronously to process jobs in background.
		// This makes a non-blocking HTTP request to wp-cron.php which will
		// process all due jobs according to their scheduled times.
		spawn_cron();

		// Wait a moment for the cron spawn to register.
		sleep( 1 );

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: number of ready jobs */
					__( 'WordPress Cron triggered. %d job(s) will be processed in the background. Refresh this page in 30 seconds to see progress.', 'seo-generator' ),
					count( $ready_jobs )
				),
			)
		);
	}
}
