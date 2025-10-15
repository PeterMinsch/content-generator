/**
 * ComparisonBlock Component
 *
 * Comparison content block editor.
 *
 * @package
 */

import { createElement } from '@wordpress/element';
import { TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import CollapsibleBlock from './CollapsibleBlock';
import RepeaterField from '../common/RepeaterField';

/**
 * ComparisonBlock component.
 *
 * @param {Object}   props            Component props.
 * @param {Object}   props.data       Block data.
 * @param {Function} props.onChange   Callback when data changes.
 * @param {Function} props.onGenerate Callback when Generate is clicked.
 * @param {string}   props.status     Block status.
 * @param {boolean}  props.isExpanded Whether block is expanded.
 * @param {Function} props.onToggle   Callback when block is toggled.
 * @return {Element} The ComparisonBlock component.
 */
const ComparisonBlock = ({
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

	const heading = data.comparison_heading || '';
	const leftLabel = data.comparison_left_label || '';
	const rightLabel = data.comparison_right_label || '';
	const summary = data.comparison_summary || '';
	const rows = data.comparison_rows || [];

	return createElement(
		CollapsibleBlock,
		{
			title: __('Comparison', 'seo-generator'),
			isExpanded,
			onToggle,
			status,
			onGenerate,
		},
		createElement(
			'div',
			{ className: 'block-fields' },
			createElement(TextControl, {
				label: __('Comparison Heading', 'seo-generator'),
				value: heading,
				onChange: (value) => updateField('comparison_heading', value),
				disabled: isGenerating,
			}),
			createElement(TextControl, {
				label: __('Left Column Label', 'seo-generator'),
				value: leftLabel,
				onChange: (value) =>
					updateField('comparison_left_label', value),
				disabled: isGenerating,
			}),
			createElement(TextControl, {
				label: __('Right Column Label', 'seo-generator'),
				value: rightLabel,
				onChange: (value) =>
					updateField('comparison_right_label', value),
				disabled: isGenerating,
			}),
			createElement(TextareaControl, {
				label: __('Comparison Summary', 'seo-generator'),
				value: summary,
				onChange: (value) => updateField('comparison_summary', value),
				disabled: isGenerating,
				rows: 3,
			}),
			createElement(
				'div',
				{ className: 'field-group' },
				createElement(
					'label',
					null,
					__('Comparison Rows', 'seo-generator')
				),
				createElement(RepeaterField, {
					fields: [
						{
							name: 'attribute',
							label: __('Attribute', 'seo-generator'),
							type: 'text',
						},
						{
							name: 'left_text',
							label: __('Left Side Text', 'seo-generator'),
							type: 'text',
							maxLength: 200,
						},
						{
							name: 'right_text',
							label: __('Right Side Text', 'seo-generator'),
							type: 'text',
							maxLength: 200,
						},
					],
					values: rows,
					onChange: (value) => updateField('comparison_rows', value),
					disabled: isGenerating,
					addLabel: __('Add Row', 'seo-generator'),
				})
			)
		)
	);
};

export default ComparisonBlock;
