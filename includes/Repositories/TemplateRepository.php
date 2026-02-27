<?php
/**
 * Template Repository
 *
 * Handles data persistence for page templates in custom database table.
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Repositories;

defined( 'ABSPATH' ) || exit;

class TemplateRepository {

	private const TABLE_NAME = 'seo_templates';
	private const DB_VERSION = '1.0';
	private const DB_VERSION_OPTION = 'seo_template_db_version';

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
			name VARCHAR(255) NOT NULL,
			slug VARCHAR(255) NOT NULL,
			category VARCHAR(50) NOT NULL DEFAULT 'city',
			description TEXT NULL,
			block_order LONGTEXT NOT NULL,
			wrapper_config LONGTEXT NULL,
			default_metadata LONGTEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'draft',
			is_system TINYINT(1) NOT NULL DEFAULT 0,
			created_by BIGINT(20) UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_slug (slug),
			INDEX idx_category (category),
			INDEX idx_status (status)
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

	/**
	 * @return int|false Template ID on success, false on failure.
	 */
	public function save( array $data ) {
		global $wpdb;

		if ( ! $this->tableExists() ) {
			$this->createTable();
		}

		$inserted = $wpdb->insert(
			$this->getTableName(),
			array(
				'name'             => sanitize_text_field( $data['name'] ),
				'slug'             => sanitize_title( $data['slug'] ),
				'category'         => sanitize_key( $data['category'] ?? 'city' ),
				'description'      => sanitize_textarea_field( $data['description'] ?? '' ),
				'block_order'      => wp_json_encode( $data['block_order'] ?? [] ),
				'wrapper_config'   => wp_json_encode( $data['wrapper_config'] ?? null ),
				'default_metadata' => wp_json_encode( $data['default_metadata'] ?? null ),
				'status'           => sanitize_key( $data['status'] ?? 'draft' ),
				'is_system'        => absint( $data['is_system'] ?? 0 ),
				'created_by'       => absint( $data['created_by'] ?? get_current_user_id() ),
				'created_at'       => current_time( 'mysql' ),
				'updated_at'       => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
		);

		if ( false === $inserted ) {
			error_log( 'Failed to save template: ' . $wpdb->last_error );
			return false;
		}

		return $wpdb->insert_id;
	}

	public function update( int $id, array $data ): bool {
		global $wpdb;

		if ( ! $this->tableExists() ) {
			return false;
		}

		$update = array( 'updated_at' => current_time( 'mysql' ) );
		$format = array( '%s' );

		if ( isset( $data['name'] ) ) {
			$update['name'] = sanitize_text_field( $data['name'] );
			$format[]       = '%s';
		}
		if ( isset( $data['slug'] ) ) {
			$update['slug'] = sanitize_title( $data['slug'] );
			$format[]       = '%s';
		}
		if ( isset( $data['category'] ) ) {
			$update['category'] = sanitize_key( $data['category'] );
			$format[]           = '%s';
		}
		if ( array_key_exists( 'description', $data ) ) {
			$update['description'] = sanitize_textarea_field( $data['description'] ?? '' );
			$format[]              = '%s';
		}
		if ( isset( $data['block_order'] ) ) {
			$update['block_order'] = wp_json_encode( $data['block_order'] );
			$format[]              = '%s';
		}
		if ( array_key_exists( 'wrapper_config', $data ) ) {
			$update['wrapper_config'] = wp_json_encode( $data['wrapper_config'] );
			$format[]                 = '%s';
		}
		if ( array_key_exists( 'default_metadata', $data ) ) {
			$update['default_metadata'] = wp_json_encode( $data['default_metadata'] );
			$format[]                   = '%s';
		}
		if ( isset( $data['status'] ) ) {
			$update['status'] = sanitize_key( $data['status'] );
			$format[]         = '%s';
		}

		$result = $wpdb->update(
			$this->getTableName(),
			$update,
			array( 'id' => $id ),
			$format,
			array( '%d' )
		);

		return false !== $result;
	}

	public function findById( int $id ): ?array {
		global $wpdb;

		if ( ! $this->tableExists() ) {
			return null;
		}

		$result = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->getTableName()} WHERE id = %d", $id ),
			ARRAY_A
		);

		return $result ? $this->deserialize( $result ) : null;
	}

	public function findBySlug( string $slug ): ?array {
		global $wpdb;

		if ( ! $this->tableExists() ) {
			return null;
		}

		$result = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->getTableName()} WHERE slug = %s", $slug ),
			ARRAY_A
		);

		return $result ? $this->deserialize( $result ) : null;
	}

	public function findAll( string $status = '', string $category = '' ): array {
		global $wpdb;

		if ( ! $this->tableExists() ) {
			return array();
		}

		$table = $this->getTableName();
		$where = array( '1=1' );
		$args  = array();

		if ( ! empty( $status ) ) {
			$where[] = 'status = %s';
			$args[]  = $status;
		}
		if ( ! empty( $category ) ) {
			$where[] = 'category = %s';
			$args[]  = $category;
		}

		$where_sql = implode( ' AND ', $where );
		$sql       = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY is_system DESC, name ASC";

		if ( ! empty( $args ) ) {
			$sql = $wpdb->prepare( $sql, ...$args );
		}

		$results = $wpdb->get_results( $sql, ARRAY_A );

		if ( ! $results ) {
			return array();
		}

		return array_map( array( $this, 'deserialize' ), $results );
	}

	public function count(): int {
		global $wpdb;

		if ( ! $this->tableExists() ) {
			return 0;
		}

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->getTableName()}" );
	}

	public function delete( int $id ): bool {
		global $wpdb;

		if ( ! $this->tableExists() ) {
			return false;
		}

		$result = $wpdb->delete(
			$this->getTableName(),
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $result && $result > 0;
	}

	/**
	 * @return int|false New template ID on success, false on failure.
	 */
	public function cloneTemplate( int $id, string $new_name, string $new_slug ) {
		$original = $this->findById( $id );
		if ( ! $original ) {
			return false;
		}

		return $this->save( array(
			'name'             => $new_name,
			'slug'             => $new_slug,
			'category'         => $original['category'],
			'description'      => $original['description'],
			'block_order'      => $original['block_order'],
			'wrapper_config'   => $original['wrapper_config'],
			'default_metadata' => $original['default_metadata'],
			'status'           => 'draft',
			'is_system'        => 0,
			'created_by'       => get_current_user_id(),
		) );
	}

	public function dropTable(): bool {
		global $wpdb;

		$result = $wpdb->query( "DROP TABLE IF EXISTS {$this->getTableName()}" );
		delete_option( self::DB_VERSION_OPTION );

		return false !== $result;
	}

	private function deserialize( array $row ): array {
		$row['block_order']      = json_decode( $row['block_order'] ?? '[]', true ) ?: [];
		$row['wrapper_config']   = json_decode( $row['wrapper_config'] ?? 'null', true );
		$row['default_metadata'] = json_decode( $row['default_metadata'] ?? 'null', true );
		$row['is_system']        = (int) $row['is_system'];
		$row['id']               = (int) $row['id'];
		$row['created_by']       = (int) $row['created_by'];
		return $row;
	}
}
