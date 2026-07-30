<?php
/**
 * Executable Phase 23C federated inventory tests.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/fixtures/class-test-adapter.php';

$tests  = 0;
$failed = 0;

function spdb_inventory_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

function spdb_inventory_error_code( $value ): string {
	return $value instanceof WP_Error ? $value->get_error_code() : '';
}

define( 'SMC_VERSION', '1.0.1' );
eval( 'function smc_user_status( $user_id ) { return (string) $GLOBALS["spdb_test_member_status"]; }' );
eval( 'function smc_is_founder( $user_id ) { return (bool) $GLOBALS["spdb_test_founder"]; }' );
eval( 'function smc_is_trusted_publisher( $user_id ) { return (bool) $GLOBALS["spdb_test_trusted"]; }' );

$GLOBALS['spdb_test_member_status'] = 'approved';
$GLOBALS['spdb_test_capabilities'] = array(
	'spdb_view_dashboard'   => true,
	'spdb_view_own_content' => true,
	'spdb_manage_own_content' => true,
);

$valid_one = SPDB_Test_Adapter::projection(
	'publication',
	'item-1',
	array(
		'title'       => 'Earlier item',
		'modified_at' => '2026-07-30T01:00:00Z',
	)
);
$valid_two = SPDB_Test_Adapter::projection(
	'publication',
	'item-2',
	array(
		'title'       => 'Later item',
		'modified_at' => '2026-07-30T03:00:00Z',
		'lifecycle_state' => 'native_unmapped_state',
	)
);
$invalid_privacy = SPDB_Test_Adapter::projection(
	'publication',
	'item-3',
	array( 'privacy_class' => 'clinical_sensitive' )
);

$registry = new SPDB_Adapter_Registry();
$adapter  = new SPDB_Test_Adapter(
	array(
		'items' => array( $valid_one, $valid_two, $invalid_privacy ),
		'total' => 3,
		'item'  => $valid_one,
	)
);
spdb_inventory_assert( true === $registry->register( $adapter ), 'Inventory test provider must register.' );
$inventory = new SPDB_Federated_Inventory( $registry );

$result = $inventory->list_items( array( 'per_page' => 20, 'scope' => 'institution' ) );
spdb_inventory_assert( is_array( $result ), 'Authorized inventory query must return a result envelope.' );
spdb_inventory_assert( 2 === count( $result['items'] ), 'Invalid native projections must be omitted rather than rendered.' );
spdb_inventory_assert( 'item-2' === $result['items'][0]['object_id'], 'Inventory must apply the requested default descending modified sort.' );
spdb_inventory_assert( 'unknown' === $result['items'][0]['lifecycle_state'], 'Unknown native states must map to unknown, not an assumed lifecycle state.' );
spdb_inventory_assert( true === $result['items'][0]['mapping_required'], 'Unknown native states must raise a mapping-required projection flag.' );
spdb_inventory_assert( 'own' === $result['scope'], 'A non-Founder must not elevate an inventory query to institution scope.' );
spdb_inventory_assert( 7 === $GLOBALS['spdb_test_last_inventory_query']['user_id'], 'The current user ID must be injected server-side into provider queries.' );
spdb_inventory_assert( 'own' === $GLOBALS['spdb_test_last_inventory_query']['scope'], 'The provider must receive the server-resolved scope.' );
spdb_inventory_assert( ! empty( $result['provider_errors'] ), 'Invalid provider projections must produce bounded partial-result diagnostics.' );

$GLOBALS['spdb_test_founder'] = true;
$founder_result = $inventory->list_items( array( 'scope' => 'institution' ) );
spdb_inventory_assert( 'institution' === $founder_result['scope'], 'A verified Founder may request institution-wide inventory scope.' );
$GLOBALS['spdb_test_founder'] = false;

$sensitive_search = $inventory->list_items( array( 'search' => 'doctor@example.test' ) );
spdb_inventory_assert( 'spdb_inventory_search_invalid' === spdb_inventory_error_code( $sensitive_search ), 'Contact details must be rejected from inventory search parameters.' );

$large_window = $inventory->list_items( array( 'page' => 5, 'per_page' => 50 ) );
spdb_inventory_assert( 'spdb_inventory_window_invalid' === spdb_inventory_error_code( $large_window ), 'Federated inventory windows above the bounded limit must be rejected.' );

$inspected = $inventory->inspect_item( 'provider_one', 'publication', 'item-1' );
spdb_inventory_assert( is_array( $inspected ), 'A matching native item projection must be inspectable.' );
spdb_inventory_assert( 'https://example.test/composer/?object=item-1' === $inspected['destinations']['edit'], 'A same-origin non-secret Composer destination must be retained.' );
spdb_inventory_assert( false === $inspected['execution_exposed'], 'Phase 23C must not expose mutation execution from the inspector.' );
spdb_inventory_assert( 1 === count( $inspected['allowed_operations'] ), 'Declared, capability-authorized native operations may be projected for inspection.' );
spdb_inventory_assert( false === $inspected['allowed_operations'][0]['environment_eligible'], 'An unreviewed provider must remain ineligible for environment writes.' );

$mismatched_registry = new SPDB_Adapter_Registry();
$mismatched_registry->register(
	new SPDB_Test_Adapter(
		array( 'item' => SPDB_Test_Adapter::projection( 'publication', 'different-item' ) )
	)
);
$mismatched = ( new SPDB_Federated_Inventory( $mismatched_registry ) )->inspect_item( 'provider_one', 'publication', 'requested-item' );
spdb_inventory_assert( 'spdb_projection_object_id_invalid' === spdb_inventory_error_code( $mismatched ), 'An inspector must reject a provider projection for a different native object.' );

$signed_registry = new SPDB_Adapter_Registry();
$signed_registry->register(
	new SPDB_Test_Adapter(
		array(
			'item' => SPDB_Test_Adapter::projection(
				'publication',
				'item-4',
				array( 'destinations' => array( 'edit' => 'https://example.test/composer/?_wpnonce=secret' ) )
			),
		)
	)
);
$signed = ( new SPDB_Federated_Inventory( $signed_registry ) )->inspect_item( 'provider_one', 'publication', 'item-4' );
spdb_inventory_assert( 'spdb_projection_destination_secret' === spdb_inventory_error_code( $signed ), 'Signed or secret-bearing destinations must not enter the dashboard projection.' );

$external_registry = new SPDB_Adapter_Registry();
$external_registry->register(
	new SPDB_Test_Adapter(
		array(
			'item' => SPDB_Test_Adapter::projection(
				'publication',
				'item-5',
				array( 'canonical_url' => 'https://external.example/item-5' )
			),
		)
	)
);
$external = ( new SPDB_Federated_Inventory( $external_registry ) )->inspect_item( 'provider_one', 'publication', 'item-5' );
spdb_inventory_assert( 'spdb_projection_destination_origin_invalid' === spdb_inventory_error_code( $external ), 'Inventory destinations must remain on the platform origin.' );

$throwing_registry = new SPDB_Adapter_Registry();
$throwing_registry->register( new SPDB_Test_Adapter( array( 'list_exception' => true ) ) );
$throwing_result = ( new SPDB_Federated_Inventory( $throwing_registry ) )->list_items( array() );
spdb_inventory_assert( is_array( $throwing_result ) && array() === $throwing_result['items'], 'A throwing provider must degrade to an empty partial result, not crash the dashboard.' );
spdb_inventory_assert( 'spdb_inventory_provider_exception' === $throwing_result['provider_errors'][0]['code'], 'A throwing provider must produce a normalized bounded error code.' );

$GLOBALS['spdb_test_member_status'] = 'submitted';
spdb_inventory_assert( true === $inventory->permission_check(), 'A restricted account with explicit owned-content view capability may use read-only inventory.' );
$GLOBALS['spdb_test_capabilities']['spdb_view_own_content'] = false;
$forbidden = $inventory->list_items( array() );
spdb_inventory_assert( 'spdb_inventory_forbidden' === spdb_inventory_error_code( $forbidden ), 'Inventory must fail closed without the explicit owned-content view capability.' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} inventory tests failed.\n" );
	exit( 1 );
}

echo "All {$tests} File 23 inventory tests passed.\n";
