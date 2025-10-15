/**
 * SerpAnswerBlock Component
 *
 * SERP Answer content block editor.
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
 * SerpAnswerBlock component.
 *
 * @param {Object}   props            Component props.
 * @param {Object}   props.data       Block data.
 * @param {Function} props.onChange   Callback when data changes.
 * @param {Function} props.onGenerate Callback when Generate is clicked.
 * @param {string}   props.status     Block status.
 * @param {boolean}  props.isExpanded Whether block is expanded.
 * @param {Function} props.onToggle   Callback when block is toggled.
 * @return {Element} The SerpAnswerBlock component.
 */
const SerpAnswerBlock = ({
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

	const heading = data.answer_heading || '';
	const paragraph = data.answer_paragraph || '';
	const bullets = data.answer_bullets || [];

	return createElement(
		CollapsibleBlock,
		{
			title: __('SERP Answer', 'seo-generator'),
			isExpanded,
			onToggle,
			status,
			onGenerate,
		},
		createElement(
			'div',
			{ className: 'block-fields' },
			createElement(
				'div',
				{ className: 'field-with-counter' },
				createElement(TextControl, {
					label: __('Answer Heading', 'seo-generator'),
					value: heading,
					onChange: (value) => updateField('answer_heading', value),
					disabled: isGenerating,
					help: __(
						'Question or heading for the SERP answer',
						'seo-generator'
					),
				}),
				createElement(CharacterCounter, {
					current: heading.length,
					max: 100,
				})
			),
			createElement(
				'div',
				{ className: 'field-with-counter' },
				createElement(TextareaControl, {
					label: __('Answer Paragraph', 'seo-generator'),
					value: paragraph,
					onChange: (value) => updateField('answer_paragraph', value),
					disabled: isGenerating,
					rows: 4,
					help: __(
						'Main answer paragraph for the SERP snippet',
						'seo-generator'
					),
				}),
				createElement(CharacterCounter, {
					current: paragraph.length,
					max: 600,
				})
			),
			createElement(
				'div',
				{ className: 'field-group' },
				createElement(
					'label',
					null,
					__('Answer Bullet Points', 'seo-generator')
				),
				createElement(RepeaterField, {
					fields: [
						{
							name: 'bullet_text',
							label: __('Bullet Text', 'seo-generator'),
							type: 'text',
							maxLength: 150,
						},
					],
					values: bullets,
					onChange: (value) => updateField('answer_bullets', value),
					disabled: isGenerating,
					addLabel: __('Add Bullet Point', 'seo-generator'),
				})
			)
		)
	);
};

export default SerpAnswerBlock;
