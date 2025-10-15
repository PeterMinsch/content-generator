<?php
/**
 * Tests for Error Handling
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Services;

use SEOGenerator\Services\OpenAIService;
use SEOGenerator\Services\CostTrackingService;
use SEOGenerator\Repositories\GenerationLogRepository;
use SEOGenerator\Exceptions\OpenAIException;
use SEOGenerator\Exceptions\RateLimitException;
use SEOGenerator\Exceptions\BudgetExceededException;
use SEOGenerator\Exceptions\NetworkException;
use SEOGenerator\Exceptions\TimeoutException;
use SEOGenerator\Exceptions\InvalidResponseException;
use WP_UnitTestCase;

/**
 * Error Handling test case.
 */
class ErrorHandlingTest extends WP_UnitTestCase {
	/**
	 * Test OpenAIException is thrown for missing API key.
	 */
	public function test_throws_exception_for_missing_api_key() {
		// Clear API key.
		update_option(
			'seo_generator_settings',
			array(
				'openai_api_key' => '',
			)
		);

		$service = new OpenAIService();

		$this->expectException( OpenAIException::class );
		$this->expectExceptionMessage( 'OpenAI API key not configured' );

		$service->generateContent( 'Test prompt' );
	}

	/**
	 * Test NetworkException is thrown for connection errors.
	 */
	public function test_throws_network_exception_for_connection_errors() {
		// Set valid API key.
		update_option(
			'seo_generator_settings',
			array(
				'openai_api_key' => seo_generator_encrypt_api_key( 'test-key' ),
			)
		);

		$service = new OpenAIService();

		// Mock network error.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				if ( strpos( $url, 'api.openai.com' ) !== false ) {
					return new \WP_Error( 'http_request_failed', 'cURL error 7: Failed to connect' );
				}
				return $preempt;
			},
			10,
			3
		);

		$this->expectException( NetworkException::class );
		$this->expectExceptionMessage( 'Unable to connect to OpenAI' );

		$service->generateContent( 'Test prompt' );
	}

	/**
	 * Test TimeoutException is thrown for timeout errors.
	 */
	public function test_throws_timeout_exception_for_timeouts() {
		update_option(
			'seo_generator_settings',
			array(
				'openai_api_key' => seo_generator_encrypt_api_key( 'test-key' ),
			)
		);

		$service = new OpenAIService();

		// Mock timeout error.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				if ( strpos( $url, 'api.openai.com' ) !== false ) {
					return new \WP_Error( 'http_request_failed', 'Operation timed out after 60 seconds' );
				}
				return $preempt;
			},
			10,
			3
		);

		$this->expectException( TimeoutException::class );
		$this->expectExceptionMessage( 'Generation timed out' );

		$service->generateContent( 'Test prompt' );
	}

	/**
	 * Test InvalidResponseException is thrown for invalid JSON.
	 */
	public function test_throws_invalid_response_exception_for_bad_json() {
		update_option(
			'seo_generator_settings',
			array(
				'openai_api_key' => seo_generator_encrypt_api_key( 'test-key' ),
			)
		);

		$service = new OpenAIService();

		// Mock invalid JSON response.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				if ( strpos( $url, 'api.openai.com' ) !== false ) {
					return array(
						'response' => array(
							'code'    => 200,
							'message' => 'OK',
						),
						'body'     => 'This is not valid JSON',
					);
				}
				return $preempt;
			},
			10,
			3
		);

		$this->expectException( InvalidResponseException::class );
		$this->expectExceptionMessage( 'Failed to parse AI response' );

		$service->generateContent( 'Test prompt' );
	}

	/**
	 * Test InvalidResponseException is thrown for missing content field.
	 */
	public function test_throws_invalid_response_exception_for_missing_content() {
		update_option(
			'seo_generator_settings',
			array(
				'openai_api_key' => seo_generator_encrypt_api_key( 'test-key' ),
			)
		);

		$service = new OpenAIService();

		// Mock response missing content field.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				if ( strpos( $url, 'api.openai.com' ) !== false ) {
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
											// Missing 'content' field
										),
									),
								),
								'usage'   => array(
									'prompt_tokens'     => 10,
									'completion_tokens' => 20,
									'total_tokens'      => 30,
								),
							)
						),
					);
				}
				return $preempt;
			},
			10,
			3
		);

		$this->expectException( InvalidResponseException::class );
		$this->expectExceptionMessage( 'Invalid response format' );

		$service->generateContent( 'Test prompt' );
	}

	/**
	 * Test RateLimitException is thrown for 429 status.
	 */
	public function test_throws_rate_limit_exception_for_429() {
		update_option(
			'seo_generator_settings',
			array(
				'openai_api_key' => seo_generator_encrypt_api_key( 'test-key' ),
			)
		);

		$service = new OpenAIService();

		// Mock 429 rate limit response.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				if ( strpos( $url, 'api.openai.com' ) !== false ) {
					return array(
						'response' => array(
							'code'    => 429,
							'message' => 'Too Many Requests',
						),
						'body'     => wp_json_encode(
							array(
								'error' => array(
									'message' => 'Rate limit exceeded',
								),
							)
						),
					);
				}
				return $preempt;
			},
			10,
			3
		);

		$this->expectException( RateLimitException::class );

		$service->generateContent( 'Test prompt' );
	}

	/**
	 * Test success rate monitoring.
	 */
	public function test_success_rate_monitoring() {
		$repository    = new GenerationLogRepository();
		$cost_tracking = new CostTrackingService( $repository );

		// Create test user and post.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$post_id = $this->factory->post->create(
			array(
				'post_type' => 'seo-page',
			)
		);

		// Insert 90 successful logs and 10 failed logs.
		for ( $i = 0; $i < 90; $i++ ) {
			$cost_tracking->logGeneration(
				array(
					'post_id'           => $post_id,
					'block_type'        => 'hero',
					'prompt_tokens'     => 100,
					'completion_tokens' => 50,
					'total_tokens'      => 150,
					'cost'              => 0.01,
					'model'             => 'gpt-4',
					'status'            => 'success',
					'error_message'     => null,
					'user_id'           => $user_id,
				)
			);
		}

		for ( $i = 0; $i < 10; $i++ ) {
			$cost_tracking->logGeneration(
				array(
					'post_id'           => $post_id,
					'block_type'        => 'hero',
					'prompt_tokens'     => 0,
					'completion_tokens' => 0,
					'total_tokens'      => 0,
					'cost'              => 0,
					'model'             => '',
					'status'            => 'failed',
					'error_message'     => 'Test error',
					'user_id'           => $user_id,
				)
			);
		}

		$success_rate = $cost_tracking->getSuccessRate( 100 );

		// Should be 90% (90 successful out of 100 total).
		$this->assertEquals( 90.0, $success_rate );

		// Clean up.
		global $wpdb;
		$table_name = $wpdb->prefix . 'seo_generation_log';
		$wpdb->query( "TRUNCATE TABLE {$table_name}" );
		wp_delete_post( $post_id, true );
	}

	/**
	 * Test BudgetExceededException includes cost details.
	 */
	public function test_budget_exceeded_exception_includes_details() {
		$exception = new BudgetExceededException(
			'Budget exceeded',
			150.00,
			100.00
		);

		$this->assertEquals( 150.00, $exception->getCurrentCost() );
		$this->assertEquals( 100.00, $exception->getBudgetLimit() );
		$this->assertEquals( 150.0, $exception->getPercentageUsed() );
	}

	/**
	 * Clean up after tests.
	 */
	public function tearDown(): void {
		remove_all_filters( 'pre_http_request' );
		parent::tearDown();
	}
}
