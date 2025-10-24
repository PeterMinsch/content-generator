<?php
/**
 * Inspect Queue Contents
 *
 * Shows the raw queue data to diagnose issues.
 */

require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You do not have permission to access this page.' );
}

// Get the raw queue data
$queue = get_option( 'seo_generation_queue', array() );
$is_paused = get_option( 'seo_queue_paused', false );

// Get specific post ID to check
$check_post_id = 2124;
$post = get_post( $check_post_id );
$queue_status_meta = get_post_meta( $check_post_id, '_queue_status', true );

?>
<!DOCTYPE html>
<html>
<head>
	<title>Queue Inspector</title>
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			max-width: 1200px;
			margin: 50px auto;
			padding: 20px;
			background: #f5f5f5;
		}
		.card {
			background: white;
			border-radius: 8px;
			padding: 30px;
			box-shadow: 0 2px 8px rgba(0,0,0,0.1);
			margin-bottom: 20px;
		}
		h1 {
			color: #333;
			margin-top: 0;
		}
		pre {
			background: #1e293b;
			color: #e2e8f0;
			padding: 15px;
			border-radius: 6px;
			overflow-x: auto;
			max-height: 500px;
			overflow-y: auto;
		}
		.warning {
			background: #fef3c7;
			border: 1px solid #fcd34d;
			color: #92400e;
			padding: 15px;
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
		.error {
			background: #fee2e2;
			border: 1px solid #fecaca;
			color: #dc2626;
			padding: 15px;
			border-radius: 6px;
			margin: 20px 0;
		}
		table {
			width: 100%;
			border-collapse: collapse;
			margin: 20px 0;
		}
		th, td {
			text-align: left;
			padding: 12px;
			border-bottom: 1px solid #e5e7eb;
		}
		th {
			background: #f9fafb;
			font-weight: 600;
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
			margin-top: 10px;
			margin-right: 10px;
		}
		.button-danger {
			background: #dc2626;
		}
	</style>
</head>
<body>
	<div class="card">
		<h1>🔍 Queue Inspector</h1>

		<h2>Queue Overview</h2>
		<ul>
			<li><strong>Total Items in Queue:</strong> <?php echo count( $queue ); ?></li>
			<li><strong>Queue Paused:</strong> <?php echo $is_paused ? '✅ Yes' : '❌ No'; ?></li>
		</ul>

		<?php if ( ! empty( $queue ) ) : ?>
			<h2>Queue Contents</h2>
			<table>
				<thead>
					<tr>
						<th>Post ID</th>
						<th>Status</th>
						<th>Scheduled Time</th>
						<th>Queued At</th>
						<th>Retry Count</th>
						<th>Blocks</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $queue as $item ) : ?>
						<?php
						$is_target = isset( $item['post_id'] ) && $item['post_id'] === $check_post_id;
						$post_title = get_the_title( $item['post_id'] ?? 0 );
						?>
						<tr style="<?php echo $is_target ? 'background: #fef3c7;' : ''; ?>">
							<td>
								<?php echo esc_html( $item['post_id'] ?? 'unknown' ); ?>
								<?php if ( $is_target ) : ?>
									<strong>← TARGET POST</strong>
								<?php endif; ?>
								<br>
								<small><?php echo esc_html( $post_title ); ?></small>
							</td>
							<td><code><?php echo esc_html( $item['status'] ?? 'unknown' ); ?></code></td>
							<td><?php echo isset( $item['scheduled_time'] ) ? date( 'Y-m-d H:i:s', $item['scheduled_time'] ) : 'N/A'; ?></td>
							<td><?php echo esc_html( $item['queued_at'] ?? 'N/A' ); ?></td>
							<td><?php echo esc_html( $item['retry_count'] ?? 0 ); ?></td>
							<td><?php echo isset( $item['blocks'] ) ? count( $item['blocks'] ) . ' blocks' : 'all blocks'; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<div class="info">
				Queue is empty - no posts waiting for generation.
			</div>
		<?php endif; ?>

		<h2>Post #<?php echo $check_post_id; ?> Details</h2>
		<?php if ( $post ) : ?>
			<table>
				<tr>
					<th>Property</th>
					<th>Value</th>
				</tr>
				<tr>
					<td>Title</td>
					<td><?php echo esc_html( $post->post_title ); ?></td>
				</tr>
				<tr>
					<td>Status</td>
					<td><code><?php echo esc_html( $post->post_status ); ?></code></td>
				</tr>
				<tr>
					<td>Created</td>
					<td><?php echo esc_html( $post->post_date ); ?></td>
				</tr>
				<tr>
					<td>Queue Status Meta</td>
					<td><code><?php echo esc_html( $queue_status_meta ?: 'none' ); ?></code></td>
				</tr>
			</table>
		<?php else : ?>
			<div class="error">Post not found!</div>
		<?php endif; ?>

		<h2>Raw Queue Data</h2>
		<pre><?php echo esc_html( print_r( $queue, true ) ); ?></pre>

		<h2>Actions</h2>

		<form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove post #<?php echo $check_post_id; ?> from the queue?');">
			<input type="hidden" name="action" value="remove_from_queue">
			<input type="hidden" name="post_id" value="<?php echo $check_post_id; ?>">
			<button type="submit" class="button button-danger">🗑️ Remove Post #<?php echo $check_post_id; ?> from Queue</button>
		</form>

		<form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to clear the ENTIRE queue?');">
			<input type="hidden" name="action" value="clear_queue">
			<button type="submit" class="button button-danger">🗑️ Clear Entire Queue</button>
		</form>

		<div style="margin-top: 30px;">
			<a href="<?php echo admin_url( 'edit.php?post_type=seo-page' ); ?>" class="button">
				← Back to SEO Pages
			</a>
		</div>
	</div>
</body>
</html>

<?php
// Handle form submissions
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'remove_from_queue' && isset( $_POST['post_id'] ) ) {
		$post_id_to_remove = (int) $_POST['post_id'];

		// Remove from queue
		$new_queue = array();
		foreach ( $queue as $item ) {
			if ( $item['post_id'] !== $post_id_to_remove ) {
				$new_queue[] = $item;
			}
		}
		update_option( 'seo_generation_queue', $new_queue );

		// Remove meta
		delete_post_meta( $post_id_to_remove, '_queue_status' );
		delete_post_meta( $post_id_to_remove, '_queued_at' );

		echo '<script>alert("Removed post #' . $post_id_to_remove . ' from queue!"); window.location.reload();</script>';
	}

	if ( isset( $_POST['action'] ) && $_POST['action'] === 'clear_queue' ) {
		update_option( 'seo_generation_queue', array() );
		echo '<script>alert("Queue cleared!"); window.location.reload();</script>';
	}
}
?>
