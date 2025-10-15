/**
 * BlockList Component
 *
 * Displays list of all 12 content blocks.
 *
 * @package
 */

import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import HeroBlock from '../blocks/HeroBlock';
import SerpAnswerBlock from '../blocks/SerpAnswerBlock';
import ProductCriteriaBlock from '../blocks/ProductCriteriaBlock';
import MaterialsBlock from '../blocks/MaterialsBlock';
import ProcessBlock from '../blocks/ProcessBlock';
import ComparisonBlock from '../blocks/ComparisonBlock';
import ProductShowcaseBlock from '../blocks/ProductShowcaseBlock';
import SizeFitBlock from '../blocks/SizeFitBlock';
import CareWarrantyBlock from '../blocks/CareWarrantyBlock';
import EthicsBlock from '../blocks/EthicsBlock';
import FAQsBlock from '../blocks/FAQsBlock';
import CTABlock from '../blocks/CTABlock';

/**
 * Block type to component mapping.
 */
const BLOCK_COMPONENTS = {
	hero: HeroBlock,
	serp_answer: SerpAnswerBlock,
	product_criteria: ProductCriteriaBlock,
	materials: MaterialsBlock,
	process: ProcessBlock,
	comparison: ComparisonBlock,
	product_showcase: ProductShowcaseBlock,
	size_fit: SizeFitBlock,
	care_warranty: CareWarrantyBlock,
	ethics: EthicsBlock,
	faqs: FAQsBlock,
	cta: CTABlock,
};

/**
 * Block IDs in display order.
 */
const BLOCK_ORDER = [
	'hero',
	'serp_answer',
	'product_criteria',
	'materials',
	'process',
	'comparison',
	'product_showcase',
	'size_fit',
	'care_warranty',
	'ethics',
	'faqs',
	'cta',
];

/**
 * BlockList component.
 *
 * @param {Object}   props                 Component props.
 * @param {number}   props.postId          Post ID.
 * @param {Object}   props.context         Page-level context for generation.
 * @param {Object}   props.blocks          Block data object.
 * @param {Object}   props.blockStatus     Block status object.
 * @param {Object}   props.expandedBlocks  Expanded state object.
 * @param {Function} props.onBlockChange   Callback when block data changes.
 * @param {Function} props.onGenerateBlock Callback when block generate clicked.
 * @param {Function} props.onToggleBlock   Callback when block is toggled.
 * @return {Element} The BlockList component.
 */
const BlockList = ({
	postId,
	context = {},
	blocks = {},
	blockStatus = {},
	expandedBlocks = {},
	onBlockChange,
	onGenerateBlock,
	onToggleBlock,
}) => {
	return createElement(
		'div',
		{ className: 'seo-generator-block-list' },
		createElement('h2', null, __('Content Blocks', 'seo-generator')),
		createElement(
			'div',
			{ className: 'block-list-items' },
			BLOCK_ORDER.map((blockId) => {
				const BlockComponent = BLOCK_COMPONENTS[blockId];
				if (!BlockComponent) {
					return null;
				}

				return createElement(BlockComponent, {
					key: blockId,
					postId,
					context,
					data: blocks[blockId] || {},
					onChange: (newData) => onBlockChange(blockId, newData),
					onGenerate: () => onGenerateBlock(blockId),
					status: blockStatus[blockId] || 'not_generated',
					isExpanded: expandedBlocks[blockId] || false,
					onToggle: () => onToggleBlock(blockId),
				});
			})
		)
	);
};

export default BlockList;
