<?php
/**
 * Reschedule Queue Jobs
 *
 * Use this to reschedule WordPress cron jobs for all pending queue items.
 */

require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Insufficient permissions.' );
}

echo '<h1>Reschedule Queue Jobs</h1>';
echo '<style>body { font-family: monospace; padding: 20px; }</style>';

// Get queue
$queue = get_option( 'seo_generation_queue', array() );

if ( empty( $queue ) ) {
	echo '<p>No items in queue.</p>';
	exit;
}

echo '<p>Found ' . count( $queue ) . ' items in queue.</p>';

// Handle reschedule action
if ( isset( $_GET['action'] ) && $_GET['action'] === 'reschedule' && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'reschedule_queue' ) ) {
	echo '<h2>Rescheduling Jobs...</h2>';

	$rescheduled = 0;
	$skipped = 0;
	$now = time();

	foreach ( $queue as $item ) {
		$post_id = $item['post_id'];
		$status = $item['status'];
		$scheduled_time = isset( $item['scheduled_time'] ) ? $item['scheduled_time'] : $now;

		// Only reschedule pending or processing items
		if ( $status === 'pending' || $status === 'processing' ) {
			// Clear any existing scheduled event for this post
			wp_clear_scheduled_hook( 'seo_generate_queued_page', array( $post_id ) );

			// Reschedule it
			// If the scheduled time has passed, schedule it for "now" (actually 5 seconds from now to stagger)
			$new_time = $scheduled_time;
			if ( $scheduled_time < $now ) {
				$new_time = $now + $rescheduled * 5; // Stagger by 5 seconds each
			}

			wp_schedule_single_event( $new_time, 'seo_generate_queued_page', array( $post_id ) );

			echo "<p style='color: green;'>✅ Rescheduled post {$post_id} for " . date( 'Y-m-d H:i:s', $new_time ) . "</p>";
			$rescheduled++;
		} else {
			echo "<p style='color: gray;'>⏭️ Skipped post {$post_id} (status: {$status})</p>";
			$skipped++;
		}
	}

	echo "<hr>";
	echo "<h3>Summary</h3>";
	echo "<p><strong>Rescheduled:</strong> {$rescheduled} jobs</p>";
	echo "<p><strong>Skipped:</strong> {$skipped} jobs (already completed/failed)</p>";

	echo "<hr>";
	echo '<p style="background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px;">';
	echo '<strong>✅ Done! Now go trigger the cron:</strong><br>';
	echo '<a href="' . plugin_dir_url( __FILE__ ) . 'trigger-cron.php" target="_blank">Open trigger-cron.php</a>';
	echo '</p>';

} else {
	// Show summary and reschedule button
	$statuses = array(
		'pending' => 0,
		'processing' => 0,
		'completed' => 0,
		'failed' => 0,
	);

	foreach ( $queue as $item ) {
		if ( isset( $item['status'] ) && isset( $statuses[ $item['status'] ] ) ) {
			$statuses[ $item['status'] ]++;
		}
	}

	echo '<h2>Current Status</h2>';
	echo '<ul>';
	echo '<li>Pending: ' . $statuses['pending'] . '</li>';
	echo '<li>Processing: ' . $statuses['processing'] . '</li>';
	echo '<li>Completed: ' . $statuses['completed'] . '</li>';
	echo '<li>Failed: ' . $statuses['failed'] . '</li>';
	echo '</ul>';

	// Check current cron jobs
	$cron_array = _get_cron_array();
	$seo_cron_count = 0;

	foreach ( $cron_array as $timestamp => $cron ) {
		if ( isset( $cron['seo_generate_queued_page'] ) ) {
			$seo_cron_count += count( $cron['seo_generate_queued_page'] );
		}
	}

	echo '<h2>WordPress Cron Status</h2>';
	echo '<p><strong>Scheduled Cron Jobs:</strong> ' . $seo_cron_count . '</p>';

	if ( $seo_cron_count === 0 ) {
		echo '<p style="background: #fff3cd; padding: 15px; border: 1px solid #ffc107; border-radius: 4px;">';
		echo '⚠️ <strong>No cron jobs scheduled!</strong> This is why your queue isn\'t processing.';
		echo '</p>';
	}

	echo '<hr>';

	$total_to_reschedule = $statuses['pending'] + $statuses['processing'];

	if ( $total_to_reschedule > 0 ) {
		$reschedule_url = add_query_arg(
			array(
				'action'   => 'reschedule',
				'_wpnonce' => wp_create_nonce( 'reschedule_queue' ),
			)
		);

		echo '<p><a href="' . esc_url( $reschedule_url ) . '" class="button button-primary" style="background: #00a32a; border-color: #00a32a; color: white; padding: 10px 20px; text-decoration: none; display: inline-block; border-radius: 3px; font-size: 16px;">';
		echo '🔄 Reschedule ' . $total_to_reschedule . ' Jobs';
		echo '</a></p>';

		echo '<p><em>This will recreate WordPress cron events for all pending and processing jobs.</em></p>';
	} else {
		echo '<p>No jobs need rescheduling.</p>';
	}
}

echo '<hr>';
echo '<p><a href="' . admin_url( 'admin.php?page=seo-queue-status' ) . '">← Back to Queue Status</a></p>';
