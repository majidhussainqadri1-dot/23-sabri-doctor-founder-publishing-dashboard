<?php
/** Main File 23 plugin service container. */
defined( 'ABSPATH' ) || exit;

require_once SPDB_PLUGIN_DIR . 'includes/interface-spdb-provider-adapter.php';
require_once SPDB_PLUGIN_DIR . 'includes/interface-spdb-workspace-provider-adapter.php';
require_once SPDB_PLUGIN_DIR . 'includes/interface-spdb-review-calendar-provider-adapter.php';
require_once SPDB_PLUGIN_DIR . 'includes/interface-spdb-operational-projection-provider.php';
require_once SPDB_PLUGIN_DIR . 'includes/interface-spdb-analytics-provider.php';
require_once SPDB_PLUGIN_DIR . 'includes/interface-spdb-ai-assistance-provider.php';
require_once SPDB_PLUGIN_DIR . 'includes/interface-spdb-collections-repository.php';
require_once SPDB_PLUGIN_DIR . 'includes/interface-spdb-native-reference-resolver.php';
require_once SPDB_PLUGIN_DIR . 'includes/interface-spdb-native-reference-provider.php';
require_once SPDB_PLUGIN_DIR . 'includes/interface-spdb-native-reference-readiness.php';

require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-adapter-registry.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-adapter-acceptance.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-native-reference-registry.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-native-reference-registration.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-membership-guard.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-capabilities.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-capability-installer.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-operation-broker.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-provider-registration.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-inventory-query.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-projection-validator.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-operational-projection-validator.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-federated-inventory.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-inventory-rest-controller.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-safe-destination.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-workspace-projection-validator.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-role-workspace-service.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-review-calendar-validator.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-review-calendar-service.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-review-calendar-rest-controller.php';

require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-collections-schema.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-collections-policy.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-wp-collections-repository.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-native-reference-readiness-bridge.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-native-reference-registry-readiness.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-collections-service-readiness-gate.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-collections-service-readiness-integration.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-collections-repository-readiness-probe.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-collections-service-probe-binding.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-collections-service-readiness-consumer.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-collections-service.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-collections-view.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-collections-rest-controller.php';

require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-admin-settings.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-module-manifest.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-legacy-migration-diagnostics.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-operations-schema.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-operations-repository.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-operations-service.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-governance-service.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-export-service.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-automation-engine.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-background-jobs.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-local-repair.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-activation-wizard.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-privacy-integration.php';
require_once SPDB_PLUGIN_DIR . 'includes/class-spdb-operations-rest-controller.php';

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
	private SPDB_Adapter_Acceptance $adapter_acceptance;
	private SPDB_Native_Reference_Registry $native_reference_registry;
	private SPDB_Operation_Broker $operation_broker;
	private SPDB_Federated_Inventory $inventory;
	private SPDB_Inventory_REST_Controller $inventory_rest;
	private SPDB_Workspace_Resolver $workspace_resolver;
	private SPDB_Role_Workspace_Service $role_workspace_service;
	private SPDB_Review_Calendar_Service $review_calendar_service;
	private SPDB_Review_Calendar_REST_Controller $review_calendar_rest;
	private SPDB_WP_Collections_Repository $collections_repository;
	private SPDB_Collections_Service $collections_service;
	private SPDB_Collections_REST_Controller $collections_rest;
	private SPDB_Operations_Repository $operations_repository;
	private SPDB_Operations_Service $operations_service;
	private SPDB_Governance_Service $governance_service;
	private SPDB_Export_Service $export_service;
	private SPDB_Automation_Engine $automation_engine;
	private SPDB_Background_Jobs $background_jobs;
	private SPDB_Local_Repair $local_repair;
	private SPDB_Activation_Wizard $activation_wizard;
	private SPDB_Privacy_Integration $privacy_integration;
	private SPDB_Operations_REST_Controller $operations_rest;
	private SPDB_Saved_Views $saved_views;
	private SPDB_REST_Privacy $rest_privacy;
	private SPDB_System_State $system_state;
	private SPDB_Overview_Service $overview_service;
	private SPDB_Dashboard_Page $dashboard_page;
	private SPDB_Dashboard_Router $dashboard_router;
	private bool $booted = false;

	private function __construct() {
		$this->adapter_registry = new SPDB_Adapter_Registry( SPDB_Adapter_Acceptance::records() );
		$acceptance = apply_filters( 'spdb/native_reference_acceptance', array() );
		if ( ! is_array( $acceptance ) ) {
			$acceptance = array();
		}
		$this->native_reference_registry = new SPDB_Native_Reference_Registry( $this->adapter_registry, $acceptance );
		$this->operation_broker = new SPDB_Operation_Broker( $this->adapter_registry );
		$this->inventory = new SPDB_Federated_Inventory( $this->adapter_registry );
		$this->inventory_rest = new SPDB_Inventory_REST_Controller( $this->inventory );
		$this->workspace_resolver = new SPDB_Workspace_Resolver();
		$this->role_workspace_service = new SPDB_Role_Workspace_Service( $this->adapter_registry );
		$this->review_calendar_service = new SPDB_Review_Calendar_Service( $this->adapter_registry );
		$this->review_calendar_rest = new SPDB_Review_Calendar_REST_Controller( $this->review_calendar_service, $this->operation_broker );

		$this->collections_repository = new SPDB_WP_Collections_Repository();
		$this->collections_service = new SPDB_Collections_Service( $this->collections_repository, $this->native_reference_registry );
		$this->collections_rest = new SPDB_Collections_REST_Controller( $this->collections_service );

		$this->operations_repository = new SPDB_Operations_Repository();
		$this->adapter_acceptance = new SPDB_Adapter_Acceptance( $this->adapter_registry, $this->operations_repository );
		$this->operations_service = new SPDB_Operations_Service( $this->adapter_registry, $this->operations_repository );
		$this->governance_service = new SPDB_Governance_Service( $this->operations_repository );
		$this->export_service = new SPDB_Export_Service( $this->operations_service, $this->review_calendar_service, $this->operations_repository );
		$this->automation_engine = new SPDB_Automation_Engine( $this->operations_repository );
		$this->background_jobs = new SPDB_Background_Jobs( $this->operations_repository, $this->operations_service, $this->export_service, $this->adapter_registry, $this->automation_engine );
		$this->local_repair = new SPDB_Local_Repair( $this->adapter_registry, $this->operations_repository );
		$this->activation_wizard = new SPDB_Activation_Wizard( $this->local_repair, $this->operations_repository );
		$this->privacy_integration = new SPDB_Privacy_Integration( $this->operations_repository, $this->export_service );
		$this->operations_rest = new SPDB_Operations_REST_Controller(
			$this->operations_service,
			$this->governance_service,
			$this->export_service,
			$this->operations_repository,
			$this->local_repair,
			$this->activation_wizard,
			$this->adapter_acceptance
		);

		$this->saved_views = new SPDB_Saved_Views( $this->operations_repository );
		$this->rest_privacy = new SPDB_REST_Privacy();
		$this->system_state = new SPDB_System_State(
			$this->adapter_registry,
			$this->collections_service,
			$this->native_reference_registry,
			$this->operations_repository,
			$this->local_repair,
			$this->activation_wizard,
			$this->adapter_acceptance
		);
		$this->overview_service = new SPDB_Overview_Service( $this->system_state );
		$this->dashboard_page = new SPDB_Dashboard_Page(
			$this->workspace_resolver,
			$this->overview_service,
			$this->system_state,
			$this->saved_views,
			$this->inventory,
			$this->role_workspace_service,
			$this->review_calendar_service,
			$this->collections_service,
			$this->operations_service,
			$this->governance_service,
			$this->export_service,
			$this->local_repair,
			$this->activation_wizard,
			$this->adapter_acceptance
		);
		$this->dashboard_router = new SPDB_Dashboard_Router( array( $this->dashboard_page, 'render' ) );
	}

	public static function instance(): SPDB_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function activate(): void {
		SPDB_Capability_Installer::ensure();
		$collections = SPDB_Collections_Schema::install();
		$operations = SPDB_Operations_Schema::install();
		if ( is_wp_error( $collections ) || is_wp_error( $operations ) ) {
			$error = is_wp_error( $collections ) ? $collections : $operations;
			wp_die(
				esc_html( $error->get_error_message() ),
				esc_html__( 'Sabri Publishing Dashboard activation failed', 'sabri-publishing-dashboard' ),
				array( 'response' => 500 )
			);
		}
		SPDB_Dashboard_Router::activate();
	}

	public static function deactivate(): void {
		SPDB_Dashboard_Router::deactivate();
		SPDB_Background_Jobs::deactivate();
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
		$this->collections_rest->register();
		$this->operations_rest->register();
		$this->export_service->register();
		$this->automation_engine->register();
		$this->background_jobs->register();
		$this->privacy_integration->register();
		$this->rest_privacy->register();
		add_action( 'init', array( 'SPDB_Capability_Installer', 'maybe_upgrade' ), 1 );
		add_action( 'admin_init', array( 'SPDB_Collections_Schema', 'maybe_upgrade' ), 2 );
		add_action( 'admin_init', array( 'SPDB_Operations_Schema', 'maybe_upgrade' ), 3 );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'plugins_loaded', array( $this, 'register_provider_adapters' ), 30 );
		add_action( 'plugins_loaded', array( $this, 'register_native_reference_resolvers' ), 31 );
		add_action( 'admin_notices', array( $this, 'render_dependency_notice' ) );
		add_action( 'spdb/background_job_dead_lettered', array( $this, 'forward_dead_letter_notice' ) );
		add_filter( 'spdb/capabilities', array( $this, 'filter_capabilities' ) );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'sabri-publishing-dashboard', false, dirname( plugin_basename( SPDB_PLUGIN_FILE ) ) . '/languages' );
	}

	public function register_provider_adapters(): void {
		SPDB_Provider_Registration::dispatch( $this->adapter_registry );
		do_action( 'spdb/file24_assurance_evidence', SPDB_Module_Manifest::assurance( $this->adapter_registry, $this->operations_repository ) );
	}

	public function register_native_reference_resolvers(): void {
		SPDB_Native_Reference_Registration::dispatch( $this->native_reference_registry );
	}

	/** @param array<string,mixed> $notice Privacy-minimized dead-letter notice. */
	public function forward_dead_letter_notice( array $notice ): void {
		do_action(
			'spdb/publishing_notification',
			array(
				'type'       => 'background_job_failed',
				'job_id'     => sanitize_key( (string) ( $notice['job_id'] ?? '' ) ),
				'job_type'   => sanitize_key( (string) ( $notice['job_type'] ?? '' ) ),
				'error_code' => sanitize_key( (string) ( $notice['last_error_code'] ?? '' ) ),
				'attempts'   => max( 0, (int) ( $notice['attempts'] ?? 0 ) ),
			)
		);
	}

	public function render_dependency_notice(): void {
		if ( SPDB_Membership_Guard::is_available() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Sabri Publishing Dashboard is in fail-closed mode because Sabri Membership Core is unavailable or incompatible. No privileged dashboard action is permitted.', 'sabri-publishing-dashboard' ) . '</p></div>';
	}

	public function registry(): SPDB_Adapter_Registry { return $this->adapter_registry; }
	public function native_references(): SPDB_Native_Reference_Registry { return $this->native_reference_registry; }
	public function broker(): SPDB_Operation_Broker { return $this->operation_broker; }
	public function inventory(): SPDB_Federated_Inventory { return $this->inventory; }
	public function role_workspace(): SPDB_Role_Workspace_Service { return $this->role_workspace_service; }
	public function review_calendar(): SPDB_Review_Calendar_Service { return $this->review_calendar_service; }
	public function collections(): SPDB_Collections_Service { return $this->collections_service; }
	public function operations(): SPDB_Operations_Service { return $this->operations_service; }
	public function governance(): SPDB_Governance_Service { return $this->governance_service; }
	public function exports(): SPDB_Export_Service { return $this->export_service; }
	/** @return array<string,mixed> */
	public function assurance_manifest(): array { return SPDB_Module_Manifest::assurance( $this->adapter_registry, $this->operations_repository ); }
	/** @return array<int,array<string,mixed>> */
	public function dependency_manifest(): array { return SPDB_Module_Manifest::snapshot( $this->adapter_registry ); }
	public function router(): SPDB_Dashboard_Router { return $this->dashboard_router; }

	/** @param string[] $capabilities @return string[] */
	public function filter_capabilities( array $capabilities ): array {
		return array_values( array_unique( array_merge( $capabilities, SPDB_Capabilities::all() ) ) );
	}
}
