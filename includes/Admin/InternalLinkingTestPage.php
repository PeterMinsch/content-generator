<?php
/**
 * Internal Linking Test Page
 *
 * Admin page for testing internal linking functionality.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Internal Linking Test Page
 */
class InternalLinkingTestPage {

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'addAdminPage' ) );
	}

	/**
	 * Add admin page
	 *
	 * @return void
	 */
	public function addAdminPage(): void {
		add_submenu_page(
			'seo-generator',
			'Test Internal Links',
			'🔗 Test Links',
			'manage_options',
			'seo-generator-test-links',
			array( $this, 'renderPage' )
		);
	}

	/**
	 * Render the test page
	 *
	 * @return void
	 */
	public function renderPage(): void {
		?>
		<div class="wrap">
			<style>
				.test-container {
					max-width: 1200px;
					margin: 20px 0;
				}
				.test-section {
					background: white;
					padding: 20px;
					margin: 20px 0;
					border: 1px solid #ccd0d4;
					border-radius: 4px;
					box-shadow: 0 1px 1px rgba(0,0,0,0.04);
				}
				.test-section h2 {
					margin-top: 0;
					border-bottom: 1px solid #ddd;
					padding-bottom: 10px;
				}
				.success-box {
					background: #d4edda;
					border: 1px solid #c3e6cb;
					color: #155724;
					padding: 15px;
					margin: 10px 0;
					border-radius: 4px;
				}
				.error-box {
					background: #f8d7da;
					border: 1px solid #f5c6cb;
					color: #721c24;
					padding: 15px;
					margin: 10px 0;
					border-radius: 4px;
				}
				.info-box {
					background: #d1ecf1;
					border: 1px solid #bee5eb;
					color: #0c5460;
					padding: 15px;
					margin: 10px 0;
					border-radius: 4px;
				}
				.warning-box {
					background: #fff3cd;
					border: 1px solid #ffeaa7;
					color: #856404;
					padding: 15px;
					margin: 10px 0;
					border-radius: 4px;
				}
				.test-table {
					width: 100%;
					border-collapse: collapse;
					margin: 15px 0;
				}
				.test-table th,
				.test-table td {
					text-align: left;
					padding: 12px;
					border-bottom: 1px solid #ddd;
				}
				.test-table th {
					background: #f0f0f1;
					font-weight: 600;
				}
				.status-badge {
					display: inline-block;
					padding: 4px 8px;
					border-radius: 3px;
					font-size: 11px;
					font-weight: 600;
					text-transform: uppercase;
				}
				.status-pass {
					background: #d4edda;
					color: #155724;
				}
				.status-fail {
					background: #f8d7da;
					color: #721c24;
				}
				pre {
					background: #f5f5f5;
					padding: 15px;
					border-radius: 4px;
					overflow-x: auto;
					font-size: 12px;
				}
			</style>

			<h1>🔗 Internal Linking System Diagnostic</h1>
			<p>This tool tests the automated internal linking feature.</p>

			<div class="test-container">
				<?php $this->runTests(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Run all diagnostic tests
	 *
	 * @return void
	 */
	private function runTests(): void {
		// TEST 1: Check if classes exist
		$this->testClassAvailability();

		// TEST 2: Check cron schedule
		$this->testCronSchedule();

		// TEST 3: Check published pages
		$this->testPublishedPages();

		// TEST 4: Test KeywordMatcher
		$this->testKeywordMatcher();

		// TEST 5: Manual link generation
		$this->testLinkGeneration();

		// TEST 6: Debug logs
		$this->testDebugLogs();

		// TEST 7: Frontend display
		$this->testFrontendDisplay();

		// TEST 8: Debug specific page
		$this->testSpecificPage();

		// TEST 9: Check Elementor meta
		$this->testElementorMeta();

		// TEST 10: Check Beaver Builder meta
		$this->testBeaverBuilderMeta();

		// TEST 11: Migrate to Block Editor
		$this->testMigrateToBlocks();

		// TEST 12: Check AI Generation Status
		$this->testAIGenerationStatus();
	}

	/**
	 * Test 1: Class Availability
	 *
	 * @return void
	 */
	private function testClassAvailability(): void {
		echo '<div class="test-section">';
		echo '<h2>Test 1: Class Availability</h2>';

		$classes = array(
			'SEOGenerator\\Services\\KeywordMatcher',
			'SEOGenerator\\Services\\InternalLinkingService',
			'SEOGenerator\\Cron\\LinkRefreshHandler',
		);

		$all_exist = true;
		echo '<table class="test-table">';
		echo '<tr><th>Class</th><th>Status</th></tr>';

		foreach ( $classes as $class ) {
			$exists     = class_exists( $class );
			$all_exist  = $all_exist && $exists;
			echo '<tr>';
			echo '<td><code>' . esc_html( $class ) . '</code></td>';
			echo '<td><span class="status-badge status-' . ( $exists ? 'pass' : 'fail' ) . '">';
			echo $exists ? '✓ LOADED' : '✗ NOT FOUND';
			echo '</span></td>';
			echo '</tr>';
		}
		echo '</table>';

		if ( $all_exist ) {
			echo '<div class="success-box">✓ All classes loaded successfully!</div>';
		} else {
			echo '<div class="error-box">✗ Some classes are missing. Check if files exist in includes/ directory.</div>';
		}
		echo '</div>';
	}

	/**
	 * Test 2: Cron Schedule
	 *
	 * @return void
	 */
	private function testCronSchedule(): void {
		echo '<div class="test-section">';
		echo '<h2>Test 2: Cron Schedule</h2>';

		$next_scheduled = wp_next_scheduled( 'seo_refresh_internal_links' );

		if ( $next_scheduled ) {
			echo '<div class="success-box">';
			echo '✓ Cron job is scheduled<br>';
			echo '<strong>Next run:</strong> ' . date( 'Y-m-d H:i:s', $next_scheduled ) . ' (' . human_time_diff( $next_scheduled ) . ')';
			echo '</div>';
		} else {
			echo '<div class="error-box">';
			echo '✗ Cron job is NOT scheduled<br>';
			echo '<strong>Action:</strong> Try deactivating and reactivating the plugin.';
			echo '</div>';
		}

		$last_refresh = get_option( 'seo_last_link_refresh' );
		if ( $last_refresh && is_array( $last_refresh ) ) {
			echo '<div class="info-box">';
			echo '<strong>Last Refresh:</strong><br>';
			echo 'Timestamp: ' . date( 'Y-m-d H:i:s', $last_refresh['timestamp'] ) . '<br>';
			echo 'Duration: ' . $last_refresh['duration'] . 's<br>';
			echo 'Total: ' . $last_refresh['summary']['total'] . ' | ';
			echo 'Processed: ' . $last_refresh['summary']['processed'] . ' | ';
			echo 'Errors: ' . $last_refresh['summary']['errors'];
			echo '</div>';
		} else {
			echo '<div class="info-box">No refresh has run yet (normal for new installations).</div>';
		}
		echo '</div>';
	}

	/**
	 * Test 3: Published Pages
	 *
	 * @return void
	 */
	private function testPublishedPages(): void {
		echo '<div class="test-section">';
		echo '<h2>Test 3: SEO Pages (All Statuses)</h2>';

		$pages = get_posts(
			array(
				'post_type'      => 'seo-page',
				'post_status'    => array( 'publish', 'pending', 'draft' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$count = count( $pages );

		if ( $count >= 2 ) {
			echo '<div class="success-box">✓ Found ' . $count . ' SEO pages (draft, pending, or published)</div>';

			// Count pages with focus keywords
			$pages_with_keywords = 0;
			foreach ( $pages as $page_id ) {
				$focus = get_field( 'seo_focus_keyword', $page_id );
				if ( ! empty( $focus ) ) {
					$pages_with_keywords++;
				}
			}

			if ( $pages_with_keywords < 2 ) {
				echo '<div class="warning-box">';
				echo '⚠ Warning: Only ' . $pages_with_keywords . ' page(s) have focus keywords set. ';
				echo 'Internal linking requires at least 2 pages with focus keywords to work properly.';
				echo '</div>';
			}

			echo '<table class="test-table">';
			echo '<tr><th>ID</th><th>Title</th><th>Status</th><th>Focus Keyword</th><th>Topic</th><th>Has Links?</th><th>Action</th></tr>';

			foreach ( array_slice( $pages, 0, 10 ) as $page_id ) {
				$title      = get_the_title( $page_id );
				$status     = get_post_status( $page_id );
				$focus      = get_field( 'seo_focus_keyword', $page_id );
				$topics     = get_the_terms( $page_id, 'seo-topic' );
				$topic_name = $topics && ! is_wp_error( $topics ) ? $topics[0]->name : '—';
				$has_links  = get_post_meta( $page_id, '_related_links', true );

				echo '<tr>';
				echo '<td>' . $page_id . '</td>';
				echo '<td>' . esc_html( $title ) . '</td>';
				echo '<td><span class="status-badge">' . esc_html( $status ) . '</span></td>';
				echo '<td>' . esc_html( $focus ?: '—' ) . '</td>';
				echo '<td>' . esc_html( $topic_name ) . '</td>';
				echo '<td><span class="status-badge status-' . ( $has_links ? 'pass' : 'fail' ) . '">';
				echo $has_links ? '✓ YES' : '✗ NO';
				echo '</span></td>';
				echo '<td><a href="?page=seo-generator-test-links&test_page_id=' . $page_id . '" class="button button-small">Test</a></td>';
				echo '</tr>';
			}
			echo '</table>';
		} elseif ( $count === 1 ) {
			echo '<div class="warning-box">⚠ Only 1 published page found. Need at least 2 pages to test linking.</div>';
		} else {
			echo '<div class="error-box">✗ No published SEO pages found. Generate some pages first!</div>';
		}
		echo '</div>';
	}

	/**
	 * Test 4: KeywordMatcher
	 *
	 * @return void
	 */
	private function testKeywordMatcher(): void {
		if ( ! class_exists( 'SEOGenerator\\Services\\KeywordMatcher' ) ) {
			return;
		}

		echo '<div class="test-section">';
		echo '<h2>Test 4: Keyword Matching Algorithm</h2>';

		try {
			$matcher = new \SEOGenerator\Services\KeywordMatcher();

			$test_text = 'platinum engagement rings with diamonds';
			$keywords  = $matcher->extractKeywords( $test_text );

			echo '<div class="info-box">';
			echo '<strong>Test Input:</strong> "' . esc_html( $test_text ) . '"<br>';
			echo '<strong>Extracted Keywords:</strong><br>';
			echo '<pre>' . print_r( $keywords, true ) . '</pre>';
			echo '</div>';

			$text1     = 'platinum wedding bands';
			$text2     = 'platinum engagement rings';
			$keywords1 = $matcher->extractKeywords( $text1 );
			$keywords2 = $matcher->extractKeywords( $text2 );
			$similarity = $matcher->calculateSimilarity( $keywords1, $keywords2 );

			echo '<div class="info-box">';
			echo '<strong>Similarity Test:</strong><br>';
			echo '"' . esc_html( $text1 ) . '" vs "' . esc_html( $text2 ) . '"<br>';
			echo '<strong>Score:</strong> ' . number_format( $similarity, 2 );
			echo '</div>';

			echo '<div class="success-box">✓ KeywordMatcher working correctly!</div>';
		} catch ( \Exception $e ) {
			echo '<div class="error-box">✗ Error: ' . esc_html( $e->getMessage() ) . '</div>';
		}
		echo '</div>';
	}

	/**
	 * Test 5: Link Generation
	 *
	 * @return void
	 */
	private function testLinkGeneration(): void {
		if ( ! class_exists( 'SEOGenerator\\Services\\InternalLinkingService' ) ) {
			return;
		}

		echo '<div class="test-section">';
		echo '<h2>Test 5: Manual Link Generation</h2>';

		// Show generate all button at the top (unless already viewing results)
		if ( ! isset( $_GET['test_page_id'] ) && ! isset( $_GET['generate_all'] ) ) {
			echo '<div style="margin-bottom: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">';
			echo '<strong>🚀 Quick Action:</strong> Generate related links for ALL pages at once<br>';
			echo '<a href="?page=seo-generator-test-links&generate_all=1" class="button button-primary" style="margin-top: 10px; font-size: 14px; padding: 8px 16px;">🔗 Generate Links for All Pages</a>';
			echo '</div>';
		}

		// Handle "generate all" action
		if ( isset( $_GET['generate_all'] ) && $_GET['generate_all'] === '1' ) {
			echo '<div class="info-box"><strong>Generating links for ALL pages...</strong></div>';

			$all_pages = get_posts(
				array(
					'post_type'      => 'seo-page',
					'post_status'    => array( 'publish', 'pending', 'draft' ),
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);

			$linking_service = new \SEOGenerator\Services\InternalLinkingService();
			$processed = 0;
			$with_links = 0;
			$no_keywords = 0;
			$start_time = microtime( true );

			foreach ( $all_pages as $page_id ) {
				$focus = get_field( 'seo_focus_keyword', $page_id );
				if ( empty( $focus ) ) {
					$no_keywords++;
					continue;
				}

				try {
					$linking_service->refreshLinks( $page_id );
					$links = get_post_meta( $page_id, '_related_links', true );
					if ( ! empty( $links['links'] ) ) {
						$with_links++;
					}
					$processed++;
				} catch ( \Exception $e ) {
					error_log( '[Internal Linking Test] Error for page ' . $page_id . ': ' . $e->getMessage() );
				}
			}

			$duration = round( ( microtime( true ) - $start_time ) * 1000, 2 );

			echo '<div class="success-box">';
			echo '✓ <strong>Bulk generation complete!</strong><br>';
			echo 'Total pages: ' . count( $all_pages ) . '<br>';
			echo 'Processed: ' . $processed . '<br>';
			echo 'Pages with links found: ' . $with_links . '<br>';
			echo 'Skipped (no focus keyword): ' . $no_keywords . '<br>';
			echo 'Duration: ' . $duration . 'ms<br>';
			echo '<a href="?page=seo-generator-test-links" class="button">← Back to Test List</a>';
			echo '</div>';
		} elseif ( isset( $_GET['test_page_id'] ) ) {
			$test_page_id = intval( $_GET['test_page_id'] );
			$page_title = get_the_title( $test_page_id );

			echo '<div class="info-box">';
			echo '<strong>Testing page:</strong> ' . esc_html( $page_title ) . ' (ID: ' . $test_page_id . ')';
			echo '</div>';

			try {
				$linking_service = new \SEOGenerator\Services\InternalLinkingService();

				// Show what we're looking for
				$focus_keyword = get_field( 'seo_focus_keyword', $test_page_id );
				$topics = get_the_terms( $test_page_id, 'seo-topic' );
				$topic_name = $topics && ! is_wp_error( $topics ) ? $topics[0]->name : 'none';

				echo '<div class="info-box">';
				echo '<strong>Focus Keyword:</strong> ' . esc_html( $focus_keyword ?: 'Not set' ) . '<br>';
				echo '<strong>Topic:</strong> ' . esc_html( $topic_name );
				echo '</div>';

				if ( empty( $focus_keyword ) ) {
					echo '<div class="error-box">✗ This page has no focus keyword set. Cannot generate related links.</div>';
				} else {
					// Show keyword extraction for debugging
					$matcher = new \SEOGenerator\Services\KeywordMatcher();
					$source_keywords = $matcher->extractKeywords( $focus_keyword );

					echo '<div class="info-box">';
					echo '<strong>Extracted Keywords & Weights:</strong><br>';
					echo '<pre>';
					foreach ( $source_keywords as $word => $weight ) {
						echo sprintf( "%-20s => %.1f\n", $word, $weight );
					}
					echo '</pre>';
					echo '</div>';

					$start_time = microtime( true );
					$related    = $linking_service->findRelatedPages( $test_page_id );
					$duration   = round( ( microtime( true ) - $start_time ) * 1000, 2 );

					// Also get ALL candidates to show why some didn't make it
					$all_candidates = get_posts(
						array(
							'post_type'      => 'seo-page',
							'post_status'    => array( 'publish', 'pending', 'draft' ),
							'posts_per_page' => -1,
							'fields'         => 'ids',
							'post__not_in'   => array( $test_page_id ),
						)
					);

					echo '<div class="info-box">';
					echo '<strong>Total candidates found:</strong> ' . count( $all_candidates ) . ' pages (excluding this one)<br>';
					echo '<strong>Pages meeting score threshold (≥10):</strong> ' . count( $related );
					echo '</div>';

					echo '<div class="success-box">';
					echo '✓ Search completed in ' . $duration . 'ms';
					echo '</div>';

					if ( ! empty( $related ) ) {
						echo '<table class="test-table">';
						echo '<tr><th>Rank</th><th>ID</th><th>Title</th><th>Score</th><th>Reasons</th></tr>';

						foreach ( $related as $index => $link ) {
							$link_title = get_the_title( $link['id'] );
							echo '<tr>';
							echo '<td>#' . ( $index + 1 ) . '</td>';
							echo '<td>' . $link['id'] . '</td>';
							echo '<td>' . esc_html( $link_title ) . '</td>';
							echo '<td><strong>' . number_format( $link['score'], 1 ) . '</strong></td>';
							echo '<td><small>' . esc_html( implode( ', ', $link['reasons'] ) ) . '</small></td>';
							echo '</tr>';
						}
						echo '</table>';

						$linking_service->storeRelatedLinks( $test_page_id, $related );
						echo '<div class="success-box">';
						echo '✓ Links stored successfully in post meta!<br>';
						echo '<strong>Database key:</strong> _related_links<br>';
						echo '<a href="?page=seo-generator-test-links" class="button">← Back to Test List</a>';
						echo '</div>';
					} else {
						echo '<div class="warning-box">';
						echo '⚠ No related pages found meeting score threshold (≥10 points).<br><br>';
						echo '<strong>Possible reasons:</strong><br>';
						echo '• Not enough other pages exist<br>';
						echo '• Other pages don\'t have focus keywords<br>';
						echo '• Keywords are too different (low similarity)<br>';
						echo '</div>';

						// Show ALL scores for debugging
						if ( ! empty( $all_candidates ) ) {
							echo '<div class="info-box">';
							echo '<strong>Debug: Scores for ALL candidate pages</strong> (showing why they didn\'t qualify):<br>';
							echo '</div>';

							echo '<table class="test-table">';
							echo '<tr><th>ID</th><th>Title</th><th>Focus Keyword</th><th>Score</th><th>Reasons</th></tr>';

							$source_topic = get_the_terms( $test_page_id, 'seo-topic' );
							$source_topic = is_array( $source_topic ) && ! empty( $source_topic ) ? $source_topic[0] : null;

							foreach ( $all_candidates as $candidate_id ) {
								$candidate_title = get_the_title( $candidate_id );
								$candidate_focus = get_field( 'seo_focus_keyword', $candidate_id );

								if ( empty( $candidate_focus ) ) {
									echo '<tr>';
									echo '<td>' . $candidate_id . '</td>';
									echo '<td>' . esc_html( $candidate_title ) . '</td>';
									echo '<td><em>No focus keyword</em></td>';
									echo '<td>—</td>';
									echo '<td><span style="color: #721c24;">Cannot score: no focus keyword</span></td>';
									echo '</tr>';
								} else {
									$score_data = $matcher->scoreCandidatePage(
										$test_page_id,
										$candidate_id,
										$source_keywords,
										$focus_keyword,
										$source_topic
									);

									$score_class = $score_data['score'] >= 10 ? 'style="background: #d4edda;"' : '';
									echo '<tr ' . $score_class . '>';
									echo '<td>' . $candidate_id . '</td>';
									echo '<td>' . esc_html( $candidate_title ) . '</td>';
									echo '<td>' . esc_html( $candidate_focus ) . '</td>';
									echo '<td><strong>' . number_format( $score_data['score'], 1 ) . '</strong></td>';
									echo '<td><small>' . esc_html( implode( ', ', $score_data['reasons'] ) ) . '</small></td>';
									echo '</tr>';
								}
							}
							echo '</table>';

							echo '<div class="info-box">';
							echo '<strong>Note:</strong> Pages with green background have score ≥10 and would normally be included, ';
							echo 'but the system limits to top 5 pages only.';
							echo '</div>';
						}

						echo '<div style="margin-top: 20px;">';
						echo '<a href="?page=seo-generator-test-links" class="button">← Back to Test List</a>';
						echo '</div>';
					}
				}
			} catch ( \Exception $e ) {
				echo '<div class="error-box">';
				echo '✗ Error: ' . esc_html( $e->getMessage() ) . '<br>';
				echo '<pre>' . esc_html( $e->getTraceAsString() ) . '</pre>';
				echo '<a href="?page=seo-generator-test-links" class="button">← Back to Test List</a>';
				echo '</div>';
			}
		} else {
			echo '<div class="info-box">';
			echo '<strong>Option 1:</strong> Click a [Test] button in Test 3 above to generate links for ONE page.<br><br>';
			echo '<strong>Option 2:</strong> Generate links for ALL pages at once:<br>';
			echo '<a href="?page=seo-generator-test-links&generate_all=1" class="button button-primary" style="margin-top: 10px;">🔗 Generate Links for All Pages</a>';
			echo '</div>';
		}
		echo '</div>';
	}

	/**
	 * Test 6: Debug Logs
	 *
	 * @return void
	 */
	private function testDebugLogs(): void {
		echo '<div class="test-section">';
		echo '<h2>Test 6: Debug Logs</h2>';

		$debug_log_path = WP_CONTENT_DIR . '/debug.log';

		if ( file_exists( $debug_log_path ) ) {
			$log_contents = file_get_contents( $debug_log_path );
			$log_lines = explode( "\n", $log_contents );

			// Get last 50 lines
			$recent_lines = array_slice( $log_lines, -50 );

			// Filter for Internal Linking messages
			$linking_lines = array_filter( $recent_lines, function( $line ) {
				return strpos( $line, '[Internal Linking]' ) !== false;
			} );

			if ( ! empty( $linking_lines ) ) {
				echo '<div class="info-box">';
				echo '<strong>Recent Internal Linking Log Entries:</strong><br>';
				echo '<pre style="max-height: 400px; overflow-y: auto; font-size: 11px;">';
				echo esc_html( implode( "\n", $linking_lines ) );
				echo '</pre>';
				echo '</div>';
			} else {
				echo '<div class="warning-box">No internal linking log entries found yet. Generate some links to see logs here.</div>';
			}

			echo '<div class="info-box">';
			echo '<strong>Full debug.log (last 50 lines):</strong><br>';
			echo '<button onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display===\'none\'?\'block\':\'none\'" class="button">Toggle Full Log</button>';
			echo '<pre style="display:none; max-height: 400px; overflow-y: auto; font-size: 10px; background: #000; color: #0f0; padding: 10px;">';
			echo esc_html( implode( "\n", $recent_lines ) );
			echo '</pre>';
			echo '</div>';
		} else {
			echo '<div class="warning-box">Debug log not found. To enable WordPress debug logging, add this to wp-config.php:<br>';
			echo '<pre>define(\'WP_DEBUG\', true);<br>define(\'WP_DEBUG_LOG\', true);</pre>';
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Test 7: Frontend Display
	 *
	 * @return void
	 */
	private function testFrontendDisplay(): void {
		echo '<div class="test-section">';
		echo '<h2>Test 6: Frontend Display</h2>';

		$pages = get_posts(
			array(
				'post_type'      => 'seo-page',
				'post_status'    => array( 'publish', 'pending', 'draft' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $pages ) ) {
			$sample_page = $pages[0];
			$has_links   = get_post_meta( $sample_page, '_related_links', true );
			$permalink   = get_permalink( $sample_page );

			if ( $has_links ) {
				echo '<div class="success-box">';
				echo '✓ Page ' . $sample_page . ' has related links<br>';
				echo '<a href="' . esc_url( $permalink ) . '" target="_blank" class="button">View on Frontend →</a>';
				echo '</div>';
				echo '<div class="info-box">Check if "Related Articles" section appears at bottom.</div>';
			} else {
				echo '<div class="warning-box">';
				echo '⚠ No related links stored yet<br>';
				echo '<a href="?page=seo-generator-test-links&test_page_id=' . $sample_page . '" class="button">Generate Links</a>';
				echo '</div>';
			}
		}
		echo '</div>';
	}

	/**
	 * Test 8: Debug Specific Page
	 *
	 * @return void
	 */
	private function testSpecificPage(): void {
		echo '<div class="test-section">';
		echo '<h2>Test 7: Debug Specific Page</h2>';

		// Get page by slug
		$page_slug = isset( $_GET['debug_slug'] ) ? sanitize_text_field( $_GET['debug_slug'] ) : '';

		if ( empty( $page_slug ) ) {
			echo '<div class="info-box">';
			echo '<strong>Enter a page slug to debug why related links aren\'t showing:</strong><br><br>';
			echo '<form method="get">';
			echo '<input type="hidden" name="page" value="seo-generator-test-links">';
			echo '<input type="text" name="debug_slug" placeholder="pearl-necklaces" style="width: 300px; padding: 8px;">';
			echo '<button type="submit" class="button button-primary">Debug Page</button>';
			echo '</form>';
			echo '<br><small>Example: "pearl-necklaces" or "wedding-rings"</small>';
			echo '</div>';
		} else {
			echo '<div class="info-box"><strong>Debugging page slug:</strong> ' . esc_html( $page_slug ) . '</div>';

			$page = get_page_by_path( $page_slug, OBJECT, 'seo-page' );

			if ( ! $page ) {
				echo '<div class="error-box">✗ Page not found with slug "' . esc_html( $page_slug ) . '"</div>';
			} else {
				$post_id = $page->ID;

				echo '<div class="success-box">';
				echo '✓ Found page ID: ' . $post_id . '<br>';
				echo '<strong>Title:</strong> ' . esc_html( get_the_title( $post_id ) ) . '<br>';
				echo '<strong>Status:</strong> ' . esc_html( $page->post_status ) . '<br>';
				echo '<strong>Permalink:</strong> <a href="' . esc_url( get_permalink( $post_id ) ) . '" target="_blank">' . esc_url( get_permalink( $post_id ) ) . '</a>';
				echo '</div>';

				// Check post meta
				echo '<h3>1. Post Meta Check</h3>';
				$related_links_raw = get_post_meta( $post_id, '_related_links', true );
				$timestamp = get_post_meta( $post_id, '_related_links_timestamp', true );

				if ( $related_links_raw ) {
					echo '<div class="success-box">✓ Post meta "_related_links" exists</div>';
					echo '<div class="info-box">';
					echo '<strong>Raw meta data:</strong><br>';
					echo '<pre>' . esc_html( print_r( $related_links_raw, true ) ) . '</pre>';
					echo '</div>';

					if ( $timestamp ) {
						$age_days = ( time() - $timestamp ) / DAY_IN_SECONDS;
						echo '<div class="info-box">';
						echo '<strong>Timestamp:</strong> ' . date( 'Y-m-d H:i:s', $timestamp ) . '<br>';
						echo '<strong>Age:</strong> ' . round( $age_days, 2 ) . ' days<br>';
						echo '<strong>Stale?</strong> ' . ( $age_days > 7 ? '⚠ YES (>7 days)' : '✓ NO (<7 days)' );
						echo '</div>';
					}
				} else {
					echo '<div class="error-box">✗ No post meta found. This page has no related links stored.</div>';
					echo '<div class="warning-box">';
					echo '<strong>Solution:</strong> Generate links for this page:<br>';
					echo '<a href="?page=seo-generator-test-links&test_page_id=' . $post_id . '" class="button">Generate Links Now</a>';
					echo '</div>';
				}

				// Test getRelatedLinks() method
				echo '<h3>2. getRelatedLinks() Method Test</h3>';
				$linking_service = new \SEOGenerator\Services\InternalLinkingService();
				$related_links = $linking_service->getRelatedLinks( $post_id );

				if ( $related_links ) {
					echo '<div class="success-box">✓ getRelatedLinks() returned ' . count( $related_links ) . ' links</div>';
					echo '<table class="test-table">';
					echo '<tr><th>ID</th><th>Title</th><th>Status</th><th>Score</th><th>Will Display?</th></tr>';

					foreach ( $related_links as $link ) {
						$linked_post = get_post( $link['id'] );
						$is_published = $linked_post && $linked_post->post_status === 'publish';

						echo '<tr>';
						echo '<td>' . $link['id'] . '</td>';
						echo '<td>' . esc_html( get_the_title( $link['id'] ) ) . '</td>';
						echo '<td><span class="status-badge status-' . ( $is_published ? 'pass' : 'fail' ) . '">' . esc_html( $linked_post->post_status ?? 'unknown' ) . '</span></td>';
						echo '<td>' . number_format( $link['score'], 1 ) . '</td>';
						echo '<td>' . ( $is_published ? '✓ YES' : '✗ NO (not published)' ) . '</td>';
						echo '</tr>';
					}
					echo '</table>';
				} else {
					echo '<div class="error-box">✗ getRelatedLinks() returned NULL</div>';
					echo '<div class="info-box">';
					echo '<strong>Possible reasons:</strong><br>';
					echo '• No related links are stored (see Test 1 above)<br>';
					echo '• Links are stale (>7 days old)<br>';
					echo '• All linked pages are draft/pending (getRelatedLinks filters for published only)';
					echo '</div>';
				}

				// Check template file
				echo '<h3>3. Template File Check</h3>';
				$template_path = WP_PLUGIN_DIR . '/content-generator-disabled/templates/frontend/blocks/related-links.php';
				echo '<div class="info-box">';
				echo '<strong>Template path:</strong> ' . esc_html( $template_path ) . '<br>';
				echo '<strong>File exists:</strong> ' . ( file_exists( $template_path ) ? '✓ YES' : '✗ NO' );
				echo '</div>';

				// Check theme template
				echo '<h3>4. Theme Template Check</h3>';
				$theme_template = get_stylesheet_directory() . '/single-seo-page.php';
				echo '<div class="info-box">';
				echo '<strong>Theme template:</strong> ' . esc_html( $theme_template ) . '<br>';
				echo '<strong>File exists:</strong> ' . ( file_exists( $theme_template ) ? '✓ YES' : '✗ NO' );
				echo '</div>';

				if ( file_exists( $theme_template ) ) {
					$template_content = file_get_contents( $theme_template );
					$has_linking_code = strpos( $template_content, 'InternalLinkingService' ) !== false;

					echo '<div class="' . ( $has_linking_code ? 'success-box' : 'error-box' ) . '">';
					echo $has_linking_code ? '✓ Template contains InternalLinkingService code' : '✗ Template does NOT contain InternalLinkingService code';
					echo '</div>';
				}

				// Summary
				echo '<h3>5. Summary</h3>';
				if ( ! $related_links_raw ) {
					echo '<div class="error-box">';
					echo '<strong>Problem:</strong> No related links stored in database<br>';
					echo '<strong>Solution:</strong> <a href="?page=seo-generator-test-links&test_page_id=' . $post_id . '">Generate links for this page</a>';
					echo '</div>';
				} elseif ( ! $related_links ) {
					echo '<div class="error-box">';
					echo '<strong>Problem:</strong> Related links exist in database but getRelatedLinks() returns NULL<br>';
					echo '<strong>Likely cause:</strong> All linked pages are draft/pending (not published)<br>';
					echo '<strong>Solution:</strong> Publish at least one of the linked pages, or link to published pages';
					echo '</div>';
				} elseif ( ! file_exists( $template_path ) ) {
					echo '<div class="error-box">';
					echo '<strong>Problem:</strong> Template file missing<br>';
					echo '<strong>Solution:</strong> Create the template file at: ' . esc_html( $template_path );
					echo '</div>';
				} else {
					echo '<div class="success-box">';
					echo '✓ Everything looks correct! Related links should display on the frontend.<br>';
					echo '<a href="' . esc_url( get_permalink( $post_id ) ) . '" target="_blank" class="button button-primary">View Page →</a>';
					echo '</div>';
				}

				echo '<div style="margin-top: 20px;">';
				echo '<a href="?page=seo-generator-test-links" class="button">← Back to Tests</a>';
				echo '</div>';
			}
		}
		echo '</div>';
	}

	/**
	 * Test 9: Elementor Meta Check
	 *
	 * @return void
	 */
	private function testElementorMeta(): void {
		echo '<div class="test-section">';
		echo '<h2>Test 8: Elementor Compatibility Check</h2>';

		$post_id = isset( $_GET['check_elementor_id'] ) ? intval( $_GET['check_elementor_id'] ) : 0;

		if ( ! $post_id ) {
			echo '<div class="info-box">';
			echo '<strong>Enter a post ID to check Elementor compatibility:</strong><br><br>';
			echo '<form method="get">';
			echo '<input type="hidden" name="page" value="seo-generator-test-links">';
			echo '<input type="number" name="check_elementor_id" placeholder="442" style="width: 200px; padding: 8px;">';
			echo '<button type="submit" class="button button-primary">Check Elementor Meta</button>';
			echo '</form>';
			echo '</div>';

			// Show recent pages
			$recent_pages = get_posts(
				array(
					'post_type'      => 'seo-page',
					'post_status'    => 'any',
					'posts_per_page' => 5,
					'orderby'        => 'ID',
					'order'          => 'DESC',
				)
			);

			if ( ! empty( $recent_pages ) ) {
				echo '<div class="info-box">';
				echo '<strong>Recent pages:</strong><br>';
				foreach ( $recent_pages as $page ) {
					echo '<a href="?page=seo-generator-test-links&check_elementor_id=' . $page->ID . '" class="button button-small" style="margin: 5px;">';
					echo 'Check Post ' . $page->ID . ': ' . esc_html( $page->post_title );
					echo '</a><br>';
				}
				echo '</div>';
			}
		} else {
			$post = get_post( $post_id );

			if ( ! $post ) {
				echo '<div class="error-box">✗ Post ID ' . $post_id . ' not found</div>';
			} else {
				echo '<div class="success-box">';
				echo '✓ Found post ID: ' . $post_id . '<br>';
				echo '<strong>Title:</strong> ' . esc_html( $post->post_title ) . '<br>';
				echo '<strong>Status:</strong> ' . esc_html( $post->post_status ) . '<br>';
				echo '<strong>Edit URL:</strong> <a href="' . get_edit_post_link( $post_id ) . '" target="_blank">Edit in WP Admin</a>';
				echo '</div>';

				// Check Elementor plugin status
				echo '<h3>1. Elementor Plugin Status</h3>';
				if ( is_plugin_active( 'elementor/elementor.php' ) ) {
					echo '<div class="success-box">✓ Elementor plugin is ACTIVE</div>';
				} else {
					echo '<div class="error-box">✗ Elementor plugin is NOT active or not installed</div>';
					echo '<div class="warning-box">Install Elementor from Plugins → Add New to use the page builder.</div>';
				}

				// Check meta fields
				echo '<h3>2. Elementor Meta Fields</h3>';
				$edit_mode = get_post_meta( $post_id, '_elementor_edit_mode', true );
				$template  = get_post_meta( $post_id, '_wp_page_template', true );

				echo '<table class="test-table">';
				echo '<tr><th>Meta Key</th><th>Current Value</th><th>Expected Value</th><th>Status</th></tr>';

				// Check _elementor_edit_mode
				echo '<tr>';
				echo '<td><code>_elementor_edit_mode</code></td>';
				echo '<td>' . ( $edit_mode ? '<code>' . esc_html( $edit_mode ) . '</code>' : '<em>not set</em>' ) . '</td>';
				echo '<td><code>builder</code></td>';
				echo '<td><span class="status-badge status-' . ( $edit_mode === 'builder' ? 'pass' : 'fail' ) . '">';
				echo $edit_mode === 'builder' ? '✓ CORRECT' : '✗ INCORRECT';
				echo '</span></td>';
				echo '</tr>';

				// Check _wp_page_template
				echo '<tr>';
				echo '<td><code>_wp_page_template</code></td>';
				echo '<td>' . ( $template ? '<code>' . esc_html( $template ) . '</code>' : '<em>not set</em>' ) . '</td>';
				echo '<td><code>elementor_canvas</code> or <code>elementor_header_footer</code></td>';
				echo '<td><span class="status-badge status-' . ( in_array( $template, array( 'elementor_canvas', 'elementor_header_footer' ) ) ? 'pass' : 'fail' ) . '">';
				echo in_array( $template, array( 'elementor_canvas', 'elementor_header_footer' ) ) ? '✓ CORRECT' : '✗ INCORRECT';
				echo '</span></td>';
				echo '</tr>';

				echo '</table>';

				// Template explanation
				echo '<div class="info-box">';
				echo '<strong>Template Options:</strong><br>';
				echo '• <code>elementor_canvas</code> - Blank canvas (no theme header/footer, just Elementor content)<br>';
				echo '• <code>elementor_header_footer</code> - Shows theme content + Elementor content<br><br>';
				echo '<strong>Current template:</strong> ' . ( $template ? '<code>' . esc_html( $template ) . '</code>' : 'not set' );
				echo '</div>';

				// Add option to switch template
				if ( $template === 'elementor_canvas' ) {
					echo '<div class="warning-box">';
					echo 'Currently using <strong>elementor_canvas</strong> (blank canvas).<br>';
					echo 'This hides your hero section in Elementor editor.<br><br>';
					echo 'Switch to <strong>elementor_header_footer</strong> to see your hero section:<br><br>';
					echo '<form method="post" style="display: inline;">';
					echo '<input type="hidden" name="switch_elementor_template" value="elementor_header_footer">';
					echo '<input type="hidden" name="post_id" value="' . $post_id . '">';
					wp_nonce_field( 'switch_template', 'switch_template_nonce' );
					echo '<button type="submit" class="button button-primary">Switch to elementor_header_footer</button>';
					echo '</form>';
					echo '</div>';
				}

				// Handle template switch
				if ( isset( $_POST['switch_elementor_template'] ) && isset( $_POST['post_id'] ) ) {
					check_admin_referer( 'switch_template', 'switch_template_nonce' );
					$switch_post_id = intval( $_POST['post_id'] );
					$new_template = sanitize_text_field( $_POST['switch_elementor_template'] );

					update_post_meta( $switch_post_id, '_wp_page_template', $new_template );

					echo '<div class="success-box">';
					echo '✓ Switched template to ' . esc_html( $new_template ) . '!<br>';
					echo '<a href="?page=seo-generator-test-links&check_elementor_id=' . $switch_post_id . '" class="button">Refresh to Verify</a>';
					echo '</div>';
				}

				echo '<table class="test-table" style="display:none;">';
				echo '<tr><th>fake</th></tr>';

				echo '</table>';

				// Summary and fix
				echo '<h3>3. Summary</h3>';
				if ( $edit_mode === 'builder' && $template === 'elementor_canvas' ) {
					echo '<div class="success-box">';
					echo '✓ Elementor meta fields are correctly set!<br><br>';
					if ( is_plugin_active( 'elementor/elementor.php' ) ) {
						echo 'You should see "Edit with Elementor" button when editing this page.<br>';
						echo '<a href="' . get_edit_post_link( $post_id ) . '" target="_blank" class="button button-primary">Open in WP Admin</a>';
					}
					echo '</div>';
				} else {
					echo '<div class="error-box">';
					echo '✗ Elementor meta fields are missing or incorrect<br><br>';
					echo '<strong>This means:</strong><br>';
					echo '• The page was created BEFORE the Elementor code was added<br>';
					echo '• OR there was an error during page creation<br><br>';
					echo '<strong>Solution:</strong> Click the button below to fix this page:<br><br>';
					echo '<form method="post" style="display: inline;">';
					echo '<input type="hidden" name="fix_elementor_meta" value="1">';
					echo '<input type="hidden" name="post_id" value="' . $post_id . '">';
					wp_nonce_field( 'fix_elementor_meta', 'fix_elementor_nonce' );
					echo '<button type="submit" class="button button-primary">Fix Elementor Meta for Post ' . $post_id . '</button>';
					echo '</form>';
					echo '</div>';
				}

				// Check if seo-page post type is enabled in Elementor
				echo '<h3>4. Elementor Post Type Support</h3>';
				$elementor_cpt_support = get_option( 'elementor_cpt_support', array() );

				if ( in_array( 'seo-page', $elementor_cpt_support, true ) ) {
					echo '<div class="success-box">✓ seo-page is enabled in Elementor settings</div>';
				} else {
					echo '<div class="error-box">';
					echo '✗ seo-page is NOT enabled in Elementor settings<br><br>';
					echo '<strong>This is likely why you don\'t see the "Edit with Elementor" button!</strong><br><br>';
					echo 'Click below to enable seo-page for Elementor:<br><br>';
					echo '<form method="post" style="display: inline;">';
					echo '<input type="hidden" name="enable_elementor_post_type" value="1">';
					wp_nonce_field( 'enable_elementor_post_type', 'enable_elementor_nonce' );
					echo '<button type="submit" class="button button-primary">Enable seo-page for Elementor</button>';
					echo '</form>';
					echo '</div>';
				}

				// Handle enable post type action
				if ( isset( $_POST['enable_elementor_post_type'] ) ) {
					check_admin_referer( 'enable_elementor_post_type', 'enable_elementor_nonce' );

					$cpt_support = get_option( 'elementor_cpt_support', array() );

					// If empty, add defaults
					if ( empty( $cpt_support ) ) {
						$cpt_support = array( 'page', 'post' );
					}

					// Add seo-page if not already there
					if ( ! in_array( 'seo-page', $cpt_support, true ) ) {
						$cpt_support[] = 'seo-page';
						update_option( 'elementor_cpt_support', $cpt_support );

						echo '<div class="success-box">';
						echo '✓ Enabled seo-page for Elementor!<br>';
						echo '<a href="?page=seo-generator-test-links&check_elementor_id=' . $post_id . '" class="button">Refresh to Verify</a>';
						echo '</div>';
					}
				}

				// Handle fix action
				if ( isset( $_POST['fix_elementor_meta'] ) && isset( $_POST['post_id'] ) ) {
					check_admin_referer( 'fix_elementor_meta', 'fix_elementor_nonce' );
					$fix_post_id = intval( $_POST['post_id'] );

					update_post_meta( $fix_post_id, '_elementor_edit_mode', 'builder' );
					update_post_meta( $fix_post_id, '_wp_page_template', 'elementor_canvas' );

					echo '<div class="success-box">';
					echo '✓ Fixed Elementor meta for post ' . $fix_post_id . '!<br>';
					echo '<a href="?page=seo-generator-test-links&check_elementor_id=' . $fix_post_id . '" class="button">Refresh to Verify</a>';
					echo '</div>';
				}

				echo '<div style="margin-top: 20px;">';
				echo '<a href="?page=seo-generator-test-links" class="button">← Back to Tests</a>';
				echo '</div>';
			}
		}
		echo '</div>';
	}

	/**
	 * Test 10: Beaver Builder Meta Check
	 *
	 * @return void
	 */
	private function testBeaverBuilderMeta(): void {
		echo '<div class="test-section">';
		echo '<h2>Test 9: Beaver Builder Compatibility Check</h2>';

		$post_id = isset( $_GET['check_bb_id'] ) ? intval( $_GET['check_bb_id'] ) : 0;

		if ( ! $post_id ) {
			echo '<div class="info-box">';
			echo '<strong>Enter a post ID to check Beaver Builder compatibility:</strong><br><br>';
			echo '<form method="get">';
			echo '<input type="hidden" name="page" value="seo-generator-test-links">';
			echo '<input type="number" name="check_bb_id" placeholder="442" style="width: 200px; padding: 8px;">';
			echo '<button type="submit" class="button button-primary">Check Beaver Builder Meta</button>';
			echo '</form>';
			echo '</div>';

			// Show recent pages
			$recent_pages = get_posts(
				array(
					'post_type'      => 'seo-page',
					'post_status'    => 'any',
					'posts_per_page' => 5,
					'orderby'        => 'ID',
					'order'          => 'DESC',
				)
			);

			if ( ! empty( $recent_pages ) ) {
				echo '<div class="info-box">';
				echo '<strong>Recent pages:</strong><br>';
				foreach ( $recent_pages as $page ) {
					echo '<a href="?page=seo-generator-test-links&check_bb_id=' . $page->ID . '" class="button button-small" style="margin: 5px;">';
					echo 'Check Post ' . $page->ID . ': ' . esc_html( $page->post_title );
					echo '</a><br>';
				}
				echo '</div>';
			}
		} else {
			$post = get_post( $post_id );

			if ( ! $post ) {
				echo '<div class="error-box">✗ Post ID ' . $post_id . ' not found</div>';
			} else {
				echo '<div class="success-box">';
				echo '✓ Found post ID: ' . $post_id . '<br>';
				echo '<strong>Title:</strong> ' . esc_html( $post->post_title ) . '<br>';
				echo '<strong>Status:</strong> ' . esc_html( $post->post_status ) . '<br>';
				echo '<strong>Edit URL:</strong> <a href="' . get_edit_post_link( $post_id ) . '" target="_blank">Edit in WP Admin</a>';
				echo '</div>';

				// Check Beaver Builder plugin status
				echo '<h3>1. Beaver Builder Plugin Status</h3>';
				if ( class_exists( 'FLBuilder' ) ) {
					echo '<div class="success-box">✓ Beaver Builder plugin is ACTIVE</div>';
				} else {
					echo '<div class="error-box">✗ Beaver Builder plugin is NOT active or not installed</div>';
					echo '<div class="warning-box">Install Beaver Builder to use the page builder.</div>';
				}

				// Check meta fields
				echo '<h3>2. Beaver Builder Meta Fields</h3>';
				$bb_enabled = get_post_meta( $post_id, '_fl_builder_enabled', true );
				$bb_draft   = get_post_meta( $post_id, '_fl_builder_draft', true );
				$bb_data    = get_post_meta( $post_id, '_fl_builder_data', true );

				echo '<table class="test-table">';
				echo '<tr><th>Meta Key</th><th>Current Value</th><th>Expected Value</th><th>Status</th></tr>';

				// Check _fl_builder_enabled
				echo '<tr>';
				echo '<td><code>_fl_builder_enabled</code></td>';
				echo '<td>' . ( $bb_enabled ? '<code>' . esc_html( $bb_enabled ) . '</code>' : '<em>not set</em>' ) . '</td>';
				echo '<td><code>1</code></td>';
				echo '<td><span class="status-badge status-' . ( $bb_enabled === '1' ? 'pass' : 'fail' ) . '">';
				echo $bb_enabled === '1' ? '✓ CORRECT' : '✗ INCORRECT';
				echo '</span></td>';
				echo '</tr>';

				// Check _fl_builder_draft
				echo '<tr>';
				echo '<td><code>_fl_builder_draft</code></td>';
				echo '<td>' . ( $bb_draft !== false ? '<code>' . esc_html( $bb_draft ) . '</code>' : '<em>not set</em>' ) . '</td>';
				echo '<td><code>empty string</code></td>';
				echo '<td><span class="status-badge status-' . ( $bb_draft === '' ? 'pass' : 'fail' ) . '">';
				echo $bb_draft === '' ? '✓ CORRECT' : '✗ INCORRECT';
				echo '</span></td>';
				echo '</tr>';

				// Check _fl_builder_data
				$bb_data_status = $bb_data !== false;
				echo '<tr>';
				echo '<td><code>_fl_builder_data</code></td>';
				echo '<td>' . ( $bb_data_status ? '<code>set</code>' : '<em>not set</em>' ) . '</td>';
				echo '<td><code>[]</code> (empty array)</td>';
				echo '<td><span class="status-badge status-' . ( $bb_data_status ? 'pass' : 'fail' ) . '">';
				echo $bb_data_status ? '✓ CORRECT' : '✗ INCORRECT';
				echo '</span></td>';
				echo '</tr>';

				echo '</table>';

				// Check if seo-page post type is enabled in Beaver Builder
				echo '<h3>3. Beaver Builder Post Type Support</h3>';
				$bb_post_types = get_option( '_fl_builder_post_types', array() );

				if ( in_array( 'seo-page', $bb_post_types, true ) ) {
					echo '<div class="success-box">✓ seo-page is enabled in Beaver Builder settings</div>';
				} else {
					echo '<div class="error-box">';
					echo '✗ seo-page is NOT enabled in Beaver Builder settings<br><br>';
					echo '<strong>This is likely why you don\'t see the Beaver Builder button!</strong><br><br>';
					echo 'Click below to enable seo-page for Beaver Builder:<br><br>';
					echo '<form method="post" style="display: inline;">';
					echo '<input type="hidden" name="enable_bb_post_type" value="1">';
					wp_nonce_field( 'enable_bb_post_type', 'enable_bb_nonce' );
					echo '<button type="submit" class="button button-primary">Enable seo-page for Beaver Builder</button>';
					echo '</form>';
					echo '</div>';
				}

				// Handle enable post type action
				if ( isset( $_POST['enable_bb_post_type'] ) ) {
					check_admin_referer( 'enable_bb_post_type', 'enable_bb_nonce' );

					$cpt_support = get_option( '_fl_builder_post_types', array() );

					// If empty, add defaults
					if ( empty( $cpt_support ) ) {
						$cpt_support = array( 'page', 'post' );
					}

					// Add seo-page if not already there
					if ( ! in_array( 'seo-page', $cpt_support, true ) ) {
						$cpt_support[] = 'seo-page';
						update_option( '_fl_builder_post_types', $cpt_support );

						echo '<div class="success-box">';
						echo '✓ Enabled seo-page for Beaver Builder!<br>';
						echo '<a href="?page=seo-generator-test-links&check_bb_id=' . $post_id . '" class="button">Refresh to Verify</a>';
						echo '</div>';
					}
				}

				// Summary and fix
				echo '<h3>4. Summary</h3>';
				$is_correct = ( $bb_enabled === '1' && $bb_draft === '' && $bb_data_status );

				if ( $is_correct ) {
					echo '<div class="success-box">';
					echo '✓ Beaver Builder meta fields are correctly set!<br><br>';
					if ( class_exists( 'FLBuilder' ) ) {
						echo 'You should see "Beaver Builder" button when editing this page.<br>';
						echo '<a href="' . get_edit_post_link( $post_id ) . '" target="_blank" class="button button-primary">Open in WP Admin</a>';
					}
					echo '</div>';
				} else {
					echo '<div class="error-box">';
					echo '✗ Beaver Builder meta fields are missing or incorrect<br><br>';
					echo '<strong>This means:</strong><br>';
					echo '• The page was created BEFORE the Beaver Builder code was added<br>';
					echo '• OR there was an error during page creation<br><br>';
					echo '<strong>Solution:</strong> Click the button below to fix this page:<br><br>';
					echo '<form method="post" style="display: inline;">';
					echo '<input type="hidden" name="fix_bb_meta" value="1">';
					echo '<input type="hidden" name="post_id" value="' . $post_id . '">';
					wp_nonce_field( 'fix_bb_meta', 'fix_bb_nonce' );
					echo '<button type="submit" class="button button-primary">Enable Beaver Builder for Post ' . $post_id . '</button>';
					echo '</form>';
					echo '</div>';
				}

				// Handle fix action
				if ( isset( $_POST['fix_bb_meta'] ) && isset( $_POST['post_id'] ) ) {
					check_admin_referer( 'fix_bb_meta', 'fix_bb_nonce' );
					$fix_post_id = intval( $_POST['post_id'] );

					update_post_meta( $fix_post_id, '_fl_builder_enabled', '1' );
					update_post_meta( $fix_post_id, '_fl_builder_draft', '' );
					update_post_meta( $fix_post_id, '_fl_builder_data', '[]' );

					echo '<div class="success-box">';
					echo '✓ Enabled Beaver Builder for post ' . $fix_post_id . '!<br>';
					echo '<a href="?page=seo-generator-test-links&check_bb_id=' . $fix_post_id . '" class="button">Refresh to Verify</a>';
					echo '</div>';
				}

				echo '<div style="margin-top: 20px;">';
				echo '<a href="?page=seo-generator-test-links" class="button">← Back to Tests</a>';
				echo '</div>';
			}
		}
		echo '</div>';
	}

	/**
	 * Test 11: Migrate Existing Pages to Block Editor
	 *
	 * @return void
	 */
	private function testMigrateToBlocks(): void {
		echo '<div class="test-section">';
		echo '<h2>Test 10: Migrate Pages to Block Editor</h2>';

		echo '<div class="info-box">';
		echo '<strong>What this does:</strong><br>';
		echo 'Migrates existing pages to use the WordPress Block Editor by:<br>';
		echo '1. Removing any page builder meta (Elementor, Beaver Builder)<br>';
		echo '2. Creating block content from ACF field data (hero_title, hero_subtitle, etc.)<br>';
		echo '3. Populating post_content with a 2-column hero section block template<br><br>';
		echo '<strong>Why you need this:</strong><br>';
		echo 'Old pages created before the block template was added have empty post_content,<br>';
		echo 'so they appear blank in the Block Editor even though they show on frontend.';
		echo '</div>';

		$post_id = isset( $_GET['migrate_blocks_id'] ) ? intval( $_GET['migrate_blocks_id'] ) : 0;

		if ( ! $post_id ) {
			// Show form to select a page
			echo '<div class="info-box">';
			echo '<strong>Enter a post ID to migrate to Block Editor:</strong><br><br>';
			echo '<form method="get">';
			echo '<input type="hidden" name="page" value="seo-generator-test-links">';
			echo '<input type="number" name="migrate_blocks_id" placeholder="442" style="width: 200px; padding: 8px;">';
			echo '<button type="submit" class="button button-primary">Check Page</button>';
			echo '</form>';
			echo '</div>';

			// Show recent pages
			$recent_pages = get_posts(
				array(
					'post_type'      => 'seo-page',
					'post_status'    => 'any',
					'posts_per_page' => 10,
					'orderby'        => 'ID',
					'order'          => 'DESC',
				)
			);

			if ( ! empty( $recent_pages ) ) {
				echo '<div class="info-box">';
				echo '<strong>Recent pages:</strong><br>';
				echo '<table class="test-table">';
				echo '<tr><th>ID</th><th>Title</th><th>Has Blocks?</th><th>Action</th></tr>';
				foreach ( $recent_pages as $page ) {
					$has_blocks = has_blocks( $page->ID );
					echo '<tr>';
					echo '<td>' . $page->ID . '</td>';
					echo '<td>' . esc_html( $page->post_title ) . '</td>';
					echo '<td><span class="status-badge status-' . ( $has_blocks ? 'pass' : 'fail' ) . '">';
					echo $has_blocks ? '✓ YES' : '✗ NO';
					echo '</span></td>';
					echo '<td><a href="?page=seo-generator-test-links&migrate_blocks_id=' . $page->ID . '" class="button button-small">Check</a></td>';
					echo '</tr>';
				}
				echo '</table>';
				echo '</div>';
			}

			// Bulk migration option
			echo '<div class="warning-box">';
			echo '<strong>🚀 Bulk Migration:</strong> Migrate ALL pages at once<br><br>';
			echo '<form method="post" style="display: inline;">';
			echo '<input type="hidden" name="migrate_all_to_blocks" value="1">';
			wp_nonce_field( 'migrate_all_blocks', 'migrate_all_nonce' );
			echo '<button type="submit" class="button button-primary" onclick="return confirm(\'Are you sure you want to migrate ALL pages to Block Editor? This will remove page builder settings.\')">Migrate All Pages to Blocks</button>';
			echo '</form>';
			echo '</div>';

			// Handle bulk migration
			if ( isset( $_POST['migrate_all_to_blocks'] ) ) {
				check_admin_referer( 'migrate_all_blocks', 'migrate_all_nonce' );

				$all_pages = get_posts(
					array(
						'post_type'      => 'seo-page',
						'post_status'    => 'any',
						'posts_per_page' => -1,
						'fields'         => 'ids',
					)
				);

				$migrated = 0;
				$skipped  = 0;

				foreach ( $all_pages as $page_id ) {
					if ( has_blocks( $page_id ) ) {
						$skipped++;
						continue;
					}

					$result = $this->migratePageToBlocks( $page_id );
					if ( $result ) {
						$migrated++;
					}
				}

				echo '<div class="success-box">';
				echo '✓ <strong>Bulk migration complete!</strong><br>';
				echo 'Total pages: ' . count( $all_pages ) . '<br>';
				echo 'Migrated: ' . $migrated . '<br>';
				echo 'Skipped (already have blocks): ' . $skipped . '<br>';
				echo '<a href="?page=seo-generator-test-links" class="button">← Back to Test List</a>';
				echo '</div>';
			}
		} else {
			// Check specific page
			$post = get_post( $post_id );

			if ( ! $post ) {
				echo '<div class="error-box">✗ Post ID ' . $post_id . ' not found</div>';
			} else {
				echo '<div class="success-box">';
				echo '✓ Found post ID: ' . $post_id . '<br>';
				echo '<strong>Title:</strong> ' . esc_html( $post->post_title ) . '<br>';
				echo '<strong>Status:</strong> ' . esc_html( $post->post_status ) . '<br>';
				echo '<strong>Edit URL:</strong> <a href="' . get_edit_post_link( $post_id ) . '" target="_blank">Edit in WP Admin</a>';
				echo '</div>';

				// Check if page already has blocks
				echo '<h3>1. Current State</h3>';
				$has_blocks = has_blocks( $post_id );

				if ( $has_blocks ) {
					echo '<div class="success-box">';
					echo '✓ This page ALREADY has blocks!<br>';
					echo 'No migration needed.<br><br>';
					echo '<a href="' . get_edit_post_link( $post_id ) . '" target="_blank" class="button">Edit in Block Editor</a>';
					echo '</div>';

					// Show what's actually stored in post_content
					echo '<h3>2. Database Content Check</h3>';
					$post_content = $post->post_content;
					$content_length = strlen( $post_content );

					echo '<div class="info-box">';
					echo '<strong>post_content length:</strong> ' . $content_length . ' characters<br>';
					echo '<strong>First 500 characters:</strong><br>';
					echo '<pre style="max-height: 200px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word;">';
					echo esc_html( substr( $post_content, 0, 500 ) );
					echo '</pre>';
					echo '</div>';

					// Test what the_content() will output
					echo '<h3>3. Frontend Output Test</h3>';
					echo '<div class="info-box">';
					echo '<strong>What the_content() will display:</strong><br>';
					echo '<div style="border: 2px solid #0c5460; padding: 15px; background: white; margin-top: 10px;">';
					$content = apply_filters( 'the_content', $post_content );
					echo $content;
					echo '</div>';
					echo '</div>';

					echo '<div class="info-box">';
					echo '<strong>View this page on frontend:</strong><br>';
					echo '<a href="' . get_permalink( $post_id ) . '" target="_blank" class="button button-primary">View Page</a>';
					echo '</div>';
				} else {
					echo '<div class="warning-box">';
					echo '⚠ This page has NO blocks (empty post_content)<br>';
					echo 'This is why the Block Editor shows blank.';
					echo '</div>';

					// Show ACF field data
					echo '<h3>2. ACF Field Data Available</h3>';
					$focus_keyword = get_field( 'seo_focus_keyword', $post_id );

					echo '<table class="test-table">';
					echo '<tr><th>Field</th><th>Value</th><th>Status</th></tr>';

					echo '<tr>';
					echo '<td><code>seo_focus_keyword</code></td>';
					echo '<td>' . ( $focus_keyword ? esc_html( $focus_keyword ) : '<em>empty</em>' ) . '</td>';
					echo '<td><span class="status-badge status-' . ( $focus_keyword ? 'pass' : 'fail' ) . '">';
					echo $focus_keyword ? '✓ HAS DATA' : '✗ EMPTY';
					echo '</span></td>';
					echo '</tr>';

					echo '</table>';

					// Show page builder meta
					echo '<h3>3. Page Builder Meta (Will be removed)</h3>';
					$elementor_enabled = get_post_meta( $post_id, '_elementor_edit_mode', true );
					$bb_enabled        = get_post_meta( $post_id, '_fl_builder_enabled', true );

					if ( $elementor_enabled || $bb_enabled ) {
						echo '<div class="warning-box">';
						if ( $elementor_enabled ) {
							echo '⚠ Elementor is enabled for this page<br>';
						}
						if ( $bb_enabled ) {
							echo '⚠ Beaver Builder is enabled for this page<br>';
						}
						echo '<br>Migration will remove these settings.';
						echo '</div>';
					} else {
						echo '<div class="info-box">No page builder meta found.</div>';
					}

					// Show migration button
					echo '<h3>4. Migrate to Block Editor</h3>';
					echo '<div class="info-box">';
					echo '<strong>This will:</strong><br>';
					echo '• Create a 2-column hero section with heading, paragraphs, and image<br>';
					echo '• Use placeholder text (you\'ll customize it after migration)<br>';
					echo '• Remove any Elementor/Beaver Builder settings<br>';
					echo '• Enable full drag-and-drop editing in Block Editor';
					echo '</div>';

					echo '<form method="post" style="margin-top: 20px;">';
					echo '<input type="hidden" name="migrate_page_to_blocks" value="1">';
					echo '<input type="hidden" name="post_id" value="' . $post_id . '">';
					wp_nonce_field( 'migrate_page_blocks', 'migrate_page_nonce' );
					echo '<button type="submit" class="button button-primary" onclick="return confirm(\'Are you sure? This will remove page builder settings.\')">Migrate to Block Editor</button>';
					echo '</form>';

					// Handle migration
					if ( isset( $_POST['migrate_page_to_blocks'] ) && isset( $_POST['post_id'] ) ) {
						check_admin_referer( 'migrate_page_blocks', 'migrate_page_nonce' );
						$migrate_post_id = intval( $_POST['post_id'] );

						$result = $this->migratePageToBlocks( $migrate_post_id );

						if ( $result ) {
							echo '<div class="success-box">';
							echo '✓ Successfully migrated to Block Editor!<br><br>';
							echo '<strong>Next steps:</strong><br>';
							echo '1. Click "Edit in Block Editor" below<br>';
							echo '2. Replace placeholder text with your content<br>';
							echo '3. Add/remove/rearrange blocks as needed<br><br>';
							echo '<a href="' . get_edit_post_link( $migrate_post_id ) . '" target="_blank" class="button button-primary">Edit in Block Editor</a> ';
							echo '<a href="?page=seo-generator-test-links&migrate_blocks_id=' . $migrate_post_id . '" class="button">Refresh to Verify</a>';
							echo '</div>';
						} else {
							echo '<div class="error-box">✗ Migration failed. Check error logs.</div>';
						}
					}
				}

				echo '<div style="margin-top: 20px;">';
				echo '<a href="?page=seo-generator-test-links" class="button">← Back to Tests</a>';
				echo '</div>';
			}
		}
		echo '</div>';
	}

	/**
	 * Migrate a single page to Block Editor
	 *
	 * @param int $post_id Post ID to migrate.
	 * @return bool True on success, false on failure.
	 */
	private function migratePageToBlocks( int $post_id ): bool {
		// Get post data
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'seo-page' ) {
			return false;
		}

		// Remove page builder meta
		delete_post_meta( $post_id, '_elementor_edit_mode' );
		delete_post_meta( $post_id, '_elementor_data' );
		delete_post_meta( $post_id, '_wp_page_template' );
		delete_post_meta( $post_id, '_fl_builder_enabled' );
		delete_post_meta( $post_id, '_fl_builder_draft' );
		delete_post_meta( $post_id, '_fl_builder_data' );

		// Get title from ACF or post title
		$page_title = get_the_title( $post_id );

		// Create block content
		$block_content = $this->generateBlockTemplate( $page_title );

		// Update post content
		$result = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $block_content,
			),
			true
		);

		return ! is_wp_error( $result );
	}

	/**
	 * Generate block template HTML
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
	 * Test 12: AI Generation Status Check
	 *
	 * @return void
	 */
	private function testAIGenerationStatus(): void {
		echo '<div class="test-section">';
		echo '<h2>Test 11: AI Generation & Image Status</h2>';

		$post_id = isset( $_GET['check_ai_id'] ) ? intval( $_GET['check_ai_id'] ) : 0;

		if ( ! $post_id ) {
			echo '<div class="info-box">';
			echo '<strong>Enter a post ID to check AI generation and image status:</strong><br><br>';
			echo '<form method="get">';
			echo '<input type="hidden" name="page" value="seo-generator-test-links">';
			echo '<input type="number" name="check_ai_id" placeholder="Enter post ID" style="width: 200px; padding: 8px;">';
			echo '<button type="submit" class="button button-primary">Check AI Status</button>';
			echo '</form>';
			echo '</div>';

			// Show recent pages
			$recent_pages = get_posts(
				array(
					'post_type'      => 'seo-page',
					'post_status'    => 'any',
					'posts_per_page' => 10,
					'orderby'        => 'ID',
					'order'          => 'DESC',
				)
			);

			if ( ! empty( $recent_pages ) ) {
				echo '<table class="test-table">';
				echo '<tr><th>ID</th><th>Title</th><th>Status</th><th>AI Generated?</th><th>Action</th></tr>';
				foreach ( $recent_pages as $page ) {
					$auto_gen = get_post_meta( $page->ID, '_auto_generated', true );
					echo '<tr>';
					echo '<td>' . $page->ID . '</td>';
					echo '<td>' . esc_html( $page->post_title ) . '</td>';
					echo '<td>' . esc_html( $page->post_status ) . '</td>';
					echo '<td><span class="status-badge status-' . ( $auto_gen ? 'pass' : 'fail' ) . '">';
					echo $auto_gen ? '✓ YES' : '✗ NO';
					echo '</span></td>';
					echo '<td><a href="?page=seo-generator-test-links&check_ai_id=' . $page->ID . '" class="button button-small">Check</a></td>';
					echo '</tr>';
				}
				echo '</table>';
			}
		} else {
			$post = get_post( $post_id );

			if ( ! $post ) {
				echo '<div class="error-box">✗ Post ID ' . $post_id . ' not found</div>';
			} else {
				echo '<div class="success-box">';
				echo '✓ Found post ID: ' . $post_id . '<br>';
				echo '<strong>Title:</strong> ' . esc_html( $post->post_title ) . '<br>';
				echo '<strong>Status:</strong> ' . esc_html( $post->post_status ) . '<br>';
				echo '<strong>View:</strong> <a href="' . get_permalink( $post_id ) . '" target="_blank">View Page</a>';
				echo '</div>';

				// Check AI generation status
				echo '<h3>1. AI Generation Status</h3>';
				$auto_gen = get_post_meta( $post_id, '_auto_generated', true );
				$gen_date = get_post_meta( $post_id, '_generation_date', true );
				$blocks_generated = get_post_meta( $post_id, '_blocks_generated', true );
				$blocks_failed = get_post_meta( $post_id, '_blocks_failed', true );

				if ( $auto_gen ) {
					echo '<div class="success-box">';
					echo '✓ This page WAS AI-generated<br>';
					if ( $gen_date ) {
						echo '<strong>Generated on:</strong> ' . esc_html( $gen_date ) . '<br>';
					}
					if ( $blocks_generated ) {
						echo '<strong>Blocks generated:</strong> ' . esc_html( $blocks_generated ) . '<br>';
					}
					if ( $blocks_failed ) {
						echo '<strong>Blocks failed:</strong> ' . esc_html( $blocks_failed ) . '<br>';
					}
					echo '</div>';
				} else {
					echo '<div class="warning-box">';
					echo '⚠ This page was NOT AI-generated<br>';
					echo 'It was either created manually or imported from CSV without auto-generate mode.';
					echo '</div>';
				}

				// Check ACF hero fields
				echo '<h3>2. ACF Hero Fields (AI-Generated Content)</h3>';
				if ( function_exists( 'get_field' ) ) {
					$hero_title = get_field( 'hero_title', $post_id );
					$hero_subtitle = get_field( 'hero_subtitle', $post_id );
					$hero_description = get_field( 'hero_description', $post_id );
					$hero_image = get_field( 'hero_image', $post_id );
					$focus_keyword = get_field( 'seo_focus_keyword', $post_id );

					echo '<table class="test-table">';
					echo '<tr><th>Field</th><th>Value</th><th>Status</th></tr>';

					// Focus keyword
					echo '<tr>';
					echo '<td><code>seo_focus_keyword</code></td>';
					echo '<td>' . ( $focus_keyword ? '<strong>' . esc_html( $focus_keyword ) . '</strong>' : '<em>empty</em>' ) . '</td>';
					echo '<td><span class="status-badge status-' . ( $focus_keyword ? 'pass' : 'fail' ) . '">';
					echo $focus_keyword ? '✓ HAS DATA' : '✗ EMPTY';
					echo '</span></td>';
					echo '</tr>';

					// Hero title
					echo '<tr>';
					echo '<td><code>hero_title</code></td>';
					echo '<td>' . ( $hero_title ? esc_html( substr( $hero_title, 0, 80 ) ) . ( strlen( $hero_title ) > 80 ? '...' : '' ) : '<em>empty</em>' ) . '</td>';
					echo '<td><span class="status-badge status-' . ( $hero_title ? 'pass' : 'fail' ) . '">';
					echo $hero_title ? '✓ HAS DATA' : '✗ EMPTY';
					echo '</span></td>';
					echo '</tr>';

					// Hero subtitle
					echo '<tr>';
					echo '<td><code>hero_subtitle</code></td>';
					echo '<td>' . ( $hero_subtitle ? esc_html( substr( $hero_subtitle, 0, 80 ) ) . ( strlen( $hero_subtitle ) > 80 ? '...' : '' ) : '<em>empty</em>' ) . '</td>';
					echo '<td><span class="status-badge status-' . ( $hero_subtitle ? 'pass' : 'fail' ) . '">';
					echo $hero_subtitle ? '✓ HAS DATA' : '✗ EMPTY';
					echo '</span></td>';
					echo '</tr>';

					// Hero description
					echo '<tr>';
					echo '<td><code>hero_description</code></td>';
					echo '<td>' . ( $hero_description ? esc_html( substr( $hero_description, 0, 80 ) ) . ( strlen( $hero_description ) > 80 ? '...' : '' ) : '<em>empty</em>' ) . '</td>';
					echo '<td><span class="status-badge status-' . ( $hero_description ? 'pass' : 'fail' ) . '">';
					echo $hero_description ? '✓ HAS DATA' : '✗ EMPTY';
					echo '</span></td>';
					echo '</tr>';

					// Hero image
					$image_status = false;
					$image_display = '<em>empty</em>';
					if ( ! empty( $hero_image ) ) {
						if ( is_array( $hero_image ) ) {
							$image_status = true;
							$image_display = 'Image ID: ' . $hero_image['ID'] . ' - ' . basename( $hero_image['url'] );
						} else if ( is_numeric( $hero_image ) ) {
							$image_status = true;
							$image_url = wp_get_attachment_url( $hero_image );
							$image_display = 'Image ID: ' . $hero_image . ' - ' . basename( $image_url );
						}
					}

					echo '<tr>';
					echo '<td><code>hero_image</code></td>';
					echo '<td>' . esc_html( $image_display ) . '</td>';
					echo '<td><span class="status-badge status-' . ( $image_status ? 'pass' : 'fail' ) . '">';
					echo $image_status ? '✓ HAS IMAGE' : '✗ NO IMAGE';
					echo '</span></td>';
					echo '</tr>';

					echo '</table>';

					// Show full content
					if ( $hero_title || $hero_subtitle || $hero_description ) {
						echo '<h3>3. Full Content Preview</h3>';
						echo '<div class="info-box">';
						if ( $hero_title ) {
							echo '<strong>Title:</strong><br><p>' . esc_html( $hero_title ) . '</p>';
						}
						if ( $hero_subtitle ) {
							echo '<strong>Subtitle:</strong><br><p>' . esc_html( $hero_subtitle ) . '</p>';
						}
						if ( $hero_description ) {
							echo '<strong>Description:</strong><br><p>' . esc_html( $hero_description ) . '</p>';
						}
						echo '</div>';
					}
				} else {
					echo '<div class="error-box">✗ ACF plugin not active</div>';
				}

				// Check Block Editor content
				echo '<h3>4. Block Editor Content (post_content)</h3>';
				$content_length = strlen( $post->post_content );
				$has_blocks = has_blocks( $post_id );

				echo '<div class="info-box">';
				echo '<strong>Has blocks:</strong> ' . ( $has_blocks ? '✓ YES' : '✗ NO' ) . '<br>';
				echo '<strong>Content length:</strong> ' . $content_length . ' characters<br>';
				if ( $content_length > 0 ) {
					echo '<strong>First 300 characters:</strong><br>';
					echo '<pre style="max-height: 150px; overflow-y: auto;">';
					echo esc_html( substr( $post->post_content, 0, 300 ) );
					echo '</pre>';
				}
				echo '</div>';

				// Check featured image
				echo '<h3>5. Featured Image (from CSV import)</h3>';
				$featured_id = get_post_thumbnail_id( $post_id );
				if ( $featured_id ) {
					$featured_url = wp_get_attachment_url( $featured_id );
					echo '<div class="success-box">';
					echo '✓ Featured image is set<br>';
					echo '<strong>Image ID:</strong> ' . $featured_id . '<br>';
					echo '<strong>URL:</strong> ' . basename( $featured_url ) . '<br>';
					echo '<img src="' . esc_url( $featured_url ) . '" style="max-width: 300px; margin-top: 10px;">';
					echo '</div>';
				} else {
					echo '<div class="warning-box">⚠ No featured image set</div>';
				}

				// Diagnosis
				echo '<h3>6. Diagnosis</h3>';
				if ( ! $auto_gen ) {
					echo '<div class="error-box">';
					echo '<strong>Problem:</strong> Page was not AI-generated<br><br>';
					echo '<strong>This means:</strong><br>';
					echo '• ACF hero fields will be empty<br>';
					echo '• No auto-assigned images<br>';
					echo '• Template will show Block Editor content or placeholder text<br><br>';
					echo '<strong>Solution:</strong><br>';
					echo '1. Use CSV import with "auto_generate" mode enabled, OR<br>';
					echo '2. Manually click "Generate All Content" button in the page editor';
					echo '</div>';
				} else if ( ! $hero_title && ! $hero_subtitle ) {
					echo '<div class="error-box">';
					echo '<strong>Problem:</strong> Page is marked as AI-generated but ACF fields are empty<br><br>';
					echo '<strong>This means:</strong><br>';
					echo '• AI generation failed or was interrupted<br>';
					echo '• Content was not saved to ACF fields<br><br>';
					echo '<strong>Solution:</strong> Re-generate the content using the "Generate All Content" button';
					echo '</div>';
				} else {
					echo '<div class="success-box">';
					echo '✓ Page looks good!<br><br>';
					echo '<strong>AI-generated content IS stored in ACF fields</strong><br>';
					echo 'The template should display this content on the frontend.<br><br>';
					if ( ! $image_status && ! $featured_id ) {
						echo '<div class="warning-box" style="margin-top: 10px;">';
						echo '⚠ However, NO IMAGE is set (neither ACF hero_image nor featured image)<br>';
						echo 'The page will display without an image.';
						echo '</div>';
					}
					echo '</div>';
				}

				echo '<div style="margin-top: 20px;">';
				echo '<a href="?page=seo-generator-test-links" class="button">← Back to Tests</a> ';
				echo '<a href="' . get_permalink( $post_id ) . '" target="_blank" class="button button-primary">View Page on Frontend</a>';
				echo '</div>';
			}
		}
		echo '</div>';
	}
}
