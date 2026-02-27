<?php
/**
 * Block Rule Editor Admin Template
 *
 * Two-panel layout: Block List | Rule Editor
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap seo-generator-page" id="block-rule-editor-app">

	<!-- Top Bar -->
	<div class="bre-top-bar">
		<h1 class="heading-1"><?php esc_html_e( 'Block Rules', 'seo-generator' ); ?></h1>
		<p class="bre-subtitle"><?php esc_html_e( 'Edit content contracts, validation rules, and image specs for each block.', 'seo-generator' ); ?></p>
	</div>

	<!-- Two-Panel Layout -->
	<div class="bre-layout">

		<!-- LEFT: Block List -->
		<div class="bre-panel bre-block-list-panel">
			<div class="bre-panel-header">
				<h3><?php esc_html_e( 'Blocks', 'seo-generator' ); ?></h3>
			</div>

			<div class="bre-search-wrapper">
				<input type="text" id="bre-search" placeholder="<?php esc_attr_e( 'Search blocks...', 'seo-generator' ); ?>" autocomplete="off">
			</div>

			<div class="bre-block-list" id="bre-block-list">
				<!-- Populated by JS -->
			</div>
		</div>

		<!-- RIGHT: Rule Editor -->
		<div class="bre-panel bre-editor-panel">
			<div id="bre-editor-placeholder" class="bre-placeholder">
				<p><?php esc_html_e( 'Select a block from the list to edit its rules.', 'seo-generator' ); ?></p>
			</div>

			<div id="bre-editor-content" class="bre-editor-content" style="display:none;">
				<!-- Block Header -->
				<div class="bre-editor-header">
					<h2 id="bre-editor-title"></h2>
					<span id="bre-editor-badge" class="bre-badge"></span>
					<span id="bre-editor-version" class="bre-version-tag"></span>
				</div>

				<!-- Content Slots Accordion -->
				<div class="bre-accordion" id="bre-slots-accordion">
					<button type="button" class="bre-accordion-toggle" data-target="bre-slots-body">
						<?php esc_html_e( 'Content Slots', 'seo-generator' ); ?>
						<span class="bre-accordion-arrow">&#9660;</span>
					</button>
					<div class="bre-accordion-body" id="bre-slots-body">
						<!-- Populated by JS -->
					</div>
				</div>

				<!-- Image Rules Accordion -->
				<div class="bre-accordion" id="bre-images-accordion">
					<button type="button" class="bre-accordion-toggle" data-target="bre-images-body">
						<?php esc_html_e( 'Image Rules', 'seo-generator' ); ?>
						<span class="bre-accordion-arrow">&#9660;</span>
					</button>
					<div class="bre-accordion-body" id="bre-images-body">
						<!-- Populated by JS -->
					</div>
				</div>

				<!-- Breadcrumb Section -->
				<div class="bre-accordion" id="bre-breadcrumb-accordion">
					<button type="button" class="bre-accordion-toggle" data-target="bre-breadcrumb-body">
						<?php esc_html_e( 'Breadcrumb', 'seo-generator' ); ?>
						<span class="bre-accordion-arrow">&#9660;</span>
					</button>
					<div class="bre-accordion-body" id="bre-breadcrumb-body">
						<div class="bre-field-row">
							<label>
								<input type="checkbox" id="bre-breadcrumb-enabled">
								<?php esc_html_e( 'Enabled', 'seo-generator' ); ?>
							</label>
						</div>
						<div class="bre-field-row">
							<label for="bre-breadcrumb-pattern"><?php esc_html_e( 'Pattern', 'seo-generator' ); ?></label>
							<input type="text" id="bre-breadcrumb-pattern" class="bre-input" placeholder="Home / {page_title}">
						</div>
					</div>
				</div>

				<!-- Version History -->
				<div class="bre-accordion" id="bre-versions-accordion">
					<button type="button" class="bre-accordion-toggle" data-target="bre-versions-body">
						<?php esc_html_e( 'Version History', 'seo-generator' ); ?>
						<span class="bre-accordion-arrow">&#9660;</span>
					</button>
					<div class="bre-accordion-body" id="bre-versions-body">
						<div id="bre-version-list">
							<!-- Populated by JS -->
						</div>
					</div>
				</div>

				<!-- Actions Bar -->
				<div class="bre-actions-bar">
					<button type="button" id="bre-save-btn" class="seo-btn-primary"><?php esc_html_e( 'Save', 'seo-generator' ); ?></button>
					<button type="button" id="bre-reset-btn" class="seo-btn-secondary"><?php esc_html_e( 'Reset to Factory', 'seo-generator' ); ?></button>
				</div>

				<div id="bre-status-message" class="bre-status-message" style="display:none;"></div>
			</div>
		</div>

	</div>
</div>
