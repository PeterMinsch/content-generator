<?php
/**
 * Import History Table Template
 *
 * Displays paginated table of import history logs.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

// Get repository.
$import_log_repo = new \SEOGenerator\Repositories\ImportLogRepository();

// Get pagination parameters.
$paged    = isset( $_GET['log_paged'] ) ? absint( $_GET['log_paged'] ) : 1;
$per_page = 10;
$offset   = ( $paged - 1 ) * $per_page;

// Get import logs and total count.
$logs        = $import_log_repo->findAll( $per_page, $offset );
$total_count = $import_log_repo->count();
$total_pages = ceil( $total_count / $per_page );

?>

<div class="wrap seo-import-history">
	<h2><?php esc_html_e( 'Import History', 'seo-generator' ); ?></h2>

	<?php if ( empty( $logs ) ) : ?>
		<p><?php esc_html_e( 'No import history found.', 'seo-generator' ); ?></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col" class="column-date"><?php esc_html_e( 'Date/Time', 'seo-generator' ); ?></th>
					<th scope="col" class="column-filename"><?php esc_html_e( 'Filename', 'seo-generator' ); ?></th>
					<th scope="col" class="column-total"><?php esc_html_e( 'Total Rows', 'seo-generator' ); ?></th>
					<th scope="col" class="column-success"><?php esc_html_e( 'Success', 'seo-generator' ); ?></th>
					<th scope="col" class="column-errors"><?php esc_html_e( 'Errors', 'seo-generator' ); ?></th>
					<th scope="col" class="column-user"><?php esc_html_e( 'User', 'seo-generator' ); ?></th>
					<th scope="col" class="column-actions"><?php esc_html_e( 'Actions', 'seo-generator' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $logs as $log ) : ?>
					<?php
					$user      = get_userdata( $log['user_id'] );
					$username  = $user ? $user->display_name : __( 'Unknown', 'seo-generator' );
					$timestamp = mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $log['timestamp'] );

					// Build details URL.
					$details_url = add_query_arg(
						array(
							'page'   => 'seo-generator-import',
							'action' => 'view_log',
							'log_id' => $log['id'],
						),
						admin_url( 'admin.php' )
					);
					?>
					<tr>
						<td class="column-date">
							<?php echo esc_html( $timestamp ); ?>
						</td>
						<td class="column-filename">
							<strong><?php echo esc_html( $log['filename'] ); ?></strong>
						</td>
						<td class="column-total">
							<?php echo absint( $log['total_rows'] ); ?>
						</td>
						<td class="column-success">
							<span class="seo-success-count">
								<?php echo absint( $log['success_count'] ); ?>
							</span>
						</td>
						<td class="column-errors">
							<?php if ( $log['error_count'] > 0 ) : ?>
								<span class="seo-error-count" style="color: #dc3232; font-weight: 600;">
									<?php echo absint( $log['error_count'] ); ?>
								</span>
							<?php else : ?>
								<span style="color: #46b450;">
									<?php esc_html_e( '0', 'seo-generator' ); ?>
								</span>
							<?php endif; ?>
						</td>
						<td class="column-user">
							<?php echo esc_html( $username ); ?>
						</td>
						<td class="column-actions">
							<a href="<?php echo esc_url( $details_url ); ?>" class="button button-small">
								<?php esc_html_e( 'View Details', 'seo-generator' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<?php
					echo paginate_links(
						array(
							'base'      => add_query_arg( 'log_paged', '%#%' ),
							'format'    => '',
							'current'   => $paged,
							'total'     => $total_pages,
							'prev_text' => '&laquo;',
							'next_text' => '&raquo;',
						)
					);
					?>
				</div>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>

<style>
.seo-import-history .column-date { width: 15%; }
.seo-import-history .column-filename { width: 25%; }
.seo-import-history .column-total { width: 10%; text-align: center; }
.seo-import-history .column-success { width: 10%; text-align: center; }
.seo-import-history .column-errors { width: 10%; text-align: center; }
.seo-import-history .column-user { width: 15%; }
.seo-import-history .column-actions { width: 15%; }
</style>
