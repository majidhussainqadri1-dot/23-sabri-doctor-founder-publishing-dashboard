<?php
/** Executable provider registration, acceptance, isolation, privacy, and routing tests. */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/fixtures/class-test-adapter.php';
require_once __DIR__ . '/fixtures/class-test-native-reference-provider.php';
if ( ! defined( 'SMC_VERSION' ) ) { define( 'SMC_VERSION', '1.0.1' ); }
if ( ! function_exists( 'smc_user_status' ) ) { function smc_user_status( $user_id ) { return (string) ( $GLOBALS['spdb_test_member_statuses'][ $user_id ] ?? $GLOBALS['spdb_test_member_status'] ); } }
if ( ! function_exists( 'smc_is_founder' ) ) { function smc_is_founder( $user_id ) { return (bool) $GLOBALS['spdb_test_founder']; } }
if ( ! function_exists( 'smc_is_trusted_publisher' ) ) { function smc_is_trusted_publisher( $user_id ) { return false; } }

$tests = 0;
$failed = 0;
function spdb_23g_assert( bool $condition, string $message ): void { global $tests, $failed; ++$tests; if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); } }
function spdb_23g_code( $value ): string { return $value instanceof WP_Error ? $value->get_error_code() : ''; }
/** @param SPDB_Provider_Adapter[] $extra @param array<string,string> $extra_acceptance */
function spdb_23g_adapter_registry( string $acceptance = 'staging_accepted', array $extra = array(), array $extra_acceptance = array() ): SPDB_Adapter_Registry {
	$map = '' === $acceptance ? array() : array( 'provider_one' => $acceptance );
	$registry = new SPDB_Adapter_Registry( array_merge( $map, $extra_acceptance ) );
	$registry->register( new SPDB_Test_Adapter() );
	foreach ( $extra as $adapter ) { $registry->register( $adapter ); }
	return $registry;
}
/** @return array<string,mixed> */
function spdb_23g_context( string $scope = 'own' ): array {
	return array(
		'user_id' => get_current_user_id(),
		'scope' => $scope,
		'is_founder' => SPDB_Membership_Guard::is_user_founder( get_current_user_id() ),
		'environment' => wp_get_environment_type(),
		'generated_at' => gmdate( 'c' ),
	);
}

$GLOBALS['spdb_test_environment'] = 'staging';
$GLOBALS['spdb_test_user_id'] = 7;
$GLOBALS['spdb_test_member_status'] = 'approved';
$GLOBALS['spdb_test_member_statuses'][7] = 'approved';
/* Positive resolver-routing assertions must reach provider governance under a current canonical publishing identity. */
$GLOBALS['spdb_test_founder'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_manage_own_content'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_manage_campaigns'] = false;

$adapters = spdb_23g_adapter_registry();
$provider = new SPDB_Test_Native_Reference_Provider();
$default_denied = new SPDB_Native_Reference_Registry( $adapters );
spdb_23g_assert( true === $default_denied->register( $provider ), 'A technically valid resolver may register without self-accepting.' );
spdb_23g_assert( false === $default_denied->is_ready(), 'Resolver acceptance must default to denied.' );
$denied = $default_denied->resolve_reference( 'provider_one', 'publication', 'post-101', spdb_23g_context() );
spdb_23g_assert( 'spdb_native_resolver_provider_not_ready' === spdb_23g_code( $denied ) && 0 === $provider->resolve_calls, 'An unaccepted resolver must fail before provider execution.' );

$unaccepted_adapter_provider = new SPDB_Test_Native_Reference_Provider();
$unaccepted_adapter = new SPDB_Native_Reference_Registry( spdb_23g_adapter_registry( '' ), array( 'provider_one' => SPDB_Native_Reference_Registry::ACCEPTANCE_STAGING_ACCEPTED ) );
$unaccepted_adapter->register( $unaccepted_adapter_provider );
spdb_23g_assert( false === $unaccepted_adapter->is_ready(), 'Resolver acceptance must not bypass File 23 adapter acceptance.' );
$unaccepted_snapshot = $unaccepted_adapter->health_snapshot();
spdb_23g_assert( 'unreviewed' === $unaccepted_snapshot['providers'][0]['adapter_acceptance_state'], 'Diagnostics must distinguish adapter acceptance from resolver acceptance.' );

$malformed_governance = new SPDB_Native_Reference_Registry( $adapters, array( 'provider_one' => 'self_accepted' ) );
$malformed_governance->register( new SPDB_Test_Native_Reference_Provider() );
$malformed_snapshot = $malformed_governance->health_snapshot();
spdb_23g_assert( 1 === $malformed_snapshot['registration_errors'] && false === $malformed_snapshot['ready'], 'Malformed governance acceptance must be recorded and remain denied.' );

$accepted_provider = new SPDB_Test_Native_Reference_Provider( array( 'extra_response' => true ) );
$accepted = new SPDB_Native_Reference_Registry( $adapters, array( 'provider_one' => SPDB_Native_Reference_Registry::ACCEPTANCE_STAGING_ACCEPTED ) );
spdb_23g_assert( true === $accepted->register( $accepted_provider ) && true === $accepted->is_ready(), 'A healthy adapter-accepted and resolver-accepted provider must become ready in staging.' );
$resolved = $accepted->resolve_reference( 'provider_one', 'publication', 'post-101', spdb_23g_context() );
spdb_23g_assert( is_array( $resolved ) && 'provider_one' === $resolved['provider_key'] && 1 === $accepted_provider->resolve_calls, 'An accepted resolver must route the exact provider reference.' );
spdb_23g_assert( is_array( $resolved ) && ! array_key_exists( 'patient_secret', $resolved ) && array_keys( $resolved ) === array( 'provider_key', 'object_type', 'object_id', 'exists', 'visible', 'reference_allowed', 'owner_user_id', 'native_version', 'scope', 'destination' ), 'The registry must reconstruct an exact safe response instead of relaying provider fields.' );
$accepted->health_snapshot(); $accepted->health_snapshot();
spdb_23g_assert( 1 === $accepted_provider->health_calls, 'Resolver health must be cached per registry request.' );

$context_provider = new SPDB_Test_Native_Reference_Provider();
$context_registry = new SPDB_Native_Reference_Registry( $adapters, array( 'provider_one' => 'staging_accepted' ) );
$context_registry->register( $context_provider );
$forged = spdb_23g_context(); $forged['user_id'] = 99;
spdb_23g_assert( 'spdb_native_resolver_context_invalid' === spdb_23g_code( $context_registry->resolve_reference( 'provider_one', 'publication', 'post-101', $forged ) ) && 0 === $context_provider->resolve_calls, 'A forged current-user context must fail before provider execution.' );
$extra_context = spdb_23g_context(); $extra_context['role'] = 'founder';
spdb_23g_assert( 'spdb_native_resolver_context_invalid' === spdb_23g_code( $context_registry->resolve_reference( 'provider_one', 'publication', 'post-101', $extra_context ) ), 'Unknown context authority fields must be rejected.' );
$institution_context = spdb_23g_context( 'institution' );
spdb_23g_assert( 'spdb_native_resolver_context_forbidden' === spdb_23g_code( $context_registry->resolve_reference( 'provider_one', 'publication', 'post-101', $institution_context ) ), 'Institution resolution still requires the distinct campaign capability even for Founder authority.' );

$GLOBALS['spdb_test_environment'] = 'production';
$production_adapters = spdb_23g_adapter_registry( 'production_accepted' );
$production_denied = new SPDB_Native_Reference_Registry( $production_adapters, array( 'provider_one' => SPDB_Native_Reference_Registry::ACCEPTANCE_STAGING_ACCEPTED ) );
$production_denied->register( new SPDB_Test_Native_Reference_Provider() );
spdb_23g_assert( false === $production_denied->is_ready(), 'Staging resolver acceptance must not authorize production resolution.' );
$production_accepted = new SPDB_Native_Reference_Registry( $production_adapters, array( 'provider_one' => SPDB_Native_Reference_Registry::ACCEPTANCE_PRODUCTION_ACCEPTED ) );
$production_accepted->register( new SPDB_Test_Native_Reference_Provider() );
spdb_23g_assert( true === $production_accepted->is_ready(), 'Production acceptance on both adapter and resolver may authorize a healthy resolver.' );
$GLOBALS['spdb_test_environment'] = 'staging';

$missing_adapter = new SPDB_Native_Reference_Registry( new SPDB_Adapter_Registry(), array( 'provider_one' => 'staging_accepted' ) );
spdb_23g_assert( 'spdb_native_resolver_adapter_missing' === spdb_23g_code( $missing_adapter->register( new SPDB_Test_Native_Reference_Provider() ) ), 'Resolver registration must require an existing adapter.' );
$version_mismatch = new SPDB_Native_Reference_Registry( $adapters );
spdb_23g_assert( 'spdb_native_resolver_provider_version_mismatch' === spdb_23g_code( $version_mismatch->register( new SPDB_Test_Native_Reference_Provider( array( 'provider_version' => '2.0.0' ) ) ) ), 'Resolver and adapter provider versions must match exactly.' );
$type_widening = new SPDB_Native_Reference_Registry( $adapters );
spdb_23g_assert( 'spdb_native_resolver_object_type_invalid' === spdb_23g_code( $type_widening->register( new SPDB_Test_Native_Reference_Provider( array( 'object_types' => array( 'publication', 'patient_record' ) ) ) ), 'A resolver may not widen its adapter object types.' );
$duplicate = new SPDB_Native_Reference_Registry( $adapters );
$duplicate->register( new SPDB_Test_Native_Reference_Provider() );
spdb_23g_assert( 'spdb_native_resolver_duplicate' === spdb_23g_code( $duplicate->register( new SPDB_Test_Native_Reference_Provider() ) ), 'Duplicate provider resolver registration must conflict deterministically.' );

$unhealthy = new SPDB_Native_Reference_Registry( $adapters, array( 'provider_one' => 'staging_accepted' ) );
$unhealthy_provider = new SPDB_Test_Native_Reference_Provider( array( 'healthy' => false, 'health_code' => 'degraded' ) );
$unhealthy->register( $unhealthy_provider );
spdb_23g_assert( false === $unhealthy->is_ready(), 'An unhealthy accepted resolver must remain unavailable.' );
$invalid_health = new SPDB_Native_Reference_Registry( $adapters, array( 'provider_one' => 'staging_accepted' ) );
$invalid_health->register( new SPDB_Test_Native_Reference_Provider( array( 'health_code' => 'BAD CODE' ) ) );
$invalid_snapshot = $invalid_health->health_snapshot();
spdb_23g_assert( false === $invalid_snapshot['ready'] && 'invalid_health' === $invalid_snapshot['providers'][0]['code'], 'Malformed health output must be reconstructed as a bounded invalid state.' );

$exception_registry = new SPDB_Native_Reference_Registry( $adapters, array( 'provider_one' => 'staging_accepted' ) );
$exception_registry->register( new SPDB_Test_Native_Reference_Provider( array( 'resolve_exception' => true ) ) );
spdb_23g_assert( 'spdb_native_resolver_provider_exception' === spdb_23g_code( $exception_registry->resolve_reference( 'provider_one', 'publication', 'post-101', spdb_23g_context() ) ), 'A provider exception must be isolated and translated.' );
$error_registry = new SPDB_Native_Reference_Registry( $adapters, array( 'provider_one' => 'staging_accepted' ) );
$error_registry->register( new SPDB_Test_Native_Reference_Provider( array( 'resolve_error' => true ) ) );
$provider_error = $error_registry->resolve_reference( 'provider_one', 'publication', 'post-101', spdb_23g_context() );
spdb_23g_assert( 'spdb_native_resolver_provider_error' === spdb_23g_code( $provider_error ) && false === strpos( $provider_error->get_error_message(), 'patient' ), 'Provider errors must be replaced with bounded non-sensitive errors.' );
$invalid_response_registry = new SPDB_Native_Reference_Registry( $adapters, array( 'provider_one' => 'staging_accepted' ) );
$invalid_response_registry->register( new SPDB_Test_Native_Reference_Provider( array( 'invalid_response' => true ) ) );
spdb_23g_assert( 'spdb_native_resolver_response_invalid' === spdb_23g_code( $invalid_response_registry->resolve_reference( 'provider_one', 'publication', 'post-101', spdb_23g_context() ) ), 'A non-array resolver response must fail closed.' );
$mismatch_registry = new SPDB_Native_Reference_Registry( $adapters, array( 'provider_one' => 'staging_accepted' ) );
$mismatch_registry->register( new SPDB_Test_Native_Reference_Provider( array( 'mismatch_response' => true ) ) );
spdb_23g_assert( 'spdb_native_resolver_response_mismatch' === spdb_23g_code( $mismatch_registry->resolve_reference( 'provider_one', 'publication', 'post-101', spdb_23g_context() ) ), 'A resolver may not redirect a request to another provider reference.' );
$scope_registry = new SPDB_Native_Reference_Registry( $adapters, array( 'provider_one' => 'staging_accepted' ) );
$scope_registry->register( new SPDB_Test_Native_Reference_Provider( array( 'scope_mismatch' => true ) ) );
spdb_23g_assert( 'spdb_native_resolver_response_mismatch' === spdb_23g_code( $scope_registry->resolve_reference( 'provider_one', 'publication', 'post-101', spdb_23g_context() ) ), 'A provider response must preserve the exact authorized scope.' );
$destination_registry = new SPDB_Native_Reference_Registry( $adapters, array( 'provider_one' => 'staging_accepted' ) );
$destination_registry->register( new SPDB_Test_Native_Reference_Provider( array( 'unsafe_destination' => true ) ) );
spdb_23g_assert( 'spdb_native_resolver_destination_invalid' === spdb_23g_code( $destination_registry->resolve_reference( 'provider_one', 'publication', 'post-101', spdb_23g_context() ) ), 'Unsafe cross-origin destinations must fail before projection.' );

$second_adapter = new SPDB_Test_Adapter( array( 'provider_key' => 'provider_two', 'provider_name' => 'Provider Two' ) );
$dispatch_adapters = spdb_23g_adapter_registry( 'staging_accepted', array( $second_adapter ), array( 'provider_two' => 'staging_accepted' ) );
$dispatch_registry = new SPDB_Native_Reference_Registry( $dispatch_adapters );
$GLOBALS['wp_filter'][ SPDB_Native_Reference_Registration::HOOK ] = array(
	static function () { throw new RuntimeException( 'Synthetic registration callback failure.' ); },
	static function ( $registry ) { $registry->register( new SPDB_Test_Native_Reference_Provider( array( 'provider_key' => 'provider_two' ) ) ); },
);
SPDB_Native_Reference_Registration::dispatch( $dispatch_registry );
spdb_23g_assert( true === $dispatch_registry->has( 'provider_two' ), 'A failing registration callback must not prevent later resolvers from registering.' );
$dispatch_snapshot = $dispatch_registry->health_snapshot();
spdb_23g_assert( 1 === $dispatch_snapshot['registration_errors'], 'Registration callback failures must appear only as bounded error counts.' );
spdb_23g_assert( ! array_key_exists( 'ignored_sensitive_detail', $dispatch_snapshot['providers'][0] ), 'Resolver diagnostics must reconstruct rather than relay provider health payloads.' );
$recorded_errors = $dispatch_registry->registration_errors();
spdb_23g_assert( false === strpos( $recorded_errors['system'][0]->get_error_message(), 'Synthetic' ), 'Stored registration errors must not retain callback or provider details.' );
for ( $i = 0; $i < 100; ++$i ) { $dispatch_registry->record_error( 'provider_two', new WP_Error( 'spdb_native_synthetic', 'Patient detail ' . $i ) ); }
$native_bounded = $dispatch_registry->registration_errors()['provider_two'] ?? array();
spdb_23g_assert( count( $native_bounded ) <= 8, 'Native resolver errors must be bounded per provider.' );
spdb_23g_assert( $native_bounded && false === strpos( $native_bounded[0]->get_error_message(), 'Patient detail' ), 'Native resolver error text must never be retained.' );

if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} Phase 23G native resolver tests failed.\n" ); exit( 1 ); }
echo "All {$tests} Phase 23G native resolver registry tests passed.\n";
