<?php
/**
 * Geographic Title Generator Page Manager
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Manages the Geographic Title Generator admin page functionality.
 *
 * This page allows users to:
 * - Upload CSV files containing keywords
 * - Generate page title variations by combining keywords with geographic data
 * - Display all possible combinations on screen
 */
class GeographicTitleGeneratorPage {
	/**
	 * Page slug.
	 *
	 * @var string
	 */
	private const PAGE_SLUG = 'seo-geographic-titles';

	/**
	 * Geographic data directory path.
	 *
	 * @var string
	 */
	private $data_dir;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->data_dir = plugin_dir_path( dirname( __DIR__ ) ) . 'docs/data/';
	}

	/**
	 * Register AJAX hooks and enqueue scripts.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_ajax_seo_upload_keywords_csv', array( $this, 'handleKeywordUpload' ) );
		add_action( 'wp_ajax_seo_generate_geo_titles', array( $this, 'handleTitleGeneration' ) );
		add_action( 'wp_ajax_seo_export_geo_titles_csv', array( $this, 'handleCsvExport' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAssets' ) );
	}

	/**
	 * Enqueue CSS and JavaScript assets for the page.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueueAssets( string $hook ): void {
		// Only enqueue on our page.
		if ( 'content-generator_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		// Enqueue shared import page styles (for seo-card, seo-card__title, etc.).
		wp_enqueue_style(
			'seo-admin-import',
			plugin_dir_url( dirname( __DIR__ ) ) . 'assets/css/admin-import.css',
			array(),
			filemtime( plugin_dir_path( dirname( __DIR__ ) ) . 'assets/css/admin-import.css' )
		);

		// Enqueue custom CSS for geo titles page.
		wp_enqueue_style(
			'seo-geo-titles',
			plugin_dir_url( dirname( __DIR__ ) ) . 'assets/css/admin-geo-titles.css',
			array( 'seo-admin-import' ), // Depends on import styles
			'1.0.0'
		);

		// Enqueue JavaScript.
		wp_enqueue_script(
			'seo-geo-titles',
			plugin_dir_url( dirname( __DIR__ ) ) . 'assets/js/src/geo-titles.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);

		// Localize script.
		wp_localize_script(
			'seo-geo-titles',
			'seoGeoTitles',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'seo_geo_titles_nonce' ),
				'uploadAction'  => 'seo_upload_keywords_csv',
				'generateAction' => 'seo_generate_geo_titles',
			)
		);
	}

	/**
	 * Handle keyword CSV upload via AJAX.
	 *
	 * @return void
	 */
	public function handleKeywordUpload(): void {
		check_ajax_referer( 'seo_geo_titles_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		if ( empty( $_FILES['csv_file'] ) ) {
			wp_send_json_error( array( 'message' => 'No file uploaded' ) );
		}

		$file = $_FILES['csv_file'];

		// Validate file type.
		$file_type = wp_check_filetype( $file['name'] );
		if ( 'csv' !== $file_type['ext'] && 'text/csv' !== $file['type'] ) {
			wp_send_json_error( array( 'message' => 'Invalid file type. Please upload a CSV file.' ) );
		}

		// Parse CSV and extract keywords.
		$keywords = $this->parseKeywordsCsv( $file['tmp_name'] );

		if ( empty( $keywords ) ) {
			wp_send_json_error( array( 'message' => 'No keywords found in CSV file' ) );
		}

		// Store keywords in transient for title generation.
		set_transient( 'seo_geo_keywords_' . get_current_user_id(), $keywords, HOUR_IN_SECONDS );

		wp_send_json_success(
			array(
				'keywords' => $keywords,
				'count'    => count( $keywords ),
			)
		);
	}

	/**
	 * Parse keywords from uploaded CSV file.
	 *
	 * @param string $file_path Path to the CSV file.
	 * @return array Array of keywords.
	 */
	private function parseKeywordsCsv( string $file_path ): array {
		$keywords = array();

		if ( ! file_exists( $file_path ) ) {
			return $keywords;
		}

		$handle = fopen( $file_path, 'r' );
		if ( false === $handle ) {
			return $keywords;
		}

		// Read first row to determine if there's a header.
		$first_row = fgetcsv( $handle );
		if ( false === $first_row ) {
			fclose( $handle );
			return $keywords;
		}

		// Check if first row is header (contains 'term', 'keyword', etc.).
		$is_header = false;
		$keyword_column_index = 0; // Default to first column

		foreach ( $first_row as $index => $cell ) {
			$cell_lower = strtolower( trim( $cell ) );
			if ( in_array( $cell_lower, array( 'term', 'keyword', 'keywords', 'word' ), true ) ) {
				$is_header = true;
				$keyword_column_index = $index; // Remember which column has keywords
				break;
			}
		}

		// If not header, add first row's first column only as keyword.
		if ( ! $is_header && isset( $first_row[0] ) ) {
			$keyword = trim( $first_row[0] );
			if ( ! empty( $keyword ) ) {
				$keywords[] = $keyword;
			}
		}

		// Read remaining rows - only extract the keyword column.
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( isset( $row[ $keyword_column_index ] ) ) {
				$keyword = trim( $row[ $keyword_column_index ] );
				if ( ! empty( $keyword ) ) {
					$keywords[] = $keyword;
				}
			}
		}

		fclose( $handle );

		return array_unique( $keywords );
	}

	/**
	 * Handle title generation via AJAX.
	 *
	 * @return void
	 */
	public function handleTitleGeneration(): void {
		$start_time = microtime( true );
		$metrics = array();

		check_ajax_referer( 'seo_geo_titles_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		// Get keywords from transient.
		$time_checkpoint = microtime( true );
		$keywords = get_transient( 'seo_geo_keywords_' . get_current_user_id() );
		$metrics['get_keywords'] = round( ( microtime( true ) - $time_checkpoint ) * 1000, 2 );

		if ( empty( $keywords ) ) {
			wp_send_json_error( array( 'message' => 'No keywords found. Please upload a CSV file first.' ) );
		}

		// Load geographic data (cached).
		$time_checkpoint = microtime( true );
		$locations = $this->getGeographicDataCached();
		$metrics['load_locations'] = round( ( microtime( true ) - $time_checkpoint ) * 1000, 2 );

		if ( empty( $locations ) ) {
			wp_send_json_error( array( 'message' => 'No geographic data found' ) );
		}

		// Get pagination parameters.
		$page = isset( $_POST['page'] ) ? max( 1, intval( $_POST['page'] ) ) : 1;
		$limit = isset( $_POST['limit'] ) ? max( 1, min( 1000, intval( $_POST['limit'] ) ) ) : 50;
		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

		$metrics['keyword_count'] = count( $keywords );
		$metrics['location_count'] = count( $locations );

		// Calculate total count.
		$time_checkpoint = microtime( true );
		$total_count = $this->calculateTotalCount( $keywords, $locations, $search );
		$metrics['calculate_count'] = round( ( microtime( true ) - $time_checkpoint ) * 1000, 2 );

		// Generate only the requested page of titles.
		$time_checkpoint = microtime( true );
		$titles = $this->generateTitleCombinationsPaginated( $keywords, $locations, $page, $limit, $search );
		$metrics['generate_titles'] = round( ( microtime( true ) - $time_checkpoint ) * 1000, 2 );

		$metrics['total_time'] = round( ( microtime( true ) - $start_time ) * 1000, 2 );

		// Log metrics to error log.
		error_log( '[GEO TITLES PERFORMANCE] Page ' . $page . ' - ' . wp_json_encode( $metrics ) );

		wp_send_json_success(
			array(
				'titles'     => $titles,
				'count'      => count( $titles ),
				'totalCount' => $total_count,
				'page'       => $page,
				'limit'      => $limit,
				'totalPages' => ceil( $total_count / $limit ),
				'metrics'    => $metrics, // Send to frontend for display.
			)
		);
	}

	/**
	 * Get geographic data with caching.
	 *
	 * @return array Array of cities/locations.
	 */
	private function getGeographicDataCached(): array {
		$cache_key = 'seo_geo_locations_data';
		$locations = get_transient( $cache_key );

		if ( false === $locations ) {
			$locations = $this->loadGeographicData();
			// Cache for 24 hours.
			set_transient( $cache_key, $locations, DAY_IN_SECONDS );
		}

		return $locations;
	}

	/**
	 * Calculate total count of titles.
	 *
	 * @param array  $keywords Array of keywords.
	 * @param array  $locations Array of locations.
	 * @param string $search Optional search term.
	 * @return int Total count.
	 */
	private function calculateTotalCount( array $keywords, array $locations, string $search = '' ): int {
		$prepositions = array( 'in', 'near', 'within' );
		$count = 0;

		foreach ( $keywords as $keyword ) {
			$keyword_normalized = strtolower( trim( $keyword ) );

			// Base keyword page.
			if ( empty( $search ) || $this->matchesSearch( $keyword_normalized, '', $search ) ) {
				$count++;
			}

			// Geographic variations.
			foreach ( $locations as $location ) {
				$location_normalized = strtolower( trim( $location ) );

				foreach ( $prepositions as $preposition ) {
					if ( empty( $search ) || $this->matchesSearch( $keyword_normalized, $location_normalized, $search, $preposition ) ) {
						$count++;
					}
				}
			}
		}

		return $count;
	}

	/**
	 * Check if a title matches the search term.
	 *
	 * @param string $keyword Keyword.
	 * @param string $location Location (optional).
	 * @param string $search Search term.
	 * @param string $preposition Preposition (optional).
	 * @return bool True if matches.
	 */
	private function matchesSearch( string $keyword, string $location, string $search, string $preposition = '' ): bool {
		$search = strtolower( trim( $search ) );
		if ( empty( $search ) ) {
			return true;
		}

		if ( empty( $location ) ) {
			// Base keyword.
			$title = $keyword;
			$slug = $keyword;
		} else {
			// Geographic variation.
			$title = $keyword . ' ' . $preposition . ' ' . $location;
			$slug = $keyword . '-' . $preposition . '-' . $location;
		}

		return ( false !== strpos( $title, $search ) || false !== strpos( $slug, $search ) );
	}

	/**
	 * Generate paginated title combinations.
	 *
	 * @param array  $keywords Array of keywords.
	 * @param array  $locations Array of locations.
	 * @param int    $page Page number (1-based).
	 * @param int    $limit Items per page.
	 * @param string $search Optional search term.
	 * @return array Array of generated titles for this page.
	 */
	private function generateTitleCombinationsPaginated( array $keywords, array $locations, int $page, int $limit, string $search = '' ): array {
		$prepositions = array( 'in', 'near', 'within' );
		$offset = ( $page - 1 ) * $limit;
		$titles = array();
		$current_index = 0;

		foreach ( $keywords as $keyword ) {
			$keyword_normalized = strtolower( trim( $keyword ) );

			// Base keyword page.
			if ( empty( $search ) || $this->matchesSearch( $keyword_normalized, '', $search ) ) {
				if ( $current_index >= $offset && count( $titles ) < $limit ) {
					$titles[] = array(
						'title' => $this->formatTitle( $keyword_normalized ),
						'slug'  => sanitize_title( $keyword_normalized ),
					);
				}
				$current_index++;

				if ( count( $titles ) >= $limit ) {
					return $titles;
				}
			}

			// Geographic variations.
			foreach ( $locations as $location ) {
				$location_normalized = strtolower( trim( $location ) );

				foreach ( $prepositions as $preposition ) {
					if ( empty( $search ) || $this->matchesSearch( $keyword_normalized, $location_normalized, $search, $preposition ) ) {
						if ( $current_index >= $offset && count( $titles ) < $limit ) {
							$slug = $keyword_normalized . '-' . $preposition . '-' . $location_normalized;
							$titles[] = array(
								'title' => $this->formatTitle( $slug ),
								'slug'  => sanitize_title( $slug ),
							);
						}
						$current_index++;

						if ( count( $titles ) >= $limit ) {
							return $titles;
						}
					}
				}
			}
		}

		return $titles;
	}

	/**
	 * Load geographic data from CSV files.
	 *
	 * @return array Array of cities/locations.
	 */
	private function loadGeographicData(): array {
		$locations = array();

		// Load from the comprehensive CSV file.
		$csv_file = $this->data_dir . 'san_diego_county_cities_communities_neighborhoods_subdivisions_v1.csv';

		if ( ! file_exists( $csv_file ) ) {
			return $locations;
		}

		$handle = fopen( $csv_file, 'r' );
		if ( false === $handle ) {
			return $locations;
		}

		// Skip header row.
		fgetcsv( $handle );

		// Read data rows.
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( count( $row ) >= 2 ) {
				$city      = trim( $row[0] );
				$area_name = trim( $row[1] );

				// Add unique locations.
				if ( ! empty( $city ) && ! in_array( $city, $locations, true ) ) {
					$locations[] = $city;
				}
				if ( ! empty( $area_name ) && ! in_array( $area_name, $locations, true ) ) {
					$locations[] = $area_name;
				}
			}
		}

		fclose( $handle );

		// Sort alphabetically.
		sort( $locations );

		return $locations;
	}


	/**
	 * Format title for display.
	 *
	 * @param string $slug The slug to format.
	 * @return string Formatted title.
	 */
	private function formatTitle( string $slug ): string {
		// Replace hyphens with spaces and capitalize words.
		return ucwords( str_replace( '-', ' ', $slug ) );
	}

	/**
	 * Render the admin page.
	 *
	 * @return void
	 */
	public function render(): void {
		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'seo-generator' ) );
		}

		?>
		<div class="wrap seo-generator-page">
			<h1 class="heading-1"><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="seo-geo-titles-container" style="max-width: 1200px;">
				<!-- Upload Section -->
				<div class="seo-card mt-6">
					<h3 class="seo-card__title">📁 Step 1: Upload Keywords CSV</h3>
					<div class="seo-card__content">
						<p>Upload a CSV file containing keywords. The system will generate page title variations by combining these keywords with San Diego geographic data.</p>

						<form id="seo-keyword-upload-form" enctype="multipart/form-data" style="margin-top: 15px;">
							<input type="file" id="keyword-csv-file" name="csv_file" accept=".csv" required class="regular-text" />
							<button type="submit" class="button button-primary" style="margin-left: 10px;">Upload Keywords</button>
						</form>

						<div id="upload-status" style="margin-top: 15px;"></div>
					</div>
				</div>

				<!-- Generation Section -->
				<div class="seo-card mt-6">
					<h3 class="seo-card__title">⚡ Step 2: Generate Title Variations</h3>
					<div class="seo-card__content">
						<p>Click the button below to generate all possible combinations of your keywords with geographic locations.</p>

						<button id="generate-titles-btn" class="button button-primary" style="margin-top: 15px;" disabled>Generate Title Variations</button>
						<div id="generation-status" style="margin-top: 15px;"></div>
					</div>
				</div>

				<!-- Results Section -->
				<div class="seo-card mt-6" style="display: none;" id="results-section">
					<h3 class="seo-card__title">📊 Generated Titles</h3>
					<div class="seo-card__content">
						<div style="margin-bottom: 15px;">
							<strong>Total Titles: <span id="total-count">0</span></strong>
							<button id="export-csv-btn" class="button" style="margin-left: 15px;">Export as CSV</button>
							<button id="copy-all-btn" class="button" style="margin-left: 10px;">Copy All Slugs</button>
						</div>

						<div style="margin-bottom: 15px;">
							<input type="text" id="search-titles" placeholder="Search titles..." class="regular-text" style="width: 300px;" />
						</div>

						<!-- Pagination Controls (Top) -->
						<div id="pagination-controls" style="margin-bottom: 15px;">
							<!-- Pagination will be inserted here via JavaScript -->
						</div>

						<div id="titles-list" style="border: 1px solid #ddd; padding: 15px; background: #fff;">
							<!-- Titles will be inserted here via JavaScript -->
						</div>

						<!-- Pagination Controls (Bottom) -->
						<div id="pagination-controls-bottom" style="margin-top: 15px;">
							<!-- Same pagination controls mirrored at bottom -->
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle CSV export via AJAX.
	 *
	 * @return void
	 */
	public function handleCsvExport(): void {
		check_ajax_referer( 'seo_geo_titles_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		// Get keywords from transient.
		$keywords = get_transient( 'seo_geo_keywords_' . get_current_user_id() );

		if ( empty( $keywords ) ) {
			wp_send_json_error( array( 'message' => 'No keywords found. Please upload a CSV file first.' ) );
		}

		// Load geographic data (cached).
		$locations = $this->getGeographicDataCached();

		if ( empty( $locations ) ) {
			wp_send_json_error( array( 'message' => 'No geographic data found' ) );
		}

		// Get search filter if provided.
		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

		// Generate all titles (no pagination).
		$all_titles = $this->generateAllTitleCombinations( $keywords, $locations, $search );

		if ( empty( $all_titles ) ) {
			wp_send_json_error( array( 'message' => 'No titles to export' ) );
		}

		// Create CSV content.
		$csv_content = "Title,Slug\n";
		foreach ( $all_titles as $item ) {
			// Escape CSV fields properly.
			$title = str_replace( '"', '""', $item['title'] );
			$slug = str_replace( '"', '""', $item['slug'] );
			$csv_content .= "\"$title\",\"$slug\"\n";
		}

		// Generate filename with timestamp.
		$filename = 'geo-titles-' . gmdate( 'Y-m-d-His' ) . '.csv';

		wp_send_json_success(
			array(
				'csv'      => $csv_content,
				'filename' => $filename,
				'count'    => count( $all_titles ),
			)
		);
	}

	/**
	 * Generate all title combinations without pagination.
	 *
	 * @param array  $keywords  Array of keywords.
	 * @param array  $locations Array of locations.
	 * @param string $search    Optional search filter.
	 * @return array Array of all title combinations.
	 */
	private function generateAllTitleCombinations( array $keywords, array $locations, string $search = '' ): array {
		$titles = array();
		$prepositions = array( 'In', 'Near', 'Within' );

		foreach ( $keywords as $keyword ) {
			foreach ( $locations as $location ) {
				foreach ( $prepositions as $prep ) {
					$title = trim( $keyword ) . ' ' . $prep . ' ' . $location;
					$slug = sanitize_title( $title );

					// Apply search filter if provided.
					if ( ! empty( $search ) ) {
						if ( stripos( $title, $search ) === false && stripos( $slug, $search ) === false ) {
							continue;
						}
					}

					$titles[] = array(
						'title' => $title,
						'slug'  => $slug,
					);
				}
			}
		}

		return $titles;
	}
}
