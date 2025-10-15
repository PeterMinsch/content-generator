/**
 * useValidation Hook
 *
 * Custom hook for form validation.
 *
 * @package
 */

import { useState, useCallback, useMemo } from '@wordpress/element';
import {
	validateRequired,
	validateCharacterLimit,
	validateUrl,
	validateEmail,
} from '../utils/validation';

/**
 * useValidation hook.
 *
 * @param {Object} rules  Validation rules object { fieldName: { required, maxLength, type } }.
 * @param {Object} values Current field values object { fieldName: value }.
 * @return {Object} Validation state and functions.
 */
export const useValidation = (rules, values) => {
	const [errors, setErrors] = useState({});
	const [touched, setTouched] = useState({});

	/**
	 * Validate a single field.
	 *
	 * @param {string} fieldName Field name to validate.
	 * @return {boolean} Whether the field is valid.
	 */
	const validateField = useCallback(
		(fieldName) => {
			const fieldRules = rules[fieldName];
			if (!fieldRules) {
				return true;
			}

			const value = values[fieldName];
			let error = null;

			// Check required
			if (fieldRules.required) {
				const result = validateRequired(value);
				if (!result.isValid) {
					error = result.error;
				}
			}

			// Check character limit (only if no error yet)
			if (!error && fieldRules.maxLength) {
				const result = validateCharacterLimit(value, fieldRules.maxLength);
				if (!result.isValid) {
					error = result.error;
				}
			}

			// Check URL format (only if no error yet)
			if (!error && fieldRules.type === 'url' && value) {
				const result = validateUrl(value);
				if (!result.isValid) {
					error = result.error;
				}
			}

			// Check email format (only if no error yet)
			if (!error && fieldRules.type === 'email' && value) {
				const result = validateEmail(value);
				if (!result.isValid) {
					error = result.error;
				}
			}

			setErrors((prev) => ({
				...prev,
				[fieldName]: error,
			}));

			return error === null;
		},
		[rules, values]
	);

	/**
	 * Validate all fields.
	 *
	 * @return {boolean} Whether all fields are valid.
	 */
	const validate = useCallback(() => {
		const newErrors = {};
		let allValid = true;

		Object.keys(rules).forEach((fieldName) => {
			const isValid = validateField(fieldName);
			if (!isValid) {
				allValid = false;
			}
		});

		return allValid;
	}, [rules, validateField]);

	/**
	 * Mark a field as touched.
	 *
	 * @param {string} fieldName Field name.
	 */
	const touchField = useCallback((fieldName) => {
		setTouched((prev) => ({
			...prev,
			[fieldName]: true,
		}));
	}, []);

	/**
	 * Clear error for a field.
	 *
	 * @param {string} fieldName Field name.
	 */
	const clearError = useCallback((fieldName) => {
		setErrors((prev) => {
			const newErrors = { ...prev };
			delete newErrors[fieldName];
			return newErrors;
		});
	}, []);

	/**
	 * Clear all errors.
	 */
	const clearAllErrors = useCallback(() => {
		setErrors({});
	}, []);

	/**
	 * Check if all fields are valid.
	 */
	const isValid = useMemo(() => {
		return Object.values(errors).every((error) => error === null || error === undefined);
	}, [errors]);

	/**
	 * Get error for a specific field (only if touched).
	 *
	 * @param {string} fieldName Field name.
	 * @return {string|null} Error message or null.
	 */
	const getFieldError = useCallback(
		(fieldName) => {
			return touched[fieldName] ? errors[fieldName] : null;
		},
		[errors, touched]
	);

	return {
		errors,
		touched,
		validate,
		validateField,
		touchField,
		clearError,
		clearAllErrors,
		isValid,
		getFieldError,
	};
};

export default useValidation;
