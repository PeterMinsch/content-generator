/**
 * CharacterCounter Component
 *
 * Displays character count with visual feedback when limit is exceeded.
 *
 * @package
 */

import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * CharacterCounter component.
 *
 * @param {Object}  props            Component props.
 * @param {number}  props.current    Current character count.
 * @param {number}  props.max        Maximum character limit.
 * @param {boolean} props.showWarning Whether to show warning at 90% (default true).
 * @return {Element} The CharacterCounter component.
 */
const CharacterCounter = ({ current = 0, max, showWarning = true }) => {
	const percentage = max > 0 ? (current / max) * 100 : 0;
	const isWarning = showWarning && percentage >= 90 && percentage < 100;
	const isError = current > max;

	let className = 'char-counter';
	if (isError) {
		className += ' char-counter-error';
	} else if (isWarning) {
		className += ' char-counter-warning';
	}

	return createElement(
		'div',
		{ className },
		createElement(
			'span',
			null,
			`${current} / ${max} `,
			__('characters', 'seo-generator'),
			isError && ` ${__('(exceeds limit)', 'seo-generator')}`
		)
	);
};

export default CharacterCounter;
