<?php
/**
 * Executable Phase 23D Founder and Doctor workspace tests.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/fixtures/class-test-workspace-adapter.php';

$tests  = 0;
$failed = 0;

function spdb_workspace_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

function spdb_workspace_error_code( $value ): string {
	return $value instanceof WP_Error ? $value->get_error_code() : '';
}

if ( ! defined( 'SMC_VERSION' ) ) {
	define( 'SMC_VERSION', '1.0.1' );
}
if ( ! function_exists( 'smc_user_status' ) ) {
	function smc_user_status( $user_id ) { return (string) $GLOBALS['spdb_test_member_status']; }
}
if ( ! function_exists( 'smc_is_founder' ) ) {
	function smc_is_founder( $user_id ) { return (bool) $GLOBALS['spdb_test_founder']; }
}
if ( ! function_exists( 'smc_is_trusted_publisher' ) ) {
	function smc_is_trusted_publisher( $user_id ) { return (bool) $GLOBALS['spdb_test_trusted']; }
}

$GLOBALS['spdb_test_member_status'] = 'approved';
$GLOBALS['spdb_test_user_id']       = 7;
$GLOBALS['spdb_test_founder']       = false;
$GLOBALS['spdb_test_trusted']       = false;
$GLOBALS['spdb_test_environment']   = 'production';
$GLOBALS['spdb_test_capabilities']  = array(
	'spdb_view_dashboard'    => true,
	'spdb_view_own_content'  => true,
	'spdb_manage_own_content'=> true,
);

$valid_workspace = array(
	'cards' => array(
		array(
			'key'              => 'drafts',
			'label'            => 'Drafts',
			'value'            => 4,
			'note'             => 'Native measured count',
			'priority'         => 'information',
			'data_status'      => 'measured',
			'source_timestamp' => '2026-07-30T09:30:00Z',
			'scope'            => 'own',
			'owner_user_id'    => 7,
		),
	),
	'actions' => array(
		array(
			'key'                 => 'create_article',
			'label'               => 'Create Article',
			'description'         => 'Open the native Composer.',
			'action_type'         => 'professional_create',
			'destination'         => 'https://example.test/composer/new/?type=article',
			'required_capability' => 'spdb_manage_own_content',
			'mutating'            => true,
			'founder_only'        => false,
			'scope'               => 'own',
			'owner_user_id'       => 7,
		),
		array(
			'key'                 => 'official_news',
			'label'               => 'Create Official News',
			'description'         => 'Open the native official Composer.',
			'action_type'         => 'official_create',
			'destination'         => 'https://example.test/composer/new/?type=official_news',
			'required_capability' => 'spdb_manage_own_content',
			'mutating'            => true,
			'founder_only'        => true,
			'scope'               => 'own',
			'owner_user_id'       => 7,
		),
	),
	'activity' => array(
		array(
			'key'          => 'draft_updated',
			'label'        => 'Draft updated',
			'level'        => 'information',
			'occurred_at'  => '2026-07-30T09:31:00Z',
			'scope'        => 'own',
			'owner_user_id'=> 7,
		),
	),
	'alerts' => array(),
	'profile' => array(
		'owner_user_id'      => 7,
		'completion_percent' => 90,
		'eligibility'        => 'eligible',
		'verification_state' => 'verified',
		'edit_destination'   => 'https://example.test/profile/edit/',
		'public_destination' => 'https://example.test/doctors/doctor-seven/',
		'source_timestamp'   => '2026-07-30T09:29:00Z',
	),
	'knowledge' => array(
		'owner_user_id'    => 7,
		'linked_items'     => 12,
		'unlinked_items'   => 2,
		'successful_cases' => 3,
		'destination'      => 'https://example.test/knowledge/doctor-seven/',
		'source_timestamp' => '2026-07-30T09:28:00Z',
	),
);

$registry = new SPDB_Adapter_Registry(
	array( 'workspace_provider' => SPDB_Adapter_Registry::ACCEPTANCE_PRODUCTION_ACCEPTED )
);
$adapter = new SPDB_Test_Workspace_Adapter( array( 'workspace' => $valid_workspace ) );
spdb_workspace_assert( true === $registry->register( $adapter ), 'A valid workspace adapter must register.' );

$service = new SPDB_Role_Workspace_Service( $registry );
$doctor_workspace = array(
	'key'            => 'doctor',
	'label'          => 'Doctor Publishing Workspace',
	'read_only'      => false,
	'account_status' => 'approved',
	'user_id'        => 7,
);
$projection = $service->build( $doctor_workspace );

spdb_workspace_assert( 'doctor_reviewed' === $projection['publishing_policy']['mode'], 'A regular doctor must receive the reviewed publishing policy.' );
spdb_workspace_assert( 1 === count( $projection['cards'] ), 'A valid measured native card must be projected.' );
spdb_workspace_assert( '4' === $projection['cards'][0]['value'], 'Measured native card values must remain truthful.' );
spdb_workspace_assert( 1 === count( $projection['actions'] ), 'A doctor must not receive the Founder-only official action.' );
spdb_workspace_assert( 'professional_create' === $projection['actions'][0]['action_type'], 'A doctor may receive an accepted professional native launch action.' );
spdb_workspace_assert( 1 === $projection['blocked_action_count'], 'The Founder-only official action must be counted as gated.' );
spdb_workspace_assert( 90 === $projection['profiles'][0]['completion_percent'], 'Profile completion must come from the validated native projection.' );
spdb_workspace_assert( 3 === $projection['knowledge'][0]['successful_cases'], 'Successful-case portfolio counts must be native measured data.' );
spdb_workspace_assert( 7 === $GLOBALS['spdb_test_workspace_context']['user_id'], 'The workspace adapter context must use the server-derived current user.' );
spdb_workspace_assert( array( 'own' ) === $GLOBALS['spdb_test_workspace_context']['allowed_scopes'], 'A doctor must receive own scope only.' );

$GLOBALS['spdb_test_founder'] = true;
$founder_workspace = $doctor_workspace;
$founder_workspace['key']   = 'founder';
$founder_workspace['label'] = 'Founder Publishing Workspace';
$founder_projection = $service->build( $founder_workspace );
spdb_workspace_assert( 'founder_official' === $founder_projection['publishing_policy']['mode'], 'The server-verified Founder must receive the official publishing policy.' );
spdb_workspace_assert( 2 === count( $founder_projection['actions'] ), 'The Founder may receive both professional and official accepted native launch actions.' );
spdb_workspace_assert( array( 'own', 'institution' ) === $GLOBALS['spdb_test_workspace_context']['allowed_scopes'], 'Only the Founder context may expose institution scope.' );

$GLOBALS['spdb_test_founder']       = false;
$GLOBALS['spdb_test_member_status'] = 'submitted';
$restricted_workspace = $doctor_workspace;
$restricted_workspace['key']            = 'restricted';
$restricted_workspace['label']          = 'Restricted Read-Only Workspace';
$restricted_workspace['read_only']      = true;
$restricted_workspace['account_status'] = 'submitted';
$restricted_projection = $service->build( $restricted_workspace );
spdb_workspace_assert( array() === $restricted_projection['actions'], 'A restricted account must not receive mutating native launch actions.' );
spdb_workspace_assert( 2 === $restricted_projection['blocked_action_count'], 'All mutating actions must be gated for a restricted account.' );
spdb_workspace_assert( 'restricted' === $restricted_projection['publishing_policy']['mode'], 'A pending account must receive restricted publishing status.' );

$GLOBALS['spdb_test_member_status'] = 'approved';
$context = array(
	'user_id'       => 7,
	'is_founder'    => false,
	'is_approved'   => true,
	'is_trusted'    => false,
	'read_only'     => false,
	'workspace_key' => 'doctor',
);
$metadata = $registry->metadata( 'workspace_provider' );

$wrong_owner = $valid_workspace;
$wrong_owner['cards'][0]['owner_user_id'] = 8;
$invalid = SPDB_Workspace_Projection_Validator::normalize( $wrong_owner, 'workspace_provider', $metadata, $context );
spdb_workspace_assert( 'spdb_workspace_owner_mismatch' === spdb_workspace_error_code( $invalid ), 'Cross-doctor own-scope projections must be rejected.' );

$institution_for_doctor = $valid_workspace;
$institution_for_doctor['cards'][0]['scope'] = 'institution';
$institution_for_doctor['cards'][0]['owner_user_id'] = 0;
$invalid = SPDB_Workspace_Projection_Validator::normalize( $institution_for_doctor, 'workspace_provider', $metadata, $context );
spdb_workspace_assert( 'spdb_workspace_institution_scope_forbidden' === spdb_workspace_error_code( $invalid ), 'Institution scope must be rejected for a doctor.' );

$unsafe_destination = $valid_workspace;
$unsafe_destination['actions'][0]['destination'] = 'https://example.test/composer/?token=secret';
$invalid = SPDB_Workspace_Projection_Validator::normalize( $unsafe_destination, 'workspace_provider', $metadata, $context );
spdb_workspace_assert( 'spdb_workspace_destination_secret' === spdb_workspace_error_code( $invalid ), 'Secret-bearing native destinations must be rejected.' );

$external_destination = $valid_workspace;
$external_destination['profile']['public_destination'] = 'https://external.example/doctors/7/';
$invalid = SPDB_Workspace_Projection_Validator::normalize( $external_destination, 'workspace_provider', $metadata, $context );
spdb_workspace_assert( 'spdb_workspace_destination_origin' === spdb_workspace_error_code( $invalid ), 'Cross-origin profile destinations must be rejected.' );

$ambiguous_time = $valid_workspace;
$ambiguous_time['cards'][0]['source_timestamp'] = 'tomorrow';
$invalid = SPDB_Workspace_Projection_Validator::normalize( $ambiguous_time, 'workspace_provider', $metadata, $context );
spdb_workspace_assert( 'spdb_workspace_card_timestamp_invalid' === spdb_workspace_error_code( $invalid ), 'Ambiguous measured-card timestamps must be rejected.' );

$patient_data = $valid_workspace;
$patient_data['activity'][0]['label'] = 'Patient 0300-1234567 updated';
$invalid = SPDB_Workspace_Projection_Validator::normalize( $patient_data, 'workspace_provider', $metadata, $context );
spdb_workspace_assert( 'spdb_workspace_activity_label_invalid' === spdb_workspace_error_code( $invalid ), 'Patient-identifying activity labels must be rejected.' );

$unaccepted_registry = new SPDB_Adapter_Registry();
$unaccepted_adapter  = new SPDB_Test_Workspace_Adapter( array( 'workspace' => $valid_workspace ) );
$unaccepted_registry->register( $unaccepted_adapter );
$unaccepted_projection = ( new SPDB_Role_Workspace_Service( $unaccepted_registry ) )->build( $doctor_workspace );
spdb_workspace_assert( array() === $unaccepted_projection['actions'], 'Mutating launch actions must remain hidden for an unaccepted provider.' );

$failure_registry = new SPDB_Adapter_Registry();
$failure_registry->register( new SPDB_Test_Workspace_Adapter( array( 'workspace' => $valid_workspace ) ) );
$failure_registry->register(
	new SPDB_Test_Workspace_Adapter(
		array(
			'provider_key'       => 'failing_workspace_provider',
			'provider_name'      => 'Failing Workspace Provider',
			'throw_on_workspace' => true,
		)
	)
);
$failure_projection = ( new SPDB_Role_Workspace_Service( $failure_registry ) )->build( $doctor_workspace );
spdb_workspace_assert( 2 === $failure_projection['provider_count'], 'A failing provider must not prevent another workspace provider from being queried.' );
spdb_workspace_assert( 1 === $failure_projection['provider_errors'], 'Provider failures must be counted without exposing exception details.' );
spdb_workspace_assert( 1 === count( $failure_projection['cards'] ), 'Valid provider data must remain available when another provider fails.' );

$empty_projection = ( new SPDB_Role_Workspace_Service( new SPDB_Adapter_Registry() ) )->build( $doctor_workspace );
spdb_workspace_assert( array() === $empty_projection['cards'], 'No-provider state must not fabricate summary counts.' );
spdb_workspace_assert( array() === $empty_projection['actions'], 'No-provider state must not fabricate native actions.' );
spdb_workspace_assert( ! empty( $empty_projection['alerts'] ), 'No-provider state must provide a truthful notice.' );

spdb_workspace_assert( 'workspace' === SPDB_Dashboard_Router::normalize_view( 'workspace' ), 'The protected role-workspace view must be allowlisted.' );
spdb_workspace_assert( false !== strpos( SPDB_Dashboard_Router::route_url( 'workspace' ), 'view=workspace' ), 'The role-workspace URL must use the protected dashboard route.' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} Phase 23D workspace tests failed.\n" );
	exit( 1 );
}

echo "All {$tests} Phase 23D role-workspace tests passed.\n";
