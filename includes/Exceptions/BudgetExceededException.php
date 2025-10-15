<?php
/**
 * Budget Exceeded Exception
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Exceptions;

defined( 'ABSPATH' ) || exit;

/**
 * Exception thrown when monthly budget limit is exceeded.
 */
class BudgetExceededException extends \Exception {
	/**
	 * Current month's cost in USD.
	 *
	 * @var float
	 */
	private float $current_cost;

	/**
	 * Monthly budget limit in USD.
	 *
	 * @var float
	 */
	private float $budget_limit;

	/**
	 * Constructor.
	 *
	 * @param string          $message Message.
	 * @param float           $current_cost Current month's cost.
	 * @param float           $budget_limit Budget limit.
	 * @param int             $code Exception code.
	 * @param \Throwable|null $previous Previous exception.
	 */
	public function __construct(
		string $message,
		float $current_cost,
		float $budget_limit,
		int $code = 0,
		?\Throwable $previous = null
	) {
		parent::__construct( $message, $code, $previous );
		$this->current_cost  = $current_cost;
		$this->budget_limit = $budget_limit;
	}

	/**
	 * Get current month's cost.
	 *
	 * @return float Current cost in USD.
	 */
	public function getCurrentCost(): float {
		return $this->current_cost;
	}

	/**
	 * Get budget limit.
	 *
	 * @return float Budget limit in USD.
	 */
	public function getBudgetLimit(): float {
		return $this->budget_limit;
	}

	/**
	 * Get percentage of budget used.
	 *
	 * @return float Percentage (0-100+).
	 */
	public function getPercentageUsed(): float {
		if ( 0.0 === $this->budget_limit ) {
			return 0.0;
		}

		return round( ( $this->current_cost / $this->budget_limit ) * 100, 2 );
	}
}
