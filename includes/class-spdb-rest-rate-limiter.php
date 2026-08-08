<?php
/**
 * Fail-closed, privacy-minimized rate limiting for every File 23 REST request.
 *
 * The final File 23 plan requires a rate-limit check on every REST/API request.
 * This guard uses one File 23-owned InnoDB counter table and an atomic
 * INSERT ... ON DUPLICATE KEY UPDATE claim. It stores no raw IP address,
 * cookie, nonce, token, URL query or request body.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_REST_Rate_Limiter {
	private const SCHEMA_VERSION = '1';
	private const VERSION_OPTION = 'spdb_rest_rate_limit_schema_version';
	private const CLEANUP_HOOK   = 'spdb_rest_rate_limit_cleanup';
	private const TABLE_SUFFIX   = 'spdb_rest_rate_limits';

	/** Register the global File 23 REST gate before mutation/session guards. */
	public static function register(): void {
		add_filter( 'rest_pre_dispatch', array( self::class, 'enforce' ), 4, 3 );
		add_action( 'init', array( self::class, 'maybe_upgrade' ), 2 );
		add_action( 'init', array( self::class, 'schedule_cleanup' ), 30 );
		add_action( self::CLEANUP_HOOK, array( self::class, 'cleanup' ) );
	}

	/** @return true|WP_Error */
	public static function activate() {
		$installed = self::install();
		if ( is_wp_error( $installed ) ) {
			return $installed;
		}
		self::schedule_cleanup();
		return true;
	}

	public static function deactivate(): void {
		if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
			wp_clear_scheduled_hook( self::CLEANUP_HOOK );
			return;
		}
		$timestamp = wp_next_scheduled( self::CLEANUP_HOOK );
		while ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CLEANUP_HOOK );
			$next = wp_next_scheduled( self::CLEANUP_HOOK );
			if ( $next === $timestamp ) {
				break;
			}
			$timestamp = $next;
		}
	}

	/** Install/repair only when the schema marker is absent or stale. */
	public static function maybe_upgrade(): void {
		if ( self::SCHEMA_VERSION !== (string) get_option( self::VERSION_OPTION, '' ) ) {
			self::install();
		}
	}

	/**
	 * Enforce one atomic claim for every `/spdb/v1` REST request.
	 *
	 * @param mixed $result Existing pre-dispatch response.
	 * @return mixed
	 */
	public static function enforce( $result, WP_REST_Server $server, WP_REST_Request $request ) {
		unset( $server );
		if ( null !== $result ) {
			return $result;
		}

		$route = (string) $request->get_route();
		if ( ! self::is_file23_route( $route ) ) {
			return null;
		}

		$method = method_exists( $request, 'get_method' ) ? (string) $request->get_method() : 'GET';
		$policy = self::policy_for( $route, $method );
		if ( null === $policy ) {
			return self::error( 'spdb_rate_limit_policy_missing', __( 'The dashboard request does not have a valid rate-limit policy.', 'sabri-publishing-dashboard' ), 503 );
		}

		if ( self::SCHEMA_VERSION !== (string) get_option( self::VERSION_OPTION, '' ) ) {
			$installed = self::install();
			if ( is_wp_error( $installed ) ) {
				return $installed;
			}
		}

		$actor_user_id = max( 0, (int) get_current_user_id() );
		$subject        = self::subject_key( $actor_user_id );
		$bucket_hash    = self::bucket_hash( $subject, (string) $policy['key'] );
		$claim          = self::claim( $bucket_hash, $actor_user_id, (string) $policy['key'], (int) $policy['limit'], (int) $policy['window'] );
		if ( is_wp_error( $claim ) ) {
			return $claim;
		}
		if ( empty( $claim['allowed'] ) ) {
			return new WP_Error(
				'spdb_rate_limit_exceeded',
				__( 'Too many dashboard requests were received. Retry after the current rate-limit window resets.', 'sabri-publishing-dashboard' ),
				array(
					'status'      => 429,
					'retry_after' => max( 1, (int) $claim['retry_after'] ),
					'policy'      => (string) $policy['key'],
				)
			);
		}
		return null;
	}

	public static function is_file23_route( string $route ): bool {
		return '/spdb/v1' === $route || 0 === strpos( $route, '/spdb/v1/' );
	}

	/**
	 * Return a deliberately small set of global actor buckets so dynamic IDs
	 * cannot be used to evade limits by rotating object references.
	 *
	 * @return array{key:string,limit:int,window:int}|null
	 */
	public static function policy_for( string $route, string $method ): ?array {
		if ( ! self::is_file23_route( $route ) ) {
			return null;
		}
		$method = strtoupper( trim( $method ) );
		$write  = in_array( $method, array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true );
		if ( ! $write ) {
			return array( 'key' => 'read', 'limit' => 240, 'window' => MINUTE_IN_SECONDS );
		}

		if ( 1 === preg_match( '#^/spdb/v1/ai-assistance$#', $route ) ) {
			return array( 'key' => 'ai', 'limit' => 20, 'window' => MINUTE_IN_SECONDS );
		}
		if ( 1 === preg_match( '#^/spdb/v1/exports$#', $route ) ) {
			return array( 'key' => 'export', 'limit' => 10, 'window' => MINUTE_IN_SECONDS );
		}
		if ( 1 === preg_match( '#^/spdb/v1/(?:settings|activation|provider-acceptance/|system-check/repair)#', $route ) ) {
			return array( 'key' => 'privileged', 'limit' => 20, 'window' => MINUTE_IN_SECONDS );
		}
		if ( 1 === preg_match( '#^/spdb/v1/(?:review|calendar)/#', $route ) ) {
			return array( 'key' => 'native-write', 'limit' => 30, 'window' => MINUTE_IN_SECONDS );
		}
		return array( 'key' => 'write', 'limit' => 60, 'window' => MINUTE_IN_SECONDS );
	}

	/** Stable lossless bucket hash; caller-controlled values are never truncated. */
	public static function bucket_hash( string $subject, string $policy_key ): string {
		return hash( 'sha256', $subject . "\n" . $policy_key );
	}

	/** @return true|WP_Error */
	private static function install() {
		global $wpdb;
		if ( ! is_object( $wpdb ) || empty( $wpdb->prefix ) ) {
			return self::error( 'spdb_rate_limit_database_unavailable', __( 'The dashboard rate-limit database is unavailable.', 'sabri-publishing-dashboard' ), 503 );
		}

		$upgrade_file = ABSPATH . 'wp-admin/includes/upgrade.php';
		if ( ! is_file( $upgrade_file ) ) {
			return self::error( 'spdb_rate_limit_upgrade_api_unavailable', __( 'The dashboard rate-limit schema cannot be verified.', 'sabri-publishing-dashboard' ), 503 );
		}
		require_once $upgrade_file;
		if ( ! function_exists( 'dbDelta' ) ) {
			return self::error( 'spdb_rate_limit_upgrade_api_unavailable', __( 'The dashboard rate-limit schema cannot be verified.', 'sabri-publishing-dashboard' ), 503 );
		}

		$table           = self::table_name();
		$charset_collate = method_exists( $wpdb, 'get_charset_collate' ) ? $wpdb->get_charset_collate() : '';
		$sql = "CREATE TABLE {$table} (
			bucket_hash char(64) NOT NULL,
			actor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			policy_key varchar(32) NOT NULL,
			request_count int(10) unsigned NOT NULL DEFAULT 0,
			window_started_at_gmt datetime NOT NULL,
			reset_at_gmt datetime NOT NULL,
			updated_at_gmt datetime NOT NULL,
			PRIMARY KEY  (bucket_hash),
			KEY actor_reset (actor_user_id, reset_at_gmt),
			KEY reset_at_gmt (reset_at_gmt)
		) ENGINE=InnoDB {$charset_collate};";
		dbDelta( $sql );

		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
		if ( ! is_string( $found ) || ! hash_equals( $table, $found ) ) {
			return self::error( 'spdb_rate_limit_table_missing', __( 'The dashboard rate-limit store is unavailable.', 'sabri-publishing-dashboard' ), 503 );
		}
		$engine = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $table ) );
		if ( 'innodb' !== strtolower( $engine ) ) {
			return self::error( 'spdb_rate_limit_table_not_transactional', __( 'The dashboard rate-limit store is not transaction-safe.', 'sabri-publishing-dashboard' ), 503 );
		}

		update_option( self::VERSION_OPTION, self::SCHEMA_VERSION, false );
		if ( self::SCHEMA_VERSION !== (string) get_option( self::VERSION_OPTION, '' ) ) {
			return self::error( 'spdb_rate_limit_schema_marker_failed', __( 'The dashboard rate-limit schema state could not be recorded.', 'sabri-publishing-dashboard' ), 503 );
		}
		return true;
	}

	/**
	 * Atomically increment one fixed-window bucket and return the observed state.
	 *
	 * @return array{allowed:bool,retry_after:int,count:int}|WP_Error
	 */
	private static function claim( string $bucket_hash, int $actor_user_id, string $policy_key, int $limit, int $window ) {
		global $wpdb;
		$table = self::table_name();
		if ( '' === $table || $limit < 1 || $window < 1 ) {
			return self::error( 'spdb_rate_limit_invalid_state', __( 'The dashboard rate-limit policy is invalid.', 'sabri-publishing-dashboard' ), 503 );
		}

		$now        = time();
		$started    = gmdate( 'Y-m-d H:i:s', $now );
		$reset      = gmdate( 'Y-m-d H:i:s', $now + $window );
		$sql = $wpdb->prepare(
			"INSERT INTO {$table} (bucket_hash, actor_user_id, policy_key, request_count, window_started_at_gmt, reset_at_gmt, updated_at_gmt)
			 VALUES (%s, %d, %s, 1, %s, %s, %s)
			 ON DUPLICATE KEY UPDATE
			 actor_user_id = VALUES(actor_user_id),
			 policy_key = VALUES(policy_key),
			 request_count = IF(reset_at_gmt <= UTC_TIMESTAMP(), 1, request_count + 1),
			 window_started_at_gmt = IF(reset_at_gmt <= UTC_TIMESTAMP(), VALUES(window_started_at_gmt), window_started_at_gmt),
			 reset_at_gmt = IF(reset_at_gmt <= UTC_TIMESTAMP(), VALUES(reset_at_gmt), reset_at_gmt),
			 updated_at_gmt = VALUES(updated_at_gmt)",
			$bucket_hash,
			$actor_user_id,
			$policy_key,
			$started,
			$reset,
			$started
		);
		if ( false === $wpdb->query( $sql ) ) {
			return self::error( 'spdb_rate_limit_persistence_failed', __( 'The dashboard request cannot proceed because rate-limit evidence could not be persisted.', 'sabri-publishing-dashboard' ), 503 );
		}

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT request_count, reset_at_gmt FROM {$table} WHERE bucket_hash = %s LIMIT 1", $bucket_hash ),
			defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A'
		);
		if ( ! is_array( $row ) || ! isset( $row['request_count'], $row['reset_at_gmt'] ) ) {
			return self::error( 'spdb_rate_limit_readback_failed', __( 'The dashboard request cannot proceed because rate-limit evidence could not be verified.', 'sabri-publishing-dashboard' ), 503 );
		}

		$count       = max( 0, (int) $row['request_count'] );
		$reset_epoch = strtotime( (string) $row['reset_at_gmt'] . ' UTC' );
		if ( false === $reset_epoch ) {
			return self::error( 'spdb_rate_limit_readback_failed', __( 'The dashboard request cannot proceed because rate-limit evidence is invalid.', 'sabri-publishing-dashboard' ), 503 );
		}
		return array(
			'allowed'     => $count <= $limit,
			'retry_after' => max( 1, $reset_epoch - time() ),
			'count'       => $count,
		);
	}

	/** Pseudonymous actor/network key; never stores or trusts forwarding headers. */
	private static function subject_key( int $actor_user_id ): string {
		if ( $actor_user_id > 0 ) {
			return 'user:' . $actor_user_id;
		}
		$remote = isset( $_SERVER['REMOTE_ADDR'] ) && is_scalar( $_SERVER['REMOTE_ADDR'] ) ? trim( (string) $_SERVER['REMOTE_ADDR'] ) : '';
		if ( '' === $remote || false === filter_var( $remote, FILTER_VALIDATE_IP ) ) {
			$remote = 'unknown';
		}
		$salt = function_exists( 'wp_salt' ) ? wp_salt( 'nonce' ) : ( defined( 'AUTH_SALT' ) ? (string) AUTH_SALT : 'spdb-test-only-fallback' );
		return 'anon:' . hash_hmac( 'sha256', $remote, $salt );
	}

	public static function schedule_cleanup(): void {
		if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK );
		}
	}

	/** Remove only expired File 23 rate-limit counters in bounded batches. */
	public static function cleanup(): void {
		global $wpdb;
		$table = self::table_name();
		if ( '' === $table || ! is_object( $wpdb ) ) {
			return;
		}
		for ( $batch = 0; $batch < 10; ++$batch ) {
			$deleted = $wpdb->query( "DELETE FROM {$table} WHERE reset_at_gmt < (UTC_TIMESTAMP() - INTERVAL 1 DAY) LIMIT 1000" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Fixed plugin-owned table/query; no input.
			if ( ! is_int( $deleted ) || $deleted < 1000 ) {
				break;
			}
		}
	}

	private static function table_name(): string {
		global $wpdb;
		return is_object( $wpdb ) && isset( $wpdb->prefix ) ? (string) $wpdb->prefix . self::TABLE_SUFFIX : '';
	}

	private static function error( string $code, string $message, int $status ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
