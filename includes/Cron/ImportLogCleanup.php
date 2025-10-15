<?php
/**
 * Import Log Cleanup Cron Job
 *
 * Handles automatic deletion of old import log records.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Cron;

use SEOGenerator\Repositories\ImportLogRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Import Log Cleanup Cron Job
 *
 * Runs daily to delete import log records older than retention period (default 90 days).
 * Retention period can be customized via filter hook.
 *
 * Usage:
 * ```php
 * // Customize retention period (default 90 days)
 * add_filter('seo_import_log_retention_days', function($days) {
 *     return 180; // Keep logs for 180 days
 * });
 * ```
 */
class ImportLogCleanup {
	/**
	 * Default retention period in days.
	 *
	 * @var int
	 */
	private const DEFAULT_RETENTION_DAYS = 90;

	/**
	 * ImportLogRepository instance.
	 *
	 * @var ImportLogRepository
	 */
	private $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository = new ImportLogRepository();
	}

	/**
	 * Register cron job hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'seo_cleanup_old_import_logs', array( $this, 'cleanup' ) );
	}

	/**
	 * Execute cleanup of old import logs.
	 *
	 * Called by WordPress cron system.
	 * Deletes import logs older than retention period.
	 *
	 * @return void
	 */
	public function cleanup(): void {
		// Get retention period (filterable).
		$retention_days = apply_filters( 'seo_import_log_retention_days', self::DEFAULT_RETENTION_DAYS );

		// Ensure reasonable minimum (at least 7 days).
		$retention_days = max( 7, intval( $retention_days ) );

		// Delete old logs.
		$deleted = $this->repository->deleteOlderThan( $retention_days );

		// Log cleanup activity.
		if ( $deleted > 0 ) {
			error_log(
				sprintf(
					'[SEO Generator] Import log cleanup: Deleted %d import log records older than %d days.',
					$deleted,
					$retention_days
				)
			);
		}
	}

	/**
	 * Schedule the cron job (called on plugin activation).
	 *
	 * @return void
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( 'seo_cleanup_old_import_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'seo_cleanup_old_import_logs' );
		}
	}

	/**
	 * Unschedule the cron job (called on plugin deactivation).
	 *
	 * @return void
	 */
	public static function unschedule(): void {
		$timestamp = wp_next_scheduled( 'seo_cleanup_old_import_logs' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'seo_cleanup_old_import_logs' );
		}
	}
}
