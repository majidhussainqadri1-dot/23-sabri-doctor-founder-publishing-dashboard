<?php
/**
 * Read-only REST controller for federated inventory projections.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Inventory_REST_Controller {
	private SPDB_Federated_Inventory $inventory;

	public function __construct( SPDB_Federated_Inventory $inventory ) {
		$this->inventory = $inventory;
	}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		register_rest_route(
			'spdb/v1',
			'/inventory',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_list' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);

		register_rest_route(
			'spdb/v1',
			'/inventory/(?P<provider>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_type>[a-z0-9][a-z0-9_-]{1,63})/(?P<object_id>[A-Za-z0-9][A-Za-z0-9._:-]{0,127})',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_item' ),
				'permission_callback' => array( $this, 'permission_check' ),
				'args'                => array(
					'provider' => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_canonical_key' ),
					),
					'object_type' => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_canonical_key' ),
					),
					'object_id' => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_object_id' ),
					),
				),
			)
		);
	}

	/** @return true|WP_Error */
	public function permission_check() {
		return $this->inventory->permission_check();
	}

	public function rest_list( WP_REST_Request $request ) {
		$result = $this->inventory->list_items( $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$response = rest_ensure_response( $result );
		if ( $response instanceof WP_HTTP_Response ) {
			$response->header( 'X-WP-Total', (string) $result['total'] );
			$response->header( 'X-WP-TotalPages', (string) $result['pages'] );
			$response->header( 'X-SPDB-Accessible-Total', (string) $result['accessible_total'] );
			$response->header( 'X-SPDB-Validated-Window', (string) $result['validated_window_count'] );
		}
		return $response;
	}

	public function rest_item( WP_REST_Request $request ) {
		$result = $this->inventory->inspect_item(
			(string) $request['provider'],
			(string) $request['object_type'],
			(string) $request['object_id']
		);
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/** @param mixed $value Raw value. */
	public function validate_canonical_key( $value ): bool {
		$value = is_scalar( $value ) ? (string) $value : '';
		return '' !== $value && sanitize_key( $value ) === $value && SPDB_Adapter_Registry::is_canonical_key( $value );
	}

	/** @param mixed $value Raw value. */
	public function validate_object_id( $value ): bool {
		return is_scalar( $value ) && SPDB_Projection_Validator::valid_object_id( (string) $value );
	}
}
