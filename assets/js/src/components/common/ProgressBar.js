/**
 * ProgressBar Component
 *
 * Displays a visual progress bar with percentage and current/total counts.
 *
 * @package
 */

import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * ProgressBar component.
 *
 * @param {Object} props          Component props.
 * @param {number} props.current  Current progress value.
 * @param {number} props.total    Total value.
 * @return {Element} The ProgressBar component.
 */
const ProgressBar = ({ current, total }) => {
	const percentage = total > 0 ? Math.round((current / total) * 100) : 0;

	return createElement(
		'div',
		{ className: 'bulk-progress-bar-container' },
		createElement(
			'div',
			{ className: 'bulk-progress-info' },
			createElement(
				'span',
				{ className: 'bulk-progress-text' },
				/* translators: %1$d: current block number, %2$d: total blocks */
				__(`Block ${current} of ${total}`, 'seo-generator')
			),
			createElement(
				'span',
				{ className: 'bulk-progress-percentage' },
				`${percentage}%`
			)
		),
		createElement(
			'div',
			{
				className: 'bulk-progress-bar',
				role: 'progressbar',
				'aria-valuenow': percentage,
				'aria-valuemin': 0,
				'aria-valuemax': 100,
				'aria-label': __('Generation progress', 'seo-generator'),
			},
			createElement('div', {
				className: 'bulk-progress-bar-fill',
				style: { width: `${percentage}%` },
			})
		)
	);
};

export default ProgressBar;
