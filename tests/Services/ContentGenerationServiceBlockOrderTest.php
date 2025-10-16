<?php
/**
 * Content Generation Service - Block Order Tests
 *
 * Tests for custom block ordering functionality.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Services;

use SEOGenerator\Services\ContentGenerationService;
use SEOGenerator\Services\OpenAIService;
use SEOGenerator\Services\PromptTemplateEngine;
use SEOGenerator\Services\BlockContentParser;
use SEOGenerator\Services\CostTrackingService;
use SEOGenerator\Services\ImageMatchingService;
use WP_UnitTestCase;
use ReflectionClass;

/**
 * Test Content Generation Service - Block Order
 */
class ContentGenerationServiceBlockOrderTest extends WP_UnitTestCase {

	/**
	 * Service instance.
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
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create mock services.
		$openai_service   = $this->createMock( OpenAIService::class );
		$prompt_engine    = $this->createMock( PromptTemplateEngine::class );
		$content_parser   = $this->createMock( BlockContentParser::class );
		$cost_tracking    = $this->createMock( CostTrackingService::class );
		$image_matching   = $this->createMock( ImageMatchingService::class );

		// Create service instance.
		$this->service = new ContentGenerationService(
			$openai_service,
			$prompt_engine,
			$content_parser,
			$cost_tracking,
			$image_matching
		);

		// Create test post.
		$this->post_id = $this->factory->post->create(
			array(
				'post_type'   => 'seo-page',
				'post_title'  => 'Test Post',
				'post_status' => 'draft',
			)
		);
	}

	/**
	 * Tear down test.
	 */
	public function tearDown(): void {
		// Clean up.
		if ( $this->post_id ) {
			wp_delete_post( $this->post_id, true );
		}

		parent::tearDown();
	}

	/**
	 * Helper method to call private methods using reflection.
	 *
	 * @param object $obj Object instance.
	 * @param string $method_name Method name.
	 * @param array  $parameters Method parameters.
	 * @return mixed Method result.
	 */
	private function callPrivateMethod( $obj, string $method_name, array $parameters = array() ) {
		$reflection = new ReflectionClass( get_class( $obj ) );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( $obj, $parameters );
	}

	/**
	 * Test getBlockOrder returns custom order when meta exists.
	 */
	public function testGetBlockOrderReturnsCustomOrderWhenMetaExists(): void {
		// Set custom block order.
		$custom_order = array( 'hero', 'faqs', 'cta', 'seo_metadata', 'serp_answer', 'product_criteria', 'materials', 'process', 'comparison', 'product_showcase', 'size_fit', 'care_warranty', 'ethics' );
		update_post_meta( $this->post_id, '_seo_block_order', wp_json_encode( $custom_order ) );

		// Call private method.
		$result = $this->callPrivateMethod( $this->service, 'getBlockOrder', array( $this->post_id ) );

		// Assert custom order is returned.
		$this->assertEquals( $custom_order, $result );
	}

	/**
	 * Test getBlockOrder returns default when no meta exists.
	 */
	public function testGetBlockOrderReturnsDefaultWhenNoMetaExists(): void {
		// Don't set any meta.

		// Call private method.
		$result = $this->callPrivateMethod( $this->service, 'getBlockOrder', array( $this->post_id ) );

		// Get default order via reflection.
		$reflection = new ReflectionClass( ContentGenerationService::class );
		$constants  = $reflection->getConstants();
		$default_order = $constants['BLOCK_ORDER'];

		// Assert default order is returned.
		$this->assertEquals( $default_order, $result );
	}

	/**
	 * Test getBlockOrder returns default when meta is invalid.
	 */
	public function testGetBlockOrderReturnsDefaultWhenMetaIsInvalid(): void {
		// Set invalid meta (not valid JSON).
		update_post_meta( $this->post_id, '_seo_block_order', 'invalid json' );

		// Call private method.
		$result = $this->callPrivateMethod( $this->service, 'getBlockOrder', array( $this->post_id ) );

		// Get default order via reflection.
		$reflection = new ReflectionClass( ContentGenerationService::class );
		$constants  = $reflection->getConstants();
		$default_order = $constants['BLOCK_ORDER'];

		// Assert default order is returned.
		$this->assertEquals( $default_order, $result );
	}

	/**
	 * Test getBlockOrder returns default when meta is empty array.
	 */
	public function testGetBlockOrderReturnsDefaultWhenMetaIsEmptyArray(): void {
		// Set empty array.
		update_post_meta( $this->post_id, '_seo_block_order', wp_json_encode( array() ) );

		// Call private method.
		$result = $this->callPrivateMethod( $this->service, 'getBlockOrder', array( $this->post_id ) );

		// Get default order via reflection.
		$reflection = new ReflectionClass( ContentGenerationService::class );
		$constants  = $reflection->getConstants();
		$default_order = $constants['BLOCK_ORDER'];

		// Assert default order is returned.
		$this->assertEquals( $default_order, $result );
	}
}
