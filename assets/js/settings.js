/**
 * Settings Page JavaScript
 *
 * Handles Test Connection functionality for OpenAI API settings.
 *
 * @param $
 * @package
 */

(function ($) {
	'use strict';

	/**
	 * Initialize settings page functionality.
	 */
	function initSettings() {
		// Test Connection button.
		const testButton = $('#test-connection-btn');
		const testResults = $('#test-results');
		const apiKeyInput = $('#api_key');
		const temperatureSlider = $('#temperature');
		const temperatureValue = $('#temperature-value');

		// Update temperature value display when slider changes.
		if (temperatureSlider.length && temperatureValue.length) {
			temperatureSlider.on('input', function () {
				temperatureValue.text($(this).val());
			});
		}

		// Handle Test Connection button click.
		if (testButton.length) {
			testButton.on('click', function (e) {
				e.preventDefault();
				testApiConnection();
			});
		}

		// Initialize default image picker.
		initDefaultImagePicker();

		/**
		 * Test API connection with OpenAI.
		 */
		function testApiConnection() {
			const apiKey = apiKeyInput.val();

			// Validate API key is provided.
			if (!apiKey || apiKey.trim() === '') {
				showError('Please enter an API key first.');
				return;
			}

			// Check if it's the masked value.
			if (apiKey.startsWith('***')) {
				showError(
					'Please enter your actual API key to test the connection.'
				);
				return;
			}

			// Disable button and show loading state.
			testButton.prop('disabled', true).text('Testing...');
			testResults.html(
				'<span class="spinner is-active" style="float: none; margin: 0;"></span>'
			);

			// Make AJAX request.
			$.ajax({
				url: seoGeneratorSettings.ajaxUrl,
				type: 'POST',
				data: {
					action: 'seo_generator_test_connection',
					api_key: apiKey,
					nonce: seoGeneratorSettings.nonce,
				},
				success(response) {
					if (response.success) {
						showSuccess(
							response.data.message,
							response.data.details
						);
					} else {
						showError(
							response.data.message ||
								'Connection failed. Please try again.'
						);
					}
				},
				error(xhr, status, error) {
					let errorMessage = 'Connection failed. Please try again.';

					if (
						xhr.responseJSON &&
						xhr.responseJSON.data &&
						xhr.responseJSON.data.message
					) {
						errorMessage = xhr.responseJSON.data.message;
					} else if (error) {
						errorMessage = 'Error: ' + error;
					}

					showError(errorMessage);
				},
				complete() {
					// Re-enable button and restore text.
					testButton.prop('disabled', false).text('Test Connection');
				},
			});
		}

		/**
		 * Show success message.
		 *
		 * @param {string} message Success message.
		 * @param {Object} details Connection details.
		 */
		function showSuccess(message, details) {
			let html =
				'<div class="notice notice-success inline" style="margin: 10px 0; padding: 10px;">';
			html +=
				'<p><span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> ';
			html += '<strong>' + escapeHtml(message) + '</strong></p>';

			if (details) {
				html +=
					'<p style="margin: 5px 0 0 25px; color: #666; font-size: 13px;">';
				html += 'Model: ' + escapeHtml(details.model);
				html += ' | Tokens used: ' + escapeHtml(details.tokens);
				html += '</p>';
			}

			html += '</div>';

			testResults.html(html);

			// Auto-hide after 10 seconds.
			setTimeout(function () {
				testResults.fadeOut(300, function () {
					$(this).html('').show();
				});
			}, 10000);
		}

		/**
		 * Show error message.
		 *
		 * @param {string} message Error message.
		 */
		function showError(message) {
			let html =
				'<div class="notice notice-error inline" style="margin: 10px 0; padding: 10px;">';
			html +=
				'<p><span class="dashicons dashicons-warning" style="color: #dc3232;"></span> ';
			html += '<strong>' + escapeHtml(message) + '</strong></p>';
			html += '</div>';

			testResults.html(html);

			// Auto-hide after 10 seconds.
			setTimeout(function () {
				testResults.fadeOut(300, function () {
					$(this).html('').show();
				});
			}, 10000);
		}

		/**
		 * Escape HTML to prevent XSS.
		 *
		 * @param {string} text Text to escape.
		 * @return {string} Escaped text.
		 */
		function escapeHtml(text) {
			if (typeof text !== 'string') {
				text = String(text);
			}

			const map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;',
			};

			return text.replace(/[&<>"']/g, function (m) {
				return map[m];
			});
		}
	}

	/**
	 * Initialize default image picker using wp.media.
	 */
	function initDefaultImagePicker() {
		const selectButton = $('#select_default_image');
		const removeButton = $('#remove_default_image');
		const imageIdInput = $('#default_image_id');
		const previewContainer = $('.default-image-preview');

		// Only initialize if elements exist.
		if (!selectButton.length || !imageIdInput.length) {
			return;
		}

		let mediaFrame;

		// Handle select button click.
		selectButton.on('click', function (e) {
			e.preventDefault();

			// If media frame already exists, open it.
			if (mediaFrame) {
				mediaFrame.open();
				return;
			}

			// Create new media frame.
			mediaFrame = wp.media({
				title: 'Select Default Hero Image',
				button: {
					text: 'Use this image',
				},
				multiple: false,
				library: {
					type: 'image',
				},
			});

			// Handle image selection.
			mediaFrame.on('select', function () {
				const attachment = mediaFrame
					.state()
					.get('selection')
					.first()
					.toJSON();

				// Update hidden input with attachment ID.
				imageIdInput.val(attachment.id);

				// Update preview.
				const imageUrl =
					attachment.sizes && attachment.sizes.medium
						? attachment.sizes.medium.url
						: attachment.url;
				previewContainer.html(
					'<img src="' +
						imageUrl +
						'" style="max-width: 300px; height: auto;" />'
				);

				// Show remove button.
				removeButton.show();
			});

			// Open media frame.
			mediaFrame.open();
		});

		// Handle remove button click.
		if (removeButton.length) {
			removeButton.on('click', function (e) {
				e.preventDefault();

				// Clear input and preview.
				imageIdInput.val('');
				previewContainer.empty();

				// Hide remove button.
				$(this).hide();
			});
		}
	}

	// Initialize when document is ready.
	$(document).ready(function () {
		initSettings();
	});
})(jQuery);
