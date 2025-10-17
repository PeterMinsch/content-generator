/**
 * Block Preview Manager
 * Manages the live preview iframe for block ordering
 *
 * @package SEOGenerator
 */

import { getBlockTemplate } from './block-templates.js';

/**
 * BlockPreviewManager
 * Manages the live preview iframe for block ordering
 */
class BlockPreviewManager {
	/**
	 * Constructor
	 *
	 * @param {HTMLIFrameElement} iframeElement - The iframe element to manage
	 */
	constructor( iframeElement ) {
		this.iframe = iframeElement;
		this.iframeDoc = null;
		this.init();
	}

	/**
	 * Initialize iframe with base HTML and CSS
	 */
	init() {
		// Check browser compatibility
		this.checkBrowserSupport();

		if ( ! this.iframe ) {
			console.error( '[Block Preview] Iframe element not found' );
			return;
		}

		// Wait for iframe to load
		this.iframe.addEventListener( 'load', () => {
			this.iframeDoc =
				this.iframe.contentDocument ||
				this.iframe.contentWindow.document;
			this.injectBaseStructure();
		} );

		// Trigger load by setting srcdoc
		this.iframe.srcdoc = this.getBaseHTML();
	}

	/**
	 * Check browser compatibility for preview features
	 *
	 * @return {boolean} True if browser supports required features
	 */
	checkBrowserSupport() {
		const checks = {
			template: 'content' in document.createElement( 'template' ),
			grid: 'grid' in document.createElement( 'div' ).style,
			flexbox: 'flex' in document.createElement( 'div' ).style,
		};

		const isSupported = Object.values( checks ).every(
			( check ) => check === true
		);

		if ( ! isSupported ) {
			console.warn(
				'[Block Preview] Browser may not fully support preview features:',
				checks
			);
		} else {
			console.log( '[Block Preview] Browser compatibility check passed' );
		}

		return isSupported;
	}

	/**
	 * Get base HTML structure for iframe
	 *
	 * @return {string} HTML document structure
	 */
	getBaseHTML() {
		return `
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Block Preview</title>
	<style>${ this.getBaseCSS() }</style>
</head>
<body>
	<div id="preview-root">
		<!-- Block templates will be injected here -->
	</div>
</body>
</html>
		`;
	}

	/**
	 * Get base CSS for preview styling
	 *
	 * @return {string} CSS styles
	 */
	getBaseCSS() {
		return `
* {
	margin: 0;
	padding: 0;
	box-sizing: border-box;
}

html {
	scroll-behavior: smooth;
}

body {
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
	font-size: 16px;
	line-height: 1.6;
	color: #1d2327;
	background: #fff;
	padding: 20px;
}

#preview-root {
	max-width: 800px;
	margin: 0 auto;
}

/* Utility classes for block templates */
.preview-block {
	margin-bottom: 30px;
	padding: 20px;
	background: #f9f9f9;
	border-radius: 4px;
}

/* Empty State */
.preview-empty-state {
	text-align: center;
	padding: 80px 20px;
	color: #646970;
}

.empty-state-icon {
	font-size: 48px;
	margin-bottom: 16px;
}

.empty-state-message {
	font-size: 18px;
	font-weight: 600;
	margin: 0 0 8px 0;
	color: #1d2327;
}

.empty-state-hint {
	font-size: 14px;
	margin: 0;
	color: #646970;
}

/* Error State */
.preview-error-state {
	text-align: center;
	padding: 80px 20px;
	color: #d63638;
}

.error-state-icon {
	font-size: 48px;
	margin-bottom: 16px;
}

.error-state-message {
	font-size: 18px;
	font-weight: 600;
	margin: 0 0 8px 0;
	color: #d63638;
}

.error-state-hint {
	font-size: 14px;
	margin: 0;
	color: #646970;
}

/* Hero Block - Matches frontend.css */
.seo-block--hero {
	text-align: center;
	padding: 3rem 0;
	border-bottom: 2px solid #dddddd;
	margin-bottom: 3rem;
}

.seo-block--hero h1 {
	margin-top: 0;
	font-size: 2.5rem;
	margin-bottom: 1rem;
	font-weight: 700;
	color: #333333;
	line-height: 1.3;
}

.seo-block--hero .hero-subtitle {
	font-size: 1.25rem;
	color: #666666;
	font-weight: 400;
	margin-bottom: 1.5rem;
}

.seo-block--hero .hero-summary {
	max-width: 800px;
	margin: 0 auto 2rem auto;
	font-size: 1.125rem;
	line-height: 1.7;
}

.seo-block--hero .hero-image {
	margin-top: 2rem;
}

.seo-block--hero .hero-image img {
	margin: 0 auto;
	border-radius: 4px;
	max-width: 100%;
	height: auto;
	display: block;
}

/* SERP Answer Block */
.serp-block {
	background: #f0f6fc;
	border-left: 4px solid #4A90E2;
}

.serp-box {
	padding: 20px;
}

.serp-heading {
	font-size: 20px;
	font-weight: 600;
	margin: 0 0 12px 0;
	color: #1d2327;
}

.serp-answer {
	margin: 0 0 15px 0;
	line-height: 1.6;
}

.serp-bullets {
	list-style: none;
	padding: 0;
	margin: 0;
}

.serp-bullets li {
	padding: 6px 0 6px 25px;
	position: relative;
}

.serp-bullets li:before {
	content: "✓";
	position: absolute;
	left: 0;
	color: #4A90E2;
	font-weight: bold;
}

/* Product Criteria Block */
.criteria-block h3 {
	font-size: 22px;
	margin: 0 0 20px 0;
}

.criteria-grid {
	display: grid;
	grid-template-columns: 1fr;
	gap: 15px;
}

.criteria-item {
	display: flex;
	gap: 12px;
	padding: 15px;
	background: #f9f9f9;
	border-radius: 6px;
}

.criteria-icon {
	flex-shrink: 0;
	width: 24px;
	height: 24px;
	background: #4A90E2;
	color: white;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 14px;
}

.criteria-content strong {
	display: block;
	margin-bottom: 5px;
	color: #1d2327;
}

.criteria-content p {
	margin: 0;
	font-size: 14px;
	color: #50575e;
}

/* Materials Block */
.materials-heading {
	font-size: 22px;
	margin: 0 0 20px 0;
}

.materials-grid {
	display: grid;
	grid-template-columns: 1fr;
	gap: 15px;
}

.material-card {
	padding: 18px;
	background: #f9f9f9;
	border-radius: 6px;
	border: 1px solid #ddd;
}

.material-card h4 {
	margin: 0 0 12px 0;
	font-size: 18px;
	color: #1d2327;
}

.material-pros,
.material-cons,
.material-best {
	font-size: 14px;
	margin-bottom: 8px;
	line-height: 1.5;
}

.material-pros strong,
.material-cons strong,
.material-best strong {
	color: #1d2327;
}

/* Process Block */
.process-heading {
	font-size: 22px;
	margin: 0 0 25px 0;
	text-align: center;
}

.process-steps {
	display: flex;
	flex-direction: column;
	gap: 20px;
}

.process-step {
	display: flex;
	gap: 15px;
}

.step-number {
	flex-shrink: 0;
	width: 40px;
	height: 40px;
	background: #4A90E2;
	color: white;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 20px;
	font-weight: 700;
}

.step-content h4 {
	margin: 0 0 8px 0;
	font-size: 18px;
	color: #1d2327;
}

.step-content p {
	margin: 0;
	font-size: 14px;
	line-height: 1.5;
	color: #50575e;
}

/* Comparison Block */
.comparison-heading {
	font-size: 22px;
	margin: 0 0 10px 0;
}

.comparison-intro {
	color: #50575e;
	margin: 0 0 20px 0;
}

.comparison-table {
	border: 1px solid #ddd;
	border-radius: 6px;
	overflow: hidden;
}

.comparison-header,
.comparison-row {
	display: grid;
	grid-template-columns: 1fr 1fr 1fr;
}

.comparison-header {
	background: #f5f5f5;
	font-weight: 600;
}

.comparison-header .comparison-col {
	padding: 12px;
	border-bottom: 2px solid #ddd;
}

.comparison-row {
	border-bottom: 1px solid #eee;
}

.comparison-row:last-child {
	border-bottom: none;
}

.comparison-col {
	padding: 12px;
	font-size: 14px;
	border-right: 1px solid #eee;
}

.comparison-col:last-child {
	border-right: none;
}

/* Product Showcase Block */
.showcase-heading {
	font-size: 22px;
	margin: 0 0 10px 0;
}

.showcase-intro {
	color: #50575e;
	margin: 0 0 20px 0;
}

.showcase-grid {
	display: grid;
	grid-template-columns: 1fr;
	gap: 15px;
}

.showcase-item {
	text-align: center;
	padding: 15px;
	background: #f9f9f9;
	border-radius: 6px;
}

.showcase-image {
	height: 200px;
	background-size: cover;
	background-position: center;
	border-radius: 4px;
	margin-bottom: 12px;
}

.showcase-item h4 {
	margin: 0 0 5px 0;
	font-size: 16px;
}

.showcase-sku {
	font-size: 12px;
	color: #999;
	margin: 0 0 8px 0;
}

.showcase-desc {
	font-size: 14px;
	color: #50575e;
	margin: 0;
}

/* Size & Fit Block */
.size-heading {
	font-size: 22px;
	margin: 0 0 20px 0;
}

.size-chart {
	margin-bottom: 20px;
}

.size-chart-image {
	width: 100%;
	height: auto;
	border-radius: 4px;
}

.comfort-fit h4 {
	font-size: 18px;
	margin: 0 0 10px 0;
}

.comfort-fit p {
	line-height: 1.6;
	color: #50575e;
	margin: 0;
}

/* Care & Warranty Block */
.care-section,
.warranty-section {
	margin-bottom: 25px;
}

.care-section:last-child,
.warranty-section:last-child {
	margin-bottom: 0;
}

.care-heading,
.warranty-heading {
	font-size: 20px;
	margin: 0 0 15px 0;
}

.care-list {
	list-style: none;
	padding: 0;
	margin: 0;
}

.care-list li {
	padding: 8px 0 8px 25px;
	position: relative;
	line-height: 1.5;
}

.care-list li:before {
	content: "•";
	position: absolute;
	left: 0;
	color: #4A90E2;
	font-size: 20px;
}

.warranty-text {
	line-height: 1.6;
	color: #50575e;
	margin: 0;
}

/* Ethics Block */
.ethics-heading {
	font-size: 22px;
	margin: 0 0 15px 0;
}

.ethics-text {
	line-height: 1.6;
	color: #50575e;
	margin: 0 0 20px 0;
}

.certifications h4 {
	font-size: 18px;
	margin: 0 0 12px 0;
}

.cert-badges {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.cert-badge {
	padding: 10px 15px;
	background: #f0f6fc;
	border-left: 3px solid #4A90E2;
	border-radius: 4px;
	font-size: 14px;
	color: #1d2327;
}

/* FAQs Block */
.faqs-heading {
	font-size: 22px;
	margin: 0 0 20px 0;
}

.faq-list {
	display: flex;
	flex-direction: column;
	gap: 15px;
}

.faq-item {
	padding: 18px;
	background: #f9f9f9;
	border-radius: 6px;
}

.faq-question {
	font-size: 16px;
	font-weight: 600;
	margin: 0 0 10px 0;
	color: #1d2327;
}

.faq-answer {
	font-size: 14px;
	line-height: 1.6;
	color: #50575e;
	margin: 0;
}

/* CTA Block */
.cta-block {
	background: linear-gradient(135deg, #4A90E2 0%, #357ABD 100%);
	color: white;
	text-align: center;
	padding: 40px 30px;
}

.cta-heading {
	font-size: 26px;
	font-weight: 700;
	margin: 0 0 15px 0;
	color: white;
}

.cta-text {
	font-size: 16px;
	line-height: 1.6;
	margin: 0 0 25px 0;
	color: rgba(255,255,255,0.95);
}

.cta-buttons {
	display: flex;
	flex-direction: column;
	gap: 10px;
	align-items: center;
}

.cta-btn {
	padding: 12px 30px;
	border: none;
	border-radius: 6px;
	font-size: 16px;
	font-weight: 600;
	cursor: pointer;
	transition: all 0.2s ease;
}

.cta-btn-primary {
	background: white;
	color: #4A90E2;
}

.cta-btn-primary:hover {
	background: #f5f5f5;
}

.cta-btn-secondary {
	background: transparent;
	color: white;
	border: 2px solid white;
}

.cta-btn-secondary:hover {
	background: rgba(255,255,255,0.1);
}

/* About Section Block */
.seo-block--about-section {
	background-color: #FEF9F4;
	padding: 3rem 1.5rem;
	margin-bottom: 2rem;
}

.about-section__content {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 2rem;
}

.about-section__header {
	text-align: center;
	max-width: 600px;
}

.about-section__heading {
	font-size: 2rem;
	font-weight: 300;
	line-height: 1;
	letter-spacing: -0.055em;
	text-transform: uppercase;
	margin: 0 0 1rem 0;
	color: #272521;
}

.about-section__heading::after {
	content: "";
	display: block;
	width: 60px;
	height: 2px;
	background: #CA9652;
	margin: 0.75rem auto 0;
}

.about-section__description {
	font-size: 1rem;
	line-height: 1.4;
	font-weight: 500;
	color: rgba(39, 37, 33, 0.65);
	margin: 0;
}

.about-section__features {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 2rem;
	width: 100%;
}

.about-section__feature {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 1rem;
	text-align: center;
}

.about-section__feature-icon {
	width: 56px;
	height: 56px;
	border-radius: 50%;
	border: 1px solid #CA9652;
	display: flex;
	align-items: center;
	justify-content: center;
	color: #CA9652;
	flex-shrink: 0;
}

.about-section__feature-icon svg {
	width: 28px;
	height: 28px;
}

.about-section__feature-content {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 0.25rem;
}

.about-section__feature-title {
	font-size: 1.25rem;
	line-height: 1;
	text-transform: uppercase;
	color: #272521;
	margin: 0;
	font-weight: 400;
}

.about-section__feature-desc {
	font-size: 0.875rem;
	line-height: 1.4;
	font-weight: 500;
	color: #8F8F8F;
	margin: 0;
}

/* Unknown Block (Fallback) */
.unknown-block {
	background: #fff3cd;
	border: 1px dashed #ffc107;
	padding: 20px;
	text-align: center;
}

.unknown-message {
	font-size: 16px;
	margin: 0 0 8px 0;
}

.unknown-hint {
	font-size: 14px;
	color: #856404;
	margin: 0;
}
		`;
	}

	/**
	 * Inject base structure into loaded iframe document
	 */
	injectBaseStructure() {
		if ( ! this.iframeDoc ) {
			console.error(
				'[Block Preview] Iframe document not accessible'
			);
			return;
		}

		console.log( '[Block Preview] Iframe initialized successfully' );

		// Show empty state initially
		this.showEmptyState();
	}

	/**
	 * Update preview with block order
	 *
	 * @param {Array} blockOrder - Array of block type strings
	 */
	updatePreview( blockOrder ) {
		try {
			if ( ! this.iframeDoc ) {
				console.warn(
					'[Block Preview] Iframe not ready yet, skipping update'
				);
				return;
			}

			if ( ! blockOrder || blockOrder.length === 0 ) {
				this.showEmptyState();
				this.announceUpdate( 0 );
				return;
			}

			// Generate HTML from block templates.
			const htmlContent = blockOrder
				.map( ( blockType ) => {
					return getBlockTemplate( blockType );
				} )
				.join( '' );

			// Update iframe content.
			const root = this.iframeDoc.getElementById( 'preview-root' );
			if ( root ) {
				root.innerHTML = htmlContent;
			}

			// Announce to screen readers
			this.announceUpdate( blockOrder.length );

			console.log(
				`[Block Preview] Rendered ${ blockOrder.length } blocks successfully`
			);
		} catch ( error ) {
			console.error( '[Block Preview] Update failed:', error );
			this.showErrorState();
		}
	}

	/**
	 * Announce preview update to screen readers
	 *
	 * @param {number} blockCount - Number of blocks in preview
	 */
	announceUpdate( blockCount ) {
		// Find ARIA live region in parent document (not in iframe)
		const liveRegion = document.querySelector(
			'.preview-sr-announcements'
		);

		if ( ! liveRegion ) {
			console.warn( '[Block Preview] ARIA live region not found' );
			return;
		}

		let announcement;
		if ( blockCount === 0 ) {
			announcement = 'Preview cleared. No blocks selected.';
		} else {
			announcement = `Preview updated with ${ blockCount } block${
				blockCount !== 1 ? 's' : ''
			}`;
		}

		// Update text content for screen readers
		liveRegion.textContent = announcement;

		// Clear after 1 second to allow multiple announcements
		setTimeout( () => {
			liveRegion.textContent = '';
		}, 1000 );
	}

	/**
	 * Clear preview content
	 */
	clearPreview() {
		if ( ! this.iframeDoc ) {
			return;
		}

		const root = this.iframeDoc.getElementById( 'preview-root' );
		if ( root ) {
			root.innerHTML = '';
		}
	}

	/**
	 * Show empty state message when no blocks selected
	 */
	showEmptyState() {
		if ( ! this.iframeDoc ) {
			return;
		}

		const root = this.iframeDoc.getElementById( 'preview-root' );
		if ( root ) {
			root.innerHTML = `
				<div class="preview-empty-state">
					<p class="empty-state-icon" aria-hidden="true">📋</p>
					<p class="empty-state-message">No blocks selected.</p>
					<p class="empty-state-hint">Drag blocks from the left to see preview.</p>
				</div>
			`;
		}

		console.log( '[Block Preview] Showing empty state' );
	}

	/**
	 * Show error state when preview rendering fails
	 */
	showErrorState() {
		if ( ! this.iframeDoc ) {
			return;
		}

		const root = this.iframeDoc.getElementById( 'preview-root' );
		if ( root ) {
			root.innerHTML = `
				<div class="preview-error-state">
					<p class="error-state-icon" aria-hidden="true">⚠️</p>
					<p class="error-state-message">Preview could not be rendered.</p>
					<p class="error-state-hint">Please refresh the page or contact support if the issue persists.</p>
				</div>
			`;
		}

		console.error( '[Block Preview] Showing error state' );
	}
}

// Export for use in block-ordering.js
window.BlockPreviewManager = BlockPreviewManager;
