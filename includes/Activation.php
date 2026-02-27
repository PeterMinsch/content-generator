<?php
/**
 * Plugin Activation Handler
 *
 * @package SEOGenerator
 */

namespace SEOGenerator;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin activation tasks.
 */
class Activation {
	/**
	 * Plugin activation callback.
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Check PHP version.
		if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
			wp_die(
				esc_html__( 'SEO Content Generator requires PHP 8.0 or higher.', 'seo-generator' ),
				esc_html__( 'Plugin Activation Error', 'seo-generator' ),
				array( 'back_link' => true )
			);
		}

		// Check WordPress version.
		if ( version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
			wp_die(
				esc_html__( 'SEO Content Generator requires WordPress 6.0 or higher.', 'seo-generator' ),
				esc_html__( 'Plugin Activation Error', 'seo-generator' ),
				array( 'back_link' => true )
			);
		}

		// Check if ACF is active.
		if ( ! class_exists( 'ACF' ) ) {
			wp_die(
				esc_html__( 'SEO Content Generator requires Advanced Custom Fields (ACF) plugin to be installed and activated.', 'seo-generator' ),
				esc_html__( 'Plugin Activation Error', 'seo-generator' ),
				array( 'back_link' => true )
			);
		}

		// Create database tables.
		self::createTables();

		// Add database indexes for performance.
		self::addDatabaseIndexes();

		// Create default taxonomy terms.
		self::createDefaultTerms();

		// Set default plugin options.
		self::setDefaultOptions();

		// Store plugin version.
		update_option( 'seo_generator_version', SEO_GENERATOR_VERSION );

		// Schedule cron jobs.
		self::scheduleCronJobs();

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Schedule cron jobs.
	 *
	 * @return void
	 */
	private static function scheduleCronJobs(): void {
		// Schedule generation log cleanup job.
		Cron\LogCleanup::schedule();

		// Schedule import log cleanup job (Story 6.7).
		if ( ! wp_next_scheduled( 'seo_cleanup_old_import_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'seo_cleanup_old_import_logs' );
		}
	}

	/**
	 * Create custom database tables.
	 *
	 * @return void
	 */
	private static function createTables(): void {
		// Create generation log table.
		self::createGenerationLogTable();

		// Create import log table (Story 6.7).
		self::createImportLogTable();

		// Create review cache table (Story 9.1).
		self::createReviewTable();

		// Create image cache table (AI Image Generator Bot).
		self::createImageCacheTable();

		// Create template table (Template Builder).
		self::createTemplateTable();
	}

	/**
	 * Create generation log table.
	 *
	 * @return void
	 */
	private static function createGenerationLogTable(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'seo_generation_log';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT(20) UNSIGNED NOT NULL,
			block_type VARCHAR(50) NOT NULL,
			prompt_tokens INT UNSIGNED NOT NULL DEFAULT 0,
			completion_tokens INT UNSIGNED NOT NULL DEFAULT 0,
			total_tokens INT UNSIGNED NOT NULL DEFAULT 0,
			cost DECIMAL(10,6) NOT NULL DEFAULT 0.000000,
			model VARCHAR(50) NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'success',
			error_message TEXT NULL,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX idx_post_id (post_id),
			INDEX idx_created_at (created_at),
			INDEX idx_cost_tracking (created_at, cost)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create import log table (Story 6.7).
	 *
	 * @return void
	 */
	private static function createImportLogTable(): void {
		$import_log_repository = new Repositories\ImportLogRepository();
		$import_log_repository->createTable();
	}

	/**
	 * Create default taxonomy terms.
	 *
	 * @return void
	 */
	private static function createDefaultTerms(): void {
		// Default SEO Topic terms.
		$seo_topics = array(
			'Engagement Rings',
			'Wedding Bands',
			"Men's Wedding Bands",
			"Women's Wedding Bands",
			'Education',
			'Comparisons',
		);

		foreach ( $seo_topics as $topic ) {
			if ( ! term_exists( $topic, Taxonomies\SEOTopic::TAXONOMY ) ) {
				wp_insert_term( $topic, Taxonomies\SEOTopic::TAXONOMY );
			}
		}

		// Default Image Tag terms.
		$image_tags = array(
			// Metals.
			'platinum',
			'gold',
			'white-gold',
			'rose-gold',
			'tungsten',
			'titanium',
			// Types.
			'wedding-band',
			'engagement-ring',
			'fashion',
			// Gender.
			'mens',
			'womens',
			'unisex',
			// Styles.
			'classic',
			'modern',
			'vintage',
			'minimalist',
			// Finishes.
			'polished',
			'brushed',
			'hammered',
			'matte',
		);

		foreach ( $image_tags as $tag ) {
			if ( ! term_exists( $tag, Taxonomies\ImageTag::TAXONOMY ) ) {
				wp_insert_term( $tag, Taxonomies\ImageTag::TAXONOMY );
			}
		}
	}

	/**
	 * Set default plugin options.
	 *
	 * @return void
	 */
	private static function setDefaultOptions(): void {
		// Migrate old setting key (Story 5.5 fix).
		self::migrateAutoAssignmentSetting();

		// Only add defaults if option doesn't exist (prevents overwriting on reactivation).
		if ( false === get_option( 'seo_generator_settings' ) ) {
			$defaults = array(
				// API Configuration (Story 2.1).
				'openai_api_key'       => '',
				'openai_model'         => 'gpt-4',
				'max_tokens'           => 2000,
				'temperature'          => 0.7,

				// Default Content (will be populated in future stories).
				'default_cta'          => '',
				'default_warranty'     => '',
				'default_care'         => '',

				// Prompt Templates (Story 2.2 - will add template defaults).
				'prompt_templates'     => array(),

				// Image Library (will be populated in future stories).
				'enable_auto_assignment' => true,
				'image_matching_mode'  => 'tag-based',

				// Review Integration (Story 9.x - Apify).
				'apify_api_token'      => '',
				'place_url'            => '',
				'max_reviews'          => 50,

				// Limits & Tracking (Story 2.5).
				'enable_cost_tracking'      => true,
				'monthly_budget'            => 100.00,
				'alert_threshold_percent'   => 80,
				'rate_limit_enabled'        => false,
				'rate_limit_per_hour'       => 10,
			);

			add_option( 'seo_generator_settings', $defaults );
		}
	}

	/**
	 * Migrate auto_assign_images to enable_auto_assignment.
	 *
	 * Fixes settings key mismatch bug from Story 5.5.
	 *
	 * @return void
	 */
	private static function migrateAutoAssignmentSetting(): void {
		$settings = get_option( 'seo_generator_settings', array() );

		// Check if old key exists and new key doesn't.
		if ( isset( $settings['auto_assign_images'] ) && ! isset( $settings['enable_auto_assignment'] ) ) {
			// Migrate to new key.
			$settings['enable_auto_assignment'] = $settings['auto_assign_images'];
			unset( $settings['auto_assign_images'] );

			// Save updated settings.
			update_option( 'seo_generator_settings', $settings );

			error_log( '[SEO Generator] Migrated auto_assign_images to enable_auto_assignment setting.' );
		}
	}

	/**
	 * Create review cache table (Story 9.1).
	 *
	 * @return void
	 */
	private static function createReviewTable(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'seo_reviews';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			source VARCHAR(50) NOT NULL DEFAULT 'google',
			external_review_id VARCHAR(255) NOT NULL,
			reviewer_name VARCHAR(255),
			reviewer_avatar_url VARCHAR(500),
			reviewer_profile_url VARCHAR(500),
			rating DECIMAL(2,1),
			review_text TEXT,
			review_date DATETIME,
			last_fetched_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY unique_review (source, external_review_id),
			INDEX idx_source (source),
			INDEX idx_last_fetched (last_fetched_at),
			INDEX idx_rating (rating)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Update database version.
		update_option( 'seo_generator_review_db_version', '1.0' );

		// Log table creation.
		error_log( '[SEO Generator] Review table created/updated' );
	}

	/**
	 * Create image cache table (AI Image Generator Bot).
	 *
	 * Stores cached AI-generated images to minimize API costs.
	 * Cache key is based on link_title + category for reuse across pages.
	 *
	 * @return void
	 */
	private static function createImageCacheTable(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'seo_image_cache';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			context_hash VARCHAR(32) NOT NULL,
			link_title VARCHAR(255) NOT NULL,
			link_category VARCHAR(100) NOT NULL,
			dalle_prompt TEXT NOT NULL,
			attachment_id BIGINT(20) UNSIGNED NOT NULL,
			usage_count INT UNSIGNED DEFAULT 1,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			last_used DATETIME DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY unique_context (context_hash),
			INDEX idx_category (link_category),
			INDEX idx_usage (usage_count),
			INDEX idx_last_used (last_used)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Update database version.
		update_option( 'seo_generator_image_cache_db_version', '1.0' );

		// Log table creation.
		error_log( '[SEO Generator] Image cache table created/updated' );
	}

	/**
	 * Create template table (Template Builder).
	 *
	 * @return void
	 */
	private static function createTemplateTable(): void {
		$template_repository = new Repositories\TemplateRepository();
		$template_repository->createTable();
	}

	/**
	 * Add database indexes for performance optimization.
	 *
	 * Adds indexes to wp_posts table to speed up queries filtering by post_type and post_status.
	 *
	 * @return void
	 */
	private static function addDatabaseIndexes(): void {
		global $wpdb;

		$table_name = $wpdb->posts;

		// Check if indexes already exist before adding them.
		$indexes = $wpdb->get_results( "SHOW INDEX FROM {$table_name}", ARRAY_A );
		$existing_indexes = array_column( $indexes, 'Key_name' );

		// Add composite index for post_type and post_status (commonly queried together).
		if ( ! in_array( 'idx_post_type_status', $existing_indexes, true ) ) {
			$wpdb->query( "ALTER TABLE {$table_name} ADD INDEX idx_post_type_status (post_type, post_status)" );
			error_log( '[SEO Generator] Added composite index idx_post_type_status to wp_posts table' );
		}

		// Add index for post_type (for queries filtering only by post_type).
		if ( ! in_array( 'idx_post_type', $existing_indexes, true ) && ! in_array( 'type_status_date', $existing_indexes, true ) ) {
			// WordPress may already have a similar index, check carefully.
			$has_post_type_index = false;
			foreach ( $indexes as $index ) {
				if ( 'post_type' === $index['Column_name'] && 0 === (int) $index['Seq_in_index'] ) {
					$has_post_type_index = true;
					break;
				}
			}

			if ( ! $has_post_type_index ) {
				$wpdb->query( "ALTER TABLE {$table_name} ADD INDEX idx_post_type (post_type)" );
				error_log( '[SEO Generator] Added index idx_post_type to wp_posts table' );
			}
		}

		error_log( '[SEO Generator] Database indexes checked/added successfully' );
	}
}
