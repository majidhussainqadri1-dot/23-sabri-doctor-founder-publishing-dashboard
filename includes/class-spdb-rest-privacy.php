<?php
/**
 * Private response policy for File 23 REST routes.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_REST_Privacy {
	public function register(): void {
		add_filter( 'rest_post_dispatch', array( $this, 'protect_response' ), 10, 3 );
	}

	public static function is_private_route( string $route ): bool {
		return 1 === preg_match( '~^/spdb/v1(?:/|$)~', $route );
	}

	/**
	 * @param WP_HTTP_Response|WP_Error $response REST response.
	 * @param WP_REST_Server            $server   REST server.
	 * @param WP_REST_Request           $request  Current request.
	 * @return WP_HTTP_Response|WP_Error
	 */
	public function protect_response( $response, $server, $request ) {
		if ( is_wp_error( $response ) || ! $request instanceof WP_REST_Request || ! self::is_private_route( $request->get_route() ) ) {
			return $response;
		}

		if ( $response instanceof WP_HTTP_Response ) {
			$response->header( 'Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0' );
			$response->header( 'Pragma', 'no-cache' );
			$response->header( 'X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet' );
			$response->header( 'X-Content-Type-Options', 'nosniff' );
		}

		return $response;
	}
}
