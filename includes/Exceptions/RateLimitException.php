<?php
/**
 * Rate Limit Exception
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Exceptions;

defined( 'ABSPATH' ) || exit;

/**
 * Exception thrown when OpenAI API rate limit is exceeded.
 */
class RateLimitException extends OpenAIException {
	/**
	 * Retry-After header value in seconds.
	 *
	 * @var int|null
	 */
	private ?int $retry_after;

	/**
	 * Constructor.
	 *
	 * @param string          $message Error message.
	 * @param int             $code Error code.
	 * @param \Throwable|null $previous Previous exception.
	 * @param int|null        $retry_after Retry-After value in seconds.
	 */
	public function __construct(
		string $message = 'API rate limit exceeded',
		int $code = 429,
		?\Throwable $previous = null,
		?int $retry_after = null
	) {
		parent::__construct( $message, $code, $previous, 429 );
		$this->retry_after = $retry_after;
	}

	/**
	 * Get retry-after value.
	 *
	 * @return int|null Seconds to wait before retrying.
	 */
	public function getRetryAfter(): ?int {
		return $this->retry_after;
	}
}
