<?php
/**
 * Template Builder Admin Template
 *
 * 3-column layout: Block Library | Template Canvas | Multi-device Preview
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

$generator    = new \SEOGenerator\Services\NextJSPageGenerator();
$project_path = get_option( 'seo_nextjs_project_path', '' );
$preview_url  = get_option( 'seo_nextjs_preview_url', 'http://contentgeneratorwpplugin.local:3000' );
?>

<div class="wrap seo-generator-page" id="template-builder-app">
	<a href="#main-content" class="skip-link"><?php esc_html_e( 'Skip to main content', 'seo-generator' ); ?></a>

	<!-- Top Bar -->
	<div class="tb-top-bar">
		<div class="tb-top-bar-left">
			<h1 class="heading-1"><?php esc_html_e( 'Template Builder', 'seo-generator' ); ?></h1>
		</div>
		<div class="tb-top-bar-actions">
			<button type="button" id="tb-new-template" class="seo-btn-secondary">+ <?php esc_html_e( 'New Template', 'seo-generator' ); ?></button>
			<button type="button" id="tb-clone-template" class="seo-btn-secondary"><?php esc_html_e( 'Clone', 'seo-generator' ); ?></button>
			<button type="button" id="tb-delete-template" class="seo-btn-secondary tb-btn-danger"><?php esc_html_e( 'Delete', 'seo-generator' ); ?></button>
		</div>
	</div>

	<!-- Template Tabs -->
	<div class="tb-template-tabs" id="tb-template-tabs" role="tablist">
		<!-- Populated by JS -->
	</div>

	<?php if ( empty( $project_path ) ) : ?>
		<div class="notice notice-warning" style="margin: 0 0 16px;">
			<p>
				<strong><?php esc_html_e( 'Next.js project path not configured.', 'seo-generator' ); ?></strong>
				<?php esc_html_e( 'Set it in Settings below before publishing.', 'seo-generator' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<!-- 3-Column Layout -->
	<div class="tb-three-col-layout" id="main-content">

		<!-- LEFT: Block Library -->
		<div class="tb-panel tb-block-library">
			<div class="tb-panel-header">
				<h3><?php esc_html_e( 'Block Library', 'seo-generator' ); ?></h3>
			</div>

			<div class="tb-search-wrapper">
				<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><circle cx="6.5" cy="6.5" r="5.5" stroke="#999" stroke-width="1.5"/><path d="M11 11L15 15" stroke="#999" stroke-width="1.5" stroke-linecap="round"/></svg>
				<input type="text" id="tb-search-blocks" placeholder="<?php esc_attr_e( 'Search blocks...', 'seo-generator' ); ?>" autocomplete="off">
			</div>

			<div class="tb-filter-pills" id="tb-filter-pills">
				<button type="button" class="tb-pill active" data-group="all"><?php esc_html_e( 'All', 'seo-generator' ); ?></button>
				<!-- Populated by JS -->
			</div>

			<div class="tb-block-list" id="tb-block-list">
				<!-- Populated by JS: accordion groups with blocks -->
			</div>
		</div>

		<!-- MIDDLE: Template Canvas -->
		<div class="tb-panel tb-canvas">
			<div class="tb-panel-header">
				<h3 id="tb-canvas-title"><?php esc_html_e( 'Template Canvas', 'seo-generator' ); ?></h3>
				<span class="tb-dirty-badge" id="tb-dirty-badge" style="display:none;"><?php esc_html_e( 'Unsaved', 'seo-generator' ); ?></span>
			</div>

			<!-- Template Metadata -->
			<div class="tb-template-meta" id="tb-template-meta">
				<div class="tb-meta-row">
					<label for="tb-template-name"><?php esc_html_e( 'Name', 'seo-generator' ); ?></label>
					<input type="text" id="tb-template-name" placeholder="<?php esc_attr_e( 'Template Name', 'seo-generator' ); ?>">
				</div>
				<div class="tb-meta-row tb-meta-row-split">
					<div>
						<label for="tb-template-category"><?php esc_html_e( 'Category', 'seo-generator' ); ?></label>
						<select id="tb-template-category">
							<!-- Populated by JS from categories -->
						</select>
					</div>
					<div>
						<label for="tb-template-status"><?php esc_html_e( 'Status', 'seo-generator' ); ?></label>
						<select id="tb-template-status">
							<option value="draft"><?php esc_html_e( 'Draft', 'seo-generator' ); ?></option>
							<option value="active"><?php esc_html_e( 'Active', 'seo-generator' ); ?></option>
						</select>
					</div>
				</div>
				<div class="tb-meta-row">
					<label for="tb-template-desc"><?php esc_html_e( 'Description', 'seo-generator' ); ?></label>
					<textarea id="tb-template-desc" rows="2" placeholder="<?php esc_attr_e( 'Optional description...', 'seo-generator' ); ?>"></textarea>
				</div>
			</div>

			<!-- Sortable Block List -->
			<div class="tb-canvas-blocks">
				<div class="tb-canvas-empty" id="tb-canvas-empty">
					<p><?php esc_html_e( 'Drag blocks from the library or click + to add them here.', 'seo-generator' ); ?></p>
				</div>
				<ul id="tb-sortable-blocks" class="seo-sortable-list tb-sortable">
					<!-- Populated by JS -->
				</ul>
			</div>

			<!-- Output Slug + Actions -->
			<div class="tb-canvas-footer">
				<div class="tb-output-slug-row">
					<label for="tb-output-slug"><?php esc_html_e( 'Publish to URL:', 'seo-generator' ); ?></label>
					<div class="tb-slug-input-wrap">
						<span class="tb-slug-prefix">yoursite.com/</span>
						<input type="text" id="tb-output-slug" placeholder="e.g. san-diego-jewelry-store">
					</div>
				</div>

				<div class="tb-action-buttons">
					<button type="button" id="tb-save-btn" class="seo-btn-secondary"><?php esc_html_e( 'Save Template', 'seo-generator' ); ?></button>
					<button type="button" id="tb-publish-btn" class="seo-btn-primary"><?php esc_html_e( 'Publish', 'seo-generator' ); ?> &rarr;</button>
				</div>
				<span id="tb-save-status" class="page-builder-save-status"></span>
			</div>
		</div>

		<!-- RIGHT: Preview -->
		<div class="tb-panel tb-preview">
			<div class="tb-panel-header">
				<h3><?php esc_html_e( 'Preview', 'seo-generator' ); ?></h3>
				<div class="tb-preview-mode-toggle" id="tb-preview-mode-toggle">
					<button type="button" class="tb-mode-btn active" data-mode="template"><?php esc_html_e( 'Template', 'seo-generator' ); ?></button>
					<button type="button" class="tb-mode-btn" data-mode="block"><?php esc_html_e( 'Block', 'seo-generator' ); ?></button>
				</div>
			</div>

			<div class="device-toggle-buttons tb-device-toggle" role="tablist" aria-label="<?php esc_attr_e( 'Preview device', 'seo-generator' ); ?>">
				<button type="button" class="device-toggle-btn" data-device="mobile" role="tab" aria-selected="false" title="<?php esc_attr_e( 'Mobile', 'seo-generator' ); ?>">
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="5" y="2" width="10" height="16" rx="1" stroke="currentColor" stroke-width="1.5"/><line x1="8" y1="15.5" x2="12" y2="15.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
					<span class="device-label"><?php esc_html_e( 'Mobile', 'seo-generator' ); ?></span>
				</button>
				<button type="button" class="device-toggle-btn active" data-device="tablet" role="tab" aria-selected="true" title="<?php esc_attr_e( 'Tablet', 'seo-generator' ); ?>">
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="3" y="2" width="14" height="16" rx="1" stroke="currentColor" stroke-width="1.5"/><circle cx="10" cy="15.5" r="0.75" fill="currentColor"/></svg>
					<span class="device-label"><?php esc_html_e( 'Tablet', 'seo-generator' ); ?></span>
				</button>
				<button type="button" class="device-toggle-btn" data-device="desktop" role="tab" aria-selected="false" title="<?php esc_attr_e( 'Desktop', 'seo-generator' ); ?>">
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="3" width="16" height="11" rx="1" stroke="currentColor" stroke-width="1.5"/><path d="M6 17H14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M10 14V17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
					<span class="device-label"><?php esc_html_e( 'Desktop', 'seo-generator' ); ?></span>
				</button>
			</div>

			<div id="tb-preview-container" class="preview-device-wrapper" data-device="tablet">
				<div class="device-frame tablet-frame">
					<div class="device-screen">
						<iframe id="tb-preview-iframe" src="about:blank" title="<?php esc_attr_e( 'Template preview', 'seo-generator' ); ?>"></iframe>
					</div>
				</div>
			</div>
		</div>

	</div>

	<!-- Settings Card -->
	<div class="seo-card mt-4">
		<h3 class="seo-card__title"><?php esc_html_e( 'Settings', 'seo-generator' ); ?></h3>
		<div class="seo-card__content">
			<table class="form-table">
				<tr>
					<th scope="row"><label for="tb-project-path"><?php esc_html_e( 'Next.js Project Path', 'seo-generator' ); ?></label></th>
					<td>
						<input type="text" id="tb-project-path" class="regular-text" style="width:100%;max-width:600px;" value="<?php echo esc_attr( $project_path ); ?>" placeholder="/var/www/new-bravojewellers/frontend">
						<p class="description"><?php esc_html_e( 'Absolute path to the Next.js frontend directory.', 'seo-generator' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="tb-preview-url"><?php esc_html_e( 'Preview URL', 'seo-generator' ); ?></label></th>
					<td>
						<input type="url" id="tb-preview-url" class="regular-text" style="width:100%;max-width:600px;" value="<?php echo esc_attr( $preview_url ); ?>" placeholder="http://localhost:3000">
						<p class="description"><?php esc_html_e( 'URL of the Next.js dev server for live preview.', 'seo-generator' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Dynamic Route', 'seo-generator' ); ?></th>
					<td>
						<?php if ( get_option( 'seo_nextjs_dynamic_setup_done', false ) ) : ?>
							<span style="color:#00a32a;font-weight:600;">&#10003; <?php esc_html_e( 'Set up', 'seo-generator' ); ?></span>
							<button type="button" id="tb-setup-dynamic-btn" class="seo-btn-secondary" style="margin-left:10px;"><?php esc_html_e( 'Re-generate Files', 'seo-generator' ); ?></button>
						<?php else : ?>
							<button type="button" id="tb-setup-dynamic-btn" class="seo-btn-primary"><?php esc_html_e( 'Setup Dynamic Route', 'seo-generator' ); ?></button>
						<?php endif; ?>
						<span id="tb-setup-dynamic-status" class="page-builder-save-status" style="margin-left:10px;"></span>
						<p class="description"><?php esc_html_e( 'Generate the [slug] catch-all route. After setup, run pnpm build once — then every publish is instant.', 'seo-generator' ); ?></p>
					</td>
				</tr>
			</table>
			<button type="button" id="tb-save-settings-btn" class="seo-btn-secondary"><?php esc_html_e( 'Save Settings', 'seo-generator' ); ?></button>
			<span id="tb-settings-status" class="page-builder-save-status" style="margin-left:10px;"></span>
		</div>
	</div>

</div>
