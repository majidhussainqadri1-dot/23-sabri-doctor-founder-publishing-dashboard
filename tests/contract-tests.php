<?php
/** Executable Phase 23A governance and adapter-contract tests. */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/fixtures/class-test-adapter.php';

$tests = 0; $failed = 0;
function spdb_test_assert( bool $condition, string $message ): void { global $tests, $failed; ++$tests; if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); } }
function spdb_test_error_code( $value ): string { return $value instanceof WP_Error ? $value->get_error_code() : ''; }

$registry = new SPDB_Adapter_Registry();
spdb_test_assert( true === $registry->register( new SPDB_Test_Adapter() ), 'A valid adapter must register.' );
spdb_test_assert( $registry->has( 'provider_one' ), 'Registered provider must be discoverable by exact canonical key.' );
spdb_test_assert( ! $registry->has( 'Provider One' ), 'Provider lookup must not silently normalize non-canonical keys.' );
spdb_test_assert( ! $registry->is_environment_write_eligible( 'provider_one' ), 'An unreviewed provider must never become write-eligible.' );
$duplicate = $registry->register( new SPDB_Test_Adapter() );
spdb_test_assert( 'spdb_duplicate_provider' === spdb_test_error_code( $duplicate ), 'Duplicate registration must fail.' );
$invalid_key = ( new SPDB_Adapter_Registry() )->register( new SPDB_Test_Adapter( array( 'provider_key' => 'Provider One' ) ) );
spdb_test_assert( 'spdb_invalid_provider_key' === spdb_test_error_code( $invalid_key ), 'Non-canonical provider keys must be rejected, not normalized.' );
$invalid_version = ( new SPDB_Adapter_Registry() )->register( new SPDB_Test_Adapter( array( 'provider_version' => 'version-one' ) ) );
spdb_test_assert( 'spdb_invalid_provider_version' === spdb_test_error_code( $invalid_version ), 'Malformed provider versions must be rejected.' );
$incompatible = ( new SPDB_Adapter_Registry() )->register( new SPDB_Test_Adapter( array( 'minimum_contract' => '3.0.0', 'maximum_contract' => '3.9.9' ) ) );
spdb_test_assert( 'spdb_incompatible_contract' === spdb_test_error_code( $incompatible ), 'Incompatible contract ranges must be rejected.' );
$self_accepted = ( new SPDB_Adapter_Registry() )->register( new SPDB_Test_Adapter( array( 'capability_state' => SPDB_Adapter_Registry::ACCEPTANCE_PRODUCTION_ACCEPTED ) ) );
spdb_test_assert( 'spdb_invalid_capability_state' === spdb_test_error_code( $self_accepted ), 'A provider must not self-declare production acceptance.' );
$throwing_registry = new SPDB_Adapter_Registry();
$throwing_result = $throwing_registry->register( new SPDB_Test_Adapter( array( 'throw_on_key' => true ) ) );
spdb_test_assert( 'spdb_adapter_registration_exception' === spdb_test_error_code( $throwing_result ), 'Adapter exceptions must be isolated as WP_Error.' );
spdb_test_assert( array() !== $throwing_registry->registration_errors(), 'Registration failures must be available to diagnostics.' );

$GLOBALS['spdb_test_environment'] = 'production';
$staging_registry = new SPDB_Adapter_Registry( array( 'provider_one' => SPDB_Adapter_Registry::ACCEPTANCE_STAGING_ACCEPTED ) );
$staging_registry->register( new SPDB_Test_Adapter() );
spdb_test_assert( ! $staging_registry->is_environment_write_eligible( 'provider_one' ), 'Staging acceptance must not authorize production writes.' );
$production_registry = new SPDB_Adapter_Registry( array( 'provider_one' => SPDB_Adapter_Registry::ACCEPTANCE_PRODUCTION_ACCEPTED ) );
$production_registry->register( new SPDB_Test_Adapter() );
spdb_test_assert( $production_registry->is_environment_write_eligible( 'provider_one' ), 'Production acceptance must authorize only the environment gate.' );
$GLOBALS['spdb_test_environment'] = 'staging';
spdb_test_assert( $staging_registry->is_environment_write_eligible( 'provider_one' ), 'Staging acceptance must authorize the staging environment gate.' );

spdb_test_assert( SPDB_Membership_Guard::supports_version( '1.0.1' ), 'Membership Core 1.0.1 must satisfy the File 23 contract.' );
spdb_test_assert( SPDB_Membership_Guard::supports_version( '1.9.9' ), 'Compatible Membership Core 1.x versions must satisfy the contract.' );
spdb_test_assert( ! SPDB_Membership_Guard::supports_version( '1.0.0' ), 'Membership Core versions below 1.0.1 must be rejected.' );
spdb_test_assert( ! SPDB_Membership_Guard::supports_version( '2.0.0' ), 'Unreviewed Membership Core major versions must be rejected.' );
spdb_test_assert( ! SPDB_Membership_Guard::supports_version( 'invalid' ), 'Malformed Membership Core versions must be rejected.' );
spdb_test_assert( ! SPDB_Membership_Guard::is_available(), 'Membership guard must detect a missing File 00 contract.' );

$GLOBALS['spdb_test_capabilities']['spdb_manage_own_content'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_view_dashboard'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_view_own_content'] = true;
spdb_test_assert( ! SPDB_Capabilities::current_user_can( 'spdb_manage_own_content' ), 'Capabilities must fail closed while File 00 is unavailable.' );
spdb_test_assert( ! SPDB_Capabilities::current_user_can( 'spdb_view_dashboard' ), 'Restricted views must fail closed while File 00 is unavailable.' );

define( 'SMC_VERSION', '1.0.1' );
$GLOBALS['spdb_test_founder'] = false;
$GLOBALS['spdb_test_trusted'] = false;
eval( 'function smc_user_status( $user_id ) { return (string) $GLOBALS["spdb_test_member_status"]; }' );
eval( 'function smc_is_founder( $user_id ) { return (bool) $GLOBALS["spdb_test_founder"]; }' );
eval( 'function smc_is_trusted_publisher( $user_id ) { return (bool) $GLOBALS["spdb_test_trusted"]; }' );
spdb_test_assert( SPDB_Membership_Guard::is_available(), 'Membership guard must recognize the compatible legacy File 00 function boundary.' );

$GLOBALS['spdb_test_member_status'] = 'submitted';
spdb_test_assert( SPDB_Capabilities::current_user_can( 'spdb_view_dashboard' ), 'A pending account with the explicit view capability must receive the restricted dashboard.' );
spdb_test_assert( SPDB_Capabilities::current_user_can( 'spdb_view_own_content' ), 'A pending account may receive explicitly assigned owned-content read-only access.' );
spdb_test_assert( ! SPDB_Capabilities::current_user_can( 'spdb_manage_own_content' ), 'A pending account must not receive content mutation authority.' );
$GLOBALS['spdb_test_member_status'] = 'suspended';
spdb_test_assert( SPDB_Capabilities::current_user_can( 'spdb_view_dashboard' ), 'A suspended account with explicit view capability must retain the restricted status/appeal workspace.' );
spdb_test_assert( ! SPDB_Capabilities::current_user_can( 'spdb_manage_own_content' ), 'A suspended account must be denied mutation authority.' );
$GLOBALS['spdb_test_member_status'] = 'approved';
spdb_test_assert( ! SPDB_Capabilities::current_user_can( 'spdb_manage_own_content' ), 'Generic approved membership must not manufacture verified-Doctor publishing authority from a stale WordPress capability.' );
$GLOBALS['spdb_test_founder'] = true;
spdb_test_assert( SPDB_Capabilities::current_user_can( 'spdb_manage_own_content' ), 'A current Founder assertion plus explicit capability may pass the publishing-identity gate.' );

$GLOBALS['spdb_test_environment'] = 'production';
$broker = new SPDB_Operation_Broker( $production_registry );
$unregistered = $broker->execute( 'provider_one', 'delete_everything', 'publication', '42', array() );
spdb_test_assert( 'spdb_unregistered_operation' === spdb_test_error_code( $unregistered ), 'Unregistered operation keys must be denied.' );
$missing_version = $broker->execute( 'provider_one', 'submit_item', 'publication', '42', array( 'idempotency_key' => '1234567890abcdef' ) );
spdb_test_assert( 'spdb_object_version_required' === spdb_test_error_code( $missing_version ), 'Versioned mutation must require the native object version.' );
$reserved_field = $broker->execute( 'provider_one', 'submit_item', 'publication', '42', array( 'object_version' => 'v1', 'idempotency_key' => '1234567890abcdef', 'author_id' => 99 ) );
spdb_test_assert( 'spdb_reserved_payload_field' === spdb_test_error_code( $reserved_field ), 'Client-supplied authority fields must be rejected.' );
$invalid_object = $broker->execute( 'provider_one', 'submit_item', 'publication', "42\n", array( 'object_version' => 'v1', 'idempotency_key' => '1234567890abcdef' ) );
spdb_test_assert( 'spdb_invalid_object_reference' === spdb_test_error_code( $invalid_object ), 'Control characters in native object references must be rejected.' );
$success = $broker->execute( 'provider_one', 'submit_item', 'publication', '42', array( 'object_version' => 'v1', 'idempotency_key' => '1234567890abcdef' ) );
spdb_test_assert( is_array( $success ) && isset( $success['confirmed_item']['object_version'] ), 'A successful action must be re-read from the native provider.' );

if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} contract tests failed.\n" ); exit( 1 ); }
echo "All {$tests} File 23 contract tests passed.\n";
