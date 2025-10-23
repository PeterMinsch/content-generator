<?php
/**
 * Quick Status Check
 */

require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Insufficient permissions.' );
}

header('Content-Type: text/plain');

echo "=== QUEUE STATUS ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// Check queue
$queue = get_option( 'seo_generation_queue', array() );
echo "Total Queue Items: " . count( $queue ) . "\n";

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

echo "Pending: " . $statuses['pending'] . "\n";
echo "Processing: " . $statuses['processing'] . "\n";
echo "Completed: " . $statuses['completed'] . "\n";
echo "Failed: " . $statuses['failed'] . "\n\n";

// Check cron
$cron_array = _get_cron_array();
$seo_cron_count = 0;
$ready_count = 0;
$now = time();

foreach ( $cron_array as $timestamp => $cron ) {
	if ( isset( $cron['seo_generate_queued_page'] ) ) {
		foreach ( $cron['seo_generate_queued_page'] as $event ) {
			$seo_cron_count++;
			if ( $timestamp <= $now ) {
				$ready_count++;
			}
		}
	}
}

echo "=== CRON STATUS ===\n";
echo "Total Cron Jobs Scheduled: " . $seo_cron_count . "\n";
echo "Ready to Run Now: " . $ready_count . "\n\n";

// Check if paused
$is_paused = get_option( 'seo_queue_paused', false );
echo "Queue Paused: " . ( $is_paused ? 'YES' : 'NO' ) . "\n\n";

// Show first 5 processing items
echo "=== PROCESSING ITEMS ===\n";
$processing_count = 0;
foreach ( $queue as $item ) {
	if ( $item['status'] === 'processing' && $processing_count < 5 ) {
		echo "Post " . $item['post_id'] . " - Status: processing\n";
		$processing_count++;
	}
}

if ( $processing_count === 0 ) {
	echo "None\n";
}
