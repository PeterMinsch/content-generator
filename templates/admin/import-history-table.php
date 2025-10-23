<?php
/**
 * Import History Table Template
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

// Get history service.
$history_service = new \SEOGenerator\Services\ImportHistoryService();

// Get current page from URL parameter.
$current_page = isset( $_GET['history_page'] ) ? max( 1, intval( $_GET['history_page'] ) ) : 1;
$per_page     = 10;

// Get history and total count.
$history     = $history_service->getHistory( $current_page, $per_page );
$total_count = $history_service->getHistoryCount();
$total_pages = ceil( $total_count / $per_page );

// Get current user.
$current_user = wp_get_current_user();
?>

<div class="seo-card" id="import-history-section">
	<h3 class="seo-card__title">
		📊 <?php esc_html_e( 'Import History', 'seo-generator' ); ?>
		<?php if ( $total_count > 0 ) : ?>
			<span style="font-weight: normal; font-size: 14px; color: var(--gray-600);">
				(<?php echo esc_html( $total_count ); ?> total)
			</span>
		<?php endif; ?>
	</h3>
	<div class="seo-card__content">
		<?php if ( empty( $history ) ) : ?>
			<p style="color: var(--gray-600); text-align: center; padding: 40px 20px;">
				<?php esc_html_e( 'No import history yet. Complete an import to see it here.', 'seo-generator' ); ?>
			</p>
		<?php else : ?>
			<div style="overflow-x: auto;">
				<table class="wp-list-table widefat fixed striped" style="width: 100%; border-collapse: collapse;">
					<thead>
						<tr>
							<th style="width: 15%; padding: 12px; text-align: left; background: var(--gray-50); border-bottom: 2px solid var(--gray-200);">
								<?php esc_html_e( 'Date & Time', 'seo-generator' ); ?>
							</th>
							<th style="width: 25%; padding: 12px; text-align: left; background: var(--gray-50); border-bottom: 2px solid var(--gray-200);">
								<?php esc_html_e( 'Filename', 'seo-generator' ); ?>
							</th>
							<th style="width: 12%; padding: 12px; text-align: left; background: var(--gray-50); border-bottom: 2px solid var(--gray-200);">
								<?php esc_html_e( 'Type', 'seo-generator' ); ?>
							</th>
							<th style="width: 10%; padding: 12px; text-align: center; background: var(--gray-50); border-bottom: 2px solid var(--gray-200);">
								<?php esc_html_e( 'Total Rows', 'seo-generator' ); ?>
							</th>
							<th style="width: 28%; padding: 12px; text-align: left; background: var(--gray-50); border-bottom: 2px solid var(--gray-200);">
								<?php esc_html_e( 'Results', 'seo-generator' ); ?>
							</th>
							<th style="width: 10%; padding: 12px; text-align: center; background: var(--gray-50); border-bottom: 2px solid var(--gray-200);">
								<?php esc_html_e( 'Actions', 'seo-generator' ); ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $history as $import ) : ?>
							<?php
							$import_id     = isset( $import['id'] ) ? $import['id'] : '';
							$timestamp     = isset( $import['timestamp'] ) ? $import['timestamp'] : 0;
							$filename      = isset( $import['filename'] ) ? $import['filename'] : 'N/A';
							$import_type   = isset( $import['import_type'] ) ? $import['import_type'] : 'csv_upload';
							$total_rows    = isset( $import['total_rows'] ) ? $import['total_rows'] : 0;
							$success_count = isset( $import['success_count'] ) ? $import['success_count'] : 0;
							$error_count   = isset( $import['error_count'] ) ? $import['error_count'] : 0;
							$skipped_count = isset( $import['skipped_count'] ) ? $import['skipped_count'] : 0;
							$user_id       = isset( $import['user_id'] ) ? $import['user_id'] : 0;

							// Get user display name.
							$user = get_userdata( $user_id );
							$username = $user ? $user->display_name : 'Unknown';

							// Format timestamp.
							$date_formatted = $timestamp ? wp_date( 'M j, Y', $timestamp ) : 'N/A';
							$time_formatted = $timestamp ? wp_date( 'g:i A', $timestamp ) : 'N/A';

							// Format import type.
							$type_label = $import_type === 'geographic_titles' ? 'Geographic Titles' : 'CSV Upload';
							$type_color = $import_type === 'geographic_titles' ? '#0073aa' : '#46b450';

							// Calculate success rate.
							$success_rate = $total_rows > 0 ? round( ( $success_count / $total_rows ) * 100 ) : 0;
							$status_color = $success_rate >= 80 ? '#46b450' : ( $success_rate >= 50 ? '#f0b849' : '#dc3232' );
							?>
							<tr>
								<td style="padding: 12px; border-bottom: 1px solid var(--gray-200);">
									<div style="font-weight: 500; color: var(--gray-900);"><?php echo esc_html( $date_formatted ); ?></div>
									<div style="font-size: 12px; color: var(--gray-600);"><?php echo esc_html( $time_formatted ); ?></div>
								</td>
								<td style="padding: 12px; border-bottom: 1px solid var(--gray-200);">
									<div style="font-family: var(--font-mono); font-size: 13px; color: var(--gray-900); word-break: break-all;">
										<?php echo esc_html( $filename ); ?>
									</div>
									<div style="font-size: 12px; color: var(--gray-600); margin-top: 4px;">
										By <?php echo esc_html( $username ); ?>
									</div>
								</td>
								<td style="padding: 12px; border-bottom: 1px solid var(--gray-200);">
									<span style="display: inline-block; padding: 4px 8px; font-size: 11px; font-weight: 600; text-transform: uppercase; border-radius: 4px; background: <?php echo esc_attr( $type_color ); ?>20; color: <?php echo esc_attr( $type_color ); ?>;">
										<?php echo esc_html( $type_label ); ?>
									</span>
								</td>
								<td style="padding: 12px; border-bottom: 1px solid var(--gray-200); text-align: center;">
									<div style="font-weight: 600; color: var(--gray-900);"><?php echo esc_html( number_format( $total_rows ) ); ?></div>
								</td>
								<td style="padding: 12px; border-bottom: 1px solid var(--gray-200);">
									<div style="display: flex; gap: 12px; align-items: center;">
										<?php if ( $success_count > 0 ) : ?>
											<div style="display: flex; align-items: center; gap: 4px;">
												<span style="width: 8px; height: 8px; border-radius: 50%; background: #46b450; display: inline-block;"></span>
												<span style="font-size: 13px; color: var(--gray-700);">
													<strong><?php echo esc_html( $success_count ); ?></strong> created
												</span>
											</div>
										<?php endif; ?>

										<?php if ( $skipped_count > 0 ) : ?>
											<div style="display: flex; align-items: center; gap: 4px;">
												<span style="width: 8px; height: 8px; border-radius: 50%; background: #f0b849; display: inline-block;"></span>
												<span style="font-size: 13px; color: var(--gray-700);">
													<strong><?php echo esc_html( $skipped_count ); ?></strong> skipped
												</span>
											</div>
										<?php endif; ?>

										<?php if ( $error_count > 0 ) : ?>
											<div style="display: flex; align-items: center; gap: 4px;">
												<span style="width: 8px; height: 8px; border-radius: 50%; background: #dc3232; display: inline-block;"></span>
												<span style="font-size: 13px; color: var(--gray-700);">
													<strong><?php echo esc_html( $error_count ); ?></strong> errors
												</span>
											</div>
										<?php endif; ?>
									</div>
									<div style="margin-top: 6px; width: 100%; height: 4px; background: var(--gray-200); border-radius: 2px; overflow: hidden;">
										<div style="width: <?php echo esc_attr( $success_rate ); ?>%; height: 100%; background: <?php echo esc_attr( $status_color ); ?>; transition: width 0.3s ease;"></div>
									</div>
								</td>
								<td style="padding: 12px; border-bottom: 1px solid var(--gray-200); text-align: center;">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-import-keywords&action=view_log&import_id=' . urlencode( $import_id ) ) ); ?>"
									   class="button button-small"
									   style="font-size: 12px;">
										<?php esc_html_e( 'View Details', 'seo-generator' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<?php if ( $total_pages > 1 ) : ?>
				<div style="margin-top: 20px; display: flex; justify-content: center; align-items: center; gap: 8px;">
					<?php
					// Previous button.
					if ( $current_page > 1 ) {
						$prev_url = add_query_arg( 'history_page', $current_page - 1 );
						echo '<a href="' . esc_url( $prev_url ) . '" class="button">&laquo; ' . esc_html__( 'Previous', 'seo-generator' ) . '</a>';
					}

					// Page numbers.
					for ( $i = 1; $i <= $total_pages; $i++ ) {
						$page_url = add_query_arg( 'history_page', $i );
						$is_current = $i === $current_page;
						$button_class = $is_current ? 'button button-primary' : 'button';
						echo '<a href="' . esc_url( $page_url ) . '" class="' . esc_attr( $button_class ) . '">' . esc_html( $i ) . '</a>';
					}

					// Next button.
					if ( $current_page < $total_pages ) {
						$next_url = add_query_arg( 'history_page', $current_page + 1 );
						echo '<a href="' . esc_url( $next_url ) . '" class="button">' . esc_html__( 'Next', 'seo-generator' ) . ' &raquo;</a>';
					}
					?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
