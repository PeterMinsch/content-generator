/**
 * FAQsBlock Component
 *
 * FAQs content block editor.
 *
 * @package
 */

import { createElement } from '@wordpress/element';
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import CollapsibleBlock from './CollapsibleBlock';
import RepeaterField from '../common/RepeaterField';

/**
 * FAQsBlock component.
 *
 * @param {Object}   props            Component props.
 * @param {Object}   props.data       Block data.
 * @param {Function} props.onChange   Callback when data changes.
 * @param {Function} props.onGenerate Callback when Generate is clicked.
 * @param {string}   props.status     Block status.
 * @param {boolean}  props.isExpanded Whether block is expanded.
 * @param {Function} props.onToggle   Callback when block is toggled.
 * @return {Element} The FAQsBlock component.
 */
const FAQsBlock = ({
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

	const heading = data.faqs_heading || '';
	const items = data.faq_items || [];

	return createElement(
		CollapsibleBlock,
		{
			title: __('FAQs', 'seo-generator'),
			isExpanded,
			onToggle,
			status,
			onGenerate,
		},
		createElement(
			'div',
			{ className: 'block-fields' },
			createElement(TextControl, {
				label: __('FAQs Heading', 'seo-generator'),
				value: heading,
				onChange: (value) => updateField('faqs_heading', value),
				disabled: isGenerating,
			}),
			createElement(
				'div',
				{ className: 'field-group' },
				createElement('label', null, __('FAQ Items', 'seo-generator')),
				createElement(RepeaterField, {
					fields: [
						{
							name: 'question',
							label: __('Question', 'seo-generator'),
							type: 'text',
						},
						{
							name: 'answer',
							label: __('Answer', 'seo-generator'),
							type: 'textarea',
							maxLength: 600,
						},
					],
					values: items,
					onChange: (value) => updateField('faq_items', value),
					disabled: isGenerating,
					addLabel: __('Add FAQ', 'seo-generator'),
				})
			)
		)
	);
};

export default FAQsBlock;
