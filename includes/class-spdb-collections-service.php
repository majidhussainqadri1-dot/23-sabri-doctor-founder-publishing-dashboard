<?php
/**
 * Phase 23F runtime authority and native-reference boundary.
 *
 * The service begins the next coding stage after foundation correction. Reads
 * require an injected repository. Writes remain fail-closed unless an explicit
 * development/staging constant is enabled; no REST mutation route is exposed.
 *
 * @package Sabri_Publishing_Dashboard
 */

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
		$repository_health = null;
		if ( null !== $this->repository ) {
			try {
				$repository_health = $this->repository->health_check();
			} catch ( Throwable $throwable ) {
				$repository_health = array( 'healthy' => false, 'code' => 'repository_exception' );
			}
		}
		return array(
			'repository_available' => null !== $this->repository,
			'resolver_available'   => null !== $this->resolver,
			'write_enabled'        => $this->writes_enabled(),
			'repository_health'    => is_array( $repository_health ) ? $repository_health : array( 'healthy' => false, 'code' => 'repository_unavailable' ),
		);
	}

	/** @return array<string,mixed>|WP_Error */
	public function list_collections( array $input ) {
		$permission = $this->read_permission();
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}
		if ( null === $this->repository ) {
			return $this->unavailable( 'spdb_collections_repository_unavailable', 'The collection metadata repository is not available.' );
		}

		$query = $this->normalize_list_query( $input );
		if ( is_wp_error( $query ) ) {
			return $query;
		}

		try {
			$result = $this->repository->list_collections( $query );
		} catch ( Throwable $throwable ) {
			return $this->unavailable( 'spdb_collections_repository_failed', 'The collection metadata repository could not complete the query.' );
		}
		return is_array( $result ) || is_wp_error( $result ) ? $result : $this->unavailable( 'spdb_collections_repository_response_invalid', 'The collection metadata repository returned an invalid response.' );
	}

	/** @return array<string,mixed>|WP_Error */
	public function get_collection( string $collection_id ) {
		$permission = $this->read_permission();
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}
		if ( ! $this->valid_metadata_id( $collection_id ) ) {
			return $this->error( 'spdb_collection_id_invalid', 'The collection identifier is invalid.' );
		}
		if ( null === $this->repository ) {
			return $this->unavailable( 'spdb_collections_repository_unavailable', 'The collection metadata repository is not available.' );
		}

		try {
			$record = $this->repository->get_collection( $collection_id );
		} catch ( Throwable $throwable ) {
			return $this->unavailable( 'spdb_collections_repository_failed', 'The collection metadata repository could not complete the query.' );
		}
		if ( is_wp_error( $record ) ) {
			return $record;
		}
		if ( ! is_array( $record ) || ! $this->record_is_visible( $record ) ) {
			return $this->error( 'spdb_collection_not_found', 'The collection was not found.', 404 );
		}
		return $record;
	}

	/** @return array<string,mixed>|WP_Error */
	public function prepare_collection_create( array $input ) {
		$gate = $this->write_gate();
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}
		$record = SPDB_Collections_Policy::validate_collection( $input );
		if ( is_wp_error( $record ) ) {
			return $record;
		}
		$contributor_state = $this->validate_contributors( $record['contributors'] );
		if ( is_wp_error( $contributor_state ) ) {
			return $contributor_state;
		}
		$record['owner_user_id'] = get_current_user_id();
		$record['created_by']    = get_current_user_id();
		$record['idempotency_hash'] = hash( 'sha256', get_current_user_id() . '|collection_create|' . $record['idempotency_key'] );
		unset( $record['idempotency_key'] );
		return $record;
	}

	/** @return array<string,mixed>|WP_Error */
	public function prepare_knowledge_link_create( array $input ) {
		$gate = $this->write_gate();
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}
		$record = SPDB_Collections_Policy::validate_knowledge_link( $input );
		if ( is_wp_error( $record ) ) {
			return $record;
		}

		$source = $this->resolve_reference( $record['source_provider_key'], $record['source_object_type'], $record['source_object_id'], $record['scope'] );
		if ( is_wp_error( $source ) ) {
			return $source;
		}
		$target = $this->resolve_reference( $record['target_provider_key'], $record['target_object_type'], $record['target_object_id'], $record['scope'] );
		if ( is_wp_error( $target ) ) {
			return $target;
		}

		$record['owner_user_id'] = get_current_user_id();
		$record['created_by']    = get_current_user_id();
		$record['relation_hash'] = hash( 'sha256', implode( '|', array( $record['source_provider_key'], $record['source_object_type'], $record['source_object_id'], $record['target_provider_key'], $record['target_object_type'], $record['target_object_id'], $record['relation_type'] ) ) );
		$record['idempotency_hash'] = hash( 'sha256', get_current_user_id() . '|knowledge_link_create|' . $record['idempotency_key'] );
		$record['source_native_version'] = (string) $source['native_version'];
		$record['target_native_version'] = (string) $target['native_version'];
		unset( $record['idempotency_key'] );
		return $record;
	}

	/** @return array<string,mixed>|WP_Error */
	public function resolve_reference( string $provider_key, string $object_type, string $object_id, string $scope = 'own' ) {
		if ( ! SPDB_Adapter_Registry::is_canonical_key( $provider_key ) || ! SPDB_Adapter_Registry::is_canonical_key( $object_type ) || ! SPDB_Projection_Validator::valid_object_id( $object_id ) ) {
			return $this->error( 'spdb_native_reference_invalid', 'The canonical native reference is invalid.' );
		}
		if ( null === $this->resolver ) {
			return $this->unavailable( 'spdb_native_reference_resolver_unavailable', 'The native-reference resolver is unavailable.' );
		}

		$context = $this->context( $scope );
		try {
			$result = $this->resolver->resolve_reference( $provider_key, $object_type, $object_id, $context );
		} catch ( Throwable $throwable ) {
			return $this->unavailable( 'spdb_native_reference_resolver_failed', 'The native provider could not resolve the reference.' );
		}
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( ! is_array( $result ) ) {
			return $this->unavailable( 'spdb_native_reference_response_invalid', 'The native provider returned an invalid reference response.' );
		}

		$exact = (string) ( $result['provider_key'] ?? '' ) === $provider_key
			&& (string) ( $result['object_type'] ?? '' ) === $object_type
			&& (string) ( $result['object_id'] ?? '' ) === $object_id;
		if ( ! $exact || true !== ( $result['exists'] ?? null ) || true !== ( $result['visible'] ?? null ) || true !== ( $result['reference_allowed'] ?? null ) ) {
			return $this->error( 'spdb_native_reference_unavailable', 'The native object is missing, hidden, or not currently authorized for this reference.', 404 );
		}
		$native_version = $result['native_version'] ?? '';
		if ( ! is_scalar( $native_version ) || '' === trim( (string) $native_version ) || strlen( trim( (string) $native_version ) ) > 191 ) {
			return $this->error( 'spdb_native_reference_version_invalid', 'The native object version is invalid.' );
		}

		$destination = '';
		if ( isset( $result['destination'] ) && '' !== $result['destination'] ) {
			$destination = SPDB_Safe_Destination::normalize( $result['destination'] );
			if ( is_wp_error( $destination ) ) {
				return $destination;
			}
		}

		return array(
			'provider_key'     => $provider_key,
			'object_type'      => $object_type,
			'object_id'        => $object_id,
			'owner_user_id'    => max( 0, (int) ( $result['owner_user_id'] ?? 0 ) ),
			'native_version'   => trim( (string) $native_version ),
			'current_destination' => $destination,
		);
	}

	/** @return true|WP_Error */
	private function read_permission() {
		if ( ! is_user_logged_in() || ! SPDB_Capabilities::current_user_can( 'spdb_view_own_content' ) ) {
			return $this->error( 'spdb_collections_read_forbidden', 'The current account is not authorized to view collection metadata.', 403 );
		}
		return true;
	}

	/** @return true|WP_Error */
	private function write_gate() {
		if ( ! $this->writes_enabled() ) {
			return $this->error( 'spdb_phase23f_writes_disabled', 'Phase 23F metadata writes remain disabled until reviewed staging acceptance.', 503 );
		}
		if ( null === $this->repository || null === $this->resolver ) {
			return $this->unavailable( 'spdb_phase23f_runtime_incomplete', 'The Phase 23F repository or native-reference resolver is unavailable.' );
		}
		return true;
	}

	private function writes_enabled(): bool {
		$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		return defined( 'SPDB_PHASE23F_WRITES_ENABLED' )
			&& true === SPDB_PHASE23F_WRITES_ENABLED
			&& in_array( $environment, array( 'local', 'development', 'staging' ), true );
	}

	/** @return array<string,mixed>|WP_Error */
	private function normalize_list_query( array $input ) {
		$allowed = array( 'scope', 'record_type', 'status', 'page', 'per_page' );
		if ( array_diff( array_keys( $input ), $allowed ) ) {
			return $this->error( 'spdb_collections_query_field_invalid', 'The collection query contains an unsupported field.' );
		}
		$scope = isset( $input['scope'] ) && is_scalar( $input['scope'] ) ? trim( (string) $input['scope'] ) : 'own';
		if ( ! in_array( $scope, SPDB_Collections_Policy::scopes(), true ) ) {
			return $this->error( 'spdb_collections_query_scope_invalid', 'The collection query scope is invalid.' );
		}
		if ( 'institution' === $scope && ! $this->current_user_is_founder() ) {
			$scope = 'own';
		}
		$record_type = isset( $input['record_type'] ) && is_scalar( $input['record_type'] ) ? trim( (string) $input['record_type'] ) : '';
		$status      = isset( $input['status'] ) && is_scalar( $input['status'] ) ? trim( (string) $input['status'] ) : '';
		if ( '' !== $record_type && ! in_array( $record_type, SPDB_Collections_Policy::record_types(), true ) ) {
			return $this->error( 'spdb_collections_query_type_invalid', 'The collection query type is invalid.' );
		}
		$statuses = array_unique( array_merge( SPDB_Collections_Policy::collection_statuses(), SPDB_Collections_Policy::campaign_statuses() ) );
		if ( '' !== $status && ! in_array( $status, $statuses, true ) ) {
			return $this->error( 'spdb_collections_query_status_invalid', 'The collection query status is invalid.' );
		}
		$page     = $this->positive_integer( $input['page'] ?? 1, 100000 );
		$per_page = $this->positive_integer( $input['per_page'] ?? 20, 50 );
		if ( null === $page || null === $per_page ) {
			return $this->error( 'spdb_collections_query_pagination_invalid', 'The collection query pagination is invalid.' );
		}
		return array(
			'scope'         => $scope,
			'owner_user_id' => get_current_user_id(),
			'record_type'   => $record_type,
			'status'        => $status,
			'page'          => $page,
			'per_page'      => $per_page,
		);
	}

	/** @return true|WP_Error */
	private function validate_contributors( array $contributors ) {
		foreach ( $contributors as $user_id ) {
			if ( ! SPDB_Membership_Guard::is_user_approved( (int) $user_id ) || ! user_can( (int) $user_id, 'spdb_manage_own_content' ) ) {
				return $this->error( 'spdb_collection_contributor_ineligible', 'A proposed contributor is not currently approved or authorized.', 422 );
			}
		}
		return true;
	}

	/** @param array<string,mixed> $record */
	private function record_is_visible( array $record ): bool {
		$scope = (string) ( $record['scope'] ?? '' );
		$owner = (int) ( $record['owner_user_id'] ?? 0 );
		return ( 'own' === $scope && $owner === get_current_user_id() ) || ( 'institution' === $scope && $this->current_user_is_founder() );
	}

	/** @return array<string,mixed> */
	private function context( string $scope ): array {
		return array(
			'user_id'      => get_current_user_id(),
			'scope'        => 'institution' === $scope && $this->current_user_is_founder() ? 'institution' : 'own',
			'is_founder'   => $this->current_user_is_founder(),
			'environment'  => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
			'generated_at' => gmdate( 'c' ),
		);
	}

	private function current_user_is_founder(): bool {
		$user_id = get_current_user_id();
		return $user_id > 0 && SPDB_Membership_Guard::is_user_approved( $user_id ) && function_exists( 'smc_is_founder' ) && smc_is_founder( $user_id );
	}

	private function positive_integer( $raw, int $maximum ): ?int {
		if ( is_int( $raw ) ) {
			$value = $raw;
		} elseif ( is_string( $raw ) && 1 === preg_match( '/^[1-9]\d*$/', $raw ) ) {
			$value = (int) $raw;
		} else {
			return null;
		}
		return $value >= 1 && $value <= $maximum ? $value : null;
	}

	private function valid_metadata_id( string $value ): bool {
		return 1 === preg_match( '/^[a-z0-9][a-z0-9_-]{15,63}$/', $value );
	}

	private function unavailable( string $code, string $message ): WP_Error {
		return $this->error( $code, $message, 503 );
	}

	private function error( string $code, string $message, int $status = 422 ): WP_Error {
		return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => $status ) );
	}
}
