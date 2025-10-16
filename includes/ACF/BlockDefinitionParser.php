<?php
/**
 * Block Definition Parser
 *
 * Converts block definition config into ACF field format.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\ACF;

defined( 'ABSPATH' ) || exit;

/**
 * BlockDefinitionParser Class
 *
 * Handles conversion from config format to ACF field format.
 */
class BlockDefinitionParser {
	/**
	 * Block definitions from config.
	 *
	 * @var array
	 */
	private $blocks;

	/**
	 * Global settings from config.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->loadBlockDefinitions();
	}

	/**
	 * Load block definitions from config file.
	 *
	 * @return void
	 */
	private function loadBlockDefinitions(): void {
		// Check if already validated in this request (request-scoped cache).
		static $validation_done = false;

		$config_file = SEO_GENERATOR_PLUGIN_DIR . 'config/block-definitions.php';

		if ( ! file_exists( $config_file ) ) {
			// Fallback to empty if config doesn't exist.
			$this->blocks   = [];
			$this->settings = [];
			return;
		}

		$config = require $config_file;

		// Allow filtering of block definitions.
		$this->blocks   = apply_filters( 'seo_generator_block_definitions', $config['blocks'] ?? [] );
		$this->settings = $config['settings'] ?? [];

		// Only validate once per request to prevent multiple validation runs.
		if ( ! $validation_done ) {
			// Validate block definitions.
			$validator = new BlockDefinitionValidator();

			if ( ! $validator->validate( $this->blocks ) ) {
				$errors = $validator->getErrors();

				// Log errors.
				if ( function_exists( 'error_log' ) ) {
					error_log( 'SEO Generator - Block configuration validation errors:' );
					foreach ( $errors as $error ) {
						error_log( '  - ' . $error );
					}
				}

				// In debug mode, throw exception to alert developer.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
					trigger_error(
						'Block configuration validation failed: ' . implode( '; ', $errors ),
						E_USER_WARNING
					);
				}
			}

			// Log warnings in debug mode.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$warnings = $validator->getWarnings( $this->blocks );
				if ( ! empty( $warnings ) && function_exists( 'error_log' ) ) {
					error_log( 'SEO Generator - Block configuration warnings:' );
					foreach ( $warnings as $warning ) {
						error_log( '  - ' . $warning );
					}
				}
			}

			// Mark validation as done for this request.
			$validation_done = true;
		}
	}

	/**
	 * Get all enabled blocks.
	 *
	 * @return array Array of enabled blocks.
	 */
	public function getEnabledBlocks(): array {
		return array_filter(
			$this->blocks,
			function ( $block ) {
				return $block['enabled'] ?? true;
			}
		);
	}

	/**
	 * Get all blocks sorted by order.
	 *
	 * @return array Sorted array of blocks.
	 */
	public function getSortedBlocks(): array {
		$blocks = $this->getEnabledBlocks();

		uasort(
			$blocks,
			function ( $a, $b ) {
				return ( $a['order'] ?? 999 ) <=> ( $b['order'] ?? 999 );
			}
		);

		return $blocks;
	}

	/**
	 * Convert all block definitions to ACF fields array.
	 *
	 * @return array ACF-formatted fields array.
	 */
	public function convertAllBlocksToACFFields(): array {
		$acf_fields = [];
		$blocks     = $this->getSortedBlocks();

		foreach ( $blocks as $block_id => $block ) {
			$acf_fields = array_merge(
				$acf_fields,
				$this->convertBlockToACFFields( $block_id, $block )
			);
		}

		return $acf_fields;
	}

	/**
	 * Convert a single block definition to ACF fields array.
	 *
	 * @param string $block_id   Block ID.
	 * @param array  $block      Block definition.
	 * @return array ACF-formatted fields.
	 */
	public function convertBlockToACFFields( string $block_id, array $block ): array {
		$acf_fields = [];
		$fields     = $block['fields'] ?? [];
		$first_field = true;

		foreach ( $fields as $field_name => $field_config ) {
			$acf_field = $this->convertFieldToACF( $field_name, $field_config );

			// Add wrapper class to first field of block.
			if ( $first_field && isset( $block['acf_wrapper_class'] ) ) {
				$acf_field['wrapper'] = [
					'class' => $block['acf_wrapper_class'],
				];
				$first_field = false;
			}

			$acf_fields[] = $acf_field;
		}

		return $acf_fields;
	}

	/**
	 * Convert a single field definition to ACF field format.
	 *
	 * @param string $field_name   Field name.
	 * @param array  $field_config Field configuration.
	 * @return array ACF field array.
	 */
	private function convertFieldToACF( string $field_name, array $field_config ): array {
		$acf_field = [
			'key'   => 'field_' . $field_name,
			'label' => $field_config['label'] ?? ucwords( str_replace( '_', ' ', $field_name ) ),
			'name'  => $field_name,
			'type'  => $field_config['type'] ?? 'text',
		];

		// Add optional properties if they exist.
		$optional_props = [
			'required',
			'maxlength',
			'rows',
			'max',
			'return_format',
			'preview_size',
			'layout',
			'wrapper',
		];

		foreach ( $optional_props as $prop ) {
			if ( isset( $field_config[ $prop ] ) ) {
				$acf_field[ $prop ] = $field_config[ $prop ];
			}
		}

		// Handle repeater sub-fields.
		if ( 'repeater' === $acf_field['type'] && isset( $field_config['sub_fields'] ) ) {
			$acf_field['sub_fields'] = [];

			foreach ( $field_config['sub_fields'] as $sub_field_name => $sub_field_config ) {
				$acf_field['sub_fields'][] = $this->convertFieldToACF( $sub_field_name, $sub_field_config );
			}
		}

		return $acf_field;
	}

	/**
	 * Get block definition by ID.
	 *
	 * @param string $block_id Block ID.
	 * @return array|null Block definition or null if not found.
	 */
	public function getBlock( string $block_id ): ?array {
		return $this->blocks[ $block_id ] ?? null;
	}

	/**
	 * Get all block IDs.
	 *
	 * @return array Array of block IDs.
	 */
	public function getBlockIds(): array {
		return array_keys( $this->getEnabledBlocks() );
	}

	/**
	 * Get AI prompt for a block.
	 *
	 * @param string $block_id Block ID.
	 * @return string|null AI prompt template or null.
	 */
	public function getAIPrompt( string $block_id ): ?string {
		$block = $this->getBlock( $block_id );
		return $block['ai_prompt'] ?? null;
	}

	/**
	 * Get frontend template path for a block.
	 *
	 * @param string $block_id Block ID.
	 * @return string|null Template path or null.
	 */
	public function getFrontendTemplate( string $block_id ): ?string {
		$block = $this->getBlock( $block_id );
		return $block['frontend_template'] ?? null;
	}

	/**
	 * Check if custom blocks are allowed.
	 *
	 * @return bool True if custom blocks allowed.
	 */
	public function allowCustomBlocks(): bool {
		return $this->settings['allow_custom_blocks'] ?? false;
	}

	/**
	 * Get block count.
	 *
	 * @return int Number of enabled blocks.
	 */
	public function getBlockCount(): int {
		return count( $this->getEnabledBlocks() );
	}
}
