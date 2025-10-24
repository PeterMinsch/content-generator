<?php
/**
 * Related Links Image Service
 *
 * Handles background generation of AI images for related links blocks.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Class RelatedLinksImageService
 *
 * Generates images for related_links blocks in the background.
 */
class RelatedLinksImageService {

	/**
	 * Generate images for a post's related_links block.
	 *
	 * This runs as a background WordPress Cron job to avoid timeouts.
	 *
	 * @param int $post_id Post ID.
	 * @return array Result with success count and errors.
	 */
	public function generateImagesForPost( int $post_id ): array {
		error_log( "[RelatedLinksImageService] Starting image generation for post {$post_id}" );

		$start_time = microtime( true );
		$generated  = 0;
		$cached     = 0;
		$failed     = 0;
		$errors     = array();

		try {
			// Get related_links data from ACF fields
			// ACF stores it as separate fields: 'section_heading' and 'links'
			$section_heading = get_field( 'section_heading', $post_id );
			$links_data = get_field( 'links', $post_id );

			// Check if data exists
			if ( empty( $links_data ) ) {
				// Try post_meta as fallback (in case it's stored differently)
				$links_data = get_post_meta( $post_id, 'links', true );

				if ( empty( $links_data ) ) {
					throw new \Exception( 'No links data found (checked both ACF and post_meta)' );
				}
			}

			// Handle different data formats
			if ( is_string( $links_data ) ) {
				// If it's a serialized string, unserialize it
				if ( substr( $links_data, 0, 2 ) === 'a:' ) {
					$links_data = maybe_unserialize( $links_data );
				} else {
					// If it's JSON, decode it
					$links_data = json_decode( $links_data, true );
				}
			}

			if ( ! is_array( $links_data ) || empty( $links_data ) ) {
				throw new \Exception( 'Links data is not in expected format' );
			}

			// Build the expected structure
			$related_links = array(
				'section_heading' => $section_heading,
				'links' => $links_data,
			);

			$page_title      = get_the_title( $post_id );
			$image_generator = new ImageGeneratorService();

			// Generate images for each link.
			foreach ( $related_links['links'] as $index => $link ) {
				if ( empty( $link['link_title'] ) || empty( $link['link_category'] ) ) {
					error_log( "[RelatedLinksImageService] Skipping link #{$index} - missing title or category" );
					continue;
				}

				// Skip if image already exists.
				if ( ! empty( $link['link_image'] ) ) {
					error_log( "[RelatedLinksImageService] Link #{$index} already has image, skipping" );
					continue;
				}

				try {
					error_log( sprintf(
						'[RelatedLinksImageService] Generating image for link: "%s" (category: %s)',
						$link['link_title'],
						$link['link_category']
					) );

					$attachment_id = $image_generator->generateImageForLink(
						$page_title,
						$link['link_title'],
						$link['link_description'] ?? '',
						$link['link_category'],
						$post_id
					);

					if ( $attachment_id ) {
						// Update the link with the image ID.
						$related_links['links'][ $index ]['link_image'] = $attachment_id;
						$generated++;

						error_log( sprintf(
							'[RelatedLinksImageService] Generated image (ID: %d) for "%s"',
							$attachment_id,
							$link['link_title']
						) );
					} else {
						$failed++;
						$errors[] = "Failed to generate image for: {$link['link_title']}";
						error_log( "[RelatedLinksImageService] Image generation returned false for: {$link['link_title']}" );
					}

				} catch ( \Exception $e ) {
					$failed++;
					$errors[] = "{$link['link_title']}: {$e->getMessage()}";
					error_log( "[RelatedLinksImageService] Error generating image for {$link['link_title']}: " . $e->getMessage() );
				}
			}

			// Save updated links data back to ACF field.
			update_field( 'links', $related_links['links'], $post_id );

			error_log( '[RelatedLinksImageService] Saved updated links with images back to ACF field' );

			$duration = round( microtime( true ) - $start_time, 2 );

			error_log( sprintf(
				'[RelatedLinksImageService] Completed for post %d in %s seconds: %d generated, %d failed',
				$post_id,
				$duration,
				$generated,
				$failed
			) );

			return array(
				'success'   => true,
				'generated' => $generated,
				'cached'    => $cached,
				'failed'    => $failed,
				'errors'    => $errors,
				'duration'  => $duration,
			);

		} catch ( \Exception $e ) {
			error_log( '[RelatedLinksImageService] Fatal error: ' . $e->getMessage() );

			return array(
				'success' => false,
				'error'   => $e->getMessage(),
			);
		}
	}
}
