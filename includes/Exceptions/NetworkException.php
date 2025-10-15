<?php
/**
 * Network Exception
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Exceptions;

defined( 'ABSPATH' ) || exit;

/**
 * Exception thrown when network connectivity issues occur.
 */
class NetworkException extends OpenAIException {
	/**
	 * Constructor.
	 *
	 * @param string          $message Technical error message.
	 * @param int             $code Exception code.
	 * @param \Throwable|null $previous Previous exception.
	 */
	public function __construct(
		string $message = 'Network connection failed',
		int $code = 0,
		?\Throwable $previous = null
	) {
		parent::__construct( $message, $code, $previous );
	}
}
