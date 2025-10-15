/**
 * Validation Utilities
 *
 * Utility functions for form validation.
 *
 * @package
 */

import { __ } from '@wordpress/i18n';

/**
 * Validate that a value is not empty.
 *
 * @param {*} value Value to validate.
 * @return {Object} Validation result { isValid: boolean, error: string | null }.
 */
export const validateRequired = (value) => {
	const isValid =
		value !== null &&
		value !== undefined &&
		String(value).trim() !== '';

	return {
		isValid,
		error: isValid ? null : __('This field is required', 'seo-generator'),
	};
};

/**
 * Validate that a value does not exceed character limit.
 *
 * @param {string} value Value to validate.
 * @param {number} max   Maximum character length.
 * @return {Object} Validation result { isValid: boolean, error: string | null }.
 */
export const validateCharacterLimit = (value, max) => {
	const length = value?.length || 0;
	const isValid = length <= max;

	return {
		isValid,
		error: isValid
			? null
			: __(`Character limit exceeded (${length}/${max})`, 'seo-generator'),
	};
};

/**
 * Validate that a value is a valid URL.
 *
 * @param {string} value Value to validate.
 * @return {Object} Validation result { isValid: boolean, error: string | null }.
 */
export const validateUrl = (value) => {
	// Empty is valid unless field is also required
	if (!value || value.trim() === '') {
		return { isValid: true, error: null };
	}

	try {
		new URL(value);
		return { isValid: true, error: null };
	} catch (err) {
		return {
			isValid: false,
			error: __(
				'Please enter a valid URL (e.g., https://example.com)',
				'seo-generator'
			),
		};
	}
};

/**
 * Validate that a value is a valid email.
 *
 * @param {string} value Value to validate.
 * @return {Object} Validation result { isValid: boolean, error: string | null }.
 */
export const validateEmail = (value) => {
	// Empty is valid unless field is also required
	if (!value || value.trim() === '') {
		return { isValid: true, error: null };
	}

	const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	const isValid = emailRegex.test(value);

	return {
		isValid,
		error: isValid
			? null
			: __('Please enter a valid email address', 'seo-generator'),
	};
};
