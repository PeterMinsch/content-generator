<?php
/**
 * Image Matching Service
 *
 * Automatically selects relevant images based on content context and tags.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Handles automatic image selection based on tag matching.
 */
class ImageMatchingService {
	/**
	 * OpenAI service for AI-generated alt text.
	 *
	 * @var OpenAIService|null
	 */
	private ?OpenAIService $openai_service = null;

	/**
	 * Constructor.
	 *
	 * @param OpenAIService|null $openai_service Optional OpenAI service for AI alt text.
	 */
	public function __construct( ?OpenAIService $openai_service = null ) {
		$this->openai_service = $openai_service;
	}

	/**
	 * Find matching image based on context keywords.
	 *
	 * Algorithm:
	 * 1. Extract keywords from context (focus_keyword, topic, category, potential_folders)
	 * 2. NEW: Attempt folder-based matching first (if potential_folders provided)
	 * 3. Query images with ALL tags (AND operator)
	 * 4. Fallback: Query with first 2 tags
	 * 5. Fallback: Query with first 1 tag
	 * 6. Fallback: Return default image or null
	 * 7. If multiple matches, select random
	 *
	 * @param array $context Context array containing keywords.
	 * @return int|null Image attachment ID or null if no match.
	 */
	public function findMatchingImage( array $context ): ?int {
		// Extract keywords from context.
		$tags = $this->extractKeywords( $context );

		if ( empty( $tags ) ) {
			$this->log( 'No keywords found in context', 0, 0, null );
			return $this->getDefaultImage();
		}

		// Attempt 0: Try folder-based matching first (highest priority).
		$image_id = $this->findMatchingImageByFolder( $context );
		if ( $image_id ) {
			return $image_id;
		}

		// Attempt 1: Match ALL tags.
		$image_id = $this->attemptMatch( $tags, 1 );
		if ( $image_id ) {
			return $image_id;
		}

		// Attempt 2: Match first 2 tags.
		if ( count( $tags ) >= 2 ) {
			$image_id = $this->attemptMatch( array_slice( $tags, 0, 2 ), 2 );
			if ( $image_id ) {
				return $image_id;
			}
		}

		// Attempt 3: Match first 1 tag.
		if ( count( $tags ) >= 1 ) {
			$image_id = $this->attemptMatch( array_slice( $tags, 0, 1 ), 3 );
			if ( $image_id ) {
				return $image_id;
			}
		}

		// Final fallback: default image or null.
		$default_id = $this->getDefaultImage();
		if ( $default_id ) {
			$this->log( implode( ', ', $tags ), 4, 1, $default_id, 'Using default image' );
		} else {
			$this->log( implode( ', ', $tags ), 4, 0, null, 'No matches found' );
		}

		return $default_id;
	}

	/**
	 * Find matching image by folder tags.
	 *
	 * Prioritizes images that have folder-based tags matching the context.
	 *
	 * @param array $context Context array containing potential folder keywords.
	 * @return int|null Image ID or null if no folder match.
	 */
	public function findMatchingImageByFolder( array $context ): ?int {
		// Extract potential folder keywords from context.
		$folder_keywords = $this->extractFolderKeywords( $context );

		if ( empty( $folder_keywords ) ) {
			return null;
		}

		// Try each folder keyword.
		foreach ( $folder_keywords as $folder_keyword ) {
			$sanitized_folder = sanitize_title( $folder_keyword );

			// Query images with this folder tag and _seo_image_folder meta.
			$args = array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => '_seo_library_image',
						'value' => '1',
					),
					array(
						'key'     => '_seo_image_folder',
						'compare' => 'EXISTS',
					),
				),
				'tax_query'      => array(
					array(
						'taxonomy' => 'image_tag',
						'field'    => 'slug',
						'terms'    => $sanitized_folder,
					),
				),
			);

			$query = new \WP_Query( $args );

			if ( ! empty( $query->posts ) ) {
				$selected_id = $this->selectRandomImage( $query->posts );
				$this->log(
					$sanitized_folder,
					0,
					count( $query->posts ),
					$selected_id,
					'Folder match'
				);
				return $selected_id;
			}

			// Fallback: Try regular images with matching folder tag (no _seo_library_image requirement).
			$fallback_args = array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'post_mime_type' => 'image',
				'tax_query'      => array(
					array(
						'taxonomy' => 'image_tag',
						'field'    => 'slug',
						'terms'    => $sanitized_folder,
					),
				),
			);

			$fallback_query = new \WP_Query( $fallback_args );

			if ( ! empty( $fallback_query->posts ) ) {
				$selected_id = $this->selectRandomImage( $fallback_query->posts );
				$this->log(
					$sanitized_folder,
					0,
					count( $fallback_query->posts ),
					$selected_id,
					'Folder match (fallback: non-library images)'
				);
				return $selected_id;
			}
		}

		return null;
	}

	/**
	 * Attempt to match images with given tags.
	 *
	 * @param array $tags    Array of tag slugs.
	 * @param int   $attempt Attempt number (for logging).
	 * @return int|null Image ID or null if no match.
	 */
	private function attemptMatch( array $tags, int $attempt ): ?int {
		$matches = $this->queryImagesByTags( $tags );

		if ( empty( $matches ) ) {
			$this->log( implode( ', ', $tags ), $attempt, 0, null );
			return null;
		}

		// Select random image if multiple matches.
		$selected_id = $this->selectRandomImage( $matches );

		$this->log( implode( ', ', $tags ), $attempt, count( $matches ), $selected_id );

		return $selected_id;
	}

	/**
	 * Extract keywords from context and convert to tag slugs.
	 *
	 * @param array $context Context array.
	 * @return array Array of tag slugs.
	 */
	private function extractKeywords( array $context ): array {
		$keywords = array();

		// Extract focus_keyword.
		if ( ! empty( $context['focus_keyword'] ) ) {
			$keywords[] = $context['focus_keyword'];
		}

		// Extract topic.
		if ( ! empty( $context['topic'] ) ) {
			$keywords[] = $context['topic'];
		}

		// Extract category.
		if ( ! empty( $context['category'] ) ) {
			$keywords[] = $context['category'];
		}

		// Convert to tag slugs: lowercase, sanitize.
		$tags = array();
		foreach ( $keywords as $keyword ) {
			// Split multi-word keywords into individual tags.
			$words = preg_split( '/[\s\-_]+/', $keyword );
			foreach ( $words as $word ) {
				$slug = sanitize_title( $word );
				if ( ! empty( $slug ) && strlen( $slug ) > 2 ) { // Ignore very short words.
					$tags[] = $slug;
				}
			}
		}

		// Remove duplicates.
		return array_unique( $tags );
	}

	/**
	 * Extract potential folder keywords from context.
	 *
	 * Looks for explicit potential_folders array or derives from keywords.
	 *
	 * @param array $context Context array.
	 * @return array Array of potential folder names.
	 */
	private function extractFolderKeywords( array $context ): array {
		$folder_keywords = array();

		// Check for explicit potential_folders in context.
		if ( ! empty( $context['potential_folders'] ) && is_array( $context['potential_folders'] ) ) {
			$folder_keywords = array_merge( $folder_keywords, $context['potential_folders'] );
		}

		// Also derive from focus_keyword, topic, category (as potential folder names).
		if ( ! empty( $context['focus_keyword'] ) ) {
			$folder_keywords[] = $context['focus_keyword'];
		}

		if ( ! empty( $context['topic'] ) ) {
			$folder_keywords[] = $context['topic'];
		}

		if ( ! empty( $context['category'] ) ) {
			$folder_keywords[] = $context['category'];
		}

		// Remove duplicates and empty values.
		$folder_keywords = array_filter( array_unique( $folder_keywords ) );

		return $folder_keywords;
	}

	/**
	 * Query images by tags using WP_Query.
	 *
	 * @param array $tags Array of tag slugs.
	 * @return array Array of image IDs.
	 */
	private function queryImagesByTags( array $tags ): array {
		if ( empty( $tags ) ) {
			return array();
		}

		// First attempt: SEO library images with tags (preferred).
		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'   => '_seo_library_image',
					'value' => '1',
				),
			),
			'tax_query'      => array(
				array(
					'taxonomy' => 'image_tag',
					'field'    => 'slug',
					'terms'    => $tags,
					'operator' => 'AND', // All tags must match.
				),
			),
		);

		$query = new \WP_Query( $args );

		// If no SEO library images found, fallback to ANY images with matching tags.
		if ( empty( $query->posts ) ) {
			$fallback_args = array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'post_mime_type' => 'image', // Only images.
				'tax_query'      => array(
					array(
						'taxonomy' => 'image_tag',
						'field'    => 'slug',
						'terms'    => $tags,
						'operator' => 'AND', // All tags must match.
					),
				),
			);

			$fallback_query = new \WP_Query( $fallback_args );

			if ( ! empty( $fallback_query->posts ) ) {
				$this->log( implode( ', ', $tags ), 0, count( $fallback_query->posts ), null, 'Fallback: using non-library images' );
				return $fallback_query->posts;
			}
		}

		return $query->posts;
	}

	/**
	 * Select random image from matches.
	 *
	 * @param array $image_ids Array of image IDs.
	 * @return int Selected image ID.
	 */
	private function selectRandomImage( array $image_ids ): int {
		if ( count( $image_ids ) === 1 ) {
			return $image_ids[0];
		}

		// Select random image.
		$random_key = array_rand( $image_ids );
		return $image_ids[ $random_key ];
	}

	/**
	 * Get default image ID from settings.
	 *
	 * @return int|null Default image ID or null.
	 */
	private function getDefaultImage(): ?int {
		$settings = get_option( 'seo_generator_image_settings', array() );

		if ( isset( $settings['default_image_id'] ) && is_numeric( $settings['default_image_id'] ) ) {
			$image_id = (int) $settings['default_image_id'];

			// Verify the attachment still exists.
			if ( 'inherit' === get_post_status( $image_id ) ) {
				return $image_id;
			}
		}

		return null;
	}

	/**
	 * Assign image to post with auto-generated alt text.
	 *
	 * @param int   $image_id Image attachment ID.
	 * @param int   $post_id  Post ID.
	 * @param array $context  Context array with page info.
	 * @return void
	 */
	public function assignImageWithMetadata( int $image_id, int $post_id, array $context ): void {
		// Check if AI alt text is enabled.
		$settings = get_option( 'seo_generator_image_settings', array() );
		$use_ai_alt_text = $settings['use_ai_alt_text'] ?? false;
		$ai_fallback = $settings['ai_alt_text_fallback'] ?? true;

		$alt_text = '';

		// Try AI alt text generation if enabled.
		if ( $use_ai_alt_text && $this->openai_service ) {
			try {
				$alt_text = $this->generateAltTextWithAI( $image_id, $context );
				$this->log( 'ai_alt_text', 0, 1, $image_id, "AI Alt text: {$alt_text}" );
			} catch ( \Exception $e ) {
				error_log( sprintf( '[SEO Generator] AI alt text generation failed: %s', $e->getMessage() ) );

				// Fall back to tag-based if enabled.
				if ( $ai_fallback ) {
					$alt_text = $this->generateAltText( $image_id, $context );
					$this->log( 'alt_text_fallback', 0, 1, $image_id, "Fallback alt text: {$alt_text}" );
				}
			}
		} else {
			// Use tag-based alt text.
			$alt_text = $this->generateAltText( $image_id, $context );
		}

		update_post_meta( $image_id, '_wp_attachment_image_alt', $alt_text );

		// Clear unnecessary metadata.
		wp_update_post(
			array(
				'ID'           => $image_id,
				'post_excerpt' => '', // Caption.
				'post_content' => '', // Description.
			)
		);

		// Clear title (keep filename-based title).
		// Note: We don't clear post_title as it's used for organization.

		$this->log( 'metadata_update', 0, 1, $image_id, "Alt text: {$alt_text}" );
	}

	/**
	 * Generate alt text from image tags and context.
	 *
	 * @param int   $image_id Image attachment ID.
	 * @param array $context  Context array.
	 * @return string Generated alt text.
	 */
	private function generateAltText( int $image_id, array $context ): string {
		// Get image tags.
		$tags = wp_get_object_terms(
			$image_id,
			'image_tag',
			array(
				'fields' => 'names',
			)
		);

		if ( is_wp_error( $tags ) || empty( $tags ) ) {
			// Fallback: use page title if no tags.
			return ! empty( $context['page_title'] ) ? sanitize_text_field( $context['page_title'] ) : '';
		}

		// Build alt text from tags.
		// Example: ["mens", "platinum", "wedding-band"] -> "Men's platinum wedding band"
		$alt_parts = array();

		// Check for possessive form (mens -> men's, womens -> women's).
		foreach ( $tags as $tag ) {
			if ( 'mens' === strtolower( $tag ) ) {
				$alt_parts[] = "Men's";
			} elseif ( 'womens' === strtolower( $tag ) ) {
				$alt_parts[] = "Women's";
			} else {
				$alt_parts[] = ucfirst( $tag );
			}
		}

		// Join with spaces and clean up.
		$alt_text = implode( ' ', $alt_parts );

		// Replace hyphens with spaces.
		$alt_text = str_replace( '-', ' ', $alt_text );

		// Limit length to 125 characters (SEO best practice).
		if ( strlen( $alt_text ) > 125 ) {
			$alt_text = substr( $alt_text, 0, 125 );
			$alt_text = substr( $alt_text, 0, strrpos( $alt_text, ' ' ) ); // Cut at last space.
		}

		return sanitize_text_field( $alt_text );
	}

	/**
	 * Generate alt text using AI.
	 *
	 * @param int   $image_id Image attachment ID.
	 * @param array $context  Context array with focus keyword, page title, etc.
	 * @return string Generated alt text.
	 * @throws \Exception If AI service is not available or fails.
	 */
	private function generateAltTextWithAI( int $image_id, array $context ): string {
		if ( ! $this->openai_service ) {
			throw new \Exception( 'OpenAI service not available for AI alt text generation.' );
		}

		// Check cache first to avoid duplicate API calls.
		$cached_alt_text = $this->getCachedAltText( $image_id, $context );
		if ( ! empty( $cached_alt_text ) ) {
			$this->log( 'ai_alt_text_cache', 0, 1, $image_id, "Using cached alt text: {$cached_alt_text}" );
			return $cached_alt_text;
		}

		// Gather metadata for AI prompt.
		$filename = get_the_title( $image_id );
		$folder_name = get_post_meta( $image_id, '_seo_image_folder', true );

		$tags = wp_get_object_terms(
			$image_id,
			'image_tag',
			array(
				'fields' => 'names',
			)
		);

		if ( is_wp_error( $tags ) ) {
			$tags = array();
		}

		// Build metadata array for OpenAI.
		$metadata = array(
			'filename'      => $filename,
			'folder_name'   => $folder_name,
			'tags'          => $tags,
			'focus_keyword' => $context['focus_keyword'] ?? '',
			'page_title'    => $context['page_title'] ?? '',
		);

		// Get AI model from settings.
		$settings = get_option( 'seo_generator_image_settings', array() );
		$model = $settings['ai_alt_text_model'] ?? 'gpt-4o-mini';
		$metadata['model'] = $model;

		// Generate alt text using AI.
		$alt_text = $this->openai_service->generateAltText( $metadata );

		// Cache the generated alt text to avoid duplicate API calls.
		$this->cacheAltText( $image_id, $context, $alt_text );

		return $alt_text;
	}

	/**
	 * Clear cached alt text for an image.
	 *
	 * Removes cached AI-generated alt text, forcing regeneration on next request.
	 * Useful for manual regeneration or when image metadata changes.
	 *
	 * @param int $image_id Image attachment ID.
	 * @return void
	 */
	public function clearAltTextCache( int $image_id ): void {
		delete_post_meta( $image_id, '_ai_generated_alt_text' );
		delete_post_meta( $image_id, '_ai_alt_text_generated_at' );
		delete_post_meta( $image_id, '_ai_alt_text_context_hash' );

		$this->log( 'ai_alt_text_cache_cleared', 0, 1, $image_id, 'Cache cleared for manual regeneration' );
	}

	/**
	 * Get cached alt text for an image.
	 *
	 * Checks if AI-generated alt text already exists in cache to avoid duplicate API calls.
	 * Uses context hash to support different alt text for different contexts.
	 *
	 * @param int   $image_id Image attachment ID.
	 * @param array $context  Context array with focus keyword, page title, etc.
	 * @return string|null Cached alt text or null if not cached.
	 */
	private function getCachedAltText( int $image_id, array $context ): ?string {
		// Get cached alt text from meta.
		$cached_alt_text = get_post_meta( $image_id, '_ai_generated_alt_text', true );

		if ( empty( $cached_alt_text ) ) {
			return null;
		}

		// Check if cache is still valid (optional: you can add expiration logic here).
		$cached_timestamp = get_post_meta( $image_id, '_ai_alt_text_generated_at', true );

		// For now, cache never expires. You can add expiration logic later if needed.
		// Example: if ( time() - $cached_timestamp > 30 * DAY_IN_SECONDS ) { return null; }

		return $cached_alt_text;
	}

	/**
	 * Cache generated alt text for an image.
	 *
	 * Stores AI-generated alt text in metadata to avoid duplicate API calls.
	 *
	 * @param int    $image_id Image attachment ID.
	 * @param array  $context  Context array with focus keyword, page title, etc.
	 * @param string $alt_text Generated alt text to cache.
	 * @return void
	 */
	private function cacheAltText( int $image_id, array $context, string $alt_text ): void {
		// Store alt text in meta.
		update_post_meta( $image_id, '_ai_generated_alt_text', $alt_text );

		// Store generation timestamp.
		update_post_meta( $image_id, '_ai_alt_text_generated_at', time() );

		// Store context hash for reference (optional, for debugging).
		$context_hash = $this->buildContextHash( $context );
		update_post_meta( $image_id, '_ai_alt_text_context_hash', $context_hash );

		$this->log( 'ai_alt_text_cached', 0, 1, $image_id, "Cached alt text: {$alt_text}" );
	}

	/**
	 * Build a hash from context to identify unique contexts.
	 *
	 * @param array $context Context array.
	 * @return string Context hash.
	 */
	private function buildContextHash( array $context ): string {
		// Build hash from relevant context fields.
		$hash_data = array(
			'focus_keyword' => $context['focus_keyword'] ?? '',
			'page_title'    => $context['page_title'] ?? '',
		);

		return md5( wp_json_encode( $hash_data ) );
	}

	/**
	 * Log image matching activity to debug.log.
	 *
	 * @param string   $tags     Tag slugs being searched.
	 * @param int      $attempt  Attempt number.
	 * @param int      $count    Number of matches found.
	 * @param int|null $selected Selected image ID.
	 * @param string   $note     Optional note.
	 * @return void
	 */
	private function log( string $tags, int $attempt, int $count, ?int $selected, string $note = '' ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$message = sprintf(
			'[SEO Generator - Image Matching] Tags: %s | Attempt: %d | Found: %d | Selected: %s',
			$tags ?: 'none',
			$attempt,
			$count,
			$selected ? (string) $selected : 'none'
		);

		if ( ! empty( $note ) ) {
			$message .= ' | ' . $note;
		}

		error_log( $message );
	}
}
