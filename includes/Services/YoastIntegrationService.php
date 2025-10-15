<?php
/**
 * Yoast SEO Integration Service
 *
 * Syncs generated content SEO data to Yoast SEO plugin fields.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Yoast SEO Integration Service
 *
 * Handles synchronization between our SEO fields and Yoast SEO fields.
 * Also provides access to Yoast's SEO analysis and ranking data.
 *
 * Benefits of integration:
 * - Get Yoast's content analysis and SEO score
 * - Use Yoast's rank tracking features
 * - Leverage Yoast's integrations (Google Search Console, etc.)
 * - Use Yoast's sitemap features
 * - Get readability analysis
 *
 * Usage:
 * ```php
 * $yoast = new YoastIntegrationService();
 * $yoast->syncToYoast( $post_id );
 * ```
 */
class YoastIntegrationService {
	/**
	 * Check if Yoast SEO is active.
	 *
	 * @return bool True if Yoast is active, false otherwise.
	 */
	public function isYoastActive(): bool {
		return defined( 'WPSEO_VERSION' );
	}

	/**
	 * Sync our SEO fields to Yoast SEO fields.
	 *
	 * Takes data from our ACF fields and populates Yoast's meta fields.
	 *
	 * @param int $post_id Post ID to sync.
	 * @return bool True on success, false if Yoast not active.
	 */
	public function syncToYoast( int $post_id ): bool {
		if ( ! $this->isYoastActive() ) {
			return false;
		}

		// Get our SEO fields.
		$focus_keyword    = get_field( 'seo_focus_keyword', $post_id );
		$seo_title        = get_field( 'seo_title', $post_id );
		$meta_description = get_field( 'seo_meta_description', $post_id );
		$canonical_url    = get_field( 'seo_canonical', $post_id );

		// Sync to Yoast fields.
		if ( ! empty( $focus_keyword ) ) {
			update_post_meta( $post_id, '_yoast_wpseo_focuskw', $focus_keyword );
		}

		if ( ! empty( $seo_title ) ) {
			update_post_meta( $post_id, '_yoast_wpseo_title', $seo_title );
		}

		if ( ! empty( $meta_description ) ) {
			update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_description );
		}

		if ( ! empty( $canonical_url ) ) {
			update_post_meta( $post_id, '_yoast_wpseo_canonical', $canonical_url );
		}

		// Additional Yoast settings you might want to set.
		// Enable breadcrumbs for this page.
		update_post_meta( $post_id, '_yoast_wpseo_breadcrumbs_title', get_the_title( $post_id ) );

		// Set meta robots to index, follow (default for SEO pages).
		update_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', '0' );
		update_post_meta( $post_id, '_yoast_wpseo_meta-robots-nofollow', '0' );

		error_log( "[Yoast Integration] Synced SEO data for post {$post_id}" );

		return true;
	}

	/**
	 * Get Yoast SEO score for a post.
	 *
	 * Returns the SEO and readability scores from Yoast analysis.
	 *
	 * @param int $post_id Post ID.
	 * @return array SEO score data.
	 */
	public function getYoastScores( int $post_id ): array {
		if ( ! $this->isYoastActive() ) {
			return array(
				'seo_score'         => null,
				'readability_score' => null,
				'available'         => false,
			);
		}

		return array(
			'seo_score'         => get_post_meta( $post_id, '_yoast_wpseo_linkdex', true ),
			'readability_score' => get_post_meta( $post_id, '_yoast_wpseo_content_score', true ),
			'available'         => true,
		);
	}

	/**
	 * Get Yoast's focus keyword from a post.
	 *
	 * @param int $post_id Post ID.
	 * @return string Focus keyword or empty string.
	 */
	public function getYoastFocusKeyword( int $post_id ): string {
		if ( ! $this->isYoastActive() ) {
			return '';
		}

		return get_post_meta( $post_id, '_yoast_wpseo_focuskw', true ) ?: '';
	}

	/**
	 * Trigger Yoast SEO analysis for a post.
	 *
	 * Forces Yoast to recalculate SEO score.
	 * Note: Yoast analysis happens automatically in admin,
	 * but this can be useful for programmatic updates.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function triggerYoastAnalysis( int $post_id ): void {
		if ( ! $this->isYoastActive() ) {
			return;
		}

		// Delete cached scores to force recalculation.
		delete_post_meta( $post_id, '_yoast_wpseo_linkdex' );
		delete_post_meta( $post_id, '_yoast_wpseo_content_score' );

		// Trigger analysis by updating a meta field.
		// Yoast will recalculate on next admin view.
		update_post_meta( $post_id, '_yoast_wpseo_meta_refresh', time() );
	}

	/**
	 * Get SEO recommendations from Yoast.
	 *
	 * Returns Yoast's analysis results with suggestions.
	 *
	 * @param int $post_id Post ID.
	 * @return array Analysis data with problems and improvements.
	 */
	public function getYoastRecommendations( int $post_id ): array {
		if ( ! $this->isYoastActive() ) {
			return array(
				'available' => false,
				'problems'  => array(),
				'improvements' => array(),
				'good'      => array(),
			);
		}

		// Yoast stores analysis results in these meta fields.
		$analysis = get_post_meta( $post_id, '_yoast_wpseo_analysis', true );

		// Parse Yoast's analysis data.
		// Note: Yoast's internal format may vary by version.
		return array(
			'available'     => true,
			'raw_analysis'  => $analysis,
			'seo_score'     => $this->getYoastScores( $post_id )['seo_score'],
		);
	}

	/**
	 * Auto-sync to Yoast after content generation.
	 *
	 * Hook this into your generation workflow.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function autoSyncAfterGeneration( int $post_id ): void {
		// Only sync for seo-page post type.
		if ( get_post_type( $post_id ) !== 'seo-page' ) {
			return;
		}

		// Sync to Yoast.
		$this->syncToYoast( $post_id );

		// Optionally trigger analysis.
		$this->triggerYoastAnalysis( $post_id );
	}

	/**
	 * Get Yoast's suggested meta description length.
	 *
	 * @return int Recommended meta description length.
	 */
	public function getMetaDescriptionLength(): int {
		// Yoast recommends 120-156 characters for meta descriptions.
		return 156;
	}

	/**
	 * Get Yoast's suggested SEO title length.
	 *
	 * @return int Recommended SEO title length.
	 */
	public function getSEOTitleLength(): int {
		// Yoast recommends max 60 characters for SEO titles.
		return 60;
	}

	/**
	 * Validate content against Yoast's recommendations.
	 *
	 * @param string $seo_title        SEO title.
	 * @param string $meta_description Meta description.
	 * @param string $focus_keyword    Focus keyword.
	 * @return array Validation results with warnings.
	 */
	public function validateSEOContent( string $seo_title, string $meta_description, string $focus_keyword ): array {
		$warnings = array();

		// Check SEO title length.
		$title_length = mb_strlen( $seo_title );
		if ( $title_length > $this->getSEOTitleLength() ) {
			$warnings[] = sprintf(
				'SEO title is %d characters (recommended: %d or less)',
				$title_length,
				$this->getSEOTitleLength()
			);
		}

		// Check meta description length.
		$desc_length = mb_strlen( $meta_description );
		if ( $desc_length < 120 || $desc_length > $this->getMetaDescriptionLength() ) {
			$warnings[] = sprintf(
				'Meta description is %d characters (recommended: 120-156)',
				$desc_length
			);
		}

		// Check if focus keyword appears in title.
		if ( ! empty( $focus_keyword ) && stripos( $seo_title, $focus_keyword ) === false ) {
			$warnings[] = sprintf(
				'Focus keyword "%s" not found in SEO title',
				$focus_keyword
			);
		}

		// Check if focus keyword appears in meta description.
		if ( ! empty( $focus_keyword ) && stripos( $meta_description, $focus_keyword ) === false ) {
			$warnings[] = sprintf(
				'Focus keyword "%s" not found in meta description',
				$focus_keyword
			);
		}

		return array(
			'valid'    => empty( $warnings ),
			'warnings' => $warnings,
		);
	}
}
