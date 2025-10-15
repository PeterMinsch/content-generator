/**
 * CompletionSummary Component
 *
 * Displays summary after bulk generation completes.
 *
 * @package
 */

import { createElement } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BLOCK_NAMES } from './BlockProgressList';

/**
 * Format cost as currency.
 *
 * @param {number} cost Cost in dollars.
 * @return {string} Formatted cost.
 */
const formatCost = (cost) => {
	return `$${cost.toFixed(4)}`;
};

/**
 * CompletionSummary component.
 *
 * @param {Object}   props              Component props.
 * @param {boolean}  props.cancelled    Whether generation was cancelled.
 * @param {number}   props.totalBlocks  Total number of blocks.
 * @param {number}   props.generated    Number of successfully generated blocks.
 * @param {Array}    props.failed       Array of failed blocks {blockType, error}.
 * @param {number}   props.totalCost    Total cost of generation.
 * @param {Function} props.onClose      Callback when Close button clicked.
 * @param {Function} props.onRetryFailed Callback when Retry Failed button clicked.
 * @return {Element} The CompletionSummary component.
 */
const CompletionSummary = ({
	cancelled = false,
	totalBlocks = 12,
	generated = 0,
	failed = [],
	totalCost = 0,
	onClose,
	onRetryFailed,
}) => {
	const hasFailures = failed.length > 0;
	const title = cancelled
		? __('Generation Cancelled', 'seo-generator')
		: __('Generation Complete!', 'seo-generator');

	return createElement(
		'div',
		{ className: 'bulk-completion-summary' },
		createElement(
			'h2',
			{ className: 'completion-title' },
			title
		),
		createElement(
			'div',
			{ className: 'completion-stats' },
			createElement(
				'div',
				{ className: 'completion-stat success' },
				createElement(
					'span',
					{ className: 'dashicons dashicons-yes' }
				),
				createElement(
					'span',
					{ className: 'stat-text' },
					/* translators: %d: number of blocks */
					__(`${generated} blocks generated successfully`, 'seo-generator')
				)
			),
			hasFailures &&
				createElement(
					'div',
					{ className: 'completion-stat failure' },
					createElement(
						'span',
						{ className: 'dashicons dashicons-no' }
					),
					createElement(
						'span',
						{ className: 'stat-text' },
						/* translators: %d: number of blocks */
						__(`${failed.length} ${failed.length === 1 ? 'block' : 'blocks'} failed`, 'seo-generator')
					)
				),
			createElement(
				'div',
				{ className: 'completion-stat cost' },
				createElement(
					'span',
					{ className: 'dashicons dashicons-money-alt' }
				),
				createElement(
					'span',
					{ className: 'stat-text' },
					__('Total cost: ', 'seo-generator') + formatCost(totalCost)
				)
			)
		),
		hasFailures &&
			createElement(
				'div',
				{ className: 'failed-blocks-list' },
				createElement(
					'h3',
					null,
					__('Failed Blocks:', 'seo-generator')
				),
				createElement(
					'ul',
					null,
					failed.map((failedBlock) =>
						createElement(
							'li',
							{ key: failedBlock.blockType },
							createElement(
								'strong',
								null,
								BLOCK_NAMES[failedBlock.blockType] || failedBlock.blockType
							),
							': ',
							failedBlock.error || __('Unknown error', 'seo-generator')
						)
					)
				)
			),
		createElement(
			'div',
			{ className: 'completion-actions' },
			hasFailures &&
				createElement(Button, {
					variant: 'secondary',
					onClick: onRetryFailed,
					children: __('Retry Failed', 'seo-generator'),
				}),
			createElement(Button, {
				variant: 'primary',
				onClick: onClose,
				children: __('Close', 'seo-generator'),
			})
		)
	);
};

export default CompletionSummary;
