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

			<!-- Generation Mode -->
			<h5 class="mb-3"><?php esc_html_e( 'Content Generation:', 'seo-generator' ); ?></h5>
			<div class="seo-radio mb-4">
				<label class="seo-radio__option">
					<input type="radio" name="generation_mode" value="drafts_only" class="seo-radio__input" checked>
					<span class="seo-radio__label"><?php esc_html_e( 'Create drafts only (generate content manually later)', 'seo-generator' ); ?></span>
				</label>
				<label class="seo-radio__option">
					<input type="radio" name="generation_mode" value="auto_generate" class="seo-radio__input">
					<span class="seo-radio__label"><?php esc_html_e( 'Auto-generate content in background', 'seo-generator' ); ?></span>
					<span class="seo-radio__badge">⭐</span>
				</label>
			</div>
			<p class="text-sm text-gray mb-4">
				<?php esc_html_e( 'Background generation processes one page every 3 minutes to respect API rate limits. Requires WordPress Cron or server cron to be active.', 'seo-generator' ); ?>
			</p>

			<!-- Block Selection (hidden initially) -->
			<div id="block-selection" style="display: none;" class="mt-4 p-4" style="background: var(--gray-50); border-radius: var(--radius-md);">
				<h4 class="mb-3"><?php esc_html_e( 'Select Blocks to Generate:', 'seo-generator' ); ?></h4>
				<p class="text-sm text-gray mb-4">
					<?php esc_html_e( 'Choose which content blocks to generate. Generating fewer blocks is faster and costs less. Leave all unchecked to generate all 12 blocks.', 'seo-generator' ); ?>
				</p>

				<div class="seo-checkbox" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-3);">
					<label class="seo-checkbox__option">
						<input type="checkbox" name="blocks_to_generate[]" value="hero" class="seo-checkbox__input" checked>
						<span class="seo-checkbox__label"><?php esc_html_e( 'Hero Section', 'seo-generator' ); ?> ⭐</span>
					</label>
					<label class="seo-checkbox__option">
						<input type="checkbox" name="blocks_to_generate[]" value="serp_answer" class="seo-checkbox__input" checked>
						<span class="seo-checkbox__label"><?php esc_html_e( 'SERP Answer', 'seo-generator' ); ?> ⭐</span>
					</label>
					<label class="seo-checkbox__option">
						<input type="checkbox" name="blocks_to_generate[]" value="product_criteria" class="seo-checkbox__input">
						<span class="seo-checkbox__label"><?php esc_html_e( 'Product Criteria', 'seo-generator' ); ?></span>
					</label>
					<label class="seo-checkbox__option">
						<input type="checkbox" name="blocks_to_generate[]" value="materials" class="seo-checkbox__input">
						<span class="seo-checkbox__label"><?php esc_html_e( 'Materials', 'seo-generator' ); ?></span>
					</label>
					<label class="seo-checkbox__option">
						<input type="checkbox" name="blocks_to_generate[]" value="process" class="seo-checkbox__input">
						<span class="seo-checkbox__label"><?php esc_html_e( 'Process', 'seo-generator' ); ?></span>
					</label>
					<label class="seo-checkbox__option">
						<input type="checkbox" name="blocks_to_generate[]" value="comparison" class="seo-checkbox__input">
						<span class="seo-checkbox__label"><?php esc_html_e( 'Comparison', 'seo-generator' ); ?></span>
					</label>
					<label class="seo-checkbox__option">
						<input type="checkbox" name="blocks_to_generate[]" value="product_showcase" class="seo-checkbox__input">
						<span class="seo-checkbox__label"><?php esc_html_e( 'Product Showcase', 'seo-generator' ); ?></span>
					</label>
					<label class="seo-checkbox__option">
						<input type="checkbox" name="blocks_to_generate[]" value="size_fit" class="seo-checkbox__input">
						<span class="seo-checkbox__label"><?php esc_html_e( 'Size & Fit', 'seo-generator' ); ?></span>
					</label>
					<label class="seo-checkbox__option">
						<input type="checkbox" name="blocks_to_generate[]" value="care_warranty" class="seo-checkbox__input">
						<span class="seo-checkbox__label"><?php esc_html_e( 'Care & Warranty', 'seo-generator' ); ?></span>
					</label>
					<label class="seo-checkbox__option">
						<input type="checkbox" name="blocks_to_generate[]" value="ethics" class="seo-checkbox__input">
						<span class="seo-checkbox__label"><?php esc_html_e( 'Ethics & Origin', 'seo-generator' ); ?></span>
					</label>
					<label class="seo-checkbox__option">
						<input type="checkbox" name="blocks_to_generate[]" value="faqs" class="seo-checkbox__input" checked>
						<span class="seo-checkbox__label"><?php esc_html_e( 'FAQs', 'seo-generator' ); ?> ⭐</span>
					</label>
					<label class="seo-checkbox__option">
						<input type="checkbox" name="blocks_to_generate[]" value="cta" class="seo-checkbox__input" checked>
						<span class="seo-checkbox__label"><?php esc_html_e( 'Call to Action', 'seo-generator' ); ?> ⭐</span>
					</label>
				</div>

				<p class="text-sm text-gray mt-4">
					<?php esc_html_e( '⭐ = Recommended for faster imports. Hero, SERP Answer, FAQs, and CTA are essential blocks (4 blocks = ~$0.03-0.05 per page).', 'seo-generator' ); ?>
				</p>

				<div class="seo-btn-group mt-4" style="gap: var(--space-2);">
					<button type="button" id="select-all-blocks" class="seo-btn-secondary"><?php esc_html_e( 'Select All', 'seo-generator' ); ?></button>
					<button type="button" id="select-recommended-blocks" class="seo-btn-secondary"><?php esc_html_e( 'Recommended Only', 'seo-generator' ); ?></button>
					<button type="button" id="clear-all-blocks" class="seo-btn-secondary"><?php esc_html_e( 'Clear All', 'seo-generator' ); ?></button>
				</div>
			</div>

			<!-- Additional Options -->
			<div class="seo-checkbox mt-4">
				<label class="seo-checkbox__option">
					<input type="checkbox" name="check_duplicates" value="1" class="seo-checkbox__input" checked>
					<span class="seo-checkbox__label"><?php esc_html_e( 'Skip duplicate posts (check if page with same title exists)', 'seo-generator' ); ?></span>
				</label>
				<label class="seo-checkbox__option">
					<input type="checkbox" name="download_images" value="1" class="seo-checkbox__input" checked>
					<span class="seo-checkbox__label"><?php esc_html_e( 'Download images from URLs', 'seo-generator' ); ?></span>
				</label>
			</div>

			<!-- Action Buttons -->
			<div class="seo-btn-group mt-6">
				<button type="button" id="cancel-mapping" class="seo-btn-secondary">
					<?php esc_html_e( 'Cancel', 'seo-generator' ); ?>
				</button>
				<button type="button" id="proceed-import" class="seo-btn-primary">
					<?php esc_html_e( 'Import CSV', 'seo-generator' ); ?> ✨
				</button>
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

<?php
// Localize script with AJAX data for column-mapping.js
wp_localize_script(
	'seo-generator-interactions',
	'seoImportData',
	array(
		'nonce'          => wp_create_nonce( 'seo_csv_upload' ),
		'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
		'maxUploadSize'  => $max_upload_size,
	)
);
?>
