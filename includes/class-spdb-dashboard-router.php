<?php
/**
 * Protected front-end router for the File 23 dashboard.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Dashboard_Router {
	public const QUERY_VAR       = 'spdb_dashboard';
	public const ROUTE           = 'publishing-dashboard';
	public const REWRITE_VERSION = '1';

	/** @var callable */
	private $renderer;

	/**
	 * @param callable $renderer Authorized dashboard renderer.
	 */
	public function __construct( callable $renderer ) {
		$this->renderer = $renderer;
	}

	public function register(): void {
		add_action( 'init', array( __CLASS__, 'register_rewrite_rule' ) );
		add_action( 'init', array( __CLASS__, 'maybe_flush_rewrite_rules' ), 99 );
		add_filter( 'query_vars', array( $this, 'register_query_var' ) );
		add_action( 'template_redirect', array( $this, 'dispatch' ), 0 );
		add_action( 'send_headers', array( $this, 'send_private_headers' ) );
		add_filter( 'body_class', array( $this, 'body_classes' ) );
	}

	public static function register_rewrite_rule(): void {
		add_rewrite_rule(
			'^' . preg_quote( self::ROUTE, '/' ) . '/?$',
			'index.php?' . self::QUERY_VAR . '=1',
			'top'
		);
	}

	/** Flush once after an upgrade that introduces or changes the route. */
	public static function maybe_flush_rewrite_rules(): void {
		if ( self::REWRITE_VERSION === (string) get_option( 'spdb_rewrite_version', '' ) ) {
			return;
		}
		flush_rewrite_rules( false );
		update_option( 'spdb_rewrite_version', self::REWRITE_VERSION, false );
	}

	public static function activate(): void {
		self::register_rewrite_rule();
		flush_rewrite_rules( false );
		update_option( 'spdb_rewrite_version', self::REWRITE_VERSION, false );
	}

	public static function deactivate(): void {
		flush_rewrite_rules( false );
	}

	/**
	 * @param string[] $query_vars Existing public query variables.
	 * @return string[]
	 */
	public function register_query_var( array $query_vars ): array {
		$query_vars[] = self::QUERY_VAR;
		return array_values( array_unique( $query_vars ) );
	}

	/** Detect the route both before and after WP_Query is populated. */
	public static function is_dashboard_request(): bool {
		if (
			isset( $GLOBALS['wp'] )
			&& is_object( $GLOBALS['wp'] )
			&& isset( $GLOBALS['wp']->query_vars )
			&& is_array( $GLOBALS['wp']->query_vars )
			&& isset( $GLOBALS['wp']->query_vars[ self::QUERY_VAR ] )
		) {
			return '1' === (string) $GLOBALS['wp']->query_vars[ self::QUERY_VAR ];
		}
		return '1' === (string) get_query_var( self::QUERY_VAR, '' );
	}

	public static function route_url( string $view = 'overview' ): string {
		$url  = home_url( '/' . self::ROUTE . '/' );
		$view = self::normalize_view( $view );
		return 'overview' === $view ? $url : add_query_arg( 'view', $view, $url );
	}

	public static function current_view(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only route selection.
		$requested = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'overview';
		return self::normalize_view( $requested );
	}

	public static function normalize_view( string $view ): string {
		$allowed = array( 'overview', 'workspace', 'inventory', 'collections', 'review', 'calendar', 'saved-views', 'system-status' );
		return in_array( $view, $allowed, true ) ? $view : 'overview';
	}

	public function dispatch(): void {
		if ( ! self::is_dashboard_request() ) {
			return;
		}

		self::mark_private_request();
		if ( ! is_user_logged_in() ) {
			auth_redirect();
			exit;
		}

		$user_id = get_current_user_id();
		if (
			! SPDB_Membership_Guard::can_user_view_restricted_dashboard( $user_id )
			|| ! SPDB_Capabilities::current_user_can( 'spdb_view_dashboard' )
		) {
			wp_die(
				esc_html__( 'You are not authorized to access the publishing dashboard.', 'sabri-publishing-dashboard' ),
				esc_html__( 'Access Denied', 'sabri-publishing-dashboard' ),
				array( 'response' => 403 )
			);
		}

		call_user_func( $this->renderer );
		exit;
	}

	public function send_private_headers(): void {
		if ( self::is_dashboard_request() ) {
			self::mark_private_request();
			self::emit_private_headers();
		}
	}

	/** Mark the current page for cache plugins before any dashboard output. */
	public static function mark_private_request(): void {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	}

	/** Emit headers shared by the virtual route and shortcode fallback page. */
	public static function emit_private_headers(): void {
		if ( headers_sent() ) {
			return;
		}
		nocache_headers();
		header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', true );
		header( 'Pragma: no-cache', true );
		header( 'X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true );
		header( 'Referrer-Policy: same-origin', true );
		header( 'X-Content-Type-Options: nosniff', true );
		header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()', true );
	}

	/**
	 * @param string[] $classes Existing body classes.
	 * @return string[]
	 */
	public function body_classes( array $classes ): array {
		if ( self::is_dashboard_request() ) {
			$classes[] = 'spdb-dashboard-request';
		}
		return $classes;
	}
}
