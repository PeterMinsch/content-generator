<?php
/**
 * Quick Queue Diagnostic
 *
 * Visit this in browser to see what's happening with the queue
 */

require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Insufficient permissions.' );
}

echo '<h1>Queue Diagnostic</h1>';
echo '<style>body { font-family: monospace; } table { border-collapse: collapse; } td, th { border: 1px solid #ccc; padding: 8px; }</style>';

// 1. Check if queue is paused
$is_paused = get_option( 'seo_queue_paused', false );
echo '<h2>Queue Status</h2>';
echo '<p><strong>Paused:</strong> ' . ( $is_paused ? 'YES ⚠️' : 'NO ✅' ) . '</p>';

// 2. Check queue data
$queue = get_option( 'seo_generation_queue', array() );
echo '<h2>Queue Items: ' . count( $queue ) . '</h2>';

if ( ! empty( $queue ) ) {
	echo '<table>';
	echo '<tr><th>Post ID</th><th>Status</th><th>Scheduled Time</th><th>Is Ready?</th></tr>';

	$now = time();
	foreach ( array_slice( $queue, 0, 10 ) as $item ) {
		$is_ready = isset( $item['scheduled_time'] ) && $item['scheduled_time'] <= $now;
		echo '<tr>';
		echo '<td>' . $item['post_id'] . '</td>';
		echo '<td>' . $item['status'] . '</td>';
		echo '<td>' . ( isset( $item['scheduled_time'] ) ? date( 'Y-m-d H:i:s', $item['scheduled_time'] ) : 'N/A' ) . '</td>';
		echo '<td>' . ( $is_ready ? 'YES ✅' : 'NO (waiting)' ) . '</td>';
		echo '</tr>';
	}

	echo '</table>';
	echo '<p><em>Showing first 10 of ' . count( $queue ) . ' items</em></p>';
}

// 3. Check scheduled cron events
$cron_array = _get_cron_array();
$seo_cron_count = 0;

echo '<h2>Scheduled Cron Events</h2>';

foreach ( $cron_array as $timestamp => $cron ) {
	if ( isset( $cron['seo_generate_queued_page'] ) ) {
		$seo_cron_count += count( $cron['seo_generate_queued_page'] );
	}
}

echo '<p><strong>Total SEO Generation Cron Jobs:</strong> ' . $seo_cron_count . '</p>';

if ( $seo_cron_count === 0 ) {
	echo '<p style="color: red; font-weight: bold;">⚠️ NO CRON JOBS FOUND!</p>';
	echo '<p>This means the jobs were never scheduled or were cleared.</p>';
}

// 4. Check if action is registered
$has_action = has_action( 'seo_generate_queued_page' );
echo '<h2>WordPress Action Hook</h2>';
echo '<p><strong>seo_generate_queued_page hook registered:</strong> ' . ( $has_action ? 'YES ✅' : 'NO ⚠️' ) . '</p>';

// 5. Test manual trigger
echo '<hr>';
echo '<h2>Manual Test</h2>';

if ( isset( $_GET['test_post_id'] ) ) {
	$test_post_id = intval( $_GET['test_post_id'] );
	echo '<p>Attempting to trigger generation for post ' . $test_post_id . '...</p>';

	error_log( "=== MANUAL TEST TRIGGER for post {$test_post_id} ===" );

	do_action( 'seo_generate_queued_page', $test_post_id );

	echo '<p><strong>Action triggered!</strong> Check debug.log for errors.</p>';
	echo '<p><a href="' . admin_url( 'post.php?post=' . $test_post_id . '&action=edit' ) . '">View Post</a></p>';
} else {
	if ( ! empty( $queue ) ) {
		$first_pending = null;
		foreach ( $queue as $item ) {
			if ( $item['status'] === 'pending' ) {
				$first_pending = $item;
				break;
			}
		}

		if ( $first_pending ) {
			$test_url = add_query_arg( 'test_post_id', $first_pending['post_id'] );
			echo '<p><a href="' . esc_url( $test_url ) . '" class="button button-primary">Test Generate First Pending Post (ID: ' . $first_pending['post_id'] . ')</a></p>';
		}
	}
}

echo '<hr>';
echo '<p><a href="' . admin_url( 'admin.php?page=seo-queue-status' ) . '">← Back to Queue Status</a></p>';
