/**
 * CTABlock Component
 *
 * Call to Action content block editor.
 *
 * @package
 */

import { createElement } from '@wordpress/element';
import { TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import CollapsibleBlock from './CollapsibleBlock';

/**
 * CTABlock component.
 *
 * @param {Object}   props            Component props.
 * @param {Object}   props.data       Block data.
 * @param {Function} props.onChange   Callback when data changes.
 * @param {Function} props.onGenerate Callback when Generate is clicked.
 * @param {string}   props.status     Block status.
 * @param {boolean}  props.isExpanded Whether block is expanded.
 * @param {Function} props.onToggle   Callback when block is toggled.
 * @return {Element} The CTABlock component.
 */
const CTABlock = ({
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

	const heading = data.cta_heading || '';
	const text = data.cta_text || '';
	const primaryLabel = data.cta_primary_label || '';
	const primaryUrl = data.cta_primary_url || '';
	const secondaryLabel = data.cta_secondary_label || '';
	const secondaryUrl = data.cta_secondary_url || '';

	return createElement(
		CollapsibleBlock,
		{
			title: __('Call to Action', 'seo-generator'),
			isExpanded,
			onToggle,
			status,
			onGenerate,
		},
		createElement(
			'div',
			{ className: 'block-fields' },
			createElement(TextControl, {
				label: __('CTA Heading', 'seo-generator'),
				value: heading,
				onChange: (value) => updateField('cta_heading', value),
				disabled: isGenerating,
			}),
			createElement(TextareaControl, {
				label: __('CTA Text', 'seo-generator'),
				value: text,
				onChange: (value) => updateField('cta_text', value),
				disabled: isGenerating,
				rows: 3,
			}),
			createElement(
				'div',
				{ className: 'field-group' },
				createElement(
					'h4',
					null,
					__('Primary Button', 'seo-generator')
				),
				createElement(TextControl, {
					label: __('Button Label', 'seo-generator'),
					value: primaryLabel,
					onChange: (value) =>
						updateField('cta_primary_label', value),
					disabled: isGenerating,
				}),
				createElement(TextControl, {
					label: __('Button URL', 'seo-generator'),
					value: primaryUrl,
					onChange: (value) => updateField('cta_primary_url', value),
					disabled: isGenerating,
					type: 'url',
				})
			),
			createElement(
				'div',
				{ className: 'field-group' },
				createElement(
					'h4',
					null,
					__('Secondary Button', 'seo-generator')
				),
				createElement(TextControl, {
					label: __('Button Label', 'seo-generator'),
					value: secondaryLabel,
					onChange: (value) =>
						updateField('cta_secondary_label', value),
					disabled: isGenerating,
				}),
				createElement(TextControl, {
					label: __('Button URL', 'seo-generator'),
					value: secondaryUrl,
					onChange: (value) =>
						updateField('cta_secondary_url', value),
					disabled: isGenerating,
					type: 'url',
				})
			)
		)
	);
};

export default CTABlock;
