/**
 * Next.js SEO Page Builder
 *
 * Tabbed interface — one tab per page template (Homepage, About Us).
 * Each tab has its own block list, sortable order, output slug, and preview.
 * "Add Block" picker lets you add blocks from ANY page template.
 * Publishes to a new URL slug — never overwrites existing pages.
 *
 * @package SEOGenerator
 */

( function () {
	'use strict';

	// ─── Config ───────────────────────────────────────────────────
	const cfg              = window.nextjsPageBuilder || {};
	const AJAX_URL         = cfg.ajaxUrl || '/wp-admin/admin-ajax.php';
	const NONCE            = cfg.nonce || '';
	const PREVIEW_BASE     = cfg.previewBase || 'http://localhost:3000';
	const PAGES            = cfg.pages || {};
	const ALL_BLOCKS       = cfg.allBlocksGrouped || {};
	const RESERVED         = cfg.reservedSlugs || [];

	// ─── State ────────────────────────────────────────────────────
	let activePage    = Object.keys( PAGES )[0] || 'homepage';
	let sortableInst  = null;

	// ─── DOM ──────────────────────────────────────────────────────
	const sortableList     = document.getElementById( 'sortable-blocks' );
	const previewIframe    = document.getElementById( 'block-preview-iframe' );
	const previewContainer = document.getElementById( 'block-preview-container' );
	const cardTitle        = document.getElementById( 'card-title' );
	const outputSlugInput  = document.getElementById( 'output-slug' );
	const saveStatus       = document.getElementById( 'save-status' );

	// Picker DOM
	const pickerOverlay    = document.getElementById( 'block-picker-overlay' );
	const pickerBody       = document.getElementById( 'block-picker-body' );
	const pickerSearch     = document.getElementById( 'block-picker-search' );
	const pickerCloseBtn   = document.getElementById( 'block-picker-close' );
	const addBlockBtn      = document.getElementById( 'add-block-btn' );

	if ( ! sortableList ) return;

	// ─── Helpers ──────────────────────────────────────────────────

	function esc( str ) {
		var d = document.createElement( 'div' );
		d.textContent = str || '';
		return d.innerHTML;
	}

	function showStatus( el, msg, type ) {
		if ( ! el ) return;
		el.textContent = msg;
		el.className = 'page-builder-save-status' + ( type ? ' page-builder-save-status--' + type : '' );
		clearTimeout( el._t );
		if ( msg ) el._t = setTimeout( function () { el.textContent = ''; el.className = 'page-builder-save-status'; }, 5000 );
	}

	function getBlockOrder() {
		return Array.from( sortableList.children ).map( function ( li ) { return li.dataset.block; } );
	}

	function pageData() {
		return PAGES[ activePage ] || {};
	}

	/**
	 * Resolve a block definition from the global pool.
	 * Checks the active page's blocks first, then all pages.
	 */
	function resolveBlock( blockId ) {
		// Check active page first.
		var pd = pageData();
		if ( pd.blocks && pd.blocks[ blockId ] ) return pd.blocks[ blockId ];

		// Check all pages.
		for ( var pageSlug in ALL_BLOCKS ) {
			var group = ALL_BLOCKS[ pageSlug ];
			if ( group.blocks && group.blocks[ blockId ] ) return group.blocks[ blockId ];
		}
		return null;
	}

	// ─── Render Blocks for Active Page ────────────────────────────

	function renderBlocks( order, allBlocks ) {
		sortableList.innerHTML = '';

		order.forEach( function ( blockId ) {
			// Resolve from active page blocks OR global pool.
			var block = ( allBlocks && allBlocks[ blockId ] ) || resolveBlock( blockId );
			if ( ! block ) return;

			var li = document.createElement( 'li' );
			li.className = 'seo-sortable-item';
			li.dataset.block = blockId;
			li.innerHTML =
				'<span class="seo-sortable-handle" aria-label="Drag to reorder">⋮⋮</span>' +
				'<div class="seo-sortable-content">' +
					'<strong class="seo-sortable-label">' + esc( block.label ) + '</strong>' +
					'<span class="seo-sortable-desc">' + esc( block.description ) + '</span>' +
				'</div>' +
				'<button type="button" class="seo-sortable-remove" aria-label="Remove block">' +
					'<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M1 1L13 13M13 1L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>' +
				'</button>';

			sortableList.appendChild( li );
		} );

		initSortable();
		updateTabBadges();
	}

	// ─── Sortable ─────────────────────────────────────────────────

	function initSortable() {
		if ( sortableInst ) sortableInst.destroy();
		if ( typeof Sortable !== 'undefined' ) {
			sortableInst = Sortable.create( sortableList, {
				animation: 200,
				handle: '.seo-sortable-handle',
				ghostClass: 'seo-sortable-ghost',
				onEnd: function () {
					updatePreview();
					updateTabBadges();
				},
			} );
		}
	}

	// Remove block handler.
	sortableList.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '.seo-sortable-remove' );
		if ( ! btn ) return;
		var li = btn.closest( '.seo-sortable-item' );
		if ( ! li ) return;

		li.style.transition = 'opacity 0.2s, transform 0.2s';
		li.style.opacity = '0';
		li.style.transform = 'translateX(20px)';
		setTimeout( function () {
			li.remove();
			updatePreview();
			updateTabBadges();
		}, 200 );
	} );

	// ─── Block Picker ─────────────────────────────────────────────

	function openPicker() {
		if ( ! pickerOverlay ) return;
		buildPickerContent( '' );
		pickerOverlay.style.display = 'flex';
		if ( pickerSearch ) {
			pickerSearch.value = '';
			pickerSearch.focus();
		}
	}

	function closePicker() {
		if ( pickerOverlay ) pickerOverlay.style.display = 'none';
	}

	function buildPickerContent( filter ) {
		if ( ! pickerBody ) return;
		pickerBody.innerHTML = '';

		var currentIds = getBlockOrder();
		var filterLower = ( filter || '' ).toLowerCase();
		var hasResults = false;

		for ( var pageSlug in ALL_BLOCKS ) {
			var group = ALL_BLOCKS[ pageSlug ];
			var blocks = group.blocks || {};
			var groupHtml = '';
			var visibleCount = 0;

			for ( var blockId in blocks ) {
				var block = blocks[ blockId ];
				var alreadyAdded = currentIds.indexOf( blockId ) !== -1;

				// Filter by search.
				if ( filterLower ) {
					var matchLabel = ( block.label || '' ).toLowerCase().indexOf( filterLower ) !== -1;
					var matchDesc  = ( block.description || '' ).toLowerCase().indexOf( filterLower ) !== -1;
					if ( ! matchLabel && ! matchDesc ) continue;
				}

				visibleCount++;
				hasResults = true;

				groupHtml +=
					'<div class="picker-block-item' + ( alreadyAdded ? ' picker-block-added' : '' ) + '" data-block-id="' + esc( blockId ) + '">' +
						'<div class="picker-block-info">' +
							'<strong>' + esc( block.label ) + '</strong>' +
							'<span class="picker-block-desc">' + esc( block.description ) + '</span>' +
						'</div>' +
						( alreadyAdded
							? '<span class="picker-block-badge">Added</span>'
							: '<button type="button" class="picker-add-btn seo-btn-secondary">+ Add</button>'
						) +
					'</div>';
			}

			if ( visibleCount > 0 ) {
				var section = document.createElement( 'div' );
				section.className = 'picker-group';
				section.innerHTML =
					'<h4 class="picker-group-label">' + esc( group.label ) + ' <span class="picker-group-count">' + visibleCount + '</span></h4>' +
					groupHtml;
				pickerBody.appendChild( section );
			}
		}

		if ( ! hasResults ) {
			pickerBody.innerHTML = '<p class="picker-empty">No blocks found.</p>';
		}
	}

	// Picker event: add block.
	if ( pickerBody ) {
		pickerBody.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '.picker-add-btn' );
			if ( ! btn ) return;

			var item = btn.closest( '.picker-block-item' );
			if ( ! item ) return;

			var blockId = item.dataset.blockId;
			if ( ! blockId ) return;

			// Add to sortable list.
			addBlockToList( blockId );

			// Update this item in the picker to show "Added".
			item.classList.add( 'picker-block-added' );
			btn.replaceWith( createBadge( 'Added' ) );
		} );
	}

	function createBadge( text ) {
		var span = document.createElement( 'span' );
		span.className = 'picker-block-badge';
		span.textContent = text;
		return span;
	}

	function addBlockToList( blockId ) {
		var block = resolveBlock( blockId );
		if ( ! block ) return;

		var li = document.createElement( 'li' );
		li.className = 'seo-sortable-item seo-sortable-item--new';
		li.dataset.block = blockId;
		li.innerHTML =
			'<span class="seo-sortable-handle" aria-label="Drag to reorder">⋮⋮</span>' +
			'<div class="seo-sortable-content">' +
				'<strong class="seo-sortable-label">' + esc( block.label ) + '</strong>' +
				'<span class="seo-sortable-desc">' + esc( block.description ) + '</span>' +
			'</div>' +
			'<button type="button" class="seo-sortable-remove" aria-label="Remove block">' +
				'<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M1 1L13 13M13 1L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>' +
			'</button>';

		sortableList.appendChild( li );

		// Brief highlight animation.
		requestAnimationFrame( function () {
			li.classList.remove( 'seo-sortable-item--new' );
		} );

		updatePreview();
		updateTabBadges();
	}

	// Picker search.
	if ( pickerSearch ) {
		pickerSearch.addEventListener( 'input', function () {
			buildPickerContent( pickerSearch.value );
		} );
	}

	// Picker open/close.
	if ( addBlockBtn ) {
		addBlockBtn.addEventListener( 'click', openPicker );
	}
	if ( pickerCloseBtn ) {
		pickerCloseBtn.addEventListener( 'click', closePicker );
	}
	if ( pickerOverlay ) {
		pickerOverlay.addEventListener( 'click', function ( e ) {
			if ( e.target === pickerOverlay ) closePicker();
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && pickerOverlay.style.display !== 'none' ) closePicker();
		} );
	}

	// ─── Preview ──────────────────────────────────────────────────

	function updatePreview() {
		if ( ! previewIframe ) return;
		var blocks = getBlockOrder();
		var pd = pageData();
		var route = pd.previewRoute || '/preview';

		if ( blocks.length === 0 ) {
			previewIframe.src = 'about:blank';
			return;
		}

		previewIframe.src = PREVIEW_BASE + route + '?blocks=' + blocks.join( ',' );
	}

	// Device toggle.
	document.querySelectorAll( '.device-toggle-btn' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			document.querySelectorAll( '.device-toggle-btn' ).forEach( function ( b ) {
				b.classList.remove( 'active' );
				b.setAttribute( 'aria-selected', 'false' );
			} );
			btn.classList.add( 'active' );
			btn.setAttribute( 'aria-selected', 'true' );

			var device = btn.dataset.device;
			var frame = previewContainer.querySelector( '.device-frame' );
			if ( frame ) frame.className = 'device-frame ' + device + '-frame';
			previewContainer.dataset.device = device;

			if ( device === 'desktop' ) setTimeout( updateDesktopScale, 50 );
			else previewContainer.style.minHeight = '';
		} );
	} );

	function updateDesktopScale() {
		var screen = previewContainer ? previewContainer.querySelector( '.desktop-frame .device-screen' ) : null;
		if ( ! screen ) return;
		var pw = previewContainer.offsetWidth - 40;
		var scale = Math.min( pw / 1440, 1 );
		screen.style.transform = 'scale(' + scale + ')';
		previewContainer.style.minHeight = ( 900 * scale + 80 ) + 'px';
	}

	window.addEventListener( 'resize', function () {
		if ( previewContainer && previewContainer.dataset.device === 'desktop' ) updateDesktopScale();
	} );

	// ─── Tab Switching ────────────────────────────────────────────

	function switchToPage( slug ) {
		activePage = slug;
		var pd = pageData();

		// Update tab active state.
		document.querySelectorAll( '.page-builder-tab' ).forEach( function ( tab ) {
			var isActive = tab.dataset.page === slug;
			tab.classList.toggle( 'active', isActive );
			tab.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
		} );

		// Update card title.
		if ( cardTitle ) {
			cardTitle.textContent = 'Customize Block Order — ' + ( pd.label || slug );
		}

		// Update output slug input.
		if ( outputSlugInput ) {
			outputSlugInput.value = pd.outputSlug || '';
		}

		// Render blocks — pass page blocks + resolve cross-page from global pool.
		renderBlocks( pd.currentOrder || [], pd.blocks || {} );

		// Update preview.
		updatePreview();
	}

	document.querySelectorAll( '.page-builder-tab' ).forEach( function ( tab ) {
		tab.addEventListener( 'click', function () {
			switchToPage( tab.dataset.page );
		} );
	} );

	// ─── Tab Badges ───────────────────────────────────────────────

	function updateTabBadges() {
		// Update active tab's badge with current sortable count.
		document.querySelectorAll( '.page-builder-tab' ).forEach( function ( tab ) {
			if ( tab.dataset.page === activePage ) {
				var badge = tab.querySelector( '.tab-block-count' );
				if ( badge ) badge.textContent = sortableList.children.length;
			}
		} );
	}

	// ─── Save Order ───────────────────────────────────────────────

	document.getElementById( 'save-order-btn' ).addEventListener( 'click', async function () {
		var order = getBlockOrder();

		var fd = new FormData();
		fd.append( 'action', 'nextjs_save_block_order' );
		fd.append( 'nonce', NONCE );
		fd.append( 'page_slug', activePage );
		fd.append( 'block_order', JSON.stringify( order ) );

		try {
			var res = await fetch( AJAX_URL, { method: 'POST', body: fd } );
			var json = await res.json();
			if ( json.success ) {
				showStatus( saveStatus, '✓ Order saved', 'success' );
				// Update local state.
				if ( PAGES[ activePage ] ) PAGES[ activePage ].currentOrder = order;
			} else {
				showStatus( saveStatus, '✕ ' + ( json.data?.message || 'Failed' ), 'error' );
			}
		} catch ( e ) {
			showStatus( saveStatus, '✕ ' + e.message, 'error' );
		}
	} );

	// ─── Reset Order ──────────────────────────────────────────────

	document.getElementById( 'reset-order-btn' ).addEventListener( 'click', async function () {
		if ( ! confirm( 'Reset block order to defaults for this page?' ) ) return;

		var fd = new FormData();
		fd.append( 'action', 'nextjs_reset_order' );
		fd.append( 'nonce', NONCE );
		fd.append( 'page_slug', activePage );

		try {
			var res = await fetch( AJAX_URL, { method: 'POST', body: fd } );
			var json = await res.json();
			if ( json.success ) {
				showStatus( saveStatus, '✓ Reset to defaults', 'success' );
				var newOrder = json.data.blockOrder || [];
				if ( PAGES[ activePage ] ) PAGES[ activePage ].currentOrder = newOrder;
				renderBlocks( newOrder, pageData().blocks || {} );
				updatePreview();
			} else {
				showStatus( saveStatus, '✕ ' + ( json.data?.message || 'Failed' ), 'error' );
			}
		} catch ( e ) {
			showStatus( saveStatus, '✕ ' + e.message, 'error' );
		}
	} );

	// ─── Publish ──────────────────────────────────────────────────

	document.getElementById( 'publish-btn' ).addEventListener( 'click', async function () {
		var slug = ( outputSlugInput ? outputSlugInput.value.trim() : '' );

		if ( ! slug ) {
			alert( 'Enter an output URL slug (e.g. "san-diego-jewelry-store").' );
			outputSlugInput.focus();
			return;
		}

		if ( RESERVED.indexOf( slug ) !== -1 ) {
			alert( 'The slug "/' + slug + '" is reserved. Choose a different one.' );
			outputSlugInput.focus();
			return;
		}

		var order = getBlockOrder();
		if ( order.length === 0 ) {
			alert( 'Add at least one block before publishing.' );
			return;
		}

		var btn = document.getElementById( 'publish-btn' );
		btn.disabled = true;
		btn.textContent = 'Publishing...';

		var fd = new FormData();
		fd.append( 'action', 'nextjs_publish_page' );
		fd.append( 'nonce', NONCE );
		fd.append( 'page_slug', activePage );
		fd.append( 'output_slug', slug );
		fd.append( 'block_order', JSON.stringify( order ) );

		try {
			var res = await fetch( AJAX_URL, { method: 'POST', body: fd } );
			var json = await res.json();
			if ( json.success ) {
				showStatus( saveStatus, '✓ ' + json.data.message, 'success' );
				// Update local state.
				if ( PAGES[ activePage ] ) {
					PAGES[ activePage ].currentOrder = order;
					PAGES[ activePage ].outputSlug = slug;
				}
			} else {
				showStatus( saveStatus, '✕ ' + ( json.data?.message || 'Publish failed' ), 'error' );
			}
		} catch ( e ) {
			showStatus( saveStatus, '✕ ' + e.message, 'error' );
		}

		btn.disabled = false;
		btn.textContent = 'Publish to Next.js →';
	} );

	// ─── Settings ─────────────────────────────────────────────────

	var settingsBtn    = document.getElementById( 'save-settings-btn' );
	var settingsStatus = document.getElementById( 'settings-status' );

	if ( settingsBtn ) {
		settingsBtn.addEventListener( 'click', async function () {
			var fd = new FormData();
			fd.append( 'action', 'nextjs_save_settings' );
			fd.append( 'nonce', NONCE );
			fd.append( 'project_path', document.getElementById( 'nextjs-project-path' ).value );
			fd.append( 'preview_url', document.getElementById( 'nextjs-preview-url' ).value );

			try {
				var res = await fetch( AJAX_URL, { method: 'POST', body: fd } );
				var json = await res.json();
				showStatus( settingsStatus, json.success ? '✓ Saved' : '✕ Failed', json.success ? 'success' : 'error' );
			} catch ( e ) {
				showStatus( settingsStatus, '✕ ' + e.message, 'error' );
			}
		} );
	}

	// ─── Init ─────────────────────────────────────────────────────
	initSortable();
	updatePreview();

} )();
