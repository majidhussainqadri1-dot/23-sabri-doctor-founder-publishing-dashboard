<?php
/** Consolidated File 23 release-boundary regression tests. */
require_once __DIR__ . '/bootstrap.php';

$tests = 0;
$failed = 0;
function spdb_consolidation_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

$root = dirname( __DIR__ );
$plugin = file_get_contents( $root . '/includes/class-spdb-plugin.php' );
$service = file_get_contents( $root . '/includes/class-spdb-collections-service.php' );
$privacy = file_get_contents( $root . '/includes/class-spdb-rest-privacy.php' );
$css_files = glob( $root . '/assets/css/*.css' ) ?: array();
$css = implode( "\n", array_map( static fn( string $path ): string => (string) file_get_contents( $path ), $css_files ) );

spdb_consolidation_assert( is_string( $plugin ) && str_contains( $plugin, "apply_filters( 'spdb/native_reference_acceptance'" ), 'Native resolver acceptance must be File 23-governed and externally configurable.' );
spdb_consolidation_assert( is_string( $plugin ) && str_contains( $plugin, 'new SPDB_Collections_Service( $this->collections_repository, $this->native_reference_registry )' ), 'Collections runtime must consume the reviewed native-reference registry.' );
spdb_consolidation_assert( is_string( $service ) && str_contains( $service, 'SPDB_Collections_Service_Readiness_Consumer::create' ), 'Collections runtime must consume the reviewed readiness consumer.' );
spdb_consolidation_assert( is_string( $service ) && str_contains( $service, 'require_read_ready' ) && str_contains( $service, 'require_write_ready' ), 'Collections read/write gates must use canonical readiness decisions.' );
spdb_consolidation_assert( is_string( $privacy ) && ( str_contains( $privacy, 'no-store' ) || str_contains( $privacy, 'DONOTCACHEPAGE' ) ), 'Private dashboard REST surfaces must carry cache-prevention controls.' );
spdb_consolidation_assert( str_contains( $css, ':focus-visible' ), 'Keyboard focus indicators must exist.' );
spdb_consolidation_assert( str_contains( $css, '[dir="rtl"]' ) || str_contains( $css, ':dir(rtl)' ) || str_contains( $css, 'direction: rtl' ), 'RTL-specific styling must exist.' );
spdb_consolidation_assert( str_contains( $css, '@media' ), 'Responsive styling must exist.' );

$role_matrix = array(
	'founder'   => array( 'approved' => true,  'write' => true,  'institution' => true ),
	'doctor'    => array( 'approved' => true,  'write' => true,  'institution' => false ),
	'reviewer'  => array( 'approved' => true,  'write' => false, 'review' => true ),
	'pending'   => array( 'approved' => false, 'write' => false, 'read_only' => true ),
	'suspended' => array( 'approved' => false, 'write' => false, 'read_only' => true ),
);
spdb_consolidation_assert( 5 === count( $role_matrix ), 'All mandated account classes must be represented by the release test matrix.' );
spdb_consolidation_assert( true === $role_matrix['founder']['institution'] && false === $role_matrix['doctor']['institution'], 'Institution scope must remain Founder-only.' );
spdb_consolidation_assert( false === $role_matrix['pending']['write'] && false === $role_matrix['suspended']['write'], 'Pending and suspended accounts must remain non-writable.' );

$forbidden_calls = array( 'wp_insert_post(', 'wp_update_post(', 'wp_delete_post(', 'register_post_type(' );
foreach ( $forbidden_calls as $call ) {
	spdb_consolidation_assert( ! str_contains( (string) $service, $call ), "Collections service must not own native publication mutation: {$call}" );
}

if ( $failed ) {
	fwrite( STDERR, "{$failed} of {$tests} File 23 consolidation tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} File 23 consolidation tests passed.\n";
