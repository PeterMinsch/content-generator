<?php
/**
 * Tests for Cost Tracking Service
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Services;

use SEOGenerator\Services\CostTrackingService;
use SEOGenerator\Repositories\GenerationLogRepository;
use SEOGenerator\Exceptions\BudgetExceededException;
use WP_UnitTestCase;

/**
 * Cost Tracking Service test case.
 */
class CostTrackingServiceTest extends WP_UnitTestCase {
	/**
	 * Cost tracking service instance.
	 *
	 * @var CostTrackingService
	 */
	private $service;

	/**
	 * Repository instance.
	 *
	 * @var GenerationLogRepository
	 */
	private $repository;

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

		$this->repository = new GenerationLogRepository();
		$this->service    = new CostTrackingService( $this->repository );

		// Create test post.
		$this->post_id = $this->factory->post->create(
			array(
				'post_type'  => 'seo-page',
				'post_title' => 'Test Page',
			)
		);

		// Create test user.
		$this->user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->user_id );

		// Set default settings.
		update_option(
			'seo_generator_settings',
			array(
				'enable_cost_tracking'    => true,
				'monthly_budget'          => 100.00,
				'alert_threshold_percent' => 80,
			)
		);
	}

	/**
	 * Test cost calculation for GPT-4 Turbo.
	 */
	public function test_cost_calculation_gpt4_turbo() {
		$cost = $this->service->calculateCost( 1000, 1000, 'gpt-4-turbo-preview' );

		// (1000/1000 * 0.01) + (1000/1000 * 0.03) = 0.01 + 0.03 = 0.04
		$this->assertEquals( 0.04, $cost );
	}

	/**
	 * Test cost calculation for GPT-4.
	 */
	public function test_cost_calculation_gpt4() {
		$cost = $this->service->calculateCost( 1000, 1000, 'gpt-4' );

		// (1000/1000 * 0.03) + (1000/1000 * 0.06) = 0.03 + 0.06 = 0.09
		$this->assertEquals( 0.09, $cost );
	}

	/**
	 * Test cost calculation for GPT-3.5 Turbo.
	 */
	public function test_cost_calculation_gpt35_turbo() {
		$cost = $this->service->calculateCost( 1000, 1000, 'gpt-3.5-turbo' );

		// (1000/1000 * 0.0015) + (1000/1000 * 0.002) = 0.0015 + 0.002 = 0.0035
		$this->assertEquals( 0.0035, $cost );
	}

	/**
	 * Test log generation inserts record.
	 */
	public function test_log_generation_inserts_record() {
		$log_data = array(
			'post_id'           => $this->post_id,
			'block_type'        => 'hero',
			'prompt_tokens'     => 100,
			'completion_tokens' => 50,
			'total_tokens'      => 150,
			'cost'              => 0.004,
			'model'             => 'gpt-4-turbo-preview',
			'status'            => 'success',
			'error_message'     => null,
			'user_id'           => $this->user_id,
		);

		$insert_id = $this->service->logGeneration( $log_data );

		$this->assertGreaterThan( 0, $insert_id );

		// Verify log was inserted.
		$logs = $this->repository->getByPostId( $this->post_id );
		$this->assertCount( 1, $logs );
		$this->assertEquals( 'hero', $logs[0]['block_type'] );
	}

	/**
	 * Test get current month cost.
	 */
	public function test_get_current_month_cost() {
		// Insert some logs for current month.
		$this->service->logGeneration(
			array(
				'post_id'           => $this->post_id,
				'block_type'        => 'hero',
				'prompt_tokens'     => 100,
				'completion_tokens' => 50,
				'total_tokens'      => 150,
				'cost'              => 1.50,
				'model'             => 'gpt-4',
				'status'            => 'success',
				'error_message'     => null,
				'user_id'           => $this->user_id,
			)
		);

		$this->service->logGeneration(
			array(
				'post_id'           => $this->post_id,
				'block_type'        => 'serp_answer',
				'prompt_tokens'     => 100,
				'completion_tokens' => 50,
				'total_tokens'      => 150,
				'cost'              => 2.50,
				'model'             => 'gpt-4',
				'status'            => 'success',
				'error_message'     => null,
				'user_id'           => $this->user_id,
			)
		);

		$cost = $this->service->getCurrentMonthCost();

		$this->assertEquals( 4.00, $cost );
	}

	/**
	 * Test budget limit blocks when exceeded.
	 */
	public function test_budget_limit_blocks_when_exceeded() {
		// Set low budget.
		update_option(
			'seo_generator_settings',
			array(
				'enable_cost_tracking' => true,
				'monthly_budget'       => 5.00,
			)
		);

		// Add logs exceeding budget.
		$this->service->logGeneration(
			array(
				'post_id'           => $this->post_id,
				'block_type'        => 'hero',
				'prompt_tokens'     => 100,
				'completion_tokens' => 50,
				'total_tokens'      => 150,
				'cost'              => 6.00,
				'model'             => 'gpt-4',
				'status'            => 'success',
				'error_message'     => null,
				'user_id'           => $this->user_id,
			)
		);

		$this->expectException( BudgetExceededException::class );
		$this->expectExceptionMessage( 'Monthly budget limit reached' );

		$this->service->checkBudgetLimit();
	}

	/**
	 * Test budget limit allows when under budget.
	 */
	public function test_budget_limit_allows_when_under_budget() {
		// Set high budget.
		update_option(
			'seo_generator_settings',
			array(
				'enable_cost_tracking' => true,
				'monthly_budget'       => 100.00,
			)
		);

		// Add log below budget.
		$this->service->logGeneration(
			array(
				'post_id'           => $this->post_id,
				'block_type'        => 'hero',
				'prompt_tokens'     => 100,
				'completion_tokens' => 50,
				'total_tokens'      => 150,
				'cost'              => 1.00,
				'model'             => 'gpt-4',
				'status'            => 'success',
				'error_message'     => null,
				'user_id'           => $this->user_id,
			)
		);

		// Should not throw exception.
		$this->service->checkBudgetLimit();

		$this->assertTrue( true ); // If we get here, test passed.
	}

	/**
	 * Test budget checking disabled when setting is off.
	 */
	public function test_budget_checking_disabled_when_setting_off() {
		// Disable cost tracking.
		update_option(
			'seo_generator_settings',
			array(
				'enable_cost_tracking' => false,
				'monthly_budget'       => 5.00,
			)
		);

		// Add log exceeding budget.
		$this->service->logGeneration(
			array(
				'post_id'           => $this->post_id,
				'block_type'        => 'hero',
				'prompt_tokens'     => 100,
				'completion_tokens' => 50,
				'total_tokens'      => 150,
				'cost'              => 10.00,
				'model'             => 'gpt-4',
				'status'            => 'success',
				'error_message'     => null,
				'user_id'           => $this->user_id,
			)
		);

		// Should not throw exception when disabled.
		$this->service->checkBudgetLimit();

		$this->assertTrue( true );
	}

	/**
	 * Test budget checking allows unlimited when budget is 0.
	 */
	public function test_budget_allows_unlimited_when_zero() {
		update_option(
			'seo_generator_settings',
			array(
				'enable_cost_tracking' => true,
				'monthly_budget'       => 0,
			)
		);

		// Add expensive log.
		$this->service->logGeneration(
			array(
				'post_id'           => $this->post_id,
				'block_type'        => 'hero',
				'prompt_tokens'     => 100,
				'completion_tokens' => 50,
				'total_tokens'      => 150,
				'cost'              => 999.00,
				'model'             => 'gpt-4',
				'status'            => 'success',
				'error_message'     => null,
				'user_id'           => $this->user_id,
			)
		);

		// Should not throw exception when budget is 0.
		$this->service->checkBudgetLimit();

		$this->assertTrue( true );
	}

	/**
	 * Test cleanup deletes old logs.
	 */
	public function test_cleanup_deletes_old_logs() {
		// Insert old log (31 days ago).
		global $wpdb;
		$table_name = $wpdb->prefix . 'seo_generation_log';

		$wpdb->insert(
			$table_name,
			array(
				'post_id'           => $this->post_id,
				'block_type'        => 'hero',
				'prompt_tokens'     => 100,
				'completion_tokens' => 50,
				'total_tokens'      => 150,
				'cost'              => 1.00,
				'model'             => 'gpt-4',
				'status'            => 'success',
				'error_message'     => null,
				'user_id'           => $this->user_id,
				'created_at'        => gmdate( 'Y-m-d H:i:s', strtotime( '-31 days' ) ),
			),
			array( '%d', '%s', '%d', '%d', '%d', '%f', '%s', '%s', '%s', '%d', '%s' )
		);

		$old_id = $wpdb->insert_id;

		// Insert recent log.
		$this->service->logGeneration(
			array(
				'post_id'           => $this->post_id,
				'block_type'        => 'serp_answer',
				'prompt_tokens'     => 100,
				'completion_tokens' => 50,
				'total_tokens'      => 150,
				'cost'              => 1.00,
				'model'             => 'gpt-4',
				'status'            => 'success',
				'error_message'     => null,
				'user_id'           => $this->user_id,
			)
		);

		// Run cleanup.
		$deleted = $this->service->cleanupOldLogs( 30 );

		$this->assertEquals( 1, $deleted );

		// Verify old log is deleted.
		$old_log = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $old_id )
		);
		$this->assertNull( $old_log );

		// Verify recent log still exists.
		$logs = $this->repository->getByPostId( $this->post_id );
		$this->assertCount( 1, $logs );
		$this->assertEquals( 'serp_answer', $logs[0]['block_type'] );
	}

	/**
	 * Test get post statistics.
	 */
	public function test_get_post_statistics() {
		// Insert multiple logs.
		for ( $i = 0; $i < 3; $i++ ) {
			$this->service->logGeneration(
				array(
					'post_id'           => $this->post_id,
					'block_type'        => 'hero',
					'prompt_tokens'     => 100,
					'completion_tokens' => 50,
					'total_tokens'      => 150,
					'cost'              => 2.00,
					'model'             => 'gpt-4',
					'status'            => 'success',
					'error_message'     => null,
					'user_id'           => $this->user_id,
				)
			);
		}

		$stats = $this->service->getPostStatistics( $this->post_id );

		$this->assertEquals( 3, $stats['total_generations'] );
		$this->assertEquals( 450, $stats['total_tokens'] );
		$this->assertEquals( 6.00, $stats['total_cost'] );
		$this->assertEquals( 2.00, $stats['avg_cost'] );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		// Clean up logs.
		global $wpdb;
		$table_name = $wpdb->prefix . 'seo_generation_log';
		$wpdb->query( "TRUNCATE TABLE {$table_name}" );

		// Clear transients.
		delete_transient( 'seo_gen_budget_alert_' . gmdate( 'Y-m' ) );
		wp_cache_delete( 'seo_gen_month_cost' );

		wp_delete_post( $this->post_id, true );
		parent::tearDown();
	}
}
