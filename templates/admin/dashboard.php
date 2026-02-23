<?php
/**
 * Dashboard Template
 *
 * @package SEOGenerator
 * @var array $data Dashboard data from DashboardCustomizer::getDashboardData()
 */

defined( 'ABSPATH' ) || exit;

$greeting    = sprintf(
	/* translators: %s: user display name */
	__( 'Welcome back, %s', 'seo-generator' ),
	esc_html( $data['current_user']->display_name )
);
$has_queue   = $data['queue_stats']['pending'] > 0 || $data['queue_stats']['processing'] > 0;
$has_failed  = $data['queue_stats']['failed'] > 0;
?>
<div class="wrap seo-generator-page seo-dashboard-wrap">

	<!-- Header -->
	<div class="seo-dashboard-header">
		<div class="seo-dashboard-header__left">
			<h1 class="seo-dashboard-title"><?php echo esc_html( $greeting ); ?></h1>
			<?php if ( ! empty( $data['business_name'] ) ) : ?>
				<p class="seo-dashboard-subtitle"><?php echo esc_html( $data['business_name'] ); ?> Content Manager</p>
			<?php endif; ?>
		</div>
		<div class="seo-dashboard-header__right">
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=seo-page' ) ); ?>" class="seo-btn-primary">
				<span class="dashicons dashicons-plus-alt2"></span>
				<?php esc_html_e( 'New Page', 'seo-generator' ); ?>
			</a>
		</div>
	</div>

	<!-- Queue Status Bar -->
	<?php if ( $has_queue || $has_failed ) : ?>
		<div class="seo-dashboard-queue-bar <?php echo $has_failed ? 'seo-dashboard-queue-bar--warning' : ''; ?>">
			<?php if ( $has_queue ) : ?>
				<span class="dashicons dashicons-update seo-spin"></span>
				<span>
					<?php
					printf(
						/* translators: %1$d: pending count, %2$d: processing count */
						esc_html__( 'Queue active: %1$d pending, %2$d processing', 'seo-generator' ),
						(int) $data['queue_stats']['pending'],
						(int) $data['queue_stats']['processing']
					);
					?>
				</span>
			<?php endif; ?>
			<?php if ( $has_failed ) : ?>
				<span class="seo-dashboard-queue-failed">
					<span class="dashicons dashicons-warning"></span>
					<?php
					printf(
						/* translators: %d: failed count */
						esc_html__( '%d failed', 'seo-generator' ),
						(int) $data['queue_stats']['failed']
					);
					?>
				</span>
			<?php endif; ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-generation-queue' ) ); ?>" class="seo-dashboard-queue-link">
				<?php esc_html_e( 'View Queue', 'seo-generator' ); ?> &rarr;
			</a>
		</div>
	<?php endif; ?>

	<!-- Stats Grid -->
	<div class="seo-dashboard-stats">
		<div class="seo-card seo-dashboard-stat">
			<div class="seo-card__content">
				<span class="seo-dashboard-stat__value"><?php echo (int) $data['total_pages']; ?></span>
				<span class="seo-dashboard-stat__label"><?php esc_html_e( 'Total Pages', 'seo-generator' ); ?></span>
			</div>
		</div>
		<div class="seo-card seo-dashboard-stat">
			<div class="seo-card__content">
				<span class="seo-dashboard-stat__value seo-dashboard-stat__value--success"><?php echo (int) $data['published']; ?></span>
				<span class="seo-dashboard-stat__label"><?php esc_html_e( 'Published', 'seo-generator' ); ?></span>
			</div>
		</div>
		<div class="seo-card seo-dashboard-stat">
			<div class="seo-card__content">
				<span class="seo-dashboard-stat__value seo-dashboard-stat__value--warning"><?php echo (int) $data['drafts']; ?></span>
				<span class="seo-dashboard-stat__label"><?php esc_html_e( 'Drafts', 'seo-generator' ); ?></span>
			</div>
		</div>
		<div class="seo-card seo-dashboard-stat">
			<div class="seo-card__content">
				<span class="seo-dashboard-stat__value seo-dashboard-stat__value--info"><?php echo (int) $data['queue_pending']; ?></span>
				<span class="seo-dashboard-stat__label"><?php esc_html_e( 'Queue Pending', 'seo-generator' ); ?></span>
			</div>
		</div>
	</div>

	<!-- Two Column Layout -->
	<div class="seo-dashboard-columns">

		<!-- Quick Actions -->
		<div class="seo-card seo-dashboard-actions-card">
			<h3 class="seo-card__title"><?php esc_html_e( 'Quick Actions', 'seo-generator' ); ?></h3>
			<div class="seo-card__content">
				<div class="seo-dashboard-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-import-keywords' ) ); ?>" class="seo-dashboard-action">
						<span class="dashicons dashicons-upload"></span>
						<span><?php esc_html_e( 'Import CSV', 'seo-generator' ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=seo-page' ) ); ?>" class="seo-dashboard-action">
						<span class="dashicons dashicons-plus-alt2"></span>
						<span><?php esc_html_e( 'Create Page', 'seo-generator' ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=seo-page' ) ); ?>" class="seo-dashboard-action">
						<span class="dashicons dashicons-admin-page"></span>
						<span><?php esc_html_e( 'All Pages', 'seo-generator' ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-generation-queue' ) ); ?>" class="seo-dashboard-action">
						<span class="dashicons dashicons-list-view"></span>
						<span><?php esc_html_e( 'Queue', 'seo-generator' ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-generator-settings' ) ); ?>" class="seo-dashboard-action">
						<span class="dashicons dashicons-admin-generic"></span>
						<span><?php esc_html_e( 'Settings', 'seo-generator' ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-geographic-titles' ) ); ?>" class="seo-dashboard-action">
						<span class="dashicons dashicons-location"></span>
						<span><?php esc_html_e( 'Geo Titles', 'seo-generator' ); ?></span>
					</a>
				</div>
			</div>
		</div>

		<!-- Recent Pages -->
		<div class="seo-card seo-dashboard-recent-card">
			<h3 class="seo-card__title"><?php esc_html_e( 'Recent Pages', 'seo-generator' ); ?></h3>
			<div class="seo-card__content">
				<?php if ( ! empty( $data['recent_pages'] ) ) : ?>
					<table class="seo-dashboard-recent-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Title', 'seo-generator' ); ?></th>
								<th><?php esc_html_e( 'Status', 'seo-generator' ); ?></th>
								<th><?php esc_html_e( 'Modified', 'seo-generator' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $data['recent_pages'] as $page ) : ?>
								<tr>
									<td>
										<a href="<?php echo esc_url( get_edit_post_link( $page->ID ) ); ?>">
											<?php echo esc_html( $page->post_title ?: __( '(no title)', 'seo-generator' ) ); ?>
										</a>
									</td>
									<td>
										<?php
										$status_class = 'publish' === $page->post_status ? 'seo-badge--published' : 'seo-badge--pending';
										$status_label = 'publish' === $page->post_status ? __( 'Published', 'seo-generator' ) : __( 'Draft', 'seo-generator' );
										?>
										<span class="seo-badge <?php echo esc_attr( $status_class ); ?>">
											<?php echo esc_html( $status_label ); ?>
										</span>
									</td>
									<td class="seo-dashboard-recent-date">
										<?php echo esc_html( human_time_diff( strtotime( $page->post_modified ), current_time( 'timestamp' ) ) . ' ago' ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<div class="seo-dashboard-recent-footer">
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=seo-page' ) ); ?>">
							<?php esc_html_e( 'View all pages', 'seo-generator' ); ?> &rarr;
						</a>
					</div>
				<?php else : ?>
					<p class="seo-dashboard-empty"><?php esc_html_e( 'No pages yet. Create your first page to get started!', 'seo-generator' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

	</div>

</div>
