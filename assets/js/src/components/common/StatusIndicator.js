/**
 * StatusIndicator Component
 *
 * Visual status indicator for content blocks using WordPress Dashicons.
 *
 * @package
 */

import { createElement } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * StatusIndicator component.
 *
 * @param {Object} props        Component props.
 * @param {string} props.status Block status (not_generated|generating|generated|failed|edited).
 * @return {Element} The StatusIndicator component.
 */
const StatusIndicator = ({ status = 'not_generated' }) => {
	const getIndicator = () => {
		switch (status) {
			case 'generating':
				return createElement(Spinner, {
					className: 'status-spinner',
				});

			case 'generated':
				return createElement('span', {
					className: 'dashicons dashicons-yes status-icon status-generated',
					'aria-label': __('Generated', 'seo-generator'),
				});

			case 'failed':
				return createElement('span', {
					className: 'dashicons dashicons-no status-icon status-failed',
					'aria-label': __('Failed', 'seo-generator'),
				});

			case 'edited':
				return createElement('span', {
					className: 'dashicons dashicons-edit status-icon status-edited',
					'aria-label': __('Edited', 'seo-generator'),
				});

			default: // not_generated
				return createElement('span', {
					className: 'dashicons dashicons-marker status-icon status-not-generated',
					'aria-label': __('Not Generated', 'seo-generator'),
				});
		}
	};

	const getLabel = () => {
		switch (status) {
			case 'generating':
				return __('Generating…', 'seo-generator');
			case 'generated':
				return __('Generated', 'seo-generator');
			case 'failed':
				return __('Failed', 'seo-generator');
			case 'edited':
				return __('Edited', 'seo-generator');
			default:
				return __('Not Generated', 'seo-generator');
		}
	};

	return createElement(
		'div',
		{ className: `status-indicator status-${status}` },
		getIndicator(),
		createElement('span', { className: 'status-label' }, getLabel())
	);
};

export default StatusIndicator;
