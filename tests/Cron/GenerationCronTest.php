<?php
/**
 * Generation Cron Integration Tests
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Cron;

use SEOGenerator\Services\GenerationQueue;
use SEOGenerator\Services\GenerationService;
use WP_UnitTestCase;

/**
 * Test cron-based content generation functionality.
 */
class GenerationCronTest extends WP_UnitTestCase {
	/**
	 * GenerationQueue instance.
	 *
	 * @var GenerationQueue
	 */
	private $queue;

	/**
	 * GenerationService instance.
	 *
	 * @var GenerationService
	 */
	private $service;

	/**
	 * Setup before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->queue   = new GenerationQueue();
		$this->service = new GenerationService();

		// Clear queue and options.
		delete_option( GenerationQueue::QUEUE_OPTION );
		delete_option( GenerationQueue::PAUSED_OPTION );
		delete_option( 'seo_last_generation_time' );
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		delete_option( GenerationQueue::QUEUE_OPTION );
		delete_option( GenerationQueue::PAUSED_OPTION );
		delete_option( 'seo_last_generation_time' );
		parent::tearDown();
	}

	/**
	 * Test that cron hook is registered.
	 */
	public function test_cron_hook_registered() {
		global $wp_filter;

		$this->assertArrayHasKey( 'seo_generate_queued_page', $wp_filter, 'Cron hook should be registered' );
	}

	/**
	 * Test processQueuedPage generates all blocks.
	 *
	 * Note: This test uses placeholder content since OpenAI API is not available in tests.
	 */
	public function test_process_queued_page() {
		// Create a test post with required ACF field.
		$post_id = $this->factory->post->create(
			array(
				'post_type'   => 'seo-page',
				'post_title'  => 'Test Post',
				'post_status' => 'draft',
			)
		);

		// Set focus keyword (required for generation).
		update_field( 'seo_focus_keyword', 'test keyword', $post_id );

		// Queue the post.
		$this->queue->queuePost( $post_id, 0 );

		// Process the queued page.
		$this->service->processQueuedPage( $post_id );

		// Verify queue status updated.
		$queued = $this->queue->getQueuedPosts();
		$this->assertEquals( 'completed', $queued[0]['status'], 'Queue status should be completed' );
	}

	/**
	 * Test post status is updated to pending after generation.
	 */
	public function test_post_status_updated() {
		$post_id = $this->factory->post->create(
			array(
				'post_type'   => 'seo-page',
				'post_title'  => 'Test Post',
				'post_status' => 'draft',
			)
		);

		update_field( 'seo_focus_keyword', 'test keyword', $post_id );
		$this->queue->queuePost( $post_id, 0 );

		$this->service->processQueuedPage( $post_id );

		$post = get_post( $post_id );
		$this->assertEquals( 'pending', $post->post_status, 'Post status should be updated to pending' );
	}

	/**
	 * Test queue status is updated to completed on success.
	 */
	public function test_queue_status_updated_to_completed() {
		$post_id = $this->factory->post->create(
			array(
				'post_type'   => 'seo-page',
				'post_status' => 'draft',
			)
		);

		update_field( 'seo_focus_keyword', 'test keyword', $post_id );
		$this->queue->queuePost( $post_id, 0 );

		$this->service->processQueuedPage( $post_id );

		$queued = $this->queue->getQueuedPosts();
		$this->assertEquals( 'completed', $queued[0]['status'], 'Queue status should be completed' );

		// Verify metadata was added.
		$auto_generated = get_post_meta( $post_id, '_auto_generated', true );
		$this->assertTrue( (bool) $auto_generated, '_auto_generated meta should be true' );

		$generation_date = get_post_meta( $post_id, '_generation_date', true );
		$this->assertNotEmpty( $generation_date, '_generation_date meta should be set' );
	}

	/**
	 * Test queue item is marked as failed when post doesn't exist.
	 */
	public function test_generation_error_handling() {
		$invalid_post_id = 999999;

		$this->queue->queuePost( $invalid_post_id, 0 );
		$this->service->processQueuedPage( $invalid_post_id );

		$queued = $this->queue->getQueuedPosts();
		$this->assertEquals( 'failed', $queued[0]['status'], 'Queue status should be failed' );
		$this->assertNotEmpty( $queued[0]['error'], 'Error message should be set' );
	}

	/**
	 * Test job is rescheduled when queue is paused.
	 */
	public function test_paused_queue_reschedules() {
		$post_id = $this->factory->post->create(
			array(
				'post_type'   => 'seo-page',
				'post_status' => 'draft',
			)
		);

		update_field( 'seo_focus_keyword', 'test keyword', $post_id );
		$this->queue->queuePost( $post_id, 0 );
		$this->queue->pauseQueue();

		$this->service->processQueuedPage( $post_id );

		// Verify job was rescheduled (new cron event created).
		$next_scheduled = wp_next_scheduled( 'seo_generate_queued_page', array( $post_id ) );
		$this->assertNotFalse( $next_scheduled, 'Job should be rescheduled when queue is paused' );

		// Verify queue status is still pending.
		$queued = $this->queue->getQueuedPosts();
		$this->assertEquals( 'pending', $queued[0]['status'], 'Queue status should remain pending' );
	}

	/**
	 * Test generateAllBlocks calls generateBlock for all 12 block types.
	 */
	public function test_generate_all_blocks() {
		$post_id = $this->factory->post->create(
			array(
				'post_type'  => 'seo-page',
				'post_title' => 'Test Post',
			)
		);

		update_field( 'seo_focus_keyword', 'test keyword', $post_id );

		$result = $this->service->generateAllBlocks( $post_id );

		$this->assertTrue( $result['success'], 'Generation should succeed' );
		$this->assertEquals( 12, $result['blocks_generated'], 'Should generate all 12 blocks' );
		$this->assertEquals( 0, $result['blocks_failed'], 'No blocks should fail' );
	}

	/**
	 * Test post remains draft if some blocks fail.
	 *
	 * Note: This test simulates partial failure by using a post without focus keyword.
	 */
	public function test_partial_failure_handling() {
		// Create post without focus keyword (will cause generation to fail).
		$post_id = $this->factory->post->create(
			array(
				'post_type'   => 'seo-page',
				'post_title'  => 'Test Post',
				'post_status' => 'draft',
			)
		);

		$this->queue->queuePost( $post_id, 0 );
		$this->service->processQueuedPage( $post_id );

		// Verify post status remains draft.
		$post = get_post( $post_id );
		$this->assertEquals( 'draft', $post->post_status, 'Post should remain draft if generation fails' );

		// Verify queue status is failed.
		$queued = $this->queue->getQueuedPosts();
		$this->assertEquals( 'failed', $queued[0]['status'], 'Queue status should be failed' );
	}

	/**
	 * Test that generation metadata is stored correctly.
	 */
	public function test_generation_metadata() {
		$post_id = $this->factory->post->create(
			array(
				'post_type'   => 'seo-page',
				'post_status' => 'draft',
			)
		);

		update_field( 'seo_focus_keyword', 'test keyword', $post_id );
		$this->queue->queuePost( $post_id, 0 );

		$this->service->processQueuedPage( $post_id );

		// Verify all metadata.
		$auto_generated   = get_post_meta( $post_id, '_auto_generated', true );
		$generation_date  = get_post_meta( $post_id, '_generation_date', true );
		$blocks_generated = get_post_meta( $post_id, '_blocks_generated', true );
		$blocks_failed    = get_post_meta( $post_id, '_blocks_failed', true );

		$this->assertEquals( '1', $auto_generated, '_auto_generated should be 1' );
		$this->assertNotEmpty( $generation_date, '_generation_date should be set' );
		$this->assertEquals( '12', $blocks_generated, '_blocks_generated should be 12' );
		$this->assertEquals( '0', $blocks_failed, '_blocks_failed should be 0' );
	}

	/**
	 * Test rate limit enforcement reschedules jobs.
	 */
	public function test_rate_limit_enforcement() {
		$post_id = $this->factory->post->create(
			array(
				'post_type'   => 'seo-page',
				'post_status' => 'draft',
			)
		);

		update_field( 'seo_focus_keyword', 'test keyword', $post_id );

		// Set last generation time to now.
		update_option( 'seo_last_generation_time', time() );

		$this->queue->queuePost( $post_id, 0 );
		$this->service->processQueuedPage( $post_id );

		// Verify job was rescheduled due to rate limit.
		$next_scheduled = wp_next_scheduled( 'seo_generate_queued_page', array( $post_id ) );
		$this->assertNotFalse( $next_scheduled, 'Job should be rescheduled due to rate limit' );

		// Verify queue status is still pending (not processed).
		$queued = $this->queue->getQueuedPosts();
		$this->assertEquals( 'pending', $queued[0]['status'], 'Queue status should remain pending when rate limited' );
	}
}
