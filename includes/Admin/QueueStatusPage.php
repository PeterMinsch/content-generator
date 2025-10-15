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
}
