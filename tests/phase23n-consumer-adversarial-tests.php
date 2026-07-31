<?php
/** Adversarial invariant tests for corrected Phase 23N readiness consumer. */
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
	public function __construct() { $this->health = spdb_23n_adv_repository_health(); }
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
function spdb_23n_adv_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
}
/** @return array<string,mixed> */
function spdb_23n_adv_repository_health( array $changes = array() ): array {
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
function spdb_23n_adv_snapshot( array $changes = array() ): array {
	return array_merge(
		array(
			'inputs_valid' => true,
			'integration_code' => 'knowledge_ready',
			'repository_available' => true,
			'repository_ready' => true,
			'repository_code' => 'ready',
			'repository_schema_version' => SPDB_Collections_Schema::VERSION,
			'repository_cached_for_request' => true,
			'read_ready' => true,
			'write_configured' => true,
			'resolver_available' => true,
			'resolver_readiness_available' => true,
			'resolver_ready' => true,
			'resolver_code' => 'ready',
			'collection_write_ready' => true,
			'knowledge_write_ready' => true,
			'any_write_ready' => true,
			'write_enabled' => true,
		),
		$changes
	);
}
/** @return array<string,mixed> */
function spdb_23n_adv_service_health( array $changes = array() ): array {
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
$raw_validator = new ReflectionMethod( SPDB_Collections_Service_Readiness_Consumer::class, 'valid_snapshot' );
$raw_validator->setAccessible( true );
$public_validator = new ReflectionMethod( SPDB_Collections_Service_Readiness_Consumer::class, 'valid_health_projection' );
$public_validator->setAccessible( true );

spdb_23n_adv_assert( true === $raw_validator->invoke( $consumer, spdb_23n_adv_snapshot() ), 'Canonical knowledge-ready snapshot must validate.' );

$input_invalid = spdb_23n_adv_snapshot( array(
	'inputs_valid' => false,
	'integration_code' => 'integration_input_invalid',
	'repository_available' => false,
	'repository_ready' => false,
	'repository_code' => 'repository_not_evaluated',
	'repository_schema_version' => '',
	'repository_cached_for_request' => false,
	'read_ready' => false,
	'write_configured' => false,
	'resolver_available' => false,
	'resolver_readiness_available' => false,
	'resolver_ready' => false,
	'resolver_code' => 'resolver_not_evaluated',
	'collection_write_ready' => false,
	'knowledge_write_ready' => false,
	'any_write_ready' => false,
	'write_enabled' => false,
) );
spdb_23n_adv_assert( true === $raw_validator->invoke( $consumer, $input_invalid ), 'Exact input-not-evaluated snapshot must validate without probing dependencies.' );

$writes_disabled = spdb_23n_adv_snapshot( array(
	'integration_code' => 'writes_disabled',
	'write_configured' => false,
	'resolver_available' => false,
	'resolver_readiness_available' => false,
	'resolver_ready' => false,
	'resolver_code' => 'resolver_absent',
	'collection_write_ready' => false,
	'knowledge_write_ready' => false,
	'any_write_ready' => false,
	'write_enabled' => false,
) );
spdb_23n_adv_assert( true === $raw_validator->invoke( $consumer, $writes_disabled ), 'Writes-disabled read integration must validate its suppressed resolver state.' );

foreach ( array(
	array( 'repository_unavailable', false, false, 'repository_unavailable', '' ),
	array( 'repository_health_invalid', true, false, 'repository_health_invalid', '' ),
	array( 'repository_not_ready', true, false, 'repository_not_ready', SPDB_Collections_Schema::VERSION ),
) as $case ) {
	list( $integration_code, $available, $ready, $repository_code, $schema_version ) = $case;
	$snapshot = spdb_23n_adv_snapshot( array(
		'integration_code' => $integration_code,
		'repository_available' => $available,
		'repository_ready' => $ready,
		'repository_code' => $repository_code,
		'repository_schema_version' => $schema_version,
		'repository_cached_for_request' => false,
		'read_ready' => false,
		'resolver_available' => false,
		'resolver_readiness_available' => false,
		'resolver_ready' => false,
		'resolver_code' => 'resolver_absent',
		'collection_write_ready' => false,
		'knowledge_write_ready' => false,
		'any_write_ready' => false,
		'write_enabled' => false,
	) );
	spdb_23n_adv_assert( true === $raw_validator->invoke( $consumer, $snapshot ), "{$integration_code} must validate only with a suppressed resolver state." );
}

$collection_only = spdb_23n_adv_snapshot( array(
	'integration_code' => 'collection_ready',
	'resolver_ready' => false,
	'resolver_code' => 'resolver_not_ready',
	'knowledge_write_ready' => false,
) );
spdb_23n_adv_assert( true === $raw_validator->invoke( $consumer, $collection_only ), 'Collection-only readiness with a formally unready resolver must validate.' );

$unknown_repository = spdb_23n_adv_snapshot( array( 'repository_code' => 'database_table_missing' ) );
spdb_23n_adv_assert( false === $raw_validator->invoke( $consumer, $unknown_repository ), 'Unknown canonical repository code must be rejected.' );
$noncanonical_repository = spdb_23n_adv_snapshot( array( 'repository_code' => 'READY' ) );
spdb_23n_adv_assert( false === $raw_validator->invoke( $consumer, $noncanonical_repository ), 'A noncanonical repository code must be rejected.' );
$overlong_repository = spdb_23n_adv_snapshot( array( 'repository_code' => str_repeat( 'a', 65 ) ) );
spdb_23n_adv_assert( false === $raw_validator->invoke( $consumer, $overlong_repository ), 'An overlong repository code must be rejected.' );
$unknown_resolver = spdb_23n_adv_snapshot( array( 'resolver_code' => 'provider_custom_ready' ) );
spdb_23n_adv_assert( false === $raw_validator->invoke( $consumer, $unknown_resolver ), 'Unknown canonical resolver code must be rejected.' );
$unknown_integration = spdb_23n_adv_snapshot( array( 'integration_code' => 'custom_ready' ) );
spdb_23n_adv_assert( false === $raw_validator->invoke( $consumer, $unknown_integration ), 'Unknown canonical integration code must be rejected.' );
$wrong_code_state = spdb_23n_adv_snapshot( array( 'repository_code' => 'repository_not_ready' ) );
spdb_23n_adv_assert( false === $raw_validator->invoke( $consumer, $wrong_code_state ), 'Repository code and readiness state must remain inseparable.' );
$write_without_read = spdb_23n_adv_snapshot( array( 'repository_ready' => false, 'read_ready' => false ) );
spdb_23n_adv_assert( false === $raw_validator->invoke( $consumer, $write_without_read ), 'A write-ready state without repository read readiness must fail closed.' );
$forged_knowledge = spdb_23n_adv_snapshot( array( 'resolver_readiness_available' => false ) );
spdb_23n_adv_assert( false === $raw_validator->invoke( $consumer, $forged_knowledge ), 'Knowledge readiness without formal readiness capability must fail closed.' );
$knowledge_without_resolver_availability = spdb_23n_adv_snapshot( array(
	'resolver_available' => false,
	'resolver_readiness_available' => false,
	'resolver_ready' => false,
	'resolver_code' => 'resolver_absent',
) );
spdb_23n_adv_assert( false === $raw_validator->invoke( $consumer, $knowledge_without_resolver_availability ), 'Knowledge readiness without resolver availability must fail closed.' );

$valid_public = spdb_23n_adv_service_health();
spdb_23n_adv_assert( true === $public_validator->invoke( $consumer, $valid_public ), 'Exact canonical ready public health must validate.' );
$unknown_public_code = $valid_public; $unknown_public_code['repository_health']['code'] = 'database_table_missing';
spdb_23n_adv_assert( false === $public_validator->invoke( $consumer, $unknown_public_code ), 'Public health must reject unknown canonical repository codes.' );
$stale_public = $valid_public; $stale_public['repository_health']['schema_version'] = '2';
spdb_23n_adv_assert( false === $public_validator->invoke( $consumer, $stale_public ), 'Public health must reject stale schema versions.' );
$knowledge_without_readiness = SPDB_Collections_Service_Readiness_Consumer::create( $repository, new class implements SPDB_Native_Reference_Resolver {
	public function resolve_reference( string $provider_key, string $object_type, string $object_id, array $context ) { return array(); }
} );
spdb_23n_adv_assert( false === $public_validator->invoke( $knowledge_without_readiness, $valid_public ), 'Public knowledge readiness must require formal readiness capability, not presence alone.' );

$repository->health = spdb_23n_adv_repository_health( array( 'schema_version' => '2' ) ); $resolver->reset();
$stale = $consumer->health( true );
spdb_23n_adv_assert( 'repository_health_invalid' === $stale['repository_health']['code'] && '' === $stale['repository_health']['schema_version'], 'Stale repository schema must fail closed into bounded invalid health.' );
spdb_23n_adv_assert( 0 === $resolver->ready_calls, 'Stale repository health must short-circuit resolver readiness.' );

$repository->health = spdb_23n_adv_repository_health( array( 'database_ready' => 'yes' ) ); $resolver->reset();
$malformed = $consumer->health( true );
spdb_23n_adv_assert( 'repository_health_invalid' === $malformed['repository_health']['code'], 'Malformed scalar repository health must fail closed.' );
spdb_23n_adv_assert( 0 === $resolver->ready_calls, 'Malformed repository health must not reach resolver readiness.' );

$repository->health = spdb_23n_adv_repository_health( array( 'healthy' => false ) ); $resolver->reset();
$contradictory = $consumer->health( true );
spdb_23n_adv_assert( 'repository_health_invalid' === $contradictory['repository_health']['code'], 'Contradictory aggregate repository health must fail closed.' );
spdb_23n_adv_assert( 0 === $resolver->ready_calls, 'Contradictory repository health must short-circuit resolver readiness.' );
spdb_23n_adv_assert( 0 === $resolver->resolve_calls, 'No adversarial consumer path may resolve a native object.' );

if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} corrected Phase 23N adversarial tests failed.\n" ); exit( 1 ); }
echo "All {$tests} corrected Phase 23N adversarial consumer tests passed.\n";
