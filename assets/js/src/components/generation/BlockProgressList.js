/**
 * BlockProgressList Component
 *
 * Displays list of all blocks with their generation status during bulk generation.
 *
 * @package
 */

import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import StatusIndicator from '../common/StatusIndicator';
import { BLOCK_TYPES } from '../../context/BlockStatusContext';

/**
 * Block display names mapping.
 */
const BLOCK_NAMES = {
	[BLOCK_TYPES.HERO]: __('Hero Section', 'seo-generator'),
	[BLOCK_TYPES.SERP_ANSWER]: __('SERP Answer', 'seo-generator'),
	[BLOCK_TYPES.PRODUCT_CRITERIA]: __('Product Criteria', 'seo-generator'),
	[BLOCK_TYPES.MATERIALS]: __('Materials Explained', 'seo-generator'),
	[BLOCK_TYPES.PROCESS]: __('Process', 'seo-generator'),
	[BLOCK_TYPES.COMPARISON]: __('Comparison', 'seo-generator'),
	[BLOCK_TYPES.PRODUCT_SHOWCASE]: __('Product Showcase', 'seo-generator'),
	[BLOCK_TYPES.SIZE_FIT]: __('Size & Fit', 'seo-generator'),
	[BLOCK_TYPES.CARE_WARRANTY]: __('Care & Warranty', 'seo-generator'),
	[BLOCK_TYPES.ETHICS]: __('Ethics & Origin', 'seo-generator'),
	[BLOCK_TYPES.FAQS]: __('FAQs', 'seo-generator'),
	[BLOCK_TYPES.CTA]: __('CTA', 'seo-generator'),
};

/**
 * Block order for display.
 */
const BLOCK_ORDER = [
	BLOCK_TYPES.HERO,
	BLOCK_TYPES.SERP_ANSWER,
	BLOCK_TYPES.PRODUCT_CRITERIA,
	BLOCK_TYPES.MATERIALS,
	BLOCK_TYPES.PROCESS,
	BLOCK_TYPES.COMPARISON,
	BLOCK_TYPES.PRODUCT_SHOWCASE,
	BLOCK_TYPES.SIZE_FIT,
	BLOCK_TYPES.CARE_WARRANTY,
	BLOCK_TYPES.ETHICS,
	BLOCK_TYPES.FAQS,
	BLOCK_TYPES.CTA,
];

/**
 * Format duration in seconds to readable string.
 *
 * @param {number} seconds Duration in seconds.
 * @return {string} Formatted duration.
 */
const formatDuration = (seconds) => {
	if (!seconds || seconds < 1) {
		return '';
	}
	return `(${Math.round(seconds)}s)`;
};

/**
 * BlockProgressList component.
 *
 * @param {Object} props           Component props.
 * @param {Object} props.statuses  Block statuses object (blockType -> status).
 * @param {Object} props.durations Block durations object (blockType -> seconds).
 * @param {string} props.currentBlock Currently generating block type.
 * @return {Element} The BlockProgressList component.
 */
const BlockProgressList = ({
	statuses = {},
	durations = {},
	currentBlock = null,
}) => {
	return createElement(
		'div',
		{ className: 'block-progress-list' },
		createElement(
			'ul',
			{ className: 'block-progress-items' },
			BLOCK_ORDER.map((blockType) => {
				const status = statuses[blockType] || 'not_generated';
				const duration = durations[blockType] || 0;
				const isCurrent = blockType === currentBlock;

				return createElement(
					'li',
					{
						key: blockType,
						className: `block-progress-item ${
							isCurrent ? 'is-current' : ''
						}`,
					},
					createElement(StatusIndicator, {
						status: isCurrent ? 'generating' : status,
					}),
					createElement(
						'span',
						{ className: 'block-progress-name' },
						BLOCK_NAMES[blockType]
					),
					status === 'generated' &&
						duration > 0 &&
						createElement(
							'span',
							{ className: 'block-progress-duration' },
							formatDuration(duration)
						),
					isCurrent &&
						status === 'generating' &&
						createElement(
							'span',
							{ className: 'block-progress-status-text' },
							__('generating...', 'seo-generator')
						)
				);
			})
		)
	);
};

export default BlockProgressList;
export { BLOCK_ORDER, BLOCK_NAMES };
