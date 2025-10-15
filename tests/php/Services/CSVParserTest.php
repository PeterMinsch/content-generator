<?php
/**
 * Tests for CSVParser service
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Services;

use SEOGenerator\Services\CSVParser;
use WP_UnitTestCase;

/**
 * Test CSVParser functionality
 */
class CSVParserTest extends WP_UnitTestCase {
	/**
	 * CSVParser instance.
	 *
	 * @var CSVParser
	 */
	private $parser;

	/**
	 * Fixtures directory.
	 *
	 * @var string
	 */
	private $fixtures_dir;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->parser       = new CSVParser();
		$this->fixtures_dir = dirname( dirname( __DIR__ ) ) . '/fixtures/csv';
	}

	/**
	 * Test successful parsing of valid UTF-8 CSV.
	 */
	public function test_parse_valid_utf8_csv() {
		$file   = $this->fixtures_dir . '/valid-utf8.csv';
		$result = $this->parser->parse( $file );

		// Assert not WP_Error.
		$this->assertNotInstanceOf( \WP_Error::class, $result );

		// Assert structure.
		$this->assertArrayHasKey( 'headers', $result );
		$this->assertArrayHasKey( 'rows', $result );
		$this->assertArrayHasKey( 'errors', $result );
		$this->assertArrayHasKey( 'metadata', $result );

		// Assert headers.
		$this->assertEquals( array( 'keyword', 'intent', 'search_volume' ), $result['headers'] );

		// Assert rows.
		$this->assertCount( 3, $result['rows'] );
		$this->assertEquals( 'platinum wedding bands', $result['rows'][0][0] );

		// Assert no errors.
		$this->assertEmpty( $result['errors'] );

		// Assert metadata.
		$this->assertEquals( 3, $result['metadata']['valid_rows'] );
		$this->assertEquals( 'UTF-8', $result['metadata']['encoding'] );
	}

	/**
	 * Test delimiter auto-detection with semicolon.
	 */
	public function test_delimiter_detection_semicolon() {
		$file   = $this->fixtures_dir . '/semicolon-delimiter.csv';
		$result = $this->parser->parse( $file );

		$this->assertNotInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( ';', $result['metadata']['delimiter'] );
		$this->assertEquals( 'platinum wedding bands', $result['rows'][0][0] );
	}

	/**
	 * Test delimiter auto-detection with tab.
	 */
	public function test_delimiter_detection_tab() {
		$file   = $this->fixtures_dir . '/tab-delimiter.csv';
		$result = $this->parser->parse( $file );

		$this->assertNotInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'tab', $result['metadata']['delimiter'] );
		$this->assertEquals( 'platinum wedding bands', $result['rows'][0][0] );
	}

	/**
	 * Test quoted fields with embedded commas.
	 */
	public function test_quoted_fields_with_commas() {
		$file   = $this->fixtures_dir . '/quoted-fields.csv';
		$result = $this->parser->parse( $file );

		$this->assertNotInstanceOf( \WP_Error::class, $result );

		// Assert quoted field preserved with comma.
		$this->assertEquals( 'platinum wedding bands, 14k gold', $result['rows'][0][0] );
		$this->assertEquals( "men's tungsten rings, black", $result['rows'][1][0] );
	}

	/**
	 * Test empty rows are skipped.
	 */
	public function test_empty_rows_skipped() {
		$file   = $this->fixtures_dir . '/empty-rows.csv';
		$result = $this->parser->parse( $file );

		$this->assertNotInstanceOf( \WP_Error::class, $result );

		// Should have 3 valid rows (empty rows skipped).
		$this->assertCount( 3, $result['rows'] );
		$this->assertEquals( 'platinum wedding bands', $result['rows'][0][0] );
		$this->assertEquals( "men's tungsten rings", $result['rows'][1][0] );
		$this->assertEquals( 'custom engagement rings', $result['rows'][2][0] );
	}

	/**
	 * Test file not found error.
	 */
	public function test_file_not_found() {
		$result = $this->parser->parse( '/nonexistent/file.csv' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'file_not_found', $result->get_error_code() );
	}

	/**
	 * Test file with only headers (no data rows).
	 */
	public function test_headers_only_returns_error() {
		$file   = $this->fixtures_dir . '/headers-only.csv';
		$result = $this->parser->parse( $file );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'no_valid_rows', $result->get_error_code() );
	}

	/**
	 * Test maximum row limit enforcement.
	 */
	public function test_maximum_row_limit() {
		// Create parser with low limit for testing.
		$parser = new CSVParser( array( 'max_rows' => 2 ) );

		$file   = $this->fixtures_dir . '/valid-utf8.csv'; // Has 3 rows.
		$result = $parser->parse( $file );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'row_limit_exceeded', $result->get_error_code() );
	}

	/**
	 * Test required column validation.
	 */
	public function test_missing_required_keyword() {
		$file = $this->fixtures_dir . '/missing-required.csv';

		// Column mapping with keyword as page_title.
		$column_mapping = array(
			'keyword'       => 'page_title',
			'intent'        => 'topic_category',
			'search_volume' => 'skip',
		);

		$result = $this->parser->parse( $file, $column_mapping );

		$this->assertNotInstanceOf( \WP_Error::class, $result );

		// Should have errors for rows 3 and 5 (missing keyword).
		$this->assertNotEmpty( $result['errors'] );
		$this->assertCount( 2, $result['errors'] );

		// Should still parse valid rows.
		$this->assertEquals( 2, $result['metadata']['valid_rows'] );
		$this->assertEquals( 2, $result['metadata']['invalid_rows'] );
	}

	/**
	 * Test error collection continues parsing.
	 */
	public function test_error_collection_continues_parsing() {
		$file = $this->fixtures_dir . '/missing-required.csv';

		$column_mapping = array(
			'keyword'       => 'page_title',
			'intent'        => 'topic_category',
			'search_volume' => 'skip',
		);

		$result = $this->parser->parse( $file, $column_mapping );

		// Should have both errors and valid rows.
		$this->assertNotEmpty( $result['errors'] );
		$this->assertNotEmpty( $result['rows'] );

		// Should have 2 valid rows despite 2 errors.
		$this->assertCount( 2, $result['rows'] );
	}

	/**
	 * Test return structure includes all required keys.
	 */
	public function test_return_structure() {
		$file   = $this->fixtures_dir . '/valid-utf8.csv';
		$result = $this->parser->parse( $file );

		// Assert all required keys present.
		$this->assertArrayHasKey( 'headers', $result );
		$this->assertArrayHasKey( 'rows', $result );
		$this->assertArrayHasKey( 'errors', $result );
		$this->assertArrayHasKey( 'metadata', $result );

		// Assert metadata structure.
		$this->assertArrayHasKey( 'total_rows', $result['metadata'] );
		$this->assertArrayHasKey( 'valid_rows', $result['metadata'] );
		$this->assertArrayHasKey( 'invalid_rows', $result['metadata'] );
		$this->assertArrayHasKey( 'encoding', $result['metadata'] );
		$this->assertArrayHasKey( 'delimiter', $result['metadata'] );
	}

	/**
	 * Test directory traversal prevention.
	 */
	public function test_directory_traversal_prevention() {
		$result = $this->parser->parse( '../../../etc/passwd' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_path', $result->get_error_code() );
	}

	/**
	 * Test file path must be within upload directory.
	 */
	public function test_file_path_within_upload_directory() {
		// Use a file outside upload directory.
		$result = $this->parser->parse( '/tmp/test.csv' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_file_path', $result->get_error_code() );
	}
}
