<?php
/**
 * Tests for ImportService
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Services;

use SEOGenerator\Services\ImportService;
use WP_UnitTestCase;

/**
 * Test ImportService functionality
 */
class ImportServiceTest extends WP_UnitTestCase {
	/**
	 * ImportService instance.
	 *
	 * @var ImportService
	 */
	private $import_service;

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->import_service = new ImportService();

		// Create test user.
		$this->user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $this->user_id );
	}

	/**
	 * Clean up test environment.
	 */
	public function tearDown(): void {
		// Clean up created posts.
		$posts = get_posts(
			array(
				'post_type'   => 'seo-page',
				'post_status' => 'any',
				'numberposts' => -1,
			)
		);

		foreach ( $posts as $post ) {
			wp_delete_post( $post->ID, true );
		}

		parent::tearDown();
	}

	/**
	 * Test successful post creation from single row.
	 */
	public function test_create_post_from_csv_row() {
		$headers = array( 'keyword', 'intent', 'search_volume' );
		$mapping = array(
			'keyword'       => 'page_title',
			'intent'        => 'topic_category',
			'search_volume' => 'skip',
		);

		$rows = array(
			array( 'Platinum Wedding Bands', 'commercial', '1000' ),
		);

		$result = $this->import_service->processSingleBatch( $rows, $headers, $mapping );

		// Assert post was created.
		$this->assertCount( 1, $result['created'] );
		$this->assertEmpty( $result['skipped'] );
		$this->assertEmpty( $result['errors'] );

		$post_id = $result['created'][0]['post_id'];

		// Verify post exists.
		$post = get_post( $post_id );
		$this->assertNotNull( $post );
		$this->assertEquals( 'Platinum Wedding Bands', $post->post_title );
		$this->assertEquals( 'seo-page', $post->post_type );
		$this->assertEquals( 'draft', $post->post_status );
	}

	/**
	 * Test batch processing with multiple rows.
	 */
	public function test_batch_processing() {
		$headers = array( 'keyword', 'intent' );
		$mapping = array(
			'keyword' => 'page_title',
			'intent'  => 'topic_category',
		);

		$rows = array(
			array( 'Platinum Wedding Bands', 'commercial' ),
			array( 'Custom Engagement Rings', 'commercial' ),
			array( 'Diamond Eternity Bands', 'commercial' ),
		);

		$result = $this->import_service->processSingleBatch( $rows, $headers, $mapping );

		// Assert all posts were created.
		$this->assertCount( 3, $result['created'] );
		$this->assertEmpty( $result['errors'] );
	}

	/**
	 * Test ACF field saving.
	 */
	public function test_acf_field_saving() {
		if ( ! function_exists( 'update_field' ) ) {
			$this->markTestSkipped( 'ACF not available' );
		}

		$headers = array( 'keyword', 'focus_keyword' );
		$mapping = array(
			'keyword'       => 'page_title',
			'focus_keyword' => 'focus_keyword',
		);

		$rows = array(
			array( 'Platinum Wedding Bands', 'platinum wedding bands' ),
		);

		$result = $this->import_service->processSingleBatch( $rows, $headers, $mapping );

		$post_id = $result['created'][0]['post_id'];

		// Verify ACF field was saved.
		$focus_keyword = get_field( 'seo_focus_keyword', $post_id );
		$this->assertEquals( 'platinum wedding bands', $focus_keyword );
	}

	/**
	 * Test taxonomy assignment with existing term.
	 */
	public function test_taxonomy_assignment_existing_term() {
		// Create existing term.
		$term = wp_insert_term( 'Product Reviews', 'seo-topic' );
		$this->assertNotInstanceOf( \WP_Error::class, $term );

		$headers = array( 'keyword', 'intent' );
		$mapping = array(
			'keyword' => 'page_title',
			'intent'  => 'topic_category',
		);

		$rows = array(
			array( 'Platinum Wedding Bands', 'commercial' ),
		);

		$result = $this->import_service->processSingleBatch( $rows, $headers, $mapping );

		$post_id = $result['created'][0]['post_id'];

		// Verify term was assigned.
		$terms = wp_get_object_terms( $post_id, 'seo-topic' );
		$this->assertCount( 1, $terms );
		$this->assertEquals( 'Product Reviews', $terms[0]->name );
	}

	/**
	 * Test taxonomy assignment with new term creation.
	 */
	public function test_taxonomy_assignment_new_term() {
		$headers = array( 'keyword', 'intent' );
		$mapping = array(
			'keyword' => 'page_title',
			'intent'  => 'topic_category',
		);

		$rows = array(
			array( 'Platinum Wedding Bands', 'informational' ),
		);

		$result = $this->import_service->processSingleBatch( $rows, $headers, $mapping );

		$post_id = $result['created'][0]['post_id'];

		// Verify term was created and assigned.
		$terms = wp_get_object_terms( $post_id, 'seo-topic' );
		$this->assertCount( 1, $terms );
		$this->assertEquals( 'How-To Guides', $terms[0]->name );
	}

	/**
	 * Test duplicate detection when enabled.
	 */
	public function test_duplicate_detection_enabled() {
		// Create existing post.
		$existing_id = wp_insert_post(
			array(
				'post_type'   => 'seo-page',
				'post_title'  => 'Platinum Wedding Bands',
				'post_status' => 'publish',
			)
		);

		$this->assertNotInstanceOf( \WP_Error::class, $existing_id );

		// Try to import duplicate with check_duplicates = true.
		$import_service = new ImportService( array( 'check_duplicates' => true ) );

		$headers = array( 'keyword' );
		$mapping = array( 'keyword' => 'page_title' );
		$rows    = array( array( 'Platinum Wedding Bands' ) );

		$result = $import_service->processSingleBatch( $rows, $headers, $mapping );

		// Assert post was skipped.
		$this->assertEmpty( $result['created'] );
		$this->assertCount( 1, $result['skipped'] );
		$this->assertEquals( 'Duplicate', $result['skipped'][0]['reason'] );
	}

	/**
	 * Test duplicate detection when disabled.
	 */
	public function test_duplicate_detection_disabled() {
		// Create existing post.
		$existing_id = wp_insert_post(
			array(
				'post_type'   => 'seo-page',
				'post_title'  => 'Platinum Wedding Bands',
				'post_status' => 'publish',
			)
		);

		$this->assertNotInstanceOf( \WP_Error::class, $existing_id );

		// Try to import duplicate with check_duplicates = false.
		$import_service = new ImportService( array( 'check_duplicates' => false ) );

		$headers = array( 'keyword' );
		$mapping = array( 'keyword' => 'page_title' );
		$rows    = array( array( 'Platinum Wedding Bands' ) );

		$result = $import_service->processSingleBatch( $rows, $headers, $mapping );

		// Assert post was created despite duplicate.
		$this->assertCount( 1, $result['created'] );
		$this->assertEmpty( $result['skipped'] );
	}

	/**
	 * Test missing title error.
	 */
	public function test_missing_title_error() {
		$headers = array( 'keyword', 'intent' );
		$mapping = array(
			'keyword' => 'page_title',
			'intent'  => 'topic_category',
		);

		$rows = array(
			array( '', 'commercial' ), // Empty title.
		);

		$result = $this->import_service->processSingleBatch( $rows, $headers, $mapping );

		// Assert error was recorded.
		$this->assertEmpty( $result['created'] );
		$this->assertCount( 1, $result['errors'] );
		$this->assertStringContainsString( 'missing page title', strtolower( $result['errors'][0]['error'] ) );
	}

	/**
	 * Test progress tracking.
	 */
	public function test_progress_tracking() {
		$this->import_service->updateProgress( 2, 5, 20, 50 );

		$progress = get_transient( 'import_progress_' . $this->user_id );

		$this->assertNotFalse( $progress );
		$this->assertEquals( 2, $progress['current_batch'] );
		$this->assertEquals( 5, $progress['total_batches'] );
		$this->assertEquals( 20, $progress['rows_processed'] );
		$this->assertEquals( 50, $progress['rows_total'] );
		$this->assertEquals( 40, $progress['percentage'] );
	}

	/**
	 * Test memory optimization.
	 */
	public function test_memory_optimization() {
		$available = $this->import_service->getAvailableMemory();

		$this->assertIsInt( $available );
		$this->assertGreaterThan( 0, $available );
	}

	/**
	 * Test error handling continues.
	 */
	public function test_error_handling_continues() {
		$headers = array( 'keyword' );
		$mapping = array( 'keyword' => 'page_title' );

		$rows = array(
			array( 'Valid Post Title' ),
			array( '' ), // Empty title - should error.
			array( 'Another Valid Post' ),
		);

		$result = $this->import_service->processSingleBatch( $rows, $headers, $mapping );

		// Assert 2 posts created and 1 error.
		$this->assertCount( 2, $result['created'] );
		$this->assertCount( 1, $result['errors'] );
	}

	/**
	 * Test import summary structure.
	 */
	public function test_import_summary_structure() {
		$headers = array( 'keyword' );
		$mapping = array( 'keyword' => 'page_title' );
		$rows    = array( array( 'Test Post' ) );

		$result = $this->import_service->processSingleBatch( $rows, $headers, $mapping );

		// Assert structure.
		$this->assertArrayHasKey( 'created', $result );
		$this->assertArrayHasKey( 'skipped', $result );
		$this->assertArrayHasKey( 'errors', $result );

		$this->assertIsArray( $result['created'] );
		$this->assertIsArray( $result['skipped'] );
		$this->assertIsArray( $result['errors'] );
	}
}
