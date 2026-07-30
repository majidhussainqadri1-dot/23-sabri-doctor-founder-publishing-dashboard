<?php
/**
 * Explicit REST endpoints for Phase 23E projections and native operations.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Review_Calendar_REST_Controller {
	private SPDB_Review_Calendar_Service $service;
	private SPDB_Operation_Broker $broker;

	public function __construct( SPDB_Review_Calendar_Service $service, SPDB_Operation_Broker $broker ) {
		$this->service = $service;
		$this->broker  = $broker;
	}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		register_rest_route(
			'spdb/v1',
			'/review',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_review' ),
				'permission_callback' => array( $this, 'review_read_permission' ),
			)
		);
		register_rest_route(
			'spdb/v1',
			'/calendar',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_calendar' ),
				'permission_callback' => array( $this, 'calendar_read_permission' ),
			)
		);

		$routes = array(
			'/review/(?P<provider>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_type>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_id>[A-Za-z0-9][A-Za-z0-9._:-]{0,127})/approve'         => 'approve_review',
			'/review/(?P<provider>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_type>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_id>[A-Za-z0-9][A-Za-z0-9._:-]{0,127})/request-changes' => 'request_changes',
			'/review/(?P<provider>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_type>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_id>[A-Za-z0-9][A-Za-z0-9._:-]{0,127})/reject'          => 'reject_review',
			'/review/(?P<provider>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_type>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_id>[A-Za-z0-9][A-Za-z0-9._:-]{0,127})/assign-reviewer' => 'assign_reviewer',
			'/calendar/(?P<provider>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_type>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_id>[A-Za-z0-9][A-Za-z0-9._:-]{0,127})/schedule'      => 'schedule',
			'/calendar/(?P<provider>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_type>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_id>[A-Za-z0-9][A-Za-z0-9._:-]{0,127})/reschedule'    => 'reschedule',
			'/calendar/(?P<provider>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_type>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_id>[A-Za-z0-9][A-Za-z0-9._:-]{0,127})/unschedule'    => 'unschedule',
		);
		foreach ( $routes as $route => $operation ) {
			$contract = SPDB_Review_Calendar_Validator::operation_contract( $operation );
			register_rest_route(
				'spdb/v1',
				$route,
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => function ( WP_REST_Request $request ) use ( $operation ) {
						return $this->execute_operation( $request, $operation );
					},
					'permission_callback' => function ( WP_REST_Request $request ) use ( $contract ) {
						return $this->mutation_permission( $request, is_array( $contract ) ? (string) $contract['capability'] : '' );
					},
				)
			);
		}
	}

	/** @return true|WP_Error */
	public function review_read_permission() {
		return SPDB_Capabilities::current_user_can( 'spdb_view_review_queue' )
			? true
			: $this->error( 'spdb_review_forbidden', 'You are not authorized to view the review queue.', 403 );
	}

	/** @return true|WP_Error */
	public function calendar_read_permission() {
		return SPDB_Capabilities::current_user_can( 'spdb_view_own_content' )
			? true
			: $this->error( 'spdb_calendar_forbidden', 'You are not authorized to view the publishing calendar.', 403 );
	}

	/** @return true|WP_Error */
	public function mutation_permission( WP_REST_Request $request, string $capability ) {
		if ( '' === $capability || ! SPDB_Capabilities::current_user_can( $capability ) ) {
			return $this->error( 'spdb_operation_forbidden', 'You are not authorized to perform this operation.', 403 );
		}
		return $this->valid_rest_nonce( $request )
			? true
			: $this->error( 'spdb_operation_nonce_invalid', 'A valid REST request nonce is required.', 403 );
	}

	public function rest_review( WP_REST_Request $request ) {
		$result = $this->service->review_queue( $request->get_params() );
		return '' !== (string) ( $result['query_error'] ?? '' )
			? $this->error( 'spdb_review_query_invalid', 'The review filters are invalid.', 400 )
			: rest_ensure_response( $result );
	}

	public function rest_calendar( WP_REST_Request $request ) {
		$result = $this->service->calendar( $request->get_params() );
		return '' !== (string) ( $result['query_error'] ?? '' )
			? $this->error( 'spdb_calendar_query_invalid', 'The calendar filters are invalid.', 400 )
			: rest_ensure_response( $result );
	}

	/** @return array<string,mixed>|WP_Error */
	public function execute_operation( WP_REST_Request $request, string $operation ) {
		$contract = SPDB_Review_Calendar_Validator::operation_contract( $operation );
		if ( null === $contract ) {
			return $this->error( 'spdb_operation_route_invalid', 'The requested operation route is invalid.', 404 );
		}
		$permission = $this->mutation_permission( $request, (string) $contract['capability'] );
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		$provider    = is_scalar( $request['provider'] ) ? (string) $request['provider'] : '';
		$object_type = is_scalar( $request['object_type'] ) ? (string) $request['object_type'] : '';
		$object_id   = is_scalar( $request['object_id'] ) ? (string) $request['object_id'] : '';
		if ( ! SPDB_Adapter_Registry::is_canonical_key( $provider ) || ! SPDB_Adapter_Registry::is_canonical_key( $object_type ) || ! SPDB_Projection_Validator::valid_object_id( $object_id ) ) {
			return $this->error( 'spdb_operation_reference_invalid', 'The native object reference is invalid.', 422 );
		}

		$payload = $this->payload( $request->get_params(), $operation );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}
		$authorization = $this->service->authorize_operation( $provider, $object_type, $object_id, $operation, (string) $payload['object_version'] );
		if ( is_wp_error( $authorization ) ) {
			return $this->error( 'spdb_operation_not_authorized', 'The native object is not currently authorized for this operation.', $this->error_status( $authorization, 403 ) );
		}
		if ( 'assign_reviewer' === $operation && ! $this->service->reviewer_target_is_eligible( (int) $payload['reviewer_id'] ) ) {
			return $this->error( 'spdb_reviewer_target_ineligible', 'The requested reviewer is not eligible for assignment.', 422 );
		}

		$result = $this->broker->execute( $provider, $operation, $object_type, $object_id, $payload );
		if ( is_wp_error( $result ) ) {
			return $this->error( 'spdb_native_action_failed', 'The native provider did not confirm the requested action. No success state has been assumed.', 409 );
		}
		$confirmed = is_array( $result['confirmed_item'] ?? null ) ? $result['confirmed_item'] : array();
		if ( (string) ( $confirmed['object_type'] ?? '' ) !== $object_type || (string) ( $confirmed['object_id'] ?? '' ) !== $object_id ) {
			return $this->error( 'spdb_native_confirmation_invalid', 'The native provider returned an invalid confirmation reference.', 409 );
		}

		return rest_ensure_response(
			array(
				'provider'         => $provider,
				'object_type'      => $object_type,
				'object_id'        => $object_id,
				'operation'        => $operation,
				'confirmed'        => true,
				'native_refetched' => true,
				'confirmed_at_gmt' => gmdate( 'c' ),
			)
		);
	}

	/** @return array<string,mixed>|WP_Error */
	private function payload( array $params, string $operation ) {
		$base_allowed = array( 'provider', 'object_type', 'object_id', 'object_version', 'idempotency_key', 'audit_reason', '_wpnonce', '_locale' );
		$operation_allowed = array();
		if ( in_array( $operation, array( 'request_changes', 'reject_review' ), true ) ) {
			$operation_allowed = array( 'reason_code', 'review_note' );
		} elseif ( 'assign_reviewer' === $operation ) {
			$operation_allowed = array( 'reviewer_id' );
		} elseif ( in_array( $operation, array( 'schedule', 'reschedule' ), true ) ) {
			$operation_allowed = array( 'scheduled_at_utc', 'native_timezone' );
		}
		$allowed = array_merge( $base_allowed, $operation_allowed );
		foreach ( $params as $key => $value ) {
			if ( ! in_array( (string) $key, $allowed, true ) ) {
				return $this->error( 'spdb_operation_payload_field_invalid', 'The request contains an undeclared payload field.', 422 );
			}
			if ( in_array( (string) $key, array( 'provider', 'object_type', 'object_id', '_wpnonce', '_locale' ), true ) ) {
				continue;
			}
			if ( ! is_scalar( $value ) ) {
				return $this->error( 'spdb_operation_payload_shape_invalid', 'An operation payload field has an invalid shape.', 422 );
			}
		}

		$payload = array();
		foreach ( array_merge( array( 'object_version', 'idempotency_key', 'audit_reason' ), $operation_allowed ) as $key ) {
			if ( array_key_exists( $key, $params ) && is_scalar( $params[ $key ] ) ) {
				$payload[ $key ] = trim( (string) $params[ $key ] );
			}
		}
		foreach ( array( 'object_version', 'idempotency_key', 'audit_reason' ) as $required ) {
			if ( '' === (string) ( $payload[ $required ] ?? '' ) ) {
				return $this->error( 'spdb_operation_payload_incomplete', 'The native object version, idempotency key, and audit reason are required.', 422 );
			}
		}
		if ( strlen( $payload['object_version'] ) > 191 || preg_match( '/[\x00-\x1F\x7F]/', $payload['object_version'] ) ) {
			return $this->error( 'spdb_object_version_invalid', 'The native object version is invalid.', 422 );
		}
		if ( strlen( $payload['audit_reason'] ) < 10 || strlen( $payload['audit_reason'] ) > 500 || preg_match( '/[\x00-\x1F\x7F]/', $payload['audit_reason'] ) || SPDB_Review_Calendar_Validator::sensitive_text( $payload['audit_reason'] ) ) {
			return $this->error( 'spdb_audit_reason_invalid', 'The audit reason is invalid or contains prohibited sensitive data.', 422 );
		}

		if ( in_array( $operation, array( 'request_changes', 'reject_review' ), true ) ) {
			if ( empty( $payload['reason_code'] ) || ! SPDB_Adapter_Registry::is_canonical_key( $payload['reason_code'] ) ) {
				return $this->error( 'spdb_reason_code_invalid', 'A canonical structured reason code is required.', 422 );
			}
			$note = (string) ( $payload['review_note'] ?? '' );
			if ( strlen( $note ) < 10 || strlen( $note ) > 1000 || preg_match( '/[\x00-\x1F\x7F]/', $note ) || SPDB_Review_Calendar_Validator::sensitive_text( $note ) ) {
				return $this->error( 'spdb_review_note_invalid', 'A meaningful privacy-safe review note is required.', 422 );
			}
		}
		if ( 'assign_reviewer' === $operation ) {
			$reviewer_id = (string) ( $payload['reviewer_id'] ?? '' );
			if ( 1 !== preg_match( '/^[1-9]\d{0,9}$/', $reviewer_id ) || (int) $reviewer_id > SPDB_Review_Calendar_Validator::MAX_REPORTED_TOTAL ) {
				return $this->error( 'spdb_reviewer_id_invalid', 'A valid reviewer identifier is required.', 422 );
			}
			$payload['reviewer_id'] = (int) $reviewer_id;
		}
		if ( in_array( $operation, array( 'schedule', 'reschedule' ), true ) ) {
			$scheduled = SPDB_Review_Calendar_Validator::normalize_utc_timestamp( $payload['scheduled_at_utc'] ?? '' );
			if ( is_wp_error( $scheduled ) ) {
				return $scheduled;
			}
			$timezone = (string) ( $payload['native_timezone'] ?? '' );
			if ( ! SPDB_Review_Calendar_Validator::valid_timezone( $timezone ) ) {
				return $this->error( 'spdb_schedule_timezone_invalid', 'A valid IANA native time zone is required.', 422 );
			}
			$payload['scheduled_at_utc'] = $scheduled;
		}
		return $payload;
	}

	private function valid_rest_nonce( WP_REST_Request $request ): bool {
		$nonce = method_exists( $request, 'get_header' ) ? (string) $request->get_header( 'X-WP-Nonce' ) : '';
		if ( '' === $nonce && isset( $request['_wpnonce'] ) && is_scalar( $request['_wpnonce'] ) ) {
			$nonce = (string) $request['_wpnonce'];
		}
		return '' !== $nonce && function_exists( 'wp_verify_nonce' ) && 1 === wp_verify_nonce( $nonce, 'wp_rest' );
	}

	private function error_status( WP_Error $error, int $default ): int {
		$data = $error->get_error_data();
		return is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : $default;
	}

	private function error( string $code, string $message, int $status ): WP_Error {
		return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => $status ) );
	}
}
