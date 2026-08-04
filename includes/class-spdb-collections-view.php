<?php
/**
 * Resolve bounded, read-only dashboard projections for Phase 23F.
 *
 * This class never calls the repository directly and never exposes a mutation.
 * Every record passes through SPDB_Collections_Service authorization and
 * projection validation before it reaches a template.
 */
defined( 'ABSPATH' ) || exit;

final class SPDB_Collections_View {
	private SPDB_Collections_Service $service;

	public function __construct( SPDB_Collections_Service $service ) {
		$this->service = $service;
	}

	/** @return array<string,mixed>|WP_Error */
	public function resolve( array $input ) {
		$query = $this->normalize_query( $input );
		if ( is_wp_error( $query ) ) { return $query; }

		$projection = array(
			'mode'       => 'collection_list',
			'section'    => $query['section'],
			'query'      => $query,
			'health'     => $this->service->health(),
			'list'       => null,
			'detail'     => null,
			'items'      => null,
			'item'       => null,
		);

		if ( 'knowledge' === $query['section'] ) {
			if ( '' !== $query['link_id'] ) {
				$detail = $this->service->get_knowledge_link( $query['link_id'] );
				if ( is_wp_error( $detail ) ) { return $detail; }
				$projection['mode']   = 'knowledge_detail';
				$projection['detail'] = $detail;
				return $projection;
			}

			$list_query = array(
				'scope'    => $query['scope'],
				'page'     => $query['page'],
				'per_page' => $query['per_page'],
			);
			if ( '' !== $query['status'] ) { $list_query['status'] = $query['status']; }
			$list = $this->service->list_knowledge_links( $list_query );
			if ( is_wp_error( $list ) ) { return $list; }
			$projection['mode'] = 'knowledge_list';
			$projection['list'] = $list;
			return $projection;
		}

		if ( '' !== $query['collection_id'] ) {
			$detail = $this->service->get_collection( $query['collection_id'] );
			if ( is_wp_error( $detail ) ) { return $detail; }
			$projection['detail'] = $detail;

			if ( '' !== $query['item_id'] ) {
				$item = $this->service->get_collection_item( $query['collection_id'], $query['item_id'] );
				if ( is_wp_error( $item ) ) { return $item; }
				$projection['mode'] = 'collection_item';
				$projection['item'] = $item;
				return $projection;
			}

			$items = $this->service->list_collection_items(
				$query['collection_id'],
				array( 'page' => $query['item_page'], 'per_page' => $query['per_page'] )
			);
			if ( is_wp_error( $items ) ) { return $items; }
			$projection['mode']  = 'collection_detail';
			$projection['items'] = $items;
			return $projection;
		}

		$list_query = array(
			'scope'    => $query['scope'],
			'page'     => $query['page'],
			'per_page' => $query['per_page'],
		);
		if ( '' !== $query['record_type'] ) { $list_query['record_type'] = $query['record_type']; }
		if ( '' !== $query['status'] ) { $list_query['status'] = $query['status']; }
		$list = $this->service->list_collections( $list_query );
		if ( is_wp_error( $list ) ) { return $list; }
		$projection['list'] = $list;
		return $projection;
	}

	/** @return array<string,mixed>|WP_Error */
	private function normalize_query( array $input ) {
		$allowed = array( 'view', 'section', 'scope', 'record_type', 'status', 'page', 'per_page', 'collection_id', 'item_id', 'item_page', 'link_id' );
		if ( array_diff( array_keys( $input ), $allowed ) ) {
			return $this->error( 'spdb_collections_view_query_invalid', 'The Collections dashboard request contains an unsupported parameter.' );
		}

		$view = $this->string_value( $input['view'] ?? 'collections' );
		if ( 'collections' !== $view ) {
			return $this->error( 'spdb_collections_view_query_invalid', 'The Collections dashboard view is invalid.' );
		}
		$section = $this->string_value( $input['section'] ?? 'collections' );
		$scope = $this->string_value( $input['scope'] ?? 'own' );
		$record_type = $this->string_value( $input['record_type'] ?? '' );
		$status = $this->string_value( $input['status'] ?? '' );
		$collection_id = $this->string_value( $input['collection_id'] ?? '' );
		$item_id = $this->string_value( $input['item_id'] ?? '' );
		$link_id = $this->string_value( $input['link_id'] ?? '' );
		$page = $this->positive_integer( $input['page'] ?? 1, 1000 );
		$item_page = $this->positive_integer( $input['item_page'] ?? 1, 1000 );
		$per_page = $this->per_page( $input['per_page'] ?? 20 );

		if ( ! in_array( $section, array( 'collections', 'knowledge' ), true ) || ! in_array( $scope, SPDB_Collections_Policy::scopes(), true ) || null === $page || null === $item_page || null === $per_page ) {
			return $this->error( 'spdb_collections_view_query_invalid', 'The Collections dashboard request is invalid.' );
		}
		foreach ( array( $collection_id, $item_id, $link_id ) as $identifier ) {
			if ( '' !== $identifier && ! $this->valid_metadata_id( $identifier ) ) {
				return $this->error( 'spdb_collections_view_identifier_invalid', 'A Collections dashboard identifier is invalid.' );
			}
		}
		if ( '' !== $item_id && '' === $collection_id ) {
			return $this->error( 'spdb_collections_view_query_invalid', 'A collection item requires its parent collection identifier.' );
		}

		if ( 'knowledge' === $section ) {
			if ( '' !== $collection_id || '' !== $item_id || '' !== $record_type || 1 !== $item_page ) {
				return $this->error( 'spdb_collections_view_query_invalid', 'Collection parameters are not valid in the knowledge-link view.' );
			}
			if ( '' !== $status && ! in_array( $status, array( 'active', 'archived' ), true ) ) {
				return $this->error( 'spdb_collections_view_query_invalid', 'The knowledge-link status filter is invalid.' );
			}
		} else {
			if ( '' !== $link_id ) {
				return $this->error( 'spdb_collections_view_query_invalid', 'A knowledge-link identifier is not valid in the collection view.' );
			}
			if ( '' !== $record_type && ! in_array( $record_type, SPDB_Collections_Policy::record_types(), true ) ) {
				return $this->error( 'spdb_collections_view_query_invalid', 'The collection record-type filter is invalid.' );
			}
			$statuses = 'collection' === $record_type
				? SPDB_Collections_Policy::collection_statuses()
				: ( 'campaign' === $record_type ? SPDB_Collections_Policy::campaign_statuses() : array_unique( array_merge( SPDB_Collections_Policy::collection_statuses(), SPDB_Collections_Policy::campaign_statuses() ) ) );
			if ( '' !== $status && ! in_array( $status, $statuses, true ) ) {
				return $this->error( 'spdb_collections_view_query_invalid', 'The status filter is invalid for the selected record type.' );
			}
		}

		return array(
			'view'          => 'collections',
			'section'       => $section,
			'scope'         => $scope,
			'record_type'   => $record_type,
			'status'        => $status,
			'page'          => $page,
			'item_page'     => $item_page,
			'per_page'      => $per_page,
			'collection_id' => $collection_id,
			'item_id'       => $item_id,
			'link_id'       => $link_id,
		);
	}

	private function string_value( $raw ): string {
		return is_string( $raw ) ? trim( $raw ) : '';
	}

	private function positive_integer( $raw, int $maximum ): ?int {
		if ( is_int( $raw ) ) { return $raw >= 1 && $raw <= $maximum ? $raw : null; }
		if ( ! is_string( $raw ) || 1 !== preg_match( '/^[1-9]\d*$/', $raw ) ) { return null; }
		$value = (int) $raw;
		return $value >= 1 && $value <= $maximum && (string) $value === $raw ? $value : null;
	}

	private function per_page( $raw ): ?int {
		$value = $this->positive_integer( $raw, 50 );
		return null !== $value && in_array( $value, array( 10, 20, 50 ), true ) ? $value : null;
	}

	private function valid_metadata_id( string $value ): bool {
		return 1 === preg_match( '/^[a-z0-9][a-z0-9_-]{15,63}$/', $value );
	}

	private function error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => 400 ) );
	}
}
