<?php
/** Executable read-only REST and collection-item authority tests for Phase 23F. */
require_once __DIR__ . '/bootstrap.php';
if ( ! defined( 'SMC_VERSION' ) ) { define( 'SMC_VERSION', '1.0.1' ); }
if ( ! function_exists( 'smc_user_status' ) ) { function smc_user_status( $user_id ) { return (string) ( $GLOBALS['spdb_test_member_statuses'][ $user_id ] ?? $GLOBALS['spdb_test_member_status'] ); } }
if ( ! function_exists( 'smc_is_founder' ) ) { function smc_is_founder( $user_id ) { return (bool) $GLOBALS['spdb_test_founder']; } }
if ( ! function_exists( 'smc_is_trusted_publisher' ) ) { function smc_is_trusted_publisher( $user_id ) { return false; } }

final class SPDB_Test_Read_Repository implements SPDB_Collections_Repository {
	public array $collection;
	public array $item;
	public array $link;
	public int $item_queries = 0;
	public bool $bad_has_more = false;
	public $total_override = null;
	public function __construct() {
		$this->collection = array( 'collection_id' => 'collection_123e4567e89b12d3a456000000000099', 'record_type' => 'collection', 'scope' => 'own', 'title' => 'Study Set', 'objective' => '', 'ethical_declaration' => '', 'owner_user_id' => 7, 'contributors' => array(), 'target_surfaces' => array(), 'status' => 'draft', 'start_at_gmt' => '', 'end_at_gmt' => '', 'version' => 1, 'created_by' => 7, 'created_at_gmt' => '2026-07-31T00:00:00Z', 'updated_at_gmt' => '2026-07-31T00:00:00Z', 'archived_at_gmt' => '' );
		$this->item = array( 'item_id' => 'item_123e4567e89b12d3a456000000000001', 'collection_id' => $this->collection['collection_id'], 'provider_key' => 'file21', 'object_type' => 'publication', 'object_id' => 'post-101', 'relation_type' => 'contains', 'native_version' => 'v1', 'position' => 0, 'version' => 1, 'added_by' => 7, 'created_at_gmt' => '2026-07-31T00:00:00Z', 'updated_at_gmt' => '2026-07-31T00:00:00Z', 'archived_at_gmt' => '' );
		$this->link = array( 'link_id' => 'knowledge_123e4567e89b12d3a456000000000001', 'scope' => 'own', 'owner_user_id' => 7, 'source_provider_key' => 'file21', 'source_object_type' => 'publication', 'source_object_id' => 'post-101', 'source_native_version' => 'v1', 'target_provider_key' => 'file06', 'target_object_type' => 'remedy', 'target_object_id' => 'remedy-22', 'target_native_version' => 'v2', 'relation_type' => 'encyclopedia', 'status' => 'active', 'version' => 1, 'created_by' => 7, 'created_at_gmt' => '2026-07-31T00:00:00Z', 'updated_at_gmt' => '2026-07-31T00:00:00Z', 'archived_at_gmt' => '' );
	}
	public function health_check(): array { return array( 'healthy' => true, 'schema_ready' => true, 'code' => 'ready' ); }
	public function list_collections( array $query ) { $total = null === $this->total_override ? 1 : $this->total_override; return array( 'items' => array( $this->collection ), 'page' => $query['page'], 'per_page' => $query['per_page'], 'total' => $total, 'has_more' => $this->bad_has_more ); }
	public function get_collection( string $collection_id ) { return $collection_id === $this->collection['collection_id'] ? $this->collection : new WP_Error( 'spdb_collection_not_found', '', array( 'status' => 404 ) ); }
	public function create_collection( array $record ) { return new WP_Error( 'not_implemented' ); }
	public function update_collection( string $collection_id, int $expected_version, array $changes, array $operation ) { return new WP_Error( 'not_implemented' ); }
	public function archive_collection( string $collection_id, int $expected_version, array $operation ) { return new WP_Error( 'not_implemented' ); }
	public function list_collection_items( string $collection_id, array $query = array() ) { ++$this->item_queries; return array( 'items' => array( $this->item ), 'page' => $query['page'], 'per_page' => $query['per_page'], 'total' => 1, 'has_more' => false ); }
	public function get_collection_item( string $collection_id, string $item_id ) { ++$this->item_queries; return $collection_id === $this->item['collection_id'] && $item_id === $this->item['item_id'] ? $this->item : new WP_Error( 'spdb_collection_item_not_found', '', array( 'status' => 404 ) ); }
	public function add_collection_item( string $collection_id, int $expected_collection_version, array $record ) { return new WP_Error( 'not_implemented' ); }
	public function update_collection_item( string $collection_id, string $item_id, int $expected_collection_version, int $expected_item_version, array $changes, array $operation ) { return new WP_Error( 'not_implemented' ); }
	public function archive_collection_item( string $collection_id, string $item_id, int $expected_collection_version, int $expected_item_version, array $operation ) { return new WP_Error( 'not_implemented' ); }
	public function list_knowledge_links( array $query ) { return array( 'items' => array( $this->link ), 'page' => $query['page'], 'per_page' => $query['per_page'], 'total' => 1, 'has_more' => false ); }
	public function get_knowledge_link( string $link_id ) { return $link_id === $this->link['link_id'] ? $this->link : new WP_Error( 'spdb_knowledge_link_not_found', '', array( 'status' => 404 ) ); }
	public function create_knowledge_link( array $record ) { return new WP_Error( 'not_implemented' ); }
	public function update_knowledge_link( string $link_id, int $expected_version, array $changes, array $operation ) { return new WP_Error( 'not_implemented' ); }
	public function archive_knowledge_link( string $link_id, int $expected_version, array $operation ) { return new WP_Error( 'not_implemented' ); }
}

$tests = 0; $failed = 0;
function spdb_read_rest_assert( bool $condition, string $message ): void { global $tests, $failed; ++$tests; if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); } }
function spdb_read_rest_code( $value ): string { return $value instanceof WP_Error ? $value->get_error_code() : ''; }

$GLOBALS['spdb_test_user_id'] = 7;
$GLOBALS['spdb_test_member_status'] = 'approved';
$GLOBALS['spdb_test_member_statuses'][7] = 'approved';
$GLOBALS['spdb_test_capabilities']['spdb_view_own_content'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_manage_campaigns'] = false;
$repository = new SPDB_Test_Read_Repository();
$service = new SPDB_Collections_Service( $repository );
$controller = new SPDB_Collections_REST_Controller( $service );

$controller->register_routes();
spdb_read_rest_assert( 6 === count( $GLOBALS['spdb_test_rest_routes'] ), 'Phase 23F must register exactly six explicit read routes in this slice.' );
foreach ( $GLOBALS['spdb_test_rest_routes'] as $route => $definition ) {
	spdb_read_rest_assert( WP_REST_Server::READABLE === $definition['methods'], "Route {$route} must be read-only." );
}

$GLOBALS['spdb_test_member_statuses'][7] = 'submitted';
spdb_read_rest_assert( 'spdb_collections_rest_forbidden' === spdb_read_rest_code( $controller->permission_check() ), 'Pending accounts must be denied at the REST permission callback.' );
$GLOBALS['spdb_test_member_statuses'][7] = 'approved';
spdb_read_rest_assert( true === $controller->permission_check(), 'Approved capable accounts must pass the read permission callback.' );

$unknown_query = new WP_REST_Request( '/spdb/v1/collections', array(), array(), array( 'user_id' => 99 ) );
spdb_read_rest_assert( 'spdb_collections_rest_query_invalid' === spdb_read_rest_code( $controller->rest_collections( $unknown_query ) ), 'Unknown or client-supplied authority query fields must fail closed.' );

$GLOBALS['spdb_test_wrap_rest_response'] = true;
$list_request = new WP_REST_Request( '/spdb/v1/collections', array(), array(), array( 'scope' => 'own', 'page' => 1, 'per_page' => 20 ) );
$list_response = $controller->rest_collections( $list_request );
spdb_read_rest_assert( $list_response instanceof WP_HTTP_Response && '1' === ( $list_response->headers['X-WP-Total'] ?? '' ) && '1' === ( $list_response->headers['X-WP-TotalPages'] ?? '' ), 'Collection list responses must expose truthful pagination headers.' );
spdb_read_rest_assert( '0' === ( $list_response->headers['X-SPDB-Has-More'] ?? '' ), 'Continuation headers must match the validated envelope.' );
$GLOBALS['spdb_test_wrap_rest_response'] = false;

$repository->bad_has_more = true;
$bad_envelope = $service->list_collections( array( 'scope' => 'own', 'page' => 1, 'per_page' => 20 ) );
spdb_read_rest_assert( 'spdb_repository_response_invalid' === spdb_read_rest_code( $bad_envelope ), 'An incorrect has_more value must invalidate the repository envelope.' );
$repository->bad_has_more = false;
$repository->total_override = str_repeat( '9', 80 );
$overflow_total = $service->list_collections( array( 'scope' => 'own', 'page' => 1, 'per_page' => 20 ) );
spdb_read_rest_assert( 'spdb_repository_response_invalid' === spdb_read_rest_code( $overflow_total ), 'Overflowing repository totals must not saturate into valid pagination values.' );
$repository->total_override = null;

$item_list = $service->list_collection_items( $repository->collection['collection_id'], array( 'page' => 1, 'per_page' => 20 ) );
spdb_read_rest_assert( is_array( $item_list ) && 1 === count( $item_list['items'] ) && 0 === $item_list['items'][0]['position'], 'Authorized item reads must return a strict collection-item projection.' );

$repository->item['position'] = '0abc';
$bad_item = $service->list_collection_items( $repository->collection['collection_id'], array() );
spdb_read_rest_assert( 'spdb_collection_items_repository_response_invalid' === spdb_read_rest_code( $bad_item ), 'Malformed item numeric values must fail closed rather than be cast.' );
$repository->item['position'] = 0;
$repository->item['version'] = str_repeat( '9', 80 );
$overflow_item = $service->list_collection_items( $repository->collection['collection_id'], array() );
spdb_read_rest_assert( 'spdb_collection_items_repository_response_invalid' === spdb_read_rest_code( $overflow_item ), 'Overflowing item versions must not saturate into valid integers.' );
$repository->item['version'] = 1;
$repository->item['archived_at_gmt'] = '2026-07-31T00:10:00Z';
$archived_item = $service->get_collection_item( $repository->collection['collection_id'], $repository->item['item_id'] );
spdb_read_rest_assert( 'spdb_collection_item_not_found' === spdb_read_rest_code( $archived_item ), 'Archived items must not appear through default item detail reads.' );
$repository->item['archived_at_gmt'] = '';

$repository->collection['title'] = "\xC3\x28";
$invalid_utf8 = $service->list_collections( array( 'scope' => 'own' ) );
spdb_read_rest_assert( 'spdb_collections_repository_response_invalid' === spdb_read_rest_code( $invalid_utf8 ), 'Invalid UTF-8 collection text must fail closed before projection.' );
$repository->collection['title'] = 'Study Set';
$repository->collection['archived_at_gmt'] = array( 'malformed' );
$bad_optional_time = $service->list_collections( array( 'scope' => 'own' ) );
spdb_read_rest_assert( 'spdb_collections_repository_response_invalid' === spdb_read_rest_code( $bad_optional_time ), 'Malformed optional lifecycle timestamps must not be normalized to empty.' );
$repository->collection['archived_at_gmt'] = '';

$queries_before = $repository->item_queries;
$repository->collection['owner_user_id'] = 99;
$foreign_parent = $service->list_collection_items( $repository->collection['collection_id'], array() );
spdb_read_rest_assert( 'spdb_collection_not_found' === spdb_read_rest_code( $foreign_parent ) && $queries_before === $repository->item_queries, 'Parent collection IDOR denial must occur before any item repository query.' );
$repository->collection['owner_user_id'] = 7;

$GLOBALS['spdb_test_founder'] = true;
$institution_without_capability = $service->list_collections( array( 'scope' => 'institution' ) );
spdb_read_rest_assert( 'spdb_collections_institution_forbidden' === spdb_read_rest_code( $institution_without_capability ), 'Founder identity without campaign authority must not open institution metadata.' );
$GLOBALS['spdb_test_founder'] = false;

$detail_with_query = new WP_REST_Request( '/spdb/v1/collections/' . $repository->collection['collection_id'], array( 'collection_id' => $repository->collection['collection_id'] ), array(), array( 'context' => 'edit' ) );
spdb_read_rest_assert( 'spdb_collections_rest_query_invalid' === spdb_read_rest_code( $controller->rest_collection( $detail_with_query ) ), 'Detail routes must reject unsupported query parameters.' );

if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} Phase 23F read REST tests failed.\n" ); exit( 1 ); }
echo "All {$tests} Phase 23F read REST and item authority tests passed.\n";
