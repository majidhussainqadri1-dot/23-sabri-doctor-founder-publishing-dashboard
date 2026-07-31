<?php
/** Executable corrective tests for the Phase 23K service-readiness integration boundary. */
require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-native-reference-readiness.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service-readiness-gate.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service-readiness-integration.php';

final class SPDB_23K_Integration_Resolver implements SPDB_Native_Reference_Resolver, SPDB_Native_Reference_Readiness {
	public bool $ready = true;
	public array $state = array( 'available' => true, 'ready' => true, 'code' => 'ready' );
	public int $ready_calls = 0;
	public int $snapshot_calls = 0;
	public int $resolve_calls = 0;
	public function is_ready(): bool { ++$this->ready_calls; return $this->ready; }
	public function readiness_snapshot(): array { ++$this->snapshot_calls; return $this->state; }
	public function resolve_reference( string $provider_key, string $object_type, string $object_id, array $context ) { ++$this->resolve_calls; return array(); }
	public function reset(): void { $this->ready_calls = 0; $this->snapshot_calls = 0; $this->resolve_calls = 0; }
}

$tests = 0;
$failed = 0;
function spdb_23k_integration_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
}
function spdb_23k_integration_code( $value ): string { return $value instanceof WP_Error ? $value->get_error_code() : ''; }

$health = array( 'healthy' => true, 'database_ready' => true, 'schema_ready' => true, 'code' => 'ready' );
$resolver = new SPDB_23K_Integration_Resolver();
$integration = new SPDB_Collections_Service_Readiness_Integration( new SPDB_Collections_Service_Readiness_Gate( $resolver ) );

$ready = $integration->snapshot( true, true, $health );
spdb_23k_integration_assert( true === $ready['read_ready'] && true === $ready['collection_write_ready'] && true === $ready['knowledge_write_ready'], 'A valid repository and resolver must project the reviewed ready state.' );
spdb_23k_integration_assert( 1 === $resolver->ready_calls && 1 === $resolver->snapshot_calls && 0 === $resolver->resolve_calls, 'A ready projection must make one readiness decision without resolving an object.' );

$resolver->reset();
$disabled = $integration->require_knowledge_write_ready( false, false, array( 'private' => 'ignored' ) );
spdb_23k_integration_assert( 'spdb_collections_writes_disabled' === spdb_23k_integration_code( $disabled ), 'The write-disable gate must precede repository and resolver disclosure.' );
spdb_23k_integration_assert( 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls, 'Disabled requirements must not probe resolver readiness.' );

$unavailable = $integration->snapshot( true, false, array( 'private' => 'ignored' ) );
spdb_23k_integration_assert( 'repository_unavailable' === $unavailable['repository_code'] && false === $unavailable['read_ready'], 'Repository absence must remain bounded.' );

$missing_database = array( 'healthy' => true, 'schema_ready' => true, 'code' => 'ready' );
spdb_23k_integration_assert( 'repository_health_invalid' === $integration->snapshot( true, true, $missing_database )['repository_code'], 'A response missing database readiness must fail closed.' );

$contradictory = array( 'healthy' => true, 'database_ready' => false, 'schema_ready' => true, 'code' => 'database_unavailable' );
spdb_23k_integration_assert( 'repository_health_invalid' === $integration->snapshot( true, true, $contradictory )['repository_code'], 'Aggregate health must agree with database and schema readiness.' );

$database_down = array( 'healthy' => false, 'database_ready' => false, 'schema_ready' => true, 'code' => 'database_unavailable', 'private_detail' => 'secret' );
$database_projection = $integration->snapshot( true, true, $database_down );
spdb_23k_integration_assert( 'repository_not_ready' === $database_projection['repository_code'], 'A valid database-down response must project a bounded not-ready state.' );
spdb_23k_integration_assert( false === strpos( implode( '|', $database_projection ), 'secret' ), 'Private repository details must not be relayed.' );

$invalid_code = array( 'healthy' => false, 'database_ready' => false, 'schema_ready' => false, 'code' => 'Private health text!' );
spdb_23k_integration_assert( 'repository_health_invalid' === $integration->snapshot( true, true, $invalid_code )['repository_code'], 'Unsafe health codes must fail closed.' );

$absent_resolver = new SPDB_Collections_Service_Readiness_Integration( new SPDB_Collections_Service_Readiness_Gate() );
$absent = $absent_resolver->snapshot( true, true, $health );
spdb_23k_integration_assert( true === $absent['collection_write_ready'] && false === $absent['knowledge_write_ready'], 'Resolver absence must not disable independent collection writes.' );
spdb_23k_integration_assert( 'spdb_native_reference_resolver_unavailable' === spdb_23k_integration_code( $absent_resolver->require_knowledge_write_ready( true, true, $health ) ), 'Resolver absence must block knowledge writes.' );

if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} Phase 23K integration tests failed.\n" ); exit( 1 ); }
echo "All {$tests} Phase 23K integration tests passed.\n";
