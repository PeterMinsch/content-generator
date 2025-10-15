<?php
/**
 * Import Log Repository Tests
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Repositories;

use SEOGenerator\Repositories\ImportLogRepository;
use WP_UnitTestCase;

/**
 * Test Import Log Repository
 */
class ImportLogRepositoryTest extends WP_UnitTestCase {

	/**
	 * Repository instance.
	 *
	 * @var ImportLogRepository
	 */
	private $repository;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->repository = new ImportLogRepository();
		$this->repository->createTable();
	}

	/**
	 * Tear down test.
	 */
	public function tearDown(): void {
		$this->repository->deleteAll();
		parent::tearDown();
	}

	/**
	 * Test table creation.
	 */
	public function testTableCreation(): void {
		$this->assertTrue( $this->repository->tableExists() );
	}

	/**
	 * Test saving import log.
	 */
	public function testSaveImportLog(): void {
		$log_data = array(
			'filename'       => 'test.csv',
			'total_rows'     => 100,
			'success_count'  => 95,
			'error_count'    => 5,
			'user_id'        => 1,
			'error_log'      => array( 'Row 10: Error 1', 'Row 20: Error 2' ),
			'created_posts'  => array(
				array( 'id' => 123, 'title' => 'Test Post 1' ),
				array( 'id' => 124, 'title' => 'Test Post 2' ),
			),
			'image_stats'    => array(
				'total'      => 95,
				'downloaded' => 90,
				'failed'     => 5,
			),
		);

		$log_id = $this->repository->save( $log_data );

		$this->assertIsInt( $log_id );
		$this->assertGreaterThan( 0, $log_id );
	}

	/**
	 * Test finding import log by ID.
	 */
	public function testFindById(): void {
		// Create log.
		$log_data = array(
			'filename'       => 'test.csv',
			'total_rows'     => 50,
			'success_count'  => 48,
			'error_count'    => 2,
			'user_id'        => 1,
			'error_log'      => array( 'Row 5: Error' ),
			'created_posts'  => array( array( 'id' => 125, 'title' => 'Test' ) ),
		);

		$log_id = $this->repository->save( $log_data );

		// Retrieve log.
		$retrieved = $this->repository->findById( $log_id );

		$this->assertIsArray( $retrieved );
		$this->assertEquals( 'test.csv', $retrieved['filename'] );
		$this->assertEquals( 50, $retrieved['total_rows'] );
		$this->assertEquals( 48, $retrieved['success_count'] );
		$this->assertEquals( 2, $retrieved['error_count'] );
		$this->assertIsArray( $retrieved['error_log'] );
		$this->assertCount( 1, $retrieved['error_log'] );
	}

	/**
	 * Test finding all logs with pagination.
	 */
	public function testFindAllWithPagination(): void {
		// Create multiple logs.
		for ( $i = 1; $i <= 15; $i++ ) {
			$this->repository->save(
				array(
					'filename'       => "test_{$i}.csv",
					'total_rows'     => $i * 10,
					'success_count'  => $i * 9,
					'error_count'    => $i,
					'user_id'        => 1,
					'error_log'      => array(),
					'created_posts'  => array(),
				)
			);
		}

		// Get first page (10 logs).
		$logs = $this->repository->findAll( 10, 0 );

		$this->assertCount( 10, $logs );

		// Get second page (5 logs).
		$logs_page2 = $this->repository->findAll( 10, 10 );

		$this->assertCount( 5, $logs_page2 );
	}

	/**
	 * Test counting total logs.
	 */
	public function testCount(): void {
		// Initially empty.
		$this->assertEquals( 0, $this->repository->count() );

		// Add 3 logs.
		for ( $i = 1; $i <= 3; $i++ ) {
			$this->repository->save(
				array(
					'filename'       => "test_{$i}.csv",
					'total_rows'     => 10,
					'success_count'  => 9,
					'error_count'    => 1,
					'user_id'        => 1,
					'error_log'      => array(),
					'created_posts'  => array(),
				)
			);
		}

		$this->assertEquals( 3, $this->repository->count() );
	}

	/**
	 * Test deleting old logs.
	 */
	public function testDeleteOlderThan(): void {
		global $wpdb;

		// Create logs with different timestamps.
		// Recent log (today).
		$this->repository->save(
			array(
				'filename'       => 'recent.csv',
				'total_rows'     => 10,
				'success_count'  => 10,
				'error_count'    => 0,
				'user_id'        => 1,
				'error_log'      => array(),
				'created_posts'  => array(),
			)
		);

		// Old log (100 days ago) - manually set timestamp.
		$old_log_id = $this->repository->save(
			array(
				'filename'       => 'old.csv',
				'total_rows'     => 10,
				'success_count'  => 10,
				'error_count'    => 0,
				'user_id'        => 1,
				'error_log'      => array(),
				'created_posts'  => array(),
			)
		);

		// Update timestamp to 100 days ago.
		$old_timestamp = gmdate( 'Y-m-d H:i:s', strtotime( '-100 days' ) );
		$table_name    = $wpdb->prefix . 'seo_import_log';
		$wpdb->update(
			$table_name,
			array( 'timestamp' => $old_timestamp ),
			array( 'id' => $old_log_id ),
			array( '%s' ),
			array( '%d' )
		);

		// Delete logs older than 90 days.
		$deleted = $this->repository->deleteOlderThan( 90 );

		$this->assertEquals( 1, $deleted );
		$this->assertEquals( 1, $this->repository->count() );
	}

	/**
	 * Test deleting all logs.
	 */
	public function testDeleteAll(): void {
		// Add logs.
		for ( $i = 1; $i <= 5; $i++ ) {
			$this->repository->save(
				array(
					'filename'       => "test_{$i}.csv",
					'total_rows'     => 10,
					'success_count'  => 9,
					'error_count'    => 1,
					'user_id'        => 1,
					'error_log'      => array(),
					'created_posts'  => array(),
				)
			);
		}

		$this->assertEquals( 5, $this->repository->count() );

		// Delete all.
		$deleted = $this->repository->deleteAll();

		$this->assertEquals( 5, $deleted );
		$this->assertEquals( 0, $this->repository->count() );
	}

	/**
	 * Test array serialization in error_log.
	 */
	public function testArraySerialization(): void {
		$error_log = array(
			'Row 1: Missing title',
			'Row 5: Invalid URL',
			'Row 10: Duplicate post',
		);

		$log_id = $this->repository->save(
			array(
				'filename'       => 'errors.csv',
				'total_rows'     => 20,
				'success_count'  => 17,
				'error_count'    => 3,
				'user_id'        => 1,
				'error_log'      => $error_log,
				'created_posts'  => array(),
			)
		);

		$retrieved = $this->repository->findById( $log_id );

		$this->assertIsArray( $retrieved['error_log'] );
		$this->assertCount( 3, $retrieved['error_log'] );
		$this->assertEquals( 'Row 1: Missing title', $retrieved['error_log'][0] );
	}

	/**
	 * Test handling null or empty data.
	 */
	public function testHandlingEmptyData(): void {
		$log_id = $this->repository->save(
			array(
				'filename'       => 'empty.csv',
				'total_rows'     => 0,
				'success_count'  => 0,
				'error_count'    => 0,
				'user_id'        => 1,
			)
		);

		$retrieved = $this->repository->findById( $log_id );

		$this->assertIsArray( $retrieved );
		$this->assertIsArray( $retrieved['error_log'] );
		$this->assertEmpty( $retrieved['error_log'] );
		$this->assertIsArray( $retrieved['created_posts'] );
		$this->assertEmpty( $retrieved['created_posts'] );
	}
}
