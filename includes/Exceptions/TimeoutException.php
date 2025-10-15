<?php
/**
 * Timeout Exception
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Exceptions;

defined( 'ABSPATH' ) || exit;

/**
 * Exception thrown when an operation times out.
 */
class TimeoutException extends OpenAIException {
	/**
	 * Constructor.
	 *
	 * @param string          $message Technical error message.
	 * @param int             $code Exception code.
	 * @param \Throwable|null $previous Previous exception.
	 */
	public function __construct(
		string $message = 'Operation timed out',
		int $code = 0,
		?\Throwable $previous = null
	) {
		parent::__construct( $message, $code, $previous );
	}
}
