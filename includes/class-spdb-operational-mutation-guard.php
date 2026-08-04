<?php
/**
 * Cross-cutting integrity guard for File 23-owned REST mutations.
 *
 * Enforces REST nonce, same-origin browser requests, request idempotency,
 * bounded replay receipts, append-only audit evidence and transactional
 * commit/rollback for local File 23 writes.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Operational_Mutation_Guard {
	private const SCHEMA_VERSION = '1.0.0';
	private const RECEIPT_TTL    = 7 * DAY_IN_SECONDS;
	private const PENDING_TTL    = 5 * MINUTE_IN_SECONDS;
	private const RESPONSE_LIMIT = 65535;

	/** @var array<int,array<string,mixed>> */
	private static array $request_contexts = array();

	public static function register(): void {
		add_filter( 'rest_pre_dispatch', array( self::class, 'before_dispatch' ), 5, 3 );
		add_filter( 'rest_post_dispatch', array( self::class, 'after_dispatch' ), 999, 3 );
		add_action( 'init', array( self::class, 'schedule_cleanup' ) );
		add_action( 'spdb_mutation_guard_cleanup', array( self::class, 'cleanup' ) );
	}

	public static function activate(): void {
		self::install_schema();
		self::schedule_cleanup();
	}

	public static function deactivate(): void {
		$timestamp = wp_next_scheduled( 'spdb_mutation_guard_cleanup' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'spdb_mutation_guard_cleanup' );
		}
	}

	/**
	 * @param mixed $result Existing pre-dispatch response.
	 * @return mixed
	 */
	public static function before_dispatch( $result, WP_REST_Server $server, WP_REST_Request $request ) {
		unset( $server );
		if ( null !== $result ) {
			return $result;
		}

		$policy = self::route_policy( (string) $request->get_route(), (string) $request->get_method() );
		if ( null === $policy ) {
			return null;
		}

		$user_id = get_current_user_id();
		if ( $user_id < 1 ) {
			return self::error( 'spdb_mutation_auth_required', __( 'Authentication is required for this dashboard action.', 'sabri-publishing-dashboard' ), 401 );
		}

		if ( ! self::valid_nonce( $request ) ) {
			return self::error( 'spdb_mutation_nonce_invalid', __( 'A valid REST request nonce is required.', 'sabri-publishing-dashboard' ), 403 );
		}

		if ( ! self::same_origin_request( $request ) ) {
			return self::error( 'spdb_mutation_origin_invalid', __( 'The request origin is not authorized.', 'sabri-publishing-dashboard' ), 403 );
		}

		$payload = self::request_payload( $request );
		$key     = self::request_idempotency_key( $request, $payload );
		if ( ! self::valid_idempotency_key( $key ) ) {
			return self::error( 'spdb_mutation_idempotency_required', __( 'A valid idempotency key is required for this dashboard action.', 'sabri-publishing-dashboard' ), 422 );
		}

		$reason = self::audit_reason( $payload, $policy );
		if ( is_wp_error( $reason ) ) {
			return $reason;
		}

		self::install_schema();

		global $wpdb;
		$transactional = ! empty( $policy['transactional'] );
		$transaction   = false;
		if ( $transactional ) {
			$transaction = false !== $wpdb->query( 'START TRANSACTION' );
			if ( ! $transaction ) {
				return self::error( 'spdb_mutation_transaction_unavailable', __( 'The dashboard action could not open a safe transaction.', 'sabri-publishing-dashboard' ), 503 );
			}
		}

		$fingerprint = self::fingerprint_payload( $payload );
		$claim       = self::claim_receipt(
			$user_id,
			(string) $request->get_route(),
			strtoupper( (string) $request->get_method() ),
			$key,
			$fingerprint,
			(string) $reason
		);

		if ( is_wp_error( $claim ) || $claim instanceof WP_REST_Response ) {
			if ( $transaction ) {
				$wpdb->query( 'ROLLBACK' );
			}
			return $claim;
		}

		self::$request_contexts[ spl_object_id( $request ) ] = array(
			'receipt_id'    => (string) $claim,
			'actor_user_id' => $user_id,
			'route'         => (string) $request->get_route(),
			'method'        => strtoupper( (string) $request->get_method() ),
			'reason'        => (string) $reason,
			'transaction'   => $transaction,
		);

		return null;
	}

	/**
	 * @param mixed $response REST response.
	 * @return mixed
	 */
	public static function after_dispatch( $response, WP_REST_Server $server, WP_REST_Request $request ) {
		unset( $server );
		$key = spl_object_id( $request );
		if ( ! isset( self::$request_contexts[ $key ] ) ) {
			return $response;
		}

		$context = self::$request_contexts[ $key ];
		unset( self::$request_contexts[ $key ] );

		global $wpdb;
		$transaction = ! empty( $context['transaction'] );
		$normalized  = rest_ensure_response( $response );
		$status      = method_exists( $normalized, 'get_status' ) ? (int) $normalized->get_status() : 200;
		$data        = method_exists( $normalized, 'get_data' ) ? $normalized->get_data() : null;

		if ( $status >= 500 ) {
			if ( $transaction ) {
				$wpdb->query( 'ROLLBACK' );
			} else {
				self::complete_receipt( $context, $status, $data, 'failed' );
			}
			return $response;
		}

		$completed = self::complete_receipt(
			$context,
			$status,
			$data,
			$status >= 400 ? 'denied' : 'completed'
		);
		if ( is_wp_error( $completed ) ) {
			if ( $transaction ) {
				$wpdb->query( 'ROLLBACK' );
			}
			return new WP_REST_Response(
				array(
					'code'    => 'spdb_mutation_audit_commit_failed',
					'message' => __( 'The dashboard action was not committed because its audit evidence could not be secured.', 'sabri-publishing-dashboard' ),
				),
				500
			);
		}

		if ( $transaction && false === $wpdb->query( 'COMMIT' ) ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_REST_Response(
				array(
					'code'    => 'spdb_mutation_commit_failed',
					'message' => __( 'The dashboard action could not be committed safely.', 'sabri-publishing-dashboard' ),
				),
				500
			);
		}

		$normalized->header( 'Cache-Control', 'private, no-store, max-age=0' );
		$normalized->header( 'X-SPDB-Idempotent', 'recorded' );
		return $normalized;
	}

	/**
	 * Return a policy only for File 23-owned mutating operational routes.
	 *
	 * @return array{transactional:bool,require_reason:bool}|null
	 */
	public static function route_policy( string $route, string $method ): ?array {
		$method = strtoupper( $method );
		if ( ! in_array( $method, array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) ) {
			return null;
		}

		$policies = array(
			'#^/spdb/v1/tasks(?:/task_[a-z0-9]{32})?$#'                   => array( 'transactional' => true, 'require_reason' => false ),
			'#^/spdb/v1/delegations(?:/delegation_[a-z0-9]{32}/revoke)?$#' => array( 'transactional' => true, 'require_reason' => true ),
			'#^/spdb/v1/automation-rules(?:/rule_[a-z0-9]{32}/status)?$#'  => array( 'transactional' => true, 'require_reason' => false ),
			'#^/spdb/v1/exports$#'                                         => array( 'transactional' => true, 'require_reason' => false ),
			'#^/spdb/v1/ai-assistance$#'                                   => array( 'transactional' => false, 'require_reason' => false ),
			'#^/spdb/v1/preferences$#'                                     => array( 'transactional' => true, 'require_reason' => false ),
			'#^/spdb/v1/settings$#'                                        => array( 'transactional' => true, 'require_reason' => true ),
			'#^/spdb/v1/system-check/repair$#'                             => array( 'transactional' => true, 'require_reason' => true ),
			'#^/spdb/v1/activation$#'                                      => array( 'transactional' => true, 'require_reason' => true ),
		);

		foreach ( $policies as $pattern => $policy ) {
			if ( 1 === preg_match( $pattern, $route ) ) {
				return $policy;
			}
		}
		return null;
	}

	public static function valid_idempotency_key( string $key ): bool {
		$length = strlen( $key );
		return $length >= 16 && $length <= 128 && 1 === preg_match( '/^[A-Za-z0-9][A-Za-z0-9._:-]+$/', $key );
	}

	/** @param array<string,mixed> $payload */
	public static function fingerprint_payload( array $payload ): string {
		unset( $payload['_wpnonce'], $payload['idempotency_key'] );
		$normalized = self::normalize_value( $payload );
		$json       = wp_json_encode( $normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		return hash( 'sha256', is_string( $json ) ? $json : '{}' );
	}

	public static function same_origin_value( string $candidate, string $home ): bool {
		$candidate_parts = wp_parse_url( $candidate );
		$home_parts      = wp_parse_url( $home );
		if ( ! is_array( $candidate_parts ) || ! is_array( $home_parts ) ) {
			return false;
		}
		$candidate_scheme = strtolower( (string) ( $candidate_parts['scheme'] ?? '' ) );
		$home_scheme      = strtolower( (string) ( $home_parts['scheme'] ?? '' ) );
		$candidate_host   = strtolower( (string) ( $candidate_parts['host'] ?? '' ) );
		$home_host        = strtolower( (string) ( $home_parts['host'] ?? '' ) );
		if ( ! in_array( $candidate_scheme, array( 'http', 'https' ), true ) || $candidate_scheme !== $home_scheme || '' === $candidate_host || $candidate_host !== $home_host ) {
			return false;
		}
		$candidate_port = isset( $candidate_parts['port'] ) ? (int) $candidate_parts['port'] : ( 'https' === $candidate_scheme ? 443 : 80 );
		$home_port      = isset( $home_parts['port'] ) ? (int) $home_parts['port'] : ( 'https' === $home_scheme ? 443 : 80 );
		return $candidate_port === $home_port;
	}

	public static function schedule_cleanup(): void {
		if ( ! wp_next_scheduled( 'spdb_mutation_guard_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'spdb_mutation_guard_cleanup' );
		}
	}

	public static function cleanup(): void {
		global $wpdb;
		$receipts = self::receipt_table();
		$audit    = self::audit_table();
		$now      = current_time( 'mysql', true );
		$days     = max( 365, (int) apply_filters( 'spdb_mutation_audit_retention_days', 2555 ) );
		$cutoff   = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$receipts} WHERE expires_at_gmt < %s LIMIT 1000", $now ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$audit} WHERE created_at_gmt < %s LIMIT 1000", $cutoff ) );
	}

	private static function install_schema(): void {
		if ( get_option( 'spdb_mutation_guard_schema_version' ) === self::SCHEMA_VERSION ) {
			return;
		}
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset  = $wpdb->get_charset_collate();
		$receipts = self::receipt_table();
		$audit    = self::audit_table();

		dbDelta(
			"CREATE TABLE {$receipts} (
				receipt_id char(37) NOT NULL,
				actor_user_id bigint(20) unsigned NOT NULL,
				route_hash char(64) NOT NULL,
				route varchar(191) NOT NULL,
				method varchar(10) NOT NULL,
				idempotency_hash char(64) NOT NULL,
				payload_hash char(64) NOT NULL,
				state varchar(20) NOT NULL,
				response_status smallint(5) unsigned NOT NULL DEFAULT 0,
				response_json longtext NULL,
				audit_reason varchar(500) NOT NULL,
				created_at_gmt datetime NOT NULL,
				updated_at_gmt datetime NOT NULL,
				expires_at_gmt datetime NOT NULL,
				PRIMARY KEY  (receipt_id),
				UNIQUE KEY actor_route_idempotency (actor_user_id,route_hash,method,idempotency_hash),
				KEY expires_at_gmt (expires_at_gmt)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$audit} (
				audit_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				receipt_id char(37) NOT NULL,
				actor_user_id bigint(20) unsigned NOT NULL,
				event_key varchar(64) NOT NULL,
				route_hash char(64) NOT NULL,
				method varchar(10) NOT NULL,
				outcome varchar(20) NOT NULL,
				reason varchar(500) NOT NULL,
				details_hash char(64) NOT NULL,
				created_at_gmt datetime NOT NULL,
				PRIMARY KEY  (audit_id),
				KEY receipt_id (receipt_id),
				KEY created_at_gmt (created_at_gmt)
			) {$charset};"
		);

		update_option( 'spdb_mutation_guard_schema_version', self::SCHEMA_VERSION, false );
	}

	/** @return string|WP_Error|WP_REST_Response */
	private static function claim_receipt( int $user_id, string $route, string $method, string $idempotency_key, string $payload_hash, string $reason ) {
		global $wpdb;
		$table      = self::receipt_table();
		$route_hash = hash( 'sha256', $route );
		$key_hash   = hash( 'sha256', $idempotency_key );
		$existing   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE actor_user_id = %d AND route_hash = %s AND method = %s AND idempotency_hash = %s",
				$user_id,
				$route_hash,
				$method,
				$key_hash
			),
			ARRAY_A
		);

		if ( is_array( $existing ) ) {
			return self::existing_receipt_result( $existing, $payload_hash );
		}

		$receipt_id = wp_generate_uuid4();
		$now        = current_time( 'mysql', true );
		$inserted   = $wpdb->insert(
			$table,
			array(
				'receipt_id'       => $receipt_id,
				'actor_user_id'    => $user_id,
				'route_hash'       => $route_hash,
				'route'            => substr( $route, 0, 191 ),
				'method'           => $method,
				'idempotency_hash' => $key_hash,
				'payload_hash'     => $payload_hash,
				'state'            => 'pending',
				'response_status'  => 0,
				'response_json'    => null,
				'audit_reason'     => $reason,
				'created_at_gmt'   => $now,
				'updated_at_gmt'   => $now,
				'expires_at_gmt'   => gmdate( 'Y-m-d H:i:s', time() + self::RECEIPT_TTL ),
			)
		);
		if ( false === $inserted ) {
			$existing = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE actor_user_id = %d AND route_hash = %s AND method = %s AND idempotency_hash = %s",
					$user_id,
					$route_hash,
					$method,
					$key_hash
				),
				ARRAY_A
			);
			return is_array( $existing )
				? self::existing_receipt_result( $existing, $payload_hash )
				: self::error( 'spdb_mutation_receipt_failed', __( 'The mutation receipt could not be secured.', 'sabri-publishing-dashboard' ), 503 );
		}

		$audit = self::append_audit( $receipt_id, $user_id, 'mutation_requested', $route_hash, $method, 'pending', $reason, $payload_hash );
		return is_wp_error( $audit ) ? $audit : $receipt_id;
	}

	/** @return WP_Error|WP_REST_Response */
	private static function existing_receipt_result( array $row, string $payload_hash ) {
		if ( ! hash_equals( (string) ( $row['payload_hash'] ?? '' ), $payload_hash ) ) {
			return self::error( 'spdb_mutation_idempotency_conflict', __( 'The idempotency key was already used with a different request.', 'sabri-publishing-dashboard' ), 409 );
		}

		$state = (string) ( $row['state'] ?? '' );
		if ( in_array( $state, array( 'completed', 'denied', 'failed' ), true ) ) {
			$data = json_decode( (string) ( $row['response_json'] ?? '' ), true );
			$response = new WP_REST_Response( null === $data ? array() : $data, max( 200, (int) ( $row['response_status'] ?? 200 ) ) );
			$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
			$response->header( 'X-SPDB-Idempotent', 'replayed' );
			return $response;
		}

		$updated = strtotime( (string) ( $row['updated_at_gmt'] ?? '' ) );
		if ( $updated && $updated > time() - self::PENDING_TTL ) {
			return self::error( 'spdb_mutation_in_progress', __( 'The same dashboard action is already being processed.', 'sabri-publishing-dashboard' ), 409 );
		}
		return self::error( 'spdb_mutation_stale_receipt', __( 'A stale mutation receipt requires reconciliation before retry.', 'sabri-publishing-dashboard' ), 409 );
	}

	/** @return true|WP_Error */
	private static function complete_receipt( array $context, int $status, $data, string $outcome ) {
		global $wpdb;
		$json = wp_json_encode( self::sanitize_response_data( $data ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( ! is_string( $json ) || strlen( $json ) > self::RESPONSE_LIMIT ) {
			return self::error( 'spdb_mutation_response_unrecordable', __( 'The dashboard response could not be recorded safely for replay.', 'sabri-publishing-dashboard' ), 500 );
		}

		$receipt_id = (string) $context['receipt_id'];
		$route_hash = hash( 'sha256', (string) $context['route'] );
		$details     = hash( 'sha256', $json );
		$audit       = self::append_audit(
			$receipt_id,
			(int) $context['actor_user_id'],
			'mutation_' . $outcome,
			$route_hash,
			(string) $context['method'],
			$outcome,
			(string) $context['reason'],
			$details
		);
		if ( is_wp_error( $audit ) ) {
			return $audit;
		}

		$updated = $wpdb->update(
			self::receipt_table(),
			array(
				'state'           => $outcome,
				'response_status' => min( 599, max( 100, $status ) ),
				'response_json'   => $json,
				'updated_at_gmt'  => current_time( 'mysql', true ),
			),
			array( 'receipt_id' => $receipt_id, 'state' => 'pending' ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%s', '%s' )
		);
		return false === $updated || 1 !== (int) $wpdb->rows_affected
			? self::error( 'spdb_mutation_receipt_update_failed', __( 'The mutation receipt could not be finalized.', 'sabri-publishing-dashboard' ), 500 )
			: true;
	}

	/** @return true|WP_Error */
	private static function append_audit( string $receipt_id, int $user_id, string $event_key, string $route_hash, string $method, string $outcome, string $reason, string $details_hash ) {
		global $wpdb;
		$inserted = $wpdb->insert(
			self::audit_table(),
			array(
				'receipt_id'     => $receipt_id,
				'actor_user_id'  => $user_id,
				'event_key'      => substr( sanitize_key( $event_key ), 0, 64 ),
				'route_hash'     => $route_hash,
				'method'         => substr( strtoupper( $method ), 0, 10 ),
				'outcome'        => substr( sanitize_key( $outcome ), 0, 20 ),
				'reason'         => substr( sanitize_text_field( $reason ), 0, 500 ),
				'details_hash'   => $details_hash,
				'created_at_gmt' => current_time( 'mysql', true ),
			)
		);
		return false === $inserted
			? self::error( 'spdb_mutation_audit_write_failed', __( 'The mutation audit record could not be secured.', 'sabri-publishing-dashboard' ), 500 )
			: true;
	}

	private static function valid_nonce( WP_REST_Request $request ): bool {
		$nonce = (string) $request->get_header( 'X-WP-Nonce' );
		if ( '' === $nonce ) {
			$params = self::request_payload( $request );
			$nonce  = isset( $params['_wpnonce'] ) && is_scalar( $params['_wpnonce'] ) ? (string) $params['_wpnonce'] : '';
		}
		return '' !== $nonce && false !== wp_verify_nonce( $nonce, 'wp_rest' );
	}

	private static function same_origin_request( WP_REST_Request $request ): bool {
		$origin  = trim( (string) $request->get_header( 'Origin' ) );
		$referer = trim( (string) $request->get_header( 'Referer' ) );
		if ( '' === $origin && '' === $referer ) {
			return defined( 'SPDB_TESTING' ) && SPDB_TESTING;
		}
		$home = home_url( '/' );
		return ( '' === $origin || self::same_origin_value( $origin, $home ) )
			&& ( '' === $referer || self::same_origin_value( $referer, $home ) );
	}

	/** @param array<string,mixed> $payload */
	private static function request_idempotency_key( WP_REST_Request $request, array $payload ): string {
		$header = trim( (string) $request->get_header( 'Idempotency-Key' ) );
		if ( '' !== $header ) {
			return $header;
		}
		return isset( $payload['idempotency_key'] ) && is_scalar( $payload['idempotency_key'] )
			? trim( (string) $payload['idempotency_key'] )
			: '';
	}

	/** @return string|WP_Error */
	private static function audit_reason( array $payload, array $policy ) {
		$reason = '';
		foreach ( array( 'audit_reason', 'reason', 'review_note' ) as $key ) {
			if ( isset( $payload[ $key ] ) && is_scalar( $payload[ $key ] ) ) {
				$reason = trim( wp_strip_all_tags( (string) $payload[ $key ] ) );
				if ( '' !== $reason ) {
					break;
				}
			}
		}
		if ( '' === $reason && empty( $policy['require_reason'] ) ) {
			$reason = 'Authorized File 23 operational mutation.';
		}
		if ( strlen( $reason ) < 10 || strlen( $reason ) > 500 || preg_match( '/[\x00-\x1F\x7F]/', $reason ) ) {
			return self::error( 'spdb_mutation_audit_reason_invalid', __( 'A meaningful privacy-safe audit reason is required.', 'sabri-publishing-dashboard' ), 422 );
		}
		return $reason;
	}

	/** @return array<string,mixed> */
	private static function request_payload( WP_REST_Request $request ): array {
		$json = $request->get_json_params();
		if ( is_array( $json ) ) {
			return $json;
		}
		$params = $request->get_body_params();
		return is_array( $params ) ? $params : array();
	}

	private static function receipt_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'spdb_mutation_receipts';
	}

	private static function audit_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'spdb_mutation_audit';
	}

	private static function sanitize_response_data( $value, int $depth = 0 ) {
		if ( $depth > 6 ) {
			return '[depth-limited]';
		}
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $key => $item ) {
				$name = is_string( $key ) ? strtolower( $key ) : (string) $key;
				if ( preg_match( '/password|secret|token|nonce|cookie|authorization|otp|cvv|card|patient|clinical|message_body/', $name ) ) {
					continue;
				}
				$out[ $key ] = self::sanitize_response_data( $item, $depth + 1 );
			}
			return $out;
		}
		if ( is_object( $value ) ) {
			return self::sanitize_response_data( get_object_vars( $value ), $depth + 1 );
		}
		if ( is_string( $value ) ) {
			return strlen( $value ) > 4096 ? substr( $value, 0, 4096 ) : $value;
		}
		return is_scalar( $value ) || null === $value ? $value : null;
	}

	private static function normalize_value( $value ) {
		if ( is_array( $value ) ) {
			if ( self::is_list_array( $value ) ) {
				return array_map( array( self::class, 'normalize_value' ), $value );
			}
			ksort( $value, SORT_STRING );
			foreach ( $value as $key => $item ) {
				$value[ $key ] = self::normalize_value( $item );
			}
		}
		return $value;
	}

	private static function is_list_array( array $value ): bool {
		$index = 0;
		foreach ( $value as $key => $_item ) {
			if ( $key !== $index ) {
				return false;
			}
			++$index;
		}
		return true;
	}

	private static function error( string $code, string $message, int $status ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
