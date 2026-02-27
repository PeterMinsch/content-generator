<?php
/**
 * Block Rule Service
 *
 * Core business logic for block rules: resolution, merging, seeding, versioning.
 * Merges: config defaults → DB profile → template override.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

use SEOGenerator\Repositories\BlockRuleProfileRepository;
use SEOGenerator\Repositories\TemplateBlockOverrideRepository;

class BlockRuleService {

	private BlockRuleProfileRepository $profile_repo;
	private TemplateBlockOverrideRepository $override_repo;

	/**
	 * Runtime cache: $cache[block_id][template_id] = resolved rules.
	 */
	private array $cache = [];

	public function __construct(
		BlockRuleProfileRepository $profile_repo,
		TemplateBlockOverrideRepository $override_repo
	) {
		$this->profile_repo  = $profile_repo;
		$this->override_repo = $override_repo;
	}

	/**
	 * Get fully resolved rules for a block, optionally with template overrides.
	 *
	 * Merge order: config → DB profile → template override.
	 */
	public function getResolvedRules( string $block_id, ?int $template_id = null ): array {
		$cache_key = $template_id ?? 0;

		if ( isset( $this->cache[ $block_id ][ $cache_key ] ) ) {
			return $this->cache[ $block_id ][ $cache_key ];
		}

		// Start with config defaults.
		$config = $this->getConfigSchema( $block_id );

		// Layer DB profile.
		$profile = $this->profile_repo->findCurrentByBlockId( $block_id );
		if ( $profile && ! empty( $profile['schema_json'] ) ) {
			$config = $this->deepMerge( $config, $profile['schema_json'] );
		}

		// Layer template override.
		if ( $template_id ) {
			$override = $this->override_repo->findByTemplateAndBlock( $template_id, $block_id );
			if ( $override && ! empty( $override['override_json'] ) ) {
				$config = $this->deepMerge( $config, $override['override_json'] );
			}
		}

		$this->cache[ $block_id ][ $cache_key ] = $config;

		return $config;
	}

	/**
	 * Get resolved content_slots schema for a block.
	 */
	public function getResolvedSlotSchema( string $block_id, ?int $template_id = null ): array {
		$rules = $this->getResolvedRules( $block_id, $template_id );
		return $rules['content_slots'] ?? [];
	}

	/**
	 * Get resolved image specs for a block.
	 */
	public function getResolvedImageSpecs( string $block_id, ?int $template_id = null ): array {
		$rules = $this->getResolvedRules( $block_id, $template_id );
		return $rules['images'] ?? [];
	}

	/**
	 * Seed all block profiles from config definitions.
	 *
	 * @return int Number of profiles seeded.
	 */
	public function seedFromConfig(): int {
		$generator  = new NextJSPageGenerator();
		$all_blocks = $generator->getAllBlocks();

		return $this->profile_repo->seedFromConfig( $all_blocks );
	}

	/**
	 * Update a block profile with new schema (creates new version).
	 *
	 * @return int|false New version ID on success, false on failure.
	 */
	public function updateProfile( string $block_id, array $schema_json ) {
		$user_id = get_current_user_id() ?: 1;
		$result  = $this->profile_repo->saveNewVersion( $block_id, $schema_json, 'edited', $user_id );

		// Clear cache for this block.
		unset( $this->cache[ $block_id ] );

		return $result;
	}

	/**
	 * Revert a block profile to a specific version.
	 */
	public function revertProfile( string $block_id, int $version ): bool {
		$result = $this->profile_repo->revertToVersion( $block_id, $version );
		unset( $this->cache[ $block_id ] );
		return $result;
	}

	/**
	 * Reset a block to its factory config (creates a new version from config).
	 */
	public function resetToFactory( string $block_id ): bool {
		$config_schema = $this->getConfigSchema( $block_id );
		if ( empty( $config_schema['content_slots'] ) && empty( $config_schema['images'] ) ) {
			return false;
		}

		$user_id = get_current_user_id() ?: 1;
		$result  = $this->profile_repo->saveNewVersion( $block_id, $config_schema, 'config', $user_id );

		unset( $this->cache[ $block_id ] );

		return false !== $result;
	}

	/**
	 * Get version history for a block.
	 */
	public function getVersionHistory( string $block_id ): array {
		return $this->profile_repo->findVersionsByBlockId( $block_id );
	}

	/**
	 * Get all current profiles.
	 */
	public function getAllCurrentProfiles(): array {
		return $this->profile_repo->findAllCurrent();
	}

	/**
	 * Get the config-derived schema for a block (no DB).
	 */
	private function getConfigSchema( string $block_id ): array {
		$generator  = new NextJSPageGenerator();
		$all_blocks = $generator->getAllBlocks();

		if ( ! isset( $all_blocks[ $block_id ] ) ) {
			return [
				'content_slots' => [],
				'images'        => [],
				'breadcrumb'    => [ 'enabled' => false, 'pattern' => 'Home / {page_title}' ],
			];
		}

		return BlockRuleProfileRepository::configToSchemaArray( $all_blocks[ $block_id ] );
	}

	/**
	 * Recursive deep merge: override values replace base values.
	 * Arrays with numeric keys are replaced entirely; assoc arrays are merged recursively.
	 */
	public function deepMerge( array $base, array $override ): array {
		foreach ( $override as $key => $value ) {
			if (
				is_array( $value ) &&
				isset( $base[ $key ] ) &&
				is_array( $base[ $key ] ) &&
				$this->isAssoc( $value )
			) {
				$base[ $key ] = $this->deepMerge( $base[ $key ], $value );
			} else {
				$base[ $key ] = $value;
			}
		}

		return $base;
	}

	/**
	 * Check if an array is associative.
	 */
	private function isAssoc( array $arr ): bool {
		if ( empty( $arr ) ) {
			return false;
		}
		return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
	}

	/**
	 * Clear the runtime cache.
	 */
	public function clearCache(): void {
		$this->cache = [];
	}
}
