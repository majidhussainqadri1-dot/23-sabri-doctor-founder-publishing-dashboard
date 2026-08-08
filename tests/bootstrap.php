<?php
/** Minimal WordPress-compatible test bootstrap for File 23 executable tests. */
define( 'ABSPATH', __DIR__ . '/' );
define( 'SPDB_VERSION', '1.2.3' );
define( 'SPDB_CONTRACT_VERSION', '2.0.0' );
if ( ! defined( 'ARRAY_A' ) ) { define( 'ARRAY_A', 'ARRAY_A' ); }
$GLOBALS['spdb_test_environment'] = 'production';
$GLOBALS['spdb_test_logged_in'] = true;
$GLOBALS['spdb_test_user_id'] = 7;
$GLOBALS['spdb_test_capabilities'] = array();
$GLOBALS['spdb_test_user_capabilities'] = array();
$GLOBALS['spdb_test_member_status'] = 'draft';
$GLOBALS['spdb_test_member_statuses'] = array();
$GLOBALS['spdb_test_founder'] = false;
$GLOBALS['spdb_test_trusted'] = false;
$GLOBALS['spdb_test_query_vars'] = array();
$GLOBALS['spdb_test_user_meta'] = array();
$GLOBALS['spdb_test_options'] = array();
$GLOBALS['spdb_test_roles'] = array();
$GLOBALS['spdb_test_force_meta_conflict'] = false;
$GLOBALS['spdb_test_last_inventory_query'] = array();
$GLOBALS['spdb_test_workspace_context'] = array();
$GLOBALS['spdb_test_review_context'] = array();
$GLOBALS['spdb_test_calendar_context'] = array();
$GLOBALS['spdb_test_review_query_count'] = 0;
$GLOBALS['spdb_test_calendar_query_count'] = 0;
$GLOBALS['spdb_test_last_operation'] = array();
$GLOBALS['spdb_test_rest_routes'] = array();
$GLOBALS['spdb_test_wrap_rest_response'] = false;
$GLOBALS['wp_filter'] = array();
$GLOBALS['wp'] = (object) array( 'query_vars' => array() );
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private string $code; private string $message; private $data;
		public function __construct( string $code = '', string $message = '', $data = null ) { $this->code = $code; $this->message = $message; $this->data = $data; }
		public function get_error_code(): string { return $this->code; }
		public function get_error_message(): string { return $this->message; }
		public function get_error_data() { return $this->data; }
	}
}
if ( ! class_exists( 'WP_HTTP_Response' ) ) {
	class WP_HTTP_Response {
		public array $headers = array(); private $data;
		public function __construct( $data = null ) { $this->data = $data; }
		public function header( string $key, string $value, bool $replace = true ): void { $this->headers[ $key ] = $value; }
		public function get_data() { return $this->data; }
	}
}
if ( ! class_exists( 'WP_REST_Server' ) ) { class WP_REST_Server { public const READABLE = 'GET'; public const CREATABLE = 'POST'; public const EDITABLE = 'POST, PUT, PATCH'; public const DELETABLE = 'DELETE'; } }
if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request implements ArrayAccess {
		private string $route; private array $params; private array $query; private array $headers = array();
		public function __construct( string $route = '', array $params = array(), array $headers = array(), ?array $query = null ) { $this->route = $route; $this->params = $params; $this->query = null === $query ? array() : $query; foreach ( $headers as $key => $value ) { $this->set_header( (string) $key, (string) $value ); } }
		public function get_route(): string { return $this->route; }
		public function get_params(): array { return $this->params; }
		public function get_query_params(): array { return $this->query; }
		public function get_header( string $key ): string { return $this->headers[ strtolower( $key ) ] ?? ''; }
		public function set_header( string $key, string $value ): void { $this->headers[ strtolower( $key ) ] = $value; }
		public function offsetExists( mixed $offset ): bool { return isset( $this->params[ $offset ] ); }
		public function offsetGet( mixed $offset ): mixed { return $this->params[ $offset ] ?? null; }
		public function offsetSet( mixed $offset, mixed $value ): void { $this->params[ $offset ] = $value; }
		public function offsetUnset( mixed $offset ): void { unset( $this->params[ $offset ] ); }
	}
}
if ( ! class_exists( 'SPDB_Test_Role' ) ) { class SPDB_Test_Role { public array $capabilities = array(); public function add_cap( string $capability, bool $grant = true ): void { $this->capabilities[ $capability ] = $grant; } public function remove_cap( string $capability ): void { unset( $this->capabilities[ $capability ] ); } } }
function is_wp_error( $value ): bool { return $value instanceof WP_Error; }
function __( string $text, string $domain = '' ): string { return $text; }
function _n( string $single, string $plural, int $number, string $domain = '' ): string { return 1 === $number ? $single : $plural; }
function sanitize_key( $key ): string { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) ) ?? ''; }
function sanitize_text_field( $value ): string { return trim( preg_replace( '/[\x00-\x1F\x7F]/u', '', strip_tags( (string) $value ) ) ?? '' ); }
function wp_strip_all_tags( $value ): string { return strip_tags( (string) $value ); }
function wp_unslash( $value ) { return $value; }
function wp_get_environment_type(): string { return (string) $GLOBALS['spdb_test_environment']; }
function is_user_logged_in(): bool { return (bool) $GLOBALS['spdb_test_logged_in']; }
function get_current_user_id(): int { return (int) $GLOBALS['spdb_test_user_id']; }
function current_user_can( string $capability, ...$args ): bool { return ! empty( $GLOBALS['spdb_test_capabilities'][ $capability ] ); }
function user_can( int $user_id, string $capability, ...$args ): bool { return ! empty( $GLOBALS['spdb_test_user_capabilities'][ $user_id ][ $capability ] ); }
function wp_verify_nonce( string $nonce, string $action ): int { return 'valid-rest-nonce' === $nonce && 'wp_rest' === $action ? 1 : 0; }
function get_query_var( string $key, $default = '' ) { return $GLOBALS['spdb_test_query_vars'][ $key ] ?? $default; }
function home_url( string $path = '' ): string { return 'https://example.test' . $path; }
function wp_parse_url( string $url ) { return parse_url( $url ); }
function esc_url_raw( string $url ): string { return filter_var( $url, FILTER_SANITIZE_URL ) ?: ''; }
function wp_json_encode( $value ) { return json_encode( $value ); }
function rest_ensure_response( $value ) { return ! empty( $GLOBALS['spdb_test_wrap_rest_response'] ) ? new WP_HTTP_Response( $value ) : $value; }
function add_query_arg( $key, $value = null, $url = null ): string { if ( is_array( $key ) ) { $base = (string) $value; foreach ( $key as $query_key => $query_value ) { $base = add_query_arg( (string) $query_key, (string) $query_value, $base ); } return $base; } $url = (string) $url; $separator = false === strpos( $url, '?' ) ? '?' : '&'; return $url . $separator . rawurlencode( (string) $key ) . '=' . rawurlencode( (string) $value ); }
function get_user_meta( int $user_id, string $key, bool $single = false ) { return $GLOBALS['spdb_test_user_meta'][ $user_id ][ $key ] ?? ( $single ? '' : array() ); }
function update_user_meta( int $user_id, string $key, $value, $previous_value = null ): bool { $current = get_user_meta( $user_id, $key, true ); if ( ! empty( $GLOBALS['spdb_test_force_meta_conflict'] ) ) { $GLOBALS['spdb_test_user_meta'][ $user_id ][ $key ] = array( 'concurrent_change' => true ); $GLOBALS['spdb_test_force_meta_conflict'] = false; return false; } if ( 4 === func_num_args() && $current !== $previous_value ) { return false; } $GLOBALS['spdb_test_user_meta'][ $user_id ][ $key ] = $value; return true; }
function wp_generate_uuid4(): string { static $counter = 0; ++$counter; return sprintf( '123e4567-e89b-12d3-a456-%012d', $counter ); }
function current_time( string $type, bool $gmt = false ): string { return '2026-07-31 00:21:00'; }
function get_role( string $role_key ) { return $GLOBALS['spdb_test_roles'][ $role_key ] ?? null; }
function get_option( string $key, $default = false ) { return $GLOBALS['spdb_test_options'][ $key ] ?? $default; }
function update_option( string $key, $value, $autoload = null ): bool { $GLOBALS['spdb_test_options'][ $key ] = $value; return true; }
function delete_option( string $key ): bool { unset( $GLOBALS['spdb_test_options'][ $key ] ); return true; }
function remove_all_actions( string $hook ): void { unset( $GLOBALS['wp_filter'][ $hook ] ); }
if ( ! function_exists( 'add_action' ) ) { function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void { $GLOBALS['wp_filter'][ $hook ][] = $callback; } }
if ( ! function_exists( 'add_filter' ) ) { function add_filter( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void { $GLOBALS['wp_filter'][ $hook ][] = $callback; } }
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( string $hook, $value, ...$args ) {
		foreach ( $GLOBALS['wp_filter'][ $hook ] ?? array() as $callback ) {
			$value = call_user_func( $callback, $value, ...$args );
		}
		return $value;
	}
}
if ( ! function_exists( 'do_action' ) ) {
	function do_action( string $hook, ...$args ): void {
		foreach ( $GLOBALS['wp_filter'][ $hook ] ?? array() as $callback ) {
			call_user_func_array( $callback, $args );
		}
	}
}
if ( ! function_exists( 'register_rest_route' ) ) { function register_rest_route( string $namespace, string $route, array $args ): bool { $GLOBALS['spdb_test_rest_routes'][ $namespace . $route ] = $args; return true; } }

require_once dirname( __DIR__ ) . '/includes/interface-spdb-provider-adapter.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-workspace-provider-adapter.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-review-calendar-provider-adapter.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-operational-projection-provider.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-analytics-provider.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-ai-assistance-provider.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-collections-repository.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-native-reference-resolver.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-native-reference-provider.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-adapter-registry.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-adapter-acceptance.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-native-reference-registry.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-native-reference-registration.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-membership-guard.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-capabilities.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-capability-installer.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-operation-broker.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-provider-registration.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-inventory-query.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-projection-validator.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-federated-inventory.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-safe-destination.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-workspace-projection-validator.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-role-workspace-service.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-review-calendar-validator.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-review-calendar-service.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-review-calendar-rest-controller.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-schema.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-policy.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-wp-collections-repository.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-service.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-view.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-collections-rest-controller.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-operations-schema.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-operational-projection-validator.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-operations-repository.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-governance-service.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-export-service.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-automation-engine.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-background-jobs.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-privacy-integration.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-local-repair.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-activation-wizard.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-operations-service.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-operations-rest-controller.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-module-manifest.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-legacy-migration-diagnostics.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-admin-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-dashboard-router.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-workspace-resolver.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-saved-views.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-rest-privacy.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-system-state.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-overview-service.php';
