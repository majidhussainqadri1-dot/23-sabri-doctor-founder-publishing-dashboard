<?php
/**
 * Database schema for File 23-owned organizational metadata only.
 *
 * Native content, destinations, clinical records, media, reports, and raw
 * analytics remain with their canonical owners.
 */
defined( 'ABSPATH' ) || exit;

final class SPDB_Collections_Schema {
	public const VERSION = '4';
	private const OPTION_KEY = 'spdb_collections_schema_version';

	/** @return true|WP_Error */
	public static function maybe_upgrade() {
		$verified = self::verify();
		if ( self::VERSION === (string) get_option( self::OPTION_KEY, '' ) && true === $verified ) {
			return true;
		}
		return self::install();
	}

	/** @return true|WP_Error */
	public static function install() {
		global $wpdb;
		if ( ! self::database_available( $wpdb ) ) {
			return self::error( 'spdb_collections_database_unavailable', 'The WordPress database service is unavailable.' );
		}
		$upgrade_file = ABSPATH . 'wp-admin/includes/upgrade.php';
		if ( ! is_file( $upgrade_file ) ) {
			return self::error( 'spdb_collections_upgrade_api_unavailable', 'The WordPress database upgrade API is unavailable.' );
		}
		require_once $upgrade_file;
		if ( ! function_exists( 'dbDelta' ) ) {
			return self::error( 'spdb_collections_upgrade_api_unavailable', 'The WordPress database upgrade API is unavailable.' );
		}

		$charset     = method_exists( $wpdb, 'get_charset_collate' ) ? $wpdb->get_charset_collate() : '';
		$collections = self::collections_table();
		$items       = self::items_table();
		$links       = self::links_table();
		$sql         = array();

		$sql[] = "CREATE TABLE {$collections} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			collection_id varchar(64) NOT NULL,
			record_type varchar(16) NOT NULL,
			scope varchar(16) NOT NULL,
			title varchar(200) NOT NULL,
			objective text NOT NULL,
			ethical_declaration text NOT NULL,
			owner_user_id bigint(20) unsigned NOT NULL,
			contributors_json longtext NOT NULL,
			target_surfaces_json longtext NOT NULL,
			status varchar(24) NOT NULL,
			start_at_gmt datetime NULL,
			end_at_gmt datetime NULL,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			idempotency_hash char(64) NOT NULL,
			request_hash char(64) NOT NULL,
			last_idempotency_hash char(64) NOT NULL,
			last_request_hash char(64) NOT NULL,
			created_audit_reason varchar(500) NOT NULL,
			last_audit_reason varchar(500) NOT NULL,
			created_by bigint(20) unsigned NOT NULL,
			created_at_gmt datetime NOT NULL,
			updated_at_gmt datetime NOT NULL,
			archived_at_gmt datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY collection_id (collection_id),
			UNIQUE KEY actor_idempotency (created_by,idempotency_hash),
			KEY owner_scope (owner_user_id,scope),
			KEY type_status (record_type,status),
			KEY updated_at_gmt (updated_at_gmt)
		) ENGINE=InnoDB {$charset};";

		$sql[] = "CREATE TABLE {$items} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			item_id varchar(64) NOT NULL,
			collection_id varchar(64) NOT NULL,
			provider_key varchar(64) NOT NULL,
			object_type varchar(64) NOT NULL,
			object_id varchar(128) NOT NULL,
			relation_type varchar(64) NOT NULL,
			reference_hash char(64) NOT NULL,
			native_version varchar(191) NOT NULL,
			position int(10) unsigned NOT NULL DEFAULT 0,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			idempotency_hash char(64) NOT NULL,
			request_hash char(64) NOT NULL,
			last_idempotency_hash char(64) NOT NULL,
			last_request_hash char(64) NOT NULL,
			created_audit_reason varchar(500) NOT NULL,
			last_audit_reason varchar(500) NOT NULL,
			added_by bigint(20) unsigned NOT NULL,
			created_at_gmt datetime NOT NULL,
			updated_at_gmt datetime NOT NULL,
			archived_at_gmt datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY item_id (item_id),
			UNIQUE KEY actor_idempotency (added_by,idempotency_hash),
			UNIQUE KEY collection_reference (collection_id,reference_hash),
			KEY collection_position (collection_id,position),
			KEY native_reference (provider_key,object_type,object_id)
		) ENGINE=InnoDB {$charset};";

		$sql[] = "CREATE TABLE {$links} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			link_id varchar(64) NOT NULL,
			scope varchar(16) NOT NULL,
			owner_user_id bigint(20) unsigned NOT NULL,
			source_provider_key varchar(64) NOT NULL,
			source_object_type varchar(64) NOT NULL,
			source_object_id varchar(128) NOT NULL,
			source_native_version varchar(191) NOT NULL,
			target_provider_key varchar(64) NOT NULL,
			target_object_type varchar(64) NOT NULL,
			target_object_id varchar(128) NOT NULL,
			target_native_version varchar(191) NOT NULL,
			relation_type varchar(64) NOT NULL,
			relation_hash char(64) NOT NULL,
			status varchar(16) NOT NULL,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			idempotency_hash char(64) NOT NULL,
			request_hash char(64) NOT NULL,
			last_idempotency_hash char(64) NOT NULL,
			last_request_hash char(64) NOT NULL,
			created_audit_reason varchar(500) NOT NULL,
			last_audit_reason varchar(500) NOT NULL,
			created_by bigint(20) unsigned NOT NULL,
			created_at_gmt datetime NOT NULL,
			updated_at_gmt datetime NOT NULL,
			archived_at_gmt datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY link_id (link_id),
			UNIQUE KEY actor_idempotency (created_by,idempotency_hash),
			UNIQUE KEY scoped_relation (owner_user_id,scope,relation_hash),
			KEY owner_scope (owner_user_id,scope),
			KEY relation_status (relation_type,status),
			KEY source_reference (source_provider_key,source_object_type,source_object_id),
			KEY target_reference (target_provider_key,target_object_type,target_object_id)
		) ENGINE=InnoDB {$charset};";

		dbDelta( $sql );
		$converted = self::ensure_transactional_tables();
		if ( is_wp_error( $converted ) ) { return $converted; }
		$verified = self::verify();
		if ( is_wp_error( $verified ) ) {
			return $verified;
		}
		if ( ! update_option( self::OPTION_KEY, self::VERSION, false ) && self::VERSION !== (string) get_option( self::OPTION_KEY, '' ) ) {
			return self::error( 'spdb_collections_schema_version_write_failed', 'The File 23 metadata schema version could not be recorded.' );
		}
		return true;
	}

	/** @return true|WP_Error */
	public static function verify() {
		global $wpdb;
		if ( ! self::database_available( $wpdb ) ) {
			return self::error( 'spdb_collections_database_unavailable', 'The WordPress database service is unavailable.' );
		}
		foreach ( self::requirements() as $table => $requirement ) {
			if ( ! self::table_exists( $table ) ) {
				return self::error( 'spdb_collections_schema_table_missing', 'A required File 23 metadata table is missing.' );
			}
			if ( 'innodb' !== strtolower( self::table_engine( $table ) ) ) {
				return self::error( 'spdb_collections_schema_not_transactional', 'A required File 23 metadata table is not using InnoDB.' );
			}
			$columns = self::column_names( $table );
			if ( is_wp_error( $columns ) || array_diff( $requirement['columns'], $columns ) ) {
				return self::error( 'spdb_collections_schema_column_missing', 'A required File 23 metadata column is missing.' );
			}
			$indexes = self::index_names( $table );
			if ( is_wp_error( $indexes ) || array_diff( $requirement['indexes'], $indexes ) ) {
				return self::error( 'spdb_collections_schema_index_missing', 'A required File 23 metadata index is missing.' );
			}
		}
		return true;
	}

	/** @return array<string,array{columns:string[],indexes:string[]}> */
	public static function requirements(): array {
		return array(
			self::collections_table() => array(
				'columns' => array( 'collection_id', 'record_type', 'scope', 'title', 'objective', 'ethical_declaration', 'owner_user_id', 'contributors_json', 'target_surfaces_json', 'status', 'start_at_gmt', 'end_at_gmt', 'version', 'idempotency_hash', 'request_hash', 'last_idempotency_hash', 'last_request_hash', 'created_audit_reason', 'last_audit_reason', 'created_by', 'created_at_gmt', 'updated_at_gmt', 'archived_at_gmt' ),
				'indexes' => array( 'PRIMARY', 'collection_id', 'actor_idempotency', 'owner_scope', 'type_status', 'updated_at_gmt' ),
			),
			self::items_table() => array(
				'columns' => array( 'item_id', 'collection_id', 'provider_key', 'object_type', 'object_id', 'relation_type', 'reference_hash', 'native_version', 'position', 'version', 'idempotency_hash', 'request_hash', 'last_idempotency_hash', 'last_request_hash', 'created_audit_reason', 'last_audit_reason', 'added_by', 'created_at_gmt', 'updated_at_gmt', 'archived_at_gmt' ),
				'indexes' => array( 'PRIMARY', 'item_id', 'actor_idempotency', 'collection_reference', 'collection_position', 'native_reference' ),
			),
			self::links_table() => array(
				'columns' => array( 'link_id', 'scope', 'owner_user_id', 'source_provider_key', 'source_object_type', 'source_object_id', 'source_native_version', 'target_provider_key', 'target_object_type', 'target_object_id', 'target_native_version', 'relation_type', 'relation_hash', 'status', 'version', 'idempotency_hash', 'request_hash', 'last_idempotency_hash', 'last_request_hash', 'created_audit_reason', 'last_audit_reason', 'created_by', 'created_at_gmt', 'updated_at_gmt', 'archived_at_gmt' ),
				'indexes' => array( 'PRIMARY', 'link_id', 'actor_idempotency', 'scoped_relation', 'owner_scope', 'relation_status', 'source_reference', 'target_reference' ),
			),
		);
	}

	/** @return string[] */
	public static function tables(): array {
		return array( self::collections_table(), self::items_table(), self::links_table() );
	}
	public static function collections_table(): string { global $wpdb; return (string) $wpdb->prefix . 'spdb_collections'; }
	public static function items_table(): string { global $wpdb; return (string) $wpdb->prefix . 'spdb_collection_items'; }
	public static function links_table(): string { global $wpdb; return (string) $wpdb->prefix . 'spdb_knowledge_links'; }

	/** @return true|WP_Error */
	private static function ensure_transactional_tables() {
		global $wpdb;
		if ( ! method_exists( $wpdb, 'query' ) ) { return self::error( 'spdb_collections_database_unavailable', 'The WordPress database service is unavailable.' ); }
		foreach ( self::tables() as $table ) {
			if ( ! self::safe_identifier( $table ) ) { return self::error( 'spdb_collections_schema_identifier_invalid', 'A metadata table identifier is invalid.' ); }
			$engine = self::table_engine( $table );
			if ( '' !== $engine && 'innodb' !== strtolower( $engine ) ) {
				$result = $wpdb->query( "ALTER TABLE `{$table}` ENGINE=InnoDB" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Identifier is strictly allowlisted.
				if ( false === $result ) { return self::error( 'spdb_collections_engine_upgrade_failed', 'A File 23 metadata table could not be upgraded to InnoDB.' ); }
			}
		}
		return true;
	}
	private static function table_engine( string $table ): string {
		global $wpdb;
		if ( ! self::safe_identifier( $table ) || ! method_exists( $wpdb, 'get_row' ) ) { return ''; }
		$row = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS LIKE %s', $table ), self::array_output() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Identifier is strictly allowlisted.
		return is_array( $row ) ? (string) ( $row['Engine'] ?? '' ) : '';
	}
	private static function database_available( $wpdb ): bool {
		return is_object( $wpdb ) && isset( $wpdb->prefix ) && method_exists( $wpdb, 'get_var' ) && method_exists( $wpdb, 'get_results' ) && method_exists( $wpdb, 'get_row' ) && method_exists( $wpdb, 'prepare' ) && method_exists( $wpdb, 'esc_like' );
	}
	private static function table_exists( string $table ): bool {
		global $wpdb;
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
		return is_string( $found ) && hash_equals( $table, $found );
	}
	/** @return string[]|WP_Error */
	private static function column_names( string $table ) {
		if ( ! self::safe_identifier( $table ) ) { return self::error( 'spdb_collections_schema_identifier_invalid', 'A metadata table identifier is invalid.' ); }
		global $wpdb;
		$rows = $wpdb->get_results( "SHOW COLUMNS FROM `{$table}`", self::array_output() );
		if ( ! is_array( $rows ) ) { return self::error( 'spdb_collections_schema_inspection_failed', 'The File 23 metadata schema could not be inspected.' ); }
		return array_values( array_filter( array_map( static fn( $row ) => is_array( $row ) ? (string) ( $row['Field'] ?? '' ) : '', $rows ) ) );
	}
	/** @return string[]|WP_Error */
	private static function index_names( string $table ) {
		if ( ! self::safe_identifier( $table ) ) { return self::error( 'spdb_collections_schema_identifier_invalid', 'A metadata table identifier is invalid.' ); }
		global $wpdb;
		$rows = $wpdb->get_results( "SHOW INDEX FROM `{$table}`", self::array_output() );
		if ( ! is_array( $rows ) ) { return self::error( 'spdb_collections_schema_inspection_failed', 'The File 23 metadata schema could not be inspected.' ); }
		$names = array_map( static fn( $row ) => is_array( $row ) ? (string) ( $row['Key_name'] ?? '' ) : '', $rows );
		return array_values( array_unique( array_filter( $names ) ) );
	}
	private static function safe_identifier( string $value ): bool { return 1 === preg_match( '/^[A-Za-z0-9_]+$/', $value ); }
	private static function array_output() { return defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A'; }
	private static function error( string $code, string $message ): WP_Error { return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => 500 ) ); }
}
