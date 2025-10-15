/**
 * CollapsibleBlock Component
 *
 * Reusable wrapper for content block components with collapsible functionality.
 *
 * @package
 */

import { createElement } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import StatusIndicator from '../common/StatusIndicator';
import ErrorMessage from '../common/ErrorMessage';

/**
 * CollapsibleBlock component.
 *
 * @param {Object}   props            Component props.
 * @param {string}   props.title      Block title displayed in header.
 * @param {boolean}  props.isExpanded Whether block is expanded.
 * @param {Function} props.onToggle   Callback when header is clicked.
 * @param {string}   props.status     Block status (not_generated|generating|generated|failed|edited).
 * @param {Function} props.onGenerate Callback when Generate button is clicked.
 * @param {string}   props.error      Error message if status is 'failed'.
 * @param {Function} props.onRetry    Callback when Retry button is clicked.
 * @param {Element}  props.children   Block content to display when expanded.
 * @return {Element} The CollapsibleBlock component.
 */
const CollapsibleBlock = ({
	title,
	isExpanded,
	onToggle,
	status = 'not_generated',
	onGenerate,
	error,
	onRetry,
	children,
}) => {
	const getButtonText = () => {
		if (status === 'generating') {
			return __('Generating...', 'seo-generator');
		}
		if (status === 'generated' || status === 'edited') {
			return __('Regenerate', 'seo-generator');
		}
		return __('Generate', 'seo-generator');
	};

	return createElement(
		'div',
		{
			className: `collapsible-block ${
				isExpanded ? 'expanded' : 'collapsed'
			} status-${status}`,
		},
		createElement(
			'div',
			{
				className: 'block-header',
				onClick: onToggle,
				role: 'button',
				tabIndex: 0,
				onKeyPress: (e) => {
					if (e.key === 'Enter' || e.key === ' ') {
						onToggle();
					}
				},
			},
			createElement(
				'span',
				{ className: 'block-toggle' },
				isExpanded ? '▼' : '▶'
			),
			createElement('h3', { className: 'block-title' }, title),
			createElement(StatusIndicator, { status }),
			createElement(Button, {
				variant: 'secondary',
				size: 'small',
				onClick: (e) => {
					e.stopPropagation();
					onGenerate();
				},
				disabled: status === 'generating',
				children: getButtonText(),
			})
		),
		error &&
			status === 'failed' &&
			createElement(ErrorMessage, {
				message: error,
				onRetry,
				showRetry: true,
			}),
		isExpanded &&
			createElement('div', { className: 'block-content' }, children)
	);
};

export default CollapsibleBlock;
