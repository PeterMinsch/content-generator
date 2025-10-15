/**
 * useGeneration Hook
 *
 * Custom hook for handling content block generation.
 *
 * @package
 */

import { useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import API from '../services/api';
import { useBlockStatus } from '../context/BlockStatusContext';

/**
 * Format error message for user display.
 *
 * @param {Error} error Error object.
 * @return {string} User-friendly error message.
 */
const formatErrorMessage = (error) => {
	// Check for specific error types
	if (error.code === 'rest_forbidden') {
		return __(
			'Permission denied. Please refresh the page and try again.',
			'seo-generator'
		);
	}

	if (error.code === 'rest_no_route') {
		return __(
			'API endpoint not found. Please contact support.',
			'seo-generator'
		);
	}

	if (error.message && error.message.includes('rate limit')) {
		return __(
			'Rate limit exceeded. Please try again later.',
			'seo-generator'
		);
	}

	if (error.message && error.message.includes('API key')) {
		return __(
			'OpenAI API key is missing or invalid. Please check your settings.',
			'seo-generator'
		);
	}

	if (error.message && error.message.includes('timeout')) {
		return __(
			'Request timed out. Please try again.',
			'seo-generator'
		);
	}

	if (error.message && error.message.includes('network')) {
		return __(
			'Network error. Please check your connection.',
			'seo-generator'
		);
	}

	// Generic error message
	return error.message || __('Generation failed. Please try again.', 'seo-generator');
};

/**
 * useGeneration hook.
 *
 * @param {number} postId    Post ID.
 * @param {string} blockType Block type.
 * @return {Object} Generation functions and state.
 */
export const useGeneration = (postId, blockType) => {
	const [isGenerating, setIsGenerating] = useState(false);
	const {
		updateBlockStatus,
		setBlockError,
		clearBlockError,
		getBlockStatus,
		getBlockError,
	} = useBlockStatus();

	const generateBlock = useCallback(
		async (context = {}) => {
			setIsGenerating(true);
			clearBlockError(blockType);
			updateBlockStatus(blockType, 'generating');

			try {
				const response = await API.generateBlock(
					postId,
					blockType,
					context
				);

				if (response.success && response.content) {
					updateBlockStatus(blockType, 'generated');
					return response.content;
				} else {
					throw new Error(
						response.message ||
							__('Generation failed', 'seo-generator')
					);
				}
			} catch (error) {
				console.error('Generation error:', error);
				const errorMessage = formatErrorMessage(error);
				setBlockError(blockType, errorMessage);
				updateBlockStatus(blockType, 'failed');
				throw error;
			} finally {
				setIsGenerating(false);
			}
		},
		[
			postId,
			blockType,
			updateBlockStatus,
			setBlockError,
			clearBlockError,
		]
	);

	const retryGeneration = useCallback(
		async (context = {}) => {
			clearBlockError(blockType);
			return await generateBlock(context);
		},
		[blockType, clearBlockError, generateBlock]
	);

	return {
		generateBlock,
		retryGeneration,
		isGenerating,
		status: getBlockStatus(blockType),
		error: getBlockError(blockType),
	};
};

export default useGeneration;
