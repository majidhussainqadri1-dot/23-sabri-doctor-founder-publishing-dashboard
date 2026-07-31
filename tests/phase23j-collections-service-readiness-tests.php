<?php
/** Executable tests for the corrected Phase 23J Collections service readiness gate. */
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
	public int $ready_calls = 0;
	public int $snapshot_calls = 0;
	public int $resolve_calls = 0;

	public function is_ready(): bool {
		++$this->ready_calls;
		if ( $this->throw_on_ready ) {
			throw new RuntimeException( 'Private readiness failure.' );
		}
		return $this->declared_ready;
	}

	public function readiness_snapshot(): array {
		++$this->snapshot_calls;
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

final class SPDB_23J_Reentrant_Resolver implements SPDB_Native_Reference_Resolver, SPDB_Native_Reference_Readiness {
	public ?SPDB_Collections_Service_Readiness_Gate $gate = null;
	public string $nested_code = '';
	public bool $reenter = true;
	public int $resolve_calls = 0;

	public function is_ready(): bool {
		if ( $this->reenter && null !== $this->gate ) {
			$nested = $this->gate->snapshot( true, true );
			$this->nested_code = $nested['resolver_code'];
		}
		return ! $this->reenter;
	}

	public function readiness_snapshot(): array {
		return $this->reenter
			? array( 'available' => true, 'ready' => false, 'code' => 'not_ready' )
			: array( 'available' => true, 'ready' => true, 'code' => 'ready' );
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
	'inputs_valid' => true,
	'gate_code' => 'collection_ready',
	'resolver_available' => false,
	'resolver_readiness_available' => false,
	'resolver_ready' => false,
	'resolver_code' => 'resolver_absent',
	'collection_write_ready' => true,
	'knowledge_write_ready' => false,
	'any_write_ready' => true,
) === $absent_snapshot, 'Resolver absence must block knowledge writes without blocking reviewed collection writes.' );
spdb_23j_assert( true === $absent->require_collection_write_ready( true, true ), 'Collection writes must remain independent of native-reference readiness.' );
spdb_23j_assert( 'spdb_native_reference_resolver_unavailable' === spdb_23j_code( $absent->require_knowledge_write_ready( true, true ) ), 'Resolver absence must have a distinct bounded error.' );

$plain_resolver = new SPDB_23J_Plain_Resolver();
$plain = new SPDB_Collections_Service_Readiness_Gate( $plain_resolver );
$plain_snapshot = $plain->snapshot( true, true );
spdb_23j_assert( true === $plain_snapshot['resolver_available'] && false === $plain_snapshot['resolver_readiness_available'] && false === $plain_snapshot['knowledge_write_ready'], 'A resolver without the formal readiness contract must fail closed.' );
spdb_23j_assert( 'spdb_native_reference_readiness_missing' === spdb_23j_code( $plain->require_knowledge_write_ready( true, true ) ), 'Missing readiness conformance requires a distinct error.' );
spdb_23j_assert( 0 === $plain_resolver->resolve_calls, 'The readiness gate must never execute native resolution.' );

$invalid_input_resolver = new SPDB_23J_Readiness_Resolver();
$invalid_input_resolver->declared_ready = true;
$invalid_input_resolver->snapshot = array( 'available' => true, 'ready' => true, 'code' => 'ready' );
$invalid_input_gate = new SPDB_Collections_Service_Readiness_Gate( $invalid_input_resolver );
$invalid_input_snapshot = $invalid_input_gate->snapshot( 'false', true );
spdb_23j_assert(
	false === $invalid_input_snapshot['inputs_valid']
	&& 'gate_input_invalid' === $invalid_input_snapshot['gate_code']
	&& 'resolver_not_evaluated' === $invalid_input_snapshot['resolver_code']
	&& false === $invalid_input_snapshot['any_write_ready'],
	'Weakly coercible authority inputs must fail closed without a write-ready projection.'
);
spdb_23j_assert( 0 === $invalid_input_resolver->ready_calls && 0 === $invalid_input_resolver->snapshot_calls, 'Invalid authority inputs must not invoke resolver readiness.' );
spdb_23j_assert( 'spdb_collections_readiness_input_invalid' === spdb_23j_code( $invalid_input_gate->require_collection_write_ready( 'false', true ) ), 'Collection write gating must reject non-boolean authority inputs.' );
spdb_23j_assert( 'spdb_collections_readiness_input_invalid' === spdb_23j_code( $invalid_input_gate->require_knowledge_write_ready( true, 1 ) ), 'Knowledge write gating must reject non-boolean authority inputs before resolver evaluation.' );
spdb_23j_assert( 0 === $invalid_input_resolver->ready_calls && 0 === $invalid_input_resolver->snapshot_calls, 'Invalid knowledge-gate inputs must not invoke readiness methods.' );

$ready_resolver = new SPDB_23J_Readiness_Resolver();
$ready_resolver->declared_ready = true;
$ready_resolver->snapshot = array( 'available' => true, 'ready' => true, 'code' => 'ready' );
$ready = new SPDB_Collections_Service_Readiness_Gate( $ready_resolver );
$ready_snapshot = $ready->snapshot( true, true );
spdb_23j_assert( true === $ready_snapshot['inputs_valid'] && 'knowledge_ready' === $ready_snapshot['gate_code'] && true === $ready_snapshot['resolver_ready'] && true === $ready_snapshot['knowledge_write_ready'], 'Knowledge writes require configured writes, repository readiness, and formal resolver readiness.' );
spdb_23j_assert( 1 === $ready_resolver->ready_calls && 1 === $ready_resolver->snapshot_calls, 'One readiness decision must call each readiness method exactly once.' );
spdb_23j_assert( true === $ready->require_knowledge_write_ready( true, true ), 'A fully ready state must pass the knowledge-write gate.' );
spdb_23j_assert( 2 === $ready_resolver->ready_calls && 2 === $ready_resolver->snapshot_calls, 'Each knowledge-gate decision must perform one bounded readiness probe.' );
spdb_23j_assert( 0 === $ready_resolver->resolve_calls, 'Readiness evaluation must not resolve a native object.' );

$write_disabled = $ready->snapshot( false, true );
spdb_23j_assert( 'writes_disabled' === $write_disabled['gate_code'] && false === $write_disabled['collection_write_ready'] && false === $write_disabled['knowledge_write_ready'] && false === $write_disabled['any_write_ready'], 'Disabled writes must override repository and resolver readiness.' );
spdb_23j_assert( 'spdb_collections_writes_disabled' === spdb_23j_code( $ready->require_collection_write_ready( false, true ) ), 'Collection write configuration denial must be explicit.' );
spdb_23j_assert( 'spdb_collections_writes_disabled' === spdb_23j_code( $ready->require_knowledge_write_ready( false, true ) ), 'Knowledge writes must inherit collection write configuration denial first.' );

$repository_unready = $ready->snapshot( true, false );
spdb_23j_assert( 'repository_not_ready' === $repository_unready['gate_code'] && false === $repository_unready['collection_write_ready'] && false === $repository_unready['knowledge_write_ready'], 'Repository unavailability must block every metadata write.' );
spdb_23j_assert( 'spdb_collections_repository_not_ready' === spdb_23j_code( $ready->require_collection_write_ready( true, false ) ), 'Collection repository readiness denial must remain distinct.' );
spdb_23j_assert( 'spdb_collections_repository_not_ready' === spdb_23j_code( $ready->require_knowledge_write_ready( true, false ) ), 'Knowledge writes must inherit repository readiness denial first.' );

$unready_resolver = new SPDB_23J_Readiness_Resolver();
$unready_resolver->declared_ready = false;
$unready_resolver->snapshot = array( 'available' => true, 'ready' => false, 'code' => 'private_provider_reason' );
$unready = new SPDB_Collections_Service_Readiness_Gate( $unready_resolver );
$unready_snapshot = $unready->snapshot( true, true );
spdb_23j_assert( 'resolver_not_ready' === $unready_snapshot['resolver_code'] && false === strpos( implode( '|', $unready_snapshot ), 'private_provider_reason' ), 'Provider readiness detail must be reduced to a bounded service state.' );
spdb_23j_assert( 'spdb_native_reference_resolver_not_ready' === spdb_23j_code( $unready->require_knowledge_write_ready( true, true ) ), 'Operational not-ready state requires a bounded distinct error.' );

$unavailable_resolver = new SPDB_23J_Readiness_Resolver();
$unavailable_resolver->declared_ready = false;
$unavailable_resolver->snapshot = array( 'available' => false, 'ready' => false, 'code' => 'private_outage' );
$unavailable = new SPDB_Collections_Service_Readiness_Gate( $unavailable_resolver );
spdb_23j_assert( 'resolver_unavailable' === $unavailable->snapshot( true, true )['resolver_code'], 'A readiness-aware but unavailable resolver requires a bounded unavailable state.' );
spdb_23j_assert( 'spdb_native_reference_resolver_unavailable' === spdb_23j_code( $unavailable->require_knowledge_write_ready( true, true ) ), 'Resolver unavailability must remain distinct from operational not-ready.' );

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
spdb_23j_assert( 'spdb_native_reference_readiness_invalid' === spdb_23j_code( $malformed_gate->require_knowledge_write_ready( true, true ) ), 'Malformed readiness requires a distinct bounded error.' );

$reordered = new SPDB_23J_Readiness_Resolver();
$reordered->declared_ready = true;
$reordered->snapshot = array( 'code' => 'ready', 'ready' => true, 'available' => true );
$reordered_gate = new SPDB_Collections_Service_Readiness_Gate( $reordered );
spdb_23j_assert( true === $reordered_gate->snapshot( true, true )['resolver_ready'], 'Valid readiness must not depend on associative key order.' );

$unsafe_code = new SPDB_23J_Readiness_Resolver();
$unsafe_code->snapshot = array( 'available' => true, 'ready' => false, 'code' => 'Private health text!' );
$unsafe_gate = new SPDB_Collections_Service_Readiness_Gate( $unsafe_code );
spdb_23j_assert( 'resolver_readiness_invalid' === $unsafe_gate->snapshot( true, true )['resolver_code'], 'Noncanonical readiness codes must fail closed.' );

$empty_code = new SPDB_23J_Readiness_Resolver();
$empty_code->snapshot = array( 'available' => true, 'ready' => false, 'code' => '' );
spdb_23j_assert( 'resolver_readiness_invalid' === ( new SPDB_Collections_Service_Readiness_Gate( $empty_code ) )->snapshot( true, true )['resolver_code'], 'Empty readiness codes must fail closed.' );

$long_code = new SPDB_23J_Readiness_Resolver();
$long_code->snapshot = array( 'available' => true, 'ready' => false, 'code' => str_repeat( 'a', 65 ) );
spdb_23j_assert( 'resolver_readiness_invalid' === ( new SPDB_Collections_Service_Readiness_Gate( $long_code ) )->snapshot( true, true )['resolver_code'], 'Overlong readiness codes must fail closed.' );

$ready_code_mismatch = new SPDB_23J_Readiness_Resolver();
$ready_code_mismatch->declared_ready = true;
$ready_code_mismatch->snapshot = array( 'available' => true, 'ready' => true, 'code' => 'not_ready' );
spdb_23j_assert( 'resolver_readiness_invalid' === ( new SPDB_Collections_Service_Readiness_Gate( $ready_code_mismatch ) )->snapshot( true, true )['resolver_code'], 'A ready snapshot must use the canonical ready code.' );

$not_ready_code_mismatch = new SPDB_23J_Readiness_Resolver();
$not_ready_code_mismatch->snapshot = array( 'available' => true, 'ready' => false, 'code' => 'ready' );
spdb_23j_assert( 'resolver_readiness_invalid' === ( new SPDB_Collections_Service_Readiness_Gate( $not_ready_code_mismatch ) )->snapshot( true, true )['resolver_code'], 'A non-ready snapshot must not use the canonical ready code.' );

$impossible = new SPDB_23J_Readiness_Resolver();
$impossible->declared_ready = true;
$impossible->snapshot = array( 'available' => false, 'ready' => true, 'code' => 'ready' );
$impossible_gate = new SPDB_Collections_Service_Readiness_Gate( $impossible );
spdb_23j_assert( 'resolver_readiness_invalid' === $impossible_gate->snapshot( true, true )['resolver_code'], 'An unavailable readiness source cannot claim ready.' );

$ready_exception = new SPDB_23J_Readiness_Resolver();
$ready_exception->throw_on_ready = true;
$ready_exception_gate = new SPDB_Collections_Service_Readiness_Gate( $ready_exception );
$ready_exception_snapshot = $ready_exception_gate->snapshot( true, true );
spdb_23j_assert( 'resolver_readiness_exception' === $ready_exception_snapshot['resolver_code'] && false === strpos( implode( '|', $ready_exception_snapshot ), 'Private' ), 'Readiness declaration exceptions must be isolated without relaying text.' );
spdb_23j_assert( 'spdb_native_reference_readiness_failed' === spdb_23j_code( $ready_exception_gate->require_knowledge_write_ready( true, true ) ), 'Readiness declaration exceptions require a bounded error.' );
$ready_exception->throw_on_ready = false;
$ready_exception->declared_ready = true;
$ready_exception->snapshot = array( 'available' => true, 'ready' => true, 'code' => 'ready' );
spdb_23j_assert( true === $ready_exception_gate->snapshot( true, true )['resolver_ready'], 'A readiness exception must not permanently latch the gate into denial.' );

$snapshot_exception = new SPDB_23J_Readiness_Resolver();
$snapshot_exception->declared_ready = true;
$snapshot_exception->throw_on_snapshot = true;
$snapshot_exception_gate = new SPDB_Collections_Service_Readiness_Gate( $snapshot_exception );
spdb_23j_assert( 'resolver_readiness_exception' === $snapshot_exception_gate->snapshot( true, true )['resolver_code'], 'Readiness snapshot exceptions must be isolated.' );
spdb_23j_assert( 0 === $snapshot_exception->resolve_calls, 'Readiness exceptions must never fall through to native resolution.' );
$snapshot_exception->throw_on_snapshot = false;
$snapshot_exception->snapshot = array( 'available' => true, 'ready' => true, 'code' => 'ready' );
spdb_23j_assert( true === $snapshot_exception_gate->snapshot( true, true )['resolver_ready'], 'A snapshot exception must release the evaluation guard for a later healthy decision.' );

$reentrant_resolver = new SPDB_23J_Reentrant_Resolver();
$reentrant_gate = new SPDB_Collections_Service_Readiness_Gate( $reentrant_resolver );
$reentrant_resolver->gate = $reentrant_gate;
$reentrant_gate->snapshot( true, true );
spdb_23j_assert( 'resolver_readiness_reentrant' === $reentrant_resolver->nested_code, 'Recursive readiness evaluation must fail closed instead of recursing indefinitely.' );
spdb_23j_assert( 0 === $reentrant_resolver->resolve_calls, 'Reentrant readiness denial must not execute native resolution.' );
$reentrant_resolver->reenter = false;
$reentrant_resolver->gate = null;
spdb_23j_assert( true === $reentrant_gate->snapshot( true, true )['resolver_ready'], 'A re-entrant denial must not permanently latch the gate after the outer evaluation ends.' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} Phase 23J Collections service readiness tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} Phase 23J Collections service readiness tests passed.\n";
