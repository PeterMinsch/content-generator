/**
 * BasicInfo Component
 *
 * Displays and manages basic page information fields.
 *
 * @package
 */

import { useState, useEffect, createElement } from '@wordpress/element';
import { TextControl, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useValidation } from '../../hooks/useValidation';
import FieldError from '../common/FieldError';

/**
 * BasicInfo component.
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.pageData Current page data.
 * @param {Function} props.onChange Callback when data changes.
 * @param {Array}    props.topics   Available topic terms.
 * @return {Element} The BasicInfo component.
 */
const BasicInfo = ({ pageData, onChange, topics }) => {
	const [title, setTitle] = useState(pageData.title || '');
	const [slug, setSlug] = useState(pageData.slug || '');
	const [topic, setTopic] = useState(pageData.topic || '');
	const [focusKeyword, setFocusKeyword] = useState(
		pageData.focusKeyword || ''
	);
	const [slugManuallyEdited, setSlugManuallyEdited] = useState(false);

	// Validation rules for required fields
	const validationRules = {
		title: { required: true },
		focusKeyword: { required: true },
	};

	const {
		errors,
		validateField,
		touchField,
		getFieldError,
	} = useValidation(validationRules, { title, focusKeyword });

	// Auto-generate slug from title.
	useEffect(() => {
		if (title && !slugManuallyEdited) {
			const generatedSlug = title
				.toLowerCase()
				.replace(/[^a-z0-9]+/g, '-')
				.replace(/(^-|-$)/g, '');
			setSlug(generatedSlug);
		}
	}, [title, slugManuallyEdited]);

	// Notify parent component of changes.
	useEffect(() => {
		onChange({
			title,
			slug,
			topic,
			focusKeyword,
		});
	}, [title, slug, topic, focusKeyword, onChange]);

	const topicOptions = [
		{ label: __('Select a topic…', 'seo-generator'), value: '' },
		...topics.map((t) => ({
			label: t.name,
			value: t.slug,
		})),
	];

	const handleTitleBlur = () => {
		touchField('title');
		validateField('title');
	};

	const handleFocusKeywordBlur = () => {
		touchField('focusKeyword');
		validateField('focusKeyword');
	};

	return createElement(
		'div',
		{ className: 'seo-generator-basic-info' },
		createElement('h2', null, __('Basic Information', 'seo-generator')),
		createElement(
			'div',
			{ className: 'field-wrapper required-field' },
			createElement(TextControl, {
				label: __('Page Title', 'seo-generator'),
				value: title,
				onChange: setTitle,
				onBlur: handleTitleBlur,
				className: getFieldError('title') ? 'has-error' : '',
				required: true,
				'aria-required': 'true',
				'aria-invalid': getFieldError('title') ? 'true' : 'false',
				'aria-describedby': getFieldError('title')
					? 'title-error'
					: undefined,
				help: __('Enter the main title for this SEO page', 'seo-generator'),
			}),
			createElement(FieldError, {
				error: getFieldError('title'),
				id: 'title-error',
			})
		),
		createElement(
			'div',
			{ className: 'field-wrapper' },
			createElement(TextControl, {
				label: __('URL Slug', 'seo-generator'),
				value: slug,
				onChange: (value) => {
					setSlug(value);
					setSlugManuallyEdited(true);
				},
				help: __('Auto-generated from title, or edit manually', 'seo-generator'),
			})
		),
		createElement(
			'div',
			{ className: 'field-wrapper' },
			createElement(SelectControl, {
				label: __('Topic', 'seo-generator'),
				value: topic,
				options: topicOptions,
				onChange: setTopic,
				help: __('Select the topic category for this page', 'seo-generator'),
			})
		),
		createElement(
			'div',
			{ className: 'field-wrapper required-field' },
			createElement(TextControl, {
				label: __('Focus Keyword', 'seo-generator'),
				value: focusKeyword,
				onChange: setFocusKeyword,
				onBlur: handleFocusKeywordBlur,
				className: getFieldError('focusKeyword') ? 'has-error' : '',
				required: true,
				'aria-required': 'true',
				'aria-invalid': getFieldError('focusKeyword') ? 'true' : 'false',
				'aria-describedby': getFieldError('focusKeyword')
					? 'focusKeyword-error'
					: undefined,
				help: __('Primary SEO keyword for this page', 'seo-generator'),
			}),
			createElement(FieldError, {
				error: getFieldError('focusKeyword'),
				id: 'focusKeyword-error',
			})
		)
	);
};

export default BasicInfo;
