<?php
/**
 * Image Upload REST API Controller
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Controllers;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Handles REST API endpoints for image uploads.
 */
class ImageUploadController extends WP_REST_Controller {
	/**
	 * API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'seo-generator/v1';

	/**
	 * Allowed image MIME types.
	 *
	 * @var array
	 */
	private const ALLOWED_MIME_TYPES = array(
		'image/jpeg',
		'image/jpg',
		'image/png',
		'image/webp',
	);

	/**
	 * Allowed file extensions.
	 *
	 * @var array
	 */
	private const ALLOWED_EXTENSIONS = array(
		'jpg',
		'jpeg',
		'png',
		'webp',
	);

	/**
	 * Chunk size for large file uploads (5MB).
	 *
	 * @var int
	 */
	private const CHUNK_THRESHOLD = 5242880;

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Upload image endpoint.
		register_rest_route(
			$this->namespace,
			'/images',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'upload_image' ),
					'permission_callback' => array( $this, 'check_upload_permission' ),
					'args'                => array(
						'folder_name' => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
							'description'       => 'Optional folder name for organizing uploaded images',
						),
					),
				),
			)
		);

		// Update image tags endpoint.
		register_rest_route(
			$this->namespace,
			'/images/(?P<id>\d+)/tags',
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_image_tags' ),
					'permission_callback' => array( $this, 'check_upload_permission' ),
					'args'                => array(
						'id'     => array(
							'required'          => true,
							'validate_callback' => function( $param ) {
								return is_numeric( $param );
							},
						),
						'add'    => array(
							'type'    => 'array',
							'default' => array(),
						),
						'remove' => array(
							'type'    => 'array',
							'default' => array(),
						),
					),
				),
			)
		);

		// Bulk tag operations endpoint.
		register_rest_route(
			$this->namespace,
			'/images/bulk-tags',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'bulk_update_tags' ),
					'permission_callback' => array( $this, 'check_upload_permission' ),
					'args'                => array(
						'image_ids' => array(
							'required' => true,
							'type'     => 'array',
						),
						'add'       => array(
							'type'    => 'array',
							'default' => array(),
						),
						'remove'    => array(
							'type'    => 'array',
							'default' => array(),
						),
					),
				),
			)
		);

		// Bulk delete endpoint.
		register_rest_route(
			$this->namespace,
			'/images/bulk-delete',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'bulk_delete_images' ),
					'permission_callback' => array( $this, 'check_delete_permission' ),
					'args'                => array(
						'image_ids' => array(
							'required'          => true,
							'type'              => 'array',
							'validate_callback' => function( $param ) {
								return is_array( $param ) && ! empty( $param );
							},
						),
					),
				),
			)
		);
	}

	/**
	 * Upload image to media library.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function upload_image( WP_REST_Request $request ) {
		// Check if file was uploaded.
		if ( empty( $_FILES['file'] ) ) {
			return new WP_Error(
				'no_file',
				__( 'No file was uploaded.', 'seo-generator' ),
				array( 'status' => 400 )
			);
		}

		$file = $_FILES['file'];

		// Check for upload errors.
		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			return new WP_Error(
				'upload_error',
				$this->get_upload_error_message( $file['error'] ),
				array( 'status' => 400 )
			);
		}

		// Validate file type.
		$filetype = wp_check_filetype( $file['name'] );

		if ( ! in_array( $filetype['type'], self::ALLOWED_MIME_TYPES, true ) ) {
			return new WP_Error(
				'invalid_file_type',
				sprintf(
					/* translators: %s: allowed file types */
					__( 'Invalid file type. Allowed types: %s', 'seo-generator' ),
					implode( ', ', self::ALLOWED_EXTENSIONS )
				),
				array( 'status' => 400 )
			);
		}

		// Check file size.
		$max_size = wp_max_upload_size();
		if ( $file['size'] > $max_size ) {
			return new WP_Error(
				'file_too_large',
				sprintf(
					/* translators: %s: maximum file size */
					__( 'File is too large. Maximum size: %s', 'seo-generator' ),
					size_format( $max_size )
				),
				array( 'status' => 400 )
			);
		}

		// Include required WordPress file handling functions.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Handle the upload.
		$upload = \wp_handle_upload(
			$file,
			array(
				'test_form' => false,
				'mimes'     => array(
					'jpg|jpeg|jpe' => 'image/jpeg',
					'png'          => 'image/png',
					'webp'         => 'image/webp',
				),
			)
		);

		if ( isset( $upload['error'] ) ) {
			return new WP_Error(
				'upload_failed',
				$upload['error'],
				array( 'status' => 500 )
			);
		}

		// Prepare attachment data.
		$attachment = array(
			'post_mime_type' => $upload['type'],
			'post_title'     => \sanitize_file_name( pathinfo( $file['name'], PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		// Insert attachment.
		$attachment_id = \wp_insert_attachment( $attachment, $upload['file'] );

		if ( \is_wp_error( $attachment_id ) ) {
			return new WP_Error(
				'attachment_creation_failed',
				__( 'Failed to create attachment.', 'seo-generator' ),
				array( 'status' => 500 )
			);
		}

		// Mark as library image.
		\update_post_meta( $attachment_id, '_seo_library_image', '1' );

		// Auto-tag images using hybrid approach (folder + filename).
		$folder_name = $request->get_param( 'folder_name' );
		$filename    = pathinfo( $file['name'], PATHINFO_FILENAME );

		error_log( 'ImageUploadController: Auto-tagging image ' . $attachment_id );
		error_log( 'ImageUploadController: Folder: ' . ( $folder_name ?: 'NONE' ) );
		error_log( 'ImageUploadController: Filename: ' . $filename );

		$this->auto_tag_image( $attachment_id, $folder_name, $filename );

		// Generate attachment metadata.
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = \wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
		\wp_update_attachment_metadata( $attachment_id, $attach_data );

		// Get attachment data for response.
		$attachment_data = $this->prepare_attachment_response( $attachment_id );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Image uploaded successfully.', 'seo-generator' ),
				'data'    => $attachment_data,
			),
			201
		);
	}

	/**
	 * Automatically tag image using hybrid approach (folder + filename).
	 *
	 * Extracts tags from:
	 * 1. Folder path (all levels) - e.g., "Gold/Wedding Bands/Mens" → ["gold", "wedding", "bands", "mens"]
	 * 2. Filename - e.g., "classic-gold-ring.jpg" → ["classic", "gold", "ring"]
	 * 3. Combines and deduplicates tags
	 *
	 * @param int         $attachment_id Attachment ID.
	 * @param string|null $folder_name   Optional folder name/path.
	 * @param string|null $filename      Optional filename (without extension).
	 * @return array Array of assigned tag slugs.
	 */
	private function auto_tag_image( int $attachment_id, ?string $folder_name = null, ?string $filename = null ): array {
		$all_tags = array();

		// Extract tags from folder path.
		if ( ! empty( $folder_name ) ) {
			$folder_tags = $this->extract_tags_from_folder( $folder_name );
			$all_tags    = array_merge( $all_tags, $folder_tags );

			// Store original folder path in meta.
			\update_post_meta( $attachment_id, '_seo_image_folder', \sanitize_text_field( $folder_name ) );

			error_log( sprintf(
				'[SEO Generator - Auto-Tag] Folder tags for image %d: %s',
				$attachment_id,
				implode( ', ', $folder_tags )
			) );
		}

		// Extract tags from filename.
		if ( ! empty( $filename ) ) {
			$filename_tags = $this->extract_tags_from_filename( $filename );
			$all_tags      = array_merge( $all_tags, $filename_tags );

			error_log( sprintf(
				'[SEO Generator - Auto-Tag] Filename tags for image %d: %s',
				$attachment_id,
				implode( ', ', $filename_tags )
			) );
		}

		// Remove duplicates and empty values.
		$all_tags = array_filter( array_unique( $all_tags ) );

		if ( empty( $all_tags ) ) {
			error_log( sprintf(
				'[SEO Generator - Auto-Tag] No tags extracted for image %d',
				$attachment_id
			) );
			return array();
		}

		// Create terms and assign to image.
		$assigned_tags = $this->create_and_assign_tags( $attachment_id, $all_tags );

		error_log( sprintf(
			'[SEO Generator - Auto-Tag] Successfully tagged image %d with %d tags: %s',
			$attachment_id,
			count( $assigned_tags ),
			implode( ', ', $assigned_tags )
		) );

		// Apply filter to allow AI vision or other methods to add more tags.
		$final_tags = apply_filters( 'seo_generator_image_tags', $assigned_tags, $attachment_id, $folder_name, $filename );

		return $final_tags;
	}

	/**
	 * Extract tags from folder path.
	 *
	 * Splits folder path by / or \ and extracts meaningful tags from each level.
	 * Example: "Gold/Wedding Bands/Mens/Classic" → ["gold", "wedding", "bands", "mens", "classic"]
	 *
	 * @param string $folder_path Folder path.
	 * @return array Array of tag slugs.
	 */
	private function extract_tags_from_folder( string $folder_path ): array {
		$tags = array();

		// Split by both forward and back slashes.
		$parts = preg_split( '/[\/\\\\]+/', $folder_path );

		foreach ( $parts as $part ) {
			// Extract tags from each folder level.
			$part_tags = $this->parse_string_to_tags( $part );
			$tags      = array_merge( $tags, $part_tags );
		}

		return array_unique( $tags );
	}

	/**
	 * Extract tags from filename.
	 *
	 * Parses filename and extracts meaningful tags.
	 * Example: "mens-gold-wedding-band-classic.jpg" → ["mens", "gold", "wedding", "band", "classic"]
	 *
	 * @param string $filename Filename (without extension).
	 * @return array Array of tag slugs.
	 */
	private function extract_tags_from_filename( string $filename ): array {
		return $this->parse_string_to_tags( $filename );
	}

	/**
	 * Parse a string into tags by splitting on delimiters.
	 *
	 * Handles:
	 * - Spaces: "Gold Wedding Band" → ["gold", "wedding", "band"]
	 * - Hyphens: "mens-gold-ring" → ["mens", "gold", "ring"]
	 * - Underscores: "wedding_band_classic" → ["wedding", "band", "classic"]
	 * - CamelCase: "GoldWeddingBand" → ["gold", "wedding", "band"]
	 *
	 * @param string $string String to parse.
	 * @return array Array of tag slugs.
	 */
	private function parse_string_to_tags( string $string ): array {
		// Handle CamelCase: insert space before capital letters.
		$string = preg_replace( '/([a-z])([A-Z])/', '$1 $2', $string );

		// Split by spaces, hyphens, underscores.
		$words = preg_split( '/[\s\-_]+/', $string );

		$tags = array();
		foreach ( $words as $word ) {
			$tag = $this->sanitize_tag( $word );

			// Only keep meaningful tags (length > 2, not numeric).
			if ( strlen( $tag ) > 2 && ! is_numeric( $tag ) ) {
				$tags[] = $tag;
			}
		}

		return $tags;
	}

	/**
	 * Sanitize tag for use as taxonomy term.
	 *
	 * @param string $tag Raw tag string.
	 * @return string Sanitized tag slug.
	 */
	private function sanitize_tag( string $tag ): string {
		return \sanitize_title( $tag );
	}

	/**
	 * Create taxonomy terms and assign to image.
	 *
	 * @param int   $attachment_id Attachment ID.
	 * @param array $tags          Array of tag slugs.
	 * @return array Array of successfully assigned tag slugs.
	 */
	private function create_and_assign_tags( int $attachment_id, array $tags ): array {
		$assigned_tags = array();

		foreach ( $tags as $tag_slug ) {
			// Check if term exists, if not create it.
			$term = \term_exists( $tag_slug, 'image_tag' );
			if ( ! $term ) {
				// Create new term (use slug as name, capitalize first letter).
				$term = \wp_insert_term( ucfirst( $tag_slug ), 'image_tag', array( 'slug' => $tag_slug ) );
			}

			if ( \is_wp_error( $term ) ) {
				// Log error but continue with other tags.
				error_log( sprintf(
					'[SEO Generator - Auto-Tag] Failed to create tag "%s" for image %d: %s',
					$tag_slug,
					$attachment_id,
					$term->get_error_message()
				) );
				continue;
			}

			$assigned_tags[] = $tag_slug;
		}

		// Assign all tags to the image at once.
		if ( ! empty( $assigned_tags ) ) {
			\wp_set_object_terms( $attachment_id, $assigned_tags, 'image_tag', false );
		}

		return $assigned_tags;
	}


	/**
	 * Update tags for a single image.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function update_image_tags( WP_REST_Request $request ) {
		$image_id = (int) $request['id'];

		// Check if attachment exists and is an image.
		$attachment = get_post( $image_id );
		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return new WP_Error(
				'invalid_image',
				__( 'Invalid image ID.', 'seo-generator' ),
				array( 'status' => 404 )
			);
		}

		// Get tags to add and remove.
		$tags_to_add    = $request->get_param( 'add' ) ?? array();
		$tags_to_remove = $request->get_param( 'remove' ) ?? array();

		// Sanitize tag inputs.
		$tags_to_add    = array_map( 'sanitize_text_field', $tags_to_add );
		$tags_to_remove = array_map( 'sanitize_text_field', $tags_to_remove );

		// Get current tags.
		$current_tags = wp_get_object_terms( $image_id, 'image_tag', array( 'fields' => 'slugs' ) );
		if ( is_wp_error( $current_tags ) ) {
			$current_tags = array();
		}

		// Remove tags.
		if ( ! empty( $tags_to_remove ) ) {
			wp_remove_object_terms( $image_id, $tags_to_remove, 'image_tag' );
		}

		// Add tags (create new tags if they don't exist).
		if ( ! empty( $tags_to_add ) ) {
			wp_set_object_terms( $image_id, $tags_to_add, 'image_tag', true );
		}

		// Get updated tag list.
		$updated_tags = wp_get_object_terms( $image_id, 'image_tag' );
		if ( is_wp_error( $updated_tags ) ) {
			$updated_tags = array();
		}

		// Format tags for response.
		$tag_data = array_map(
			function( $term ) {
				return array(
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				);
			},
			$updated_tags
		);

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Tags updated successfully.', 'seo-generator' ),
				'tags'    => $tag_data,
			),
			200
		);
	}

	/**
	 * Bulk update tags for multiple images.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function bulk_update_tags( WP_REST_Request $request ) {
		$image_ids = $request->get_param( 'image_ids' ) ?? array();

		if ( empty( $image_ids ) ) {
			return new WP_Error(
				'missing_images',
				__( 'No images provided.', 'seo-generator' ),
				array( 'status' => 400 )
			);
		}

		// Get tags to add and remove.
		$tags_to_add    = $request->get_param( 'add' ) ?? array();
		$tags_to_remove = $request->get_param( 'remove' ) ?? array();

		// Sanitize inputs.
		$image_ids      = array_map( 'intval', $image_ids );
		$tags_to_add    = array_map( 'sanitize_text_field', $tags_to_add );
		$tags_to_remove = array_map( 'sanitize_text_field', $tags_to_remove );

		$updated_count = 0;
		$errors        = array();

		foreach ( $image_ids as $image_id ) {
			// Verify attachment exists.
			$attachment = get_post( $image_id );
			if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
				$errors[] = sprintf(
					/* translators: %d: image ID */
					__( 'Invalid image ID: %d', 'seo-generator' ),
					$image_id
				);
				continue;
			}

			// Remove tags.
			if ( ! empty( $tags_to_remove ) ) {
				wp_remove_object_terms( $image_id, $tags_to_remove, 'image_tag' );
			}

			// Add tags.
			if ( ! empty( $tags_to_add ) ) {
				wp_set_object_terms( $image_id, $tags_to_add, 'image_tag', true );
			}

			$updated_count++;
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => sprintf(
					/* translators: %d: number of images */
					_n( '%d image updated.', '%d images updated.', $updated_count, 'seo-generator' ),
					$updated_count
				),
				'updated' => $updated_count,
				'errors'  => $errors,
			),
			200
		);
	}

	/**
	 * Prepare attachment data for API response.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array Attachment data.
	 */
	private function prepare_attachment_response( int $attachment_id ): array {
		$attachment = get_post( $attachment_id );
		$image_url  = wp_get_attachment_url( $attachment_id );
		$thumb_url  = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );

		return array(
			'id'        => $attachment_id,
			'title'     => $attachment->post_title,
			'filename'  => basename( get_attached_file( $attachment_id ) ),
			'url'       => $image_url,
			'thumbnail' => $thumb_url,
			'mime_type' => $attachment->post_mime_type,
			'size'      => filesize( get_attached_file( $attachment_id ) ),
		);
	}

	/**
	 * Get human-readable upload error message.
	 *
	 * @param int $error_code PHP upload error code.
	 * @return string Error message.
	 */
	private function get_upload_error_message( int $error_code ): string {
		$upload_errors = array(
			UPLOAD_ERR_INI_SIZE   => __( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'seo-generator' ),
			UPLOAD_ERR_FORM_SIZE  => __( 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.', 'seo-generator' ),
			UPLOAD_ERR_PARTIAL    => __( 'The uploaded file was only partially uploaded.', 'seo-generator' ),
			UPLOAD_ERR_NO_FILE    => __( 'No file was uploaded.', 'seo-generator' ),
			UPLOAD_ERR_NO_TMP_DIR => __( 'Missing a temporary folder.', 'seo-generator' ),
			UPLOAD_ERR_CANT_WRITE => __( 'Failed to write file to disk.', 'seo-generator' ),
			UPLOAD_ERR_EXTENSION  => __( 'A PHP extension stopped the file upload.', 'seo-generator' ),
		);

		return $upload_errors[ $error_code ] ?? __( 'Unknown upload error.', 'seo-generator' );
	}

	/**
	 * Bulk delete images.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function bulk_delete_images( WP_REST_Request $request ) {
		$image_ids = $request->get_param( 'image_ids' ) ?? array();

		if ( empty( $image_ids ) ) {
			return new WP_Error(
				'missing_images',
				__( 'No images provided for deletion.', 'seo-generator' ),
				array( 'status' => 400 )
			);
		}

		// Sanitize inputs.
		$image_ids = array_map( 'intval', $image_ids );

		$deleted_count = 0;
		$errors        = array();

		foreach ( $image_ids as $image_id ) {
			// Verify attachment exists.
			$attachment = get_post( $image_id );
			if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
				$errors[] = sprintf(
					/* translators: %d: image ID */
					__( 'Invalid image ID: %d', 'seo-generator' ),
					$image_id
				);
				continue;
			}

			// Check if user can delete this attachment.
			if ( ! current_user_can( 'delete_post', $image_id ) ) {
				$errors[] = sprintf(
					/* translators: %d: image ID */
					__( 'Permission denied to delete image ID: %d', 'seo-generator' ),
					$image_id
				);
				continue;
			}

			// Delete the attachment (and its file).
			$result = \wp_delete_attachment( $image_id, true );

			if ( false === $result || null === $result ) {
				$errors[] = sprintf(
					/* translators: %d: image ID */
					__( 'Failed to delete image ID: %d', 'seo-generator' ),
					$image_id
				);
			} else {
				$deleted_count++;
			}
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => sprintf(
					/* translators: %d: number of images */
					_n( '%d image deleted.', '%d images deleted.', $deleted_count, 'seo-generator' ),
					$deleted_count
				),
				'deleted' => $deleted_count,
				'errors'  => $errors,
			),
			200
		);
	}

	/**
	 * Check if current user has permission to upload images.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if user has permission, error otherwise.
	 */
	public function check_upload_permission( WP_REST_Request $request ) {
		// Check user capability.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'insufficient_permissions',
				__( 'You do not have permission to upload images.', 'seo-generator' ),
				array( 'status' => 403 )
			);
		}

		// Verify nonce.
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'invalid_nonce',
				__( 'Invalid security token.', 'seo-generator' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Check if current user has permission to delete images.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if user has permission, error otherwise.
	 */
	public function check_delete_permission( WP_REST_Request $request ) {
		// Check user capability.
		if ( ! current_user_can( 'delete_posts' ) ) {
			return new WP_Error(
				'insufficient_permissions',
				__( 'You do not have permission to delete images.', 'seo-generator' ),
				array( 'status' => 403 )
			);
		}

		// Verify nonce.
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'invalid_nonce',
				__( 'Invalid security token.', 'seo-generator' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}
}
