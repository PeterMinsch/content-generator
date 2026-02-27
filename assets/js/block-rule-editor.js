/**
 * Block Rule Editor
 *
 * Two-panel UI: Block List | Rule Editor.
 * IIFE pattern matching existing convention.
 *
 * @package SEOGenerator
 */

( function () {
	'use strict';

	const cfg      = window.blockRuleEditorData || {};
	const AJAX_URL = cfg.ajaxUrl || '/wp-admin/admin-ajax.php';
	const NONCE    = cfg.nonce || '';
	const BLOCKS   = cfg.blocks || {};
	let   profiles = cfg.profiles || {};

	let activeBlockId = null;
	let currentRules  = null;
	let searchQuery   = '';

	// ─── Helpers ──────────────────────────────────────────────────

	function $( sel ) { return document.querySelector( sel ); }
	function $$( sel ) { return document.querySelectorAll( sel ); }

	function ajax( action, data ) {
		const fd = new FormData();
		fd.append( 'action', action );
		fd.append( 'nonce', NONCE );
		for ( const [ k, v ] of Object.entries( data ) ) {
			fd.append( k, typeof v === 'object' ? JSON.stringify( v ) : v );
		}
		return fetch( AJAX_URL, { method: 'POST', body: fd } )
			.then( r => r.json() );
	}

	function showStatus( msg, type ) {
		const el = $( '#bre-status-message' );
		el.textContent = msg;
		el.className = 'bre-status-message bre-status-' + type;
		el.style.display = 'block';
		setTimeout( () => { el.style.display = 'none'; }, 4000 );
	}

	// ─── Block List ──────────────────────────────────────────────

	function renderBlockList() {
		const container = $( '#bre-block-list' );
		container.innerHTML = '';

		// Group blocks.
		const groups = {};
		for ( const [ id, b ] of Object.entries( BLOCKS ) ) {
			if ( searchQuery && ! b.label.toLowerCase().includes( searchQuery ) && ! id.toLowerCase().includes( searchQuery ) ) {
				continue;
			}
			if ( ! groups[ b.group ] ) {
				groups[ b.group ] = { label: b.group_label, blocks: [] };
			}
			groups[ b.group ].blocks.push( { ...b, id } );
		}

		for ( const [ groupId, group ] of Object.entries( groups ) ) {
			if ( ! group.blocks.length ) continue;

			const header = document.createElement( 'div' );
			header.className = 'bre-group-header';
			header.textContent = group.label;
			container.appendChild( header );

			for ( const block of group.blocks ) {
				const item = document.createElement( 'div' );
				item.className = 'bre-block-item' + ( block.id === activeBlockId ? ' active' : '' );
				item.dataset.blockId = block.id;

				const profile = profiles[ block.id ];
				const badges = [];
				if ( profile ) {
					if ( profile.source === 'edited' ) {
						badges.push( '<span class="bre-badge bre-badge-edited">Edited</span>' );
					}
					badges.push( '<span class="bre-badge bre-badge-version">v' + profile.version + '</span>' );
				}

				item.innerHTML =
					'<div class="bre-block-item-main">' +
						'<span class="bre-block-label">' + esc( block.label ) + '</span>' +
						'<span class="bre-block-slots">' + block.slot_count + ' slots</span>' +
					'</div>' +
					'<div class="bre-block-item-badges">' + badges.join( '' ) + '</div>';

				item.addEventListener( 'click', () => loadBlock( block.id ) );
				container.appendChild( item );
			}
		}
	}

	function esc( str ) {
		const d = document.createElement( 'div' );
		d.textContent = str;
		return d.innerHTML;
	}

	// ─── Load Block ──────────────────────────────────────────────

	function loadBlock( blockId ) {
		activeBlockId = blockId;
		renderBlockList();

		ajax( 'br_get_profile', { block_id: blockId } ).then( resp => {
			if ( ! resp.success ) {
				showStatus( resp.data.message || 'Error loading block.', 'error' );
				return;
			}

			currentRules = resp.data.rules;
			renderEditor();
			loadVersionHistory();
		} );
	}

	// ─── Render Editor ───────────────────────────────────────────

	function renderEditor() {
		$( '#bre-editor-placeholder' ).style.display = 'none';
		$( '#bre-editor-content' ).style.display = 'block';

		const block = BLOCKS[ activeBlockId ];
		$( '#bre-editor-title' ).textContent = block ? block.label : activeBlockId;

		const profile = profiles[ activeBlockId ];
		const badge = $( '#bre-editor-badge' );
		const ver = $( '#bre-editor-version' );
		if ( profile ) {
			badge.textContent = profile.source === 'edited' ? 'Edited' : 'Config';
			badge.className = 'bre-badge ' + ( profile.source === 'edited' ? 'bre-badge-edited' : 'bre-badge-config' );
			ver.textContent = 'v' + profile.version;
		} else {
			badge.textContent = 'Config';
			badge.className = 'bre-badge bre-badge-config';
			ver.textContent = '';
		}

		renderSlots();
		renderImages();
		renderBreadcrumb();
	}

	function renderSlots() {
		const container = $( '#bre-slots-body' );
		const slots = currentRules.content_slots || {};
		container.innerHTML = '';

		if ( ! Object.keys( slots ).length ) {
			container.innerHTML = '<p class="bre-empty">No content slots defined.</p>';
			return;
		}

		for ( const [ name, slot ] of Object.entries( slots ) ) {
			const card = document.createElement( 'div' );
			card.className = 'bre-slot-card';
			card.innerHTML =
				'<h4 class="bre-slot-name">' + esc( name ) + ' <span class="bre-slot-type">(' + esc( slot.type || 'text' ) + ')</span></h4>' +
				'<div class="bre-field-grid">' +
					field( 'max_length', 'Max Length', slot.max_length, 'number', name ) +
					field( 'mobile_max_length', 'Mobile Max', slot.mobile_max_length, 'number', name ) +
					checkbox( 'mobile_hidden', 'Mobile Hidden', slot.mobile_hidden, name ) +
					checkbox( 'required', 'Required', slot.required, name ) +
					select( 'over_limit_action', 'Over Limit', slot.over_limit_action, [ 'truncate', 'flag', 'regenerate' ], name ) +
					field( 'ai_hint', 'AI Hint', slot.ai_hint, 'text', name ) +
					field( 'validation.min_length', 'Min Length', ( slot.validation || {} ).min_length || 0, 'number', name ) +
					field( 'validation.forbidden_patterns', 'Forbidden Patterns', ( ( slot.validation || {} ).forbidden_patterns || [] ).join( ', ' ), 'text', name ) +
					checkbox( 'validation.must_contain_keyword', 'Must Contain Keyword', ( slot.validation || {} ).must_contain_keyword, name ) +
				'</div>';
			container.appendChild( card );
		}
	}

	function field( key, label, value, type, slotName ) {
		const id = 'bre-slot-' + slotName + '-' + key.replace( /\./g, '-' );
		return '<div class="bre-field">' +
			'<label for="' + id + '">' + esc( label ) + '</label>' +
			'<input type="' + type + '" id="' + id + '" class="bre-input" ' +
			'data-slot="' + esc( slotName ) + '" data-key="' + esc( key ) + '" ' +
			'value="' + esc( String( value ?? '' ) ) + '">' +
		'</div>';
	}

	function checkbox( key, label, checked, slotName ) {
		const id = 'bre-slot-' + slotName + '-' + key.replace( /\./g, '-' );
		return '<div class="bre-field bre-field-check">' +
			'<label><input type="checkbox" id="' + id + '" ' +
			'data-slot="' + esc( slotName ) + '" data-key="' + esc( key ) + '" ' +
			( checked ? 'checked' : '' ) + '> ' + esc( label ) + '</label>' +
		'</div>';
	}

	function select( key, label, value, options, slotName ) {
		const id = 'bre-slot-' + slotName + '-' + key.replace( /\./g, '-' );
		let opts = '';
		for ( const o of options ) {
			opts += '<option value="' + esc( o ) + '"' + ( o === value ? ' selected' : '' ) + '>' + esc( o ) + '</option>';
		}
		return '<div class="bre-field">' +
			'<label for="' + id + '">' + esc( label ) + '</label>' +
			'<select id="' + id + '" class="bre-input" ' +
			'data-slot="' + esc( slotName ) + '" data-key="' + esc( key ) + '">' + opts + '</select>' +
		'</div>';
	}

	function renderImages() {
		const container = $( '#bre-images-body' );
		const images = currentRules.images || [];
		container.innerHTML = '';

		if ( ! images.length ) {
			container.innerHTML = '<p class="bre-empty">No image rules defined.</p>';
			return;
		}

		images.forEach( ( img, idx ) => {
			const card = document.createElement( 'div' );
			card.className = 'bre-image-card';
			card.innerHTML =
				'<h4>' + esc( img.label || 'Image ' + ( idx + 1 ) ) + '</h4>' +
				'<div class="bre-field-grid">' +
					'<div class="bre-field"><label>Desktop</label><span class="bre-readonly">' +
						( img.desktop ? img.desktop.join( ' x ' ) : 'N/A' ) + '</span></div>' +
					'<div class="bre-field"><label>Mobile</label><span class="bre-readonly">' +
						( img.mobile ? img.mobile.join( ' x ' ) : 'N/A' ) + '</span></div>' +
					'<div class="bre-field bre-field-check"><label>' +
						'<input type="checkbox" data-img-idx="' + idx + '" data-key="required" ' +
						( img.required ? 'checked' : '' ) + '> Required</label></div>' +
					'<div class="bre-field bre-field-check"><label>' +
						'<input type="checkbox" data-img-idx="' + idx + '" data-key="alt_text_required" ' +
						( img.alt_text_required ? 'checked' : '' ) + '> Alt Text Required</label></div>' +
					'<div class="bre-field"><label>Source Rule</label>' +
						'<select data-img-idx="' + idx + '" data-key="source_rule" class="bre-input">' +
							'<option value="library"' + ( img.source_rule === 'library' ? ' selected' : '' ) + '>Library</option>' +
							'<option value="upload"' + ( img.source_rule === 'upload' ? ' selected' : '' ) + '>Upload</option>' +
							'<option value="ai"' + ( img.source_rule === 'ai' ? ' selected' : '' ) + '>AI Generated</option>' +
						'</select></div>' +
				'</div>';
			container.appendChild( card );
		} );
	}

	function renderBreadcrumb() {
		const bc = currentRules.breadcrumb || {};
		$( '#bre-breadcrumb-enabled' ).checked = !! bc.enabled;
		$( '#bre-breadcrumb-pattern' ).value = bc.pattern || 'Home / {page_title}';
	}

	// ─── Collect Edited Rules ────────────────────────────────────

	function collectRules() {
		const rules = JSON.parse( JSON.stringify( currentRules ) );

		// Collect slots.
		$$( '#bre-slots-body [data-slot]' ).forEach( el => {
			const slot = el.dataset.slot;
			const key  = el.dataset.key;

			if ( ! rules.content_slots[ slot ] ) return;

			let value;
			if ( el.type === 'checkbox' ) {
				value = el.checked;
			} else if ( el.type === 'number' ) {
				value = parseInt( el.value, 10 ) || 0;
			} else {
				value = el.value;
			}

			// Handle nested keys like "validation.min_length".
			if ( key.includes( '.' ) ) {
				const parts = key.split( '.' );
				if ( ! rules.content_slots[ slot ][ parts[0] ] ) {
					rules.content_slots[ slot ][ parts[0] ] = {};
				}
				if ( parts[1] === 'forbidden_patterns' ) {
					value = value ? value.split( ',' ).map( s => s.trim() ).filter( Boolean ) : [];
				}
				rules.content_slots[ slot ][ parts[0] ][ parts[1] ] = value;
			} else {
				rules.content_slots[ slot ][ key ] = value;
			}
		} );

		// Collect images.
		$$( '#bre-images-body [data-img-idx]' ).forEach( el => {
			const idx = parseInt( el.dataset.imgIdx, 10 );
			const key = el.dataset.key;
			if ( ! rules.images[ idx ] ) return;
			rules.images[ idx ][ key ] = el.type === 'checkbox' ? el.checked : el.value;
		} );

		// Collect breadcrumb.
		rules.breadcrumb = {
			enabled: $( '#bre-breadcrumb-enabled' ).checked,
			pattern: $( '#bre-breadcrumb-pattern' ).value,
		};

		return rules;
	}

	// ─── Version History ─────────────────────────────────────────

	function loadVersionHistory() {
		ajax( 'br_get_version_history', { block_id: activeBlockId } ).then( resp => {
			if ( ! resp.success ) return;

			const list = $( '#bre-version-list' );
			const versions = resp.data.versions || [];

			if ( ! versions.length ) {
				list.innerHTML = '<p class="bre-empty">No versions yet.</p>';
				return;
			}

			list.innerHTML = '';
			for ( const v of versions ) {
				const row = document.createElement( 'div' );
				row.className = 'bre-version-row' + ( v.is_current ? ' bre-version-current' : '' );
				row.innerHTML =
					'<span class="bre-version-num">v' + v.version + '</span>' +
					'<span class="bre-version-source">' + esc( v.source ) + '</span>' +
					'<span class="bre-version-date">' + esc( v.created_at ) + '</span>' +
					( v.notes ? '<span class="bre-version-note">' + esc( v.notes ) + '</span>' : '' ) +
					( ! v.is_current
						? '<button type="button" class="bre-btn-sm" data-revert="' + v.version + '">Revert</button>'
						: '<span class="bre-current-label">Current</span>' );
				list.appendChild( row );
			}

			// Revert buttons.
			$$( '#bre-version-list [data-revert]' ).forEach( btn => {
				btn.addEventListener( 'click', () => {
					const ver = parseInt( btn.dataset.revert, 10 );
					if ( ! confirm( 'Revert to version ' + ver + '?' ) ) return;

					ajax( 'br_revert_profile', { block_id: activeBlockId, version: ver } ).then( r => {
						if ( r.success ) {
							currentRules = r.data.rules;
							refreshProfiles();
							renderEditor();
							loadVersionHistory();
							showStatus( r.data.message, 'success' );
						} else {
							showStatus( r.data.message || 'Revert failed.', 'error' );
						}
					} );
				} );
			} );
		} );
	}

	function refreshProfiles() {
		ajax( 'br_list_profiles', {} ).then( resp => {
			if ( resp.success ) {
				profiles = resp.data.profiles;
				renderBlockList();
			}
		} );
	}

	// ─── Actions ─────────────────────────────────────────────────

	function save() {
		if ( ! activeBlockId ) return;

		const rules = collectRules();

		ajax( 'br_save_profile', { block_id: activeBlockId, schema_json: rules } ).then( resp => {
			if ( resp.success ) {
				currentRules = resp.data.rules;
				refreshProfiles();
				renderEditor();
				loadVersionHistory();
				showStatus( resp.data.message, 'success' );
			} else {
				showStatus( resp.data.message || 'Save failed.', 'error' );
			}
		} );
	}

	function resetToFactory() {
		if ( ! activeBlockId ) return;
		if ( ! confirm( 'Reset "' + ( BLOCKS[ activeBlockId ]?.label || activeBlockId ) + '" to factory config?' ) ) return;

		ajax( 'br_reset_to_factory', { block_id: activeBlockId } ).then( resp => {
			if ( resp.success ) {
				currentRules = resp.data.rules;
				refreshProfiles();
				renderEditor();
				loadVersionHistory();
				showStatus( resp.data.message, 'success' );
			} else {
				showStatus( resp.data.message || 'Reset failed.', 'error' );
			}
		} );
	}

	// ─── Accordions ──────────────────────────────────────────────

	function initAccordions() {
		$$( '.bre-accordion-toggle' ).forEach( btn => {
			btn.addEventListener( 'click', () => {
				const target = $( '#' + btn.dataset.target );
				const open = target.style.display !== 'none';
				target.style.display = open ? 'none' : 'block';
				btn.querySelector( '.bre-accordion-arrow' ).textContent = open ? '\u25B6' : '\u25BC';
			} );
		} );
	}

	// ─── Init ────────────────────────────────────────────────────

	function init() {
		renderBlockList();
		initAccordions();

		$( '#bre-search' ).addEventListener( 'input', e => {
			searchQuery = e.target.value.toLowerCase();
			renderBlockList();
		} );

		$( '#bre-save-btn' ).addEventListener( 'click', save );
		$( '#bre-reset-btn' ).addEventListener( 'click', resetToFactory );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
