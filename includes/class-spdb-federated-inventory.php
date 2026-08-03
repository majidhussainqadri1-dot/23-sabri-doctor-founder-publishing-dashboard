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
		$seen             = array();
		$reported_total   = 0;
		$errors           = array();
		$queried          = array();

		foreach ( array_diff( $query['providers'], array_keys( $selected ) ) as $missing_provider ) {
			$errors[] = array( 'provider_key' => $missing_provider, 'code' => 'provider_unavailable' );
		}

		foreach ( $selected as $provider_key => $adapter ) {
			$metadata = $this->registry->metadata( $provider_key );
			if ( ! is_array( $metadata ) ) {
				continue;
			}

			if ( $query['object_types'] && ! array_intersect( $query['object_types'], $metadata['object_types'] ?? array() ) ) {
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
				$errors[] = array( 'provider_key' => $provider_key, 'code' => 'provider_query_failed' );
				continue;
			}
			if ( ! is_array( $response ) || ! isset( $response['items'] ) || ! is_array( $response['items'] ) ) {
				$errors[] = array( 'provider_key' => $provider_key, 'code' => 'invalid_list_response' );
				continue;
			}

			$provider_total = $this->provider_total( $response['total'] ?? count( $response['items'] ), count( $response['items'] ) );
			if ( is_wp_error( $provider_total ) ) {
				$errors[] = array( 'provider_key' => $provider_key, 'code' => 'invalid_total' );
				continue;
			}
			$reported_total += $provider_total;

			foreach ( array_slice( $response['items'], 0, (int) $query['window'] ) as $raw_item ) {
				$item = SPDB_Projection_Validator::normalize_item( $raw_item, $provider_key, $metadata );
				if ( is_wp_error( $item ) ) {
					$errors[] = array( 'provider_key' => $provider_key, 'code' => sanitize_key( $item->get_error_code() ) ?: 'invalid_projection' );
					continue;
				}
				if ( ! $this->scope_allows_item( $item, (string) $query['scope'], (int) $query['user_id'] ) ) {
					$errors[] = array( 'provider_key' => $provider_key, 'code' => 'scope_mismatch' );
					continue;
				}
				if ( ! $this->item_matches_query( $item, $query ) ) {
					$errors[] = array( 'provider_key' => $provider_key, 'code' => 'filter_mismatch' );
					continue;
				}

				$reference = $provider_key . ':' . $item['object_type'] . ':' . $item['object_id'];
				if ( isset( $seen[ $reference ] ) ) {
					$errors[] = array( 'provider_key' => $provider_key, 'code' => 'duplicate_projection' );
					continue;
				}
				$seen[ $reference ] = true;
				$item['effective_state'] = $this->registry->get_effective_state( $provider_key );
				$items[] = $item;
			}
		}

		$this->sort_items( $items, (string) $query['sort'], (string) $query['direction'] );
		$validated_window_count = count( $items );
		$accessible_total       = min( SPDB_Inventory_Query::MAX_WINDOW, max( $reported_total, $validated_window_count ) );
		$pages                  = max( 1, (int) ceil( $accessible_total / max( 1, (int) $query['per_page'] ) ) );
		if ( (int) $query['page'] > $pages ) {
			return new WP_Error( 'spdb_inventory_page_out_of_range', __( 'The requested inventory page is outside the available bounded result window.', 'sabri-publishing-dashboard' ), array( 'status' => 400 ) );
		}

		$offset = ( (int) $query['page'] - 1 ) * (int) $query['per_page'];
		$items  = array_slice( $items, $offset, (int) $query['per_page'] );

		return array(
			'items'                  => $items,
			'total'                  => $reported_total,
			'accessible_total'       => $accessible_total,
			'validated_window_count' => $validated_window_count,
			'page'                   => (int) $query['page'],
			'per_page'               => (int) $query['per_page'],
			'pages'                  => $pages,
			'scope'                  => (string) $query['scope'],
			'query'                  => SPDB_Inventory_Query::public_projection( $query ),
			'providers_queried'      => array_values( array_unique( $queried ) ),
			'provider_errors'        => array_slice( $errors, 0, 25 ),
		);
	}

	/** @return array<string,mixed>|WP_Error */
	public function inspect_item( string $provider_key, string $object_type, string $object_id ) {
		$permission = $this->permission_check();
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		$provider_key = trim( $provider_key );
		$object_type  = trim( $object_type );
		$object_id    = trim( $object_id );
		if ( sanitize_key( $provider_key ) !== $provider_key || sanitize_key( $object_type ) !== $object_type || ! SPDB_Adapter_Registry::is_canonical_key( $provider_key ) || ! SPDB_Adapter_Registry::is_canonical_key( $object_type ) || ! SPDB_Projection_Validator::valid_object_id( $object_id ) ) {
			return new WP_Error( 'spdb_inventory_reference_invalid', __( 'The inventory object reference is invalid.', 'sabri-publishing-dashboard' ), array( 'status' => 400 ) );
		}

		$adapter  = $this->registry->get( $provider_key );
		$metadata = $this->registry->metadata( $provider_key );
		if ( ! $adapter || ! is_array( $metadata ) || ! in_array( $object_type, $metadata['object_types'] ?? array(), true ) ) {
			return $this->not_found();
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
			return $this->not_found();
		}

		$item = SPDB_Projection_Validator::normalize_item( $raw, $provider_key, $metadata, $object_type, $object_id );
		if ( is_wp_error( $item ) ) {
			return $item;
		}

		$scope = $this->resolve_scope( 'institution' );
		if ( ! $this->scope_allows_item( $item, $scope, get_current_user_id() ) ) {
			return $this->not_found();
		}

		$item['effective_state']    = $this->registry->get_effective_state( $provider_key );
		$item['allowed_operations'] = $this->allowed_operations( $adapter, $metadata, $object_type, $object_id, $provider_key );
		$item['execution_exposed']  = false;
		return $item;
	}

	/** @return array<string,array<string,string>> */
	public function provider_options(): array {
		$options = array();
		foreach ( $this->registry->all() as $provider_key => $adapter ) {
			$metadata = $this->registry->metadata( $provider_key );
			if ( ! is_array( $metadata ) ) {
				continue;
			}
			$read_state = $this->provider_read_state( $provider_key, $metadata );
			if ( true !== $read_state ) {
				continue;
			}
			$options[ $provider_key ] = array(
				'label' => (string) $metadata['provider_name'],
				'state' => $this->registry->get_effective_state( $provider_key ),
			);
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
		$user_id    = get_current_user_id();
		$is_founder = function_exists( 'smc_is_founder' ) && smc_is_founder( $user_id );
		return $is_founder && 'institution' === $requested ? 'institution' : 'own';
	}

	/** @param string[] $requested @return array<string,SPDB_Provider_Adapter> */
	private function selected_providers( array $requested ): array {
		$all = $this->registry->all();
		return array() === $requested ? $all : array_intersect_key( $all, array_fill_keys( $requested, true ) );
	}

	/** @param array<string,mixed> $metadata @return true|string */
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

	/** @param mixed $raw_total @return int|WP_Error */
	private function provider_total( $raw_total, int $returned_count ) {
		if ( is_int( $raw_total ) ) {
			$total = $raw_total;
		} elseif ( is_string( $raw_total ) && ctype_digit( $raw_total ) ) {
			$total = (int) $raw_total;
		} else {
			return new WP_Error( 'spdb_inventory_total_invalid', __( 'A provider returned an invalid inventory total.', 'sabri-publishing-dashboard' ) );
		}
		if ( $total < 0 || $total > 1000000000 ) {
			return new WP_Error( 'spdb_inventory_total_invalid', __( 'A provider returned an invalid inventory total.', 'sabri-publishing-dashboard' ) );
		}
		return max( $total, $returned_count );
	}

	/** @param array<string,mixed> $item */
	private function scope_allows_item( array $item, string $scope, int $user_id ): bool {
		if ( 'institution' === $scope ) {
			return function_exists( 'smc_is_founder' ) && smc_is_founder( $user_id );
		}
		return $user_id > 0 && (int) ( $item['owner_user_id'] ?? 0 ) === $user_id;
	}

	/** @param array<string,mixed> $item @param array<string,mixed> $query */
	private function item_matches_query( array $item, array $query ): bool {
		if ( $query['object_types'] && ! in_array( $item['object_type'], $query['object_types'], true ) ) {
			return false;
		}
		foreach ( array( 'lifecycle_state', 'review_state', 'visibility_state', 'operational_state', 'language', 'topic' ) as $field ) {
			if ( '' !== (string) $query[ $field ] && (string) $item[ $field ] !== (string) $query[ $field ] ) {
				return false;
			}
		}
		if ( '' !== (string) $query['search'] ) {
			$haystack = implode( ' ', array( $item['title'], $item['summary'], $item['author']['display_name'] ?? '' ) );
			if ( false === stripos( $haystack, (string) $query['search'] ) ) {
				return false;
			}
		}
		if ( '' !== (string) $query['date_from'] || '' !== (string) $query['date_to'] ) {
			$date = substr( (string) ( $item['modified_at'] ?: $item['created_at'] ), 0, 10 );
			if ( 10 !== strlen( $date ) || ( '' !== (string) $query['date_from'] && $date < $query['date_from'] ) || ( '' !== (string) $query['date_to'] && $date > $query['date_to'] ) ) {
				return false;
			}
		}
		return true;
	}

	/** @param array<int,array<string,mixed>> $items */
	private function sort_items( array &$items, string $sort, string $direction ): void {
		usort(
			$items,
			static function ( array $left, array $right ) use ( $sort, $direction ): int {
				$left_value  = (string) ( $left[ $sort ] ?? '' );
				$right_value = (string) ( $right[ $sort ] ?? '' );
				$result      = 'title' === $sort ? strcasecmp( $left_value, $right_value ) : strcmp( $left_value, $right_value );
				if ( 0 === $result ) {
					$result = strcmp( (string) $left['provider_key'] . ':' . (string) $left['object_type'] . ':' . (string) $left['object_id'], (string) $right['provider_key'] . ':' . (string) $right['object_type'] . ':' . (string) $right['object_id'] );
				}
				return 'asc' === $direction ? $result : -$result;
			}
		);
	}

	/** @param array<string,mixed> $metadata @return array<int,array<string,mixed>> */
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
		$result      = array();
		$seen        = array();
		foreach ( $allowed as $raw_operation_key ) {
			if ( count( $result ) >= 20 || ! is_scalar( $raw_operation_key ) ) {
				continue;
			}
			$operation_key = trim( (string) $raw_operation_key );
			if ( isset( $seen[ $operation_key ] ) || sanitize_key( $operation_key ) !== $operation_key || ! isset( $definitions[ $operation_key ] ) || ! is_array( $definitions[ $operation_key ] ) ) {
				continue;
			}
			$seen[ $operation_key ] = true;
			$required = (string) ( $definitions[ $operation_key ]['required_capability'] ?? '' );
			if ( '' === $required || ! SPDB_Capabilities::current_user_can( $required ) ) {
				continue;
			}
			$result[] = array(
				'key'                  => $operation_key,
				'required_capability'  => $required,
				'environment_eligible' => $this->registry->is_environment_write_eligible( $provider_key ),
				'execution_exposed'    => false,
			);
		}
		return $result;
	}

	private function not_found(): WP_Error {
		return new WP_Error( 'spdb_inventory_item_unavailable', __( 'The requested native item is unavailable.', 'sabri-publishing-dashboard' ), array( 'status' => 404 ) );
	}
}
