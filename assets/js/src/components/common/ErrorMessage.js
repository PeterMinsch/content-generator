/**
 * ErrorMessage Component
 *
 * Displays error messages with retry functionality.
 *
 * @package
 */

import { createElement } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * ErrorMessage component.
 *
 * @param {Object}   props          Component props.
 * @param {string}   props.message  Error message to display.
 * @param {Function} props.onRetry  Callback when retry button is clicked.
 * @param {boolean}  props.showRetry Whether to show retry button.
 * @return {Element} The ErrorMessage component.
 */
const ErrorMessage = ({ message, onRetry, showRetry = true }) => {
	if (!message) {
		return null;
	}

	return createElement(
		'div',
		{ className: 'block-error-message' },
		createElement(
			'span',
			{ className: 'dashicons dashicons-warning error-icon' }
		),
		createElement('span', { className: 'error-text' }, message),
		showRetry &&
			onRetry &&
			createElement(Button, {
				variant: 'link',
				onClick: onRetry,
				className: 'error-retry-button',
				children: __('Retry', 'seo-generator'),
			})
	);
};

export default ErrorMessage;
