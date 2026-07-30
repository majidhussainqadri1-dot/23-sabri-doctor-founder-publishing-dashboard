<?php
/** Executable Phase 23E review and calendar tests. */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/fixtures/class-test-review-calendar-adapter.php';

$tests = 0; $failed = 0;
function spdb_rc_assert( bool $condition, string $message ): void {
	global $tests, $failed; ++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
}
if ( ! defined( 'SMC_VERSION' ) ) { define( 'SMC_VERSION', '1.0.1' ); }
if ( ! function_exists( 'smc_user_status' ) ) { function smc_user_status( $user_id ) { return (string) $GLOBALS['spdb_test_member_status']; } }
if ( ! function_exists( 'smc_is_founder' ) ) { function smc_is_founder( $user_id ) { return (bool) $GLOBALS['spdb_test_founder']; } }
if ( ! function_exists( 'smc_is_trusted_publisher' ) ) { function smc_is_trusted_publisher( $user_id ) { return false; } }

$GLOBALS['spdb_test_user_id'] = 7;
$GLOBALS['spdb_test_member_status'] = 'approved';
$GLOBALS['spdb_test_founder'] = false;
$GLOBALS['spdb_test_environment'] = 'production';
$GLOBALS['spdb_test_capabilities'] = array(
	'spdb_view_dashboard' => true,
	'spdb_view_own_content' => true,
	'spdb_view_review_queue' => true,
	'spdb_review_assigned_content' => true,
	'spdb_manage_schedule' => true,
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
$calendar = $service->calendar();
spdb_rc_assert( 1 === $review['validated_count'], 'A valid assigned native review item must be projected.' );
spdb_rc_assert( array( 'approve_review', 'request_changes', 'reject_review' ) === $review['items'][0]['allowed_operations'], 'Accepted native review operations must remain available.' );
spdb_rc_assert( 7 === $GLOBALS['spdb_test_review_context']['user_id'], 'Review authority context must use the authenticated user.' );
spdb_rc_assert( 1 === $calendar['validated_count'], 'A valid own-scope calendar item must be projected.' );
spdb_rc_assert( 'Asia/Karachi' === $calendar['items'][0]['native_timezone'], 'Native timezone must be preserved.' );
spdb_rc_assert( array( 'reschedule', 'unschedule' ) === $calendar['items'][0]['allowed_operations'], 'Accepted native schedule operations must remain available.' );

$self_review = $review_item; $self_review['author_id'] = 7; $self_review['author_name'] = 'Reviewer Seven';
$self_registry = new SPDB_Adapter_Registry( array( 'review_calendar_provider' => SPDB_Adapter_Registry::ACCEPTANCE_PRODUCTION_ACCEPTED ) );
$self_registry->register( new SPDB_Test_Review_Calendar_Adapter( array(
	'review' => array( 'items' => array( $self_review ), 'total' => 1 ),
	'allowed_operations' => array( 'approve_review', 'request_changes', 'reject_review' ),
) ) );
$self_projection = ( new SPDB_Review_Calendar_Service( $self_registry ) )->review_queue();
spdb_rc_assert( array( 'request_changes' ) === $self_projection['items'][0]['allowed_operations'], 'Separation policy must remove self-approval and self-rejection.' );

$wrong_reviewer = $review_item; $wrong_reviewer['assigned_reviewer_id'] = 8;
$invalid = SPDB_Review_Calendar_Validator::normalize_review_queue(
	array( 'items' => array( $wrong_reviewer ), 'total' => 1 ),
	'review_calendar_provider', $registry->metadata( 'review_calendar_provider' ), $service->context()
);
spdb_rc_assert( $invalid instanceof WP_Error && 'spdb_review_assignment_forbidden' === $invalid->get_error_code(), 'Another reviewer assignment must fail closed.' );

$unsafe = $calendar_item; $unsafe['native_edit_url'] = 'https://evil.example/schedule/';
$invalid = SPDB_Review_Calendar_Validator::normalize_calendar(
	array( 'items' => array( $unsafe ), 'total' => 1 ),
	'review_calendar_provider', $registry->metadata( 'review_calendar_provider' ), $service->context()
);
spdb_rc_assert( $invalid instanceof WP_Error && 'spdb_workspace_destination_origin' === $invalid->get_error_code(), 'Cross-origin schedule destinations must be rejected.' );

$bad_timezone = $calendar_item; $bad_timezone['native_timezone'] = 'Karachi';
$invalid = SPDB_Review_Calendar_Validator::normalize_calendar(
	array( 'items' => array( $bad_timezone ), 'total' => 1 ),
	'review_calendar_provider', $registry->metadata( 'review_calendar_provider' ), $service->context()
);
spdb_rc_assert( $invalid instanceof WP_Error && 'spdb_calendar_timezone_invalid' === $invalid->get_error_code(), 'Non-IANA timezones must be rejected.' );

$unaccepted = new SPDB_Adapter_Registry();
$unaccepted->register( new SPDB_Test_Review_Calendar_Adapter( array(
	'review' => array( 'items' => array( $review_item ), 'total' => 1 ),
	'calendar' => array( 'items' => array( $calendar_item ), 'total' => 1 ),
	'allowed_operations' => array( 'approve_review', 'reschedule' ),
) ) );
$unaccepted_service = new SPDB_Review_Calendar_Service( $unaccepted );
spdb_rc_assert( array() === $unaccepted_service->review_queue()['items'][0]['allowed_operations'], 'Unaccepted providers must not expose review mutations.' );
spdb_rc_assert( array() === $unaccepted_service->calendar()['items'][0]['allowed_operations'], 'Unaccepted providers must not expose schedule mutations.' );

$failure_registry = new SPDB_Adapter_Registry();
$failure_registry->register( new SPDB_Test_Review_Calendar_Adapter( array( 'throw_review' => true, 'throw_calendar' => true ) ) );
$failure_service = new SPDB_Review_Calendar_Service( $failure_registry );
spdb_rc_assert( 1 === $failure_service->review_queue()['provider_errors'], 'Review provider exceptions must be isolated.' );
spdb_rc_assert( 1 === $failure_service->calendar()['provider_errors'], 'Calendar provider exceptions must be isolated.' );

$controller = new SPDB_Review_Calendar_REST_Controller( $service, new SPDB_Operation_Broker( $registry ) );
$request = new WP_REST_Request( '', array(
	'provider' => 'review_calendar_provider', 'object_type' => 'publication', 'object_id' => 'post-101',
	'object_version' => 'v4', 'idempotency_key' => '1234567890abcdef', 'audit_reason' => 'Reviewed evidence and policy requirements.',
) );
$response = $controller->execute_operation( $request, 'approve_review' );
spdb_rc_assert( is_array( $response ) && ! empty( $response['confirmed']) && ! empty( $response['native_refetched'] ), 'A native review operation must be confirmed only after re-fetch.' );
spdb_rc_assert( 'review' === SPDB_Dashboard_Router::normalize_view( 'review' ), 'Review view must be allowlisted.' );
spdb_rc_assert( 'calendar' === SPDB_Dashboard_Router::normalize_view( 'calendar' ), 'Calendar view must be allowlisted.' );

if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} Phase 23E tests failed.\n" ); exit( 1 ); }
echo "All {$tests} Phase 23E review and calendar tests passed.\n";
