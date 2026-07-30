<?php
/**
 * Executable tests for File 23 REST privacy route matching.
 */

require_once __DIR__ . '/bootstrap.php';

$cases = array(
	'/spdb/v1'                    => true,
	'/spdb/v1/'                   => true,
	'/spdb/v1/saved-views'        => true,
	'/spdb/v10/saved-views'       => false,
	'/wp/v2/users'                => false,
	'/other/spdb/v1/saved-views'  => false,
);

$failed = 0;
foreach ( $cases as $route => $expected ) {
	$actual = SPDB_REST_Privacy::is_private_route( $route );
	if ( $expected !== $actual ) {
		++$failed;
		fwrite( STDERR, "FAIL: REST privacy scope mismatch for {$route}.\n" );
	}
}

if ( $failed > 0 ) {
	exit( 1 );
}

echo 'All File 23 REST privacy route tests passed.' . PHP_EOL;
