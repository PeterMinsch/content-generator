/**
 * BlockItem Component
 *
 * Displays a single content block with status and generation controls.
 *
 * @package
 */

import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * BlockItem component.
 *
 * @param {Object}   props            Component props.
 * @param {string}   props.blockName  Block name/label.
 * @param {string}   props.blockId    Block identifier.
 * @param {Object}   props.blockData  Block field data.
 * @param {Function} props.onGenerate Callback when Generate button clicked.
 * @param {string}   props.status     Block status (not_generated, generating, generated, failed, edited).
 * @return {Element} The BlockItem component.
 */
const BlockItem = ({
	blockName,
	blockId,
	blockData,
	onGenerate,
	status = 'not_generated',
}) => {
	const [isCollapsed, setIsCollapsed] = useState(true);

	const getStatusIcon = () => {
		switch (status) {
			case 'generating':
				return '⏳';
			case 'generated':
				return '✓';
			case 'failed':
				return '✗';
			case 'edited':
				return '✏️';
			default:
				return '○';
		}
	};

	const getStatusLabel = () => {
		switch (status) {
			case 'generating':
				return __('Generating…', 'seo-generator');
			case 'generated':
				return __('Generated', 'seo-generator');
			case 'failed':
				return __('Failed', 'seo-generator');
			case 'edited':
				return __('Edited', 'seo-generator');
			default:
				return __('Not Generated', 'seo-generator');
		}
	};

	return (
		<div className="seo-generator-block-item">
			<div
				className="block-item-header"
				onClick={() => setIsCollapsed(!isCollapsed)}
				role="button"
				tabIndex={0}
				onKeyPress={(e) => {
					if (e.key === 'Enter' || e.key === ' ') {
						setIsCollapsed(!isCollapsed);
					}
				}}
			>
				<span className="block-toggle">{isCollapsed ? '▶' : '▼'}</span>
				<span className="block-name">{blockName}</span>
				<span className="block-status">
					<span className="status-icon">{getStatusIcon()}</span>
					<span className="status-label">{getStatusLabel()}</span>
				</span>
				<Button
					variant="secondary"
					size="small"
					onClick={(e) => {
						e.stopPropagation();
						onGenerate(blockId);
					}}
					disabled={status === 'generating'}
				>
					{__('Generate', 'seo-generator')}
				</Button>
			</div>

			{!isCollapsed && (
				<div className="block-item-content">
					<p className="placeholder-text">
						{__(
							'Block content editor will be implemented in Story 3.3',
							'seo-generator'
						)}
					</p>
					{blockData && (
						<pre>{JSON.stringify(blockData, null, 2)}</pre>
					)}
				</div>
			)}
		</div>
	);
};

export default BlockItem;
