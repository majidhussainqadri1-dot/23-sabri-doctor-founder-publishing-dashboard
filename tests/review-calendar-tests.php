<?php
/** Executable corrective Phase 23E review and calendar tests. */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/fixtures/class-test-review-calendar-adapter.php';

$tests = 0; $failed = 0;
function spdb_rc_assert( bool $condition, string $message ): void {
	global $tests, $failed; ++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
}
function spdb_rc_error_code( $value ): string { return $value instanceof WP_Error ? $value->get_error_code() : ''; }
function spdb_rc_request( array $params, bool $with_nonce = true ): WP_REST_Request {
	if ( $with_nonce ) { $params['_wpnonce'] = 'valid-rest-nonce'; }
	return new WP_REST_Request( '', $params );
}
if ( ! defined( 'SMC_VERSION' ) ) { define( 'SMC_VERSION', '1.0.1' ); }
if ( ! function_exists( 'smc_user_status' ) ) { function smc_user_status( $user_id ) { return (string) ( $GLOBALS['spdb_test_member_statuses'][ (int) $user_id ] ?? $GLOBALS['spdb_test_member_status'] ); } }
if ( ! function_exists( 'smc_is_founder' ) ) { function smc_is_founder( $user_id ) { return (bool) $GLOBALS['spdb_test_founder']; } }
if ( ! function_exists( 'smc_is_trusted_publisher' ) ) { function smc_is_trusted_publisher( $user_id ) { return false; } }

$GLOBALS['spdb_test_user_id'] = 7;
$GLOBALS['spdb_test_member_status'] = 'approved';
$GLOBALS['spdb_test_member_statuses'][7] = 'approved';
$GLOBALS['spdb_test_founder'] = false;
$GLOBALS['spdb_test_environment'] = 'production';
$GLOBALS['spdb_test_capabilities'] = array(
	'spdb_view_dashboard'           => true,
	'spdb_view_own_content'         => true,
	'spdb_view_review_queue'        => true,
	'spdb_review_assigned_content'  => true,
	'spdb_manage_schedule'          => true,
);

$review_item = array(
	'object_type' => 'publication', 'object_id' => 'post-101', 'title' => 'Clinical education draft',
	'author_id' => 9, 'author_name' => 'Doctor Nine', 'scope' => 'institution',
	'native_version' => 'v4', 'last_synced_at' => '2026-07-30T12:00:00Z',
	'review_state' => 'awaiting_review', 'assigned_reviewer_id' => 7, 'assigned_reviewer_name' => 'Reviewer Seven',
	'due_at' => '2026-08-01T09:00:00Z', 'privacy_flags' => array(), 'safety_flags' => array(),
	'source_flags' => array( 'source_complete' ), 'copyright_flags' => array(),
	'native_review_url' => 'https://example.test/newsroom/review/post-101/',
	'allowed_operations' => array( 'approve_review', 'request_changes', 'reject_review' ),
	'separation_required' => true,
);
$calendar_item = array(
	'object_type' => 'publication', 'object_id' => 'post-201', 'title' => 'Scheduled article',
	'author_id' => 7, 'author_name' => 'Doctor Seven', 'scope' => 'own',
	'native_version' => 'v2', 'last_synced_at' => '2026-07-30T12:01:00Z',
	'status' => 'scheduled', 'scheduled_at_utc' => '2026-08-02T10:00:00Z',
	'native_timezone' => 'Asia/Karachi', 'conflicts' => array(),
	'native_edit_url' => 'https://example.test/composer/post-201/schedule/',
	'allowed_operations' => array( 'reschedule', 'unschedule' ),
);

$registry = new SPDB_Adapter_Registry( array( 'review_calendar_provider' => SPDB_Adapter_Registry::ACCEPTANCE_PRODUCTION_ACCEPTED ) );
$adapter = new SPDB_Test_Review_Calendar_Adapter( array(
	'review' => array( 'items' => array( $review_item ), 'total' => 1, 'has_more' => false ),
	'calendar' => array( 'items' => array( $calendar_item ), 'total' => 1, 'has_more' => false ),
	'allowed_operations' => array( 'approve_review', 'request_changes', 'reject_review', 'reschedule', 'unschedule' ),
) );
spdb_rc_assert( true === $registry->register( $adapter ), 'A valid Phase 23E adapter must register.' );
$service = new SPDB_Review_Calendar_Service( $registry );
$review = $service->review_queue();
spdb_rc_assert( 1 === $review['validated_count'] && 1 === $review['accessible_total'], 'A valid assigned native review item must be projected.' );
spdb_rc_assert( array( 'approve_review', 'request_changes', 'reject_review' ) === $review['items'][0]['allowed_operations'], 'Accepted review operations must remain available.' );
spdb_rc_assert( 7 === $GLOBALS['spdb_test_review_context']['user_id'], 'Review authority context must use the authenticated user.' );
/* Scheduling mutation metadata is publishing authority, so the positive calendar projection uses a current canonical Founder identity. */
$GLOBALS['spdb_test_founder'] = true;
$calendar = $service->calendar();
spdb_rc_assert( 1 === $calendar['validated_count'] && 1 === $calendar['accessible_total'], 'A valid own-scope calendar item must be projected.' );
spdb_rc_assert( 'Asia/Karachi' === $calendar['items'][0]['native_timezone'], 'Native timezone must be preserved.' );
spdb_rc_assert( array( 'reschedule', 'unschedule' ) === $calendar['items'][0]['allowed_operations'], 'Accepted schedule operations must remain available for a current publishing identity.' );
spdb_rc_assert( 1 === $GLOBALS['spdb_test_last_review_query']['page'] && 100 === $GLOBALS['spdb_test_last_review_query']['per_page'], 'Providers must receive a bounded first-page window for central pagination.' );
$GLOBALS['spdb_test_founder'] = false;

/* Query validation must fail closed, never silently broaden. */
$before = $GLOBALS['spdb_test_review_query_count'];
$invalid_query = $service->review_queue( array( 'review_state' => 'not-a-state' ) );
spdb_rc_assert( '' !== $invalid_query['query_error'] && $before === $GLOBALS['spdb_test_review_query_count'], 'Invalid review filters must not execute a broader provider query.' );
spdb_rc_assert( is_wp_error( SPDB_Review_Calendar_Validator::normalize_query( array( 'provider' => array( 'x' ) ), 'review' ) ), 'Nested filter shapes must fail closed.' );
spdb_rc_assert( is_wp_error( SPDB_Review_Calendar_Validator::normalize_query( array( 'page' => '0' ), 'review' ) ), 'Zero pagination must be rejected.' );
spdb_rc_assert( is_wp_error( SPDB_Review_Calendar_Validator::normalize_query( array( 'due_from' => '2026-08-02', 'due_to' => '2026-08-01' ), 'review' ) ), 'Reversed review date ranges must be rejected.' );
spdb_rc_assert( is_wp_error( SPDB_Review_Calendar_Validator::normalize_query( array(), 'other' ) ), 'Unknown projection surfaces must be rejected.' );

/* Projection envelope, shape, text, IDs, flags, and separation rules. */
$invalid = SPDB_Review_Calendar_Validator::normalize_review_queue( array( 'items' => array( $review_item ), 'total' => 1, 'has_more' => 'false' ), 'review_calendar_provider', $registry->metadata( 'review_calendar_provider' ), $service->context() );
spdb_rc_assert( 'spdb_review_has_more_invalid' === spdb_rc_error_code( $invalid ), 'Continuation flags must be strict booleans.' );
$missing_separation = $review_item; unset( $missing_separation['separation_required'] );
$invalid = SPDB_Review_Calendar_Validator::normalize_review_queue( array( 'items' => array( $missing_separation ), 'total' => 1 ), 'review_calendar_provider', $registry->metadata( 'review_calendar_provider' ), $service->context() );
spdb_rc_assert( 'spdb_review_separation_flag_invalid' === spdb_rc_error_code( $invalid ), 'Separation-of-duties metadata must be explicit and boolean.' );
$duplicate_flag = $review_item; $duplicate_flag['source_flags'] = array( 'source_complete', 'source_complete' );
$invalid = SPDB_Review_Calendar_Validator::normalize_review_queue( array( 'items' => array( $duplicate_flag ), 'total' => 1 ), 'review_calendar_provider', $registry->metadata( 'review_calendar_provider' ), $service->context() );
spdb_rc_assert( 'spdb_projection_flag_invalid' === spdb_rc_error_code( $invalid ), 'Duplicate flags must be rejected.' );
$duplicate_operation = $review_item; $duplicate_operation['allowed_operations'] = array( 'approve_review', 'approve_review' );
$invalid = SPDB_Review_Calendar_Validator::normalize_review_queue( array( 'items' => array( $duplicate_operation ), 'total' => 1 ), 'review_calendar_provider', $registry->metadata( 'review_calendar_provider' ), $service->context() );
spdb_rc_assert( 'spdb_projection_operation_invalid' === spdb_rc_error_code( $invalid ), 'Duplicate operation keys must be rejected.' );
$bad_id = $review_item; $bad_id['object_id'] = 'post/101';
$invalid = SPDB_Review_Calendar_Validator::normalize_review_queue( array( 'items' => array( $bad_id ), 'total' => 1 ), 'review_calendar_provider', $registry->metadata( 'review_calendar_provider' ), $service->context() );
spdb_rc_assert( 'spdb_projection_object_id_invalid' === spdb_rc_error_code( $invalid ), 'Object IDs must use the canonical projection format.' );
$markup_title = $review_item; $markup_title['title'] = '<b>Hidden markup</b>';
$invalid = SPDB_Review_Calendar_Validator::normalize_review_queue( array( 'items' => array( $markup_title ), 'total' => 1 ), 'review_calendar_provider', $registry->metadata( 'review_calendar_provider' ), $service->context() );
spdb_rc_assert( 'spdb_projection_title_invalid' === spdb_rc_error_code( $invalid ), 'Provider markup must not be silently transformed into trusted text.' );
$huge_total = SPDB_Review_Calendar_Validator::normalize_review_queue( array( 'items' => array(), 'total' => '99999999999', 'has_more' => false ), 'review_calendar_provider', $registry->metadata( 'review_calendar_provider' ), $service->context() );
spdb_rc_assert( 'spdb_review_total_invalid' === spdb_rc_error_code( $huge_total ), 'Unbounded native totals must be rejected.' );

$wrong_reviewer = $review_item; $wrong_reviewer['assigned_reviewer_id'] = 8;
$invalid = SPDB_Review_Calendar_Validator::normalize_review_queue( array( 'items' => array( $wrong_reviewer ), 'total' => 1 ), 'review_calendar_provider', $registry->metadata( 'review_calendar_provider' ), $service->context() );
spdb_rc_assert( 'spdb_review_assignment_forbidden' === spdb_rc_error_code( $invalid ), 'Another reviewer assignment must fail closed.' );
$unsafe = $calendar_item; $unsafe['native_edit_url'] = 'https://evil.example/schedule/';
$invalid = SPDB_Review_Calendar_Validator::normalize_calendar( array( 'items' => array( $unsafe ), 'total' => 1 ), 'review_calendar_provider', $registry->metadata( 'review_calendar_provider' ), $service->context() );
spdb_rc_assert( 'spdb_workspace_destination_origin' === spdb_rc_error_code( $invalid ), 'Cross-origin schedule destinations must be rejected.' );
$bad_timezone = $calendar_item; $bad_timezone['native_timezone'] = 'Karachi';
$invalid = SPDB_Review_Calendar_Validator::normalize_calendar( array( 'items' => array( $bad_timezone ), 'total' => 1 ), 'review_calendar_provider', $registry->metadata( 'review_calendar_provider' ), $service->context() );
spdb_rc_assert( 'spdb_calendar_timezone_invalid' === spdb_rc_error_code( $invalid ), 'Non-IANA timezones must be rejected.' );

/* Self-decision and semantic operation contracts. */
$self_review = $review_item; $self_review['author_id'] = 7; $self_review['author_name'] = 'Reviewer Seven'; $self_review['separation_required'] = false;
$self_registry = new SPDB_Adapter_Registry( array( 'review_calendar_provider' => SPDB_Adapter_Registry::ACCEPTANCE_PRODUCTION_ACCEPTED ) );
$self_adapter = new SPDB_Test_Review_Calendar_Adapter( array(
	'review' => array( 'items' => array( $self_review ), 'total' => 1, 'has_more' => false ),
	'allowed_operations' => array( 'approve_review', 'request_changes', 'reject_review' ),
) );
$self_registry->register( $self_adapter );
$self_service = new SPDB_Review_Calendar_Service( $self_registry );
$self_projection = $self_service->review_queue();
spdb_rc_assert( array( 'request_changes' ) === $self_projection['items'][0]['allowed_operations'], 'Self-approval and self-rejection must be blocked regardless of provider flags.' );
$self_controller = new SPDB_Review_Calendar_REST_Controller( $self_service, new SPDB_Operation_Broker( $self_registry ) );
$self_response = $self_controller->execute_operation( spdb_rc_request( array( 'provider' => 'review_calendar_provider', 'object_type' => 'publication', 'object_id' => 'post-101', 'object_version' => 'v4', 'idempotency_key' => '1234567890abcdef', 'audit_reason' => 'Independent evidence reviewed fully.' ) ), 'approve_review' );
spdb_rc_assert( 'spdb_operation_not_authorized' === spdb_rc_error_code( $self_response ), 'Direct REST calls must not bypass self-decision separation.' );

$downgrade_registry = new SPDB_Adapter_Registry( array( 'review_calendar_provider' => SPDB_Adapter_Registry::ACCEPTANCE_PRODUCTION_ACCEPTED ) );
$downgrade_adapter = new SPDB_Test_Review_Calendar_Adapter( array(
	'review' => array( 'items' => array( $review_item ), 'total' => 1, 'has_more' => false ),
	'allowed_operations' => array( 'approve_review' ),
	'operation_capability_overrides' => array( 'approve_review' => 'spdb_view_own_content' ),
) );
spdb_rc_assert( true === $downgrade_registry->register( $downgrade_adapter ), 'A syntactically valid provider definition may register for semantic revalidation.' );
spdb_rc_assert( array() === ( new SPDB_Review_Calendar_Service( $downgrade_registry ) )->review_queue()['items'][0]['allowed_operations'], 'A provider cannot downgrade File 23 operation capability semantics.' );

/* Provider acceptance and failure isolation. */
$unaccepted = new SPDB_Adapter_Registry();
$unaccepted->register( new SPDB_Test_Review_Calendar_Adapter( array( 'review' => array( 'items' => array( $review_item ), 'total' => 1, 'has_more' => false ), 'calendar' => array( 'items' => array( $calendar_item ), 'total' => 1, 'has_more' => false ), 'allowed_operations' => array( 'approve_review', 'reschedule' ) ) ) );
$unaccepted_service = new SPDB_Review_Calendar_Service( $unaccepted );
spdb_rc_assert( array() === $unaccepted_service->review_queue()['items'][0]['allowed_operations'], 'Unaccepted providers must not expose review mutations.' );
spdb_rc_assert( array() === $unaccepted_service->calendar()['items'][0]['allowed_operations'], 'Unaccepted providers must not expose schedule mutations.' );
$failure_registry = new SPDB_Adapter_Registry();
$failure_registry->register( new SPDB_Test_Review_Calendar_Adapter( array( 'throw_review' => true, 'throw_calendar' => true ) ) );
$failure_service = new SPDB_Review_Calendar_Service( $failure_registry );
spdb_rc_assert( 1 === $failure_service->review_queue()['provider_errors'], 'Review provider exceptions must be isolated.' );
spdb_rc_assert( 1 === $failure_service->calendar()['provider_errors'], 'Calendar provider exceptions must be isolated.' );

/* REST nonce, payload, version, state, privacy, target, and confirmation controls. */
$controller = new SPDB_Review_Calendar_REST_Controller( $service, new SPDB_Operation_Broker( $registry ) );
$base_params = array( 'provider' => 'review_calendar_provider', 'object_type' => 'publication', 'object_id' => 'post-101', 'object_version' => 'v4', 'idempotency_key' => '1234567890abcdef', 'audit_reason' => 'Reviewed evidence and policy requirements.' );
$response = $controller->execute_operation( spdb_rc_request( $base_params ), 'approve_review' );
spdb_rc_assert( is_array( $response ) && ! empty( $response['confirmed'] ) && ! empty( $response['native_refetched'] ), 'A native review operation must be confirmed only after a matching re-fetch.' );
spdb_rc_assert( 'approve_review' === $GLOBALS['spdb_test_last_operation']['operation'], 'Only the explicit authorized operation may reach the native provider.' );
spdb_rc_assert( 'spdb_operation_nonce_invalid' === spdb_rc_error_code( $controller->execute_operation( spdb_rc_request( $base_params, false ), 'approve_review' ) ), 'Mutation routes require a valid REST nonce.' );
$unknown = $base_params; $unknown['role'] = 'founder';
spdb_rc_assert( 'spdb_operation_payload_field_invalid' === spdb_rc_error_code( $controller->execute_operation( spdb_rc_request( $unknown ), 'approve_review' ) ), 'Unknown or authority-bearing payload fields must be rejected.' );
$wrong_version = $base_params; $wrong_version['object_version'] = 'v3';
spdb_rc_assert( 'spdb_operation_not_authorized' === spdb_rc_error_code( $controller->execute_operation( spdb_rc_request( $wrong_version ), 'approve_review' ) ), 'Stale native object versions must fail closed.' );
$pii_audit = $base_params; $pii_audit['audit_reason'] = 'Reviewed patient contact test@example.com fully.';
spdb_rc_assert( 'spdb_audit_reason_invalid' === spdb_rc_error_code( $controller->execute_operation( spdb_rc_request( $pii_audit ), 'approve_review' ) ), 'Audit reasons containing contact data must be rejected.' );
$request_changes = $base_params; $request_changes['reason_code'] = 'missing_sources';
spdb_rc_assert( 'spdb_review_note_invalid' === spdb_rc_error_code( $controller->execute_operation( spdb_rc_request( $request_changes ), 'request_changes' ) ), 'Request-changes operations require a meaningful review note.' );
$request_changes['review_note'] = 'Please add the cited native sources before resubmission.';
spdb_rc_assert( is_array( $controller->execute_operation( spdb_rc_request( $request_changes ), 'request_changes' ) ), 'A complete privacy-safe request-changes operation may proceed.' );

$calendar_controller = $controller;
$GLOBALS['spdb_test_founder'] = true;
$schedule_params = array( 'provider' => 'review_calendar_provider', 'object_type' => 'publication', 'object_id' => 'post-201', 'object_version' => 'v2', 'idempotency_key' => 'abcdef1234567890', 'audit_reason' => 'Schedule verified against the publication plan.' );
spdb_rc_assert( 'spdb_operation_payload_incomplete' === spdb_rc_error_code( $calendar_controller->execute_operation( spdb_rc_request( $schedule_params ), 'reschedule' ) ), 'Rescheduling requires UTC time and native timezone.' );
$schedule_params['scheduled_at_utc'] = '2026-02-30T10:00:00Z'; $schedule_params['native_timezone'] = 'Asia/Karachi';
spdb_rc_assert( 'spdb_schedule_timestamp_invalid' === spdb_rc_error_code( $calendar_controller->execute_operation( spdb_rc_request( $schedule_params ), 'reschedule' ) ), 'Impossible calendar dates must be rejected.' );
$schedule_params['scheduled_at_utc'] = '2026-08-03T10:00:00Z';
spdb_rc_assert( is_array( $calendar_controller->execute_operation( spdb_rc_request( $schedule_params ), 'reschedule' ) ), 'A valid reschedule operation may proceed after fresh authorization.' );

$GLOBALS['spdb_test_founder'] = false;
$assign_item = $review_item; $assign_item['allowed_operations'][] = 'assign_reviewer';
$assign_registry = new SPDB_Adapter_Registry( array( 'review_calendar_provider' => SPDB_Adapter_Registry::ACCEPTANCE_PRODUCTION_ACCEPTED ) );
$assign_registry->register( new SPDB_Test_Review_Calendar_Adapter( array( 'review' => array( 'items' => array( $assign_item ), 'total' => 1, 'has_more' => false ), 'allowed_operations' => array( 'assign_reviewer' ) ) ) );
$assign_controller = new SPDB_Review_Calendar_REST_Controller( new SPDB_Review_Calendar_Service( $assign_registry ), new SPDB_Operation_Broker( $assign_registry ) );
$assign_params = $base_params; $assign_params['reviewer_id'] = '8';
spdb_rc_assert( 'spdb_operation_not_authorized' === spdb_rc_error_code( $assign_controller->execute_operation( spdb_rc_request( $assign_params ), 'assign_reviewer' ) ), 'Reviewer assignment must remain Founder-only.' );
$GLOBALS['spdb_test_founder'] = true;
$GLOBALS['spdb_test_member_statuses'][8] = 'approved';
$GLOBALS['spdb_test_user_capabilities'][8]['spdb_review_assigned_content'] = true;
spdb_rc_assert( is_array( $assign_controller->execute_operation( spdb_rc_request( $assign_params ), 'assign_reviewer' ) ), 'Founder may assign an approved capable reviewer.' );
$GLOBALS['spdb_test_member_statuses'][8] = 'suspended';
spdb_rc_assert( 'spdb_reviewer_target_ineligible' === spdb_rc_error_code( $assign_controller->execute_operation( spdb_rc_request( $assign_params ), 'assign_reviewer' ) ), 'Suspended reviewer targets must be rejected.' );
$GLOBALS['spdb_test_founder'] = false;

$mismatch_registry = new SPDB_Adapter_Registry( array( 'review_calendar_provider' => SPDB_Adapter_Registry::ACCEPTANCE_PRODUCTION_ACCEPTED ) );
$mismatch_registry->register( new SPDB_Test_Review_Calendar_Adapter( array( 'review' => array( 'items' => array( $review_item ), 'total' => 1, 'has_more' => false ), 'allowed_operations' => array( 'approve_review' ), 'confirmation_id' => 'other-id' ) ) );
$mismatch_controller = new SPDB_Review_Calendar_REST_Controller( new SPDB_Review_Calendar_Service( $mismatch_registry ), new SPDB_Operation_Broker( $mismatch_registry ) );
spdb_rc_assert( 'spdb_native_action_failed' === spdb_rc_error_code( $mismatch_controller->execute_operation( spdb_rc_request( $base_params ), 'approve_review' ) ), 'Mismatched native confirmation references must not be reported as success.' );

/* Cross-owner calendar operation denial: identity is rejected before object authorization when no canonical publishing identity is present. */
$foreign_calendar = $calendar_item; $foreign_calendar['author_id'] = 8; $foreign_calendar['author_name'] = 'Doctor Eight';
$foreign_registry = new SPDB_Adapter_Registry( array( 'review_calendar_provider' => SPDB_Adapter_Registry::ACCEPTANCE_PRODUCTION_ACCEPTED ) );
$foreign_registry->register( new SPDB_Test_Review_Calendar_Adapter( array( 'calendar' => array( 'items' => array( $foreign_calendar ), 'total' => 1, 'has_more' => false ), 'allowed_operations' => array( 'reschedule' ) ) ) );
$foreign_controller = new SPDB_Review_Calendar_REST_Controller( new SPDB_Review_Calendar_Service( $foreign_registry ), new SPDB_Operation_Broker( $foreign_registry ) );
spdb_rc_assert( 'spdb_operation_forbidden' === spdb_rc_error_code( $foreign_controller->execute_operation( spdb_rc_request( $schedule_params ), 'reschedule' ) ), 'A non-authorized actor must be denied before another Doctor’s schedule object is evaluated.' );

/* Central pagination must be truthful and bounded. */
$many = array();
for ( $index = 1; $index <= 30; ++$index ) {
	$item = $calendar_item;
	$item['object_id'] = 'post-' . ( 300 + $index );
	$item['title'] = 'Scheduled article ' . $index;
	$item['scheduled_at_utc'] = sprintf( '2026-08-%02dT10:00:00Z', $index );
	$many[] = $item;
}
$many_registry = new SPDB_Adapter_Registry();
$many_registry->register( new SPDB_Test_Review_Calendar_Adapter( array( 'calendar' => array( 'items' => $many, 'total' => 30, 'has_more' => false ) ) ) );
$many_result = ( new SPDB_Review_Calendar_Service( $many_registry ) )->calendar( array( 'page' => '2', 'per_page' => '10' ) );
spdb_rc_assert( 30 === $many_result['accessible_total'] && 3 === $many_result['pages'] && 10 === $many_result['validated_count'], 'Federated pagination must expose reachable validated totals and page size truthfully.' );
spdb_rc_assert( 'post-311' === $many_result['items'][0]['object_id'], 'Central pagination must return the requested slice after global sorting.' );

spdb_rc_assert( 'review' === SPDB_Dashboard_Router::normalize_view( 'review' ), 'Review view must be allowlisted.' );
spdb_rc_assert( 'calendar' === SPDB_Dashboard_Router::normalize_view( 'calendar' ), 'Calendar view must be allowlisted.' );

if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} corrective Phase 23E tests failed.\n" ); exit( 1 ); }
echo "All {$tests} corrective Phase 23E review and calendar tests passed.\n";
