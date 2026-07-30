<?php
/**
 * Database schema for File 23-owned cross-module metadata only.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Collections_Schema {
	public const VERSION = '1';

	public static function maybe_upgrade(): void {
		if ( self::VERSION !== (string) get_option( 'spdb_collections_schema_version', '' ) ) {
			self::install();
		}
	}

	public static function install(): void {
		global $wpdb;
		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) || ! isset( $wpdb->prefix ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = method_exists( $wpdb, 'get_charset_collate' ) ? $wpdb->get_charset_collate() : '';
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
			progress tinyint(3) unsigned NOT NULL DEFAULT 0,
			results_summary text NOT NULL,
			final_report_url text NOT NULL,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			idempotency_hash char(64) NOT NULL,
			last_idempotency_hash char(64) NOT NULL,
			created_by bigint(20) unsigned NOT NULL,
			created_at_gmt datetime NOT NULL,
			updated_at_gmt datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY collection_id (collection_id),
			UNIQUE KEY idempotency_hash (idempotency_hash),
			KEY owner_scope (owner_user_id,scope),
			KEY type_status (record_type,status)
		) {$charset};";

		$sql[] = "CREATE TABLE {$items} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			item_id varchar(64) NOT NULL,
			collection_id varchar(64) NOT NULL,
			provider_key varchar(64) NOT NULL,
			object_type varchar(64) NOT NULL,
			object_id varchar(128) NOT NULL,
			relation_type varchar(64) NOT NULL,
			position int(10) unsigned NOT NULL DEFAULT 0,
			native_destination text NOT NULL,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			idempotency_hash char(64) NOT NULL,
			last_idempotency_hash char(64) NOT NULL,
			added_by bigint(20) unsigned NOT NULL,
			created_at_gmt datetime NOT NULL,
			updated_at_gmt datetime NOT NULL,
			archived_at_gmt datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY item_id (item_id),
			UNIQUE KEY idempotency_hash (idempotency_hash),
			UNIQUE KEY collection_object_relation (collection_id,provider_key,object_type,object_id,relation_type),
			KEY collection_position (collection_id,position)
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
			native_destination text NOT NULL,
			status varchar(16) NOT NULL,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			idempotency_hash char(64) NOT NULL,
			last_idempotency_hash char(64) NOT NULL,
			created_by bigint(20) unsigned NOT NULL,
			created_at_gmt datetime NOT NULL,
			updated_at_gmt datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY link_id (link_id),
			UNIQUE KEY idempotency_hash (idempotency_hash),
			UNIQUE KEY canonical_relation (source_provider_key,source_object_type,source_object_id,target_provider_key,target_object_type,target_object_id,relation_type),
			KEY owner_scope (owner_user_id,scope),
			KEY relation_status (relation_type,status)
		) {$charset};";

		dbDelta( $sql );
		update_option( 'spdb_collections_schema_version', self::VERSION, false );
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
}
