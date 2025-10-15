<?php
/**
 * Tests for BlockDefinitionValidator
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\ACF;

use SEOGenerator\ACF\BlockDefinitionValidator;
use WP_UnitTestCase;

/**
 * BlockDefinitionValidator test case.
 */
class BlockDefinitionValidatorTest extends WP_UnitTestCase {
	/**
	 * Validator instance.
	 *
	 * @var BlockDefinitionValidator
	 */
	private $validator;

	/**
	 * Set up before tests.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->validator = new BlockDefinitionValidator();
	}

	/**
	 * Test valid block configuration passes validation.
	 */
	public function test_valid_configuration_passes() {
		$blocks = [
			'test_block' => [
				'label'       => 'Test Block',
				'description' => 'Test description',
				'order'       => 1,
				'enabled'     => true,
				'fields'      => [
					'test_field' => [
						'label' => 'Test Field',
						'type'  => 'text',
					],
				],
			],
		];

		$result = $this->validator->validate( $blocks );

		$this->assertTrue( $result );
		$this->assertEmpty( $this->validator->getErrors() );
	}

	/**
	 * Test empty blocks array fails validation.
	 */
	public function test_empty_blocks_fails() {
		$blocks = [];

		$result = $this->validator->validate( $blocks );

		$this->assertFalse( $result );
		$this->assertNotEmpty( $this->validator->getErrors() );
		$this->assertStringContainsString( 'No blocks defined', $this->validator->getErrors()[0] );
	}

	/**
	 * Test block missing label fails validation.
	 */
	public function test_missing_label_fails() {
		$blocks = [
			'test_block' => [
				'fields' => [
					'test_field' => [
						'type' => 'text',
					],
				],
			],
		];

		$result = $this->validator->validate( $blocks );

		$this->assertFalse( $result );
		$errors = $this->validator->getErrors();
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( "missing required property 'label'", implode( ' ', $errors ) );
	}

	/**
	 * Test block missing fields fails validation.
	 */
	public function test_missing_fields_fails() {
		$blocks = [
			'test_block' => [
				'label' => 'Test Block',
			],
		];

		$result = $this->validator->validate( $blocks );

		$this->assertFalse( $result );
		$errors = $this->validator->getErrors();
		$this->assertStringContainsString( "missing required property 'fields'", implode( ' ', $errors ) );
	}

	/**
	 * Test block with empty fields array fails validation.
	 */
	public function test_empty_fields_fails() {
		$blocks = [
			'test_block' => [
				'label'  => 'Test Block',
				'fields' => [],
			],
		];

		$result = $this->validator->validate( $blocks );

		$this->assertFalse( $result );
		$errors = $this->validator->getErrors();
		$this->assertStringContainsString( 'has no fields defined', implode( ' ', $errors ) );
	}

	/**
	 * Test invalid block ID format fails validation.
	 */
	public function test_invalid_block_id_format_fails() {
		$blocks = [
			'Test-Block' => [
				'label'  => 'Test Block',
				'fields' => [
					'test_field' => [
						'type' => 'text',
					],
				],
			],
		];

		$result = $this->validator->validate( $blocks );

		$this->assertFalse( $result );
		$errors = $this->validator->getErrors();
		$this->assertStringContainsString( 'lowercase letters and underscores', implode( ' ', $errors ) );
	}

	/**
	 * Test field missing type fails validation.
	 */
	public function test_field_missing_type_fails() {
		$blocks = [
			'test_block' => [
				'label'  => 'Test Block',
				'fields' => [
					'test_field' => [
						'label' => 'Test Field',
					],
				],
			],
		];

		$result = $this->validator->validate( $blocks );

		$this->assertFalse( $result );
		$errors = $this->validator->getErrors();
		$this->assertStringContainsString( "missing required property 'type'", implode( ' ', $errors ) );
	}

	/**
	 * Test invalid field type fails validation.
	 */
	public function test_invalid_field_type_fails() {
		$blocks = [
			'test_block' => [
				'label'  => 'Test Block',
				'fields' => [
					'test_field' => [
						'type' => 'invalid_type',
					],
				],
			],
		];

		$result = $this->validator->validate( $blocks );

		$this->assertFalse( $result );
		$errors = $this->validator->getErrors();
		$this->assertStringContainsString( 'invalid type', implode( ' ', $errors ) );
	}

	/**
	 * Test repeater without sub_fields fails validation.
	 */
	public function test_repeater_without_subfields_fails() {
		$blocks = [
			'test_block' => [
				'label'  => 'Test Block',
				'fields' => [
					'test_repeater' => [
						'type' => 'repeater',
					],
				],
			],
		];

		$result = $this->validator->validate( $blocks );

		$this->assertFalse( $result );
		$errors = $this->validator->getErrors();
		$this->assertStringContainsString( "missing required property 'sub_fields'", implode( ' ', $errors ) );
	}

	/**
	 * Test valid repeater field passes validation.
	 */
	public function test_valid_repeater_passes() {
		$blocks = [
			'test_block' => [
				'label'  => 'Test Block',
				'fields' => [
					'test_repeater' => [
						'type'       => 'repeater',
						'sub_fields' => [
							'sub_field' => [
								'type' => 'text',
							],
						],
					],
				],
			],
		];

		$result = $this->validator->validate( $blocks );

		$this->assertTrue( $result );
		$this->assertEmpty( $this->validator->getErrors() );
	}

	/**
	 * Test duplicate field names across blocks fails validation.
	 */
	public function test_duplicate_field_names_fails() {
		$blocks = [
			'block_1' => [
				'label'  => 'Block 1',
				'fields' => [
					'title' => [
						'type' => 'text',
					],
				],
			],
			'block_2' => [
				'label'  => 'Block 2',
				'fields' => [
					'title' => [
						'type' => 'text',
					],
				],
			],
		];

		$result = $this->validator->validate( $blocks );

		$this->assertFalse( $result );
		$errors = $this->validator->getErrors();
		$this->assertStringContainsString( "Field name 'title' is used", implode( ' ', $errors ) );
		$this->assertStringContainsString( 'must be unique', implode( ' ', $errors ) );
	}

	/**
	 * Test invalid field name format fails validation.
	 */
	public function test_invalid_field_name_format_fails() {
		$blocks = [
			'test_block' => [
				'label'  => 'Test Block',
				'fields' => [
					'Test-Field' => [
						'type' => 'text',
					],
				],
			],
		];

		$result = $this->validator->validate( $blocks );

		$this->assertFalse( $result );
		$errors = $this->validator->getErrors();
		$this->assertStringContainsString( 'lowercase letter or underscore', implode( ' ', $errors ) );
	}

	/**
	 * Test invalid order type fails validation.
	 */
	public function test_invalid_order_type_fails() {
		$blocks = [
			'test_block' => [
				'label'  => 'Test Block',
				'order'  => 'first', // Should be int.
				'fields' => [
					'test_field' => [
						'type' => 'text',
					],
				],
			],
		];

		$result = $this->validator->validate( $blocks );

		$this->assertFalse( $result );
		$errors = $this->validator->getErrors();
		$this->assertStringContainsString( 'order', implode( ' ', $errors ) );
		$this->assertStringContainsString( 'must be an integer', implode( ' ', $errors ) );
	}

	/**
	 * Test invalid enabled type fails validation.
	 */
	public function test_invalid_enabled_type_fails() {
		$blocks = [
			'test_block' => [
				'label'   => 'Test Block',
				'enabled' => 'yes', // Should be bool.
				'fields'  => [
					'test_field' => [
						'type' => 'text',
					],
				],
			],
		];

		$result = $this->validator->validate( $blocks );

		$this->assertFalse( $result );
		$errors = $this->validator->getErrors();
		$this->assertStringContainsString( 'enabled', implode( ' ', $errors ) );
		$this->assertStringContainsString( 'must be a boolean', implode( ' ', $errors ) );
	}

	/**
	 * Test validateOrThrow throws exception on invalid config.
	 */
	public function test_validate_or_throw_throws_exception() {
		$blocks = [
			'test_block' => [
				'label' => 'Test Block',
				// Missing fields.
			],
		];

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Block configuration validation failed' );

		$this->validator->validateOrThrow( $blocks );
	}

	/**
	 * Test validateOrThrow does not throw on valid config.
	 */
	public function test_validate_or_throw_does_not_throw_on_valid() {
		$blocks = [
			'test_block' => [
				'label'  => 'Test Block',
				'fields' => [
					'test_field' => [
						'type' => 'text',
					],
				],
			],
		];

		try {
			$this->validator->validateOrThrow( $blocks );
			$this->assertTrue( true ); // No exception thrown.
		} catch ( \Exception $e ) {
			$this->fail( 'Should not throw exception for valid config' );
		}
	}

	/**
	 * Test getWarnings returns warnings for missing optional properties.
	 */
	public function test_get_warnings_returns_warnings() {
		$blocks = [
			'test_block' => [
				'label'  => 'Test Block',
				'fields' => [
					'heading' => [
						'type' => 'text',
					],
				],
			],
		];

		$warnings = $this->validator->getWarnings( $blocks );

		$this->assertNotEmpty( $warnings );

		$warning_text = implode( ' ', $warnings );
		$this->assertStringContainsString( 'no description', $warning_text );
		$this->assertStringContainsString( 'no AI prompt', $warning_text );
		$this->assertStringContainsString( 'no frontend template', $warning_text );
	}

	/**
	 * Test nested sub_fields validation.
	 */
	public function test_nested_subfields_validation() {
		$blocks = [
			'test_block' => [
				'label'  => 'Test Block',
				'fields' => [
					'outer_repeater' => [
						'type'       => 'repeater',
						'sub_fields' => [
							'inner_repeater' => [
								'type'       => 'repeater',
								'sub_fields' => [
									'deep_field' => [
										'type' => 'invalid_type',
									],
								],
							],
						],
					],
				],
			],
		];

		$result = $this->validator->validate( $blocks );

		$this->assertFalse( $result );
		$errors = $this->validator->getErrors();
		$this->assertStringContainsString( 'deep_field', implode( ' ', $errors ) );
		$this->assertStringContainsString( 'invalid type', implode( ' ', $errors ) );
	}

	/**
	 * Test all standard ACF field types are recognized as valid.
	 */
	public function test_all_acf_field_types_are_valid() {
		$field_types = [
			'text',
			'textarea',
			'number',
			'email',
			'url',
			'image',
			'file',
			'select',
			'checkbox',
			'radio',
			'true_false',
			'repeater',
			'wysiwyg',
		];

		foreach ( $field_types as $type ) {
			$blocks = [
				'test_block' => [
					'label'  => 'Test Block',
					'fields' => [
						'test_field' => [
							'type' => $type,
						],
					],
				],
			];

			// Add sub_fields for repeater type.
			if ( 'repeater' === $type ) {
				$blocks['test_block']['fields']['test_field']['sub_fields'] = [
					'sub' => [ 'type' => 'text' ],
				];
			}

			$result = $this->validator->validate( $blocks );
			$this->assertTrue( $result, "Field type '{$type}' should be valid" );
		}
	}
}
