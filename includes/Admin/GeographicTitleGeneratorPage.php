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
	 * MODE CONFIGURATION
	 *
	 * Set to true: ONLY structured mode (style, stone, product, metal columns required)
	 * Set to false: Flexible mode - accepts any CSV format and maps columns intelligently
	 *
	 * @var bool
	 */
	private const FORCE_STRUCTURED_MODE = false;

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
	 * Attribute dictionaries for intelligent parsing.
	 *
	 * @var array
	 */
	private $attribute_dictionaries;

	/**
	 * City to zip code mappings.
	 *
	 * @var array
	 */
	private $city_zipcodes;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->data_dir = plugin_dir_path( dirname( __DIR__ ) ) . 'docs/data/';
		$this->initializeAttributeDictionaries();
		$this->initializeCityZipcodes();
	}

	/**
	 * Initialize dictionaries for intelligent keyword parsing.
	 *
	 * @return void
	 */
	private function initializeAttributeDictionaries(): void {
		$this->attribute_dictionaries = array(
			'stones'   => array(
				'diamond',
				'sapphire',
				'ruby',
				'emerald',
				'amethyst',
				'topaz',
				'opal',
				'pearl',
				'aquamarine',
				'garnet',
				'peridot',
				'citrine',
				'tanzanite',
				'turquoise',
				'jade',
				'onyx',
				'moonstone',
				'alexandrite',
				'morganite',
				'spinel',
			),
			'products' => array(
				'ring',
				'rings',
				'necklace',
				'necklaces',
				'bracelet',
				'bracelets',
				'earring',
				'earrings',
				'pendant',
				'pendants',
				'chain',
				'chains',
				'charm',
				'charms',
				'anklet',
				'anklets',
				'brooch',
				'brooches',
				'watch',
				'watches',
				'band',
				'bands',
			),
			'styles'   => array(
				'engagement',
				'wedding',
				'anniversary',
				'vintage',
				'modern',
				'classic',
				'contemporary',
				'antique',
				'art deco',
				'victorian',
				'edwardian',
				'minimalist',
				'luxury',
				'designer',
				'custom',
				'handmade',
				'estate',
			),
			'metals'   => array(
				'gold',
				'silver',
				'platinum',
				'rose gold',
				'white gold',
				'yellow gold',
				'titanium',
				'stainless steel',
				'palladium',
				'bronze',
				'copper',
			),
		);
	}

	/**
	 * Initialize city to zip code mappings.
	 *
	 * @return void
	 */
	private function initializeCityZipcodes(): void {
		$this->city_zipcodes = array(
			'carlsbad'        => array( '92008', '92009', '92010', '92011' ),
			'chula vista'     => array( '91910', '91911', '91913', '91914', '91915' ),
			'coronado'        => array( '92118' ),
			'del mar'         => array( '92014' ),
			'el cajon'        => array( '92019', '92020', '92021' ),
			'encinitas'       => array( '92024', '92007' ),
			'escondido'       => array( '92025', '92026', '92027', '92029', '92046' ),
			'imperial beach'  => array( '91932', '91933' ),
			'la mesa'         => array( '91941', '91942', '91943', '91944' ),
			'lemon grove'     => array( '91945', '91946' ),
			'national city'   => array( '91950', '91951' ),
			'oceanside'       => array( '92054', '92055', '92056', '92057', '92058' ),
			'poway'           => array( '92064', '92074' ),
			'san diego'       => array(
				'92101', '92102', '92103', '92104', '92105', '92106', '92107', '92108', '92109', '92110',
				'92111', '92113', '92114', '92115', '92116', '92117', '92119', '92120', '92121', '92122',
				'92123', '92124', '92126', '92127', '92128', '92129', '92130', '92131', '92134', '92136',
				'92139', '92140', '92145', '92154', '92155', '92161', '92173', '92182', '91901', '91902',
				'91905', '91906', '91911', '91913', '91916', '91917', '91931', '91934', '91935', '91941',
				'91942', '91945', '91948', '91962', '91963', '91977', '91978', '91980', '92037', '92071', '92093',
			),
			'san marcos'      => array( '92069', '92078', '92079' ),
			'santee'          => array( '92071' ),
			'solana beach'    => array( '92075' ),
			'vista'           => array( '92081', '92083', '92084', '92085' ),
		);
	}

	/**
	 * Get zip codes for a given city.
	 *
	 * @param string $city The city name.
	 * @return array Array of zip codes for the city.
	 */
	private function getZipCodesForCity( string $city ): array {
		$city_lower = strtolower( trim( $city ) );
		return $this->city_zipcodes[ $city_lower ] ?? array();
	}

	/**
	 * Extract city from location and get a zip code.
	 *
	 * @param string $location The location (could be city or neighborhood).
	 * @return string A zip code for the location, or empty string if not found.
	 */
	private function getZipCodeForLocation( string $location ): string {
		$location_lower = strtolower( trim( $location ) );

		// First, check if location is directly a city name
		if ( isset( $this->city_zipcodes[ $location_lower ] ) ) {
			$zipcodes = $this->city_zipcodes[ $location_lower ];
			// Return the first zip code for the city
			return ! empty( $zipcodes ) ? $zipcodes[0] : '';
		}

		// If not a direct city match, try to find the parent city from geographic data
		$city = $this->findCityForLocation( $location );
		if ( ! empty( $city ) ) {
			$zipcodes = $this->getZipCodesForCity( $city );
			return ! empty( $zipcodes ) ? $zipcodes[0] : '';
		}

		return '';
	}

	/**
	 * Find the parent city for a given location (neighborhood/area).
	 *
	 * @param string $location The location to find the city for.
	 * @return string The parent city name, or empty string if not found.
	 */
	private function findCityForLocation( string $location ): string {
		$csv_file = $this->data_dir . 'san_diego_county_cities_communities_neighborhoods_subdivisions_v1.csv';

		if ( ! file_exists( $csv_file ) ) {
			return '';
		}

		$handle = fopen( $csv_file, 'r' );
		if ( false === $handle ) {
			return '';
		}

		// Skip header row.
		fgetcsv( $handle );

		$location_lower = strtolower( trim( $location ) );

		// Read data rows.
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( count( $row ) >= 2 ) {
				$city      = trim( $row[0] );
				$area_name = strtolower( trim( $row[1] ) );

				// If the location matches an area name, return its city
				if ( $area_name === $location_lower ) {
					fclose( $handle );
					return $city;
				}
			}
		}

		fclose( $handle );
		return '';
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
		add_action( 'wp_ajax_seo_send_geo_to_import', array( $this, 'handleSendToImport' ) );
		add_action( 'wp_ajax_seo_clear_geo_cache', array( $this, 'handleClearCache' ) );
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

		// Check for errors (e.g., wrong format when structured mode is forced).
		if ( isset( $keywords['error'] ) ) {
			wp_send_json_error( array( 'message' => $keywords['error'] ) );
		}

		if ( empty( $keywords ) ) {
			wp_send_json_error( array( 'message' => 'No keywords found in CSV file' ) );
		}

		// Store keywords in transient for title generation.
		set_transient( 'seo_geo_keywords_' . get_current_user_id(), $keywords, HOUR_IN_SECONDS );

		// Clear location cache to ensure fresh data is loaded.
		delete_transient( 'seo_geo_locations_data_v4' );

		// Debug: Show first parsed item
		$debug_info = '';
		if ( ! empty( $keywords[0] ) ) {
			$debug_info = 'First item parsed: ' . wp_json_encode( $keywords[0] );
		}

		wp_send_json_success(
			array(
				'keywords' => $keywords,
				'count'    => count( $keywords ),
				'debug'    => $debug_info,
			)
		);
	}

	/**
	 * Intelligently parse a keyword/phrase and extract attributes.
	 *
	 * @param string $keyword The keyword to parse (e.g., "Diamond Ring", "Vintage Gold Necklace").
	 * @return array Extracted attributes (style, stone, product, metal).
	 */
	private function parseKeywordIntoAttributes( string $keyword ): array {
		$keyword_lower = strtolower( trim( $keyword ) );
		$words = explode( ' ', $keyword_lower );

		$attributes = array(
			'style'   => '',
			'stone'   => '',
			'product' => '',
			'metal'   => '',
		);

		// Debug logging
		error_log( '[PARSER] Parsing keyword: ' . $keyword );
		error_log( '[PARSER] Words: ' . wp_json_encode( $words ) );

		// Check for multi-word matches first (e.g., "rose gold", "art deco").
		foreach ( array( 'metals', 'styles' ) as $dict_key ) {
			foreach ( $this->attribute_dictionaries[ $dict_key ] as $term ) {
				if ( strpos( $keyword_lower, $term ) !== false ) {
					$attr_key = rtrim( $dict_key, 's' ); // "metals" → "metal"
					$attributes[ $attr_key ] = ucwords( $term );
					// Remove matched term from keyword to avoid double-matching.
					$keyword_lower = str_replace( $term, '', $keyword_lower );
					break;
				}
			}
		}

		// Check for single-word matches.
		foreach ( $words as $word ) {
			$word = trim( $word );
			if ( empty( $word ) ) {
				continue;
			}

			// Check stones.
			if ( empty( $attributes['stone'] ) && in_array( $word, $this->attribute_dictionaries['stones'], true ) ) {
				$attributes['stone'] = ucfirst( $word );
				continue;
			}

			// Check products.
			if ( empty( $attributes['product'] ) && in_array( $word, $this->attribute_dictionaries['products'], true ) ) {
				$attributes['product'] = ucfirst( $word );
				continue;
			}

			// Check styles (if not already found multi-word).
			if ( empty( $attributes['style'] ) && in_array( $word, $this->attribute_dictionaries['styles'], true ) ) {
				$attributes['style'] = ucfirst( $word );
				continue;
			}

			// Check metals (if not already found multi-word).
			if ( empty( $attributes['metal'] ) && in_array( $word, $this->attribute_dictionaries['metals'], true ) ) {
				$attributes['metal'] = ucfirst( $word );
				continue;
			}
		}

		// If no product found, use the entire keyword as product.
		if ( empty( $attributes['product'] ) ) {
			$attributes['product'] = ucwords( $keyword );
		}

		// Debug logging
		error_log( '[PARSER] Result: ' . wp_json_encode( $attributes ) );

		return $attributes;
	}

	/**
	 * Parse keywords from uploaded CSV file.
	 * Supports two modes:
	 * 1. Simple mode: single keyword column
	 * 2. Structured mode: style, stone, product, metal columns
	 *
	 * @param string $file_path Path to the CSV file.
	 * @return array Array of keywords or structured data.
	 */
	private function parseKeywordsCsv( string $file_path ): array {
		$data = array();

		if ( ! file_exists( $file_path ) ) {
			return $data;
		}

		$handle = fopen( $file_path, 'r' );
		if ( false === $handle ) {
			return $data;
		}

		// Read first row to determine mode and structure.
		$first_row = fgetcsv( $handle );
		if ( false === $first_row ) {
			fclose( $handle );
			return $data;
		}

		// Intelligent column mapping - map any column name to our attributes.
		$column_mapping = array(
			'style'   => array( 'style', 'type', 'category' ),
			'stone'   => array( 'stone', 'gem', 'gemstone', 'material' ),
			'product' => array( 'product', 'item', 'name', 'keyword', 'keywords', 'term', 'title' ),
			'metal'   => array( 'metal', 'finish', 'color' ),
		);

		$structured_columns = array();
		$is_structured_mode = false;

		// Map CSV columns to our attributes.
		foreach ( $first_row as $index => $cell ) {
			$cell_lower = strtolower( trim( $cell ) );

			foreach ( $column_mapping as $attribute => $possible_names ) {
				if ( in_array( $cell_lower, $possible_names, true ) ) {
					$structured_columns[ $attribute ] = $index;
					$is_structured_mode = true;
					break;
				}
			}
		}

		// Force structured mode if configured.
		if ( self::FORCE_STRUCTURED_MODE && ! $is_structured_mode ) {
			fclose( $handle );
			return array(
				'error' => 'Structured mode required. CSV must have headers: style, stone, product, metal (or similar)',
			);
		}

		// Check if only product column exists (keyword/term column) - trigger intelligent parsing
		$only_product_column = $is_structured_mode &&
		                       isset( $structured_columns['product'] ) &&
		                       ! isset( $structured_columns['style'] ) &&
		                       ! isset( $structured_columns['stone'] ) &&
		                       ! isset( $structured_columns['metal'] );

		// If structured mode detected, parse as structured data.
		if ( $is_structured_mode && ! $only_product_column ) {
			// True structured mode with multiple attribute columns
			while ( ( $row = fgetcsv( $handle ) ) !== false ) {
				$item = array(
					'mode'    => 'structured',
					'style'   => isset( $structured_columns['style'] ) && isset( $row[ $structured_columns['style'] ] ) ? trim( $row[ $structured_columns['style'] ] ) : '',
					'stone'   => isset( $structured_columns['stone'] ) && isset( $row[ $structured_columns['stone'] ] ) ? trim( $row[ $structured_columns['stone'] ] ) : '',
					'product' => isset( $structured_columns['product'] ) && isset( $row[ $structured_columns['product'] ] ) ? trim( $row[ $structured_columns['product'] ] ) : '',
					'metal'   => isset( $structured_columns['metal'] ) && isset( $row[ $structured_columns['metal'] ] ) ? trim( $row[ $structured_columns['metal'] ] ) : '',
				);

				// Skip completely empty rows.
				if ( empty( $item['style'] ) && empty( $item['stone'] ) && empty( $item['product'] ) && empty( $item['metal'] ) ) {
					continue;
				}

				$data[] = $item;
			}
		} elseif ( $only_product_column ) {
			// Only product/keyword column - run intelligent parser
			$product_col_index = $structured_columns['product'];
			while ( ( $row = fgetcsv( $handle ) ) !== false ) {
				$keyword = isset( $row[ $product_col_index ] ) ? trim( $row[ $product_col_index ] ) : '';
				error_log( '[CSV] Only product column detected, parsing: ' . $keyword );
				if ( ! empty( $keyword ) ) {
					$parsed = $this->parseKeywordIntoAttributes( $keyword );
					$data[] = array_merge( array( 'mode' => 'structured' ), $parsed );
				}
			}
		} else {
			// Fallback: intelligently parse first column as keyword.
			// If first row looks like a header, skip it.
			$first_cell = trim( $first_row[0] );
			$looks_like_header = in_array(
				strtolower( $first_cell ),
				array( 'term', 'keyword', 'keywords', 'word', 'name', 'title', 'item', 'product' ),
				true
			);

			// If not a header, parse first row and extract attributes.
			if ( ! $looks_like_header && ! empty( $first_cell ) ) {
				$parsed = $this->parseKeywordIntoAttributes( $first_cell );
				$data[] = array_merge( array( 'mode' => 'structured' ), $parsed );
			}

			// Read remaining rows - intelligently parse each keyword.
			while ( ( $row = fgetcsv( $handle ) ) !== false ) {
				$keyword = isset( $row[0] ) ? trim( $row[0] ) : '';
				error_log( '[CSV] Row data: ' . wp_json_encode( $row ) );
				if ( ! empty( $keyword ) ) {
					error_log( '[CSV] Parsing keyword: ' . $keyword );
					$parsed = $this->parseKeywordIntoAttributes( $keyword );
					error_log( '[CSV] Parsed result: ' . wp_json_encode( $parsed ) );
					$data[] = array_merge( array( 'mode' => 'structured' ), $parsed );
				}
			}
		}

		fclose( $handle );

		return $data;
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
		$landmarks_only = ! empty( $_POST['landmarksOnly'] ) && ( 'true' === $_POST['landmarksOnly'] || true === $_POST['landmarksOnly'] );
		$urgent_only = ! empty( $_POST['urgentOnly'] ) && ( 'true' === $_POST['urgentOnly'] || true === $_POST['urgentOnly'] );

		// Filter locations if landmarks-only mode is enabled.
		if ( $landmarks_only ) {
			$original_count = count( $locations );
			$locations = array_filter(
				$locations,
				function ( $location_data ) {
					return isset( $location_data['type'] ) && 'landmark' === $location_data['type'];
				}
			);
			$filtered_count = count( $locations );
			error_log( '[GeoTitles] Landmarks-only filter: ' . $original_count . ' -> ' . $filtered_count . ' locations' );
		}

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

		// Check for duplicates and filter them out.
		$duplicate_count = 0;
		$time_checkpoint = microtime( true );
		$duplicate_slugs = $this->checkDuplicateTitles( $titles );
		$duplicate_count = count( $duplicate_slugs );
		$metrics['check_duplicates'] = round( ( microtime( true ) - $time_checkpoint ) * 1000, 2 );

		// Filter out duplicates from the titles array.
		$original_count = count( $titles );
		$titles = array_filter(
			$titles,
			function ( $title ) use ( $duplicate_slugs ) {
				return ! in_array( $title['slug'], $duplicate_slugs, true );
			}
		);
		$titles = array_values( $titles ); // Re-index array

		$filtered_count = $original_count - count( $titles );
		error_log( '[GeoTitles] Filtered out ' . $filtered_count . ' duplicates from current page' );

		// Filter for urgent words only if requested.
		if ( $urgent_only ) {
			$pre_urgent_count = count( $titles );
			$titles = array_filter(
				$titles,
				function ( $title ) {
					return ! empty( $title['urgent'] );
				}
			);
			$titles = array_values( $titles ); // Re-index array
			$urgent_filtered_count = $pre_urgent_count - count( $titles );
			error_log( '[GeoTitles] Urgent-only filter: ' . $pre_urgent_count . ' -> ' . count( $titles ) . ' titles' );
		}

		$metrics['total_time'] = round( ( microtime( true ) - $start_time ) * 1000, 2 );

		// Log metrics to error log.
		error_log( '[GEO TITLES PERFORMANCE] Page ' . $page . ' - ' . wp_json_encode( $metrics ) );

		// Debug: Log first title to verify structure
		if ( ! empty( $titles ) ) {
			error_log( '[GEO TITLES DEBUG] First title structure: ' . wp_json_encode( $titles[0] ) );
		}

		wp_send_json_success(
			array(
				'titles'          => $titles,
				'count'           => count( $titles ),
				'totalCount'      => $total_count,
				'page'            => $page,
				'limit'           => $limit,
				'totalPages'      => ceil( $total_count / $limit ),
				'duplicateCount'  => $duplicate_count,
				'duplicateSlugs'  => $duplicate_slugs,
				'metrics'         => $metrics, // Send to frontend for display.
			)
		);
	}

	/**
	 * Handle clearing the geographic data cache.
	 *
	 * @return void
	 */
	public function handleClearCache(): void {
		check_ajax_referer( 'seo_geo_titles_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		// Delete all versions of the cache
		delete_transient( 'seo_geo_locations_data_v5' );
		delete_transient( 'seo_geo_locations_data_v4' );
		delete_transient( 'seo_geo_locations_data_v3' );
		delete_transient( 'seo_geo_locations_data_v2' );
		delete_transient( 'seo_geo_locations_data' );

		wp_send_json_success( array( 'message' => 'Cache cleared successfully!' ) );
	}

	/**
	 * Get geographic data with caching.
	 *
	 * @return array Array of cities/locations.
	 */
	private function getGeographicDataCached(): array {
		$cache_key = 'seo_geo_locations_data_v5'; // v5: Changed to array structure with type field
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
	private function calculateTotalCount( array $data, array $locations, string $search = '' ): int {
		$prepositions = array( 'In', 'Near', 'Within' );
		$count = 0;

		foreach ( $data as $item ) {
			$mode = isset( $item['mode'] ) ? $item['mode'] : 'simple';

			if ( 'structured' === $mode ) {
				// Structured mode: each item generates (locations × prepositions) titles
				foreach ( $locations as $location => $location_data ) {
					foreach ( $prepositions as $prep ) {
						// Build title to check search filter
						$title_parts = array();
						if ( ! empty( $item['style'] ) ) {
							$title_parts[] = $item['style'];
						}
						if ( ! empty( $item['stone'] ) ) {
							$title_parts[] = $item['stone'];
						}
						if ( ! empty( $item['product'] ) ) {
							$title_parts[] = $item['product'];
						}
						if ( ! empty( $item['metal'] ) ) {
							$title_parts[] = $item['metal'];
						}
						$title_parts[] = $prep;
						$title_parts[] = $location;

						$title = implode( ' ', $title_parts );
						$slug = sanitize_title( $title );

						// Check search filter
						if ( empty( $search ) || stripos( $title, $search ) !== false || stripos( $slug, $search ) !== false ) {
							$count++;
						}
					}
				}
			} else {
				// Simple mode: each keyword generates (locations × prepositions) titles
				$keyword = isset( $item['keyword'] ) ? trim( $item['keyword'] ) : '';

				if ( empty( $keyword ) ) {
					continue;
				}

				foreach ( $locations as $location => $location_data ) {
					foreach ( $prepositions as $prep ) {
						$title = $keyword . ' ' . $prep . ' ' . $location;
						$slug = sanitize_title( $title );

						// Check search filter
						if ( empty( $search ) || stripos( $title, $search ) !== false || stripos( $slug, $search ) !== false ) {
							$count++;
						}
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
	private function generateTitleCombinationsPaginated( array $data, array $locations, int $page, int $limit, string $search = '' ): array {
		$prepositions = array( 'In', 'Near', 'Within' );
		$offset = ( $page - 1 ) * $limit;
		$titles = array();
		$current_index = 0;

		// Load urgent words for optional title variations.
		$urgent_words = $this->loadUrgentWords();
		$urgent_word_count = count( $urgent_words );

		foreach ( $data as $item ) {
			$mode = isset( $item['mode'] ) ? $item['mode'] : 'simple';

			if ( 'structured' === $mode ) {
				// Structured mode: [style] [stone] [product] [metal] + [preposition] + [location]
				foreach ( $locations as $location => $location_data ) {
					foreach ( $prepositions as $prep ) {
						$title_parts = array();

						if ( ! empty( $item['style'] ) ) {
							$title_parts[] = $item['style'];
						}
						if ( ! empty( $item['stone'] ) ) {
							$title_parts[] = $item['stone'];
						}
						if ( ! empty( $item['product'] ) ) {
							$title_parts[] = $item['product'];
						}
						if ( ! empty( $item['metal'] ) ) {
							$title_parts[] = $item['metal'];
						}

						$title_parts[] = $prep;
						$title_parts[] = $location;

						$title = implode( ' ', $title_parts );

						// Randomly add urgent word (50% chance) if urgent words are available.
						$urgent_word = '';
						if ( $urgent_word_count > 0 && rand( 0, 1 ) === 1 ) {
							$urgent_word = $urgent_words[ array_rand( $urgent_words ) ];
							// 80% chance to place after (SEO optimal), 20% chance to place before.
							if ( rand( 1, 100 ) <= 80 ) {
								$title = $title . ', ' . $urgent_word;
							} else {
								$title = $urgent_word . ': ' . $title;
							}
						}

						$slug = sanitize_title( $title );

						// Apply search filter.
						if ( ! empty( $search ) ) {
							if ( stripos( $title, $search ) === false && stripos( $slug, $search ) === false ) {
								$current_index++;
								continue;
							}
						}

						if ( $current_index >= $offset && count( $titles ) < $limit ) {
							$titles[] = array(
								'title'        => $title,
								'slug'         => $slug,
								'stone'        => ! empty( $item['stone'] ) ? $item['stone'] : '',
								'product'      => ! empty( $item['product'] ) ? $item['product'] : '',
								'style'        => ! empty( $item['style'] ) ? $item['style'] : '',
								'metal'        => ! empty( $item['metal'] ) ? $item['metal'] : '',
								'location'     => $location,
								'zip'          => $location_data['zip'],
								'locationType' => $location_data['type'],
								'urgent'       => $urgent_word,
							);
						}
						$current_index++;

						if ( count( $titles ) >= $limit ) {
							return $titles;
						}
					}
				}
			} else {
				// Simple mode: [keyword] + [preposition] + [location]
				$keyword = isset( $item['keyword'] ) ? trim( $item['keyword'] ) : '';

				if ( empty( $keyword ) ) {
					continue;
				}

				foreach ( $locations as $location => $location_data ) {
					foreach ( $prepositions as $prep ) {
						$title = $keyword . ' ' . $prep . ' ' . $location;

						// Randomly add urgent word (50% chance) if urgent words are available.
						$urgent_word = '';
						if ( $urgent_word_count > 0 && rand( 0, 1 ) === 1 ) {
							$urgent_word = $urgent_words[ array_rand( $urgent_words ) ];
							// 80% chance to place after (SEO optimal), 20% chance to place before.
							if ( rand( 1, 100 ) <= 80 ) {
								$title = $title . ', ' . $urgent_word;
							} else {
								$title = $urgent_word . ': ' . $title;
							}
						}

						$slug = sanitize_title( $title );

						// Apply search filter.
						if ( ! empty( $search ) ) {
							if ( stripos( $title, $search ) === false && stripos( $slug, $search ) === false ) {
								$current_index++;
								continue;
							}
						}

						if ( $current_index >= $offset && count( $titles ) < $limit ) {
							$titles[] = array(
								'title'        => $title,
								'slug'         => $slug,
								'stone'        => '',
								'product'      => $keyword,
								'style'        => '',
								'metal'        => '',
								'location'     => $location,
								'zip'          => $location_data['zip'],
								'locationType' => $location_data['type'],
								'urgent'       => $urgent_word,
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
	 * @return array Array of cities/locations with their zip codes and types.
	 *               Format: [ 'location_name' => ['zip' => 'zip_code', 'type' => 'neighborhood'|'landmark'], ... ]
	 */
	private function loadGeographicData(): array {
		$locations = array();
		$city_zips = array(); // Store city-to-zip mappings for landmarks

		// Load from the comprehensive CSV file (cities, neighborhoods, subdivisions).
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
			if ( count( $row ) >= 5 ) {
				$city      = trim( $row[0] );
				$area_name = trim( $row[1] );
				$zip_code  = trim( $row[4] ); // Column 5 is zip_code

				// Add unique locations with their zip codes and type.
				if ( ! empty( $city ) && ! isset( $locations[ $city ] ) ) {
					$locations[ $city ] = array(
						'zip'  => $zip_code,
						'type' => 'neighborhood',
					);
					$city_zips[ strtolower( $city ) ] = $zip_code; // Store for landmark lookup
				}
				if ( ! empty( $area_name ) && ! isset( $locations[ $area_name ] ) ) {
					$locations[ $area_name ] = array(
						'zip'  => $zip_code,
						'type' => 'neighborhood',
					);
				}
			}
		}

		fclose( $handle );

		// Load landmarks from attractions CSV
		$landmarks_before = count( $locations );
		$this->loadLandmarks( $locations, $city_zips, 'san_diego_county_landmarks_attractions.csv' );
		$landmarks_added = count( $locations ) - $landmarks_before;
		error_log( '[GeoTitles] Landmarks loaded: ' . $landmarks_added );

		// Add zip codes as separate locations
		$unique_zips = array();
		foreach ( $locations as $location_data ) {
			if ( ! empty( $location_data['zip'] ) ) {
				$unique_zips[] = $location_data['zip'];
			}
		}
		$unique_zips = array_unique( $unique_zips );

		foreach ( $unique_zips as $zip ) {
			if ( ! empty( $zip ) && ! isset( $locations[ $zip ] ) ) {
				// Add zip code as a location with itself as the "zip code"
				$locations[ $zip ] = array(
					'zip'  => $zip,
					'type' => 'zipcode',
				);
			}
		}

		// Count by type for debugging
		$type_counts = array(
			'neighborhood' => 0,
			'landmark'     => 0,
			'zipcode'      => 0,
		);
		foreach ( $locations as $location_data ) {
			$type = $location_data['type'] ?? 'unknown';
			if ( isset( $type_counts[ $type ] ) ) {
				$type_counts[ $type ]++;
			}
		}
		error_log( '[GeoTitles] Final location counts: ' . wp_json_encode( $type_counts ) );

		// Sort alphabetically by key (using SORT_STRING to maintain string keys).
		ksort( $locations, SORT_STRING );

		// Debug: Log first few entries to verify structure
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$sample = array_slice( $locations, 0, 3, true );
			error_log( '[GeoTitles] Loaded locations sample: ' . wp_json_encode( $sample ) );
			error_log( '[GeoTitles] Total locations loaded: ' . count( $locations ) );
		}

		return $locations;
	}

	/**
	 * Load landmarks from CSV and add to locations array.
	 *
	 * @param array  $locations  Reference to locations array.
	 * @param array  $city_zips  City name to zip code mapping.
	 * @param string $filename   Landmark CSV filename.
	 * @return void
	 */
	private function loadLandmarks( array &$locations, array $city_zips, string $filename ): void {
		$csv_file = $this->data_dir . $filename;

		if ( ! file_exists( $csv_file ) ) {
			error_log( '[GeoTitles] Landmarks file not found: ' . $csv_file );
			return;
		}

		$handle = fopen( $csv_file, 'r' );
		if ( false === $handle ) {
			error_log( '[GeoTitles] Failed to open landmarks file: ' . $csv_file );
			return;
		}

		// Skip header row.
		fgetcsv( $handle );

		$attempted = 0;
		$added = 0;
		$no_zip = 0;

		// Read landmark data.
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( count( $row ) >= 2 ) {
				$landmark_name = trim( $row[1] ); // Column 2 is Name
				$location_str  = isset( $row[2] ) ? trim( $row[2] ) : ''; // Column 3 is Location

				if ( empty( $landmark_name ) ) {
					continue;
				}

				$attempted++;

				// Try to extract zip code from location string
				$zip_code = $this->extractZipFromLocation( $location_str, $city_zips );

				if ( empty( $zip_code ) ) {
					$no_zip++;
					if ( $no_zip <= 5 ) { // Log first 5 failures
						error_log( '[GeoTitles] No zip for landmark: ' . $landmark_name . ' (location: ' . $location_str . ')' );
					}
				}

				// Add landmark if we found a zip code and it's not already in the list
				if ( ! empty( $zip_code ) && ! isset( $locations[ $landmark_name ] ) ) {
					$locations[ $landmark_name ] = array(
						'zip'  => $zip_code,
						'type' => 'landmark',
					);
					$added++;
				}
			}
		}

		error_log( '[GeoTitles] Landmarks - Attempted: ' . $attempted . ', Added: ' . $added . ', No zip: ' . $no_zip );

		fclose( $handle );
	}

	/**
	 * Extract zip code from a location string by matching city names.
	 *
	 * @param string $location_str Location string (e.g., "Balboa Park, San Diego").
	 * @param array  $city_zips    City name to zip code mapping.
	 * @return string Zip code or empty string if not found.
	 */
	private function extractZipFromLocation( string $location_str, array $city_zips ): string {
		if ( empty( $location_str ) ) {
			return '';
		}

		$location_lower = strtolower( $location_str );

		// Check each known city to see if it's mentioned in the location string
		foreach ( $city_zips as $city_name => $zip_code ) {
			if ( false !== strpos( $location_lower, $city_name ) ) {
				return $zip_code;
			}
		}

		// Default to San Diego downtown if no city found
		return '92101';
	}

	/**
	 * Load urgent words from CSV file.
	 *
	 * @return array Array of urgent words.
	 */
	private function loadUrgentWords(): array {
		$urgent_words = array();
		$csv_file     = $this->data_dir . 'urgent_words.csv';

		if ( ! file_exists( $csv_file ) ) {
			error_log( '[GeoTitles] Urgent words file not found: ' . $csv_file );
			return $urgent_words;
		}

		$handle = fopen( $csv_file, 'r' );
		if ( false === $handle ) {
			error_log( '[GeoTitles] Failed to open urgent words file: ' . $csv_file );
			return $urgent_words;
		}

		// Skip header row.
		fgetcsv( $handle );

		// Read urgent words.
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( ! empty( $row[0] ) ) {
				$urgent_words[] = trim( $row[0] );
			}
		}

		fclose( $handle );

		error_log( '[GeoTitles] Loaded ' . count( $urgent_words ) . ' urgent words' );

		return $urgent_words;
	}

	/**
	 * Check if titles already exist in WordPress database.
	 *
	 * Checks against both post titles and slugs to detect duplicates.
	 *
	 * @param array $titles Array of title items with 'title' and 'slug' keys.
	 * @return array Array of slugs that already exist.
	 */
	private function checkDuplicateTitles( array $titles ): array {
		global $wpdb;

		$duplicates = array();

		// Extract all slugs for batch checking.
		$slugs = array_column( $titles, 'slug' );

		if ( empty( $slugs ) ) {
			return $duplicates;
		}

		error_log( '[GeoTitles] Checking ' . count( $slugs ) . ' slugs for duplicates' );
		error_log( '[GeoTitles] First 5 slugs: ' . wp_json_encode( array_slice( $slugs, 0, 5 ) ) );

		// Prepare placeholders for IN clause.
		$placeholders = implode( ', ', array_fill( 0, count( $slugs ), '%s' ) );

		// Query to check for existing posts with same slug or title.
		// Check both seo-page and regular posts.
		$query = $wpdb->prepare(
			"SELECT post_name, post_title
			FROM {$wpdb->posts}
			WHERE post_status IN ('publish', 'draft', 'pending', 'future')
			AND (post_name IN ($placeholders) OR post_title IN ($placeholders))
			AND post_type IN ('seo-page', 'post', 'page')",
			array_merge( $slugs, array_column( $titles, 'title' ) )
		);

		$existing_posts = $wpdb->get_results( $query );
		error_log( '[GeoTitles] Found ' . count( $existing_posts ) . ' existing posts' );
		if ( ! empty( $existing_posts ) ) {
			error_log( '[GeoTitles] Existing posts: ' . wp_json_encode( $existing_posts ) );
		}

		// Build array of existing slugs and titles.
		foreach ( $existing_posts as $post ) {
			$duplicates[] = $post->post_name;
			// Also add by title match.
			foreach ( $titles as $title_item ) {
				if ( $title_item['title'] === $post->post_title ) {
					$duplicates[] = $title_item['slug'];
				}
			}
		}

		$unique_duplicates = array_unique( $duplicates );
		error_log( '[GeoTitles] Total duplicates found: ' . count( $unique_duplicates ) );

		return $unique_duplicates;
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
		<style>
			/* Glass/Neumorphism Design for Geographic Title Generator */
			.wrap.seo-generator-page {
				background: linear-gradient(135deg, #FEF9F4 0%, #f5ede3 50%, #FEF9F4 100%);
				margin-left: -20px;
				margin-right: 0;
				padding: 40px;
				min-height: 100vh;
				position: relative;
			}

			.wrap.seo-generator-page::before {
				content: '';
				position: absolute;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
				background:
					radial-gradient(circle at 20% 30%, rgba(202, 150, 82, 0.05) 0%, transparent 50%),
					radial-gradient(circle at 80% 70%, rgba(188, 140, 77, 0.05) 0%, transparent 50%);
				pointer-events: none;
			}

			.wrap.seo-generator-page .heading-1 {
				color: #272521;
				font-size: 42px;
				font-weight: 300;
				letter-spacing: -0.5px;
				margin-bottom: 30px;
				text-transform: uppercase;
				font-family: 'Cormorant', serif;
				position: relative;
				z-index: 1;
				text-align: center;
			}

			.wrap.seo-generator-page .heading-1::after {
				content: '';
				display: block;
				width: 60px;
				height: 3px;
				background: linear-gradient(90deg, #CA9652, transparent);
				margin: 12px auto 0;
				border-radius: 2px;
			}

			.seo-geo-titles-container {
				max-width: 1200px;
				margin: 0 auto;
				position: relative;
				z-index: 1;
			}

			.seo-card {
				background: rgba(255, 255, 255, 0.6);
				backdrop-filter: blur(20px);
				-webkit-backdrop-filter: blur(20px);
				border-radius: 24px;
				box-shadow:
					0 8px 32px rgba(202, 150, 82, 0.08),
					inset 0 1px 0 rgba(255, 255, 255, 0.9),
					inset 0 -1px 0 rgba(202, 150, 82, 0.1);
				border: 1px solid rgba(255, 255, 255, 0.4);
				overflow: hidden;
				transition: all 0.3s ease;
				margin-top: 24px;
			}

			.seo-card:hover {
				box-shadow:
					0 12px 48px rgba(202, 150, 82, 0.12),
					inset 0 1px 0 rgba(255, 255, 255, 1),
					inset 0 -1px 0 rgba(202, 150, 82, 0.15);
				transform: translateY(-2px);
			}

			.seo-card__title {
				background: rgba(202, 150, 82, 0.08);
				backdrop-filter: blur(10px);
				-webkit-backdrop-filter: blur(10px);
				color: #272521;
				padding: 20px 28px;
				margin: 0;
				font-size: 20px;
				font-weight: 500;
				letter-spacing: -0.3px;
				border-bottom: 1px solid rgba(202, 150, 82, 0.15);
			}

			.seo-card__content {
				padding: 28px;
			}

			/* Glass Buttons */
			.button, .button-primary {
				border-radius: 14px !important;
				padding: 11px 24px !important;
				font-size: 14px !important;
				font-weight: 500 !important;
				transition: all 0.25s ease !important;
				backdrop-filter: blur(10px) !important;
				-webkit-backdrop-filter: blur(10px) !important;
				border: 1px solid rgba(202, 150, 82, 0.2) !important;
			}

			.button-primary {
				background: rgba(202, 150, 82, 0.15) !important;
				color: #8C6839 !important;
				box-shadow:
					0 4px 16px rgba(202, 150, 82, 0.15),
					inset 0 1px 0 rgba(255, 255, 255, 0.4) !important;
			}

			.button-primary:hover {
				background: rgba(202, 150, 82, 0.25) !important;
				box-shadow:
					0 6px 24px rgba(202, 150, 82, 0.2),
					inset 0 1px 0 rgba(255, 255, 255, 0.5) !important;
				transform: translateY(-1px) !important;
				color: #644A29 !important;
			}

			.button:not(.button-primary) {
				background: rgba(255, 255, 255, 0.5) !important;
				color: #8C6839 !important;
				box-shadow:
					0 4px 12px rgba(0, 0, 0, 0.05),
					inset 0 1px 0 rgba(255, 255, 255, 0.6) !important;
			}

			.button:not(.button-primary):hover {
				background: rgba(255, 255, 255, 0.7) !important;
				box-shadow:
					0 6px 16px rgba(0, 0, 0, 0.08),
					inset 0 1px 0 rgba(255, 255, 255, 0.8) !important;
				transform: translateY(-1px) !important;
			}

			/* Glass Input Fields */
			.regular-text, #search-titles {
				border-radius: 14px !important;
				border: 1px solid rgba(202, 150, 82, 0.2) !important;
				padding: 11px 18px !important;
				font-size: 14px !important;
				transition: all 0.25s !important;
				background: rgba(255, 255, 255, 0.6) !important;
				backdrop-filter: blur(10px) !important;
				-webkit-backdrop-filter: blur(10px) !important;
				box-shadow:
					inset 0 1px 3px rgba(0, 0, 0, 0.05),
					0 1px 0 rgba(255, 255, 255, 0.5) !important;
			}

			.regular-text:focus, #search-titles:focus {
				border-color: rgba(202, 150, 82, 0.4) !important;
				background: rgba(255, 255, 255, 0.8) !important;
				box-shadow:
					0 0 0 3px rgba(202, 150, 82, 0.08),
					inset 0 1px 3px rgba(0, 0, 0, 0.05),
					0 1px 0 rgba(255, 255, 255, 0.6) !important;
				outline: none !important;
			}

			/* Glass Filter Checkboxes */
			label[id$="-container"] {
				background: rgba(255, 255, 255, 0.5);
				backdrop-filter: blur(10px);
				-webkit-backdrop-filter: blur(10px);
				border: 1px solid rgba(202, 150, 82, 0.2);
				border-radius: 14px;
				padding: 10px 18px !important;
				transition: all 0.25s;
				cursor: pointer;
				box-shadow:
					0 4px 12px rgba(0, 0, 0, 0.04),
					inset 0 1px 0 rgba(255, 255, 255, 0.6);
			}

			label[id$="-container"]:hover {
				background: rgba(202, 150, 82, 0.08);
				border-color: rgba(202, 150, 82, 0.3);
				box-shadow:
					0 6px 16px rgba(202, 150, 82, 0.1),
					inset 0 1px 0 rgba(255, 255, 255, 0.7);
			}

			label[id$="-container"] input[type="checkbox"] {
				width: 18px;
				height: 18px;
				accent-color: #CA9652;
				cursor: pointer;
			}

			label[id$="-container"] span {
				color: #272521;
				font-weight: 500;
				font-size: 14px;
			}

			/* Glass Results Table */
			#titles-list {
				border: 1px solid rgba(202, 150, 82, 0.15) !important;
				border-radius: 20px !important;
				padding: 20px !important;
				background: rgba(255, 255, 255, 0.4) !important;
				backdrop-filter: blur(20px) !important;
				-webkit-backdrop-filter: blur(20px) !important;
				box-shadow:
					0 8px 32px rgba(202, 150, 82, 0.06),
					inset 0 1px 0 rgba(255, 255, 255, 0.8) !important;
			}

			#titles-list table {
				border-collapse: separate;
				border-spacing: 0;
				width: 100%;
			}

			#titles-list thead {
				background: rgba(202, 150, 82, 0.1);
				backdrop-filter: blur(10px);
				-webkit-backdrop-filter: blur(10px);
			}

			#titles-list thead th {
				color: #644A29 !important;
				padding: 14px 10px !important;
				font-weight: 600 !important;
				text-transform: uppercase;
				font-size: 12px;
				letter-spacing: 0.5px;
				border-bottom: 1px solid rgba(202, 150, 82, 0.15) !important;
			}

			#titles-list tbody tr {
				transition: all 0.2s;
				background: rgba(255, 255, 255, 0.3);
			}

			#titles-list tbody tr:hover {
				background: rgba(202, 150, 82, 0.05);
			}

			#titles-list tbody tr:nth-child(even) {
				background: rgba(254, 249, 244, 0.3);
			}

			#titles-list tbody tr:nth-child(even):hover {
				background: rgba(202, 150, 82, 0.08);
			}

			#titles-list tbody td {
				padding: 12px 10px !important;
				color: #272521;
				font-size: 13px;
				border-bottom: 1px solid rgba(202, 150, 82, 0.08);
			}

			/* Glass Pagination */
			.pagination-btn {
				background: rgba(255, 255, 255, 0.5) !important;
				backdrop-filter: blur(10px) !important;
				-webkit-backdrop-filter: blur(10px) !important;
				border: 1px solid rgba(202, 150, 82, 0.2) !important;
				color: #8C6839 !important;
				border-radius: 12px !important;
				padding: 8px 14px !important;
				margin: 0 3px !important;
				transition: all 0.25s !important;
				font-weight: 500 !important;
				font-size: 13px !important;
				box-shadow:
					0 2px 8px rgba(0, 0, 0, 0.04),
					inset 0 1px 0 rgba(255, 255, 255, 0.6) !important;
			}

			.pagination-btn:hover:not(:disabled) {
				background: rgba(202, 150, 82, 0.12) !important;
				border-color: rgba(202, 150, 82, 0.3) !important;
				box-shadow:
					0 4px 12px rgba(202, 150, 82, 0.1),
					inset 0 1px 0 rgba(255, 255, 255, 0.7) !important;
			}

			.pagination-btn.active {
				background: rgba(202, 150, 82, 0.15) !important;
				color: #644A29 !important;
				border-color: rgba(202, 150, 82, 0.3) !important;
				box-shadow:
					0 4px 16px rgba(202, 150, 82, 0.15),
					inset 0 1px 0 rgba(255, 255, 255, 0.5) !important;
			}

			.pagination-btn:disabled {
				opacity: 0.4;
				cursor: not-allowed;
			}

			/* Glass Status Messages */
			#generation-status, #upload-status {
				border-radius: 14px;
				padding: 14px 20px;
				margin-top: 14px;
				backdrop-filter: blur(10px);
				-webkit-backdrop-filter: blur(10px);
			}

			#generation-status.success, #upload-status.success {
				background: rgba(202, 150, 82, 0.08);
				border: 1px solid rgba(202, 150, 82, 0.2);
				color: #644A29;
				box-shadow:
					0 4px 12px rgba(202, 150, 82, 0.08),
					inset 0 1px 0 rgba(255, 255, 255, 0.6);
			}

			/* Code blocks */
			code, pre {
				background: rgba(255, 255, 255, 0.6) !important;
				backdrop-filter: blur(10px) !important;
				-webkit-backdrop-filter: blur(10px) !important;
				border: 1px solid rgba(202, 150, 82, 0.15) !important;
				border-radius: 8px !important;
				padding: 4px 8px !important;
				font-family: 'Monaco', 'Menlo', monospace !important;
				color: #8C6839 !important;
				box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05) !important;
			}

			/* Total Count Display */
			#total-count {
				color: #CA9652;
				font-weight: 700;
				font-size: 17px;
			}

			/* Subtle animations */
			@keyframes float {
				0%, 100% { transform: translateY(0px); }
				50% { transform: translateY(-3px); }
			}

			.seo-card {
				animation: float 6s ease-in-out infinite;
			}

			.seo-card:nth-child(2) {
				animation-delay: 1s;
			}

			.seo-card:nth-child(3) {
				animation-delay: 2s;
			}
		</style>
		<div class="wrap seo-generator-page">
			<h1 class="heading-1"><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="seo-geo-titles-container" style="max-width: 1200px;">
				<!-- Upload Section -->
				<div class="seo-card mt-6">
					<h3 class="seo-card__title">📁 Step 1: Upload Product Data CSV</h3>
					<div class="seo-card__content">
						<p>Upload a CSV file with product attributes. The system will generate page titles using the pattern: <code>[style] [stone] [product] [metal] + [location]</code></p>

						<div style="background: #f9f9f9; padding: 15px; border-left: 3px solid #2271b1; margin: 15px 0;">
							<strong>Required CSV Format:</strong>
							<pre style="margin: 10px 0; font-size: 13px;">style,stone,product,metal
engagement,diamond,ring,platinum
wedding,sapphire,necklace,gold
,emerald,earrings,
vintage,ruby,bracelet,silver</pre>
							<small><strong>Note:</strong> Empty columns are OK! The system will skip missing attributes.</small>
						</div>

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
						<button id="clear-cache-btn" class="button" style="margin-top: 15px; margin-left: 10px;">Clear Location Cache</button>
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
							<button id="send-to-import-btn" class="button button-primary" style="margin-left: 10px;">Send to Import Page</button>
						</div>

						<div style="margin-bottom: 15px; display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
							<input type="text" id="search-titles" placeholder="Search titles..." class="regular-text" style="width: 300px;" />
							<label style="display: none; align-items: center; gap: 5px; cursor: pointer;" id="hide-duplicates-container">
								<input type="checkbox" id="hide-duplicates-checkbox" />
								<span>Hide duplicate titles (<span id="duplicate-count-label">0</span>)</span>
							</label>
							<label style="display: flex; align-items: center; gap: 5px; cursor: pointer;" id="landmarks-only-container">
								<input type="checkbox" id="landmarks-only-checkbox" />
								<span>Show landmarks only (<span id="landmarks-count-label">0</span>)</span>
							</label>
							<label style="display: flex; align-items: center; gap: 5px; cursor: pointer;" id="urgent-only-container">
								<input type="checkbox" id="urgent-only-checkbox" />
								<span>Show urgent words only (<span id="urgent-count-label">0</span>)</span>
							</label>
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

		// Filter out duplicates.
		if ( ! empty( $all_titles ) ) {
			$original_count = count( $all_titles );
			$duplicate_slugs = $this->checkDuplicateTitles( $all_titles );

			// Filter out duplicates from the titles array.
			$all_titles = array_filter(
				$all_titles,
				function ( $title ) use ( $duplicate_slugs ) {
					return ! in_array( $title['slug'], $duplicate_slugs, true );
				}
			);
			$all_titles = array_values( $all_titles ); // Re-index array

			$filtered_count = $original_count - count( $all_titles );
			error_log( '[GeoTitles] CSV Export: Filtered out ' . $filtered_count . ' duplicates (' . $original_count . ' -> ' . count( $all_titles ) . ')' );
		}

		if ( empty( $all_titles ) ) {
			wp_send_json_error( array( 'message' => 'No titles to export' ) );
		}

		// Create CSV content with all columns.
		$csv_content = "Title,Slug,Stone,Product,Style,Metal,Location,Landmarks,Zip Code,Urgent\n";
		foreach ( $all_titles as $item ) {
			// Escape CSV fields properly.
			$title = str_replace( '"', '""', $item['title'] );
			$slug = str_replace( '"', '""', $item['slug'] );
			$stone = str_replace( '"', '""', $item['stone'] ?? '' );
			$product = str_replace( '"', '""', $item['product'] ?? '' );
			$style = str_replace( '"', '""', $item['style'] ?? '' );
			$metal = str_replace( '"', '""', $item['metal'] ?? '' );

			// Separate location and landmarks based on type.
			$is_landmark = isset( $item['locationType'] ) && 'landmark' === $item['locationType'];
			$location = $is_landmark ? '' : str_replace( '"', '""', $item['location'] ?? '' );
			$landmark = $is_landmark ? str_replace( '"', '""', $item['location'] ?? '' ) : '';
			$zip = str_replace( '"', '""', $item['zip'] ?? '' );
			$urgent = str_replace( '"', '""', $item['urgent'] ?? '' );

			$csv_content .= "\"$title\",\"$slug\",\"$stone\",\"$product\",\"$style\",\"$metal\",\"$location\",\"$landmark\",\"$zip\",\"$urgent\"\n";
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
	 * Handle sending geographic titles directly to the import page.
	 *
	 * Creates a CSV file in the uploads directory and stores the path in a transient,
	 * allowing the Import page to load it automatically.
	 *
	 * @return void
	 */
	public function handleSendToImport(): void {
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

		// Filter out duplicates.
		if ( ! empty( $all_titles ) ) {
			$original_count = count( $all_titles );
			$duplicate_slugs = $this->checkDuplicateTitles( $all_titles );

			// Filter out duplicates from the titles array.
			$all_titles = array_filter(
				$all_titles,
				function ( $title ) use ( $duplicate_slugs ) {
					return ! in_array( $title['slug'], $duplicate_slugs, true );
				}
			);
			$all_titles = array_values( $all_titles ); // Re-index array

			$filtered_count = $original_count - count( $all_titles );
			error_log( '[GeoTitles] Send to Import: Filtered out ' . $filtered_count . ' duplicates (' . $original_count . ' -> ' . count( $all_titles ) . ')' );
		}

		if ( empty( $all_titles ) ) {
			wp_send_json_error( array( 'message' => 'No titles to send' ) );
		}

		// Create CSV content with keyword column (for import page compatibility).
		$csv_content = "keyword\n";
		foreach ( $all_titles as $item ) {
			// Escape CSV field properly.
			$title = str_replace( '"', '""', $item['title'] );
			$csv_content .= "\"$title\"\n";
		}

		// Save to uploads directory.
		$upload_dir = wp_upload_dir();
		$filename   = 'geo_titles_import_' . time() . '.csv';
		$file_path  = $upload_dir['path'] . '/' . $filename;

		// Write the file.
		$result = file_put_contents( $file_path, $csv_content );

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => 'Failed to save CSV file to uploads directory' ) );
		}

		// Store file path in transient for Import page to pick up.
		$user_id       = get_current_user_id();
		$transient_key = 'import_file_' . $user_id;
		set_transient( $transient_key, $file_path, HOUR_IN_SECONDS );

		// Get the Import page URL.
		$import_url = admin_url( 'admin.php?page=seo-import-keywords' );

		wp_send_json_success(
			array(
				'message'    => 'Titles saved successfully. Redirecting to Import page...',
				'count'      => count( $all_titles ),
				'file_path'  => $file_path,
				'import_url' => $import_url,
			)
		);
	}

	/**
	 * Generate all title combinations without pagination.
	 *
	 * @param array  $data      Array of keywords or structured data.
	 * @param array  $locations Array of locations.
	 * @param string $search    Optional search filter.
	 * @return array Array of all title combinations.
	 */
	private function generateAllTitleCombinations( array $data, array $locations, string $search = '' ): array {
		$titles = array();
		$prepositions = array( 'In', 'Near', 'Within' );

		// Load urgent words for optional title variations.
		$urgent_words = $this->loadUrgentWords();
		$urgent_word_count = count( $urgent_words );

		foreach ( $data as $item ) {
			$mode = isset( $item['mode'] ) ? $item['mode'] : 'simple';

			if ( 'structured' === $mode ) {
				// Structured mode: [style] [stone] [product] [metal] + [preposition] + [location]
				foreach ( $locations as $location => $location_data ) {
					foreach ( $prepositions as $prep ) {
						$title_parts = array();

						if ( ! empty( $item['style'] ) ) {
							$title_parts[] = $item['style'];
						}
						if ( ! empty( $item['stone'] ) ) {
							$title_parts[] = $item['stone'];
						}
						if ( ! empty( $item['product'] ) ) {
							$title_parts[] = $item['product'];
						}
						if ( ! empty( $item['metal'] ) ) {
							$title_parts[] = $item['metal'];
						}

						$title_parts[] = $prep;
						$title_parts[] = $location;

						$title = implode( ' ', $title_parts );

						// Randomly add urgent word (50% chance) if urgent words are available.
						$urgent_word = '';
						if ( $urgent_word_count > 0 && rand( 0, 1 ) === 1 ) {
							$urgent_word = $urgent_words[ array_rand( $urgent_words ) ];
							// 80% chance to place after (SEO optimal), 20% chance to place before.
							if ( rand( 1, 100 ) <= 80 ) {
								$title = $title . ', ' . $urgent_word;
							} else {
								$title = $urgent_word . ': ' . $title;
							}
						}

						$slug = sanitize_title( $title );

						// Apply search filter if provided.
						if ( ! empty( $search ) ) {
							if ( stripos( $title, $search ) === false && stripos( $slug, $search ) === false ) {
								continue;
							}
						}

						$titles[] = array(
							'title'    => $title,
							'slug'     => $slug,
							'stone'    => ! empty( $item['stone'] ) ? $item['stone'] : '',
							'product'  => ! empty( $item['product'] ) ? $item['product'] : '',
							'style'    => ! empty( $item['style'] ) ? $item['style'] : '',
							'metal'    => ! empty( $item['metal'] ) ? $item['metal'] : '',
							'location'     => $location,
							'zip'          => $location_data['zip'],
							'locationType' => $location_data['type'],
							'urgent'       => $urgent_word,
						);
					}
				}
			} else {
				// Simple mode: [keyword] + [preposition] + [location]
				$keyword = isset( $item['keyword'] ) ? trim( $item['keyword'] ) : '';

				if ( empty( $keyword ) ) {
					continue;
				}

				foreach ( $locations as $location => $location_data ) {
					foreach ( $prepositions as $prep ) {
						$title = $keyword . ' ' . $prep . ' ' . $location;

						// Randomly add urgent word (50% chance) if urgent words are available.
						$urgent_word = '';
						if ( $urgent_word_count > 0 && rand( 0, 1 ) === 1 ) {
							$urgent_word = $urgent_words[ array_rand( $urgent_words ) ];
							// 80% chance to place after (SEO optimal), 20% chance to place before.
							if ( rand( 1, 100 ) <= 80 ) {
								$title = $title . ', ' . $urgent_word;
							} else {
								$title = $urgent_word . ': ' . $title;
							}
						}

						$slug = sanitize_title( $title );

						// Apply search filter if provided.
						if ( ! empty( $search ) ) {
							if ( stripos( $title, $search ) === false && stripos( $slug, $search ) === false ) {
								continue;
							}
						}

						$titles[] = array(
							'title'    => $title,
							'slug'     => $slug,
							'stone'    => '',
							'product'  => $keyword,
							'style'    => '',
							'metal'    => '',
							'location'     => $location,
							'zip'          => $location_data['zip'],
							'locationType' => $location_data['type'],
							'urgent'       => $urgent_word,
						);
					}
				}
			}
		}

		return $titles;
	}
}
