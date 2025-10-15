/**
 * GenerationControls Component
 *
 * Displays controls for bulk generation and saving.
 *
 * @package
 */

import { createElement, useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import BulkGenerationModal from '../generation/BulkGenerationModal';

/**
 * GenerationControls component.
 *
 * @param {Object}   props          Component props.
 * @param {number}   props.postId   Post ID.
 * @param {Object}   props.context  Generation context (title, topic, focusKeyword).
 * @param {Function} props.onSave   Callback when Save Draft clicked.
 * @param {boolean}  props.isSaving Whether save is in progress.
 * @return {Element} The GenerationControls component.
 */
const GenerationControls = ({
	postId,
	context = {},
	onSave,
	isSaving = false,
}) => {
	const [isModalOpen, setIsModalOpen] = useState(false);

	const handleGenerateAll = () => {
		// Validate required fields before generation
		if (!context.page_title || context.page_title.trim() === '') {
			alert(__('Please enter a Page Title before generating content.', 'seo-generator'));
			return;
		}

		if (!context.focus_keyword || context.focus_keyword.trim() === '') {
			alert(__('Please enter a Focus Keyword before generating content.', 'seo-generator'));
			return;
		}

		setIsModalOpen(true);
	};

	const handleCloseModal = () => {
		setIsModalOpen(false);
	};

	// Check if required fields are filled
	const canGenerate =
		context.page_title && context.page_title.trim() !== '' &&
		context.focus_keyword && context.focus_keyword.trim() !== '';

	return createElement(
		'div',
		{ className: 'seo-generator-controls' },
		createElement(Button, {
			variant: 'primary',
			onClick: handleGenerateAll,
			disabled: !canGenerate,
			title: !canGenerate
				? __('Please fill in Page Title and Focus Keyword', 'seo-generator')
				: '',
			children: __('Generate All Blocks', 'seo-generator'),
		}),
		createElement(Button, {
			variant: 'secondary',
			onClick: onSave,
			disabled: isSaving,
			isBusy: isSaving,
			children: isSaving
				? __('Saving…', 'seo-generator')
				: __('Save Draft', 'seo-generator'),
		}),
		createElement(BulkGenerationModal, {
			isOpen: isModalOpen,
			onClose: handleCloseModal,
			postId,
			context,
		})
	);
};

export default GenerationControls;
