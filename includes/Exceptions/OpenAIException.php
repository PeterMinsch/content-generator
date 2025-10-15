<?php
/**
 * OpenAI Exception
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Exceptions;

defined( 'ABSPATH' ) || exit;

/**
 * Exception thrown when OpenAI API encounters an error.
 */
class OpenAIException extends \Exception {
	/**
	 * HTTP status code from API response.
	 *
	 * @var int|null
	 */
	private ?int $http_status;

	/**
	 * Response body from API.
	 *
	 * @var string|null
	 */
	private ?string $response_body;

	/**
	 * Constructor.
	 *
	 * @param string          $message Error message.
	 * @param int             $code Error code.
	 * @param \Throwable|null $previous Previous exception.
	 * @param int|null        $http_status HTTP status code.
	 * @param string|null     $response_body Response body.
	 */
	public function __construct(
		string $message = '',
		int $code = 0,
		?\Throwable $previous = null,
		?int $http_status = null,
		?string $response_body = null
	) {
		parent::__construct( $message, $code, $previous );
		$this->http_status   = $http_status;
		$this->response_body = $response_body;
	}

	/**
	 * Get HTTP status code.
	 *
	 * @return int|null
	 */
	public function getHttpStatus(): ?int {
		return $this->http_status;
	}

	/**
	 * Get response body.
	 *
	 * @return string|null
	 */
	public function getResponseBody(): ?string {
		return $this->response_body;
	}
}
