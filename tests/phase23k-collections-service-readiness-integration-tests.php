<?php
/** Executable tests for corrected Phase 23K readiness integration. */
require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-native-reference-readiness.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service-readiness-gate.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service-readiness-integration.php';

final class SPDB_23K_Readiness_Resolver implements SPDB_Native_Reference_Resolver, SPDB_Native_Reference_Readiness {
	public bool $declared_ready = true;
	/** @var mixed */
	public $snapshot = array( 'available' => true, 'ready' => true, 'code' => 'ready' );
	public int $ready_calls = 0;
	public int $snapshot_calls = 0;
	public int $resolve_calls = 0;

	public function is_ready(): bool {
		++$this->ready_calls;
		return $this->declared_ready;
	}

	public function readiness_snapshot(): array {
		++$this->snapshot_calls;
		return $this->snapshot;
	}

	public function resolve_reference( string $provider_key, string $object_type, string $object_id, array $context ) {
		++$this->resolve_calls;
		return array();
	}
}

$tests = 0;
$failed = 0;
function spdb_23k_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}
function spdb_23k_code( $value ): string {
	return $value instanceof WP_Error ? $value->get_error_code() : '';
}
/** @return array<string,mixed> */
function spdb_23k_health( array $changes = array() ): array {
	return array_merge(
		array(
			'healthy' => true,
			'database_ready' => true,
			'schema_ready' => true,
			'schema_version' => SPDB_Collections_Schema::VERSION,
			'code' => 'ready',
			'cached_for_request' => true,
		),
		$changes
	);
}

$no_resolver_gate = new SPDB_Collections_Service_Readiness_Gate();
$no_resolver = new SPDB_Collections_Service_Readiness_Integration( $no_resolver_gate );

$invalid = $no_resolver->snapshot( 'false', true, spdb_23k_health() );
spdb_23k_assert(
	false === $invalid['inputs_valid']
	&& 'integration_input_invalid' === $invalid['integration_code']
	&& 'repository_not_evaluated' === $invalid['repository_code']
	&& false === $invalid['read_ready']
	&& false === $invalid['any_write_ready'],
	'Weakly coercible integration authority inputs must fail closed.'
);
spdb_23k_assert( 'spdb_collections_readiness_input_invalid' === spdb_23k_code( $no_resolver->require_collection_write_ready( true, 1, spdb_23k_health() ) ), 'Collection requirements must reject non-boolean repository authority.' );
spdb_23k_assert( 'spdb_collections_readiness_input_invalid' === spdb_23k_code( $no_resolver->require_knowledge_write_ready( 'false', true, spdb_23k_health() ) ), 'Knowledge requirements must reject non-boolean write authority.' );

$unavailable = $no_resolver->snapshot( true, false, array( 'secret' => 'ignored' ) );
spdb_23k_assert(
	true === $unavailable['inputs_valid']
	&& 'repository_unavailable' === $unavailable['integration_code']
	&& false === $unavailable['repository_available']
	&& false === $unavailable['repository_ready']
	&& false === $unavailable['read_ready']
	&& false === $unavailable['collection_write_ready'],
	'An unavailable repository must be bounded without trusting supplied health.'
);
spdb_23k_assert( 'spdb_collections_repository_unavailable' === spdb_23k_code( $no_resolver->require_collection_write_ready( true, false, array() ) ), 'Repository absence requires a distinct collection error.' );
spdb_23k_assert( 'spdb_collections_repository_unavailable' === spdb_23k_code( $no_resolver->require_knowledge_write_ready( true, false, array() ) ), 'Repository absence must precede resolver readiness for enabled knowledge writes.' );

$write_disabled = $no_resolver->snapshot( false, false, array() );
spdb_23k_assert( 'writes_disabled' === $write_disabled['integration_code'] && false === $write_disabled['write_enabled'], 'Writes-disabled precedence must match executable requirements.' );
spdb_23k_assert( 'spdb_collections_writes_disabled' === spdb_23k_code( $no_resolver->require_collection_write_ready( false, false, array() ) ), 'Writes-disabled must precede repository availability for collection requirements.' );
spdb_23k_assert( 'spdb_collections_writes_disabled' === spdb_23k_code( $no_resolver->require_knowledge_write_ready( false, false, array() ) ), 'Writes-disabled must precede repository availability for knowledge requirements.' );

$ready_no_resolver = $no_resolver->snapshot( true, true, spdb_23k_health() );
spdb_23k_assert(
	'collection_ready' === $ready_no_resolver['integration_code']
	&& true === $ready_no_resolver['repository_available']
	&& true === $ready_no_resolver['repository_ready']
	&& '4' === $ready_no_resolver['repository_schema_version']
	&& true === $ready_no_resolver['repository_cached_for_request']
	&& true === $ready_no_resolver['read_ready']
	&& true === $ready_no_resolver['collection_write_ready']
	&& false === $ready_no_resolver['knowledge_write_ready'],
	'A ready repository without a resolver must permit only the reviewed collection-write decision.'
);
spdb_23k_assert( true === $no_resolver->require_collection_write_ready( true, true, spdb_23k_health() ), 'A ready exact repository health projection must pass collection readiness.' );
spdb_23k_assert( 'spdb_native_reference_resolver_unavailable' === spdb_23k_code( $no_resolver->require_knowledge_write_ready( true, true, spdb_23k_health() ) ), 'Knowledge readiness must still require a resolver.' );

$unknown = $no_resolver->snapshot( true, true, spdb_23k_health( array( 'private_detail' => 'forbidden' ) ) );
spdb_23k_assert( 'repository_health_invalid' === $unknown['integration_code'] && false === $unknown['repository_ready'], 'Unknown repository-health fields must fail closed.' );
$missing = spdb_23k_health();
unset( $missing['cached_for_request'] );
spdb_23k_assert( 'repository_health_invalid' === $no_resolver->snapshot( true, true, $missing )['integration_code'], 'Missing repository-health fields must fail closed.' );
$wrong_scalar = spdb_23k_health( array( 'database_ready' => 1 ) );
spdb_23k_assert( 'repository_health_invalid' === $no_resolver->snapshot( true, true, $wrong_scalar )['integration_code'], 'Non-boolean repository-health scalars must fail closed.' );
$wrong_cache = spdb_23k_health( array( 'cached_for_request' => 'true' ) );
spdb_23k_assert( 'repository_health_invalid' === $no_resolver->snapshot( true, true, $wrong_cache )['integration_code'], 'The request-cache marker must be an exact boolean.' );
$wrong_schema = spdb_23k_health( array( 'schema_version' => '2' ) );
spdb_23k_assert( 'repository_health_invalid' === $no_resolver->snapshot( true, true, $wrong_schema )['integration_code'], 'A stale schema version must fail closed.' );
$invalid_schema = spdb_23k_health( array( 'schema_version' => '03' ) );
spdb_23k_assert( 'repository_health_invalid' === $no_resolver->snapshot( true, true, $invalid_schema )['integration_code'], 'A noncanonical schema version must fail closed.' );
$health_mismatch = spdb_23k_health( array( 'healthy' => true, 'database_ready' => false, 'code' => 'database_unavailable' ) );
spdb_23k_assert( 'repository_health_invalid' === $no_resolver->snapshot( true, true, $health_mismatch )['integration_code'], 'Aggregate healthy state must equal database-and-schema readiness.' );
$ready_code_mismatch = spdb_23k_health( array( 'code' => 'degraded' ) );
spdb_23k_assert( 'repository_health_invalid' === $no_resolver->snapshot( true, true, $ready_code_mismatch )['integration_code'], 'A ready repository must use the canonical ready code.' );
$not_ready_code_mismatch = spdb_23k_health( array( 'healthy' => false, 'database_ready' => false, 'code' => 'ready' ) );
spdb_23k_assert( 'repository_health_invalid' === $no_resolver->snapshot( true, true, $not_ready_code_mismatch )['integration_code'], 'A non-ready repository must not use the ready code.' );
$unsafe_code = spdb_23k_health( array( 'healthy' => false, 'database_ready' => false, 'code' => 'Private database text!' ) );
spdb_23k_assert( 'repository_health_invalid' === $no_resolver->snapshot( true, true, $unsafe_code )['integration_code'], 'Unsafe repository health codes must fail closed.' );

$not_ready = spdb_23k_health( array( 'healthy' => false, 'database_ready' => false, 'code' => 'database_unavailable', 'cached_for_request' => false ) );
$not_ready_snapshot = $no_resolver->snapshot( true, true, $not_ready );
spdb_23k_assert(
	'repository_not_ready' === $not_ready_snapshot['integration_code']
	&& 'repository_not_ready' === $not_ready_snapshot['repository_code']
	&& false === $not_ready_snapshot['repository_cached_for_request']
	&& false === strpos( implode( '|', $not_ready_snapshot ), 'database_unavailable' ),
	'A valid degraded repository state must be reduced to bounded service output without relaying provider details.'
);
spdb_23k_assert( 'spdb_collections_repository_not_ready' === spdb_23k_code( $no_resolver->require_collection_write_ready( true, true, $not_ready ) ), 'A valid degraded repository requires a distinct not-ready error.' );

$reordered = array(
	'cached_for_request' => true,
	'code' => 'ready',
	'schema_version' => SPDB_Collections_Schema::VERSION,
	'schema_ready' => true,
	'database_ready' => true,
	'healthy' => true,
);
spdb_23k_assert( true === $no_resolver->snapshot( true, true, $reordered )['repository_ready'], 'Valid repository health must not depend on associative key order.' );

$resolver = new SPDB_23K_Readiness_Resolver();
$ready_gate = new SPDB_Collections_Service_Readiness_Gate( $resolver );
$integration = new SPDB_Collections_Service_Readiness_Integration( $ready_gate );
$knowledge_ready = $integration->snapshot( true, true, spdb_23k_health() );
spdb_23k_assert(
	'knowledge_ready' === $knowledge_ready['integration_code']
	&& true === $knowledge_ready['resolver_ready']
	&& true === $knowledge_ready['knowledge_write_ready']
	&& true === $knowledge_ready['write_enabled'],
	'A ready repository and formal ready resolver must produce knowledge readiness.'
);
spdb_23k_assert( 1 === $resolver->ready_calls && 1 === $resolver->snapshot_calls, 'One integration snapshot must perform one bounded resolver-readiness probe.' );
spdb_23k_assert( true === $integration->require_knowledge_write_ready( true, true, spdb_23k_health() ), 'A fully ready integration must pass the knowledge requirement.' );
spdb_23k_assert( 0 === $resolver->resolve_calls, 'Readiness integration must never resolve a native object.' );

$resolver->declared_ready = false;
$resolver->snapshot = array( 'available' => true, 'ready' => false, 'code' => 'private_provider_reason' );
$resolver_not_ready = $integration->snapshot( true, true, spdb_23k_health() );
spdb_23k_assert(
	'collection_ready' === $resolver_not_ready['integration_code']
	&& false === $resolver_not_ready['knowledge_write_ready']
	&& 'resolver_not_ready' === $resolver_not_ready['resolver_code']
	&& false === strpos( implode( '|', $resolver_not_ready ), 'private_provider_reason' ),
	'Resolver not-ready detail must be bounded while collection readiness remains independent.'
);
spdb_23k_assert( 'spdb_native_reference_resolver_not_ready' === spdb_23k_code( $integration->require_knowledge_write_ready( true, true, spdb_23k_health() ) ), 'Knowledge requirement must preserve bounded resolver-not-ready denial.' );
spdb_23k_assert( 0 === $resolver->resolve_calls, 'Denied integration decisions must not execute native resolution.' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} Phase 23K readiness integration tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} Phase 23K readiness integration tests passed.\n";
