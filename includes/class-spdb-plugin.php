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

final class SPDB_Plugin {
	private static ?SPDB_Plugin $instance = null;

	private SPDB_Adapter_Registry $adapter_registry;

	private SPDB_Operation_Broker $operation_broker;

	private bool $booted = false;

	private function __construct() {
		// Phase 23A does not load persisted acceptance. All providers therefore
		// remain unreviewed and write-ineligible until a later approved phase.
		$this->adapter_registry = new SPDB_Adapter_Registry();
		$this->operation_broker = new SPDB_Operation_Broker( $this->adapter_registry );
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

	/**
	 * @param string[] $capabilities Existing capability list.
	 * @return string[]
	 */
	public function filter_capabilities( array $capabilities ): array {
		return array_values( array_unique( array_merge( $capabilities, SPDB_Capabilities::all() ) ) );
	}
}
