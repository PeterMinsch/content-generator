<?php
/**
 * Block Rule Profile Repository
 *
 * Handles data persistence for versioned block rule profiles.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Repositories;

defined( 'ABSPATH' ) || exit;

class BlockRuleProfileRepository {

	private const TABLE_NAME        = 'seo_block_rule_profiles';
	private const DB_VERSION        = '1.0';
	private const DB_VERSION_OPTION = 'seo_block_rule_db_version';

	private function getTableName(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	public function createTable(): bool {
		global $wpdb;

		$table_name      = $this->getTableName();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			block_id VARCHAR(100) NOT NULL,
			version INT UNSIGNED NOT NULL DEFAULT 1,
			is_current TINYINT(1) NOT NULL DEFAULT 1,
			schema_json LONGTEXT NOT NULL,
			source VARCHAR(20) NOT NULL DEFAULT 'config',
			notes TEXT NULL,
			created_by BIGINT(20) UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_block_version (block_id, version),
			INDEX idx_block_current (block_id, is_current),
			INDEX idx_source (source)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );

		return $this->tableExists();
	}

	public function tableExists(): bool {
		global $wpdb;
		$table_name = $this->getTableName();
		$result     = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
		return $result === $table_name;
	}

	public function findCurrentByBlockId( string $block_id ): ?array {
		global $wpdb;

		if ( ! $this->tableExists() ) {
			return null;
		}

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->getTableName()} WHERE block_id = %s AND is_current = 1",
				$block_id
			),
			ARRAY_A
		);

		return $result ? $this->deserialize( $result ) : null;
	}

	public function findAllCurrent(): array {
		global $wpdb;

		if ( ! $this->tableExists() ) {
			return [];
		}

		$results = $wpdb->get_results(
			"SELECT * FROM {$this->getTableName()} WHERE is_current = 1 ORDER BY block_id ASC",
			ARRAY_A
		);

		return $results ? array_map( [ $this, 'deserialize' ], $results ) : [];
	}

	public function findVersionsByBlockId( string $block_id ): array {
		global $wpdb;

		if ( ! $this->tableExists() ) {
			return [];
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->getTableName()} WHERE block_id = %s ORDER BY version DESC",
				$block_id
			),
			ARRAY_A
		);

		return $results ? array_map( [ $this, 'deserialize' ], $results ) : [];
	}

	/**
	 * Save a new version of a block rule profile.
	 *
	 * @return int|false Insert ID on success, false on failure.
	 */
	public function saveNewVersion( string $block_id, array $schema, string $source, int $user_id ) {
		global $wpdb;

		if ( ! $this->tableExists() ) {
			$this->createTable();
		}

		$table = $this->getTableName();

		// Get next version number.
		$max_version = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT MAX(version) FROM {$table} WHERE block_id = %s", $block_id )
		);
		$new_version = $max_version + 1;

		// Mark all existing versions as not current.
		$wpdb->update(
			$table,
			[ 'is_current' => 0, 'updated_at' => current_time( 'mysql' ) ],
			[ 'block_id' => $block_id ],
			[ '%d', '%s' ],
			[ '%s' ]
		);

		// Insert new version.
		$inserted = $wpdb->insert(
			$table,
			[
				'block_id'   => $block_id,
				'version'    => $new_version,
				'is_current' => 1,
				'schema_json' => wp_json_encode( $schema ),
				'source'     => $source,
				'created_by' => $user_id,
				'created_at' => current_time( 'mysql' ),
				'updated_at' => current_time( 'mysql' ),
			],
			[ '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%s' ]
		);

		if ( false === $inserted ) {
			error_log( '[SEO Generator] Failed to save block rule profile: ' . $wpdb->last_error );
			return false;
		}

		return $wpdb->insert_id;
	}

	public function revertToVersion( string $block_id, int $version ): bool {
		global $wpdb;

		if ( ! $this->tableExists() ) {
			return false;
		}

		$table = $this->getTableName();

		// Check target version exists.
		$target = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE block_id = %s AND version = %d",
				$block_id,
				$version
			),
			ARRAY_A
		);

		if ( ! $target ) {
			return false;
		}

		// Create a new version with the old schema.
		$target = $this->deserialize( $target );
		$user_id = get_current_user_id() ?: 1;

		$result = $this->saveNewVersion( $block_id, $target['schema_json'], 'edited', $user_id );

		// Add revert note.
		if ( $result ) {
			$wpdb->update(
				$table,
				[ 'notes' => "Reverted to version {$version}" ],
				[ 'id' => $result ],
				[ '%s' ],
				[ '%d' ]
			);
		}

		return false !== $result;
	}

	/**
	 * Seed profiles from config block definitions.
	 *
	 * @return int Number of profiles seeded.
	 */
	public function seedFromConfig( array $config_blocks ): int {
		$count = 0;

		foreach ( $config_blocks as $block_id => $block_def ) {
			// Skip if already has a profile.
			if ( $this->findCurrentByBlockId( $block_id ) ) {
				continue;
			}

			$schema = BlockRuleProfileRepository::configToSchemaArray( $block_def );
			if ( empty( $schema['content_slots'] ) && empty( $schema['images'] ) ) {
				continue;
			}

			$result = $this->saveNewVersion( $block_id, $schema, 'config', 1 );
			if ( $result ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Convert a config block definition to a schema array.
	 */
	public static function configToSchemaArray( array $block_def ): array {
		$schema = [
			'content_slots' => [],
			'images'        => [],
			'breadcrumb'    => [ 'enabled' => false, 'pattern' => 'Home / {page_title}' ],
		];

		if ( ! empty( $block_def['content_slots'] ) ) {
			foreach ( $block_def['content_slots'] as $slot_name => $slot_def ) {
				$schema['content_slots'][ $slot_name ] = [
					'type'              => $slot_def['type'] ?? 'text',
					'max_length'        => $slot_def['max_length'] ?? 100,
					'mobile_max_length' => $slot_def['mobile_max_length'] ?? ( $slot_def['max_length'] ?? 100 ),
					'mobile_hidden'     => ! empty( $slot_def['mobile_hidden'] ),
					'ai_hint'           => $slot_def['ai_hint'] ?? '',
					'required'          => true,
					'over_limit_action' => 'truncate',
					'validation'        => [
						'min_length'           => 0,
						'forbidden_patterns'   => [],
						'must_contain_keyword' => false,
					],
				];
			}
		}

		if ( ! empty( $block_def['images'] ) ) {
			foreach ( $block_def['images'] as $img ) {
				$schema['images'][] = [
					'label'             => $img['label'] ?? 'Image',
					'desktop'           => $img['desktop'] ?? [ 800, 600 ],
					'mobile'            => $img['mobile'] ?? null,
					'required'          => true,
					'alt_text_required' => true,
					'source_rule'       => 'library',
				];
			}
		}

		return $schema;
	}

	private function deserialize( array $row ): array {
		$row['id']          = (int) $row['id'];
		$row['version']     = (int) $row['version'];
		$row['is_current']  = (int) $row['is_current'];
		$row['created_by']  = (int) $row['created_by'];
		$row['schema_json'] = json_decode( $row['schema_json'] ?? '{}', true ) ?: [];
		return $row;
	}
}
