/**
 * FieldError Component
 *
 * Displays validation error messages for form fields.
 *
 * @package
 */

import { createElement } from '@wordpress/element';

/**
 * FieldError component.
 *
 * @param {Object}      props       Component props.
 * @param {string|null} props.error Error message to display.
 * @param {string}      props.id    Optional ID for aria-describedby linking.
 * @return {Element|null} The FieldError component or null if no error.
 */
const FieldError = ({ error, id }) => {
	if (!error) {
		return null;
	}

	return createElement(
		'div',
		{
			className: 'field-error-message',
			id,
			role: 'alert',
			'aria-live': 'polite',
		},
		createElement('span', { className: 'dashicons dashicons-warning' }),
		createElement('span', { className: 'error-text' }, error)
	);
};

export default FieldError;
