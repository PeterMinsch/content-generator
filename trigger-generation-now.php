<?php
/**
 * Trigger Generation NOW
 *
 * Manually triggers the generation cron job immediately instead of waiting.
 * Use this when WordPress Cron isn't firing automatically.
 */

require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You do not have permission to access this page.' );
}

$post_id = 2124;

// Check queue status
$queue = get_option( 'seo_generation_queue', array() );
$queue_item = null;
foreach ( $queue as $item ) {
	if ( $item['post_id'] === $post_id ) {
		$queue_item = $item;
		break;
	}
}

// Check if cron is scheduled
$next_run = wp_next_scheduled( 'seo_generate_queued_page', array( $post_id ) );

?>
<!DOCTYPE html>
<html>
<head>
	<title>Trigger Generation</title>
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			max-width: 800px;
			margin: 50px auto;
			padding: 20px;
			background: #f5f5f5;
		}
		.card {
			background: white;
			border-radius: 8px;
			padding: 30px;
			box-shadow: 0 2px 8px rgba(0,0,0,0.1);
		}
		h1 {
			color: #333;
			margin-top: 0;
		}
		.info {
			background: #dbeafe;
			border: 1px solid #bfdbfe;
			color: #1e40af;
			padding: 15px;
			border-radius: 6px;
			margin: 20px 0;
		}
		.success {
			background: #d1fae5;
			border: 1px solid #a7f3d0;
			color: #059669;
			padding: 15px;
			border-radius: 6px;
			margin: 20px 0;
		}
		.error {
			background: #fee2e2;
			border: 1px solid #fecaca;
			color: #dc2626;
			padding: 15px;
			border-radius: 6px;
			margin: 20px 0;
		}
		.warning {
			background: #fef3c7;
			border: 1px solid #fcd34d;
			color: #92400e;
			padding: 15px;
			border-radius: 6px;
			margin: 20px 0;
		}
		.button {
			display: inline-block;
			padding: 12px 24px;
			background: #2563eb;
			color: white;
			text-decoration: none;
			border-radius: 6px;
			margin-top: 10px;
			margin-right: 10px;
			font-weight: 600;
		}
		.button-danger {
			background: #dc2626;
		}
		code {
			background: #f1f5f9;
			padding: 2px 6px;
			border-radius: 3px;
			font-family: 'Monaco', 'Courier New', monospace;
		}
		pre {
			background: #1e293b;
			color: #e2e8f0;
			padding: 15px;
			border-radius: 6px;
			overflow-x: auto;
		}
	</style>
</head>
<body>
	<div class="card">
		<h1>⚡ Trigger Generation NOW</h1>

		<h2>Current Status</h2>
		<ul>
			<li><strong>Post ID:</strong> <?php echo $post_id; ?></li>
			<li><strong>In Queue:</strong> <?php echo $queue_item ? '✅ Yes' : '❌ No'; ?></li>
			<?php if ( $queue_item ) : ?>
				<li><strong>Queue Status:</strong> <code><?php echo esc_html( $queue_item['status'] ); ?></code></li>
				<li><strong>Scheduled Time:</strong> <?php echo date( 'Y-m-d H:i:s', $queue_item['scheduled_time'] ); ?></li>
			<?php endif; ?>
			<li><strong>Cron Scheduled:</strong> <?php echo $next_run ? '✅ Yes (' . date( 'Y-m-d H:i:s', $next_run ) . ')' : '❌ No'; ?></li>
		</ul>

		<?php if ( ! $queue_item ) : ?>
			<div class="error">
				<strong>❌ Post not in queue!</strong><br>
				Go back and queue the post first using <code>manual-queue-post.php</code>
			</div>
		<?php elseif ( $queue_item['status'] === 'processing' ) : ?>
			<div class="warning">
				<strong>⚠️ Already Processing!</strong><br>
				The post is marked as "processing" but no cron is scheduled. This means it's stuck/crashed.<br>
				<strong>You can force it to run anyway to see the error:</strong>
			</div>

			<form method="post">
				<input type="hidden" name="action" value="force_run">
				<button type="submit" class="button button-danger">⚡ FORCE RUN (Ignore Processing Status)</button>
			</form>

			<div class="info" style="margin-top: 20px;">
				<strong>Or reset it first:</strong><br>
				<a href="inspect-queue.php" class="button">Go to Inspect Queue → Remove Post</a>
			</div>
		<?php else : ?>
			<div class="info">
				<strong>ℹ️ Ready to Generate</strong><br>
				Click the button below to trigger generation immediately instead of waiting for WordPress Cron.
			</div>

			<form method="post">
				<input type="hidden" name="action" value="trigger_now">
				<button type="submit" class="button">⚡ TRIGGER GENERATION NOW</button>
			</form>
		<?php endif; ?>

		<?php
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['action'] ) && ( $_POST['action'] === 'trigger_now' || $_POST['action'] === 'force_run' ) ) {
			echo '<div class="info"><strong>🚀 Starting generation...</strong></div>';

			// Flush output so user sees the message
			if ( ob_get_level() > 0 ) {
				ob_flush();
			}
			flush();

			try {
				// Load the generation service
				$generation_service = new \SEOGenerator\Services\GenerationService();

				// Manually trigger the generation
				error_log( '[Manual Trigger] Starting generation for post ' . $post_id );

				// Call the cron handler directly
				$generation_service->processQueuedPage( $post_id );

				echo '<div class="success">';
				echo '<strong>✅ Generation triggered!</strong><br>';
				echo 'Check the debug.log for details.<br>';
				echo 'Refresh the SEO Pages list to see the result.';
				echo '</div>';

			} catch ( Exception $e ) {
				echo '<div class="error">';
				echo '<strong>❌ Error:</strong><br>';
				echo '<pre>' . esc_html( $e->getMessage() ) . '</pre>';
				echo '<pre>' . esc_html( $e->getTraceAsString() ) . '</pre>';
				echo '</div>';
				error_log( '[Manual Trigger] Error: ' . $e->getMessage() );
			}
		}
		?>

		<h2>Troubleshooting</h2>
		<div class="info">
			<strong>If generation still doesn't work:</strong>
			<ol>
				<li>Check <code>wp-content/debug.log</code> for errors</li>
				<li>Verify OpenAI API key is configured in Settings</li>
				<li>Make sure image cache table exists (run <code>create-image-cache-table.php</code>)</li>
				<li>Check PHP memory limit (needs at least 256MB)</li>
			</ol>
		</div>

		<div style="margin-top: 30px;">
			<a href="check-queue-now.php" class="button">
				Check Queue Status
			</a>
			<a href="inspect-queue.php" class="button">
				Inspect Queue
			</a>
			<a href="<?php echo admin_url( 'edit.php?post_type=seo-page' ); ?>" class="button">
				← Back to SEO Pages
			</a>
		</div>
	</div>

	<script>
		// Auto-scroll to bottom after form submission
		<?php if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) : ?>
		window.scrollTo(0, document.body.scrollHeight);
		<?php endif; ?>
	</script>
</body>
</html>
