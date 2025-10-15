/**
 * MaterialsBlock Component
 *
 * Materials Explained content block editor.
 *
 * @package
 */

import { createElement } from '@wordpress/element';
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import CollapsibleBlock from './CollapsibleBlock';
import RepeaterField from '../common/RepeaterField';

/**
 * MaterialsBlock component.
 *
 * @param {Object}   props            Component props.
 * @param {Object}   props.data       Block data.
 * @param {Function} props.onChange   Callback when data changes.
 * @param {Function} props.onGenerate Callback when Generate is clicked.
 * @param {string}   props.status     Block status.
 * @param {boolean}  props.isExpanded Whether block is expanded.
 * @param {Function} props.onToggle   Callback when block is toggled.
 * @return {Element} The MaterialsBlock component.
 */
const MaterialsBlock = ({
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

	const heading = data.materials_heading || '';
	const items = data.materials_items || [];

	return createElement(
		CollapsibleBlock,
		{
			title: __('Materials Explained', 'seo-generator'),
			isExpanded,
			onToggle,
			status,
			onGenerate,
		},
		createElement(
			'div',
			{ className: 'block-fields' },
			createElement(TextControl, {
				label: __('Materials Heading', 'seo-generator'),
				value: heading,
				onChange: (value) => updateField('materials_heading', value),
				disabled: isGenerating,
				help: __('Heading for the materials section', 'seo-generator'),
			}),
			createElement(
				'div',
				{ className: 'field-group' },
				createElement(
					'label',
					null,
					__('Material Items', 'seo-generator')
				),
				createElement(RepeaterField, {
					fields: [
						{
							name: 'material',
							label: __('Material Name', 'seo-generator'),
							type: 'text',
						},
						{
							name: 'pros',
							label: __('Pros', 'seo-generator'),
							type: 'textarea',
						},
						{
							name: 'cons',
							label: __('Cons', 'seo-generator'),
							type: 'textarea',
						},
						{
							name: 'best_for',
							label: __('Best For', 'seo-generator'),
							type: 'text',
						},
						{
							name: 'allergy_notes',
							label: __('Allergy Notes', 'seo-generator'),
							type: 'text',
						},
						{
							name: 'care',
							label: __('Care Instructions', 'seo-generator'),
							type: 'textarea',
						},
					],
					values: items,
					onChange: (value) => updateField('materials_items', value),
					disabled: isGenerating,
					addLabel: __('Add Material', 'seo-generator'),
				})
			)
		)
	);
};

export default MaterialsBlock;
