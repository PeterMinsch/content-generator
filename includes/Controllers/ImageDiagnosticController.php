<?php
/**
 * Image Diagnostic Controller
 *
 * Debug endpoint to diagnose image matching issues.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Controllers;

use SEOGenerator\Services\ImageMatchingService;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Provides diagnostic endpoints for debugging image matching.
 */
class ImageDiagnosticController extends WP_REST_Controller {
	/**
	 * API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'seo-generator/v1';

	/**
	 * Image matching service.
	 *
	 * @var ImageMatchingService
	 */
	private ImageMatchingService $image_matching;

	/**
	 * Constructor.
	 *
	 * @param ImageMatchingService $image_matching Image matching service.
	 */
	public function __construct( ImageMatchingService $image_matching ) {
		$this->image_matching = $image_matching;
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Diagnostic endpoint.
		register_rest_route(
			$this->namespace,
			'/images/diagnose',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'diagnose_matching' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'focus_keyword' => array(
							'required' => true,
							'type'     => 'string',
						),
						'page_title'    => array(
							'required' => false,
							'type'     => 'string',
						),
						'topic'         => array(
							'required' => false,
							'type'     => 'string',
						),
					),
				),
			)
		);
	}

	/**
	 * Diagnose image matching for given context.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response with diagnostic information.
	 */
	public function diagnose_matching( WP_REST_Request $request ) {
		$focus_keyword = $request->get_param( 'focus_keyword' );
		$page_title    = $request->get_param( 'page_title' );
		$topic         = $request->get_param( 'topic' );

		$context = array(
			'focus_keyword' => $focus_keyword,
			'page_title'    => $page_title,
			'topic'         => $topic,
		);

		// Step 1: Extract keywords/tags from context.
		$extracted_tags = $this->extractKeywordsPublic( $context );

		// Step 2: Check how many images exist in media library.
		$all_images_query = new \WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => 'image',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$total_images = count( $all_images_query->posts );

		// Step 3: Check how many images have _seo_library_image flag.
		$seo_library_query = new \WP_Query(
			array(
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
			)
		);

		$seo_library_images = count( $seo_library_query->posts );

		// Step 4: Check how many images have image_tag taxonomy terms.
		$tagged_images_query = new \WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => 'image',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'tax_query'      => array(
					array(
						'taxonomy' => 'image_tag',
						'operator' => 'EXISTS',
					),
				),
			)
		);

		$tagged_images = $tagged_images_query->posts;

		// Step 5: Get all existing image_tag terms.
		$all_tags = get_terms(
			array(
				'taxonomy'   => 'image_tag',
				'hide_empty' => false,
			)
		);

		$tag_list = array();
		if ( ! is_wp_error( $all_tags ) ) {
			foreach ( $all_tags as $term ) {
				$tag_list[] = array(
					'name'  => $term->name,
					'slug'  => $term->slug,
					'count' => $term->count,
				);
			}
		}

		// Step 6: Try matching with each level of tags.
		$matching_results = array();

		// All tags.
		if ( ! empty( $extracted_tags ) ) {
			$all_tags_match = $this->queryByTags( $extracted_tags );
			$matching_results['all_tags'] = array(
				'tags'   => $extracted_tags,
				'found'  => count( $all_tags_match ),
				'images' => $all_tags_match,
			);
		}

		// First 2 tags.
		if ( count( $extracted_tags ) >= 2 ) {
			$two_tags       = array_slice( $extracted_tags, 0, 2 );
			$two_tags_match = $this->queryByTags( $two_tags );
			$matching_results['first_2_tags'] = array(
				'tags'   => $two_tags,
				'found'  => count( $two_tags_match ),
				'images' => $two_tags_match,
			);
		}

		// First 1 tag.
		if ( count( $extracted_tags ) >= 1 ) {
			$one_tag       = array_slice( $extracted_tags, 0, 1 );
			$one_tag_match = $this->queryByTags( $one_tag );
			$matching_results['first_1_tag'] = array(
				'tags'   => $one_tag,
				'found'  => count( $one_tag_match ),
				'images' => $one_tag_match,
			);
		}

		// Step 7: Try actual matching.
		$matched_image_id = $this->image_matching->findMatchingImage( $context );

		// Step 8: Get sample images with their tags.
		$sample_images = array();
		$sample_ids    = array_slice( $tagged_images, 0, 5 ); // First 5 tagged images.

		foreach ( $sample_ids as $image_id ) {
			$image_tags = wp_get_object_terms( $image_id, 'image_tag' );
			$tag_names  = array();
			if ( ! is_wp_error( $image_tags ) ) {
				foreach ( $image_tags as $tag ) {
					$tag_names[] = array(
						'name' => $tag->name,
						'slug' => $tag->slug,
					);
				}
			}

			$sample_images[] = array(
				'id'       => $image_id,
				'title'    => get_the_title( $image_id ),
				'url'      => wp_get_attachment_url( $image_id ),
				'tags'     => $tag_names,
				'is_seo'   => get_post_meta( $image_id, '_seo_library_image', true ) === '1',
			);
		}

		// Build response.
		return new WP_REST_Response(
			array(
				'success'    => true,
				'context'    => array(
					'focus_keyword' => $focus_keyword,
					'page_title'    => $page_title,
					'topic'         => $topic,
				),
				'extraction' => array(
					'extracted_tags' => $extracted_tags,
					'tag_count'      => count( $extracted_tags ),
				),
				'library'    => array(
					'total_images'       => $total_images,
					'seo_library_images' => $seo_library_images,
					'tagged_images'      => count( $tagged_images ),
					'available_tags'     => $tag_list,
				),
				'matching'   => array(
					'results'          => $matching_results,
					'final_match'      => $matched_image_id,
					'final_match_url'  => $matched_image_id ? wp_get_attachment_url( $matched_image_id ) : null,
				),
				'samples'    => $sample_images,
			),
			200
		);
	}

	/**
	 * Extract keywords from context (public version of private method).
	 *
	 * @param array $context Context array.
	 * @return array Array of tag slugs.
	 */
	private function extractKeywordsPublic( array $context ): array {
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
	 * Query images by tags.
	 *
	 * @param array $tags Array of tag slugs.
	 * @return array Array of image IDs.
	 */
	private function queryByTags( array $tags ): array {
		if ( empty( $tags ) ) {
			return array();
		}

		// Try regular images with tags.
		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'post_mime_type' => 'image',
			'tax_query'      => array(
				array(
					'taxonomy' => 'image_tag',
					'field'    => 'slug',
					'terms'    => $tags,
					'operator' => 'AND',
				),
			),
		);

		$query = new \WP_Query( $args );

		return $query->posts;
	}

	/**
	 * Check permission.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool True if allowed.
	 */
	public function check_permission( WP_REST_Request $request ) {
		return current_user_can( 'edit_posts' );
	}
}
