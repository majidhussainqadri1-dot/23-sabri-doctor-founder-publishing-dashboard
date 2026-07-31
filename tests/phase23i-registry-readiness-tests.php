<?php
/** Executable registry-to-readiness conformance tests for Phase 23I. */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/fixtures/class-test-adapter.php';
require_once __DIR__ . '/fixtures/class-test-native-reference-provider.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-native-reference-readiness.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-native-reference-registry-readiness.php';

$tests = 0;
$failed = 0;
function spdb_23i_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
}

/** @param array<string,string> $adapter_acceptance */
function spdb_23i_adapters( array $adapter_acceptance = array( 'provider_one' => 'staging_accepted' ) ): SPDB_Adapter_Registry {
	$adapters = new SPDB_Adapter_Registry( $adapter_acceptance );
	$adapters->register( new SPDB_Test_Adapter() );
	return $adapters;
}

$GLOBALS['spdb_test_environment'] = 'staging';

$empty_registry = new SPDB_Native_Reference_Registry( spdb_23i_adapters(), array() );
$empty = new SPDB_Native_Reference_Registry_Readiness( $empty_registry );
spdb_23i_assert( array( 'available' => true, 'ready' => false, 'code' => 'registry_empty' ) === $empty->readiness_snapshot(), 'An empty registry must be available but not ready.' );
spdb_23i_assert( false === $empty->is_ready(), 'An empty registry must not satisfy the readiness contract.' );

$unaccepted_registry = new SPDB_Native_Reference_Registry( spdb_23i_adapters(), array() );
$unaccepted_registry->register( new SPDB_Test_Native_Reference_Provider() );
$unaccepted = new SPDB_Native_Reference_Registry_Readiness( $unaccepted_registry );
spdb_23i_assert( array( 'available' => true, 'ready' => false, 'code' => 'no_ready_provider' ) === $unaccepted->readiness_snapshot(), 'A registered but unaccepted provider must not make the registry ready.' );

$accepted_registry = new SPDB_Native_Reference_Registry( spdb_23i_adapters(), array( 'provider_one' => 'staging_accepted' ) );
$accepted_registry->register( new SPDB_Test_Native_Reference_Provider() );
$accepted = new SPDB_Native_Reference_Registry_Readiness( $accepted_registry );
$accepted_snapshot = $accepted->readiness_snapshot();
spdb_23i_assert( array( 'available' => true, 'ready' => true, 'code' => 'ready' ) === $accepted_snapshot && true === $accepted->is_ready(), 'One accepted healthy contract-current provider must make aggregate readiness true.' );
spdb_23i_assert( array_keys( $accepted_snapshot ) === array( 'available', 'ready', 'code' ), 'Registry details must not escape the fixed readiness projection.' );

$unhealthy_registry = new SPDB_Native_Reference_Registry( spdb_23i_adapters(), array( 'provider_one' => 'staging_accepted' ) );
$unhealthy_registry->register( new SPDB_Test_Native_Reference_Provider( array( 'healthy' => false, 'health_code' => 'private_degraded_reason' ) ) );
$unhealthy = new SPDB_Native_Reference_Registry_Readiness( $unhealthy_registry );
$unhealthy_snapshot = $unhealthy->readiness_snapshot();
spdb_23i_assert( array( 'available' => true, 'ready' => false, 'code' => 'no_ready_provider' ) === $unhealthy_snapshot && ! in_array( 'private_degraded_reason', $unhealthy_snapshot, true ), 'Unhealthy provider detail must be reduced to a bounded no-ready state.' );

$drift_provider = new SPDB_Test_Native_Reference_Provider();
$drift_registry = new SPDB_Native_Reference_Registry( spdb_23i_adapters(), array( 'provider_one' => 'staging_accepted' ) );
$drift_registry->register( $drift_provider );
$drift = new SPDB_Native_Reference_Registry_Readiness( $drift_registry );
spdb_23i_assert( true === $drift->is_ready(), 'The unchanged provider must initially be ready.' );
$drift_provider->set_provider_version( '1.0.1' );
spdb_23i_assert( array( 'available' => true, 'ready' => false, 'code' => 'no_ready_provider' ) === $drift->readiness_snapshot(), 'Contract drift must revoke readiness immediately.' );

$error_registry = new SPDB_Native_Reference_Registry( spdb_23i_adapters(), array( 'provider_one' => 'invalid_self_acceptance' ) );
$error_projection = new SPDB_Native_Reference_Registry_Readiness( $error_registry );
spdb_23i_assert( array( 'available' => true, 'ready' => false, 'code' => 'registry_registration_errors' ) === $error_projection->readiness_snapshot(), 'Registry governance errors must remain bounded counts and block empty readiness.' );

$multi_adapters = new SPDB_Adapter_Registry( array( 'provider_one' => 'staging_accepted', 'provider_two' => 'staging_accepted' ) );
$multi_adapters->register( new SPDB_Test_Adapter() );
$multi_adapters->register( new SPDB_Test_Adapter( array( 'provider_key' => 'provider_two', 'provider_name' => 'Provider Two' ) ) );
$multi_registry = new SPDB_Native_Reference_Registry( $multi_adapters, array( 'provider_one' => 'staging_accepted' ) );
$multi_registry->register( new SPDB_Test_Native_Reference_Provider() );
$multi_registry->register( new SPDB_Test_Native_Reference_Provider( array( 'provider_key' => 'provider_two' ) ) );
$multi = new SPDB_Native_Reference_Registry_Readiness( $multi_registry );
spdb_23i_assert( array( 'available' => true, 'ready' => true, 'code' => 'ready' ) === $multi->readiness_snapshot(), 'One ready provider must keep aggregate readiness true when another provider remains unaccepted.' );

$reordered = new SPDB_Native_Reference_Registry_Readiness( static function (): array {
	return array(
		'providers' => array( array( 'ready' => true, 'private_detail' => 'not projected' ) ),
		'ready' => true,
		'registration_errors' => 0,
		'ready_count' => 1,
		'resolver_count' => 1,
		'available' => true,
	);
} );
spdb_23i_assert( array( 'available' => true, 'ready' => true, 'code' => 'ready' ) === $reordered->readiness_snapshot(), 'Valid registry health must not depend on associative key order.' );

$invalid_unknown = new SPDB_Native_Reference_Registry_Readiness( static function (): array {
	return array( 'available' => true, 'resolver_count' => 0, 'ready_count' => 0, 'registration_errors' => 0, 'ready' => false, 'providers' => array(), 'secret' => 'hidden' );
} );
spdb_23i_assert( array( 'available' => false, 'ready' => false, 'code' => 'registry_health_invalid' ) === $invalid_unknown->readiness_snapshot(), 'Unknown aggregate health fields must fail closed.' );

$invalid_counts = new SPDB_Native_Reference_Registry_Readiness( static function (): array {
	return array( 'available' => true, 'resolver_count' => 1, 'ready_count' => 2, 'registration_errors' => 0, 'ready' => true, 'providers' => array( array( 'ready' => true ) ) );
} );
spdb_23i_assert( array( 'available' => false, 'ready' => false, 'code' => 'registry_health_invalid' ) === $invalid_counts->readiness_snapshot(), 'Impossible aggregate counts must fail closed.' );

$inconsistent_provider_count = new SPDB_Native_Reference_Registry_Readiness( static function (): array {
	return array( 'available' => true, 'resolver_count' => 1, 'ready_count' => 1, 'registration_errors' => 0, 'ready' => true, 'providers' => array( array( 'ready' => false ) ) );
} );
spdb_23i_assert( array( 'available' => false, 'ready' => false, 'code' => 'registry_health_invalid' ) === $inconsistent_provider_count->readiness_snapshot(), 'Provider readiness and aggregate counts must agree exactly.' );

$exception = new SPDB_Native_Reference_Registry_Readiness( static function (): array {
	throw new RuntimeException( 'Private registry health exception.' );
} );
$exception_snapshot = $exception->readiness_snapshot();
spdb_23i_assert( array( 'available' => false, 'ready' => false, 'code' => 'registry_health_exception' ) === $exception_snapshot && false === strpos( implode( '|', $exception_snapshot ), 'Private' ), 'Registry health exceptions must be isolated without relaying text.' );

$invalid_constructor_rejected = false;
try { new SPDB_Native_Reference_Registry_Readiness( new stdClass() ); }
catch ( InvalidArgumentException $exception_value ) { $invalid_constructor_rejected = true; }
spdb_23i_assert( $invalid_constructor_rejected, 'An unsupported health source must be rejected at construction.' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} Phase 23I registry readiness tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} Phase 23I registry readiness tests passed.\n";
