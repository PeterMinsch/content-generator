<?php
/**
 * Generation Result Data Transfer Object
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Represents the result of an OpenAI content generation request.
 */
class GenerationResult {
	/**
	 * Generated content.
	 *
	 * @var string
	 */
	private string $content;

	/**
	 * Number of prompt tokens used.
	 *
	 * @var int
	 */
	private int $prompt_tokens;

	/**
	 * Number of completion tokens used.
	 *
	 * @var int
	 */
	private int $completion_tokens;

	/**
	 * Total tokens used.
	 *
	 * @var int
	 */
	private int $total_tokens;

	/**
	 * Model used for generation.
	 *
	 * @var string
	 */
	private string $model;

	/**
	 * Constructor.
	 *
	 * @param string $content Generated content.
	 * @param int    $prompt_tokens Number of prompt tokens.
	 * @param int    $completion_tokens Number of completion tokens.
	 * @param int    $total_tokens Total tokens used.
	 * @param string $model Model used for generation.
	 */
	public function __construct(
		string $content,
		int $prompt_tokens,
		int $completion_tokens,
		int $total_tokens,
		string $model
	) {
		$this->content           = $content;
		$this->prompt_tokens     = $prompt_tokens;
		$this->completion_tokens = $completion_tokens;
		$this->total_tokens      = $total_tokens;
		$this->model             = $model;
	}

	/**
	 * Get the generated content.
	 *
	 * @return string
	 */
	public function getContent(): string {
		return $this->content;
	}

	/**
	 * Get the number of prompt tokens used.
	 *
	 * @return int
	 */
	public function getPromptTokens(): int {
		return $this->prompt_tokens;
	}

	/**
	 * Get the number of completion tokens used.
	 *
	 * @return int
	 */
	public function getCompletionTokens(): int {
		return $this->completion_tokens;
	}

	/**
	 * Get the total tokens used.
	 *
	 * @return int
	 */
	public function getTotalTokens(): int {
		return $this->total_tokens;
	}

	/**
	 * Get the model used for generation.
	 *
	 * @return string
	 */
	public function getModel(): string {
		return $this->model;
	}

	/**
	 * Convert to array for serialization.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'content'           => $this->content,
			'prompt_tokens'     => $this->prompt_tokens,
			'completion_tokens' => $this->completion_tokens,
			'total_tokens'      => $this->total_tokens,
			'model'             => $this->model,
		);
	}
}
