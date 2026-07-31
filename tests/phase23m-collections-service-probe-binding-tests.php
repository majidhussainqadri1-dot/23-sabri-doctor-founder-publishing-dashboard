<?php
/** Executable tests for initial Phase 23M service-probe composition. */
require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-collections-repository.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-native-reference-readiness.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service-readiness-gate.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service-readiness-integration.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-repository-readiness-probe.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service-probe-binding.php';

final class SPDB_23M_Repository implements SPDB_Collections_Repository {
	/** @var array<string,mixed> */
	public array $health;
	public bool $throw = false;
	public int $health_calls = 0;

	public function __construct() {
		$this->health = spdb_23m_health();
	}
	public function health_check(): array {
		++$this->health_calls;
		if ( $this->throw ) {
			throw new RuntimeException( 'private binding repository failure' );
		}
		return $this->health;
	}
	public function list_collections( array $query ) { return array(); }
	public function get_collection( string $collection_id ) { return array(); }
	public function create_collection( array $record ) { return array(); }
	public function update_collection( string $collection_id, int $expected_version, array $changes, array $operation ) { return array(); }
	public function archive_collection( string $collection_id, int $expected_version, array $operation ) { return array(); }
	public function list_collection_items( string $collection_id, array $query = array() ) { return array(); }
	public function get_collection_item( string $collection_id, string $item_id ) { return array(); }
	public function add_collection_item( string $collection_id, int $expected_collection_version, array $record ) { return array(); }
	public function update_collection_item( string $collection_id, string $item_id, int $expected_collection_version, int $expected_item_version, array $changes, array $operation ) { return array(); }
	public function archive_collection_item( string $collection_id, string $item_id, int $expected_collection_version, int $expected_item_version, array $operation ) { return array(); }
	public function list_knowledge_links( array $query ) { return array(); }
	public function get_knowledge_link( string $link_id ) { return array(); }
	public function create_knowledge_link( array $record ) { return array(); }
	public function update_knowledge_link( string $link_id, int $expected_version, array $changes, array $operation ) { return array(); }
	public function archive_knowledge_link( string $link_id, int $expected_version, array $operation ) { return array(); }
}

final class SPDB_23M_Resolver implements SPDB_Native_Reference_Resolver, SPDB_Native_Reference_Readiness {
	public bool $ready = true;
	public int $ready_calls = 0;
	public int $snapshot_calls = 0;
	public int $resolve_calls = 0;
	public function is_ready(): bool { ++$this->ready_calls; return $this->ready; }
	public function readiness_snapshot(): array {
		++$this->snapshot_calls;
		return $this->ready
			? array( 'available' => true, 'ready' => true, 'code' => 'ready' )
			: array( 'available' => true, 'ready' => false, 'code' => 'not_ready' );
	}
	public function resolve_reference( string $provider_key, string $object_type, string $object_id, array $context ) {
		++$this->resolve_calls;
		return array();
	}
	public function reset(): void {
		$this->ready_calls = 0;
		$this->snapshot_calls = 0;
		$this->resolve_calls = 0;
	}
}

$tests = 0;
$failed = 0;
function spdb_23m_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}
function spdb_23m_code( $value ): string {
	return $value instanceof WP_Error ? $value->get_error_code() : '';
}
/** @return array<string,mixed> */
function spdb_23m_health( array $changes = array() ): array {
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

$constructor = new ReflectionMethod( SPDB_Collections_Service_Probe_Binding::class, '__construct' );
spdb_23m_assert( $constructor->isPrivate(), 'The binding constructor must remain private so callers cannot inject mismatched components.' );
spdb_23m_assert( ! method_exists( SPDB_Collections_Service_Probe_Binding::class, 'set_repository' ) && ! method_exists( SPDB_Collections_Service_Probe_Binding::class, 'set_resolver' ) && ! method_exists( SPDB_Collections_Service_Probe_Binding::class, 'set_probe' ), 'The binding must not expose dependency replacement methods.' );

$repository = new SPDB_23M_Repository();
$resolver = new SPDB_23M_Resolver();
$binding = SPDB_Collections_Service_Probe_Binding::create( $repository, $resolver );
spdb_23m_assert( $binding->service() instanceof SPDB_Collections_Service, 'The binding must expose the paired Collections service.' );
spdb_23m_assert( $binding->service() === $binding->service(), 'The binding must retain one service instance.' );

$invalid = $binding->readiness_snapshot( 'false' );
spdb_23m_assert( false === $invalid['inputs_valid'] && 'integration_input_invalid' === $invalid['integration_code'], 'Weakly coercible authority must fail closed through the bound probe.' );
spdb_23m_assert( 0 === $repository->health_calls && 0 === $resolver->ready_calls, 'Invalid authority must not probe either bound dependency.' );

$repository->health_calls = 0;
$resolver->reset();
$read = $binding->readiness_snapshot( false );
spdb_23m_assert( true === $read['read_ready'] && false === $read['write_enabled'], 'Bound read readiness must remain available with writes disabled.' );
spdb_23m_assert( 1 === $repository->health_calls && 0 === $resolver->ready_calls, 'Read readiness must acquire only the bound repository once.' );

$repository->health_calls = 0;
$resolver->reset();
$write = $binding->readiness_snapshot( true );
spdb_23m_assert( true === $write['collection_write_ready'] && true === $write['knowledge_write_ready'], 'A fully ready binding must project both reviewed write classes.' );
spdb_23m_assert( 1 === $repository->health_calls && 1 === $resolver->ready_calls && 1 === $resolver->snapshot_calls && 0 === $resolver->resolve_calls, 'A full bound snapshot must evaluate each readiness source once and never resolve a native object.' );

$repository->health_calls = 0;
$resolver->reset();
spdb_23m_assert( true === $binding->require_read_ready(), 'The paired repository must satisfy bound read readiness.' );
spdb_23m_assert( 1 === $repository->health_calls && 0 === $resolver->ready_calls, 'Bound read requirement must remain resolver-independent.' );

$repository->health_calls = 0;
$resolver->reset();
spdb_23m_assert( 'spdb_collections_writes_disabled' === spdb_23m_code( $binding->require_collection_write_ready( false ) ), 'Disabled collection writes must preserve the stable denial.' );
spdb_23m_assert( 'spdb_collections_writes_disabled' === spdb_23m_code( $binding->require_knowledge_write_ready( false ) ), 'Disabled knowledge writes must preserve the stable denial.' );
spdb_23m_assert( 0 === $repository->health_calls && 0 === $resolver->ready_calls, 'Disabled bound writes must short-circuit both dependencies.' );

$repository->health_calls = 0;
$resolver->reset();
spdb_23m_assert( true === $binding->require_collection_write_ready( true ), 'Bound collection readiness must pass for a ready repository.' );
spdb_23m_assert( 1 === $repository->health_calls && 0 === $resolver->ready_calls, 'Bound collection readiness must remain resolver-independent.' );

$repository->health_calls = 0;
$resolver->reset();
spdb_23m_assert( true === $binding->require_knowledge_write_ready( true ), 'Bound knowledge readiness must pass for a ready repository and resolver.' );
spdb_23m_assert( 1 === $repository->health_calls && 1 === $resolver->ready_calls && 1 === $resolver->snapshot_calls && 0 === $resolver->resolve_calls, 'Bound knowledge readiness must use the paired dependencies exactly once.' );

$resolver->ready = false;
$repository->health_calls = 0;
$resolver->reset();
spdb_23m_assert( 'spdb_native_reference_resolver_not_ready' === spdb_23m_code( $binding->require_knowledge_write_ready( true ) ), 'An unready paired resolver must block knowledge readiness.' );
spdb_23m_assert( 1 === $repository->health_calls && 1 === $resolver->ready_calls && 0 === $resolver->resolve_calls, 'Resolver denial must remain bounded and side-effect free.' );
$resolver->ready = true;

$repository->throw = true;
$repository->health_calls = 0;
$resolver->reset();
$failed_snapshot = $binding->readiness_snapshot( true );
spdb_23m_assert( 'repository_not_ready' === $failed_snapshot['repository_code'] && false === strpos( implode( '|', $failed_snapshot ), 'private binding repository failure' ), 'A bound repository exception must be isolated without relaying text.' );
spdb_23m_assert( 1 === $repository->health_calls && 0 === $resolver->ready_calls, 'Repository failure must short-circuit the paired resolver.' );
$repository->throw = false;
spdb_23m_assert( true === $binding->require_read_ready(), 'The bound readiness chain must recover after repository failure.' );

$absent = SPDB_Collections_Service_Probe_Binding::create( null, $resolver );
spdb_23m_assert( 'spdb_collections_repository_unavailable' === spdb_23m_code( $absent->require_read_ready() ), 'A binding without a repository must remain explicitly unavailable.' );
$no_resolver = SPDB_Collections_Service_Probe_Binding::create( $repository, null );
spdb_23m_assert( true === $no_resolver->require_collection_write_ready( true ), 'A binding without a resolver may still satisfy collection readiness.' );
spdb_23m_assert( 'spdb_native_reference_resolver_unavailable' === spdb_23m_code( $no_resolver->require_knowledge_write_ready( true ) ), 'A binding without a resolver must deny knowledge readiness.' );
spdb_23m_assert( 0 === $resolver->resolve_calls, 'The Phase 23M binding must never resolve a native object.' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} Phase 23M service-probe binding tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} Phase 23M service-probe binding tests passed.\n";
