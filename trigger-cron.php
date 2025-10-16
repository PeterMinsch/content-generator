<?php
/**
 * Manual Cron Trigger
 *
 * Visit this file directly in your browser to manually trigger WordPress cron:
 * http://contentgeneratorwpplugin.local/wp-content/plugins/content-generator-disabled/trigger-cron.php
 *
 * This is useful for local development where WordPress pseudo-cron doesn't run automatically.
 * DELETE THIS FILE after using it or when deploying to production!
 */

// Load WordPress.
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

// Check if user is admin.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Insufficient permissions.' );
}

echo '<h1>Manual Cron Trigger</h1>';

// Handle cleanup action.
if ( isset( $_GET['action'] ) && $_GET['action'] === 'cleanup_orphaned' && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'cleanup_orphaned_cron' ) ) {
	echo '<div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 4px;">';
	echo '<h3>Cleaning up orphaned cron jobs...</h3>';

	$cron_array = _get_cron_array();
	$cleaned = 0;
	$queue = get_option( 'seo_generation_queue', array() );
	$valid_post_ids = array_column( $queue, 'post_id' );

	foreach ( $cron_array as $timestamp => $cron ) {
		if ( isset( $cron['seo_generate_queued_page'] ) ) {
			foreach ( $cron['seo_generate_queued_page'] as $key => $event ) {
				$post_id = $event['args'][0];

				// Check if post exists and is in queue.
				$post = get_post( $post_id );
				$in_queue = in_array( $post_id, $valid_post_ids, true );

				if ( ! $post || ! $in_queue ) {
					// Orphaned cron job - remove it.
					wp_unschedule_event( $timestamp, 'seo_generate_queued_page', $event['args'] );
					$cleaned++;
					echo "<p>✅ Removed orphaned cron job for post {$post_id}</p>";
				}
			}
		}
	}

	echo "<p><strong>Cleaned {$cleaned} orphaned cron jobs.</strong></p>";
	echo '</div>';
}

echo '<p>Checking for pending cron events...</p>';

// Get all scheduled cron events.
$cron_array = _get_cron_array();
$found = 0;
$triggered = 0;
$orphaned = 0;

// Get queue data for comparison.
$queue = get_option( 'seo_generation_queue', array() );
$valid_post_ids = array_column( $queue, 'post_id' );

echo '<h2>Scheduled Generation Jobs:</h2>';
echo '<table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%;">';
echo '<tr><th>Post ID</th><th>Post Exists?</th><th>In Queue?</th><th>Scheduled Time</th><th>Status</th></tr>';

foreach ( $cron_array as $timestamp => $cron ) {
	if ( isset( $cron['seo_generate_queued_page'] ) ) {
		foreach ( $cron['seo_generate_queued_page'] as $key => $event ) {
			$found++;
			$post_id = $event['args'][0];
			$scheduled_time = date( 'Y-m-d H:i:s', $timestamp );
			$is_ready = time() >= $timestamp;

			// Check if post exists.
			$post = get_post( $post_id );
			$post_exists = $post ? 'Yes' : 'No (deleted)';
			$post_exists_color = $post ? 'green' : 'red';

			// Check if in queue.
			$in_queue = in_array( $post_id, $valid_post_ids, true );
			$in_queue_text = $in_queue ? 'Yes' : 'No';
			$in_queue_color = $in_queue ? 'green' : 'orange';

			if ( ! $post || ! $in_queue ) {
				$orphaned++;
			}

			echo '<tr>';
			echo '<td>' . esc_html( $post_id ) . '</td>';
			echo '<td style="color: ' . $post_exists_color . ';">' . esc_html( $post_exists ) . '</td>';
			echo '<td style="color: ' . $in_queue_color . ';">' . esc_html( $in_queue_text ) . '</td>';
			echo '<td>' . esc_html( $scheduled_time ) . '</td>';

			if ( ! $post || ! $in_queue ) {
				echo '<td style="color: red; font-weight: bold;">⚠️ Orphaned (should be cleaned)</td>';
			} elseif ( $is_ready ) {
				echo '<td style="color: green; font-weight: bold;">Ready - Triggering now...</td>';

				// Trigger the event immediately.
				do_action( 'seo_generate_queued_page', $post_id );
				$triggered++;

				// Remove the scheduled event.
				wp_unschedule_event( $timestamp, 'seo_generate_queued_page', $event['args'] );
			} else {
				$wait_seconds = $timestamp - time();
				$wait_minutes = round( $wait_seconds / 60, 1 );
				echo '<td style="color: orange;">Waiting (' . esc_html( $wait_minutes ) . ' minutes)</td>';
			}
			echo '</tr>';
		}
	}
}

echo '</table>';

if ( $found === 0 ) {
	echo '<p><strong>No generation jobs found in cron.</strong></p>';
	echo '<p>This might mean:</p>';
	echo '<ul>';
	echo '<li>No posts are queued for generation</li>';
	echo '<li>The queue was cleared</li>';
	echo '<li>All jobs have already been processed</li>';
	echo '</ul>';
} else {
	echo "<p><strong>Found {$found} scheduled jobs. Triggered {$triggered} ready jobs.</strong></p>";

	if ( $orphaned > 0 ) {
		$cleanup_url = add_query_arg(
			array(
				'action'   => 'cleanup_orphaned',
				'_wpnonce' => wp_create_nonce( 'cleanup_orphaned_cron' ),
			),
			$_SERVER['REQUEST_URI']
		);
		echo '<div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;">';
		echo "<p><strong>⚠️ Warning: Found {$orphaned} orphaned cron jobs!</strong></p>";
		echo '<p>These are scheduled jobs for posts that were deleted or are no longer in the queue.</p>';
		echo '<p><a href="' . esc_url( $cleanup_url ) . '" class="button button-secondary">🧹 Clean Up Orphaned Jobs</a></p>';
		echo '</div>';
	}

	if ( $triggered > 0 ) {
		echo '<p style="color: green; font-weight: bold;">✅ Generation started! Check your posts list to see the progress.</p>';
		echo '<p>Note: Large imports may take several minutes to complete.</p>';
	}

	if ( $found > $triggered && $found - $triggered > $orphaned ) {
		$remaining = $found - $triggered - $orphaned;
		echo "<p><strong>{$remaining} valid jobs are waiting for their scheduled time.</strong></p>";
		echo '<p>Refresh this page in a few minutes to trigger the next batch.</p>';
	}
}

// Check the queue option.
echo '<hr>';
echo '<h3>Queue Data (from database):</h3>';
echo '<table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%;">';
echo '<tr><th>Post ID</th><th>Status</th><th>Scheduled Time</th><th>Queued At</th></tr>';

if ( empty( $queue ) ) {
	echo '<tr><td colspan="4">No items in queue</td></tr>';
} else {
	foreach ( $queue as $item ) {
		$post_id = isset( $item['post_id'] ) ? $item['post_id'] : 'N/A';
		$status = isset( $item['status'] ) ? $item['status'] : 'N/A';
		$scheduled_time = isset( $item['scheduled_time'] ) ? date( 'Y-m-d H:i:s', $item['scheduled_time'] ) : 'N/A';
		$queued_at = isset( $item['queued_at'] ) ? $item['queued_at'] : 'N/A';

		echo '<tr>';
		echo '<td>' . esc_html( $post_id ) . '</td>';
		echo '<td>' . esc_html( $status ) . '</td>';
		echo '<td>' . esc_html( $scheduled_time ) . '</td>';
		echo '<td>' . esc_html( $queued_at ) . '</td>';
		echo '</tr>';
	}
}

echo '</table>';

echo '<hr>';
echo '<p><strong>How WordPress Cron Works:</strong></p>';
echo '<ul>';
echo '<li>WordPress uses a "pseudo-cron" that only runs when someone visits your site</li>';
echo '<li>On local development, this means cron jobs won\'t run unless you browse pages</li>';
echo '<li>This script manually triggers any ready cron jobs</li>';
echo '<li>For production, consider using a real cron job or a plugin like WP Crontrol</li>';
echo '</ul>';

echo '<hr>';
echo '<p><a href="' . admin_url( 'admin.php?page=seo-generator-import' ) . '" class="button">← Back to Import Page</a></p>';
echo '<p><a href="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" class="button button-primary">🔄 Refresh & Check Again</a></p>';

echo '<style>body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; }</style>';
