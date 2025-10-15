<?php
/**
 * Block Definition Validator
 *
 * Validates block configuration structure and field definitions.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\ACF;

defined( 'ABSPATH' ) || exit;

/**
 * BlockDefinitionValidator Class
 *
 * Validates block configuration arrays for required properties and correct structure.
 */
class BlockDefinitionValidator {
	/**
	 * Valid ACF field types.
	 *
	 * @var array
	 */
	private const VALID_FIELD_TYPES = [
		'text',
		'textarea',
		'number',
		'range',
		'email',
		'url',
		'password',
		'image',
		'file',
		'wysiwyg',
		'oembed',
		'gallery',
		'select',
		'checkbox',
		'radio',
		'button_group',
		'true_false',
		'link',
		'post_object',
		'page_link',
		'relationship',
		'taxonomy',
		'user',
		'google_map',
		'date_picker',
		'date_time_picker',
		'time_picker',
		'color_picker',
		'message',
		'accordion',
		'tab',
		'group',
		'repeater',
		'flexible_content',
		'clone',
	];

	/**
	 * Validation errors.
	 *
	 * @var array
	 */
	private $errors = [];

	/**
	 * Validate all block definitions.
	 *
	 * @param array $blocks Array of block definitions.
	 * @return bool True if valid, false otherwise.
	 */
	public function validate( array $blocks ): bool {
		$this->errors = [];

		if ( empty( $blocks ) ) {
			$this->errors[] = 'No blocks defined in configuration.';
			return false;
		}

		$all_field_names = [];

		foreach ( $blocks as $block_id => $block ) {
			$this->validateBlockId( $block_id );
			$this->validateBlockStructure( $block_id, $block );

			// Collect field names for uniqueness check.
			if ( isset( $block['fields'] ) ) {
				$field_names      = $this->collectFieldNames( $block['fields'] );
				$all_field_names = array_merge( $all_field_names, $field_names );
			}
		}

		// Check for duplicate field names across all blocks.
		$this->validateFieldUniqueness( $all_field_names );

		return empty( $this->errors );
	}

	/**
	 * Get validation errors.
	 *
	 * @return array Array of error messages.
	 */
	public function getErrors(): array {
		return $this->errors;
	}

	/**
	 * Validate block ID format.
	 *
	 * @param string $block_id Block ID.
	 * @return void
	 */
	private function validateBlockId( string $block_id ): void {
		if ( empty( $block_id ) ) {
			$this->errors[] = 'Block ID cannot be empty.';
			return;
		}

		// Block ID should be lowercase with underscores only.
		if ( ! preg_match( '/^[a-z_]+$/', $block_id ) ) {
			$this->errors[] = "Block ID '{$block_id}' should contain only lowercase letters and underscores.";
		}
	}

	/**
	 * Validate block structure.
	 *
	 * @param string $block_id Block ID.
	 * @param array  $block    Block definition.
	 * @return void
	 */
	private function validateBlockStructure( string $block_id, array $block ): void {
		// Check required properties.
		if ( ! isset( $block['label'] ) || empty( $block['label'] ) ) {
			$this->errors[] = "Block '{$block_id}' is missing required property 'label'.";
		}

		if ( ! isset( $block['fields'] ) ) {
			$this->errors[] = "Block '{$block_id}' is missing required property 'fields'.";
			return;
		}

		if ( ! is_array( $block['fields'] ) ) {
			$this->errors[] = "Block '{$block_id}' property 'fields' must be an array.";
			return;
		}

		if ( empty( $block['fields'] ) ) {
			$this->errors[] = "Block '{$block_id}' has no fields defined.";
			return;
		}

		// Validate optional properties.
		if ( isset( $block['order'] ) && ! is_int( $block['order'] ) ) {
			$this->errors[] = "Block '{$block_id}' property 'order' must be an integer.";
		}

		if ( isset( $block['enabled'] ) && ! is_bool( $block['enabled'] ) ) {
			$this->errors[] = "Block '{$block_id}' property 'enabled' must be a boolean.";
		}

		// Validate fields.
		foreach ( $block['fields'] as $field_name => $field_config ) {
			$this->validateField( $block_id, $field_name, $field_config );
		}
	}

	/**
	 * Validate field configuration.
	 *
	 * @param string $block_id    Block ID.
	 * @param string $field_name  Field name.
	 * @param array  $field_config Field configuration.
	 * @param string $parent_path Optional. Parent field path for nested fields.
	 * @return void
	 */
	private function validateField( string $block_id, string $field_name, array $field_config, string $parent_path = '' ): void {
		$field_path = $parent_path ? "{$parent_path}.{$field_name}" : $field_name;

		// Validate field name format.
		if ( ! preg_match( '/^[a-z_][a-z0-9_]*$/', $field_name ) ) {
			$this->errors[] = "Block '{$block_id}' field '{$field_path}' name should start with lowercase letter or underscore and contain only lowercase letters, numbers, and underscores.";
		}

		// Check for type property.
		if ( ! isset( $field_config['type'] ) ) {
			$this->errors[] = "Block '{$block_id}' field '{$field_path}' is missing required property 'type'.";
			return;
		}

		// Validate field type.
		if ( ! in_array( $field_config['type'], self::VALID_FIELD_TYPES, true ) ) {
			$this->errors[] = "Block '{$block_id}' field '{$field_path}' has invalid type '{$field_config['type']}'.";
		}

		// Validate repeater sub_fields.
		if ( 'repeater' === $field_config['type'] ) {
			if ( ! isset( $field_config['sub_fields'] ) ) {
				$this->errors[] = "Block '{$block_id}' repeater field '{$field_path}' is missing required property 'sub_fields'.";
				return;
			}

			if ( ! is_array( $field_config['sub_fields'] ) ) {
				$this->errors[] = "Block '{$block_id}' repeater field '{$field_path}' property 'sub_fields' must be an array.";
				return;
			}

			if ( empty( $field_config['sub_fields'] ) ) {
				$this->errors[] = "Block '{$block_id}' repeater field '{$field_path}' has no sub_fields defined.";
				return;
			}

			// Validate each sub-field.
			foreach ( $field_config['sub_fields'] as $sub_field_name => $sub_field_config ) {
				$this->validateField( $block_id, $sub_field_name, $sub_field_config, $field_path );
			}
		}

		// Validate flexible_content layouts.
		if ( 'flexible_content' === $field_config['type'] ) {
			if ( ! isset( $field_config['layouts'] ) ) {
				$this->errors[] = "Block '{$block_id}' flexible_content field '{$field_path}' is missing required property 'layouts'.";
			}
		}
	}

	/**
	 * Collect all field names from fields array (including sub_fields).
	 *
	 * @param array $fields Fields array.
	 * @return array Array of field names.
	 */
	private function collectFieldNames( array $fields ): array {
		$names = [];

		foreach ( $fields as $field_name => $field_config ) {
			$names[] = $field_name;

			// Recursively collect sub_field names.
			if ( isset( $field_config['sub_fields'] ) && is_array( $field_config['sub_fields'] ) ) {
				$sub_names = $this->collectFieldNames( $field_config['sub_fields'] );
				$names     = array_merge( $names, $sub_names );
			}
		}

		return $names;
	}

	/**
	 * Validate that field names are unique.
	 *
	 * @param array $field_names Array of all field names.
	 * @return void
	 */
	private function validateFieldUniqueness( array $field_names ): void {
		$counts = array_count_values( $field_names );

		foreach ( $counts as $name => $count ) {
			if ( $count > 1 ) {
				$this->errors[] = "Field name '{$name}' is used {$count} times. Field names must be unique across all blocks.";
			}
		}
	}

	/**
	 * Validate and throw exception if invalid.
	 *
	 * @param array $blocks Array of block definitions.
	 * @throws \Exception If validation fails.
	 * @return void
	 */
	public function validateOrThrow( array $blocks ): void {
		if ( ! $this->validate( $blocks ) ) {
			$error_message = "Block configuration validation failed:\n" . implode( "\n", $this->errors );
			throw new \Exception( $error_message );
		}
	}

	/**
	 * Get validation warnings (non-critical issues).
	 *
	 * @param array $blocks Array of block definitions.
	 * @return array Array of warning messages.
	 */
	public function getWarnings( array $blocks ): array {
		$warnings = [];

		foreach ( $blocks as $block_id => $block ) {
			// Warn if block has no description.
			if ( ! isset( $block['description'] ) || empty( $block['description'] ) ) {
				$warnings[] = "Block '{$block_id}' has no description. Consider adding one for documentation.";
			}

			// Warn if block has no AI prompt.
			if ( ! isset( $block['ai_prompt'] ) || empty( $block['ai_prompt'] ) ) {
				$warnings[] = "Block '{$block_id}' has no AI prompt. Consider adding one for content generation.";
			}

			// Warn if block has no frontend template.
			if ( ! isset( $block['frontend_template'] ) || empty( $block['frontend_template'] ) ) {
				$warnings[] = "Block '{$block_id}' has no frontend template specified.";
			}

			// Warn about missing required flag on important fields.
			foreach ( $block['fields'] as $field_name => $field_config ) {
				if ( str_contains( $field_name, 'heading' ) || str_contains( $field_name, 'title' ) ) {
					if ( ! isset( $field_config['required'] ) || ! $field_config['required'] ) {
						$warnings[] = "Block '{$block_id}' field '{$field_name}' looks important but is not marked as required.";
					}
				}
			}
		}

		return $warnings;
	}
}
