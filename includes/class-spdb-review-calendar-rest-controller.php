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
			'/review/(?P<provider>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_type>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_id>[A-Za-z0-9][A-Za-z0-9._:-]{0,190})/approve'         => 'approve_review',
			'/review/(?P<provider>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_type>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_id>[A-Za-z0-9][A-Za-z0-9._:-]{0,190})/request-changes' => 'request_changes',
			'/review/(?P<provider>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_type>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_id>[A-Za-z0-9][A-Za-z0-9._:-]{0,190})/reject'          => 'reject_review',
			'/review/(?P<provider>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_type>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_id>[A-Za-z0-9][A-Za-z0-9._:-]{0,190})/assign-reviewer' => 'assign_reviewer',
			'/calendar/(?P<provider>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_type>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_id>[A-Za-z0-9][A-Za-z0-9._:-]{0,190})/schedule'      => 'schedule',
			'/calendar/(?P<provider>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_type>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_id>[A-Za-z0-9][A-Za-z0-9._:-]{0,190})/reschedule'    => 'reschedule',
			'/calendar/(?P<provider>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_type>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_id>[A-Za-z0-9][A-Za-z0-9._:-]{0,190})/unschedule'    => 'unschedule',
		);
		foreach ( $routes as $route => $operation ) {
			register_rest_route(
				'spdb/v1',
				$route,
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => function ( WP_REST_Request $request ) use ( $operation ) {
						return $this->execute_operation( $request, $operation );
					},
					'permission_callback' => in_array( $operation, SPDB_Review_Calendar_Validator::review_operations(), true )
						? array( $this, 'review_write_permission' )
						: array( $this, 'calendar_write_permission' ),
				)
			);
		}
	}

	/** @return true|WP_Error */
	public function review_read_permission() {
		return SPDB_Capabilities::current_user_can( 'spdb_view_review_queue' )
			? true
			: new WP_Error( 'spdb_review_forbidden', __( 'You are not authorized to view the review queue.', 'sabri-publishing-dashboard' ), array( 'status' => 403 ) );
	}

	/** @return true|WP_Error */
	public function calendar_read_permission() {
		return SPDB_Capabilities::current_user_can( 'spdb_view_own_content' )
			? true
			: new WP_Error( 'spdb_calendar_forbidden', __( 'You are not authorized to view the publishing calendar.', 'sabri-publishing-dashboard' ), array( 'status' => 403 ) );
	}

	/** @return true|WP_Error */
	public function review_write_permission() {
		return SPDB_Capabilities::current_user_can( 'spdb_review_assigned_content' )
			? true
			: new WP_Error( 'spdb_review_action_forbidden', __( 'You are not authorized to perform review actions.', 'sabri-publishing-dashboard' ), array( 'status' => 403 ) );
	}

	/** @return true|WP_Error */
	public function calendar_write_permission() {
		return SPDB_Capabilities::current_user_can( 'spdb_manage_schedule' )
			? true
			: new WP_Error( 'spdb_schedule_action_forbidden', __( 'You are not authorized to perform schedule actions.', 'sabri-publishing-dashboard' ), array( 'status' => 403 ) );
	}

	public function rest_review( WP_REST_Request $request ) {
		return rest_ensure_response( $this->service->review_queue( $request->get_params() ) );
	}

	public function rest_calendar( WP_REST_Request $request ) {
		return rest_ensure_response( $this->service->calendar( $request->get_params() ) );
	}

	/** @return array<string,mixed>|WP_Error */
	public function execute_operation( WP_REST_Request $request, string $operation ) {
		if ( ! in_array( $operation, array_merge( SPDB_Review_Calendar_Validator::review_operations(), SPDB_Review_Calendar_Validator::calendar_operations() ), true ) ) {
			return new WP_Error( 'spdb_operation_route_invalid', __( 'The requested operation route is invalid.', 'sabri-publishing-dashboard' ), array( 'status' => 404 ) );
		}
		$provider    = is_scalar( $request['provider'] ) ? (string) $request['provider'] : '';
		$object_type = is_scalar( $request['object_type'] ) ? (string) $request['object_type'] : '';
		$object_id   = is_scalar( $request['object_id'] ) ? (string) $request['object_id'] : '';
		if ( ! SPDB_Adapter_Registry::is_canonical_key( $provider ) || ! SPDB_Adapter_Registry::is_canonical_key( $object_type ) || ! SPDB_Projection_Validator::valid_object_id( $object_id ) ) {
			return new WP_Error( 'spdb_operation_reference_invalid', __( 'The native object reference is invalid.', 'sabri-publishing-dashboard' ), array( 'status' => 422 ) );
		}
		$payload = $this->payload( $request->get_params(), $operation );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}
		$result = $this->broker->execute( $provider, $operation, $object_type, $object_id, $payload );
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'spdb_native_action_failed', __( 'The native provider did not confirm the requested action. No success state has been assumed.', 'sabri-publishing-dashboard' ), array( 'status' => 409 ) );
		}
		return rest_ensure_response(
			array(
				'provider'        => $provider,
				'object_type'     => $object_type,
				'object_id'       => $object_id,
				'operation'       => $operation,
				'confirmed'       => true,
				'native_refetched'=> true,
				'confirmed_at_gmt'=> gmdate( 'c' ),
			)
		);
	}

	/** @return array<string,mixed>|WP_Error */
	private function payload( array $params, string $operation ) {
		$allowed = array( 'object_version', 'idempotency_key', 'audit_reason' );
		if ( in_array( $operation, array( 'request_changes', 'reject_review' ), true ) ) {
			$allowed[] = 'reason_code';
			$allowed[] = 'review_note';
		}
		if ( 'assign_reviewer' === $operation ) {
			$allowed[] = 'reviewer_id';
		}
		if ( in_array( $operation, array( 'schedule', 'reschedule' ), true ) ) {
			$allowed[] = 'scheduled_at_utc';
			$allowed[] = 'native_timezone';
		}
		$payload = array();
		foreach ( $allowed as $key ) {
			if ( ! array_key_exists( $key, $params ) || is_array( $params[ $key ] ) || is_object( $params[ $key ] ) ) {
				continue;
			}
			$payload[ $key ] = trim( (string) $params[ $key ] );
		}
		foreach ( array( 'object_version', 'idempotency_key', 'audit_reason' ) as $required ) {
			if ( '' === (string) ( $payload[ $required ] ?? '' ) ) {
				return new WP_Error( 'spdb_operation_payload_incomplete', __( 'The native object version, idempotency key, and audit reason are required.', 'sabri-publishing-dashboard' ), array( 'status' => 422 ) );
			}
		}
		if ( isset( $payload['reason_code'] ) && ! SPDB_Adapter_Registry::is_canonical_key( $payload['reason_code'] ) ) {
			return new WP_Error( 'spdb_reason_code_invalid', __( 'The structured reason code is invalid.', 'sabri-publishing-dashboard' ), array( 'status' => 422 ) );
		}
		if ( isset( $payload['review_note'] ) && ( strlen( $payload['review_note'] ) > 1000 || preg_match( '/(?:https?:\/\/|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}|\b(?:\+?92|0)?3\d{9}\b)/i', $payload['review_note'] ) ) ) {
			return new WP_Error( 'spdb_review_note_invalid', __( 'The review note is invalid or contains prohibited contact or URL data.', 'sabri-publishing-dashboard' ), array( 'status' => 422 ) );
		}
		if ( isset( $payload['reviewer_id'] ) && 1 !== preg_match( '/^[1-9]\d*$/', $payload['reviewer_id'] ) ) {
			return new WP_Error( 'spdb_reviewer_id_invalid', __( 'The reviewer identifier is invalid.', 'sabri-publishing-dashboard' ), array( 'status' => 422 ) );
		}
		if ( isset( $payload['scheduled_at_utc'] ) && 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $payload['scheduled_at_utc'] ) ) {
			return new WP_Error( 'spdb_schedule_timestamp_invalid', __( 'The schedule timestamp must be absolute UTC RFC 3339.', 'sabri-publishing-dashboard' ), array( 'status' => 422 ) );
		}
		if ( isset( $payload['native_timezone'] ) && ! in_array( $payload['native_timezone'], timezone_identifiers_list(), true ) ) {
			return new WP_Error( 'spdb_schedule_timezone_invalid', __( 'The native schedule time zone is invalid.', 'sabri-publishing-dashboard' ), array( 'status' => 422 ) );
		}
		return $payload;
	}
}
