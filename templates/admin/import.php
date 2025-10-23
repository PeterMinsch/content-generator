<?php
/**
 * CSV Import Page Template - macOS-Inspired UI
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

// Get max upload size.
$max_upload_size = wp_max_upload_size();
?>

<div class="wrap seo-generator-page">
	<!-- Skip Navigation Link -->
	<a href="#main-content" class="skip-link"><?php esc_html_e( 'Skip to main content', 'seo-generator' ); ?></a>

	<!-- Page Header -->
	<h1 class="heading-1"><?php esc_html_e( 'CSV Import', 'seo-generator' ); ?></h1>

	<!-- Geographic Titles Success Banner (hidden by default, shown by JavaScript) -->
	<div id="geo-titles-success-banner" style="display: none; margin-top: 20px;">
		<div class="notice notice-success" style="padding: 20px; border-left: 4px solid #00a32a; background: #f0f6fc;">
			<h2 style="margin: 0 0 10px 0; color: #00a32a; font-size: 18px;">
				✓ Geographic Titles Successfully Loaded!
			</h2>
			<p style="margin: 0; font-size: 14px;">
				<strong id="geo-titles-count">0</strong> titles from the Geographic Title Generator are ready to import.
				The file has been automatically loaded and is ready for column mapping below.
			</p>
		</div>
	</div>

	<!-- Instructions Card -->
	<div class="seo-card mt-6">
		<h3 class="seo-card__title">
			📋 <?php esc_html_e( 'Import Instructions', 'seo-generator' ); ?>
		</h3>
		<div class="seo-card__content">
			<h4><?php esc_html_e( 'Supported CSV Format:', 'seo-generator' ); ?></h4>
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

			<h4 class="mt-4"><?php esc_html_e( 'Required Columns:', 'seo-generator' ); ?></h4>
			<p><?php esc_html_e( 'Your CSV must have at least one column that can be mapped to "Page Title" (typically: keyword, title, or query).', 'seo-generator' ); ?></p>

			<h4 class="mt-4"><?php esc_html_e( 'Example CSV Format:', 'seo-generator' ); ?></h4>
			<pre style="background: var(--gray-50); padding: var(--space-3); border: 1px solid var(--gray-200); border-radius: var(--radius-md); overflow-x: auto; font-family: var(--font-mono); font-size: var(--text-sm);">keyword,intent,search_volume,image_url
platinum wedding bands,commercial,1000,https://example.com/image.jpg
men's tungsten rings,commercial,800,</pre>
		</div>
	</div>

	<!-- Step 1: File Upload -->
	<div class="seo-card mt-4" id="upload-section">
		<h3 class="seo-card__title">
			1️⃣ <?php esc_html_e( 'Upload CSV File', 'seo-generator' ); ?>
		</h3>
		<div class="seo-card__content">
			<!-- Upload Form (required by column-mapping.js) -->
			<form id="csv-upload-form" method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'seo_csv_upload', 'seo_csv_nonce' ); ?>

				<!-- Drop Zone -->
				<div class="seo-drop-zone" id="csv-drop-zone">
					<span class="seo-drop-zone__icon" aria-hidden="true">📄</span>
					<p class="seo-drop-zone__text"><?php esc_html_e( 'Drop CSV file here or click to browse', 'seo-generator' ); ?></p>
					<p class="seo-drop-zone__hint">
						<?php
						printf(
							/* translators: %s: maximum file size */
							esc_html__( 'Supported format: .csv (max %s)', 'seo-generator' ),
							esc_html( size_format( $max_upload_size ) )
						);
						?>
					</p>
					<input type="file" name="csv_file" id="csv_file" accept=".csv" aria-label="<?php esc_attr_e( 'Select CSV file', 'seo-generator' ); ?>">
				</div>
			</form>

			<!-- Upload Progress -->
			<div class="seo-upload-progress mt-4" id="upload-progress" style="display: none;">
				<div class="seo-upload-progress__info">
					<span class="seo-upload-progress__name"></span>
					<span class="seo-upload-progress__size"></span>
				</div>
				<div class="seo-upload-progress__bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
					<div class="seo-upload-progress__fill"></div>
				</div>
			</div>
		</div>
	</div>

	<!-- Step 2: Column Mapping (hidden initially) -->
	<div class="seo-card mt-4" id="column-mapping-section" style="display: none;">
		<h3 class="seo-card__title">
			2️⃣ <?php esc_html_e( 'Map CSV Columns', 'seo-generator' ); ?>
		</h3>
		<div class="seo-card__content">
			<p class="mb-4"><?php esc_html_e( 'Select how each CSV column should be mapped to page fields. The system has auto-detected likely mappings based on column names.', 'seo-generator' ); ?></p>

			<!-- Column Mapping Interface -->
			<div class="seo-column-mapping" id="column-mapping-table">
				<!-- Dynamically populated by JavaScript -->
			</div>

			<!-- Preview -->
			<h4 class="mt-6 mb-3"><?php esc_html_e( 'Preview (First 3 Rows)', 'seo-generator' ); ?></h4>
			<div id="preview-table-container" style="overflow-x: auto;">
				<!-- Dynamically populated by JavaScript -->
			</div>

			<!-- Import Options -->
			<h4 class="mt-6 mb-3"><?php esc_html_e( '⚙️ Import Settings', 'seo-generator' ); ?></h4>

			<!-- Additional Options -->
			<div class="seo-checkbox">
				<label class="seo-checkbox__option">
					<input type="checkbox" name="check_duplicates" value="1" class="seo-checkbox__input" checked>
					<span class="seo-checkbox__label"><?php esc_html_e( 'Skip duplicate posts (check if page with same title exists)', 'seo-generator' ); ?></span>
				</label>
				<label class="seo-checkbox__option">
					<input type="checkbox" name="download_images" value="1" class="seo-checkbox__input" checked>
					<span class="seo-checkbox__label"><?php esc_html_e( 'Download images from URLs', 'seo-generator' ); ?></span>
				</label>
			</div>

			<p class="text-sm text-gray mt-4">
				<?php esc_html_e( 'Content will be auto-generated in the background. Background generation processes one page every 3 minutes to respect API rate limits.', 'seo-generator' ); ?>
			</p>
		</div>
	</div>

	<!-- Step 2b: Block Ordering (hidden initially) -->
	<!-- TEMP: Removed display:none for testing -->
	<div class="seo-card mt-4" id="block-ordering-section">
		<h3 class="seo-card__title">
			<?php esc_html_e( 'Customize Block Order', 'seo-generator' ); ?>
		</h3>
		<div class="seo-card__content">
			<!-- Split-Pane Layout -->
			<div class="block-ordering-split-layout">
				<!-- Left Pane: Block Ordering Controls -->
				<div class="block-ordering-pane">
					<p class="mb-4"><?php esc_html_e( 'Drag blocks to reorder how content will be generated on your pages. This order will be applied to all pages in this import.', 'seo-generator' ); ?></p>

					<!-- Sortable Block List -->
					<ul id="sortable-blocks" class="seo-sortable-list">
				<?php
				// Get block definitions
				$block_config = require plugin_dir_path( dirname( __DIR__ ) ) . 'config/block-definitions.php';
				$blocks       = $block_config['blocks'] ?? array();

				// Define default order (excluding seo_metadata which is internal)
				$default_order = array( 'hero', 'about_section', 'serp_answer', 'product_criteria', 'materials', 'process', 'comparison', 'product_showcase', 'size_fit', 'care_warranty', 'ethics', 'faqs', 'cta' );

				foreach ( $default_order as $block_key ) :
					if ( isset( $blocks[ $block_key ] ) ) :
						$block = $blocks[ $block_key ];
						?>
						<li class="seo-sortable-item" data-block="<?php echo esc_attr( $block_key ); ?>" data-enabled="true">
							<span class="seo-sortable-handle" aria-label="<?php esc_attr_e( 'Drag to reorder', 'seo-generator' ); ?>">⋮⋮</span>
							<div class="seo-sortable-content">
								<strong class="seo-sortable-label"><?php echo esc_html( $block['label'] ); ?></strong>
								<span class="seo-sortable-desc"><?php echo esc_html( $block['description'] ); ?></span>
							</div>
							<button type="button" class="seo-sortable-remove" aria-label="<?php esc_attr_e( 'Remove block', 'seo-generator' ); ?>" title="<?php esc_attr_e( 'Click to remove this block', 'seo-generator' ); ?>">
								<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M1 1L13 13M13 1L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
								</svg>
							</button>
						</li>
						<?php
					endif;
				endforeach;
				?>
			</ul>

			<!-- Options -->
			<div class="seo-checkbox mt-4">
				<label class="seo-checkbox__option">
					<input type="checkbox" id="apply-order-to-all" class="seo-checkbox__input" checked>
					<span class="seo-checkbox__label"><?php esc_html_e( 'Use this order for all pages in this import', 'seo-generator' ); ?></span>
				</label>
			</div>

					<!-- Action Buttons -->
					<div class="seo-btn-group mt-6">
						<button type="button" id="reset-order-btn" class="seo-btn-secondary">
							<?php esc_html_e( 'Reset to Default Order', 'seo-generator' ); ?>
						</button>
						<button type="button" id="proceed-import-btn" class="seo-btn-primary">
							<?php esc_html_e( 'Proceed with Import', 'seo-generator' ); ?> →
						</button>
					</div>
				</div>

				<!-- Right Pane: Preview Container -->
				<div class="block-preview-pane" aria-label="<?php esc_attr_e( 'Page Preview', 'seo-generator' ); ?>">
					<div class="block-preview-header">
						<div class="preview-header-content">
							<div>
								<h3><?php esc_html_e( 'Page Preview', 'seo-generator' ); ?></h3>
								<p class="preview-disclaimer"><?php esc_html_e( 'This is a simplified preview. Actual styling may vary based on your theme.', 'seo-generator' ); ?></p>
							</div>
							<!-- Device Toggle Buttons -->
							<div class="device-toggle-buttons" role="tablist" aria-label="<?php esc_attr_e( 'Preview device type', 'seo-generator' ); ?>">
								<button type="button" class="device-toggle-btn" data-device="mobile" role="tab" aria-selected="false" aria-label="<?php esc_attr_e( 'Mobile preview', 'seo-generator' ); ?>" title="<?php esc_attr_e( 'Preview on mobile device', 'seo-generator' ); ?>">
									<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
										<rect x="5" y="2" width="10" height="16" rx="1" stroke="currentColor" stroke-width="1.5"/>
										<line x1="8" y1="15.5" x2="12" y2="15.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
									</svg>
									<span class="device-label"><?php esc_html_e( 'Mobile', 'seo-generator' ); ?></span>
								</button>
								<button type="button" class="device-toggle-btn active" data-device="tablet" role="tab" aria-selected="true" aria-label="<?php esc_attr_e( 'Tablet preview', 'seo-generator' ); ?>" title="<?php esc_attr_e( 'Preview on tablet device', 'seo-generator' ); ?>">
									<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
										<rect x="3" y="2" width="14" height="16" rx="1" stroke="currentColor" stroke-width="1.5"/>
										<circle cx="10" cy="15.5" r="0.75" fill="currentColor"/>
									</svg>
									<span class="device-label"><?php esc_html_e( 'Tablet', 'seo-generator' ); ?></span>
								</button>
								<button type="button" class="device-toggle-btn" data-device="desktop" role="tab" aria-selected="false" aria-label="<?php esc_attr_e( 'Desktop preview', 'seo-generator' ); ?>" title="<?php esc_attr_e( 'Preview on desktop device', 'seo-generator' ); ?>">
									<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
										<rect x="2" y="3" width="16" height="11" rx="1" stroke="currentColor" stroke-width="1.5"/>
										<path d="M6 17H14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
										<path d="M10 14V17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
									</svg>
									<span class="device-label"><?php esc_html_e( 'Desktop', 'seo-generator' ); ?></span>
								</button>
							</div>
						</div>
					</div>
					<div id="block-preview-container" class="preview-device-wrapper" data-device="tablet">
						<!-- Device Frame Container -->
						<div class="device-frame tablet-frame">
							<div class="device-screen">
								<iframe
									id="block-preview-iframe"
									sandbox="allow-same-origin"
									title="<?php esc_attr_e( 'Block preview iframe', 'seo-generator' ); ?>"
									aria-label="<?php esc_attr_e( 'Live preview of page layout', 'seo-generator' ); ?>"
								></iframe>
							</div>
						</div>
					</div>
					<!-- ARIA Live Region for Screen Reader Announcements -->
					<div class="preview-sr-announcements" role="status" aria-live="polite" aria-atomic="true"></div>
				</div>
			</div>
		</div>
	</div>

	<!-- Import Progress -->
	<div class="seo-progress-card mt-4" id="import-progress" style="display: none;">
		<div class="seo-progress-card__header">
			🤖 <?php esc_html_e( 'Importing CSV...', 'seo-generator' ); ?>
		</div>
		<div class="seo-progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
			<div class="seo-progress-bar__fill" style="width: 0%"></div>
		</div>
		<div class="seo-progress-list" id="progress-items">
			<!-- Dynamically populated by JavaScript -->
		</div>
		<div class="seo-progress-card__footer" id="progress-footer">
			<?php esc_html_e( 'Starting import...', 'seo-generator' ); ?>
		</div>
	</div>

	<!-- Import Results -->
	<div class="seo-card mt-4" id="import-results" style="display: none;">
		<h3 class="seo-card__title">
			✅ <?php esc_html_e( 'Import Complete', 'seo-generator' ); ?>
		</h3>
		<div class="seo-card__content">
			<div class="success-message">
				<p>
					<strong id="pages-created">0</strong> <?php esc_html_e( 'pages created successfully', 'seo-generator' ); ?>
				</p>
				<p>
					<strong id="total-processed">0</strong> <?php esc_html_e( 'rows processed', 'seo-generator' ); ?>
				</p>
			</div>

			<div id="error-list" class="error-message mt-4" style="display: none;">
				<h4><?php esc_html_e( 'Errors Encountered:', 'seo-generator' ); ?></h4>
				<ul id="error-items" style="list-style: disc; margin-left: 20px;"></ul>
			</div>

			<div class="seo-btn-group mt-6">
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=seo-page' ) ); ?>" class="seo-btn-primary">
					<?php esc_html_e( 'View Created Pages', 'seo-generator' ); ?> →
				</a>
				<button type="button" id="new-import" class="seo-btn-secondary">
					<?php esc_html_e( 'Start New Import', 'seo-generator' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Live Region for Screen Readers -->
	<div id="import-announcements" class="sr-live-polite" role="status" aria-live="polite" aria-atomic="true"></div>

	<!-- Import History Section -->
	<div class="mt-6">
		<hr style="margin: 2rem 0; border: none; border-top: 1px solid var(--gray-200);">
		<?php
		$history_template = plugin_dir_path( __DIR__ ) . 'admin/import-history-table.php';
		if ( file_exists( $history_template ) ) {
			include $history_template;
		}
		?>
	</div>
</div>

<script>
(function() {
	'use strict';

	// Auto-submit form when file is selected
	const fileInput = document.getElementById('csv_file');
	const uploadForm = document.getElementById('csv-upload-form');

	if (fileInput && uploadForm) {
		fileInput.addEventListener('change', function() {
			if (this.files && this.files.length > 0) {
				// Trigger form submission which column-mapping.js will handle
				uploadForm.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
			}
		});
	}

	// Show/hide block selection based on generation mode
	document.querySelectorAll('input[name="generation_mode"]').forEach(radio => {
		radio.addEventListener('change', function() {
			const blockSection = document.getElementById('block-selection');
			if (this.value === 'auto_generate') {
				blockSection.style.display = 'block';
			} else {
				blockSection.style.display = 'none';
			}
		});
	});

	// Block selection buttons
	document.getElementById('select-all-blocks')?.addEventListener('click', function() {
		document.querySelectorAll('input[name="blocks_to_generate[]"]').forEach(cb => cb.checked = true);
	});

	document.getElementById('select-recommended-blocks')?.addEventListener('click', function() {
		document.querySelectorAll('input[name="blocks_to_generate[]"]').forEach(cb => cb.checked = false);
		['hero', 'serp_answer', 'faqs', 'cta'].forEach(value => {
			const cb = document.querySelector(`input[name="blocks_to_generate[]"][value="${value}"]`);
			if (cb) cb.checked = true;
		});
	});

	document.getElementById('clear-all-blocks')?.addEventListener('click', function() {
		document.querySelectorAll('input[name="blocks_to_generate[]"]').forEach(cb => cb.checked = false);
	});

})();
</script>
