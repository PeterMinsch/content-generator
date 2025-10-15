<?php
/**
 * GenerationQueue Service Tests
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Services;

use SEOGenerator\Services\GenerationQueue;
use WP_UnitTestCase;

/**
 * Test GenerationQueue service functionality.
 */
class GenerationQueueTest extends WP_UnitTestCase {
	/**
	 * GenerationQueue instance.
	 *
	 * @var GenerationQueue
	 */
	private $queue;

	/**
	 * Setup before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->queue = new GenerationQueue();
		// Clear queue before each test.
		delete_option( GenerationQueue::QUEUE_OPTION );
		delete_option( GenerationQueue::PAUSED_OPTION );
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		// Clean up.
		delete_option( GenerationQueue::QUEUE_OPTION );
		delete_option( GenerationQueue::PAUSED_OPTION );
		parent::tearDown();
	}

	/**
	 * Test that queuePost adds a job to the queue with pending status.
	 */
	public function test_queue_post() {
		$post_id = $this->factory->post->create(
			array(
				'post_type'  => 'seo-page',
				'post_title' => 'Test Post',
			)
		);

		$result = $this->queue->queuePost( $post_id, 0 );

		$this->assertTrue( $result, 'queuePost should return true' );

		$queued = $this->queue->getQueuedPosts();
		$this->assertCount( 1, $queued, 'Queue should contain 1 job' );
		$this->assertEquals( 'pending', $queued[0]['status'], 'Status should be pending' );
		$this->assertEquals( $post_id, $queued[0]['post_id'], 'Post ID should match' );
	}

	/**
	 * Test that duplicate posts are not queued twice.
	 */
	public function test_queue_post_prevents_duplicates() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );

		$this->queue->queuePost( $post_id, 0 );
		$result = $this->queue->queuePost( $post_id, 0 );

		$this->assertFalse( $result, 'Duplicate post should not be queued' );

		$queued = $this->queue->getQueuedPosts();
		$this->assertCount( 1, $queued, 'Queue should still contain only 1 job' );
	}

	/**
	 * Test that jobs are scheduled with 3-minute offset.
	 */
	public function test_scheduling_with_offset() {
		$post_id_1 = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );
		$post_id_2 = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );

		$base_time = time();

		$this->queue->queuePost( $post_id_1, 0 );
		$this->queue->queuePost( $post_id_2, 1 );

		$queued = $this->queue->getQueuedPosts();

		$this->assertCount( 2, $queued, 'Queue should contain 2 jobs' );

		// Check scheduling offset (3 minutes = 180 seconds).
		$time_diff = $queued[1]['scheduled_time'] - $queued[0]['scheduled_time'];
		$this->assertEquals( 180, $time_diff, 'Jobs should be scheduled 180 seconds apart' );
	}

	/**
	 * Test getQueuedPosts returns all queued posts.
	 */
	public function test_get_queued_posts() {
		$post_id_1 = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );
		$post_id_2 = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );

		$this->queue->queuePost( $post_id_1, 0 );
		$this->queue->queuePost( $post_id_2, 1 );

		$queued = $this->queue->getQueuedPosts();

		$this->assertCount( 2, $queued, 'Should return all 2 queued posts' );
	}

	/**
	 * Test getQueuedPosts filters by status.
	 */
	public function test_get_queued_posts_by_status() {
		$post_id_1 = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );
		$post_id_2 = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );

		$this->queue->queuePost( $post_id_1, 0 );
		$this->queue->queuePost( $post_id_2, 1 );

		// Update one to completed status.
		$this->queue->updateQueueStatus( $post_id_1, 'completed' );

		$pending = $this->queue->getQueuedPosts( 'pending' );
		$completed = $this->queue->getQueuedPosts( 'completed' );

		$this->assertCount( 1, $pending, 'Should return 1 pending job' );
		$this->assertCount( 1, $completed, 'Should return 1 completed job' );
		$this->assertEquals( $post_id_2, $pending[0]['post_id'], 'Pending job should be post 2' );
		$this->assertEquals( $post_id_1, $completed[0]['post_id'], 'Completed job should be post 1' );
	}

	/**
	 * Test updateQueueStatus updates the status for specific post.
	 */
	public function test_update_queue_status() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );

		$this->queue->queuePost( $post_id, 0 );
		$result = $this->queue->updateQueueStatus( $post_id, 'processing' );

		$this->assertTrue( $result, 'updateQueueStatus should return true' );

		$queued = $this->queue->getQueuedPosts();
		$this->assertEquals( 'processing', $queued[0]['status'], 'Status should be updated to processing' );
	}

	/**
	 * Test updateQueueStatus with error message.
	 */
	public function test_update_queue_status_with_error() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );

		$this->queue->queuePost( $post_id, 0 );
		$this->queue->updateQueueStatus( $post_id, 'failed', 'Test error message' );

		$queued = $this->queue->getQueuedPosts();
		$this->assertEquals( 'failed', $queued[0]['status'], 'Status should be failed' );
		$this->assertEquals( 'Test error message', $queued[0]['error'], 'Error message should be stored' );
	}

	/**
	 * Test clearQueue removes all jobs and cancels cron events.
	 */
	public function test_clear_queue() {
		$post_id_1 = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );
		$post_id_2 = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );

		$this->queue->queuePost( $post_id_1, 0 );
		$this->queue->queuePost( $post_id_2, 1 );

		$this->queue->clearQueue();

		$queued = $this->queue->getQueuedPosts();
		$this->assertEmpty( $queued, 'Queue should be empty after clear' );

		// Verify cron events were cancelled.
		$next_1 = wp_next_scheduled( 'seo_generate_queued_page', array( $post_id_1 ) );
		$next_2 = wp_next_scheduled( 'seo_generate_queued_page', array( $post_id_2 ) );

		$this->assertFalse( $next_1, 'Cron event for post 1 should be cancelled' );
		$this->assertFalse( $next_2, 'Cron event for post 2 should be cancelled' );
	}

	/**
	 * Test pauseQueue sets the paused flag.
	 */
	public function test_pause_queue() {
		$this->queue->pauseQueue();

		$this->assertTrue( $this->queue->isPaused(), 'Queue should be paused' );
	}

	/**
	 * Test resumeQueue unsets the paused flag.
	 */
	public function test_resume_queue() {
		$this->queue->pauseQueue();
		$this->queue->resumeQueue();

		$this->assertFalse( $this->queue->isPaused(), 'Queue should not be paused after resume' );
	}

	/**
	 * Test getQueueStats returns correct counts by status.
	 */
	public function test_queue_stats() {
		$post_id_1 = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );
		$post_id_2 = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );
		$post_id_3 = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );

		$this->queue->queuePost( $post_id_1, 0 );
		$this->queue->queuePost( $post_id_2, 1 );
		$this->queue->queuePost( $post_id_3, 2 );

		$this->queue->updateQueueStatus( $post_id_1, 'completed' );
		$this->queue->updateQueueStatus( $post_id_2, 'failed' );

		$stats = $this->queue->getQueueStats();

		$this->assertEquals( 1, $stats['pending'], 'Should have 1 pending' );
		$this->assertEquals( 0, $stats['processing'], 'Should have 0 processing' );
		$this->assertEquals( 1, $stats['completed'], 'Should have 1 completed' );
		$this->assertEquals( 1, $stats['failed'], 'Should have 1 failed' );
		$this->assertEquals( 3, $stats['total'], 'Total should be 3' );
	}

	/**
	 * Test getEstimatedCompletion calculates correct time.
	 */
	public function test_estimated_completion() {
		$post_id_1 = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );
		$post_id_2 = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );

		$this->queue->queuePost( $post_id_1, 0 );
		$this->queue->queuePost( $post_id_2, 1 );

		$estimated = $this->queue->getEstimatedCompletion();

		$this->assertNotNull( $estimated, 'Estimated completion should not be null' );
		$this->assertIsString( $estimated, 'Estimated completion should be a string' );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $estimated, 'Should be valid datetime format' );
	}

	/**
	 * Test getEstimatedCompletion returns null when no pending jobs.
	 */
	public function test_estimated_completion_empty_queue() {
		$estimated = $this->queue->getEstimatedCompletion();

		$this->assertNull( $estimated, 'Estimated completion should be null for empty queue' );
	}

	/**
	 * Test removeJob removes a specific job from the queue.
	 */
	public function test_remove_job() {
		$post_id_1 = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );
		$post_id_2 = $this->factory->post->create( array( 'post_type' => 'seo-page' ) );

		$this->queue->queuePost( $post_id_1, 0 );
		$this->queue->queuePost( $post_id_2, 1 );

		$result = $this->queue->removeJob( $post_id_1 );

		$this->assertTrue( $result, 'removeJob should return true' );

		$queued = $this->queue->getQueuedPosts();
		$this->assertCount( 1, $queued, 'Queue should contain 1 job after removal' );
		$this->assertEquals( $post_id_2, $queued[0]['post_id'], 'Remaining job should be post 2' );
	}

	/**
	 * Test removeJob returns false for non-existent job.
	 */
	public function test_remove_job_not_found() {
		$result = $this->queue->removeJob( 999999 );

		$this->assertFalse( $result, 'removeJob should return false for non-existent job' );
	}
}
