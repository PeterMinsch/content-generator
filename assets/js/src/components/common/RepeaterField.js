/**
 * RepeaterField Component
 *
 * Generic repeater field component for handling array-based form fields.
 *
 * @package
 */

import { createElement } from '@wordpress/element';
import { Button, TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import CharacterCounter from './CharacterCounter';
import MediaPicker from './MediaPicker';

/**
 * RepeaterField component.
 *
 * @param {Object}   props          Component props.
 * @param {Array}    props.fields   Field definitions [{name, label, type, maxLength}].
 * @param {Array}    props.values   Current values array.
 * @param {Function} props.onChange Callback when values change.
 * @param {boolean}  props.disabled Whether fields are disabled.
 * @param {number}   props.maxItems Maximum number of items (optional).
 * @param {string}   props.addLabel Custom label for Add button.
 * @return {Element} The RepeaterField component.
 */
const RepeaterField = ({
	fields,
	values = [],
	onChange,
	disabled = false,
	maxItems = null,
	addLabel = null,
}) => {
	const addItem = () => {
		const emptyItem = {};
		fields.forEach((field) => {
			emptyItem[field.name] = '';
		});
		onChange([...values, emptyItem]);
	};

	const removeItem = (index) => {
		onChange(values.filter((_, i) => i !== index));
	};

	const updateItem = (index, fieldName, value) => {
		const newValues = [...values];
		newValues[index] = { ...newValues[index], [fieldName]: value };
		onChange(newValues);
	};

	const canAddMore = maxItems === null || values.length < maxItems;

	return createElement(
		'div',
		{ className: 'repeater-field' },
		values.map((item, index) =>
			createElement(
				'div',
				{ key: index, className: 'repeater-item' },
				createElement(
					'div',
					{ className: 'repeater-item-header' },
					createElement(
						'span',
						{ className: 'repeater-item-number' },
						`#${index + 1}`
					),
					createElement(Button, {
						isDestructive: true,
						variant: 'tertiary',
						size: 'small',
						onClick: () => removeItem(index),
						disabled,
						children: __('Remove', 'seo-generator'),
					})
				),
				createElement(
					'div',
					{ className: 'repeater-item-fields' },
					fields.map((field) => {
						const fieldValue = item[field.name] || '';
						const commonProps = {
							key: field.name,
							label: field.label,
							value: fieldValue,
							onChange: (value) =>
								updateItem(index, field.name, value),
							disabled,
						};

						if (field.type === 'image') {
							return createElement(
								'div',
								{ className: 'field-wrapper' },
								createElement('label', null, field.label),
								createElement(MediaPicker, {
									value: fieldValue,
									onChange: (value) =>
										updateItem(index, field.name, value),
									label: field.pickerLabel || __('Select Image', 'seo-generator'),
								})
							);
						}

						if (field.type === 'textarea') {
							return createElement(
								'div',
								{ className: 'field-with-counter' },
								createElement(TextareaControl, {
									...commonProps,
									rows: 3,
								}),
								field.maxLength &&
									createElement(CharacterCounter, {
										current: fieldValue.length,
										max: field.maxLength,
									})
							);
						}

						return createElement(
							'div',
							{ className: 'field-with-counter' },
							createElement(TextControl, commonProps),
							field.maxLength &&
								createElement(CharacterCounter, {
									current: fieldValue.length,
									max: field.maxLength,
								})
						);
					})
				)
			)
		),
		createElement(
			'div',
			{ className: 'repeater-actions' },
			createElement(Button, {
				variant: 'secondary',
				onClick: addItem,
				disabled: disabled || !canAddMore,
				children: addLabel || __('Add Item', 'seo-generator'),
			}),
			maxItems &&
				createElement(
					'span',
					{ className: 'repeater-count-info' },
					`${values.length} / ${maxItems}`
				)
		)
	);
};

export default RepeaterField;
