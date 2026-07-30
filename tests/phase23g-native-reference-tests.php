<?php
/** Executable provider registration, acceptance, isolation, and routing tests. */
require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-native-reference-provider.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-native-reference-registry.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-native-reference-registration.php';
require_once __DIR__ . '/fixtures/class-test-adapter.php';
require_once __DIR__ . '/fixtures/class-test-native-reference-provider.php';

$tests = 0;
$failed = 0;
function spdb_23g_assert( bool $condition, string $message ): void { global $tests, $failed; ++$tests; if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); } }
function spdb_23g_code( $value ): string { return $value instanceof WP_Error ? $value->get_error_code() : ''; }
function spdb_23g_adapter_registry( array $extra = array() ): SPDB_Adapter_Registry {
	$registry = new SPDB_Adapter_Registry();
	$registry->register( new SPDB_Test_Adapter() );
	foreach ( $extra as $adapter ) { $registry->register( $adapter ); }
	return $registry;
}

$GLOBALS['spdb_test_environment'] = 'staging';
$adapters = spdb_23g_adapter_registry();
$provider = new SPDB_Test_Native_Reference_Provider();
$default_denied = new SPDB_Native_Reference_Registry( $adapters );
spdb_23g_assert( true === $default_denied->register( $provider ), 'A technically valid resolver may register without self-accepting.' );
spdb_23g_assert( false === $default_denied->is_ready(), 'Resolver acceptance must default to denied.' );
$denied = $default_denied->resolve_reference( 'provider_one', 'publication', 'post-101', array( 'user_id' => 7, 'scope' => 'own' ) );
spdb_23g_assert( 'spdb_native_resolver_provider_not_ready' === spdb_23g_code( $denied ) && 0 === $provider->resolve_calls, 'An unaccepted resolver must fail before provider execution.' );

$accepted_provider = new SPDB_Test_Native_Reference_Provider();
$accepted = new SPDB_Native_Reference_Registry( $adapters, array( 'provider_one' => SPDB_Native_Reference_Registry::ACCEPTANCE_STAGING_ACCEPTED ) );
spdb_23g_assert( true === $accepted->register( $accepted_provider ) && true === $accepted->is_ready(), 'A healthy staging-accepted resolver must become ready in staging.' );
$resolved = $accepted->resolve_reference( 'provider_one', 'publication', 'post-101', array( 'user_id' => 7, 'scope' => 'own' ) );
spdb_23g_assert( is_array( $resolved ) && 'provider_one' === $resolved['provider_key'] && 1 === $accepted_provider->resolve_calls, 'An accepted resolver must route the exact provider reference.' );
$accepted->health_snapshot(); $accepted->health_snapshot();
spdb_23g_assert( 1 === $accepted_provider->health_calls, 'Resolver health must be cached per registry request.' );

$GLOBALS['spdb_test_environment'] = 'production';
$production_denied = new SPDB_Native_Reference_Registry( $adapters, array( 'provider_one' => SPDB_Native_Reference_Registry::ACCEPTANCE_STAGING_ACCEPTED ) );
$production_denied->register( new SPDB_Test_Native_Reference_Provider() );
spdb_23g_assert( false === $production_denied->is_ready(), 'Staging acceptance must not authorize production resolution.' );
$production_accepted = new SPDB_Native_Reference_Registry( $adapters, array( 'provider_one' => SPDB_Native_Reference_Registry::ACCEPTANCE_PRODUCTION_ACCEPTED ) );
$production_accepted->register( new SPDB_Test_Native_Reference_Provider() );
spdb_23g_assert( true === $production_accepted->is_ready(), 'Production acceptance may authorize a healthy resolver in production.' );
$GLOBALS['spdb_test_environment'] = 'staging';

$missing_adapter = new SPDB_Native_Reference_Registry( new SPDB_Adapter_Registry(), array( 'provider_one' => 'staging_accepted' ) );
spdb_23g_assert( 'spdb_native_resolver_adapter_missing' === spdb_23g_code( $missing_adapter->register( new SPDB_Test_Native_Reference_Provider() ) ), 'Resolver registration must require an existing adapter.' );
$version_mismatch = new SPDB_Native_Reference_Registry( $adapters );
spdb_23g_assert( 'spdb_native_resolver_provider_version_mismatch' === spdb_23g_code( $version_mismatch->register( new SPDB_Test_Native_Reference_Provider( array( 'provider_version' => '2.0.0' ) ) ) ), 'Resolver and adapter provider versions must match exactly.' );
$type_widening = new SPDB_Native_Reference_Registry( $adapters );
spdb_23g_assert( 'spdb_native_resolver_object_type_invalid' === spdb_23g_code( $type_widening->register( new SPDB_Test_Native_Reference_Provider( array( 'object_types' => array( 'publication', 'patient_record' ) ) ) ) ), 'A resolver may not widen its adapter object types.' );
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
spdb_23g_assert( 'spdb_native_resolver_provider_exception' === spdb_23g_code( $exception_registry->resolve_reference( 'provider_one', 'publication', 'post-101', array( 'user_id' => 7, 'scope' => 'own' ) ) ), 'A provider exception must be isolated and translated.' );
$invalid_response_registry = new SPDB_Native_Reference_Registry( $adapters, array( 'provider_one' => 'staging_accepted' ) );
$invalid_response_registry->register( new SPDB_Test_Native_Reference_Provider( array( 'invalid_response' => true ) ) );
spdb_23g_assert( 'spdb_native_resolver_response_invalid' === spdb_23g_code( $invalid_response_registry->resolve_reference( 'provider_one', 'publication', 'post-101', array( 'user_id' => 7, 'scope' => 'own' ) ) ), 'A non-array resolver response must fail closed.' );
$mismatch_registry = new SPDB_Native_Reference_Registry( $adapters, array( 'provider_one' => 'staging_accepted' ) );
$mismatch_registry->register( new SPDB_Test_Native_Reference_Provider( array( 'mismatch_response' => true ) ) );
spdb_23g_assert( 'spdb_native_resolver_response_mismatch' === spdb_23g_code( $mismatch_registry->resolve_reference( 'provider_one', 'publication', 'post-101', array( 'user_id' => 7, 'scope' => 'own' ) ) ), 'A resolver may not redirect a request to another provider reference.' );

$second_adapter = new SPDB_Test_Adapter( array( 'provider_key' => 'provider_two', 'provider_name' => 'Provider Two' ) );
$dispatch_adapters = spdb_23g_adapter_registry( array( $second_adapter ) );
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

if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} Phase 23G native resolver tests failed.\n" ); exit( 1 ); }
echo "All {$tests} Phase 23G native resolver registry tests passed.\n";
