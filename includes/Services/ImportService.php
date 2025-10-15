<?php
/**
 * Import Service
 *
 * Handles batch creation of posts from CSV data.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Import Service
 *
 * Creates draft posts from parsed CSV data with batch processing,
 * duplicate detection, and comprehensive error handling.
 *
 * Usage:
 * ```php
 * $import_service = new ImportService([
 *     'batch_size' => 10,
 *     'check_duplicates' => true,
 * ]);
 *
 * $result = $import_service->processSingleBatch($rows, $headers, $mapping);
 * ```
 */
class ImportService {
	/**
	 * Batch size for processing.
	 *
	 * @var int
	 */
	private $batch_size = 10;

	/**
	 * Whether to check for duplicate posts.
	 *
	 * @var bool
	 */
	private $check_duplicates = true;

	/**
	 * Generation mode (drafts_only or auto_generate).
	 *
	 * @var string
	 */
	private $generation_mode = 'drafts_only';

	/**
	 * Blocks to generate during auto_generate mode.
	 * If null, all blocks will be generated.
	 *
	 * @var array|null
	 */
	private $blocks_to_generate = null;

	/**
	 * Queue index for scheduling offset.
	 *
	 * @var int
	 */
	private $queue_index = 0;

	/**
	 * Image download tracking.
	 *
	 * @var array
	 */
	private $image_results = array(
		'downloaded' => array(),
		'reused'     => array(),
		'failed'     => array(),
	);

	/**
	 * ImageDownloadService instance.
	 *
	 * @var ImageDownloadService|null
	 */
	private $image_service = null;

	/**
	 * Constructor.
	 *
	 * @param array $options Configuration options.
	 *                       - batch_size: Number of rows per batch (default: 10)
	 *                       - check_duplicates: Check for existing posts (default: true)
	 *                       - generation_mode: Generation mode (drafts_only or auto_generate, default: drafts_only)
	 *                       - blocks_to_generate: Array of specific blocks to generate, or null for all blocks (default: null)
	 */
	public function __construct( array $options = array() ) {
		$this->batch_size         = isset( $options['batch_size'] ) ? (int) $options['batch_size'] : 10;
		$this->check_duplicates   = isset( $options['check_duplicates'] ) ? (bool) $options['check_duplicates'] : true;
		$this->generation_mode    = isset( $options['generation_mode'] ) ? $options['generation_mode'] : 'drafts_only';
		$this->blocks_to_generate = isset( $options['blocks_to_generate'] ) ? $options['blocks_to_generate'] : null;
		$this->image_service      = new ImageDownloadService();
	}

	/**
	 * Process a single batch of CSV rows.
	 *
	 * @param array $rows    CSV rows to process.
	 * @param array $headers CSV column headers.
	 * @param array $mapping Column to field mapping.
	 * @return array Batch results with created, skipped, errors, and images.
	 */
	public function processSingleBatch( array $rows, array $headers, array $mapping ): array {
		// Suspend cache to prevent memory bloat.
		wp_suspend_cache_addition( true );

		$results = array(
			'created' => array(),
			'skipped' => array(),
			'errors'  => array(),
			'images'  => array(
				'downloaded' => array(),
				'reused'     => array(),
				'failed'     => array(),
			),
		);

		foreach ( $rows as $row_index => $row ) {
			$post_result = $this->createPost( $row, $headers, $mapping );

			if ( is_wp_error( $post_result ) ) {
				// Check if it's a duplicate (skipped) or actual error.
				if ( $post_result->get_error_code() === 'duplicate' ) {
					$results['skipped'][] = array(
						'row'    => $row_index + 2, // +2 for header row and 0-index.
						'title'  => $this->extractValue( $row, $headers, $mapping, 'page_title' ),
						'reason' => 'Duplicate',
					);
				} else {
					$results['errors'][] = array(
						'row'   => $row_index + 2,
						'error' => $post_result->get_error_message(),
					);
				}
			} else {
				// Successfully created post.
				$results['created'][] = array(
					'post_id' => $post_result,
					'title'   => get_the_title( $post_result ),
				);

				// Queue for generation if auto_generate enabled.
				if ( $this->generation_mode === 'auto_generate' ) {
					$queue = new GenerationQueue();
					$queue->queuePost( $post_result, $this->queue_index, $this->blocks_to_generate );
					$this->queue_index++;
				}
			}
		}

		// Trigger WordPress Cron to start processing queue immediately.
		if ( $this->generation_mode === 'auto_generate' && ! empty( $results['created'] ) ) {
			$this->triggerCronProcessing();
		}

		// Include image results.
		$results['images'] = $this->image_results;

		// Clear cache after batch.
		wp_cache_flush();
		wp_suspend_cache_addition( false );

		return $results;
	}

	/**
	 * Create a post from CSV row.
	 *
	 * @param array $row     CSV row data.
	 * @param array $headers CSV headers.
	 * @param array $mapping Column to field mapping.
	 * @return int|\WP_Error Post ID on success, WP_Error on failure.
	 */
	private function createPost( array $row, array $headers, array $mapping ) {
		// Extract title.
		$title = $this->extractValue( $row, $headers, $mapping, 'page_title' );

		if ( empty( $title ) ) {
			return new \WP_Error(
				'missing_title',
				__( 'Row missing page title.', 'seo-generator' )
			);
		}

		// Check for duplicates.
		if ( $this->check_duplicates && $this->postExists( $title ) ) {
			return new \WP_Error(
				'duplicate',
				sprintf(
					/* translators: %s: post title */
					__( 'Post already exists: %s', 'seo-generator' ),
					$title
				)
			);
		}

		// Create post with block template.
		$post_data = array(
			'post_type'    => 'seo-page',
			'post_status'  => 'draft',
			'post_title'   => $title,
			'post_name'    => sanitize_title( $title ),
			'post_author'  => get_current_user_id(),
			'post_content' => $this->generateBlockTemplate( $title ),
		);

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Save metadata (ACF fields, taxonomy).
		$this->saveMetadata( $post_id, $row, $headers, $mapping );

		return $post_id;
	}

	/**
	 * Generate block template HTML for new posts.
	 *
	 * @param string $title Page title.
	 * @return string Block HTML content.
	 */
	private function generateBlockTemplate( string $title ): string {
		// Create block content matching the template in SEOPage.php
		$blocks = '<!-- wp:columns {"className":"hero-section"} -->
<div class="wp-block-columns hero-section"><!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">' . esc_html( $title ) . '</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>In a world where the delicate and the dainty often take center stage, the allure of wide band diamond rings offers a refreshing deviation—a bold statement of elegance and individuality.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>These rings, with their generous bands and captivating diamonds, do more than adorn a finger; they tell a story. A story of craftsmanship, tradition, and personal expression that transcends time and fashion.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:image {"align":"center"} -->
<figure class="wp-block-image aligncenter"><img alt=""/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->';

		return $blocks;
	}

	/**
	 * Save post metadata (ACF fields and taxonomy).
	 *
	 * @param int   $post_id Post ID.
	 * @param array $row     CSV row data.
	 * @param array $headers CSV headers.
	 * @param array $mapping Column to field mapping.
	 * @return void
	 */
	private function saveMetadata( int $post_id, array $row, array $headers, array $mapping ): void {
		// Save Focus Keyword ACF field.
		$focus_keyword = $this->extractValue( $row, $headers, $mapping, 'focus_keyword' );
		if ( ! empty( $focus_keyword ) && function_exists( 'update_field' ) ) {
			update_field( 'seo_focus_keyword', $focus_keyword, $post_id );
		}

		// Assign Topic Category taxonomy.
		$topic = $this->extractValue( $row, $headers, $mapping, 'topic_category' );
		if ( ! empty( $topic ) ) {
			$this->assignTaxonomy( $post_id, $topic );
		}

		// Handle image URL download (Story 6.6).
		$image_url = $this->extractValue( $row, $headers, $mapping, 'image_url' );
		if ( ! empty( $image_url ) && filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
			$image_url = esc_url_raw( $image_url );

			// Check for existing image first (for tracking reused images).
			$existing = $this->image_service->downloadAndAttach(
				$image_url,
				$post_id,
				get_the_title( $post_id )
			);

			if ( is_wp_error( $existing ) ) {
				// Track failure.
				$this->image_results['failed'][] = array(
					'post_id' => $post_id,
					'url'     => $image_url,
					'error'   => $this->image_service->formatImageError( $existing ),
				);
				// Log error but continue import.
				error_log( "Failed to download image for post {$post_id}: " . $existing->get_error_message() );
			} else {
				// Check if it was reused or newly downloaded.
				// If reused, the downloadAndAttach method already logged it.
				// We can detect reuse by checking if _import_date was just set.
				$import_date = get_post_meta( $existing, '_import_date', true );
				$is_recent   = $import_date && ( strtotime( $import_date ) > ( time() - 5 ) );

				if ( $is_recent ) {
					// Newly downloaded.
					$this->image_results['downloaded'][] = array(
						'post_id'       => $post_id,
						'attachment_id' => $existing,
					);
				} else {
					// Reused existing image.
					$this->image_results['reused'][] = array(
						'post_id'       => $post_id,
						'attachment_id' => $existing,
					);
				}
			}
		}
	}

	/**
	 * Extract value from row based on column mapping.
	 *
	 * @param array  $row     CSV row data.
	 * @param array  $headers CSV headers.
	 * @param array  $mapping Column to field mapping.
	 * @param string $field   Field to extract.
	 * @return string Extracted value or empty string.
	 */
	private function extractValue( array $row, array $headers, array $mapping, string $field ): string {
		// Find the column that maps to this field.
		$column_name = array_search( $field, $mapping, true );

		if ( $column_name === false ) {
			return '';
		}

		// Find the index of this column in headers.
		$column_index = array_search( $column_name, $headers, true );

		if ( $column_index === false || ! isset( $row[ $column_index ] ) ) {
			return '';
		}

		return trim( $row[ $column_index ] );
	}

	/**
	 * Assign taxonomy term to post (create term if doesn't exist).
	 *
	 * @param int    $post_id Post ID.
	 * @param string $topic_name Topic/category name.
	 * @return void
	 */
	private function assignTaxonomy( int $post_id, string $topic_name ): void {
		// Map intent to topic if needed.
		$topic_name = $this->mapIntentToTopic( $topic_name );

		// Check if term exists.
		$term = term_exists( $topic_name, 'seo-topic' );

		if ( ! $term ) {
			// Create term if it doesn't exist.
			$term = wp_insert_term( $topic_name, 'seo-topic' );

			if ( is_wp_error( $term ) ) {
				error_log( 'Failed to create term: ' . $term->get_error_message() );
				return;
			}
		}

		// Assign term to post.
		$term_id = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
		$result  = wp_set_object_terms( $post_id, $term_id, 'seo-topic' );

		if ( is_wp_error( $result ) ) {
			error_log( 'Failed to assign term: ' . $result->get_error_message() );
		}
	}

	/**
	 * Map common intent values to topic categories.
	 *
	 * @param string $intent Intent value from CSV.
	 * @return string Mapped topic name.
	 */
	private function mapIntentToTopic( string $intent ): string {
		$mapping = array(
			'commercial'     => 'Product Reviews',
			'informational'  => 'How-To Guides',
			'transactional'  => 'Product Reviews',
			'navigational'   => 'Resources',
		);

		$intent_lower = strtolower( trim( $intent ) );

		return isset( $mapping[ $intent_lower ] ) ? $mapping[ $intent_lower ] : $intent;
	}

	/**
	 * Check if post with given title already exists.
	 *
	 * @param string $title Post title.
	 * @return bool True if post exists, false otherwise.
	 */
	private function postExists( string $title ): bool {
		$query = new \WP_Query(
			array(
				'post_type'      => 'seo-page',
				'post_status'    => 'any',
				'title'          => $title,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		return $query->have_posts();
	}

	/**
	 * Update import progress in transient.
	 *
	 * @param int    $current_batch Current batch number.
	 * @param int    $total_batches Total number of batches.
	 * @param int    $rows_processed Rows processed so far.
	 * @param int    $rows_total     Total rows to process.
	 * @param string $status         Optional status message (e.g., "Downloading image...").
	 * @return void
	 */
	public function updateProgress( int $current_batch, int $total_batches, int $rows_processed, int $rows_total, string $status = '' ): void {
		$user_id = get_current_user_id();

		$progress = array(
			'current_batch'     => $current_batch,
			'total_batches'     => $total_batches,
			'rows_processed'    => $rows_processed,
			'rows_total'        => $rows_total,
			'percentage'        => $rows_total > 0 ? round( ( $rows_processed / $rows_total ) * 100 ) : 0,
			'timestamp'         => time(),
			'status'            => $status,
			'images_downloaded' => count( $this->image_results['downloaded'] ),
			'images_reused'     => count( $this->image_results['reused'] ),
			'images_failed'     => count( $this->image_results['failed'] ),
		);

		set_transient( 'import_progress_' . $user_id, $progress, HOUR_IN_SECONDS );
	}

	/**
	 * Trigger WordPress Cron to start processing immediately.
	 *
	 * This ensures queued posts start generating right away instead of waiting
	 * for the next site visit to trigger cron.
	 *
	 * @return void
	 */
	private function triggerCronProcessing(): void {
		// Spawn a non-blocking request to wp-cron.php.
		$cron_url = site_url( 'wp-cron.php?doing_wp_cron' );

		// Use wp_remote_post with a very short timeout and blocking=false
		// so it doesn't slow down the import.
		wp_remote_post(
			$cron_url,
			array(
				'timeout'   => 0.01,
				'blocking'  => false,
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
			)
		);

		error_log( '[SEO Generator] Triggered WordPress Cron to start processing queue' );
	}

	/**
	 * Get available memory in bytes.
	 *
	 * @return int Available memory in bytes.
	 */
	public function getAvailableMemory(): int {
		$memory_limit       = ini_get( 'memory_limit' );
		$memory_limit_bytes = $this->convertToBytes( $memory_limit );
		$memory_used        = memory_get_usage( true );

		return $memory_limit_bytes - $memory_used;
	}

	/**
	 * Convert memory limit string to bytes.
	 *
	 * @param string $value Memory limit value (e.g., '256M', '1G').
	 * @return int Memory limit in bytes.
	 */
	private function convertToBytes( string $value ): int {
		$value = trim( $value );
		$unit  = strtolower( substr( $value, -1 ) );
		$value = (int) $value;

		switch ( $unit ) {
			case 'g':
				return $value * 1024 * 1024 * 1024;
			case 'm':
				return $value * 1024 * 1024;
			case 'k':
				return $value * 1024;
			default:
				return $value;
		}
	}
}
