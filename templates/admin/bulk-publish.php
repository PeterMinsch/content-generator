<?php
/**
 * Bulk Publish Page Template
 *
 * CSV upload → AI content generation → dynamic page publishing.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

$dynamic_setup_done = (bool) get_option( 'seo_nextjs_dynamic_setup_done', false );
?>

<div class="wrap seo-generator-page">
	<h1 class="heading-1"><?php esc_html_e( 'Bulk Publish Dynamic Pages', 'seo-generator' ); ?></h1>

	<?php if ( ! $dynamic_setup_done ) : ?>
		<div class="notice notice-warning" style="padding: 15px;">
			<p>
				<strong><?php esc_html_e( 'Dynamic routing is not set up.', 'seo-generator' ); ?></strong>
				<?php esc_html_e( 'Go to Page Builder and click "Setup Dynamic Route" first.', 'seo-generator' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<!-- Step 1: CSV Upload -->
	<div class="seo-card mt-4" id="step-upload">
		<h3 class="seo-card__title">
			<?php esc_html_e( 'Step 1: Upload CSV', 'seo-generator' ); ?>
		</h3>
		<div class="seo-card__content">
			<p><?php esc_html_e( 'Upload a CSV file with keywords to generate pages. Required column: keyword (or title). Optional columns: slug, blocks.', 'seo-generator' ); ?></p>

			<div style="margin: 16px 0;">
				<strong><?php esc_html_e( 'Example CSV format:', 'seo-generator' ); ?></strong>
				<pre style="background: #f0f0f1; padding: 10px; border-radius: 4px; font-size: 12px; overflow-x: auto;">keyword,slug
san diego engagement rings,san-diego-engagement-rings
los angeles diamond rings,los-angeles-diamond-rings
custom wedding bands orange county,custom-wedding-bands-orange-county</pre>
			</div>

			<form id="bulk-publish-upload-form" enctype="multipart/form-data">
				<input type="file" name="csv_file" id="csv-file-input" accept=".csv,.txt" style="margin-bottom: 12px;" />
				<br />
				<button type="submit" class="button button-primary" <?php echo $dynamic_setup_done ? '' : 'disabled'; ?>>
					<?php esc_html_e( 'Upload & Preview', 'seo-generator' ); ?>
				</button>
			</form>

			<div id="upload-status" style="margin-top: 12px; display: none;"></div>
		</div>
	</div>

	<!-- Step 2: Preview & Configure (hidden until CSV uploaded) -->
	<div class="seo-card mt-4" id="step-configure" style="display: none;">
		<h3 class="seo-card__title">
			<?php esc_html_e( 'Step 2: Configure & Preview', 'seo-generator' ); ?>
		</h3>
		<div class="seo-card__content">
			<div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 16px;">
				<div>
					<label for="page-template-select" style="display: block; margin-bottom: 4px; font-weight: 600;">
						<?php esc_html_e( 'Page Template:', 'seo-generator' ); ?>
					</label>
					<select id="page-template-select">
						<option value="homepage"><?php esc_html_e( 'Homepage', 'seo-generator' ); ?></option>
						<option value="about"><?php esc_html_e( 'About Us', 'seo-generator' ); ?></option>
					</select>
				</div>

				<div>
					<label style="display: block; margin-bottom: 4px; font-weight: 600;">
						<?php esc_html_e( 'Column Mapping:', 'seo-generator' ); ?>
					</label>
					<span id="column-mapping-info" style="color: #646970; font-size: 13px;">
						<?php esc_html_e( 'Auto-detected', 'seo-generator' ); ?>
					</span>
				</div>
			</div>

			<!-- CSV Preview Table -->
			<div style="overflow-x: auto; max-height: 400px; overflow-y: auto; margin-bottom: 16px;">
				<table class="wp-list-table widefat striped" id="csv-preview-table">
					<thead id="csv-preview-thead"></thead>
					<tbody id="csv-preview-tbody"></tbody>
				</table>
			</div>

			<div id="csv-summary" style="margin-bottom: 16px; padding: 10px; background: #f0f6fc; border-radius: 4px;"></div>
		</div>
	</div>

	<!-- Step 3: Generate & Publish (hidden until configured) -->
	<div class="seo-card mt-4" id="step-publish" style="display: none;">
		<h3 class="seo-card__title">
			<?php esc_html_e( 'Step 3: Generate & Publish', 'seo-generator' ); ?>
		</h3>
		<div class="seo-card__content">
			<p><?php esc_html_e( 'AI will generate unique content for each page\'s content slots, then publish them instantly via the dynamic route.', 'seo-generator' ); ?></p>

			<div style="display: flex; gap: 12px; margin-bottom: 16px;">
				<button type="button" id="btn-publish-immediate" class="button button-primary">
					<?php esc_html_e( 'Generate & Publish Now', 'seo-generator' ); ?>
				</button>
				<button type="button" id="btn-publish-queued" class="button button-secondary">
					<?php esc_html_e( 'Queue for Background Processing', 'seo-generator' ); ?>
				</button>
			</div>

			<p style="color: #646970; font-size: 12px;">
				<?php esc_html_e( '"Publish Now" processes all rows immediately (best for < 10 pages). "Queue" processes in background (best for larger batches).', 'seo-generator' ); ?>
			</p>

			<!-- Progress -->
			<div id="publish-progress" style="display: none; margin-top: 16px;">
				<div style="background: #f0f0f1; border-radius: 4px; overflow: hidden; height: 24px; margin-bottom: 8px;">
					<div id="progress-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s; border-radius: 4px;"></div>
				</div>
				<p id="progress-text" style="font-size: 13px; color: #646970;"></p>
			</div>
		</div>
	</div>

	<!-- Results (hidden until processing complete) -->
	<div class="seo-card mt-4" id="step-results" style="display: none;">
		<h3 class="seo-card__title">
			<?php esc_html_e( 'Results', 'seo-generator' ); ?>
		</h3>
		<div class="seo-card__content">
			<div id="results-summary" style="margin-bottom: 16px; padding: 12px; border-radius: 4px;"></div>
			<div style="overflow-x: auto; max-height: 400px; overflow-y: auto;">
				<table class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Keyword', 'seo-generator' ); ?></th>
							<th><?php esc_html_e( 'Slug', 'seo-generator' ); ?></th>
							<th><?php esc_html_e( 'Status', 'seo-generator' ); ?></th>
							<th><?php esc_html_e( 'Message', 'seo-generator' ); ?></th>
						</tr>
					</thead>
					<tbody id="results-tbody"></tbody>
				</table>
			</div>
		</div>
	</div>
</div>
