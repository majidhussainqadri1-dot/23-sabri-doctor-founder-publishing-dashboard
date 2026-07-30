<?php
/**
 * Executable File 23 dashboard-core regression tests.
 */

require_once __DIR__ . '/bootstrap.php';

$tests  = 0;
$failed = 0;

function spdb_core_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

function spdb_core_error_code( $value ): string {
	return $value instanceof WP_Error ? $value->get_error_code() : '';
}

define( 'SMC_VERSION', '1.0.1' );
eval( 'function smc_user_status( $user_id ) { return (string) $GLOBALS["spdb_test_member_status"]; }' );
eval( 'function smc_is_founder( $user_id ) { return (bool) $GLOBALS["spdb_test_founder"]; }' );
eval( 'function smc_is_trusted_publisher( $user_id ) { return (bool) $GLOBALS["spdb_test_trusted"]; }' );

$GLOBALS['spdb_test_capabilities']['spdb_view_dashboard']   = true;
$GLOBALS['spdb_test_capabilities']['spdb_view_own_content'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_run_system_check'] = true;
$GLOBALS['spdb_test_member_status']                         = 'approved';

$resolver = new SPDB_Workspace_Resolver();

$GLOBALS['spdb_test_founder'] = true;
$workspace = $resolver->resolve( 7 );
spdb_core_assert( 'founder' === $workspace['key'], 'Approved Founder must receive the Founder workspace.' );
spdb_core_assert( false === $workspace['read_only'], 'Founder workspace must not be marked read-only.' );

$GLOBALS['spdb_test_founder'] = false;
$GLOBALS['spdb_test_trusted'] = true;
$workspace = $resolver->resolve( 7 );
spdb_core_assert( 'trusted_doctor' === $workspace['key'], 'Trusted publisher must receive the trusted-doctor workspace.' );

$GLOBALS['spdb_test_trusted'] = false;
$workspace = $resolver->resolve( 7 );
spdb_core_assert( 'doctor' === $workspace['key'], 'Approved doctor must receive the doctor workspace.' );

$other_user_workspace = $resolver->resolve( 99 );
spdb_core_assert( 'denied' === $other_user_workspace['key'], 'Current-user capability resolution must deny another user ID.' );
spdb_core_assert( 'unknown' === $other_user_workspace['account_status'], 'Another user membership status must not be projected.' );

$GLOBALS['spdb_test_member_status'] = 'submitted';
$workspace = $resolver->resolve( 7 );
spdb_core_assert( 'restricted' === $workspace['key'], 'Pending account must receive the restricted workspace.' );
spdb_core_assert( true === $workspace['read_only'], 'Pending workspace must be read-only.' );

$GLOBALS['spdb_test_capabilities']['spdb_view_dashboard'] = false;
$workspace = $resolver->resolve( 7 );
spdb_core_assert( 'denied' === $workspace['key'], 'Dashboard access must be denied without the explicit view capability.' );

$GLOBALS['spdb_test_capabilities']['spdb_view_dashboard'] = true;
$GLOBALS['spdb_test_member_status'] = 'approved';

spdb_core_assert( 'overview' === SPDB_Dashboard_Router::normalize_view( 'unknown' ), 'Unknown dashboard views must fall back to overview.' );
spdb_core_assert( 'workspace' === SPDB_Dashboard_Router::normalize_view( 'workspace' ), 'Implemented role-workspace route must be accepted.' );
spdb_core_assert( 'inventory' === SPDB_Dashboard_Router::normalize_view( 'inventory' ), 'Implemented inventory route must be accepted.' );
spdb_core_assert( 'saved-views' === SPDB_Dashboard_Router::normalize_view( 'saved-views' ), 'Implemented saved-views route must be accepted.' );
spdb_core_assert( 'https://example.test/publishing-dashboard/' === SPDB_Dashboard_Router::route_url(), 'Canonical dashboard URL must be stable.' );
spdb_core_assert( false !== strpos( SPDB_Dashboard_Router::route_url( 'system-status' ), 'view=system-status' ), 'Implemented subview URL must use an allowlisted query value.' );

$GLOBALS['wp']->query_vars[ SPDB_Dashboard_Router::QUERY_VAR ] = '1';
spdb_core_assert( SPDB_Dashboard_Router::is_dashboard_request(), 'Early WordPress query vars must identify the protected dashboard route.' );
$GLOBALS['wp']->query_vars = array();
$GLOBALS['spdb_test_query_vars'][ SPDB_Dashboard_Router::QUERY_VAR ] = '1';
spdb_core_assert( SPDB_Dashboard_Router::is_dashboard_request(), 'Final WP_Query vars must identify the protected dashboard route.' );
$GLOBALS['spdb_test_query_vars'] = array();

$invalid = SPDB_Saved_Views::normalize_definition( array( 'label' => '' ) );
spdb_core_assert( 'spdb_invalid_saved_view_label' === spdb_core_error_code( $invalid ), 'Blank saved-view labels must be rejected.' );

$invalid_label_shape = SPDB_Saved_Views::normalize_definition( array( 'label' => array( 'not', 'scalar' ) ) );
spdb_core_assert( 'spdb_invalid_saved_view_label' === spdb_core_error_code( $invalid_label_shape ), 'Nested saved-view labels must be rejected without conversion warnings.' );

$sensitive_label = SPDB_Saved_Views::normalize_definition( array( 'label' => 'Patient patient@example.com' ) );
spdb_core_assert( 'spdb_sensitive_saved_view_label' === spdb_core_error_code( $sensitive_label ), 'Contact details must be rejected from saved-view labels.' );

$invalid_filters_shape = SPDB_Saved_Views::normalize_definition( array( 'label' => 'Bad filters', 'filters' => 'status=draft' ) );
spdb_core_assert( 'spdb_invalid_saved_view_filter' === spdb_core_error_code( $invalid_filters_shape ), 'Saved-view filters must be a structured object.' );

$invalid_nested_filter = SPDB_Saved_Views::normalize_definition(
	array(
		'label'   => 'Nested filter',
		'filters' => array( 'status' => array( array( 'draft' ) ) ),
	)
);
spdb_core_assert( 'spdb_invalid_saved_view_filter' === spdb_core_error_code( $invalid_nested_filter ), 'Nested saved-view filter values must be rejected.' );

$invalid_sort = SPDB_Saved_Views::normalize_definition(
	array(
		'label'   => 'Invalid sort',
		'filters' => array( 'sort' => 'delete-everything' ),
	)
);
spdb_core_assert( 'spdb_invalid_saved_view_sort' === spdb_core_error_code( $invalid_sort ), 'Unregistered sort values must be rejected.' );

$invalid_date = SPDB_Saved_Views::normalize_definition(
	array(
		'label'   => 'Invalid date',
		'filters' => array( 'date_from' => '2026-02-31' ),
	)
);
spdb_core_assert( 'spdb_invalid_saved_view_date' === spdb_core_error_code( $invalid_date ), 'Impossible saved-view dates must be rejected.' );

$sensitive_filter = SPDB_Saved_Views::normalize_definition(
	array(
		'label'   => 'Private filter',
		'filters' => array( 'provider' => 'https://example.test/private?token=secret' ),
	)
);
spdb_core_assert( 'spdb_sensitive_saved_view_filter' === spdb_core_error_code( $sensitive_filter ), 'URLs and tokens must not enter saved-view filters.' );

$normalized = SPDB_Saved_Views::normalize_definition(
	array(
		'label'   => 'Scheduled articles',
		'filters' => array(
			'status'     => 'scheduled',
			'provider'   => 'file21',
			'patient_id' => 'must-not-persist',
			'unknown'    => 'ignored',
			'date_from'  => '2026-07-01',
			'direction'  => 'desc',
		),
	)
);
spdb_core_assert( is_array( $normalized ), 'Valid saved-view definition must normalize.' );
spdb_core_assert( ! isset( $normalized['filters']['patient_id'] ), 'Patient identifiers must not enter saved-view storage.' );
spdb_core_assert( ! isset( $normalized['filters']['unknown'] ), 'Unregistered filter keys must be discarded.' );
spdb_core_assert( 'scheduled' === $normalized['filters']['status'], 'Allowlisted operational filters must be retained.' );
spdb_core_assert( '2026-07-01' === $normalized['filters']['date_from'], 'Valid ISO dates must be retained.' );
spdb_core_assert( 'desc' === $normalized['filters']['direction'], 'Allowlisted sort direction must be retained.' );

$GLOBALS['spdb_test_user_meta'][7]['spdb_saved_views_v1'] = array(
	array(
		'id'         => 'view_123e4567e89b12d3a456426614174000',
		'label'      => '<b>My view</b>',
		'filters'    => array( 'status' => 'draft', 'patient_id' => 'forbidden' ),
		'created_at' => '2026-07-30 02:27:00',
		'version'    => 1,
	),
);
$saved_views = new SPDB_Saved_Views();
$stored      = $saved_views->get_for_user( 7 );
spdb_core_assert( 'My view' === $stored[0]['label'], 'Stored labels must be sanitized before projection.' );
spdb_core_assert( ! isset( $stored[0]['filters']['patient_id'] ), 'Stored filters must be revalidated before projection.' );
spdb_core_assert( array() === $saved_views->get_for_user( 99 ), 'Saved-view projection must remain bound to the current user.' );
spdb_core_assert( $saved_views->validate_view_id( 'view_123e4567e89b12d3a456426614174000' ), 'Canonical saved-view IDs must validate.' );
spdb_core_assert( ! $saved_views->validate_view_id( 'view_invalid' ), 'Malformed saved-view IDs must be rejected.' );

$reflection = new ReflectionClass( SPDB_Saved_Views::class );
$compare    = $reflection->getMethod( 'compare_and_store' );
$compare->setAccessible( true );
$raw_before = $GLOBALS['spdb_test_user_meta'][7]['spdb_saved_views_v1'];
$GLOBALS['spdb_test_force_meta_conflict'] = true;
$conflict = $compare->invoke( $saved_views, 7, $raw_before, array() );
spdb_core_assert( 'spdb_saved_view_conflict' === spdb_core_error_code( $conflict ), 'Concurrent saved-view updates must return a conflict instead of silently overwriting.' );
$GLOBALS['spdb_test_user_meta'][7]['spdb_saved_views_v1'] = $raw_before;

$GLOBALS['spdb_test_member_status'] = 'submitted';
spdb_core_assert( true === $saved_views->read_permission_check(), 'Restricted account may list existing saved views.' );
$restricted_write = $saved_views->write_permission_check();
spdb_core_assert( 'spdb_saved_views_read_only' === spdb_core_error_code( $restricted_write ), 'Restricted account must not create or delete saved views.' );

$GLOBALS['spdb_test_member_status'] = 'approved';
spdb_core_assert( true === $saved_views->write_permission_check(), 'Approved account may pass the personal saved-view mutation gate.' );

$registry      = new SPDB_Adapter_Registry();
$state_service = new SPDB_System_State( $registry );
$workspace     = $resolver->resolve( 7 );
$state         = $state_service->snapshot( $workspace );
spdb_core_assert( 0 === $state['provider_count'], 'An empty registry must report zero providers without fabricated counts.' );
spdb_core_assert( false === $state['production_writes'], 'Phase 23D system state must declare production writes disabled.' );
spdb_core_assert( '23D' === $state['phase'], 'System state must identify the active implementation phase.' );

$overview = ( new SPDB_Overview_Service( $state_service ) )->build( $workspace );
spdb_core_assert( 4 === count( $overview['cards'] ), 'Overview must provide the bounded dashboard summary cards.' );
spdb_core_assert( ! empty( $overview['alerts'] ), 'No-provider state must produce an explicit truthful notice.' );

$navigation = $resolver->navigation( $workspace );
spdb_core_assert( isset( $navigation['overview'], $navigation['workspace'], $navigation['inventory'], $navigation['saved-views'], $navigation['system-status'] ), 'Only implemented and authorized dashboard destinations must be exposed.' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} dashboard-core tests failed.\n" );
	exit( 1 );
}

echo "All {$tests} File 23 dashboard-core tests passed.\n";
