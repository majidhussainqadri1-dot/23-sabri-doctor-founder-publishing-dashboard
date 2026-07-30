<?php
/**
 * Main File 23 plugin service container.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

require_once SPDB_PLUGIN_DIR . 'includes/interface-spdb-provider-adapter.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-adapter-registry.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-membership-guard.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-capabilities.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-operation-broker.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-dashboard-router.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-workspace-resolver.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-saved-views.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-system-state.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-overview-service.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-dashboard-page.php';

final class SPDB_Plugin {
	private static ?SPDB_Plugin $instance = null;

	private SPDB_Adapter_Registry $adapter_registry;
	private SPDB_Operation_Broker $operation_broker;
	private SPDB_Workspace_Resolver $workspace_resolver;
	private SPDB_Saved_Views $saved_views;
	private SPDB_System_State $system_state;
	private SPDB_Overview_Service $overview_service;
	private SPDB_Dashboard_Page $dashboard_page;
	private SPDB_Dashboard_Router $dashboard_router;
	private bool $booted = false;

	private function __construct() {
		// Provider acceptance is still not persisted in Phase 23B. All providers
		// remain write-ineligible until a later reviewed governance phase.
		$this->adapter_registry   = new SPDB_Adapter_Registry();
		$this->operation_broker   = new SPDB_Operation_Broker( $this->adapter_registry );
		$this->workspace_resolver = new SPDB_Workspace_Resolver();
		$this->saved_views        = new SPDB_Saved_Views();
		$this->system_state       = new SPDB_System_State( $this->adapter_registry );
		$this->overview_service   = new SPDB_Overview_Service( $this->system_state );
		$this->dashboard_page     = new SPDB_Dashboard_Page(
			$this->workspace_resolver,
			$this->overview_service,
			$this->system_state,
			$this->saved_views
		);
		$this->dashboard_router   = new SPDB_Dashboard_Router( array( $this->dashboard_page, 'render' ) );
	}

	public static function instance(): SPDB_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		$this->dashboard_router->register();
		$this->dashboard_page->register();
		$this->saved_views->register();

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'plugins_loaded', array( $this, 'register_provider_adapters' ), 30 );
		add_action( 'admin_notices', array( $this, 'render_dependency_notice' ) );
		add_filter( 'spdb/capabilities', array( $this, 'filter_capabilities' ) );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'sabri-publishing-dashboard',
			false,
			dirname( plugin_basename( SPDB_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Allow native providers to register versioned adapters.
	 */
	public function register_provider_adapters(): void {
		try {
			/**
			 * Register File 23 provider adapters.
			 *
			 * Providers receive the guarded registry instance. Registration never
			 * grants staging or production acceptance and never constitutes full
			 * action authorization.
			 *
			 * @param SPDB_Adapter_Registry $registry Adapter registry.
			 */
			do_action( 'spdb/register_adapters', $this->adapter_registry );
		} catch ( Throwable $throwable ) {
			$this->adapter_registry->record_error(
				'system',
				new WP_Error( 'spdb_adapter_hook_exception', __( 'A provider registration hook failed.', 'sabri-publishing-dashboard' ) )
			);
		}
	}

	/**
	 * Show a non-sensitive administrator warning when File 00 is unavailable.
	 */
	public function render_dependency_notice(): void {
		if ( SPDB_Membership_Guard::is_available() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p>'
			. esc_html__( 'Sabri Publishing Dashboard is in fail-closed mode because Sabri Membership Core is unavailable or incompatible. No privileged dashboard action is permitted.', 'sabri-publishing-dashboard' )
			. '</p></div>';
	}

	public function registry(): SPDB_Adapter_Registry {
		return $this->adapter_registry;
	}

	public function broker(): SPDB_Operation_Broker {
		return $this->operation_broker;
	}

	public function router(): SPDB_Dashboard_Router {
		return $this->dashboard_router;
	}

	/**
	 * @param string[] $capabilities Existing capability list.
	 * @return string[]
	 */
	public function filter_capabilities( array $capabilities ): array {
		return array_values( array_unique( array_merge( $capabilities, SPDB_Capabilities::all() ) ) );
	}
}
