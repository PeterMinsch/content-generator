/**
 * PageEditor Component
 *
 * Main component for editing SEO pages with AI-generated content blocks.
 *
 * @package
 */

import { useState, useEffect, useCallback, createElement } from '@wordpress/element';
import { Spinner, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import BasicInfo from './BasicInfo';
import BlockList from './BlockList';
import GenerationControls from './GenerationControls';
import API from '../../services/api';
import { BlockStatusProvider } from '../../context/BlockStatusContext';

/**
 * PageEditor component.
 *
 * @return {Element} The PageEditor component.
 */
const PageEditor = () => {
	const [isLoading, setIsLoading] = useState(true);
	const [error, setError] = useState(null);
	const [pageData, setPageData] = useState({
		title: '',
		slug: '',
		topic: '',
		focusKeyword: '',
		blocks: {},
	});
	const [blockStatus, setBlockStatus] = useState({});
	const [expandedBlocks, setExpandedBlocks] = useState({ hero: true });
	const [topics, setTopics] = useState([]);
	const [isGenerating, setIsGenerating] = useState(false);
	const [isSaving, setIsSaving] = useState(false);

	// Get post ID from localized data.
	const postId =
		window.seoGeneratorData?.postId ||
		new URLSearchParams(window.location.search).get('post') ||
		0;

	// Load initial data on mount.
	useEffect(() => {
		const loadInitialData = async () => {
			try {
				setIsLoading(true);

				// Fetch topics.
				const topicsData = await API.fetchTopicTerms();
				setTopics(topicsData || []);

				// Fetch page data if editing existing post.
				if (postId) {
					try {
						const pageDataResponse =
							await API.fetchPageData(postId);
						setPageData(pageDataResponse);

						// Initialize block status.
						const initialStatus = {};
						Object.keys(pageDataResponse.blocks || {}).forEach(
							(blockId) => {
								initialStatus[blockId] = pageDataResponse
									.blocks[blockId]
									? 'generated'
									: 'not_generated';
							}
						);
						setBlockStatus(initialStatus);
					} catch (pageError) {
						// Page might be new, that's okay.
						if (pageError.code !== 'rest_post_invalid_id') {
							throw pageError;
						}
					}
				}

				setIsLoading(false);
			} catch (err) {
				setError(
					err.message ||
						__('Failed to load page data', 'seo-generator')
				);
				setIsLoading(false);
			}
		};

		loadInitialData();
	}, [postId]);

	/**
	 * Handle basic info changes.
	 *
	 * @param {Object} newBasicInfo Updated basic info.
	 */
	const handleBasicInfoChange = useCallback((newBasicInfo) => {
		setPageData((prev) => ({
			...prev,
			...newBasicInfo,
		}));
	}, []);

	/**
	 * Handle block data changes.
	 *
	 * @param {string} blockId Block ID.
	 * @param {Object} newData New block data.
	 */
	const handleBlockChange = useCallback((blockId, newData) => {
		setPageData((prev) => ({
			...prev,
			blocks: {
				...prev.blocks,
				[blockId]: newData,
			},
		}));

		// Mark as edited if it was previously generated.
		setBlockStatus((prev) => {
			if (prev[blockId] === 'generated') {
				return {
					...prev,
					[blockId]: 'edited',
				};
			}
			return prev;
		});
	}, []);

	/**
	 * Handle block toggle (expand/collapse).
	 *
	 * @param {string} blockId Block ID to toggle.
	 */
	const handleToggleBlock = useCallback((blockId) => {
		setExpandedBlocks((prev) => ({
			...prev,
			[blockId]: !prev[blockId],
		}));
	}, []);

	/**
	 * Handle single block generation.
	 *
	 * @param {string} blockId Block ID to generate.
	 */
	const handleGenerateBlock = useCallback(async (blockId) => {
		if (!postId) {
			setError(
				__(
					'Please save the page first before generating content',
					'seo-generator'
				)
			);
			return;
		}

		try {
			setBlockStatus((prev) => ({
				...prev,
				[blockId]: 'generating',
			}));

			const result = await API.generateBlock(postId, blockId, {
				page_title: pageData.title,
				page_topic: pageData.topic,
				focus_keyword: pageData.focusKeyword,
			});

			setPageData((prev) => ({
				...prev,
				blocks: {
					...prev.blocks,
					[blockId]: result.content,
				},
			}));

			setBlockStatus((prev) => ({
				...prev,
				[blockId]: 'generated',
			}));
		} catch (err) {
			setBlockStatus((prev) => ({
				...prev,
				[blockId]: 'failed',
			}));
			setError(
				err.message || __('Failed to generate block', 'seo-generator')
			);
		}
	}, [postId, pageData.title, pageData.topic, pageData.focusKeyword]);

	/**
	 * Handle generate all blocks.
	 */
	const handleGenerateAll = async () => {
		if (!postId) {
			setError(
				__(
					'Please save the page first before generating content',
					'seo-generator'
				)
			);
			return;
		}

		try {
			setIsGenerating(true);
			await API.generateAllBlocks(postId);
			// Refresh page data after bulk generation.
			const updatedData = await API.fetchPageData(postId);
			setPageData(updatedData);
			setIsGenerating(false);
		} catch (err) {
			setError(
				err.message ||
					__('Failed to generate all blocks', 'seo-generator')
			);
			setIsGenerating(false);
		}
	};

	/**
	 * Handle save draft.
	 */
	const handleSave = useCallback(async () => {
		if (!postId) {
			setError(__('Cannot save: Post ID not found', 'seo-generator'));
			return;
		}

		try {
			setIsSaving(true);
			await API.updatePageData(postId, pageData);
			setIsSaving(false);
		} catch (err) {
			setError(err.message || __('Failed to save', 'seo-generator'));
			setIsSaving(false);
		}
	}, [postId, pageData]);

	if (isLoading) {
		return createElement(
			'div',
			{ className: 'seo-generator-page-editor loading' },
			createElement(Spinner),
			createElement('p', null, __('Loading editor…', 'seo-generator'))
		);
	}

	return createElement(
		BlockStatusProvider,
		{ initialData: pageData.blocks },
		createElement(
			'div',
			{ className: 'seo-generator-page-editor' },
			createElement('h1', null, __('SEO Content Generator', 'seo-generator')),
			error &&
				createElement(
					Notice,
					{
						status: 'error',
						onRemove: () => setError(null),
						isDismissible: true,
					},
					error
				),
			createElement(GenerationControls, {
				postId,
				context: {
					page_title: pageData.title,
					page_topic: pageData.topic,
					focus_keyword: pageData.focusKeyword,
				},
				onSave: handleSave,
				isSaving,
			}),
			createElement(BasicInfo, {
				pageData,
				onChange: handleBasicInfoChange,
				topics,
			}),
			createElement(BlockList, {
				postId,
				context: {
					page_title: pageData.title,
					page_topic: pageData.topic,
					focus_keyword: pageData.focusKeyword,
				},
				blocks: pageData.blocks,
				blockStatus,
				expandedBlocks,
				onBlockChange: handleBlockChange,
				onGenerateBlock: handleGenerateBlock,
				onToggleBlock: handleToggleBlock,
			})
		)
	);
};

export default PageEditor;
