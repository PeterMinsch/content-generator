<?php
/**
 * Page Builder Admin Template
 *
 * Drag-and-drop interface for managing Next.js page blocks.
 * Supports multiple pages via tabs.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

// Load config.
$config       = require SEO_GENERATOR_PLUGIN_DIR . 'config/nextjs-block-definitions.php';
$pages        = $config['pages'];
$project_path = get_option( 'seo_nextjs_project_path', '' );
$preview_url  = get_option( 'seo_nextjs_preview_url', 'http://localhost:3000' );

// Get the first page slug as default active.
$page_slugs  = array_keys( $pages );
$active_slug = $page_slugs[0] ?? 'homepage';

// Get saved order for active page.
$active_page  = $pages[ $active_slug ];
$saved_order  = get_option( 'seo_nextjs_block_order_' . $active_slug, '' );
if ( ! empty( $saved_order ) && is_string( $saved_order ) ) {
	$block_order = json_decode( $saved_order, true );
	if ( ! is_array( $block_order ) ) {
		$block_order = $active_page['default_order'];
	}
} else {
	$block_order = $active_page['default_order'];
}
$blocks = $active_page['blocks'];
?>

<div class="wrap seo-generator-page">
	<!-- Skip Navigation Link -->
	<a href="#main-content" class="skip-link"><?php esc_html_e( 'Skip to main content', 'seo-generator' ); ?></a>

	<!-- Page Header -->
	<h1 class="heading-1"><?php esc_html_e( 'Next.js Page Builder', 'seo-generator' ); ?></h1>

	<?php if ( empty( $project_path ) ) : ?>
		<div class="notice notice-warning" style="margin: 20px 0;">
			<p>
				<strong><?php esc_html_e( 'Next.js project path not configured.', 'seo-generator' ); ?></strong>
				<?php esc_html_e( 'Set the path in Settings below before publishing.', 'seo-generator' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<!-- Page Tabs -->
	<div class="page-builder-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Page selection', 'seo-generator' ); ?>">
		<?php foreach ( $pages as $slug => $page_config ) : ?>
			<button
				type="button"
				class="page-builder-tab<?php echo $slug === $active_slug ? ' active' : ''; ?>"
				data-page="<?php echo esc_attr( $slug ); ?>"
				role="tab"
				aria-selected="<?php echo $slug === $active_slug ? 'true' : 'false'; ?>"
			>
				<?php echo esc_html( $page_config['label'] ); ?>
				<span class="tab-block-count" id="tab-count-<?php echo esc_attr( $slug ); ?>">
					<?php echo count( $slug === $active_slug ? $block_order : $page_config['default_order'] ); ?>
				</span>
			</button>
		<?php endforeach; ?>
	</div>

	<!-- Block Ordering + Preview -->
	<div class="seo-card" id="block-ordering-section" style="margin-top: 0; border-top-left-radius: 0;">
		<h3 class="seo-card__title" id="card-title">
			<?php
			printf(
				/* translators: %s: page label */
				esc_html__( 'Customize Block Order — %s', 'seo-generator' ),
				esc_html( $active_page['label'] )
			);
			?>
		</h3>
		<div class="seo-card__content">
			<!-- Split-Pane Layout -->
			<div class="block-ordering-split-layout">
				<!-- Left Pane: Block Ordering Controls -->
				<div class="block-ordering-pane">
					<p class="mb-4" id="block-pane-description"><?php esc_html_e( 'Drag blocks to reorder how they appear on the page. Click Publish to write the updated page.tsx file.', 'seo-generator' ); ?></p>

					<!-- Sortable Block List -->
					<ul id="sortable-blocks" class="seo-sortable-list">
						<?php
						foreach ( $block_order as $block_key ) :
							if ( ! isset( $blocks[ $block_key ] ) ) {
								continue;
							}
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
						<?php endforeach; ?>
					</ul>

					<!-- Removed blocks area -->
					<div id="removed-blocks-section" style="display: none; margin-top: 20px;">
						<h4 style="margin-bottom: 10px; color: #646970;"><?php esc_html_e( 'Removed Blocks', 'seo-generator' ); ?></h4>
						<ul id="removed-blocks" class="seo-sortable-list" style="opacity: 0.6;"></ul>
					</div>

					<!-- Action Buttons -->
					<div class="seo-btn-group mt-6">
						<button type="button" id="reset-order-btn" class="seo-btn-secondary">
							<?php esc_html_e( 'Reset to Default Order', 'seo-generator' ); ?>
						</button>
						<button type="button" id="publish-page-btn" class="seo-btn-primary">
							<?php esc_html_e( 'Publish to Next.js', 'seo-generator' ); ?> →
						</button>
					</div>
					<span id="save-status" class="page-builder-save-status" style="display: inline-block; margin-top: 10px;"></span>
				</div>

				<!-- Right Pane: Preview Container -->
				<div class="block-preview-pane" aria-label="<?php esc_attr_e( 'Page Preview', 'seo-generator' ); ?>">
					<div class="block-preview-header">
						<div class="preview-header-content">
							<div>
								<h3><?php esc_html_e( 'Live Preview', 'seo-generator' ); ?></h3>
								<p class="preview-disclaimer"><?php esc_html_e( 'Live preview from Next.js dev server. Must be running for preview to work.', 'seo-generator' ); ?></p>
							</div>
							<!-- Device Toggle Buttons -->
							<div class="device-toggle-buttons" role="tablist" aria-label="<?php esc_attr_e( 'Preview device type', 'seo-generator' ); ?>">
								<button type="button" class="device-toggle-btn" data-device="mobile" role="tab" aria-selected="false" title="<?php esc_attr_e( 'Mobile preview', 'seo-generator' ); ?>">
									<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
										<rect x="5" y="2" width="10" height="16" rx="1" stroke="currentColor" stroke-width="1.5"/>
										<line x1="8" y1="15.5" x2="12" y2="15.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
									</svg>
									<span class="device-label"><?php esc_html_e( 'Mobile', 'seo-generator' ); ?></span>
								</button>
								<button type="button" class="device-toggle-btn active" data-device="tablet" role="tab" aria-selected="true" title="<?php esc_attr_e( 'Tablet preview', 'seo-generator' ); ?>">
									<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
										<rect x="3" y="2" width="14" height="16" rx="1" stroke="currentColor" stroke-width="1.5"/>
										<circle cx="10" cy="15.5" r="0.75" fill="currentColor"/>
									</svg>
									<span class="device-label"><?php esc_html_e( 'Tablet', 'seo-generator' ); ?></span>
								</button>
								<button type="button" class="device-toggle-btn" data-device="desktop" role="tab" aria-selected="false" title="<?php esc_attr_e( 'Desktop preview', 'seo-generator' ); ?>">
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
						<div class="device-frame tablet-frame">
							<div class="device-screen">
								<iframe
									id="block-preview-iframe"
									src="about:blank"
									title="<?php esc_attr_e( 'Block preview iframe', 'seo-generator' ); ?>"
									aria-label="<?php esc_attr_e( 'Live preview of page layout', 'seo-generator' ); ?>"
								></iframe>
							</div>
						</div>
					</div>
					<div class="preview-sr-announcements" role="status" aria-live="polite" aria-atomic="true"></div>
				</div>
			</div>
		</div>
	</div>

	<!-- Settings Section -->
	<div class="seo-card mt-4">
		<h3 class="seo-card__title">
			⚙️ <?php esc_html_e( 'Page Builder Settings', 'seo-generator' ); ?>
		</h3>
		<div class="seo-card__content">
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="nextjs-project-path"><?php esc_html_e( 'Next.js Project Path', 'seo-generator' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							id="nextjs-project-path"
							class="regular-text"
							style="width: 100%; max-width: 600px;"
							value="<?php echo esc_attr( $project_path ); ?>"
							placeholder="C:\Users\petem\dev\smartjeweller\new-bravojewellers\frontend"
						>
						<p class="description">
							<?php esc_html_e( 'Absolute path to the Next.js frontend directory (where src/app/page.tsx lives).', 'seo-generator' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="nextjs-preview-url"><?php esc_html_e( 'Preview URL', 'seo-generator' ); ?></label>
					</th>
					<td>
						<input
							type="url"
							id="nextjs-preview-url"
							class="regular-text"
							style="width: 100%; max-width: 600px;"
							value="<?php echo esc_attr( $preview_url ); ?>"
							placeholder="http://localhost:3000"
						>
						<p class="description">
							<?php esc_html_e( 'URL of the running Next.js dev server. Must be running for preview to work.', 'seo-generator' ); ?>
						</p>
					</td>
				</tr>
			</table>
			<button type="button" id="save-settings-btn" class="seo-btn-secondary">
				<?php esc_html_e( 'Save Settings', 'seo-generator' ); ?>
			</button>
			<span id="settings-status" class="page-builder-save-status" style="margin-left: 10px;"></span>
		</div>
	</div>

</div>
