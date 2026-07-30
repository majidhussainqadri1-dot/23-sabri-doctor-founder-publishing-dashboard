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
	private ?SPDB_Collections_Service $collections_service;
	private bool $assets_localized = false;
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
		?SPDB_Collections_Service $collections_service = null
	) {
		$this->workspace_resolver      = $workspace_resolver;
		$this->overview_service        = $overview_service;
		$this->system_state            = $system_state;
		$this->saved_views             = $saved_views;
		$this->inventory               = $inventory;
		$this->role_workspace_service  = $role_workspace_service;
		$this->review_calendar_service = $review_calendar_service;
		$this->collections_service     = $collections_service;
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
		if ( ! wp_style_is( 'spdb-dashboard', 'registered' ) ) {
			wp_register_style( 'spdb-dashboard', SPDB_PLUGIN_URL . 'assets/css/dashboard.css', array(), SPDB_VERSION );
		}
		if ( ! wp_style_is( 'spdb-dashboard-corrections', 'registered' ) ) {
			wp_register_style( 'spdb-dashboard-corrections', SPDB_PLUGIN_URL . 'assets/css/dashboard-corrections.css', array( 'spdb-dashboard' ), SPDB_VERSION );
		}
		if ( ! wp_style_is( 'spdb-inventory', 'registered' ) ) {
			wp_register_style( 'spdb-inventory', SPDB_PLUGIN_URL . 'assets/css/inventory.css', array( 'spdb-dashboard-corrections' ), SPDB_VERSION );
		}
		if ( ! wp_style_is( 'spdb-workspace', 'registered' ) ) {
			wp_register_style( 'spdb-workspace', SPDB_PLUGIN_URL . 'assets/css/workspace.css', array( 'spdb-dashboard-corrections' ), SPDB_VERSION );
		}
		if ( ! wp_style_is( 'spdb-review-calendar', 'registered' ) ) {
			wp_register_style( 'spdb-review-calendar', SPDB_PLUGIN_URL . 'assets/css/review-calendar.css', array( 'spdb-dashboard-corrections' ), SPDB_VERSION );
		}
		if ( ! wp_script_is( 'spdb-dashboard', 'registered' ) ) {
			wp_register_script( 'spdb-dashboard', SPDB_PLUGIN_URL . 'assets/js/dashboard.js', array( 'wp-api-fetch', 'wp-i18n' ), SPDB_VERSION, true );
		}
	}

	public function enqueue_assets(): void {
		$this->register_assets();
		wp_enqueue_style( 'spdb-dashboard-corrections' );
		$current = SPDB_Dashboard_Router::current_view();
		if ( 'inventory' === $current ) {
			wp_enqueue_style( 'spdb-inventory' );
		}
		if ( 'workspace' === $current ) {
			wp_enqueue_style( 'spdb-workspace' );
		}
		if ( in_array( $current, array( 'review', 'calendar' ), true ) ) {
			wp_enqueue_style( 'spdb-review-calendar' );
		}
		if ( 'saved-views' !== $current ) {
			return;
		}
		wp_enqueue_script( 'spdb-dashboard' );
		if ( $this->assets_localized ) {
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
		$this->assets_localized = true;
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
		$overview       = $this->overview_service->build( $workspace );
		$system_state   = $this->system_state->snapshot( $workspace );
		$saved_views    = $this->saved_views->get_for_user( get_current_user_id() );
		$instance_id    = 'spdb-' . (string) ++$this->render_count;
		$role_workspace = null;
		$review_result  = null;
		$calendar_result = null;
		$inventory_result    = null;
		$inventory_item      = null;
		$inventory_providers = array();

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
		if ( 'review' === $current ) {
			$review_result = $this->review_calendar_service->review_queue( $this->review_request_input() );
		}
		if ( 'calendar' === $current ) {
			$calendar_result = $this->review_calendar_service->calendar( $this->calendar_request_input() );
		}
		ob_start();
		include SPDB_PLUGIN_DIR . 'templates/dashboard.php';
		return (string) ob_get_clean();
	}

	private function inventory_request_input(): array {
		return $this->read_query( array( 'page', 'per_page', 'search', 'provider', 'object_type', 'lifecycle_state', 'review_state', 'visibility_state', 'operational_state', 'language', 'topic', 'date_from', 'date_to', 'sort', 'direction', 'scope' ) );
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
}
