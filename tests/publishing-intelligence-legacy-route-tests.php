<?php
/** File 23 legacy feature-ID REST compatibility and authorization regressions. */
$GLOBALS['spdb_registered_intelligence_routes'] = array();
function register_rest_route( $namespace, $route, $options ) {
    $GLOBALS['spdb_registered_intelligence_routes'][ $namespace . $route ] = $options;
}
require __DIR__ . '/publishing-intelligence-security-regression-tests.php';

SPDB_Publishing_Intelligence::register_rest_routes();
$routes = $GLOBALS['spdb_registered_intelligence_routes'];
$legacy_pattern = '/spdb/v1/intelligence/(?P<feature_id>F23-FPI-[0-9]{2})';
$slug_pattern = '/spdb/v1/intelligence/(?P<feature>[a-z0-9-]+)';
$assert( isset( $routes[ $legacy_pattern ], $routes[ $slug_pattern ] ), 'Both legacy-ID and canonical-slug GET routes must be registered.' );
$assert( 'rest_can_view' === $routes[ $legacy_pattern ]['permission_callback'][1], 'Legacy-ID GET must use the same authorization callback as slug GET.' );
$assert( 'rest_snapshot' === $routes[ $legacy_pattern ]['callback'][1], 'Legacy-ID GET must use the same canonical snapshot handler.' );

$GLOBALS['spdb_test_founder'] = false;
$GLOBALS['spdb_test_institutional'] = false;
$GLOBALS['spdb_test_caps']['spdb_view_dashboard'] = true;
$GLOBALS['spdb_test_caps']['spdb_view_own_analytics'] = true;
$GLOBALS['spdb_test_caps']['spdb_view_global_analytics'] = false;
$ordinary = new SPDB_Test_Request( array( 'scope' => 'own', 'feature' => 'mission-control' ), '/spdb/v1/intelligence/F23-FPI-01' );
$assert( SPDB_Publishing_Intelligence::rest_can_view( $ordinary ), 'Ordinary authorized feature ID 01 remains accessible.' );
$legacy_founder = new SPDB_Test_Request( array( 'scope' => 'own', 'feature' => 'mission-control' ), '/spdb/v1/intelligence/F23-FPI-12' );
$assert( ! SPDB_Publishing_Intelligence::rest_can_view( $legacy_founder ), 'Spoofed legacy feature ID 12 cannot bypass Founder-only permission simulator.' );
$legacy_unknown = new SPDB_Test_Request( array( 'scope' => 'own', 'feature' => 'mission-control' ), '/spdb/v1/intelligence/F23-FPI-99' );
$assert( ! SPDB_Publishing_Intelligence::rest_can_view( $legacy_unknown ), 'Unknown legacy IDs fail closed before snapshot.' );
$legacy_whatif = new SPDB_Test_Request( array( 'scope' => 'own', 'feature' => 'mission-control' ), '/spdb/v1/intelligence/F23-FPI-24' );
$assert( ! SPDB_Publishing_Intelligence::rest_can_view( $legacy_whatif ), 'Legacy what-if ID requires elevated authority.' );
$GLOBALS['spdb_test_founder'] = true;
$assert( SPDB_Publishing_Intelligence::rest_can_view( $legacy_founder ), 'Founder may use legacy permission simulator route.' );
$assert( SPDB_Publishing_Intelligence::rest_can_view( $legacy_whatif ), 'Founder may use legacy what-if route.' );
$GLOBALS['spdb_test_founder'] = false;
$GLOBALS['spdb_test_caps']['spdb_view_global_analytics'] = true;
$GLOBALS['spdb_test_institutional'] = true;
$assert( SPDB_Publishing_Intelligence::rest_can_view( $legacy_whatif ), 'Approved institutional global operator may use legacy what-if route.' );
$assert( ! SPDB_Publishing_Intelligence::rest_can_view( $legacy_founder ), 'Institutional global operator cannot impersonate Founder on legacy ID 12.' );
$GLOBALS['spdb_test_institutional'] = false;
$GLOBALS['spdb_test_caps']['spdb_view_global_analytics'] = false;
$spoofed_slug = new SPDB_Test_Request( array( 'scope' => 'own', 'feature' => 'mission-control' ), '/spdb/v1/intelligence/permission-simulator' );
$assert( ! SPDB_Publishing_Intelligence::rest_can_view( $spoofed_slug ), 'Canonical slug simulator remains Founder-only under feature spoofing.' );
$spoofed_simulate = new SPDB_Test_Request( array( 'scope' => 'own', 'feature' => 'mission-control' ), '/spdb/v1/intelligence/simulate' );
$assert( ! SPDB_Publishing_Intelligence::rest_can_view( $spoofed_simulate ), 'POST simulate route remains elevated under feature spoofing.' );
if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} security assertions failed.\n" ); exit( 1 ); }
echo "File 23 legacy-ID route security compatibility: PASS.\n";
