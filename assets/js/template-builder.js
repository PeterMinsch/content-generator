/**
 * Template Builder
 *
 * 3-column UI: Block Library | Template Canvas | Multi-device Preview.
 * IIFE pattern. Uses SortableJS (loaded via CDN).
 *
 * @package SEOGenerator
 */

( function () {
	'use strict';

	// ─── Config ───────────────────────────────────────────────────
	const cfg          = window.templateBuilderData || {};
	const AJAX_URL     = cfg.ajaxUrl || '/wp-admin/admin-ajax.php';
	const NONCE        = cfg.nonce || '';
	const PREVIEW_BASE = cfg.previewBase || 'http://localhost:3000';
	const BLOCK_GROUPS = cfg.blockGroups || {};
	const RESERVED     = cfg.reservedSlugs || [];
	const CATEGORIES   = cfg.categories || [];
	let   DYNAMIC_SETUP = cfg.dynamicSetupDone || false;

	// ─── State ────────────────────────────────────────────────────
	let templates             = cfg.templates || {};
	let activeTemplateId      = cfg.activeTemplateId || null;
	let previewMode           = 'template'; // or 'block'
	let selectedBlockForPreview = null;
	let isDirty               = false;
	let searchQuery           = '';
	let activeGroupFilter     = 'all';
	let sortableInst          = null;

	// ─── DOM ──────────────────────────────────────────────────────
	const app              = document.getElementById( 'template-builder-app' );
	if ( ! app ) return;

	const tabsContainer    = document.getElementById( 'tb-template-tabs' );
	const sortableList     = document.getElementById( 'tb-sortable-blocks' );
	const canvasEmpty      = document.getElementById( 'tb-canvas-empty' );
	const canvasTitle      = document.getElementById( 'tb-canvas-title' );
	const dirtyBadge       = document.getElementById( 'tb-dirty-badge' );
	const previewIframe    = document.getElementById( 'tb-preview-iframe' );
	const previewContainer = document.getElementById( 'tb-preview-container' );
	const saveStatus       = document.getElementById( 'tb-save-status' );
	const searchInput      = document.getElementById( 'tb-search-blocks' );
	const filterPills      = document.getElementById( 'tb-filter-pills' );
	const blockListEl      = document.getElementById( 'tb-block-list' );
	const previewToggle    = document.getElementById( 'tb-preview-mode-toggle' );

	// Meta fields.
	const nameInput     = document.getElementById( 'tb-template-name' );
	const categorySelect = document.getElementById( 'tb-template-category' );
	const statusSelect  = document.getElementById( 'tb-template-status' );
	const descInput     = document.getElementById( 'tb-template-desc' );
	const outputSlug    = document.getElementById( 'tb-output-slug' );

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

	function setDirty( val ) {
		isDirty = val;
		if ( dirtyBadge ) dirtyBadge.style.display = val ? '' : 'none';
	}

	function getBlockOrder() {
		return Array.from( sortableList.children ).map( function ( li ) { return li.dataset.block; } );
	}

	function activeTemplate() {
		return templates[ activeTemplateId ] || null;
	}

	function getAllBlocks() {
		var all = {};
		Object.values( BLOCK_GROUPS ).forEach( function ( g ) {
			Object.assign( all, g.blocks || {} );
		} );
		return all;
	}

	function findBlockGroup( blockId ) {
		for ( var gid in BLOCK_GROUPS ) {
			if ( BLOCK_GROUPS[ gid ].blocks[ blockId ] ) return gid;
		}
		return '';
	}

	async function ajax( action, data ) {
		var fd = new FormData();
		fd.append( 'action', action );
		fd.append( 'nonce', NONCE );
		if ( data ) {
			Object.keys( data ).forEach( function ( k ) { fd.append( k, data[ k ] ); } );
		}
		var res = await fetch( AJAX_URL, { method: 'POST', body: fd } );
		return res.json();
	}

	// ─── Category Select Population ──────────────────────────────

	function populateCategorySelect() {
		if ( ! categorySelect ) return;
		categorySelect.innerHTML = '';
		CATEGORIES.forEach( function ( cat ) {
			var opt = document.createElement( 'option' );
			opt.value = cat;
			opt.textContent = cat.charAt( 0 ).toUpperCase() + cat.slice( 1 );
			categorySelect.appendChild( opt );
		} );
	}

	// ─── Template Tabs ───────────────────────────────────────────

	function renderTemplateTabs() {
		tabsContainer.innerHTML = '';

		Object.values( templates ).forEach( function ( t ) {
			var btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'tb-template-tab' + ( t.id == activeTemplateId ? ' active' : '' );
			btn.dataset.id = t.id;

			var count = ( t.block_order || [] ).length;
			var html = esc( t.name );
			html += ' <span class="tb-tab-count">' + count + '</span>';
			if ( t.is_system ) html += ' <span class="tb-system-badge">System</span>';
			btn.innerHTML = html;

			btn.addEventListener( 'click', function () {
				loadTemplate( parseInt( t.id ) );
			} );

			tabsContainer.appendChild( btn );
		} );
	}

	function updateTabBadge() {
		var tab = tabsContainer.querySelector( '.tb-template-tab.active' );
		if ( tab ) {
			var badge = tab.querySelector( '.tb-tab-count' );
			if ( badge ) badge.textContent = sortableList.children.length;
		}
	}

	// ─── Load Template ───────────────────────────────────────────

	function loadTemplate( id ) {
		if ( isDirty && ! confirm( 'You have unsaved changes. Discard and switch?' ) ) return;

		activeTemplateId = id;
		var t = activeTemplate();
		if ( ! t ) return;

		// Update tabs.
		tabsContainer.querySelectorAll( '.tb-template-tab' ).forEach( function ( tab ) {
			tab.classList.toggle( 'active', parseInt( tab.dataset.id ) === id );
		} );

		// Populate meta fields.
		if ( nameInput )      nameInput.value     = t.name || '';
		if ( categorySelect ) categorySelect.value = t.category || 'city';
		if ( statusSelect )   statusSelect.value   = t.status || 'draft';
		if ( descInput )      descInput.value      = t.description || '';
		if ( outputSlug )     outputSlug.value     = '';
		if ( canvasTitle )    canvasTitle.textContent = 'Template Canvas — ' + ( t.name || '' );

		// Render blocks in canvas.
		renderCanvasBlocks( t.block_order || [] );

		// Preview.
		previewMode = 'template';
		updatePreviewModeUI();
		updatePreview();

		setDirty( false );
	}

	// ─── Canvas Blocks ───────────────────────────────────────────

	function renderCanvasBlocks( order ) {
		sortableList.innerHTML = '';
		var allBlocks = getAllBlocks();

		order.forEach( function ( blockId ) {
			var block = allBlocks[ blockId ];
			if ( ! block ) return;
			addBlockToCanvas( blockId, block, true );
		} );

		updateCanvasEmpty();
		initSortable();
		updateTabBadge();
	}

	function addBlockToCanvas( blockId, block, skipAnimation ) {
		var li = document.createElement( 'li' );
		li.className = 'seo-sortable-item';
		li.dataset.block = blockId;

		var groupId = findBlockGroup( blockId );
		var groupLabel = BLOCK_GROUPS[ groupId ] ? BLOCK_GROUPS[ groupId ].label : '';

		li.innerHTML =
			'<span class="seo-sortable-handle" aria-label="Drag to reorder">&#x2807;</span>' +
			'<div class="seo-sortable-content">' +
				'<strong class="seo-sortable-label">' + esc( block.label ) + '</strong>' +
				'<span class="seo-sortable-desc">' + esc( block.description ) + '</span>' +
				( groupLabel ? ' <span class="tb-block-group-badge">' + esc( groupLabel ) + '</span>' : '' ) +
			'</div>' +
			'<div class="tb-block-actions">' +
				'<button type="button" class="tb-block-action-btn tb-override-block-btn" title="Block rule overrides" data-block="' + esc( blockId ) + '">&#9881;</button>' +
				'<button type="button" class="tb-block-action-btn tb-preview-block-btn" title="Preview this block" data-block="' + esc( blockId ) + '">&#128065;</button>' +
				'<button type="button" class="tb-block-action-btn tb-remove-block-btn" title="Remove">&times;</button>' +
			'</div>' +
			'<div class="tb-override-panel" data-block="' + esc( blockId ) + '" style="display:none;">' +
				'<div class="tb-override-loading">Loading rules...</div>' +
			'</div>';

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
			setDirty( true );
		}

		updateCanvasEmpty();
		updateTabBadge();
	}

	function updateCanvasEmpty() {
		if ( canvasEmpty ) {
			canvasEmpty.style.display = sortableList.children.length === 0 ? '' : 'none';
		}
	}

	// ─── Sortable ─────────────────────────────────────────────────

	function initSortable() {
		if ( sortableInst ) sortableInst.destroy();
		if ( typeof Sortable !== 'undefined' && sortableList ) {
			sortableInst = Sortable.create( sortableList, {
				animation: 200,
				handle: '.seo-sortable-handle',
				ghostClass: 'seo-sortable-ghost',
				onEnd: function () {
					setDirty( true );
					updatePreview();
					updateTabBadge();
				},
			} );
		}
	}

	// Canvas click handlers: remove + preview block.
	if ( sortableList ) {
		sortableList.addEventListener( 'click', function ( e ) {
			// Remove.
			var removeBtn = e.target.closest( '.tb-remove-block-btn' );
			if ( removeBtn ) {
				var li = removeBtn.closest( '.seo-sortable-item' );
				if ( li ) {
					li.style.transition = 'opacity 0.2s, transform 0.2s';
					li.style.opacity = '0';
					li.style.transform = 'translateX(20px)';
					setTimeout( function () {
						li.remove();
						setDirty( true );
						updatePreview();
						updateCanvasEmpty();
						updateTabBadge();
					}, 200 );
				}
				return;
			}

			// Preview single block.
			var previewBtn = e.target.closest( '.tb-preview-block-btn' );
			if ( previewBtn ) {
				selectedBlockForPreview = previewBtn.dataset.block;
				previewMode = 'block';
				updatePreviewModeUI();
				updatePreview();
				return;
			}

			// Block rule override gear icon.
			var overrideBtn = e.target.closest( '.tb-override-block-btn' );
			if ( overrideBtn ) {
				var blockId = overrideBtn.dataset.block;
				var panel = sortableList.querySelector( '.tb-override-panel[data-block="' + blockId + '"]' );
				if ( panel ) {
					var isOpen = panel.style.display !== 'none';
					if ( isOpen ) {
						panel.style.display = 'none';
					} else {
						panel.style.display = 'block';
						loadOverridePanel( blockId, panel );
					}
				}
				return;
			}

			// Override panel save/clear buttons.
			var saveOverrideBtn = e.target.closest( '.tb-save-override-btn' );
			if ( saveOverrideBtn ) {
				saveBlockOverride( saveOverrideBtn.dataset.block );
				return;
			}

			var clearOverrideBtn = e.target.closest( '.tb-clear-override-btn' );
			if ( clearOverrideBtn ) {
				clearBlockOverride( clearOverrideBtn.dataset.block );
				return;
			}
		} );
	}

	// ─── Block Library (Left Panel) ──────────────────────────────

	function renderBlockLibrary() {
		blockListEl.innerHTML = '';
		var allBlocks = getAllBlocks();
		var query = searchQuery.toLowerCase();

		Object.keys( BLOCK_GROUPS ).forEach( function ( groupId ) {
			var group = BLOCK_GROUPS[ groupId ];
			if ( activeGroupFilter !== 'all' && activeGroupFilter !== groupId ) return;

			var blockEntries = [];
			Object.keys( group.blocks ).forEach( function ( blockId ) {
				var block = group.blocks[ blockId ];
				if ( query ) {
					var haystack = ( block.label + ' ' + block.description ).toLowerCase();
					if ( haystack.indexOf( query ) === -1 ) return;
				}
				blockEntries.push( { id: blockId, block: block } );
			} );

			if ( blockEntries.length === 0 ) return;

			// Accordion group.
			var groupDiv = document.createElement( 'div' );
			groupDiv.className = 'tb-accordion-group';

			var header = document.createElement( 'button' );
			header.type = 'button';
			header.className = 'tb-accordion-header';
			header.innerHTML = '<span>' + esc( group.label ) + ' (' + blockEntries.length + ')</span><span class="tb-accordion-chevron">&#9660;</span>';
			header.addEventListener( 'click', function () {
				groupDiv.classList.toggle( 'collapsed' );
			} );

			var body = document.createElement( 'div' );
			body.className = 'tb-accordion-body';

			blockEntries.forEach( function ( entry ) {
				var item = document.createElement( 'div' );
				item.className = 'tb-lib-block';
				item.innerHTML =
					'<div class="tb-lib-block-info">' +
						'<span class="tb-lib-block-label">' + esc( entry.block.label ) + '</span>' +
						'<span class="tb-lib-block-desc">' + esc( entry.block.description ) + '</span>' +
					'</div>' +
					'<div class="tb-lib-block-actions">' +
						'<button type="button" class="tb-lib-action-btn tb-lib-preview" data-block="' + esc( entry.id ) + '" title="Preview">&#128065;</button>' +
						'<button type="button" class="tb-lib-action-btn tb-lib-add" data-block="' + esc( entry.id ) + '" title="Add">+</button>' +
					'</div>';
				body.appendChild( item );
			} );

			groupDiv.appendChild( header );
			groupDiv.appendChild( body );
			blockListEl.appendChild( groupDiv );
		} );
	}

	// Filter pills.
	function renderFilterPills() {
		// "All" already in HTML. Add group pills.
		Object.keys( BLOCK_GROUPS ).forEach( function ( groupId ) {
			var btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'tb-pill';
			btn.dataset.group = groupId;
			btn.textContent = BLOCK_GROUPS[ groupId ].label;
			filterPills.appendChild( btn );
		} );
	}

	if ( filterPills ) {
		filterPills.addEventListener( 'click', function ( e ) {
			var pill = e.target.closest( '.tb-pill' );
			if ( ! pill ) return;
			filterPills.querySelectorAll( '.tb-pill' ).forEach( function ( p ) { p.classList.remove( 'active' ); } );
			pill.classList.add( 'active' );
			activeGroupFilter = pill.dataset.group || 'all';
			renderBlockLibrary();
		} );
	}

	// Search.
	if ( searchInput ) {
		searchInput.addEventListener( 'input', function () {
			searchQuery = searchInput.value;
			renderBlockLibrary();
		} );
	}

	// Library click handlers: add + preview.
	if ( blockListEl ) {
		blockListEl.addEventListener( 'click', function ( e ) {
			var addBtn = e.target.closest( '.tb-lib-add' );
			if ( addBtn ) {
				var blockId = addBtn.dataset.block;
				var allBlocks = getAllBlocks();
				var block = allBlocks[ blockId ];
				if ( block ) {
					addBlockToCanvas( blockId, block, false );
					updatePreview();
				}
				return;
			}

			var previewBtn = e.target.closest( '.tb-lib-preview' );
			if ( previewBtn ) {
				selectedBlockForPreview = previewBtn.dataset.block;
				previewMode = 'block';
				updatePreviewModeUI();
				updatePreview();
			}
		} );
	}

	// ─── Preview ──────────────────────────────────────────────────

	function updatePreview() {
		if ( ! previewIframe ) return;

		if ( previewMode === 'block' && selectedBlockForPreview ) {
			previewIframe.src = PREVIEW_BASE + '/preview?blocks=' + selectedBlockForPreview;
		} else {
			var blocks = getBlockOrder();
			if ( blocks.length === 0 ) {
				previewIframe.src = 'about:blank';
				return;
			}
			previewIframe.src = PREVIEW_BASE + '/preview?blocks=' + blocks.join( ',' );
		}
	}

	function updatePreviewModeUI() {
		if ( ! previewToggle ) return;
		previewToggle.querySelectorAll( '.tb-mode-btn' ).forEach( function ( btn ) {
			btn.classList.toggle( 'active', btn.dataset.mode === previewMode );
		} );
	}

	// Preview mode toggle.
	if ( previewToggle ) {
		previewToggle.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '.tb-mode-btn' );
			if ( ! btn ) return;
			previewMode = btn.dataset.mode;
			updatePreviewModeUI();
			updatePreview();
		} );
	}

	// Device toggle.
	app.querySelectorAll( '.tb-device-toggle .device-toggle-btn' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			app.querySelectorAll( '.tb-device-toggle .device-toggle-btn' ).forEach( function ( b ) {
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
		previewContainer.style.minHeight = ( 900 * scale + 40 ) + 'px';
	}

	window.addEventListener( 'resize', function () {
		if ( previewContainer && previewContainer.dataset.device === 'desktop' ) updateDesktopScale();
	} );

	// ─── Template Management ─────────────────────────────────────

	// Create.
	var newBtn = document.getElementById( 'tb-new-template' );
	if ( newBtn ) {
		newBtn.addEventListener( 'click', async function () {
			var name = prompt( 'New template name:' );
			if ( ! name ) return;

			var json = await ajax( 'tb_create_template', { name: name } );
			if ( json.success ) {
				var t = json.data.template;
				templates[ t.id ] = t;
				renderTemplateTabs();
				loadTemplate( t.id );
				showStatus( saveStatus, 'Template created.', 'success' );
			} else {
				alert( json.data?.message || 'Failed to create template.' );
			}
		} );
	}

	// Clone.
	var cloneBtn = document.getElementById( 'tb-clone-template' );
	if ( cloneBtn ) {
		cloneBtn.addEventListener( 'click', async function () {
			if ( ! activeTemplateId ) return;
			var t = activeTemplate();
			var name = prompt( 'Clone name:', ( t ? t.name + ' (Copy)' : 'Copy' ) );
			if ( ! name ) return;

			var json = await ajax( 'tb_clone_template', { template_id: activeTemplateId, new_name: name } );
			if ( json.success ) {
				var newT = json.data.template;
				templates[ newT.id ] = newT;
				renderTemplateTabs();
				loadTemplate( newT.id );
				showStatus( saveStatus, 'Template cloned.', 'success' );
			} else {
				alert( json.data?.message || 'Failed to clone template.' );
			}
		} );
	}

	// Delete.
	var deleteBtn = document.getElementById( 'tb-delete-template' );
	if ( deleteBtn ) {
		deleteBtn.addEventListener( 'click', async function () {
			if ( ! activeTemplateId ) return;
			var t = activeTemplate();
			if ( t && t.is_system ) {
				alert( 'System templates cannot be deleted.' );
				return;
			}
			if ( ! confirm( 'Delete template "' + ( t ? t.name : '' ) + '"? This cannot be undone.' ) ) return;

			var json = await ajax( 'tb_delete_template', { template_id: activeTemplateId } );
			if ( json.success ) {
				delete templates[ activeTemplateId ];
				var ids = Object.keys( templates );
				activeTemplateId = ids.length > 0 ? parseInt( ids[0] ) : null;
				renderTemplateTabs();
				if ( activeTemplateId ) loadTemplate( activeTemplateId );
				showStatus( saveStatus, 'Template deleted.', 'success' );
			} else {
				alert( json.data?.message || 'Failed to delete template.' );
			}
		} );
	}

	// ─── Save Template ───────────────────────────────────────────

	var saveBtn = document.getElementById( 'tb-save-btn' );
	if ( saveBtn ) {
		saveBtn.addEventListener( 'click', async function () {
			if ( ! activeTemplateId ) return;

			var order = getBlockOrder();
			var data = {
				template_id: activeTemplateId,
				name:        nameInput ? nameInput.value : '',
				category:    categorySelect ? categorySelect.value : 'city',
				status:      statusSelect ? statusSelect.value : 'draft',
				description: descInput ? descInput.value : '',
				block_order: JSON.stringify( order ),
			};

			var json = await ajax( 'tb_update_template', data );
			if ( json.success ) {
				templates[ activeTemplateId ] = json.data.template;
				renderTemplateTabs();
				setDirty( false );
				showStatus( saveStatus, 'Template saved.', 'success' );
			} else {
				showStatus( saveStatus, json.data?.message || 'Save failed.', 'error' );
			}
		} );
	}

	// ─── Publish ──────────────────────────────────────────────────

	var publishBtn = document.getElementById( 'tb-publish-btn' );
	if ( publishBtn ) {
		publishBtn.addEventListener( 'click', async function () {
			if ( ! activeTemplateId ) return;

			var slug = ( outputSlug ? outputSlug.value.trim() : '' );
			if ( ! slug ) {
				alert( 'Enter an output URL slug (e.g. "san-diego-jewelry-store").' );
				if ( outputSlug ) outputSlug.focus();
				return;
			}

			if ( RESERVED.indexOf( slug ) !== -1 ) {
				alert( 'The slug "/' + slug + '" is reserved. Choose a different one.' );
				if ( outputSlug ) outputSlug.focus();
				return;
			}

			var order = getBlockOrder();
			if ( order.length === 0 ) {
				alert( 'Add at least one block before publishing.' );
				return;
			}

			publishBtn.disabled = true;
			publishBtn.textContent = 'Publishing...';

			var json = await ajax( 'tb_publish_page', {
				template_id: activeTemplateId,
				output_slug: slug,
				block_order: JSON.stringify( order ),
			} );

			if ( json.success ) {
				if ( json.data.build_status === 'not_needed' ) {
					showStatus( saveStatus, 'Published to /' + slug + ' — live now!', 'success' );
				} else if ( json.data.build_status === 'started' ) {
					showStatus( saveStatus, 'Published! Building & restarting...', 'success' );
				} else {
					showStatus( saveStatus, 'Published! Run pnpm build to go live.', 'success' );
				}

				// Also save template with new block order.
				templates[ activeTemplateId ].block_order = order;
				setDirty( false );
			} else {
				showStatus( saveStatus, json.data?.message || 'Publish failed.', 'error' );
			}

			publishBtn.disabled = false;
			publishBtn.innerHTML = 'Publish &rarr;';
		} );
	}

	// ─── Settings ─────────────────────────────────────────────────

	var settingsBtn    = document.getElementById( 'tb-save-settings-btn' );
	var settingsStatus = document.getElementById( 'tb-settings-status' );

	if ( settingsBtn ) {
		settingsBtn.addEventListener( 'click', async function () {
			var json = await ajax( 'tb_save_settings', {
				project_path: document.getElementById( 'tb-project-path' ).value,
				preview_url:  document.getElementById( 'tb-preview-url' ).value,
			} );
			showStatus( settingsStatus, json.success ? 'Saved' : 'Failed', json.success ? 'success' : 'error' );
		} );
	}

	// Dynamic route setup.
	var setupBtn    = document.getElementById( 'tb-setup-dynamic-btn' );
	var setupStatus = document.getElementById( 'tb-setup-dynamic-status' );

	if ( setupBtn ) {
		setupBtn.addEventListener( 'click', async function () {
			setupBtn.disabled = true;
			setupBtn.textContent = 'Setting up...';

			var json = await ajax( 'tb_setup_dynamic', {} );
			if ( json.success ) {
				DYNAMIC_SETUP = true;
				showStatus( setupStatus, json.data.message, 'success' );
				setupBtn.textContent = 'Re-generate Files';
			} else {
				showStatus( setupStatus, json.data?.message || 'Setup failed', 'error' );
				setupBtn.textContent = 'Setup Dynamic Route';
			}
			setupBtn.disabled = false;
		} );
	}

	// ─── Block Rule Overrides ─────────────────────────────────────

	function loadOverridePanel( blockId, panel ) {
		if ( ! activeTemplateId ) {
			panel.innerHTML = '<p class="tb-override-msg">Save the template first to enable overrides.</p>';
			return;
		}

		panel.innerHTML = '<div class="tb-override-loading">Loading resolved rules...</div>';

		// Fetch resolved rules for this block + template.
		var fd = new FormData();
		fd.append( 'action', 'tb_get_block_resolved_rules' );
		fd.append( 'nonce', NONCE );
		fd.append( 'template_id', activeTemplateId );
		fd.append( 'block_id', blockId );

		fetch( AJAX_URL, { method: 'POST', body: fd } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( resp ) {
				if ( ! resp.success ) {
					panel.innerHTML = '<p class="tb-override-msg">' + esc( resp.data.message || 'Error' ) + '</p>';
					return;
				}

				var rules = resp.data.rules || {};
				var hasOverride = resp.data.has_override || false;
				var slots = rules.content_slots || {};

				var html = '<div class="tb-override-header">' +
					( hasOverride
						? '<span class="tb-override-badge tb-override-badge-active">Has Override</span>'
						: '<span class="tb-override-badge tb-override-badge-default">Using Defaults</span>'
					) +
				'</div>';

				html += '<div class="tb-override-slots">';
				for ( var slotName in slots ) {
					var slot = slots[ slotName ];
					html += '<div class="tb-override-slot">' +
						'<label class="tb-override-slot-label">' + esc( slotName ) + '</label>' +
						'<div class="tb-override-fields">' +
							'<label><small>Max Length</small>' +
								'<input type="number" class="tb-override-input" data-slot="' + esc( slotName ) + '" data-key="max_length" value="' + ( slot.max_length || '' ) + '">' +
							'</label>' +
							'<label><small>Mobile Max</small>' +
								'<input type="number" class="tb-override-input" data-slot="' + esc( slotName ) + '" data-key="mobile_max_length" value="' + ( slot.mobile_max_length || '' ) + '">' +
							'</label>' +
							'<label><small>Over Limit</small>' +
								'<select class="tb-override-input" data-slot="' + esc( slotName ) + '" data-key="over_limit_action">' +
									'<option value="truncate"' + ( slot.over_limit_action === 'truncate' ? ' selected' : '' ) + '>Truncate</option>' +
									'<option value="flag"' + ( slot.over_limit_action === 'flag' ? ' selected' : '' ) + '>Flag</option>' +
								'</select>' +
							'</label>' +
						'</div>' +
					'</div>';
				}
				html += '</div>';

				html += '<div class="tb-override-actions">' +
					'<button type="button" class="seo-btn-primary tb-save-override-btn" data-block="' + esc( blockId ) + '">Save Override</button>' +
					'<button type="button" class="seo-btn-secondary tb-clear-override-btn" data-block="' + esc( blockId ) + '">Clear Override</button>' +
				'</div>';

				panel.innerHTML = html;
			} );
	}

	function saveBlockOverride( blockId ) {
		if ( ! activeTemplateId ) return;

		var panel = sortableList.querySelector( '.tb-override-panel[data-block="' + blockId + '"]' );
		if ( ! panel ) return;

		// Collect override values from inputs.
		var override = { content_slots: {} };
		var inputs = panel.querySelectorAll( '.tb-override-input' );
		inputs.forEach( function ( input ) {
			var slot = input.dataset.slot;
			var key  = input.dataset.key;
			if ( ! override.content_slots[ slot ] ) override.content_slots[ slot ] = {};
			var val = input.type === 'number' ? parseInt( input.value, 10 ) || 0 : input.value;
			override.content_slots[ slot ][ key ] = val;
		} );

		var fd = new FormData();
		fd.append( 'action', 'tb_save_block_override' );
		fd.append( 'nonce', NONCE );
		fd.append( 'template_id', activeTemplateId );
		fd.append( 'block_id', blockId );
		fd.append( 'override_json', JSON.stringify( override ) );

		fetch( AJAX_URL, { method: 'POST', body: fd } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( resp ) {
				if ( resp.success ) {
					loadOverridePanel( blockId, panel );
					showStatus( saveStatus, 'Override saved.', 'success' );
				} else {
					showStatus( saveStatus, resp.data.message || 'Save failed.', 'error' );
				}
			} );
	}

	function clearBlockOverride( blockId ) {
		if ( ! activeTemplateId ) return;
		if ( ! confirm( 'Clear override for this block? It will revert to default rules.' ) ) return;

		var panel = sortableList.querySelector( '.tb-override-panel[data-block="' + blockId + '"]' );

		var fd = new FormData();
		fd.append( 'action', 'tb_delete_block_override' );
		fd.append( 'nonce', NONCE );
		fd.append( 'template_id', activeTemplateId );
		fd.append( 'block_id', blockId );

		fetch( AJAX_URL, { method: 'POST', body: fd } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( resp ) {
				if ( resp.success ) {
					if ( panel ) loadOverridePanel( blockId, panel );
					showStatus( saveStatus, 'Override cleared.', 'success' );
				} else {
					showStatus( saveStatus, resp.data.message || 'Clear failed.', 'error' );
				}
			} );
	}

	// ─── Dirty State ──────────────────────────────────────────────

	// Track changes on meta fields.
	[ nameInput, categorySelect, statusSelect, descInput ].forEach( function ( el ) {
		if ( el ) {
			el.addEventListener( 'input', function () { setDirty( true ); } );
			el.addEventListener( 'change', function () { setDirty( true ); } );
		}
	} );

	// Warn on unload.
	window.addEventListener( 'beforeunload', function ( e ) {
		if ( isDirty ) {
			e.preventDefault();
			e.returnValue = '';
		}
	} );

	// ─── Init ─────────────────────────────────────────────────────

	// Auto-collapse WP sidebar to maximize preview space.
	var wasFolded = document.body.classList.contains( 'folded' );
	document.body.classList.add( 'folded' );

	// Restore original sidebar state when leaving the page.
	window.addEventListener( 'pagehide', function () {
		if ( ! wasFolded ) document.body.classList.remove( 'folded' );
	} );

	populateCategorySelect();
	renderFilterPills();
	renderBlockLibrary();
	renderTemplateTabs();

	if ( activeTemplateId && templates[ activeTemplateId ] ) {
		loadTemplate( activeTemplateId );
	}

	initSortable();

} )();
