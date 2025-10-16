<?php
/**
 * Emergency Queue Clearer
 *
 * Visit this file directly in your browser to clear the generation queue:
 * http://contentgeneratorwpplugin.local/wp-content/plugins/content-generator-disabled/clear-queue-emergency.php
 *
 * DELETE THIS FILE after using it!
 */

// Load WordPress.
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

// Check if user is admin.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Insufficient permissions.' );
}

// Clear the queue.
$queue = new \SEOGenerator\Services\GenerationQueue();

echo '<h1>Emergency Queue Clear</h1>';
echo '<p>Current queue stats:</p>';
echo '<pre>';
print_r( $queue->getQueueStats() );
echo '</pre>';

// Clear it.
$queue->clearQueue();
$queue->resumeQueue(); // Also resume in case it was paused

echo '<p><strong>✅ Queue cleared successfully!</strong></p>';
echo '<p>Your site should be fast again now.</p>';
echo '<p><strong>IMPORTANT:</strong> Delete this file (clear-queue-emergency.php) for security!</p>';

// Also clear any stuck cron events.
$cron_array = _get_cron_array();
$cleared = 0;
foreach ( $cron_array as $timestamp => $cron ) {
	if ( isset( $cron['seo_generate_queued_page'] ) ) {
		foreach ( $cron['seo_generate_queued_page'] as $key => $event ) {
			wp_unschedule_event( $timestamp, 'seo_generate_queued_page', $event['args'] );
			$cleared++;
		}
	}
}

echo "<p>Cleared {$cleared} pending cron events.</p>";
