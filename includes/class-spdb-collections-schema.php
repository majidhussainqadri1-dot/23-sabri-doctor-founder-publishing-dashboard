<?php
/**
 * Database schema for File 23-owned cross-module metadata only.
 *
 * The schema deliberately stores canonical identifiers and bounded governance
 * metadata. Native destinations, content bodies, reports, clinical records,
 * media, and analytics remain with their canonical owners.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Collections_Schema {
	public const VERSION = '2';
	private const OPTION_KEY = 'spdb_collections_schema_version';

	/** @return true|WP_Error */
	public static function maybe_upgrade() {
		if ( self::VERSION === (string) get_option( self::OPTION_KEY, '' ) ) {
			return true;
		}
		return self::install();
	}

	/** @return true|WP_Error */
	public static function install() {
		global $wpdb;
		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) || ! isset( $wpdb->prefix ) || ! method_exists( $wpdb, 'get_var' ) || ! method_exists( $wpdb, 'prepare' ) ) {
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

		$sql = array();
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
			last_idempotency_hash char(64) NOT NULL,
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
		) {$charset};";

		$sql[] = "CREATE TABLE {$items} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			item_id varchar(64) NOT NULL,
			collection_id varchar(64) NOT NULL,
			provider_key varchar(64) NOT NULL,
			object_type varchar(64) NOT NULL,
			object_id varchar(128) NOT NULL,
			relation_type varchar(64) NOT NULL,
			reference_hash char(64) NOT NULL,
			position int(10) unsigned NOT NULL DEFAULT 0,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			idempotency_hash char(64) NOT NULL,
			last_idempotency_hash char(64) NOT NULL,
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
		) {$charset};";

		$sql[] = "CREATE TABLE {$links} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			link_id varchar(64) NOT NULL,
			scope varchar(16) NOT NULL,
			owner_user_id bigint(20) unsigned NOT NULL,
			source_provider_key varchar(64) NOT NULL,
			source_object_type varchar(64) NOT NULL,
			source_object_id varchar(128) NOT NULL,
			target_provider_key varchar(64) NOT NULL,
			target_object_type varchar(64) NOT NULL,
			target_object_id varchar(128) NOT NULL,
			relation_type varchar(64) NOT NULL,
			relation_hash char(64) NOT NULL,
			status varchar(16) NOT NULL,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			idempotency_hash char(64) NOT NULL,
			last_idempotency_hash char(64) NOT NULL,
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
		) {$charset};";

		dbDelta( $sql );

		foreach ( self::tables() as $table ) {
			if ( ! self::table_exists( $table ) ) {
				return self::error( 'spdb_collections_schema_incomplete', 'A required File 23 metadata table was not created or verified.' );
			}
		}

		update_option( self::OPTION_KEY, self::VERSION, false );
		return true;
	}

	/** @return string[] */
	public static function tables(): array {
		return array( self::collections_table(), self::items_table(), self::links_table() );
	}

	public static function collections_table(): string {
		global $wpdb;
		return (string) $wpdb->prefix . 'spdb_collections';
	}

	public static function items_table(): string {
		global $wpdb;
		return (string) $wpdb->prefix . 'spdb_collection_items';
	}

	public static function links_table(): string {
		global $wpdb;
		return (string) $wpdb->prefix . 'spdb_knowledge_links';
	}

	private static function table_exists( string $table ): bool {
		global $wpdb;
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
		return is_string( $found ) && hash_equals( $table, $found );
	}

	private static function error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => 500 ) );
	}
}
