<?php
/**
 * Executable tests for File 23 REST privacy routing and headers.
 */

require_once __DIR__ . '/bootstrap.php';

$cases = array(
	'/spdb/v1'                   => true,
	'/spdb/v1/'                  => true,
	'/spdb/v1/saved-views'       => true,
	'/spdb/v10/saved-views'      => false,
	'/wp/v2/users'               => false,
	'/other/spdb/v1/saved-views' => false,
);

$failed = 0;
function spdb_rest_assert( bool $condition, string $message ): void {
	global $failed;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

foreach ( $cases as $route => $expected ) {
	spdb_rest_assert( $expected === SPDB_REST_Privacy::is_private_route( $route ), "REST privacy scope mismatch for {$route}." );
}

$headers = SPDB_REST_Privacy::private_headers();
spdb_rest_assert( isset( $headers['Cache-Control'] ) && false !== strpos( $headers['Cache-Control'], 'no-store' ), 'Private REST policy must prohibit storage.' );
spdb_rest_assert( isset( $headers['X-Robots-Tag'] ) && false !== strpos( $headers['X-Robots-Tag'], 'noindex' ), 'Private REST policy must prohibit indexing.' );

$privacy  = new SPDB_REST_Privacy();
$response = new WP_HTTP_Response();
$request  = new WP_REST_Request( '/spdb/v1/saved-views' );
$result   = $privacy->protect_response( $response, null, $request );
spdb_rest_assert( $result === $response, 'REST response object must be preserved.' );
spdb_rest_assert( isset( $response->headers['Cache-Control'] ), 'Normal File 23 REST responses must receive private cache headers.' );
spdb_rest_assert( isset( $response->headers['Referrer-Policy'] ), 'Normal File 23 REST responses must receive referrer protection.' );

$error = new WP_Error( 'spdb_test_error', 'Denied.' );
spdb_rest_assert( $error === $privacy->protect_response( $error, null, $request ), 'Error responses must remain errors before WordPress conversion.' );
spdb_rest_assert( false === $privacy->protect_served_response( false, new WP_HTTP_Response(), new WP_REST_Request( '/wp/v2/posts' ), null ), 'Unrelated REST routes must remain untouched.' );

if ( $failed > 0 ) {
	exit( 1 );
}

echo 'All File 23 REST privacy tests passed.' . PHP_EOL;
