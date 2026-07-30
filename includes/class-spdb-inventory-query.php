<?php
/**
 * Normalize and bound federated inventory queries.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Inventory_Query {
	public const MAX_PER_PAGE  = 50;
	public const MAX_WINDOW    = 200;
	public const MAX_PROVIDERS = 10;
	public const MAX_TYPES     = 20;

	/**
	 * @param array<string,mixed> $input Raw query input.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function normalize( array $input ) {
		$page_raw     = self::scalar_string( $input['page'] ?? '1', 'spdb_inventory_window_invalid' );
		$per_page_raw = self::scalar_string( $input['per_page'] ?? '20', 'spdb_inventory_window_invalid' );
		if ( is_wp_error( $page_raw ) || is_wp_error( $per_page_raw ) || ! ctype_digit( $page_raw ) || ! ctype_digit( $per_page_raw ) ) {
			return self::error( 'spdb_inventory_window_invalid', 'The requested inventory page is outside the bounded query window.' );
		}

		$page     = (int) $page_raw;
		$per_page = (int) $per_page_raw;
		if ( $page < 1 || $per_page < 1 || $per_page > self::MAX_PER_PAGE || $page * $per_page > self::MAX_WINDOW ) {
			return self::error( 'spdb_inventory_window_invalid', 'The requested inventory page is outside the bounded query window.' );
		}

		$search_raw = self::scalar_string( $input['search'] ?? '', 'spdb_inventory_search_invalid' );
		if ( is_wp_error( $search_raw ) ) {
			return $search_raw;
		}
		$search = trim( sanitize_text_field( $search_raw ) );
		if ( strlen( $search ) > 100 || self::contains_sensitive_pattern( $search ) ) {
			return self::error( 'spdb_inventory_search_invalid', 'Inventory search must be short and must not contain contact details or URLs.' );
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
			$raw = self::scalar_string( $input[ $field ] ?? '', 'spdb_inventory_filter_invalid' );
			if ( is_wp_error( $raw ) ) {
				return $raw;
			}
			$value = self::canonical_key( $raw, true );
			if ( is_wp_error( $value ) ) {
				return self::error( 'spdb_inventory_filter_invalid', 'An inventory filter value is invalid or was not already canonical.' );
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
			return self::error( 'spdb_inventory_date_range_invalid', 'The inventory start date must not be later than the end date.' );
		}

		$sort_raw = self::scalar_string( $input['sort'] ?? 'modified_at', 'spdb_inventory_filter_invalid' );
		if ( is_wp_error( $sort_raw ) ) {
			return $sort_raw;
		}
		$sort = self::canonical_key( $sort_raw, false );
		if ( is_wp_error( $sort ) || ! in_array( $sort, array( 'modified_at', 'created_at', 'scheduled_at', 'published_at', 'title' ), true ) ) {
			return self::error( 'spdb_inventory_sort_invalid', 'The inventory sort value is invalid.' );
		}

		$direction_raw = self::scalar_string( $input['direction'] ?? 'desc', 'spdb_inventory_filter_invalid' );
		if ( is_wp_error( $direction_raw ) ) {
			return $direction_raw;
		}
		$direction = self::canonical_key( $direction_raw, false );
		if ( is_wp_error( $direction ) || ! in_array( $direction, array( 'asc', 'desc' ), true ) ) {
			return self::error( 'spdb_inventory_direction_invalid', 'The inventory sort direction is invalid.' );
		}

		$scope_raw = self::scalar_string( $input['scope'] ?? 'own', 'spdb_inventory_filter_invalid' );
		if ( is_wp_error( $scope_raw ) ) {
			return $scope_raw;
		}
		$scope = self::canonical_key( $scope_raw, false );
		if ( is_wp_error( $scope ) || ! in_array( $scope, array( 'own', 'institution' ), true ) ) {
			return self::error( 'spdb_inventory_scope_invalid', 'The inventory scope is invalid.' );
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
	 * Return only client-safe normalized query fields.
	 *
	 * @param array<string,mixed> $query Internal normalized query.
	 * @return array<string,mixed>
	 */
	public static function public_projection( array $query ): array {
		$allowed = array( 'page', 'per_page', 'search', 'providers', 'object_types', 'date_from', 'date_to', 'sort', 'direction', 'scope', 'lifecycle_state', 'review_state', 'visibility_state', 'operational_state', 'language', 'topic' );
		return array_intersect_key( $query, array_fill_keys( $allowed, true ) );
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
		if ( is_object( $value ) || is_resource( $value ) ) {
			return self::error( 'spdb_inventory_' . $label . '_invalid', 'An inventory filter value has an invalid shape.' );
		}

		$values = is_array( $value ) ? $value : preg_split( '/\s*,\s*/', (string) $value );
		if ( ! is_array( $values ) || count( $values ) > $limit ) {
			return self::error( 'spdb_inventory_' . $label . '_list_invalid', 'An inventory filter contains too many values.' );
		}

		$clean = array();
		foreach ( $values as $item ) {
			if ( is_array( $item ) || is_object( $item ) || is_resource( $item ) ) {
				return self::error( 'spdb_inventory_' . $label . '_invalid', 'An inventory filter value has an invalid shape.' );
			}
			$key = self::canonical_key( trim( (string) $item ), false );
			if ( is_wp_error( $key ) ) {
				return self::error( 'spdb_inventory_' . $label . '_invalid', 'An inventory filter key is not already canonical.' );
			}
			$clean[] = $key;
		}

		return array_values( array_unique( array_filter( $clean, static fn( string $key ): bool => '' !== $key ) ) );
	}

	/**
	 * @param mixed $value Raw date.
	 * @return string|WP_Error
	 */
	private static function normalize_date( $value ) {
		$raw = self::scalar_string( $value, 'spdb_inventory_date_invalid' );
		if ( is_wp_error( $raw ) ) {
			return $raw;
		}
		$value = trim( sanitize_text_field( $raw ) );
		if ( '' === $value ) {
			return '';
		}
		if ( 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts ) || ! checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ) {
			return self::error( 'spdb_inventory_date_invalid', 'Inventory dates must use valid YYYY-MM-DD values.' );
		}
		return $value;
	}

	/**
	 * @param string $raw Raw key.
	 * @param bool   $allow_empty Whether an empty value is accepted.
	 * @return string|WP_Error
	 */
	private static function canonical_key( string $raw, bool $allow_empty ) {
		if ( '' === $raw && $allow_empty ) {
			return '';
		}
		$sanitized = sanitize_key( $raw );
		if ( $sanitized !== $raw || 1 !== preg_match( '/^[a-z0-9][a-z0-9_-]{0,63}$/', $raw ) ) {
			return self::error( 'spdb_inventory_key_invalid', 'An inventory key is not canonical.' );
		}
		return $raw;
	}

	/**
	 * @param mixed  $value Raw scalar.
	 * @param string $code Error code.
	 * @return string|WP_Error
	 */
	private static function scalar_string( $value, string $code ) {
		if ( is_array( $value ) || is_object( $value ) || is_resource( $value ) ) {
			return self::error( $code, 'An inventory query value has an invalid shape.' );
		}
		return (string) $value;
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

	private static function error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => 400 ) );
	}
}
