<?php
/**
 * Run Generation with Full Debug Output
 *
 * Runs generation with verbose error catching and output.
 */

// Start output buffering with flush
ob_implicit_flush(true);
ob_end_flush();

require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You do not have permission to access this page.' );
}

$post_id = 2124;

?>
<!DOCTYPE html>
<html>
<head>
	<title>Generation Debug</title>
	<style>
		body {
			font-family: 'Monaco', 'Courier New', monospace;
			background: #1e293b;
			color: #e2e8f0;
			padding: 20px;
			font-size: 13px;
		}
		.log { margin: 5px 0; }
		.success { color: #10b981; }
		.error { color: #ef4444; }
		.info { color: #3b82f6; }
		.warning { color: #f59e0b; }
		h1 { color: #10b981; border-bottom: 2px solid #10b981; padding-bottom: 10px; }
		pre { background: #0f172a; padding: 10px; border-radius: 4px; overflow-x: auto; }
	</style>
</head>
<body>
	<h1>⚡ Generation Debug Log</h1>
	<div class="log info">Starting generation for post #<?php echo $post_id; ?>...</div>

<?php
flush();

try {
	// Step 1: Verify post exists
	echo '<div class="log info">[1/10] Checking post exists...</div>';
	flush();

	$post = get_post( $post_id );
	if ( ! $post ) {
		throw new Exception( "Post #{$post_id} not found" );
	}
	echo '<div class="log success">✓ Post found: ' . esc_html( $post->post_title ) . '</div>';
	flush();

	// Step 2: Check API key
	echo '<div class="log info">[2/10] Checking OpenAI API key...</div>';
	flush();

	$settings = new \SEOGenerator\Services\SettingsService();
	$api_key = $settings->getApiKey();
	if ( empty( $api_key ) ) {
		throw new Exception( "OpenAI API key not configured. Please add it in Settings." );
	}
	echo '<div class="log success">✓ API key configured (' . strlen( $api_key ) . ' chars)</div>';
	flush();

	// Step 3: Check database table
	echo '<div class="log info">[3/10] Checking image cache table...</div>';
	flush();

	global $wpdb;
	$table_name = $wpdb->prefix . 'seo_image_cache';
	$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;
	if ( ! $table_exists ) {
		throw new Exception( "Image cache table doesn't exist. Run create-image-cache-table.php first." );
	}
	echo '<div class="log success">✓ Database table exists</div>';
	flush();

	// Step 4: Initialize services
	echo '<div class="log info">[4/10] Initializing services...</div>';
	flush();

	$generation_service = new \SEOGenerator\Services\GenerationService();
	echo '<div class="log success">✓ GenerationService initialized</div>';
	flush();

	// Step 5: Remove from queue to reset status
	echo '<div class="log info">[5/10] Resetting queue status...</div>';
	flush();

	$queue = get_option( 'seo_generation_queue', array() );
	$new_queue = array();
	foreach ( $queue as $item ) {
		if ( $item['post_id'] !== $post_id ) {
			$new_queue[] = $item;
		}
	}
	update_option( 'seo_generation_queue', $new_queue );
	delete_post_meta( $post_id, '_queue_status' );
	delete_post_meta( $post_id, '_queued_at' );
	echo '<div class="log success">✓ Queue status reset</div>';
	flush();

	// Step 6: Re-queue the post
	echo '<div class="log info">[6/10] Re-queuing post...</div>';
	flush();

	$queue_service = new \SEOGenerator\Services\GenerationQueue();
	$queue_service->queuePost( $post_id, 0 );
	echo '<div class="log success">✓ Post re-queued</div>';
	flush();

	// Step 7: Check queue status
	echo '<div class="log info">[7/10] Verifying queue...</div>';
	flush();

	$queue = get_option( 'seo_generation_queue', array() );
	$found = false;
	foreach ( $queue as $item ) {
		if ( $item['post_id'] === $post_id ) {
			$found = true;
			echo '<div class="log success">✓ Post in queue with status: ' . $item['status'] . '</div>';
		}
	}
	if ( ! $found ) {
		throw new Exception( "Post not in queue after queueing!" );
	}
	flush();

	// Step 8: Check cron schedule
	echo '<div class="log info">[8/10] Checking WordPress Cron...</div>';
	flush();

	$next_run = wp_next_scheduled( 'seo_generate_queued_page', array( $post_id ) );
	if ( $next_run ) {
		$time_diff = $next_run - time();
		echo '<div class="log success">✓ Cron scheduled for: ' . date( 'Y-m-d H:i:s', $next_run ) . ' (in ' . $time_diff . ' seconds)</div>';
	} else {
		echo '<div class="log warning">⚠ No cron scheduled (this is unusual but we can still force run)</div>';
	}
	flush();

	// Step 9: Manually trigger generation
	echo '<div class="log info">[9/10] Manually triggering generation NOW...</div>';
	echo '<div class="log info">This may take 2-5 minutes depending on API response time...</div>';
	flush();

	$start_time = microtime( true );

	// Actually run the generation
	$generation_service->processQueuedPage( $post_id );

	$end_time = microtime( true );
	$duration = round( $end_time - $start_time, 2 );

	echo '<div class="log success">✓ Generation completed in ' . $duration . ' seconds</div>';
	flush();

	// Step 10: Verify results
	echo '<div class="log info">[10/10] Verifying results...</div>';
	flush();

	$post = get_post( $post_id );
	echo '<div class="log info">Post status: ' . $post->post_status . '</div>';

	// Check if blocks were generated
	$blocks_generated = get_post_meta( $post_id, '_blocks_generated', true );
	$blocks_failed = get_post_meta( $post_id, '_blocks_failed', true );

	echo '<div class="log success">✓ Blocks generated: ' . ( $blocks_generated ?: 0 ) . '</div>';
	if ( $blocks_failed ) {
		echo '<div class="log warning">⚠ Blocks failed: ' . $blocks_failed . '</div>';
	}

	// Check if related_links was generated
	$related_links = get_post_meta( $post_id, 'related_links', true );
	if ( ! empty( $related_links ) ) {
		echo '<div class="log success">✓ Related links block generated</div>';
		if ( is_string( $related_links ) ) {
			$related_links = json_decode( $related_links, true );
		}
		if ( isset( $related_links['links'] ) && is_array( $related_links['links'] ) ) {
			echo '<div class="log info">  → ' . count( $related_links['links'] ) . ' links created</div>';
			foreach ( $related_links['links'] as $link ) {
				$has_image = ! empty( $link['link_image'] );
				echo '<div class="log ' . ( $has_image ? 'success' : 'warning' ) . '">  → ' .
					esc_html( $link['link_title'] ?? 'Unknown' ) .
					( $has_image ? ' (✓ has image)' : ' (⚠ no image)' ) .
					'</div>';
			}
		}
	} else {
		echo '<div class="log warning">⚠ Related links block not found</div>';
	}

	echo '<div class="log success">━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</div>';
	echo '<div class="log success">✅ GENERATION COMPLETE!</div>';
	echo '<div class="log success">━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</div>';

} catch ( Exception $e ) {
	echo '<div class="log error">━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</div>';
	echo '<div class="log error">❌ ERROR: ' . esc_html( $e->getMessage() ) . '</div>';
	echo '<div class="log error">━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</div>';
	echo '<pre>' . esc_html( $e->getTraceAsString() ) . '</pre>';
}

flush();
?>

	<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #475569;">
		<a href="<?php echo get_permalink( $post_id ); ?>" target="_blank" style="color: #10b981; text-decoration: none;">→ View Generated Page</a> |
		<a href="<?php echo admin_url( 'post.php?post=' . $post_id . '&action=edit' ); ?>" target="_blank" style="color: #10b981; text-decoration: none;">→ Edit Post</a> |
		<a href="<?php echo admin_url( 'edit.php?post_type=seo-page' ); ?>" style="color: #10b981; text-decoration: none;">→ Back to SEO Pages</a>
	</div>
</body>
</html>
