<?php
/** Executable time-of-check/time-of-use contract drift tests for Phase 23G. */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/fixtures/class-test-adapter.php';
require_once __DIR__ . '/fixtures/class-test-native-reference-provider.php';
if ( ! defined( 'SMC_VERSION' ) ) { define( 'SMC_VERSION', '1.0.1' ); }
if ( ! function_exists( 'smc_user_status' ) ) { function smc_user_status( $user_id ) { return (string) ( $GLOBALS['spdb_test_member_statuses'][ $user_id ] ?? $GLOBALS['spdb_test_member_status'] ); } }
if ( ! function_exists( 'smc_is_founder' ) ) { function smc_is_founder( $user_id ) { return (bool) $GLOBALS['spdb_test_founder']; } }
if ( ! function_exists( 'smc_is_trusted_publisher' ) ) { function smc_is_trusted_publisher( $user_id ) { return false; } }

$tests = 0;
$failed = 0;
function spdb_23g_drift_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
}
function spdb_23g_drift_code( $value ): string { return $value instanceof WP_Error ? $value->get_error_code() : ''; }
function spdb_23g_drift_context(): array {
	return array(
		'user_id' => get_current_user_id(),
		'scope' => 'own',
		'is_founder' => SPDB_Membership_Guard::is_user_founder( get_current_user_id() ),
		'environment' => wp_get_environment_type(),
		'generated_at' => gmdate( 'c' ),
	);
}
function spdb_23g_drift_registry(): SPDB_Native_Reference_Registry {
	$adapters = new SPDB_Adapter_Registry( array( 'provider_one' => 'staging_accepted' ) );
	$adapters->register( new SPDB_Test_Adapter() );
	return new SPDB_Native_Reference_Registry( $adapters, array( 'provider_one' => 'staging_accepted' ) );
}

$GLOBALS['spdb_test_environment'] = 'staging';
$GLOBALS['spdb_test_user_id'] = 7;
$GLOBALS['spdb_test_member_status'] = 'approved';
$GLOBALS['spdb_test_member_statuses'][7] = 'approved';
/* The drift test must reach provider-readiness evaluation under a current canonical publishing identity. */
$GLOBALS['spdb_test_founder'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_manage_own_content'] = true;

$version_provider = new SPDB_Test_Native_Reference_Provider();
$version_registry = spdb_23g_drift_registry();
spdb_23g_drift_assert( true === $version_registry->register( $version_provider ) && true === $version_registry->is_ready(), 'The unchanged accepted resolver must initially be ready.' );
$version_provider->set_provider_version( '1.0.1' );
spdb_23g_drift_assert( false === $version_registry->is_ready(), 'Provider-version drift after registration must revoke readiness.' );
$version_health = $version_registry->health_snapshot();
spdb_23g_drift_assert( 'contract_drift' === $version_health['providers'][0]['code'] && false === $version_health['providers'][0]['ready'], 'Diagnostics must expose bounded contract-drift state.' );
$version_result = $version_registry->resolve_reference( 'provider_one', 'publication', 'post-101', spdb_23g_drift_context() );
spdb_23g_drift_assert( 'spdb_native_resolver_provider_not_ready' === spdb_23g_drift_code( $version_result ) && 0 === $version_provider->resolve_calls, 'A drifted resolver must be denied before its provider callback executes.' );

$type_provider = new SPDB_Test_Native_Reference_Provider();
$type_registry = spdb_23g_drift_registry();
spdb_23g_drift_assert( true === $type_registry->register( $type_provider ) && true === $type_registry->is_ready(), 'A second unchanged resolver must initially be ready.' );
$type_provider->set_object_types( array( 'publication', 'profile' ) );
$type_health = $type_registry->health_snapshot();
spdb_23g_drift_assert( false === $type_registry->is_ready() && 'contract_drift' === $type_health['providers'][0]['code'], 'Object-type drift after registration must revoke readiness.' );
$type_result = $type_registry->resolve_reference( 'provider_one', 'publication', 'post-101', spdb_23g_drift_context() );
spdb_23g_drift_assert( 'spdb_native_resolver_provider_not_ready' === spdb_23g_drift_code( $type_result ) && 0 === $type_provider->resolve_calls, 'Object-type drift must fail before provider execution.' );

if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} Phase 23G contract drift tests failed.\n" ); exit( 1 ); }
echo "All {$tests} Phase 23G contract drift tests passed.\n";
