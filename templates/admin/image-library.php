<?php
/**
 * Image Library Manager Template
 *
 * Variables available:
 * - $query: WP_Query object with library images
 * - $all_tags: Array of image_tag term objects
 * - $search: Current search term (if any)
 * - $tag: Current tag filter (if any)
 * - $paged: Current page number
 * - $this: ImageLibraryPage instance
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

// Enqueue admin styles.
wp_enqueue_style(
	'seo-generator-image-library',
	plugin_dir_url( dirname( __DIR__ ) ) . 'assets/css/admin-image-library.css',
	array(),
	'1.0.0'
);

// Enqueue upload script.
wp_enqueue_script(
	'seo-generator-image-upload',
	plugin_dir_url( dirname( __DIR__ ) ) . 'assets/js/build/image-upload.js',
	array( 'wp-api-fetch' ),
	'1.0.0',
	true
);

// Localize script with nonce and endpoint.
wp_localize_script(
	'seo-generator-image-upload',
	'seoImageUpload',
	array(
		'nonce'    => wp_create_nonce( 'wp_rest' ),
		'endpoint' => rest_url( 'seo-generator/v1/images' ),
		'maxSize'  => wp_max_upload_size(),
	)
);

// Enqueue tag management script.
wp_enqueue_script(
	'seo-generator-image-tags',
	plugin_dir_url( dirname( __DIR__ ) ) . 'assets/js/build/image-tags.js',
	array( 'wp-api-fetch' ),
	'1.0.0',
	true
);
?>

<div class="wrap seo-image-library-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Image Library Manager', 'seo-generator' ); ?></h1>

	<!-- Header Controls -->
	<div class="image-library-header">
		<!-- Search Form -->
		<form method="get" class="image-library-search">
			<input type="hidden" name="page" value="seo-image-library">
			<input
				type="search"
				name="s"
				value="<?php echo esc_attr( $search ); ?>"
				placeholder="<?php esc_attr_e( 'Search by filename...', 'seo-generator' ); ?>"
			>
			<button type="submit" class="button"><?php esc_html_e( 'Search', 'seo-generator' ); ?></button>
			<?php if ( ! empty( $search ) || ! empty( $tag ) || ! empty( $folder ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-image-library' ) ); ?>" class="button">
					<?php esc_html_e( 'Clear Filters', 'seo-generator' ); ?>
				</a>
			<?php endif; ?>
		</form>

		<!-- Tag Filter -->
		<form method="get" class="image-library-filter">
			<input type="hidden" name="page" value="seo-image-library">
			<select name="tag" onchange="this.form.submit()">
				<option value=""><?php esc_html_e( 'All Tags', 'seo-generator' ); ?></option>
				<?php foreach ( $all_tags as $term ) : ?>
					<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $tag, $term->slug ); ?>>
						<?php echo esc_html( $term->name ); ?> (<?php echo esc_html( $term->count ); ?>)
					</option>
				<?php endforeach; ?>
			</select>
		</form>

		<!-- Folder Filter -->
		<?php if ( ! empty( $all_folders ) ) : ?>
			<form method="get" class="image-library-filter">
				<input type="hidden" name="page" value="seo-image-library">
				<select name="folder" onchange="this.form.submit()">
					<option value=""><?php esc_html_e( 'All Folders', 'seo-generator' ); ?></option>
					<?php foreach ( $all_folders as $folder_name ) : ?>
						<option value="<?php echo esc_attr( $folder_name ); ?>" <?php selected( $folder, $folder_name ); ?>>
							<?php echo esc_html( $folder_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</form>
		<?php endif; ?>
	</div>

	<hr class="wp-header-end">

	<!-- Upload Area -->
	<div class="image-library-upload-area" id="seo-upload-area">
		<input
			type="file"
			id="seo-file-input"
			class="seo-file-input"
			multiple
			accept="image/jpeg,image/jpg,image/png,image/webp"
			style="display: none;"
		>
		<input
			type="file"
			id="seo-folder-input"
			class="seo-folder-input"
			webkitdirectory
			directory
			multiple
			accept="image/jpeg,image/jpg,image/png,image/webp"
			style="display: none;"
		>
		<div class="upload-notice">
			<p>
				<span class="dashicons dashicons-upload"></span>
				<?php esc_html_e( 'Bulk Upload', 'seo-generator' ); ?>
			</p>
			<p class="description">
				<?php esc_html_e( 'Drag and drop images here or click to upload', 'seo-generator' ); ?>
			</p>
			<p class="description">
				<?php esc_html_e( 'Supported formats: JPG, PNG, WEBP', 'seo-generator' ); ?>
			</p>
			<div class="upload-buttons">
				<button type="button" id="seo-upload-files-btn" class="button button-primary">
					<span class="dashicons dashicons-upload"></span>
					<?php esc_html_e( 'Upload Files', 'seo-generator' ); ?>
				</button>
				<button type="button" id="seo-upload-folder-btn" class="button button-secondary">
					<span class="dashicons dashicons-category"></span>
					<?php esc_html_e( 'Upload Folder', 'seo-generator' ); ?>
				</button>
			</div>
			<div id="seo-folder-not-supported" class="notice notice-warning" style="display: none; margin-top: 10px;">
				<p><?php esc_html_e( 'Your browser does not support folder upload. Please use Chrome, Edge, or Firefox for this feature.', 'seo-generator' ); ?></p>
			</div>
		</div>
	</div>

	<!-- Upload Progress Modal -->
	<div id="seo-upload-progress" class="seo-upload-progress" style="display: none;">
		<h3><?php esc_html_e( 'Uploading Images...', 'seo-generator' ); ?></h3>
		<div id="seo-upload-list" class="seo-upload-list"></div>
		<div class="seo-upload-summary">
			<strong id="seo-upload-count">0</strong> <?php esc_html_e( 'images uploaded', 'seo-generator' ); ?>
		</div>
	</div>

	<!-- Bulk Actions -->
	<div class="tablenav top">
		<div class="alignleft actions bulkactions">
			<label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'seo-generator' ); ?></label>
			<select name="action" id="bulk-action-selector-top">
				<option value="-1"><?php esc_html_e( 'Bulk Actions', 'seo-generator' ); ?></option>
				<option value="add-tags"><?php esc_html_e( 'Add Tags', 'seo-generator' ); ?></option>
				<option value="remove-tags"><?php esc_html_e( 'Remove Tags', 'seo-generator' ); ?></option>
				<option value="delete"><?php esc_html_e( 'Delete Permanently', 'seo-generator' ); ?></option>
			</select>
			<button type="button" id="bulk-action-apply" class="button action"><?php esc_html_e( 'Apply', 'seo-generator' ); ?></button>
			<span class="selected-count" style="display:none;">
				<strong>0</strong> <?php esc_html_e( 'images selected', 'seo-generator' ); ?>
			</span>
		</div>
	</div>

	<?php if ( $query->have_posts() ) : ?>
		<!-- Image Grid -->
		<div class="image-library-grid">
			<?php while ( $query->have_posts() ) : $query->the_post(); ?>
				<?php
				$image_id     = get_the_ID();
				$image_tags   = $this->getImageTags( $image_id );
				$image_folder = $this->getImageFolder( $image_id );
				$filename     = basename( get_attached_file( $image_id ) );
				?>
				<div class="image-grid-item" data-image-id="<?php echo esc_attr( $image_id ); ?>">
					<!-- Checkbox for bulk selection -->
					<div class="image-checkbox">
						<input
							type="checkbox"
							class="image-select"
							value="<?php echo esc_attr( $image_id ); ?>"
							id="image-<?php echo esc_attr( $image_id ); ?>"
						>
					</div>

					<!-- Thumbnail -->
					<div class="image-thumbnail">
						<label for="image-<?php echo esc_attr( $image_id ); ?>">
							<?php echo wp_get_attachment_image( $image_id, 'thumbnail', false, array( 'class' => 'library-thumb' ) ); ?>
						</label>
					</div>

					<!-- Image Info -->
					<div class="image-info">
						<div class="image-filename" title="<?php echo esc_attr( $filename ); ?>">
							<?php echo esc_html( wp_trim_words( $filename, 3, '...' ) ); ?>
						</div>

						<!-- Folder Badge -->
						<?php if ( $image_folder ) : ?>
							<div class="image-folder-badge" style="background: #0073aa; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; margin: 5px 0; display: inline-block;">
								<span class="dashicons dashicons-category" style="font-size: 12px; width: 12px; height: 12px; vertical-align: middle;"></span>
								<?php echo esc_html( $image_folder ); ?>
							</div>
						<?php endif; ?>

						<!-- Current Tags -->
						<div class="image-tags" data-image-id="<?php echo esc_attr( $image_id ); ?>">
							<?php if ( ! empty( $image_tags ) ) : ?>
								<?php foreach ( $image_tags as $tag ) : ?>
									<span class="image-tag" data-tag-id="<?php echo esc_attr( $tag->term_id ); ?>" data-tag-slug="<?php echo esc_attr( $tag->slug ); ?>">
										<?php echo esc_html( $tag->name ); ?>
										<button
											type="button"
											class="remove-tag"
											data-image-id="<?php echo esc_attr( $image_id ); ?>"
											data-tag-slug="<?php echo esc_attr( $tag->slug ); ?>"
											aria-label="<?php esc_attr_e( 'Remove tag', 'seo-generator' ); ?>"
											title="<?php esc_attr_e( 'Remove tag', 'seo-generator' ); ?>"
										>
											<span class="dashicons dashicons-no-alt"></span>
										</button>
									</span>
								<?php endforeach; ?>
							<?php else : ?>
								<span class="no-tags"><?php esc_html_e( 'No tags', 'seo-generator' ); ?></span>
							<?php endif; ?>
						</div>

						<!-- Edit Tags Button -->
						<div class="image-actions">
							<button
								type="button"
								class="button button-small edit-tags-button"
								data-image-id="<?php echo esc_attr( $image_id ); ?>"
								data-filename="<?php echo esc_attr( $filename ); ?>"
								title="<?php esc_attr_e( 'Add tags to this image', 'seo-generator' ); ?>"
							>
								<?php esc_html_e( 'Add Tags', 'seo-generator' ); ?>
							</button>
						</div>
					</div>
				</div>
			<?php endwhile; ?>
		</div>

		<!-- Pagination -->
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<?php
				$total_pages = $query->max_num_pages;
				$current     = $paged;

				if ( $total_pages > 1 ) {
					$page_links = paginate_links(
						array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'prev_text' => __( '&laquo; Previous', 'seo-generator' ),
							'next_text' => __( 'Next &raquo;', 'seo-generator' ),
							'total'     => $total_pages,
							'current'   => $current,
							'type'      => 'plain',
						)
					);

					if ( $page_links ) {
						echo '<span class="displaying-num">' . sprintf(
							/* translators: %d: number of images */
							esc_html( _n( '%d image', '%d images', $query->found_posts, 'seo-generator' ) ),
							number_format_i18n( $query->found_posts )
						) . '</span>';
						echo wp_kses_post( $page_links );
					}
				}
				?>
			</div>
		</div>

	<?php else : ?>
		<!-- Empty State -->
		<div class="notice notice-info">
			<p>
				<?php
				if ( ! empty( $search ) || ! empty( $tag ) ) {
					esc_html_e( 'No images found matching your search criteria.', 'seo-generator' );
				} else {
					esc_html_e( 'No images in the library yet. Upload images to get started.', 'seo-generator' );
				}
				?>
			</p>
		</div>
	<?php endif; ?>

	<?php wp_reset_postdata(); ?>
</div>
