/**
 * HeroBlock Component
 *
 * Hero Section content block editor.
 *
 * @package
 */

import { createElement } from '@wordpress/element';
import { TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import CollapsibleBlock from './CollapsibleBlock';
import CharacterCounter from '../common/CharacterCounter';
import MediaPicker from '../common/MediaPicker';
import { useGeneration } from '../../hooks/useGeneration';
import { BLOCK_TYPES } from '../../context/BlockStatusContext';

/**
 * HeroBlock component.
 *
 * @param {Object}   props            Component props.
 * @param {number}   props.postId     Post ID.
 * @param {Object}   props.data       Block data.
 * @param {Object}   props.context    Page-level context (title, topic, focusKeyword).
 * @param {Function} props.onChange   Callback when data changes.
 * @param {boolean}  props.isExpanded Whether block is expanded.
 * @param {Function} props.onToggle   Callback when block is toggled.
 * @return {Element} The HeroBlock component.
 */
const HeroBlock = ({
	postId,
	data = {},
	context = {},
	onChange,
	isExpanded,
	onToggle,
}) => {
	const { generateBlock, retryGeneration, status, error } = useGeneration(
		postId,
		BLOCK_TYPES.HERO
	);

	const handleGenerate = async () => {
		try {
			const generatedContent = await generateBlock(context);

			if (generatedContent) {
				onChange(generatedContent);
			}
		} catch (err) {
			// Error is already handled by the hook
			console.error('HeroBlock generation failed:', err);
		}
	};

	const isGenerating = status === 'generating';

	const updateField = (field, value) => {
		onChange({ ...data, [field]: value });
	};

	const title = data.hero_title || '';
	const subtitle = data.hero_subtitle || '';
	const summary = data.hero_summary || '';
	const heroImage = data.hero_image || null;

	return createElement(
		CollapsibleBlock,
		{
			title: __('Hero Section', 'seo-generator'),
			isExpanded,
			onToggle,
			status,
			onGenerate: handleGenerate,
			error,
			onRetry: retryGeneration,
		},
		createElement(
			'div',
			{ className: 'block-fields' },
			createElement(
				'div',
				{ className: 'field-with-counter' },
				createElement(TextControl, {
					label: __('Hero Title', 'seo-generator'),
					value: title,
					onChange: (value) => updateField('hero_title', value),
					disabled: isGenerating,
					help: __(
						'Main headline for the hero section',
						'seo-generator'
					),
				}),
				createElement(CharacterCounter, {
					current: title.length,
					max: 100,
				})
			),
			createElement(
				'div',
				{ className: 'field-with-counter' },
				createElement(TextControl, {
					label: __('Hero Subtitle', 'seo-generator'),
					value: subtitle,
					onChange: (value) => updateField('hero_subtitle', value),
					disabled: isGenerating,
					help: __(
						'Supporting headline for the hero section',
						'seo-generator'
					),
				}),
				createElement(CharacterCounter, {
					current: subtitle.length,
					max: 150,
				})
			),
			createElement(
				'div',
				{ className: 'field-with-counter' },
				createElement(TextareaControl, {
					label: __('Hero Summary', 'seo-generator'),
					value: summary,
					onChange: (value) => updateField('hero_summary', value),
					disabled: isGenerating,
					rows: 4,
					help: __(
						'Brief summary text for the hero section',
						'seo-generator'
					),
				}),
				createElement(CharacterCounter, {
					current: summary.length,
					max: 400,
				})
			),
			createElement(
				'div',
				{ className: 'field-wrapper' },
				createElement('label', null, __('Hero Image', 'seo-generator')),
				createElement(MediaPicker, {
					value: heroImage,
					onChange: (imageId) => updateField('hero_image', imageId),
					label: __('Select Hero Image', 'seo-generator'),
				})
			)
		)
	);
};

export default HeroBlock;
