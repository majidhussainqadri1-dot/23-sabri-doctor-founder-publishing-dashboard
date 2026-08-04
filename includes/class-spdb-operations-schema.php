<?php
/**
 * File 23-owned operational metadata schema.
 *
 * These tables deliberately store only dashboard preferences, canonical object
 * pointers, bounded aggregates, jobs, and audit evidence. Native publication,
 * review, source, media, comment, notification-delivery, and clinical records
 * remain with their canonical owners.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Operations_Schema {
	public const VERSION = '1.1.0';

	private const OPTION_KEY = 'spdb_operations_schema_version';

	/**
	 * Install or upgrade the schema when needed.
	 *
	 * @return true|WP_Error
	 */
	public static function maybe_upgrade() {
		if ( self::VERSION === (string) get_option( self::OPTION_KEY, '' ) ) {
			$verified = self::verify();
			if ( true === $verified ) {
				return true;
			}
		}

		return self::install();
	}

	/**
	 * Create all File 23-owned operational tables idempotently.
	 *
	 * @return true|WP_Error
	 */
	public static function install() {
		global $wpdb;

		if ( ! self::database_available() ) {
			return self::error(
				'spdb_operations_database_unavailable',
				__( 'The WordPress database service is unavailable.', 'sabri-publishing-dashboard' )
			);
		}

		$upgrade_file = ABSPATH . 'wp-admin/includes/upgrade.php';
		if ( ! is_file( $upgrade_file ) ) {
			return self::error(
				'spdb_operations_upgrade_api_unavailable',
				__( 'The WordPress database upgrade API is unavailable.', 'sabri-publishing-dashboard' )
			);
		}

		require_once $upgrade_file;
		if ( ! function_exists( 'dbDelta' ) ) {
			return self::error(
				'spdb_operations_upgrade_api_unavailable',
				__( 'The WordPress database upgrade API is unavailable.', 'sabri-publishing-dashboard' )
			);
		}

		$charset_collate = method_exists( $wpdb, 'get_charset_collate' ) ? $wpdb->get_charset_collate() : '';
		$tables          = self::table_names();
		$sql             = array();

		$sql[] = "CREATE TABLE {$tables['preferences']} (
			user_id bigint(20) unsigned NOT NULL,
			preferences_json longtext NOT NULL,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			updated_at_gmt datetime NOT NULL,
			PRIMARY KEY  (user_id)
		) ENGINE=InnoDB {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['saved_views']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			view_id varchar(64) NOT NULL,
			owner_user_id bigint(20) unsigned NOT NULL,
			label varchar(80) NOT NULL,
			filters_json longtext NOT NULL,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			created_at_gmt datetime NOT NULL,
			updated_at_gmt datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY view_id (view_id),
			KEY owner_updated (owner_user_id, updated_at_gmt)
		) ENGINE=InnoDB {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['tasks']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			task_id varchar(64) NOT NULL,
			owner_user_id bigint(20) unsigned NOT NULL,
			assignee_user_id bigint(20) unsigned NOT NULL,
			scope varchar(16) NOT NULL,
			provider_key varchar(64) NOT NULL,
			object_type varchar(64) NOT NULL,
			object_id varchar(128) NOT NULL,
			title varchar(200) NOT NULL,
			description text NOT NULL,
			priority varchar(16) NOT NULL,
			status varchar(24) NOT NULL,
			due_at_gmt datetime NULL,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			created_by bigint(20) unsigned NOT NULL,
			audit_reason varchar(500) NOT NULL,
			created_at_gmt datetime NOT NULL,
			updated_at_gmt datetime NOT NULL,
			completed_at_gmt datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY task_id (task_id),
			KEY assignee_status (assignee_user_id, status),
			KEY owner_status (owner_user_id, status),
			KEY due_at_gmt (due_at_gmt),
			KEY native_ref (provider_key, object_type, object_id)
		) ENGINE=InnoDB {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['delegations']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			delegation_id varchar(64) NOT NULL,
			principal_user_id bigint(20) unsigned NOT NULL,
			delegate_user_id bigint(20) unsigned NOT NULL,
			scope_json longtext NOT NULL,
			status varchar(16) NOT NULL,
			requires_mfa tinyint(1) unsigned NOT NULL DEFAULT 1,
			starts_at_gmt datetime NOT NULL,
			expires_at_gmt datetime NOT NULL,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			created_by bigint(20) unsigned NOT NULL,
			reason varchar(500) NOT NULL,
			created_at_gmt datetime NOT NULL,
			revoked_at_gmt datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY delegation_id (delegation_id),
			KEY principal_status (principal_user_id, status),
			KEY delegate_status (delegate_user_id, status),
			KEY expires_at_gmt (expires_at_gmt)
		) ENGINE=InnoDB {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['automation_rules']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			rule_id varchar(64) NOT NULL,
			owner_user_id bigint(20) unsigned NOT NULL,
			rule_type varchar(64) NOT NULL,
			event_key varchar(96) NOT NULL,
			condition_json longtext NOT NULL,
			action_json longtext NOT NULL,
			status varchar(16) NOT NULL,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			last_run_at_gmt datetime NULL,
			next_run_at_gmt datetime NULL,
			created_by bigint(20) unsigned NOT NULL,
			audit_reason varchar(500) NOT NULL,
			created_at_gmt datetime NOT NULL,
			updated_at_gmt datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY rule_id (rule_id),
			KEY owner_status (owner_user_id, status),
			KEY event_status (event_key, status),
			KEY next_run_at_gmt (next_run_at_gmt)
		) ENGINE=InnoDB {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['metric_snapshots']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			snapshot_id varchar(64) NOT NULL,
			provider_key varchar(64) NOT NULL,
			metric_key varchar(96) NOT NULL,
			scope varchar(16) NOT NULL,
			owner_user_id bigint(20) unsigned NOT NULL,
			period_start_gmt datetime NOT NULL,
			period_end_gmt datetime NOT NULL,
			definition_hash char(64) NOT NULL,
			value_json longtext NOT NULL,
			cohort_count bigint(20) unsigned NOT NULL DEFAULT 0,
			privacy_threshold int(10) unsigned NOT NULL DEFAULT 5,
			generated_at_gmt datetime NOT NULL,
			expires_at_gmt datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY snapshot_id (snapshot_id),
			UNIQUE KEY metric_period (provider_key, metric_key, scope, owner_user_id, period_start_gmt, period_end_gmt),
			KEY expires_at_gmt (expires_at_gmt)
		) ENGINE=InnoDB {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['export_jobs']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			export_id varchar(64) NOT NULL,
			owner_user_id bigint(20) unsigned NOT NULL,
			report_key varchar(96) NOT NULL,
			format varchar(16) NOT NULL,
			scope varchar(16) NOT NULL,
			status varchar(24) NOT NULL,
			filters_json longtext NOT NULL,
			storage_ref varchar(255) NOT NULL,
			file_hash char(64) NOT NULL,
			row_count bigint(20) unsigned NOT NULL DEFAULT 0,
			error_code varchar(96) NOT NULL,
			created_at_gmt datetime NOT NULL,
			updated_at_gmt datetime NOT NULL,
			expires_at_gmt datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY export_id (export_id),
			KEY owner_status (owner_user_id, status),
			KEY expires_at_gmt (expires_at_gmt)
		) ENGINE=InnoDB {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['adapter_health']} (
			provider_key varchar(64) NOT NULL,
			maturity_state varchar(32) NOT NULL,
			health_json longtext NOT NULL,
			checked_at_gmt datetime NOT NULL,
			expires_at_gmt datetime NOT NULL,
			PRIMARY KEY  (provider_key),
			KEY expires_at_gmt (expires_at_gmt)
		) ENGINE=InnoDB {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['background_jobs']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			job_id varchar(64) NOT NULL,
			job_type varchar(64) NOT NULL,
			owner_user_id bigint(20) unsigned NOT NULL,
			payload_json longtext NOT NULL,
			status varchar(24) NOT NULL,
			attempts int(10) unsigned NOT NULL DEFAULT 0,
			max_attempts int(10) unsigned NOT NULL DEFAULT 5,
			available_at_gmt datetime NOT NULL,
			locked_at_gmt datetime NULL,
			lock_token char(64) NOT NULL,
			last_error_code varchar(96) NOT NULL,
			idempotency_key varchar(128) NOT NULL,
			created_at_gmt datetime NOT NULL,
			updated_at_gmt datetime NOT NULL,
			finished_at_gmt datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY job_id (job_id),
			UNIQUE KEY type_idempotency (job_type, idempotency_key),
			KEY status_available (status, available_at_gmt),
			KEY owner_status (owner_user_id, status)
		) ENGINE=InnoDB {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['dashboard_audit']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_id varchar(64) NOT NULL,
			actor_user_id bigint(20) unsigned NOT NULL,
			event_key varchar(96) NOT NULL,
			object_ref_hash char(64) NOT NULL,
			payload_json longtext NOT NULL,
			previous_hash char(64) NOT NULL,
			event_hash char(64) NOT NULL,
			created_at_gmt datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY event_id (event_id),
			KEY actor_created (actor_user_id, created_at_gmt),
			KEY event_created (event_key, created_at_gmt)
		) ENGINE=InnoDB {$charset_collate};";

		dbDelta( $sql );

		$converted = self::ensure_transactional_tables();
		if ( is_wp_error( $converted ) ) {
			return $converted;
		}

		$verified = self::verify();
		if ( is_wp_error( $verified ) ) {
			return $verified;
		}

		update_option( self::OPTION_KEY, self::VERSION, false );
		if ( self::VERSION !== (string) get_option( self::OPTION_KEY, '' ) ) {
			return self::error(
				'spdb_operations_schema_version_failed',
				__( 'The operational schema version could not be recorded.', 'sabri-publishing-dashboard' )
			);
		}

		return true;
	}

	/**
	 * Verify required tables without modifying data.
	 *
	 * @return true|WP_Error
	 */
	public static function verify() {
		global $wpdb;
		if ( ! self::database_available() ) {
			return self::error(
				'spdb_operations_database_unavailable',
				__( 'The WordPress database service is unavailable.', 'sabri-publishing-dashboard' )
			);
		}

		foreach ( self::table_names() as $table ) {
			$found = $wpdb->get_var(
				$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) )
			);
			if ( ! is_string( $found ) || ! hash_equals( $table, $found ) ) {
				return self::error(
					'spdb_operations_table_missing',
					__( 'A required File 23 operational metadata table is missing.', 'sabri-publishing-dashboard' )
				);
			}
			$engine = self::table_engine( $table );
			if ( 'innodb' !== strtolower( $engine ) ) {
				return self::error(
					'spdb_operations_table_not_transactional',
					__( 'A required File 23 operational metadata table is not using InnoDB.', 'sabri-publishing-dashboard' )
				);
			}
		}

		return true;
	}

	/** @return string[] */
	public static function tables(): array {
		return array_values( self::table_names() );
	}

	public static function table( string $suffix ): string {
		$tables = self::table_names();
		return isset( $tables[ $suffix ] ) ? $tables[ $suffix ] : '';
	}

	/** @return array<string,string> */
	private static function table_names(): array {
		global $wpdb;
		$prefix = is_object( $wpdb ) && isset( $wpdb->prefix ) ? (string) $wpdb->prefix : '';

		return array(
			'preferences'      => $prefix . 'spdb_preferences',
			'saved_views'      => $prefix . 'spdb_saved_views',
			'tasks'            => $prefix . 'spdb_tasks',
			'delegations'      => $prefix . 'spdb_delegations',
			'automation_rules' => $prefix . 'spdb_automation_rules',
			'metric_snapshots' => $prefix . 'spdb_metric_snapshots',
			'export_jobs'      => $prefix . 'spdb_export_jobs',
			'adapter_health'   => $prefix . 'spdb_adapter_health',
			'background_jobs'  => $prefix . 'spdb_background_jobs',
			'dashboard_audit'  => $prefix . 'spdb_dashboard_audit',
		);
	}

	/** @return true|WP_Error */
	private static function ensure_transactional_tables() {
		global $wpdb;
		if ( ! method_exists( $wpdb, 'query' ) ) { return self::error( 'spdb_operations_database_unavailable', __( 'The WordPress database service is unavailable.', 'sabri-publishing-dashboard' ) ); }
		foreach ( self::table_names() as $table ) {
			if ( 1 !== preg_match( '/^[A-Za-z0-9_]+$/', $table ) ) {
				return self::error( 'spdb_operations_table_identifier_invalid', __( 'An operational table identifier is invalid.', 'sabri-publishing-dashboard' ) );
			}
			$engine = self::table_engine( $table );
			if ( '' !== $engine && 'innodb' !== strtolower( $engine ) ) {
				$result = $wpdb->query( "ALTER TABLE `{$table}` ENGINE=InnoDB" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Identifier is strictly allowlisted.
				if ( false === $result ) {
					return self::error( 'spdb_operations_engine_upgrade_failed', __( 'A File 23 operational table could not be upgraded to InnoDB.', 'sabri-publishing-dashboard' ) );
				}
			}
		}
		return true;
	}

	private static function table_engine( string $table ): string {
		global $wpdb;
		if ( 1 !== preg_match( '/^[A-Za-z0-9_]+$/', $table ) || ! method_exists( $wpdb, 'get_row' ) ) {
			return '';
		}
		$row = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS LIKE %s', $table ), defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Identifier is strictly allowlisted.
		return is_array( $row ) ? (string) ( $row['Engine'] ?? '' ) : '';
	}

	private static function database_available(): bool {
		global $wpdb;
		return is_object( $wpdb )
			&& isset( $wpdb->prefix )
			&& method_exists( $wpdb, 'get_var' )
			&& method_exists( $wpdb, 'get_row' )
			&& method_exists( $wpdb, 'prepare' )
			&& method_exists( $wpdb, 'esc_like' );
	}

	private static function error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => 500 ) );
	}
}
