<?php
/**
 * Read-only federated content inventory over native provider adapters.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Federated_Inventory {
	private SPDB_Adapter_Registry $registry;

	public function __construct( SPDB_Adapter_Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * @param array<string,mixed> $input Raw query.
	 * @return array<string,mixed>|WP_Error
	 */
	public function list_items( array $input ) {
		$permission = $this->permission_check();
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		$query = SPDB_Inventory_Query::normalize( $input );
		if ( is_wp_error( $query ) ) {
			return $query;
		}

		$query['scope']   = $this->resolve_scope( (string) $query['scope'] );
		$query['user_id'] = get_current_user_id();
		$selected         = $this->selected_providers( $query['providers'] );
		$items            = array();
		$total            = 0;
		$errors           = array();
		$queried          = array();

		foreach ( $selected as $provider_key => $adapter ) {
			$metadata = $this->registry->metadata( $provider_key );
			if ( ! is_array( $metadata ) ) {
				continue;
			}

			$read_state = $this->provider_read_state( $provider_key, $metadata );
			if ( true !== $read_state ) {
				$errors[] = array( 'provider_key' => $provider_key, 'code' => $read_state );
				continue;
			}

			$provider_query = $query;
			$provider_query['page']     = 1;
			$provider_query['per_page'] = (int) $query['window'];
			unset( $provider_query['providers'], $provider_query['window'] );

			try {
				$response = $adapter->list_items( $provider_query );
			} catch ( Throwable $throwable ) {
				$response = new WP_Error( 'spdb_inventory_provider_exception', __( 'The provider inventory query failed.', 'sabri-publishing-dashboard' ) );
			}

			$queried[] = $provider_key;
			if ( is_wp_error( $response ) ) {
				$errors[] = array( 'provider_key' => $provider_key, 'code' => sanitize_key( $response->get_error_code() ) ?: 'provider_error' );
				continue;
			}
			if ( ! is_array( $response ) || ! isset( $response['items'] ) || ! is_array( $response['items'] ) ) {
				$errors[] = array( 'provider_key' => $provider_key, 'code' => 'invalid_list_response' );
				continue;
			}

			$provider_total = isset( $response['total'] ) ? (int) $response['total'] : count( $response['items'] );
			$total += max( 0, min( 1000000000, $provider_total ) );

			foreach ( array_slice( $response['items'], 0, (int) $query['window'] ) as $raw_item ) {
				$item = SPDB_Projection_Validator::normalize_item( $raw_item, $provider_key, $metadata );
				if ( is_wp_error( $item ) ) {
					$errors[] = array( 'provider_key' => $provider_key, 'code' => sanitize_key( $item->get_error_code() ) ?: 'invalid_projection' );
					continue;
				}
				$item['effective_state'] = $this->registry->get_effective_state( $provider_key );
				$items[] = $item;
			}
		}

		$this->sort_items( $items, (string) $query['sort'], (string) $query['direction'] );
		$offset = ( (int) $query['page'] - 1 ) * (int) $query['per_page'];
		$items  = array_slice( $items, $offset, (int) $query['per_page'] );

		return array(
			'items'             => $items,
			'total'             => $total,
			'page'              => (int) $query['page'],
			'per_page'          => (int) $query['per_page'],
			'pages'             => max( 1, (int) ceil( $total / max( 1, (int) $query['per_page'] ) ) ),
			'scope'             => (string) $query['scope'],
			'query'             => $query,
			'providers_queried' => array_values( array_unique( $queried ) ),
			'provider_errors'   => array_slice( $errors, 0, 25 ),
		);
	}

	/**
	 * @return array<string,mixed>|WP_Error
	 */
	public function inspect_item( string $provider_key, string $object_type, string $object_id ) {
		$permission = $this->permission_check();
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		$provider_key = sanitize_key( $provider_key );
		$object_type  = sanitize_key( $object_type );
		$object_id    = trim( $object_id );
		if ( ! SPDB_Adapter_Registry::is_canonical_key( $provider_key ) || ! SPDB_Adapter_Registry::is_canonical_key( $object_type ) || ! SPDB_Projection_Validator::valid_object_id( $object_id ) ) {
			return new WP_Error( 'spdb_inventory_reference_invalid', __( 'The inventory object reference is invalid.', 'sabri-publishing-dashboard' ), array( 'status' => 400 ) );
		}

		$adapter  = $this->registry->get( $provider_key );
		$metadata = $this->registry->metadata( $provider_key );
		if ( ! $adapter || ! is_array( $metadata ) ) {
			return new WP_Error( 'spdb_inventory_provider_missing', __( 'The requested inventory provider is unavailable.', 'sabri-publishing-dashboard' ), array( 'status' => 404 ) );
		}
		if ( ! in_array( $object_type, $metadata['object_types'] ?? array(), true ) ) {
			return new WP_Error( 'spdb_inventory_type_unsupported', __( 'The requested object type is not supported by this provider.', 'sabri-publishing-dashboard' ), array( 'status' => 404 ) );
		}

		$read_state = $this->provider_read_state( $provider_key, $metadata );
		if ( true !== $read_state ) {
			return new WP_Error( 'spdb_inventory_provider_unreadable', __( 'The provider is not currently available for inventory reads.', 'sabri-publishing-dashboard' ), array( 'status' => 503 ) );
		}

		try {
			$raw = $adapter->get_item( $object_type, $object_id );
		} catch ( Throwable $throwable ) {
			$raw = new WP_Error( 'spdb_inventory_provider_exception', __( 'The provider item query failed.', 'sabri-publishing-dashboard' ) );
		}
		if ( is_wp_error( $raw ) ) {
			return new WP_Error( sanitize_key( $raw->get_error_code() ) ?: 'spdb_inventory_item_error', __( 'The requested native item could not be projected.', 'sabri-publishing-dashboard' ), array( 'status' => 404 ) );
		}

		$item = SPDB_Projection_Validator::normalize_item( $raw, $provider_key, $metadata, $object_type, $object_id );
		if ( is_wp_error( $item ) ) {
			return $item;
		}

		$item['effective_state']   = $this->registry->get_effective_state( $provider_key );
		$item['allowed_operations'] = $this->allowed_operations( $adapter, $metadata, $object_type, $object_id, $provider_key );
		$item['execution_exposed']  = false;

		return $item;
	}

	/**
	 * @return array<string,array<string,string>>
	 */
	public function provider_options(): array {
		$options = array();
		foreach ( $this->registry->all() as $provider_key => $adapter ) {
			$metadata = $this->registry->metadata( $provider_key );
			if ( is_array( $metadata ) ) {
				$options[ $provider_key ] = array(
					'label' => (string) $metadata['provider_name'],
					'state' => $this->registry->get_effective_state( $provider_key ),
				);
			}
		}
		return $options;
	}

	/** @return true|WP_Error */
	public function permission_check() {
		if ( ! is_user_logged_in() || ! SPDB_Membership_Guard::current_user_can_view_restricted_dashboard() || ! SPDB_Capabilities::current_user_can( 'spdb_view_own_content' ) ) {
			return new WP_Error( 'spdb_inventory_forbidden', __( 'You are not authorized to view the content inventory.', 'sabri-publishing-dashboard' ), array( 'status' => 403 ) );
		}
		return true;
	}

	private function resolve_scope( string $requested ): string {
		$user_id = get_current_user_id();
		$is_founder = function_exists( 'smc_is_founder' ) && smc_is_founder( $user_id );
		return $is_founder && 'institution' === $requested ? 'institution' : 'own';
	}

	/**
	 * @param string[] $requested Requested provider keys.
	 * @return array<string,SPDB_Provider_Adapter>
	 */
	private function selected_providers( array $requested ): array {
		$all = $this->registry->all();
		if ( array() === $requested ) {
			return $all;
		}
		return array_intersect_key( $all, array_fill_keys( $requested, true ) );
	}

	/**
	 * @param array<string,mixed> $metadata Provider metadata.
	 * @return true|string
	 */
	private function provider_read_state( string $provider_key, array $metadata ) {
		$declared   = (string) ( $metadata['declared_capability'] ?? SPDB_Adapter_Registry::CAPABILITY_UNAVAILABLE );
		$acceptance = $this->registry->get_acceptance_state( $provider_key );
		if ( SPDB_Adapter_Registry::ACCEPTANCE_REVOKED === $acceptance ) {
			return 'acceptance_revoked';
		}
		if ( SPDB_Adapter_Registry::CAPABILITY_TEMPORARILY_SUSPENDED === $declared ) {
			return 'provider_suspended';
		}
		if ( ! in_array( $declared, array( SPDB_Adapter_Registry::CAPABILITY_READ_ONLY, SPDB_Adapter_Registry::CAPABILITY_WRITE_CAPABLE, SPDB_Adapter_Registry::CAPABILITY_REVIEW_CAPABLE ), true ) ) {
			return 'provider_not_read_capable';
		}
		return true;
	}

	/**
	 * @param array<int,array<string,mixed>> $items Items by reference.
	 */
	private function sort_items( array &$items, string $sort, string $direction ): void {
		usort(
			$items,
			static function ( array $left, array $right ) use ( $sort, $direction ): int {
				$left_value  = (string) ( $left[ $sort ] ?? '' );
				$right_value = (string) ( $right[ $sort ] ?? '' );
				$result = 'title' === $sort ? strcasecmp( $left_value, $right_value ) : strcmp( $left_value, $right_value );
				if ( 0 === $result ) {
					$result = strcmp( (string) $left['provider_key'] . ':' . (string) $left['object_id'], (string) $right['provider_key'] . ':' . (string) $right['object_id'] );
				}
				return 'asc' === $direction ? $result : -$result;
			}
		);
	}

	/**
	 * @param array<string,mixed> $metadata Provider metadata.
	 * @return array<int,array<string,mixed>>
	 */
	private function allowed_operations( SPDB_Provider_Adapter $adapter, array $metadata, string $object_type, string $object_id, string $provider_key ): array {
		try {
			$allowed = $adapter->get_allowed_operations( $object_type, $object_id );
		} catch ( Throwable $throwable ) {
			return array();
		}
		if ( ! is_array( $allowed ) ) {
			return array();
		}

		$definitions = is_array( $metadata['operation_definitions'] ?? null ) ? $metadata['operation_definitions'] : array();
		$result = array();
		foreach ( array_slice( array_values( array_unique( $allowed ) ), 0, 20 ) as $operation_key ) {
			$operation_key = sanitize_key( (string) $operation_key );
			if ( ! isset( $definitions[ $operation_key ] ) || ! is_array( $definitions[ $operation_key ] ) ) {
				continue;
			}
			$required = (string) ( $definitions[ $operation_key ]['required_capability'] ?? '' );
			if ( '' === $required || ! SPDB_Capabilities::current_user_can( $required ) ) {
				continue;
			}
			$result[] = array(
				'key'                 => $operation_key,
				'required_capability' => $required,
				'environment_eligible'=> $this->registry->is_environment_write_eligible( $provider_key ),
				'execution_exposed'   => false,
			);
		}
		return $result;
	}
}
