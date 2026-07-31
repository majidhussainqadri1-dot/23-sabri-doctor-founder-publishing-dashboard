<?php
/** Executable tests for the initial Phase 23N service readiness consumer. */
require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-collections-repository.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-native-reference-readiness.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service-readiness-gate.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service-readiness-integration.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-repository-readiness-probe.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service-readiness-consumer.php';

final class SPDB_23N_Repository implements SPDB_Collections_Repository {
	/** @var array<string,mixed> */
	public array $health;
	public bool $throw = false;
	public int $health_calls = 0;

	public function __construct() {
		$this->health = spdb_23n_health();
	}
	public function health_check(): array {
		++$this->health_calls;
		if ( $this->throw ) {
			throw new RuntimeException( 'private Phase 23N repository exception' );
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

final class SPDB_23N_Resolver implements SPDB_Native_Reference_Resolver, SPDB_Native_Reference_Readiness {
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

final class SPDB_23N_Resolver_Without_Readiness implements SPDB_Native_Reference_Resolver {
	public int $resolve_calls = 0;
	public function resolve_reference( string $provider_key, string $object_type, string $object_id, array $context ) {
		++$this->resolve_calls;
		return array();
	}
}

$tests = 0;
$failed = 0;
function spdb_23n_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}
function spdb_23n_code( $value ): string {
	return $value instanceof WP_Error ? $value->get_error_code() : '';
}
/** @return array<string,mixed> */
function spdb_23n_health( array $changes = array() ): array {
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
function spdb_23n_private( object $object, string $property ) {
	$reflection = new ReflectionProperty( get_class( $object ), $property );
	$reflection->setAccessible( true );
	return $reflection->getValue( $object );
}
/** @param string[] $expected */
function spdb_23n_exact_keys( array $value, array $expected ): bool {
	$actual = array_keys( $value );
	sort( $actual );
	sort( $expected );
	return $actual === $expected;
}

$top_keys = array(
	'repository_available', 'resolver_available', 'read_ready', 'write_configured',
	'collection_write_ready', 'knowledge_write_ready', 'any_write_ready',
	'write_enabled', 'repository_health',
);
$repository_keys = array(
	'healthy', 'database_ready', 'schema_ready', 'schema_version', 'code', 'cached_for_request',
);

$constructor = new ReflectionMethod( SPDB_Collections_Service_Readiness_Consumer::class, '__construct' );
$cloner = new ReflectionMethod( SPDB_Collections_Service_Readiness_Consumer::class, '__clone' );
spdb_23n_assert( $constructor->isPrivate(), 'The consumer constructor must remain private.' );
spdb_23n_assert( $cloner->isPrivate(), 'The consumer must remain non-clonable.' );
spdb_23n_assert( ! method_exists( SPDB_Collections_Service_Readiness_Consumer::class, 'probe' ), 'The consumer must not expose the raw readiness probe.' );
spdb_23n_assert( ! method_exists( SPDB_Collections_Service_Readiness_Consumer::class, 'service' ), 'The consumer must not expose a Collections service.' );
spdb_23n_assert( ! method_exists( SPDB_Collections_Service_Readiness_Consumer::class, 'repository' ) && ! method_exists( SPDB_Collections_Service_Readiness_Consumer::class, 'resolver' ), 'The consumer must not expose repository or resolver dependencies.' );

$repository = new SPDB_23N_Repository();
$resolver = new SPDB_23N_Resolver();
$consumer = SPDB_Collections_Service_Readiness_Consumer::create( $repository, $resolver );
$probe = spdb_23n_private( $consumer, 'probe' );
$integration = spdb_23n_private( $probe, 'integration' );
$gate = spdb_23n_private( $integration, 'gate' );
spdb_23n_assert( spdb_23n_private( $probe, 'repository' ) === $repository, 'The consumer probe must retain the exact factory repository object.' );
spdb_23n_assert( spdb_23n_private( $gate, 'resolver' ) === $resolver, 'The consumer gate must retain the exact factory resolver object.' );
spdb_23n_assert( true === spdb_23n_private( $consumer, 'resolver_available' ), 'Resolver presence must be stored as a bounded boolean.' );

$clone_blocked = false;
try { $copy = clone $consumer; } catch ( Throwable $throwable ) { $clone_blocked = true; }
spdb_23n_assert( $clone_blocked, 'The consumer must reject cloning.' );
$serialize_blocked = false;
try { serialize( $consumer ); } catch ( LogicException $exception ) { $serialize_blocked = true; }
spdb_23n_assert( $serialize_blocked, 'The consumer must reject serialization.' );
$unserialize_blocked = false;
try { $consumer->__unserialize( array() ); } catch ( LogicException $exception ) { $unserialize_blocked = true; }
spdb_23n_assert( $unserialize_blocked, 'The consumer must reject unserialization.' );

$invalid = $consumer->health( 'false' );
spdb_23n_assert( spdb_23n_exact_keys( $invalid, $top_keys ), 'Invalid-input health must retain the exact stable service health keys.' );
spdb_23n_assert( spdb_23n_exact_keys( $invalid['repository_health'], $repository_keys ), 'Invalid-input repository health must retain the exact six-field shape.' );
spdb_23n_assert( false === $invalid['write_configured'] && false === $invalid['any_write_ready'] && 'repository_not_evaluated' === $invalid['repository_health']['code'], 'Weakly coercible write authority must fail closed.' );
spdb_23n_assert( true === $invalid['resolver_available'], 'Resolver presence must remain truthful even when authority input is invalid.' );
spdb_23n_assert( 0 === $repository->health_calls && 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls, 'Invalid write authority must not probe repository or resolver readiness.' );

$repository->health_calls = 0;
$resolver->reset();
$disabled = $consumer->health( false );
spdb_23n_assert( spdb_23n_exact_keys( $disabled, $top_keys ) && spdb_23n_exact_keys( $disabled['repository_health'], $repository_keys ), 'Writes-disabled health must retain exact stable shapes.' );
spdb_23n_assert( true === $disabled['repository_available'] && true === $disabled['resolver_available'] && true === $disabled['read_ready'], 'Writes-disabled health must truthfully report repository and resolver presence while preserving read readiness.' );
spdb_23n_assert( false === $disabled['write_configured'] && false === $disabled['write_enabled'] && false === $disabled['any_write_ready'], 'Writes-disabled health must deny every write state.' );
spdb_23n_assert( true === $disabled['repository_health']['healthy'] && 'ready' === $disabled['repository_health']['code'] && SPDB_Collections_Schema::VERSION === $disabled['repository_health']['schema_version'], 'Ready repository health must remain bounded and current.' );
spdb_23n_assert( 1 === $repository->health_calls && 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls, 'Writes-disabled health must acquire repository health once without evaluating resolver readiness.' );

$repository->health_calls = 0;
$resolver->reset();
$enabled = $consumer->health( true );
spdb_23n_assert( true === $enabled['collection_write_ready'] && true === $enabled['knowledge_write_ready'] && true === $enabled['write_enabled'], 'Fully ready service health must project both write classes.' );
spdb_23n_assert( 1 === $repository->health_calls && 1 === $resolver->ready_calls && 1 === $resolver->snapshot_calls && 0 === $resolver->resolve_calls, 'Enabled health must evaluate each readiness source once and never resolve a native object.' );

$repository->health_calls = 0;
$resolver->reset();
spdb_23n_assert( true === $consumer->require_read_ready(), 'Ready repository must satisfy service read readiness.' );
spdb_23n_assert( 1 === $repository->health_calls && 0 === $resolver->ready_calls, 'Read readiness must remain resolver-independent.' );

$repository->health_calls = 0;
$resolver->reset();
$invalid_requirement = $consumer->require_write_ready( 'yes', true );
spdb_23n_assert( 'spdb_collections_readiness_input_invalid' === spdb_23n_code( $invalid_requirement ), 'Weakly coercible resolver requirement must fail closed.' );
spdb_23n_assert( 0 === $repository->health_calls && 0 === $resolver->ready_calls, 'Invalid resolver requirement must short-circuit every readiness source.' );

$repository->health_calls = 0;
$resolver->reset();
spdb_23n_assert( 'spdb_collections_writes_disabled' === spdb_23n_code( $consumer->require_write_ready( false, false ) ), 'Disabled collection writes must preserve the stable denial.' );
spdb_23n_assert( 'spdb_collections_writes_disabled' === spdb_23n_code( $consumer->require_write_ready( true, false ) ), 'Disabled knowledge writes must preserve the stable denial.' );
spdb_23n_assert( 0 === $repository->health_calls && 0 === $resolver->ready_calls, 'Disabled writes must short-circuit repository and resolver readiness.' );

$repository->health_calls = 0;
$resolver->reset();
spdb_23n_assert( true === $consumer->require_write_ready( false, true ), 'Ready repository must satisfy collection-write readiness.' );
spdb_23n_assert( 1 === $repository->health_calls && 0 === $resolver->ready_calls, 'Collection-write readiness must remain resolver-independent.' );

$repository->health_calls = 0;
$resolver->reset();
spdb_23n_assert( true === $consumer->require_write_ready( true, true ), 'Ready repository and resolver must satisfy knowledge-write readiness.' );
spdb_23n_assert( 1 === $repository->health_calls && 1 === $resolver->ready_calls && 1 === $resolver->snapshot_calls && 0 === $resolver->resolve_calls, 'Knowledge-write readiness must evaluate each source once without native resolution.' );

$resolver->ready = false;
$repository->health_calls = 0;
$resolver->reset();
$unready_health = $consumer->health( true );
spdb_23n_assert( true === $unready_health['resolver_available'] && true === $unready_health['collection_write_ready'] && false === $unready_health['knowledge_write_ready'], 'An unready present resolver must block only knowledge readiness.' );
$resolver->ready = true;

$without_resolver = SPDB_Collections_Service_Readiness_Consumer::create( $repository, null );
$without_resolver_health = $without_resolver->health( true );
spdb_23n_assert( false === $without_resolver_health['resolver_available'] && true === $without_resolver_health['collection_write_ready'] && false === $without_resolver_health['knowledge_write_ready'], 'Resolver absence must remain distinct from collection readiness.' );
spdb_23n_assert( 'spdb_native_reference_resolver_unavailable' === spdb_23n_code( $without_resolver->require_write_ready( true, true ) ), 'Resolver absence must deny knowledge readiness.' );

$missing_readiness_resolver = new SPDB_23N_Resolver_Without_Readiness();
$missing_readiness = SPDB_Collections_Service_Readiness_Consumer::create( $repository, $missing_readiness_resolver );
$missing_health = $missing_readiness->health( true );
spdb_23n_assert( true === $missing_health['resolver_available'] && true === $missing_health['collection_write_ready'] && false === $missing_health['knowledge_write_ready'], 'A present resolver without formal readiness must fail closed only for knowledge readiness.' );
spdb_23n_assert( 'spdb_native_reference_readiness_missing' === spdb_23n_code( $missing_readiness->require_write_ready( true, true ) ), 'Missing resolver readiness contract must retain the stable denial.' );
spdb_23n_assert( 0 === $missing_readiness_resolver->resolve_calls, 'Missing readiness metadata must never fall through to native resolution.' );

$repository->throw = true;
$repository->health_calls = 0;
$resolver->reset();
$exception = $consumer->health( true );
spdb_23n_assert( false === $exception['read_ready'] && 'repository_not_ready' === $exception['repository_health']['code'], 'Repository exception must become bounded not-ready service health.' );
spdb_23n_assert( false === strpos( implode( '|', $exception['repository_health'] ), 'private Phase 23N repository exception' ), 'Repository exception text must never enter service health.' );
spdb_23n_assert( 1 === $repository->health_calls && 0 === $resolver->ready_calls, 'Repository failure must stop before resolver readiness.' );
$repository->throw = false;
spdb_23n_assert( true === $consumer->require_read_ready(), 'The same consumer must recover after repository exception.' );

$repository->health = spdb_23n_health( array( 'private_detail' => 'forbidden' ) );
$resolver->reset();
$unknown = $consumer->health( true );
spdb_23n_assert( 'repository_health_invalid' === $unknown['repository_health']['code'] && false === strpos( implode( '|', $unknown['repository_health'] ), 'forbidden' ), 'Unknown repository fields must fail closed without private detail relay.' );
spdb_23n_assert( 0 === $resolver->ready_calls, 'Invalid repository health must short-circuit resolver readiness.' );

$repository->health = spdb_23n_health( array( 'healthy' => false, 'database_ready' => false, 'code' => 'database_unavailable', 'cached_for_request' => false ) );
$resolver->reset();
$degraded = $consumer->health( true );
spdb_23n_assert( 'repository_not_ready' === $degraded['repository_health']['code'] && false === strpos( implode( '|', $degraded['repository_health'] ), 'database_unavailable' ), 'Degraded repository detail must be reduced to the bounded service code.' );
spdb_23n_assert( 0 === $resolver->ready_calls, 'Degraded repository health must short-circuit resolver readiness.' );

$repository->health = spdb_23n_health();
$repository_two = new SPDB_23N_Repository();
$resolver_two = new SPDB_23N_Resolver();
$consumer_two = SPDB_Collections_Service_Readiness_Consumer::create( $repository_two, $resolver_two );
$repository->health_calls = 0;
$resolver->reset();
$repository_two->health_calls = 0;
$resolver_two->reset();
spdb_23n_assert( true === $consumer_two->require_write_ready( true, true ), 'A second consumer must operate with its own dependency pair.' );
spdb_23n_assert( 0 === $repository->health_calls && 0 === $resolver->ready_calls, 'A second consumer must not touch the first consumer dependencies.' );
spdb_23n_assert( 1 === $repository_two->health_calls && 1 === $resolver_two->ready_calls && 0 === $resolver_two->resolve_calls, 'The second consumer must use only its own dependency pair.' );
spdb_23n_assert( 0 === $resolver->resolve_calls, 'No Phase 23N consumer path may resolve a native object.' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} Phase 23N readiness consumer tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} initial Phase 23N service readiness consumer tests passed.\n";
