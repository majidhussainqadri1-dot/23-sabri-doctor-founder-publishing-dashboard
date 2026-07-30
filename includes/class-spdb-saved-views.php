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
					),
				),
			)
		);
	}

	/**
	 * Restricted accounts may list their existing personal preferences, but the
	 * workspace remains read-only.
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
	 * Personal preference mutation requires an approved or verified File 00
	 * account. Pending, rejected, expired, appeal-review, and suspended accounts
	 * remain strictly read-only.
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

		$user_id = get_current_user_id();
		$views   = $this->get_for_user( $user_id );
		if ( count( $views ) >= self::MAX_VIEWS ) {
			return new WP_Error(
				'spdb_saved_view_limit',
				__( 'The saved-view limit has been reached.', 'sabri-publishing-dashboard' ),
				array( 'status' => 409 )
			);
		}

		$view = array(
			'id'         => 'view_' . str_replace( '-', '', wp_generate_uuid4() ),
			'label'      => $definition['label'],
			'filters'    => $definition['filters'],
			'created_at' => current_time( 'mysql', true ),
			'version'    => 1,
		);
		$views[] = $view;

		if ( ! update_user_meta( $user_id, self::META_KEY, $views ) ) {
			return new WP_Error(
				'spdb_saved_view_write_failed',
				__( 'The saved view could not be stored.', 'sabri-publishing-dashboard' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response( $view, 201 );
	}

	public function rest_delete( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$id      = sanitize_key( (string) $request['id'] );
		$views   = $this->get_for_user( $user_id );
		$kept    = array();
		$deleted = false;

		foreach ( $views as $view ) {
			if ( isset( $view['id'] ) && hash_equals( (string) $view['id'], $id ) ) {
				$deleted = true;
				continue;
			}
			$kept[] = $view;
		}

		if ( ! $deleted ) {
			return new WP_Error(
				'spdb_saved_view_not_found',
				__( 'The saved view was not found.', 'sabri-publishing-dashboard' ),
				array( 'status' => 404 )
			);
		}

		if ( ! update_user_meta( $user_id, self::META_KEY, $kept ) ) {
			return new WP_Error(
				'spdb_saved_view_delete_failed',
				__( 'The saved view could not be deleted.', 'sabri-publishing-dashboard' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response( array( 'deleted' => true, 'id' => $id ) );
	}

	/**
	 * Read File 23-owned preferences through the same validation boundary used
	 * for incoming requests. Direct database tampering must not bypass the
	 * non-clinical allowlist when values are projected to the browser or REST.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_for_user( int $user_id ): array {
		$views = get_user_meta( $user_id, self::META_KEY, true );
		if ( ! is_array( $views ) ) {
			return array();
		}

		$validated = array();
		foreach ( array_slice( $views, 0, self::MAX_VIEWS ) as $view ) {
			if ( ! is_array( $view ) || empty( $view['id'] ) || empty( $view['label'] ) || ! isset( $view['filters'] ) || ! is_array( $view['filters'] ) ) {
				continue;
			}

			$id = sanitize_key( (string) $view['id'] );
			if ( 1 !== preg_match( '/^view_[a-z0-9]{32}$/', $id ) ) {
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
				'created_at' => isset( $view['created_at'] ) ? sanitize_text_field( (string) $view['created_at'] ) : '',
				'version'    => isset( $view['version'] ) ? max( 1, (int) $view['version'] ) : 1,
			);
		}

		return $validated;
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

		$label = isset( $input['label'] ) ? trim( sanitize_text_field( (string) $input['label'] ) ) : '';
		if ( '' === $label || strlen( $label ) > 80 ) {
			return new WP_Error( 'spdb_invalid_saved_view_label', __( 'The saved-view label must contain 1 to 80 characters.', 'sabri-publishing-dashboard' ) );
		}

		$filters = isset( $input['filters'] ) && is_array( $input['filters'] ) ? $input['filters'] : array();
		$allowed = array( 'status', 'type', 'provider', 'language', 'sort', 'direction', 'date_from', 'date_to' );
		$clean   = array();

		foreach ( $filters as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( ! in_array( $key, $allowed, true ) ) {
				continue;
			}

			if ( is_array( $value ) ) {
				$value = array_slice( $value, 0, 20 );
				$value = array_values( array_filter( array_map( array( __CLASS__, 'sanitize_filter_value' ), $value ), 'strlen' ) );
			} else {
				$value = self::sanitize_filter_value( $value );
			}

			if ( array() !== $value && '' !== $value ) {
				$clean[ $key ] = $value;
			}
		}

		return array( 'label' => $label, 'filters' => $clean );
	}

	/**
	 * @param mixed $value Raw filter value.
	 */
	public static function sanitize_filter_value( $value ): string {
		$value = sanitize_text_field( (string) $value );
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, 100 ) : substr( $value, 0, 100 );
	}
}
