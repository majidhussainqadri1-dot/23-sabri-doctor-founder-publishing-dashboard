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

	public function __construct(
		SPDB_Workspace_Resolver $workspace_resolver,
		SPDB_Overview_Service $overview_service,
		SPDB_System_State $system_state,
		SPDB_Saved_Views $saved_views
	) {
		$this->workspace_resolver = $workspace_resolver;
		$this->overview_service   = $overview_service;
		$this->system_state       = $system_state;
		$this->saved_views        = $saved_views;
	}

	public function register(): void {
		add_shortcode( 'sabri_publishing_dashboard', array( $this, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	public function register_assets(): void {
		wp_register_style(
			'spdb-dashboard',
			SPDB_PLUGIN_URL . 'assets/css/dashboard.css',
			array(),
			SPDB_VERSION
		);

		wp_register_script(
			'spdb-dashboard',
			SPDB_PLUGIN_URL . 'assets/js/dashboard.js',
			array( 'wp-api-fetch', 'wp-i18n' ),
			SPDB_VERSION,
			true
		);
	}

	public function enqueue_assets(): void {
		wp_enqueue_style( 'spdb-dashboard' );
		wp_enqueue_script( 'spdb-dashboard' );
		wp_localize_script(
			'spdb-dashboard',
			'SPDBDashboard',
			array(
				'restRoot' => esc_url_raw( rest_url( 'spdb/v1/' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'viewUrl'  => esc_url_raw( SPDB_Dashboard_Router::route_url( 'saved-views' ) ),
				'strings'  => array(
					'loading'       => __( 'Loading saved views…', 'sabri-publishing-dashboard' ),
					'empty'         => __( 'No saved views yet.', 'sabri-publishing-dashboard' ),
					'createFailed'  => __( 'The saved view could not be created.', 'sabri-publishing-dashboard' ),
					'deleteFailed'  => __( 'The saved view could not be deleted.', 'sabri-publishing-dashboard' ),
					'deleteLabel'   => __( 'Delete', 'sabri-publishing-dashboard' ),
					'created'       => __( 'Saved view created.', 'sabri-publishing-dashboard' ),
					'deleted'       => __( 'Saved view deleted.', 'sabri-publishing-dashboard' ),
				),
			)
		);
	}

	public function render(): void {
		$this->enqueue_assets();
		status_header( 200 );
		get_header();
		echo $this->render_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- All template fields are escaped at rendering boundaries.
		get_footer();
	}

	public function shortcode(): string {
		if ( ! is_user_logged_in() ) {
			return '<div class="spdb-notice spdb-notice--warning" role="status">' . esc_html__( 'Sign in to access the publishing dashboard.', 'sabri-publishing-dashboard' ) . '</div>';
		}

		$user_id = get_current_user_id();
		if (
			! SPDB_Membership_Guard::can_user_view_restricted_dashboard( $user_id )
			|| ! SPDB_Capabilities::current_user_can( 'spdb_view_dashboard' )
		) {
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

		$overview    = $this->overview_service->build( $workspace );
		$system_state = $this->system_state->snapshot( $workspace );
		$saved_views  = $this->saved_views->get_for_user( get_current_user_id() );

		ob_start();
		include SPDB_PLUGIN_DIR . 'templates/dashboard.php';
		return (string) ob_get_clean();
	}
}
