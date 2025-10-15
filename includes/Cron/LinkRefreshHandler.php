<?php
/**
 * Link Refresh Cron Handler
 *
 * Handles scheduled refresh of internal links.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Cron;

use SEOGenerator\Services\InternalLinkingService;

defined( 'ABSPATH' ) || exit;

/**
 * Link Refresh Handler
 *
 * Registers cron jobs and handles link refresh operations.
 */
class LinkRefreshHandler {

	/**
	 * Cron hook name
	 *
	 * @var string
	 */
	private const CRON_HOOK = 'seo_refresh_internal_links';

	/**
	 * InternalLinkingService instance
	 *
	 * @var InternalLinkingService
	 */
	private $linking_service;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->linking_service = new InternalLinkingService();
	}

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function register(): void {
		// Schedule cron event if not scheduled.
		add_action( 'init', array( $this, 'scheduleCronEvent' ) );

		// Register cron callback.
		add_action( self::CRON_HOOK, array( $this, 'handleCronRefresh' ) );

		// Clean up on plugin deactivation.
		register_deactivation_hook( SEO_GENERATOR_PLUGIN_FILE, array( $this, 'unscheduleCronEvent' ) );
	}

	/**
	 * Schedule cron event if not already scheduled
	 *
	 * @return void
	 */
	public function scheduleCronEvent(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			// Schedule weekly refresh (every Sunday at 3 AM).
			wp_schedule_event( strtotime( 'next Sunday 3:00 AM' ), 'weekly', self::CRON_HOOK );
			error_log( '[Internal Linking] Scheduled weekly link refresh cron job' );
		}
	}

	/**
	 * Unschedule cron event
	 *
	 * @return void
	 */
	public function unscheduleCronEvent(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
			error_log( '[Internal Linking] Unscheduled weekly link refresh cron job' );
		}
	}

	/**
	 * Handle cron refresh callback
	 *
	 * @return void
	 */
	public function handleCronRefresh(): void {
		error_log( '[Internal Linking] Starting scheduled link refresh' );

		$start_time = microtime( true );

		try {
			$summary = $this->linking_service->refreshAllLinks();

			$duration = round( microtime( true ) - $start_time, 2 );

			error_log(
				sprintf(
					'[Internal Linking] Scheduled refresh completed in %s seconds: %d pages processed, %d errors',
					$duration,
					$summary['processed'],
					$summary['errors']
				)
			);

			// Store last refresh summary for admin display.
			update_option(
				'seo_last_link_refresh',
				array(
					'timestamp' => time(),
					'duration'  => $duration,
					'summary'   => $summary,
				)
			);

		} catch ( \Exception $e ) {
			error_log( '[Internal Linking] Cron refresh failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Trigger immediate refresh (for manual execution)
	 *
	 * @return array Refresh summary.
	 */
	public function triggerManualRefresh(): array {
		error_log( '[Internal Linking] Manual refresh triggered' );

		$start_time = microtime( true );
		$summary    = $this->linking_service->refreshAllLinks();
		$duration   = round( microtime( true ) - $start_time, 2 );

		// Store last refresh summary.
		update_option(
			'seo_last_link_refresh',
			array(
				'timestamp' => time(),
				'duration'  => $duration,
				'summary'   => $summary,
			)
		);

		return array_merge( $summary, array( 'duration' => $duration ) );
	}

	/**
	 * Get last refresh info
	 *
	 * @return array|null Last refresh data or null.
	 */
	public function getLastRefreshInfo(): ?array {
		$data = get_option( 'seo_last_link_refresh' );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return null;
		}

		return $data;
	}

	/**
	 * Get next scheduled refresh time
	 *
	 * @return int|false Next scheduled timestamp or false.
	 */
	public function getNextScheduledTime() {
		return wp_next_scheduled( self::CRON_HOOK );
	}
}
