<?php
/**
 * Generation REST API Controller
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Controllers;

use SEOGenerator\Services\ContentGenerationService;
use SEOGenerator\Exceptions\OpenAIException;
use SEOGenerator\Exceptions\RateLimitException;
use SEOGenerator\Exceptions\BudgetExceededException;
use SEOGenerator\Exceptions\NetworkException;
use SEOGenerator\Exceptions\TimeoutException;
use SEOGenerator\Exceptions\InvalidResponseException;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Handles REST API endpoints for content generation.
 */
class GenerationController extends WP_REST_Controller {
	/**
	 * API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'seo-generator/v1';

	/**
	 * Valid block types.
	 *
	 * @var array
	 */
	private const VALID_BLOCKS = array(
		'hero',
		'serp_answer',
		'product_criteria',
		'materials',
		'process',
		'comparison',
		'product_showcase',
		'size_fit',
		'care_warranty',
		'ethics',
		'faqs',
		'cta',
	);

	/**
	 * Content generation service.
	 *
	 * @var ContentGenerationService
	 */
	private ContentGenerationService $generation_service;

	/**
	 * Constructor.
	 *
	 * @param ContentGenerationService $generation_service Generation service.
	 */
	public function __construct( ContentGenerationService $generation_service ) {
		$this->generation_service = $generation_service;
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Get page data.
		register_rest_route(
			$this->namespace,
			'/pages/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_page_data' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Update page data.
		register_rest_route(
			$this->namespace,
			'/pages/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_page_data' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Single block generation.
		register_rest_route(
			$this->namespace,
			'/pages/(?P<id>\d+)/generate',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'generate_block' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => $this->get_endpoint_args(),
				),
			)
		);

		// Bulk generation (all blocks).
		register_rest_route(
			$this->namespace,
			'/pages/(?P<id>\d+)/generate-all',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'generate_all_blocks' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Generation progress.
		register_rest_route(
			$this->namespace,
			'/pages/(?P<id>\d+)/generate-progress',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_generation_progress' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);
	}

	/**
	 * Get page data including all ACF fields.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function get_page_data( WP_REST_Request $request ) {
		$post_id = (int) $request['id'];

		// Validate post exists and is correct type.
		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'post_not_found',
				'Post not found.',
				array( 'status' => 404 )
			);
		}

		if ( 'seo-page' !== $post->post_type ) {
			return new WP_Error(
				'invalid_post_type',
				'Post must be of type seo-page.',
				array( 'status' => 400 )
			);
		}

		// Get all ACF fields for this post.
		$blocks = array();
		foreach ( self::VALID_BLOCKS as $block_type ) {
			$block_data = $this->get_block_fields( $block_type, $post_id );
			if ( ! empty( $block_data ) ) {
				$blocks[ $block_type ] = $block_data;
			}
		}

		// Get SEO topic.
		$topics = wp_get_post_terms( $post_id, 'seo-topic', array( 'fields' => 'ids' ) );
		$topic  = ! empty( $topics ) ? $topics[0] : '';

		// Prepare response.
		$data = array(
			'title'        => $post->post_title,
			'slug'         => $post->post_name,
			'topic'        => $topic,
			'focusKeyword' => get_post_meta( $post_id, 'focus_keyword', true ),
			'blocks'       => $blocks,
		);

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Update page data including ACF fields.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function update_page_data( WP_REST_Request $request ) {
		$post_id = (int) $request['id'];
		$data    = $request->get_json_params();

		// Validate post exists and is correct type.
		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'post_not_found',
				'Post not found.',
				array( 'status' => 404 )
			);
		}

		if ( 'seo-page' !== $post->post_type ) {
			return new WP_Error(
				'invalid_post_type',
				'Post must be of type seo-page.',
				array( 'status' => 400 )
			);
		}

		// Update basic post data.
		if ( isset( $data['title'] ) ) {
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => sanitize_text_field( $data['title'] ),
				)
			);
		}

		// Update SEO topic.
		if ( isset( $data['topic'] ) ) {
			wp_set_post_terms( $post_id, array( (int) $data['topic'] ), 'seo-topic' );
		}

		// Update focus keyword.
		if ( isset( $data['focusKeyword'] ) ) {
			update_post_meta( $post_id, 'focus_keyword', sanitize_text_field( $data['focusKeyword'] ) );
		}

		// Update block data.
		if ( isset( $data['blocks'] ) && is_array( $data['blocks'] ) ) {
			foreach ( $data['blocks'] as $block_type => $block_data ) {
				if ( in_array( $block_type, self::VALID_BLOCKS, true ) && is_array( $block_data ) ) {
					// Save each individual field in the block
					foreach ( $block_data as $field_name => $field_value ) {
						update_field( $field_name, $field_value, $post_id );
					}
				}
			}
		}

		// Return updated data.
		return $this->get_page_data( $request );
	}

	/**
	 * Generate content for a single block.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function generate_block( WP_REST_Request $request ) {
		$post_id    = (int) $request['id'];
		$block_type = $request['blockType'];
		$context    = $request['context'] ?? array();

		// Validate post exists and is correct type.
		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'post_not_found',
				'Post not found.',
				array( 'status' => 404 )
			);
		}

		if ( 'seo-page' !== $post->post_type ) {
			return new WP_Error(
				'invalid_post_type',
				'Post must be of type seo-page.',
				array( 'status' => 400 )
			);
		}

		// Validate block type.
		if ( ! in_array( $block_type, self::VALID_BLOCKS, true ) ) {
			return new WP_Error(
				'invalid_block_type',
				sprintf(
					'Block type "%s" is not supported. Valid blocks: %s',
					$block_type,
					implode( ', ', self::VALID_BLOCKS )
				),
				array( 'status' => 400 )
			);
		}

		// Generate content.
		try {
			$result = $this->generation_service->generateSingleBlock( $post_id, $block_type, $context );

			return new WP_REST_Response( $result, 200 );

		} catch ( BudgetExceededException $e ) {
			error_log(
				sprintf(
					'[SEO Generator] Budget exceeded for post %d, block %s - $%.2f / $%.2f',
					$post_id,
					$block_type,
					$e->getCurrentCost(),
					$e->getBudgetLimit()
				)
			);

			return new WP_Error(
				'budget_exceeded',
				'Monthly budget limit reached ($' . number_format( $e->getCurrentCost(), 2 ) . ' of $' . number_format( $e->getBudgetLimit(), 2 ) . '). Please increase limit in Settings or wait until next month.',
				array(
					'status'       => 429,
					'current_cost' => $e->getCurrentCost(),
					'budget_limit' => $e->getBudgetLimit(),
				)
			);

		} catch ( RateLimitException $e ) {
			error_log(
				sprintf(
					'[SEO Generator] Rate limit for post %d, block %s',
					$post_id,
					$block_type
				)
			);

			return new WP_Error(
				'rate_limit_exceeded',
				'OpenAI rate limit reached. Retrying in ' . $e->getRetryAfter() . ' seconds...',
				array(
					'status'      => 429,
					'retry_after' => $e->getRetryAfter(),
				)
			);

		} catch ( TimeoutException $e ) {
			error_log(
				sprintf(
					'[SEO Generator] Timeout for post %d, block %s - %s',
					$post_id,
					$block_type,
					$e->getMessage()
				)
			);

			return new WP_Error(
				'timeout',
				'Generation timed out. Please try again.',
				array( 'status' => 504 )
			);

		} catch ( NetworkException $e ) {
			error_log(
				sprintf(
					'[SEO Generator] Network error for post %d, block %s - %s',
					$post_id,
					$block_type,
					$e->getMessage()
				)
			);

			return new WP_Error(
				'network_error',
				'Unable to connect to OpenAI. Check your internet connection.',
				array( 'status' => 503 )
			);

		} catch ( InvalidResponseException $e ) {
			error_log(
				sprintf(
					'[SEO Generator] Invalid response for post %d, block %s - %s',
					$post_id,
					$block_type,
					$e->getMessage()
				)
			);

			return new WP_Error(
				'invalid_response',
				'Failed to parse AI response. Please try regenerating.',
				array( 'status' => 500 )
			);

		} catch ( OpenAIException $e ) {
			error_log(
				sprintf(
					'[SEO Generator] OpenAI error for post %d, block %s - %s',
					$post_id,
					$block_type,
					$e->getMessage()
				)
			);

			return new WP_Error(
				'openai_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);

		} catch ( \Exception $e ) {
			error_log(
				sprintf(
					'[SEO Generator] Unexpected error for post %d, block %s - %s',
					$post_id,
					$block_type,
					$e->getMessage()
				)
			);

			return new WP_Error(
				'generation_error',
				'An unexpected error occurred. Please try again.',
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Generate all blocks for a post.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function generate_all_blocks( WP_REST_Request $request ) {
		$post_id = (int) $request['id'];

		// Validate post exists and is correct type.
		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'post_not_found',
				'Post not found.',
				array( 'status' => 404 )
			);
		}

		if ( 'seo-page' !== $post->post_type ) {
			return new WP_Error(
				'invalid_post_type',
				'Post must be of type seo-page.',
				array( 'status' => 400 )
			);
		}

		// Generate all blocks.
		try {
			$result = $this->generation_service->generateAllBlocks( $post_id );

			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $result->toArray(),
				),
				200
			);

		} catch ( BudgetExceededException $e ) {
			error_log(
				sprintf(
					'[SEO Generator] Budget exceeded during bulk generation for post %d',
					$post_id
				)
			);

			return new WP_Error(
				'budget_exceeded',
				'Monthly budget limit reached. Please increase limit in Settings.',
				array(
					'status'       => 429,
					'current_cost' => $e->getCurrentCost(),
					'budget_limit' => $e->getBudgetLimit(),
				)
			);

		} catch ( RateLimitException $e ) {
			error_log(
				sprintf(
					'[SEO Generator] Rate limit during bulk generation for post %d',
					$post_id
				)
			);

			return new WP_Error(
				'rate_limit_exceeded',
				'OpenAI rate limit reached. Please try again later.',
				array( 'status' => 429 )
			);

		} catch ( \Exception $e ) {
			error_log(
				sprintf(
					'[SEO Generator] Bulk generation error for post %d - %s',
					$post_id,
					$e->getMessage()
				)
			);

			return new WP_Error(
				'bulk_generation_error',
				'Failed to generate content: ' . $e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Get bulk generation progress.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function get_generation_progress( WP_REST_Request $request ) {
		$post_id = (int) $request['id'];
		$user_id = get_current_user_id();

		$progress = $this->generation_service->getProgress( $post_id, $user_id );

		if ( null === $progress ) {
			return new WP_Error(
				'progress_not_found',
				'No active generation found for this post.',
				array( 'status' => 404 )
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $progress,
			),
			200
		);
	}

	/**
	 * Get block fields for a specific block type.
	 *
	 * Reads individual ACF fields for a block and assembles them into the expected structure.
	 *
	 * @param string $block_type Block type.
	 * @param int    $post_id    Post ID.
	 * @return array Block data or empty array if no fields found.
	 */
	private function get_block_fields( string $block_type, int $post_id ): array {
		// Load block definitions.
		$definitions = require SEO_GENERATOR_PLUGIN_DIR . 'config/block-definitions.php';

		if ( ! isset( $definitions['blocks'][ $block_type ] ) ) {
			error_log( "[get_block_fields] Block type '$block_type' not found in definitions" );
			return array();
		}

		$block_def = $definitions['blocks'][ $block_type ];
		$block_data = array();
		$has_content = false;

		error_log( "[get_block_fields] Reading block '$block_type' for post $post_id" );

		// Read each field defined in the block.
		foreach ( $block_def['fields'] as $field_name => $field_config ) {
			$value = get_field( $field_name, $post_id );

			error_log( "[get_block_fields]   Field '$field_name' = " . wp_json_encode( $value ) );

			// For image fields, normalize to ID if we got an array.
			if ( 'image' === $field_config['type'] && is_array( $value ) ) {
				$value = $value['ID'] ?? $value['id'] ?? null;
				error_log( "[get_block_fields]   Normalized image field to ID: $value" );
			}

			// Check if this field has any content.
			if ( ! empty( $value ) || ( is_numeric( $value ) && $value !== '' ) ) {
				$block_data[ $field_name ] = $value;
				$has_content = true;
			} else {
				// Include empty fields in the structure for consistency.
				$block_data[ $field_name ] = $this->get_default_field_value( $field_config['type'] );
			}
		}

		error_log( "[get_block_fields] Block '$block_type' has_content: " . ( $has_content ? 'YES' : 'NO' ) );
		error_log( "[get_block_fields] Returning data: " . wp_json_encode( $block_data ) );

		// Only return block data if at least one field has content.
		return $has_content ? $block_data : array();
	}

	/**
	 * Get default value for a field type.
	 *
	 * @param string $field_type Field type.
	 * @return mixed Default value.
	 */
	private function get_default_field_value( string $field_type ) {
		switch ( $field_type ) {
			case 'repeater':
				return array();
			case 'image':
				return null;
			case 'number':
				return 0;
			default:
				return '';
		}
	}

	/**
	 * Check permission for generation endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if allowed, WP_Error otherwise.
	 */
	public function check_permission( WP_REST_Request $request ) {
		// Check user capability.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'insufficient_permissions',
				'You do not have permission to generate content.',
				array( 'status' => 403 )
			);
		}

		// Check if user can edit the specific post.
		$post_id = (int) $request['id'];
		$post    = get_post( $post_id );

		if ( $post && ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'cannot_edit_post',
				'You do not have permission to edit this post.',
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get endpoint arguments.
	 *
	 * @return array Endpoint arguments.
	 */
	private function get_endpoint_args(): array {
		return array(
			'blockType' => array(
				'required'          => true,
				'type'              => 'string',
				'enum'              => self::VALID_BLOCKS,
				'description'       => 'Block type to generate',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function ( $param ) {
					return in_array( $param, self::VALID_BLOCKS, true );
				},
			),
			'context'   => array(
				'required'          => false,
				'type'              => 'object',
				'description'       => 'Additional context for generation',
				'default'           => array(),
				'sanitize_callback' => function ( $param ) {
					return is_array( $param ) ? $param : array();
				},
			),
		);
	}
}
