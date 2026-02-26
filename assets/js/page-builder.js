/**
 * Next.js SEO Page Builder
 *
 * Tabbed interface — one tab per page template (Homepage, About Us).
 * Shared block catalog — click "+ Add Block" to pick from any group
 * (Homepage, About, Diamonds, Custom Design, Engagement, Contacts).
 * Publishes to a new URL slug — never overwrites existing pages.
 *
 * @package SEOGenerator
 */

( function () {
	'use strict';

	// ─── Config ───────────────────────────────────────────────────
	const cfg           = window.nextjsPageBuilder || {};
	const AJAX_URL      = cfg.ajaxUrl || '/wp-admin/admin-ajax.php';
	const NONCE         = cfg.nonce || '';
	const PREVIEW_BASE  = cfg.previewBase || 'http://localhost:3000';
	const BLOCK_GROUPS  = cfg.blockGroups || {};
	const PAGES         = cfg.pages || {};
	const RESERVED      = cfg.reservedSlugs || [];
	let   DYNAMIC_SETUP = cfg.dynamicSetupDone || false;

	// ─── State ────────────────────────────────────────────────────
	let activePage    = Object.keys( PAGES )[0] || 'homepage';
	let sortableInst  = null;
	let pickerOpen    = false;

	// ─── DOM ──────────────────────────────────────────────────────
	const sortableList     = document.getElementById( 'sortable-blocks' );
	const previewIframe    = document.getElementById( 'block-preview-iframe' );
	const previewContainer = document.getElementById( 'block-preview-container' );
	const cardTitle        = document.getElementById( 'card-title' );
	const outputSlugInput  = document.getElementById( 'output-slug' );
	const saveStatus       = document.getElementById( 'save-status' );
	const blockPicker      = document.getElementById( 'block-picker' );
	const blockCatalog     = document.getElementById( 'block-catalog' );
	const groupTabs        = document.getElementById( 'block-group-tabs' );
	const addBlockToggle   = document.getElementById( 'add-block-toggle' );

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
	 * Get a flat map of ALL blocks across all groups.
	 */
	function getAllBlocks() {
		var all = {};
		Object.values( BLOCK_GROUPS ).forEach( function ( g ) {
			Object.assign( all, g.blocks || {} );
		} );
		return all;
	}

	// ─── Block Catalog (Shared Picker) ────────────────────────────

	function renderBlockCatalog( filterGroup ) {
		blockCatalog.innerHTML = '';

		Object.keys( BLOCK_GROUPS ).forEach( function ( groupId ) {
			var group = BLOCK_GROUPS[ groupId ];
			if ( filterGroup && filterGroup !== 'all' && filterGroup !== groupId ) return;

			var groupEl = document.createElement( 'div' );
			groupEl.className = 'block-catalog-group';
			groupEl.innerHTML = '<h5 class="block-catalog-group-label">' + esc( group.label ) + '</h5>';

			var grid = document.createElement( 'div' );
			grid.className = 'block-catalog-grid';

			Object.keys( group.blocks ).forEach( function ( blockId ) {
				var block = group.blocks[ blockId ];
				var btn = document.createElement( 'button' );
				btn.type = 'button';
				btn.className = 'block-catalog-item';
				btn.dataset.blockId = blockId;
				btn.innerHTML =
					'<strong>' + esc( block.label ) + '</strong>' +
					'<span>' + esc( block.description ) + '</span>';
				btn.addEventListener( 'click', function () {
					addBlockToPage( blockId, block );
					updatePreview();
				} );
				grid.appendChild( btn );
			} );

			groupEl.appendChild( grid );
			blockCatalog.appendChild( groupEl );
		} );
	}

	function renderGroupTabs() {
		Object.keys( BLOCK_GROUPS ).forEach( function ( groupId ) {
			var group = BLOCK_GROUPS[ groupId ];
			var btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'block-group-tab';
			btn.dataset.group = groupId;
			btn.textContent = group.label;
			groupTabs.appendChild( btn );
		} );

		groupTabs.addEventListener( 'click', function ( e ) {
			var tab = e.target.closest( '.block-group-tab' );
			if ( ! tab ) return;
			groupTabs.querySelectorAll( '.block-group-tab' ).forEach( function ( t ) { t.classList.remove( 'active' ); } );
			tab.classList.add( 'active' );
			renderBlockCatalog( tab.dataset.group );
		} );
	}

	// Toggle picker visibility.
	if ( addBlockToggle ) {
		addBlockToggle.addEventListener( 'click', function () {
			pickerOpen = ! pickerOpen;
			blockPicker.style.display = pickerOpen ? '' : 'none';
			addBlockToggle.textContent = pickerOpen ? '− Close' : '+ Add Block';
		} );
	}

	// ─── Render Blocks for Active Page ────────────────────────────

	function renderBlocks( order ) {
		var allBlocks = getAllBlocks();
		sortableList.innerHTML = '';

		order.forEach( function ( blockId ) {
			var block = allBlocks[ blockId ];
			if ( ! block ) return;
			addBlockToPage( blockId, block, true );
		} );

		initSortable();
		updateTabBadges();
	}

	function addBlockToPage( blockId, block, skipAnimation ) {
		var li = document.createElement( 'li' );
		li.className = 'seo-sortable-item';
		li.dataset.block = blockId;
		li.innerHTML =
			'<span class="seo-sortable-handle" aria-label="Drag to reorder">⠿</span>' +
			'<div class="seo-sortable-content">' +
				'<strong class="seo-sortable-label">' + esc( block.label ) + '</strong>' +
				'<span class="seo-sortable-desc">' + esc( block.description ) + '</span>' +
			'</div>' +
			'<button type="button" class="seo-sortable-remove" aria-label="Remove block">' +
				'<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M1 1L13 13M13 1L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>' +
			'</button>';

		if ( ! skipAnimation ) {
			li.style.opacity = '0';
			li.style.transform = 'translateY(-10px)';
		}

		sortableList.appendChild( li );

		if ( ! skipAnimation ) {
			requestAnimationFrame( function () {
				li.style.transition = 'opacity 0.2s, transform 0.2s';
				li.style.opacity = '1';
				li.style.transform = '';
			} );
		}

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

		// Render blocks.
		renderBlocks( pd.currentOrder || [] );

		// Close picker on tab switch.
		if ( pickerOpen ) {
			pickerOpen = false;
			blockPicker.style.display = 'none';
			if ( addBlockToggle ) addBlockToggle.textContent = '+ Add Block';
		}

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
				renderBlocks( newOrder );
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
				if ( PAGES[ activePage ] ) {
					PAGES[ activePage ].currentOrder = order;
					PAGES[ activePage ].outputSlug = slug;
				}
				if ( json.data.build_status === 'not_needed' ) {
					showStatus( saveStatus, '✓ Published to /' + slug + ' — live now!', 'success' );
				} else if ( json.data.build_status === 'started' ) {
					showStatus( saveStatus, '✓ Published! Building & restarting — may take a few minutes.', 'success' );
				} else {
					showStatus( saveStatus, '✓ Published! Run npm run build on the server to go live.', 'success' );
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

	// ─── Dynamic Route Setup ──────────────────────────────────────

	var setupDynamicBtn = document.getElementById( 'setup-dynamic-btn' );
	var setupStatus     = document.getElementById( 'setup-dynamic-status' );

	if ( setupDynamicBtn ) {
		setupDynamicBtn.addEventListener( 'click', async function () {
			setupDynamicBtn.disabled = true;
			setupDynamicBtn.textContent = 'Setting up...';

			var fd = new FormData();
			fd.append( 'action', 'nextjs_setup_dynamic' );
			fd.append( 'nonce', NONCE );

			try {
				var res = await fetch( AJAX_URL, { method: 'POST', body: fd } );
				var json = await res.json();
				if ( json.success ) {
					DYNAMIC_SETUP = true;
					showStatus( setupStatus, '✓ ' + json.data.message, 'success' );
					setupDynamicBtn.textContent = 'Re-generate Files';
				} else {
					showStatus( setupStatus, '✕ ' + ( json.data?.message || 'Setup failed' ), 'error' );
					setupDynamicBtn.textContent = 'Setup Dynamic Route';
				}
			} catch ( e ) {
				showStatus( setupStatus, '✕ ' + e.message, 'error' );
				setupDynamicBtn.textContent = 'Setup Dynamic Route';
			}

			setupDynamicBtn.disabled = false;
		} );
	}

	// ─── Init ─────────────────────────────────────────────────────
	renderGroupTabs();
	renderBlockCatalog( 'all' );
	initSortable();
	updatePreview();

} )();
