<?php
/**
 * Accessible private dashboard page and shortcode renderer.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Dashboard_Page {
	private SPDB_Workspace_Resolver $workspace_resolver;
	private SPDB_Overview_Service $overview_service;
	private SPDB_System_State $system_state;
	private SPDB_Saved_Views $saved_views;
	private SPDB_Federated_Inventory $inventory;
	private SPDB_Role_Workspace_Service $role_workspace_service;
	private SPDB_Review_Calendar_Service $review_calendar_service;
	private ?SPDB_Collections_View $collections_view;
	private ?SPDB_Operations_Service $operations_service;
	private ?SPDB_Governance_Service $governance_service;
	private ?SPDB_Export_Service $export_service;
	private ?SPDB_Local_Repair $local_repair;
	private ?SPDB_Activation_Wizard $activation_wizard;
	private bool $saved_assets_localized = false;
	private bool $operations_assets_localized = false;
	private bool $shortcode_page_protected = false;
	private int $render_count = 0;

	public function __construct(
		SPDB_Workspace_Resolver $workspace_resolver,
		SPDB_Overview_Service $overview_service,
		SPDB_System_State $system_state,
		SPDB_Saved_Views $saved_views,
		SPDB_Federated_Inventory $inventory,
		SPDB_Role_Workspace_Service $role_workspace_service,
		SPDB_Review_Calendar_Service $review_calendar_service,
		?SPDB_Collections_Service $collections_service = null,
		?SPDB_Operations_Service $operations_service = null,
		?SPDB_Governance_Service $governance_service = null,
		?SPDB_Export_Service $export_service = null,
		?SPDB_Local_Repair $local_repair = null,
		?SPDB_Activation_Wizard $activation_wizard = null
	) {
		$this->workspace_resolver      = $workspace_resolver;
		$this->overview_service        = $overview_service;
		$this->system_state            = $system_state;
		$this->saved_views             = $saved_views;
		$this->inventory               = $inventory;
		$this->role_workspace_service  = $role_workspace_service;
		$this->review_calendar_service = $review_calendar_service;
		$this->collections_view        = null === $collections_service ? null : new SPDB_Collections_View( $collections_service );
		$this->operations_service      = $operations_service;
		$this->governance_service      = $governance_service;
		$this->export_service          = $export_service;
		$this->local_repair            = $local_repair;
		$this->activation_wizard       = $activation_wizard;
	}

	public function register(): void {
		add_shortcode( 'sabri_publishing_dashboard', array( $this, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'template_redirect', array( $this, 'protect_shortcode_page' ), -1 );
	}

	public function protect_shortcode_page(): void {
		if ( ! is_singular() ) {
			return;
		}
		$post = get_post();
		if ( ! $post instanceof WP_Post || ! has_shortcode( (string) $post->post_content, 'sabri_publishing_dashboard' ) ) {
			return;
		}
		$this->shortcode_page_protected = true;
		SPDB_Dashboard_Router::mark_private_request();
		SPDB_Dashboard_Router::emit_private_headers();
		$user_id = get_current_user_id();
		if ( is_user_logged_in() && SPDB_Membership_Guard::can_user_view_restricted_dashboard( $user_id ) && SPDB_Capabilities::current_user_can( 'spdb_view_dashboard' ) ) {
			$this->enqueue_assets();
		}
	}

	public function register_assets(): void {
		$styles = array(
			'spdb-dashboard'             => array( 'assets/css/dashboard.css', array() ),
			'spdb-dashboard-corrections' => array( 'assets/css/dashboard-corrections.css', array( 'spdb-dashboard' ) ),
			'spdb-inventory'             => array( 'assets/css/inventory.css', array( 'spdb-dashboard-corrections' ) ),
			'spdb-workspace'             => array( 'assets/css/workspace.css', array( 'spdb-dashboard-corrections' ) ),
			'spdb-review-calendar'       => array( 'assets/css/review-calendar.css', array( 'spdb-dashboard-corrections' ) ),
			'spdb-collections'           => array( 'assets/css/collections.css', array( 'spdb-dashboard-corrections' ) ),
			'spdb-operations'            => array( 'assets/css/operations.css', array( 'spdb-dashboard-corrections' ) ),
		);
		foreach ( $styles as $handle => $definition ) {
			if ( ! wp_style_is( $handle, 'registered' ) ) {
				wp_register_style( $handle, SPDB_PLUGIN_URL . $definition[0], $definition[1], SPDB_VERSION );
			}
		}
		if ( ! wp_script_is( 'spdb-dashboard', 'registered' ) ) {
			wp_register_script( 'spdb-dashboard', SPDB_PLUGIN_URL . 'assets/js/dashboard.js', array( 'wp-api-fetch', 'wp-i18n' ), SPDB_VERSION, true );
		}
		if ( ! wp_script_is( 'spdb-operations', 'registered' ) ) {
			wp_register_script( 'spdb-operations', SPDB_PLUGIN_URL . 'assets/js/operations.js', array( 'wp-api-fetch', 'wp-i18n' ), SPDB_VERSION, true );
		}
	}

	public function enqueue_assets(): void {
		$this->register_assets();
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'spdb-dashboard-corrections' );
		$current = SPDB_Dashboard_Router::current_view();
		if ( 'inventory' === $current ) {
			wp_enqueue_style( 'spdb-inventory' );
		}
		if ( 'workspace' === $current ) {
			wp_enqueue_style( 'spdb-workspace' );
		}
		if ( in_array( $current, array( 'collections', 'knowledge' ), true ) ) {
			wp_enqueue_style( 'spdb-collections' );
		}
		if ( in_array( $current, array( 'review', 'calendar' ), true ) ) {
			wp_enqueue_style( 'spdb-review-calendar' );
		}

		$operations_views = array( 'create', 'knowledge', 'sources', 'media', 'interactions', 'revisions', 'analytics', 'notifications', 'tasks', 'reports', 'settings', 'system-status' );
		if ( in_array( $current, $operations_views, true ) ) {
			wp_enqueue_style( 'spdb-operations' );
			wp_enqueue_script( 'spdb-operations' );
			$this->localize_operations_assets();
		}

		if ( 'saved-views' === $current ) {
			wp_enqueue_script( 'spdb-dashboard' );
			$this->localize_saved_assets();
		}
	}

	private function localize_saved_assets(): void {
		if ( $this->saved_assets_localized ) {
			return;
		}
		wp_localize_script(
			'spdb-dashboard',
			'SPDBDashboard',
			array(
				'restRoot' => esc_url_raw( rest_url( 'spdb/v1/' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'viewUrl'  => esc_url_raw( SPDB_Dashboard_Router::route_url( 'saved-views' ) ),
				'strings'  => array(
					'loading'      => __( 'Loading saved views…', 'sabri-publishing-dashboard' ),
					'empty'        => __( 'No saved views yet.', 'sabri-publishing-dashboard' ),
					'createFailed' => __( 'The saved view could not be created.', 'sabri-publishing-dashboard' ),
					'deleteFailed' => __( 'The saved view could not be deleted.', 'sabri-publishing-dashboard' ),
					'deleteLabel'  => __( 'Delete', 'sabri-publishing-dashboard' ),
					'created'      => __( 'Saved view created.', 'sabri-publishing-dashboard' ),
					'deleted'      => __( 'Saved view deleted.', 'sabri-publishing-dashboard' ),
				),
			)
		);
		$this->saved_assets_localized = true;
	}

	private function localize_operations_assets(): void {
		if ( $this->operations_assets_localized ) {
			return;
		}
		wp_localize_script(
			'spdb-operations',
			'SPDBOperations',
			array(
				'restRoot' => esc_url_raw( rest_url( 'spdb/v1/' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'currentView' => SPDB_Dashboard_Router::current_view(),
				'strings'  => array(
					'working' => __( 'Working…', 'sabri-publishing-dashboard' ),
					'saved'   => __( 'Saved successfully.', 'sabri-publishing-dashboard' ),
					'queued'  => __( 'The request was queued successfully.', 'sabri-publishing-dashboard' ),
					'failed'  => __( 'The request could not be completed.', 'sabri-publishing-dashboard' ),
					'confirm' => __( 'Confirm this audited operation?', 'sabri-publishing-dashboard' ),
				),
			)
		);
		$this->operations_assets_localized = true;
	}

	public function render(): void {
		$this->enqueue_assets();
		status_header( 200 );
		get_header();
		echo $this->render_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		get_footer();
	}

	public function shortcode(): string {
		if ( ! SPDB_Dashboard_Router::is_dashboard_request() && ! $this->shortcode_page_protected ) {
			return '<div class="spdb-notice spdb-notice--critical" role="alert">' . esc_html__( 'The publishing dashboard shortcode may only be used directly on a protected singular page.', 'sabri-publishing-dashboard' ) . '</div>';
		}
		if ( ! is_user_logged_in() ) {
			return '<div class="spdb-notice spdb-notice--warning" role="status">' . esc_html__( 'Sign in to access the publishing dashboard.', 'sabri-publishing-dashboard' ) . '</div>';
		}
		$user_id = get_current_user_id();
		if ( ! SPDB_Membership_Guard::can_user_view_restricted_dashboard( $user_id ) || ! SPDB_Capabilities::current_user_can( 'spdb_view_dashboard' ) ) {
			return '<div class="spdb-notice spdb-notice--critical" role="alert">' . esc_html__( 'You are not authorized to access the publishing dashboard.', 'sabri-publishing-dashboard' ) . '</div>';
		}
		$this->enqueue_assets();
		return $this->render_markup();
	}

	private function render_markup(): string {
		$workspace  = $this->workspace_resolver->resolve( get_current_user_id() );
		$navigation = $this->workspace_resolver->navigation( $workspace );
		$current    = SPDB_Dashboard_Router::current_view();
		if ( ! isset( $navigation[ $current ] ) ) {
			$current = 'overview';
		}

		$overview                = $this->overview_service->build( $workspace );
		$system_state            = $this->system_state->snapshot( $workspace );
		$saved_views             = $this->saved_views->get_for_user( get_current_user_id() );
		$instance_id             = 'spdb-' . (string) ++$this->render_count;
		$role_workspace          = null;
		$review_result           = null;
		$calendar_result         = null;
		$inventory_result        = null;
		$inventory_item          = null;
		$inventory_providers     = array();
		$collections_projection  = null;
		$operational_result      = null;
		$operational_domain      = '';
		$analytics_result        = null;
		$governance_result       = null;
		$exports_result          = null;
		$settings_result         = null;
		$system_check            = null;
		$activation_state        = null;
		$composer_url            = '';

		if ( 'inventory' === $current ) {
			$inventory_result    = $this->inventory->list_items( $this->inventory_request_input() );
			$inventory_providers = $this->inventory->provider_options();
			$reference = $this->inspection_reference();
			if ( null !== $reference ) {
				$inventory_item = $this->inventory->inspect_item( $reference['provider'], $reference['object_type'], $reference['object_id'] );
			}
		}
		if ( 'workspace' === $current ) {
			$role_workspace = $this->role_workspace_service->build( $workspace );
		}
		if ( 'collections' === $current ) {
			$collections_projection = null === $this->collections_view
				? $this->unavailable( 'spdb_collections_view_unavailable', __( 'The Collections view service is unavailable.', 'sabri-publishing-dashboard' ) )
				: $this->collections_view->resolve( $this->collections_request_input() );
		}
		if ( 'review' === $current ) {
			$review_result = $this->review_calendar_service->review_queue( $this->review_request_input() );
		}
		if ( 'calendar' === $current ) {
			$calendar_result = $this->review_calendar_service->calendar( $this->calendar_request_input() );
		}
		if ( 'create' === $current ) {
			$composer_url = null === $this->operations_service ? '' : $this->operations_service->composer_url();
		}

		$domain_map = array(
			'knowledge'     => 'gaps',
			'sources'       => 'sources',
			'media'         => 'media',
			'interactions'  => 'interactions',
			'revisions'     => 'revisions',
			'notifications' => 'notifications',
		);
		if ( isset( $domain_map[ $current ] ) ) {
			$operational_domain = $domain_map[ $current ];
			$operational_result = null === $this->operations_service
				? $this->unavailable( 'spdb_operations_unavailable', __( 'The operational projection service is unavailable.', 'sabri-publishing-dashboard' ) )
				: $this->operations_service->projections( $operational_domain, $this->operations_request_input() );
		}
		if ( 'analytics' === $current ) {
			$analytics_result = null === $this->operations_service
				? $this->unavailable( 'spdb_analytics_unavailable', __( 'The analytics projection service is unavailable.', 'sabri-publishing-dashboard' ) )
				: $this->operations_service->analytics( $this->analytics_request_input() );
		}
		if ( 'tasks' === $current ) {
			$governance_result = null === $this->governance_service
				? array( 'tasks' => array(), 'delegations' => array(), 'automation_rules' => array(), 'institutional' => false, 'settings' => SPDB_Admin_Settings::get() )
				: $this->governance_service->snapshot();
		}
		if ( 'reports' === $current ) {
			$exports_result = null === $this->export_service
				? $this->unavailable( 'spdb_exports_unavailable', __( 'The export service is unavailable.', 'sabri-publishing-dashboard' ) )
				: $this->export_service->list_exports();
		}
		if ( 'settings' === $current ) {
			$settings_result  = SPDB_Admin_Settings::get();
			$activation_state = null === $this->activation_wizard ? array() : $this->activation_wizard->state();
		}
		if ( 'system-status' === $current ) {
			$system_check     = null === $this->local_repair ? array() : $this->local_repair->system_check();
			$activation_state = null === $this->activation_wizard ? array() : $this->activation_wizard->state();
		}

		ob_start();
		include SPDB_PLUGIN_DIR . 'templates/dashboard.php';
		return (string) ob_get_clean();
	}

	private function inventory_request_input(): array {
		return $this->read_query( array( 'page', 'per_page', 'search', 'provider', 'object_type', 'lifecycle_state', 'review_state', 'visibility_state', 'operational_state', 'language', 'topic', 'date_from', 'date_to', 'sort', 'direction', 'scope' ) );
	}

	private function operations_request_input(): array {
		return $this->read_query( array( 'page', 'per_page', 'search', 'provider', 'object_type', 'status', 'language', 'topic', 'date_from', 'date_to', 'scope', 'cursor', 'sort', 'direction' ) );
	}

	private function analytics_request_input(): array {
		return $this->read_query( array( 'scope', 'provider', 'metric', 'date_from', 'date_to', 'interval', 'timezone' ) );
	}

	private function collections_request_input(): array {
		$allowed = array( 'view', 'section', 'scope', 'record_type', 'status', 'page', 'per_page', 'collection_id', 'item_id', 'item_page', 'link_id' );
		$routing = array( 'page_id', 'p', 'post_type' );
		$input   = array( 'view' => 'collections' );
		foreach ( $_GET as $raw_key => $raw_value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Strict read-only dashboard query.
			$key = sanitize_key( (string) $raw_key );
			if ( '' === $key || in_array( $key, $routing, true ) ) {
				continue;
			}
			$value = is_array( $raw_value ) ? wp_unslash( $raw_value ) : (string) wp_unslash( $raw_value );
			if ( ! in_array( $key, $allowed, true ) ) {
				$input[ $key ] = $value;
				continue;
			}
			$input[ $key ] = $value;
		}
		return $input;
	}

	private function review_request_input(): array {
		return $this->read_query( array( 'provider', 'review_state', 'assigned', 'due_from', 'due_to', 'page', 'per_page' ) );
	}

	private function calendar_request_input(): array {
		return $this->read_query( array( 'provider', 'status', 'date_from', 'date_to', 'timezone', 'page', 'per_page' ) );
	}

	private function read_query( array $keys ): array {
		$input = array();
		foreach ( $keys as $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only projection filter.
			if ( isset( $_GET[ $key ] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only projection filter.
				$input[ $key ] = wp_unslash( $_GET[ $key ] );
			}
		}
		return $input;
	}

	private function inspection_reference(): ?array {
		$values = array();
		foreach ( array( 'inspect_provider', 'inspect_type', 'inspect_id' ) as $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only inspector selection.
			if ( ! isset( $_GET[ $key ] ) || is_array( $_GET[ $key ] ) ) {
				return null;
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only inspector selection.
			$values[ $key ] = (string) wp_unslash( $_GET[ $key ] );
		}
		return array( 'provider' => $values['inspect_provider'], 'object_type' => $values['inspect_type'], 'object_id' => $values['inspect_id'] );
	}

	private function unavailable( string $code, string $message ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => 503 ) );
	}
}
