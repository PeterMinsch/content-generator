/**
 * Block Ordering Component
 *
 * Handles drag-and-drop block ordering for CSV import.
 *
 * @package SEOGenerator
 */

import Sortable from 'sortablejs';
import apiFetch from '@wordpress/api-fetch';

/**
 * Block Ordering Manager
 */
class BlockOrderingManager {
	/**
	 * Constructor
	 */
	constructor() {
		this.sortableList = document.getElementById( 'sortable-blocks' );
		this.sortable = null;
		this.defaultOrder = [];

		if ( ! this.sortableList ) {
			return;
		}

		// Store default order for reset functionality
		this.storeDefaultOrder();

		// Initialize sortable
		this.initSortable();

		// Bind event listeners
		this.bindEvents();

		// Trigger initial preview render
		this.triggerInitialPreview();
	}

	/**
	 * Store the default block order
	 */
	storeDefaultOrder() {
		this.defaultOrder = Array.from( this.sortableList.children ).map(
			( li ) => li.dataset.block
		);
	}

	/**
	 * Initialize Sortable.js
	 */
	initSortable() {
		this.sortable = Sortable.create( this.sortableList, {
			animation: 200, // Smooth animation when dropping
			easing: 'cubic-bezier(0.4, 0, 0.2, 1)', // Apple's easing curve
			handle: '.seo-sortable-handle',
			ghostClass: 'seo-sortable-ghost',
			dragClass: 'seo-sortable-drag',
			chosenClass: 'seo-sortable-chosen',

			// Make the card follow cursor
			forceFallback: false, // Use native HTML5 drag
			fallbackOnBody: true, // Append drag element to body
			swapThreshold: 0.65, // Percentage of target that must be covered before swap

			// Custom drag image - makes card follow cursor smoothly
			setData: function( dataTransfer, dragEl ) {
				// Create a custom drag preview
				const rect = dragEl.getBoundingClientRect();
				const clone = dragEl.cloneNode( true );

				// Style the clone to look elevated
				clone.style.width = rect.width + 'px';
				clone.style.transform = 'rotate(2deg) scale(1.05)';
				clone.style.opacity = '0.95';

				dataTransfer.setDragImage( clone, rect.width / 2, rect.height / 2 );
			},

			onStart: () => {
				this.sortableList.classList.add( 'seo-sortable-dragging' );
			},
			onEnd: () => {
				this.sortableList.classList.remove( 'seo-sortable-dragging' );
				// Update preview after drag-drop completes
				this.updatePreview();
			},
		} );
	}

	/**
	 * Bind event listeners
	 */
	bindEvents() {
		// Reset button
		const resetBtn = document.getElementById( 'reset-order-btn' );
		if ( resetBtn ) {
			resetBtn.addEventListener( 'click', () =>
				this.resetToDefaultOrder()
			);
		}

		// Proceed button
		const proceedBtn = document.getElementById( 'proceed-import-btn' );
		if ( proceedBtn ) {
			proceedBtn.addEventListener( 'click', () =>
				this.proceedWithImport()
			);
		}

		// Remove buttons
		this.sortableList.addEventListener( 'click', ( e ) => {
			const removeBtn = e.target.closest( '.seo-sortable-remove' );
			if ( removeBtn ) {
				const listItem = removeBtn.closest( '.seo-sortable-item' );
				this.removeBlock( listItem );
			}
		} );

		// Device toggle buttons
		this.bindDeviceToggle();
	}

	/**
	 * Bind device toggle button events
	 */
	bindDeviceToggle() {
		const deviceButtons = document.querySelectorAll( '.device-toggle-btn' );
		const previewContainer = document.getElementById( 'block-preview-container' );
		const deviceFrame = previewContainer?.querySelector( '.device-frame' );
		const iframe = document.getElementById( 'block-preview-iframe' );

		if ( ! deviceButtons.length || ! previewContainer || ! deviceFrame ) {
			return;
		}

		deviceButtons.forEach( ( button ) => {
			button.addEventListener( 'click', () => {
				const device = button.dataset.device;

				// Update active state
				deviceButtons.forEach( ( btn ) => {
					btn.classList.remove( 'active' );
					btn.setAttribute( 'aria-selected', 'false' );
				} );
				button.classList.add( 'active' );
				button.setAttribute( 'aria-selected', 'true' );

				// Update device frame
				deviceFrame.className = `device-frame ${ device }-frame`;
				previewContainer.dataset.device = device;

				// Update iframe width based on device
				const widths = {
					mobile: '375px',
					tablet: '768px',
					desktop: '1200px',
				};

				if ( iframe ) {
					iframe.style.width = widths[ device ] || '100%';
				}

				// Announce to screen readers
				const announcement = document.querySelector( '.preview-sr-announcements' );
				if ( announcement ) {
					announcement.textContent = `Preview switched to ${ device } view`;
				}

				console.log( `[Device Toggle] Switched to ${ device } view` );
			} );
		} );
	}

	/**
	 * Remove a block (disable it and remove from DOM)
	 *
	 * @param {HTMLElement} listItem The list item to remove
	 */
	removeBlock( listItem ) {
		// Store the current height and position relative to parent
		const height = listItem.offsetHeight;
		const width = listItem.offsetWidth;
		const offsetTop = listItem.offsetTop;

		// Create a placeholder to maintain space during animation
		const placeholder = document.createElement( 'div' );
		placeholder.style.height = `${height}px`;
		placeholder.style.marginBottom = '8px';
		placeholder.style.transition = 'height 300ms ease, margin 300ms ease';

		// Insert placeholder before the item
		listItem.parentNode.insertBefore( placeholder, listItem );

		// Position the item absolutely relative to the list container
		listItem.style.position = 'absolute';
		listItem.style.width = `${width}px`;
		listItem.style.top = `${offsetTop}px`;
		listItem.style.left = '0';

		// Force a reflow
		// eslint-disable-next-line no-void
		void listItem.offsetHeight;

		// Add removing class to trigger fade out
		listItem.classList.add( 'removing' );

		// Collapse the placeholder to create smooth gap closing
		requestAnimationFrame( () => {
			placeholder.style.height = '0';
			placeholder.style.marginBottom = '0';
		} );

		// After animation completes, ACTUALLY REMOVE THE ELEMENT from DOM
		setTimeout( () => {
			// Remove the list item completely from DOM
			if ( listItem.parentNode ) {
				listItem.parentNode.removeChild( listItem );
			}
			// Remove the placeholder
			if ( placeholder.parentNode ) {
				placeholder.parentNode.removeChild( placeholder );
			}

			// Update preview after block is removed
			this.updatePreview();
		}, 300 );
	}

	/**
	 * Get current block order
	 *
	 * @return {Array} Array of block keys in current order
	 */
	getBlockOrder() {
		return Array.from( this.sortableList.children ).map(
			( li ) => li.dataset.block
		);
	}

	/**
	 * Update the preview iframe with current block order
	 */
	updatePreview() {
		const blockOrder = this.getBlockOrder();

		if ( window.blockPreviewManager ) {
			window.blockPreviewManager.updatePreview( blockOrder );
			console.log(
				'[Block Ordering] Preview updated with blocks:',
				blockOrder
			);
		} else {
			console.warn(
				'[Block Ordering] BlockPreviewManager not available'
			);
		}
	}

	/**
	 * Trigger initial preview render
	 */
	triggerInitialPreview() {
		// Wait for iframe to be ready before triggering initial preview
		setTimeout( () => {
			this.updatePreview();
			console.log( '[Block Ordering] Initial preview rendered' );
		}, 500 );
	}

	/**
	 * Get enabled blocks
	 *
	 * @return {Array} Array of block keys that are enabled
	 */
	getEnabledBlocks() {
		return Array.from( this.sortableList.children )
			.filter( ( li ) => li.dataset.enabled === 'true' )
			.map( ( li ) => li.dataset.block );
	}

	/**
	 * Reset to default block order
	 */
	resetToDefaultOrder() {
		if ( ! this.sortable || this.defaultOrder.length === 0 ) {
			return;
		}

		// Re-enable all blocks
		Array.from( this.sortableList.children ).forEach( ( li ) => {
			li.dataset.enabled = 'true';
		} );

		// Use Sortable's sort method to reorder
		this.sortable.sort( this.defaultOrder );

		// Visual feedback
		this.sortableList.classList.add( 'seo-sortable-reset' );
		setTimeout( () => {
			this.sortableList.classList.remove( 'seo-sortable-reset' );
		}, 300 );

		// Update preview after reset
		this.updatePreview();
	}

	/**
	 * Proceed with import
	 */
	async proceedWithImport() {
		const blockOrder = this.getBlockOrder();
		const enabledBlocks = this.getEnabledBlocks();
		const applyToAll =
			document.getElementById( 'apply-order-to-all' )?.checked ?? true;

		// DEBUG: Log what we're sending
		console.log( '=== BLOCK ORDERING DEBUG ===' );
		console.log( 'Block Order (remaining blocks):', blockOrder );
		console.log( 'Enabled Blocks (only checked):', enabledBlocks );
		console.log( 'Apply to All:', applyToAll );
		console.log( '===========================' );

		// Validate at least one block remains
		if ( blockOrder.length === 0 ) {
			// eslint-disable-next-line no-alert
			alert(
				'You must keep at least one block to generate content.'
			);
			return;
		}

		// Validate at least one block is enabled (this should be same as blockOrder now)
		if ( enabledBlocks.length === 0 ) {
			// eslint-disable-next-line no-alert
			alert(
				'Please enable at least one block to generate content.'
			);
			return;
		}

		// Show loading state
		const proceedBtn = document.getElementById( 'proceed-import-btn' );
		if ( proceedBtn ) {
			proceedBtn.disabled = true;
			proceedBtn.textContent = 'Saving block order...';
		}

		try {
			// Get nonce from localized script data
			const nonce = window.seoImportData?.nonce || '';
			const ajaxUrl = window.seoImportData?.ajaxUrl || '/wp-admin/admin-ajax.php';

			// Save block order and enabled blocks via AJAX
			const formData = new FormData();
			formData.append( 'action', 'seo_save_block_order' );
			formData.append( 'nonce', nonce );
			formData.append( 'block_order', JSON.stringify( blockOrder ) );
			formData.append( 'blocks_to_generate', JSON.stringify( enabledBlocks ) );
			formData.append( 'apply_to_all', applyToAll ? '1' : '0' );

			const response = await fetch( ajaxUrl, {
				method: 'POST',
				body: formData,
			} ).then( ( res ) => res.json() );

			if ( response.success ) {
				// Hide block ordering section
				document.getElementById( 'block-ordering-section' ).style.display =
					'none';

				// Start batch import (trigger the existing import workflow)
				this.startBatchImport();
			} else {
				throw new Error(
					response.data?.message || 'Failed to save block order'
				);
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Block order save error:', error );
			// eslint-disable-next-line no-alert
			alert( `Error saving block order: ${ error.message }` );

			// Reset button state
			if ( proceedBtn ) {
				proceedBtn.disabled = false;
				proceedBtn.textContent = 'Proceed with Import →';
			}
		}
	}

	/**
	 * Start batch import
	 *
	 * This function triggers the existing CSV import workflow
	 */
	startBatchImport() {
		// Show import progress section
		const importProgress = document.getElementById( 'import-progress' );
		if ( importProgress ) {
			importProgress.style.display = 'block';
		}

		// Trigger the existing import process
		// This will be wired up in column-mapping.js
		const event = new CustomEvent( 'seo-start-batch-import' );
		document.dispatchEvent( event );
	}
}

// Initialize on DOM ready
if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', () => {
		// eslint-disable-next-line no-new
		new BlockOrderingManager();

		// Initialize Block Preview Manager
		const previewIframe = document.getElementById( 'block-preview-iframe' );
		if ( previewIframe && window.BlockPreviewManager ) {
			window.blockPreviewManager = new window.BlockPreviewManager(
				previewIframe
			);
			console.log( '[Block Preview] Preview manager initialized' );
		}
	} );
} else {
	// eslint-disable-next-line no-new
	new BlockOrderingManager();

	// Initialize Block Preview Manager
	const previewIframe = document.getElementById( 'block-preview-iframe' );
	if ( previewIframe && window.BlockPreviewManager ) {
		window.blockPreviewManager = new window.BlockPreviewManager(
			previewIframe
		);
		console.log( '[Block Preview] Preview manager initialized' );
	}
}

export default BlockOrderingManager;
