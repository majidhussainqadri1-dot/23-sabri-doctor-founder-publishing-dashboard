<?php
/** Permanent gate for the completed 2026-08-05 ten-round review. */
$root = dirname( __DIR__ );
$tests = 0;
$failed = 0;
$assert = static function ( bool $ok, string $message ) use ( &$tests, &$failed ): void {
    ++$tests;
    if ( ! $ok ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};
$read = static function ( string $path ) use ( $root ): string { return (string) file_get_contents( $root . '/' . $path ); };
$main = $read( 'sabri-publishing-dashboard.php' );
$audit = $read( 'docs/AUDIT-10-ROUND-REVIEW-AND-CORRECTIONS-2026-08-05.md' );
$build = $read( 'tools/build-final-release.sh' );
$controller = $read( 'includes/class-spdb-operations-rest-controller.php' );
$resolver = $read( 'includes/class-spdb-workspace-resolver.php' );
$validator = $read( 'includes/class-spdb-operational-projection-validator.php' );
$guard = $read( 'includes/class-spdb-operational-mutation-guard.php' );
$jobs = $read( 'includes/class-spdb-background-jobs.php' );

$assert( 10 === preg_match_all( '/^## Round (?:10|[1-9]) /m', $audit ), 'Historical audit must retain exactly ten rounds.' );
$assert( str_contains( $audit, 'Version 1.2.2' ), 'Historical audit must retain Version 1.2.2 as its 2026-08-05 baseline.' );
$current = '';
if ( preg_match( "/define\( 'SPDB_VERSION', '([^']+)' \)/", $main, $match ) ) { $current = (string) $match[1]; }
$assert( '' !== $current && version_compare( $current, '1.2.2', '>=' ), 'Later corrected releases may advance but must not regress below 1.2.2.' );
$assert( str_contains( $build, 'version="' . $current . '"' ), 'Build version must match the current runtime version.' );
foreach ( array( 'tasks_permission', 'delegations_permission', 'rules_permission', 'exports_permission', 'ai_permission' ) as $callback ) {
    $assert( str_contains( $controller, "'{$callback}'" ), "Resource-specific gate missing: {$callback}." );
}
foreach ( array( 'appointments', 'messages', 'reviews', 'followers', 'downloads', 'support', 'learning' ) as $domain ) {
    $assert( str_contains( $controller, $domain ), "Operational domain missing: {$domain}." );
}
$assert( str_contains( $resolver, 'SPDB_Safe_Destination::normalize' ), 'Professional destinations must use centralized safe normalization.' );
$assert( str_contains( $validator, 'SPDB_Safe_Destination::normalize' ), 'Projection destinations must use centralized safe normalization.' );
$assert( str_contains( $guard, 'spdb_mutation_idempotency_mismatch' ), 'Mutation replay protection must remain present.' );
$assert( str_contains( $jobs, 'wp_clear_scheduled_hook( self::HOOK )' ), 'Background lifecycle cleanup must remain present.' );

if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} historical ten-round tests failed.\n" ); exit( 1 ); }
echo "All {$tests} historical ten-round tests passed.\n";
