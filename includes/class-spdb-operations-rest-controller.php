<?php
/**
 * Private REST controller for the complete File 23 operational surfaces.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Operations_REST_Controller {
	private const NAMESPACE = 'spdb/v1';

	private SPDB_Operations_Service $operations;
	private SPDB_Governance_Service $governance;
	private SPDB_Export_Service $exports;
	private SPDB_Operations_Repository $repository;
	private SPDB_Local_Repair $repair;
	private SPDB_Activation_Wizard $activation;
	private SPDB_Adapter_Acceptance $adapter_acceptance;

	public function __construct(
		SPDB_Operations_Service $operations,
		SPDB_Governance_Service $governance,
		SPDB_Export_Service $exports,
		SPDB_Operations_Repository $repository,
		SPDB_Local_Repair $repair,
		SPDB_Activation_Wizard $activation,
		SPDB_Adapter_Acceptance $adapter_acceptance
	) {
		$this->operations = $operations;
		$this->governance = $governance;
		$this->exports    = $exports;
		$this->repository = $repository;
		$this->repair     = $repair;
		$this->activation = $activation;
		$this->adapter_acceptance = $adapter_acceptance;
	}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/operations/(?P<domain>sources|media|interactions|gaps|revisions|notifications)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_projections' ),
				'permission_callback' => array( $this, 'read_permission' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/analytics',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_analytics' ),
				'permission_callback' => array( $this, 'read_permission' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/tasks',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_tasks' ),
					'permission_callback' => array( $this, 'read_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_task' ),
					'permission_callback' => array( $this, 'write_permission' ),
				),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/tasks/(?P<id>task_[a-z0-9]{32})',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_task' ),
				'permission_callback' => array( $this, 'write_permission' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/delegations',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_delegations' ),
					'permission_callback' => array( $this, 'read_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_delegation' ),
					'permission_callback' => array( $this, 'write_permission' ),
				),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/delegations/(?P<id>delegation_[a-z0-9]{32})/revoke',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'revoke_delegation' ),
				'permission_callback' => array( $this, 'write_permission' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/automation-rules',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_rules' ),
					'permission_callback' => array( $this, 'read_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_rule' ),
					'permission_callback' => array( $this, 'write_permission' ),
				),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/automation-rules/(?P<id>rule_[a-z0-9]{32})/status',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_rule_status' ),
				'permission_callback' => array( $this, 'write_permission' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/exports',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_exports' ),
					'permission_callback' => array( $this, 'read_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_export' ),
					'permission_callback' => array( $this, 'write_permission' ),
				),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/ai-assistance',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'request_ai' ),
				'permission_callback' => array( $this, 'write_permission' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/preferences',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_preferences' ),
					'permission_callback' => array( $this, 'read_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_preferences' ),
					'permission_callback' => array( $this, 'write_permission' ),
				),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'settings_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'settings_permission' ),
				),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/legacy-migration',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_legacy_migration' ),
				'permission_callback' => array( $this, 'system_permission' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/system-check',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_system_check' ),
				'permission_callback' => array( $this, 'system_permission' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/system-check/repair',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'run_repair' ),
				'permission_callback' => array( $this, 'system_permission' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/provider-acceptance/(?P<provider_key>[a-z0-9][a-z0-9_-]{1,63})',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'record_provider_acceptance' ),
				'permission_callback' => array( $this, 'settings_permission' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/activation',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_activation' ),
					'permission_callback' => array( $this, 'system_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'record_activation' ),
					'permission_callback' => array( $this, 'settings_permission' ),
				),
			)
		);
	}

	/** @return true|WP_Error */
	public function read_permission() {
		$user_id = get_current_user_id();
		if ( $user_id < 1 || ! SPDB_Membership_Guard::can_user_view_restricted_dashboard( $user_id ) || ! SPDB_Capabilities::current_user_can( 'spdb_view_dashboard' ) ) {
			return self::forbidden();
		}
		return true;
	}

	/** @return true|WP_Error */
	public function write_permission() {
		$read = $this->read_permission();
		if ( is_wp_error( $read ) ) {
			return $read;
		}
		return SPDB_Membership_Guard::current_user_is_approved()
			? true
			: new WP_Error( 'spdb_workspace_read_only', __( 'The current dashboard workspace is read-only.', 'sabri-publishing-dashboard' ), array( 'status' => 403 ) );
	}

	/** @return true|WP_Error */
	public function settings_permission() {
		$write = $this->write_permission();
		if ( is_wp_error( $write ) ) {
			return $write;
		}
		return SPDB_Capabilities::current_user_can( 'spdb_manage_dashboard_settings' ) ? true : self::forbidden();
	}

	/** @return true|WP_Error */
	public function system_permission() {
		$read = $this->read_permission();
		if ( is_wp_error( $read ) ) {
			return $read;
		}
		return SPDB_Capabilities::current_user_can( 'spdb_run_system_check' ) ? true : self::forbidden();
	}

	public function get_projections( WP_REST_Request $request ) {
		return rest_ensure_response( $this->operations->projections( (string) $request['domain'], $request->get_params() ) );
	}

	public function get_analytics( WP_REST_Request $request ) {
		return rest_ensure_response( $this->operations->analytics( $request->get_params() ) );
	}

	public function get_tasks( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$page     = max( 1, (int) ( $request->get_param( 'page' ) ?: 1 ) );
		$per_page = min( 100, max( 1, (int) ( $request->get_param( 'per_page' ) ?: 25 ) ) );
		$result   = $this->repository->list_tasks( $user_id, $this->governance->is_institutional( $user_id ), $page, $per_page );
		return is_wp_error( $result ) ? $result : rest_ensure_response( array( 'items' => $result, 'page' => $page, 'per_page' => $per_page ) );
	}

	public function create_task( WP_REST_Request $request ) {
		$result = $this->governance->create_task( $this->json( $request ) );
		return is_wp_error( $result ) ? $result : new WP_REST_Response( $result, 201 );
	}

	public function update_task( WP_REST_Request $request ) {
		return rest_ensure_response( $this->governance->update_task( (string) $request['id'], $this->json( $request ) ) );
	}

	public function get_delegations() {
		$user_id = get_current_user_id();
		$result  = $this->repository->list_delegations( $user_id, $this->governance->is_institutional( $user_id ) );
		return is_wp_error( $result ) ? $result : rest_ensure_response( array( 'items' => $result ) );
	}

	public function create_delegation( WP_REST_Request $request ) {
		$result = $this->governance->create_delegation( $this->json( $request ) );
		return is_wp_error( $result ) ? $result : new WP_REST_Response( $result, 201 );
	}

	public function revoke_delegation( WP_REST_Request $request ) {
		return rest_ensure_response( $this->governance->revoke_delegation( (string) $request['id'], $this->json( $request ) ) );
	}

	public function get_rules() {
		$user_id = get_current_user_id();
		$result  = $this->repository->list_rules( $user_id, $this->governance->is_institutional( $user_id ) );
		return is_wp_error( $result ) ? $result : rest_ensure_response( array( 'items' => $result ) );
	}

	public function create_rule( WP_REST_Request $request ) {
		$result = $this->governance->create_rule( $this->json( $request ) );
		return is_wp_error( $result ) ? $result : new WP_REST_Response( $result, 201 );
	}

	public function update_rule_status( WP_REST_Request $request ) {
		return rest_ensure_response( $this->governance->update_rule_status( (string) $request['id'], $this->json( $request ) ) );
	}

	public function get_exports() {
		$result = $this->exports->list_exports();
		return is_wp_error( $result ) ? $result : rest_ensure_response( array( 'items' => $result ) );
	}

	public function create_export( WP_REST_Request $request ) {
		$result = $this->exports->request_export( $this->json( $request ) );
		return is_wp_error( $result ) ? $result : new WP_REST_Response( $result, 202 );
	}

	public function request_ai( WP_REST_Request $request ) {
		return rest_ensure_response( $this->operations->ai_assistance( $this->json( $request ) ) );
	}

	public function get_preferences() {
		return rest_ensure_response( $this->repository->get_preferences( get_current_user_id() ) );
	}

	public function update_preferences( WP_REST_Request $request ) {
		$input       = $this->json( $request );
		$preferences = isset( $input['preferences'] ) && is_array( $input['preferences'] ) ? $this->sanitize_preferences( $input['preferences'] ) : array();
		return rest_ensure_response( $this->repository->update_preferences( get_current_user_id(), max( 0, (int) ( $input['version'] ?? 0 ) ), $preferences ) );
	}

	public function get_settings() {
		return rest_ensure_response( SPDB_Admin_Settings::get() );
	}

	public function update_settings( WP_REST_Request $request ) {
		return rest_ensure_response( SPDB_Admin_Settings::update( $this->json( $request ) ) );
	}

	public function get_legacy_migration() {
		return rest_ensure_response( SPDB_Legacy_Migration_Diagnostics::snapshot() );
	}

	public function get_system_check() {
		return rest_ensure_response( $this->repair->system_check() );
	}

	public function run_repair( WP_REST_Request $request ) {
		$input = $this->json( $request );
		return rest_ensure_response( $this->repair->repair( sanitize_key( (string) ( $input['action'] ?? '' ) ), (string) ( $input['reason'] ?? '' ) ) );
	}

	public function get_activation() {
		return rest_ensure_response( $this->activation->state() );
	}

	public function record_activation( WP_REST_Request $request ) {
		return rest_ensure_response( $this->activation->record_acceptance( $this->json( $request ) ) );
	}

	public function record_provider_acceptance( WP_REST_Request $request ) {
		return rest_ensure_response( $this->adapter_acceptance->record( (string) $request['provider_key'], $this->json( $request ) ) );
	}

	/** @return array<string,mixed> */
	private function json( WP_REST_Request $request ): array {
		$input = $request->get_json_params();
		return is_array( $input ) ? $input : array();
	}

	/** @param array<string,mixed> $input @return array<string,mixed> */
	private function sanitize_preferences( array $input ): array {
		$out = array();
		$allowed = array( 'density', 'default_view', 'calendar_view', 'reduced_motion', 'high_contrast', 'rtl_preference' );
		foreach ( $allowed as $key ) {
			if ( ! array_key_exists( $key, $input ) ) {
				continue;
			}
			$value = $input[ $key ];
			if ( is_bool( $value ) ) {
				$out[ $key ] = $value;
			} elseif ( is_scalar( $value ) ) {
				$out[ $key ] = substr( sanitize_text_field( (string) $value ), 0, 64 );
			}
		}
		return $out;
	}

	private static function forbidden(): WP_Error {
		return new WP_Error( 'spdb_rest_forbidden', __( 'You are not authorized to access this private dashboard resource.', 'sabri-publishing-dashboard' ), array( 'status' => 403 ) );
	}
}
