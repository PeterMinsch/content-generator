/**
 * Geographic Title Generator JavaScript
 *
 * Handles CSV upload, title generation, and display functionality.
 */

(function ($) {
	'use strict';

	let totalCount = 0;
	let currentPage = 1;
	let itemsPerPage = 20; // Changed to 20 items to test scrolling
	let searchTerm = '';
	let totalPages = 0;
	let performanceMonitor = null;

	/**
	 * Performance monitoring class.
	 */
	class PerformanceMonitor {
		constructor() {
			this.startTime = performance.now();
			this.lastFrameTime = performance.now();
			this.frameCount = 0;
			this.fps = 0;
			this.isScrolling = false;
			this.createDashboard();
			this.startMonitoring();
		}

		createDashboard() {
			const $dashboard = $('<div>', {
				id: 'perf-dashboard',
				style: `
					position: fixed;
					top: 50px;
					right: 20px;
					background: rgba(0,0,0,0.9);
					color: #0f0;
					padding: 15px;
					border-radius: 5px;
					font-family: monospace;
					font-size: 12px;
					z-index: 999999;
					min-width: 300px;
					box-shadow: 0 4px 8px rgba(0,0,0,0.5);
				`,
			});

			$dashboard.html(`
				<div style="margin-bottom: 10px; color: #fff; font-weight: bold; border-bottom: 1px solid #0f0; padding-bottom: 5px;">
					⚡ PERFORMANCE MONITOR
				</div>
				<div id="perf-fps" style="margin: 5px 0;">FPS: <span style="color: #0f0;">--</span></div>
				<div id="perf-memory" style="margin: 5px 0;">Memory: <span style="color: #0f0;">--</span></div>
				<div id="perf-dom" style="margin: 5px 0;">DOM Nodes: <span style="color: #0f0;">--</span></div>
				<div id="perf-table" style="margin: 5px 0;">Table Rows: <span style="color: #0f0;">--</span></div>
				<div id="perf-scroll" style="margin: 5px 0;">Scroll Events/sec: <span style="color: #0f0;">0</span></div>
				<div id="perf-render" style="margin: 5px 0;">Paint Time: <span style="color: #0f0;">--</span></div>
				<div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #333;">
					<button id="close-perf-dashboard" style="background: #f00; color: #fff; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px;">
						Close Monitor
					</button>
				</div>
			`);

			$('body').append($dashboard);

			$('#close-perf-dashboard').on('click', () => {
				this.stopMonitoring();
				$('#perf-dashboard').remove();
			});
		}

		startMonitoring() {
			// Monitor FPS
			this.fpsInterval = setInterval(() => {
				this.measureFPS();
			}, 1000);

			// Monitor memory
			this.memoryInterval = setInterval(() => {
				this.measureMemory();
			}, 2000);

			// Monitor DOM
			this.domInterval = setInterval(() => {
				this.measureDOM();
			}, 3000);

			// Monitor scroll performance
			let scrollEventCount = 0;
			let scrollCountInterval;

			$('#titles-list').on('scroll.perf', () => {
				scrollEventCount++;
				this.isScrolling = true;

				if (!scrollCountInterval) {
					scrollCountInterval = setInterval(() => {
						$('#perf-scroll span').text(scrollEventCount);
						scrollEventCount = 0;
					}, 1000);
				}
			});
		}

		measureFPS() {
			const now = performance.now();
			const delta = now - this.lastFrameTime;
			this.fps = Math.round(1000 / delta);
			this.lastFrameTime = now;

			const color = this.fps >= 50 ? '#0f0' : this.fps >= 30 ? '#ff0' : '#f00';
			$('#perf-fps span').css('color', color).text(this.fps + ' fps');
		}

		measureMemory() {
			if (performance.memory) {
				const used = (performance.memory.usedJSHeapSize / 1048576).toFixed(2);
				const total = (performance.memory.totalJSHeapSize / 1048576).toFixed(2);
				const limit = (performance.memory.jsHeapSizeLimit / 1048576).toFixed(2);

				const percentage = ((used / total) * 100).toFixed(0);
				const color = percentage < 70 ? '#0f0' : percentage < 85 ? '#ff0' : '#f00';

				$('#perf-memory span')
					.css('color', color)
					.text(`${used} / ${total} MB (${percentage}%)`);
			} else {
				$('#perf-memory span').text('Not available');
			}
		}

		measureDOM() {
			const totalNodes = document.getElementsByTagName('*').length;
			const tableRows = $('#titles-list tbody tr').length;

			$('#perf-dom span').text(totalNodes.toLocaleString());
			$('#perf-table span').text(tableRows);

			// Measure paint time
			if (this.isScrolling) {
				const paintStart = performance.now();
				requestAnimationFrame(() => {
					const paintTime = (performance.now() - paintStart).toFixed(2);
					const color = paintTime < 16 ? '#0f0' : paintTime < 33 ? '#ff0' : '#f00';
					$('#perf-render span').css('color', color).text(paintTime + ' ms');
					this.isScrolling = false;
				});
			}
		}

		stopMonitoring() {
			clearInterval(this.fpsInterval);
			clearInterval(this.memoryInterval);
			clearInterval(this.domInterval);
			$('#titles-list').off('scroll.perf');
		}
	}

	/**
	 * Initialize the page.
	 */
	function init() {
		bindEvents();

		// DISABLED: Performance monitor was causing the performance issues!
		// Add performance monitor button
		// const $perfButton = $(
		// 	'<button id="show-perf-monitor" class="button" style="margin-left: 10px;">📊 Show Performance Monitor</button>'
		// );
		// $('#generate-titles-btn').after($perfButton);

		// $perfButton.on('click', () => {
		// 	if (!performanceMonitor) {
		// 		performanceMonitor = new PerformanceMonitor();
		// 		$perfButton.text('📊 Monitor Active');
		// 	}
		// });
	}

	/**
	 * Bind event handlers.
	 */
	function bindEvents() {
		// Handle keyword CSV upload
		$('#seo-keyword-upload-form').on('submit', handleKeywordUpload);

		// Handle title generation
		$('#generate-titles-btn').on('click', handleTitleGeneration);

		// Handle export to CSV
		$('#export-csv-btn').on('click', exportToCSV);

		// Handle copy all slugs
		$('#copy-all-btn').on('click', copyAllSlugs);

		// Handle search/filter
		$('#search-titles').on('input', filterTitles);

		// Handle pagination
		$(document).on('click', '.pagination-btn', handlePagination);
		$(document).on('change', '.items-per-page-select', handleItemsPerPageChange);
	}

	/**
	 * Handle keyword CSV upload.
	 */
	function handleKeywordUpload(e) {
		e.preventDefault();

		const fileInput = $('#keyword-csv-file')[0];
		const file = fileInput.files[0];

		if (!file) {
			showUploadStatus('Please select a CSV file.', 'error');
			return;
		}

		const formData = new FormData();
		formData.append('action', seoGeoTitles.uploadAction);
		formData.append('nonce', seoGeoTitles.nonce);
		formData.append('csv_file', file);

		showUploadStatus('Uploading and processing keywords...', 'info');

		$.ajax({
			url: seoGeoTitles.ajaxUrl,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function (response) {
				if (response.success) {
					const count = response.data.count;
					showUploadStatus(
						`✓ Successfully uploaded ${count} keyword${count !== 1 ? 's' : ''}. You can now generate title variations.`,
						'success'
					);
					$('#generate-titles-btn').prop('disabled', false);
				} else {
					showUploadStatus('Error: ' + (response.data.message || 'Upload failed'), 'error');
				}
			},
			error: function (xhr, status, error) {
				showUploadStatus('Error uploading file: ' + error, 'error');
			},
		});
	}

	/**
	 * Handle title generation - triggers loading of first page.
	 */
	function handleTitleGeneration() {
		console.time('TOTAL_GENERATION_TIME');
		console.log('[PERF] User clicked Generate button');
		currentPage = 1;
		searchTerm = '';
		$('#search-titles').val('');
		loadTitlesPage();
	}

	/**
	 * Load titles for a specific page from server.
	 */
	function loadTitlesPage() {
		console.log('[PERF] loadTitlesPage() started');
		const ajaxStartTime = performance.now();
		const $btn = $('#generate-titles-btn');
		const originalText = $btn.text();

		console.log('[PERF] Disabling button and showing status...');
		$btn.prop('disabled', true).text('Loading...');
		showGenerationStatus('Loading titles...', 'info');

		// Show loading state on results section
		if (currentPage === 1 && !searchTerm) {
			console.log('[PERF] Showing results section instantly (no animation)');
			$('#results-section').show(); // Changed from slideDown to instant show
		}

		console.log('[PERF] Setting loading message...');
		$('#titles-list').html('<p style="text-align: center; padding: 40px; color: #666;">Loading titles...</p>');

		console.log('[PERF] Starting AJAX request...');
		$.ajax({
			url: seoGeoTitles.ajaxUrl,
			type: 'POST',
			data: {
				action: seoGeoTitles.generateAction,
				nonce: seoGeoTitles.nonce,
				page: currentPage,
				limit: itemsPerPage,
				search: searchTerm,
			},
			success: function (response) {
				console.log('[PERF] AJAX response received');
				const ajaxEndTime = performance.now();
				const ajaxDuration = Math.round(ajaxEndTime - ajaxStartTime);

				if (response.success) {
					console.log('[PERF] Response successful, processing data...');
					totalCount = response.data.totalCount;
					totalPages = response.data.totalPages;

					// Display performance metrics
					if (response.data.metrics) {
						console.log('=== GEOGRAPHIC TITLES PERFORMANCE METRICS ===');
						console.log('AJAX Round-trip Time:', ajaxDuration + 'ms');
						console.log('Server Metrics:', response.data.metrics);
						console.log('Keywords:', response.data.metrics.keyword_count);
						console.log('Locations:', response.data.metrics.location_count);
						console.log('Total Combinations:', totalCount.toLocaleString());
						console.log('============================================');

						// Show performance warning if slow
						if (response.data.metrics.total_time > 2000) {
							showGenerationStatus(
								`⚠ Slow response (${response.data.metrics.total_time}ms). Check console for details.`,
								'info'
							);
						}
					}

					if (currentPage === 1 && !searchTerm) {
						const serverTime = response.data.metrics ? response.data.metrics.total_time : '?';
						showGenerationStatus(
							`✓ Generated ${totalCount.toLocaleString()} titles in ${serverTime}ms (AJAX: ${ajaxDuration}ms)`,
							'success'
						);
					}

					console.log('[PERF] Starting render...');
					const renderStartTime = performance.now();
					displayTitlesWithPagination(response.data.titles);
					const renderEndTime = performance.now();
					const renderDuration = Math.round(renderEndTime - renderStartTime);

					console.log('[PERF] Browser Render Time:', renderDuration + 'ms');

					console.log('[PERF] Showing results section (if needed)...');
					$('#results-section').show(); // Changed from slideDown to instant show

					console.timeEnd('TOTAL_GENERATION_TIME');
					console.log('[PERF] === ALL OPERATIONS COMPLETE ===');
				} else {
					showGenerationStatus('Error: ' + (response.data.message || 'Generation failed'), 'error');
					$('#titles-list').html(
						'<p style="color: #d63638; padding: 20px;">Error loading titles. Please try again.</p>'
					);
				}

				$btn.prop('disabled', false).text(originalText);
			},
			error: function (xhr, status, error) {
				const ajaxEndTime = performance.now();
				const ajaxDuration = Math.round(ajaxEndTime - ajaxStartTime);
				console.error('AJAX Error after ' + ajaxDuration + 'ms:', error);

				showGenerationStatus('Error loading titles: ' + error, 'error');
				$('#titles-list').html(
					'<p style="color: #d63638; padding: 20px;">Error loading titles. Please try again.</p>'
				);
				$btn.prop('disabled', false).text(originalText);
			},
		});
	}

	/**
	 * Display titles with pagination.
	 */
	function displayTitlesWithPagination(titles) {
		console.log('[DISPLAY] displayTitlesWithPagination called with', titles.length, 'titles');
		const $list = $('#titles-list');
		$list.empty();

		$('#total-count').text(totalCount.toLocaleString());

		if (!titles || titles.length === 0) {
			console.log('[DISPLAY] No titles to display!');
			$list.html('<p style="color: #666;">No titles to display.</p>');
			return;
		}

		console.log('[DISPLAY] Building table with', titles.length, 'rows');

		// Calculate display indices
		const startIndex = (currentPage - 1) * itemsPerPage;
		const endIndex = Math.min(startIndex + titles.length, totalCount);

		// Create table
		const $table = $('<table>', {
			class: 'wp-list-table widefat fixed striped',
			style: 'width: 100%;',
		});

		// Table header
		const $thead = $('<thead>').append(
			$('<tr>').append(
				$('<th>', { style: 'width: 50px;' }).text('#'),
				$('<th>', { style: 'width: 40%;' }).text('Title'),
				$('<th>', { style: 'width: 60%;' }).text('Slug')
			)
		);

		// Table body with current page items
		const $tbody = $('<tbody>');
		titles.forEach((item, index) => {
			const actualIndex = startIndex + index + 1;
			const $row = $('<tr>').append(
				$('<td>').text(actualIndex),
				$('<td>').text(item.title),
				$('<td>').html(
					'<code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: 12px;">' +
						$('<div>').text(item.slug).html() +
						'</code>'
				)
			);
			$tbody.append($row);
		});

		$table.append($thead, $tbody);
		$list.append($table);

		console.log('[DISPLAY] Table appended to DOM');
		console.log('[DISPLAY] #titles-list contents:', $list.html().substring(0, 200));

		// Add pagination controls
		renderPaginationControls(totalPages, startIndex, endIndex, totalCount);
	}

	/**
	 * Render pagination controls.
	 */
	function renderPaginationControls(totalPages, startIndex, endIndex, totalCount) {
		// Render at top
		const $paginationTop = $('#pagination-controls');
		$paginationTop.empty();

		// Render at bottom
		const $paginationBottom = $('#pagination-controls-bottom');
		$paginationBottom.empty();

		if (totalPages <= 1) {
			return;
		}

		const $controls = createPaginationControls(totalPages, startIndex, endIndex, totalCount);
		const $controlsClone = createPaginationControls(totalPages, startIndex, endIndex, totalCount);

		$paginationTop.append($controls);
		$paginationBottom.append($controlsClone);
	}

	/**
	 * Create pagination controls element.
	 */
	function createPaginationControls(totalPages, startIndex, endIndex, totalCount) {
		const $controls = $('<div>', { class: 'pagination-wrapper' });

		// Page info
		const $info = $('<div>', { class: 'pagination-info' }).html(
			`Showing <strong>${startIndex + 1}-${endIndex}</strong> of <strong>${totalCount.toLocaleString()}</strong> titles`
		);

		// Navigation buttons
		const $nav = $('<div>', { class: 'pagination-nav' });

		// First page button
		const $first = $('<button>', {
			class: 'button pagination-btn',
			'data-page': 1,
			disabled: currentPage === 1,
		}).text('« First');

		// Previous button
		const $prev = $('<button>', {
			class: 'button pagination-btn',
			'data-page': currentPage - 1,
			disabled: currentPage === 1,
		}).text('‹ Previous');

		// Page number display
		const $pageNum = $('<span>', {
			class: 'pagination-current',
		}).html(`Page <strong>${currentPage}</strong> of <strong>${totalPages}</strong>`);

		// Next button
		const $next = $('<button>', {
			class: 'button pagination-btn',
			'data-page': currentPage + 1,
			disabled: currentPage === totalPages,
		}).text('Next ›');

		// Last page button
		const $last = $('<button>', {
			class: 'button pagination-btn',
			'data-page': totalPages,
			disabled: currentPage === totalPages,
		}).text('Last »');

		$nav.append($first, $prev, $pageNum, $next, $last);

		// Items per page selector (use class instead of ID to avoid duplicates)
		const $perPage = $('<div>', { class: 'pagination-per-page' }).html(`
			<label>Items per page:</label>
			<select class="items-per-page-select regular-text" style="width: auto;">
				<option value="10" ${itemsPerPage === 10 ? 'selected' : ''}>10</option>
				<option value="20" ${itemsPerPage === 20 ? 'selected' : ''}>20</option>
				<option value="25" ${itemsPerPage === 25 ? 'selected' : ''}>25</option>
				<option value="50" ${itemsPerPage === 50 ? 'selected' : ''}>50</option>
				<option value="100" ${itemsPerPage === 100 ? 'selected' : ''}>100</option>
			</select>
		`);

		$controls.append($info, $nav, $perPage);
		return $controls;
	}

	/**
	 * Handle pagination button clicks.
	 */
	function handlePagination(e) {
		const page = parseInt($(e.target).data('page'));
		if (!isNaN(page) && page > 0 && page <= totalPages) {
			currentPage = page;
			loadTitlesPage();
			// Scroll to top of results (instant, no smooth animation)
			$('#results-section').get(0).scrollIntoView({ behavior: 'auto', block: 'start' });
		}
	}

	/**
	 * Handle items per page change.
	 */
	function handleItemsPerPageChange(e) {
		itemsPerPage = parseInt($(e.target).val());
		currentPage = 1; // Reset to first page
		loadTitlesPage();
	}

	/**
	 * Filter titles based on search input.
	 */
	function filterTitles() {
		searchTerm = $('#search-titles').val().toLowerCase();
		currentPage = 1; // Reset to first page when filtering
		loadTitlesPage();
	}

	/**
	 * Export titles to CSV.
	 */
	function exportToCSV() {
		if (totalCount === 0) {
			alert('No titles to export.');
			return;
		}

		const $btn = $('#export-csv-btn');
		const originalText = $btn.text();

		if (
			totalCount > 5000 &&
			!confirm(
				`You are about to export ${totalCount.toLocaleString()} titles. This may take a moment. Continue?`
			)
		) {
			return;
		}

		$btn.prop('disabled', true).text('Exporting...');

		$.ajax({
			url: seoGeoTitles.ajaxUrl,
			type: 'POST',
			data: {
				action: 'seo_export_geo_titles_csv',
				nonce: seoGeoTitles.nonce,
				search: searchTerm,
			},
			success: function (response) {
				if (response.success) {
					// Create a Blob from the CSV content
					const blob = new Blob([response.data.csv], { type: 'text/csv;charset=utf-8;' });
					const url = URL.createObjectURL(blob);

					// Create a temporary download link
					const link = document.createElement('a');
					link.href = url;
					link.download = response.data.filename;
					link.style.display = 'none';

					// Trigger download
					document.body.appendChild(link);
					link.click();

					// Cleanup
					document.body.removeChild(link);
					URL.revokeObjectURL(url);

					alert(`✓ Successfully exported ${response.data.count.toLocaleString()} titles to ${response.data.filename}`);
				} else {
					alert('Error: ' + (response.data.message || 'Export failed'));
				}

				$btn.prop('disabled', false).text(originalText);
			},
			error: function (xhr, status, error) {
				alert('Error exporting CSV: ' + error);
				$btn.prop('disabled', false).text(originalText);
			},
		});
	}

	/**
	 * Copy all slugs to clipboard.
	 */
	function copyAllSlugs() {
		if (totalCount === 0) {
			alert('No titles to copy.');
			return;
		}

		alert(
			`Note: Copy all ${totalCount.toLocaleString()} slugs will be implemented in the next update. For now, you can copy visible titles manually.`
		);
	}

	/**
	 * Show upload status message.
	 */
	function showUploadStatus(message, type) {
		showStatus('#upload-status', message, type);
	}

	/**
	 * Show generation status message.
	 */
	function showGenerationStatus(message, type) {
		showStatus('#generation-status', message, type);
	}

	/**
	 * Show status message.
	 */
	function showStatus(selector, message, type) {
		const $status = $(selector);
		const typeClass = type === 'error' ? 'notice-error' : type === 'success' ? 'notice-success' : 'notice-info';

		$status.html(`<div class="notice ${typeClass}" style="padding: 10px;"><p>${message}</p></div>`);

		if (type === 'success' || type === 'error') {
			setTimeout(() => {
				$status.fadeOut(() => {
					$status.empty().show();
				});
			}, 5000);
		}
	}

	// Initialize when document is ready
	$(document).ready(init);
})(jQuery);
