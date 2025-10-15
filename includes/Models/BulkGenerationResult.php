<?php
/**
 * Bulk Generation Result Model
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Represents the result of a bulk content generation operation.
 */
class BulkGenerationResult {
	/**
	 * Total number of blocks attempted.
	 *
	 * @var int
	 */
	private int $total_blocks;

	/**
	 * Number of successfully generated blocks.
	 *
	 * @var int
	 */
	private int $success_count;

	/**
	 * List of failed blocks with error messages.
	 *
	 * @var array
	 */
	private array $failed_blocks;

	/**
	 * Total tokens used across all blocks.
	 *
	 * @var int
	 */
	private int $total_tokens;

	/**
	 * Total cost in USD.
	 *
	 * @var float
	 */
	private float $total_cost;

	/**
	 * Total generation time in seconds.
	 *
	 * @var float
	 */
	private float $total_time;

	/**
	 * Constructor.
	 *
	 * @param int   $total_blocks Total blocks attempted.
	 * @param int   $success_count Successful blocks.
	 * @param array $failed_blocks Failed blocks with errors.
	 * @param int   $total_tokens Total tokens used.
	 * @param float $total_cost Total cost.
	 * @param float $total_time Total time in seconds.
	 */
	public function __construct(
		int $total_blocks,
		int $success_count,
		array $failed_blocks,
		int $total_tokens,
		float $total_cost,
		float $total_time
	) {
		$this->total_blocks  = $total_blocks;
		$this->success_count = $success_count;
		$this->failed_blocks = $failed_blocks;
		$this->total_tokens  = $total_tokens;
		$this->total_cost    = $total_cost;
		$this->total_time    = $total_time;
	}

	/**
	 * Get total blocks attempted.
	 *
	 * @return int
	 */
	public function getTotalBlocks(): int {
		return $this->total_blocks;
	}

	/**
	 * Get success count.
	 *
	 * @return int
	 */
	public function getSuccessCount(): int {
		return $this->success_count;
	}

	/**
	 * Get failed blocks.
	 *
	 * @return array
	 */
	public function getFailedBlocks(): array {
		return $this->failed_blocks;
	}

	/**
	 * Get total tokens.
	 *
	 * @return int
	 */
	public function getTotalTokens(): int {
		return $this->total_tokens;
	}

	/**
	 * Get total cost.
	 *
	 * @return float
	 */
	public function getTotalCost(): float {
		return $this->total_cost;
	}

	/**
	 * Get total time.
	 *
	 * @return float
	 */
	public function getTotalTime(): float {
		return $this->total_time;
	}

	/**
	 * Calculate success rate as percentage.
	 *
	 * @return float Success rate percentage (0-100).
	 */
	public function getSuccessRate(): float {
		if ( 0 === $this->total_blocks ) {
			return 0.0;
		}

		return round( ( $this->success_count / $this->total_blocks ) * 100, 2 );
	}

	/**
	 * Convert to array for serialization.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'totalBlocks'   => $this->total_blocks,
			'successCount'  => $this->success_count,
			'failedBlocks'  => $this->failed_blocks,
			'totalTokens'   => $this->total_tokens,
			'totalCost'     => $this->total_cost,
			'totalTime'     => $this->total_time,
			'successRate'   => $this->getSuccessRate(),
		);
	}
}
