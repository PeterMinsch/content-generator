<?php
/**
 * Tests for Generation Controller
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Controllers;

use SEOGenerator\Controllers\GenerationController;
use SEOGenerator\Services\ContentGenerationService;
use SEOGenerator\Services\OpenAIService;
use SEOGenerator\Services\PromptTemplateEngine;
use SEOGenerator\Services\BlockContentParser;
use SEOGenerator\Services\CostTrackingService;
use SEOGenerator\Repositories\GenerationLogRepository;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Generation Controller test case.
 */
class GenerationControllerTest extends WP_UnitTestCase {
	/**
	 * Controller instance.
	 *
	 * @var GenerationController
	 */
	private $controller;

	/**
	 * Test post ID.
	 *
	 * @var int
	 */
	private $post_id;

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

		// Create services.
		$openai_service     = new OpenAIService();
		$prompt_engine      = new PromptTemplateEngine();
		$content_parser     = new BlockContentParser();
		$log_repository     = new GenerationLogRepository();
		$cost_tracking      = new CostTrackingService( $log_repository );
		$generation_service = new ContentGenerationService( $openai_service, $prompt_engine, $content_parser, $cost_tracking );

		// Create controller.
		$this->controller = new GenerationController( $generation_service );
		$this->controller->register_routes();

		// Set up admin user.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
	}

	/**
	 * Test REST route is registered.
	 */
	public function test_rest_route_registered() {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/seo-generator/v1/pages/(?P<id>\d+)/generate', $routes );
	}

	/**
	 * Test permission check requires edit_posts capability.
	 */
	public function test_permission_check_requires_capability() {
		// Create user without permission.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$request  = new WP_REST_Request( 'POST', '/seo-generator/v1/pages/' . $this->post_id . '/generate' );
		$request->set_param( 'blockType', 'hero' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
		$this->assertEquals( 'insufficient_permissions', $response->get_data()['code'] );
	}

	/**
	 * Test validation rejects non-existent post.
	 */
	public function test_rejects_non_existent_post() {
		add_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10, 3 );

		$request  = new WP_REST_Request( 'POST', '/seo-generator/v1/pages/99999/generate' );
		$request->set_param( 'blockType', 'hero' );

		$response = rest_get_server()->dispatch( $request );

		remove_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10 );

		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( 'post_not_found', $response->get_data()['code'] );
	}

	/**
	 * Test validation rejects wrong post type.
	 */
	public function test_rejects_wrong_post_type() {
		// Create regular post.
		$post_id = $this->factory->post->create(
			array(
				'post_type' => 'post',
			)
		);

		add_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10, 3 );

		$request  = new WP_REST_Request( 'POST', '/seo-generator/v1/pages/' . $post_id . '/generate' );
		$request->set_param( 'blockType', 'hero' );

		$response = rest_get_server()->dispatch( $request );

		remove_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10 );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'invalid_post_type', $response->get_data()['code'] );
	}

	/**
	 * Test validation rejects invalid block type.
	 */
	public function test_rejects_invalid_block_type() {
		add_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10, 3 );

		$request  = new WP_REST_Request( 'POST', '/seo-generator/v1/pages/' . $this->post_id . '/generate' );
		$request->set_param( 'blockType', 'invalid_block' );

		$response = rest_get_server()->dispatch( $request );

		remove_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10 );

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test successful generation returns expected data.
	 */
	public function test_successful_generation_returns_data() {
		// Mock successful OpenAI response.
		add_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10, 3 );

		$request  = new WP_REST_Request( 'POST', '/seo-generator/v1/pages/' . $this->post_id . '/generate' );
		$request->set_param( 'blockType', 'hero' );

		$response = rest_get_server()->dispatch( $request );

		remove_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10 );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'content', $data );
		$this->assertArrayHasKey( 'metadata', $data );
		$this->assertArrayHasKey( 'promptTokens', $data['metadata'] );
		$this->assertArrayHasKey( 'completionTokens', $data['metadata'] );
		$this->assertArrayHasKey( 'totalTokens', $data['metadata'] );
		$this->assertArrayHasKey( 'cost', $data['metadata'] );
		$this->assertArrayHasKey( 'generationTime', $data['metadata'] );
		$this->assertArrayHasKey( 'model', $data['metadata'] );
	}

	/**
	 * Test generation with additional context.
	 */
	public function test_generation_with_additional_context() {
		add_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10, 3 );

		$request  = new WP_REST_Request( 'POST', '/seo-generator/v1/pages/' . $this->post_id . '/generate' );
		$request->set_param( 'blockType', 'hero' );
		$request->set_param(
			'context',
			array(
				'custom_field' => 'custom_value',
			)
		);

		$response = rest_get_server()->dispatch( $request );

		remove_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10 );

		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Mock successful API response for hero block.
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
										'headline'    => 'Premium Wedding Bands',
										'subheadline' => 'Discover our curated collection of expertly crafted wedding bands.',
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
	 * Test bulk generation endpoint is registered.
	 */
	public function test_bulk_generation_route_registered() {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/seo-generator/v1/pages/(?P<id>\\d+)/generate-all', $routes );
	}

	/**
	 * Test progress endpoint is registered.
	 */
	public function test_progress_route_registered() {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/seo-generator/v1/pages/(?P<id>\\d+)/generate-progress', $routes );
	}

	/**
	 * Test successful bulk generation.
	 */
	public function test_successful_bulk_generation() {
		add_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10, 3 );

		$request = new WP_REST_Request( 'POST', '/seo-generator/v1/pages/' . $this->post_id . '/generate-all' );

		$response = rest_get_server()->dispatch( $request );

		remove_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10 );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertArrayHasKey( 'totalBlocks', $data['data'] );
		$this->assertArrayHasKey( 'successCount', $data['data'] );
		$this->assertArrayHasKey( 'failedBlocks', $data['data'] );
		$this->assertArrayHasKey( 'totalTokens', $data['data'] );
		$this->assertArrayHasKey( 'totalCost', $data['data'] );
		$this->assertArrayHasKey( 'totalTime', $data['data'] );
		$this->assertArrayHasKey( 'successRate', $data['data'] );
	}

	/**
	 * Test bulk generation requires permission.
	 */
	public function test_bulk_generation_requires_permission() {
		// Create user without permission.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'POST', '/seo-generator/v1/pages/' . $this->post_id . '/generate-all' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
		$this->assertEquals( 'insufficient_permissions', $response->get_data()['code'] );
	}

	/**
	 * Test bulk generation rejects non-existent post.
	 */
	public function test_bulk_generation_rejects_non_existent_post() {
		add_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10, 3 );

		$request = new WP_REST_Request( 'POST', '/seo-generator/v1/pages/99999/generate-all' );

		$response = rest_get_server()->dispatch( $request );

		remove_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10 );

		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( 'post_not_found', $response->get_data()['code'] );
	}

	/**
	 * Test bulk generation rejects wrong post type.
	 */
	public function test_bulk_generation_rejects_wrong_post_type() {
		// Create regular post.
		$post_id = $this->factory->post->create(
			array(
				'post_type' => 'post',
			)
		);

		add_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10, 3 );

		$request = new WP_REST_Request( 'POST', '/seo-generator/v1/pages/' . $post_id . '/generate-all' );

		$response = rest_get_server()->dispatch( $request );

		remove_filter( 'pre_http_request', array( $this, 'mockSuccessfulResponse' ), 10 );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'invalid_post_type', $response->get_data()['code'] );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test progress endpoint returns 404 when no active generation.
	 */
	public function test_progress_endpoint_returns_404_when_no_active_generation() {
		$request = new WP_REST_Request( 'GET', '/seo-generator/v1/pages/' . $this->post_id . '/generate-progress' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( 'progress_not_found', $response->get_data()['code'] );
	}

	/**
	 * Test progress endpoint returns progress data.
	 */
	public function test_progress_endpoint_returns_progress_data() {
		$user_id = get_current_user_id();

		// Set progress data.
		$progress = array(
			'currentBlock'            => 'materials',
			'currentBlockIndex'       => 4,
			'totalBlocks'             => 12,
			'completionPercentage'    => 33.33,
			'timeElapsed'             => 90,
			'estimatedTimeRemaining'  => 180,
			'completedBlocks'         => array( 'hero', 'serp_answer', 'product_criteria' ),
			'failedBlocks'            => array(),
		);

		set_transient( 'seo_gen_progress_' . $this->post_id . '_' . $user_id, $progress, 10 * MINUTE_IN_SECONDS );

		$request = new WP_REST_Request( 'GET', '/seo-generator/v1/pages/' . $this->post_id . '/generate-progress' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertEquals( 'materials', $data['data']['currentBlock'] );
		$this->assertEquals( 33.33, $data['data']['completionPercentage'] );

		// Clean up.
		delete_transient( 'seo_gen_progress_' . $this->post_id . '_' . $user_id );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		// Clean up transients.
		$user_id = get_current_user_id();
		delete_transient( 'seo_gen_bulk_active_' . $user_id );
		delete_transient( 'seo_gen_progress_' . $this->post_id . '_' . $user_id );

		wp_delete_post( $this->post_id, true );
		parent::tearDown();
	}
}
