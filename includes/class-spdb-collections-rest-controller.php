<?php
/** Read-only REST controller for Phase 23F organizational metadata. */
defined( 'ABSPATH' ) || exit;

final class SPDB_Collections_REST_Controller {
	private SPDB_Collections_Service $service;

	public function __construct( SPDB_Collections_Service $service ) {
		$this->service = $service;
	}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		register_rest_route( 'spdb/v1', '/collections', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( $this, 'rest_collections' ),
			'permission_callback' => array( $this, 'permission_check' ),
		) );
		register_rest_route( 'spdb/v1', '/collections/(?P<collection_id>[a-z0-9][a-z0-9_-]{15,63})', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( $this, 'rest_collection' ),
			'permission_callback' => array( $this, 'permission_check' ),
			'args' => array( 'collection_id' => array( 'required' => true, 'validate_callback' => array( $this, 'validate_metadata_id' ) ) ),
		) );
		register_rest_route( 'spdb/v1', '/collections/(?P<collection_id>[a-z0-9][a-z0-9_-]{15,63})/items', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( $this, 'rest_collection_items' ),
			'permission_callback' => array( $this, 'permission_check' ),
			'args' => array( 'collection_id' => array( 'required' => true, 'validate_callback' => array( $this, 'validate_metadata_id' ) ) ),
		) );
		register_rest_route( 'spdb/v1', '/collections/(?P<collection_id>[a-z0-9][a-z0-9_-]{15,63})/items/(?P<item_id>[a-z0-9][a-z0-9_-]{15,63})', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( $this, 'rest_collection_item' ),
			'permission_callback' => array( $this, 'permission_check' ),
			'args' => array(
				'collection_id' => array( 'required' => true, 'validate_callback' => array( $this, 'validate_metadata_id' ) ),
				'item_id' => array( 'required' => true, 'validate_callback' => array( $this, 'validate_metadata_id' ) ),
			),
		) );
		register_rest_route( 'spdb/v1', '/knowledge-links', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( $this, 'rest_knowledge_links' ),
			'permission_callback' => array( $this, 'permission_check' ),
		) );
		register_rest_route( 'spdb/v1', '/knowledge-links/(?P<link_id>[a-z0-9][a-z0-9_-]{15,63})', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( $this, 'rest_knowledge_link' ),
			'permission_callback' => array( $this, 'permission_check' ),
			'args' => array( 'link_id' => array( 'required' => true, 'validate_callback' => array( $this, 'validate_metadata_id' ) ) ),
		) );
	}

	/** @return true|WP_Error */
	public function permission_check() {
		if ( ! SPDB_Membership_Guard::current_user_is_approved() || ! SPDB_Capabilities::current_user_can( 'spdb_view_own_content' ) ) {
			return new WP_Error( 'spdb_collections_rest_forbidden', __( 'An approved current account with collection-view authority is required.', 'sabri-publishing-dashboard' ), array( 'status' => 403 ) );
		}
		return true;
	}

	public function rest_collections( WP_REST_Request $request ) {
		$query = $this->allow_query( $request, array( 'scope', 'record_type', 'status', 'page', 'per_page' ) );
		if ( is_wp_error( $query ) ) { return $query; }
		$result = $this->service->list_collections( $query );
		return is_wp_error( $result ) ? $result : $this->list_response( $result );
	}

	public function rest_collection( WP_REST_Request $request ) {
		$empty = $this->allow_query( $request, array() );
		if ( is_wp_error( $empty ) ) { return $empty; }
		$result = $this->service->get_collection( (string) $request['collection_id'] );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public function rest_collection_items( WP_REST_Request $request ) {
		$query = $this->allow_query( $request, array( 'page', 'per_page' ) );
		if ( is_wp_error( $query ) ) { return $query; }
		$result = $this->service->list_collection_items( (string) $request['collection_id'], $query );
		return is_wp_error( $result ) ? $result : $this->list_response( $result );
	}

	public function rest_collection_item( WP_REST_Request $request ) {
		$empty = $this->allow_query( $request, array() );
		if ( is_wp_error( $empty ) ) { return $empty; }
		$result = $this->service->get_collection_item( (string) $request['collection_id'], (string) $request['item_id'] );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public function rest_knowledge_links( WP_REST_Request $request ) {
		$query = $this->allow_query( $request, array( 'scope', 'status', 'page', 'per_page' ) );
		if ( is_wp_error( $query ) ) { return $query; }
		$result = $this->service->list_knowledge_links( $query );
		return is_wp_error( $result ) ? $result : $this->list_response( $result );
	}

	public function rest_knowledge_link( WP_REST_Request $request ) {
		$empty = $this->allow_query( $request, array() );
		if ( is_wp_error( $empty ) ) { return $empty; }
		$result = $this->service->get_knowledge_link( (string) $request['link_id'] );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/** @param mixed $value */
	public function validate_metadata_id( $value ): bool {
		return is_string( $value ) && 1 === preg_match( '/^[a-z0-9][a-z0-9_-]{15,63}$/', $value );
	}

	/** @return array<string,mixed>|WP_Error */
	private function allow_query( WP_REST_Request $request, array $allowed ) {
		$query = method_exists( $request, 'get_query_params' ) ? $request->get_query_params() : array();
		if ( ! is_array( $query ) || array_diff( array_keys( $query ), $allowed ) ) {
			return new WP_Error( 'spdb_collections_rest_query_invalid', __( 'The Phase 23F read request contains an unsupported query parameter.', 'sabri-publishing-dashboard' ), array( 'status' => 400 ) );
		}
		$result = array();
		foreach ( $allowed as $key ) {
			if ( array_key_exists( $key, $query ) ) { $result[ $key ] = $query[ $key ]; }
		}
		return $result;
	}

	private function list_response( array $result ) {
		$response = rest_ensure_response( $result );
		if ( $response instanceof WP_HTTP_Response ) {
			$total = (int) $result['total'];
			$per_page = max( 1, (int) $result['per_page'] );
			$response->header( 'X-WP-Total', (string) $total );
			$response->header( 'X-WP-TotalPages', (string) (int) ceil( $total / $per_page ) );
			$response->header( 'X-SPDB-Has-More', ! empty( $result['has_more'] ) ? '1' : '0' );
		}
		return $response;
	}
}
