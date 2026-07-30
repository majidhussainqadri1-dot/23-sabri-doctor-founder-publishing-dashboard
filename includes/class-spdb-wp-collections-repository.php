<?php
/**
 * WordPress persistence for File 23-owned collection and knowledge metadata.
 *
 * This corrective slice enables verified reads and idempotent creates only.
 * Update, reorder, and archive operations remain explicitly fail-closed until
 * their separate review and test gate is complete.
 */
defined( 'ABSPATH' ) || exit;

final class SPDB_WP_Collections_Repository implements SPDB_Collections_Repository {
	private $wpdb;

	public function __construct( $wpdb = null ) {
		if ( null === $wpdb ) { global $wpdb; }
		$this->wpdb = $wpdb;
	}

	public function health_check(): array {
		$database = $this->database_available();
		$schema = $database ? SPDB_Collections_Schema::verify() : new WP_Error( 'spdb_collections_database_unavailable' );
		return array(
			'healthy' => $database && true === $schema,
			'database_ready' => $database,
			'schema_ready' => true === $schema,
			'schema_version' => SPDB_Collections_Schema::VERSION,
			'code' => true === $schema ? 'ready' : ( is_wp_error( $schema ) ? $schema->get_error_code() : 'schema_unavailable' ),
		);
	}

	public function list_collections( array $query ) {
		$ready = $this->ready(); if ( is_wp_error( $ready ) ) { return $ready; }
		$normalized = $this->normalize_list_query( $query, true ); if ( is_wp_error( $normalized ) ) { return $normalized; }
		$where = array( 'scope = %s', 'owner_user_id = %d' );
		$args = array( $normalized['scope'], $normalized['owner_user_id'] );
		if ( '' !== $normalized['record_type'] ) { $where[] = 'record_type = %s'; $args[] = $normalized['record_type']; }
		if ( '' !== $normalized['status'] ) { $where[] = 'status = %s'; $args[] = $normalized['status']; }
		$clause = implode( ' AND ', $where );
		$table = SPDB_Collections_Schema::collections_table();
		$offset = ( $normalized['page'] - 1 ) * $normalized['per_page'];
		$list_sql = "SELECT * FROM {$table} WHERE {$clause} ORDER BY updated_at_gmt DESC, id DESC LIMIT %d OFFSET %d";
		$list_args = array_merge( $args, array( $normalized['per_page'], $offset ) );
		$rows = $this->wpdb->get_results( $this->prepare( $list_sql, $list_args ), $this->array_output() );
		$total = $this->wpdb->get_var( $this->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$clause}", $args ) );
		if ( ! is_array( $rows ) || ! is_numeric( $total ) ) { return $this->database_error( 'spdb_collections_query_failed', 'The collection metadata query failed.' ); }
		$items = array();
		foreach ( $rows as $row ) { $record = $this->hydrate_collection( $row ); if ( is_wp_error( $record ) ) { return $record; } $items[] = $record; }
		return array( 'items' => $items, 'page' => $normalized['page'], 'per_page' => $normalized['per_page'], 'total' => (int) $total, 'has_more' => $offset + count( $items ) < (int) $total );
	}

	public function get_collection( string $collection_id ) {
		$ready = $this->ready(); if ( is_wp_error( $ready ) ) { return $ready; }
		if ( ! $this->valid_id( $collection_id ) ) { return $this->error( 'spdb_collection_id_invalid', 'The collection identifier is invalid.' ); }
		$table = SPDB_Collections_Schema::collections_table();
		$row = $this->wpdb->get_row( $this->prepare( "SELECT * FROM {$table} WHERE collection_id = %s LIMIT 1", array( $collection_id ) ), $this->array_output() );
		if ( null === $row ) { return $this->error( 'spdb_collection_not_found', 'The collection was not found.', 404 ); }
		return $this->hydrate_collection( $row );
	}

	public function create_collection( array $record ) {
		$ready = $this->ready(); if ( is_wp_error( $ready ) ) { return $ready; }
		$required = array( 'record_type', 'scope', 'title', 'objective', 'ethical_declaration', 'contributors', 'target_surfaces', 'status', 'start_at_gmt', 'end_at_gmt', 'owner_user_id', 'created_by', 'idempotency_hash', 'request_hash', 'audit_reason' );
		if ( array_diff( $required, array_keys( $record ) ) ) { return $this->error( 'spdb_collection_record_incomplete', 'The collection persistence record is incomplete.' ); }
		$actor = (int) $record['created_by'];
		$replay = $this->collection_replay( $actor, (string) $record['idempotency_hash'], (string) $record['request_hash'] );
		if ( null !== $replay ) { return $replay; }
		$now = gmdate( 'Y-m-d H:i:s' );
		$data = array(
			'collection_id' => $this->new_id( 'collection' ), 'record_type' => (string) $record['record_type'], 'scope' => (string) $record['scope'],
			'title' => (string) $record['title'], 'objective' => (string) $record['objective'], 'ethical_declaration' => (string) $record['ethical_declaration'],
			'owner_user_id' => (int) $record['owner_user_id'], 'contributors_json' => $this->json( $record['contributors'] ), 'target_surfaces_json' => $this->json( $record['target_surfaces'] ),
			'status' => (string) $record['status'], 'start_at_gmt' => $this->mysql_time( (string) $record['start_at_gmt'] ), 'end_at_gmt' => $this->mysql_time( (string) $record['end_at_gmt'] ),
			'version' => 1, 'idempotency_hash' => (string) $record['idempotency_hash'], 'request_hash' => (string) $record['request_hash'],
			'last_idempotency_hash' => (string) $record['idempotency_hash'], 'last_request_hash' => (string) $record['request_hash'],
			'created_audit_reason' => (string) $record['audit_reason'], 'last_audit_reason' => (string) $record['audit_reason'], 'created_by' => $actor,
			'created_at_gmt' => $now, 'updated_at_gmt' => $now, 'archived_at_gmt' => null,
		);
		$result = $this->wpdb->insert( SPDB_Collections_Schema::collections_table(), $data );
		if ( false === $result ) {
			$replay = $this->collection_replay( $actor, (string) $record['idempotency_hash'], (string) $record['request_hash'] );
			return null !== $replay ? $replay : $this->database_error( 'spdb_collection_create_failed', 'The collection metadata could not be created.' );
		}
		return $this->get_collection( $data['collection_id'] );
	}

	public function update_collection( string $collection_id, int $expected_version, array $changes, array $operation ) { return $this->not_implemented( 'collection update' ); }
	public function archive_collection( string $collection_id, int $expected_version, array $operation ) { return $this->not_implemented( 'collection archive' ); }

	public function list_collection_items( string $collection_id, array $query = array() ) {
		$ready = $this->ready(); if ( is_wp_error( $ready ) ) { return $ready; }
		if ( ! $this->valid_id( $collection_id ) ) { return $this->error( 'spdb_collection_id_invalid', 'The collection identifier is invalid.' ); }
		$page = $this->bounded_int( $query['page'] ?? 1, 1, 100000 ); $per_page = $this->bounded_int( $query['per_page'] ?? 20, 1, 50 );
		if ( null === $page || null === $per_page || array_diff( array_keys( $query ), array( 'page', 'per_page' ) ) ) { return $this->error( 'spdb_collection_items_query_invalid', 'The collection item query is invalid.' ); }
		$table = SPDB_Collections_Schema::items_table(); $offset = ( $page - 1 ) * $per_page;
		$rows = $this->wpdb->get_results( $this->prepare( "SELECT * FROM {$table} WHERE collection_id = %s AND archived_at_gmt IS NULL ORDER BY position ASC, id ASC LIMIT %d OFFSET %d", array( $collection_id, $per_page, $offset ) ), $this->array_output() );
		$total = $this->wpdb->get_var( $this->prepare( "SELECT COUNT(*) FROM {$table} WHERE collection_id = %s AND archived_at_gmt IS NULL", array( $collection_id ) ) );
		if ( ! is_array( $rows ) || ! is_numeric( $total ) ) { return $this->database_error( 'spdb_collection_items_query_failed', 'The collection item query failed.' ); }
		$items = array(); foreach ( $rows as $row ) { $item = $this->hydrate_item( $row ); if ( is_wp_error( $item ) ) { return $item; } $items[] = $item; }
		return array( 'items' => $items, 'page' => $page, 'per_page' => $per_page, 'total' => (int) $total, 'has_more' => $offset + count( $items ) < (int) $total );
	}

	public function get_collection_item( string $collection_id, string $item_id ) {
		$ready = $this->ready(); if ( is_wp_error( $ready ) ) { return $ready; }
		if ( ! $this->valid_id( $collection_id ) || ! $this->valid_id( $item_id ) ) { return $this->error( 'spdb_collection_item_id_invalid', 'The collection item identifier is invalid.' ); }
		$table = SPDB_Collections_Schema::items_table();
		$row = $this->wpdb->get_row( $this->prepare( "SELECT * FROM {$table} WHERE collection_id = %s AND item_id = %s LIMIT 1", array( $collection_id, $item_id ) ), $this->array_output() );
		return null === $row ? $this->error( 'spdb_collection_item_not_found', 'The collection item was not found.', 404 ) : $this->hydrate_item( $row );
	}
	public function add_collection_item( string $collection_id, int $expected_collection_version, array $record ) { return $this->not_implemented( 'collection item creation' ); }
	public function update_collection_item( string $collection_id, string $item_id, int $expected_collection_version, int $expected_item_version, array $changes, array $operation ) { return $this->not_implemented( 'collection item update' ); }
	public function archive_collection_item( string $collection_id, string $item_id, int $expected_collection_version, int $expected_item_version, array $operation ) { return $this->not_implemented( 'collection item archive' ); }

	public function list_knowledge_links( array $query ) {
		$ready = $this->ready(); if ( is_wp_error( $ready ) ) { return $ready; }
		$normalized = $this->normalize_list_query( $query, false ); if ( is_wp_error( $normalized ) ) { return $normalized; }
		$table = SPDB_Collections_Schema::links_table(); $offset = ( $normalized['page'] - 1 ) * $normalized['per_page'];
		$where = array( 'scope = %s', 'owner_user_id = %d' ); $args = array( $normalized['scope'], $normalized['owner_user_id'] );
		if ( '' !== $normalized['status'] ) { $where[] = 'status = %s'; $args[] = $normalized['status']; }
		$clause = implode( ' AND ', $where );
		$rows = $this->wpdb->get_results( $this->prepare( "SELECT * FROM {$table} WHERE {$clause} ORDER BY updated_at_gmt DESC, id DESC LIMIT %d OFFSET %d", array_merge( $args, array( $normalized['per_page'], $offset ) ) ), $this->array_output() );
		$total = $this->wpdb->get_var( $this->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$clause}", $args ) );
		if ( ! is_array( $rows ) || ! is_numeric( $total ) ) { return $this->database_error( 'spdb_knowledge_links_query_failed', 'The knowledge-link query failed.' ); }
		$items = array(); foreach ( $rows as $row ) { $item = $this->hydrate_link( $row ); if ( is_wp_error( $item ) ) { return $item; } $items[] = $item; }
		return array( 'items' => $items, 'page' => $normalized['page'], 'per_page' => $normalized['per_page'], 'total' => (int) $total, 'has_more' => $offset + count( $items ) < (int) $total );
	}

	public function get_knowledge_link( string $link_id ) {
		$ready = $this->ready(); if ( is_wp_error( $ready ) ) { return $ready; }
		if ( ! $this->valid_id( $link_id ) ) { return $this->error( 'spdb_knowledge_link_id_invalid', 'The knowledge-link identifier is invalid.' ); }
		$table = SPDB_Collections_Schema::links_table();
		$row = $this->wpdb->get_row( $this->prepare( "SELECT * FROM {$table} WHERE link_id = %s LIMIT 1", array( $link_id ) ), $this->array_output() );
		return null === $row ? $this->error( 'spdb_knowledge_link_not_found', 'The knowledge link was not found.', 404 ) : $this->hydrate_link( $row );
	}

	public function create_knowledge_link( array $record ) {
		$ready = $this->ready(); if ( is_wp_error( $ready ) ) { return $ready; }
		$required = array( 'scope', 'owner_user_id', 'source_provider_key', 'source_object_type', 'source_object_id', 'source_native_version', 'target_provider_key', 'target_object_type', 'target_object_id', 'target_native_version', 'relation_type', 'relation_hash', 'status', 'created_by', 'idempotency_hash', 'request_hash', 'audit_reason' );
		if ( array_diff( $required, array_keys( $record ) ) ) { return $this->error( 'spdb_knowledge_record_incomplete', 'The knowledge-link persistence record is incomplete.' ); }
		$actor = (int) $record['created_by']; $replay = $this->link_replay( $actor, (string) $record['idempotency_hash'], (string) $record['request_hash'] );
		if ( null !== $replay ) { return $replay; }
		$now = gmdate( 'Y-m-d H:i:s' );
		$data = array(
			'link_id' => $this->new_id( 'knowledge' ), 'scope' => (string) $record['scope'], 'owner_user_id' => (int) $record['owner_user_id'],
			'source_provider_key' => (string) $record['source_provider_key'], 'source_object_type' => (string) $record['source_object_type'], 'source_object_id' => (string) $record['source_object_id'], 'source_native_version' => (string) $record['source_native_version'],
			'target_provider_key' => (string) $record['target_provider_key'], 'target_object_type' => (string) $record['target_object_type'], 'target_object_id' => (string) $record['target_object_id'], 'target_native_version' => (string) $record['target_native_version'],
			'relation_type' => (string) $record['relation_type'], 'relation_hash' => (string) $record['relation_hash'], 'status' => (string) $record['status'], 'version' => 1,
			'idempotency_hash' => (string) $record['idempotency_hash'], 'request_hash' => (string) $record['request_hash'], 'last_idempotency_hash' => (string) $record['idempotency_hash'], 'last_request_hash' => (string) $record['request_hash'],
			'created_audit_reason' => (string) $record['audit_reason'], 'last_audit_reason' => (string) $record['audit_reason'], 'created_by' => $actor, 'created_at_gmt' => $now, 'updated_at_gmt' => $now, 'archived_at_gmt' => null,
		);
		$result = $this->wpdb->insert( SPDB_Collections_Schema::links_table(), $data );
		if ( false === $result ) { $replay = $this->link_replay( $actor, (string) $record['idempotency_hash'], (string) $record['request_hash'] ); return null !== $replay ? $replay : $this->database_error( 'spdb_knowledge_link_create_failed', 'The knowledge-link metadata could not be created.' ); }
		return $this->get_knowledge_link( $data['link_id'] );
	}
	public function update_knowledge_link( string $link_id, int $expected_version, array $changes, array $operation ) { return $this->not_implemented( 'knowledge-link update' ); }
	public function archive_knowledge_link( string $link_id, int $expected_version, array $operation ) { return $this->not_implemented( 'knowledge-link archive' ); }

	private function collection_replay( int $actor, string $key_hash, string $request_hash ) {
		$table = SPDB_Collections_Schema::collections_table();
		$row = $this->wpdb->get_row( $this->prepare( "SELECT * FROM {$table} WHERE created_by = %d AND idempotency_hash = %s LIMIT 1", array( $actor, $key_hash ) ), $this->array_output() );
		if ( null === $row ) { return null; }
		if ( ! is_array( $row ) || ! hash_equals( (string) ( $row['request_hash'] ?? '' ), $request_hash ) ) { return $this->error( 'spdb_idempotency_payload_conflict', 'The idempotency key was already used with a different request.', 409 ); }
		$record = $this->hydrate_collection( $row ); if ( is_array( $record ) ) { $record['replayed'] = true; } return $record;
	}
	private function link_replay( int $actor, string $key_hash, string $request_hash ) {
		$table = SPDB_Collections_Schema::links_table();
		$row = $this->wpdb->get_row( $this->prepare( "SELECT * FROM {$table} WHERE created_by = %d AND idempotency_hash = %s LIMIT 1", array( $actor, $key_hash ) ), $this->array_output() );
		if ( null === $row ) { return null; }
		if ( ! is_array( $row ) || ! hash_equals( (string) ( $row['request_hash'] ?? '' ), $request_hash ) ) { return $this->error( 'spdb_idempotency_payload_conflict', 'The idempotency key was already used with a different request.', 409 ); }
		$record = $this->hydrate_link( $row ); if ( is_array( $record ) ) { $record['replayed'] = true; } return $record;
	}

	private function hydrate_collection( $row ) {
		if ( ! is_array( $row ) ) { return $this->database_error( 'spdb_collection_record_invalid', 'A stored collection record is invalid.' ); }
		$contributors = $this->decode_list( $row['contributors_json'] ?? '' ); $surfaces = $this->decode_list( $row['target_surfaces_json'] ?? '' );
		if ( is_wp_error( $contributors ) || is_wp_error( $surfaces ) ) { return $this->database_error( 'spdb_collection_record_invalid', 'A stored collection record is invalid.' ); }
		return array(
			'collection_id' => (string) ( $row['collection_id'] ?? '' ), 'record_type' => (string) ( $row['record_type'] ?? '' ), 'scope' => (string) ( $row['scope'] ?? '' ), 'title' => (string) ( $row['title'] ?? '' ),
			'objective' => (string) ( $row['objective'] ?? '' ), 'ethical_declaration' => (string) ( $row['ethical_declaration'] ?? '' ), 'owner_user_id' => (int) ( $row['owner_user_id'] ?? 0 ),
			'contributors' => $contributors, 'target_surfaces' => $surfaces, 'status' => (string) ( $row['status'] ?? '' ), 'start_at_gmt' => $this->rfc3339( $row['start_at_gmt'] ?? null ), 'end_at_gmt' => $this->rfc3339( $row['end_at_gmt'] ?? null ),
			'version' => (int) ( $row['version'] ?? 0 ), 'created_by' => (int) ( $row['created_by'] ?? 0 ), 'created_at_gmt' => $this->rfc3339( $row['created_at_gmt'] ?? null ), 'updated_at_gmt' => $this->rfc3339( $row['updated_at_gmt'] ?? null ), 'archived_at_gmt' => $this->rfc3339( $row['archived_at_gmt'] ?? null ),
		);
	}
	private function hydrate_item( $row ) {
		if ( ! is_array( $row ) ) { return $this->database_error( 'spdb_collection_item_record_invalid', 'A stored collection item is invalid.' ); }
		return array( 'item_id' => (string) ( $row['item_id'] ?? '' ), 'collection_id' => (string) ( $row['collection_id'] ?? '' ), 'provider_key' => (string) ( $row['provider_key'] ?? '' ), 'object_type' => (string) ( $row['object_type'] ?? '' ), 'object_id' => (string) ( $row['object_id'] ?? '' ), 'relation_type' => (string) ( $row['relation_type'] ?? '' ), 'native_version' => (string) ( $row['native_version'] ?? '' ), 'position' => (int) ( $row['position'] ?? 0 ), 'version' => (int) ( $row['version'] ?? 0 ), 'added_by' => (int) ( $row['added_by'] ?? 0 ), 'created_at_gmt' => $this->rfc3339( $row['created_at_gmt'] ?? null ), 'updated_at_gmt' => $this->rfc3339( $row['updated_at_gmt'] ?? null ) );
	}
	private function hydrate_link( $row ) {
		if ( ! is_array( $row ) ) { return $this->database_error( 'spdb_knowledge_link_record_invalid', 'A stored knowledge link is invalid.' ); }
		return array( 'link_id' => (string) ( $row['link_id'] ?? '' ), 'scope' => (string) ( $row['scope'] ?? '' ), 'owner_user_id' => (int) ( $row['owner_user_id'] ?? 0 ), 'source_provider_key' => (string) ( $row['source_provider_key'] ?? '' ), 'source_object_type' => (string) ( $row['source_object_type'] ?? '' ), 'source_object_id' => (string) ( $row['source_object_id'] ?? '' ), 'source_native_version' => (string) ( $row['source_native_version'] ?? '' ), 'target_provider_key' => (string) ( $row['target_provider_key'] ?? '' ), 'target_object_type' => (string) ( $row['target_object_type'] ?? '' ), 'target_object_id' => (string) ( $row['target_object_id'] ?? '' ), 'target_native_version' => (string) ( $row['target_native_version'] ?? '' ), 'relation_type' => (string) ( $row['relation_type'] ?? '' ), 'status' => (string) ( $row['status'] ?? '' ), 'version' => (int) ( $row['version'] ?? 0 ), 'created_by' => (int) ( $row['created_by'] ?? 0 ), 'created_at_gmt' => $this->rfc3339( $row['created_at_gmt'] ?? null ), 'updated_at_gmt' => $this->rfc3339( $row['updated_at_gmt'] ?? null ), 'archived_at_gmt' => $this->rfc3339( $row['archived_at_gmt'] ?? null ) );
	}

	private function normalize_list_query( array $query, bool $collections ) {
		$allowed = $collections ? array( 'scope', 'owner_user_id', 'record_type', 'status', 'page', 'per_page' ) : array( 'scope', 'owner_user_id', 'status', 'page', 'per_page' );
		if ( array_diff( array_keys( $query ), $allowed ) ) { return $this->error( 'spdb_repository_query_invalid', 'The repository query contains an unsupported field.' ); }
		$scope = (string) ( $query['scope'] ?? '' ); $owner = (int) ( $query['owner_user_id'] ?? 0 ); $page = $this->bounded_int( $query['page'] ?? 1, 1, 100000 ); $per_page = $this->bounded_int( $query['per_page'] ?? 20, 1, 50 );
		if ( ! in_array( $scope, SPDB_Collections_Policy::scopes(), true ) || $owner < 1 || null === $page || null === $per_page ) { return $this->error( 'spdb_repository_query_invalid', 'The repository query is invalid.' ); }
		return array( 'scope' => $scope, 'owner_user_id' => $owner, 'record_type' => $collections ? (string) ( $query['record_type'] ?? '' ) : '', 'status' => (string) ( $query['status'] ?? '' ), 'page' => $page, 'per_page' => $per_page );
	}
	private function ready() { $health = $this->health_check(); return ! empty( $health['healthy'] ) ? true : $this->error( 'spdb_collections_repository_not_ready', 'The collection metadata repository is not ready.', 503 ); }
	private function database_available(): bool { return is_object( $this->wpdb ) && method_exists( $this->wpdb, 'prepare' ) && method_exists( $this->wpdb, 'get_var' ) && method_exists( $this->wpdb, 'get_row' ) && method_exists( $this->wpdb, 'get_results' ) && method_exists( $this->wpdb, 'insert' ); }
	private function prepare( string $sql, array $args ): string { return (string) call_user_func_array( array( $this->wpdb, 'prepare' ), array_merge( array( $sql ), $args ) ); }
	private function new_id( string $prefix ): string { return $prefix . '_' . strtolower( str_replace( '-', '', wp_generate_uuid4() ) ); }
	private function valid_id( string $value ): bool { return 1 === preg_match( '/^[a-z0-9][a-z0-9_-]{15,63}$/', $value ); }
	private function json( $value ): string { $json = wp_json_encode( $value ); return is_string( $json ) ? $json : '[]'; }
	private function decode_list( $value ) { if ( ! is_string( $value ) ) { return $this->error( 'spdb_repository_json_invalid', 'Stored metadata JSON is invalid.', 500 ); } $decoded = json_decode( $value, true ); return is_array( $decoded ) && $this->is_list( $decoded ) ? $decoded : $this->error( 'spdb_repository_json_invalid', 'Stored metadata JSON is invalid.', 500 ); }
	private function mysql_time( string $value ) { return '' === $value ? null : str_replace( array( 'T', 'Z' ), array( ' ', '' ), $value ); }
	private function rfc3339( $value ): string { if ( ! is_string( $value ) || '' === $value || '0000-00-00 00:00:00' === $value ) { return ''; } return str_replace( ' ', 'T', $value ) . 'Z'; }
	private function bounded_int( $raw, int $minimum, int $maximum ): ?int { if ( is_int( $raw ) ) { $value = $raw; } elseif ( is_string( $raw ) && 1 === preg_match( '/^[1-9]\d*$/', $raw ) ) { $value = (int) $raw; } else { return null; } return $value >= $minimum && $value <= $maximum ? $value : null; }
	private function is_list( array $value ): bool { $expected = 0; foreach ( $value as $key => $unused ) { if ( $key !== $expected ) { return false; } ++$expected; } return true; }
	private function array_output() { return defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A'; }
	private function not_implemented( string $operation ): WP_Error { return $this->error( 'spdb_repository_operation_not_implemented', sprintf( 'The %s operation remains disabled pending its separate review gate.', $operation ), 503 ); }
	private function database_error( string $code, string $message ): WP_Error { return $this->error( $code, $message, 500 ); }
	private function error( string $code, string $message, int $status = 422 ): WP_Error { return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => $status ) ); }
}
