<?php
/**
 * Clear All Generation Jobs
 *
 * Visit this file directly in your browser to clear ALL scheduled generation jobs:
 * http://contentgeneratorwpplugin.local/wp-content/plugins/content-generator-disabled/clear-all-jobs.php
 *
 * This will:
 * - Cancel all scheduled cron jobs for generation
 * - Clear the entire generation queue
 * - Give you a fresh start
 *
 * DELETE THIS FILE after using it!
 */

// Load WordPress.
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

// Check if user is admin.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Insufficient permissions.' );
}

echo '<h1>Clear All Generation Jobs</h1>';

// Confirm action.
if ( ! isset( $_GET['confirm'] ) || $_GET['confirm'] !== 'yes' ) {
	echo '<div style="background: #fff3cd; border: 1px solid #ffc107; padding: 20px; margin: 20px 0; border-radius: 4px;">';
	echo '<h2>⚠️ Are you sure?</h2>';
	echo '<p>This will clear ALL scheduled generation jobs and empty the entire queue.</p>';
	echo '<p><strong>This action cannot be undone.</strong></p>';
	echo '<p>';
	echo '<a href="' . esc_url( add_query_arg( 'confirm', 'yes' ) ) . '" class="button button-primary" style="background: #dc3232; border-color: #dc3232;">Yes, Clear Everything</a> ';
	echo '<a href="' . admin_url( 'admin.php?page=seo-generator-import' ) . '" class="button">Cancel</a>';
	echo '</p>';
	echo '</div>';

	// Show current status.
	$cron_array = _get_cron_array();
	$job_count = 0;

	foreach ( $cron_array as $timestamp => $cron ) {
		if ( isset( $cron['seo_generate_queued_page'] ) ) {
			$job_count += count( $cron['seo_generate_queued_page'] );
		}
	}

	$queue = get_option( 'seo_generation_queue', array() );
	$queue_count = count( $queue );

	echo '<h3>Current Status:</h3>';
	echo '<ul>';
	echo '<li><strong>Scheduled cron jobs:</strong> ' . $job_count . '</li>';
	echo '<li><strong>Items in queue:</strong> ' . $queue_count . '</li>';
	echo '</ul>';

	echo '<style>body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; }</style>';
	exit;
}

// User confirmed - proceed with clearing.
echo '<h2>Clearing all generation jobs...</h2>';

// 1. Clear all scheduled cron events.
$cron_array = _get_cron_array();
$cleared_cron = 0;

foreach ( $cron_array as $timestamp => $cron ) {
	if ( isset( $cron['seo_generate_queued_page'] ) ) {
		foreach ( $cron['seo_generate_queued_page'] as $key => $event ) {
			wp_unschedule_event( $timestamp, 'seo_generate_queued_page', $event['args'] );
			$cleared_cron++;
		}
	}
}

echo '<p>✅ Cleared <strong>' . $cleared_cron . '</strong> scheduled cron jobs.</p>';

// 2. Clear the generation queue.
$queue = get_option( 'seo_generation_queue', array() );
$queue_count = count( $queue );

delete_option( 'seo_generation_queue' );
echo '<p>✅ Cleared <strong>' . $queue_count . '</strong> items from the generation queue.</p>';

// 3. Resume queue (in case it was paused).
delete_option( 'seo_queue_paused' );
echo '<p>✅ Queue resumed (if it was paused).</p>';

echo '<hr>';
echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; margin: 20px 0; border-radius: 4px; color: #155724;">';
echo '<h2 style="margin-top: 0; color: #155724;">✅ All Clear!</h2>';
echo '<p>All scheduled generation jobs have been cleared. You can now:</p>';
echo '<ul>';
echo '<li>Import a new CSV file</li>';
echo '<li>Start fresh with a new batch</li>';
echo '<li>Your site should be running normally</li>';
echo '</ul>';
echo '</div>';

echo '<p><strong>IMPORTANT:</strong> Delete this file (clear-all-jobs.php) for security!</p>';

echo '<hr>';
echo '<p><a href="' . admin_url( 'admin.php?page=seo-generator-import' ) . '" class="button button-primary">← Back to Import Page</a></p>';

echo '<style>body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; }</style>';
