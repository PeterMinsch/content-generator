<?php
/**
 * Validation Service
 *
 * Validates generated content against block rule profiles.
 * Supports auto-fix (truncation) and issue reporting.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

class ValidationService {

	private BlockRuleService $rule_service;

	public function __construct( BlockRuleService $rule_service ) {
		$this->rule_service = $rule_service;
	}

	/**
	 * Validate all page content against block rules.
	 *
	 * @param array       $slot_content { block_id => { slot_name => value } }
	 * @param array       $block_order  Block IDs on this page.
	 * @param int|null    $template_id  Template ID for overrides.
	 * @param string|null $focus_keyword Focus keyword for keyword checks.
	 * @return ValidationResult
	 */
	public function validatePage( array $slot_content, array $block_order, ?int $template_id = null, ?string $focus_keyword = null ): ValidationResult {
		$all_issues = [];

		foreach ( $block_order as $block_id ) {
			$slot_values = $slot_content[ $block_id ] ?? [];
			$schema      = $this->rule_service->getResolvedSlotSchema( $block_id, $template_id );

			if ( empty( $schema ) ) {
				continue;
			}

			$issues = $this->validateBlock( $block_id, $slot_values, $schema, $focus_keyword );
			$all_issues = array_merge( $all_issues, $issues );
		}

		return new ValidationResult( $all_issues );
	}

	/**
	 * Validate a single block's slot values against its schema.
	 *
	 * @return ValidationIssue[]
	 */
	public function validateBlock( string $block_id, array $slot_values, array $schema, ?string $focus_keyword = null ): array {
		$issues = [];

		foreach ( $schema as $slot_name => $slot_def ) {
			$value = $slot_values[ $slot_name ] ?? '';

			// Required check.
			if ( ! empty( $slot_def['required'] ) && empty( trim( $value ) ) ) {
				$issues[] = new ValidationIssue(
					$block_id,
					$slot_name,
					'error',
					'required',
					"Slot \"{$slot_name}\" is required but empty."
				);
				continue;
			}

			if ( empty( $value ) ) {
				continue;
			}

			$max_length = $slot_def['max_length'] ?? 0;
			$validation = $slot_def['validation'] ?? [];

			// Max length check.
			if ( $max_length > 0 && mb_strlen( $value ) > $max_length ) {
				$action   = $slot_def['over_limit_action'] ?? 'truncate';
				$severity = $action === 'flag' ? 'warning' : 'error';

				$issues[] = new ValidationIssue(
					$block_id,
					$slot_name,
					$severity,
					'max_length',
					"Slot \"{$slot_name}\" exceeds max length of {$max_length} (" . mb_strlen( $value ) . " chars). Action: {$action}."
				);
			}

			// Min length check.
			$min_length = $validation['min_length'] ?? 0;
			if ( $min_length > 0 && mb_strlen( $value ) < $min_length ) {
				$issues[] = new ValidationIssue(
					$block_id,
					$slot_name,
					'warning',
					'min_length',
					"Slot \"{$slot_name}\" is shorter than min length of {$min_length} (" . mb_strlen( $value ) . " chars)."
				);
			}

			// Forbidden patterns.
			$forbidden = $validation['forbidden_patterns'] ?? [];
			foreach ( $forbidden as $pattern ) {
				if ( ! empty( $pattern ) && stripos( $value, $pattern ) !== false ) {
					$issues[] = new ValidationIssue(
						$block_id,
						$slot_name,
						'warning',
						'forbidden_pattern',
						"Slot \"{$slot_name}\" contains forbidden pattern: \"{$pattern}\"."
					);
				}
			}

			// Must contain keyword.
			if ( ! empty( $validation['must_contain_keyword'] ) && ! empty( $focus_keyword ) ) {
				if ( stripos( $value, $focus_keyword ) === false ) {
					$issues[] = new ValidationIssue(
						$block_id,
						$slot_name,
						'warning',
						'must_contain_keyword',
						"Slot \"{$slot_name}\" should contain the focus keyword \"{$focus_keyword}\"."
					);
				}
			}
		}

		return $issues;
	}

	/**
	 * Auto-fix content based on over_limit_action rules.
	 *
	 * @return array { 'fixed' => array, 'remaining_issues' => ValidationIssue[] }
	 */
	public function autoFix( array $slot_content, array $block_order, ?int $template_id = null ): array {
		$fixed            = $slot_content;
		$remaining_issues = [];

		foreach ( $block_order as $block_id ) {
			if ( ! isset( $fixed[ $block_id ] ) ) {
				continue;
			}

			$schema = $this->rule_service->getResolvedSlotSchema( $block_id, $template_id );

			foreach ( $fixed[ $block_id ] as $slot_name => &$value ) {
				$slot_def   = $schema[ $slot_name ] ?? [];
				$max_length = $slot_def['max_length'] ?? 0;
				$action     = $slot_def['over_limit_action'] ?? 'truncate';

				if ( $max_length > 0 && mb_strlen( $value ) > $max_length ) {
					if ( $action === 'truncate' ) {
						$value = mb_substr( $value, 0, $max_length );
					} elseif ( $action === 'flag' ) {
						$remaining_issues[] = new ValidationIssue(
							$block_id,
							$slot_name,
							'warning',
							'max_length',
							"Slot \"{$slot_name}\" exceeds {$max_length} chars (flagged, not truncated)."
						);
					}
					// 'regenerate' is a future enhancement — treat as flag for now.
					if ( $action === 'regenerate' ) {
						$remaining_issues[] = new ValidationIssue(
							$block_id,
							$slot_name,
							'warning',
							'max_length',
							"Slot \"{$slot_name}\" exceeds {$max_length} chars (regenerate not yet implemented, flagged)."
						);
					}
				}
			}
			unset( $value );
		}

		return [
			'fixed'            => $fixed,
			'remaining_issues' => $remaining_issues,
		];
	}
}

/**
 * Validation Result — aggregates issues from a full page validation.
 */
class ValidationResult {

	public bool $passed;

	/** @var ValidationIssue[] */
	public array $issues;

	/** @var ValidationIssue[] */
	public array $warnings;

	public function __construct( array $issues ) {
		$this->issues   = $issues;
		$this->warnings = array_filter( $issues, fn( $i ) => $i->severity === 'warning' );
		$this->passed   = ! $this->hasErrors();
	}

	public function hasErrors(): bool {
		foreach ( $this->issues as $issue ) {
			if ( $issue->severity === 'error' ) {
				return true;
			}
		}
		return false;
	}

	public function toArray(): array {
		return [
			'passed'   => $this->passed,
			'issues'   => array_map( fn( $i ) => $i->toArray(), $this->issues ),
			'warnings' => array_map( fn( $i ) => $i->toArray(), $this->warnings ),
		];
	}
}

/**
 * Validation Issue — a single validation failure or warning.
 */
class ValidationIssue {

	public string $block_id;
	public string $slot_name;
	public string $severity; // error | warning
	public string $rule;
	public string $message;

	public function __construct( string $block_id, string $slot_name, string $severity, string $rule, string $message ) {
		$this->block_id  = $block_id;
		$this->slot_name = $slot_name;
		$this->severity  = $severity;
		$this->rule      = $rule;
		$this->message   = $message;
	}

	public function toArray(): array {
		return [
			'block_id'  => $this->block_id,
			'slot_name' => $this->slot_name,
			'severity'  => $this->severity,
			'rule'      => $this->rule,
			'message'   => $this->message,
		];
	}
}
