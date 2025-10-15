/**
 * CareWarrantyBlock Component
 *
 * Care & Warranty content block editor.
 *
 * @package
 */

import { createElement } from '@wordpress/element';
import { TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import CollapsibleBlock from './CollapsibleBlock';
import RepeaterField from '../common/RepeaterField';

/**
 * CareWarrantyBlock component.
 *
 * @param {Object}   props            Component props.
 * @param {Object}   props.data       Block data.
 * @param {Function} props.onChange   Callback when data changes.
 * @param {Function} props.onGenerate Callback when Generate is clicked.
 * @param {string}   props.status     Block status.
 * @param {boolean}  props.isExpanded Whether block is expanded.
 * @param {Function} props.onToggle   Callback when block is toggled.
 * @return {Element} The CareWarrantyBlock component.
 */
const CareWarrantyBlock = ({
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

	const careHeading = data.care_heading || '';
	const careBullets = data.care_bullets || [];
	const warrantyHeading = data.warranty_heading || '';
	const warrantyText = data.warranty_text || '';

	return createElement(
		CollapsibleBlock,
		{
			title: __('Care & Warranty', 'seo-generator'),
			isExpanded,
			onToggle,
			status,
			onGenerate,
		},
		createElement(
			'div',
			{ className: 'block-fields' },
			createElement(TextControl, {
				label: __('Care Heading', 'seo-generator'),
				value: careHeading,
				onChange: (value) => updateField('care_heading', value),
				disabled: isGenerating,
			}),
			createElement(
				'div',
				{ className: 'field-group' },
				createElement('label', null, __('Care Tips', 'seo-generator')),
				createElement(RepeaterField, {
					fields: [
						{
							name: 'care_tip',
							label: __('Care Tip', 'seo-generator'),
							type: 'text',
						},
					],
					values: careBullets,
					onChange: (value) => updateField('care_bullets', value),
					disabled: isGenerating,
					addLabel: __('Add Care Tip', 'seo-generator'),
				})
			),
			createElement(TextControl, {
				label: __('Warranty Heading', 'seo-generator'),
				value: warrantyHeading,
				onChange: (value) => updateField('warranty_heading', value),
				disabled: isGenerating,
			}),
			createElement(TextareaControl, {
				label: __('Warranty Text', 'seo-generator'),
				value: warrantyText,
				onChange: (value) => updateField('warranty_text', value),
				disabled: isGenerating,
				rows: 4,
			})
		)
	);
};

export default CareWarrantyBlock;
