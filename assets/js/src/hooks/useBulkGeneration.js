/**
 * useBulkGeneration Hook
 *
 * Custom hook for handling sequential bulk generation of all content blocks.
 *
 * @package
 */

import { useState, useCallback, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import API from '../services/api';
import { useBlockStatus } from '../context/BlockStatusContext';

/**
 * Calculate estimated time remaining.
 *
 * @param {number} completedCount Number of completed blocks.
 * @param {number} totalBlocks Total number of blocks.
 * @param {Array} completedDurations Array of completion times in seconds.
 * @return {string|null} Formatted time string or null.
 */
const calculateTimeRemaining = (
	completedCount,
	totalBlocks,
	completedDurations
) => {
	if (completedCount === 0 || completedDurations.length === 0) {
		return null;
	}

	const avgTime =
		completedDurations.reduce((a, b) => a + b, 0) / completedCount;
	const remainingBlocks = totalBlocks - completedCount;
	const estimatedSeconds = avgTime * remainingBlocks;

	if (estimatedSeconds < 60) {
		return __(`${Math.round(estimatedSeconds)}s`, 'seo-generator');
	}

	const minutes = Math.floor(estimatedSeconds / 60);
	const seconds = Math.round(estimatedSeconds % 60);

	return `${minutes}m ${seconds}s`;
};

/**
 * useBulkGeneration hook.
 *
 * @param {number} postId Post ID.
 * @param {Object} context Generation context (title, topic, focusKeyword).
 * @return {Object} Bulk generation functions and state.
 */
export const useBulkGeneration = (postId, context = {}) => {
	const [isGenerating, setIsGenerating] = useState(false);
	const [progress, setProgress] = useState({
		current: 0,
		total: 0,
		currentBlock: null,
		statuses: {},
		durations: {},
		costs: [],
		errors: [],
		cancelled: false,
		timeRemaining: null,
	});

	const { updateBlockStatus } = useBlockStatus();
	const cancelledRef = useRef(false);

	const startGeneration = useCallback(
		async (blockTypes) => {
			setIsGenerating(true);
			cancelledRef.current = false;

			// Initialize progress
			setProgress({
				current: 0,
				total: blockTypes.length,
				currentBlock: null,
				statuses: {},
				durations: {},
				costs: [],
				errors: [],
				cancelled: false,
				timeRemaining: null,
			});

			const completedTimes = [];

			for (let i = 0; i < blockTypes.length; i++) {
				// Check if cancelled
				if (cancelledRef.current) {
					setProgress((prev) => ({
						...prev,
						cancelled: true,
						currentBlock: null,
					}));
					break;
				}

				const blockType = blockTypes[i];
				const startTime = Date.now();

				// Update current block
				setProgress((prev) => ({
					...prev,
					currentBlock: blockType,
					statuses: {
						...prev.statuses,
						[blockType]: 'generating',
					},
				}));

				// Update global block status
				updateBlockStatus(blockType, 'generating');

				try {
					const response = await API.generateBlock(
						postId,
						blockType,
						context
					);

					const endTime = Date.now();
					const duration = (endTime - startTime) / 1000;
					completedTimes.push(duration);

					const cost = response.data?.cost || 0;

					// Update progress on success
					setProgress((prev) => {
						const newProgress = {
							...prev,
							current: i + 1,
							statuses: {
								...prev.statuses,
								[blockType]: 'generated',
							},
							durations: {
								...prev.durations,
								[blockType]: duration,
							},
							costs: [...prev.costs, cost],
							timeRemaining: calculateTimeRemaining(
								i + 1,
								blockTypes.length,
								completedTimes
							),
						};
						return newProgress;
					});

					// Update global block status
					updateBlockStatus(blockType, 'generated');
				} catch (error) {
					console.error(`Block generation failed: ${blockType}`, error);

					const errorMessage =
						error.message ||
						__('Generation failed', 'seo-generator');

					// Update progress on failure
					setProgress((prev) => ({
						...prev,
						current: i + 1,
						statuses: {
							...prev.statuses,
							[blockType]: 'failed',
						},
						errors: [
							...prev.errors,
							{ blockType, error: errorMessage },
						],
					}));

					// Update global block status
					updateBlockStatus(blockType, 'failed');
				}
			}

			setIsGenerating(false);
		},
		[postId, context, updateBlockStatus]
	);

	const cancelGeneration = useCallback(() => {
		cancelledRef.current = true;
	}, []);

	const resetProgress = useCallback(() => {
		setProgress({
			current: 0,
			total: 0,
			currentBlock: null,
			statuses: {},
			durations: {},
			costs: [],
			errors: [],
			cancelled: false,
			timeRemaining: null,
		});
		cancelledRef.current = false;
	}, []);

	return {
		isGenerating,
		progress,
		startGeneration,
		cancelGeneration,
		resetProgress,
	};
};

export default useBulkGeneration;
