<?php
/**
 * Template Block Override Repository
 *
 * Handles data persistence for per-template block rule overrides.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Repositories;

defined( 'ABSPATH' ) || exit;

class TemplateBlockOverrideRepository {

	private const TABLE_NAME        = 'seo_template_block_overrides';
	private const DB_VERSION        = '1.0';
	private const DB_VERSION_OPTION = 'seo_template_override_db_version';

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
			template_id BIGINT(20) UNSIGNED NOT NULL,
			block_id VARCHAR(100) NOT NULL,
			override_json LONGTEXT NOT NULL,
			created_by BIGINT(20) UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_template_block (template_id, block_id),
			INDEX idx_template_id (template_id)
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

	public function findByTemplateAndBlock( int $template_id, string $block_id ): ?array {
		global $wpdb;

		if ( ! $this->tableExists() ) {
			return null;
		}

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->getTableName()} WHERE template_id = %d AND block_id = %s",
				$template_id,
				$block_id
			),
			ARRAY_A
		);

		return $result ? $this->deserialize( $result ) : null;
	}

	public function findAllByTemplateId( int $template_id ): array {
		global $wpdb;

		if ( ! $this->tableExists() ) {
			return [];
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->getTableName()} WHERE template_id = %d ORDER BY block_id ASC",
				$template_id
			),
			ARRAY_A
		);

		return $results ? array_map( [ $this, 'deserialize' ], $results ) : [];
	}

	public function saveOverride( int $template_id, string $block_id, array $override, int $user_id ): bool {
		global $wpdb;

		if ( ! $this->tableExists() ) {
			$this->createTable();
		}

		$table    = $this->getTableName();
		$existing = $this->findByTemplateAndBlock( $template_id, $block_id );

		if ( $existing ) {
			$result = $wpdb->update(
				$table,
				[
					'override_json' => wp_json_encode( $override ),
					'updated_at'    => current_time( 'mysql' ),
				],
				[
					'template_id' => $template_id,
					'block_id'    => $block_id,
				],
				[ '%s', '%s' ],
				[ '%d', '%s' ]
			);
			return false !== $result;
		}

		$inserted = $wpdb->insert(
			$table,
			[
				'template_id'   => $template_id,
				'block_id'      => $block_id,
				'override_json' => wp_json_encode( $override ),
				'created_by'    => $user_id,
				'created_at'    => current_time( 'mysql' ),
				'updated_at'    => current_time( 'mysql' ),
			],
			[ '%d', '%s', '%s', '%d', '%s', '%s' ]
		);

		return false !== $inserted;
	}

	public function deleteOverride( int $template_id, string $block_id ): bool {
		global $wpdb;

		if ( ! $this->tableExists() ) {
			return false;
		}

		$result = $wpdb->delete(
			$this->getTableName(),
			[
				'template_id' => $template_id,
				'block_id'    => $block_id,
			],
			[ '%d', '%s' ]
		);

		return false !== $result && $result > 0;
	}

	public function deleteAllByTemplateId( int $template_id ): int {
		global $wpdb;

		if ( ! $this->tableExists() ) {
			return 0;
		}

		$result = $wpdb->delete(
			$this->getTableName(),
			[ 'template_id' => $template_id ],
			[ '%d' ]
		);

		return false !== $result ? $result : 0;
	}

	private function deserialize( array $row ): array {
		$row['id']            = (int) $row['id'];
		$row['template_id']   = (int) $row['template_id'];
		$row['created_by']    = (int) $row['created_by'];
		$row['override_json'] = json_decode( $row['override_json'] ?? '{}', true ) ?: [];
		return $row;
	}
}
