<?php
/** Complete table/engine/column/index verification for File 23 operational metadata. */
defined( 'ABSPATH' ) || exit;

final class SPDB_Operations_Schema_Integrity {
	/** Register a fail-closed REST health gate after normal schema upgrade has had a chance to run. */
	public static function register(): void {
		add_filter( 'rest_pre_dispatch', array( self::class, 'rest_gate' ), 3, 3 );
	}

	public static function activate(): void {
		$result = self::verify();
		if ( is_wp_error( $result ) ) {
			wp_die(
				esc_html( $result->get_error_message() ),
				esc_html__( 'Sabri Publishing Dashboard operational schema verification failed', 'sabri-publishing-dashboard' ),
				array( 'response' => 500 )
			);
		}
	}

	/** @param mixed $result @return mixed */
	public static function rest_gate( $result, WP_REST_Server $server, WP_REST_Request $request ) {
		unset( $server );
		if ( null !== $result ) { return $result; }
		$route = (string) $request->get_route();
		if ( '/spdb/v1' !== $route && 0 !== strpos( $route, '/spdb/v1/' ) ) { return null; }
		$verified = self::verify();
		return is_wp_error( $verified ) ? $verified : null;
	}

	/** @return true|WP_Error */
	public static function verify() {
		global $wpdb;
		if ( ! is_object( $wpdb ) || ! method_exists( $wpdb, 'get_var' ) || ! method_exists( $wpdb, 'get_results' ) || ! method_exists( $wpdb, 'prepare' ) || ! method_exists( $wpdb, 'esc_like' ) ) {
			return self::error( 'spdb_operations_integrity_database_unavailable', __( 'The File 23 operational schema cannot be verified.', 'sabri-publishing-dashboard' ) );
		}
		foreach ( self::requirements() as $suffix => $requirement ) {
			$table = SPDB_Operations_Schema::table( $suffix );
			if ( '' === $table || 1 !== preg_match( '/^[A-Za-z0-9_]+$/', $table ) ) {
				return self::error( 'spdb_operations_integrity_identifier_invalid', __( 'A File 23 operational table identifier is invalid.', 'sabri-publishing-dashboard' ) );
			}
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
			if ( ! is_string( $found ) || ! hash_equals( $table, $found ) ) {
				return self::error( 'spdb_operations_integrity_table_missing', __( 'A required File 23 operational table is missing.', 'sabri-publishing-dashboard' ) );
			}
			$engine = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $table ) );
			if ( 'innodb' !== strtolower( $engine ) ) {
				return self::error( 'spdb_operations_integrity_engine_invalid', __( 'A required File 23 operational table is not transaction-safe.', 'sabri-publishing-dashboard' ) );
			}
			$column_rows = $wpdb->get_results( "SHOW COLUMNS FROM `{$table}`", defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Strict plugin-owned identifier.
			$columns = is_array( $column_rows ) ? array_values( array_filter( array_map( static fn( $row ) => is_array( $row ) ? (string) ( $row['Field'] ?? '' ) : '', $column_rows ) ) ) : array();
			if ( array_diff( $requirement['columns'], $columns ) ) {
				return self::error( 'spdb_operations_integrity_column_missing', __( 'A required File 23 operational table column is missing.', 'sabri-publishing-dashboard' ) );
			}
			$index_rows = $wpdb->get_results( "SHOW INDEX FROM `{$table}`", defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Strict plugin-owned identifier.
			$indexes = is_array( $index_rows ) ? array_values( array_unique( array_filter( array_map( static fn( $row ) => is_array( $row ) ? (string) ( $row['Key_name'] ?? '' ) : '', $index_rows ) ) ) ) : array();
			if ( array_diff( $requirement['indexes'], $indexes ) ) {
				return self::error( 'spdb_operations_integrity_index_missing', __( 'A required File 23 operational table index is missing.', 'sabri-publishing-dashboard' ) );
			}
		}
		return true;
	}

	/** @return array<string,array{columns:string[],indexes:string[]}> */
	public static function requirements(): array {
		return array(
			'preferences' => array(
				'columns' => array( 'user_id','preferences_json','version','updated_at_gmt' ),
				'indexes' => array( 'PRIMARY' ),
			),
			'saved_views' => array(
				'columns' => array( 'id','view_id','owner_user_id','label','filters_json','version','created_at_gmt','updated_at_gmt' ),
				'indexes' => array( 'PRIMARY','view_id','owner_updated' ),
			),
			'tasks' => array(
				'columns' => array( 'id','task_id','owner_user_id','assignee_user_id','scope','provider_key','object_type','object_id','title','description','priority','status','due_at_gmt','version','created_by','audit_reason','created_at_gmt','updated_at_gmt','completed_at_gmt' ),
				'indexes' => array( 'PRIMARY','task_id','assignee_status','owner_status','due_at_gmt','native_ref' ),
			),
			'delegations' => array(
				'columns' => array( 'id','delegation_id','principal_user_id','delegate_user_id','scope_json','status','requires_mfa','starts_at_gmt','expires_at_gmt','version','created_by','reason','created_at_gmt','revoked_at_gmt' ),
				'indexes' => array( 'PRIMARY','delegation_id','principal_status','delegate_status','expires_at_gmt' ),
			),
			'automation_rules' => array(
				'columns' => array( 'id','rule_id','owner_user_id','rule_type','event_key','condition_json','action_json','status','version','last_run_at_gmt','next_run_at_gmt','created_by','audit_reason','created_at_gmt','updated_at_gmt' ),
				'indexes' => array( 'PRIMARY','rule_id','owner_status','event_status','next_run_at_gmt' ),
			),
			'metric_snapshots' => array(
				'columns' => array( 'id','snapshot_id','provider_key','metric_key','scope','owner_user_id','period_start_gmt','period_end_gmt','definition_hash','value_json','cohort_count','privacy_threshold','generated_at_gmt','expires_at_gmt' ),
				'indexes' => array( 'PRIMARY','snapshot_id','metric_period','expires_at_gmt' ),
			),
			'export_jobs' => array(
				'columns' => array( 'id','export_id','owner_user_id','report_key','format','scope','status','filters_json','storage_ref','file_hash','row_count','error_code','created_at_gmt','updated_at_gmt','expires_at_gmt' ),
				'indexes' => array( 'PRIMARY','export_id','owner_status','expires_at_gmt' ),
			),
			'adapter_health' => array(
				'columns' => array( 'provider_key','maturity_state','health_json','checked_at_gmt','expires_at_gmt' ),
				'indexes' => array( 'PRIMARY','expires_at_gmt' ),
			),
			'background_jobs' => array(
				'columns' => array( 'id','job_id','job_type','owner_user_id','payload_json','status','attempts','max_attempts','available_at_gmt','locked_at_gmt','lock_token','last_error_code','idempotency_key','created_at_gmt','updated_at_gmt','finished_at_gmt' ),
				'indexes' => array( 'PRIMARY','job_id','type_idempotency','status_available','owner_status' ),
			),
			'dashboard_audit' => array(
				'columns' => array( 'id','event_id','actor_user_id','event_key','object_ref_hash','payload_json','previous_hash','event_hash','created_at_gmt' ),
				'indexes' => array( 'PRIMARY','event_id','actor_created','event_created' ),
			),
		);
	}

	private static function error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => 503 ) );
	}
}
