<?php
/**
 * Page Builder Admin Template
 *
 * Tabbed layout: one tab per page template (Homepage, About Us).
 * Shared block catalog — click "+ Add Block" to pick from any group.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

$generator    = new \SEOGenerator\Services\NextJSPageGenerator();
$pages        = $generator->getPages();
$project_path = get_option( 'seo_nextjs_project_path', '' );
$preview_url  = get_option( 'seo_nextjs_preview_url', 'http://contentgeneratorwpplugin.local:3000' );
$first_slug   = array_key_first( $pages );
?>

<div class="wrap seo-generator-page" id="page-builder-app">
	<a href="#main-content" class="skip-link"><?php esc_html_e( 'Skip to main content', 'seo-generator' ); ?></a>

	<h1 class="heading-1"><?php esc_html_e( 'SEO Page Builder', 'seo-generator' ); ?></h1>
	<p class="text-muted" style="margin-top: 4px; margin-bottom: 20px;">
		<?php esc_html_e( 'Reorder blocks and publish to a new URL. Original pages are never overwritten.', 'seo-generator' ); ?>
	</p>

	<?php if ( empty( $project_path ) ) : ?>
		<div class="notice notice-warning" style="margin: 0 0 20px;">
			<p>
				<strong><?php esc_html_e( 'Next.js project path not configured.', 'seo-generator' ); ?></strong>
				<?php esc_html_e( 'Set it in Settings below before publishing.', 'seo-generator' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<!-- Page Tabs -->
	<div class="page-builder-tabs" role="tablist">
		<?php foreach ( $pages as $slug => $page ) :
			$saved_order = get_option( "seo_nextjs_block_order_{$slug}", null );
			$order       = is_array( $saved_order ) ? $saved_order : ( $page['default_order'] ?? [] );
			$count       = count( $order );
			$active      = ( $slug === $first_slug );
		?>
			<button
				type="button"
				class="page-builder-tab<?php echo $active ? ' active' : ''; ?>"
				data-page="<?php echo esc_attr( $slug ); ?>"
				role="tab"
				aria-selected="<?php echo $active ? 'true' : 'false'; ?>"
			>
				<?php echo esc_html( $page['label'] ); ?>
				<span class="tab-block-count"><?php echo (int) $count; ?></span>
			</button>
		<?php endforeach; ?>
	</div>

	<!-- Main Card -->
	<div class="seo-card" id="main-content">
		<h3 class="seo-card__title" id="card-title">
			<?php
			$first_page = $pages[ $first_slug ] ?? [];
			printf(
				esc_html__( 'Customize Block Order — %s', 'seo-generator' ),
				esc_html( $first_page['label'] ?? '' )
			);
			?>
		</h3>

		<div class="seo-card__content">

			<!-- Output Slug -->
			<div class="output-slug-row" style="margin-bottom: 20px;">
				<label for="output-slug" style="font-weight: 600; font-size: 13px; display: block; margin-bottom: 4px;">
					<?php esc_html_e( 'Publish to URL:', 'seo-generator' ); ?>
				</label>
				<div style="display: flex; align-items: center; gap: 4px; max-width: 500px;">
					<span style="color: #646970; font-family: monospace; font-size: 13px; white-space: nowrap;">yoursite.com/</span>
					<input
						type="text"
						id="output-slug"
						class="regular-text"
						style="width: 100%;"
						placeholder="e.g. san-diego-jewelry-store"
						value="<?php echo esc_attr( $generator->getSavedSlug( $first_slug ) ); ?>"
					>
				</div>
				<p class="description" style="margin-top: 4px;">
					<?php esc_html_e( 'This creates a new page at this URL. The original page is untouched.', 'seo-generator' ); ?>
				</p>
			</div>

			<div class="block-ordering-split-layout">

				<!-- Left Pane: Sortable Blocks + Add Block -->
				<div class="block-ordering-pane">

					<!-- Add Block Toggle -->
					<div class="add-block-header">
						<button type="button" id="add-block-toggle" class="seo-btn-secondary add-block-btn">
							+ <?php esc_html_e( 'Add Block', 'seo-generator' ); ?>
						</button>
						<span class="text-muted" style="font-size: 12px;"><?php esc_html_e( 'Add widgets from any page', 'seo-generator' ); ?></span>
					</div>

					<!-- Block Picker (hidden by default) -->
					<div id="block-picker" class="block-picker" style="display: none;">
						<div id="block-group-tabs" class="block-group-tabs" role="tablist">
							<button type="button" class="block-group-tab active" data-group="all"><?php esc_html_e( 'All', 'seo-generator' ); ?></button>
							<!-- Populated by JS -->
						</div>
						<div id="block-catalog" class="block-catalog">
							<!-- Populated by JS -->
						</div>
					</div>

					<!-- Current page blocks -->
					<ul id="sortable-blocks" class="seo-sortable-list">
						<?php
						$all_blocks   = $generator->getAllBlocks();
						$saved_order  = get_option( "seo_nextjs_block_order_{$first_slug}", null );
						$active_order = is_array( $saved_order ) ? $saved_order : ( $first_page['default_order'] ?? [] );

						foreach ( $active_order as $block_id ) :
							if ( ! isset( $all_blocks[ $block_id ] ) ) continue;
							$block = $all_blocks[ $block_id ];
						?>
							<li class="seo-sortable-item" data-block="<?php echo esc_attr( $block_id ); ?>">
								<span class="seo-sortable-handle" aria-label="<?php esc_attr_e( 'Drag to reorder', 'seo-generator' ); ?>">⠿</span>
								<div class="seo-sortable-content">
									<strong class="seo-sortable-label"><?php echo esc_html( $block['label'] ); ?></strong>
									<span class="seo-sortable-desc"><?php echo esc_html( $block['description'] ); ?></span>
								</div>
								<button type="button" class="seo-sortable-remove" aria-label="<?php esc_attr_e( 'Remove block', 'seo-generator' ); ?>">
									<svg width="14" height="14" viewBox="0 0 14 14" fill="none">
										<path d="M1 1L13 13M13 1L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
									</svg>
								</button>
							</li>
						<?php endforeach; ?>
					</ul>

					<!-- Action Buttons -->
					<div class="seo-btn-group mt-6">
						<button type="button" id="save-order-btn" class="seo-btn-secondary">
							<?php esc_html_e( 'Save Order', 'seo-generator' ); ?>
						</button>
						<button type="button" id="reset-order-btn" class="seo-btn-secondary">
							<?php esc_html_e( 'Reset to Default', 'seo-generator' ); ?>
						</button>
						<button type="button" id="publish-btn" class="seo-btn-primary">
							<?php esc_html_e( 'Publish to Next.js', 'seo-generator' ); ?> →
						</button>
					</div>
					<span id="save-status" class="page-builder-save-status" style="display: inline-block; margin-top: 10px;"></span>
				</div>

				<!-- Right Pane: Preview -->
				<div class="block-preview-pane" aria-label="<?php esc_attr_e( 'Page Preview', 'seo-generator' ); ?>">
					<div class="block-preview-header">
						<div class="preview-header-content">
							<div>
								<h3><?php esc_html_e( 'Live Preview', 'seo-generator' ); ?></h3>
								<p class="preview-disclaimer"><?php esc_html_e( 'Preview from Next.js dev server.', 'seo-generator' ); ?></p>
							</div>
							<div class="device-toggle-buttons" role="tablist" aria-label="<?php esc_attr_e( 'Preview device', 'seo-generator' ); ?>">
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
						</div>
					</div>
					<div id="block-preview-container" class="preview-device-wrapper" data-device="tablet">
						<div class="device-frame tablet-frame">
							<div class="device-screen">
								<iframe id="block-preview-iframe" src="about:blank" title="<?php esc_attr_e( 'Page preview', 'seo-generator' ); ?>"></iframe>
							</div>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>

	<!-- Settings -->
	<div class="seo-card mt-4">
		<h3 class="seo-card__title">⚙️ <?php esc_html_e( 'Settings', 'seo-generator' ); ?></h3>
		<div class="seo-card__content">
			<table class="form-table">
				<tr>
					<th scope="row"><label for="nextjs-project-path"><?php esc_html_e( 'Next.js Project Path', 'seo-generator' ); ?></label></th>
					<td>
						<input type="text" id="nextjs-project-path" class="regular-text" style="width: 100%; max-width: 600px;" value="<?php echo esc_attr( $project_path ); ?>" placeholder="/var/www/new-bravojewellers/frontend">
						<p class="description"><?php esc_html_e( 'Absolute path to the Next.js frontend directory.', 'seo-generator' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="nextjs-preview-url"><?php esc_html_e( 'Preview URL', 'seo-generator' ); ?></label></th>
					<td>
						<input type="url" id="nextjs-preview-url" class="regular-text" style="width: 100%; max-width: 600px;" value="<?php echo esc_attr( $preview_url ); ?>" placeholder="http://localhost:3000">
						<p class="description"><?php esc_html_e( 'URL of the Next.js dev server for live preview.', 'seo-generator' ); ?></p>
					</td>
				</tr>
			</table>
			<table class="form-table" style="margin-top: 0;">
				<tr>
					<th scope="row"><?php esc_html_e( 'Dynamic Route', 'seo-generator' ); ?></th>
					<td>
						<?php if ( get_option( 'seo_nextjs_dynamic_setup_done', false ) ) : ?>
							<span style="color: #00a32a; font-weight: 600;">&#10003; <?php esc_html_e( 'Set up', 'seo-generator' ); ?></span>
							<button type="button" id="setup-dynamic-btn" class="seo-btn-secondary" style="margin-left: 10px;">
								<?php esc_html_e( 'Re-generate Files', 'seo-generator' ); ?>
							</button>
						<?php else : ?>
							<button type="button" id="setup-dynamic-btn" class="seo-btn-primary">
								<?php esc_html_e( 'Setup Dynamic Route', 'seo-generator' ); ?>
							</button>
						<?php endif; ?>
						<span id="setup-dynamic-status" class="page-builder-save-status" style="margin-left: 10px;"></span>
						<p class="description"><?php esc_html_e( 'Generate the [slug] catch-all route. After setup, run pnpm build once — then every publish is instant.', 'seo-generator' ); ?></p>
					</td>
				</tr>
			</table>
			<button type="button" id="save-settings-btn" class="seo-btn-secondary"><?php esc_html_e( 'Save Settings', 'seo-generator' ); ?></button>
			<span id="settings-status" class="page-builder-save-status" style="margin-left: 10px;"></span>
		</div>
	</div>

</div>
