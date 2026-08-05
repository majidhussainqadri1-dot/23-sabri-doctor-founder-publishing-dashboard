<?php
/** Three-plan harmonization regression tests for File 23. */
$tests = 0; $failed = 0;
function spdb_three_plan_assert( bool $condition, string $message ): void {
	global $tests, $failed; ++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
}
$root = dirname( __DIR__ );
$resolver = (string) file_get_contents( $root . '/includes/class-spdb-workspace-resolver.php' );
$registration = (string) file_get_contents( $root . '/includes/class-spdb-provider-registration.php' );
$adapter_registry = (string) file_get_contents( $root . '/includes/class-spdb-adapter-registry.php' );
$validator = (string) file_get_contents( $root . '/includes/class-spdb-operational-projection-validator.php' );
$css = (string) file_get_contents( $root . '/assets/css/dashboard-corrections.css' );
$harmonized = (string) file_get_contents( $root . '/docs/THREE-PLAN-HARMONIZATION-IMPLEMENTATION-2026-08-05.md' );
$provider_contract = (string) file_get_contents( $root . '/docs/PROFESSIONAL-SURFACE-PROVIDER-CONTRACT.md' );
$surfaces = array( 'appointments', 'messages', 'reviews', 'followers', 'downloads', 'support', 'learning' );
foreach ( $surfaces as $surface ) {
	spdb_three_plan_assert( str_contains( $resolver, "'{$surface}'" ), "Workspace navigation must declare the {$surface} native professional surface." );
	spdb_three_plan_assert( str_contains( $validator, "'{$surface}'" ), "Projection validator must recognize the {$surface} domain." );
	spdb_three_plan_assert( str_contains( $provider_contract, "`{$surface}`" ), "Provider documentation must define the {$surface} surface." );
}
spdb_three_plan_assert( str_contains( $resolver, "apply_filters( 'spdb_native_professional_surface_provider'" ), 'File 23 may select only a registered provider identifier for each professional surface.' );
spdb_three_plan_assert( ! str_contains( $resolver, "apply_filters( 'spdb_native_professional_surface_contract'" ), 'An arbitrary plugin must not inject the final professional-surface contract directly.' );
spdb_three_plan_assert( str_contains( $registration, 'private static ?SPDB_Adapter_Registry $registry' ) && str_contains( $registration, 'self::$registry = $registry' ) && str_contains( $registration, 'public static function registry()' ), 'File 23 must retain the canonical request-scoped registered adapter registry.' );
spdb_three_plan_assert( str_contains( $resolver, 'SPDB_Provider_Registration::registry()' ) && str_contains( $resolver, '$registry->get( $provider )' ) && str_contains( $resolver, 'get_professional_surface_contract' ), 'The final surface contract must come from the selected registered adapter object.' );
spdb_three_plan_assert( str_contains( $provider_contract, 'spdb_native_professional_surface_provider' ) && str_contains( $provider_contract, 'get_professional_surface_contract' ), 'Provider documentation must describe selection and adapter-owned contract retrieval.' );
spdb_three_plan_assert( str_contains( $resolver, 'catch ( Throwable $throwable )' ) && str_contains( $resolver, 'spdb_professional_surface_contract_exception' ), 'A failing professional-surface adapter must be isolated and recorded.' );
spdb_three_plan_assert( ! str_contains( $resolver, "\$contract['accepted']" ), 'A provider must not be able to self-declare acceptance in its surface contract.' );
spdb_three_plan_assert( str_contains( $provider_contract, 'Providers cannot self-approve' ) && ! str_contains( $provider_contract, "'accepted'          => true" ), 'Provider documentation must preserve File 23-owned acceptance.' );
spdb_three_plan_assert( str_contains( $resolver, 'provider_is_accepted' ) && str_contains( $resolver, 'get_acceptance_state' ) && str_contains( $resolver, 'ACCEPTANCE_PRODUCTION_ACCEPTED' ) && str_contains( $resolver, 'ACCEPTANCE_STAGING_ACCEPTED' ), 'Environment-aware File 23 acceptance must fail closed.' );
spdb_three_plan_assert( str_contains( $adapter_registry, 'bind_acceptance_record' ) && str_contains( $adapter_registry, 'hash_equals( $provider_version' ) && str_contains( $adapter_registry, 'hash_equals( SPDB_CONTRACT_VERSION' ) && str_contains( $adapter_registry, 'hash_equals( SPDB_VERSION' ), 'Registry acceptance must be bound to the exact provider, contract and File 23 versions.' );
spdb_three_plan_assert( str_contains( $resolver, 'hash_equals( $provider, $contract_provider )' ) && str_contains( $resolver, 'hash_equals( $provider_version' ), 'Surface contract identity and version must match the selected registered adapter.' );
spdb_three_plan_assert( str_contains( $resolver, 'current_user_can( $capability )' ), 'Each native surface must recheck its provider-declared WordPress capability.' );
spdb_three_plan_assert( ! str_contains( $resolver, 'SPDB_Capabilities::current_user_can( $capability )' ), 'External native capabilities must not be rejected by the File 23-only capability allowlist.' );
spdb_three_plan_assert( str_contains( $resolver, 'provider_version' ) && str_contains( $resolver, 'contract_version' ) && str_contains( $resolver, 'provider_key' ), 'Provider identity, semantic version and contract version must be mandatory.' );
spdb_three_plan_assert( str_contains( $resolver, 'is_semver' ) && str_contains( $resolver, '[0-9A-Za-z.-]+' ), 'Provider versions must support semantic prerelease/build identifiers.' );
spdb_three_plan_assert( str_contains( $resolver, "\$home['scheme']" ) && str_contains( $resolver, "\$target['scheme']" ) && str_contains( $resolver, 'normalized_port' ), 'Same-origin validation must compare scheme, host and normalized port.' );
spdb_three_plan_assert( str_contains( $resolver, "isset( \$target['fragment'] )" ) && str_contains( $resolver, "isset( \$target['user'] )" ), 'Professional links must reject fragments and embedded credentials.' );
spdb_three_plan_assert( str_contains( $provider_contract, 'same scheme, host and normalized port' ) && str_contains( $provider_contract, 'native route authorization rechecked again on arrival' ), 'Provider documentation must require exact-origin and destination-side authorization.' );
spdb_three_plan_assert( ! str_contains( $resolver, "home_url( '/appointments/'" ), 'File 23 must not guess or invent native routes.' );
spdb_three_plan_assert( str_contains( $validator, 'message_text' ) && str_contains( $validator, 'national_id' ) && str_contains( $validator, 'prescription' ), 'Projection metadata filtering must cover messaging, identity and clinical secrets.' );
spdb_three_plan_assert( str_contains( $provider_contract, 'must not include patient information' ) && str_contains( $provider_contract, 'payment data' ), 'Provider documentation must prohibit sensitive contract payloads.' );
spdb_three_plan_assert( str_contains( $css, '--spdb-accent: #16843f' ) && str_contains( $css, '--spdb-accent-strong: #0b5f2b' ), 'The current visual constitution must use green identity tokens.' );
spdb_three_plan_assert( str_contains( $harmonized, 'All-Chats Recovered Directives' ), 'The recovered-directives plan must be named as a governing source.' );
spdb_three_plan_assert( str_contains( $harmonized, 'does not duplicate native' ), 'The harmonization record must preserve canonical native ownership.' );
spdb_three_plan_assert( str_contains( $harmonized, 'Hostinger staging' ), 'The source record must not misclassify staging acceptance as complete.' );
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} three-plan harmonization tests failed.\n" ); exit( 1 ); }
echo "All {$tests} three-plan harmonization tests passed.\n";
