<?php
/**
 * Queue Status Checker
 *
 * Access this file at: /wp-content/plugins/content-generator-disabled/check-queue-status.php?action=status
 *
 * Available actions:
 * - status: Show queue status
 * - trigger: Manually trigger next pending job
 * - cron: Show WordPress Cron schedule
 */

// Load WordPress.
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

// Security check.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Access denied' );
}

$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'status';

?>
<!DOCTYPE html>
<html>
<head>
	<title>Queue Status Checker</title>
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			padding: 20px;
			max-width: 1200px;
			margin: 0 auto;
		}
		h1, h2 { color: #333; }
		table {
			border-collapse: collapse;
			width: 100%;
			margin: 20px 0;
			background: white;
		}
		th, td {
			border: 1px solid #ddd;
			padding: 12px;
			text-align: left;
		}
		th {
			background-color: #0073aa;
			color: white;
		}
		tr:nth-child(even) { background-color: #f9f9f9; }
		.status-pending { color: #f0ad4e; font-weight: bold; }
		.status-processing { color: #5bc0de; font-weight: bold; }
		.status-completed { color: #5cb85c; font-weight: bold; }
		.status-failed { color: #d9534f; font-weight: bold; }
		.stats {
			display: grid;
			grid-template-columns: repeat(5, 1fr);
			gap: 15px;
			margin: 20px 0;
		}
		.stat-card {
			background: white;
			padding: 20px;
			border-radius: 8px;
			border: 1px solid #ddd;
			text-align: center;
		}
		.stat-number { font-size: 32px; font-weight: bold; color: #0073aa; }
		.stat-label { color: #666; margin-top: 5px; }
		.button {
			display: inline-block;
			background: #0073aa;
			color: white;
			padding: 10px 20px;
			text-decoration: none;
			border-radius: 4px;
			margin: 5px;
		}
		.button:hover { background: #005a87; }
		.button-danger { background: #d9534f; }
		.button-danger:hover { background: #c9302c; }
		pre {
			background: #f5f5f5;
			padding: 15px;
			border-radius: 4px;
			overflow-x: auto;
		}
		.success {
			background: #d4edda;
			color: #155724;
			padding: 15px;
			border-radius: 4px;
			margin: 20px 0;
		}
		.error {
			background: #f8d7da;
			color: #721c24;
			padding: 15px;
			border-radius: 4px;
			margin: 20px 0;
		}
	</style>
</head>
<body>
	<h1>🔄 Generation Queue Status</h1>

	<div>
		<a href="?action=status" class="button">Queue Status</a>
		<a href="?action=trigger" class="button">Trigger Next Job</a>
		<a href="?action=cron" class="button">Cron Schedule</a>
		<a href="?action=clear" class="button button-danger" onclick="return confirm('Are you sure you want to clear the entire queue?')">Clear Queue</a>
	</div>

	<?php
	if ( $action === 'status' ) {
		// Get queue.
		$queue = get_option( 'seo_generation_queue', array() );

		// Get stats.
		$stats = array(
			'total'      => count( $queue ),
			'pending'    => 0,
			'processing' => 0,
			'completed'  => 0,
			'failed'     => 0,
		);

		foreach ( $queue as $item ) {
			if ( isset( $item['status'] ) ) {
				$stats[ $item['status'] ]++;
			}
		}

		// Check if paused.
		$is_paused = get_option( 'seo_queue_paused', false );

		echo '<h2>Queue Statistics</h2>';

		if ( $is_paused ) {
			echo '<div class="error">⚠️ Queue is PAUSED. Jobs will not process until resumed.</div>';
		}

		echo '<div class="stats">';
		echo '<div class="stat-card"><div class="stat-number">' . $stats['total'] . '</div><div class="stat-label">Total Jobs</div></div>';
		echo '<div class="stat-card"><div class="stat-number status-pending">' . $stats['pending'] . '</div><div class="stat-label">Pending</div></div>';
		echo '<div class="stat-card"><div class="stat-number status-processing">' . $stats['processing'] . '</div><div class="stat-label">Processing</div></div>';
		echo '<div class="stat-card"><div class="stat-number status-completed">' . $stats['completed'] . '</div><div class="stat-label">Completed</div></div>';
		echo '<div class="stat-card"><div class="stat-number status-failed">' . $stats['failed'] . '</div><div class="stat-label">Failed</div></div>';
		echo '</div>';

		if ( ! empty( $queue ) ) {
			echo '<h2>Queued Posts</h2>';
			echo '<table>';
			echo '<tr>';
			echo '<th>Post ID</th>';
			echo '<th>Post Title</th>';
			echo '<th>Status</th>';
			echo '<th>Scheduled Time</th>';
			echo '<th>Queued At</th>';
			echo '<th>Time Until Run</th>';
			echo '<th>Error</th>';
			echo '</tr>';

			$now = time();

			foreach ( $queue as $item ) {
				$post_id = $item['post_id'];
				$post_title = get_the_title( $post_id );
				$status = $item['status'];
				$scheduled_time = isset( $item['scheduled_time'] ) ? $item['scheduled_time'] : 0;
				$queued_at = isset( $item['queued_at'] ) ? $item['queued_at'] : '';
				$error = isset( $item['error'] ) ? $item['error'] : '';

				$time_until = '';
				if ( $scheduled_time > 0 && $status === 'pending' ) {
					$diff = $scheduled_time - $now;
					if ( $diff > 0 ) {
						$minutes = floor( $diff / 60 );
						$seconds = $diff % 60;
						$time_until = "{$minutes}m {$seconds}s";
					} else {
						$time_until = 'READY NOW';
					}
				}

				echo '<tr>';
				echo '<td><a href="' . get_edit_post_link( $post_id ) . '" target="_blank">' . $post_id . '</a></td>';
				echo '<td>' . esc_html( $post_title ) . '</td>';
				echo '<td class="status-' . $status . '">' . strtoupper( $status ) . '</td>';
				echo '<td>' . ( $scheduled_time > 0 ? date( 'Y-m-d H:i:s', $scheduled_time ) : 'N/A' ) . '</td>';
				echo '<td>' . esc_html( $queued_at ) . '</td>';
				echo '<td><strong>' . esc_html( $time_until ) . '</strong></td>';
				echo '<td>' . ( $error ? '<span style="color: red;">' . esc_html( $error ) . '</span>' : '-' ) . '</td>';
				echo '</tr>';
			}

			echo '</table>';
		} else {
			echo '<p>No posts in queue.</p>';
		}

	} elseif ( $action === 'trigger' ) {
		echo '<h2>Manual Trigger</h2>';

		// Find next pending job.
		$queue = get_option( 'seo_generation_queue', array() );
		$next_job = null;

		foreach ( $queue as $item ) {
			if ( $item['status'] === 'pending' ) {
				$next_job = $item;
				break;
			}
		}

		if ( $next_job ) {
			$post_id = $next_job['post_id'];
			$post_title = get_the_title( $post_id );

			echo '<p>Triggering generation for: <strong>' . esc_html( $post_title ) . '</strong> (ID: ' . $post_id . ')</p>';

			// Trigger the cron action directly.
			do_action( 'seo_generate_queued_page', $post_id );

			echo '<div class="success">✅ Job triggered! Check the post to see if content was generated.</div>';
			echo '<p><a href="' . get_edit_post_link( $post_id ) . '" target="_blank" class="button">View Post</a></p>';
			echo '<p><a href="?action=status" class="button">Back to Queue Status</a></p>';
		} else {
			echo '<div class="error">No pending jobs in queue.</div>';
			echo '<p><a href="?action=status" class="button">Back to Queue Status</a></p>';
		}

	} elseif ( $action === 'cron' ) {
		echo '<h2>WordPress Cron Schedule</h2>';

		// Get all scheduled events for our action.
		$cron_array = _get_cron_array();
		$our_events = array();

		if ( is_array( $cron_array ) ) {
			foreach ( $cron_array as $timestamp => $cron ) {
				if ( isset( $cron['seo_generate_queued_page'] ) ) {
					foreach ( $cron['seo_generate_queued_page'] as $event ) {
						$our_events[] = array(
							'timestamp' => $timestamp,
							'post_id'   => isset( $event['args'][0] ) ? $event['args'][0] : 'N/A',
						);
					}
				}
			}
		}

		if ( ! empty( $our_events ) ) {
			echo '<p>Found <strong>' . count( $our_events ) . '</strong> scheduled generation events.</p>';
			echo '<table>';
			echo '<tr><th>Post ID</th><th>Scheduled Time</th><th>Time Until Run</th></tr>';

			$now = time();
			foreach ( $our_events as $event ) {
				$diff = $event['timestamp'] - $now;
				$time_until = '';
				if ( $diff > 0 ) {
					$minutes = floor( $diff / 60 );
					$seconds = $diff % 60;
					$time_until = "{$minutes}m {$seconds}s";
				} else {
					$time_until = 'OVERDUE (should run soon)';
				}

				echo '<tr>';
				echo '<td>' . $event['post_id'] . '</td>';
				echo '<td>' . date( 'Y-m-d H:i:s', $event['timestamp'] ) . '</td>';
				echo '<td><strong>' . $time_until . '</strong></td>';
				echo '</tr>';
			}
			echo '</table>';
		} else {
			echo '<p>No scheduled generation events found.</p>';
		}

		// Check if WP Cron is disabled.
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			echo '<div class="error">⚠️ WordPress Cron is DISABLED (DISABLE_WP_CRON = true). You need to set up a system cron job to run wp-cron.php</div>';
		} else {
			echo '<div class="success">✅ WordPress Cron is enabled.</div>';
		}

		echo '<p><a href="?action=status" class="button">Back to Queue Status</a></p>';

	} elseif ( $action === 'clear' ) {
		echo '<h2>Clear Queue</h2>';

		// Get queue service.
		$queue_service = new \SEOGenerator\Services\GenerationQueue();
		$queue_service->clearQueue();

		echo '<div class="success">✅ Queue cleared! All pending jobs have been removed and scheduled cron events cancelled.</div>';
		echo '<p><a href="?action=status" class="button">Back to Queue Status</a></p>';
	}
	?>

	<hr style="margin: 40px 0;">

	<h2>Debug Information</h2>
	<p><strong>Current Time:</strong> <?php echo date( 'Y-m-d H:i:s', time() ); ?></p>
	<p><strong>WordPress Cron:</strong> <?php echo defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? '❌ DISABLED' : '✅ ENABLED'; ?></p>
	<p><strong>Queue Paused:</strong> <?php echo get_option( 'seo_queue_paused', false ) ? '⚠️ YES' : '✅ NO'; ?></p>
	<p><strong>Last Generation Time:</strong> <?php
		$last_gen = get_option( 'seo_last_generation_time', 0 );
		echo $last_gen > 0 ? date( 'Y-m-d H:i:s', $last_gen ) : 'Never';
	?></p>
</body>
</html>
