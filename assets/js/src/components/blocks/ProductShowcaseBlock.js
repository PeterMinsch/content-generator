/**
 * ProductShowcaseBlock Component
 *
 * Product Showcase content block editor.
 *
 * @package
 */

import { createElement } from '@wordpress/element';
import { TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import CollapsibleBlock from './CollapsibleBlock';
import RepeaterField from '../common/RepeaterField';

/**
 * ProductShowcaseBlock component.
 *
 * @param {Object}   props            Component props.
 * @param {Object}   props.data       Block data.
 * @param {Function} props.onChange   Callback when data changes.
 * @param {Function} props.onGenerate Callback when Generate is clicked.
 * @param {string}   props.status     Block status.
 * @param {boolean}  props.isExpanded Whether block is expanded.
 * @param {Function} props.onToggle   Callback when block is toggled.
 * @return {Element} The ProductShowcaseBlock component.
 */
const ProductShowcaseBlock = ({
	data = {},
	onChange,
	onGenerate,
	status,
	isExpanded,
	onToggle,
}) => {
	const isGenerating = status === 'generating';

	const updateField = (field, value) => {
		onChange({ ...data, [field]: value });
	};

	const heading = data.showcase_heading || '';
	const intro = data.showcase_intro || '';
	const products = data.showcase_products || [];

	return createElement(
		CollapsibleBlock,
		{
			title: __('Product Showcase', 'seo-generator'),
			isExpanded,
			onToggle,
			status,
			onGenerate,
		},
		createElement(
			'div',
			{ className: 'block-fields' },
			createElement(TextControl, {
				label: __('Showcase Heading', 'seo-generator'),
				value: heading,
				onChange: (value) => updateField('showcase_heading', value),
				disabled: isGenerating,
			}),
			createElement(TextareaControl, {
				label: __('Showcase Intro', 'seo-generator'),
				value: intro,
				onChange: (value) => updateField('showcase_intro', value),
				disabled: isGenerating,
				rows: 3,
			}),
			createElement(
				'div',
				{ className: 'field-group' },
				createElement('label', null, __('Products', 'seo-generator')),
				createElement(RepeaterField, {
					fields: [
						{
							name: 'product_sku',
							label: __('Product SKU', 'seo-generator'),
							type: 'text',
						},
						{
							name: 'alt_image_url',
							label: __('Alt Image URL', 'seo-generator'),
							type: 'text',
						},
					],
					values: products,
					onChange: (value) =>
						updateField('showcase_products', value),
					disabled: isGenerating,
					addLabel: __('Add Product', 'seo-generator'),
				})
			)
		)
	);
};

export default ProductShowcaseBlock;
