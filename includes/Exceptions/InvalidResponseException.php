<?php
/**
 * Invalid Response Exception
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Exceptions;

defined( 'ABSPATH' ) || exit;

/**
 * Exception thrown when API response cannot be parsed or is invalid.
 */
class InvalidResponseException extends OpenAIException {
	/**
	 * Constructor.
	 *
	 * @param string          $message Technical error message.
	 * @param int             $code Exception code.
	 * @param \Throwable|null $previous Previous exception.
	 */
	public function __construct(
		string $message = 'Invalid API response',
		int $code = 0,
		?\Throwable $previous = null
	) {
		parent::__construct( $message, $code, $previous );
	}
}
