/**
 * ProductCriteriaBlock Component
 *
 * Product Criteria content block editor.
 *
 * @package
 */

import { createElement } from '@wordpress/element';
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import CollapsibleBlock from './CollapsibleBlock';
import RepeaterField from '../common/RepeaterField';

/**
 * ProductCriteriaBlock component.
 *
 * @param {Object}   props            Component props.
 * @param {Object}   props.data       Block data.
 * @param {Function} props.onChange   Callback when data changes.
 * @param {Function} props.onGenerate Callback when Generate is clicked.
 * @param {string}   props.status     Block status.
 * @param {boolean}  props.isExpanded Whether block is expanded.
 * @param {Function} props.onToggle   Callback when block is toggled.
 * @return {Element} The ProductCriteriaBlock component.
 */
const ProductCriteriaBlock = ({
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

	const heading = data.criteria_heading || '';
	const items = data.criteria_items || [];

	return createElement(
		CollapsibleBlock,
		{
			title: __('Product Criteria', 'seo-generator'),
			isExpanded,
			onToggle,
			status,
			onGenerate,
		},
		createElement(
			'div',
			{ className: 'block-fields' },
			createElement(TextControl, {
				label: __('Criteria Heading', 'seo-generator'),
				value: heading,
				onChange: (value) => updateField('criteria_heading', value),
				disabled: isGenerating,
				help: __(
					'Heading for the product criteria section',
					'seo-generator'
				),
			}),
			createElement(
				'div',
				{ className: 'field-group' },
				createElement(
					'label',
					null,
					__('Criteria Items', 'seo-generator')
				),
				createElement(RepeaterField, {
					fields: [
						{
							name: 'name',
							label: __('Criteria Name', 'seo-generator'),
							type: 'text',
						},
						{
							name: 'explanation',
							label: __('Explanation', 'seo-generator'),
							type: 'textarea',
						},
					],
					values: items,
					onChange: (value) => updateField('criteria_items', value),
					disabled: isGenerating,
					addLabel: __('Add Criteria', 'seo-generator'),
				})
			)
		)
	);
};

export default ProductCriteriaBlock;
