/**
 * Column Mapping Handler
 *
 * Handles CSV column mapping interface and validation.
 *
 * @package SEOGenerator
 */

// Wait for DOM to be ready.
document.addEventListener('DOMContentLoaded', () => {
	const uploadForm = document.getElementById('csv-upload-form');
	const columnMappingSection = document.getElementById('column-mapping-section');
	const columnMappingTable = document.getElementById('column-mapping-table');
	const previewTableContainer = document.getElementById('preview-table-container');
	const proceedImportBtn = document.getElementById('proceed-import');
	const cancelMappingBtn = document.getElementById('cancel-mapping');

	if (!uploadForm) {
		return; // Not on import page.
	}

	let currentMappings = {};
	let currentHeaders = [];

	/**
	 * Handle form submission.
	 */
	uploadForm.addEventListener('submit', async (e) => {
		e.preventDefault();

		const formData = new FormData(uploadForm);
		formData.append('action', 'seo_upload_csv');
		formData.append('nonce', seoImportData.nonce);

		try {
			// Upload file via AJAX.
			const uploadResponse = await fetch(seoImportData.ajaxUrl, {
				method: 'POST',
				body: formData,
			});

			const uploadData = await uploadResponse.json();

			if (!uploadData.success) {
				throw new Error(uploadData.data.message || 'File upload failed');
			}

			// Load column mapping.
			await loadColumnMapping();
		} catch (error) {
			console.error('Upload error:', error);
			alert('File upload failed: ' + error.message);
		}
	});

	/**
	 * Load column mapping data via AJAX.
	 */
	async function loadColumnMapping() {
		try {
			const response = await fetch(seoImportData.ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'seo_get_column_mapping',
					nonce: seoImportData.nonce,
				}),
			});

			const data = await response.json();

			if (data.success) {
				currentHeaders = data.data.headers;
				currentMappings = data.data.mappings;

				// Render UI.
				renderColumnMapping(data.data.headers, data.data.mappings);
				renderPreviewTable(data.data.headers, data.data.preview_rows);

				// Show mapping section.
				columnMappingSection.style.display = 'block';
			} else {
				throw new Error(data.data.message || 'Failed to load column mapping');
			}
		} catch (error) {
			console.error('Column mapping error:', error);
			alert('Failed to load column mapping: ' + error.message);
		}
	}

	/**
	 * Render column mapping UI.
	 *
	 * @param {Array} headers CSV column headers.
	 * @param {Object} mappings Auto-detected mappings.
	 */
	function renderColumnMapping(headers, mappings) {
		const table = document.createElement('table');
		table.className = 'wp-list-table widefat fixed striped';

		// Table header.
		const thead = document.createElement('thead');
		thead.innerHTML = `
			<tr>
				<th>CSV Column</th>
				<th>Map To</th>
			</tr>
		`;
		table.appendChild(thead);

		// Table body.
		const tbody = document.createElement('tbody');

		headers.forEach((header, index) => {
			const tr = document.createElement('tr');

			const tdHeader = document.createElement('td');
			tdHeader.textContent = header;
			tr.appendChild(tdHeader);

			const tdMapping = document.createElement('td');
			const select = document.createElement('select');
			select.name = `mapping[${escapeHtml(header)}]`;
			select.className = 'mapping-select';
			select.dataset.column = header;

			// Dropdown options.
			const options = [
				{ value: 'page_title', label: 'Page Title' },
				{ value: 'focus_keyword', label: 'Focus Keyword' },
				{ value: 'topic_category', label: 'Topic Category' },
				{ value: 'image_url', label: 'Image URL' },
				{ value: 'skip', label: 'Skip' },
			];

			options.forEach(option => {
				const optionEl = document.createElement('option');
				optionEl.value = option.value;
				optionEl.textContent = option.label;

				// Pre-select based on auto-detection.
				if (mappings[header] === option.value) {
					optionEl.selected = true;
				}

				select.appendChild(optionEl);
			});

			// Update mappings when changed.
			select.addEventListener('change', (e) => {
				currentMappings[header] = e.target.value;
			});

			tdMapping.appendChild(select);
			tr.appendChild(tdMapping);

			tbody.appendChild(tr);
		});

		table.appendChild(tbody);

		// Clear and append.
		columnMappingTable.innerHTML = '';
		columnMappingTable.appendChild(table);
	}

	/**
	 * Render preview table.
	 *
	 * @param {Array} headers CSV column headers.
	 * @param {Array} previewRows Preview data rows.
	 */
	function renderPreviewTable(headers, previewRows) {
		const table = document.createElement('table');
		table.className = 'wp-list-table widefat fixed striped';

		// Table header.
		const thead = document.createElement('thead');
		const headerRow = document.createElement('tr');
		headers.forEach(header => {
			const th = document.createElement('th');
			th.textContent = header;
			headerRow.appendChild(th);
		});
		thead.appendChild(headerRow);
		table.appendChild(thead);

		// Table body.
		const tbody = document.createElement('tbody');
		previewRows.forEach(row => {
			const tr = document.createElement('tr');
			row.forEach(cell => {
				const td = document.createElement('td');
				td.textContent = cell || '—';
				tr.appendChild(td);
			});
			tbody.appendChild(tr);
		});
		table.appendChild(tbody);

		// Clear and append.
		previewTableContainer.innerHTML = '';
		previewTableContainer.appendChild(table);
	}

	/**
	 * Validate mapping configuration.
	 *
	 * @return {Object} Validation result.
	 */
	function validateMapping() {
		const hasPagTitle = Object.values(currentMappings).includes('page_title');

		if (!hasPagTitle) {
			return {
				valid: false,
				error: 'At least one column must be mapped to "Page Title".',
			};
		}

		return { valid: true };
	}

	/**
	 * Escape HTML to prevent XSS.
	 *
	 * @param {string} text Text to escape.
	 * @return {string} Escaped text.
	 */
	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	// Proceed button click.
	if (proceedImportBtn) {
		proceedImportBtn.addEventListener('click', async () => {
			// Validate mappings.
			const validation = validateMapping();

			if (!validation.valid) {
				alert(validation.error);
				return;
			}

			// Capture import options.
			const generationMode = document.querySelector('input[name="generation_mode"]:checked')?.value || 'drafts_only';
			const checkDuplicates = document.querySelector('input[name="check_duplicates"]')?.checked || false;

			// Capture blocks to generate (if auto_generate mode).
			const blocksCheckboxes = document.querySelectorAll('input[name="blocks_to_generate[]"]:checked');
			const blocksToGenerate = Array.from(blocksCheckboxes).map(cb => cb.value);

			// Disable button during processing.
			proceedImportBtn.disabled = true;
			proceedImportBtn.textContent = 'Processing...';

			try {
				// Step 1: Validate mappings and save options.
				const validateResponse = await fetch(seoImportData.ajaxUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						action: 'seo_validate_mapping',
						nonce: seoImportData.nonce,
						mappings: JSON.stringify(currentMappings),
						generation_mode: generationMode,
						check_duplicates: checkDuplicates ? '1' : '0',
						blocks_to_generate: JSON.stringify(blocksToGenerate),
					}),
				});

				const validateData = await validateResponse.json();

				if (!validateData.success) {
					throw new Error(validateData.data.message || 'Validation failed');
				}

				// Step 2: Parse CSV with CSVParser service.
				const parseResponse = await fetch(seoImportData.ajaxUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						action: 'seo_parse_csv',
						nonce: seoImportData.nonce,
					}),
				});

				const parseData = await parseResponse.json();

				if (!parseData.success) {
					throw new Error(parseData.data.message || 'CSV parsing failed');
				}

				// Display parsing results.
				displayParsingResults(parseData.data);

			} catch (error) {
				console.error('Processing error:', error);
				alert('Processing failed: ' + error.message);

				// Re-enable button.
				proceedImportBtn.disabled = false;
				proceedImportBtn.innerHTML = '<span class="dashicons dashicons-yes" style="vertical-align: middle;"></span> Proceed to Import';
			}
		});
	}

	/**
	 * Display CSV parsing results to user.
	 *
	 * @param {Object} data Parsing results from server.
	 */
	async function displayParsingResults(data) {
		const metadata = data.metadata;
		const errors = data.errors || [];

		let message = `CSV parsed successfully!\n\n`;
		message += `Total rows: ${metadata.total_rows}\n`;
		message += `Valid rows: ${metadata.valid_rows}\n`;
		message += `Invalid rows: ${metadata.invalid_rows}\n`;
		message += `Encoding: ${metadata.encoding}\n`;
		message += `Delimiter: ${metadata.delimiter}\n`;

		if (errors.length > 0) {
			message += `\n${errors.length} row error(s) found:\n`;
			errors.slice(0, 5).forEach(error => {
				message += `- ${error}\n`;
			});
			if (errors.length > 5) {
				message += `...and ${errors.length - 5} more\n`;
			}
			message += `\nValid rows will still be imported.`;
		}

		message += `\n\nProceed with import?`;

		const confirmed = confirm(message);

		if (confirmed) {
			// Start batch import
			await startBatchImport(metadata.valid_rows);
		} else {
			// Re-enable button
			proceedImportBtn.disabled = false;
			proceedImportBtn.innerHTML = '<span class="dashicons dashicons-yes" style="vertical-align: middle;"></span> Proceed to Import';
		}
	}

	/**
	 * Start batch import process.
	 *
	 * @param {number} totalRows Total number of rows to import.
	 */
	async function startBatchImport(totalRows) {
		const totalBatches = Math.ceil(totalRows / 10);

		// Update button text
		proceedImportBtn.textContent = 'Importing...';

		try {
			for (let batchIndex = 0; batchIndex < totalBatches; batchIndex++) {
				// Update progress text
				proceedImportBtn.textContent = `Importing batch ${batchIndex + 1} of ${totalBatches}...`;

				// Get current options
				const checkDuplicates = document.querySelector('input[name="check_duplicates"]')?.checked || false;

				const response = await fetch(seoImportData.ajaxUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						action: 'seo_import_batch',
						nonce: seoImportData.nonce,
						batch_index: batchIndex,
						check_duplicates: checkDuplicates ? '1' : '0',
					}),
				});

				const data = await response.json();

				if (!data.success) {
					throw new Error(data.data.message || 'Batch import failed');
				}

				// Check if complete
				if (data.data.completed) {
					showCompletionSummary(data.data.cumulative);
					break;
				}
			}
		} catch (error) {
			console.error('Import error:', error);
			alert('Import failed: ' + error.message);

			// Re-enable button
			proceedImportBtn.disabled = false;
			proceedImportBtn.innerHTML = '<span class="dashicons dashicons-yes" style="vertical-align: middle;"></span> Proceed to Import';
		}
	}

	/**
	 * Show completion summary.
	 *
	 * @param {Object} results Cumulative import results.
	 */
	function showCompletionSummary(results) {
		let message = `Import Complete!\n\n`;
		message += `Created: ${results.created.length} posts\n`;
		message += `Skipped: ${results.skipped.length} duplicates\n`;
		message += `Errors: ${results.errors.length} rows\n\n`;

		if (results.errors.length > 0) {
			message += `Errors:\n`;
			results.errors.slice(0, 5).forEach(error => {
				message += `- Row ${error.row}: ${error.error}\n`;
			});
			if (results.errors.length > 5) {
				message += `...and ${results.errors.length - 5} more\n`;
			}
		}

		message += `\nView created posts in the SEO Pages admin screen.`;

		alert(message);

		// Re-enable and reset button
		proceedImportBtn.disabled = false;
		proceedImportBtn.innerHTML = '<span class="dashicons dashicons-yes" style="vertical-align: middle;"></span> Proceed to Import';

		// Optionally redirect to posts list
		if (confirm('Would you like to view the created posts?')) {
			window.location.href = 'edit.php?post_type=seo-page';
		}
	}

	// Cancel button click.
	if (cancelMappingBtn) {
		cancelMappingBtn.addEventListener('click', () => {
			// Hide mapping section and reset.
			columnMappingSection.style.display = 'none';
			currentMappings = {};
			currentHeaders = [];
			uploadForm.reset();
		});
	}
});
