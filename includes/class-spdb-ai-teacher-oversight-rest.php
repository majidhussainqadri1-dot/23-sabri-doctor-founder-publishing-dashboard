<?php
/**
 * Founder/security oversight projection for the institutional AI Homeopathy Teacher.
 *
 * File 23 never generates or publishes AI content here. File 16 remains the
 * generation-policy owner, File 22 the composer bridge and File 21 the
 * publication-lifecycle owner. This controller exposes only the existing
 * privacy-filtered provider projection contract for operational oversight.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_AI_Teacher_Oversight_REST {
	private const NAMESPACE = 'spdb/v1';

	public static function register(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_route' ) );
	}

	public static function register_route(): void {
		register_rest_route(
			self::NAMESPACE,
			'/operations/ai_teacher',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_projection' ),
				'permission_callback' => array( __CLASS__, 'permission' ),
			)
		);
	}

	/** @return true|WP_Error */
	public static function permission() {
		$user_id = get_current_user_id();
		if (
			$user_id < 1
			|| ! SPDB_Membership_Guard::is_user_approved( $user_id )
			|| ! SPDB_Capabilities::current_user_can( 'spdb_view_assurance_status' )
		) {
			return new WP_Error(
				'spdb_ai_teacher_oversight_forbidden',
				__( 'AI Teacher oversight is restricted to authorized human oversight accounts.', 'sabri-publishing-dashboard' ),
				array( 'status' => 403 )
			);
		}
		if ( SPDB_Membership_Guard::is_user_institutional_ai( $user_id ) ) {
			return new WP_Error(
				'spdb_ai_teacher_self_oversight_forbidden',
				__( 'An institutional AI account cannot authorize or oversee itself.', 'sabri-publishing-dashboard' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	public static function get_projection( WP_REST_Request $request ) {
		$registry = SPDB_Provider_Registration::registry();
		if ( ! $registry instanceof SPDB_Adapter_Registry ) {
			return new WP_Error(
				'spdb_ai_teacher_provider_registry_unavailable',
				__( 'The provider registry is unavailable.', 'sabri-publishing-dashboard' ),
				array( 'status' => 503 )
			);
		}
		$service = new SPDB_Operations_Service( $registry, new SPDB_Operations_Repository() );
		$result  = $service->projections( 'ai_teacher', $request->get_params() );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}
}
