/**
 * SizeFitBlock Component
 *
 * Size & Fit content block editor.
 *
 * @package
 */

import { createElement } from '@wordpress/element';
import { TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import CollapsibleBlock from './CollapsibleBlock';
import MediaPicker from '../common/MediaPicker';

/**
 * SizeFitBlock component.
 *
 * @param {Object}   props            Component props.
 * @param {Object}   props.data       Block data.
 * @param {Function} props.onChange   Callback when data changes.
 * @param {Function} props.onGenerate Callback when Generate is clicked.
 * @param {string}   props.status     Block status.
 * @param {boolean}  props.isExpanded Whether block is expanded.
 * @param {Function} props.onToggle   Callback when block is toggled.
 * @return {Element} The SizeFitBlock component.
 */
const SizeFitBlock = ({
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

	const heading = data.size_heading || '';
	const notes = data.comfort_fit_notes || '';
	const sizeChartImage = data.size_chart_image || null;

	return createElement(
		CollapsibleBlock,
		{
			title: __('Size & Fit', 'seo-generator'),
			isExpanded,
			onToggle,
			status,
			onGenerate,
		},
		createElement(
			'div',
			{ className: 'block-fields' },
			createElement(TextControl, {
				label: __('Size Heading', 'seo-generator'),
				value: heading,
				onChange: (value) => updateField('size_heading', value),
				disabled: isGenerating,
			}),
			createElement(
				'div',
				{ className: 'field-wrapper' },
				createElement(
					'label',
					null,
					__('Size Chart Image', 'seo-generator')
				),
				createElement(MediaPicker, {
					value: sizeChartImage,
					onChange: (imageId) => updateField('size_chart_image', imageId),
					label: __('Select Size Chart Image', 'seo-generator'),
				})
			),
			createElement(TextareaControl, {
				label: __('Comfort & Fit Notes', 'seo-generator'),
				value: notes,
				onChange: (value) => updateField('comfort_fit_notes', value),
				disabled: isGenerating,
				rows: 4,
			})
		)
	);
};

export default SizeFitBlock;
