/**
 * Validation Rules Configuration
 *
 * Centralized validation rules for all form fields.
 *
 * @package
 */

/**
 * Validation rules for all fields.
 * Structure: { fieldName: { required, maxLength, type } }
 */
export const validationRules = {
	// Basic Info - Required Fields
	title: { required: true },
	focusKeyword: { required: true },

	// Hero Block
	hero_title: { maxLength: 100 },
	hero_subtitle: { maxLength: 150 },
	hero_summary: { maxLength: 400 },

	// SERP Answer Block
	answer_heading: { maxLength: 100 },
	answer_paragraph: { maxLength: 600 },

	// Product Criteria Block
	// Repeater items handled per-item

	// Materials Block
	// No specific limits defined

	// Process Block
	// Steps handled per-item with maxLength: 400

	// Comparison Block
	// left_text and right_text: 200 chars each (handled per-item)

	// Product Showcase Block
	alt_image_url: { type: 'url' },

	// Size & Fit Block
	// No specific limits defined

	// Care & Warranty Block
	// No specific limits defined

	// Ethics Block
	ethics_text: { maxLength: 800 },
	// cert_link in certifications repeater: type: 'url' (handled per-item)

	// FAQs Block
	// Questions/answers handled per-item with answer maxLength: 600

	// CTA Block
	cta_primary_url: { type: 'url' },
	cta_secondary_url: { type: 'url' },

	// SEO Meta Fields
	seo_title: { maxLength: 65 },
	seo_meta_description: { maxLength: 165 },
	seo_canonical: { type: 'url' },
};

/**
 * Get validation rules for a specific field.
 *
 * @param {string} fieldName Field name.
 * @return {Object|null} Validation rules or null if not found.
 */
export const getFieldRules = (fieldName) => {
	return validationRules[fieldName] || null;
};

/**
 * Check if a field is required.
 *
 * @param {string} fieldName Field name.
 * @return {boolean} Whether the field is required.
 */
export const isFieldRequired = (fieldName) => {
	const rules = validationRules[fieldName];
	return rules?.required === true;
};

/**
 * Get maximum character length for a field.
 *
 * @param {string} fieldName Field name.
 * @return {number|null} Maximum length or null if no limit.
 */
export const getFieldMaxLength = (fieldName) => {
	const rules = validationRules[fieldName];
	return rules?.maxLength || null;
};

export default validationRules;
