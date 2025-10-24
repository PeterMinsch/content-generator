<?php
/**
 * Check Queue Status
 *
 * Quick diagnostic to see what's happening with the generation queue.
 */

require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You do not have permission to access this page.' );
}

global $wpdb;

// Get recent posts in generation
$posts = $wpdb->get_results( "
	SELECT ID, post_title, post_status, post_date
	FROM {$wpdb->posts}
	WHERE post_type = 'seo-page'
	AND post_status IN ('draft', 'pending', 'processing')
	ORDER BY ID DESC
	LIMIT 10
" );

?>
<!DOCTYPE html>
<html>
<head>
	<title>Queue Status Diagnostic</title>
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
		.status-queued { color: #ca8a04; }
		.status-processing { color: #2563eb; }
		.status-completed { color: #059669; }
		.status-failed { color: #dc2626; }
		.status-draft { color: #6b7280; }
		code {
			background: #f1f5f9;
			padding: 2px 6px;
			border-radius: 3px;
			font-family: 'Monaco', 'Courier New', monospace;
			font-size: 13px;
		}
		.error-box {
			background: #fee2e2;
			border: 1px solid #fecaca;
			color: #dc2626;
			padding: 12px;
			border-radius: 6px;
			margin: 10px 0;
		}
		.info-box {
			background: #dbeafe;
			border: 1px solid #bfdbfe;
			color: #1e40af;
			padding: 12px;
			border-radius: 6px;
			margin: 10px 0;
		}
	</style>
</head>
<body>
	<div class="card">
		<h1>🔍 Queue Status Diagnostic</h1>
		<p><strong>Current Time:</strong> <?php echo current_time( 'mysql' ); ?> (<?php echo current_time( 'timestamp' ); ?>)</p>

		<h2>Recent Posts in Queue</h2>
		<?php if ( empty( $posts ) ) : ?>
			<div class="info-box">
				No posts currently in draft/processing status.
			</div>
		<?php else : ?>
			<table>
				<thead>
					<tr>
						<th>ID</th>
						<th>Title</th>
						<th>Post Status</th>
						<th>Queue Status</th>
						<th>Created</th>
						<th>Error</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $posts as $post ) : ?>
						<?php
						$queue_status = get_post_meta( $post->ID, '_queue_status', true );
						$queue_error  = get_post_meta( $post->ID, '_queue_error', true );
						$queued_at    = get_post_meta( $post->ID, '_queued_at', true );
						$started_at   = get_post_meta( $post->ID, '_generation_started', true );
						?>
						<tr>
							<td><?php echo esc_html( $post->ID ); ?></td>
							<td><?php echo esc_html( $post->post_title ); ?></td>
							<td><code><?php echo esc_html( $post->post_status ); ?></code></td>
							<td>
								<code class="status-<?php echo esc_attr( $queue_status ?: 'unknown' ); ?>">
									<?php echo esc_html( $queue_status ?: 'none' ); ?>
								</code>
							</td>
							<td><?php echo esc_html( $post->post_date ); ?></td>
							<td>
								<?php if ( $queue_error ) : ?>
									<div class="error-box">
										<?php echo esc_html( $queue_error ); ?>
									</div>
								<?php else : ?>
									—
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="6" style="background: #f9fafb; font-size: 12px; color: #6b7280;">
								<?php if ( $queued_at ) : ?>
									Queued: <?php echo esc_html( date( 'Y-m-d H:i:s', $queued_at ) ); ?> |
								<?php endif; ?>
								<?php if ( $started_at ) : ?>
									Started: <?php echo esc_html( $started_at ); ?> |
								<?php endif; ?>
								<a href="<?php echo admin_url( 'post.php?post=' . $post->ID . '&action=edit' ); ?>" target="_blank">Edit</a> |
								<a href="<?php echo get_permalink( $post->ID ); ?>" target="_blank">View</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h2>WordPress Cron Status</h2>
		<?php
		$cron_events    = _get_cron_array();
		$queue_events   = array();
		$current_time   = time();
		$upcoming_count = 0;

		foreach ( $cron_events as $timestamp => $events ) {
			if ( isset( $events['seo_generate_queued_page'] ) ) {
				foreach ( $events['seo_generate_queued_page'] as $event ) {
					$upcoming_count++;
					$time_diff = $timestamp - $current_time;
					$queue_events[] = array(
						'timestamp' => $timestamp,
						'time_diff' => $time_diff,
						'post_id'   => $event['args'][0] ?? 'unknown',
						'status'    => $time_diff < 0 ? 'overdue' : 'scheduled',
					);
				}
			}
		}
		?>
		<p><strong>Upcoming Generation Jobs:</strong> <?php echo count( $queue_events ); ?></p>
		<?php if ( ! empty( $queue_events ) ) : ?>
			<table>
				<thead>
					<tr>
						<th>Post ID</th>
						<th>Scheduled Time</th>
						<th>Time Until Run</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( array_slice( $queue_events, 0, 10 ) as $event ) : ?>
						<tr>
							<td><?php echo esc_html( $event['post_id'] ); ?></td>
							<td><?php echo esc_html( date( 'Y-m-d H:i:s', $event['timestamp'] ) ); ?></td>
							<td>
								<?php
								$diff = abs( $event['time_diff'] );
								if ( $diff < 60 ) {
									echo esc_html( $diff ) . ' seconds';
								} elseif ( $diff < 3600 ) {
									echo esc_html( floor( $diff / 60 ) ) . ' minutes';
								} else {
									echo esc_html( floor( $diff / 3600 ) ) . ' hours';
								}
								?>
							</td>
							<td>
								<?php if ( $event['status'] === 'overdue' ) : ?>
									<span style="color: #dc2626;">⚠️ Overdue</span>
								<?php else : ?>
									<span style="color: #059669;">✓ Scheduled</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<div class="info-box">
				No generation jobs scheduled in WordPress cron.
			</div>
		<?php endif; ?>

		<h2>System Info</h2>
		<ul>
			<li><strong>WP Cron Enabled:</strong> <?php echo defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? '❌ Disabled' : '✓ Enabled'; ?></li>
			<li><strong>WordPress Version:</strong> <?php echo get_bloginfo( 'version' ); ?></li>
			<li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
			<li><strong>Plugin Version:</strong> <?php echo defined( 'SEO_GENERATOR_VERSION' ) ? SEO_GENERATOR_VERSION : 'unknown'; ?></li>
		</ul>

		<div style="margin-top: 30px;">
			<a href="<?php echo admin_url( 'edit.php?post_type=seo-page' ); ?>" style="display: inline-block; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px;">
				← Back to SEO Pages
			</a>
		</div>
	</div>
</body>
</html>
