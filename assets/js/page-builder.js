/**
 * Next.js Page Builder
 *
 * Multi-page drag-and-drop block management for the Bravo Jewellers Next.js site.
 * Tabs switch between pages; each page has its own blocks, order, and preview route.
 *
 * @package SEOGenerator
 */

( function () {
	'use strict';

	// ─── Config from wp_localize_script ───────────────────────────
	const config       = window.nextjsPageBuilder || {};
	const AJAX_URL     = config.ajaxUrl || '/wp-admin/admin-ajax.php';
	const NONCE        = config.nonce || '';
	const PREVIEW_BASE = config.previewBase || 'http://localhost:3000';
	const PAGES        = config.pages || {};

	// ─── State ────────────────────────────────────────────────────
	let activePage   = config.activePage || Object.keys( PAGES )[0] || 'homepage';
	let currentOrder = []; // managed per-page
	let sortableInstance = null;

	// ─── DOM References ───────────────────────────────────────────
	const sortableList     = document.getElementById( 'sortable-blocks' );
	const removedSection   = document.getElementById( 'removed-blocks-section' );
	const removedList      = document.getElementById( 'removed-blocks' );
	const previewIframe    = document.getElementById( 'block-preview-iframe' );
	const previewContainer = document.getElementById( 'block-preview-container' );
	const publishBtn       = document.getElementById( 'publish-page-btn' );
	const resetBtn         = document.getElementById( 'reset-order-btn' );
	const saveStatus       = document.getElementById( 'save-status' );
	const cardTitle        = document.getElementById( 'card-title' );

	if ( ! sortableList ) return;

	// ─── Helpers ──────────────────────────────────────────────────

	function escHtml( str ) {
		var div = document.createElement( 'div' );
		div.textContent = str || '';
		return div.innerHTML;
	}

	function showStatus( el, message, type ) {
		if ( ! el ) return;
		el.textContent = message;
		el.className = 'page-builder-save-status';
		if ( type ) el.classList.add( 'page-builder-save-status--' + type );
		if ( message ) {
			clearTimeout( el._timeout );
			el._timeout = setTimeout( function () {
				el.textContent = '';
				el.className = 'page-builder-save-status';
			}, 5000 );
		}
	}

	function getPageData() {
		return PAGES[ activePage ] || {};
	}

	// ─── Render Blocks for Active Page ────────────────────────────

	function renderBlocks( order, allBlocks ) {
		sortableList.innerHTML = '';
		if ( removedList ) removedList.innerHTML = '';
		if ( removedSection ) removedSection.style.display = 'none';

		// Active blocks.
		order.forEach( function ( blockId ) {
			var block = allBlocks[ blockId ];
			if ( ! block ) return;
			sortableList.appendChild( createBlockItem( blockId, block ) );
		} );

		// Removed blocks (in allBlocks but not in order).
		var removedIds = Object.keys( allBlocks ).filter( function ( id ) {
			return order.indexOf( id ) === -1;
		} );
		if ( removedIds.length > 0 && removedList ) {
			removedIds.forEach( function ( blockId ) {
				var block = allBlocks[ blockId ];
				if ( ! block ) return;
				removedList.appendChild( createRemovedItem( blockId, block ) );
			} );
			removedSection.style.display = '';
		}

		// Update tab count badge.
		var countEl = document.getElementById( 'tab-count-' + activePage );
		if ( countEl ) countEl.textContent = order.length;

		initSortable();
	}

	function createBlockItem( blockId, block ) {
		var li = document.createElement( 'li' );
		li.className = 'seo-sortable-item';
		li.dataset.block = blockId;
		li.dataset.enabled = 'true';
		li.innerHTML =
			'<span class="seo-sortable-handle" aria-label="Drag to reorder">⋮⋮</span>' +
			'<div class="seo-sortable-content">' +
				'<strong class="seo-sortable-label">' + escHtml( block.label ) + '</strong>' +
				'<span class="seo-sortable-desc">' + escHtml( block.description ) + '</span>' +
			'</div>' +
			'<button type="button" class="seo-sortable-remove" aria-label="Remove block" title="Click to remove this block">' +
				'<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">' +
					'<path d="M1 1L13 13M13 1L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>' +
				'</svg>' +
			'</button>';
		return li;
	}

	function createRemovedItem( blockId, block ) {
		var li = document.createElement( 'li' );
		li.className = 'seo-sortable-item';
		li.dataset.block = blockId;
		li.innerHTML =
			'<div class="seo-sortable-content">' +
				'<strong class="seo-sortable-label">' + escHtml( block.label ) + '</strong>' +
				'<span class="seo-sortable-desc">' + escHtml( block.description ) + '</span>' +
			'</div>' +
			'<button type="button" class="seo-sortable-add-back" title="Add back" style="' +
				'padding: 4px 12px; background: rgba(202,150,82,0.1); color: #CA9652;' +
				'border: 1px solid rgba(202,150,82,0.3); border-radius: 6px; cursor: pointer;' +
				'font-size: 12px; font-weight: 600;">+ Add</button>';

		li.querySelector( '.seo-sortable-add-back' ).addEventListener( 'click', function () {
			addBlockBack( blockId, li );
		} );

		return li;
	}

	// ─── SortableJS ───────────────────────────────────────────────

	function initSortable() {
		if ( sortableInstance ) {
			sortableInstance.destroy();
		}
		if ( typeof Sortable !== 'undefined' ) {
			sortableInstance = Sortable.create( sortableList, {
				animation: 200,
				easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
				handle: '.seo-sortable-handle',
				ghostClass: 'seo-sortable-ghost',
				dragClass: 'seo-sortable-drag',
				chosenClass: 'seo-sortable-chosen',
				swapThreshold: 0.65,
				onEnd: function () {
					updatePreview();
				},
			} );
		}
	}

	// ─── Block Actions ────────────────────────────────────────────

	sortableList.addEventListener( 'click', function ( e ) {
		var removeBtn = e.target.closest( '.seo-sortable-remove' );
		if ( ! removeBtn ) return;

		var listItem = removeBtn.closest( '.seo-sortable-item' );
		if ( ! listItem ) return;

		var blockId   = listItem.dataset.block;
		var pageData  = getPageData();
		var blockData = pageData.blocks ? pageData.blocks[ blockId ] : null;

		listItem.classList.add( 'removing' );
		setTimeout( function () {
			listItem.remove();

			if ( blockData && removedList ) {
				removedList.appendChild( createRemovedItem( blockId, blockData ) );
				removedSection.style.display = '';
			}

			updatePreview();
			updateTabCount();
		}, 300 );
	} );

	function addBlockBack( blockId, removedLi ) {
		var pageData  = getPageData();
		var blockData = pageData.blocks ? pageData.blocks[ blockId ] : null;
		if ( ! blockData ) return;

		removedLi.remove();
		if ( removedList && removedList.children.length === 0 ) {
			removedSection.style.display = 'none';
		}

		sortableList.appendChild( createBlockItem( blockId, blockData ) );
		updatePreview();
		updateTabCount();
	}

	function updateTabCount() {
		var count = getBlockOrder().length;
		var countEl = document.getElementById( 'tab-count-' + activePage );
		if ( countEl ) countEl.textContent = count;
	}

	// ─── Get Block Order from DOM ─────────────────────────────────

	function getBlockOrder() {
		return Array.from( sortableList.children )
			.filter( function ( li ) { return ! li.classList.contains( 'removing' ); } )
			.map( function ( li ) { return li.dataset.block; } );
	}

	// ─── Preview ──────────────────────────────────────────────────

	function updatePreview() {
		if ( ! previewIframe ) return;
		var pageData    = getPageData();
		var route       = pageData.previewRoute || '/preview';
		var blocksParam = getBlockOrder().join( ',' );
		previewIframe.src = PREVIEW_BASE + route + '?blocks=' + blocksParam;
	}

	// Device toggle.
	document.querySelectorAll( '.device-toggle-btn' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var device = btn.dataset.device;

			document.querySelectorAll( '.device-toggle-btn' ).forEach( function ( b ) {
				b.classList.remove( 'active' );
				b.setAttribute( 'aria-selected', 'false' );
			} );
			btn.classList.add( 'active' );
			btn.setAttribute( 'aria-selected', 'true' );

			var frameEl = previewContainer.querySelector( '.device-frame' );
			if ( frameEl ) {
				frameEl.className = 'device-frame ' + device + '-frame';
			}
			previewContainer.dataset.device = device;

			if ( device === 'desktop' ) {
				setTimeout( updateDesktopScale, 50 );
			} else {
				// Reset any scaling.
				previewContainer.style.minHeight = '';
			}
		} );
	} );

	// ─── Desktop Scale ────────────────────────────────────────────

	function updateDesktopScale() {
		var frameEl = previewContainer ? previewContainer.querySelector( '.desktop-frame .device-screen' ) : null;
		if ( ! frameEl ) return;

		var panelWidth = previewContainer.offsetWidth - 40;
		var scale = Math.min( panelWidth / 1440, 1 );
		frameEl.style.transform = 'scale(' + scale + ')';

		var iframeHeight = frameEl.querySelector( 'iframe' )?.offsetHeight || 900;
		previewContainer.style.minHeight = ( iframeHeight * scale + 80 ) + 'px';
	}

	window.addEventListener( 'resize', function () {
		if ( previewContainer && previewContainer.dataset.device === 'desktop' ) {
			updateDesktopScale();
		}
	} );

	// ─── Tab Switching ────────────────────────────────────────────

	document.querySelectorAll( '.page-builder-tab' ).forEach( function ( tab ) {
		tab.addEventListener( 'click', function () {
			var pageSlug = tab.dataset.page;
			if ( pageSlug === activePage ) return;

			// Update tab active state.
			document.querySelectorAll( '.page-builder-tab' ).forEach( function ( t ) {
				t.classList.remove( 'active' );
				t.setAttribute( 'aria-selected', 'false' );
			} );
			tab.classList.add( 'active' );
			tab.setAttribute( 'aria-selected', 'true' );

			// Switch page.
			activePage = pageSlug;
			var pageData = getPageData();

			// Update title.
			if ( cardTitle ) {
				cardTitle.textContent = 'Customize Block Order — ' + ( pageData.label || pageSlug );
			}

			// Render blocks for this page.
			renderBlocks( pageData.currentOrder || pageData.defaultOrder, pageData.blocks || {} );

			// Update preview.
			updatePreview();

			// Clear status.
			showStatus( saveStatus, '', '' );
		} );
	} );

	// ─── Publish ──────────────────────────────────────────────────

	publishBtn.addEventListener( 'click', async function () {
		var blockOrder = getBlockOrder();

		if ( blockOrder.length === 0 ) {
			alert( 'Add at least one block before publishing.' );
			return;
		}

		var pageData = getPageData();
		publishBtn.disabled = true;
		publishBtn.textContent = 'Publishing ' + ( pageData.label || '' ) + '...';
		showStatus( saveStatus, '', '' );

		try {
			var formData = new FormData();
			formData.append( 'action', 'nextjs_publish_page' );
			formData.append( 'nonce', NONCE );
			formData.append( 'page_slug', activePage );
			formData.append( 'block_order', JSON.stringify( blockOrder ) );

			var res  = await fetch( AJAX_URL, { method: 'POST', body: formData } );
			var json = await res.json();

			if ( json.success ) {
				showStatus( saveStatus, '✓ ' + json.data.message, 'success' );
				// Update the saved order in our local state.
				if ( PAGES[ activePage ] ) {
					PAGES[ activePage ].currentOrder = blockOrder;
				}
			} else {
				showStatus( saveStatus, '✕ ' + ( json.data?.message || 'Publish failed.' ), 'error' );
			}
		} catch ( err ) {
			showStatus( saveStatus, '✕ Network error: ' + err.message, 'error' );
		}

		publishBtn.disabled = false;
		publishBtn.textContent = 'Publish to Next.js →';
	} );

	// ─── Reset ────────────────────────────────────────────────────

	resetBtn.addEventListener( 'click', function () {
		var pageData = getPageData();
		renderBlocks( pageData.defaultOrder || [], pageData.blocks || {} );

		sortableList.classList.add( 'seo-sortable-reset' );
		setTimeout( function () {
			sortableList.classList.remove( 'seo-sortable-reset' );
		}, 400 );

		updatePreview();
		showStatus( saveStatus, '↩ Reset to default order', 'info' );
	} );

	// ─── Save Settings ────────────────────────────────────────────

	var saveSettingsBtn = document.getElementById( 'save-settings-btn' );
	var settingsStatus  = document.getElementById( 'settings-status' );

	if ( saveSettingsBtn ) {
		saveSettingsBtn.addEventListener( 'click', async function () {
			var projectPath = document.getElementById( 'nextjs-project-path' )?.value || '';
			var previewUrl  = document.getElementById( 'nextjs-preview-url' )?.value || '';

			var formData = new FormData();
			formData.append( 'action', 'nextjs_save_settings' );
			formData.append( 'nonce', NONCE );
			formData.append( 'project_path', projectPath );
			formData.append( 'preview_url', previewUrl );

			try {
				var res  = await fetch( AJAX_URL, { method: 'POST', body: formData } );
				var json = await res.json();
				if ( json.success ) {
					showStatus( settingsStatus, '✓ Settings saved', 'success' );
				} else {
					showStatus( settingsStatus, '✕ Failed to save', 'error' );
				}
			} catch ( err ) {
				showStatus( settingsStatus, '✕ ' + err.message, 'error' );
			}
		} );
	}

	// ─── Initial Load ─────────────────────────────────────────────
	setTimeout( updatePreview, 300 );

} )();
