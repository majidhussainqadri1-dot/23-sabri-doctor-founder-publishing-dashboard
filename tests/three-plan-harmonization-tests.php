<?php
/** Three-plan harmonization regression tests for File 23. */
$tests = 0; $failed = 0;
function spdb_three_plan_assert( bool $condition, string $message ): void {
	global $tests, $failed; ++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
}
$root = dirname( __DIR__ );
$resolver = (string) file_get_contents( $root . '/includes/class-spdb-workspace-resolver.php' );
$validator = (string) file_get_contents( $root . '/includes/class-spdb-operational-projection-validator.php' );
$css = (string) file_get_contents( $root . '/assets/css/dashboard-corrections.css' );
$harmonized = (string) file_get_contents( $root . '/docs/THREE-PLAN-HARMONIZATION-IMPLEMENTATION-2026-08-05.md' );
$surfaces = array( 'appointments', 'messages', 'reviews', 'followers', 'downloads', 'support', 'learning' );
foreach ( $surfaces as $surface ) {
	spdb_three_plan_assert( str_contains( $resolver, "'{$surface}'" ), "Workspace navigation must declare the {$surface} native professional surface." );
	spdb_three_plan_assert( str_contains( $validator, "'{$surface}'" ), "Projection validator must recognize the {$surface} domain." );
}
spdb_three_plan_assert( str_contains( $resolver, "apply_filters( 'spdb_native_professional_surface_contract'" ), 'Native modules must provide a versioned professional-surface contract.' );
spdb_three_plan_assert( str_contains( $resolver, "empty( $contract['accepted'] )" ), 'Unaccepted native surface providers must fail closed.' );
spdb_three_plan_assert( str_contains( $resolver, "SPDB_Capabilities::current_user_can( $capability )" ), 'Each native surface must recheck its declared capability.' );
spdb_three_plan_assert( str_contains( $resolver, "contract_version" ) && str_contains( $resolver, "provider_key" ), 'Provider identity and contract version must be mandatory.' );
spdb_three_plan_assert( str_contains( $resolver, 'same_origin_url' ) && str_contains( $resolver, "isset( $target['fragment'] )" ), 'Professional links must reject unsafe or fragment-bearing destinations.' );
spdb_three_plan_assert( ! str_contains( $resolver, "home_url( '/appointments/'" ), 'File 23 must not guess or invent native routes.' );
spdb_three_plan_assert( str_contains( $validator, 'message_text' ) && str_contains( $validator, 'national_id' ) && str_contains( $validator, 'prescription' ), 'Projection metadata filtering must cover messaging, identity and clinical secrets.' );
spdb_three_plan_assert( str_contains( $css, '--spdb-accent: #16843f' ) && str_contains( $css, '--spdb-accent-strong: #0b5f2b' ), 'The current visual constitution must use green identity tokens.' );
spdb_three_plan_assert( str_contains( $harmonized, 'All-Chats Recovered Directives' ), 'The recovered-directives plan must be named as a governing source.' );
spdb_three_plan_assert( str_contains( $harmonized, 'does not duplicate native' ), 'The harmonization record must preserve canonical native ownership.' );
spdb_three_plan_assert( str_contains( $harmonized, 'Hostinger staging' ), 'The source record must not misclassify staging acceptance as complete.' );
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} three-plan harmonization tests failed.\n" ); exit( 1 ); }
echo "All {$tests} three-plan harmonization tests passed.\n";
