<?php
/** Executable query, projection, and static accessibility tests for the Phase 23F Collections UI. */
require_once __DIR__ . '/bootstrap.php';
if ( ! defined( 'SMC_VERSION' ) ) { define( 'SMC_VERSION', '1.0.1' ); }
if ( ! function_exists( 'smc_user_status' ) ) { function smc_user_status( $user_id ) { return (string) ( $GLOBALS['spdb_test_member_statuses'][ $user_id ] ?? $GLOBALS['spdb_test_member_status'] ); } }
if ( ! function_exists( 'smc_is_founder' ) ) { function smc_is_founder( $user_id ) { return (bool) $GLOBALS['spdb_test_founder']; } }
if ( ! function_exists( 'smc_is_trusted_publisher' ) ) { function smc_is_trusted_publisher( $user_id ) { return false; } }

final class SPDB_Test_Collections_UI_Repository implements SPDB_Collections_Repository {
	public array $collection;
	public array $item;
	public array $link;
	public int $item_queries = 0;
	public function __construct() {
		$this->collection = array( 'collection_id' => 'collection_123e4567e89b12d3a456000000000099', 'record_type' => 'collection', 'scope' => 'own', 'title' => 'Study Set', 'objective' => '', 'ethical_declaration' => '', 'owner_user_id' => 7, 'contributors' => array(), 'target_surfaces' => array(), 'status' => 'draft', 'start_at_gmt' => '', 'end_at_gmt' => '', 'version' => 1, 'created_by' => 7, 'created_at_gmt' => '2026-07-31T00:00:00Z', 'updated_at_gmt' => '2026-07-31T00:00:00Z', 'archived_at_gmt' => '' );
		$this->item = array( 'item_id' => 'item_123e4567e89b12d3a456000000000001', 'collection_id' => $this->collection['collection_id'], 'provider_key' => 'file21', 'object_type' => 'publication', 'object_id' => 'post-101', 'relation_type' => 'contains', 'native_version' => 'v1', 'position' => 0, 'version' => 1, 'added_by' => 7, 'created_at_gmt' => '2026-07-31T00:00:00Z', 'updated_at_gmt' => '2026-07-31T00:00:00Z', 'archived_at_gmt' => '' );
		$this->link = array( 'link_id' => 'knowledge_123e4567e89b12d3a456000000000001', 'scope' => 'own', 'owner_user_id' => 7, 'source_provider_key' => 'file21', 'source_object_type' => 'publication', 'source_object_id' => 'post-101', 'source_native_version' => 'v1', 'target_provider_key' => 'file06', 'target_object_type' => 'remedy', 'target_object_id' => 'remedy-22', 'target_native_version' => 'v2', 'relation_type' => 'encyclopedia', 'status' => 'active', 'version' => 1, 'created_by' => 7, 'created_at_gmt' => '2026-07-31T00:00:00Z', 'updated_at_gmt' => '2026-07-31T00:00:00Z', 'archived_at_gmt' => '' );
	}
	public function health_check(): array { return array( 'healthy' => true, 'schema_ready' => true, 'code' => 'ready' ); }
	public function list_collections( array $query ) { return array( 'items' => array( $this->collection ), 'page' => $query['page'], 'per_page' => $query['per_page'], 'total' => 1, 'has_more' => false ); }
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
function spdb_ui_assert( bool $condition, string $message ): void { global $tests, $failed; ++$tests; if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); } }
function spdb_ui_code( $value ): string { return $value instanceof WP_Error ? $value->get_error_code() : ''; }

$GLOBALS['spdb_test_user_id'] = 7;
$GLOBALS['spdb_test_member_status'] = 'approved';
$GLOBALS['spdb_test_member_statuses'][7] = 'approved';
$GLOBALS['spdb_test_capabilities']['spdb_view_own_content'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_manage_own_content'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_manage_campaigns'] = false;
$GLOBALS['spdb_test_founder'] = false;

$repository = new SPDB_Test_Collections_UI_Repository();
$view = new SPDB_Collections_View( new SPDB_Collections_Service( $repository ) );

spdb_ui_assert( 'collections' === SPDB_Dashboard_Router::normalize_view( 'collections' ), 'The protected router must accept the Collections view.' );
spdb_ui_assert( 'overview' === SPDB_Dashboard_Router::normalize_view( 'collections/delete' ), 'Unknown or mutation-like view names must normalize to Overview.' );

$unknown = $view->resolve( array( 'view' => 'collections', 'user_id' => 99 ) );
spdb_ui_assert( 'spdb_collections_view_query_invalid' === spdb_ui_code( $unknown ), 'Client-supplied authority fields must be rejected by the UI query contract.' );
$overflow = $view->resolve( array( 'view' => 'collections', 'page' => str_repeat( '9', 80 ) ) );
spdb_ui_assert( 'spdb_collections_view_query_invalid' === spdb_ui_code( $overflow ), 'Overflowing UI pagination must fail closed.' );
$item_without_parent = $view->resolve( array( 'view' => 'collections', 'item_id' => $repository->item['item_id'] ) );
spdb_ui_assert( 'spdb_collections_view_query_invalid' === spdb_ui_code( $item_without_parent ), 'An item detail request must include its parent collection.' );
$mixed_section = $view->resolve( array( 'view' => 'collections', 'section' => 'knowledge', 'collection_id' => $repository->collection['collection_id'] ) );
spdb_ui_assert( 'spdb_collections_view_query_invalid' === spdb_ui_code( $mixed_section ), 'Knowledge mode must reject collection parameters.' );
$invalid_status = $view->resolve( array( 'view' => 'collections', 'record_type' => 'collection', 'status' => 'paused' ) );
spdb_ui_assert( 'spdb_collections_view_query_invalid' === spdb_ui_code( $invalid_status ), 'UI filters must bind status to the selected record type.' );

$list = $view->resolve( array( 'view' => 'collections', 'section' => 'collections', 'scope' => 'own', 'page' => 1, 'per_page' => 20 ) );
spdb_ui_assert( is_array( $list ) && 'collection_list' === $list['mode'] && 1 === count( $list['list']['items'] ), 'The view model must return an authorized collection-list projection.' );
$detail = $view->resolve( array( 'view' => 'collections', 'section' => 'collections', 'collection_id' => $repository->collection['collection_id'], 'per_page' => 20 ) );
spdb_ui_assert( is_array( $detail ) && 'collection_detail' === $detail['mode'] && 1 === count( $detail['items']['items'] ), 'Collection detail must include parent-authorized active items.' );
$item = $view->resolve( array( 'view' => 'collections', 'section' => 'collections', 'collection_id' => $repository->collection['collection_id'], 'item_id' => $repository->item['item_id'] ) );
spdb_ui_assert( is_array( $item ) && 'collection_item' === $item['mode'] && $repository->item['item_id'] === $item['item']['item_id'], 'Item detail must flow through the parent-authorized service path.' );
$knowledge = $view->resolve( array( 'view' => 'collections', 'section' => 'knowledge', 'scope' => 'own', 'page' => 1, 'per_page' => 10 ) );
spdb_ui_assert( is_array( $knowledge ) && 'knowledge_list' === $knowledge['mode'] && 1 === count( $knowledge['list']['items'] ), 'The view model must return an authorized knowledge-list projection.' );
$knowledge_detail = $view->resolve( array( 'view' => 'collections', 'section' => 'knowledge', 'link_id' => $repository->link['link_id'] ) );
spdb_ui_assert( is_array( $knowledge_detail ) && 'knowledge_detail' === $knowledge_detail['mode'], 'The view model must return an authorized knowledge-detail projection.' );

$queries_before = $repository->item_queries;
$repository->collection['owner_user_id'] = 99;
$foreign_item = $view->resolve( array( 'view' => 'collections', 'collection_id' => $repository->collection['collection_id'], 'item_id' => $repository->item['item_id'] ) );
spdb_ui_assert( 'spdb_collection_not_found' === spdb_ui_code( $foreign_item ) && $queries_before === $repository->item_queries, 'Foreign parent collection denial must occur before an item query.' );
$repository->collection['owner_user_id'] = 7;

$root = dirname( __DIR__ );
$template = file_get_contents( $root . '/templates/collections.php' );
$dashboard = file_get_contents( $root . '/templates/dashboard.php' );
$css = file_get_contents( $root . '/assets/css/collections.css' );
$page = file_get_contents( $root . '/includes/class-spdb-dashboard-page.php' );
spdb_ui_assert( is_string( $template ) && str_contains( $template, '<caption>' ) && str_contains( $template, 'aria-current="page"' ) && str_contains( $template, 'role="region"' ) && str_contains( $template, '<dl>'), 'The Collections template must provide semantic captions, current state, regions, and definition lists.' );
spdb_ui_assert( is_string( $template ) && ! preg_match( '/method\s*=\s*["\']post["\']|\b(?:create|update|delete|archive|reorder)_(?:collection|item|link)\b|WP_REST_Server::(?:CREATABLE|EDITABLE|DELETABLE)/i', $template ), 'The Collections template must not expose mutation controls.' );
spdb_ui_assert( is_string( $dashboard ) && str_contains( $dashboard, 'templates/collections.php' ) && str_contains( $dashboard, 'Phase 23F — Collections and Knowledge' ), 'The main dashboard must integrate the Collections template and truthful phase label.' );
spdb_ui_assert( is_string( $css ) && str_contains( $css, ':focus-visible' ) && str_contains( $css, '[dir="rtl"]' ) && str_contains( $css, 'prefers-reduced-motion' ) && str_contains( $css, 'forced-colors' ), 'Collections CSS must include focus, RTL, reduced-motion, and forced-color controls.' );
spdb_ui_assert( is_string( $page ) && str_contains( $page, 'SPDB_Collections_View' ) && str_contains( $page, "'page_id', 'p', 'post_type'" ) && str_contains( $page, 'assets/css/collections.css' ), 'The dashboard page must use the view model, preserve shortcode routing compatibility, and load Collections CSS.' );

if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} Phase 23F Collections UI tests failed.\n" ); exit( 1 ); }
echo "All {$tests} Phase 23F Collections UI boundary tests passed.\n";
