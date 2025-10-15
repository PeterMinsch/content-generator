/**
 * Image Upload Handler
 *
 * Handles drag-and-drop and file input upload for image library.
 *
 * @package SEOGenerator
 */

import apiFetch from '@wordpress/api-fetch';

// Wait for DOM to be ready.
document.addEventListener('DOMContentLoaded', () => {
	const uploadArea = document.getElementById('seo-upload-area');
	const fileInput = document.getElementById('seo-file-input');
	const folderInput = document.getElementById('seo-folder-input');
	const uploadFilesBtn = document.getElementById('seo-upload-files-btn');
	const uploadFolderBtn = document.getElementById('seo-upload-folder-btn');
	const folderNotSupported = document.getElementById('seo-folder-not-supported');
	const progressContainer = document.getElementById('seo-upload-progress');
	const uploadList = document.getElementById('seo-upload-list');
	const uploadCount = document.getElementById('seo-upload-count');

	if (!uploadArea || !fileInput) {
		return; // Not on image library page.
	}

	let totalUploaded = 0;
	let filesQueue = [];

	/**
	 * Check if browser supports folder upload.
	 *
	 * @return {boolean} Whether browser supports webkitdirectory.
	 */
	function supportsFolderUpload() {
		const input = document.createElement('input');
		return 'webkitdirectory' in input || 'directory' in input;
	}

	// Check folder upload support and show/hide button.
	if (!supportsFolderUpload()) {
		if (uploadFolderBtn) {
			uploadFolderBtn.disabled = true;
			uploadFolderBtn.title = 'Folder upload not supported in this browser';
		}
	}

	/**
	 * Handle files selected/dropped.
	 *
	 * @param {FileList} files Files to upload.
	 * @param {boolean} isFolder Whether files are from folder upload.
	 */
	function handleFiles(files, isFolder = false) {
		const fileArray = Array.from(files);

		// Group files by folder if this is a folder upload.
		if (isFolder) {
			const folderGroups = groupFilesByFolder(fileArray);

			// Create queue with folder information.
			filesQueue = [];
			Object.entries(folderGroups).forEach(([folderName, folderFiles]) => {
				folderFiles.forEach(file => {
					if (validateFile(file)) {
						filesQueue.push({
							file: file,
							folderName: folderName
						});
					}
				});
			});
		} else {
			// Individual file upload (no folder info).
			const validFiles = fileArray.filter(validateFile);
			filesQueue = validFiles.map(file => ({ file: file, folderName: null }));
		}

		if (filesQueue.length === 0) {
			alert('No valid image files selected. Supported formats: JPG, PNG, WEBP');
			return;
		}

		// Show progress container.
		progressContainer.style.display = 'block';

		// Reset counter.
		totalUploaded = 0;
		updateUploadCount();

		// Upload files sequentially.
		uploadNextFile();
	}

	/**
	 * Group files by their immediate parent folder.
	 *
	 * @param {File[]} files Files with webkitRelativePath property.
	 * @return {Object} Object mapping folder names to file arrays.
	 */
	function groupFilesByFolder(files) {
		const folders = {};

		files.forEach(file => {
			// webkitRelativePath contains: "folder/subfolder/image.jpg"
			// We want the immediate parent folder.
			let folderName = 'root';

			if (file.webkitRelativePath) {
				const pathParts = file.webkitRelativePath.split('/');
				// Get immediate parent folder (second to last part).
				if (pathParts.length >= 2) {
					folderName = pathParts[pathParts.length - 2];
				}
			}

			if (!folders[folderName]) {
				folders[folderName] = [];
			}
			folders[folderName].push(file);
		});

		return folders;
	}

	/**
	 * Validate file type and size.
	 *
	 * @param {File} file File to validate.
	 * @return {boolean} Whether file is valid.
	 */
	function validateFile(file) {
		const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
		const maxSize = seoImageUpload.maxSize;

		if (!allowedTypes.includes(file.type)) {
			console.warn('Invalid file type:', file.name, file.type);
			return false;
		}

		if (file.size > maxSize) {
			console.warn('File too large:', file.name, formatBytes(file.size));
			return false;
		}

		return true;
	}

	/**
	 * Upload next file in queue.
	 */
	function uploadNextFile() {
		if (filesQueue.length === 0) {
			// All files uploaded.
			setTimeout(() => {
				alert(`${totalUploaded} image(s) uploaded successfully! Refreshing page...`);
				window.location.reload();
			}, 1000);
			return;
		}

		const fileItem = filesQueue.shift();
		uploadFile(fileItem.file, fileItem.folderName);
	}

	/**
	 * Upload single file.
	 *
	 * @param {File} file File to upload.
	 * @param {string|null} folderName Folder name if from folder upload.
	 */
	async function uploadFile(file, folderName = null) {
		// Create progress item.
		const progressItem = createProgressItem(file, folderName);
		uploadList.appendChild(progressItem);

		const formData = new FormData();
		formData.append('file', file);

		// Add folder name if present.
		if (folderName) {
			console.log('Uploading file with folder name:', folderName);
			formData.append('folder_name', folderName);
		} else {
			console.log('Uploading file without folder name');
		}

		try {
			// Upload via REST API.
			const response = await fetch(seoImageUpload.endpoint, {
				method: 'POST',
				headers: {
					'X-WP-Nonce': seoImageUpload.nonce,
				},
				body: formData,
			});

			const data = await response.json();

			if (!response.ok) {
				throw new Error(data.message || 'Upload failed');
			}

			// Mark as success.
			markSuccess(progressItem);
			totalUploaded++;
			updateUploadCount();

			// Upload next file.
			uploadNextFile();
		} catch (error) {
			// Mark as error.
			markError(progressItem, error.message);

			// Continue with next file.
			uploadNextFile();
		}
	}

	/**
	 * Create progress item element.
	 *
	 * @param {File} file File being uploaded.
	 * @param {string|null} folderName Folder name if from folder upload.
	 * @return {HTMLElement} Progress item element.
	 */
	function createProgressItem(file, folderName = null) {
		const item = document.createElement('div');
		item.className = 'seo-upload-item';

		const folderBadge = folderName
			? `<span class="seo-upload-folder-badge" style="background: #0073aa; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-right: 5px;">${escapeHtml(folderName)}</span>`
			: '';

		item.innerHTML = `
			<div class="seo-upload-item-info">
				${folderBadge}
				<span class="seo-upload-filename">${escapeHtml(file.name)}</span>
				<span class="seo-upload-filesize">(${formatBytes(file.size)})</span>
			</div>
			<div class="seo-upload-status">
				<span class="dashicons dashicons-update spin"></span>
				<span class="seo-upload-status-text">Uploading...</span>
			</div>
		`;

		return item;
	}

	/**
	 * Mark upload as successful.
	 *
	 * @param {HTMLElement} item Progress item element.
	 */
	function markSuccess(item) {
		const status = item.querySelector('.seo-upload-status');
		status.innerHTML = `
			<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
			<span class="seo-upload-status-text">Success</span>
		`;
		item.classList.add('success');
	}

	/**
	 * Mark upload as failed.
	 *
	 * @param {HTMLElement} item Progress item element.
	 * @param {string} errorMessage Error message.
	 */
	function markError(item, errorMessage) {
		const status = item.querySelector('.seo-upload-status');
		status.innerHTML = `
			<span class="dashicons dashicons-no-alt" style="color: #dc3232;"></span>
			<span class="seo-upload-status-text">Error: ${escapeHtml(errorMessage)}</span>
		`;
		item.classList.add('error');
	}

	/**
	 * Update upload count display.
	 */
	function updateUploadCount() {
		uploadCount.textContent = totalUploaded;
	}

	/**
	 * Format bytes to human-readable string.
	 *
	 * @param {number} bytes File size in bytes.
	 * @return {string} Formatted size.
	 */
	function formatBytes(bytes) {
		if (bytes === 0) return '0 Bytes';
		const k = 1024;
		const sizes = ['Bytes', 'KB', 'MB', 'GB'];
		const i = Math.floor(Math.log(bytes) / Math.log(k));
		return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
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

	// Button click listeners.
	if (uploadFilesBtn) {
		uploadFilesBtn.addEventListener('click', (e) => {
			e.stopPropagation();
			fileInput.click();
		});
	}

	if (uploadFolderBtn) {
		uploadFolderBtn.addEventListener('click', (e) => {
			e.stopPropagation();
			if (!supportsFolderUpload()) {
				if (folderNotSupported) {
					folderNotSupported.style.display = 'block';
				}
				return;
			}
			if (folderNotSupported) {
				folderNotSupported.style.display = 'none';
			}
			folderInput.click();
		});
	}

	// Drag and drop event listeners.
	uploadArea.addEventListener('dragover', (e) => {
		e.preventDefault();
		e.stopPropagation();
		uploadArea.classList.add('drag-over');
	});

	uploadArea.addEventListener('dragleave', (e) => {
		e.preventDefault();
		e.stopPropagation();
		uploadArea.classList.remove('drag-over');
	});

	uploadArea.addEventListener('drop', (e) => {
		e.preventDefault();
		e.stopPropagation();
		uploadArea.classList.remove('drag-over');

		const files = e.dataTransfer.files;
		handleFiles(files, false);
	});

	// File input change listener.
	fileInput.addEventListener('change', (e) => {
		const files = e.target.files;
		handleFiles(files, false);

		// Reset file input.
		fileInput.value = '';
	});

	// Folder input change listener.
	if (folderInput) {
		folderInput.addEventListener('change', (e) => {
			const files = e.target.files;
			handleFiles(files, true);

			// Reset folder input.
			folderInput.value = '';
		});
	}
});
