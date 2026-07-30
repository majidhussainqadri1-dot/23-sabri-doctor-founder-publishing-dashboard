<?php
/** Minimal WordPress-compatible test bootstrap for File 23 executable tests. */
define( 'ABSPATH', __DIR__ . '/' );
define( 'SPDB_VERSION', '0.6.1' );
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
if ( ! class_exists( 'WP_HTTP_Response' ) ) { class WP_HTTP_Response { public array $headers = array(); public function header( string $key, string $value, bool $replace = true ): void { $this->headers[ $key ] = $value; } } }
if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request implements ArrayAccess {
		private string $route; private array $params; private array $headers = array();
		public function __construct( string $route = '', array $params = array(), array $headers = array() ) { $this->route = $route; $this->params = $params; foreach ( $headers as $key => $value ) { $this->set_header( (string) $key, (string) $value ); } }
		public function get_route(): string { return $this->route; } public function get_params(): array { return $this->params; } public function get_header( string $key ): string { return $this->headers[ strtolower( $key ) ] ?? ''; } public function set_header( string $key, string $value ): void { $this->headers[ strtolower( $key ) ] = $value; }
		public function offsetExists( $offset ): bool { return isset( $this->params[ $offset ] ); } public function offsetGet( $offset ) { return $this->params[ $offset ] ?? null; } public function offsetSet( $offset, $value ): void { $this->params[ $offset ] = $value; } public function offsetUnset( $offset ): void { unset( $this->params[ $offset ] ); }
	}
}
if ( ! class_exists( 'SPDB_Test_Role' ) ) { class SPDB_Test_Role { public array $capabilities = array(); public function add_cap( string $capability, bool $grant = true ): void { $this->capabilities[ $capability ] = $grant; } } }
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
function rest_ensure_response( $value ) { return $value; }
function add_query_arg( $key, $value = null, $url = null ): string { if ( is_array( $key ) ) { $base = (string) $value; foreach ( $key as $query_key => $query_value ) { $base = add_query_arg( (string) $query_key, (string) $query_value, $base ); } return $base; } $url = (string) $url; $separator = false === strpos( $url, '?' ) ? '?' : '&'; return $url . $separator . rawurlencode( (string) $key ) . '=' . rawurlencode( (string) $value ); }
function get_user_meta( int $user_id, string $key, bool $single = false ) { return $GLOBALS['spdb_test_user_meta'][ $user_id ][ $key ] ?? ( $single ? '' : array() ); }
function update_user_meta( int $user_id, string $key, $value, $previous_value = null ): bool { $current = get_user_meta( $user_id, $key, true ); if ( ! empty( $GLOBALS['spdb_test_force_meta_conflict'] ) ) { $GLOBALS['spdb_test_user_meta'][ $user_id ][ $key ] = array( 'concurrent_change' => true ); $GLOBALS['spdb_test_force_meta_conflict'] = false; return false; } if ( 4 === func_num_args() && $current !== $previous_value ) { return false; } $GLOBALS['spdb_test_user_meta'][ $user_id ][ $key ] = $value; return true; }
function wp_generate_uuid4(): string { static $counter = 0; ++$counter; return sprintf( '123e4567-e89b-12d3-a456-%012d', $counter ); }
function current_time( string $type, bool $gmt = false ): string { return '2026-07-30 10:26:00'; }
function get_role( string $role_key ) { return $GLOBALS['spdb_test_roles'][ $role_key ] ?? null; }
function get_option( string $key, $default = false ) { return $GLOBALS['spdb_test_options'][ $key ] ?? $default; }
function update_option( string $key, $value, $autoload = null ): bool { $GLOBALS['spdb_test_options'][ $key ] = $value; return true; }
function remove_all_actions( string $hook ): void { unset( $GLOBALS['wp_filter'][ $hook ] ); }

require_once dirname( __DIR__ ) . '/includes/interface-spdb-provider-adapter.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-workspace-provider-adapter.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-review-calendar-provider-adapter.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-collections-repository.php';
require_once dirname( __DIR__ ) . '/includes/interface-spdb-native-reference-resolver.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-adapter-registry.php';
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
require_once dirname( __DIR__ ) . '/includes/class-spdb-dashboard-router.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-workspace-resolver.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-saved-views.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-rest-privacy.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-system-state.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-overview-service.php';
