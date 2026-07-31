<?php
/** Executable regression tests for Phase 23K readiness-snapshot short-circuiting. */
require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-native-reference-readiness.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service-readiness-gate.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service-readiness-integration.php';

final class SPDB_23K_Short_Circuit_Resolver implements SPDB_Native_Reference_Resolver, SPDB_Native_Reference_Readiness {
	public int $ready_calls = 0;
	public int $snapshot_calls = 0;
	public int $resolve_calls = 0;
	public function is_ready(): bool { ++$this->ready_calls; return true; }
	public function readiness_snapshot(): array { ++$this->snapshot_calls; return array( 'available' => true, 'ready' => true, 'code' => 'ready' ); }
	public function resolve_reference( string $provider_key, string $object_type, string $object_id, array $context ) { ++$this->resolve_calls; return array(); }
	public function reset(): void { $this->ready_calls = 0; $this->snapshot_calls = 0; $this->resolve_calls = 0; }
}

$tests = 0;
$failed = 0;
function spdb_23k_short_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
}

$resolver = new SPDB_23K_Short_Circuit_Resolver();
$gate = new SPDB_Collections_Service_Readiness_Gate( $resolver );

$disabled = $gate->snapshot( false, true );
spdb_23k_short_assert( 'writes_disabled' === $disabled['gate_code'] && 'resolver_not_evaluated' === $disabled['resolver_code'], 'Disabled writes must not evaluate resolver readiness.' );
spdb_23k_short_assert( 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls && 0 === $resolver->resolve_calls, 'Disabled-write snapshots must not call resolver methods.' );

$repository_unready = $gate->snapshot( true, false );
spdb_23k_short_assert( 'repository_not_ready' === $repository_unready['gate_code'] && 'resolver_not_evaluated' === $repository_unready['resolver_code'], 'Repository denial must not evaluate resolver readiness.' );
spdb_23k_short_assert( 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls && 0 === $resolver->resolve_calls, 'Repository-unready snapshots must not call resolver methods.' );

$ready = $gate->snapshot( true, true );
spdb_23k_short_assert( 'knowledge_ready' === $ready['gate_code'] && 'ready' === $ready['resolver_code'], 'A ready repository must perform one resolver readiness decision.' );
spdb_23k_short_assert( 1 === $resolver->ready_calls && 1 === $resolver->snapshot_calls && 0 === $resolver->resolve_calls, 'A ready snapshot must call each readiness method once and never resolve an object.' );

$health = array( 'healthy' => true, 'database_ready' => true, 'schema_ready' => true, 'code' => 'ready' );
$integration = new SPDB_Collections_Service_Readiness_Integration( $gate );
$resolver->reset();
$integration_disabled = $integration->snapshot( false, true, $health );
spdb_23k_short_assert( 'resolver_not_evaluated' === $integration_disabled['resolver_code'], 'The integration snapshot must preserve disabled-write short-circuiting.' );
spdb_23k_short_assert( 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls && 0 === $resolver->resolve_calls, 'Disabled integration snapshots must not call resolver methods.' );

$resolver->reset();
$integration_unavailable = $integration->snapshot( true, false, array( 'private' => 'ignored' ) );
spdb_23k_short_assert( 'resolver_not_evaluated' === $integration_unavailable['resolver_code'], 'The integration snapshot must preserve repository-unavailable short-circuiting.' );
spdb_23k_short_assert( 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls && 0 === $resolver->resolve_calls, 'Unavailable repository snapshots must not call resolver methods.' );

if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} Phase 23K short-circuit tests failed.\n" ); exit( 1 ); }
echo "All {$tests} Phase 23K readiness short-circuit tests passed.\n";
