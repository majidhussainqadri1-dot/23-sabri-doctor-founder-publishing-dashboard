<?php
/** Runtime authority boundary for File 23 collections and knowledge metadata. */
defined( 'ABSPATH' ) || exit;

final class SPDB_Collections_Service {
	private ?SPDB_Collections_Repository $repository;
	private ?SPDB_Native_Reference_Resolver $resolver;

	public function __construct( ?SPDB_Collections_Repository $repository = null, ?SPDB_Native_Reference_Resolver $resolver = null ) {
		$this->repository = $repository;
		$this->resolver   = $resolver;
	}

	/** @return array<string,mixed> */
	public function health(): array {
		$repository_health = array( 'healthy' => false, 'schema_ready' => false, 'code' => 'repository_unavailable' );
		if ( null !== $this->repository ) {
			try {
				$value = $this->repository->health_check();
				if ( is_array( $value ) ) { $repository_health = $value; }
			} catch ( Throwable $throwable ) {
				$repository_health = array( 'healthy' => false, 'schema_ready' => false, 'code' => 'repository_exception' );
			}
		}
		$repository_ready = true === ( $repository_health['healthy'] ?? false ) && true === ( $repository_health['schema_ready'] ?? false );
		$configured       = $this->writes_configured();
		return array(
			'repository_available' => null !== $this->repository,
			'resolver_available'   => null !== $this->resolver,
			'read_ready'           => $repository_ready,
			'write_configured'     => $configured,
			'collection_write_ready' => $configured && $repository_ready,
			'knowledge_write_ready'  => $configured && $repository_ready && null !== $this->resolver,
			'write_enabled'          => $configured && $repository_ready && null !== $this->resolver,
			'repository_health'      => $repository_health,
		);
	}

	/** @return array<string,mixed>|WP_Error */
	public function list_collections( array $input ) {
		$permission = $this->read_permission(); if ( is_wp_error( $permission ) ) { return $permission; }
		$repository = $this->read_repository(); if ( is_wp_error( $repository ) ) { return $repository; }
		$query = $this->normalize_collection_query( $input ); if ( is_wp_error( $query ) ) { return $query; }
		try { $result = $this->repository->list_collections( $query ); }
		catch ( Throwable $throwable ) { return $this->unavailable( 'spdb_collections_repository_failed', 'The collection metadata repository could not complete the query.' ); }
		if ( is_wp_error( $result ) ) { return $result; }
		return $this->validate_collection_envelope( $result, $query );
	}

	/** @return array<string,mixed>|WP_Error */
	public function get_collection( string $collection_id ) {
		$permission = $this->read_permission(); if ( is_wp_error( $permission ) ) { return $permission; }
		$repository = $this->read_repository(); if ( is_wp_error( $repository ) ) { return $repository; }
		if ( ! $this->valid_metadata_id( $collection_id ) ) { return $this->error( 'spdb_collection_id_invalid', 'The collection identifier is invalid.' ); }
		try { $record = $this->repository->get_collection( $collection_id ); }
		catch ( Throwable $throwable ) { return $this->unavailable( 'spdb_collections_repository_failed', 'The collection metadata repository could not complete the query.' ); }
		if ( is_wp_error( $record ) ) { return $record; }
		$record = $this->validate_collection_record( $record );
		return is_wp_error( $record ) || ! $this->record_is_visible( $record ) ? $this->error( 'spdb_collection_not_found', 'The collection was not found.', 404 ) : $record;
	}

	/** @return array<string,mixed>|WP_Error */
	public function create_collection( array $input ) {
		$record = $this->prepare_collection_create( $input ); if ( is_wp_error( $record ) ) { return $record; }
		try { $created = $this->repository->create_collection( $record ); }
		catch ( Throwable $throwable ) { return $this->unavailable( 'spdb_collection_create_failed', 'The collection metadata repository could not complete the create operation.' ); }
		if ( is_wp_error( $created ) ) { return $created; }
		$created = $this->validate_collection_record( $created );
		return is_wp_error( $created ) || ! $this->record_is_visible( $created ) ? $this->unavailable( 'spdb_collection_create_response_invalid', 'The collection repository returned an invalid create response.' ) : $created;
	}

	/** @return array<string,mixed>|WP_Error */
	public function prepare_collection_create( array $input ) {
		$gate = $this->write_gate( false ); if ( is_wp_error( $gate ) ) { return $gate; }
		$record = SPDB_Collections_Policy::validate_collection( $input ); if ( is_wp_error( $record ) ) { return $record; }
		$contributor_state = $this->validate_contributors( $record['contributors'] ); if ( is_wp_error( $contributor_state ) ) { return $contributor_state; }
		$actor = get_current_user_id();
		$record['owner_user_id'] = $actor;
		$record['created_by']    = $actor;
		$record['request_hash']  = $this->request_hash( $record, array( 'idempotency_key' ) );
		$record['idempotency_hash'] = hash( 'sha256', $actor . '|collection_create|' . $record['idempotency_key'] );
		unset( $record['idempotency_key'] );
		return $record;
	}

	/** @return array<string,mixed>|WP_Error */
	public function list_knowledge_links( array $input ) {
		$permission = $this->read_permission(); if ( is_wp_error( $permission ) ) { return $permission; }
		$repository = $this->read_repository(); if ( is_wp_error( $repository ) ) { return $repository; }
		$query = $this->normalize_knowledge_query( $input ); if ( is_wp_error( $query ) ) { return $query; }
		try { $result = $this->repository->list_knowledge_links( $query ); }
		catch ( Throwable $throwable ) { return $this->unavailable( 'spdb_knowledge_repository_failed', 'The knowledge-link repository could not complete the query.' ); }
		if ( is_wp_error( $result ) ) { return $result; }
		return $this->validate_knowledge_envelope( $result, $query );
	}

	/** @return array<string,mixed>|WP_Error */
	public function get_knowledge_link( string $link_id ) {
		$permission = $this->read_permission(); if ( is_wp_error( $permission ) ) { return $permission; }
		$repository = $this->read_repository(); if ( is_wp_error( $repository ) ) { return $repository; }
		if ( ! $this->valid_metadata_id( $link_id ) ) { return $this->error( 'spdb_knowledge_link_id_invalid', 'The knowledge-link identifier is invalid.' ); }
		try { $record = $this->repository->get_knowledge_link( $link_id ); }
		catch ( Throwable $throwable ) { return $this->unavailable( 'spdb_knowledge_repository_failed', 'The knowledge-link repository could not complete the query.' ); }
		if ( is_wp_error( $record ) ) { return $record; }
		$record = $this->validate_knowledge_record( $record );
		return is_wp_error( $record ) || ! $this->record_is_visible( $record ) ? $this->error( 'spdb_knowledge_link_not_found', 'The knowledge link was not found.', 404 ) : $record;
	}

	/** @return array<string,mixed>|WP_Error */
	public function create_knowledge_link( array $input ) {
		$record = $this->prepare_knowledge_link_create( $input ); if ( is_wp_error( $record ) ) { return $record; }
		try { $created = $this->repository->create_knowledge_link( $record ); }
		catch ( Throwable $throwable ) { return $this->unavailable( 'spdb_knowledge_link_create_failed', 'The knowledge-link repository could not complete the create operation.' ); }
		if ( is_wp_error( $created ) ) { return $created; }
		$created = $this->validate_knowledge_record( $created );
		return is_wp_error( $created ) || ! $this->record_is_visible( $created ) ? $this->unavailable( 'spdb_knowledge_create_response_invalid', 'The knowledge-link repository returned an invalid create response.' ) : $created;
	}

	/** @return array<string,mixed>|WP_Error */
	public function prepare_knowledge_link_create( array $input ) {
		$gate = $this->write_gate( true ); if ( is_wp_error( $gate ) ) { return $gate; }
		$record = SPDB_Collections_Policy::validate_knowledge_link( $input ); if ( is_wp_error( $record ) ) { return $record; }
		$request_hash = $this->request_hash( $record, array( 'idempotency_key' ) );
		$source = $this->resolve_reference( $record['source_provider_key'], $record['source_object_type'], $record['source_object_id'], $record['scope'] ); if ( is_wp_error( $source ) ) { return $source; }
		$target = $this->resolve_reference( $record['target_provider_key'], $record['target_object_type'], $record['target_object_id'], $record['scope'] ); if ( is_wp_error( $target ) ) { return $target; }
		$actor = get_current_user_id();
		$record['owner_user_id'] = $actor;
		$record['created_by']    = $actor;
		$record['status']        = 'active';
		$record['relation_hash'] = hash( 'sha256', implode( '|', array( $record['source_provider_key'], $record['source_object_type'], $record['source_object_id'], $record['target_provider_key'], $record['target_object_type'], $record['target_object_id'], $record['relation_type'] ) ) );
		$record['request_hash']  = $request_hash;
		$record['idempotency_hash'] = hash( 'sha256', $actor . '|knowledge_link_create|' . $record['idempotency_key'] );
		$record['source_native_version'] = $source['native_version'];
		$record['target_native_version'] = $target['native_version'];
		unset( $record['idempotency_key'] );
		return $record;
	}

	/** @return array<string,mixed>|WP_Error */
	public function resolve_reference( string $provider_key, string $object_type, string $object_id, string $scope = 'own' ) {
		$authority = $this->reference_authority( $scope ); if ( is_wp_error( $authority ) ) { return $authority; }
		if ( ! SPDB_Adapter_Registry::is_canonical_key( $provider_key ) || ! SPDB_Adapter_Registry::is_canonical_key( $object_type ) || ! SPDB_Projection_Validator::valid_object_id( $object_id ) ) { return $this->error( 'spdb_native_reference_invalid', 'The canonical native reference is invalid.' ); }
		if ( null === $this->resolver ) { return $this->unavailable( 'spdb_native_reference_resolver_unavailable', 'The native-reference resolver is unavailable.' ); }
		try { $result = $this->resolver->resolve_reference( $provider_key, $object_type, $object_id, $this->context( $scope ) ); }
		catch ( Throwable $throwable ) { return $this->unavailable( 'spdb_native_reference_resolver_failed', 'The native provider could not resolve the reference.' ); }
		if ( is_wp_error( $result ) ) { return $result; }
		if ( ! is_array( $result ) ) { return $this->unavailable( 'spdb_native_reference_response_invalid', 'The native provider returned an invalid reference response.' ); }
		$exact = (string) ( $result['provider_key'] ?? '' ) === $provider_key && (string) ( $result['object_type'] ?? '' ) === $object_type && (string) ( $result['object_id'] ?? '' ) === $object_id;
		if ( ! $exact || true !== ( $result['exists'] ?? null ) || true !== ( $result['visible'] ?? null ) || true !== ( $result['reference_allowed'] ?? null ) ) { return $this->error( 'spdb_native_reference_unavailable', 'The native object is missing, hidden, or not currently authorized for this reference.', 404 ); }
		$native_version = $this->native_version( $result['native_version'] ?? null ); if ( is_wp_error( $native_version ) ) { return $native_version; }
		$owner = $this->nonnegative_integer( $result['owner_user_id'] ?? null ); if ( null === $owner ) { return $this->error( 'spdb_native_reference_owner_invalid', 'The native object owner identifier is invalid.' ); }
		$destination = '';
		if ( array_key_exists( 'destination', $result ) && '' !== $result['destination'] ) { $destination = SPDB_Safe_Destination::normalize( $result['destination'] ); if ( is_wp_error( $destination ) ) { return $destination; } }
		return array( 'provider_key' => $provider_key, 'object_type' => $object_type, 'object_id' => $object_id, 'owner_user_id' => $owner, 'native_version' => $native_version, 'current_destination' => $destination );
	}

	/** @return true|WP_Error */
	private function read_permission() {
		if ( ! SPDB_Membership_Guard::current_user_is_approved() || ! SPDB_Capabilities::current_user_can( 'spdb_view_own_content' ) ) { return $this->error( 'spdb_collections_read_forbidden', 'An approved current account with collection-view authority is required.', 403 ); }
		return true;
	}
	/** @return true|WP_Error */
	private function read_repository() {
		if ( null === $this->repository ) { return $this->unavailable( 'spdb_collections_repository_unavailable', 'The collection metadata repository is not available.' ); }
		try { $health = $this->repository->health_check(); } catch ( Throwable $throwable ) { return $this->unavailable( 'spdb_collections_repository_failed', 'The collection metadata repository health check failed.' ); }
		return is_array( $health ) && true === ( $health['healthy'] ?? false ) && true === ( $health['schema_ready'] ?? false ) ? true : $this->unavailable( 'spdb_collections_repository_not_ready', 'The collection metadata repository is not ready.' );
	}
	/** @return true|WP_Error */
	private function write_gate( bool $requires_resolver ) {
		if ( ! $this->writes_configured() ) { return $this->error( 'spdb_phase23f_writes_disabled', 'Phase 23F metadata writes remain disabled until reviewed staging acceptance.', 503 ); }
		$repository = $this->read_repository(); if ( is_wp_error( $repository ) ) { return $repository; }
		if ( $requires_resolver && null === $this->resolver ) { return $this->unavailable( 'spdb_native_reference_resolver_unavailable', 'The native-reference resolver is unavailable.' ); }
		return true;
	}
	private function writes_configured(): bool {
		$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		return defined( 'SPDB_PHASE23F_WRITES_ENABLED' ) && true === SPDB_PHASE23F_WRITES_ENABLED && in_array( $environment, array( 'local', 'development', 'staging' ), true );
	}
	/** @return true|WP_Error */
	private function reference_authority( string $scope ) {
		if ( ! in_array( $scope, SPDB_Collections_Policy::scopes(), true ) ) { return $this->error( 'spdb_knowledge_scope_invalid', 'The knowledge-link scope is invalid.' ); }
		if ( ! SPDB_Membership_Guard::current_user_is_approved() ) { return $this->error( 'spdb_collections_account_forbidden', 'An approved current account is required.', 403 ); }
		if ( 'institution' === $scope ) {
			return $this->current_user_is_founder() && SPDB_Capabilities::current_user_can( 'spdb_manage_campaigns' ) ? true : $this->error( 'spdb_collections_institution_forbidden', 'Institution metadata is Founder-governed.', 403 );
		}
		return SPDB_Capabilities::current_user_can( 'spdb_manage_own_content' ) ? true : $this->error( 'spdb_collections_own_forbidden', 'Own-scope reference authority is required.', 403 );
	}

	/** @return array<string,mixed>|WP_Error */
	private function normalize_collection_query( array $input ) {
		$allowed = array( 'scope', 'record_type', 'status', 'page', 'per_page' ); if ( array_diff( array_keys( $input ), $allowed ) ) { return $this->error( 'spdb_collections_query_field_invalid', 'The collection query contains an unsupported field.' ); }
		$scope = $this->scope_for_read( $input['scope'] ?? 'own' ); if ( is_wp_error( $scope ) ) { return $scope; }
		$record_type = is_scalar( $input['record_type'] ?? '' ) ? trim( (string) ( $input['record_type'] ?? '' ) ) : '';
		$status = is_scalar( $input['status'] ?? '' ) ? trim( (string) ( $input['status'] ?? '' ) ) : '';
		if ( '' !== $record_type && ! in_array( $record_type, SPDB_Collections_Policy::record_types(), true ) ) { return $this->error( 'spdb_collections_query_type_invalid', 'The collection query type is invalid.' ); }
		$statuses = 'collection' === $record_type ? SPDB_Collections_Policy::collection_statuses() : ( 'campaign' === $record_type ? SPDB_Collections_Policy::campaign_statuses() : array_unique( array_merge( SPDB_Collections_Policy::collection_statuses(), SPDB_Collections_Policy::campaign_statuses() ) ) );
		if ( '' !== $status && ! in_array( $status, $statuses, true ) ) { return $this->error( 'spdb_collections_query_status_invalid', 'The collection query status is invalid for the selected record type.' ); }
		$page = $this->positive_integer( $input['page'] ?? 1, 100000 ); $per_page = $this->positive_integer( $input['per_page'] ?? 20, 50 );
		if ( null === $page || null === $per_page ) { return $this->error( 'spdb_collections_query_pagination_invalid', 'The collection query pagination is invalid.' ); }
		return array( 'scope' => $scope, 'owner_user_id' => get_current_user_id(), 'record_type' => $record_type, 'status' => $status, 'page' => $page, 'per_page' => $per_page );
	}
	/** @return array<string,mixed>|WP_Error */
	private function normalize_knowledge_query( array $input ) {
		$allowed = array( 'scope', 'status', 'page', 'per_page' ); if ( array_diff( array_keys( $input ), $allowed ) ) { return $this->error( 'spdb_knowledge_query_field_invalid', 'The knowledge-link query contains an unsupported field.' ); }
		$scope = $this->scope_for_read( $input['scope'] ?? 'own' ); if ( is_wp_error( $scope ) ) { return $scope; }
		$status = is_scalar( $input['status'] ?? '' ) ? trim( (string) ( $input['status'] ?? '' ) ) : '';
		if ( '' !== $status && ! in_array( $status, array( 'active', 'archived' ), true ) ) { return $this->error( 'spdb_knowledge_query_status_invalid', 'The knowledge-link status filter is invalid.' ); }
		$page = $this->positive_integer( $input['page'] ?? 1, 100000 ); $per_page = $this->positive_integer( $input['per_page'] ?? 20, 50 );
		if ( null === $page || null === $per_page ) { return $this->error( 'spdb_knowledge_query_pagination_invalid', 'The knowledge-link query pagination is invalid.' ); }
		return array( 'scope' => $scope, 'owner_user_id' => get_current_user_id(), 'status' => $status, 'page' => $page, 'per_page' => $per_page );
	}
	/** @return string|WP_Error */
	private function scope_for_read( $raw ) {
		$scope = is_scalar( $raw ) ? trim( (string) $raw ) : '';
		if ( ! in_array( $scope, SPDB_Collections_Policy::scopes(), true ) ) { return $this->error( 'spdb_collections_query_scope_invalid', 'The metadata scope is invalid.' ); }
		if ( 'institution' === $scope && ! $this->current_user_is_founder() ) { return $this->error( 'spdb_collections_institution_forbidden', 'Institution metadata is Founder-governed.', 403 ); }
		return $scope;
	}

	private function validate_collection_envelope( $result, array $query ) {
		$envelope = $this->validate_envelope( $result, $query ); if ( is_wp_error( $envelope ) ) { return $envelope; }
		$items = array(); foreach ( $envelope['items'] as $row ) { $record = $this->validate_collection_record( $row ); if ( is_wp_error( $record ) || ! $this->record_is_visible( $record ) ) { return $this->unavailable( 'spdb_collections_repository_response_invalid', 'The collection repository returned an unauthorized or invalid record.' ); } $items[] = $record; }
		$envelope['items'] = $items; return $envelope;
	}
	private function validate_knowledge_envelope( $result, array $query ) {
		$envelope = $this->validate_envelope( $result, $query ); if ( is_wp_error( $envelope ) ) { return $envelope; }
		$items = array(); foreach ( $envelope['items'] as $row ) { $record = $this->validate_knowledge_record( $row ); if ( is_wp_error( $record ) || ! $this->record_is_visible( $record ) ) { return $this->unavailable( 'spdb_knowledge_repository_response_invalid', 'The knowledge repository returned an unauthorized or invalid record.' ); } $items[] = $record; }
		$envelope['items'] = $items; return $envelope;
	}
	private function validate_envelope( $result, array $query ) {
		if ( ! is_array( $result ) || ! isset( $result['items'] ) || ! is_array( $result['items'] ) || ! $this->is_list( $result['items'] ) ) { return $this->unavailable( 'spdb_repository_response_invalid', 'The metadata repository returned an invalid response.' ); }
		$page = $this->nonnegative_integer( $result['page'] ?? null ); $per_page = $this->nonnegative_integer( $result['per_page'] ?? null ); $total = $this->nonnegative_integer( $result['total'] ?? null );
		if ( null === $page || null === $per_page || null === $total || $page !== $query['page'] || $per_page !== $query['per_page'] || count( $result['items'] ) > $per_page || $total < count( $result['items'] ) || ! is_bool( $result['has_more'] ?? null ) ) { return $this->unavailable( 'spdb_repository_response_invalid', 'The metadata repository returned an inconsistent response.' ); }
		return array( 'items' => $result['items'], 'page' => $page, 'per_page' => $per_page, 'total' => $total, 'has_more' => (bool) $result['has_more'] );
	}
	private function validate_collection_record( $record ) {
		$allowed = array( 'collection_id', 'record_type', 'scope', 'title', 'objective', 'ethical_declaration', 'owner_user_id', 'contributors', 'target_surfaces', 'status', 'start_at_gmt', 'end_at_gmt', 'version', 'created_by', 'created_at_gmt', 'updated_at_gmt', 'archived_at_gmt', 'replayed' );
		if ( ! is_array( $record ) || array_diff( array_keys( $record ), $allowed ) ) { return $this->error( 'spdb_collection_projection_invalid', 'A collection projection contains an invalid field or shape.' ); }
		$type = is_string( $record['record_type'] ?? null ) ? $record['record_type'] : '';
		$scope = is_string( $record['scope'] ?? null ) ? $record['scope'] : '';
		$status = is_string( $record['status'] ?? null ) ? $record['status'] : '';
		$owner = $this->strict_positive_integer( $record['owner_user_id'] ?? null );
		$version = $this->strict_positive_integer( $record['version'] ?? null );
		$created_by = $this->strict_positive_integer( $record['created_by'] ?? null );
		$statuses = 'campaign' === $type ? SPDB_Collections_Policy::campaign_statuses() : SPDB_Collections_Policy::collection_statuses();
		$title = $this->projection_text( $record['title'] ?? null, 200, false );
		$objective = $this->projection_text( $record['objective'] ?? '', 1000, true );
		$ethics = $this->projection_text( $record['ethical_declaration'] ?? '', 1000, true );
		$contributors = $this->projection_positive_list( $record['contributors'] ?? null, 25 );
		$surfaces = $this->projection_enum_list( $record['target_surfaces'] ?? null, SPDB_Collections_Policy::target_surfaces(), 20 );
		$start = $this->projection_timestamp( $record['start_at_gmt'] ?? '', true );
		$end = $this->projection_timestamp( $record['end_at_gmt'] ?? '', true );
		$created = $this->projection_timestamp( $record['created_at_gmt'] ?? null, false );
		$updated = $this->projection_timestamp( $record['updated_at_gmt'] ?? null, false );
		$archived = $this->projection_timestamp( $record['archived_at_gmt'] ?? '', true );
		if ( ! $this->valid_metadata_id( is_string( $record['collection_id'] ?? null ) ? $record['collection_id'] : '' ) || ! in_array( $type, SPDB_Collections_Policy::record_types(), true ) || ! in_array( $scope, SPDB_Collections_Policy::scopes(), true ) || ! in_array( $status, $statuses, true ) || null === $owner || null === $version || null === $created_by || is_wp_error( $title ) || is_wp_error( $objective ) || is_wp_error( $ethics ) || is_wp_error( $contributors ) || is_wp_error( $surfaces ) || is_wp_error( $start ) || is_wp_error( $end ) || is_wp_error( $created ) || is_wp_error( $updated ) || is_wp_error( $archived ) ) { return $this->error( 'spdb_collection_projection_invalid', 'A collection projection is invalid.' ); }
		if ( '' !== $start && '' !== $end && $start > $end ) { return $this->error( 'spdb_collection_projection_invalid', 'A collection projection contains an invalid date range.' ); }
		if ( 'collection' === $type && ( '' !== $ethics || array() !== $surfaces || '' !== $start || '' !== $end || ( 'own' === $scope && array() !== $contributors ) ) ) { return $this->error( 'spdb_collection_projection_invalid', 'A collection projection violates the collection metadata contract.' ); }
		if ( 'campaign' === $type && ( 'institution' !== $scope || '' === $objective || '' === $ethics || array() === $surfaces || '' === $start || '' === $end ) ) { return $this->error( 'spdb_collection_projection_invalid', 'A campaign projection violates the campaign metadata contract.' ); }
		$projection = array( 'collection_id' => $record['collection_id'], 'record_type' => $type, 'scope' => $scope, 'title' => $title, 'objective' => $objective, 'ethical_declaration' => $ethics, 'owner_user_id' => $owner, 'contributors' => $contributors, 'target_surfaces' => $surfaces, 'status' => $status, 'start_at_gmt' => $start, 'end_at_gmt' => $end, 'version' => $version, 'created_by' => $created_by, 'created_at_gmt' => $created, 'updated_at_gmt' => $updated, 'archived_at_gmt' => $archived );
		if ( array_key_exists( 'replayed', $record ) ) { if ( ! is_bool( $record['replayed'] ) ) { return $this->error( 'spdb_collection_projection_invalid', 'A collection replay marker is invalid.' ); } $projection['replayed'] = $record['replayed']; }
		return $projection;
	}
	private function validate_knowledge_record( $record ) {
		$allowed = array( 'link_id', 'scope', 'owner_user_id', 'source_provider_key', 'source_object_type', 'source_object_id', 'source_native_version', 'target_provider_key', 'target_object_type', 'target_object_id', 'target_native_version', 'relation_type', 'status', 'version', 'created_by', 'created_at_gmt', 'updated_at_gmt', 'archived_at_gmt', 'replayed' );
		if ( ! is_array( $record ) || array_diff( array_keys( $record ), $allowed ) ) { return $this->error( 'spdb_knowledge_projection_invalid', 'A knowledge-link projection contains an invalid field or shape.' ); }
		$scope = is_string( $record['scope'] ?? null ) ? $record['scope'] : '';
		$status = is_string( $record['status'] ?? null ) ? $record['status'] : '';
		$owner = $this->strict_positive_integer( $record['owner_user_id'] ?? null );
		$version = $this->strict_positive_integer( $record['version'] ?? null );
		$created_by = $this->strict_positive_integer( $record['created_by'] ?? null );
		$source_provider = is_string( $record['source_provider_key'] ?? null ) ? $record['source_provider_key'] : '';
		$source_type = is_string( $record['source_object_type'] ?? null ) ? $record['source_object_type'] : '';
		$source_id = is_string( $record['source_object_id'] ?? null ) ? $record['source_object_id'] : '';
		$target_provider = is_string( $record['target_provider_key'] ?? null ) ? $record['target_provider_key'] : '';
		$target_type = is_string( $record['target_object_type'] ?? null ) ? $record['target_object_type'] : '';
		$target_id = is_string( $record['target_object_id'] ?? null ) ? $record['target_object_id'] : '';
		$source_version = $this->native_version( $record['source_native_version'] ?? null );
		$target_version = $this->native_version( $record['target_native_version'] ?? null );
		$relation = is_string( $record['relation_type'] ?? null ) ? $record['relation_type'] : '';
		$created = $this->projection_timestamp( $record['created_at_gmt'] ?? null, false );
		$updated = $this->projection_timestamp( $record['updated_at_gmt'] ?? null, false );
		$archived = $this->projection_timestamp( $record['archived_at_gmt'] ?? '', true );
		if ( ! $this->valid_metadata_id( is_string( $record['link_id'] ?? null ) ? $record['link_id'] : '' ) || ! in_array( $scope, SPDB_Collections_Policy::scopes(), true ) || null === $owner || null === $version || null === $created_by || ! in_array( $status, array( 'active', 'archived' ), true ) || ! SPDB_Adapter_Registry::is_canonical_key( $source_provider ) || ! SPDB_Adapter_Registry::is_canonical_key( $source_type ) || ! SPDB_Projection_Validator::valid_object_id( $source_id ) || ! SPDB_Adapter_Registry::is_canonical_key( $target_provider ) || ! SPDB_Adapter_Registry::is_canonical_key( $target_type ) || ! SPDB_Projection_Validator::valid_object_id( $target_id ) || is_wp_error( $source_version ) || is_wp_error( $target_version ) || ! in_array( $relation, SPDB_Collections_Policy::knowledge_relations(), true ) || is_wp_error( $created ) || is_wp_error( $updated ) || is_wp_error( $archived ) ) { return $this->error( 'spdb_knowledge_projection_invalid', 'A knowledge-link projection is invalid.' ); }
		if ( $source_provider === $target_provider && $source_type === $target_type && $source_id === $target_id ) { return $this->error( 'spdb_knowledge_projection_invalid', 'A knowledge-link projection points an object to itself.' ); }
		$projection = array( 'link_id' => $record['link_id'], 'scope' => $scope, 'owner_user_id' => $owner, 'source_provider_key' => $source_provider, 'source_object_type' => $source_type, 'source_object_id' => $source_id, 'source_native_version' => $source_version, 'target_provider_key' => $target_provider, 'target_object_type' => $target_type, 'target_object_id' => $target_id, 'target_native_version' => $target_version, 'relation_type' => $relation, 'status' => $status, 'version' => $version, 'created_by' => $created_by, 'created_at_gmt' => $created, 'updated_at_gmt' => $updated, 'archived_at_gmt' => $archived );
		if ( array_key_exists( 'replayed', $record ) ) { if ( ! is_bool( $record['replayed'] ) ) { return $this->error( 'spdb_knowledge_projection_invalid', 'A knowledge-link replay marker is invalid.' ); } $projection['replayed'] = $record['replayed']; }
		return $projection;
	}
	/** @param array<string,mixed> $record */
	private function record_is_visible( array $record ): bool { $scope = (string) ( $record['scope'] ?? '' ); $owner = (int) ( $record['owner_user_id'] ?? 0 ); return ( 'own' === $scope && $owner === get_current_user_id() ) || ( 'institution' === $scope && $this->current_user_is_founder() ); }
	private function context( string $scope ): array { return array( 'user_id' => get_current_user_id(), 'scope' => $scope, 'is_founder' => $this->current_user_is_founder(), 'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production', 'generated_at' => gmdate( 'c' ) ); }
	private function current_user_is_founder(): bool { $user_id = get_current_user_id(); return $user_id > 0 && SPDB_Membership_Guard::is_user_approved( $user_id ) && function_exists( 'smc_is_founder' ) && smc_is_founder( $user_id ); }
	/** @return true|WP_Error */
	private function validate_contributors( array $contributors ) { foreach ( $contributors as $user_id ) { if ( ! SPDB_Membership_Guard::is_user_approved( (int) $user_id ) || ! user_can( (int) $user_id, 'spdb_manage_own_content' ) ) { return $this->error( 'spdb_collection_contributor_ineligible', 'A proposed contributor is not currently approved or authorized.' ); } } return true; }
	private function native_version( $raw ) { if ( ! is_scalar( $raw ) ) { return $this->error( 'spdb_native_reference_version_invalid', 'The native object version is invalid.' ); } $value = trim( (string) $raw ); if ( '' === $value || $this->text_length( $value ) > 191 || preg_match( '/[\x00-\x1F\x7F]/u', $value ) ) { return $this->error( 'spdb_native_reference_version_invalid', 'The native object version is invalid.' ); } return $value; }
	private function request_hash( array $record, array $exclude = array() ): string { foreach ( $exclude as $key ) { unset( $record[ $key ] ); } $record = $this->canonicalize( $record ); $json = wp_json_encode( $record ); return hash( 'sha256', is_string( $json ) ? $json : serialize( $record ) ); }
	private function canonicalize( $value ) { if ( ! is_array( $value ) ) { return $value; } if ( ! $this->is_list( $value ) ) { ksort( $value ); } foreach ( $value as $key => $item ) { $value[ $key ] = $this->canonicalize( $item ); } return $value; }
	private function strict_positive_integer( $raw ): ?int { if ( is_int( $raw ) ) { return $raw > 0 ? $raw : null; } if ( is_string( $raw ) && 1 === preg_match( '/^[1-9]\d*$/', $raw ) ) { $value = (int) $raw; return $value > 0 ? $value : null; } return null; }
	private function projection_text( $raw, int $maximum, bool $allow_empty ) { if ( ! is_string( $raw ) ) { return $this->error( 'spdb_projection_text_invalid', 'Projected metadata text has an invalid shape.' ); } $value = trim( $raw ); if ( ( ! $allow_empty && '' === $value ) || $this->text_length( $value ) > $maximum || wp_strip_all_tags( $value ) !== $value || preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value ) || preg_match( '/(?:https?:\/\/|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}|\b(?:\+?92|0)?3\d{9}\b|\b\d{5}-\d{7}-\d\b)/iu', $value ) ) { return $this->error( 'spdb_projection_text_invalid', 'Projected metadata text is invalid or sensitive.' ); } return $value; }
	private function projection_positive_list( $raw, int $maximum ) { if ( ! is_array( $raw ) || ! $this->is_list( $raw ) || count( $raw ) > $maximum ) { return $this->error( 'spdb_projection_list_invalid', 'A projected integer list is invalid.' ); } $result = array(); foreach ( $raw as $item ) { $value = $this->strict_positive_integer( $item ); if ( null === $value || in_array( $value, $result, true ) ) { return $this->error( 'spdb_projection_list_invalid', 'A projected integer list is invalid.' ); } $result[] = $value; } return $result; }
	private function projection_enum_list( $raw, array $allowed, int $maximum ) { if ( ! is_array( $raw ) || ! $this->is_list( $raw ) || count( $raw ) > $maximum ) { return $this->error( 'spdb_projection_list_invalid', 'A projected metadata list is invalid.' ); } $result = array(); foreach ( $raw as $item ) { if ( ! is_string( $item ) || ! in_array( $item, $allowed, true ) || in_array( $item, $result, true ) ) { return $this->error( 'spdb_projection_list_invalid', 'A projected metadata list is invalid.' ); } $result[] = $item; } return $result; }
	private function projection_timestamp( $raw, bool $allow_empty ) { if ( ! is_string( $raw ) ) { return $this->error( 'spdb_projection_timestamp_invalid', 'A projected timestamp has an invalid shape.' ); } $value = trim( $raw ); if ( '' === $value ) { return $allow_empty ? '' : $this->error( 'spdb_projection_timestamp_invalid', 'A projected timestamp is required.' ); } if ( 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value ) ) { return $this->error( 'spdb_projection_timestamp_invalid', 'A projected timestamp is invalid.' ); } try { $date = new DateTimeImmutable( $value ); } catch ( Throwable $throwable ) { return $this->error( 'spdb_projection_timestamp_invalid', 'A projected timestamp is invalid.' ); } return $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d\TH:i:s\Z' ) === $value ? $value : $this->error( 'spdb_projection_timestamp_invalid', 'A projected timestamp is not canonical UTC.' ); }
	private function nonnegative_integer( $raw ): ?int { if ( is_int( $raw ) ) { $value = $raw; } elseif ( is_string( $raw ) && 1 === preg_match( '/^(?:0|[1-9]\d*)$/', $raw ) ) { $value = (int) $raw; } else { return null; } return $value >= 0 ? $value : null; }
	private function positive_integer( $raw, int $maximum ): ?int { $value = $this->nonnegative_integer( $raw ); return null !== $value && $value >= 1 && $value <= $maximum ? $value : null; }
	private function valid_metadata_id( string $value ): bool { return 1 === preg_match( '/^[a-z0-9][a-z0-9_-]{15,63}$/', $value ); }
	private function is_list( array $value ): bool { $expected = 0; foreach ( $value as $key => $unused ) { if ( $key !== $expected ) { return false; } ++$expected; } return true; }
	private function text_length( string $value ): int { if ( function_exists( 'mb_strlen' ) ) { return mb_strlen( $value, 'UTF-8' ); } $count = preg_match_all( '/./us', $value, $matches ); return false === $count ? strlen( $value ) : $count; }
	private function unavailable( string $code, string $message ): WP_Error { return $this->error( $code, $message, 503 ); }
	private function error( string $code, string $message, int $status = 422 ): WP_Error { return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => $status ) ); }
}
