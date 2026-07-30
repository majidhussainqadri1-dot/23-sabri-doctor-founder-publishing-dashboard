<?php
/**
 * Main File 23 plugin service container.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

require_once SPDB_PLUGIN_DIR . 'includes/interface-spdb-provider-adapter.php';
require_once SPDB_PLUGIN_DIR . 'includes/interface-spdb-workspace-provider-adapter.php';
require_once SPDB_PLUGIN_DIR . 'includes/interface-spdb-review-calendar-provider-adapter.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-adapter-registry.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-membership-guard.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-capabilities.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-capability-installer.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-operation-broker.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-provider-registration.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-inventory-query.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-projection-validator.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-federated-inventory.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-inventory-rest-controller.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-safe-destination.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-workspace-projection-validator.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-role-workspace-service.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-review-calendar-validator.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-review-calendar-service.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-review-calendar-rest-controller.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-dashboard-router.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-workspace-resolver.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-saved-views.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-rest-privacy.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-system-state.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-overview-service.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-dashboard-page.php';

final class SPDB_Plugin {
	private static ?SPDB_Plugin $instance = null;

	private SPDB_Adapter_Registry $adapter_registry;
	private SPDB_Operation_Broker $operation_broker;
	private SPDB_Federated_Inventory $inventory;
	private SPDB_Inventory_REST_Controller $inventory_rest;
	private SPDB_Workspace_Resolver $workspace_resolver;
	private SPDB_Role_Workspace_Service $role_workspace_service;
	private SPDB_Review_Calendar_Service $review_calendar_service;
	private SPDB_Review_Calendar_REST_Controller $review_calendar_rest;
	private SPDB_Saved_Views $saved_views;
	private SPDB_REST_Privacy $rest_privacy;
	private SPDB_System_State $system_state;
	private SPDB_Overview_Service $overview_service;
	private SPDB_Dashboard_Page $dashboard_page;
	private SPDB_Dashboard_Router $dashboard_router;
	private bool $booted = false;

	private function __construct() {
		// Acceptance remains File 23-controlled and is never provider self-declared.
		// Until a reviewed persistence layer supplies acceptance states, production
		// mutation endpoints fail closed while read-only projections remain usable.
		$this->adapter_registry        = new SPDB_Adapter_Registry();
		$this->operation_broker        = new SPDB_Operation_Broker( $this->adapter_registry );
		$this->inventory               = new SPDB_Federated_Inventory( $this->adapter_registry );
		$this->inventory_rest          = new SPDB_Inventory_REST_Controller( $this->inventory );
		$this->workspace_resolver      = new SPDB_Workspace_Resolver();
		$this->role_workspace_service  = new SPDB_Role_Workspace_Service( $this->adapter_registry );
		$this->review_calendar_service = new SPDB_Review_Calendar_Service( $this->adapter_registry );
		$this->review_calendar_rest    = new SPDB_Review_Calendar_REST_Controller( $this->review_calendar_service, $this->operation_broker );
		$this->saved_views             = new SPDB_Saved_Views();
		$this->rest_privacy            = new SPDB_REST_Privacy();
		$this->system_state            = new SPDB_System_State( $this->adapter_registry );
		$this->overview_service        = new SPDB_Overview_Service( $this->system_state );
		$this->dashboard_page          = new SPDB_Dashboard_Page(
			$this->workspace_resolver,
			$this->overview_service,
			$this->system_state,
			$this->saved_views,
			$this->inventory,
			$this->role_workspace_service,
			$this->review_calendar_service
		);
		$this->dashboard_router = new SPDB_Dashboard_Router( array( $this->dashboard_page, 'render' ) );
	}

	public static function instance(): SPDB_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** Activation is an administrator-approved capability assignment event. */
	public static function activate(): void {
		SPDB_Capability_Installer::ensure();
		SPDB_Dashboard_Router::activate();
	}

	public static function deactivate(): void {
		SPDB_Dashboard_Router::deactivate();
	}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;
		$this->dashboard_router->register();
		$this->dashboard_page->register();
		$this->saved_views->register();
		$this->inventory_rest->register();
		$this->review_calendar_rest->register();
		$this->rest_privacy->register();
		add_action( 'init', array( 'SPDB_Capability_Installer', 'maybe_upgrade' ), 1 );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'plugins_loaded', array( $this, 'register_provider_adapters' ), 30 );
		add_action( 'admin_notices', array( $this, 'render_dependency_notice' ) );
		add_filter( 'spdb/capabilities', array( $this, 'filter_capabilities' ) );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'sabri-publishing-dashboard', false, dirname( plugin_basename( SPDB_PLUGIN_FILE ) ) . '/languages' );
	}

	/** Invoke every provider registration callback behind its own failure boundary. */
	public function register_provider_adapters(): void {
		SPDB_Provider_Registration::dispatch( $this->adapter_registry );
	}

	/** Show a non-sensitive administrator warning when File 00 is unavailable. */
	public function render_dependency_notice(): void {
		if ( SPDB_Membership_Guard::is_available() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>'
			. esc_html__( 'Sabri Publishing Dashboard is in fail-closed mode because Sabri Membership Core is unavailable or incompatible. No privileged dashboard action is permitted.', 'sabri-publishing-dashboard' )
			. '</p></div>';
	}

	public function registry(): SPDB_Adapter_Registry { return $this->adapter_registry; }
	public function broker(): SPDB_Operation_Broker { return $this->operation_broker; }
	public function inventory(): SPDB_Federated_Inventory { return $this->inventory; }
	public function role_workspace(): SPDB_Role_Workspace_Service { return $this->role_workspace_service; }
	public function review_calendar(): SPDB_Review_Calendar_Service { return $this->review_calendar_service; }
	public function router(): SPDB_Dashboard_Router { return $this->dashboard_router; }

	/**
	 * @param string[] $capabilities Existing capability list.
	 * @return string[]
	 */
	public function filter_capabilities( array $capabilities ): array {
		return array_values( array_unique( array_merge( $capabilities, SPDB_Capabilities::all() ) ) );
	}
}
