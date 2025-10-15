<?php
/**
 * Post List Columns
 *
 * Adds custom columns to SEO Pages list in WordPress admin.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Manages custom columns for SEO Pages post list.
 */
class PostListColumns {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'manage_seo-page_posts_columns', array( $this, 'addColumns' ) );
		add_action( 'manage_seo-page_posts_custom_column', array( $this, 'renderColumn' ), 10, 2 );
		add_filter( 'manage_edit-seo-page_sortable_columns', array( $this, 'addSortableColumns' ) );
		add_action( 'admin_head', array( $this, 'addColumnStyles' ) );
	}

	/**
	 * Add custom columns to SEO Pages list.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function addColumns( array $columns ): array {
		// Insert generation status column after title.
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			if ( $key === 'title' ) {
				$new_columns['generation_status'] = 'Generation Status';
				$new_columns['focus_keyword']      = 'Focus Keyword';
				$new_columns['topic']              = 'Topic';
			}
		}

		return $new_columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function renderColumn( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'generation_status':
				$this->renderGenerationStatus( $post_id );
				break;

			case 'focus_keyword':
				$this->renderFocusKeyword( $post_id );
				break;

			case 'topic':
				$this->renderTopic( $post_id );
				break;
		}
	}

	/**
	 * Render generation status column.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function renderGenerationStatus( int $post_id ): void {
		// Check queue status first.
		$queue_status = $this->getQueueStatus( $post_id );

		if ( $queue_status ) {
			$this->displayQueueStatus( $queue_status );
			return;
		}

		// If not in queue, check if content has been generated.
		$has_content = $this->hasGeneratedContent( $post_id );

		if ( $has_content ) {
			echo '<span class="seo-badge seo-badge--published">Published</span>';
		} else {
			echo '<span class="seo-badge seo-badge--draft">Draft</span>';
		}
	}

	/**
	 * Display queue status badge.
	 *
	 * @param array $queue_item Queue item data.
	 * @return void
	 */
	private function displayQueueStatus( array $queue_item ): void {
		$status = $queue_item['status'];
		$scheduled_time = isset( $queue_item['scheduled_time'] ) ? $queue_item['scheduled_time'] : 0;

		switch ( $status ) {
			case 'pending':
				$time_until = '';
				if ( $scheduled_time > 0 ) {
					$diff = $scheduled_time - time();
					if ( $diff > 0 ) {
						$minutes = floor( $diff / 60 );
						$time_until = " ({$minutes}m)";
					} else {
						$time_until = ' (ready)';
					}
				}
				echo '<span class="seo-badge seo-badge--pending">Queued' . esc_html( $time_until ) . '</span>';
				break;

			case 'processing':
				echo '<span class="seo-badge seo-badge--pending">Generating...</span>';
				break;

			case 'completed':
				echo '<span class="seo-badge seo-badge--published">Published</span>';
				break;

			case 'failed':
				$error = isset( $queue_item['error'] ) ? $queue_item['error'] : 'Unknown error';
				echo '<span class="seo-badge seo-badge--failed" title="' . esc_attr( $error ) . '">Failed</span>';
				break;

			default:
				echo '<span class="seo-badge seo-badge--draft">Unknown</span>';
		}
	}

	/**
	 * Render focus keyword column.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function renderFocusKeyword( int $post_id ): void {
		if ( function_exists( 'get_field' ) ) {
			$focus_keyword = get_field( 'seo_focus_keyword', $post_id );
			if ( $focus_keyword ) {
				echo '<strong>' . esc_html( $focus_keyword ) . '</strong>';
			} else {
				echo '<span style="color: #999;">—</span>';
			}
		}
	}

	/**
	 * Render topic column.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function renderTopic( int $post_id ): void {
		$terms = get_the_terms( $post_id, 'seo-topic' );

		if ( $terms && ! is_wp_error( $terms ) ) {
			$term_links = array();
			foreach ( $terms as $term ) {
				$term_links[] = '<a href="' . esc_url( admin_url( 'edit.php?post_type=seo-page&seo-topic=' . $term->slug ) ) . '">' . esc_html( $term->name ) . '</a>';
			}
			echo implode( ', ', $term_links );
		} else {
			echo '<span style="color: #999;">—</span>';
		}
	}

	/**
	 * Get queue status for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array|null Queue item data or null if not in queue.
	 */
	private function getQueueStatus( int $post_id ): ?array {
		$queue = get_option( 'seo_generation_queue', array() );

		foreach ( $queue as $item ) {
			if ( isset( $item['post_id'] ) && $item['post_id'] === $post_id ) {
				return $item;
			}
		}

		return null;
	}

	/**
	 * Check if post has generated content.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True if content exists, false otherwise.
	 */
	private function hasGeneratedContent( int $post_id ): bool {
		// Check if auto_generated meta exists.
		$auto_generated = get_post_meta( $post_id, '_auto_generated', true );
		if ( $auto_generated ) {
			return true;
		}

		// Check if any hero fields have content (as a proxy for generation).
		if ( function_exists( 'get_field' ) ) {
			$hero_title = get_field( 'hero_title', $post_id );
			$hero_summary = get_field( 'hero_summary', $post_id );

			if ( ! empty( $hero_title ) || ! empty( $hero_summary ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add sortable columns.
	 *
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function addSortableColumns( array $columns ): array {
		$columns['focus_keyword'] = 'focus_keyword';
		$columns['topic'] = 'topic';
		return $columns;
	}

	/**
	 * Add inline styles for list columns.
	 *
	 * @return void
	 */
	public function addColumnStyles(): void {
		$screen = get_current_screen();

		if ( ! $screen || $screen->post_type !== 'seo-page' || $screen->base !== 'edit' ) {
			return;
		}

		?>
		<style>
			/* Column widths */
			.column-generation_status {
				width: 140px;
			}
			.column-focus_keyword {
				width: 180px;
			}
			.column-topic {
				width: 150px;
			}

			/* List table styling */
			.wp-list-table tbody tr {
				transition: background-color 150ms ease;
			}

			.wp-list-table tbody tr:hover {
				background-color: var(--gray-50);
			}

			/* Badge cursor for failed status */
			.seo-badge--failed {
				cursor: help;
			}
		</style>
		<?php
	}
}
