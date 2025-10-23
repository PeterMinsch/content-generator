<?php
/**
 * Import Details View Template
 *
 * Displays detailed information about a single import log.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

// Get import ID from URL.
$import_id = isset( $_GET['import_id'] ) ? sanitize_text_field( wp_unslash( $_GET['import_id'] ) ) : '';

if ( ! $import_id ) {
	wp_die( esc_html__( 'Invalid import ID.', 'seo-generator' ) );
}

// Get history service and fetch import details.
$history_service = new \SEOGenerator\Services\ImportHistoryService();
$import          = $history_service->getImportDetails( $import_id );

if ( ! $import ) {
	wp_die( esc_html__( 'Import not found.', 'seo-generator' ) );
}

// Get user info.
$user_id  = isset( $import['user_id'] ) ? $import['user_id'] : 0;
$user     = get_userdata( $user_id );
$username = $user ? $user->display_name : __( 'Unknown', 'seo-generator' );

// Format timestamp.
$timestamp      = isset( $import['timestamp'] ) ? $import['timestamp'] : 0;
$date_formatted = $timestamp ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) : 'N/A';

// Get import data.
$filename      = isset( $import['filename'] ) ? $import['filename'] : 'N/A';
$import_type   = isset( $import['import_type'] ) ? $import['import_type'] : 'csv_upload';
$total_rows    = isset( $import['total_rows'] ) ? $import['total_rows'] : 0;
$success_count = isset( $import['success_count'] ) ? $import['success_count'] : 0;
$error_count   = isset( $import['error_count'] ) ? $import['error_count'] : 0;
$skipped_count = isset( $import['skipped_count'] ) ? $import['skipped_count'] : 0;
$errors        = isset( $import['errors'] ) && is_array( $import['errors'] ) ? $import['errors'] : array();
$logs          = isset( $import['logs'] ) && is_array( $import['logs'] ) ? $import['logs'] : array();

// Calculate success rate.
$success_rate = $total_rows > 0 ? round( ( $success_count / $total_rows ) * 100, 1 ) : 0;

// Format import type.
$type_label = $import_type === 'geographic_titles' ? 'Geographic Titles' : 'CSV Upload';

?>

<div class="wrap seo-generator-page">
	<h1 class="heading-1"><?php esc_html_e( 'Import Details', 'seo-generator' ); ?></h1>

	<p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-import-keywords' ) ); ?>" class="button">
			&larr; <?php esc_html_e( 'Back to Import Page', 'seo-generator' ); ?>
		</a>
	</p>

	<!-- Import Summary Card -->
	<div class="seo-card mt-4">
		<h3 class="seo-card__title">
			📋 <?php esc_html_e( 'Import Summary', 'seo-generator' ); ?>
		</h3>
		<div class="seo-card__content">
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Filename:', 'seo-generator' ); ?></th>
						<td>
							<code style="padding: 4px 8px; background: var(--gray-100); border-radius: 4px; font-size: 13px;">
								<?php echo esc_html( $filename ); ?>
							</code>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Import Type:', 'seo-generator' ); ?></th>
						<td>
							<span style="display: inline-block; padding: 4px 12px; background: #0073aa20; color: #0073aa; border-radius: 4px; font-weight: 600; font-size: 12px; text-transform: uppercase;">
								<?php echo esc_html( $type_label ); ?>
							</span>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Import Date/Time:', 'seo-generator' ); ?></th>
						<td><?php echo esc_html( $date_formatted ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Imported By:', 'seo-generator' ); ?></th>
						<td><?php echo esc_html( $username ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Total Rows:', 'seo-generator' ); ?></th>
						<td><strong><?php echo esc_html( number_format( $total_rows ) ); ?></strong></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Successful:', 'seo-generator' ); ?></th>
						<td>
							<span style="color: #46b450; font-weight: 600; font-size: 16px;">
								<?php echo esc_html( number_format( $success_count ) ); ?>
							</span>
							<span style="color: var(--gray-600); margin-left: 8px;">
								(<?php echo esc_html( $success_rate ); ?>%)
							</span>
						</td>
					</tr>
					<?php if ( $skipped_count > 0 ) : ?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Skipped:', 'seo-generator' ); ?></th>
							<td>
								<span style="color: #f0b849; font-weight: 600; font-size: 16px;">
									<?php echo esc_html( number_format( $skipped_count ) ); ?>
								</span>
							</td>
						</tr>
					<?php endif; ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Errors:', 'seo-generator' ); ?></th>
						<td>
							<?php if ( $error_count > 0 ) : ?>
								<span style="color: #dc3232; font-weight: 600; font-size: 16px;">
									<?php echo esc_html( number_format( $error_count ) ); ?>
								</span>
							<?php else : ?>
								<span style="color: #46b450; font-weight: 600;">
									<?php esc_html_e( '0', 'seo-generator' ); ?>
								</span>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>

			<!-- Visual Progress Bar -->
			<div style="margin-top: 24px;">
				<div style="margin-bottom: 8px; font-weight: 600; color: var(--gray-700);">
					<?php esc_html_e( 'Success Rate:', 'seo-generator' ); ?>
					<span style="float: right;"><?php echo esc_html( $success_rate ); ?>%</span>
				</div>
				<div style="width: 100%; height: 24px; background: var(--gray-200); border-radius: 12px; overflow: hidden; position: relative;">
					<div style="width: <?php echo esc_attr( $success_rate ); ?>%; height: 100%; background: linear-gradient(90deg, #46b450, #2e7d32); transition: width 0.5s ease;"></div>
				</div>
			</div>
		</div>
	</div>

	<!-- Logs Section -->
	<?php if ( ! empty( $logs ) ) : ?>
		<div class="seo-card mt-4">
			<h3 class="seo-card__title">
				📝 <?php esc_html_e( 'Import Logs', 'seo-generator' ); ?>
			</h3>
			<div class="seo-card__content">
				<div style="background: #f9f9f9; padding: 16px; border-radius: 6px; border: 1px solid var(--gray-200);">
					<ul style="list-style: none; margin: 0; padding: 0; font-family: var(--font-mono); font-size: 13px;">
						<?php foreach ( $logs as $log_entry ) : ?>
							<li style="padding: 8px 0; border-bottom: 1px solid var(--gray-200); color: var(--gray-700);">
								<span style="color: #0073aa; margin-right: 8px;">→</span>
								<?php echo esc_html( $log_entry ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<!-- Error Log Section -->
	<?php if ( ! empty( $errors ) ) : ?>
		<div class="seo-card mt-4" style="border-left: 4px solid #dc3232;">
			<h3 class="seo-card__title" style="color: #dc3232;">
				⚠️ <?php esc_html_e( 'Error Log', 'seo-generator' ); ?>
			</h3>
			<div class="seo-card__content">
				<p style="color: var(--gray-700); margin-bottom: 16px;">
					<?php echo esc_html( count( $errors ) ); ?>
					<?php esc_html_e( 'error(s) occurred during this import.', 'seo-generator' ); ?>
				</p>

				<div style="background: #fff9f9; padding: 16px; border-radius: 6px; border: 1px solid #ffc9c9; max-height: 400px; overflow-y: auto;">
					<ol style="list-style: decimal; margin-left: 20px; font-family: var(--font-mono); font-size: 13px; color: #a00;">
						<?php foreach ( $errors as $error ) : ?>
							<li style="margin-bottom: 8px; padding: 4px 0;">
								<?php echo esc_html( $error ); ?>
							</li>
						<?php endforeach; ?>
					</ol>
				</div>
			</div>
		</div>
	<?php else : ?>
		<div class="seo-card mt-4" style="border-left: 4px solid #46b450;">
			<div class="seo-card__content">
				<p style="color: #46b450; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px;">
					<span style="font-size: 20px;">✓</span>
					<?php esc_html_e( 'No errors occurred during this import!', 'seo-generator' ); ?>
				</p>
			</div>
		</div>
	<?php endif; ?>
</div>
