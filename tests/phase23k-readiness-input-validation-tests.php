<?php
/** Executable regression tests for Phase 23K readiness authority input validation. */
require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-native-reference-readiness.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service-readiness-gate.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service-readiness-integration.php';

final class SPDB_23K_Input_Resolver implements SPDB_Native_Reference_Resolver, SPDB_Native_Reference_Readiness {
	public int $ready_calls = 0;
	public int $snapshot_calls = 0;
	public int $resolve_calls = 0;
	public function is_ready(): bool { ++$this->ready_calls; return true; }
	public function readiness_snapshot(): array { ++$this->snapshot_calls; return array( 'available' => true, 'ready' => true, 'code' => 'ready' ); }
	public function resolve_reference( string $provider_key, string $object_type, string $object_id, array $context ) { ++$this->resolve_calls; return array(); }
}

$tests = 0;
$failed = 0;
function spdb_23k_input_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
}
function spdb_23k_input_code( $value ): string { return $value instanceof WP_Error ? $value->get_error_code() : ''; }

$health = array( 'healthy' => true, 'database_ready' => true, 'schema_ready' => true, 'code' => 'ready' );
$resolver = new SPDB_23K_Input_Resolver();
$integration = new SPDB_Collections_Service_Readiness_Integration( new SPDB_Collections_Service_Readiness_Gate( $resolver ) );

$weak_writes = $integration->snapshot( 'false', true, $health );
spdb_23k_input_assert( 'integration_input_invalid' === $weak_writes['repository_code'], 'A weakly coercible writes flag must fail closed.' );
spdb_23k_input_assert( false === $weak_writes['write_configured'] && false === $weak_writes['write_enabled'], 'Invalid writes input must never project write authority.' );
spdb_23k_input_assert( 'resolver_not_evaluated' === $weak_writes['resolver_code'], 'Invalid writes input must not evaluate resolver readiness.' );

$weak_repository = $integration->snapshot( true, 1, $health );
spdb_23k_input_assert( 'integration_input_invalid' === $weak_repository['repository_code'], 'A weakly coercible repository-availability flag must fail closed.' );
spdb_23k_input_assert( false === $weak_repository['repository_available'] && false === $weak_repository['read_ready'], 'Invalid repository input must never project read authority.' );

spdb_23k_input_assert( 'spdb_collections_readiness_input_invalid' === spdb_23k_input_code( $integration->require_collection_write_ready( 'true', true, $health ) ), 'Collection-write requirements must reject a non-boolean writes flag.' );
spdb_23k_input_assert( 'spdb_collections_readiness_input_invalid' === spdb_23k_input_code( $integration->require_knowledge_write_ready( true, 0, $health ) ), 'Knowledge-write requirements must reject a non-boolean repository-availability flag.' );
spdb_23k_input_assert( 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls && 0 === $resolver->resolve_calls, 'Invalid authority inputs must not call resolver methods.' );

$valid = $integration->snapshot( true, true, $health );
spdb_23k_input_assert( true === $valid['write_enabled'] && 'ready' === $valid['repository_code'], 'Strict booleans with valid health must preserve the ready path.' );
spdb_23k_input_assert( 1 === $resolver->ready_calls && 1 === $resolver->snapshot_calls && 0 === $resolver->resolve_calls, 'The valid path must perform one readiness decision without native resolution.' );

if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} Phase 23K input validation tests failed.\n" ); exit( 1 ); }
echo "All {$tests} Phase 23K readiness input validation tests passed.\n";
