<?php
/**
 * Cross-cutting integrity guard for File 23-owned REST mutations.
 *
 * Enforces REST nonce, same-origin browser requests, request idempotency,
 * bounded replay receipts, hash-chained audit evidence and transactional
 * commit/rollback for local File 23 writes.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Operational_Mutation_Guard {
	private const RECEIPT_PREFIX = 'spdb_mutation_receipt_';
	private const RECEIPT_TTL    = 7 * DAY_IN_SECONDS;
	private const PENDING_TTL    = 5 * MINUTE_IN_SECONDS;
	private const RESPONSE_LIMIT      = 65535;
	private const MAX_PAYLOAD_DEPTH   = 8;
	private const MAX_PAYLOAD_NODES   = 500;
	private const MAX_STRING_LENGTH   = 8192;

	/** @var array<int,array<string,mixed>> */
	private static array $request_contexts = array();

	public static function register(): void {
		add_filter( 'rest_pre_dispatch', array( self::class, 'before_dispatch' ), 5, 3 );
		add_filter( 'rest_post_dispatch', array( self::class, 'after_dispatch' ), 999, 3 );
		add_action( 'init', array( self::class, 'schedule_cleanup' ) );
		add_action( 'spdb_mutation_guard_cleanup', array( self::class, 'cleanup' ) );
	}

	public static function activate(): void {
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
		$bounded = self::validate_payload_bounds( $payload );
		if ( is_wp_error( $bounded ) ) {
			return $bounded;
		}
		$key     = self::request_idempotency_key( $request, $payload );
		if ( ! self::valid_idempotency_key( $key ) ) {
			return self::error( 'spdb_mutation_idempotency_required', __( 'A valid idempotency key is required for this dashboard action.', 'sabri-publishing-dashboard' ), 422 );
		}

		$reason = self::audit_reason( $payload, $policy );
		if ( is_wp_error( $reason ) ) {
			return $reason;
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
			return $claim;
		}

		global $wpdb;
		$transactional = ! empty( $policy['transactional'] );
		$transaction   = false;
		if ( $transactional ) {
			$transaction = false !== $wpdb->query( 'START TRANSACTION' );
			if ( ! $transaction ) {
				delete_option( (string) $claim['option_name'] );
				return self::error( 'spdb_mutation_transaction_unavailable', __( 'The dashboard action could not open a safe transaction.', 'sabri-publishing-dashboard' ), 503 );
			}
		}

		self::$request_contexts[ spl_object_id( $request ) ] = array(
			'receipt_option' => (string) $claim['option_name'],
			'receipt_id'     => (string) $claim['receipt_id'],
			'actor_user_id'  => $user_id,
			'route'          => (string) $request->get_route(),
			'method'         => strtoupper( (string) $request->get_method() ),
			'reason'         => (string) $reason,
			'transaction'    => $transaction,
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

		if ( $status >= 400 ) {
			if ( $transaction ) {
				$wpdb->query( 'ROLLBACK' );
			}
			$completed = self::complete_receipt( $context, $status, $data, $status >= 500 ? 'failed' : 'denied' );
			if ( is_wp_error( $completed ) ) {
				return new WP_REST_Response(
					array(
						'code'    => 'spdb_mutation_failure_evidence_failed',
						'message' => __( 'The dashboard action failed and its replay evidence could not be finalized.', 'sabri-publishing-dashboard' ),
					),
					500
				);
			}
			$normalized->header( 'Cache-Control', 'private, no-store, max-age=0' );
			$normalized->header( 'X-SPDB-Idempotent', 'recorded' );
			return $normalized;
		}

		$completed = self::complete_receipt( $context, $status, $data, 'completed' );
		if ( is_wp_error( $completed ) ) {
			if ( $transaction ) {
				$wpdb->query( 'ROLLBACK' );
			}
			self::clear_receipt_cache( (string) $context['receipt_option'] );
			self::complete_receipt(
				$context,
				500,
				array( 'code' => 'spdb_mutation_audit_commit_failed' ),
				'failed'
			);
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
			self::clear_receipt_cache( (string) $context['receipt_option'] );
			self::complete_receipt(
				$context,
				500,
				array( 'code' => 'spdb_mutation_commit_failed' ),
				'failed'
			);
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
			'#^/spdb/v1/tasks(?:/task_[a-z0-9]{32})?$#'                    => array( 'transactional' => true, 'require_reason' => false ),
			'#^/spdb/v1/delegations(?:/delegation_[a-z0-9]{32}/revoke)?$#' => array( 'transactional' => true, 'require_reason' => true ),
			'#^/spdb/v1/automation-rules(?:/rule_[a-z0-9]{32}/status)?$#'   => array( 'transactional' => true, 'require_reason' => false ),
			'#^/spdb/v1/exports$#'                                          => array( 'transactional' => true, 'require_reason' => false ),
			'#^/spdb/v1/ai-assistance$#'                                    => array( 'transactional' => false, 'require_reason' => false ),
			'#^/spdb/v1/preferences$#'                                      => array( 'transactional' => true, 'require_reason' => false ),
			'#^/spdb/v1/settings$#'                                         => array( 'transactional' => true, 'require_reason' => true ),
			'#^/spdb/v1/system-check/repair$#'                              => array( 'transactional' => true, 'require_reason' => true ),
			'#^/spdb/v1/activation$#'                                       => array( 'transactional' => true, 'require_reason' => true ),
			'#^/spdb/v1/provider-acceptance/[a-z0-9][a-z0-9_-]{1,63}$#'         => array( 'transactional' => true, 'require_reason' => true ),
		);

		foreach ( $policies as $pattern => $policy ) {
			if ( 1 === preg_match( $pattern, $route ) ) {
				return $policy;
			}
		}
		return null;
	}

	/** @return array<int,array<string,mixed>> */
	public static function privacy_export_receipts( int $user_id, int $page = 1, int $per_page = 100 ): array {
		$rows = self::receipt_rows_for_user( $user_id, false );
		$page = max( 1, $page );
		$per_page = min( 200, max( 1, $per_page ) );
		return array_slice( $rows, ( $page - 1 ) * $per_page, $per_page );
	}

	public static function erase_user_receipts( int $user_id ): int {
		$rows = self::receipt_rows_for_user( $user_id, true );
		return count( $rows );
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
		if ( isset( $candidate_parts['user'] ) || isset( $candidate_parts['pass'] ) || isset( $home_parts['user'] ) || isset( $home_parts['pass'] ) ) {
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
		$like = $wpdb->esc_like( self::RECEIPT_PREFIX ) . '%';
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id ASC LIMIT 1000",
				$like
			),
			ARRAY_A
		);
		if ( ! is_array( $rows ) ) {
			return;
		}
		$now = time();
		foreach ( $rows as $row ) {
			$name = (string) ( $row['option_name'] ?? '' );
			$data = maybe_unserialize( $row['option_value'] ?? null );
			if ( ! is_array( $data ) || (int) ( $data['expires_at'] ?? 0 ) <= $now ) {
				delete_option( $name );
			}
		}
	}

	/** @return array{option_name:string,receipt_id:string}|WP_Error|WP_REST_Response */
	private static function claim_receipt( int $user_id, string $route, string $method, string $idempotency_key, string $payload_hash, string $reason ) {
		$route_hash  = hash( 'sha256', $route );
		$key_hash    = hash( 'sha256', $idempotency_key );
		$option_name = self::receipt_option_name( $user_id, $route_hash, $method, $key_hash );
		$existing    = get_option( $option_name, null );

		if ( is_array( $existing ) ) {
			return self::existing_receipt_result( $existing, $payload_hash );
		}

		$receipt_id = wp_generate_uuid4();
		$now        = time();
		$receipt    = array(
			'receipt_id'       => $receipt_id,
			'actor_user_id'    => $user_id,
			'route_hash'       => $route_hash,
			'method'           => $method,
			'idempotency_hash' => $key_hash,
			'payload_hash'     => $payload_hash,
			'state'            => 'pending',
			'response_status'  => 0,
			'response_data'    => null,
			'created_at'       => $now,
			'updated_at'       => $now,
			'expires_at'       => $now + self::RECEIPT_TTL,
		);

		if ( ! add_option( $option_name, $receipt, '', false ) ) {
			$existing = get_option( $option_name, null );
			return is_array( $existing )
				? self::existing_receipt_result( $existing, $payload_hash )
				: self::error( 'spdb_mutation_receipt_failed', __( 'The mutation receipt could not be secured.', 'sabri-publishing-dashboard' ), 503 );
		}

		$audit = self::append_audit( $receipt_id, $user_id, 'mutation_requested', $route_hash, $method, 'pending', $reason, $payload_hash );
		if ( is_wp_error( $audit ) ) {
			delete_option( $option_name );
			return $audit;
		}

		return array( 'option_name' => $option_name, 'receipt_id' => $receipt_id );
	}

	/** @return WP_Error|WP_REST_Response */
	private static function existing_receipt_result( array $row, string $payload_hash ) {
		if ( ! hash_equals( (string) ( $row['payload_hash'] ?? '' ), $payload_hash ) ) {
			return self::error( 'spdb_mutation_idempotency_conflict', __( 'The idempotency key was already used with a different request.', 'sabri-publishing-dashboard' ), 409 );
		}

		$state = (string) ( $row['state'] ?? '' );
		if ( in_array( $state, array( 'completed', 'denied', 'failed' ), true ) ) {
			$response = new WP_REST_Response( $row['response_data'] ?? array(), max( 200, (int) ( $row['response_status'] ?? 200 ) ) );
			$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
			$response->header( 'X-SPDB-Idempotent', 'replayed' );
			return $response;
		}

		$updated = (int) ( $row['updated_at'] ?? 0 );
		if ( $updated > time() - self::PENDING_TTL ) {
			return self::error( 'spdb_mutation_in_progress', __( 'The same dashboard action is already being processed.', 'sabri-publishing-dashboard' ), 409 );
		}
		return self::error( 'spdb_mutation_stale_receipt', __( 'A stale mutation receipt requires reconciliation before retry.', 'sabri-publishing-dashboard' ), 409 );
	}

	/** @return true|WP_Error */
	private static function complete_receipt( array $context, int $status, $data, string $outcome ) {
		$safe_data = self::sanitize_response_data( $data );
		$json      = wp_json_encode( $safe_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
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

		$option_name = (string) $context['receipt_option'];
		$receipt     = get_option( $option_name, null );
		if ( ! is_array( $receipt ) || ! hash_equals( $receipt_id, (string) ( $receipt['receipt_id'] ?? '' ) ) || 'pending' !== (string) ( $receipt['state'] ?? '' ) ) {
			return self::error( 'spdb_mutation_receipt_state_invalid', __( 'The mutation receipt is no longer current.', 'sabri-publishing-dashboard' ), 409 );
		}

		$receipt['state']           = $outcome;
		$receipt['response_status'] = min( 599, max( 100, $status ) );
		$receipt['response_data']   = $safe_data;
		$receipt['updated_at']      = time();
		$updated = update_option( $option_name, $receipt, false );
		if ( ! $updated ) {
			$stored = get_option( $option_name, null );
			if ( ! is_array( $stored ) || ! hash_equals( $outcome, (string) ( $stored['state'] ?? '' ) ) || (int) ( $stored['response_status'] ?? 0 ) !== (int) $receipt['response_status'] ) {
				return self::error( 'spdb_mutation_receipt_update_failed', __( 'The mutation receipt could not be finalized.', 'sabri-publishing-dashboard' ), 500 );
			}
		}
		return true;
	}

	/** @return true|WP_Error */
	private static function append_audit( string $receipt_id, int $user_id, string $event_key, string $route_hash, string $method, string $outcome, string $reason, string $details_hash ) {
		if ( ! class_exists( 'SPDB_Operations_Repository' ) ) {
			return self::error( 'spdb_mutation_audit_unavailable', __( 'The dashboard audit service is unavailable.', 'sabri-publishing-dashboard' ), 503 );
		}
		$repository = new SPDB_Operations_Repository();
		return $repository->append_audit(
			$user_id,
			$event_key,
			'mutation-receipt:' . $receipt_id,
			array(
				'receipt_hash' => hash( 'sha256', $receipt_id ),
				'route_hash'   => $route_hash,
				'method'       => $method,
				'outcome'      => $outcome,
				'reason'       => $reason,
				'details_hash' => $details_hash,
			)
		);
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
		if ( self::text_length( $reason ) < 10 || self::text_length( $reason ) > 500 || preg_match( '/[\x00-\x1F\x7F]/', $reason ) || self::sensitive_audit_text( $reason ) ) {
			return self::error( 'spdb_mutation_audit_reason_invalid', __( 'A meaningful privacy-safe audit reason is required.', 'sabri-publishing-dashboard' ), 422 );
		}
		return $reason;
	}

	private static function sensitive_audit_text( string $value ): bool {
		return 1 === preg_match( '/password|secret|token|nonce|otp|cvv|authorization|cookie|bearer|api[ _-]?key|patient[ _-]?(?:name|id)|message[ _-]?body|diagnosis|prescription/i', $value )
			|| 1 === preg_match( '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $value )
			|| 1 === preg_match( '/\b\+?\d[\d ()-]{7,}\d\b/', $value );
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

	private static function clear_receipt_cache( string $option_name ): void {
		if ( function_exists( 'wp_cache_delete' ) ) {
			wp_cache_delete( $option_name, 'options' );
		}
	}

	/** @return array<int,array<string,mixed>> */
	private static function receipt_rows_for_user( int $user_id, bool $erase ): array {
		if ( $user_id < 1 ) {
			return array();
		}
		global $wpdb;
		if ( ! is_object( $wpdb ) || ! isset( $wpdb->options ) || ! method_exists( $wpdb, 'get_results' ) || ! method_exists( $wpdb, 'prepare' ) ) {
			return array();
		}
		$found  = array();
		$cursor = 0;
		$like   = $wpdb->esc_like( self::RECEIPT_PREFIX ) . '%';
		for ( $batch = 0; $batch < 40; ++$batch ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT option_id, option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s AND option_id > %d ORDER BY option_id ASC LIMIT 250",
					$like,
					$cursor
				),
				defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A'
			);
			if ( ! is_array( $rows ) || ! $rows ) {
				break;
			}
			foreach ( $rows as $row ) {
				$cursor  = max( $cursor, (int) ( $row['option_id'] ?? 0 ) );
				$receipt = maybe_unserialize( $row['option_value'] ?? null );
				if ( ! is_array( $receipt ) || (int) ( $receipt['actor_user_id'] ?? 0 ) !== $user_id ) {
					continue;
				}
				$found[] = array(
					'receipt_id_hash' => hash( 'sha256', (string) ( $receipt['receipt_id'] ?? '' ) ),
					'route_hash'      => (string) ( $receipt['route_hash'] ?? '' ),
					'method'          => sanitize_key( strtolower( (string) ( $receipt['method'] ?? '' ) ) ),
					'state'           => sanitize_key( (string) ( $receipt['state'] ?? '' ) ),
					'response_status' => max( 0, (int) ( $receipt['response_status'] ?? 0 ) ),
					'created_at'      => max( 0, (int) ( $receipt['created_at'] ?? 0 ) ),
					'updated_at'      => max( 0, (int) ( $receipt['updated_at'] ?? 0 ) ),
					'expires_at'      => max( 0, (int) ( $receipt['expires_at'] ?? 0 ) ),
				);
				if ( $erase ) {
					delete_option( (string) ( $row['option_name'] ?? '' ) );
				}
			}
			if ( count( $rows ) < 250 ) {
				break;
			}
		}
		return $found;
	}

	private static function receipt_option_name( int $user_id, string $route_hash, string $method, string $key_hash ): string {
		return self::RECEIPT_PREFIX . hash( 'sha256', implode( '|', array( (string) $user_id, $route_hash, $method, $key_hash ) ) );
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
			return self::text_length( $value ) > 4096 ? self::text_substr( $value, 0, 4096 ) : $value;
		}
		return is_scalar( $value ) || null === $value ? $value : null;
	}

	/** @return true|WP_Error */
	public static function validate_payload_bounds( array $payload ) {
		$nodes = 0;
		$valid = self::walk_payload_bounds( $payload, 0, $nodes );
		return $valid
			? true
			: self::error( 'spdb_mutation_payload_too_large', __( 'The dashboard request payload exceeds the safe complexity limit.', 'sabri-publishing-dashboard' ), 413 );
	}

	private static function walk_payload_bounds( $value, int $depth, int &$nodes ): bool {
		++$nodes;
		if ( $depth > self::MAX_PAYLOAD_DEPTH || $nodes > self::MAX_PAYLOAD_NODES ) {
			return false;
		}
		if ( is_string( $value ) ) {
			return self::text_length( $value ) <= self::MAX_STRING_LENGTH && ! preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value );
		}
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				if ( is_string( $key ) && self::text_length( $key ) > 128 ) {
					return false;
				}
				if ( ! self::walk_payload_bounds( $item, $depth + 1, $nodes ) ) {
					return false;
				}
			}
			return true;
		}
		return is_scalar( $value ) || null === $value;
	}

	private static function text_length( string $value ): int {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : strlen( $value );
	}

	private static function text_substr( string $value, int $start, int $length ): string {
		return function_exists( 'mb_substr' ) ? mb_substr( $value, $start, $length, 'UTF-8' ) : substr( $value, $start, $length );
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
