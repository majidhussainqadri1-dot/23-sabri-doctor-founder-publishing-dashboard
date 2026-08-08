<?php
/** Executable File 23 REST rate-limit contract tests. */
require_once __DIR__ . '/bootstrap.php';
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) { define( 'MINUTE_IN_SECONDS', 60 ); }
if ( ! defined( 'HOUR_IN_SECONDS' ) ) { define( 'HOUR_IN_SECONDS', 3600 ); }
require_once dirname( __DIR__ ) . '/includes/class-spdb-rest-rate-limiter.php';

$tests = 0;
$failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};

$assert( ! SPDB_REST_Rate_Limiter::is_file23_route( '/wp/v2/posts' ), 'Non-File-23 routes must remain outside the limiter.' );
$assert( SPDB_REST_Rate_Limiter::is_file23_route( '/spdb/v1/inventory' ), 'File 23 REST routes must be recognized.' );
$assert( null === SPDB_REST_Rate_Limiter::policy_for( '/wp/v2/posts', 'GET' ), 'Foreign REST routes must not receive File 23 policy.' );

$read = SPDB_REST_Rate_Limiter::policy_for( '/spdb/v1/inventory', 'GET' );
$assert( is_array( $read ) && 'read' === $read['key'] && 240 === $read['limit'] && 60 === $read['window'], 'Read traffic must use the bounded read bucket.' );
$write = SPDB_REST_Rate_Limiter::policy_for( '/spdb/v1/tasks', 'POST' );
$assert( is_array( $write ) && 'write' === $write['key'] && 60 === $write['limit'], 'Generic writes must use the write bucket.' );
$ai = SPDB_REST_Rate_Limiter::policy_for( '/spdb/v1/ai-assistance', 'POST' );
$assert( is_array( $ai ) && 'ai' === $ai['key'] && 20 === $ai['limit'], 'AI requests must have a tighter bucket.' );
$export = SPDB_REST_Rate_Limiter::policy_for( '/spdb/v1/exports', 'POST' );
$assert( is_array( $export ) && 'export' === $export['key'] && 10 === $export['limit'], 'Export generation must have a strict bucket.' );
$privileged = SPDB_REST_Rate_Limiter::policy_for( '/spdb/v1/provider-acceptance/file21', 'POST' );
$assert( is_array( $privileged ) && 'privileged' === $privileged['key'] && 20 === $privileged['limit'], 'Provider acceptance must use the privileged bucket.' );
$assert( 'write' === SPDB_REST_Rate_Limiter::policy_for( '/spdb/v1/settings-evil', 'POST' )['key'], 'Near-match routes must not inherit privileged classification by prefix.' );
$native_a = SPDB_REST_Rate_Limiter::policy_for( '/spdb/v1/review/file21/publication/post-1/approve', 'POST' );
$native_b = SPDB_REST_Rate_Limiter::policy_for( '/spdb/v1/calendar/file21/publication/post-999/reschedule', 'POST' );
$assert( is_array( $native_a ) && 'native-write' === $native_a['key'] && 30 === $native_a['limit'], 'Review writes must be rate-limited.' );
$assert( is_array( $native_b ) && 'native-write' === $native_b['key'] && 30 === $native_b['limit'], 'Calendar writes must be rate-limited.' );
$assert( $native_a['key'] === $native_b['key'], 'Dynamic object IDs must not create per-object evasion buckets.' );

$hash_a = SPDB_REST_Rate_Limiter::bucket_hash( 'user:7', 'write' );
$hash_b = SPDB_REST_Rate_Limiter::bucket_hash( 'user:8', 'write' );
$hash_c = SPDB_REST_Rate_Limiter::bucket_hash( 'user:7', 'read' );
$assert( 64 === strlen( $hash_a ) && 1 === preg_match( '/^[a-f0-9]{64}$/', $hash_a ), 'Rate-limit bucket IDs must be fixed cryptographic hashes.' );
$assert( $hash_a !== $hash_b && $hash_a !== $hash_c, 'Actor and policy boundaries must produce distinct buckets.' );

$root = dirname( __DIR__ );
$source = (string) file_get_contents( $root . '/includes/class-spdb-rest-rate-limiter.php' );
$bootstrap = (string) file_get_contents( $root . '/sabri-publishing-dashboard.php' );
$ops_doc = (string) file_get_contents( $root . '/docs/OPERATIONS-API.md' );
$db_doc = (string) file_get_contents( $root . '/docs/DATABASE-SCHEMA-MANIFEST.md' );

foreach ( array( 'rest_pre_dispatch', "ON DUPLICATE KEY UPDATE", 'UTC_TIMESTAMP()', 'spdb_rate_limit_exceeded', 'spdb_rate_limit_persistence_failed', "'REMOTE_ADDR'" ) as $marker ) {
	$assert( false !== strpos( $source, $marker ), "Rate-limit source is missing marker: {$marker}." );
}
$assert( false === strpos( $source, 'HTTP_X_FORWARDED_FOR' ), 'Untrusted forwarding headers must not select the anonymous rate-limit subject.' );
foreach ( array( 'class-spdb-rest-rate-limiter.php', 'SPDB_REST_Rate_Limiter::register()', "array( 'SPDB_REST_Rate_Limiter', 'activate' )" ) as $marker ) {
	$assert( false !== strpos( $bootstrap, $marker ), "Plugin bootstrap is missing rate-limit marker: {$marker}." );
}
$assert( false !== stripos( $ops_doc, 'rate limit' ), 'Operations API documentation must describe runtime rate limiting.' );
$assert( false !== strpos( $db_doc, 'spdb_rest_rate_limits' ), 'Database manifest must disclose the File 23 rate-limit counter table.' );

if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} REST rate-limit tests failed.\n" ); exit( 1 ); }
echo "All {$tests} File 23 REST rate-limit tests passed.\n";
