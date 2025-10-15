<?php
/**
 * CSV Import Page Template
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

// Enqueue admin styles.
wp_enqueue_style(
	'seo-generator-import',
	plugin_dir_url( dirname( __DIR__ ) ) . 'assets/css/admin-import.css',
	array(),
	'1.0.0'
);

// Enqueue column mapping script.
wp_enqueue_script(
	'seo-generator-column-mapping',
	plugin_dir_url( dirname( __DIR__ ) ) . 'assets/js/build/column-mapping.js',
	array( 'wp-api-fetch' ),
	'1.0.0',
	true
);

// Localize script with AJAX data.
wp_localize_script(
	'seo-generator-column-mapping',
	'seoImportData',
	array(
		'nonce'    => wp_create_nonce( 'seo_csv_upload' ),
		'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
	)
);

// Get max upload size.
$max_upload_size = wp_max_upload_size();
?>

<div class="wrap seo-import-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Import Keywords', 'seo-generator' ); ?></h1>

	<hr class="wp-header-end">

	<!-- Instructions Section -->
	<div class="import-instructions">
		<h2><?php esc_html_e( 'CSV Import Instructions', 'seo-generator' ); ?></h2>

		<div class="notice notice-info">
			<p><strong><?php esc_html_e( 'Supported CSV Format:', 'seo-generator' ); ?></strong></p>
			<ul style="list-style: disc; margin-left: 20px;">
				<li><?php esc_html_e( 'File format: .csv only', 'seo-generator' ); ?></li>
				<li><?php esc_html_e( 'Encoding: UTF-8 recommended', 'seo-generator' ); ?></li>
				<li>
					<?php
					printf(
						/* translators: %s: maximum file size */
						esc_html__( 'Maximum file size: %s', 'seo-generator' ),
						esc_html( size_format( $max_upload_size ) )
					);
					?>
				</li>
				<li><?php esc_html_e( 'Maximum rows: 1000 per import', 'seo-generator' ); ?></li>
			</ul>
		</div>

		<div class="notice notice-warning">
			<p><strong><?php esc_html_e( 'Required Columns:', 'seo-generator' ); ?></strong></p>
			<p><?php esc_html_e( 'Your CSV must have at least one column that can be mapped to "Page Title" (typically: keyword, title, or query).', 'seo-generator' ); ?></p>
		</div>

		<h3><?php esc_html_e( 'Optional Columns:', 'seo-generator' ); ?></h3>
		<ul style="list-style: disc; margin-left: 20px;">
			<li><strong><?php esc_html_e( 'Focus Keyword:', 'seo-generator' ); ?></strong> <?php esc_html_e( 'Column names: keyword, search_query', 'seo-generator' ); ?></li>
			<li><strong><?php esc_html_e( 'Topic Category:', 'seo-generator' ); ?></strong> <?php esc_html_e( 'Column names: intent, category, topic', 'seo-generator' ); ?></li>
			<li><strong><?php esc_html_e( 'Search Volume:', 'seo-generator' ); ?></strong> <?php esc_html_e( 'Column names: volume, searches (can be skipped)', 'seo-generator' ); ?></li>
			<li><strong><?php esc_html_e( 'Image URL:', 'seo-generator' ); ?></strong> <?php esc_html_e( 'Column names: image_url, image', 'seo-generator' ); ?></li>
		</ul>

		<div class="notice notice-info" style="margin-top: 20px;">
			<h3 style="margin-top: 0;"><?php esc_html_e( 'Image Downloads', 'seo-generator' ); ?></h3>
			<p><strong><?php esc_html_e( 'Automatic Image Downloading:', 'seo-generator' ); ?></strong></p>
			<p><?php esc_html_e( 'When your CSV includes an "Image URL" column, the plugin will automatically download images and assign them to the imported pages.', 'seo-generator' ); ?></p>

			<p><strong><?php esc_html_e( 'Supported Image Types:', 'seo-generator' ); ?></strong></p>
			<ul style="list-style: disc; margin-left: 20px;">
				<li><?php esc_html_e( 'JPEG (.jpg, .jpeg)', 'seo-generator' ); ?></li>
				<li><?php esc_html_e( 'PNG (.png)', 'seo-generator' ); ?></li>
				<li><?php esc_html_e( 'GIF (.gif)', 'seo-generator' ); ?></li>
				<li><?php esc_html_e( 'WebP (.webp)', 'seo-generator' ); ?></li>
			</ul>

			<p><strong><?php esc_html_e( 'Image Requirements:', 'seo-generator' ); ?></strong></p>
			<ul style="list-style: disc; margin-left: 20px;">
				<li><?php esc_html_e( 'URLs must use HTTP or HTTPS protocol', 'seo-generator' ); ?></li>
				<li>
					<?php
					$image_settings = get_option( 'seo_generator_image_settings', array() );
					$max_size       = $image_settings['max_image_size'] ?? 5;
					printf(
						/* translators: %d: maximum image size in MB */
						esc_html__( 'Maximum file size: %d MB (configurable in settings)', 'seo-generator' ),
						intval( $max_size )
					);
					?>
				</li>
				<li>
					<?php
					$timeout = $image_settings['image_download_timeout'] ?? 30;
					printf(
						/* translators: %d: timeout in seconds */
						esc_html__( 'Download timeout: %d seconds (configurable in settings)', 'seo-generator' ),
						intval( $timeout )
					);
					?>
				</li>
			</ul>

			<p><strong><?php esc_html_e( 'Important Notes:', 'seo-generator' ); ?></strong></p>
			<ul style="list-style: disc; margin-left: 20px;">
				<li><?php esc_html_e( 'Images are downloaded to your WordPress Media Library', 'seo-generator' ); ?></li>
				<li><?php esc_html_e( 'Duplicate images are detected and reused (matched by URL)', 'seo-generator' ); ?></li>
				<li><?php esc_html_e( 'Downloaded images are set as both the featured image and hero_image ACF field', 'seo-generator' ); ?></li>
				<li><?php esc_html_e( 'If an image download fails, the import continues with the remaining posts', 'seo-generator' ); ?></li>
				<li><?php esc_html_e( 'Failed downloads are logged and included in the import summary', 'seo-generator' ); ?></li>
				<li><?php esc_html_e( 'Large images may fail to download - optimize images before hosting', 'seo-generator' ); ?></li>
			</ul>

			<p><strong><?php esc_html_e( 'Configuration:', 'seo-generator' ); ?></strong></p>
			<p>
				<?php
				printf(
					/* translators: %s: settings page URL */
					wp_kses_post( __( 'You can adjust download timeout, maximum file size, and duplicate detection settings in the <a href="%s">Image Settings</a> page.', 'seo-generator' ) ),
					esc_url( admin_url( 'admin.php?page=seo-generator-settings&tab=images' ) )
				);
				?>
			</p>
		</div>

		<h3><?php esc_html_e( 'Example CSV Format:', 'seo-generator' ); ?></h3>
		<pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow-x: auto;">
keyword,intent,search_volume,image_url
platinum wedding bands,commercial,1000,https://example.com/image.jpg
men's tungsten rings,commercial,800,
		</pre>
	</div>

	<!-- Upload Form -->
	<div class="import-upload-section">
		<h2><?php esc_html_e( 'Step 1: Upload CSV File', 'seo-generator' ); ?></h2>

		<form method="post" enctype="multipart/form-data" id="csv-upload-form">
			<input type="hidden" name="action" value="seo_upload_csv">
			<?php wp_nonce_field( 'seo_csv_upload', 'seo_csv_nonce' ); ?>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="csv_file"><?php esc_html_e( 'Select CSV File', 'seo-generator' ); ?></label>
						</th>
						<td>
							<input type="file" name="csv_file" id="csv_file" accept=".csv" required>
							<p class="description">
								<?php
								printf(
									/* translators: %s: maximum file size */
									esc_html__( 'Maximum upload size: %s', 'seo-generator' ),
									esc_html( size_format( $max_upload_size ) )
								);
								?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary">
					<span class="dashicons dashicons-upload" style="vertical-align: middle;"></span>
					<?php esc_html_e( 'Upload & Map', 'seo-generator' ); ?>
				</button>
			</p>
		</form>
	</div>

	<!-- Column Mapping Section -->
	<div id="column-mapping-section" class="import-section" style="display:none;">
		<h2><?php esc_html_e( 'Step 2: Map CSV Columns', 'seo-generator' ); ?></h2>
		<p><?php esc_html_e( 'Select how each CSV column should be mapped to page fields. The system has auto-detected likely mappings based on column names.', 'seo-generator' ); ?></p>

		<div id="column-mapping-table"></div>

		<h3 style="margin-top: 30px;"><?php esc_html_e( 'Preview (First 3 Rows)', 'seo-generator' ); ?></h3>
		<div id="preview-table-container"></div>

		<!-- Import Options Section -->
		<div class="import-options-section" style="margin-top: 30px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 3px;">
			<h3><?php esc_html_e( 'Import Options', 'seo-generator' ); ?></h3>

			<fieldset style="margin-bottom: 15px;">
				<legend class="screen-reader-text">
					<?php esc_html_e( 'Generation mode', 'seo-generator' ); ?>
				</legend>

				<p><strong><?php esc_html_e( 'Content Generation:', 'seo-generator' ); ?></strong></p>

				<label style="display: block; margin-bottom: 10px;">
					<input type="radio" name="generation_mode" value="drafts_only" checked>
					<?php esc_html_e( 'Create drafts only (generate content manually later)', 'seo-generator' ); ?>
				</label>

				<label style="display: block; margin-bottom: 10px;">
					<input type="radio" name="generation_mode" value="auto_generate">
					<?php esc_html_e( 'Auto-generate content in background', 'seo-generator' ); ?>
				</label>

				<p class="description">
					<?php esc_html_e( 'Background generation processes one page every 3 minutes to respect API rate limits. Requires WordPress Cron or server cron to be active.', 'seo-generator' ); ?>
				</p>
			</fieldset>

			<!-- Block Selection (only shown when auto_generate is selected) -->
			<div id="block-selection-section" style="display: none; margin: 20px 0; padding: 15px; background: #fff; border: 1px solid #ccc; border-radius: 3px;">
				<p><strong><?php esc_html_e( 'Select Blocks to Generate:', 'seo-generator' ); ?></strong></p>
				<p class="description" style="margin-bottom: 15px;">
					<?php esc_html_e( 'Choose which content blocks to generate. Generating fewer blocks is faster and costs less. Leave all unchecked to generate all 12 blocks.', 'seo-generator' ); ?>
				</p>

				<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
					<label style="display: flex; align-items: center;">
						<input type="checkbox" name="blocks_to_generate[]" value="hero" checked>
						<span style="margin-left: 5px;"><?php esc_html_e( 'Hero Section', 'seo-generator' ); ?> ⭐</span>
					</label>
					<label style="display: flex; align-items: center;">
						<input type="checkbox" name="blocks_to_generate[]" value="serp_answer" checked>
						<span style="margin-left: 5px;"><?php esc_html_e( 'SERP Answer', 'seo-generator' ); ?> ⭐</span>
					</label>
					<label style="display: flex; align-items: center;">
						<input type="checkbox" name="blocks_to_generate[]" value="product_criteria">
						<span style="margin-left: 5px;"><?php esc_html_e( 'Product Criteria', 'seo-generator' ); ?></span>
					</label>
					<label style="display: flex; align-items: center;">
						<input type="checkbox" name="blocks_to_generate[]" value="materials">
						<span style="margin-left: 5px;"><?php esc_html_e( 'Materials', 'seo-generator' ); ?></span>
					</label>
					<label style="display: flex; align-items: center;">
						<input type="checkbox" name="blocks_to_generate[]" value="process">
						<span style="margin-left: 5px;"><?php esc_html_e( 'Process', 'seo-generator' ); ?></span>
					</label>
					<label style="display: flex; align-items: center;">
						<input type="checkbox" name="blocks_to_generate[]" value="comparison">
						<span style="margin-left: 5px;"><?php esc_html_e( 'Comparison', 'seo-generator' ); ?></span>
					</label>
					<label style="display: flex; align-items: center;">
						<input type="checkbox" name="blocks_to_generate[]" value="product_showcase">
						<span style="margin-left: 5px;"><?php esc_html_e( 'Product Showcase', 'seo-generator' ); ?></span>
					</label>
					<label style="display: flex; align-items: center;">
						<input type="checkbox" name="blocks_to_generate[]" value="size_fit">
						<span style="margin-left: 5px;"><?php esc_html_e( 'Size & Fit', 'seo-generator' ); ?></span>
					</label>
					<label style="display: flex; align-items: center;">
						<input type="checkbox" name="blocks_to_generate[]" value="care_warranty">
						<span style="margin-left: 5px;"><?php esc_html_e( 'Care & Warranty', 'seo-generator' ); ?></span>
					</label>
					<label style="display: flex; align-items: center;">
						<input type="checkbox" name="blocks_to_generate[]" value="ethics">
						<span style="margin-left: 5px;"><?php esc_html_e( 'Ethics & Origin', 'seo-generator' ); ?></span>
					</label>
					<label style="display: flex; align-items: center;">
						<input type="checkbox" name="blocks_to_generate[]" value="faqs" checked>
						<span style="margin-left: 5px;"><?php esc_html_e( 'FAQs', 'seo-generator' ); ?> ⭐</span>
					</label>
					<label style="display: flex; align-items: center;">
						<input type="checkbox" name="blocks_to_generate[]" value="cta" checked>
						<span style="margin-left: 5px;"><?php esc_html_e( 'Call to Action', 'seo-generator' ); ?> ⭐</span>
					</label>
				</div>

				<p class="description" style="margin-top: 15px;">
					<?php esc_html_e( '⭐ = Recommended for faster imports. Hero, SERP Answer, FAQs, and CTA are essential blocks (4 blocks = ~$0.03-0.05 per page).', 'seo-generator' ); ?>
				</p>

				<p style="margin-top: 10px;">
					<button type="button" id="select-all-blocks" class="button button-small"><?php esc_html_e( 'Select All', 'seo-generator' ); ?></button>
					<button type="button" id="select-recommended-blocks" class="button button-small"><?php esc_html_e( 'Recommended Only', 'seo-generator' ); ?></button>
					<button type="button" id="clear-all-blocks" class="button button-small"><?php esc_html_e( 'Clear All', 'seo-generator' ); ?></button>
				</p>
			</div>

			<label style="display: block;">
				<input type="checkbox" name="check_duplicates" value="1" checked>
				<?php esc_html_e( 'Skip duplicate posts (check if page with same title exists)', 'seo-generator' ); ?>
			</label>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Show/hide block selection based on generation mode
			$('input[name="generation_mode"]').on('change', function() {
				if ($(this).val() === 'auto_generate') {
					$('#block-selection-section').slideDown();
				} else {
					$('#block-selection-section').slideUp();
				}
			});

			// Select all blocks
			$('#select-all-blocks').on('click', function() {
				$('input[name="blocks_to_generate[]"]').prop('checked', true);
			});

			// Select only recommended blocks
			$('#select-recommended-blocks').on('click', function() {
				$('input[name="blocks_to_generate[]"]').prop('checked', false);
				$('input[name="blocks_to_generate[]"][value="hero"]').prop('checked', true);
				$('input[name="blocks_to_generate[]"][value="serp_answer"]').prop('checked', true);
				$('input[name="blocks_to_generate[]"][value="faqs"]').prop('checked', true);
				$('input[name="blocks_to_generate[]"][value="cta"]').prop('checked', true);
			});

			// Clear all blocks
			$('#clear-all-blocks').on('click', function() {
				$('input[name="blocks_to_generate[]"]').prop('checked', false);
			});
		});
		</script>

		<div class="mapping-actions" style="margin-top: 20px;">
			<button type="button" id="proceed-import" class="button button-primary">
				<span class="dashicons dashicons-yes" style="vertical-align: middle;"></span>
				<?php esc_html_e( 'Proceed to Import', 'seo-generator' ); ?>
			</button>
			<button type="button" id="cancel-mapping" class="button">
				<?php esc_html_e( 'Cancel', 'seo-generator' ); ?>
			</button>
		</div>
	</div>

	<!-- Progress Indicator -->
	<div id="import-progress" style="display:none;">
		<h3><?php esc_html_e( 'Processing Import...', 'seo-generator' ); ?></h3>
		<div class="progress-bar" style="background: #f0f0f0; border: 1px solid #ddd; height: 30px; border-radius: 3px; overflow: hidden;">
			<div class="progress-fill" style="width:0%; background: #0073aa; height: 100%; transition: width 0.3s;"></div>
		</div>
		<p class="progress-text" style="margin-top: 10px;">
			<strong>0</strong> <?php esc_html_e( 'of', 'seo-generator' ); ?> <strong>0</strong> <?php esc_html_e( 'rows processed', 'seo-generator' ); ?>
		</p>
	</div>

	<!-- Completion Summary -->
	<div id="import-results" style="display:none;">
		<h3><?php esc_html_e( 'Import Complete', 'seo-generator' ); ?></h3>
		<div class="notice notice-success">
			<p>
				<strong id="total-processed">0</strong> <?php esc_html_e( 'rows processed', 'seo-generator' ); ?>
			</p>
			<p>
				<strong id="pages-created">0</strong> <?php esc_html_e( 'pages created successfully', 'seo-generator' ); ?>
			</p>
		</div>
		<div id="error-list" style="display:none;">
			<h4><?php esc_html_e( 'Errors Encountered:', 'seo-generator' ); ?></h4>
			<ul id="error-items" style="list-style: disc; margin-left: 20px; color: #d63638;"></ul>
		</div>
		<p>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=seo-page' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'View Created Pages', 'seo-generator' ); ?>
			</a>
		</p>
	</div>
</div>
