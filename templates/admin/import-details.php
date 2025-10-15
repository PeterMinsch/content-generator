<?php
/**
 * Import Details View Template
 *
 * Displays detailed information about a single import log.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

// Get log ID from URL.
$log_id = isset( $_GET['log_id'] ) ? absint( $_GET['log_id'] ) : 0;

if ( ! $log_id ) {
	wp_die( esc_html__( 'Invalid import log ID.', 'seo-generator' ) );
}

// Get repository and fetch log.
$import_log_repo = new \SEOGenerator\Repositories\ImportLogRepository();
$log             = $import_log_repo->findById( $log_id );

if ( ! $log ) {
	wp_die( esc_html__( 'Import log not found.', 'seo-generator' ) );
}

// Get user info.
$user     = get_userdata( $log['user_id'] );
$username = $user ? $user->display_name : __( 'Unknown', 'seo-generator' );

// Format timestamp.
$timestamp = mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $log['timestamp'] );

// Calculate success rate.
$success_rate = $log['total_rows'] > 0 ? round( ( $log['success_count'] / $log['total_rows'] ) * 100, 1 ) : 0;

?>

<div class="wrap seo-import-details">
	<h1><?php esc_html_e( 'Import Details', 'seo-generator' ); ?></h1>

	<p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-generator-import' ) ); ?>" class="button">
			&larr; <?php esc_html_e( 'Back to Import History', 'seo-generator' ); ?>
		</a>
	</p>

	<div class="seo-import-summary-card" style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
		<h2><?php esc_html_e( 'Import Summary', 'seo-generator' ); ?></h2>

		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Filename:', 'seo-generator' ); ?></th>
				<td><strong><?php echo esc_html( $log['filename'] ); ?></strong></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Import Date/Time:', 'seo-generator' ); ?></th>
				<td><?php echo esc_html( $timestamp ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Imported By:', 'seo-generator' ); ?></th>
				<td><?php echo esc_html( $username ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Total Rows:', 'seo-generator' ); ?></th>
				<td><?php echo absint( $log['total_rows'] ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Successful:', 'seo-generator' ); ?></th>
				<td>
					<span style="color: #46b450; font-weight: 600;">
						<?php echo absint( $log['success_count'] ); ?>
					</span>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Errors:', 'seo-generator' ); ?></th>
				<td>
					<?php if ( $log['error_count'] > 0 ) : ?>
						<span style="color: #dc3232; font-weight: 600;">
							<?php echo absint( $log['error_count'] ); ?>
						</span>
					<?php else : ?>
						<span style="color: #46b450;">
							<?php esc_html_e( '0', 'seo-generator' ); ?>
						</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Success Rate:', 'seo-generator' ); ?></th>
				<td>
					<strong><?php echo esc_html( $success_rate ); ?>%</strong>
				</td>
			</tr>
		</table>
	</div>

	<?php if ( ! empty( $log['created_posts'] ) && is_array( $log['created_posts'] ) ) : ?>
		<div class="seo-created-posts" style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
			<h2><?php esc_html_e( 'Created Posts', 'seo-generator' ); ?></h2>
			<p><?php echo count( $log['created_posts'] ); ?> <?php esc_html_e( 'posts created during this import.', 'seo-generator' ); ?></p>

			<ul style="list-style: disc; margin-left: 20px;">
				<?php foreach ( $log['created_posts'] as $created_post ) : ?>
					<?php
					// Handle different post data structures.
					if ( is_array( $created_post ) ) {
						$post_id    = $created_post['id'] ?? 0;
						$post_title = $created_post['title'] ?? __( 'Untitled', 'seo-generator' );
					} else {
						$post_id    = $created_post;
						$post_obj   = get_post( $post_id );
						$post_title = $post_obj ? $post_obj->post_title : __( 'Untitled', 'seo-generator' );
					}

					$edit_url = get_edit_post_link( $post_id );
					?>
					<li>
						<?php if ( $edit_url ) : ?>
							<a href="<?php echo esc_url( $edit_url ); ?>" target="_blank">
								<?php echo esc_html( $post_title ); ?>
							</a>
							<span style="color: #646970;">(ID: <?php echo absint( $post_id ); ?>)</span>
						<?php else : ?>
							<?php echo esc_html( $post_title ); ?>
							<span style="color: #646970;">(ID: <?php echo absint( $post_id ); ?>)</span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $log['error_log'] ) && is_array( $log['error_log'] ) ) : ?>
		<div class="seo-error-log" style="background: #fff; border: 1px solid #dc3232; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
			<h2 style="color: #dc3232;"><?php esc_html_e( 'Error Log', 'seo-generator' ); ?></h2>
			<p><?php echo count( $log['error_log'] ); ?> <?php esc_html_e( 'error(s) occurred during this import.', 'seo-generator' ); ?></p>

			<div style="background: #f9f9f9; padding: 15px; border-radius: 3px; max-height: 400px; overflow-y: auto;">
				<ol style="list-style: decimal; margin-left: 20px; font-family: monospace; font-size: 13px;">
					<?php foreach ( $log['error_log'] as $error ) : ?>
						<li style="margin-bottom: 8px; color: #dc3232;">
							<?php echo esc_html( $error ); ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>

			<p style="margin-top: 15px;">
				<a href="<?php echo esc_url( add_query_arg( 'download_error_log', $log_id ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Download Error Log (.txt)', 'seo-generator' ); ?>
				</a>
			</p>
		</div>
	<?php else : ?>
		<div class="notice notice-success inline">
			<p>
				<strong><?php esc_html_e( 'No errors occurred during this import!', 'seo-generator' ); ?></strong>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $log['image_stats'] ) && is_array( $log['image_stats'] ) ) : ?>
		<div class="seo-image-stats" style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px;">
			<h2><?php esc_html_e( 'Image Download Statistics', 'seo-generator' ); ?></h2>

			<table class="form-table">
				<?php if ( isset( $log['image_stats']['total'] ) ) : ?>
					<tr>
						<th><?php esc_html_e( 'Total Images:', 'seo-generator' ); ?></th>
						<td><?php echo absint( $log['image_stats']['total'] ); ?></td>
					</tr>
				<?php endif; ?>
				<?php if ( isset( $log['image_stats']['downloaded'] ) ) : ?>
					<tr>
						<th><?php esc_html_e( 'Successfully Downloaded:', 'seo-generator' ); ?></th>
						<td>
							<span style="color: #46b450; font-weight: 600;">
								<?php echo absint( $log['image_stats']['downloaded'] ); ?>
							</span>
						</td>
					</tr>
				<?php endif; ?>
				<?php if ( isset( $log['image_stats']['failed'] ) ) : ?>
					<tr>
						<th><?php esc_html_e( 'Failed:', 'seo-generator' ); ?></th>
						<td>
							<span style="color: #dc3232; font-weight: 600;">
								<?php echo absint( $log['image_stats']['failed'] ); ?>
							</span>
						</td>
					</tr>
				<?php endif; ?>
				<?php if ( isset( $log['image_stats']['skipped'] ) ) : ?>
					<tr>
						<th><?php esc_html_e( 'Skipped:', 'seo-generator' ); ?></th>
						<td><?php echo absint( $log['image_stats']['skipped'] ); ?></td>
					</tr>
				<?php endif; ?>
			</table>
		</div>
	<?php endif; ?>
</div>

<?php
// Handle error log download.
if ( isset( $_GET['download_error_log'] ) && absint( $_GET['download_error_log'] ) === $log_id ) {
	// Verify nonce for security.
	check_admin_referer( 'download_error_log_' . $log_id );

	if ( ! empty( $log['error_log'] ) && is_array( $log['error_log'] ) ) {
		$filename    = 'import-errors-' . $log_id . '-' . gmdate( 'Y-m-d' ) . '.txt';
		$error_lines = array(
			'Import Error Log',
			'Generated: ' . current_time( 'mysql' ),
			'Import ID: ' . $log_id,
			'Filename: ' . $log['filename'],
			'Import Date: ' . $log['timestamp'],
			'',
			'Errors:',
			'',
		);

		foreach ( $log['error_log'] as $index => $error ) {
			$error_lines[] = ( $index + 1 ) . '. ' . $error;
		}

		$content = implode( "\n", $error_lines );

		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $content ) );
		echo $content;
		exit;
	}
}
?>
