<?php
/** Regression tests for malformed dashboard view selectors. */
require_once __DIR__ . '/bootstrap.php';

$tests = 0;
$failed = 0;
function spdb_router_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

$_GET['view'] = array( 'collections' );
spdb_router_assert( 'overview' === SPDB_Dashboard_Router::current_view(), 'Array-valued view selectors must fail closed to Overview without sanitization warnings.' );
$_GET['view'] = 'collections';
spdb_router_assert( 'collections' === SPDB_Dashboard_Router::current_view(), 'A canonical scalar Collections selector must remain available.' );
unset( $_GET['view'] );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} dashboard-router regression tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} dashboard-router regression tests passed.\n";
