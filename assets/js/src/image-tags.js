/**
 * Image Tag Management
 *
 * Handles adding, removing, and bulk managing tags for library images.
 *
 * @package SEOGenerator
 */

import apiFetch from '@wordpress/api-fetch';

document.addEventListener('DOMContentLoaded', () => {
	const imageLibraryWrap = document.querySelector('.seo-image-library-wrap');

	if (!imageLibraryWrap) {
		return; // Not on image library page.
	}

	let allTags = [];
	let activeTagInput = null;

	/**
	 * Initialize tag management.
	 */
	async function init() {
		// Load all available tags for autocomplete.
		await loadAllTags();

		// Event delegation for tag removal.
		imageLibraryWrap.addEventListener('click', handleTagRemoval);

		// Event delegation for Edit Tags buttons.
		imageLibraryWrap.addEventListener('click', handleEditTagsClick);

		// Bulk actions handler.
		const bulkApplyButton = document.querySelector('.action');
		if (bulkApplyButton) {
			bulkApplyButton.addEventListener('click', handleBulkActions);
		}

		// Update selected count when checkboxes change.
		imageLibraryWrap.addEventListener('change', updateSelectedCount);
	}

	/**
	 * Load all image tags from REST API.
	 */
	async function loadAllTags() {
		try {
			const tags = await apiFetch({ path: '/wp/v2/image-tags?per_page=100' });
			allTags = tags.map(tag => ({
				id: tag.id,
				name: tag.name,
				slug: tag.slug,
			}));
		} catch (error) {
			console.error('Failed to load tags:', error);
			allTags = [];
		}
	}

	/**
	 * Handle tag removal button click.
	 *
	 * @param {Event} e Click event.
	 */
	async function handleTagRemoval(e) {
		if (!e.target.closest('.remove-tag')) {
			return;
		}

		e.preventDefault();
		e.stopPropagation();

		const button = e.target.closest('.remove-tag');
		const imageId = button.dataset.imageId;
		const tagSlug = button.dataset.tagSlug;

		if (!imageId || !tagSlug) {
			return;
		}

		// Disable button during request.
		button.disabled = true;

		try {
			await apiFetch({
				path: `/seo-generator/v1/images/${imageId}/tags`,
				method: 'PUT',
				data: {
					add: [],
					remove: [tagSlug],
				},
			});

			// Remove tag badge from UI.
			const tagBadge = button.closest('.image-tag');
			if (tagBadge) {
				tagBadge.remove();
			}

			// Show "No tags" if no tags left.
			const tagsContainer = button.closest('.image-tags');
			if (tagsContainer && !tagsContainer.querySelector('.image-tag')) {
				tagsContainer.innerHTML = '<span class="no-tags">No tags</span>';
			}
		} catch (error) {
			console.error('Failed to remove tag:', error);
			alert('Failed to remove tag. Please try again.');
			button.disabled = false;
		}
	}

	/**
	 * Handle Edit Tags button click.
	 *
	 * @param {Event} e Click event.
	 */
	function handleEditTagsClick(e) {
		if (!e.target.closest('.edit-tags-button')) {
			return;
		}

		e.preventDefault();

		const button = e.target.closest('.edit-tags-button');
		const imageId = button.dataset.imageId;
		const filename = button.dataset.filename;

		// Close any open tag input.
		if (activeTagInput) {
			activeTagInput.remove();
			activeTagInput = null;
		}

		// Create tag input field.
		const tagsContainer = button.closest('.image-grid-item').querySelector('.image-tags');
		const tagInput = createTagInput(imageId, filename);

		tagsContainer.appendChild(tagInput);
		tagInput.querySelector('input').focus();

		activeTagInput = tagInput;
	}

	/**
	 * Create tag input element with autocomplete.
	 *
	 * @param {string} imageId Image ID.
	 * @param {string} filename Image filename.
	 * @return {HTMLElement} Tag input element.
	 */
	function createTagInput(imageId, filename) {
		const wrapper = document.createElement('div');
		wrapper.className = 'tag-input-wrapper';

		const suggestions = getSuggestedTags(filename);

		wrapper.innerHTML = `
			<div class="tag-input-container">
				<input
					type="text"
					class="tag-input"
					placeholder="Type tag name..."
					data-image-id="${imageId}"
				/>
				<button type="button" class="button button-small add-tag-submit">Add</button>
				<button type="button" class="button button-small tag-input-cancel">Cancel</button>
			</div>
			${suggestions.length > 0 ? `
				<div class="tag-suggestions">
					<small>Suggestions from filename:</small>
					${suggestions.map(tag => `<button type="button" class="tag-suggestion" data-tag-slug="${tag}">${tag}</button>`).join('')}
				</div>
			` : ''}
			<div class="tag-autocomplete" style="display: none;"></div>
		`;

		// Add event listeners.
		const input = wrapper.querySelector('.tag-input');
		const submitButton = wrapper.querySelector('.add-tag-submit');
		const cancelButton = wrapper.querySelector('.tag-input-cancel');
		const autocompleteDiv = wrapper.querySelector('.tag-autocomplete');

		input.addEventListener('input', () => showAutocomplete(input, autocompleteDiv));
		input.addEventListener('keydown', (e) => {
			if (e.key === 'Enter') {
				e.preventDefault();
				submitTag(imageId, input.value, wrapper);
			} else if (e.key === 'Escape') {
				wrapper.remove();
				activeTagInput = null;
			}
		});

		submitButton.addEventListener('click', () => submitTag(imageId, input.value, wrapper));
		cancelButton.addEventListener('click', () => {
			wrapper.remove();
			activeTagInput = null;
		});

		// Suggestion buttons.
		wrapper.querySelectorAll('.tag-suggestion').forEach(btn => {
			btn.addEventListener('click', () => {
				input.value = btn.dataset.tagSlug;
				submitTag(imageId, btn.dataset.tagSlug, wrapper);
			});
		});

		return wrapper;
	}

	/**
	 * Get suggested tags from filename.
	 *
	 * @param {string} filename Image filename.
	 * @return {Array} Suggested tag slugs.
	 */
	function getSuggestedTags(filename) {
		// Remove extension and split by - or _.
		const name = filename.replace(/\.[^.]+$/, '');
		const parts = name.toLowerCase().split(/[-_]/);

		// Match against existing tag slugs.
		const tagSlugs = allTags.map(t => t.slug);
		return parts.filter(part => tagSlugs.includes(part));
	}

	/**
	 * Show autocomplete suggestions.
	 *
	 * @param {HTMLInputElement} input Input element.
	 * @param {HTMLElement} autocompleteDiv Autocomplete container.
	 */
	function showAutocomplete(input, autocompleteDiv) {
		const query = input.value.toLowerCase().trim();

		if (query.length < 2) {
			autocompleteDiv.style.display = 'none';
			return;
		}

		const matches = allTags.filter(tag =>
			tag.name.toLowerCase().includes(query) || tag.slug.includes(query)
		).slice(0, 10);

		if (matches.length === 0) {
			autocompleteDiv.style.display = 'none';
			return;
		}

		autocompleteDiv.innerHTML = matches.map(tag =>
			`<div class="autocomplete-item" data-tag-slug="${tag.slug}">${tag.name}</div>`
		).join('');

		autocompleteDiv.style.display = 'block';

		// Add click handlers.
		autocompleteDiv.querySelectorAll('.autocomplete-item').forEach(item => {
			item.addEventListener('click', () => {
				input.value = item.dataset.tagSlug;
				autocompleteDiv.style.display = 'none';
				submitTag(input.dataset.imageId, item.dataset.tagSlug, input.closest('.tag-input-wrapper'));
			});
		});
	}

	/**
	 * Submit new tag.
	 *
	 * @param {string} imageId Image ID.
	 * @param {string} tagValue Tag value/slug.
	 * @param {HTMLElement} wrapper Tag input wrapper.
	 */
	async function submitTag(imageId, tagValue, wrapper) {
		const tag = tagValue.trim().toLowerCase();

		if (!tag) {
			return;
		}

		try {
			const response = await apiFetch({
				path: `/seo-generator/v1/images/${imageId}/tags`,
				method: 'PUT',
				data: {
					add: [tag],
					remove: [],
				},
			});

			// Refresh tags display.
			const tagsContainer = wrapper.closest('.image-tags');
			renderTags(tagsContainer, response.tags);

			// Remove input.
			wrapper.remove();
			activeTagInput = null;
		} catch (error) {
			console.error('Failed to add tag:', error);
			alert('Failed to add tag. Please try again.');
		}
	}

	/**
	 * Render tags in container.
	 *
	 * @param {HTMLElement} container Tags container.
	 * @param {Array} tags Array of tag objects.
	 */
	function renderTags(container, tags) {
		const imageId = container.dataset.imageId;

		if (!tags || tags.length === 0) {
			container.innerHTML = '<span class="no-tags">No tags</span>';
			return;
		}

		container.innerHTML = tags.map(tag => `
			<span class="image-tag" data-tag-id="${tag.id}" data-tag-slug="${tag.slug}">
				${escapeHtml(tag.name)}
				<button
					type="button"
					class="remove-tag"
					data-image-id="${imageId}"
					data-tag-slug="${tag.slug}"
					aria-label="Remove tag"
					title="Remove tag"
				>
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</span>
		`).join('');
	}

	/**
	 * Handle bulk actions.
	 *
	 * @param {Event} e Click event.
	 */
	async function handleBulkActions(e) {
		e.preventDefault();

		const action = document.querySelector('#bulk-action-selector-top').value;
		const selectedImages = getSelectedImageIds();

		if (selectedImages.length === 0) {
			alert('Please select images first.');
			return;
		}

		if (action === 'add-tags') {
			const tags = prompt('Enter tags to add (comma-separated):');
			if (!tags) return;

			const tagArray = tags.split(',').map(t => t.trim().toLowerCase());
			await bulkUpdateTags(selectedImages, tagArray, []);
		} else if (action === 'remove-tags') {
			const tags = prompt('Enter tags to remove (comma-separated):');
			if (!tags) return;

			const tagArray = tags.split(',').map(t => t.trim().toLowerCase());
			await bulkUpdateTags(selectedImages, [], tagArray);
		} else if (action === 'delete') {
			const count = selectedImages.length;
			const confirmed = confirm(
				`Are you sure you want to permanently delete ${count} image${count > 1 ? 's' : ''}?\n\nThis action cannot be undone!`
			);

			if (!confirmed) return;

			await bulkDeleteImages(selectedImages);
		}
	}

	/**
	 * Get selected image IDs from checkboxes.
	 *
	 * @return {Array} Array of image IDs.
	 */
	function getSelectedImageIds() {
		const checkboxes = document.querySelectorAll('.image-select:checked');
		return Array.from(checkboxes).map(cb => cb.value);
	}

	/**
	 * Bulk update tags for multiple images.
	 *
	 * @param {Array} imageIds Image IDs.
	 * @param {Array} add Tags to add.
	 * @param {Array} remove Tags to remove.
	 */
	async function bulkUpdateTags(imageIds, add, remove) {
		try {
			const response = await apiFetch({
				path: '/seo-generator/v1/images/bulk-tags',
				method: 'POST',
				data: {
					image_ids: imageIds,
					add,
					remove,
				},
			});

			alert(response.message);
			window.location.reload();
		} catch (error) {
			console.error('Bulk update failed:', error);
			alert('Failed to update tags. Please try again.');
		}
	}

	/**
	 * Bulk delete images.
	 *
	 * @param {Array} imageIds Image IDs to delete.
	 */
	async function bulkDeleteImages(imageIds) {
		try {
			const response = await apiFetch({
				path: '/seo-generator/v1/images/bulk-delete',
				method: 'POST',
				data: {
					image_ids: imageIds,
				},
			});

			alert(response.message);
			window.location.reload();
		} catch (error) {
			console.error('Bulk delete failed:', error);
			alert('Failed to delete images. Please try again.');
		}
	}

	/**
	 * Update selected count display.
	 */
	function updateSelectedCount() {
		const count = document.querySelectorAll('.image-select:checked').length;
		const countDisplay = document.querySelector('.selected-count');

		if (countDisplay) {
			if (count > 0) {
				countDisplay.style.display = 'inline';
				countDisplay.querySelector('strong').textContent = count;
			} else {
				countDisplay.style.display = 'none';
			}
		}

		// Enable/disable bulk actions dropdown.
		const bulkActionsSelect = document.querySelector('#bulk-action-selector-top');
		const bulkApplyButton = document.querySelector('.action');

		if (bulkActionsSelect) {
			bulkActionsSelect.disabled = count === 0;
		}
		if (bulkApplyButton) {
			bulkApplyButton.disabled = count === 0;
		}
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

	// Initialize.
	init();
});
