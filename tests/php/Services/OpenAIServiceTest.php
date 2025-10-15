<?php
/**
 * Tests for OpenAI Service
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Services;

use SEOGenerator\Services\OpenAIService;
use SEOGenerator\Models\GenerationResult;
use SEOGenerator\Exceptions\OpenAIException;
use SEOGenerator\Exceptions\RateLimitException;
use WP_UnitTestCase;

/**
 * OpenAI Service test case.
 */
class OpenAIServiceTest extends WP_UnitTestCase {
	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Set up API key in settings.
		$encrypted_key = seo_generator_encrypt_api_key( 'test-api-key-12345' );
		update_option(
			'seo_generator_settings',
			array(
				'openai_api_key' => $encrypted_key,
			)
		);
	}

	/**
	 * Test successful content generation.
	 */
	public function test_successful_content_generation() {
		$service = new OpenAIService();

		// Mock successful API response.
		add_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10, 3 );

		$result = $service->generateContent( 'Generate content about wedding rings' );

		remove_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10 );

		$this->assertInstanceOf( GenerationResult::class, $result );
		$this->assertEquals( 'Generated content about wedding rings.', $result->getContent() );
		$this->assertEquals( 100, $result->getPromptTokens() );
		$this->assertEquals( 200, $result->getCompletionTokens() );
		$this->assertEquals( 300, $result->getTotalTokens() );
		$this->assertEquals( 'gpt-4-turbo-preview', $result->getModel() );
	}

	/**
	 * Test generation with custom options.
	 */
	public function test_generation_with_custom_options() {
		$service = new OpenAIService();

		// Mock successful API response.
		add_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10, 3 );

		$result = $service->generateContent(
			'Generate content',
			array(
				'temperature' => 0.5,
				'max_tokens'  => 500,
			)
		);

		remove_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10 );

		$this->assertInstanceOf( GenerationResult::class, $result );
	}

	/**
	 * Test missing API key throws exception.
	 */
	public function test_missing_api_key_throws_exception() {
		// Remove API key from settings.
		update_option( 'seo_generator_settings', array() );

		$service = new OpenAIService();

		$this->expectException( OpenAIException::class );
		$this->expectExceptionMessage( 'OpenAI API key not configured' );

		$service->generateContent( 'Test prompt' );
	}

	/**
	 * Test invalid API key error.
	 */
	public function test_invalid_api_key_error() {
		$service = new OpenAIService();

		// Mock 401 error response.
		add_filter( 'pre_http_request', array( $this, 'mock401Response' ), 10, 3 );

		$this->expectException( OpenAIException::class );
		$this->expectExceptionMessage( 'Invalid API key' );

		try {
			$service->generateContent( 'Test prompt' );
		} finally {
			remove_filter( 'pre_http_request', array( $this, 'mock401Response' ), 10 );
		}
	}

	/**
	 * Test rate limit exception.
	 */
	public function test_rate_limit_exception() {
		$service = new OpenAIService();

		// Mock 429 error response.
		add_filter( 'pre_http_request', array( $this, 'mock429Response' ), 10, 3 );

		$this->expectException( RateLimitException::class );
		$this->expectExceptionMessage( 'rate limit exceeded' );

		try {
			$service->generateContent( 'Test prompt' );
		} finally {
			remove_filter( 'pre_http_request', array( $this, 'mock429Response' ), 10 );
		}
	}

	/**
	 * Test server error with retry.
	 */
	public function test_server_error_retry() {
		$service = new OpenAIService();

		// Mock 500 error response that succeeds on retry.
		add_filter( 'pre_http_request', array( $this, 'mock500WithRetry' ), 10, 3 );

		$result = $service->generateContent( 'Test prompt' );

		remove_filter( 'pre_http_request', array( $this, 'mock500WithRetry' ), 10 );

		$this->assertInstanceOf( GenerationResult::class, $result );
	}

	/**
	 * Test JSON parsing error.
	 */
	public function test_json_parsing_error() {
		$service = new OpenAIService();

		// Mock invalid JSON response.
		add_filter( 'pre_http_request', array( $this, 'mockInvalidJsonResponse' ), 10, 3 );

		$this->expectException( OpenAIException::class );
		$this->expectExceptionMessage( 'Failed to parse API response' );

		try {
			$service->generateContent( 'Test prompt' );
		} finally {
			remove_filter( 'pre_http_request', array( $this, 'mockInvalidJsonResponse' ), 10 );
		}
	}

	/**
	 * Test network timeout error.
	 */
	public function test_network_timeout_error() {
		$service = new OpenAIService();

		// Mock timeout error.
		add_filter( 'pre_http_request', array( $this, 'mockTimeoutError' ), 10, 3 );

		$this->expectException( OpenAIException::class );
		$this->expectExceptionMessage( 'timed out' );

		try {
			$service->generateContent( 'Test prompt' );
		} finally {
			remove_filter( 'pre_http_request', array( $this, 'mockTimeoutError' ), 10 );
		}
	}

	/**
	 * Test GenerationResult toArray method.
	 */
	public function test_generation_result_to_array() {
		$result = new GenerationResult(
			'Test content',
			100,
			200,
			300,
			'gpt-4'
		);

		$array = $result->toArray();

		$this->assertIsArray( $array );
		$this->assertEquals( 'Test content', $array['content'] );
		$this->assertEquals( 100, $array['prompt_tokens'] );
		$this->assertEquals( 200, $array['completion_tokens'] );
		$this->assertEquals( 300, $array['total_tokens'] );
		$this->assertEquals( 'gpt-4', $array['model'] );
	}

	/**
	 * Mock successful API response.
	 *
	 * @param false|array|WP_Error $preempt Whether to preempt an HTTP request's return value.
	 * @param array                $args HTTP request arguments.
	 * @param string               $url The request URL.
	 * @return array Mocked response.
	 */
	public function mockSuccessfulResponse( $preempt, $args, $url ) {
		if ( strpos( $url, 'api.openai.com' ) === false ) {
			return $preempt;
		}

		return array(
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'body'     => wp_json_encode(
				array(
					'choices' => array(
						array(
							'message' => array(
								'content' => 'Generated content about wedding rings.',
							),
						),
					),
					'usage'   => array(
						'prompt_tokens'     => 100,
						'completion_tokens' => 200,
						'total_tokens'      => 300,
					),
					'model'   => 'gpt-4-turbo-preview',
				)
			),
		);
	}

	/**
	 * Mock 401 unauthorized response.
	 *
	 * @param false|array|WP_Error $preempt Whether to preempt an HTTP request's return value.
	 * @param array                $args HTTP request arguments.
	 * @param string               $url The request URL.
	 * @return array Mocked response.
	 */
	public function mock401Response( $preempt, $args, $url ) {
		if ( strpos( $url, 'api.openai.com' ) === false ) {
			return $preempt;
		}

		return array(
			'response' => array(
				'code'    => 401,
				'message' => 'Unauthorized',
			),
			'body'     => wp_json_encode(
				array(
					'error' => array(
						'message' => 'Invalid API key',
					),
				)
			),
		);
	}

	/**
	 * Mock 429 rate limit response.
	 *
	 * @param false|array|WP_Error $preempt Whether to preempt an HTTP request's return value.
	 * @param array                $args HTTP request arguments.
	 * @param string               $url The request URL.
	 * @return array Mocked response.
	 */
	public function mock429Response( $preempt, $args, $url ) {
		if ( strpos( $url, 'api.openai.com' ) === false ) {
			return $preempt;
		}

		return array(
			'response' => array(
				'code'    => 429,
				'message' => 'Too Many Requests',
			),
			'body'     => wp_json_encode(
				array(
					'error' => array(
						'message'     => 'Rate limit exceeded',
						'retry_after' => 60,
					),
				)
			),
		);
	}

	/**
	 * Mock 500 error that succeeds on retry.
	 *
	 * @param false|array|WP_Error $preempt Whether to preempt an HTTP request's return value.
	 * @param array                $args HTTP request arguments.
	 * @param string               $url The request URL.
	 * @return array Mocked response.
	 */
	public function mock500WithRetry( $preempt, $args, $url ) {
		if ( strpos( $url, 'api.openai.com' ) === false ) {
			return $preempt;
		}

		static $attempt = 0;
		$attempt++;

		// First attempt fails, second succeeds.
		if ( $attempt === 1 ) {
			return array(
				'response' => array(
					'code'    => 500,
					'message' => 'Internal Server Error',
				),
				'body'     => wp_json_encode(
					array(
						'error' => array(
							'message' => 'Server error',
						),
					)
				),
			);
		}

		return $this->mockSuccessfulResponse( $preempt, $args, $url );
	}

	/**
	 * Mock invalid JSON response.
	 *
	 * @param false|array|WP_Error $preempt Whether to preempt an HTTP request's return value.
	 * @param array                $args HTTP request arguments.
	 * @param string               $url The request URL.
	 * @return array Mocked response.
	 */
	public function mockInvalidJsonResponse( $preempt, $args, $url ) {
		if ( strpos( $url, 'api.openai.com' ) === false ) {
			return $preempt;
		}

		return array(
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'body'     => 'Invalid JSON {{{',
		);
	}

	/**
	 * Mock timeout error.
	 *
	 * @param false|array|WP_Error $preempt Whether to preempt an HTTP request's return value.
	 * @param array                $args HTTP request arguments.
	 * @param string               $url The request URL.
	 * @return WP_Error Mocked error.
	 */
	public function mockTimeoutError( $preempt, $args, $url ) {
		if ( strpos( $url, 'api.openai.com' ) === false ) {
			return $preempt;
		}

		static $attempt = 0;
		$attempt++;

		// Fail both attempts.
		return new \WP_Error( 'http_request_failed', 'Operation timed out after 60 seconds' );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		delete_option( 'seo_generator_settings' );
		parent::tearDown();
	}
}
