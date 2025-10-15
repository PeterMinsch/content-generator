<?php
/**
 * Plugin Deactivation Handler
 *
 * @package SEOGenerator
 */

namespace SEOGenerator;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin deactivation tasks.
 */
class Deactivation {
	/**
	 * Plugin deactivation callback.
	 *
	 * Note: Does not delete data. Only cleanup tasks are performed.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Unschedule cron jobs.
		self::unscheduleCronJobs();

		// Flush rewrite rules to clean up custom post type permalinks.
		flush_rewrite_rules();

		// Clear any transients or cache.
		self::clearCache();
	}

	/**
	 * Unschedule cron jobs.
	 *
	 * @return void
	 */
	private static function unscheduleCronJobs(): void {
		// Unschedule log cleanup job.
		Cron\LogCleanup::unschedule();

		// Unschedule queue cleanup job.
		Cron\QueueCleanup::unschedule();

		// Unschedule import log cleanup job.
		Cron\ImportLogCleanup::unschedule();
	}

	/**
	 * Clear plugin caches and transients.
	 *
	 * @return void
	 */
	private static function clearCache(): void {
		global $wpdb;

		// Delete all plugin transients.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_seo_gen_' ) . '%'
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_timeout_seo_gen_' ) . '%'
			)
		);
	}
}
