<?php
/**
 * Main File 23 plugin service container.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

require_once SPDB_PLUGIN_DIR . 'includes/interface-spdb-provider-adapter.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-adapter-registry.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-capabilities.php';

final class SPDB_Plugin {
	private static ?SPDB_Plugin $instance = null;

	private SPDB_Adapter_Registry $adapter_registry;

	private bool $booted = false;

	private function __construct() {
		$this->adapter_registry = new SPDB_Adapter_Registry();
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
		/**
		 * Register File 23 provider adapters.
		 *
		 * Providers receive the guarded registry instance. Registration does not
		 * grant write permission; maturity, current user capabilities, ownership,
		 * provider policy, and current object version are still required.
		 *
		 * @param SPDB_Adapter_Registry $registry Adapter registry.
		 */
		do_action( 'spdb/register_adapters', $this->adapter_registry );
	}

	public function registry(): SPDB_Adapter_Registry {
		return $this->adapter_registry;
	}

	/**
	 * @param string[] $capabilities Existing capability list.
	 * @return string[]
	 */
	public function filter_capabilities( array $capabilities ): array {
		return array_values( array_unique( array_merge( $capabilities, SPDB_Capabilities::all() ) ) );
	}
}
