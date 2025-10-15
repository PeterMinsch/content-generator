<?php
/**
 * Image Download Service
 *
 * Handles downloading images from URLs during CSV import.
 * Downloads images to WordPress Media Library and assigns to posts.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Image Download Service
 *
 * Downloads images from URLs and attaches them to WordPress posts.
 *
 * Features:
 * - URL validation (format, scheme, reachability)
 * - Duplicate detection (reuse existing images)
 * - File size validation (max 5MB by default)
 * - Image type validation (JPEG, PNG, GIF, WebP)
 * - Timeout configuration (30 seconds by default)
 * - Comprehensive error handling
 * - Metadata tracking (_source_url, _seo_imported_image)
 * - ACF hero_image field assignment
 * - Featured image assignment
 *
 * Usage:
 * ```php
 * $service = new ImageDownloadService();
 * $attachment_id = $service->downloadAndAttach(
 *     'https://example.com/image.jpg',
 *     123, // post ID
 *     'Product hero image'
 * );
 *
 * if (is_wp_error($attachment_id)) {
 *     error_log('Image download failed: ' . $attachment_id->get_error_message());
 * } else {
 *     // Success - attachment_id is the Media Library item ID
 * }
 * ```
 *
 * Configuration:
 * ```php
 * // Override timeout (default 30 seconds)
 * add_filter('seo_generator_image_download_timeout', function($timeout) {
 *     return 45;
 * });
 *
 * // Override max file size (default 5MB)
 * add_filter('seo_generator_max_image_size', function($size) {
 *     return 10 * 1024 * 1024; // 10MB
 * });
 * ```
 */
class ImageDownloadService {
	/**
	 * Download timeout in seconds.
	 *
	 * @var int
	 */
	private $timeout = 30;

	/**
	 * Maximum file size in bytes (5MB default).
	 *
	 * @var int
	 */
	private $max_file_size = 5242880;

	/**
	 * Allowed image MIME types.
	 *
	 * @var array
	 */
	private $allowed_mime_types = array(
		'image/jpeg',
		'image/jpg',
		'image/png',
		'image/gif',
		'image/webp',
	);

	/**
	 * Constructor.
	 *
	 * @param array $options Optional configuration options.
	 *                       - timeout: Download timeout in seconds (default from settings or 30)
	 *                       - max_file_size: Maximum file size in bytes (default from settings or 5MB)
	 */
	public function __construct( array $options = array() ) {
		// Get settings from wp_options.
		$settings = get_option( 'seo_generator_image_settings', array() );

		// Timeout in seconds.
		$default_timeout = $settings['image_download_timeout'] ?? 30;
		$this->timeout   = $options['timeout'] ?? apply_filters( 'seo_generator_image_download_timeout', $default_timeout );

		// Max file size in bytes.
		$default_max_size_mb = $settings['max_image_size'] ?? 5;
		$default_max_size    = $default_max_size_mb * 1024 * 1024; // Convert MB to bytes.
		$this->max_file_size = $options['max_file_size'] ?? apply_filters( 'seo_generator_max_image_size', $default_max_size );
	}

	/**
	 * Download image from URL and attach to post.
	 *
	 * This is the main entry point for downloading images.
	 * Handles validation, duplicate detection, download, and attachment.
	 *
	 * @param string $image_url   Image URL to download.
	 * @param int    $post_id     Post ID to attach image to.
	 * @param string $description Optional image description (used as alt text).
	 * @return int|\WP_Error Attachment ID on success, WP_Error on failure.
	 */
	public function downloadAndAttach( string $image_url, int $post_id, string $description = '' ) {
		// Validate URL format.
		if ( ! $this->isValidUrl( $image_url ) ) {
			return new \WP_Error( 'invalid_url', __( 'Invalid image URL format.', 'seo-generator' ) );
		}

		// Check for duplicates (reuse existing images).
		$existing = $this->findExistingImage( $image_url );
		if ( $existing ) {
			error_log( "Reusing existing image {$existing} for URL: {$image_url}" );
			$this->attachImageToPost( $existing, $post_id );
			return $existing;
		}

		// Validate image before downloading (check headers).
		$validation = $this->validateImageUrl( $image_url );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Download image.
		return $this->downloadImage( $image_url, $post_id, $description );
	}

	/**
	 * Validate URL format and scheme.
	 *
	 * @param string $url URL to validate.
	 * @return bool True if valid, false otherwise.
	 */
	private function isValidUrl( string $url ): bool {
		$url = esc_url_raw( $url );
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		$scheme = parse_url( $url, PHP_URL_SCHEME );
		return in_array( $scheme, array( 'http', 'https' ), true );
	}

	/**
	 * Find existing image by source URL.
	 *
	 * Queries Media Library for attachment with matching _source_url meta.
	 * If found, returns attachment ID for reuse.
	 *
	 * @param string $url Image source URL.
	 * @return int|null Attachment ID if found, null otherwise.
	 */
	private function findExistingImage( string $url ): ?int {
		global $wpdb;

		// Query postmeta for matching source URL.
		$attachment_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta}
				WHERE meta_key = '_source_url'
				AND meta_value = %s
				LIMIT 1",
				$url
			)
		);

		if ( $attachment_id ) {
			// Verify attachment still exists.
			$attachment = get_post( $attachment_id );
			if ( $attachment && $attachment->post_type === 'attachment' ) {
				return (int) $attachment_id;
			}
		}

		return null;
	}

	/**
	 * Validate image URL before downloading.
	 *
	 * Checks:
	 * - URL is reachable (HTTP 200)
	 * - Content-Type is valid image type
	 * - Content-Length is under max file size
	 *
	 * @param string $url Image URL to validate.
	 * @return true|\WP_Error True if valid, WP_Error if invalid.
	 */
	private function validateImageUrl( string $url ) {
		// Get headers without downloading full file.
		$response = wp_remote_head( $url, array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'connection_failed',
				__( 'Could not connect to image URL.', 'seo-generator' )
			);
		}

		// Check response code.
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			if ( 404 === $response_code ) {
				return new \WP_Error( 'http_404', __( 'Image not found (404).', 'seo-generator' ) );
			}
			return new \WP_Error(
				'http_error',
				sprintf( __( 'HTTP error: %d', 'seo-generator' ), $response_code )
			);
		}

		$headers = wp_remote_retrieve_headers( $response );

		// Validate content type.
		$content_type = $headers['content-type'] ?? '';
		if ( ! in_array( $content_type, $this->allowed_mime_types, true ) ) {
			return new \WP_Error(
				'invalid_type',
				sprintf( __( 'Invalid image type: %s', 'seo-generator' ), $content_type )
			);
		}

		// Validate file size.
		$content_length = intval( $headers['content-length'] ?? 0 );
		if ( $content_length > $this->max_file_size ) {
			return new \WP_Error(
				'file_too_large',
				sprintf(
					__( 'File too large: %s (max %s)', 'seo-generator' ),
					size_format( $content_length ),
					size_format( $this->max_file_size )
				)
			);
		}

		return true;
	}

	/**
	 * Download image from URL using media_sideload_image().
	 *
	 * @param string $url         Image URL.
	 * @param int    $post_id     Post ID to attach to.
	 * @param string $description Image description.
	 * @return int|\WP_Error Attachment ID on success, WP_Error on failure.
	 */
	private function downloadImage( string $url, int $post_id, string $description ) {
		// Require WordPress media functions.
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Set timeout.
		add_filter( 'http_request_timeout', array( $this, 'setDownloadTimeout' ) );

		// Download and sideload image.
		$attachment_id = media_sideload_image( $url, $post_id, $description, 'id' );

		// Remove timeout filter.
		remove_filter( 'http_request_timeout', array( $this, 'setDownloadTimeout' ) );

		if ( is_wp_error( $attachment_id ) ) {
			error_log( "Failed to download image: {$url} - " . $attachment_id->get_error_message() );
			return $attachment_id;
		}

		// Save metadata.
		$this->saveImageMetadata( $attachment_id, $url, $post_id );

		// Attach to post.
		$this->attachImageToPost( $attachment_id, $post_id );

		return $attachment_id;
	}

	/**
	 * Save image metadata after download.
	 *
	 * Saves:
	 * - _source_url: Original image URL
	 * - _seo_imported_image: Flag marking image as imported (value: 1)
	 * - _import_date: Import date (MySQL datetime)
	 * - _imported_for_post: Post ID image was imported for
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $source_url    Original image URL.
	 * @param int    $post_id       Post ID.
	 * @return void
	 */
	private function saveImageMetadata( int $attachment_id, string $source_url, int $post_id ): void {
		update_post_meta( $attachment_id, '_source_url', $source_url );
		update_post_meta( $attachment_id, '_seo_imported_image', 1 );
		update_post_meta( $attachment_id, '_import_date', current_time( 'mysql' ) );
		update_post_meta( $attachment_id, '_imported_for_post', $post_id );
	}

	/**
	 * Attach image to post.
	 *
	 * Sets:
	 * - Featured image (post thumbnail)
	 * - ACF hero_image field
	 *
	 * @param int $attachment_id Attachment ID.
	 * @param int $post_id       Post ID.
	 * @return void
	 */
	private function attachImageToPost( int $attachment_id, int $post_id ): void {
		// Set as featured image.
		set_post_thumbnail( $post_id, $attachment_id );

		// Update ACF field.
		if ( function_exists( 'update_field' ) ) {
			update_field( 'hero_image', $attachment_id, $post_id );
		}
	}

	/**
	 * Get download timeout.
	 *
	 * Used as filter callback for http_request_timeout.
	 *
	 * @return int Timeout in seconds.
	 */
	public function setDownloadTimeout(): int {
		return $this->timeout;
	}

	/**
	 * Format error message for user display.
	 *
	 * Maps WP_Error codes to user-friendly messages.
	 *
	 * @param \WP_Error $error Error object.
	 * @return string User-friendly error message.
	 */
	public function formatImageError( \WP_Error $error ): string {
		$code    = $error->get_error_code();
		$message = $error->get_error_message();

		$error_map = array(
			'http_404'                   => __( 'Image not found (404)', 'seo-generator' ),
			'http_request_failed'        => __( 'Network error downloading image', 'seo-generator' ),
			'rest_upload_unknown_error'  => __( 'Failed to upload image to media library', 'seo-generator' ),
			'invalid_url'                => __( 'Invalid image URL format', 'seo-generator' ),
			'invalid_type'               => __( 'Invalid image file type', 'seo-generator' ),
			'file_too_large'             => __( 'Image file is too large', 'seo-generator' ),
			'connection_failed'          => __( 'Could not connect to image URL', 'seo-generator' ),
			'http_error'                 => __( 'HTTP error accessing image', 'seo-generator' ),
		);

		return $error_map[ $code ] ?? $message;
	}

	/**
	 * Download images for multiple posts (bulk operation).
	 *
	 * Processes downloads sequentially to avoid memory issues.
	 *
	 * @param array $posts_with_urls Array of post_id => image_url pairs.
	 * @return array Results with 'downloaded', 'reused', and 'failed' arrays.
	 */
	public function downloadImagesForPosts( array $posts_with_urls ): array {
		$results = array(
			'downloaded' => array(),
			'reused'     => array(),
			'failed'     => array(),
		);

		foreach ( $posts_with_urls as $post_id => $image_url ) {
			// Check for existing image first.
			$existing = $this->findExistingImage( $image_url );

			if ( $existing ) {
				$this->attachImageToPost( $existing, $post_id );
				$results['reused'][] = array(
					'post_id'       => $post_id,
					'attachment_id' => $existing,
				);
			} else {
				$attachment_id = $this->downloadAndAttach( $image_url, $post_id, get_the_title( $post_id ) );

				if ( is_wp_error( $attachment_id ) ) {
					$results['failed'][] = array(
						'post_id' => $post_id,
						'url'     => $image_url,
						'error'   => $this->formatImageError( $attachment_id ),
					);
				} else {
					$results['downloaded'][] = array(
						'post_id'       => $post_id,
						'attachment_id' => $attachment_id,
					);
				}
			}

			// Memory cleanup after each download.
			wp_cache_flush();
		}

		return $results;
	}
}
