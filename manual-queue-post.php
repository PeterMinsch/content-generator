<?php
/**
 * Manual Queue Post
 *
 * Manually queue a post for generation.
 * Use this if a post got created but wasn't added to the queue properly.
 *
 * Usage:
 * 1. Edit $post_id below to the stuck post ID
 * 2. Navigate to this file in your browser
 */

require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You do not have permission to access this page.' );
}

// ====================================================================
// CONFIGURE THIS: Set the post ID you want to queue
// ====================================================================
$post_id = 2124; // Change this to your stuck post ID
// ====================================================================

// Load the queue service
require_once plugin_dir_path( __FILE__ ) . 'includes/Services/GenerationQueue.php';
$queue = new \SEOGenerator\Services\GenerationQueue();

// Check if post exists
$post = get_post( $post_id );
if ( ! $post || $post->post_type !== 'seo-page' ) {
	wp_die( "Error: Post ID {$post_id} not found or is not an SEO page." );
}

// Queue the post (index 0 = immediate generation)
$result = $queue->queuePost( $post_id, 0 );

if ( $result ) {
	$success = true;
	$message = "Successfully queued post #{$post_id}: {$post->post_title}";

	// Check if cron event was scheduled
	$next_run = wp_next_scheduled( 'seo_generate_queued_page', array( $post_id ) );
	if ( $next_run ) {
		$message .= "\n\nWordPress Cron scheduled for: " . date( 'Y-m-d H:i:s', $next_run );
		$time_until = $next_run - time();
		if ( $time_until > 0 ) {
			$message .= " (in {$time_until} seconds)";
		} else {
			$message .= " (overdue by " . abs( $time_until ) . " seconds - should run soon)";
		}
	}
} else {
	$success = false;
	$message = "Failed to queue post #{$post_id}. It may already be queued.";
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Manual Queue Post</title>
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
		.success {
			color: #059669;
			padding: 15px;
			background: #d1fae5;
			border-radius: 6px;
			margin: 20px 0;
		}
		.error {
			color: #dc2626;
			padding: 15px;
			background: #fee2e2;
			border-radius: 6px;
			margin: 20px 0;
		}
		.info {
			background: #dbeafe;
			border: 1px solid #bfdbfe;
			color: #1e40af;
			padding: 15px;
			border-radius: 6px;
			margin: 20px 0;
		}
		code {
			background: #f1f5f9;
			padding: 2px 6px;
			border-radius: 3px;
			font-family: 'Monaco', 'Courier New', monospace;
		}
		.button {
			display: inline-block;
			padding: 10px 20px;
			background: #2563eb;
			color: white;
			text-decoration: none;
			border-radius: 6px;
			margin-top: 20px;
			margin-right: 10px;
		}
		.button-secondary {
			background: #6b7280;
		}
		pre {
			background: #1e293b;
			color: #e2e8f0;
			padding: 15px;
			border-radius: 6px;
			overflow-x: auto;
			white-space: pre-wrap;
			word-wrap: break-word;
		}
	</style>
</head>
<body>
	<div class="card">
		<h1>📋 Manual Queue Post</h1>

		<?php if ( $success ) : ?>
			<div class="success">
				<strong>✅ Success!</strong><br>
				<pre><?php echo esc_html( $message ); ?></pre>
			</div>

			<div class="info">
				<strong>ℹ️ What happens next:</strong><br>
				<ol>
					<li>WordPress Cron will run the generation job automatically</li>
					<li>If using default cron, it runs on next page load</li>
					<li>Generation typically takes 2-5 minutes per page</li>
					<li>Check the Queue Status page to monitor progress</li>
				</ol>
			</div>

			<div class="info">
				<strong>💡 Need it faster?</strong><br>
				You can trigger WordPress Cron immediately:
				<pre>wp cron event run seo_generate_queued_page --allow-root</pre>
				Or navigate to: <code>/wp-cron.php</code>
			</div>

		<?php else : ?>
			<div class="error">
				<strong>❌ Error</strong><br>
				<?php echo esc_html( $message ); ?>
			</div>
		<?php endif; ?>

		<h2>Post Details</h2>
		<ul>
			<li><strong>Post ID:</strong> <?php echo esc_html( $post_id ); ?></li>
			<li><strong>Title:</strong> <?php echo esc_html( $post->post_title ); ?></li>
			<li><strong>Status:</strong> <?php echo esc_html( $post->post_status ); ?></li>
			<li><strong>Created:</strong> <?php echo esc_html( $post->post_date ); ?></li>
		</ul>

		<div style="margin-top: 30px;">
			<a href="<?php echo admin_url( 'edit.php?post_type=seo-page' ); ?>" class="button">
				← Back to SEO Pages
			</a>
			<a href="check-queue-now.php" class="button button-secondary">
				Check Queue Status
			</a>
			<a href="<?php echo admin_url( 'post.php?post=' . $post_id . '&action=edit' ); ?>" class="button button-secondary">
				Edit Post
			</a>
		</div>
	</div>
</body>
</html>
