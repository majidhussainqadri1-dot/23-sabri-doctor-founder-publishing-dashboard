<?php
/**
 * Normalize and bound federated inventory queries.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Inventory_Query {
	public const MAX_PER_PAGE = 50;
	public const MAX_WINDOW   = 200;
	public const MAX_PROVIDERS = 10;
	public const MAX_TYPES     = 20;

	/**
	 * @param array<string,mixed> $input Raw query input.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function normalize( array $input ) {
		$page     = isset( $input['page'] ) ? (int) $input['page'] : 1;
		$per_page = isset( $input['per_page'] ) ? (int) $input['per_page'] : 20;

		if ( $page < 1 || $per_page < 1 || $per_page > self::MAX_PER_PAGE || $page * $per_page > self::MAX_WINDOW ) {
			return new WP_Error(
				'spdb_inventory_window_invalid',
				__( 'The requested inventory page is outside the bounded query window.', 'sabri-publishing-dashboard' ),
				array( 'status' => 400 )
			);
		}

		$search = isset( $input['search'] ) ? trim( sanitize_text_field( (string) $input['search'] ) ) : '';
		if ( strlen( $search ) > 100 || self::contains_sensitive_pattern( $search ) ) {
			return new WP_Error(
				'spdb_inventory_search_invalid',
				__( 'Inventory search must be short and must not contain contact details or URLs.', 'sabri-publishing-dashboard' ),
				array( 'status' => 400 )
			);
		}

		$providers = self::normalize_key_list( $input['provider'] ?? ( $input['providers'] ?? array() ), self::MAX_PROVIDERS, 'provider' );
		if ( is_wp_error( $providers ) ) {
			return $providers;
		}

		$object_types = self::normalize_key_list( $input['object_type'] ?? ( $input['object_types'] ?? array() ), self::MAX_TYPES, 'object_type' );
		if ( is_wp_error( $object_types ) ) {
			return $object_types;
		}

		$states = array();
		foreach ( array( 'lifecycle_state', 'review_state', 'visibility_state', 'operational_state', 'language', 'topic' ) as $field ) {
			$value = isset( $input[ $field ] ) ? sanitize_key( (string) $input[ $field ] ) : '';
			if ( '' !== $value && 1 !== preg_match( '/^[a-z0-9][a-z0-9_-]{0,63}$/', $value ) ) {
				return new WP_Error(
					'spdb_inventory_filter_invalid',
					__( 'An inventory filter value is invalid.', 'sabri-publishing-dashboard' ),
					array( 'status' => 400 )
				);
			}
			$states[ $field ] = $value;
		}

		$date_from = self::normalize_date( $input['date_from'] ?? '' );
		if ( is_wp_error( $date_from ) ) {
			return $date_from;
		}
		$date_to = self::normalize_date( $input['date_to'] ?? '' );
		if ( is_wp_error( $date_to ) ) {
			return $date_to;
		}
		if ( '' !== $date_from && '' !== $date_to && $date_from > $date_to ) {
			return new WP_Error(
				'spdb_inventory_date_range_invalid',
				__( 'The inventory start date must not be later than the end date.', 'sabri-publishing-dashboard' ),
				array( 'status' => 400 )
			);
		}

		$sort = isset( $input['sort'] ) ? sanitize_key( (string) $input['sort'] ) : 'modified_at';
		if ( ! in_array( $sort, array( 'modified_at', 'created_at', 'scheduled_at', 'published_at', 'title' ), true ) ) {
			$sort = 'modified_at';
		}

		$direction = isset( $input['direction'] ) ? sanitize_key( (string) $input['direction'] ) : 'desc';
		if ( ! in_array( $direction, array( 'asc', 'desc' ), true ) ) {
			$direction = 'desc';
		}

		$scope = isset( $input['scope'] ) ? sanitize_key( (string) $input['scope'] ) : 'own';
		if ( ! in_array( $scope, array( 'own', 'institution' ), true ) ) {
			$scope = 'own';
		}

		return array_merge(
			array(
				'page'         => $page,
				'per_page'     => $per_page,
				'window'       => $page * $per_page,
				'search'       => $search,
				'providers'    => $providers,
				'object_types' => $object_types,
				'date_from'    => $date_from,
				'date_to'      => $date_to,
				'sort'         => $sort,
				'direction'    => $direction,
				'scope'        => $scope,
			),
			$states
		);
	}

	/**
	 * @param mixed  $value Raw list value.
	 * @param int    $limit Maximum entries.
	 * @param string $label Error label.
	 * @return string[]|WP_Error
	 */
	private static function normalize_key_list( $value, int $limit, string $label ) {
		if ( '' === $value || null === $value ) {
			return array();
		}

		$values = is_array( $value ) ? $value : preg_split( '/\s*,\s*/', (string) $value );
		if ( ! is_array( $values ) || count( $values ) > $limit ) {
			return new WP_Error(
				'spdb_inventory_' . $label . '_list_invalid',
				__( 'An inventory filter contains too many values.', 'sabri-publishing-dashboard' ),
				array( 'status' => 400 )
			);
		}

		$clean = array();
		foreach ( $values as $item ) {
			if ( is_array( $item ) || is_object( $item ) ) {
				return new WP_Error(
					'spdb_inventory_' . $label . '_invalid',
					__( 'An inventory filter value has an invalid shape.', 'sabri-publishing-dashboard' ),
					array( 'status' => 400 )
				);
			}
			$key = sanitize_key( (string) $item );
			if ( '' === $key ) {
				continue;
			}
			if ( 1 !== preg_match( '/^[a-z0-9][a-z0-9_-]{1,63}$/', $key ) ) {
				return new WP_Error(
					'spdb_inventory_' . $label . '_invalid',
					__( 'An inventory filter key is not canonical.', 'sabri-publishing-dashboard' ),
					array( 'status' => 400 )
				);
			}
			$clean[] = $key;
		}

		return array_values( array_unique( $clean ) );
	}

	/**
	 * @param mixed $value Raw date.
	 * @return string|WP_Error
	 */
	private static function normalize_date( $value ) {
		$value = trim( sanitize_text_field( (string) $value ) );
		if ( '' === $value ) {
			return '';
		}
		if ( 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts ) || ! checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ) {
			return new WP_Error(
				'spdb_inventory_date_invalid',
				__( 'Inventory dates must use valid YYYY-MM-DD values.', 'sabri-publishing-dashboard' ),
				array( 'status' => 400 )
			);
		}
		return $value;
	}

	private static function contains_sensitive_pattern( string $value ): bool {
		if ( '' === $value ) {
			return false;
		}
		return 1 === preg_match(
			'~(?:https?://|www\.|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}|\+?\d[\d\s().-]{6,}\d)~i',
			$value
		);
	}
}
