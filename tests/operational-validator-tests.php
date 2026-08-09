<?php
/** Executable strict projection and privacy-threshold tests. */
define( 'ABSPATH', __DIR__ . '/' );
final class WP_Error {
	public function __construct( private string $code, private string $message = '', private array $data = array() ) {}
	public function get_error_code(): string { return $this->code; }
}
function is_wp_error( $value ): bool { return $value instanceof WP_Error; }
function __( string $text, string $domain = '' ): string { return $text; }
function sanitize_key( string $value ): string { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $value ) ) ?? ''; }
function wp_strip_all_tags( string $value ): string { return strip_tags( $value ); }
function esc_url_raw( string $url, array $protocols = array() ): string { return filter_var( $url, FILTER_VALIDATE_URL ) ? $url : ''; }
function wp_parse_url( string $url ) { return parse_url( $url ); }
function home_url( string $path = '' ): string { return 'https://example.test/' . ltrim( $path, '/' ); }
require_once dirname( __DIR__ ) . '/includes/class-spdb-projection-validator.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-safe-destination.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-operational-projection-validator.php';

$tests = 0; $failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void { ++$tests; if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); } };
$item = array(
	'object_type' => 'source_record', 'object_id' => 'abc-1', 'native_version' => 'v2',
	'title' => '<b>Evidence source</b>', 'status' => 'broken', 'updated_at' => '2026-08-04T00:00:00Z',
	'privacy_class' => 'restricted', 'destination_url' => 'https://example.test/native/source/abc-1',
	'metadata' => array( 'license' => 'CC BY', 'patient_name' => 'must disappear', 'email' => 'hidden@example.test' ),
);
$validated = SPDB_Operational_Projection_Validator::projection_item( $item, 'file21', 'sources' );
$assert( is_array( $validated ), 'Valid operational projection must be accepted.' );
$assert( 'Evidence source' === ( $validated['title'] ?? '' ), 'Projection title must be stripped to safe text.' );
$assert( isset( $validated['metadata']['license'] ) && ! isset( $validated['metadata']['patient_name'] ) && ! isset( $validated['metadata']['email'] ), 'Sensitive projection metadata must be removed.' );
$item['destination_url'] = 'https://attacker.example/steal';
$unsafe = SPDB_Operational_Projection_Validator::projection_item( $item, 'file21', 'sources' );
$assert( is_array( $unsafe ) && '' === $unsafe['destination_url'], 'Cross-origin destination must be removed.' );
$item['destination_url'] = 'http://example.test/native/source/abc-1';
$downgrade = SPDB_Operational_Projection_Validator::projection_item( $item, 'file21', 'sources' );
$assert( is_array( $downgrade ) && '' === $downgrade['destination_url'], 'Scheme-downgrade destination must be removed.' );
$item['destination_url'] = 'https://example.test/native/source/abc-1?token=patient-secret';
$secret_destination = SPDB_Operational_Projection_Validator::projection_item( $item, 'file21', 'sources' );
$assert( is_array( $secret_destination ) && '' === $secret_destination['destination_url'], 'Secret-bearing same-origin destination must be removed.' );
$item['destination_url'] = 'https://example.test/native/source/abc-1?redirect=https%3A%2F%2Fevil.example';
$redirect_destination = SPDB_Operational_Projection_Validator::projection_item( $item, 'file21', 'sources' );
$assert( is_array( $redirect_destination ) && '' === $redirect_destination['destination_url'], 'Nested redirect destination must be removed.' );

$identifier_item = $item;
$identifier_item['destination_url'] = '';
$identifier_item['summary'] = 'Contact +92 300 1234567 for details.';
$assert( is_wp_error( SPDB_Operational_Projection_Validator::projection_item( $identifier_item, 'file21', 'sources' ) ), 'Direct contact identifiers in projection text must fail closed.' );

$message_item = array(
	'object_type' => 'conversation_alert', 'object_id' => 'msg-1', 'native_version' => 'v1',
	'title' => 'Unread conversation', 'summary' => 'One unread item.',
	'status' => 'unread', 'updated_at' => '2026-08-04T00:00:00Z', 'privacy_class' => 'private',
	'destination_url' => 'https://example.test/messages/msg-1', 'metadata' => array( 'thread_label' => 'unread' ),
);
$message_projection = SPDB_Operational_Projection_Validator::projection_item( $message_item, 'file17', 'messages' );
$assert( is_array( $message_projection ) && 'Native message' === $message_projection['title'] && '' === $message_projection['summary'] && '' === $message_projection['destination_url'] && array() === $message_projection['metadata'], 'Messages must be reduced to a non-identifying status envelope.' );
$leaking_message = $message_item;
$leaking_message['summary'] = 'Private message body with patient id must not cross this boundary.';
$assert( is_wp_error( SPDB_Operational_Projection_Validator::projection_item( $leaking_message, 'file17', 'messages' ) ), 'A provider that attempts to send a private message body or patient identifier must fail closed.' );

$appointment_item = $message_item;
$appointment_item['object_type'] = 'appointment_alert';
$appointment_item['object_id'] = 'appt-1';
$appointment_item['destination_url'] = 'https://example.test/appointments/appt-1';
$appointment_projection = SPDB_Operational_Projection_Validator::projection_item( $appointment_item, 'file08', 'appointments' );
$assert( is_array( $appointment_projection ) && 'Native appointment' === $appointment_projection['title'] && '' === $appointment_projection['summary'] && '' === $appointment_projection['destination_url'] && array() === $appointment_projection['metadata'], 'Appointments must be reduced to a non-identifying status envelope.' );

$clinical_item = $message_item;
$clinical_item['object_type'] = 'clinical_status';
$clinical_item['object_id'] = 'clinical-1';
$clinical_item['privacy_class'] = 'clinical_sensitive';
$clinical_projection = SPDB_Operational_Projection_Validator::projection_item( $clinical_item, 'file24', 'support' );
$assert( is_array( $clinical_projection ) && 'Native restricted item' === $clinical_projection['title'] && '' === $clinical_projection['summary'] && array() === $clinical_projection['metadata'], 'Clinical-sensitive projections must expose status only, never clinical content.' );

$invalid_id = $item;
$invalid_id['object_id'] = "abc\npatient";
$assert( is_wp_error( SPDB_Operational_Projection_Validator::projection_item( $invalid_id, 'file21', 'sources' ) ), 'Control characters in native object IDs must fail closed.' );
$noncanonical = $item;
$noncanonical['object_type'] = 'Source Record';
$assert( is_wp_error( SPDB_Operational_Projection_Validator::projection_item( $noncanonical, 'file21', 'sources' ) ), 'Malformed provider identity keys must be rejected rather than silently normalized.' );
$invalid_time = $item;
$invalid_time['updated_at'] = 'tomorrow';
$assert( is_wp_error( SPDB_Operational_Projection_Validator::projection_item( $invalid_time, 'file21', 'sources' ) ), 'Relative or malformed provider timestamps must fail instead of fabricating the current time.' );
$invalid_time['updated_at'] = '2026-02-30T10:00:00Z';
$assert( is_wp_error( SPDB_Operational_Projection_Validator::projection_item( $invalid_time, 'file21', 'sources' ) ), 'Impossible calendar dates must fail instead of being normalized silently.' );
$invalid_page = SPDB_Operational_Projection_Validator::projection_page( array( 'items' => array( $item, $invalid_id ), 'total' => 999, 'has_more' => true, 'generated_at' => '2026-08-04T00:00:00Z' ), 'file21', 'sources' );
$assert( is_wp_error( $invalid_page ), 'A page containing an invalid item must fail as a unit and must not leak an unvalidated total.' );

$metrics = SPDB_Operational_Projection_Validator::metrics(
	array( 'metrics' => array(
		array( 'metric_key' => 'views', 'label' => 'Views', 'definition' => 'Eligible aggregate views.', 'value' => 15, 'unit' => 'count', 'interval' => 'daily', 'cohort_count' => 3, 'privacy_threshold' => 5 ),
		array( 'metric_key' => 'saves', 'label' => 'Saves', 'definition' => 'Eligible aggregate saves.', 'value' => 8, 'unit' => 'count', 'interval' => 'daily', 'cohort_count' => 8, 'privacy_threshold' => 5 ),
	) ), 'file21', 5
);
$assert( is_array( $metrics ) && 2 === count( $metrics['metrics'] ), 'Valid aggregate metrics must be accepted.' );
$assert( true === $metrics['metrics'][0]['suppressed'] && null === $metrics['metrics'][0]['value'], 'Small cohorts must be suppressed.' );
$assert( false === $metrics['metrics'][1]['suppressed'] && 8 === $metrics['metrics'][1]['value'], 'Eligible aggregate must remain visible.' );
$invalid_metrics = SPDB_Operational_Projection_Validator::metrics( array( 'metrics' => array( array( 'metric_key' => 'views', 'label' => 'Views', 'definition' => 'Eligible aggregate views.', 'unit' => 'count', 'interval' => 'daily', 'cohort_count' => 8, 'privacy_threshold' => 5, 'generated_at' => 'not-a-time' ) ) ), 'file21', 5 );
$assert( is_wp_error( $invalid_metrics ), 'Invalid analytics timestamps and missing unsuppressed values must fail closed.' );

if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} validator tests failed.\n" ); exit( 1 ); }
echo "All {$tests} operational validator tests passed.\n";
