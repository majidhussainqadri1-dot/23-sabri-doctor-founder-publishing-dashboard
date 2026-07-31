<?php
/** Executable tests for corrected Phase 23L repository readiness probe. */
require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-collections-repository.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-native-reference-readiness.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service-readiness-gate.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service-readiness-integration.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-repository-readiness-probe.php';

final class SPDB_23L_Repository implements SPDB_Collections_Repository {
	/** @var array<string,mixed> */
	public array $health;
	public bool $throw = false;
	public bool $reenter = false;
	public int $health_calls = 0;
	public ?SPDB_Collections_Repository_Readiness_Probe $probe = null;

	public function __construct() {
		$this->health = spdb_23l_health();
	}

	public function health_check(): array {
		++$this->health_calls;
		if ( $this->throw ) {
			throw new RuntimeException( 'private repository exception' );
		}
		if ( $this->reenter && null !== $this->probe ) {
			$this->probe->snapshot( false );
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

final class SPDB_23L_Resolver implements SPDB_Native_Reference_Resolver, SPDB_Native_Reference_Readiness {
	public int $ready_calls = 0;
	public int $snapshot_calls = 0;
	public int $resolve_calls = 0;
	public bool $ready = true;

	public function is_ready(): bool {
		++$this->ready_calls;
		return $this->ready;
	}
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
function spdb_23l_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}
function spdb_23l_code( $value ): string {
	return $value instanceof WP_Error ? $value->get_error_code() : '';
}
/** @return array<string,mixed> */
function spdb_23l_health( array $changes = array() ): array {
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

$repository = new SPDB_23L_Repository();
$resolver = new SPDB_23L_Resolver();
$probe = new SPDB_Collections_Repository_Readiness_Probe(
	$repository,
	new SPDB_Collections_Service_Readiness_Integration(
		new SPDB_Collections_Service_Readiness_Gate( $resolver )
	)
);
$repository->probe = $probe;

$invalid = $probe->snapshot( 'false' );
spdb_23l_assert(
	false === $invalid['inputs_valid']
	&& 'integration_input_invalid' === $invalid['integration_code']
	&& 'repository_not_evaluated' === $invalid['repository_code']
	&& false === $invalid['any_write_ready'],
	'Weakly coercible authority input must fail closed before repository acquisition.'
);
spdb_23l_assert( 0 === $repository->health_calls && 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls, 'Invalid authority input must not probe repository or resolver readiness.' );
spdb_23l_assert( 'spdb_collections_readiness_input_invalid' === spdb_23l_code( $probe->require_collection_write_ready( 1 ) ), 'Invalid collection-write authority must retain the bounded input error.' );
spdb_23l_assert( 'spdb_collections_readiness_input_invalid' === spdb_23l_code( $probe->require_knowledge_write_ready( 'true' ) ), 'Invalid knowledge-write authority must retain the bounded input error.' );
spdb_23l_assert( 0 === $repository->health_calls && 0 === $resolver->ready_calls, 'Invalid write requirements must short-circuit every probe.' );

$repository->health_calls = 0;
$resolver->reset();
$read_snapshot = $probe->snapshot( false );
spdb_23l_assert( true === $read_snapshot['read_ready'] && false === $read_snapshot['write_enabled'], 'A writes-disabled snapshot must still provide bounded repository read readiness.' );
spdb_23l_assert( 1 === $repository->health_calls && 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls, 'Read-only snapshot must acquire repository health once and skip resolver readiness.' );

$repository->health_calls = 0;
$resolver->reset();
$write_snapshot = $probe->snapshot( true );
spdb_23l_assert( true === $write_snapshot['collection_write_ready'] && true === $write_snapshot['knowledge_write_ready'], 'A fully ready state must project both reviewed write classes.' );
spdb_23l_assert( 1 === $repository->health_calls && 1 === $resolver->ready_calls && 1 === $resolver->snapshot_calls && 0 === $resolver->resolve_calls, 'Write snapshot must acquire repository health once and resolver readiness once without native resolution.' );

$repository->health_calls = 0;
$resolver->reset();
spdb_23l_assert( true === $probe->require_read_ready(), 'Ready repository must pass the read requirement.' );
spdb_23l_assert( 1 === $repository->health_calls && 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls, 'Read requirement must not evaluate resolver readiness.' );

$repository->health_calls = 0;
$resolver->reset();
spdb_23l_assert( 'spdb_collections_writes_disabled' === spdb_23l_code( $probe->require_collection_write_ready( false ) ), 'Disabled collection writes must retain the stable denial.' );
spdb_23l_assert( 'spdb_collections_writes_disabled' === spdb_23l_code( $probe->require_knowledge_write_ready( false ) ), 'Disabled knowledge writes must retain the stable denial.' );
spdb_23l_assert( 0 === $repository->health_calls && 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls, 'Disabled writes must short-circuit repository and resolver probes.' );

$repository->health_calls = 0;
$resolver->reset();
spdb_23l_assert( true === $probe->require_collection_write_ready( true ), 'Ready repository must pass collection-write readiness.' );
spdb_23l_assert( 1 === $repository->health_calls && 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls, 'Collection-write requirement must remain independent of resolver readiness.' );

$repository->health_calls = 0;
$resolver->reset();
spdb_23l_assert( true === $probe->require_knowledge_write_ready( true ), 'Ready repository and resolver must pass knowledge-write readiness.' );
spdb_23l_assert( 1 === $repository->health_calls && 1 === $resolver->ready_calls && 1 === $resolver->snapshot_calls && 0 === $resolver->resolve_calls, 'Knowledge-write requirement must evaluate each readiness source once and never resolve a native object.' );

$resolver->ready = false;
$repository->health_calls = 0;
$resolver->reset();
spdb_23l_assert( 'spdb_native_reference_resolver_not_ready' === spdb_23l_code( $probe->require_knowledge_write_ready( true ) ), 'An unready resolver must block only knowledge writes.' );
spdb_23l_assert( 1 === $repository->health_calls && 1 === $resolver->ready_calls && 1 === $resolver->snapshot_calls && 0 === $resolver->resolve_calls, 'Resolver denial must remain bounded and side-effect free.' );
$resolver->ready = true;

$absent_probe = new SPDB_Collections_Repository_Readiness_Probe(
	null,
	new SPDB_Collections_Service_Readiness_Integration(
		new SPDB_Collections_Service_Readiness_Gate( $resolver )
	)
);
spdb_23l_assert( 'spdb_collections_repository_unavailable' === spdb_23l_code( $absent_probe->require_read_ready() ), 'Repository absence must produce the bounded unavailable denial.' );
spdb_23l_assert( 'spdb_collections_repository_unavailable' === spdb_23l_code( $absent_probe->require_collection_write_ready( true ) ), 'Repository absence must block enabled collection writes.' );

$repository->throw = true;
$repository->health_calls = 0;
$resolver->reset();
$exception_snapshot = $probe->snapshot( true );
spdb_23l_assert(
	'repository_not_ready' === $exception_snapshot['repository_code']
	&& SPDB_Collections_Schema::VERSION === $exception_snapshot['repository_schema_version']
	&& false === $exception_snapshot['repository_cached_for_request']
	&& false === strpos( implode( '|', $exception_snapshot ), 'private repository exception' ),
	'Repository exceptions must become a complete bounded six-field not-ready state without private detail.'
);
spdb_23l_assert( 1 === $repository->health_calls && 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls, 'Repository failure must stop before resolver readiness.' );
$repository->throw = false;
$repository->health_calls = 0;
spdb_23l_assert( true === $probe->require_read_ready(), 'The repository probe must recover after an exception.' );
spdb_23l_assert( 1 === $repository->health_calls, 'Exception recovery must perform one fresh repository health call.' );

$repository->health = spdb_23l_health( array( 'database_ready' => 'yes' ) );
$resolver->reset();
$invalid_health = $probe->snapshot( true );
spdb_23l_assert( 'repository_health_invalid' === $invalid_health['repository_code'], 'Malformed repository health must fail closed.' );
spdb_23l_assert( 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls, 'Malformed repository health must short-circuit resolver readiness.' );

$repository->health = spdb_23l_health( array( 'private_detail' => 'forbidden' ) );
$resolver->reset();
spdb_23l_assert( 'repository_health_invalid' === $probe->snapshot( true )['repository_code'], 'Unknown repository health fields must fail closed.' );
spdb_23l_assert( 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls, 'Unknown-field repository health must short-circuit resolver readiness.' );

$repository->health = spdb_23l_health( array( 'schema_version' => '2' ) );
$resolver->reset();
spdb_23l_assert( 'repository_health_invalid' === $probe->snapshot( true )['repository_code'], 'A stale schema version must fail closed.' );
spdb_23l_assert( 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls, 'Stale-schema repository health must short-circuit resolver readiness.' );

$repository->health = spdb_23l_health( array( 'healthy' => false, 'database_ready' => false, 'code' => 'database_unavailable', 'cached_for_request' => false ) );
$resolver->reset();
$degraded = $probe->snapshot( true );
spdb_23l_assert( 'repository_not_ready' === $degraded['repository_code'] && false === strpos( implode( '|', $degraded ), 'database_unavailable' ), 'Valid degraded health must be reduced without relaying repository detail.' );
spdb_23l_assert( 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls, 'Degraded repository health must short-circuit resolver readiness.' );

$repository->health = spdb_23l_health();
$repository->reenter = true;
$repository->health_calls = 0;
$resolver->reset();
$reentrant = $probe->snapshot( true );
spdb_23l_assert(
	'repository_not_ready' === $reentrant['repository_code']
	&& SPDB_Collections_Schema::VERSION === $reentrant['repository_schema_version']
	&& false === $reentrant['repository_cached_for_request'],
	'Re-entrant repository acquisition must become a complete bounded not-ready state.'
);
spdb_23l_assert( 1 === $repository->health_calls && 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls, 'Re-entrant acquisition must not recurse into a second repository call or resolver readiness.' );
$repository->reenter = false;
$repository->health_calls = 0;
spdb_23l_assert( true === $probe->require_read_ready(), 'The repository probe must recover after re-entrant denial.' );
spdb_23l_assert( 1 === $repository->health_calls, 'Re-entrancy recovery must perform one fresh repository health call.' );
spdb_23l_assert( 0 === $resolver->resolve_calls, 'No repository readiness probe path may resolve a native object.' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} Phase 23L readiness probe tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} Phase 23L collections repository readiness probe tests passed.\n";
