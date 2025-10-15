<?php
/**
 * Image Library Page Manager
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Manages the Image Library admin page functionality.
 */
class ImageLibraryPage {
	/**
	 * Meta key for library images.
	 *
	 * @var string
	 */
	private const LIBRARY_META_KEY = '_seo_library_image';

	/**
	 * Images per page.
	 *
	 * @var int
	 */
	private const PER_PAGE = 20;

	/**
	 * Get library images with pagination.
	 *
	 * @param int $paged Current page number.
	 * @return \WP_Query Query object with library images.
	 */
	public function getLibraryImages( int $paged = 1 ): \WP_Query {
		$args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'posts_per_page' => self::PER_PAGE,
			'paged'          => $paged,
			'meta_query'     => array(
				array(
					'key'   => self::LIBRARY_META_KEY,
					'value' => '1',
				),
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		return new \WP_Query( $args );
	}

	/**
	 * Get tags for a specific image.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array Array of term objects.
	 */
	public function getImageTags( int $attachment_id ): array {
		$terms = wp_get_object_terms( $attachment_id, 'image_tag' );

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		return $terms;
	}

	/**
	 * Search images by filename.
	 *
	 * @param string $search Search term.
	 * @param int    $paged Current page number.
	 * @return \WP_Query Query object with search results.
	 */
	public function searchImages( string $search, int $paged = 1 ): \WP_Query {
		$args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'posts_per_page' => self::PER_PAGE,
			'paged'          => $paged,
			's'              => sanitize_text_field( $search ),
			'meta_query'     => array(
				array(
					'key'   => self::LIBRARY_META_KEY,
					'value' => '1',
				),
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		return new \WP_Query( $args );
	}

	/**
	 * Filter images by tag.
	 *
	 * @param string $tag_slug Tag slug to filter by.
	 * @param int    $paged Current page number.
	 * @return \WP_Query Query object with filtered images.
	 */
	public function filterByTag( string $tag_slug, int $paged = 1 ): \WP_Query {
		$args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'posts_per_page' => self::PER_PAGE,
			'paged'          => $paged,
			'meta_query'     => array(
				array(
					'key'   => self::LIBRARY_META_KEY,
					'value' => '1',
				),
			),
			'tax_query'      => array(
				array(
					'taxonomy' => 'image_tag',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $tag_slug ),
				),
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		return new \WP_Query( $args );
	}

	/**
	 * Get all available image tags.
	 *
	 * @return array Array of term objects.
	 */
	public function getAllTags(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'image_tag',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		return $terms;
	}

	/**
	 * Get all unique folder names from images in library.
	 *
	 * @return array Array of folder names (unique, sorted).
	 */
	public function getAllFolders(): array {
		global $wpdb;

		// Query distinct folder values from postmeta.
		$folders = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_value
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->postmeta} pm2 ON pm.post_id = pm2.post_id
				WHERE pm.meta_key = '_seo_image_folder'
				AND pm2.meta_key = %s
				AND pm2.meta_value = '1'
				AND pm.meta_value != ''
				ORDER BY pm.meta_value ASC",
				self::LIBRARY_META_KEY
			)
		);

		return $folders ?: array();
	}

	/**
	 * Filter images by folder.
	 *
	 * @param string $folder Folder name to filter by.
	 * @param int    $paged Current page number.
	 * @return \WP_Query Query object with filtered images.
	 */
	public function filterByFolder( string $folder, int $paged = 1 ): \WP_Query {
		$args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'posts_per_page' => self::PER_PAGE,
			'paged'          => $paged,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'   => self::LIBRARY_META_KEY,
					'value' => '1',
				),
				array(
					'key'   => '_seo_image_folder',
					'value' => sanitize_text_field( $folder ),
				),
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		return new \WP_Query( $args );
	}

	/**
	 * Get folder name for a specific image.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string|null Folder name or null if not set.
	 */
	public function getImageFolder( int $attachment_id ): ?string {
		$folder = get_post_meta( $attachment_id, '_seo_image_folder', true );
		return ! empty( $folder ) ? $folder : null;
	}

	/**
	 * Render the image library page.
	 *
	 * @return void
	 */
	public function render(): void {
		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'seo-generator' ) );
		}

		// Get current page number.
		$paged = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;

		// Handle search and filtering.
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$tag    = isset( $_GET['tag'] ) ? sanitize_text_field( wp_unslash( $_GET['tag'] ) ) : '';
		$folder = isset( $_GET['folder'] ) ? sanitize_text_field( wp_unslash( $_GET['folder'] ) ) : '';

		// Get images based on filters.
		if ( ! empty( $search ) ) {
			$query = $this->searchImages( $search, $paged );
		} elseif ( ! empty( $folder ) ) {
			$query = $this->filterByFolder( $folder, $paged );
		} elseif ( ! empty( $tag ) ) {
			$query = $this->filterByTag( $tag, $paged );
		} else {
			$query = $this->getLibraryImages( $paged );
		}

		// Get all tags for filter dropdown.
		$all_tags = $this->getAllTags();

		// Get all folders for filter dropdown.
		$all_folders = $this->getAllFolders();

		// Load template.
		$template_path = plugin_dir_path( dirname( __DIR__ ) ) . 'templates/admin/image-library.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			echo '<div class="wrap">';
			echo '<h1>' . esc_html__( 'Image Library Manager', 'seo-generator' ) . '</h1>';
			echo '<p>' . esc_html__( 'Template file not found.', 'seo-generator' ) . '</p>';
			echo '</div>';
		}
	}
}
