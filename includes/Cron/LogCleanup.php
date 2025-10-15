<?php
/**
 * Log Cleanup Cron Job
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Cron;

use SEOGenerator\Services\CostTrackingService;
use SEOGenerator\Repositories\GenerationLogRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Handles scheduled cleanup of old generation logs.
 */
class LogCleanup {
	/**
	 * Cron hook name.
	 */
	public const HOOK_NAME = 'seo_generator_cleanup_old_logs';

	/**
	 * Number of days to retain logs.
	 */
	private const RETENTION_DAYS = 30;

	/**
	 * Cost tracking service.
	 *
	 * @var CostTrackingService
	 */
	private CostTrackingService $cost_tracking;

	/**
	 * Constructor.
	 *
	 * @param CostTrackingService $cost_tracking Cost tracking service.
	 */
	public function __construct( CostTrackingService $cost_tracking ) {
		$this->cost_tracking = $cost_tracking;
	}

	/**
	 * Register cron hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( self::HOOK_NAME, array( $this, 'execute' ) );
	}

	/**
	 * Execute the cleanup job.
	 *
	 * @return void
	 */
	public function execute(): void {
		$deleted = $this->cost_tracking->cleanupOldLogs( self::RETENTION_DAYS );

		if ( $deleted > 0 ) {
			error_log(
				sprintf(
					'[SEO Generator Cron] Successfully cleaned up %d log entries',
					$deleted
				)
			);
		}
	}

	/**
	 * Schedule the cron job.
	 *
	 * @return void
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::HOOK_NAME ) ) {
			wp_schedule_event( time(), 'daily', self::HOOK_NAME );
			error_log( '[SEO Generator] Scheduled daily log cleanup cron job' );
		}
	}

	/**
	 * Unschedule the cron job.
	 *
	 * @return void
	 */
	public static function unschedule(): void {
		$timestamp = wp_next_scheduled( self::HOOK_NAME );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK_NAME );
			error_log( '[SEO Generator] Unscheduled log cleanup cron job' );
		}

		// Clear all instances of this hook.
		wp_clear_scheduled_hook( self::HOOK_NAME );
	}
}
