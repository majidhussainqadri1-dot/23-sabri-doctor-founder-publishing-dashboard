<?php
/** Behavioral and source-contract tests for the operational mutation guard. */
define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'MINUTE_IN_SECONDS', 60 );
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function wp_parse_url( $url ) { return parse_url( $url ); }
require_once dirname( __DIR__ ) . '/includes/class-spdb-operational-mutation-guard.php';

$tests = 0;
$failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
};

$assert( null !== SPDB_Operational_Mutation_Guard::route_policy( '/spdb/v1/tasks', 'POST' ), 'Task creation must be guarded.' );
$assert( null !== SPDB_Operational_Mutation_Guard::route_policy( '/spdb/v1/tasks/task_0123456789abcdef0123456789abcdef', 'PATCH' ), 'Task update must be guarded.' );
$assert( null !== SPDB_Operational_Mutation_Guard::route_policy( '/spdb/v1/delegations/delegation_0123456789abcdef0123456789abcdef/revoke', 'POST' ), 'Delegation revocation must be guarded.' );
$assert( null !== SPDB_Operational_Mutation_Guard::route_policy( '/spdb/v1/automation-rules/rule_0123456789abcdef0123456789abcdef/status', 'POST' ), 'Automation status mutation must be guarded.' );
$assert( null !== SPDB_Operational_Mutation_Guard::route_policy( '/spdb/v1/system-check/repair', 'POST' ), 'Repair mutation must be guarded.' );
$assert( null === SPDB_Operational_Mutation_Guard::route_policy( '/spdb/v1/tasks', 'GET' ), 'Read-only operations must not open mutation transactions.' );
$assert( null === SPDB_Operational_Mutation_Guard::route_policy( '/spdb/v1/review/provider/type/id/approve', 'POST' ), 'Native review operations remain under their dedicated broker guard.' );
$assert( null === SPDB_Operational_Mutation_Guard::route_policy( '/wp/v2/posts/1', 'POST' ), 'The guard must not acquire another module route.' );

$assert( SPDB_Operational_Mutation_Guard::valid_idempotency_key( 'spdb_0123456789abcdef0123456789abcdef' ), 'Canonical idempotency key must pass.' );
$assert( ! SPDB_Operational_Mutation_Guard::valid_idempotency_key( 'short' ), 'Short idempotency key must fail.' );
$assert( ! SPDB_Operational_Mutation_Guard::valid_idempotency_key( 'spdb invalid idempotency key' ), 'Whitespace in idempotency key must fail.' );
$assert( ! SPDB_Operational_Mutation_Guard::valid_idempotency_key( str_repeat( 'a', 129 ) ), 'Overlong idempotency key must fail.' );

$first = SPDB_Operational_Mutation_Guard::fingerprint_payload(
	array(
		'b' => array( 'y' => 2, 'x' => 1 ),
		'a' => 'value',
		'idempotency_key' => 'ignored-key',
		'_wpnonce' => 'ignored-nonce',
	)
);
$second = SPDB_Operational_Mutation_Guard::fingerprint_payload(
	array(
		'a' => 'value',
		'b' => array( 'x' => 1, 'y' => 2 ),
	)
);
$third = SPDB_Operational_Mutation_Guard::fingerprint_payload(
	array(
		'a' => 'changed',
		'b' => array( 'x' => 1, 'y' => 2 ),
	)
);
$assert( hash_equals( $first, $second ), 'Fingerprint must be deterministic across associative key order and transport-only fields.' );
$assert( ! hash_equals( $first, $third ), 'Fingerprint must change when business payload changes.' );

$assert( SPDB_Operational_Mutation_Guard::same_origin_value( 'https://example.test/path', 'https://example.test/' ), 'Same scheme, host and effective port must pass.' );
$assert( SPDB_Operational_Mutation_Guard::same_origin_value( 'https://example.test:443/path', 'https://example.test/' ), 'Explicit default HTTPS port must pass.' );
$assert( ! SPDB_Operational_Mutation_Guard::same_origin_value( 'http://example.test/path', 'https://example.test/' ), 'Scheme downgrade must fail.' );
$assert( ! SPDB_Operational_Mutation_Guard::same_origin_value( 'https://evil.example/path', 'https://example.test/' ), 'Cross-origin host must fail.' );
$assert( ! SPDB_Operational_Mutation_Guard::same_origin_value( 'https://example.test:444/path', 'https://example.test/' ), 'Cross-port origin must fail.' );

$start = microtime( true );
$hashes = array();
for ( $i = 0; $i < 10000; ++$i ) {
	$hashes[] = SPDB_Operational_Mutation_Guard::fingerprint_payload(
		array(
			'object_id' => 'object-' . $i,
			'version' => $i % 17,
			'filters' => array( 'language' => 0 === $i % 2 ? 'ur' : 'en', 'page' => intdiv( $i, 25 ) + 1 ),
		)
	);
}
$elapsed = microtime( true ) - $start;
$assert( 10000 === count( array_unique( $hashes ) ), '10,000 modeled operations must retain unique payload fingerprints.' );
$assert( $elapsed < 8.0, '10,000-operation fingerprint model must complete within the CI safety budget.' );

$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-spdb-operational-mutation-guard.php' );
$assert( is_string( $source ) && false !== strpos( $source, 'START TRANSACTION' ), 'Guard must start a database transaction for local writes.' );
$assert( is_string( $source ) && false !== strpos( $source, 'ROLLBACK' ) && false !== strpos( $source, 'COMMIT' ), 'Guard must contain explicit rollback and commit paths.' );
$assert( is_string( $source ) && false !== strpos( $source, 'mutation_requested' ) && false !== strpos( $source, 'append_audit' ), 'Guard must append request and outcome audit evidence.' );
$assert( is_string( $source ) && false !== strpos( $source, 'Idempotency-Key' ) && false !== strpos( $source, 'X-WP-Nonce' ), 'Guard must enforce idempotency and REST nonce headers.' );

if ( $failed ) {
	fwrite( STDERR, "{$failed} of {$tests} operational mutation guard tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} operational mutation guard tests passed in " . number_format( $elapsed, 4 ) . " seconds.\n";
