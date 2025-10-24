<?php
/**
 * Image Generator Service
 *
 * Orchestrates the two-stage AI image generation system:
 * Stage 1: PromptGeneratorService (GPT-4 generates optimized prompts)
 * Stage 2: DalleService (DALL-E 3 generates images)
 *
 * Includes smart caching to minimize API costs.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

/**
 * Class ImageGeneratorService
 *
 * High-level orchestrator for AI image generation with caching.
 */
class ImageGeneratorService {

	/**
	 * Prompt generator service
	 *
	 * @var PromptGeneratorService
	 */
	private $prompt_generator;

	/**
	 * DALL-E service
	 *
	 * @var DalleService
	 */
	private $dalle_service;

	/**
	 * Cache table name
	 *
	 * @var string
	 */
	private $cache_table;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->prompt_generator = new PromptGeneratorService();
		$this->dalle_service    = new DalleService();
		$this->cache_table      = $wpdb->prefix . 'seo_image_cache';
	}

	/**
	 * Generate an image for a related link with caching
	 *
	 * @param string $page_title       Main page title (context).
	 * @param string $link_title       Related link title.
	 * @param string $link_description Link description.
	 * @param string $link_category    Category tag.
	 * @param int    $post_id          WordPress post ID.
	 * @return int|false Attachment ID on success, false on failure
	 */
	public function generateImageForLink( string $page_title, string $link_title, string $link_description, string $link_category, int $post_id = 0 ) {
		try {
			// Create context hash for caching
			$context_hash = $this->createContextHash( $link_title, $link_category, $link_description );

			// Check cache first
			$cached_attachment_id = $this->getCachedImage( $context_hash );
			if ( $cached_attachment_id ) {
				error_log( sprintf(
					'[ImageGenerator] Using cached image (attachment_id: %d) for "%s"',
					$cached_attachment_id,
					$link_title
				) );
				return $cached_attachment_id;
			}

			// Stage 1: Generate optimized prompt using GPT-4
			error_log( '[ImageGenerator] Stage 1: Generating optimized prompt for "' . $link_title . '"' );
			$dalle_prompt = $this->prompt_generator->generatePrompt(
				$page_title,
				$link_title,
				$link_description,
				$link_category
			);

			// Stage 2: Generate image using DALL-E 3
			error_log( '[ImageGenerator] Stage 2: Generating image with DALL-E 3' );
			$filename      = sanitize_title( $link_title ) . '-' . time();
			$attachment_id = $this->dalle_service->generateImage( $dalle_prompt, $filename, $post_id );

			if ( ! $attachment_id ) {
				throw new \Exception( 'Failed to generate image with DALL-E' );
			}

			// Cache the result
			$this->cacheImage( $context_hash, $link_title, $link_category, $dalle_prompt, $attachment_id );

			// Track cost
			$this->trackCost();

			error_log( sprintf(
				'[ImageGenerator] Successfully generated new image (attachment_id: %d) for "%s"',
				$attachment_id,
				$link_title
			) );

			return $attachment_id;

		} catch ( \Exception $e ) {
			error_log( '[ImageGenerator] Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Create a context hash for caching
	 *
	 * Uses link_title + category as primary hash.
	 * This allows reuse across different pages with same category.
	 *
	 * @param string $link_title       Link title.
	 * @param string $link_category    Category.
	 * @param string $link_description Description (optional, not used in hash).
	 * @return string MD5 hash
	 */
	private function createContextHash( string $link_title, string $link_category, string $link_description ): string {
		// Normalize strings for consistent hashing
		$normalized_title    = strtolower( trim( $link_title ) );
		$normalized_category = strtolower( trim( $link_category ) );

		// Hash based on title + category (most important factors)
		$context_string = $normalized_title . '|' . $normalized_category;

		return md5( $context_string );
	}

	/**
	 * Get cached image attachment ID
	 *
	 * @param string $context_hash Context hash.
	 * @return int|false Attachment ID if found, false otherwise
	 */
	private function getCachedImage( string $context_hash ) {
		global $wpdb;

		$result = $wpdb->get_var( $wpdb->prepare(
			"SELECT attachment_id FROM {$this->cache_table} WHERE context_hash = %s LIMIT 1",
			$context_hash
		) );

		if ( $result && get_post( $result ) ) {
			// Increment usage count
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$this->cache_table} SET usage_count = usage_count + 1, last_used = NOW() WHERE context_hash = %s",
				$context_hash
			) );
			return (int) $result;
		}

		return false;
	}

	/**
	 * Cache generated image
	 *
	 * @param string $context_hash  Context hash.
	 * @param string $link_title    Link title.
	 * @param string $link_category Category.
	 * @param string $dalle_prompt  DALL-E prompt used.
	 * @param int    $attachment_id WordPress attachment ID.
	 */
	private function cacheImage( string $context_hash, string $link_title, string $link_category, string $dalle_prompt, int $attachment_id ): void {
		global $wpdb;

		$wpdb->replace(
			$this->cache_table,
			array(
				'context_hash'  => $context_hash,
				'link_title'    => $link_title,
				'link_category' => $link_category,
				'dalle_prompt'  => $dalle_prompt,
				'attachment_id' => $attachment_id,
				'usage_count'   => 1,
				'created_at'    => current_time( 'mysql' ),
				'last_used'     => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
		);

		error_log( sprintf(
			'[ImageGenerator] Cached image (hash: %s, attachment_id: %d) for "%s"',
			$context_hash,
			$attachment_id,
			$link_title
		) );
	}

	/**
	 * Track cost in WordPress options
	 */
	private function trackCost(): void {
		$total_cost   = (float) get_option( 'seo_generator_image_generation_cost', 0 );
		$total_images = (int) get_option( 'seo_generator_image_generation_count', 0 );

		// DALL-E 3 cost: $0.040 per image (1024x1024, standard quality)
		// GPT-4 cost: ~$0.0015 per prompt (assuming ~500 tokens total)
		$cost_per_generation = 0.040 + 0.0015;

		update_option( 'seo_generator_image_generation_cost', $total_cost + $cost_per_generation );
		update_option( 'seo_generator_image_generation_count', $total_images + 1 );
	}

	/**
	 * Get current cost statistics
	 *
	 * @return array Statistics array
	 */
	public static function getCostStats(): array {
		global $wpdb;
		$cache_table = $wpdb->prefix . 'seo_image_cache';

		$total_cost       = (float) get_option( 'seo_generator_image_generation_cost', 0 );
		$total_images     = (int) get_option( 'seo_generator_image_generation_count', 0 );
		$cached_images    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$cache_table}" );
		$total_usages     = (int) $wpdb->get_var( "SELECT SUM(usage_count) FROM {$cache_table}" );
		$cache_efficiency = $total_usages > 0 ? ( ( $total_usages - $total_images ) / $total_usages ) * 100 : 0;

		return array(
			'total_cost'         => number_format( $total_cost, 2 ),
			'total_generated'    => $total_images,
			'unique_cached'      => $cached_images,
			'total_usages'       => $total_usages,
			'cache_efficiency'   => number_format( $cache_efficiency, 1 ) . '%',
			'cost_per_image'     => $total_images > 0 ? number_format( $total_cost / $total_images, 4 ) : '0.0000',
			'estimated_savings'  => number_format( ( $total_usages - $total_images ) * 0.0415, 2 ),
		);
	}

	/**
	 * Clear all cached images
	 *
	 * @return bool Success
	 */
	public static function clearCache(): bool {
		global $wpdb;
		$cache_table = $wpdb->prefix . 'seo_image_cache';

		$result = $wpdb->query( "TRUNCATE TABLE {$cache_table}" );

		return false !== $result;
	}

	/**
	 * Get most used cached images
	 *
	 * @param int $limit Number of results to return.
	 * @return array
	 */
	public static function getMostUsedImages( int $limit = 10 ): array {
		global $wpdb;
		$cache_table = $wpdb->prefix . 'seo_image_cache';

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT link_title, link_category, usage_count, attachment_id, created_at
			FROM {$cache_table}
			ORDER BY usage_count DESC
			LIMIT %d",
			$limit
		), ARRAY_A );

		return $results ? $results : array();
	}
}
