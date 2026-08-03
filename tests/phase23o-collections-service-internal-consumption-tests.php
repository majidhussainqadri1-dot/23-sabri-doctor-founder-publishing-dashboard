<?php
/** Executable tests for Phase 23O Collections service internal readiness consumption. */
require_once __DIR__ . '/bootstrap.php';
if ( ! defined( 'SPDB_PHASE23F_WRITES_ENABLED' ) ) { define( 'SPDB_PHASE23F_WRITES_ENABLED', true ); }
if ( ! defined( 'SMC_VERSION' ) ) { define( 'SMC_VERSION', '1.0.1' ); }
if ( ! function_exists( 'smc_user_status' ) ) { function smc_user_status( $user_id ) { return (string) ( $GLOBALS['spdb_test_member_statuses'][ $user_id ] ?? $GLOBALS['spdb_test_member_status'] ); } }
if ( ! function_exists( 'smc_is_founder' ) ) { function smc_is_founder( $user_id ) { return (bool) $GLOBALS['spdb_test_founder']; } }
if ( ! function_exists( 'smc_is_trusted_publisher' ) ) { function smc_is_trusted_publisher( $user_id ) { return false; } }

final class SPDB_23O_Repository implements SPDB_Collections_Repository {
	/** @var array<string,mixed> */
	public array $health;
	/** @var array<string,array<string,mixed>> */
	public array $collections = array();
	public int $health_calls = 0;
	public function __construct() { $this->health = spdb_23o_health(); }
	public function health_check(): array { ++$this->health_calls; return $this->health; }
	public function list_collections( array $query ) {
		$items = array_values( array_filter( $this->collections, static fn( $row ) => (string) $row['scope'] === $query['scope'] && (int) $row['owner_user_id'] === $query['owner_user_id'] ) );
		return array( 'items' => $items, 'page' => $query['page'], 'per_page' => $query['per_page'], 'total' => count( $items ), 'has_more' => false );
	}
	public function get_collection( string $collection_id ) { return $this->collections[ $collection_id ] ?? new WP_Error( 'spdb_collection_not_found', '', array( 'status' => 404 ) ); }
	public function create_collection( array $record ) { return $record; }
	public function update_collection( string $collection_id, int $expected_version, array $changes, array $operation ) { return new WP_Error( 'not_implemented' ); }
	public function archive_collection( string $collection_id, int $expected_version, array $operation ) { return new WP_Error( 'not_implemented' ); }
	public function list_collection_items( string $collection_id, array $query = array() ) { return array( 'items' => array(), 'page' => $query['page'] ?? 1, 'per_page' => $query['per_page'] ?? 20, 'total' => 0, 'has_more' => false ); }
	public function get_collection_item( string $collection_id, string $item_id ) { return new WP_Error( 'not_found' ); }
	public function add_collection_item( string $collection_id, int $expected_collection_version, array $record ) { return new WP_Error( 'not_implemented' ); }
	public function update_collection_item( string $collection_id, string $item_id, int $expected_collection_version, int $expected_item_version, array $changes, array $operation ) { return new WP_Error( 'not_implemented' ); }
	public function archive_collection_item( string $collection_id, string $item_id, int $expected_collection_version, int $expected_item_version, array $operation ) { return new WP_Error( 'not_implemented' ); }
	public function list_knowledge_links( array $query ) { return array( 'items' => array(), 'page' => $query['page'], 'per_page' => $query['per_page'], 'total' => 0, 'has_more' => false ); }
	public function get_knowledge_link( string $link_id ) { return new WP_Error( 'not_found' ); }
	public function create_knowledge_link( array $record ) { return $record; }
	public function update_knowledge_link( string $link_id, int $expected_version, array $changes, array $operation ) { return new WP_Error( 'not_implemented' ); }
	public function archive_knowledge_link( string $link_id, int $expected_version, array $operation ) { return new WP_Error( 'not_implemented' ); }
}

final class SPDB_23O_Resolver implements SPDB_Native_Reference_Resolver, SPDB_Native_Reference_Readiness {
	public bool $ready = true;
	public int $ready_calls = 0;
	public int $snapshot_calls = 0;
	public int $resolve_calls = 0;
	/** @var array<string,mixed> */
	public array $last_context = array();
	public function is_ready(): bool { ++$this->ready_calls; return $this->ready; }
	public function readiness_snapshot(): array {
		++$this->snapshot_calls;
		return $this->ready ? array( 'available' => true, 'ready' => true, 'code' => 'ready' ) : array( 'available' => true, 'ready' => false, 'code' => 'not_ready' );
	}
	public function resolve_reference( string $provider_key, string $object_type, string $object_id, array $context ) {
		++$this->resolve_calls;
		$this->last_context = $context;
		return array(
			'provider_key' => $provider_key,
			'object_type' => $object_type,
			'object_id' => $object_id,
			'exists' => true,
			'visible' => true,
			'reference_allowed' => true,
			'owner_user_id' => 7,
			'native_version' => 'v1',
			'destination' => 'https://example.test/native/view',
		);
	}
	public function reset(): void { $this->ready_calls = 0; $this->snapshot_calls = 0; $this->resolve_calls = 0; $this->last_context = array(); }
}

final class SPDB_23O_Resolver_Without_Readiness implements SPDB_Native_Reference_Resolver {
	public int $resolve_calls = 0;
	public function resolve_reference( string $provider_key, string $object_type, string $object_id, array $context ) { ++$this->resolve_calls; return array(); }
}

$tests = 0;
$failed = 0;
function spdb_23o_assert( bool $condition, string $message ): void {
	global $tests, $failed;
	++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
}
function spdb_23o_code( $value ): string { return $value instanceof WP_Error ? $value->get_error_code() : ''; }
function spdb_23o_private( object $object, string $property ) {
	$reflection = new ReflectionProperty( get_class( $object ), $property );
	$reflection->setAccessible( true );
	return $reflection->getValue( $object );
}
/** @return array<string,mixed> */
function spdb_23o_health( array $changes = array() ): array {
	return array_merge( array(
		'healthy' => true,
		'database_ready' => true,
		'schema_ready' => true,
		'schema_version' => SPDB_Collections_Schema::VERSION,
		'code' => 'ready',
		'cached_for_request' => true,
	), $changes );
}
/** @return array<string,mixed> */
function spdb_23o_collection_row(): array {
	return array(
		'collection_id' => 'collection_123e4567e89b12d3a456000000000099',
		'record_type' => 'collection', 'scope' => 'own', 'title' => 'Study Set',
		'objective' => '', 'ethical_declaration' => '', 'owner_user_id' => 7,
		'contributors' => array(), 'target_surfaces' => array(), 'status' => 'draft',
		'start_at_gmt' => '', 'end_at_gmt' => '', 'version' => 1, 'created_by' => 7,
		'created_at_gmt' => '2026-07-31T12:00:00Z', 'updated_at_gmt' => '2026-07-31T12:00:00Z', 'archived_at_gmt' => '',
	);
}
/** @return array<string,mixed> */
function spdb_23o_collection_input(): array {
	return array(
		'record_type' => 'collection', 'scope' => 'own', 'title' => 'Study Set',
		'objective' => '', 'ethical_declaration' => '', 'contributors' => array(),
		'target_surfaces' => array(), 'status' => 'draft', 'start_at_gmt' => '',
		'end_at_gmt' => '', 'idempotency_key' => 'phase23o-collection-0001',
		'audit_reason' => 'Prepare a reviewed own-scope collection.',
	);
}
/** @return array<string,mixed> */
function spdb_23o_link_input(): array {
	return array(
		'scope' => 'own',
		'source_provider_key' => 'file21', 'source_object_type' => 'publication', 'source_object_id' => 'post-101',
		'target_provider_key' => 'file06', 'target_object_type' => 'remedy', 'target_object_id' => 'remedy-22',
		'relation_type' => 'encyclopedia', 'idempotency_key' => 'phase23o-link-00001',
		'audit_reason' => 'Prepare two reviewed canonical knowledge references.',
	);
}

$GLOBALS['spdb_test_user_id'] = 7;
$GLOBALS['spdb_test_member_status'] = 'approved';
$GLOBALS['spdb_test_member_statuses'][7] = 'approved';
$GLOBALS['spdb_test_founder'] = false;
$GLOBALS['spdb_test_capabilities']['spdb_view_own_content'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_manage_own_content'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_manage_campaigns'] = false;

$repository = new SPDB_23O_Repository();
$resolver = new SPDB_23O_Resolver();
$service = new SPDB_Collections_Service( $repository, $resolver );
$consumer = spdb_23o_private( $service, 'readiness' );
spdb_23o_assert( $consumer instanceof SPDB_Collections_Service_Readiness_Consumer, 'The service must retain the reviewed readiness consumer.' );
$full_probe = spdb_23o_private( $consumer, 'probe' );
$collection_probe = spdb_23o_private( $consumer, 'collection_probe' );
spdb_23o_assert( spdb_23o_private( $full_probe, 'repository' ) === $repository && spdb_23o_private( $collection_probe, 'repository' ) === $repository, 'Full and collection-only probes must retain the exact service repository.' );
$full_gate = spdb_23o_private( spdb_23o_private( $full_probe, 'integration' ), 'gate' );
spdb_23o_assert( spdb_23o_private( $full_gate, 'resolver' ) === $resolver, 'The service consumer must retain the exact service resolver.' );

$top_keys = array( 'repository_available', 'resolver_available', 'read_ready', 'write_configured', 'collection_write_ready', 'knowledge_write_ready', 'any_write_ready', 'write_enabled', 'repository_health' );
$repository_keys = array( 'healthy', 'database_ready', 'schema_ready', 'schema_version', 'code', 'cached_for_request' );
$GLOBALS['spdb_test_environment'] = 'production';
$repository->health_calls = 0; $resolver->reset();
$health = $service->health();
spdb_23o_assert( array() === array_diff( $top_keys, array_keys( $health ) ) && array() === array_diff( array_keys( $health ), $top_keys ), 'Service health must retain the exact stable nine-field shape.' );
spdb_23o_assert( array() === array_diff( $repository_keys, array_keys( $health['repository_health'] ) ) && array() === array_diff( array_keys( $health['repository_health'] ), $repository_keys ), 'Service repository health must retain the exact bounded six-field shape.' );
spdb_23o_assert( true === $health['read_ready'] && false === $health['write_enabled'] && true === $health['resolver_available'], 'Production health must allow verified reads, deny writes, and report resolver presence truthfully.' );
spdb_23o_assert( 1 === $repository->health_calls && 0 === $resolver->ready_calls && 0 === $resolver->snapshot_calls, 'Writes-disabled service health must acquire repository health once and skip resolver readiness.' );

$GLOBALS['spdb_test_environment'] = 'staging';
$repository->health_calls = 0; $resolver->reset();
$staging_health = $service->health();
spdb_23o_assert( true === $staging_health['collection_write_ready'] && true === $staging_health['knowledge_write_ready'], 'Ready staging health must expose both reviewed write classes.' );
spdb_23o_assert( 1 === $repository->health_calls && 1 === $resolver->ready_calls && 1 === $resolver->snapshot_calls && 0 === $resolver->resolve_calls, 'Enabled service health must evaluate each readiness source once without native resolution.' );

$repository->collections['collection_123e4567e89b12d3a456000000000099'] = spdb_23o_collection_row();
$repository->health_calls = 0; $resolver->reset();
$list = $service->list_collections( array( 'scope' => 'own', 'page' => 1, 'per_page' => 20 ) );
spdb_23o_assert( is_array( $list ) && 1 === count( $list['items'] ), 'Service reads must still return validated own-scope data.' );
spdb_23o_assert( 1 === $repository->health_calls && 0 === $resolver->ready_calls, 'Service read authorization must consume one repository probe and remain resolver-independent.' );

$repository->health_calls = 0; $resolver->reset();
$collection = $service->prepare_collection_create( spdb_23o_collection_input() );
spdb_23o_assert( is_array( $collection ) && 7 === $collection['owner_user_id'], 'Collection preparation must pass the consumer-owned collection gate.' );
spdb_23o_assert( 1 === $repository->health_calls && 0 === $resolver->ready_calls && 0 === $resolver->resolve_calls, 'Collection preparation must remain resolver-independent.' );

$repository->health_calls = 0; $resolver->reset();
$link = $service->prepare_knowledge_link_create( spdb_23o_link_input() );
spdb_23o_assert( is_array( $link ) && 'v1' === $link['source_native_version'] && 'v1' === $link['target_native_version'], 'Knowledge preparation must retain observed source and target versions.' );
spdb_23o_assert( 1 === $repository->health_calls && 1 === $resolver->ready_calls && 1 === $resolver->snapshot_calls && 2 === $resolver->resolve_calls, 'Knowledge preparation must gate readiness once, then resolve exactly two references without double-gating.' );

$GLOBALS['spdb_test_environment'] = 'production';
$repository->health_calls = 0; $resolver->reset();
$blocked_direct = $service->resolve_reference( 'file21', 'publication', 'post-101', 'own' );
spdb_23o_assert( 'spdb_collections_writes_disabled' === spdb_23o_code( $blocked_direct ), 'Direct public resolution must not bypass the reviewed write-readiness gate.' );
spdb_23o_assert( 0 === $repository->health_calls && 0 === $resolver->ready_calls && 0 === $resolver->resolve_calls, 'Writes-disabled direct resolution must short-circuit every readiness and native call.' );

$GLOBALS['spdb_test_environment'] = 'staging';
$repository->health_calls = 0; $resolver->reset();
$resolved = $service->resolve_reference( 'file21', 'publication', 'post-101', 'own' );
spdb_23o_assert( is_array( $resolved ) && 'v1' === $resolved['native_version'], 'Direct resolution must succeed only after reviewed readiness.' );
spdb_23o_assert( 1 === $repository->health_calls && 1 === $resolver->ready_calls && 1 === $resolver->snapshot_calls && 1 === $resolver->resolve_calls, 'Direct resolution must gate once and execute the native resolver once.' );

$resolver->ready = false;
$repository->health_calls = 0; $resolver->reset();
$unready = $service->resolve_reference( 'file21', 'publication', 'post-101', 'own' );
spdb_23o_assert( 'spdb_native_reference_resolver_not_ready' === spdb_23o_code( $unready ), 'An unready formal resolver must block direct native resolution.' );
spdb_23o_assert( 1 === $repository->health_calls && 1 === $resolver->ready_calls && 0 === $resolver->resolve_calls, 'Unready resolver denial must occur before native resolution.' );
$resolver->ready = true;

$missing_readiness_resolver = new SPDB_23O_Resolver_Without_Readiness();
$missing_service = new SPDB_Collections_Service( $repository, $missing_readiness_resolver );
$repository->health_calls = 0;
$missing = $missing_service->resolve_reference( 'file21', 'publication', 'post-101', 'own' );
spdb_23o_assert( 'spdb_native_reference_readiness_missing' === spdb_23o_code( $missing ), 'A resolver without the formal readiness contract must fail closed.' );
spdb_23o_assert( 1 === $repository->health_calls && 0 === $missing_readiness_resolver->resolve_calls, 'Missing formal readiness must stop before native resolution.' );

$repository->health = spdb_23o_health( array( 'database_ready' => 'yes' ) );
$repository->health_calls = 0; $resolver->reset();
$invalid_repository = $service->list_collections( array( 'scope' => 'own' ) );
spdb_23o_assert( 'spdb_collections_repository_health_invalid' === spdb_23o_code( $invalid_repository ), 'Malformed repository health must fail closed through the consumer.' );
spdb_23o_assert( 1 === $repository->health_calls && 0 === $resolver->ready_calls && 0 === $resolver->resolve_calls, 'Malformed repository health must short-circuit resolver readiness and native resolution.' );

if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} Phase 23O service internal-consumption tests failed.\n" ); exit( 1 ); }
echo "All {$tests} Phase 23O Collections service internal-consumption tests passed.\n";
