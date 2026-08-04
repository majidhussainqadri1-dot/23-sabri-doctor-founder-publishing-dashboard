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

$metrics = SPDB_Operational_Projection_Validator::metrics(
	array( 'metrics' => array(
		array( 'metric_key' => 'views', 'label' => 'Views', 'definition' => 'Eligible aggregate views.', 'value' => 15, 'unit' => 'count', 'interval' => 'daily', 'cohort_count' => 3, 'privacy_threshold' => 5 ),
		array( 'metric_key' => 'saves', 'label' => 'Saves', 'definition' => 'Eligible aggregate saves.', 'value' => 8, 'unit' => 'count', 'interval' => 'daily', 'cohort_count' => 8, 'privacy_threshold' => 5 ),
	) ),
	'file21',
	5
);
$assert( is_array( $metrics ) && 2 === count( $metrics['metrics'] ), 'Valid aggregate metrics must be accepted.' );
$assert( true === $metrics['metrics'][0]['suppressed'] && null === $metrics['metrics'][0]['value'], 'Small cohorts must be suppressed.' );
$assert( false === $metrics['metrics'][1]['suppressed'] && 8 === $metrics['metrics'][1]['value'], 'Eligible aggregate must remain visible.' );

if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} validator tests failed.\n" ); exit( 1 ); }
echo "All {$tests} operational validator tests passed.\n";
