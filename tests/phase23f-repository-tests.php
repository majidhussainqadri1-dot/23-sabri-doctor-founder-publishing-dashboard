<?php
/** Executable WordPress repository tests for the corrective Phase 23F slice. */
require_once __DIR__ . '/bootstrap.php';

final class SPDB_Test_WPDB {
	public string $prefix = 'wp_'; public array $rows = array(); public bool $missing_index = false; private int $next_id = 1;
	public function __construct() { foreach ( array( 'wp_spdb_collections', 'wp_spdb_collection_items', 'wp_spdb_knowledge_links' ) as $table ) { $this->rows[ $table ] = array(); } }
	public function get_charset_collate(): string { return 'CHARACTER SET utf8mb4'; }
	public function esc_like( string $value ): string { return addcslashes( $value, '_%\\' ); }
	public function prepare( string $query, ...$args ): string {
		if ( 1 === count( $args ) && is_array( $args[0] ) ) { $args = $args[0]; }
		$index = 0;
		return preg_replace_callback( '/%[ds]/', function ( $match ) use ( &$index, $args ) { $value = $args[ $index++ ] ?? null; return '%d' === $match[0] ? (string) (int) $value : "'" . str_replace( "'", "''", (string) $value ) . "'"; }, $query ) ?? $query;
	}
	public function get_var( string $query ) {
		if ( preg_match( "/SHOW TABLES LIKE '([^']+)'/", $query, $match ) ) { return array_key_exists( str_replace( '\\_', '_', $match[1] ), $this->rows ) ? str_replace( '\\_', '_', $match[1] ) : null; }
		if ( false !== stripos( $query, 'SELECT COUNT(*)' ) ) { return count( $this->filter_select( $query ) ); }
		return null;
	}
	public function get_results( string $query, $output = null ) {
		if ( preg_match( '/SHOW COLUMNS FROM `([^`]+)`/', $query, $match ) ) { $requirements = SPDB_Collections_Schema::requirements(); return array_map( static fn( $field ) => array( 'Field' => $field ), $requirements[ $match[1] ]['columns'] ?? array() ); }
		if ( preg_match( '/SHOW INDEX FROM `([^`]+)`/', $query, $match ) ) { $requirements = SPDB_Collections_Schema::requirements(); $indexes = $requirements[ $match[1] ]['indexes'] ?? array(); if ( $this->missing_index ) { array_pop( $indexes ); } return array_map( static fn( $name ) => array( 'Key_name' => $name ), $indexes ); }
		if ( false !== stripos( $query, 'SELECT *' ) ) { return $this->filter_select( $query ); }
		return array();
	}
	public function get_row( string $query, $output = null ) { $rows = $this->filter_select( $query ); return $rows[0] ?? null; }
	public function insert( string $table, array $data, $format = null ) { if ( ! isset( $this->rows[ $table ] ) ) { return false; } $data['id'] = $this->next_id++; $this->rows[ $table ][] = $data; return 1; }
	private function filter_select( string $query ): array {
		if ( ! preg_match( '/FROM\s+(wp_[a-z0-9_]+)/i', $query, $table_match ) ) { return array(); }
		$rows = $this->rows[ $table_match[1] ] ?? array();
		$filters = array(
			'collection_id' => "/collection_id = '([^']+)'/", 'link_id' => "/link_id = '([^']+)'/", 'item_id' => "/item_id = '([^']+)'/", 'scope' => "/scope = '([^']+)'/", 'record_type' => "/record_type = '([^']+)'/", 'status' => "/status = '([^']+)'/", 'idempotency_hash' => "/idempotency_hash = '([^']+)'/",
		);
		foreach ( $filters as $key => $pattern ) { if ( preg_match( $pattern, $query, $match ) ) { $rows = array_values( array_filter( $rows, static fn( $row ) => (string) ( $row[ $key ] ?? '' ) === $match[1] ) ); } }
		foreach ( array( 'owner_user_id', 'created_by' ) as $key ) { if ( preg_match( '/' . $key . ' = (\d+)/', $query, $match ) ) { $rows = array_values( array_filter( $rows, static fn( $row ) => (int) ( $row[ $key ] ?? 0 ) === (int) $match[1] ) ); } }
		if ( false !== stripos( $query, 'archived_at_gmt IS NULL' ) ) { $rows = array_values( array_filter( $rows, static fn( $row ) => null === ( $row['archived_at_gmt'] ?? null ) ) ); }
		$offset = preg_match( '/OFFSET (\d+)/', $query, $match ) ? (int) $match[1] : 0; $limit = preg_match( '/LIMIT (\d+)/', $query, $match ) ? (int) $match[1] : count( $rows );
		return array_slice( $rows, $offset, $limit );
	}
}
function spdb_repository_assert( bool $condition, string $message ): void { global $tests, $failed; ++$tests; if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); } }
function spdb_repository_code( $value ): string { return $value instanceof WP_Error ? $value->get_error_code() : ''; }
$tests = 0; $failed = 0;
$wpdb = new SPDB_Test_WPDB(); $GLOBALS['wpdb'] = $wpdb;
$repository = new SPDB_WP_Collections_Repository( $wpdb );
$health = $repository->health_check(); spdb_repository_assert( true === $health['healthy'] && '3' === $health['schema_version'], 'The concrete repository must verify Schema 3 before use.' );
$record = array( 'record_type' => 'collection', 'scope' => 'own', 'title' => 'Study Set', 'objective' => '', 'ethical_declaration' => '', 'contributors' => array(), 'target_surfaces' => array(), 'status' => 'draft', 'start_at_gmt' => '', 'end_at_gmt' => '', 'owner_user_id' => 7, 'created_by' => 7, 'idempotency_hash' => str_repeat( 'a', 64 ), 'request_hash' => str_repeat( 'b', 64 ), 'audit_reason' => 'Create the reviewed repository record.' );
$created = $repository->create_collection( $record ); spdb_repository_assert( is_array( $created ) && 1 === $created['version'] && 7 === $created['owner_user_id'], 'The repository must create and hydrate an owned collection.' );
$replay = $repository->create_collection( $record ); spdb_repository_assert( is_array( $replay ) && true === $replay['replayed'], 'An exact idempotent replay must return the original record.' );
$conflict_record = $record; $conflict_record['request_hash'] = str_repeat( 'c', 64 ); $conflict = $repository->create_collection( $conflict_record ); spdb_repository_assert( 'spdb_idempotency_payload_conflict' === spdb_repository_code( $conflict ), 'The same idempotency key with another payload must conflict.' );
$list = $repository->list_collections( array( 'scope' => 'own', 'owner_user_id' => 7, 'record_type' => 'collection', 'status' => 'draft', 'page' => 1, 'per_page' => 20 ) ); spdb_repository_assert( is_array( $list ) && 1 === $list['total'] && 1 === count( $list['items'] ), 'The concrete repository must return a bounded collection envelope.' );
$link_record = array( 'scope' => 'own', 'owner_user_id' => 7, 'source_provider_key' => 'file21', 'source_object_type' => 'publication', 'source_object_id' => 'post-101', 'source_native_version' => 'v1', 'target_provider_key' => 'file06', 'target_object_type' => 'remedy', 'target_object_id' => 'remedy-22', 'target_native_version' => 'v2', 'relation_type' => 'encyclopedia', 'relation_hash' => str_repeat( 'd', 64 ), 'status' => 'active', 'created_by' => 7, 'idempotency_hash' => str_repeat( 'e', 64 ), 'request_hash' => str_repeat( 'f', 64 ), 'audit_reason' => 'Create the reviewed knowledge relationship.' );
$link = $repository->create_knowledge_link( $link_record ); spdb_repository_assert( is_array( $link ) && 'v1' === $link['source_native_version'], 'The repository must persist observed native versions without destinations.' );
$unsupported = $repository->archive_knowledge_link( $link['link_id'], 1, array() ); spdb_repository_assert( 'spdb_repository_operation_not_implemented' === spdb_repository_code( $unsupported ), 'Ungated archive operations must remain explicitly fail-closed.' );
$wpdb->missing_index = true; $broken = SPDB_Collections_Schema::verify(); spdb_repository_assert( 'spdb_collections_schema_index_missing' === spdb_repository_code( $broken ), 'Schema verification must detect a missing required index.' );
if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} Phase 23F repository tests failed.\n" ); exit( 1 ); }
echo "All {$tests} corrective Phase 23F repository tests passed.\n";
