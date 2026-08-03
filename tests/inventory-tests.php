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
$GLOBALS['spdb_test_founder']       = false;
$GLOBALS['spdb_test_capabilities']  = array(
	'spdb_view_dashboard'     => true,
	'spdb_view_own_content'   => true,
	'spdb_manage_own_content' => true,
);

$valid_one = SPDB_Test_Adapter::projection( 'publication', 'item-1', array( 'title' => 'Earlier item', 'modified_at' => '2026-07-30T01:00:00Z' ) );
$valid_two = SPDB_Test_Adapter::projection(
	'publication',
	'item-2',
	array(
		'title'             => 'Later item',
		'modified_at'       => '2026-07-30T03:00:00Z',
		'lifecycle_state'   => 'native_unmapped_state',
	)
);
$invalid_privacy = SPDB_Test_Adapter::projection( 'publication', 'item-3', array( 'privacy_class' => 'clinical_sensitive' ) );

$registry = new SPDB_Adapter_Registry();
$adapter  = new SPDB_Test_Adapter( array( 'items' => array( $valid_one, $valid_two, $invalid_privacy ), 'total' => 3, 'item' => $valid_one ) );
spdb_inventory_assert( true === $registry->register( $adapter ), 'Inventory test provider must register.' );
$inventory = new SPDB_Federated_Inventory( $registry );

$result = $inventory->list_items( array( 'per_page' => 20, 'scope' => 'institution' ) );
spdb_inventory_assert( is_array( $result ), 'Authorized inventory query must return a result envelope.' );
spdb_inventory_assert( 2 === count( $result['items'] ), 'Invalid native projections must be omitted rather than rendered.' );
spdb_inventory_assert( 'item-2' === $result['items'][0]['object_id'], 'Inventory must apply the requested default descending modified sort.' );
spdb_inventory_assert( 'unknown' === $result['items'][0]['lifecycle_state'], 'Unknown native states must map to unknown.' );
spdb_inventory_assert( true === $result['items'][0]['mapping_required'], 'Unknown native states must raise a mapping-required flag.' );
spdb_inventory_assert( 'own' === $result['scope'], 'A non-Founder must not elevate an inventory query to institution scope.' );
spdb_inventory_assert( 7 === $GLOBALS['spdb_test_last_inventory_query']['user_id'], 'Current user ID must be injected server-side into provider queries.' );
spdb_inventory_assert( 'own' === $GLOBALS['spdb_test_last_inventory_query']['scope'], 'Provider must receive the server-resolved scope.' );
spdb_inventory_assert( ! isset( $result['query']['user_id'], $result['query']['window'] ), 'Internal authority and window fields must not be reflected to REST/UI clients.' );
spdb_inventory_assert( 2 === $result['validated_window_count'], 'Validated window count must distinguish rendered projections from native totals.' );
spdb_inventory_assert( ! empty( $result['provider_errors'] ), 'Invalid provider projections must produce bounded diagnostics.' );

$GLOBALS['spdb_test_founder'] = true;
$founder_result = $inventory->list_items( array( 'scope' => 'institution' ) );
spdb_inventory_assert( 'institution' === $founder_result['scope'], 'A verified Founder may request institution-wide inventory scope.' );
$GLOBALS['spdb_test_founder'] = false;

$sensitive_search = $inventory->list_items( array( 'search' => 'doctor@example.test' ) );
spdb_inventory_assert( 'spdb_inventory_search_invalid' === spdb_inventory_error_code( $sensitive_search ), 'Contact details must be rejected from search.' );
$noncanonical = $inventory->list_items( array( 'lifecycle_state' => 'Draft' ) );
spdb_inventory_assert( 'spdb_inventory_filter_invalid' === spdb_inventory_error_code( $noncanonical ), 'Query keys must not be silently normalized.' );
$invalid_sort = $inventory->list_items( array( 'sort' => 'newest' ) );
spdb_inventory_assert( 'spdb_inventory_sort_invalid' === spdb_inventory_error_code( $invalid_sort ), 'Unknown sort values must fail closed instead of silently changing semantics.' );
$large_window = $inventory->list_items( array( 'page' => 5, 'per_page' => 50 ) );
spdb_inventory_assert( 'spdb_inventory_window_invalid' === spdb_inventory_error_code( $large_window ), 'Federated windows above the limit must be rejected.' );

$inspected = $inventory->inspect_item( 'provider_one', 'publication', 'item-1' );
spdb_inventory_assert( is_array( $inspected ), 'A matching owned native item must be inspectable.' );
spdb_inventory_assert( 7 === $inspected['owner_user_id'], 'Inspector must project the canonical native owner.' );
spdb_inventory_assert( 'https://example.test/composer/?object=item-1' === $inspected['destinations']['edit'], 'Safe same-origin Composer destination must be retained.' );
spdb_inventory_assert( false === $inspected['execution_exposed'], 'Phase 23C must not expose mutation execution.' );
spdb_inventory_assert( 1 === count( $inspected['allowed_operations'] ), 'Declared capability-authorized operation metadata may be projected.' );
spdb_inventory_assert( false === $inspected['allowed_operations'][0]['environment_eligible'], 'Unreviewed provider must remain ineligible for writes.' );
$noncanonical_reference = $inventory->inspect_item( 'Provider_One', 'publication', 'item-1' );
spdb_inventory_assert( 'spdb_inventory_reference_invalid' === spdb_inventory_error_code( $noncanonical_reference ), 'Inspector references must already be canonical.' );

$cross_owner_projection = SPDB_Test_Adapter::projection( 'publication', 'other-doctor-item', array( 'owner_user_id' => 99, 'author' => array( 'id' => 99, 'display_name' => 'Other Doctor' ) ) );
$cross_registry = new SPDB_Adapter_Registry();
$cross_registry->register( new SPDB_Test_Adapter( array( 'items' => array( $cross_owner_projection ), 'total' => 1, 'item' => $cross_owner_projection ) ) );
$cross_inventory = new SPDB_Federated_Inventory( $cross_registry );
$cross_list = $cross_inventory->list_items( array() );
spdb_inventory_assert( array() === $cross_list['items'], 'Own-content inventory must omit another doctor’s item even when a provider returns it.' );
spdb_inventory_assert( 'scope_mismatch' === $cross_list['provider_errors'][0]['code'], 'Cross-owner projection must produce a bounded scope mismatch diagnostic.' );
$cross_inspect = $cross_inventory->inspect_item( 'provider_one', 'publication', 'other-doctor-item' );
spdb_inventory_assert( 'spdb_inventory_item_unavailable' === spdb_inventory_error_code( $cross_inspect ), 'Cross-doctor inspector access must fail as non-enumerating not-found.' );
$GLOBALS['spdb_test_founder'] = true;
$founder_cross = $cross_inventory->list_items( array( 'scope' => 'institution' ) );
spdb_inventory_assert( 1 === count( $founder_cross['items'] ), 'Verified Founder institution scope may include another owner’s validated item.' );
$GLOBALS['spdb_test_founder'] = false;

$filter_registry = new SPDB_Adapter_Registry();
$filter_registry->register( new SPDB_Test_Adapter( array( 'items' => array( SPDB_Test_Adapter::projection( 'publication', 'published-item', array( 'lifecycle_state' => 'published' ) ) ), 'total' => 1 ) ) );
$filter_result = ( new SPDB_Federated_Inventory( $filter_registry ) )->list_items( array( 'lifecycle_state' => 'draft' ) );
spdb_inventory_assert( array() === $filter_result['items'], 'File 23 must not display provider items that violate the normalized filter.' );
spdb_inventory_assert( 'filter_mismatch' === $filter_result['provider_errors'][0]['code'], 'Provider filter mismatch must be explicit.' );

$duplicate_registry = new SPDB_Adapter_Registry();
$duplicate = SPDB_Test_Adapter::projection( 'publication', 'duplicate-item' );
$duplicate_registry->register( new SPDB_Test_Adapter( array( 'items' => array( $duplicate, $duplicate ), 'total' => 2 ) ) );
$duplicate_result = ( new SPDB_Federated_Inventory( $duplicate_registry ) )->list_items( array() );
spdb_inventory_assert( 1 === count( $duplicate_result['items'] ), 'Duplicate canonical provider references must render once.' );
spdb_inventory_assert( 'duplicate_projection' === $duplicate_result['provider_errors'][0]['code'], 'Duplicate projections must be diagnosed.' );

$bad_total_registry = new SPDB_Adapter_Registry();
$bad_total_registry->register( new SPDB_Test_Adapter( array( 'items' => array( $valid_one ), 'total' => array( 'secret' ) ) ) );
$bad_total = ( new SPDB_Federated_Inventory( $bad_total_registry ) )->list_items( array() );
spdb_inventory_assert( array() === $bad_total['items'], 'Malformed provider totals must invalidate that provider response.' );
spdb_inventory_assert( 'invalid_total' === $bad_total['provider_errors'][0]['code'], 'Malformed totals must use a bounded generic code.' );

$large_total_registry = new SPDB_Adapter_Registry();
$large_total_registry->register( new SPDB_Test_Adapter( array( 'items' => array( $valid_one ), 'total' => 1000 ) ) );
$large_total = ( new SPDB_Federated_Inventory( $large_total_registry ) )->list_items( array( 'per_page' => 50 ) );
spdb_inventory_assert( 4 === $large_total['pages'], 'Pagination must be capped to the 200-item federated window.' );
spdb_inventory_assert( 200 === $large_total['accessible_total'], 'Accessible total must disclose the bounded window rather than imply all 1,000 items are pageable.' );

$mismatched_registry = new SPDB_Adapter_Registry();
$mismatched_registry->register( new SPDB_Test_Adapter( array( 'item' => SPDB_Test_Adapter::projection( 'publication', 'different-item' ) ) ) );
$mismatched = ( new SPDB_Federated_Inventory( $mismatched_registry ) )->inspect_item( 'provider_one', 'publication', 'requested-item' );
spdb_inventory_assert( 'spdb_projection_object_id_invalid' === spdb_inventory_error_code( $mismatched ), 'Inspector must reject a mismatched native object.' );

$signed_registry = new SPDB_Adapter_Registry();
$signed_registry->register( new SPDB_Test_Adapter( array( 'item' => SPDB_Test_Adapter::projection( 'publication', 'item-4', array( 'destinations' => array( 'edit' => 'https://example.test/composer/?_wpnonce=secret' ) ) ) ) ) );
$signed = ( new SPDB_Federated_Inventory( $signed_registry ) )->inspect_item( 'provider_one', 'publication', 'item-4' );
spdb_inventory_assert( 'spdb_projection_destination_secret' === spdb_inventory_error_code( $signed ), 'Signed destinations must not enter the projection.' );

$fragment_registry = new SPDB_Adapter_Registry();
$fragment_registry->register( new SPDB_Test_Adapter( array( 'item' => SPDB_Test_Adapter::projection( 'publication', 'item-fragment', array( 'canonical_url' => 'https://example.test/publication/item-fragment/#access_token=secret' ) ) ) ) );
$fragment = ( new SPDB_Federated_Inventory( $fragment_registry ) )->inspect_item( 'provider_one', 'publication', 'item-fragment' );
spdb_inventory_assert( 'spdb_projection_destination_invalid' === spdb_inventory_error_code( $fragment ), 'Fragment-bearing URLs must be rejected.' );

$redirect_registry = new SPDB_Adapter_Registry();
$redirect_registry->register( new SPDB_Test_Adapter( array( 'item' => SPDB_Test_Adapter::projection( 'publication', 'item-redirect', array( 'destinations' => array( 'edit' => 'https://example.test/composer/?redirect=https%3A%2F%2Fevil.example' ) ) ) ) ) );
$redirect = ( new SPDB_Federated_Inventory( $redirect_registry ) )->inspect_item( 'provider_one', 'publication', 'item-redirect' );
spdb_inventory_assert( 'spdb_projection_destination_secret' === spdb_inventory_error_code( $redirect ), 'Nested redirect URLs must be rejected.' );

$external_thumbnail_registry = new SPDB_Adapter_Registry();
$external_thumbnail_registry->register( new SPDB_Test_Adapter( array( 'item' => SPDB_Test_Adapter::projection( 'publication', 'item-thumb', array( 'thumbnail_url' => 'https://tracking.example/pixel.jpg' ) ) ) ) );
$external_thumbnail = ( new SPDB_Federated_Inventory( $external_thumbnail_registry ) )->inspect_item( 'provider_one', 'publication', 'item-thumb' );
spdb_inventory_assert( 'spdb_projection_destination_origin_invalid' === spdb_inventory_error_code( $external_thumbnail ), 'Unapproved external thumbnails must be rejected.' );

$relative_time_registry = new SPDB_Adapter_Registry();
$relative_time_registry->register( new SPDB_Test_Adapter( array( 'item' => SPDB_Test_Adapter::projection( 'publication', 'item-time', array( 'modified_at' => 'tomorrow' ) ) ) ) );
$relative_time = ( new SPDB_Federated_Inventory( $relative_time_registry ) )->inspect_item( 'provider_one', 'publication', 'item-time' );
spdb_inventory_assert( 'spdb_projection_timestamp_invalid' === spdb_inventory_error_code( $relative_time ), 'Relative or ambiguous timestamps must be rejected.' );

$operation_registry = new SPDB_Adapter_Registry();
$operation_registry->register( new SPDB_Test_Adapter( array( 'item' => $valid_one, 'allowed_operations' => array( array( 'bad' ), 'Submit_Item', 'submit_item', 'submit_item' ) ) ) );
$operation_item = ( new SPDB_Federated_Inventory( $operation_registry ) )->inspect_item( 'provider_one', 'publication', 'item-1' );
spdb_inventory_assert( 1 === count( $operation_item['allowed_operations'] ), 'Malformed, noncanonical, and duplicate operation keys must be ignored safely.' );

$throwing_registry = new SPDB_Adapter_Registry();
$throwing_registry->register( new SPDB_Test_Adapter( array( 'list_exception' => true ) ) );
$throwing_result = ( new SPDB_Federated_Inventory( $throwing_registry ) )->list_items( array() );
spdb_inventory_assert( is_array( $throwing_result ) && array() === $throwing_result['items'], 'Throwing provider must degrade without crashing.' );
spdb_inventory_assert( 'provider_query_failed' === $throwing_result['provider_errors'][0]['code'], 'Provider failures must use a non-sensitive generic code.' );

$error_registry = new SPDB_Adapter_Registry();
$error_registry->register( new SPDB_Test_Adapter( array( 'list_error' => true ) ) );
$error_result = ( new SPDB_Federated_Inventory( $error_registry ) )->list_items( array() );
spdb_inventory_assert( 'provider_query_failed' === $error_result['provider_errors'][0]['code'], 'Provider-defined error codes must not leak through the inventory envelope.' );

$GLOBALS['spdb_test_member_status'] = 'submitted';
spdb_inventory_assert( true === $inventory->permission_check(), 'Restricted account with explicit own-content capability may use read-only inventory.' );
$GLOBALS['spdb_test_capabilities']['spdb_view_own_content'] = false;
$forbidden = $inventory->list_items( array() );
spdb_inventory_assert( 'spdb_inventory_forbidden' === spdb_inventory_error_code( $forbidden ), 'Inventory must fail closed without own-content view capability.' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} inventory tests failed.\n" );
	exit( 1 );
}

echo "All {$tests} File 23 inventory tests passed.\n";
