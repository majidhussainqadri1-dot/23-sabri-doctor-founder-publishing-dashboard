<?php
/**
 * File 23-owned personal saved views.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Saved_Views {
	private const META_KEY  = 'spdb_saved_views_v1';
	private const MAX_VIEWS = 25;
	private ?SPDB_Operations_Repository $repository;

	public function __construct( ?SPDB_Operations_Repository $repository = null ) {
		$this->repository = $repository;
	}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		register_rest_route(
			'spdb/v1',
			'/saved-views',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'rest_list' ),
					'permission_callback' => array( $this, 'read_permission_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'rest_create' ),
					'permission_callback' => array( $this, 'write_permission_check' ),
				),
			)
		);

		register_rest_route(
			'spdb/v1',
			'/saved-views/(?P<id>[a-z0-9_-]{8,64})',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'rest_delete' ),
				'permission_callback' => array( $this, 'write_permission_check' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => array( $this, 'validate_view_id' ),
					),
				),
			)
		);
	}

	/**
	 * @param mixed $value Candidate route value.
	 */
	public function validate_view_id( $value ): bool {
		return is_string( $value ) && 1 === preg_match( '/^view_[a-z0-9]{32}$/', $value );
	}

	/**
	 * Restricted accounts may list their existing personal preferences.
	 *
	 * @return true|WP_Error
	 */
	public function read_permission_check() {
		$user_id = get_current_user_id();
		if (
			$user_id < 1
			|| ! SPDB_Membership_Guard::can_user_view_restricted_dashboard( $user_id )
			|| ! SPDB_Capabilities::current_user_can( 'spdb_view_dashboard' )
		) {
			return new WP_Error(
				'spdb_saved_views_forbidden',
				__( 'You are not authorized to access dashboard saved views.', 'sabri-publishing-dashboard' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Personal preference mutation requires an approved or verified account.
	 *
	 * @return true|WP_Error
	 */
	public function write_permission_check() {
		$read_permission = $this->read_permission_check();
		if ( is_wp_error( $read_permission ) ) {
			return $read_permission;
		}

		if ( ! SPDB_Membership_Guard::current_user_is_approved() ) {
			return new WP_Error(
				'spdb_saved_views_read_only',
				__( 'This dashboard workspace is read-only.', 'sabri-publishing-dashboard' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	public function rest_list() {
		return rest_ensure_response( array( 'items' => $this->get_for_user( get_current_user_id() ) ) );
	}

	public function rest_create( WP_REST_Request $request ) {
		$definition = self::normalize_definition( $request->get_json_params() );
		if ( is_wp_error( $definition ) ) {
			return $definition;
		}
		if ( null !== $this->repository ) {
			$result = $this->repository->create_saved_view( get_current_user_id(), $definition['label'], $definition['filters'] );
			return is_wp_error( $result ) ? $result : new WP_REST_Response( $result, 201 );
		}

		$user_id = get_current_user_id();
		$raw     = get_user_meta( $user_id, self::META_KEY, true );
		$views   = $this->normalize_stored_views( $raw );
		if ( count( $views ) >= self::MAX_VIEWS ) {
			return new WP_Error( 'spdb_saved_view_limit', __( 'The saved-view limit has been reached.', 'sabri-publishing-dashboard' ), array( 'status' => 409 ) );
		}
		$view = array( 'id' => 'view_' . str_replace( '-', '', wp_generate_uuid4() ), 'label' => $definition['label'], 'filters' => $definition['filters'], 'created_at' => current_time( 'mysql', true ), 'version' => 1 );
		$views[] = $view;
		$write = $this->compare_and_store( $user_id, $raw, $views );
		return is_wp_error( $write ) ? $write : new WP_REST_Response( $view, 201 );
	}

	public function rest_delete( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$id      = (string) $request['id'];
		if ( null !== $this->repository ) {
			$result = $this->repository->delete_saved_view( $user_id, $id );
			return is_wp_error( $result ) ? $result : rest_ensure_response( array( 'deleted' => true, 'id' => $id ) );
		}

		$raw     = get_user_meta( $user_id, self::META_KEY, true );
		$views   = $this->normalize_stored_views( $raw );
		$kept    = array();
		$deleted = false;
		foreach ( $views as $view ) {
			if ( hash_equals( (string) $view['id'], $id ) ) { $deleted = true; continue; }
			$kept[] = $view;
		}
		if ( ! $deleted ) {
			return new WP_Error( 'spdb_saved_view_not_found', __( 'The saved view was not found.', 'sabri-publishing-dashboard' ), array( 'status' => 404 ) );
		}
		$write = $this->compare_and_store( $user_id, $raw, $kept );
		return is_wp_error( $write ) ? $write : rest_ensure_response( array( 'deleted' => true, 'id' => $id ) );
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_for_user( int $user_id ): array {
		if ( $user_id < 1 || $user_id !== get_current_user_id() ) {
			return array();
		}
		if ( null === $this->repository ) {
			return $this->normalize_stored_views( get_user_meta( $user_id, self::META_KEY, true ) );
		}

		$rows = $this->repository->list_saved_views( $user_id );
		if ( ! empty( $rows ) ) {
			return $rows;
		}

		$legacy = $this->normalize_stored_views( get_user_meta( $user_id, self::META_KEY, true ) );
		if ( empty( $legacy ) ) {
			return array();
		}
		$migrated = array();
		foreach ( $legacy as $view ) {
			$result = $this->repository->create_saved_view( $user_id, $view['label'], $view['filters'] );
			if ( is_array( $result ) ) { $migrated[] = $result; }
		}
		if ( count( $migrated ) === count( $legacy ) ) {
			delete_user_meta( $user_id, self::META_KEY );
		}
		return $migrated;
	}

	/**
	 * @param mixed $raw Stored user-meta value.
	 * @return array<int,array<string,mixed>>
	 */
	private function normalize_stored_views( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$validated = array();
		foreach ( array_slice( $raw, 0, self::MAX_VIEWS ) as $view ) {
			if ( ! is_array( $view ) || empty( $view['id'] ) || empty( $view['label'] ) || ! isset( $view['filters'] ) || ! is_array( $view['filters'] ) ) {
				continue;
			}

			$id = sanitize_key( (string) $view['id'] );
			if ( ! $this->validate_view_id( $id ) ) {
				continue;
			}

			$definition = self::normalize_definition(
				array(
					'label'   => $view['label'],
					'filters' => $view['filters'],
				)
			);
			if ( is_wp_error( $definition ) ) {
				continue;
			}

			$validated[] = array(
				'id'         => $id,
				'label'      => $definition['label'],
				'filters'    => $definition['filters'],
				'created_at' => isset( $view['created_at'] ) && is_scalar( $view['created_at'] ) ? sanitize_text_field( (string) $view['created_at'] ) : '',
				'version'    => isset( $view['version'] ) ? min( 2147483647, max( 1, (int) $view['version'] ) ) : 1,
			);
		}

		return $validated;
	}

	/**
	 * Compare-and-store prevents concurrent requests from silently overwriting
	 * one another's saved-view changes.
	 *
	 * @param mixed                            $previous_raw Exact prior meta value.
	 * @param array<int,array<string,mixed>> $new_value    Validated replacement.
	 * @return true|WP_Error
	 */
	private function compare_and_store( int $user_id, $previous_raw, array $new_value ) {
		if ( update_user_meta( $user_id, self::META_KEY, $new_value, $previous_raw ) ) {
			return true;
		}

		$current = get_user_meta( $user_id, self::META_KEY, true );
		if ( $current !== $previous_raw ) {
			return new WP_Error(
				'spdb_saved_view_conflict',
				__( 'Saved views changed in another request. Refresh and try again.', 'sabri-publishing-dashboard' ),
				array( 'status' => 409 )
			);
		}

		return new WP_Error(
			'spdb_saved_view_write_failed',
			__( 'The saved-view change could not be stored.', 'sabri-publishing-dashboard' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Normalize a deliberately narrow, non-clinical filter definition.
	 *
	 * @param mixed $input Raw request input.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function normalize_definition( $input ) {
		if ( ! is_array( $input ) ) {
			return new WP_Error( 'spdb_invalid_saved_view', __( 'The saved-view payload is invalid.', 'sabri-publishing-dashboard' ) );
		}

		if ( isset( $input['label'] ) && ! is_scalar( $input['label'] ) ) {
			return new WP_Error( 'spdb_invalid_saved_view_label', __( 'The saved-view label is invalid.', 'sabri-publishing-dashboard' ) );
		}

		$label        = isset( $input['label'] ) ? trim( sanitize_text_field( (string) $input['label'] ) ) : '';
		$label_length = function_exists( 'mb_strlen' ) ? mb_strlen( $label ) : strlen( $label );
		if ( '' === $label || $label_length > 80 ) {
			return new WP_Error( 'spdb_invalid_saved_view_label', __( 'The saved-view label must contain 1 to 80 characters.', 'sabri-publishing-dashboard' ) );
		}

		if ( self::contains_sensitive_pattern( $label ) ) {
			return new WP_Error( 'spdb_sensitive_saved_view_label', __( 'Saved-view labels must not contain contact details, URLs, or sensitive identifiers.', 'sabri-publishing-dashboard' ) );
		}

		if ( isset( $input['filters'] ) && ! is_array( $input['filters'] ) ) {
			return new WP_Error( 'spdb_invalid_saved_view_filter', __( 'Saved-view filters must be an object.', 'sabri-publishing-dashboard' ) );
		}

		$filters = $input['filters'] ?? array();
		$allowed = array( 'status', 'type', 'provider', 'language', 'sort', 'direction', 'date_from', 'date_to' );
		$multi   = array( 'status', 'type', 'provider', 'language' );
		$clean   = array();

		foreach ( $filters as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( ! in_array( $key, $allowed, true ) ) {
				continue;
			}

			if ( is_array( $value ) ) {
				if ( ! in_array( $key, $multi, true ) || count( $value ) > 20 ) {
					return new WP_Error( 'spdb_invalid_saved_view_filter', __( 'A saved-view filter has an invalid value shape.', 'sabri-publishing-dashboard' ) );
				}

				$values = array();
				foreach ( $value as $entry ) {
					if ( ! is_scalar( $entry ) && null !== $entry ) {
						return new WP_Error( 'spdb_invalid_saved_view_filter', __( 'A saved-view filter contains a non-scalar value.', 'sabri-publishing-dashboard' ) );
					}
					$normalized = self::normalize_filter_value( $key, $entry );
					if ( is_wp_error( $normalized ) ) {
						return $normalized;
					}
					$values[] = $normalized;
				}
				$values = array_values( array_unique( array_filter( $values, 'strlen' ) ) );
				if ( array() !== $values ) {
					$clean[ $key ] = $values;
				}
				continue;
			}

			if ( ! is_scalar( $value ) && null !== $value ) {
				return new WP_Error( 'spdb_invalid_saved_view_filter', __( 'A saved-view filter contains a non-scalar value.', 'sabri-publishing-dashboard' ) );
			}

			$normalized = self::normalize_filter_value( $key, $value );
			if ( is_wp_error( $normalized ) ) {
				return $normalized;
			}
			if ( '' !== $normalized ) {
				$clean[ $key ] = $normalized;
			}
		}

		return array( 'label' => $label, 'filters' => $clean );
	}

	/**
	 * @param mixed $value Raw filter value.
	 * @return string|WP_Error
	 */
	private static function normalize_filter_value( string $key, $value ) {
		$value = trim( sanitize_text_field( (string) $value ) );
		if ( '' === $value ) {
			return '';
		}

		if ( in_array( $key, array( 'date_from', 'date_to' ), true ) ) {
			if ( 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts ) || ! checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ) {
				return new WP_Error( 'spdb_invalid_saved_view_date', __( 'The saved-view date must use a valid YYYY-MM-DD value.', 'sabri-publishing-dashboard' ) );
			}
			return $value;
		}

		if ( self::contains_sensitive_pattern( $value ) ) {
			return new WP_Error( 'spdb_sensitive_saved_view_filter', __( 'Saved-view filters must not contain contact details, URLs, or sensitive identifiers.', 'sabri-publishing-dashboard' ) );
		}

		if ( in_array( $key, array( 'status', 'type', 'provider', 'language' ), true ) ) {
			$value = sanitize_key( $value );
			if ( 1 !== preg_match( '/^[a-z0-9][a-z0-9_-]{0,63}$/', $value ) ) {
				return new WP_Error( 'spdb_invalid_saved_view_filter_key', __( 'A saved-view filter value is not a canonical key.', 'sabri-publishing-dashboard' ) );
			}
			return $value;
		}

		if ( 'sort' === $key ) {
			$value = sanitize_key( $value );
			return in_array( $value, array( 'modified', 'created', 'title' ), true )
				? $value
				: new WP_Error( 'spdb_invalid_saved_view_sort', __( 'The saved-view sort value is invalid.', 'sabri-publishing-dashboard' ) );
		}

		if ( 'direction' === $key ) {
			$value = sanitize_key( $value );
			return in_array( $value, array( 'asc', 'desc' ), true )
				? $value
				: new WP_Error( 'spdb_invalid_saved_view_direction', __( 'The saved-view direction is invalid.', 'sabri-publishing-dashboard' ) );
		}

		return new WP_Error( 'spdb_invalid_saved_view_filter', __( 'The saved-view filter is invalid.', 'sabri-publishing-dashboard' ) );
	}

	private static function contains_sensitive_pattern( string $value ): bool {
		return 1 === preg_match(
			'~(?:https?://|www\.|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}|\+?\d[\d\s().-]{6,}\d)~i',
			$value
		);
	}
}
