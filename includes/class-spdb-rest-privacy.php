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
		add_filter( 'rest_pre_serve_request', array( $this, 'protect_served_response' ), 10, 4 );
	}

	public static function is_private_route( string $route ): bool {
		return 1 === preg_match( '~^/spdb/v1(?:/|$)~', $route );
	}

	/**
	 * @return array<string,string>
	 */
	public static function private_headers(): array {
		return array(
			'Cache-Control'        => 'private, no-store, no-cache, must-revalidate, max-age=0',
			'Pragma'               => 'no-cache',
			'X-Robots-Tag'         => 'noindex, nofollow, noarchive, nosnippet',
			'X-Content-Type-Options' => 'nosniff',
			'Referrer-Policy'      => 'same-origin',
		);
	}

	/**
	 * Attach headers to normal REST response objects.
	 *
	 * @param WP_HTTP_Response|WP_Error $response REST response.
	 * @param WP_REST_Server            $server   REST server.
	 * @param WP_REST_Request           $request  Current request.
	 * @return WP_HTTP_Response|WP_Error
	 */
	public function protect_response( $response, $server, $request ) {
		if ( ! $request instanceof WP_REST_Request || ! self::is_private_route( $request->get_route() ) ) {
			return $response;
		}

		if ( $response instanceof WP_HTTP_Response ) {
			foreach ( self::private_headers() as $name => $value ) {
				$response->header( $name, $value );
			}
		}

		return $response;
	}

	/**
	 * Emit the same policy immediately before serving every File 23 REST result,
	 * including converted WP_Error responses.
	 *
	 * @param bool             $served  Whether the response was already served.
	 * @param WP_HTTP_Response $result  REST response being served.
	 * @param WP_REST_Request  $request Current request.
	 * @param WP_REST_Server   $server  REST server.
	 */
	public function protect_served_response( bool $served, $result, $request, $server ): bool {
		if ( ! $request instanceof WP_REST_Request || ! self::is_private_route( $request->get_route() ) ) {
			return $served;
		}

		if ( ! headers_sent() ) {
			foreach ( self::private_headers() as $name => $value ) {
				header( $name . ': ' . $value, true );
			}
		}

		return $served;
	}
}
