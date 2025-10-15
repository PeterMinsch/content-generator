<?php
/**
 * Queue Status Page Template
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

// Enqueue queue status script.
wp_enqueue_script(
	'seo-generator-queue-status',
	plugin_dir_url( dirname( __DIR__ ) ) . 'assets/js/build/queue-status.js',
	array( 'wp-api-fetch' ),
	'1.0.0',
	true
);

// Enqueue queue status styles.
wp_enqueue_style(
	'seo-generator-queue-status',
	plugin_dir_url( dirname( __DIR__ ) ) . 'assets/css/admin-queue.css',
	array(),
	'1.0.0'
);

// Localize script with AJAX data.
wp_localize_script(
	'seo-generator-queue-status',
	'seoQueueData',
	array(
		'nonce'   => wp_create_nonce( 'seo_queue_nonce' ),
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
	)
);
?>

<div class="wrap seo-generator-page seo-queue-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Generation Queue', 'seo-generator' ); ?></h1>

	<hr class="wp-header-end">

	<?php if ( $is_paused ) : ?>
		<div class="seo-card mt-4" style="border-left: 4px solid var(--warning);">
			<div class="seo-card__content">
				<p><strong><?php esc_html_e( '⚠️ Queue is currently paused.', 'seo-generator' ); ?></strong></p>
			</div>
		</div>
	<?php endif; ?>

	<!-- Queue Statistics -->
	<div class="queue-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-4); margin: var(--space-6) 0;">
		<div class="seo-card" style="text-align: center;">
			<div class="seo-card__content">
				<span style="display: block; font-size: var(--text-3xl); font-weight: 600; color: var(--warning);" id="pending-count"><?php echo esc_html( $stats['pending'] ); ?></span>
				<span style="display: block; font-size: var(--text-sm); color: var(--gray-700); margin-top: var(--space-2);"><?php esc_html_e( 'Pending', 'seo-generator' ); ?></span>
			</div>
		</div>
		<div class="seo-card" style="text-align: center;">
			<div class="seo-card__content">
				<span style="display: block; font-size: var(--text-3xl); font-weight: 600; color: var(--info);" id="processing-count"><?php echo esc_html( $stats['processing'] ); ?></span>
				<span style="display: block; font-size: var(--text-sm); color: var(--gray-700); margin-top: var(--space-2);"><?php esc_html_e( 'Processing', 'seo-generator' ); ?></span>
			</div>
		</div>
		<div class="seo-card" style="text-align: center;">
			<div class="seo-card__content">
				<span style="display: block; font-size: var(--text-3xl); font-weight: 600; color: var(--success);" id="completed-count"><?php echo esc_html( $stats['completed'] ); ?></span>
				<span style="display: block; font-size: var(--text-sm); color: var(--gray-700); margin-top: var(--space-2);"><?php esc_html_e( 'Completed', 'seo-generator' ); ?></span>
			</div>
		</div>
		<div class="seo-card" style="text-align: center;">
			<div class="seo-card__content">
				<span style="display: block; font-size: var(--text-3xl); font-weight: 600; color: var(--error);" id="failed-count"><?php echo esc_html( $stats['failed'] ); ?></span>
				<span style="display: block; font-size: var(--text-sm); color: var(--gray-700); margin-top: var(--space-2);"><?php esc_html_e( 'Failed', 'seo-generator' ); ?></span>
			</div>
		</div>
	</div>

	<!-- Estimated Completion -->
	<p class="estimated-completion" id="estimated-completion">
		<?php if ( $estimated ) : ?>
			<?php
			printf(
				/* translators: %s: estimated completion time */
				esc_html__( 'Estimated completion: %s', 'seo-generator' ),
				esc_html( $estimated )
			);
			?>
		<?php else : ?>
			<?php esc_html_e( 'No pending jobs', 'seo-generator' ); ?>
		<?php endif; ?>
	</p>

	<!-- Queue Control Buttons -->
	<div class="queue-actions" style="display: flex; gap: var(--space-3); margin: var(--space-6) 0;">
		<?php if ( $is_paused ) : ?>
			<button id="resume-queue" class="seo-btn-primary">
				▶️ <?php esc_html_e( 'Resume Queue', 'seo-generator' ); ?>
			</button>
		<?php else : ?>
			<button id="pause-queue" class="seo-btn-secondary">
				⏸️ <?php esc_html_e( 'Pause Queue', 'seo-generator' ); ?>
			</button>
		<?php endif; ?>
		<button id="clear-queue" class="seo-btn-secondary">
			🗑️ <?php esc_html_e( 'Clear Queue', 'seo-generator' ); ?>
		</button>
	</div>

	<!-- WordPress Cron Information -->
	<div class="seo-card mt-6">
		<h3 class="seo-card__title">ℹ️ <?php esc_html_e( 'WordPress Cron Information', 'seo-generator' ); ?></h3>
		<div class="seo-card__content">
			<p>
				<?php esc_html_e( 'WordPress Cron only runs when someone visits your site. For reliable background processing:', 'seo-generator' ); ?>
			</p>
			<ul style="list-style: disc; margin-left: 20px;">
				<li>
					<strong><?php esc_html_e( 'For Production:', 'seo-generator' ); ?></strong>
					<?php esc_html_e( 'Disable WP-Cron and use server cron:', 'seo-generator' ); ?>
					<code>wp cron event run --due-now</code>
				</li>
				<li>
					<strong><?php esc_html_e( 'Manual Processing:', 'seo-generator' ); ?></strong>
					<?php esc_html_e( 'Use WP-CLI command:', 'seo-generator' ); ?>
					<code>wp seo-generator queue process</code>
				</li>
				<li>
					<?php esc_html_e( 'Jobs are scheduled 3 minutes apart to respect API rate limits.', 'seo-generator' ); ?>
				</li>
			</ul>
		</div>
	</div>

	<!-- Queued Jobs Table -->
	<h2><?php esc_html_e( 'Queued Jobs', 'seo-generator' ); ?></h2>

	<?php if ( empty( $queue ) ) : ?>
		<p><?php esc_html_e( 'No jobs in queue.', 'seo-generator' ); ?></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Post ID', 'seo-generator' ); ?></th>
					<th><?php esc_html_e( 'Post Title', 'seo-generator' ); ?></th>
					<th><?php esc_html_e( 'Scheduled Time', 'seo-generator' ); ?></th>
					<th><?php esc_html_e( 'Status', 'seo-generator' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'seo-generator' ); ?></th>
				</tr>
			</thead>
			<tbody id="queue-list">
				<?php foreach ( $queue as $item ) : ?>
					<?php
					$post_title = get_the_title( $item['post_id'] );
					if ( empty( $post_title ) ) {
						$post_title = sprintf( __( '(Post #%d)', 'seo-generator' ), $item['post_id'] );
					}
					$scheduled_date = gmdate( 'Y-m-d H:i:s', $item['scheduled_time'] );
					$status_class   = 'status-' . esc_attr( $item['status'] );
					?>
					<tr data-post-id="<?php echo esc_attr( $item['post_id'] ); ?>" class="<?php echo esc_attr( $status_class ); ?>">
						<td><?php echo esc_html( $item['post_id'] ); ?></td>
						<td>
							<a href="<?php echo esc_url( get_edit_post_link( $item['post_id'] ) ); ?>">
								<?php echo esc_html( $post_title ); ?>
							</a>
						</td>
						<td class="column-scheduled"><?php echo esc_html( $scheduled_date ); ?></td>
						<td class="column-status">
							<?php if ( $item['status'] === 'processing' ) : ?>
								<span class="seo-badge seo-badge--pending">
									<?php esc_html_e( 'Processing...', 'seo-generator' ); ?>
								</span>
							<?php elseif ( $item['status'] === 'failed' && isset( $item['error'] ) ) : ?>
								<span class="seo-badge seo-badge--failed" title="<?php echo esc_attr( $item['error'] ); ?>">
									<?php esc_html_e( 'Failed', 'seo-generator' ); ?>
								</span>
							<?php elseif ( $item['status'] === 'completed' ) : ?>
								<span class="seo-badge seo-badge--published">
									<?php esc_html_e( 'Completed', 'seo-generator' ); ?>
								</span>
							<?php else : ?>
								<span class="seo-badge seo-badge--draft">
									<?php echo esc_html( ucfirst( $item['status'] ) ); ?>
								</span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( $item['status'] === 'pending' ) : ?>
								<button class="button-link cancel-job" data-post-id="<?php echo esc_attr( $item['post_id'] ); ?>">
									<?php esc_html_e( 'Cancel', 'seo-generator' ); ?>
								</button>
							<?php elseif ( $item['status'] === 'failed' && isset( $item['error'] ) ) : ?>
								<span class="error-message" title="<?php echo esc_attr( $item['error'] ); ?>">
									<?php esc_html_e( 'View Error', 'seo-generator' ); ?>
								</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
