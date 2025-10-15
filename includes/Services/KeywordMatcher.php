<?php
/**
 * Keyword Matcher Service
 *
 * Calculates keyword similarity between pages for internal linking.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Keyword Matcher Service
 *
 * Handles keyword extraction and similarity scoring for internal linking.
 */
class KeywordMatcher {

	/**
	 * High-value keywords (weight: 3)
	 *
	 * @var array
	 */
	private $high_value_keywords = array(
		'engagement',
		'wedding',
		'eternity',
		'anniversary',
		'bridal',
		'proposal',
		'bride',
		'groom',
	);

	/**
	 * Metals (weight: 2)
	 *
	 * @var array
	 */
	private $metals = array(
		'platinum',
		'gold',
		'white-gold',
		'rose-gold',
		'yellow-gold',
		'silver',
		'titanium',
		'tungsten',
		'palladium',
	);

	/**
	 * Stones (weight: 2)
	 *
	 * @var array
	 */
	private $stones = array(
		'diamond',
		'sapphire',
		'ruby',
		'emerald',
		'moissanite',
		'morganite',
		'aquamarine',
		'topaz',
		'opal',
	);

	/**
	 * Generic keywords (weight: 0.5)
	 *
	 * @var array
	 */
	private $generic_keywords = array(
		'ring',
		'band',
		'jewelry',
		'necklace',
		'bracelet',
		'earrings',
		'jewellery',
	);

	/**
	 * Stop words to exclude
	 *
	 * @var array
	 */
	private $stop_words = array(
		'the',
		'a',
		'an',
		'and',
		'or',
		'but',
		'in',
		'on',
		'at',
		'to',
		'for',
		'of',
		'with',
		'is',
		'was',
		'are',
		'been',
		'be',
		'have',
		'has',
		'had',
		'do',
		'does',
		'did',
	);

	/**
	 * Extract weighted keywords from text
	 *
	 * @param string $text Text to extract keywords from.
	 * @return array Associative array of keyword => weight.
	 */
	public function extractKeywords( string $text ): array {
		// Convert to lowercase.
		$text = strtolower( $text );

		// Remove special characters, keep spaces and hyphens.
		$text = preg_replace( '/[^a-z0-9\s\-]/', '', $text );

		// Split into words.
		$words = preg_split( '/\s+/', $text );

		// Remove stop words.
		$words = array_diff( $words, $this->stop_words );

		// Remove short words (< 3 chars).
		$words = array_filter(
			$words,
			function ( $word ) {
				return strlen( $word ) >= 3;
			}
		);

		// Remove duplicates.
		$words = array_unique( $words );

		// Apply weights.
		$weighted = array();
		foreach ( $words as $word ) {
			$weighted[ $word ] = $this->getKeywordWeight( $word );
		}

		return $weighted;
	}

	/**
	 * Get weight for a keyword
	 *
	 * @param string $keyword Keyword to weigh.
	 * @return float Weight value.
	 */
	private function getKeywordWeight( string $keyword ): float {
		if ( in_array( $keyword, $this->high_value_keywords, true ) ) {
			return 3.0;
		}

		if ( in_array( $keyword, $this->metals, true ) || in_array( $keyword, $this->stones, true ) ) {
			return 2.0;
		}

		if ( in_array( $keyword, $this->generic_keywords, true ) ) {
			return 0.5;
		}

		// Default weight for other keywords.
		return 1.0;
	}

	/**
	 * Calculate similarity score between two keyword sets
	 *
	 * @param array $keywords1 First keyword set (keyword => weight).
	 * @param array $keywords2 Second keyword set (keyword => weight).
	 * @return float Similarity score.
	 */
	public function calculateSimilarity( array $keywords1, array $keywords2 ): float {
		$score = 0;

		foreach ( $keywords1 as $word => $weight1 ) {
			if ( isset( $keywords2[ $word ] ) ) {
				// Both have the word - multiply weights.
				$score += $weight1 * $keywords2[ $word ];
			}
		}

		return $score;
	}

	/**
	 * Calculate similarity between two pages
	 *
	 * @param int $page1_id First page ID.
	 * @param int $page2_id Second page ID.
	 * @return float Similarity score.
	 */
	public function calculatePageSimilarity( int $page1_id, int $page2_id ): float {
		// Get focus keywords.
		$keyword1 = get_field( 'seo_focus_keyword', $page1_id );
		$keyword2 = get_field( 'seo_focus_keyword', $page2_id );

		if ( empty( $keyword1 ) || empty( $keyword2 ) ) {
			return 0;
		}

		// Extract weighted keywords.
		$words1 = $this->extractKeywords( $keyword1 );
		$words2 = $this->extractKeywords( $keyword2 );

		// Calculate similarity.
		return $this->calculateSimilarity( $words1, $words2 );
	}

	/**
	 * Score a candidate page against a source page
	 *
	 * @param int    $source_id      Source page ID.
	 * @param int    $candidate_id   Candidate page ID.
	 * @param array  $source_keywords Source page keywords.
	 * @param string $source_focus   Source focus keyword.
	 * @param object $source_topic   Source topic term.
	 * @return array Score data with breakdown.
	 */
	public function scoreCandidatePage( int $source_id, int $candidate_id, array $source_keywords, string $source_focus, $source_topic ): array {
		$score   = 0;
		$reasons = array();

		$candidate = get_post( $candidate_id );
		if ( ! $candidate ) {
			return array(
				'score'   => 0,
				'reasons' => array( 'Page not found' ),
			);
		}

		// Get candidate data.
		$candidate_focus = get_field( 'seo_focus_keyword', $candidate_id );
		$candidate_topic = get_the_terms( $candidate_id, 'seo-topic' );
		$candidate_topic = is_array( $candidate_topic ) && ! empty( $candidate_topic ) ? $candidate_topic[0] : null;

		// Keyword similarity (0-50 points).
		if ( $candidate_focus ) {
			$candidate_keywords = $this->extractKeywords( $candidate_focus );
			$keyword_score      = $this->calculateSimilarity( $source_keywords, $candidate_keywords );
			$keyword_score      = min( 50, $keyword_score ); // Cap at 50.
			$score             += $keyword_score;

			if ( $keyword_score > 0 ) {
				$reasons[] = sprintf( 'Keyword similarity: %.1f', $keyword_score );
			}
		}

		// Same topic bonus (20 points).
		if ( $source_topic && $candidate_topic && $source_topic->term_id === $candidate_topic->term_id ) {
			$score    += 20;
			$reasons[] = sprintf( 'Same topic: %s (+20)', $source_topic->name );
		}

		// Title contains focus keyword (15 points).
		if ( stripos( $candidate->post_title, $source_focus ) !== false ) {
			$score    += 15;
			$reasons[] = 'Title contains focus keyword (+15)';
		}

		// Meta description match (10 points).
		$candidate_meta = get_field( 'seo_meta_description', $candidate_id );
		if ( $candidate_meta && stripos( $candidate_meta, $source_focus ) !== false ) {
			$score    += 10;
			$reasons[] = 'Meta description contains keyword (+10)';
		}

		// Recently published penalty (-10 points if <7 days old).
		$age_days = ( time() - strtotime( $candidate->post_date ) ) / DAY_IN_SECONDS;
		if ( $age_days < 7 ) {
			$score    -= 10;
			$reasons[] = sprintf( 'Recently published: %.0f days old (-10)', $age_days );
		} elseif ( $age_days > 30 ) {
			$score    += 5;
			$reasons[] = 'Established page (+5)';
		}

		return array(
			'id'      => $candidate_id,
			'score'   => $score,
			'reasons' => $reasons,
		);
	}
}
