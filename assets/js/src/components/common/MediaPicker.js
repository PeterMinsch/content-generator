/**
 * MediaPicker Component
 *
 * WordPress Media Library picker for selecting images.
 *
 * @package
 */

import { useState, useEffect, createElement } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import API from '../../services/api';

/**
 * MediaPicker component.
 *
 * @param {Object}   props          Component props.
 * @param {number}   props.value    Image ID.
 * @param {Function} props.onChange Callback when image changes.
 * @param {string}   props.label    Button label (default: 'Select Image').
 * @return {Element} The MediaPicker component.
 */
const MediaPicker = ({ value, onChange, label = 'Select Image' }) => {
	const [image, setImage] = useState(null);
	const [isLoading, setIsLoading] = useState(false);
	const [error, setError] = useState(null);

	// Load image data when value changes
	useEffect(() => {
		if (value && typeof value === 'number') {
			setIsLoading(true);
			setError(null);

			API.fetchImageData(value)
				.then((imageData) => {
					setImage(imageData);
					setIsLoading(false);
				})
				.catch((err) => {
					console.error('Failed to fetch image data:', err);
					setError(__('Unable to load image', 'seo-generator'));
					setImage(null);
					setIsLoading(false);
				});
		} else {
			setImage(null);
			setError(null);
		}
	}, [value]);

	/**
	 * Open WordPress Media Library modal.
	 */
	const openMediaLibrary = () => {
		// Check if wp.media is available
		if (!window.wp || !window.wp.media) {
			console.error('WordPress media library not available');
			alert(
				__(
					'Media library not available. Please refresh the page.',
					'seo-generator'
				)
			);
			return;
		}

		// Create media frame
		const mediaFrame = window.wp.media({
			title: label,
			button: {
				text: __('Select Image', 'seo-generator'),
			},
			multiple: false,
		});

		// Handle image selection
		mediaFrame.on('select', function () {
			const attachment = mediaFrame
				.state()
				.get('selection')
				.first()
				.toJSON();

			// Update component state
			setImage({
				id: attachment.id,
				url: attachment.url,
				thumbnailUrl:
					attachment.sizes?.thumbnail?.url || attachment.url,
				alt: attachment.alt || '',
			});

			// Notify parent component
			onChange(attachment.id);
		});

		// Open the modal
		mediaFrame.open();
	};

	/**
	 * Remove selected image.
	 */
	const removeImage = () => {
		setImage(null);
		setError(null);
		onChange(null);
	};

	// Loading state
	if (isLoading) {
		return createElement(
			'div',
			{ className: 'media-picker media-picker-loading' },
			createElement(Spinner),
			createElement(
				'span',
				{ className: 'loading-text' },
				__('Loading image...', 'seo-generator')
			)
		);
	}

	// Error state
	if (error) {
		return createElement(
			'div',
			{ className: 'media-picker media-picker-error' },
			createElement(
				'p',
				{ className: 'error-message' },
				createElement(
					'span',
					{ className: 'dashicons dashicons-warning' }
				),
				error
			),
			createElement(Button, {
				variant: 'primary',
				onClick: openMediaLibrary,
				children: label,
			})
		);
	}

	// Image selected state
	if (image) {
		return createElement(
			'div',
			{ className: 'media-picker media-picker-selected' },
			createElement('img', {
				src: image.thumbnailUrl,
				alt: image.alt,
				className: 'media-picker-preview',
			}),
			createElement(
				'div',
				{ className: 'media-picker-buttons' },
				createElement(Button, {
					variant: 'secondary',
					onClick: openMediaLibrary,
					children: __('Change Image', 'seo-generator'),
				}),
				createElement(Button, {
					variant: 'link',
					isDestructive: true,
					onClick: removeImage,
					children: __('Remove Image', 'seo-generator'),
				})
			)
		);
	}

	// No image selected state
	return createElement(
		'div',
		{ className: 'media-picker media-picker-empty' },
		createElement(Button, {
			variant: 'secondary',
			onClick: openMediaLibrary,
			children: label,
		})
	);
};

export default MediaPicker;
