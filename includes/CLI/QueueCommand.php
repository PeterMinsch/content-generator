<?php
/**
 * WP-CLI Queue Management Commands
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\CLI;

use SEOGenerator\Services\GenerationQueue;
use SEOGenerator\Services\GenerationService;

defined( 'ABSPATH' ) || exit;

/**
 * Manage the SEO content generation queue.
 *
 * ## EXAMPLES
 *
 *     # List all queued jobs
 *     $ wp seo-generator queue list
 *
 *     # Process the next pending job
 *     $ wp seo-generator queue process
 *
 *     # Clear all pending jobs
 *     $ wp seo-generator queue clear
 *
 *     # Show queue statistics
 *     $ wp seo-generator queue status
 */
class QueueCommand {
	/**
	 * List all queued jobs.
	 *
	 * ## OPTIONS
	 *
	 * [--status=<status>]
	 * : Filter jobs by status (pending, processing, completed, failed)
	 *
	 * [--format=<format>]
	 * : Render output in a particular format
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # List all queued jobs
	 *     $ wp seo-generator queue list
	 *
	 *     # List only pending jobs
	 *     $ wp seo-generator queue list --status=pending
	 *
	 *     # Output as JSON
	 *     $ wp seo-generator queue list --format=json
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function list( $args, $assoc_args ) {
		$queue  = new GenerationQueue();
		$status = isset( $assoc_args['status'] ) ? $assoc_args['status'] : null;

		// Get queued posts (optionally filtered by status).
		$posts = $status ? $queue->getQueuedPosts( $status ) : $queue->getQueuedPosts();

		if ( empty( $posts ) ) {
			\WP_CLI::success( 'Queue is empty' );
			return;
		}

		// Format data for output.
		$table = array_map(
			function ( $item ) {
				$post_title = get_the_title( $item['post_id'] );
				if ( empty( $post_title ) ) {
					$post_title = sprintf( '(Post #%d)', $item['post_id'] );
				}

				return array(
					'Post ID'   => $item['post_id'],
					'Title'     => $post_title,
					'Scheduled' => gmdate( 'Y-m-d H:i:s', $item['scheduled_time'] ),
					'Status'    => $item['status'],
					'Queued At' => isset( $item['queued_at'] ) ? $item['queued_at'] : '',
				);
			},
			$posts
		);

		// Output table.
		\WP_CLI\Utils\format_items(
			isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table',
			$table,
			array( 'Post ID', 'Title', 'Scheduled', 'Status', 'Queued At' )
		);
	}

	/**
	 * Process the next pending job in the queue.
	 *
	 * ## EXAMPLES
	 *
	 *     # Process next pending job
	 *     $ wp seo-generator queue process
	 *     Processing post 123...
	 *     Success: Post 123 processed
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function process( $args, $assoc_args ) {
		$queue   = new GenerationQueue();
		$pending = $queue->getQueuedPosts( 'pending' );

		if ( empty( $pending ) ) {
			\WP_CLI::success( 'No pending jobs' );
			return;
		}

		// Get first pending job.
		$next    = array_shift( $pending );
		$post_id = $next['post_id'];

		\WP_CLI::log( "Processing post {$post_id}..." );

		// Process the queued page.
		$service = new GenerationService();
		$service->processQueuedPage( $post_id );

		// Check if successful.
		$updated = $queue->getQueuedPosts();
		$status  = null;

		foreach ( $updated as $item ) {
			if ( $item['post_id'] === $post_id ) {
				$status = $item['status'];
				break;
			}
		}

		if ( $status === 'completed' ) {
			\WP_CLI::success( "Post {$post_id} processed successfully" );
		} elseif ( $status === 'failed' ) {
			\WP_CLI::warning( "Post {$post_id} processing failed" );
		} else {
			\WP_CLI::log( "Post {$post_id} status: {$status}" );
		}
	}

	/**
	 * Clear all pending jobs from the queue.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip confirmation prompt
	 *
	 * ## EXAMPLES
	 *
	 *     # Clear queue with confirmation
	 *     $ wp seo-generator queue clear
	 *
	 *     # Clear queue without confirmation
	 *     $ wp seo-generator queue clear --yes
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function clear( $args, $assoc_args ) {
		$queue = new GenerationQueue();
		$stats = $queue->getQueueStats();

		if ( $stats['total'] === 0 ) {
			\WP_CLI::success( 'Queue is already empty' );
			return;
		}

		// Confirm before clearing.
		if ( ! isset( $assoc_args['yes'] ) ) {
			\WP_CLI::confirm(
				sprintf(
					'Are you sure you want to clear %d jobs from the queue?',
					$stats['total']
				)
			);
		}

		$queue->clearQueue();
		\WP_CLI::success( 'Queue cleared' );
	}

	/**
	 * Show queue statistics.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Render output in a particular format
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Show queue statistics
	 *     $ wp seo-generator queue status
	 *
	 *     # Output as JSON
	 *     $ wp seo-generator queue status --format=json
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function status( $args, $assoc_args ) {
		$queue     = new GenerationQueue();
		$stats     = $queue->getQueueStats();
		$estimated = $queue->getEstimatedCompletion();
		$is_paused = $queue->isPaused();

		// Format for output.
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';

		if ( $format === 'json' || $format === 'yaml' ) {
			$data = array(
				'pending'              => $stats['pending'],
				'processing'           => $stats['processing'],
				'completed'            => $stats['completed'],
				'failed'               => $stats['failed'],
				'total'                => $stats['total'],
				'paused'               => $is_paused,
				'estimated_completion' => $estimated,
			);

			\WP_CLI\Utils\format_items( $format, array( $data ), array_keys( $data ) );
		} else {
			// Table format.
			\WP_CLI::line( 'Queue Status:' );
			\WP_CLI::line( '  Pending:     ' . $stats['pending'] );
			\WP_CLI::line( '  Processing:  ' . $stats['processing'] );
			\WP_CLI::line( '  Completed:   ' . $stats['completed'] );
			\WP_CLI::line( '  Failed:      ' . $stats['failed'] );
			\WP_CLI::line( '  Total:       ' . $stats['total'] );
			\WP_CLI::line( '  Paused:      ' . ( $is_paused ? 'Yes' : 'No' ) );

			if ( $estimated ) {
				\WP_CLI::line( '  Estimated completion: ' . $estimated );
			}
		}
	}

	/**
	 * Pause the queue.
	 *
	 * ## EXAMPLES
	 *
	 *     # Pause queue
	 *     $ wp seo-generator queue pause
	 *     Success: Queue paused
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function pause( $args, $assoc_args ) {
		$queue = new GenerationQueue();

		if ( $queue->isPaused() ) {
			\WP_CLI::warning( 'Queue is already paused' );
			return;
		}

		$queue->pauseQueue();
		\WP_CLI::success( 'Queue paused' );
	}

	/**
	 * Resume the queue.
	 *
	 * ## EXAMPLES
	 *
	 *     # Resume queue
	 *     $ wp seo-generator queue resume
	 *     Success: Queue resumed
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function resume( $args, $assoc_args ) {
		$queue = new GenerationQueue();

		if ( ! $queue->isPaused() ) {
			\WP_CLI::warning( 'Queue is not paused' );
			return;
		}

		$queue->resumeQueue();
		\WP_CLI::success( 'Queue resumed' );
	}
}

// Register commands if WP-CLI is available.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	\WP_CLI::add_command( 'seo-generator queue', QueueCommand::class );
}
