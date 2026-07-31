<?php
/** Adversarial invariant tests for the Phase 23N service readiness consumer. */
require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-collections-repository.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-native-reference-readiness.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service-readiness-gate.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service-readiness-integration.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-repository-readiness-probe.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service-readiness-consumer.php';

final class SPDB_23N_Adversarial_Repository implements SPDB_Collections_Repository {
	/** @var array<string,mixed> */
	public array $health;
	public int $health_calls = 0;
	public function __construct() { $this->health = spdb_23n_adversarial_repository_health(); }
	public function health_check(): array { ++$this->health_calls; return $this->health; }
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

final class SPDB_23N_Adversarial_Resolver implements SPDB_Native_Reference_Resolver, SPDB_Native_Reference_Readiness {
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
function spdb_23n_adversarial_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
}
/** @return array<string,mixed> */
function spdb_23n_adversarial_repository_health( array $changes = array() ): array {
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
/** @return array<string,mixed> */
function spdb_23n_adversarial_service_health( array $changes = array() ): array {
	$base = array(
		'repository_available' => true,
		'resolver_available' => true,
		'read_ready' => true,
		'write_configured' => true,
		'collection_write_ready' => true,
		'knowledge_write_ready' => true,
		'any_write_ready' => true,
		'write_enabled' => true,
		'repository_health' => array(
			'healthy' => true,
			'database_ready' => true,
			'schema_ready' => true,
			'schema_version' => SPDB_Collections_Schema::VERSION,
			'code' => 'ready',
			'cached_for_request' => true,
		),
	);
	foreach ( $changes as $key => $value ) { $base[ $key ] = $value; }
	return $base;
}

$repository = new SPDB_23N_Adversarial_Repository();
$resolver = new SPDB_23N_Adversarial_Resolver();
$consumer = SPDB_Collections_Service_Readiness_Consumer::create( $repository, $resolver );

$repository->health = spdb_23n_adversarial_repository_health( array( 'schema_version' => '2' ) );
$resolver->reset();
$stale = $consumer->health( true );
spdb_23n_adversarial_assert( 'repository_health_invalid' === $stale['repository_health']['code'] && '' === $stale['repository_health']['schema_version'], 'A stale schema version must fail closed into a bounded invalid repository state.' );
spdb_23n_adversarial_assert( 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls, 'Stale schema health must short-circuit resolver readiness.' );

$repository->health = spdb_23n_adversarial_repository_health( array( 'database_ready' => 'yes' ) );
$resolver->reset();
$malformed = $consumer->health( true );
spdb_23n_adversarial_assert( 'repository_health_invalid' === $malformed['repository_health']['code'], 'Malformed scalar repository health must fail closed.' );
spdb_23n_adversarial_assert( 0 === $resolver->ready_calls, 'Malformed scalar repository health must not reach resolver readiness.' );

$repository->health = spdb_23n_adversarial_repository_health( array( 'healthy' => false ) );
$resolver->reset();
$contradictory = $consumer->health( true );
spdb_23n_adversarial_assert( 'repository_health_invalid' === $contradictory['repository_health']['code'], 'Contradictory aggregate repository health must fail closed.' );
spdb_23n_adversarial_assert( 0 === $resolver->ready_calls, 'Contradictory repository health must short-circuit resolver readiness.' );

$validator = new ReflectionMethod( SPDB_Collections_Service_Readiness_Consumer::class, 'valid_health_projection' );
$validator->setAccessible( true );
$valid = spdb_23n_adversarial_service_health();
spdb_23n_adversarial_assert( true === $validator->invoke( $consumer, $valid ), 'The exact canonical ready service-health projection must validate.' );

$noncanonical_code = $valid;
$noncanonical_code['repository_health']['code'] = 'READY';
spdb_23n_adversarial_assert( false === $validator->invoke( $consumer, $noncanonical_code ), 'A noncanonical repository code must be rejected by the consumer validator.' );

$long_code = $valid;
$long_code['repository_health']['code'] = str_repeat( 'a', 65 );
spdb_23n_adversarial_assert( false === $validator->invoke( $consumer, $long_code ), 'An overlong repository code must be rejected.' );

$stale_projection = $valid;
$stale_projection['repository_health']['schema_version'] = '2';
spdb_23n_adversarial_assert( false === $validator->invoke( $consumer, $stale_projection ), 'A stale service-health schema version must be rejected.' );

$ready_code_conflict = $valid;
$ready_code_conflict['repository_health']['healthy'] = false;
$ready_code_conflict['repository_health']['database_ready'] = false;
$ready_code_conflict['repository_health']['schema_ready'] = false;
$ready_code_conflict['read_ready'] = false;
$ready_code_conflict['collection_write_ready'] = false;
$ready_code_conflict['knowledge_write_ready'] = false;
$ready_code_conflict['any_write_ready'] = false;
$ready_code_conflict['write_enabled'] = false;
spdb_23n_adversarial_assert( false === $validator->invoke( $consumer, $ready_code_conflict ), 'An unhealthy repository may not retain code=ready.' );

$write_without_read = $valid;
$write_without_read['read_ready'] = false;
$write_without_read['repository_health']['healthy'] = false;
$write_without_read['repository_health']['database_ready'] = false;
$write_without_read['repository_health']['schema_ready'] = false;
$write_without_read['repository_health']['code'] = 'repository_not_ready';
spdb_23n_adversarial_assert( false === $validator->invoke( $consumer, $write_without_read ), 'A write-ready state without repository read readiness must be rejected.' );

$knowledge_without_resolver = $valid;
$knowledge_without_resolver['resolver_available'] = false;
spdb_23n_adversarial_assert( false === $validator->invoke( $consumer, $knowledge_without_resolver ), 'Knowledge readiness without resolver availability must be rejected.' );

spdb_23n_adversarial_assert( 0 === $resolver->resolve_calls, 'No adversarial consumer path may resolve a native object.' );

if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} Phase 23N adversarial consumer tests failed.\n" ); exit( 1 ); }
echo "All {$tests} Phase 23N adversarial consumer tests passed.\n";
