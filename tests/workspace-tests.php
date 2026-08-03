<?php
/** Executable corrective Phase 23D Founder and Doctor workspace tests. */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/fixtures/class-test-workspace-adapter.php';
$tests = 0; $failed = 0;
function spdb_workspace_assert( bool $condition, string $message ): void { global $tests, $failed; ++$tests; if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); } }
function spdb_workspace_error_code( $value ): string { return $value instanceof WP_Error ? $value->get_error_code() : ''; }
if ( ! defined( 'SMC_VERSION' ) ) { define( 'SMC_VERSION', '1.0.1' ); }
if ( ! function_exists( 'smc_user_status' ) ) { function smc_user_status( $user_id ) { return (string) $GLOBALS['spdb_test_member_status']; } }
if ( ! function_exists( 'smc_is_founder' ) ) { function smc_is_founder( $user_id ) { return (bool) $GLOBALS['spdb_test_founder']; } }
if ( ! function_exists( 'smc_is_trusted_publisher' ) ) { function smc_is_trusted_publisher( $user_id ) { return (bool) $GLOBALS['spdb_test_trusted']; } }
$GLOBALS['spdb_test_member_status'] = 'approved';
$GLOBALS['spdb_test_user_id'] = 7;
$GLOBALS['spdb_test_founder'] = false;
$GLOBALS['spdb_test_trusted'] = false;
$GLOBALS['spdb_test_environment'] = 'production';
$GLOBALS['spdb_test_capabilities'] = array( 'spdb_view_dashboard' => true, 'spdb_view_own_content' => true, 'spdb_manage_own_content' => true );

$valid_workspace = array(
	'cards' => array( array( 'key' => 'drafts', 'label' => 'Drafts', 'value' => 4, 'note' => 'Native measured count', 'priority' => 'information', 'data_status' => 'measured', 'source_timestamp' => '2026-07-30T09:30:00Z', 'scope' => 'own', 'owner_user_id' => 7 ) ),
	'actions' => array(
		array( 'key' => 'create_article', 'label' => 'Create Article', 'description' => 'Open the native Composer.', 'action_type' => 'professional_create', 'destination' => 'https://example.test/composer/new/?type=article', 'required_capability' => 'spdb_manage_own_content', 'mutating' => true, 'founder_only' => false, 'scope' => 'own', 'owner_user_id' => 7 ),
		array( 'key' => 'official_news', 'label' => 'Create Official News', 'description' => 'Open the native official Composer.', 'action_type' => 'official_create', 'destination' => 'https://example.test/composer/new/?type=official_news', 'required_capability' => 'spdb_manage_own_content', 'mutating' => true, 'founder_only' => true, 'scope' => 'own', 'owner_user_id' => 7 ),
	),
	'activity' => array( array( 'key' => 'draft_updated', 'label' => 'Draft updated', 'level' => 'information', 'occurred_at' => '2026-07-30T09:31:00Z', 'scope' => 'own', 'owner_user_id' => 7 ) ),
	'alerts' => array(),
	'profile' => array( 'owner_user_id' => 7, 'completion_percent' => 90, 'eligibility' => 'eligible', 'verification_state' => 'verified', 'edit_destination' => 'https://example.test/profile/edit/', 'public_destination' => 'https://example.test/doctors/doctor-seven/', 'source_timestamp' => '2026-07-30T09:29:00Z' ),
	'knowledge' => array( 'owner_user_id' => 7, 'linked_items' => 12, 'unlinked_items' => 2, 'successful_cases' => 3, 'destination' => 'https://example.test/knowledge/doctor-seven/', 'source_timestamp' => '2026-07-30T09:28:00Z' ),
);
$registry = new SPDB_Adapter_Registry( array( 'workspace_provider' => SPDB_Adapter_Registry::ACCEPTANCE_PRODUCTION_ACCEPTED ) );
spdb_workspace_assert( true === $registry->register( new SPDB_Test_Workspace_Adapter( array( 'workspace' => $valid_workspace ) ) ), 'A valid workspace adapter must register.' );
$service = new SPDB_Role_Workspace_Service( $registry );
$input = array( 'key' => 'doctor', 'label' => 'Doctor Publishing Workspace', 'read_only' => false, 'account_status' => 'approved', 'user_id' => 7 );
$projection = $service->build( $input );
spdb_workspace_assert( 'doctor' === $projection['workspace_key'], 'File 00 must determine the Doctor workspace.' );
spdb_workspace_assert( 'doctor_reviewed' === $projection['publishing_policy']['mode'], 'A regular doctor must receive reviewed publishing policy.' );
spdb_workspace_assert( '4' === $projection['cards'][0]['value'], 'Measured values must remain truthful.' );
spdb_workspace_assert( 4 === count( $projection['actions'] ), 'Doctor receives professional, profile, public-profile, and knowledge actions, not official action.' );
spdb_workspace_assert( 1 === $projection['blocked_action_count'], 'Founder action must be gated.' );
spdb_workspace_assert( ! isset( $projection['profiles'][0]['edit_destination'], $projection['profiles'][0]['public_destination'] ), 'Profile destinations must not bypass the action gate.' );
spdb_workspace_assert( ! isset( $projection['knowledge'][0]['destination'] ), 'Knowledge destination must not bypass the action gate.' );
spdb_workspace_assert( array( 'own' ) === $GLOBALS['spdb_test_workspace_context']['allowed_scopes'], 'Doctor context is own-scope only.' );
spdb_workspace_assert( false !== strpos( $projection['generated_at_gmt'], 'T' ), 'Generation time must be RFC 3339-compatible.' );

$forged = $input; $forged['key'] = 'founder'; $forged['read_only'] = false; $forged['account_status'] = 'verified';
$forged_projection = $service->build( $forged );
spdb_workspace_assert( 'doctor' === $forged_projection['workspace_key'], 'Forged Founder workspace input must be ignored.' );
spdb_workspace_assert( 'doctor_reviewed' === $forged_projection['publishing_policy']['mode'], 'Forged Founder input must not expose Founder policy.' );

$GLOBALS['spdb_test_founder'] = true;
$founder = $service->build( $input );
spdb_workspace_assert( 'founder' === $founder['workspace_key'], 'Server-verified Founder must receive Founder workspace.' );
spdb_workspace_assert( 'founder_official' === $founder['publishing_policy']['mode'], 'Founder must receive official policy.' );
spdb_workspace_assert( 5 === count( $founder['actions'] ), 'Founder may receive all accepted actions.' );
spdb_workspace_assert( array( 'own', 'institution' ) === $GLOBALS['spdb_test_workspace_context']['allowed_scopes'], 'Only Founder receives institution scope.' );

$GLOBALS['spdb_test_founder'] = false; $GLOBALS['spdb_test_member_status'] = 'submitted';
$restricted = $service->build( $input );
spdb_workspace_assert( 'restricted' === $restricted['workspace_key'] && true === $restricted['read_only'], 'Pending File 00 status must force restricted read-only mode.' );
spdb_workspace_assert( 1 === count( $restricted['actions'] ) && 'view_public_profile' === $restricted['actions'][0]['action_type'], 'Restricted account may receive only safe non-mutating public-profile view.' );
spdb_workspace_assert( 4 === $restricted['blocked_action_count'], 'All mutating actions must be gated for restricted account.' );

$GLOBALS['spdb_test_member_status'] = 'approved';
$context = array( 'user_id' => 7, 'is_founder' => false, 'is_approved' => true, 'is_trusted' => false, 'read_only' => false, 'workspace_key' => 'doctor' );
$metadata = $registry->metadata( 'workspace_provider' );
$case = $valid_workspace; $case['cards'][0]['owner_user_id'] = 8;
spdb_workspace_assert( 'spdb_workspace_owner_mismatch' === spdb_workspace_error_code( SPDB_Workspace_Projection_Validator::normalize( $case, 'workspace_provider', $metadata, $context ) ), 'Cross-doctor projection must be rejected.' );
$case = $valid_workspace; $case['actions'][0]['mutating'] = false;
spdb_workspace_assert( 'spdb_workspace_action_contract_mismatch' === spdb_workspace_error_code( SPDB_Workspace_Projection_Validator::normalize( $case, 'workspace_provider', $metadata, $context ) ), 'Create action cannot claim non-mutating semantics.' );
$case = $valid_workspace; $case['actions'][0]['required_capability'] = 'spdb_view_own_content';
spdb_workspace_assert( 'spdb_workspace_action_contract_mismatch' === spdb_workspace_error_code( SPDB_Workspace_Projection_Validator::normalize( $case, 'workspace_provider', $metadata, $context ) ), 'Provider cannot downgrade create action capability.' );
$case_metadata = $metadata; $case_metadata['supported_capabilities'] = array( 'spdb_view_own_content' );
spdb_workspace_assert( 'spdb_workspace_action_capability_invalid' === spdb_workspace_error_code( SPDB_Workspace_Projection_Validator::normalize( $valid_workspace, 'workspace_provider', $case_metadata, $context ) ), 'Action capability must be provider-declared.' );
$case = $valid_workspace; $case['actions'][0]['destination'] = 'https://example.test/composer/?token=secret';
spdb_workspace_assert( 'spdb_workspace_destination_secret' === spdb_workspace_error_code( SPDB_Workspace_Projection_Validator::normalize( $case, 'workspace_provider', $metadata, $context ) ), 'Secret-bearing URL must be rejected.' );
$case = $valid_workspace; $case['actions'][0]['destination'] = 'https://example.test/composer/?next=https%253A%252F%252Fevil.example';
spdb_workspace_assert( 'spdb_workspace_destination_secret' === spdb_workspace_error_code( SPDB_Workspace_Projection_Validator::normalize( $case, 'workspace_provider', $metadata, $context ) ), 'Double-encoded nested URL must be rejected.' );
$case = $valid_workspace; $case['actions'][0]['destination'] = 'https://example.test/composer/?type=one&type=two';
spdb_workspace_assert( 'spdb_workspace_destination_query' === spdb_workspace_error_code( SPDB_Workspace_Projection_Validator::normalize( $case, 'workspace_provider', $metadata, $context ) ), 'Duplicate query parameters must be rejected.' );
$case = $valid_workspace; $case['profile']['source_timestamp'] = '';
spdb_workspace_assert( 'spdb_workspace_profile_timestamp_invalid' === spdb_workspace_error_code( SPDB_Workspace_Projection_Validator::normalize( $case, 'workspace_provider', $metadata, $context ) ), 'Profile source timestamp is mandatory.' );
$case = $valid_workspace; $case['profile']['verification_state'] = 'Verified Label';
spdb_workspace_assert( 'spdb_workspace_key_invalid' === spdb_workspace_error_code( SPDB_Workspace_Projection_Validator::normalize( $case, 'workspace_provider', $metadata, $context ) ), 'Malformed verification state must fail closed.' );
$case = $valid_workspace; $case['activity'][0]['label'] = 'Patient 0300-1234567 updated';
spdb_workspace_assert( 'spdb_workspace_activity_label_invalid' === spdb_workspace_error_code( SPDB_Workspace_Projection_Validator::normalize( $case, 'workspace_provider', $metadata, $context ) ), 'Patient-identifying activity must be rejected.' );

$unaccepted_registry = new SPDB_Adapter_Registry(); $unaccepted_registry->register( new SPDB_Test_Workspace_Adapter( array( 'workspace' => $valid_workspace ) ) );
$unaccepted = ( new SPDB_Role_Workspace_Service( $unaccepted_registry ) )->build( $input );
$mutating = array_filter( $unaccepted['actions'], static fn( array $action ): bool => ! empty( $action['mutating'] ) );
spdb_workspace_assert( array() === array_values( $mutating ) && 1 === count( $unaccepted['actions'] ), 'Unaccepted provider may expose only safe non-mutating view.' );

$failure_registry = new SPDB_Adapter_Registry();
$failure_registry->register( new SPDB_Test_Workspace_Adapter( array( 'workspace' => $valid_workspace ) ) );
$failure_registry->register( new SPDB_Test_Workspace_Adapter( array( 'provider_key' => 'failing_workspace_provider', 'provider_name' => 'Failing Workspace Provider', 'throw_on_workspace' => true ) ) );
$failure = ( new SPDB_Role_Workspace_Service( $failure_registry ) )->build( $input );
spdb_workspace_assert( 2 === $failure['provider_count'] && 1 === $failure['provider_errors'] && 1 === count( $failure['cards'] ), 'Provider failure must be isolated.' );

$acceptance = array(); for ( $i = 0; $i < 25; ++$i ) { $acceptance[ 'workspace_' . $i ] = SPDB_Adapter_Registry::ACCEPTANCE_PRODUCTION_ACCEPTED; }
$bounded_registry = new SPDB_Adapter_Registry( $acceptance );
for ( $i = 0; $i < 25; ++$i ) { $w = $valid_workspace; $w['cards'][0]['key'] = 'drafts_' . $i; $bounded_registry->register( new SPDB_Test_Workspace_Adapter( array( 'provider_key' => 'workspace_' . $i, 'provider_name' => 'Workspace ' . $i, 'workspace' => $w ) ) ); }
$bounded = ( new SPDB_Role_Workspace_Service( $bounded_registry ) )->build( $input );
spdb_workspace_assert( 20 === $bounded['provider_count'], 'Providers must be globally bounded.' );
spdb_workspace_assert( count( $bounded['actions'] ) <= 24 && count( $bounded['profiles'] ) <= 10 && count( $bounded['knowledge'] ) <= 10, 'Workspace output collections must be globally bounded.' );
spdb_workspace_assert( false !== strpos( implode( ' ', array_column( $bounded['alerts'], 'message' ) ), 'safety limit' ), 'Truncation must be reported.' );

$empty = ( new SPDB_Role_Workspace_Service( new SPDB_Adapter_Registry() ) )->build( $input );
spdb_workspace_assert( array() === $empty['cards'] && array() === $empty['actions'] && ! empty( $empty['alerts'] ), 'No-provider state must be truthful.' );
spdb_workspace_assert( 'workspace' === SPDB_Dashboard_Router::normalize_view( 'workspace' ), 'Workspace route must be allowlisted.' );
if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} corrective Phase 23D workspace tests failed.\n" ); exit( 1 ); }
echo "All {$tests} corrective Phase 23D role-workspace tests passed.\n";
