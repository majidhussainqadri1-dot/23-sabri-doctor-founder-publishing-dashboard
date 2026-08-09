<?php
/**
 * Strong-session and browser-origin gate for sensitive File 23 mutations.
 *
 * File 00 is the canonical identity and session-assurance owner. File 23 must
 * never turn a valid login or a WordPress capability into a substitute for
 * the current File 00 session_two_factor assertion.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Sensitive_Session_Guard {
	public static function register(): void {
		add_filter( 'rest_pre_dispatch', array( self::class, 'enforce' ), 6, 3 );
	}

	/** @param mixed $result @return mixed */
	public static function enforce( $result, WP_REST_Server $server, WP_REST_Request $request ) {
		unset( $server );
		if ( null !== $result || ! self::is_sensitive_mutation( (string) $request->get_route(), (string) $request->get_method() ) ) {
			return $result;
		}
		if ( get_current_user_id() < 1 ) {
			return new WP_Error( 'spdb_sensitive_session_auth_required', __( 'Authentication is required for this sensitive dashboard action.', 'sabri-publishing-dashboard' ), array( 'status' => 401 ) );
		}
		if ( ! self::same_origin_request( $request ) ) {
			return new WP_Error( 'spdb_sensitive_session_origin_invalid', __( 'The request origin is not authorized for this sensitive dashboard action.', 'sabri-publishing-dashboard' ), array( 'status' => 403 ) );
		}
		if ( ! SPDB_Membership_Guard::current_user_has_sensitive_session() ) {
			return new WP_Error( 'spdb_sensitive_session_required', __( 'Current File 00 session assurance is required before this sensitive dashboard action can continue.', 'sabri-publishing-dashboard' ), array( 'status' => 403 ) );
		}
		return $result;
	}

	public static function is_sensitive_mutation( string $route, string $method ): bool {
		if ( ! in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) ) {
			return false;
		}
		foreach ( array(
			'#^/spdb/v1/tasks(?:/task_[a-z0-9]{32})?$#',
			'#^/spdb/v1/delegations(?:/delegation_[a-z0-9]{32}/revoke)?$#',
			'#^/spdb/v1/automation-rules(?:/rule_[a-z0-9]{32}/status)?$#',
			'#^/spdb/v1/exports$#',
			'#^/spdb/v1/ai-assistance$#',
			'#^/spdb/v1/settings$#',
			'#^/spdb/v1/system-check/repair$#',
			'#^/spdb/v1/activation$#',
			'#^/spdb/v1/provider-acceptance/[a-z0-9][a-z0-9_-]{1,63}$#',
			'#^/spdb/v1/review/[a-z0-9][a-z0-9_-]{1,63}/[a-z0-9][a-z0-9_-]{1,63}/[A-Za-z0-9][A-Za-z0-9._:-]{0,127}/(?:approve|request-changes|reject|assign-reviewer)$#',
			'#^/spdb/v1/calendar/[a-z0-9][a-z0-9_-]{1,63}/[a-z0-9][a-z0-9_-]{1,63}/[A-Za-z0-9][A-Za-z0-9._:-]{0,127}/(?:schedule|reschedule|unschedule)$#',
		) as $pattern ) {
			if ( 1 === preg_match( $pattern, $route ) ) {
				return true;
			}
		}
		return false;
	}

	private static function same_origin_request( WP_REST_Request $request ): bool {
		$origin  = trim( (string) $request->get_header( 'Origin' ) );
		$referer = trim( (string) $request->get_header( 'Referer' ) );
		if ( '' === $origin && '' === $referer ) {
			return defined( 'SPDB_TESTING' ) && SPDB_TESTING;
		}
		$home = home_url( '/' );
		return ( '' === $origin || SPDB_Operational_Mutation_Guard::same_origin_value( $origin, $home ) )
			&& ( '' === $referer || SPDB_Operational_Mutation_Guard::same_origin_value( $referer, $home ) );
	}
}
