/**
 * ProcessBlock Component
 *
 * Process content block editor.
 *
 * @package
 */

import { createElement } from '@wordpress/element';
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import CollapsibleBlock from './CollapsibleBlock';
import RepeaterField from '../common/RepeaterField';

/**
 * ProcessBlock component.
 *
 * @param {Object}   props            Component props.
 * @param {Object}   props.data       Block data.
 * @param {Function} props.onChange   Callback when data changes.
 * @param {Function} props.onGenerate Callback when Generate is clicked.
 * @param {string}   props.status     Block status.
 * @param {boolean}  props.isExpanded Whether block is expanded.
 * @param {Function} props.onToggle   Callback when block is toggled.
 * @return {Element} The ProcessBlock component.
 */
const ProcessBlock = ({
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

	const heading = data.process_heading || '';
	const steps = data.process_steps || [];

	return createElement(
		CollapsibleBlock,
		{
			title: __('Process', 'seo-generator'),
			isExpanded,
			onToggle,
			status,
			onGenerate,
		},
		createElement(
			'div',
			{ className: 'block-fields' },
			createElement(TextControl, {
				label: __('Process Heading', 'seo-generator'),
				value: heading,
				onChange: (value) => updateField('process_heading', value),
				disabled: isGenerating,
				help: __('Heading for the process section', 'seo-generator'),
			}),
			createElement(
				'div',
				{ className: 'field-group' },
				createElement(
					'label',
					null,
					__('Process Steps', 'seo-generator')
				),
				createElement(
					'p',
					{ className: 'description' },
					__('Maximum 4 steps', 'seo-generator')
				),
				createElement(RepeaterField, {
					fields: [
						{
							name: 'step_title',
							label: __('Step Title', 'seo-generator'),
							type: 'text',
						},
						{
							name: 'step_text',
							label: __('Step Description', 'seo-generator'),
							type: 'textarea',
							maxLength: 400,
						},
						{
							name: 'step_image',
							label: __('Step Image', 'seo-generator'),
							type: 'image',
							pickerLabel: __('Select Step Image', 'seo-generator'),
						},
					],
					values: steps,
					onChange: (value) => updateField('process_steps', value),
					disabled: isGenerating,
					maxItems: 4,
					addLabel: __('Add Step', 'seo-generator'),
				})
			)
		)
	);
};

export default ProcessBlock;
