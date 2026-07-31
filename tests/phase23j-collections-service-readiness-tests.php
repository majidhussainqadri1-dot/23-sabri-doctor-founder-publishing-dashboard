<?php
/** Executable tests for the isolated Phase 23J Collections service readiness gate. */
require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-native-reference-readiness.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service-readiness-gate.php';

final class SPDB_23J_Plain_Resolver implements SPDB_Native_Reference_Resolver {
	public int $resolve_calls = 0;
	public function resolve_reference( string $provider_key, string $object_type, string $object_id, array $context ) {
		++$this->resolve_calls;
		return array();
	}
}

final class SPDB_23J_Readiness_Resolver implements SPDB_Native_Reference_Resolver, SPDB_Native_Reference_Readiness {
	public bool $declared_ready = false;
	/** @var mixed */
	public $snapshot = array( 'available' => true, 'ready' => false, 'code' => 'not_ready' );
	public bool $throw_on_ready = false;
	public bool $throw_on_snapshot = false;
	public int $resolve_calls = 0;

	public function is_ready(): bool {
		if ( $this->throw_on_ready ) {
			throw new RuntimeException( 'Private readiness failure.' );
		}
		return $this->declared_ready;
	}

	public function readiness_snapshot(): array {
		if ( $this->throw_on_snapshot ) {
			throw new RuntimeException( 'Private snapshot failure.' );
		}
		return $this->snapshot;
	}

	public function resolve_reference( string $provider_key, string $object_type, string $object_id, array $context ) {
		++$this->resolve_calls;
		return array();
	}
}

$tests = 0;
$failed = 0;
function spdb_23j_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}
function spdb_23j_code( $value ): string {
	return $value instanceof WP_Error ? $value->get_error_code() : '';
}

$absent = new SPDB_Collections_Service_Readiness_Gate();
$absent_snapshot = $absent->snapshot( true, true );
spdb_23j_assert( array(
	'resolver_available' => false,
	'resolver_readiness_available' => false,
	'resolver_ready' => false,
	'resolver_code' => 'resolver_absent',
	'collection_write_ready' => true,
	'knowledge_write_ready' => false,
	'any_write_ready' => true,
) === $absent_snapshot, 'Resolver absence must block knowledge writes without blocking reviewed collection writes.' );
spdb_23j_assert( 'spdb_native_reference_resolver_unavailable' === spdb_23j_code( $absent->require_knowledge_write_ready( true, true ) ), 'Resolver absence must have a distinct bounded error.' );

$plain_resolver = new SPDB_23J_Plain_Resolver();
$plain = new SPDB_Collections_Service_Readiness_Gate( $plain_resolver );
$plain_snapshot = $plain->snapshot( true, true );
spdb_23j_assert( true === $plain_snapshot['resolver_available'] && false === $plain_snapshot['resolver_readiness_available'] && false === $plain_snapshot['knowledge_write_ready'], 'A resolver without the formal readiness contract must fail closed.' );
spdb_23j_assert( 'spdb_native_reference_resolver_not_ready' === spdb_23j_code( $plain->require_knowledge_write_ready( true, true ) ), 'Missing readiness conformance must not be mistaken for resolver absence.' );
spdb_23j_assert( 0 === $plain_resolver->resolve_calls, 'The readiness gate must never execute native resolution.' );

$ready_resolver = new SPDB_23J_Readiness_Resolver();
$ready_resolver->declared_ready = true;
$ready_resolver->snapshot = array( 'available' => true, 'ready' => true, 'code' => 'ready' );
$ready = new SPDB_Collections_Service_Readiness_Gate( $ready_resolver );
$ready_snapshot = $ready->snapshot( true, true );
spdb_23j_assert( true === $ready_snapshot['resolver_ready'] && true === $ready_snapshot['knowledge_write_ready'], 'Knowledge writes require configured writes, repository readiness, and formal resolver readiness.' );
spdb_23j_assert( true === $ready->require_knowledge_write_ready( true, true ), 'A fully ready state must pass the knowledge-write gate.' );
spdb_23j_assert( 0 === $ready_resolver->resolve_calls, 'Readiness evaluation must not resolve a native object.' );

$write_disabled = $ready->snapshot( false, true );
spdb_23j_assert( false === $write_disabled['collection_write_ready'] && false === $write_disabled['knowledge_write_ready'] && false === $write_disabled['any_write_ready'], 'Disabled writes must override repository and resolver readiness.' );
spdb_23j_assert( 'spdb_phase23f_writes_disabled' === spdb_23j_code( $ready->require_knowledge_write_ready( false, true ) ), 'Write configuration denial must be evaluated first.' );

$repository_unready = $ready->snapshot( true, false );
spdb_23j_assert( false === $repository_unready['collection_write_ready'] && false === $repository_unready['knowledge_write_ready'], 'Repository unavailability must block every metadata write.' );
spdb_23j_assert( 'spdb_collections_repository_not_ready' === spdb_23j_code( $ready->require_knowledge_write_ready( true, false ) ), 'Repository readiness denial must remain distinct.' );

$unready_resolver = new SPDB_23J_Readiness_Resolver();
$unready_resolver->declared_ready = false;
$unready_resolver->snapshot = array( 'available' => true, 'ready' => false, 'code' => 'private_provider_reason' );
$unready = new SPDB_Collections_Service_Readiness_Gate( $unready_resolver );
$unready_snapshot = $unready->snapshot( true, true );
spdb_23j_assert( 'resolver_not_ready' === $unready_snapshot['resolver_code'] && false === strpos( implode( '|', $unready_snapshot ), 'private_provider_reason' ), 'Provider readiness detail must be reduced to a bounded service state.' );

$unavailable_resolver = new SPDB_23J_Readiness_Resolver();
$unavailable_resolver->declared_ready = false;
$unavailable_resolver->snapshot = array( 'available' => false, 'ready' => false, 'code' => 'private_outage' );
$unavailable = new SPDB_Collections_Service_Readiness_Gate( $unavailable_resolver );
spdb_23j_assert( 'resolver_unavailable' === $unavailable->snapshot( true, true )['resolver_code'], 'A readiness-aware but unavailable resolver requires a bounded unavailable state.' );

$disagreement = new SPDB_23J_Readiness_Resolver();
$disagreement->declared_ready = false;
$disagreement->snapshot = array( 'available' => true, 'ready' => true, 'code' => 'ready' );
$disagreement_gate = new SPDB_Collections_Service_Readiness_Gate( $disagreement );
spdb_23j_assert( false === $disagreement_gate->snapshot( true, true )['resolver_ready'], 'Declared readiness and snapshot readiness disagreement must fail closed.' );

$malformed = new SPDB_23J_Readiness_Resolver();
$malformed->declared_ready = true;
$malformed->snapshot = array( 'available' => true, 'ready' => true, 'code' => 'ready', 'secret' => 'hidden' );
$malformed_gate = new SPDB_Collections_Service_Readiness_Gate( $malformed );
spdb_23j_assert( 'resolver_readiness_invalid' === $malformed_gate->snapshot( true, true )['resolver_code'], 'Unknown readiness fields must fail closed.' );

$unsafe_code = new SPDB_23J_Readiness_Resolver();
$unsafe_code->declared_ready = false;
$unsafe_code->snapshot = array( 'available' => true, 'ready' => false, 'code' => 'Private health text!' );
$unsafe_gate = new SPDB_Collections_Service_Readiness_Gate( $unsafe_code );
spdb_23j_assert( 'resolver_readiness_invalid' === $unsafe_gate->snapshot( true, true )['resolver_code'], 'Noncanonical readiness codes must fail closed.' );

$impossible = new SPDB_23J_Readiness_Resolver();
$impossible->declared_ready = true;
$impossible->snapshot = array( 'available' => false, 'ready' => true, 'code' => 'ready' );
$impossible_gate = new SPDB_Collections_Service_Readiness_Gate( $impossible );
spdb_23j_assert( 'resolver_readiness_invalid' === $impossible_gate->snapshot( true, true )['resolver_code'], 'An unavailable readiness source cannot claim ready.' );

$exception = new SPDB_23J_Readiness_Resolver();
$exception->throw_on_ready = true;
$exception_gate = new SPDB_Collections_Service_Readiness_Gate( $exception );
$exception_snapshot = $exception_gate->snapshot( true, true );
spdb_23j_assert( 'resolver_readiness_exception' === $exception_snapshot['resolver_code'] && false === strpos( implode( '|', $exception_snapshot ), 'Private' ), 'Readiness exceptions must be isolated without relaying text.' );
spdb_23j_assert( 0 === $exception->resolve_calls, 'Readiness exceptions must not fall through to native resolution.' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} Phase 23J Collections service readiness tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} Phase 23J Collections service readiness tests passed.\n";
