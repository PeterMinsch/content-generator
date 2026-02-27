<?php
/**
 * Bulk Publish Service
 *
 * Orchestrates the CSV → AI slot generation → dynamic page publish pipeline.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

use SEOGenerator\Repositories\TemplateRepository;

class BulkPublishService {

	/**
	 * @var SlotContentGenerator
	 */
	private SlotContentGenerator $slot_generator;

	/**
	 * @var NextJSPageGenerator
	 */
	private NextJSPageGenerator $page_generator;

	/**
	 * @var TemplateService|null
	 */
	private ?TemplateService $template_service;

	/**
	 * @var ValidationService|null
	 */
	private ?ValidationService $validation_service;

	/**
	 * Constructor.
	 *
	 * @param SlotContentGenerator    $slot_generator      Slot content generator.
	 * @param NextJSPageGenerator     $page_generator      Page generator.
	 * @param TemplateService|null    $template_service    Template service (optional).
	 * @param ValidationService|null  $validation_service  Validation service (optional).
	 */
	public function __construct( SlotContentGenerator $slot_generator, NextJSPageGenerator $page_generator, ?TemplateService $template_service = null, ?ValidationService $validation_service = null ) {
		$this->slot_generator     = $slot_generator;
		$this->page_generator     = $page_generator;
		$this->template_service   = $template_service;
		$this->validation_service = $validation_service;
	}

	/**
	 * Process a single row from a CSV and publish a dynamic page.
	 *
	 * @param array $row            Parsed CSV row with keys: keyword, slug, page_template, blocks (optional).
	 * @param array $global_context Business settings and overrides.
	 * @return array { success: bool, message: string, slug?: string }
	 */
	public function processRow( array $row, array $global_context = [] ): array {
		$keyword       = trim( $row['keyword'] ?? '' );
		$slug          = sanitize_title( $row['slug'] ?? '' );
		$page_template = sanitize_key( $row['page_template'] ?? 'homepage' );

		if ( empty( $keyword ) ) {
			return [ 'success' => false, 'message' => 'Missing keyword.' ];
		}

		if ( empty( $slug ) ) {
			$slug = sanitize_title( $keyword );
		}

		if ( ! $this->page_generator->isSlugSafe( $slug ) ) {
			return [ 'success' => false, 'message' => "Slug \"{$slug}\" is reserved." ];
		}

		// Determine block order: try DB template first, then config fallback.
		$template_id = null;
		if ( ! empty( $row['blocks'] ) ) {
			$block_order = array_map( 'trim', explode( ',', $row['blocks'] ) );
		} else {
			$block_order = [];

			// Try DB template.
			if ( $this->template_service ) {
				$db_template = $this->template_service->getBySlug( $page_template );
				if ( $db_template ) {
					$block_order = $db_template['block_order'] ?? [];
					$template_id = (int) $db_template['id'];
				}
			}

			// Fallback to config.
			if ( empty( $block_order ) ) {
				$block_order = $this->page_generator->getDefaultOrder( $page_template );
			}
		}

		if ( empty( $block_order ) ) {
			$block_order = $this->page_generator->getDefaultOrder( 'homepage' );
		}

		// Build generation context.
		$context = array_merge( $global_context, [
			'focus_keyword' => $keyword,
			'page_title'    => ucwords( $keyword ),
		] );

		// Generate slot content via AI (passes template_id for rule overrides).
		try {
			$slot_content = $this->slot_generator->generateForPage( $block_order, $context, $template_id );
		} catch ( \Exception $e ) {
			error_log( "[SEO Generator] Slot generation failed for '{$keyword}': " . $e->getMessage() );
			$slot_content = [];
		}

		// Post-generation validation (if ValidationService available).
		$validation_issues = [];
		if ( $this->validation_service && ! empty( $slot_content ) ) {
			$validation_result = $this->validation_service->validatePage(
				$slot_content,
				$block_order,
				$template_id,
				$keyword
			);
			if ( ! empty( $validation_result->issues ) ) {
				$validation_issues = $validation_result->toArray()['issues'];
			}
		}

		// Generate SEO metadata via AI.
		try {
			$metadata = $this->slot_generator->generateMetadata( $context );
		} catch ( \Exception $e ) {
			error_log( "[SEO Generator] Metadata generation failed for '{$keyword}': " . $e->getMessage() );
			$metadata = null;
		}

		// Publish the dynamic page.
		$result = $this->page_generator->publish(
			$page_template,
			$block_order,
			$slug,
			$slot_content,
			$metadata
		);

		if ( $result['success'] ) {
			$result['slug'] = $slug;
		}

		if ( ! empty( $validation_issues ) ) {
			$result['validation_issues'] = $validation_issues;
		}

		return $result;
	}

	/**
	 * Queue a batch of CSV rows for background processing.
	 *
	 * @param array $rows           Array of parsed CSV rows.
	 * @param array $global_context Business settings.
	 * @return array { queued: int, errors: string[] }
	 */
	public function queueBatch( array $rows, array $global_context = [] ): array {
		$queue  = new GenerationQueue();
		$queued = 0;
		$errors = [];

		foreach ( $rows as $index => $row ) {
			$keyword = trim( $row['keyword'] ?? '' );
			$slug    = sanitize_title( $row['slug'] ?? '' );

			if ( empty( $keyword ) ) {
				$errors[] = "Row " . ( $index + 1 ) . ": Missing keyword.";
				continue;
			}

			if ( empty( $slug ) ) {
				$slug = sanitize_title( $keyword );
			}

			$success = $queue->queueDynamicPublish( $index, [
				'keyword'       => $keyword,
				'slug'          => $slug,
				'page_template' => $row['page_template'] ?? 'homepage',
				'blocks'        => $row['blocks'] ?? '',
				'context'       => $global_context,
			] );

			if ( $success ) {
				$queued++;
			} else {
				$errors[] = "Row " . ( $index + 1 ) . ": Already queued.";
			}
		}

		return [
			'queued' => $queued,
			'errors' => $errors,
		];
	}

	/**
	 * Process a single queued dynamic publish job (called by cron).
	 *
	 * @param array $job_data The job data from the queue item.
	 * @return array Result with success/error.
	 */
	public function processQueuedJob( array $job_data ): array {
		return $this->processRow( $job_data, $job_data['context'] ?? [] );
	}
}
