<?php
/**
 * WordPress persistence for File 23-owned collection and knowledge metadata.
 *
 * Verified reads and idempotent creates are implemented. Update, reorder, and
 * archive operations remain fail-closed until their separate review gate.
 */
defined( 'ABSPATH' ) || exit;

final class SPDB_WP_Collections_Repository implements SPDB_Collections_Repository {
	private $wpdb;
	/** @var array<string,mixed>|null */
	private ?array $health_cache = null;

	public function __construct( $wpdb = null ) {
		if ( null === $wpdb ) {
			global $wpdb;
		}
		$this->wpdb = $wpdb;
	}

	/** @return array<string,mixed> */
	public function health_check(): array {
		if ( null !== $this->health_cache ) {
			return $this->health_cache;
		}
		$database = $this->database_available();
		$schema   = $database ? SPDB_Collections_Schema::verify() : new WP_Error( 'spdb_collections_database_unavailable' );
		$this->health_cache = array(
			'healthy'            => $database && true === $schema,
			'database_ready'     => $database,
			'schema_ready'       => true === $schema,
			'schema_version'     => SPDB_Collections_Schema::VERSION,
			'code'               => true === $schema ? 'ready' : ( is_wp_error( $schema ) ? $schema->get_error_code() : 'schema_unavailable' ),
			'cached_for_request' => true,
		);
		return $this->health_cache;
	}

	/** @return array<string,mixed> */
	public function refresh_health(): array {
		$this->health_cache = null;
		return $this->health_check();
	}

	public function list_collections( array $query ) {
		$ready = $this->ready();
		if ( is_wp_error( $ready ) ) { return $ready; }
		$normalized = $this->normalize_list_query( $query, true );
		if ( is_wp_error( $normalized ) ) { return $normalized; }

		$where = array( 'scope = %s', 'owner_user_id = %d' );
		$args  = array( $normalized['scope'], $normalized['owner_user_id'] );
		if ( '' !== $normalized['record_type'] ) { $where[] = 'record_type = %s'; $args[] = $normalized['record_type']; }
		if ( '' !== $normalized['status'] ) { $where[] = 'status = %s'; $args[] = $normalized['status']; }
		$clause = implode( ' AND ', $where );
		$table  = SPDB_Collections_Schema::collections_table();
		$offset = ( $normalized['page'] - 1 ) * $normalized['per_page'];
		$rows   = $this->wpdb->get_results(
			$this->prepare( "SELECT * FROM {$table} WHERE {$clause} ORDER BY updated_at_gmt DESC, id DESC LIMIT %d OFFSET %d", array_merge( $args, array( $normalized['per_page'], $offset ) ) ),
			$this->array_output()
		);
		$total_raw = $this->wpdb->get_var( $this->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$clause}", $args ) );
		$total     = $this->strict_nonnegative_integer( $total_raw );
		if ( ! is_array( $rows ) || null === $total ) { return $this->database_error( 'spdb_collections_query_failed', 'The collection metadata query failed.' ); }
		$items = array();
		foreach ( $rows as $row ) {
			$record = $this->hydrate_collection( $row );
			if ( is_wp_error( $record ) ) { return $record; }
			$items[] = $record;
		}
		return $this->envelope( $items, $normalized['page'], $normalized['per_page'], $total );
	}

	public function get_collection( string $collection_id ) {
		$ready = $this->ready();
		if ( is_wp_error( $ready ) ) { return $ready; }
		if ( ! $this->valid_id( $collection_id ) ) { return $this->error( 'spdb_collection_id_invalid', 'The collection identifier is invalid.' ); }
		$table = SPDB_Collections_Schema::collections_table();
		$row   = $this->wpdb->get_row( $this->prepare( "SELECT * FROM {$table} WHERE collection_id = %s LIMIT 1", array( $collection_id ) ), $this->array_output() );
		if ( null === $row ) { return $this->error( 'spdb_collection_not_found', 'The collection was not found.', 404 ); }
		return $this->hydrate_collection( $row );
	}

	public function create_collection( array $record ) {
		$ready = $this->ready();
		if ( is_wp_error( $ready ) ) { return $ready; }
		$record = $this->validate_collection_insert( $record );
		if ( is_wp_error( $record ) ) { return $record; }
		$actor  = $record['created_by'];
		$replay = $this->collection_replay( $actor, $record['idempotency_hash'], $record['request_hash'] );
		if ( null !== $replay ) { return $replay; }
		$contributors_json = $this->json( $record['contributors'] );
		$surfaces_json     = $this->json( $record['target_surfaces'] );
		if ( is_wp_error( $contributors_json ) || is_wp_error( $surfaces_json ) ) { return $this->error( 'spdb_collection_json_encode_failed', 'Collection metadata JSON could not be encoded.', 500 ); }
		$now  = gmdate( 'Y-m-d H:i:s' );
		$data = array(
			'collection_id' => $this->new_id( 'collection' ),
			'record_type' => $record['record_type'], 'scope' => $record['scope'], 'title' => $record['title'],
			'objective' => $record['objective'], 'ethical_declaration' => $record['ethical_declaration'],
			'owner_user_id' => $record['owner_user_id'], 'contributors_json' => $contributors_json, 'target_surfaces_json' => $surfaces_json,
			'status' => $record['status'], 'start_at_gmt' => $this->mysql_time( $record['start_at_gmt'] ), 'end_at_gmt' => $this->mysql_time( $record['end_at_gmt'] ),
			'version' => 1, 'idempotency_hash' => $record['idempotency_hash'], 'request_hash' => $record['request_hash'],
			'last_idempotency_hash' => $record['idempotency_hash'], 'last_request_hash' => $record['request_hash'],
			'created_audit_reason' => $record['audit_reason'], 'last_audit_reason' => $record['audit_reason'], 'created_by' => $actor,
			'created_at_gmt' => $now, 'updated_at_gmt' => $now, 'archived_at_gmt' => null,
		);
		$result = $this->wpdb->insert( SPDB_Collections_Schema::collections_table(), $data );
		if ( false === $result ) {
			$replay = $this->collection_replay( $actor, $record['idempotency_hash'], $record['request_hash'] );
			return null !== $replay ? $replay : $this->database_error( 'spdb_collection_create_failed', 'The collection metadata could not be created.' );
		}
		return $this->get_collection( $data['collection_id'] );
	}

	public function update_collection( string $collection_id, int $expected_version, array $changes, array $operation ) { return $this->not_implemented( 'collection update' ); }
	public function archive_collection( string $collection_id, int $expected_version, array $operation ) { return $this->not_implemented( 'collection archive' ); }

	public function list_collection_items( string $collection_id, array $query = array() ) {
		$ready = $this->ready();
		if ( is_wp_error( $ready ) ) { return $ready; }
		if ( ! $this->valid_id( $collection_id ) ) { return $this->error( 'spdb_collection_id_invalid', 'The collection identifier is invalid.' ); }
		if ( array_diff( array_keys( $query ), array( 'page', 'per_page' ) ) ) { return $this->error( 'spdb_collection_items_query_invalid', 'The collection item query is invalid.' ); }
		$page     = $this->bounded_int( $query['page'] ?? 1, 1, 1000 );
		$per_page = $this->bounded_int( $query['per_page'] ?? 20, 1, 50 );
		if ( null === $page || null === $per_page ) { return $this->error( 'spdb_collection_items_query_invalid', 'The collection item query is invalid.' ); }
		$table  = SPDB_Collections_Schema::items_table();
		$offset = ( $page - 1 ) * $per_page;
		$rows   = $this->wpdb->get_results( $this->prepare( "SELECT * FROM {$table} WHERE collection_id = %s AND archived_at_gmt IS NULL ORDER BY position ASC, id ASC LIMIT %d OFFSET %d", array( $collection_id, $per_page, $offset ) ), $this->array_output() );
		$total_raw = $this->wpdb->get_var( $this->prepare( "SELECT COUNT(*) FROM {$table} WHERE collection_id = %s AND archived_at_gmt IS NULL", array( $collection_id ) ) );
		$total     = $this->strict_nonnegative_integer( $total_raw );
		if ( ! is_array( $rows ) || null === $total ) { return $this->database_error( 'spdb_collection_items_query_failed', 'The collection item query failed.' ); }
		$items = array();
		foreach ( $rows as $row ) {
			$item = $this->hydrate_item( $row );
			if ( is_wp_error( $item ) ) { return $item; }
			$items[] = $item;
		}
		return $this->envelope( $items, $page, $per_page, $total );
	}

	public function get_collection_item( string $collection_id, string $item_id ) {
		$ready = $this->ready();
		if ( is_wp_error( $ready ) ) { return $ready; }
		if ( ! $this->valid_id( $collection_id ) || ! $this->valid_id( $item_id ) ) { return $this->error( 'spdb_collection_item_id_invalid', 'The collection item identifier is invalid.' ); }
		$table = SPDB_Collections_Schema::items_table();
		$row   = $this->wpdb->get_row( $this->prepare( "SELECT * FROM {$table} WHERE collection_id = %s AND item_id = %s AND archived_at_gmt IS NULL LIMIT 1", array( $collection_id, $item_id ) ), $this->array_output() );
		return null === $row ? $this->error( 'spdb_collection_item_not_found', 'The collection item was not found.', 404 ) : $this->hydrate_item( $row );
	}
	public function add_collection_item( string $collection_id, int $expected_collection_version, array $record ) { return $this->not_implemented( 'collection item creation' ); }
	public function update_collection_item( string $collection_id, string $item_id, int $expected_collection_version, int $expected_item_version, array $changes, array $operation ) { return $this->not_implemented( 'collection item update' ); }
	public function archive_collection_item( string $collection_id, string $item_id, int $expected_collection_version, int $expected_item_version, array $operation ) { return $this->not_implemented( 'collection item archive' ); }

	public function list_knowledge_links( array $query ) {
		$ready = $this->ready();
		if ( is_wp_error( $ready ) ) { return $ready; }
		$normalized = $this->normalize_list_query( $query, false );
		if ( is_wp_error( $normalized ) ) { return $normalized; }
		$table  = SPDB_Collections_Schema::links_table();
		$offset = ( $normalized['page'] - 1 ) * $normalized['per_page'];
		$where  = array( 'scope = %s', 'owner_user_id = %d' );
		$args   = array( $normalized['scope'], $normalized['owner_user_id'] );
		if ( '' !== $normalized['status'] ) { $where[] = 'status = %s'; $args[] = $normalized['status']; }
		$clause = implode( ' AND ', $where );
		$rows = $this->wpdb->get_results( $this->prepare( "SELECT * FROM {$table} WHERE {$clause} ORDER BY updated_at_gmt DESC, id DESC LIMIT %d OFFSET %d", array_merge( $args, array( $normalized['per_page'], $offset ) ) ), $this->array_output() );
		$total_raw = $this->wpdb->get_var( $this->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$clause}", $args ) );
		$total     = $this->strict_nonnegative_integer( $total_raw );
		if ( ! is_array( $rows ) || null === $total ) { return $this->database_error( 'spdb_knowledge_links_query_failed', 'The knowledge-link query failed.' ); }
		$items = array();
		foreach ( $rows as $row ) {
			$item = $this->hydrate_link( $row );
			if ( is_wp_error( $item ) ) { return $item; }
			$items[] = $item;
		}
		return $this->envelope( $items, $normalized['page'], $normalized['per_page'], $total );
	}

	public function get_knowledge_link( string $link_id ) {
		$ready = $this->ready();
		if ( is_wp_error( $ready ) ) { return $ready; }
		if ( ! $this->valid_id( $link_id ) ) { return $this->error( 'spdb_knowledge_link_id_invalid', 'The knowledge-link identifier is invalid.' ); }
		$table = SPDB_Collections_Schema::links_table();
		$row   = $this->wpdb->get_row( $this->prepare( "SELECT * FROM {$table} WHERE link_id = %s LIMIT 1", array( $link_id ) ), $this->array_output() );
		return null === $row ? $this->error( 'spdb_knowledge_link_not_found', 'The knowledge link was not found.', 404 ) : $this->hydrate_link( $row );
	}

	public function create_knowledge_link( array $record ) {
		$ready = $this->ready();
		if ( is_wp_error( $ready ) ) { return $ready; }
		$record = $this->validate_link_insert( $record );
		if ( is_wp_error( $record ) ) { return $record; }
		$actor  = $record['created_by'];
		$replay = $this->link_replay( $actor, $record['idempotency_hash'], $record['request_hash'] );
		if ( null !== $replay ) { return $replay; }
		$conflict = $this->relation_conflict( $record['owner_user_id'], $record['scope'], $record['relation_hash'] );
		if ( is_wp_error( $conflict ) ) { return $conflict; }
		$now  = gmdate( 'Y-m-d H:i:s' );
		$data = array(
			'link_id' => $this->new_id( 'knowledge' ), 'scope' => $record['scope'], 'owner_user_id' => $record['owner_user_id'],
			'source_provider_key' => $record['source_provider_key'], 'source_object_type' => $record['source_object_type'], 'source_object_id' => $record['source_object_id'], 'source_native_version' => $record['source_native_version'],
			'target_provider_key' => $record['target_provider_key'], 'target_object_type' => $record['target_object_type'], 'target_object_id' => $record['target_object_id'], 'target_native_version' => $record['target_native_version'],
			'relation_type' => $record['relation_type'], 'relation_hash' => $record['relation_hash'], 'status' => $record['status'], 'version' => 1,
			'idempotency_hash' => $record['idempotency_hash'], 'request_hash' => $record['request_hash'], 'last_idempotency_hash' => $record['idempotency_hash'], 'last_request_hash' => $record['request_hash'],
			'created_audit_reason' => $record['audit_reason'], 'last_audit_reason' => $record['audit_reason'], 'created_by' => $actor, 'created_at_gmt' => $now, 'updated_at_gmt' => $now, 'archived_at_gmt' => null,
		);
		$result = $this->wpdb->insert( SPDB_Collections_Schema::links_table(), $data );
		if ( false === $result ) {
			$replay = $this->link_replay( $actor, $record['idempotency_hash'], $record['request_hash'] );
			if ( null !== $replay ) { return $replay; }
			$conflict = $this->relation_conflict( $record['owner_user_id'], $record['scope'], $record['relation_hash'] );
			return is_wp_error( $conflict ) ? $conflict : $this->database_error( 'spdb_knowledge_link_create_failed', 'The knowledge-link metadata could not be created.' );
		}
		return $this->get_knowledge_link( $data['link_id'] );
	}
	public function update_knowledge_link( string $link_id, int $expected_version, array $changes, array $operation ) { return $this->not_implemented( 'knowledge-link update' ); }
	public function archive_knowledge_link( string $link_id, int $expected_version, array $operation ) { return $this->not_implemented( 'knowledge-link archive' ); }

	private function collection_replay( int $actor, string $key_hash, string $request_hash ) {
		$table = SPDB_Collections_Schema::collections_table();
		$row   = $this->wpdb->get_row( $this->prepare( "SELECT * FROM {$table} WHERE created_by = %d AND idempotency_hash = %s LIMIT 1", array( $actor, $key_hash ) ), $this->array_output() );
		if ( null === $row ) { return null; }
		if ( ! is_array( $row ) || ! hash_equals( (string) ( $row['request_hash'] ?? '' ), $request_hash ) ) { return $this->error( 'spdb_idempotency_payload_conflict', 'The idempotency key was already used with a different request.', 409 ); }
		$record = $this->hydrate_collection( $row );
		if ( is_array( $record ) ) { $record['replayed'] = true; }
		return $record;
	}

	private function link_replay( int $actor, string $key_hash, string $request_hash ) {
		$table = SPDB_Collections_Schema::links_table();
		$row   = $this->wpdb->get_row( $this->prepare( "SELECT * FROM {$table} WHERE created_by = %d AND idempotency_hash = %s LIMIT 1", array( $actor, $key_hash ) ), $this->array_output() );
		if ( null === $row ) { return null; }
		if ( ! is_array( $row ) || ! hash_equals( (string) ( $row['request_hash'] ?? '' ), $request_hash ) ) { return $this->error( 'spdb_idempotency_payload_conflict', 'The idempotency key was already used with a different request.', 409 ); }
		$record = $this->hydrate_link( $row );
		if ( is_array( $record ) ) { $record['replayed'] = true; }
		return $record;
	}

	/** @return true|WP_Error */
	private function relation_conflict( int $owner, string $scope, string $relation_hash ) {
		$table = SPDB_Collections_Schema::links_table();
		$found = $this->wpdb->get_var( $this->prepare( "SELECT link_id FROM {$table} WHERE owner_user_id = %d AND scope = %s AND relation_hash = %s LIMIT 1", array( $owner, $scope, $relation_hash ) ) );
		return is_string( $found ) && '' !== $found ? $this->error( 'spdb_knowledge_relation_conflict', 'The canonical knowledge relationship already exists.', 409 ) : true;
	}

	private function hydrate_collection( $row ) {
		if ( ! is_array( $row ) ) { return $this->database_error( 'spdb_collection_record_invalid', 'A stored collection record is invalid.' ); }
		$contributors = $this->decode_list( $row['contributors_json'] ?? null );
		$surfaces     = $this->decode_list( $row['target_surfaces_json'] ?? null );
		if ( is_wp_error( $contributors ) || is_wp_error( $surfaces ) ) { return $this->database_error( 'spdb_collection_record_invalid', 'A stored collection record is invalid.' ); }
		return array(
			'collection_id' => $row['collection_id'] ?? null, 'record_type' => $row['record_type'] ?? null, 'scope' => $row['scope'] ?? null, 'title' => $row['title'] ?? null,
			'objective' => $row['objective'] ?? null, 'ethical_declaration' => $row['ethical_declaration'] ?? null, 'owner_user_id' => $row['owner_user_id'] ?? null,
			'contributors' => $contributors, 'target_surfaces' => $surfaces, 'status' => $row['status'] ?? null, 'start_at_gmt' => $this->rfc3339( $row['start_at_gmt'] ?? null ), 'end_at_gmt' => $this->rfc3339( $row['end_at_gmt'] ?? null ),
			'version' => $row['version'] ?? null, 'created_by' => $row['created_by'] ?? null, 'created_at_gmt' => $this->rfc3339( $row['created_at_gmt'] ?? null ), 'updated_at_gmt' => $this->rfc3339( $row['updated_at_gmt'] ?? null ), 'archived_at_gmt' => $this->rfc3339( $row['archived_at_gmt'] ?? null ),
		);
	}

	private function hydrate_item( $row ) {
		if ( ! is_array( $row ) ) { return $this->database_error( 'spdb_collection_item_record_invalid', 'A stored collection item is invalid.' ); }
		return array(
			'item_id' => $row['item_id'] ?? null, 'collection_id' => $row['collection_id'] ?? null, 'provider_key' => $row['provider_key'] ?? null,
			'object_type' => $row['object_type'] ?? null, 'object_id' => $row['object_id'] ?? null, 'relation_type' => $row['relation_type'] ?? null,
			'native_version' => $row['native_version'] ?? null, 'position' => $row['position'] ?? null, 'version' => $row['version'] ?? null, 'added_by' => $row['added_by'] ?? null,
			'created_at_gmt' => $this->rfc3339( $row['created_at_gmt'] ?? null ), 'updated_at_gmt' => $this->rfc3339( $row['updated_at_gmt'] ?? null ), 'archived_at_gmt' => $this->rfc3339( $row['archived_at_gmt'] ?? null ),
		);
	}

	private function hydrate_link( $row ) {
		if ( ! is_array( $row ) ) { return $this->database_error( 'spdb_knowledge_link_record_invalid', 'A stored knowledge link is invalid.' ); }
		return array(
			'link_id' => $row['link_id'] ?? null, 'scope' => $row['scope'] ?? null, 'owner_user_id' => $row['owner_user_id'] ?? null,
			'source_provider_key' => $row['source_provider_key'] ?? null, 'source_object_type' => $row['source_object_type'] ?? null, 'source_object_id' => $row['source_object_id'] ?? null, 'source_native_version' => $row['source_native_version'] ?? null,
			'target_provider_key' => $row['target_provider_key'] ?? null, 'target_object_type' => $row['target_object_type'] ?? null, 'target_object_id' => $row['target_object_id'] ?? null, 'target_native_version' => $row['target_native_version'] ?? null,
			'relation_type' => $row['relation_type'] ?? null, 'status' => $row['status'] ?? null, 'version' => $row['version'] ?? null, 'created_by' => $row['created_by'] ?? null,
			'created_at_gmt' => $this->rfc3339( $row['created_at_gmt'] ?? null ), 'updated_at_gmt' => $this->rfc3339( $row['updated_at_gmt'] ?? null ), 'archived_at_gmt' => $this->rfc3339( $row['archived_at_gmt'] ?? null ),
		);
	}

	private function normalize_list_query( array $query, bool $collections ) {
		$allowed = $collections ? array( 'scope', 'owner_user_id', 'record_type', 'status', 'page', 'per_page' ) : array( 'scope', 'owner_user_id', 'status', 'page', 'per_page' );
		if ( array_diff( array_keys( $query ), $allowed ) ) { return $this->error( 'spdb_repository_query_invalid', 'The repository query contains an unsupported field.' ); }
		$scope = is_string( $query['scope'] ?? null ) ? $query['scope'] : '';
		$owner = $this->strict_positive_integer( $query['owner_user_id'] ?? null );
		$page = $this->bounded_int( $query['page'] ?? 1, 1, 1000 );
		$per_page = $this->bounded_int( $query['per_page'] ?? 20, 1, 50 );
		$record_type = $collections && is_string( $query['record_type'] ?? '' ) ? $query['record_type'] : '';
		$status = is_string( $query['status'] ?? '' ) ? $query['status'] : '';
		if ( ! in_array( $scope, SPDB_Collections_Policy::scopes(), true ) || null === $owner || null === $page || null === $per_page ) { return $this->error( 'spdb_repository_query_invalid', 'The repository query is invalid.' ); }
		if ( $collections ) {
			if ( '' !== $record_type && ! in_array( $record_type, SPDB_Collections_Policy::record_types(), true ) ) { return $this->error( 'spdb_repository_query_invalid', 'The collection record type is invalid.' ); }
			$statuses = 'collection' === $record_type ? SPDB_Collections_Policy::collection_statuses() : ( 'campaign' === $record_type ? SPDB_Collections_Policy::campaign_statuses() : array_unique( array_merge( SPDB_Collections_Policy::collection_statuses(), SPDB_Collections_Policy::campaign_statuses() ) ) );
			if ( '' !== $status && ! in_array( $status, $statuses, true ) ) { return $this->error( 'spdb_repository_query_invalid', 'The collection status is invalid for the selected record type.' ); }
		} elseif ( '' !== $status && ! in_array( $status, array( 'active', 'archived' ), true ) ) {
			return $this->error( 'spdb_repository_query_invalid', 'The knowledge-link status is invalid.' );
		}
		return array( 'scope' => $scope, 'owner_user_id' => $owner, 'record_type' => $record_type, 'status' => $status, 'page' => $page, 'per_page' => $per_page );
	}

	private function validate_collection_insert( array $record ) {
		$allowed = array( 'record_type', 'scope', 'title', 'objective', 'ethical_declaration', 'contributors', 'target_surfaces', 'status', 'start_at_gmt', 'end_at_gmt', 'audit_reason', 'owner_user_id', 'created_by', 'idempotency_hash', 'request_hash' );
		if ( array_diff( array_keys( $record ), $allowed ) || array_diff( $allowed, array_keys( $record ) ) ) { return $this->error( 'spdb_collection_record_invalid', 'The collection persistence record is invalid.' ); }
		$type = is_string( $record['record_type'] ) ? $record['record_type'] : '';
		$scope = is_string( $record['scope'] ) ? $record['scope'] : '';
		$status = is_string( $record['status'] ) ? $record['status'] : '';
		$owner = $this->strict_positive_integer( $record['owner_user_id'] );
		$actor = $this->strict_positive_integer( $record['created_by'] );
		$title = $this->plain_text( $record['title'], 200, false );
		$objective = $this->plain_text( $record['objective'], 1000, true );
		$ethics = $this->plain_text( $record['ethical_declaration'], 1000, true );
		$reason = $this->plain_text( $record['audit_reason'], 500, false );
		$contributors = $this->positive_list( $record['contributors'], 25 );
		$surfaces = $this->enum_list( $record['target_surfaces'], SPDB_Collections_Policy::target_surfaces(), 20 );
		$start = $this->timestamp( $record['start_at_gmt'], true );
		$end = $this->timestamp( $record['end_at_gmt'], true );
		$statuses = 'campaign' === $type ? SPDB_Collections_Policy::campaign_statuses() : SPDB_Collections_Policy::collection_statuses();
		if ( ! in_array( $type, SPDB_Collections_Policy::record_types(), true ) || ! in_array( $scope, SPDB_Collections_Policy::scopes(), true ) || ! in_array( $status, $statuses, true ) || null === $owner || null === $actor || $owner !== $actor || is_wp_error( $title ) || is_wp_error( $objective ) || is_wp_error( $ethics ) || is_wp_error( $reason ) || is_wp_error( $contributors ) || is_wp_error( $surfaces ) || is_wp_error( $start ) || is_wp_error( $end ) || ! $this->hash64( $record['idempotency_hash'] ) || ! $this->hash64( $record['request_hash'] ) ) { return $this->error( 'spdb_collection_record_invalid', 'The collection persistence record is invalid.' ); }
		if ( $this->text_length( $reason ) < 10 || ( '' !== $start && '' !== $end && $start > $end ) ) { return $this->error( 'spdb_collection_record_invalid', 'The collection persistence record is invalid.' ); }
		if ( 'collection' === $type && ( '' !== $ethics || array() !== $surfaces || '' !== $start || '' !== $end || ( 'own' === $scope && array() !== $contributors ) ) ) { return $this->error( 'spdb_collection_record_invalid', 'The ordinary collection persistence contract was violated.' ); }
		if ( 'campaign' === $type && ( 'institution' !== $scope || '' === $objective || '' === $ethics || array() === $surfaces || '' === $start || '' === $end ) ) { return $this->error( 'spdb_collection_record_invalid', 'The campaign persistence contract was violated.' ); }
		return array_merge( $record, array( 'owner_user_id' => $owner, 'created_by' => $actor, 'title' => $title, 'objective' => $objective, 'ethical_declaration' => $ethics, 'audit_reason' => $reason, 'contributors' => $contributors, 'target_surfaces' => $surfaces, 'start_at_gmt' => $start, 'end_at_gmt' => $end ) );
	}

	private function validate_link_insert( array $record ) {
		$allowed = array( 'scope', 'owner_user_id', 'source_provider_key', 'source_object_type', 'source_object_id', 'source_native_version', 'target_provider_key', 'target_object_type', 'target_object_id', 'target_native_version', 'relation_type', 'relation_hash', 'status', 'created_by', 'idempotency_hash', 'request_hash', 'audit_reason' );
		if ( array_diff( array_keys( $record ), $allowed ) || array_diff( $allowed, array_keys( $record ) ) ) { return $this->error( 'spdb_knowledge_record_invalid', 'The knowledge-link persistence record is invalid.' ); }
		$scope = is_string( $record['scope'] ) ? $record['scope'] : '';
		$owner = $this->strict_positive_integer( $record['owner_user_id'] );
		$actor = $this->strict_positive_integer( $record['created_by'] );
		$reason = $this->plain_text( $record['audit_reason'], 500, false );
		$source_version = $this->native_version( $record['source_native_version'] );
		$target_version = $this->native_version( $record['target_native_version'] );
		$source_provider = is_string( $record['source_provider_key'] ) ? $record['source_provider_key'] : '';
		$source_type = is_string( $record['source_object_type'] ) ? $record['source_object_type'] : '';
		$source_id = is_string( $record['source_object_id'] ) ? $record['source_object_id'] : '';
		$target_provider = is_string( $record['target_provider_key'] ) ? $record['target_provider_key'] : '';
		$target_type = is_string( $record['target_object_type'] ) ? $record['target_object_type'] : '';
		$target_id = is_string( $record['target_object_id'] ) ? $record['target_object_id'] : '';
		$relation = is_string( $record['relation_type'] ) ? $record['relation_type'] : '';
		if ( ! in_array( $scope, SPDB_Collections_Policy::scopes(), true ) || null === $owner || null === $actor || $owner !== $actor || 'active' !== $record['status'] || is_wp_error( $reason ) || $this->text_length( $reason ) < 10 || is_wp_error( $source_version ) || is_wp_error( $target_version ) || ! SPDB_Adapter_Registry::is_canonical_key( $source_provider ) || ! SPDB_Adapter_Registry::is_canonical_key( $source_type ) || ! SPDB_Projection_Validator::valid_object_id( $source_id ) || ! SPDB_Adapter_Registry::is_canonical_key( $target_provider ) || ! SPDB_Adapter_Registry::is_canonical_key( $target_type ) || ! SPDB_Projection_Validator::valid_object_id( $target_id ) || ! in_array( $relation, SPDB_Collections_Policy::knowledge_relations(), true ) || ! $this->hash64( $record['relation_hash'] ) || ! $this->hash64( $record['idempotency_hash'] ) || ! $this->hash64( $record['request_hash'] ) ) { return $this->error( 'spdb_knowledge_record_invalid', 'The knowledge-link persistence record is invalid.' ); }
		if ( $source_provider === $target_provider && $source_type === $target_type && $source_id === $target_id ) { return $this->error( 'spdb_knowledge_record_invalid', 'A knowledge relationship cannot point an object to itself.' ); }
		return array_merge( $record, array( 'owner_user_id' => $owner, 'created_by' => $actor, 'audit_reason' => $reason, 'source_native_version' => $source_version, 'target_native_version' => $target_version ) );
	}

	/** @param array<int,mixed> $items @return array<string,mixed> */
	private function envelope( array $items, int $page, int $per_page, int $total ): array {
		$offset = ( $page - 1 ) * $per_page;
		return array( 'items' => $items, 'page' => $page, 'per_page' => $per_page, 'total' => $total, 'has_more' => $offset + count( $items ) < $total );
	}
	private function ready() { $health = $this->health_check(); return true === ( $health['healthy'] ?? false ) ? true : $this->error( 'spdb_collections_repository_not_ready', 'The collection metadata repository is not ready.', 503 ); }
	private function database_available(): bool { return is_object( $this->wpdb ) && method_exists( $this->wpdb, 'prepare' ) && method_exists( $this->wpdb, 'get_var' ) && method_exists( $this->wpdb, 'get_row' ) && method_exists( $this->wpdb, 'get_results' ) && method_exists( $this->wpdb, 'insert' ); }
	private function prepare( string $sql, array $args ): string { return (string) call_user_func_array( array( $this->wpdb, 'prepare' ), array_merge( array( $sql ), $args ) ); }
	private function new_id( string $prefix ): string { return $prefix . '_' . strtolower( str_replace( '-', '', wp_generate_uuid4() ) ); }
	private function valid_id( string $value ): bool { return 1 === preg_match( '/^[a-z0-9][a-z0-9_-]{15,63}$/', $value ); }
	private function hash64( $value ): bool { return is_string( $value ) && 1 === preg_match( '/^[a-f0-9]{64}$/', $value ); }
	private function json( $value ) { $json = wp_json_encode( $value ); return is_string( $json ) ? $json : $this->error( 'spdb_repository_json_encode_failed', 'Metadata JSON could not be encoded.', 500 ); }
	private function decode_list( $value ) { if ( ! is_string( $value ) ) { return $this->error( 'spdb_repository_json_invalid', 'Stored metadata JSON is invalid.', 500 ); } $decoded = json_decode( $value, true ); return is_array( $decoded ) && $this->is_list( $decoded ) ? $decoded : $this->error( 'spdb_repository_json_invalid', 'Stored metadata JSON is invalid.', 500 ); }
	private function mysql_time( string $value ) { return '' === $value ? null : str_replace( array( 'T', 'Z' ), array( ' ', '' ), $value ); }
	private function rfc3339( $value ): string {
		if ( null === $value || '' === $value || '0000-00-00 00:00:00' === $value ) { return ''; }
		if ( ! is_string( $value ) ) { return '__invalid_timestamp__'; }
		return 1 === preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value ) ? str_replace( ' ', 'T', $value ) . 'Z' : $value;
	}
	private function bounded_int( $raw, int $minimum, int $maximum ): ?int { $value = $this->strict_positive_integer( $raw ); return null !== $value && $value >= $minimum && $value <= $maximum ? $value : null; }
	private function strict_positive_integer( $raw ): ?int {
		if ( is_int( $raw ) ) { return $raw > 0 ? $raw : null; }
		if ( is_string( $raw ) && 1 === preg_match( '/^[1-9]\d*$/', $raw ) ) {
			$value = (int) $raw;
			return $value > 0 && (string) $value === $raw ? $value : null;
		}
		return null;
	}
	private function strict_nonnegative_integer( $raw ): ?int {
		if ( is_int( $raw ) ) { return $raw >= 0 ? $raw : null; }
		if ( is_string( $raw ) && 1 === preg_match( '/^(?:0|[1-9]\d*)$/', $raw ) ) {
			$value = (int) $raw;
			return (string) $value === $raw ? $value : null;
		}
		return null;
	}
	private function plain_text( $raw, int $maximum, bool $allow_empty ) { if ( ! is_string( $raw ) ) { return $this->error( 'spdb_repository_text_invalid', 'Metadata text has an invalid shape.' ); } $value = trim( $raw ); if ( ( ! $allow_empty && '' === $value ) || $this->text_length( $value ) > $maximum || wp_strip_all_tags( $value ) !== $value || preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value ) || preg_match( '/(?:https?:\/\/|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}|\b(?:\+?92|0)?3\d{9}\b|\b\d{5}-\d{7}-\d\b)/iu', $value ) ) { return $this->error( 'spdb_repository_text_invalid', 'Metadata text is invalid or sensitive.' ); } return $value; }
	private function positive_list( $raw, int $maximum ) { if ( ! is_array( $raw ) || ! $this->is_list( $raw ) || count( $raw ) > $maximum ) { return $this->error( 'spdb_repository_list_invalid', 'A metadata integer list is invalid.' ); } $result = array(); foreach ( $raw as $item ) { $value = $this->strict_positive_integer( $item ); if ( null === $value || in_array( $value, $result, true ) ) { return $this->error( 'spdb_repository_list_invalid', 'A metadata integer list is invalid.' ); } $result[] = $value; } return $result; }
	private function enum_list( $raw, array $allowed, int $maximum ) { if ( ! is_array( $raw ) || ! $this->is_list( $raw ) || count( $raw ) > $maximum ) { return $this->error( 'spdb_repository_list_invalid', 'A metadata enumeration list is invalid.' ); } $result = array(); foreach ( $raw as $item ) { if ( ! is_string( $item ) || ! in_array( $item, $allowed, true ) || in_array( $item, $result, true ) ) { return $this->error( 'spdb_repository_list_invalid', 'A metadata enumeration list is invalid.' ); } $result[] = $item; } return $result; }
	private function timestamp( $raw, bool $allow_empty ) { if ( ! is_string( $raw ) ) { return $this->error( 'spdb_repository_timestamp_invalid', 'A metadata timestamp has an invalid shape.' ); } $value = trim( $raw ); if ( '' === $value ) { return $allow_empty ? '' : $this->error( 'spdb_repository_timestamp_invalid', 'A metadata timestamp is required.' ); } if ( 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value ) ) { return $this->error( 'spdb_repository_timestamp_invalid', 'A metadata timestamp is invalid.' ); } try { $date = new DateTimeImmutable( $value ); } catch ( Throwable $throwable ) { return $this->error( 'spdb_repository_timestamp_invalid', 'A metadata timestamp is invalid.' ); } return $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d\TH:i:s\Z' ) === $value ? $value : $this->error( 'spdb_repository_timestamp_invalid', 'A metadata timestamp is not canonical UTC.' ); }
	private function native_version( $raw ) { if ( ! is_string( $raw ) ) { return $this->error( 'spdb_repository_native_version_invalid', 'A native version is invalid.' ); } $value = trim( $raw ); return '' !== $value && $this->text_length( $value ) <= 191 && ! preg_match( '/[\x00-\x1F\x7F]/u', $value ) ? $value : $this->error( 'spdb_repository_native_version_invalid', 'A native version is invalid.' ); }
	private function is_list( array $value ): bool { $expected = 0; foreach ( $value as $key => $unused ) { if ( $key !== $expected ) { return false; } ++$expected; } return true; }
	private function text_length( string $value ): int { if ( 1 !== preg_match( '//u', $value ) ) { return PHP_INT_MAX; } if ( function_exists( 'mb_strlen' ) ) { return mb_strlen( $value, 'UTF-8' ); } $count = preg_match_all( '/./us', $value, $matches ); return false === $count ? PHP_INT_MAX : $count; }
	private function array_output() { return defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A'; }
	private function not_implemented( string $operation ): WP_Error { return $this->error( 'spdb_repository_operation_not_implemented', sprintf( 'The %s operation remains disabled pending its separate review gate.', $operation ), 503 ); }
	private function database_error( string $code, string $message ): WP_Error { return $this->error( $code, $message, 500 ); }
	private function error( string $code, string $message, int $status = 422 ): WP_Error { return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => $status ) ); }
}
