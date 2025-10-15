/**
 * API Service
 *
 * Handles all API communication with WordPress REST API.
 *
 * @package
 */

import apiFetch from '@wordpress/api-fetch';

/**
 * API service for SEO Generator.
 */
const API = {
	/**
	 * Fetch page data for a specific post.
	 *
	 * @param {number} postId Post ID to fetch.
	 * @return {Promise<Object>} Page data.
	 */
	async fetchPageData(postId) {
		return await apiFetch({
			path: `/seo-generator/v1/pages/${postId}`,
			method: 'GET',
		});
	},

	/**
	 * Fetch all terms from seo-topic taxonomy.
	 *
	 * @return {Promise<Array>} Array of topic terms.
	 */
	async fetchTopicTerms() {
		return await apiFetch({
			path: '/wp/v2/seo-topics',
			method: 'GET',
		});
	},

	/**
	 * Update page data.
	 *
	 * @param {number} postId Post ID to update.
	 * @param {Object} data   Page data to save.
	 * @return {Promise<Object>} Updated page data.
	 */
	async updatePageData(postId, data) {
		return await apiFetch({
			path: `/seo-generator/v1/pages/${postId}`,
			method: 'PUT',
			data,
		});
	},

	/**
	 * Generate content for a single block.
	 *
	 * @param {number} postId    Post ID.
	 * @param {string} blockType Block type to generate.
	 * @param {Object} context   Additional context for generation.
	 * @return {Promise<Object>} Generated content and metadata.
	 */
	async generateBlock(postId, blockType, context = {}) {
		return await apiFetch({
			path: `/seo-generator/v1/pages/${postId}/generate`,
			method: 'POST',
			data: {
				blockType,
				context,
			},
		});
	},

	/**
	 * Generate all blocks for a page.
	 *
	 * @param {number} postId Post ID.
	 * @return {Promise<Object>} Generation results.
	 */
	async generateAllBlocks(postId) {
		return await apiFetch({
			path: `/seo-generator/v1/pages/${postId}/generate-all`,
			method: 'POST',
		});
	},

	/**
	 * Fetch image data from WordPress Media Library.
	 *
	 * @param {number} imageId Image attachment ID.
	 * @return {Promise<Object>} Image data object.
	 */
	async fetchImageData(imageId) {
		const response = await apiFetch({
			path: `/wp/v2/media/${imageId}`,
			method: 'GET',
		});

		return {
			id: response.id,
			url: response.source_url,
			thumbnailUrl:
				response.media_details?.sizes?.thumbnail?.source_url ||
				response.source_url,
			alt: response.alt_text || '',
		};
	},
};

export default API;
