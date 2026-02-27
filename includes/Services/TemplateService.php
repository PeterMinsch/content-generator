<?php
/**
 * Template Service
 *
 * Business logic layer for template management: validation, seeding, categories.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

use SEOGenerator\Repositories\TemplateRepository;
use SEOGenerator\Repositories\TemplateBlockOverrideRepository;

class TemplateService {

	public const CATEGORIES = [ 'city', 'repair', 'product', 'editorial', 'collection', 'custom' ];

	private TemplateRepository $repository;

	public function __construct( TemplateRepository $repository ) {
		$this->repository = $repository;
	}

	public function create( array $data ): array {
		if ( empty( $data['name'] ) ) {
			return [ 'success' => false, 'message' => 'Template name is required.' ];
		}

		$slug = sanitize_title( $data['slug'] ?? $data['name'] );

		if ( $this->repository->findBySlug( $slug ) ) {
			return [ 'success' => false, 'message' => "A template with slug \"{$slug}\" already exists." ];
		}

		if ( ! empty( $data['category'] ) && ! in_array( $data['category'], self::CATEGORIES, true ) ) {
			return [ 'success' => false, 'message' => 'Invalid category.' ];
		}

		$data['slug'] = $slug;
		$id = $this->repository->save( $data );

		if ( false === $id ) {
			return [ 'success' => false, 'message' => 'Failed to create template.' ];
		}

		return [
			'success'  => true,
			'message'  => 'Template created.',
			'template' => $this->repository->findById( $id ),
		];
	}

	public function update( int $id, array $data ): array {
		$existing = $this->repository->findById( $id );
		if ( ! $existing ) {
			return [ 'success' => false, 'message' => 'Template not found.' ];
		}

		if ( isset( $data['slug'] ) ) {
			$new_slug = sanitize_title( $data['slug'] );
			if ( $new_slug !== $existing['slug'] ) {
				$conflict = $this->repository->findBySlug( $new_slug );
				if ( $conflict && (int) $conflict['id'] !== $id ) {
					return [ 'success' => false, 'message' => "Slug \"{$new_slug}\" is already in use." ];
				}
				$data['slug'] = $new_slug;
			}
		}

		if ( ! empty( $data['category'] ) && ! in_array( $data['category'], self::CATEGORIES, true ) ) {
			return [ 'success' => false, 'message' => 'Invalid category.' ];
		}

		$result = $this->repository->update( $id, $data );

		if ( ! $result ) {
			return [ 'success' => false, 'message' => 'Failed to update template.' ];
		}

		return [
			'success'  => true,
			'message'  => 'Template updated.',
			'template' => $this->repository->findById( $id ),
		];
	}

	public function delete( int $id ): array {
		$existing = $this->repository->findById( $id );
		if ( ! $existing ) {
			return [ 'success' => false, 'message' => 'Template not found.' ];
		}

		if ( $existing['is_system'] ) {
			return [ 'success' => false, 'message' => 'System templates cannot be deleted.' ];
		}

		$result = $this->repository->delete( $id );

		if ( $result ) {
			// Cascade delete block rule overrides for this template.
			$override_repo = new TemplateBlockOverrideRepository();
			$override_repo->deleteAllByTemplateId( $id );
		}

		return $result
			? [ 'success' => true, 'message' => 'Template deleted.' ]
			: [ 'success' => false, 'message' => 'Failed to delete template.' ];
	}

	public function cloneTemplate( int $id, string $new_name ): array {
		$existing = $this->repository->findById( $id );
		if ( ! $existing ) {
			return [ 'success' => false, 'message' => 'Template not found.' ];
		}

		$new_slug = sanitize_title( $new_name );
		$counter  = 1;
		$base     = $new_slug;
		while ( $this->repository->findBySlug( $new_slug ) ) {
			$new_slug = $base . '-' . $counter;
			$counter++;
		}

		$new_id = $this->repository->cloneTemplate( $id, $new_name, $new_slug );

		if ( false === $new_id ) {
			return [ 'success' => false, 'message' => 'Failed to clone template.' ];
		}

		return [
			'success'  => true,
			'message'  => 'Template cloned.',
			'template' => $this->repository->findById( $new_id ),
		];
	}

	public function getById( int $id ): ?array {
		return $this->repository->findById( $id );
	}

	public function getBySlug( string $slug ): ?array {
		return $this->repository->findBySlug( $slug );
	}

	public function getAll( string $status = '', string $category = '' ): array {
		return $this->repository->findAll( $status, $category );
	}

	/**
	 * Seed system templates from config/nextjs-block-definitions.php pages key.
	 * Only creates if slug doesn't already exist in DB.
	 */
	public function seedSystemTemplates(): void {
		$config = require SEO_GENERATOR_PLUGIN_DIR . 'config/nextjs-block-definitions.php';
		$pages  = $config['pages'] ?? [];

		foreach ( $pages as $slug => $page ) {
			if ( $this->repository->findBySlug( $slug ) ) {
				continue;
			}

			// Check for saved block order in wp_options (from old Page Builder).
			$saved_order = get_option( "seo_nextjs_block_order_{$slug}", null );
			$block_order = is_array( $saved_order ) ? $saved_order : ( $page['default_order'] ?? [] );

			$wrapper_config = null;
			if ( ! empty( $page['wrapper_open'] ) || ! empty( $page['wrapper_close'] ) ) {
				$wrapper_config = [
					'wrapper_open'  => $page['wrapper_open'] ?? '',
					'wrapper_close' => $page['wrapper_close'] ?? '',
					'use_client'    => ! empty( $page['use_client'] ),
					'preview_route' => $page['preview_route'] ?? '/preview',
				];
			} else {
				$wrapper_config = [
					'wrapper_open'  => '',
					'wrapper_close' => '',
					'use_client'    => ! empty( $page['use_client'] ),
					'preview_route' => $page['preview_route'] ?? '/preview',
				];
			}

			$this->repository->save( [
				'name'             => $page['label'] ?? ucfirst( $slug ),
				'slug'             => $slug,
				'category'         => 'city',
				'description'      => '',
				'block_order'      => $block_order,
				'wrapper_config'   => $wrapper_config,
				'default_metadata' => $page['default_metadata'] ?? null,
				'status'           => 'active',
				'is_system'        => 1,
				'created_by'       => get_current_user_id() ?: 1,
			] );
		}
	}

	/**
	 * Validate that all block IDs in the order exist in the shared catalog.
	 *
	 * @return array { valid: string[], invalid: string[] }
	 */
	public function validateBlockOrder( array $block_ids ): array {
		$generator = new NextJSPageGenerator();
		$all       = array_keys( $generator->getAllBlocks() );
		$valid     = [];
		$invalid   = [];

		foreach ( $block_ids as $id ) {
			if ( in_array( $id, $all, true ) ) {
				$valid[] = $id;
			} else {
				$invalid[] = $id;
			}
		}

		return [ 'valid' => $valid, 'invalid' => $invalid ];
	}
}
