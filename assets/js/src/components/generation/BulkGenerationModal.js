/**
 * BulkGenerationModal Component
 *
 * Modal for bulk generation of all content blocks with real-time progress.
 *
 * @package
 */

import { createElement, useEffect } from '@wordpress/element';
import { Modal, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ProgressBar from '../common/ProgressBar';
import BlockProgressList from './BlockProgressList';
import CompletionSummary from './CompletionSummary';
import { useBulkGeneration } from '../../hooks/useBulkGeneration';
import { BLOCK_ORDER } from './BlockProgressList';

/**
 * BulkGenerationModal component.
 *
 * @param {Object}   props          Component props.
 * @param {boolean}  props.isOpen   Whether modal is open.
 * @param {Function} props.onClose  Callback when modal should close.
 * @param {number}   props.postId   Post ID.
 * @param {Object}   props.context  Generation context (title, topic, focusKeyword).
 * @return {Element|null} The BulkGenerationModal component or null.
 */
const BulkGenerationModal = ({ isOpen, onClose, postId, context = {} }) => {
	const {
		isGenerating,
		progress,
		startGeneration,
		cancelGeneration,
		resetProgress,
	} = useBulkGeneration(postId, context);

	// Start generation when modal opens
	useEffect(() => {
		if (isOpen && !isGenerating && progress.current === 0) {
			startGeneration(BLOCK_ORDER);
		}
	}, [isOpen]);

	const isComplete = !isGenerating && progress.current === progress.total && progress.total > 0;
	const totalCost = progress.costs.reduce((sum, cost) => sum + cost, 0);
	const generatedCount = Object.values(progress.statuses).filter(
		(status) => status === 'generated'
	).length;

	const handleClose = () => {
		resetProgress();
		onClose();
	};

	const handleCancel = () => {
		cancelGeneration();
	};

	const handleRetryFailed = () => {
		// Get failed block types
		const failedBlocks = Object.entries(progress.statuses)
			.filter(([_, status]) => status === 'failed')
			.map(([blockType, _]) => blockType);

		if (failedBlocks.length > 0) {
			resetProgress();
			startGeneration(failedBlocks);
		}
	};

	if (!isOpen) {
		return null;
	}

	return createElement(
		Modal,
		{
			title: isComplete
				? ''
				: __('Generating Content...', 'seo-generator'),
			isDismissible: isComplete,
			onRequestClose: isComplete ? handleClose : null,
			className: 'bulk-generation-modal',
		},
		!isComplete &&
			createElement(
				'div',
				{ className: 'bulk-modal-content generating' },
				createElement(ProgressBar, {
					current: progress.current,
					total: progress.total,
				}),
				createElement(BlockProgressList, {
					statuses: progress.statuses,
					durations: progress.durations,
					currentBlock: progress.currentBlock,
				}),
				progress.timeRemaining &&
					createElement(
						'div',
						{ className: 'bulk-time-remaining' },
						createElement(
							'span',
							{ className: 'dashicons dashicons-clock' }
						),
						createElement(
							'span',
							null,
							__('Estimated time remaining: ', 'seo-generator') +
								progress.timeRemaining
						)
					),
				createElement(
					'div',
					{ className: 'bulk-modal-actions' },
					!progress.cancelled &&
						isGenerating &&
						createElement(Button, {
							variant: 'secondary',
							onClick: handleCancel,
							children: __('Cancel', 'seo-generator'),
						})
				)
			),
		isComplete &&
			createElement(CompletionSummary, {
				cancelled: progress.cancelled,
				totalBlocks: progress.total,
				generated: generatedCount,
				failed: progress.errors,
				totalCost,
				onClose: handleClose,
				onRetryFailed: handleRetryFailed,
			})
	);
};

export default BulkGenerationModal;
