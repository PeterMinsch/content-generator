<?php
/**
 * Tests for Bulk Generation
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Services;

use SEOGenerator\Services\ContentGenerationService;
use SEOGenerator\Services\OpenAIService;
use SEOGenerator\Services\PromptTemplateEngine;
use SEOGenerator\Services\BlockContentParser;
use SEOGenerator\Services\CostTrackingService;
use SEOGenerator\Repositories\GenerationLogRepository;
use SEOGenerator\Models\BulkGenerationResult;
use SEOGenerator\Exceptions\RateLimitException;
use WP_UnitTestCase;

/**
 * Bulk Generation test case.
 */
class BulkGenerationTest extends WP_UnitTestCase {
	/**
	 * Content generation service instance.
	 *
	 * @var ContentGenerationService
	 */
	private $service;

	/**
	 * Test post ID.
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create test post.
		$this->post_id = $this->factory->post->create(
			array(
				'post_type'  => 'seo-page',
				'post_title' => 'Test Wedding Bands',
			)
		);

		// Create admin user.
		$this->user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->user_id );

		// Create service.
		$openai_service  = new OpenAIService();
		$prompt_engine   = new PromptTemplateEngine();
		$content_parser  = new BlockContentParser();
		$log_repository  = new GenerationLogRepository();
		$cost_tracking   = new CostTrackingService( $log_repository );

		$this->service = new ContentGenerationService( $openai_service, $prompt_engine, $content_parser, $cost_tracking );
	}

	/**
	 * Test successful bulk generation of all 12 blocks.
	 */
	public function test_successful_bulk_generation() {
		// Mock successful OpenAI response.
		add_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10, 3 );

		$result = $this->service->generateAllBlocks( $this->post_id );

		remove_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10 );

		$this->assertInstanceOf( BulkGenerationResult::class, $result );
		$this->assertEquals( 12, $result->getTotalBlocks() );
		$this->assertEquals( 12, $result->getSuccessCount() );
		$this->assertEmpty( $result->getFailedBlocks() );
		$this->assertGreaterThan( 0, $result->getTotalTokens() );
		$this->assertGreaterThan( 0, $result->getTotalCost() );
		$this->assertGreaterThan( 0, $result->getTotalTime() );
		$this->assertEquals( 100.0, $result->getSuccessRate() );
	}

	/**
	 * Test bulk generation continues when individual blocks fail.
	 */
	public function test_continues_on_individual_block_failures() {
		$call_count = 0;

		// Mock responses: first 3 succeed, 4th fails, rest succeed.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( &$call_count ) {
				if ( strpos( $url, 'api.openai.com' ) === false ) {
					return $preempt;
				}

				++$call_count;

				// Fail on 4th call.
				if ( 4 === $call_count ) {
					return array(
						'response' => array(
							'code'    => 500,
							'message' => 'Internal Server Error',
						),
						'body'     => wp_json_encode(
							array(
								'error' => array(
									'message' => 'Service temporarily unavailable',
								),
							)
						),
					);
				}

				// Success for other calls.
				return $this->mockSuccessfulResponse( $preempt, $args, $url );
			},
			10,
			3
		);

		$result = $this->service->generateAllBlocks( $this->post_id );

		remove_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10 );

		$this->assertEquals( 12, $result->getTotalBlocks() );
		$this->assertEquals( 11, $result->getSuccessCount() );
		$this->assertCount( 1, $result->getFailedBlocks() );
		$this->assertEquals( 91.67, $result->getSuccessRate() );
	}

	/**
	 * Test rate limiting enforces max 3 concurrent generations.
	 */
	public function test_rate_limiting_enforces_max_concurrent() {
		// Simulate 3 active bulk generations.
		set_transient(
			'seo_gen_bulk_active_' . $this->user_id,
			array( 1, 2, 3 ),
			10 * MINUTE_IN_SECONDS
		);

		$this->expectException( RateLimitException::class );
		$this->expectExceptionMessage( 'Maximum 3 concurrent bulk generations allowed' );

		$this->service->generateAllBlocks( $this->post_id );
	}

	/**
	 * Test progress tracking updates correctly.
	 */
	public function test_progress_tracking_updates() {
		// Mock successful OpenAI response.
		add_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10, 3 );

		// Start generation in background.
		$result = $this->service->generateAllBlocks( $this->post_id );

		remove_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10 );

		// Progress should be cleared after completion.
		$progress = $this->service->getProgress( $this->post_id, $this->user_id );
		$this->assertNull( $progress );
	}

	/**
	 * Test bulk result toArray includes all metadata.
	 */
	public function test_bulk_result_includes_all_metadata() {
		// Mock successful OpenAI response.
		add_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10, 3 );

		$result = $this->service->generateAllBlocks( $this->post_id );

		remove_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10 );

		$array = $result->toArray();

		$this->assertArrayHasKey( 'totalBlocks', $array );
		$this->assertArrayHasKey( 'successCount', $array );
		$this->assertArrayHasKey( 'failedBlocks', $array );
		$this->assertArrayHasKey( 'totalTokens', $array );
		$this->assertArrayHasKey( 'totalCost', $array );
		$this->assertArrayHasKey( 'totalTime', $array );
		$this->assertArrayHasKey( 'successRate', $array );
	}

	/**
	 * Test transients are created and cleaned up properly.
	 */
	public function test_transients_cleaned_up() {
		// Mock successful OpenAI response.
		add_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10, 3 );

		$result = $this->service->generateAllBlocks( $this->post_id );

		remove_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10 );

		// Bulk active transient should be cleaned up.
		$active = get_transient( 'seo_gen_bulk_active_' . $this->user_id );
		$this->assertIsArray( $active );
		$this->assertNotContains( $this->post_id, $active );

		// Progress transient should be cleaned up.
		$progress = get_transient( 'seo_gen_progress_' . $this->post_id . '_' . $this->user_id );
		$this->assertFalse( $progress );
	}

	/**
	 * Test all 12 blocks generated in correct order.
	 */
	public function test_blocks_generated_in_correct_order() {
		$generated_blocks = array();

		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( &$generated_blocks ) {
				if ( strpos( $url, 'api.openai.com' ) === false ) {
					return $preempt;
				}

				// Track which block is being generated based on prompt content.
				$body = json_decode( $args['body'], true );
				if ( isset( $body['messages'][1]['content'] ) ) {
					$content = $body['messages'][1]['content'];

					if ( strpos( $content, 'hero' ) !== false ) {
						$generated_blocks[] = 'hero';
					} elseif ( strpos( $content, 'SERP answer' ) !== false ) {
						$generated_blocks[] = 'serp_answer';
					}
					// Add more block detection as needed.
				}

				return $this->mockSuccessfulResponse( $preempt, $args, $url );
			},
			10,
			3
		);

		$result = $this->service->generateAllBlocks( $this->post_id );

		remove_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10 );

		// Verify blocks were generated.
		$this->assertGreaterThan( 0, count( $generated_blocks ) );
	}

	/**
	 * Mock successful API response for blocks.
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
								'content' => wp_json_encode(
									array(
										'headline'    => 'Test Headline',
										'subheadline' => 'Test Subheadline',
									)
								),
							),
						),
					),
					'usage'   => array(
						'prompt_tokens'     => 100,
						'completion_tokens' => 50,
						'total_tokens'      => 150,
					),
					'model'   => 'gpt-4-turbo-preview',
				)
			),
		);
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		// Clean up transients.
		delete_transient( 'seo_gen_bulk_active_' . $this->user_id );
		delete_transient( 'seo_gen_progress_' . $this->post_id . '_' . $this->user_id );

		wp_delete_post( $this->post_id, true );
		parent::tearDown();
	}
}
