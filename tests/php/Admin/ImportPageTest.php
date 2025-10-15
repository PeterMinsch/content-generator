<?php
/**
 * Tests for ImportPage class
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Admin;

use SEOGenerator\Admin\ImportPage;
use WP_UnitTestCase;

/**
 * Test ImportPage functionality
 */
class ImportPageTest extends WP_UnitTestCase {
	/**
	 * ImportPage instance.
	 *
	 * @var ImportPage
	 */
	private $import_page;

	/**
	 * Test file path.
	 *
	 * @var string
	 */
	private $test_csv_file;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->import_page = new ImportPage();

		// Create test CSV file.
		$this->test_csv_file = __DIR__ . '/../../fixtures/test-import.csv';
		if ( ! file_exists( $this->test_csv_file ) ) {
			file_put_contents(
				$this->test_csv_file,
				"keyword,intent,search_volume\nplatinum wedding bands,commercial,1000\nmens tungsten rings,commercial,800"
			);
		}
	}

	/**
	 * Clean up test environment.
	 */
	public function tearDown(): void {
		// Clean up uploaded files.
		$upload_dir = wp_upload_dir();
		$files      = glob( $upload_dir['path'] . '/import_*.csv' );
		foreach ( $files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}

		// Clean up transients.
		delete_transient( 'import_file_' . get_current_user_id() );

		parent::tearDown();
	}

	/**
	 * Test render method requires correct capability.
	 */
	public function test_render_requires_edit_posts_capability() {
		// Create subscriber user (no edit_posts capability).
		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		// Expect wp_die to be called.
		$this->expectException( \WPDieException::class );

		// Try to render.
		$this->import_page->render();
	}

	/**
	 * Test render method works for authorized user.
	 */
	public function test_render_works_for_authorized_user() {
		// Create editor user (has edit_posts capability).
		$editor = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		// Render should not throw exception.
		ob_start();
		try {
			$this->import_page->render();
			$output = ob_get_clean();
			// Template should load (or show error if template missing).
			$this->assertNotEmpty( $output );
		} catch ( \Exception $e ) {
			ob_end_clean();
			$this->fail( 'Render should not throw exception for authorized user: ' . $e->getMessage() );
		}
	}

	/**
	 * Test handleUpload accepts valid CSV files.
	 */
	public function test_handleUpload_accepts_valid_csv() {
		// Create editor user.
		$editor = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		// Set up nonce.
		$_REQUEST['seo_csv_nonce'] = wp_create_nonce( 'seo_csv_upload' );

		// Mock file upload.
		$_FILES['csv_file'] = array(
			'name'     => 'test.csv',
			'type'     => 'text/csv',
			'tmp_name' => $this->test_csv_file,
			'error'    => UPLOAD_ERR_OK,
			'size'     => filesize( $this->test_csv_file ),
		);

		// Handle upload.
		$result = $this->import_page->handleUpload();

		// Assert success.
		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'file_path', $result );
		$this->assertFileExists( $result['file_path'] );

		// Verify transient was set.
		$transient = get_transient( 'import_file_' . get_current_user_id() );
		$this->assertEquals( $result['file_path'], $transient );
	}

	/**
	 * Test handleUpload rejects non-CSV file extensions.
	 */
	public function test_handleUpload_rejects_non_csv_extension() {
		// Create editor user.
		$editor = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		// Set up nonce.
		$_REQUEST['seo_csv_nonce'] = wp_create_nonce( 'seo_csv_upload' );

		// Mock file upload with .txt extension.
		$_FILES['csv_file'] = array(
			'name'     => 'test.txt',
			'type'     => 'text/plain',
			'tmp_name' => $this->test_csv_file,
			'error'    => UPLOAD_ERR_OK,
			'size'     => filesize( $this->test_csv_file ),
		);

		// Handle upload.
		$result = $this->import_page->handleUpload();

		// Assert failure.
		$this->assertFalse( $result['success'] );
		$this->assertArrayHasKey( 'error', $result );
		$this->assertStringContainsString( 'csv', strtolower( $result['error'] ) );
	}

	/**
	 * Test handleUpload rejects files exceeding size limit.
	 */
	public function test_handleUpload_rejects_oversized_files() {
		// Create editor user.
		$editor = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		// Set up nonce.
		$_REQUEST['seo_csv_nonce'] = wp_create_nonce( 'seo_csv_upload' );

		// Mock file upload with size exceeding limit.
		$_FILES['csv_file'] = array(
			'name'     => 'test.csv',
			'type'     => 'text/csv',
			'tmp_name' => $this->test_csv_file,
			'error'    => UPLOAD_ERR_OK,
			'size'     => wp_max_upload_size() + 1000, // Exceed limit.
		);

		// Handle upload.
		$result = $this->import_page->handleUpload();

		// Assert failure.
		$this->assertFalse( $result['success'] );
		$this->assertArrayHasKey( 'error', $result );
		$this->assertStringContainsString( 'size', strtolower( $result['error'] ) );
	}

	/**
	 * Test handleUpload returns error when no file uploaded.
	 */
	public function test_handleUpload_returns_error_for_missing_file() {
		// Create editor user.
		$editor = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		// Set up nonce.
		$_REQUEST['seo_csv_nonce'] = wp_create_nonce( 'seo_csv_upload' );

		// Don't set $_FILES.

		// Handle upload.
		$result = $this->import_page->handleUpload();

		// Assert failure.
		$this->assertFalse( $result['success'] );
		$this->assertArrayHasKey( 'error', $result );
	}

	/**
	 * Test handleUpload handles upload errors gracefully.
	 */
	public function test_handleUpload_handles_upload_errors() {
		// Create editor user.
		$editor = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		// Set up nonce.
		$_REQUEST['seo_csv_nonce'] = wp_create_nonce( 'seo_csv_upload' );

		// Mock file upload with error.
		$_FILES['csv_file'] = array(
			'name'     => 'test.csv',
			'type'     => 'text/csv',
			'tmp_name' => '',
			'error'    => UPLOAD_ERR_INI_SIZE,
			'size'     => 0,
		);

		// Handle upload.
		$result = $this->import_page->handleUpload();

		// Assert failure.
		$this->assertFalse( $result['success'] );
		$this->assertArrayHasKey( 'error', $result );
	}

	/**
	 * Test handleUpload sanitizes malicious filenames.
	 */
	public function test_handleUpload_sanitizes_malicious_filename() {
		// Create editor user.
		$editor = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		// Set up nonce.
		$_REQUEST['seo_csv_nonce'] = wp_create_nonce( 'seo_csv_upload' );

		// Mock file upload with malicious filename.
		$_FILES['csv_file'] = array(
			'name'     => '../../../evil.csv',
			'type'     => 'text/csv',
			'tmp_name' => $this->test_csv_file,
			'error'    => UPLOAD_ERR_OK,
			'size'     => filesize( $this->test_csv_file ),
		);

		// Handle upload.
		$result = $this->import_page->handleUpload();

		// Assert success (filename should be sanitized).
		$this->assertTrue( $result['success'] );

		// Verify file path doesn't contain directory traversal.
		$this->assertStringNotContainsString( '..', $result['file_path'] );
		$this->assertStringStartsWith( wp_upload_dir()['path'], $result['file_path'] );
	}

	/**
	 * Test handleUpload requires valid nonce.
	 */
	public function test_handleUpload_requires_valid_nonce() {
		// Create editor user.
		$editor = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		// Set up invalid nonce.
		$_REQUEST['seo_csv_nonce'] = 'invalid_nonce';

		// Mock file upload.
		$_FILES['csv_file'] = array(
			'name'     => 'test.csv',
			'type'     => 'text/csv',
			'tmp_name' => $this->test_csv_file,
			'error'    => UPLOAD_ERR_OK,
			'size'     => filesize( $this->test_csv_file ),
		);

		// Expect nonce verification to fail.
		$this->expectException( \WPDieException::class );

		// Handle upload.
		$this->import_page->handleUpload();
	}

	/**
	 * Test parseCSVHeaders returns headers and preview rows for valid CSV.
	 */
	public function test_parseCSVHeaders_returns_headers_and_preview() {
		$result = $this->import_page->parseCSVHeaders( $this->test_csv_file );

		// Assert success.
		$this->assertArrayHasKey( 'headers', $result );
		$this->assertArrayHasKey( 'preview_rows', $result );

		// Verify headers.
		$this->assertEquals( array( 'keyword', 'intent', 'search_volume' ), $result['headers'] );

		// Verify preview rows (should have 2 rows from test data).
		$this->assertCount( 2, $result['preview_rows'] );
		$this->assertEquals( 'platinum wedding bands', $result['preview_rows'][0][0] );
	}

	/**
	 * Test parseCSVHeaders returns error for non-existent file.
	 */
	public function test_parseCSVHeaders_returns_error_for_missing_file() {
		$result = $this->import_page->parseCSVHeaders( '/nonexistent/file.csv' );

		// Assert error.
		$this->assertArrayHasKey( 'error', $result );
		$this->assertStringContainsString( 'not found', strtolower( $result['error'] ) );
	}

	/**
	 * Test parseCSVHeaders handles CSV with only headers.
	 */
	public function test_parseCSVHeaders_handles_headers_only() {
		// Create CSV with only headers.
		$headers_only_file = __DIR__ . '/../../fixtures/test-headers-only.csv';
		file_put_contents( $headers_only_file, 'keyword,intent,search_volume' );

		$result = $this->import_page->parseCSVHeaders( $headers_only_file );

		// Assert success with empty preview.
		$this->assertArrayHasKey( 'headers', $result );
		$this->assertArrayHasKey( 'preview_rows', $result );
		$this->assertEquals( array( 'keyword', 'intent', 'search_volume' ), $result['headers'] );
		$this->assertEmpty( $result['preview_rows'] );

		// Clean up.
		unlink( $headers_only_file );
	}

	/**
	 * Test detectColumnMappings auto-detects common column names.
	 */
	public function test_detectColumnMappings_detects_common_columns() {
		$headers = array( 'keyword', 'intent', 'search_volume', 'image_url' );
		$result  = $this->import_page->detectColumnMappings( $headers );

		// Assert mappings.
		$this->assertEquals( 'page_title', $result['keyword'] );
		$this->assertEquals( 'topic_category', $result['intent'] );
		$this->assertEquals( 'skip', $result['search_volume'] );
		$this->assertEquals( 'image_url', $result['image_url'] );
	}

	/**
	 * Test detectColumnMappings is case-insensitive.
	 */
	public function test_detectColumnMappings_is_case_insensitive() {
		$headers = array( 'KEYWORD', 'Intent', 'SEARCH_VOLUME' );
		$result  = $this->import_page->detectColumnMappings( $headers );

		// Assert mappings work regardless of case.
		$this->assertEquals( 'page_title', $result['KEYWORD'] );
		$this->assertEquals( 'topic_category', $result['Intent'] );
		$this->assertEquals( 'skip', $result['SEARCH_VOLUME'] );
	}

	/**
	 * Test detectColumnMappings defaults unknown columns to skip.
	 */
	public function test_detectColumnMappings_defaults_unknown_to_skip() {
		$headers = array( 'unknown_column', 'random_data' );
		$result  = $this->import_page->detectColumnMappings( $headers );

		// Assert unknown columns default to 'skip'.
		$this->assertEquals( 'skip', $result['unknown_column'] );
		$this->assertEquals( 'skip', $result['random_data'] );
	}

	/**
	 * Test validateMapping returns valid when page_title is present.
	 */
	public function test_validateMapping_returns_valid_with_page_title() {
		$mapping = array(
			'keyword'       => 'page_title',
			'intent'        => 'topic_category',
			'search_volume' => 'skip',
		);

		$result = $this->import_page->validateMapping( $mapping );

		// Assert valid.
		$this->assertTrue( $result['valid'] );
	}

	/**
	 * Test validateMapping returns invalid without page_title.
	 */
	public function test_validateMapping_returns_invalid_without_page_title() {
		$mapping = array(
			'intent'        => 'topic_category',
			'search_volume' => 'skip',
		);

		$result = $this->import_page->validateMapping( $mapping );

		// Assert invalid.
		$this->assertFalse( $result['valid'] );
		$this->assertArrayHasKey( 'error', $result );
		$this->assertStringContainsString( 'Page Title', $result['error'] );
	}
}
