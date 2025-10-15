/**
 * EthicsBlock Component
 *
 * Ethics & Origin content block editor.
 *
 * @package
 */

import { createElement } from '@wordpress/element';
import { TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import CollapsibleBlock from './CollapsibleBlock';
import CharacterCounter from '../common/CharacterCounter';
import RepeaterField from '../common/RepeaterField';

/**
 * EthicsBlock component.
 *
 * @param {Object}   props            Component props.
 * @param {Object}   props.data       Block data.
 * @param {Function} props.onChange   Callback when data changes.
 * @param {Function} props.onGenerate Callback when Generate is clicked.
 * @param {string}   props.status     Block status.
 * @param {boolean}  props.isExpanded Whether block is expanded.
 * @param {Function} props.onToggle   Callback when block is toggled.
 * @return {Element} The EthicsBlock component.
 */
const EthicsBlock = ({
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

	const heading = data.ethics_heading || '';
	const text = data.ethics_text || '';
	const certifications = data.certifications || [];

	return createElement(
		CollapsibleBlock,
		{
			title: __('Ethics & Origin', 'seo-generator'),
			isExpanded,
			onToggle,
			status,
			onGenerate,
		},
		createElement(
			'div',
			{ className: 'block-fields' },
			createElement(TextControl, {
				label: __('Ethics Heading', 'seo-generator'),
				value: heading,
				onChange: (value) => updateField('ethics_heading', value),
				disabled: isGenerating,
			}),
			createElement(
				'div',
				{ className: 'field-with-counter' },
				createElement(TextareaControl, {
					label: __('Ethics Text', 'seo-generator'),
					value: text,
					onChange: (value) => updateField('ethics_text', value),
					disabled: isGenerating,
					rows: 5,
					help: __(
						'Description of ethical sourcing and origin',
						'seo-generator'
					),
				}),
				createElement(CharacterCounter, {
					current: text.length,
					max: 800,
				})
			),
			createElement(
				'div',
				{ className: 'field-group' },
				createElement(
					'label',
					null,
					__('Certifications', 'seo-generator')
				),
				createElement(RepeaterField, {
					fields: [
						{
							name: 'cert_name',
							label: __('Certification Name', 'seo-generator'),
							type: 'text',
						},
						{
							name: 'cert_link',
							label: __('Certification Link', 'seo-generator'),
							type: 'text',
						},
					],
					values: certifications,
					onChange: (value) => updateField('certifications', value),
					disabled: isGenerating,
					addLabel: __('Add Certification', 'seo-generator'),
				})
			)
		)
	);
};

export default EthicsBlock;
