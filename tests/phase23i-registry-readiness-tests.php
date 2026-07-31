<?php
/** Executable registry-to-readiness conformance tests for Phase 23I. */
define( 'SPDB_TESTING', true );
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
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

/** @param array<string,string> $adapter_acceptance */
function spdb_23i_adapters( array $adapter_acceptance = array( 'provider_one' => 'staging_accepted' ) ): SPDB_Adapter_Registry {
	$adapters = new SPDB_Adapter_Registry( $adapter_acceptance );
	$adapters->register( new SPDB_Test_Adapter() );
	return $adapters;
}

/** @return array<string,mixed> */
function spdb_23i_health_provider( string $provider_key, bool $ready, bool $healthy = true, string $code = 'healthy' ): array {
	return array(
		'provider_key' => $provider_key,
		'healthy'      => $healthy,
		'ready'        => $ready,
		'code'         => $code,
	);
}

/** @param array<int,array<string,mixed>> $providers @return array<string,mixed> */
function spdb_23i_health( array $providers, int $ready_count, int $registration_errors = 0, bool $available = true ): array {
	return array(
		'available'           => $available,
		'resolver_count'      => count( $providers ),
		'ready_count'         => $ready_count,
		'registration_errors' => $registration_errors,
		'ready'               => $ready_count > 0,
		'providers'           => $providers,
	);
}

$GLOBALS['spdb_test_environment'] = 'staging';

$constructor = new ReflectionMethod( SPDB_Native_Reference_Registry_Readiness::class, '__construct' );
spdb_23i_assert( $constructor->isPrivate(), 'The raw health-reader constructor must not be a public production trust boundary.' );

$empty_registry = new SPDB_Native_Reference_Registry( spdb_23i_adapters(), array() );
$empty = SPDB_Native_Reference_Registry_Readiness::from_registry( $empty_registry );
spdb_23i_assert( array( 'available' => true, 'ready' => false, 'code' => 'registry_empty' ) === $empty->readiness_snapshot(), 'An empty registry must be available but not ready.' );
spdb_23i_assert( false === $empty->is_ready(), 'An empty registry must not satisfy the readiness contract.' );

$unaccepted_registry = new SPDB_Native_Reference_Registry( spdb_23i_adapters(), array() );
$unaccepted_registry->register( new SPDB_Test_Native_Reference_Provider() );
$unaccepted = SPDB_Native_Reference_Registry_Readiness::from_registry( $unaccepted_registry );
spdb_23i_assert( array( 'available' => true, 'ready' => false, 'code' => 'no_ready_provider' ) === $unaccepted->readiness_snapshot(), 'A registered but unaccepted provider must not make the registry ready.' );

$accepted_registry = new SPDB_Native_Reference_Registry( spdb_23i_adapters(), array( 'provider_one' => 'staging_accepted' ) );
$accepted_registry->register( new SPDB_Test_Native_Reference_Provider() );
$accepted = SPDB_Native_Reference_Registry_Readiness::from_registry( $accepted_registry );
$accepted_snapshot = $accepted->readiness_snapshot();
spdb_23i_assert( array( 'available' => true, 'ready' => true, 'code' => 'ready' ) === $accepted_snapshot && true === $accepted->is_ready(), 'One accepted healthy contract-current provider must make aggregate readiness true.' );
spdb_23i_assert( array_keys( $accepted_snapshot ) === array( 'available', 'ready', 'code' ), 'Registry details must not escape the fixed readiness projection.' );

$unhealthy_registry = new SPDB_Native_Reference_Registry( spdb_23i_adapters(), array( 'provider_one' => 'staging_accepted' ) );
$unhealthy_registry->register( new SPDB_Test_Native_Reference_Provider( array( 'healthy' => false, 'health_code' => 'private_degraded_reason' ) ) );
$unhealthy = SPDB_Native_Reference_Registry_Readiness::from_registry( $unhealthy_registry );
$unhealthy_snapshot = $unhealthy->readiness_snapshot();
spdb_23i_assert( array( 'available' => true, 'ready' => false, 'code' => 'no_ready_provider' ) === $unhealthy_snapshot && ! in_array( 'private_degraded_reason', $unhealthy_snapshot, true ), 'Unhealthy provider detail must be reduced to a bounded no-ready state.' );

$drift_provider = new SPDB_Test_Native_Reference_Provider();
$drift_registry = new SPDB_Native_Reference_Registry( spdb_23i_adapters(), array( 'provider_one' => 'staging_accepted' ) );
$drift_registry->register( $drift_provider );
$drift = SPDB_Native_Reference_Registry_Readiness::from_registry( $drift_registry );
spdb_23i_assert( true === $drift->is_ready(), 'The unchanged provider must initially be ready.' );
$drift_provider->set_provider_version( '1.0.1' );
spdb_23i_assert( array( 'available' => true, 'ready' => false, 'code' => 'no_ready_provider' ) === $drift->readiness_snapshot(), 'Contract drift must revoke readiness immediately.' );

$error_registry = new SPDB_Native_Reference_Registry( spdb_23i_adapters(), array( 'provider_one' => 'invalid_self_acceptance' ) );
$error_projection = SPDB_Native_Reference_Registry_Readiness::from_registry( $error_registry );
spdb_23i_assert( array( 'available' => true, 'ready' => false, 'code' => 'registry_registration_errors' ) === $error_projection->readiness_snapshot(), 'Registry governance errors must remain bounded counts and block empty readiness.' );

$multi_adapters = new SPDB_Adapter_Registry( array( 'provider_one' => 'staging_accepted', 'provider_two' => 'staging_accepted' ) );
$multi_adapters->register( new SPDB_Test_Adapter() );
$multi_adapters->register( new SPDB_Test_Adapter( array( 'provider_key' => 'provider_two', 'provider_name' => 'Provider Two' ) ) );
$multi_registry = new SPDB_Native_Reference_Registry( $multi_adapters, array( 'provider_one' => 'staging_accepted' ) );
$multi_registry->register( new SPDB_Test_Native_Reference_Provider() );
$multi_registry->register( new SPDB_Test_Native_Reference_Provider( array( 'provider_key' => 'provider_two' ) ) );
$multi = SPDB_Native_Reference_Registry_Readiness::from_registry( $multi_registry );
spdb_23i_assert( array( 'available' => true, 'ready' => true, 'code' => 'ready' ) === $multi->readiness_snapshot(), 'One ready provider must keep aggregate readiness true when another provider remains unaccepted.' );

$reordered = SPDB_Native_Reference_Registry_Readiness::from_health_reader_for_tests( static function (): array {
	return array(
		'providers'           => array( spdb_23i_health_provider( 'provider_one', true ) ),
		'ready'               => true,
		'registration_errors' => 0,
		'ready_count'         => 1,
		'resolver_count'      => 1,
		'available'           => true,
	);
} );
spdb_23i_assert( array( 'available' => true, 'ready' => true, 'code' => 'ready' ) === $reordered->readiness_snapshot(), 'Valid registry health must not depend on associative key order.' );

$invalid_unknown = SPDB_Native_Reference_Registry_Readiness::from_health_reader_for_tests( static function (): array {
	return spdb_23i_health( array(), 0 ) + array( 'secret' => 'hidden' );
} );
spdb_23i_assert( array( 'available' => false, 'ready' => false, 'code' => 'registry_health_invalid' ) === $invalid_unknown->readiness_snapshot(), 'Unknown aggregate health fields must fail closed.' );

$invalid_counts = SPDB_Native_Reference_Registry_Readiness::from_health_reader_for_tests( static function (): array {
	return array(
		'available'           => true,
		'resolver_count'      => 1,
		'ready_count'         => 2,
		'registration_errors' => 0,
		'ready'               => true,
		'providers'           => array( spdb_23i_health_provider( 'provider_one', true ) ),
	);
} );
spdb_23i_assert( array( 'available' => false, 'ready' => false, 'code' => 'registry_health_invalid' ) === $invalid_counts->readiness_snapshot(), 'Impossible aggregate counts must fail closed.' );

$inconsistent_provider_count = SPDB_Native_Reference_Registry_Readiness::from_health_reader_for_tests( static function (): array {
	return spdb_23i_health( array( spdb_23i_health_provider( 'provider_one', false ) ), 1 );
} );
spdb_23i_assert( array( 'available' => false, 'ready' => false, 'code' => 'registry_health_invalid' ) === $inconsistent_provider_count->readiness_snapshot(), 'Provider readiness and aggregate counts must agree exactly.' );

$duplicate_provider = SPDB_Native_Reference_Registry_Readiness::from_health_reader_for_tests( static function (): array {
	return spdb_23i_health(
		array(
			spdb_23i_health_provider( 'provider_one', true ),
			spdb_23i_health_provider( 'provider_one', false ),
		),
		1
	);
} );
spdb_23i_assert( array( 'available' => false, 'ready' => false, 'code' => 'registry_health_invalid' ) === $duplicate_provider->readiness_snapshot(), 'Duplicate provider identities must not inflate aggregate readiness.' );

$noncanonical_provider = SPDB_Native_Reference_Registry_Readiness::from_health_reader_for_tests( static function (): array {
	return spdb_23i_health( array( spdb_23i_health_provider( 'Provider One', true ) ), 1 );
} );
spdb_23i_assert( array( 'available' => false, 'ready' => false, 'code' => 'registry_health_invalid' ) === $noncanonical_provider->readiness_snapshot(), 'Noncanonical provider identities must fail closed.' );

$unsafe_code = SPDB_Native_Reference_Registry_Readiness::from_health_reader_for_tests( static function (): array {
	return spdb_23i_health( array( spdb_23i_health_provider( 'provider_one', false, false, 'Private health text!' ) ), 0 );
} );
spdb_23i_assert( array( 'available' => false, 'ready' => false, 'code' => 'registry_health_invalid' ) === $unsafe_code->readiness_snapshot(), 'Unbounded or noncanonical provider health codes must fail closed.' );

$ready_but_unhealthy = SPDB_Native_Reference_Registry_Readiness::from_health_reader_for_tests( static function (): array {
	return spdb_23i_health( array( spdb_23i_health_provider( 'provider_one', true, false, 'degraded' ) ), 1 );
} );
spdb_23i_assert( array( 'available' => false, 'ready' => false, 'code' => 'registry_health_invalid' ) === $ready_but_unhealthy->readiness_snapshot(), 'A provider cannot be ready while its health is false.' );

$unavailable_but_ready = SPDB_Native_Reference_Registry_Readiness::from_health_reader_for_tests( static function (): array {
	return spdb_23i_health( array( spdb_23i_health_provider( 'provider_one', true ) ), 1, 0, false );
} );
spdb_23i_assert( array( 'available' => false, 'ready' => false, 'code' => 'registry_health_invalid' ) === $unavailable_but_ready->readiness_snapshot(), 'Unavailable aggregate health cannot simultaneously claim readiness.' );

$exception = SPDB_Native_Reference_Registry_Readiness::from_health_reader_for_tests( static function (): array {
	throw new RuntimeException( 'Private registry health exception.' );
} );
$exception_snapshot = $exception->readiness_snapshot();
spdb_23i_assert( array( 'available' => false, 'ready' => false, 'code' => 'registry_health_exception' ) === $exception_snapshot && false === strpos( implode( '|', $exception_snapshot ), 'Private' ), 'Registry health exceptions must be isolated without relaying text.' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} corrective Phase 23I registry readiness tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} corrective Phase 23I registry readiness tests passed.\n";
