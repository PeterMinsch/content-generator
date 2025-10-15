<?php
/**
 * Cost Tracking Service
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

use SEOGenerator\Repositories\GenerationLogRepository;
use SEOGenerator\Exceptions\BudgetExceededException;

defined( 'ABSPATH' ) || exit;

/**
 * Manages cost tracking, budget limits, and generation logging.
 */
class CostTrackingService {
	/**
	 * Model pricing per 1K tokens (prompt / completion).
	 */
	private const MODEL_PRICING = array(
		'gpt-4-turbo-preview' => array(
			'prompt'     => 0.01,
			'completion' => 0.03,
		),
		'gpt-4'               => array(
			'prompt'     => 0.03,
			'completion' => 0.06,
		),
		'gpt-3.5-turbo'       => array(
			'prompt'     => 0.0015,
			'completion' => 0.002,
		),
	);

	/**
	 * Cache key for current month cost.
	 */
	private const CACHE_KEY_MONTH_COST = 'seo_gen_month_cost';

	/**
	 * Cache duration (5 minutes).
	 */
	private const CACHE_DURATION = 300;

	/**
	 * Repository instance.
	 *
	 * @var GenerationLogRepository
	 */
	private GenerationLogRepository $repository;

	/**
	 * Constructor.
	 *
	 * @param GenerationLogRepository $repository Repository instance.
	 */
	public function __construct( GenerationLogRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Log a generation request.
	 *
	 * @param array $log_data Log data.
	 * @return int Insert ID, or 0 on failure.
	 */
	public function logGeneration( array $log_data ): int {
		// Ensure created_at is set.
		if ( ! isset( $log_data['created_at'] ) ) {
			$log_data['created_at'] = current_time( 'mysql' );
		}

		$insert_id = $this->repository->insert( $log_data );

		// Clear cost cache after logging.
		wp_cache_delete( self::CACHE_KEY_MONTH_COST );

		// Check for budget alert after successful log.
		if ( $insert_id > 0 && 'success' === $log_data['status'] ) {
			$this->checkBudgetAlert();
		}

		return $insert_id;
	}

	/**
	 * Calculate cost based on token usage.
	 *
	 * @param int    $prompt_tokens Prompt tokens used.
	 * @param int    $completion_tokens Completion tokens used.
	 * @param string $model Model name.
	 * @return float Cost in USD.
	 */
	public function calculateCost( int $prompt_tokens, int $completion_tokens, string $model ): float {
		$pricing = self::MODEL_PRICING[ $model ] ?? self::MODEL_PRICING['gpt-4-turbo-preview'];

		$prompt_cost     = ( $prompt_tokens / 1000 ) * $pricing['prompt'];
		$completion_cost = ( $completion_tokens / 1000 ) * $pricing['completion'];

		return round( $prompt_cost + $completion_cost, 6 );
	}

	/**
	 * Get current month's total cost.
	 *
	 * @return float Total cost in USD.
	 */
	public function getCurrentMonthCost(): float {
		// Try to get from cache.
		$cached = wp_cache_get( self::CACHE_KEY_MONTH_COST );

		if ( false !== $cached ) {
			return (float) $cached;
		}

		// Query from database.
		$cost = $this->repository->getCurrentMonthCost();

		// Cache result.
		wp_cache_set( self::CACHE_KEY_MONTH_COST, $cost, '', self::CACHE_DURATION );

		return $cost;
	}

	/**
	 * Check if generation would exceed budget limit.
	 *
	 * @return void
	 * @throws BudgetExceededException If budget limit exceeded.
	 */
	public function checkBudgetLimit(): void {
		$settings = get_option( 'seo_generator_settings', array() );
		$enabled  = $settings['enable_cost_tracking'] ?? true;

		if ( ! $enabled ) {
			return;
		}

		$monthly_budget = (float) ( $settings['monthly_budget'] ?? 0 );

		// If budget is 0, no limit is enforced.
		if ( 0.0 === $monthly_budget ) {
			return;
		}

		$current_cost = $this->getCurrentMonthCost();

		if ( $current_cost >= $monthly_budget ) {
			throw new BudgetExceededException(
				sprintf(
					'Monthly budget limit reached ($%.2f of $%.2f). Please increase limit in Settings or wait until next month.',
					$current_cost,
					$monthly_budget
				),
				$current_cost,
				$monthly_budget
			);
		}
	}

	/**
	 * Check and send budget alert if threshold reached.
	 *
	 * @return void
	 */
	private function checkBudgetAlert(): void {
		$settings = get_option( 'seo_generator_settings', array() );
		$enabled  = $settings['enable_cost_tracking'] ?? true;

		if ( ! $enabled ) {
			return;
		}

		$monthly_budget     = (float) ( $settings['monthly_budget'] ?? 0 );
		$alert_threshold    = (int) ( $settings['alert_threshold_percent'] ?? 80 );

		// If budget is 0, no alerts.
		if ( 0.0 === $monthly_budget ) {
			return;
		}

		$current_cost = $this->getCurrentMonthCost();
		$percent_used = ( $current_cost / $monthly_budget ) * 100;

		if ( $percent_used >= $alert_threshold ) {
			$transient_key = 'seo_gen_budget_alert_' . gmdate( 'Y-m' );

			// Check if alert already sent this month.
			if ( ! get_transient( $transient_key ) ) {
				$this->sendBudgetAlert( $current_cost, $monthly_budget, $percent_used );
				set_transient( $transient_key, true, MONTH_IN_SECONDS );
			}
		}
	}

	/**
	 * Send budget alert email to admin.
	 *
	 * @param float $current_cost Current month's cost.
	 * @param float $monthly_budget Monthly budget limit.
	 * @param float $percent_used Percentage of budget used.
	 * @return void
	 */
	private function sendBudgetAlert( float $current_cost, float $monthly_budget, float $percent_used ): void {
		$to      = get_option( 'admin_email' );
		$subject = 'SEO Generator: Budget Alert';

		$message = sprintf(
			"Your SEO Content Generator has used %.1f%% of your monthly budget.\n\n" .
			"Current spend: $%.2f\n" .
			"Monthly budget: $%.2f\n" .
			"Remaining: $%.2f\n\n" .
			"Please review your usage or increase your budget limit in Settings.\n\n" .
			"View Settings: %s",
			$percent_used,
			$current_cost,
			$monthly_budget,
			$monthly_budget - $current_cost,
			admin_url( 'admin.php?page=seo-generator-settings' )
		);

		wp_mail( $to, $subject, $message );

		error_log(
			sprintf(
				'[SEO Generator] Budget alert sent: %.1f%% used ($%.2f / $%.2f)',
				$percent_used,
				$current_cost,
				$monthly_budget
			)
		);
	}

	/**
	 * Clean up old log entries.
	 *
	 * @param int $days Number of days to keep (default 30).
	 * @return int Number of logs deleted.
	 */
	public function cleanupOldLogs( int $days = 30 ): int {
		$old_logs = $this->repository->getOldLogs( $days );
		$ids      = array_column( $old_logs, 'id' );

		if ( empty( $ids ) ) {
			return 0;
		}

		$deleted = $this->repository->delete( $ids );

		if ( $deleted > 0 ) {
			error_log(
				sprintf(
					'[SEO Generator] Cleaned up %d log entries older than %d days',
					$deleted,
					$days
				)
			);
		}

		return $deleted;
	}

	/**
	 * Get statistics for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array Statistics.
	 */
	public function getPostStatistics( int $post_id ): array {
		return $this->repository->getPostStatistics( $post_id );
	}

	/**
	 * Get logs for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array Log entries.
	 */
	public function getPostLogs( int $post_id ): array {
		return $this->repository->getByPostId( $post_id );
	}

	/**
	 * Get success rate for recent generations.
	 *
	 * @param int $limit Number of recent generations to check (default 100).
	 * @return float Success rate percentage (0-100).
	 */
	public function getSuccessRate( int $limit = 100 ): float {
		global $wpdb;

		$table_name = $wpdb->prefix . 'seo_generation_log';

		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COUNT(*) as total,
					SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful
				FROM {$table_name}
				ORDER BY created_at DESC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		if ( ! $stats || 0 === (int) $stats['total'] ) {
			return 100.0;
		}

		$total      = (int) $stats['total'];
		$successful = (int) $stats['successful'];
		$rate       = ( $successful / $total ) * 100;

		// Log warning if success rate drops below 95%.
		if ( $rate < 95.0 ) {
			error_log(
				sprintf(
					'[SEO Generator] Warning: Success rate dropped to %.1f%% (%d/%d successful in last %d generations)',
					$rate,
					$successful,
					$total,
					$limit
				)
			);

			// Send admin notification if rate is critically low.
			if ( $rate < 80.0 ) {
				$this->sendLowSuccessRateAlert( $rate, $successful, $total );
			}
		}

		return round( $rate, 2 );
	}

	/**
	 * Send low success rate alert to admin.
	 *
	 * @param float $rate Success rate percentage.
	 * @param int   $successful Successful generations.
	 * @param int   $total Total generations.
	 * @return void
	 */
	private function sendLowSuccessRateAlert( float $rate, int $successful, int $total ): void {
		// Check if alert already sent today.
		$transient_key = 'seo_gen_low_success_alert_' . gmdate( 'Y-m-d' );

		if ( get_transient( $transient_key ) ) {
			return;
		}

		$to      = get_option( 'admin_email' );
		$subject = 'SEO Generator: Low Success Rate Alert';

		$message = sprintf(
			"Your SEO Content Generator has a low success rate.\n\n" .
			"Success rate: %.1f%%\n" .
			"Successful: %d/%d generations\n\n" .
			"This may indicate an issue with:\n" .
			"- OpenAI API connectivity\n" .
			"- API key validity\n" .
			"- Content parsing errors\n\n" .
			"Please review the error logs and consider investigating.\n\n" .
			"View Logs: %s",
			$rate,
			$successful,
			$total,
			admin_url( 'admin.php?page=seo-generator-settings' )
		);

		wp_mail( $to, $subject, $message );

		// Set transient to prevent duplicate alerts today.
		set_transient( $transient_key, true, DAY_IN_SECONDS );

		error_log(
			sprintf(
				'[SEO Generator] Low success rate alert sent: %.1f%% (%d/%d)',
				$rate,
				$successful,
				$total
			)
		);
	}
}
