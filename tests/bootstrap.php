<?php
/**
 * Minimal WordPress-compatible test bootstrap for File 23 executable tests.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'SPDB_VERSION', '0.2.0' );
define( 'SPDB_CONTRACT_VERSION', '2.0.0' );

$GLOBALS['spdb_test_environment']   = 'production';
$GLOBALS['spdb_test_logged_in']     = true;
$GLOBALS['spdb_test_user_id']       = 7;
$GLOBALS['spdb_test_capabilities']  = array();
$GLOBALS['spdb_test_member_status'] = 'draft';
$GLOBALS['spdb_test_founder']       = false;
$GLOBALS['spdb_test_trusted']       = false;
$GLOBALS['spdb_test_query_vars']    = array();
$GLOBALS['spdb_test_user_meta']     = array();
$GLOBALS['wp']                      = (object) array( 'query_vars' => array() );

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private string $code;
		private string $message;
		private $data;

		public function __construct( string $code = '', string $message = '', $data = null ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		public function get_error_code(): string {
			return $this->code;
		}

		public function get_error_message(): string {
			return $this->message;
		}

		public function get_error_data() {
			return $this->data;
		}
	}
}

function is_wp_error( $value ): bool {
	return $value instanceof WP_Error;
}

function __( string $text, string $domain = '' ): string {
	return $text;
}

function _n( string $single, string $plural, int $number, string $domain = '' ): string {
	return 1 === $number ? $single : $plural;
}

function sanitize_key( $key ): string {
	$key = strtolower( (string) $key );
	return preg_replace( '/[^a-z0-9_\-]/', '', $key ) ?? '';
}

function sanitize_text_field( $value ): string {
	$value = strip_tags( (string) $value );
	$value = preg_replace( '/[\x00-\x1F\x7F]/u', '', $value ) ?? '';
	return trim( $value );
}

function wp_unslash( $value ) {
	return $value;
}

function wp_get_environment_type(): string {
	return (string) $GLOBALS['spdb_test_environment'];
}

function is_user_logged_in(): bool {
	return (bool) $GLOBALS['spdb_test_logged_in'];
}

function get_current_user_id(): int {
	return (int) $GLOBALS['spdb_test_user_id'];
}

function current_user_can( string $capability, ...$args ): bool {
	return ! empty( $GLOBALS['spdb_test_capabilities'][ $capability ] );
}

function get_query_var( string $key, $default = '' ) {
	return $GLOBALS['spdb_test_query_vars'][ $key ] ?? $default;
}

function home_url( string $path = '' ): string {
	return 'https://example.test' . $path;
}

function add_query_arg( string $key, string $value, string $url ): string {
	$separator = false === strpos( $url, '?' ) ? '?' : '&';
	return $url . $separator . rawurlencode( $key ) . '=' . rawurlencode( $value );
}

function get_user_meta( int $user_id, string $key, bool $single = false ) {
	return $GLOBALS['spdb_test_user_meta'][ $user_id ][ $key ] ?? ( $single ? '' : array() );
}

function update_user_meta( int $user_id, string $key, $value ): bool {
	$GLOBALS['spdb_test_user_meta'][ $user_id ][ $key ] = $value;
	return true;
}

function wp_generate_uuid4(): string {
	return '123e4567-e89b-12d3-a456-426614174000';
}

function current_time( string $type, bool $gmt = false ): string {
	return '2026-07-30 02:27:00';
}

require_once dirname( __DIR__ ) . '/includes/interface-spdb-provider-adapter.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-adapter-registry.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-membership-guard.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-capabilities.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-operation-broker.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-dashboard-router.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-workspace-resolver.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-saved-views.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-rest-privacy.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-system-state.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-overview-service.php';
