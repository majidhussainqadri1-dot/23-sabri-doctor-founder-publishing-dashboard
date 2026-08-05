<?php
/** Executable source gate for the 2026-08-05 ten-round corrective review. */
$tests = 0; $failed = 0;
function spdb_ten_round_assert( bool $condition, string $message ): void {
	global $tests, $failed; ++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
}
function spdb_ten_round_source( string $path ): string {
	$value = file_get_contents( dirname( __DIR__ ) . '/' . $path );
	return is_string( $value ) ? $value : '';
}

$main = spdb_ten_round_source( 'sabri-publishing-dashboard.php' );
$controller = spdb_ten_round_source( 'includes/class-spdb-operations-rest-controller.php' );
$adapter_registry = spdb_ten_round_source( 'includes/class-spdb-adapter-registry.php' );
$native_registry = spdb_ten_round_source( 'includes/class-spdb-native-reference-registry.php' );
$resolver = spdb_ten_round_source( 'includes/class-spdb-workspace-resolver.php' );
$validator = spdb_ten_round_source( 'includes/class-spdb-operational-projection-validator.php' );
$service = spdb_ten_round_source( 'includes/class-spdb-operations-service.php' );
$guard = spdb_ten_round_source( 'includes/class-spdb-operational-mutation-guard.php' );
$jobs = spdb_ten_round_source( 'includes/class-spdb-background-jobs.php' );
$plugin = spdb_ten_round_source( 'includes/class-spdb-plugin.php' );
$operations_js = spdb_ten_round_source( 'assets/js/operations.js' );
$dashboard_js = spdb_ten_round_source( 'assets/js/dashboard.js' );
$build = spdb_ten_round_source( 'tools/build-final-release.sh' );
$audit = spdb_ten_round_source( 'docs/AUDIT-10-ROUND-REVIEW-AND-CORRECTIONS-2026-08-05.md' );

spdb_ten_round_assert( str_contains( $audit, 'Round 1 — Branch convergence' ) && str_contains( $audit, 'File 21/File 22' ), 'Round 1 must preserve the previously reviewed live provider integration.' );
foreach ( array( 'tasks_permission', 'delegations_permission', 'rules_permission', 'exports_permission', 'ai_permission' ) as $callback ) {
	spdb_ten_round_assert( str_contains( $controller, "'{$callback}'" ), "Round 2 must route {$callback} through a resource-specific capability gate." );
}
spdb_ten_round_assert( str_contains( $adapter_registry, 'MAX_TOTAL_ERRORS = 64' ) && str_contains( $native_registry, 'MAX_TOTAL_ERRORS = 64' ), 'Round 3 must bound both provider error registries.' );
spdb_ten_round_assert( str_contains( $adapter_registry, 'bounded provider integration error' ) && ! str_contains( $adapter_registry, '$this->registration_errors[ $key ][] = $error' ), 'Round 3 must replace provider-controlled error text.' );
spdb_ten_round_assert( str_contains( $resolver, 'SPDB_Safe_Destination::normalize' ) && str_contains( $validator, 'SPDB_Safe_Destination::normalize' ), 'Round 4 must centralize exact-origin and secret-free destination validation.' );
spdb_ten_round_assert( str_contains( $validator, 'spdb_projection_invalid_page_item' ) && str_contains( $validator, 'SPDB_Projection_Validator::valid_object_id' ), 'Round 5 must reject invalid projection pages and native identifiers.' );
spdb_ten_round_assert( ! str_contains( $service, "strtotime( (string) \$snapshot['generated_at_gmt'] ) ?: time()" ), 'Round 5 must not fabricate current timestamps for corrupt cached analytics.' );
spdb_ten_round_assert( str_contains( $guard, 'spdb_mutation_idempotency_mismatch' ) && str_contains( $guard, 'saved-views(?:/view_' ), 'Round 6 must unify idempotency transports and guard saved-view mutations.' );
spdb_ten_round_assert( ! str_contains( $operations_js, 'Math.random' ) && str_contains( $operations_js, 'data-spdb-submitting' ) && str_contains( $dashboard_js, "'Idempotency-Key'" ), 'Round 7 must fail closed without Web Crypto and prevent re-entrant form submissions.' );
$constructor_start = strpos( $plugin, 'new SPDB_Dashboard_Page(' );
$constructor_end = false === $constructor_start ? false : strpos( $plugin, '$this->dashboard_router', $constructor_start );
$constructor = false === $constructor_start || false === $constructor_end ? '' : substr( $plugin, $constructor_start, $constructor_end - $constructor_start );
spdb_ten_round_assert( '' !== $constructor && ! str_contains( $constructor, '$this->adapter_acceptance' ), 'Round 7 must pass the dashboard renderer its exact constructor dependencies.' );
spdb_ten_round_assert( str_contains( $guard, "wp_clear_scheduled_hook( 'spdb_mutation_guard_cleanup' )" ) && str_contains( $jobs, 'wp_clear_scheduled_hook( self::HOOK )' ), 'Round 8 must clear every scheduled lifecycle event during deactivation.' );
$domains = array( 'appointments', 'messages', 'reviews', 'followers', 'downloads', 'support', 'learning' );
foreach ( $domains as $domain ) { spdb_ten_round_assert( str_contains( $controller, $domain ), "Round 9 must expose the {$domain} projection route." ); }
spdb_ten_round_assert( str_contains( $service, 'spdb_projection_provider_domains_invalid' ) && ! str_contains( $service, "array_map( 'sanitize_key', \$domains )" ), 'Round 9 must reject non-canonical provider domain declarations instead of normalizing them.' );
spdb_ten_round_assert( str_contains( $main, 'Version:     1.2.2' ) && str_contains( $main, "SPDB_VERSION', '1.2.2" ), 'Round 10 must identify the combined corrective candidate as Version 1.2.2.' );
spdb_ten_round_assert( 2 === substr_count( $build, '1\.2\.2$' ) && ! str_contains( $build, '1\.2\.1$' ), 'Round 10 package metadata checks must verify Version 1.2.2 rather than the superseded escaped pattern.' );
spdb_ten_round_assert( 10 === preg_match_all( '/^## Round (?:10|[1-9]) /m', $audit ), 'The corrective record must contain exactly ten numbered review rounds.' );

if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} ten-round corrective review tests failed.\n" ); exit( 1 ); }
echo "All {$tests} ten-round corrective review tests passed.\n";
