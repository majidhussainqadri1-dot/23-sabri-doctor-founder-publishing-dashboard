<?php
/**
 * Minimal WordPress-compatible test bootstrap for Phase 23A contract tests.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'SPDB_CONTRACT_VERSION', '2.0.0' );

$GLOBALS['spdb_test_environment']   = 'production';
$GLOBALS['spdb_test_logged_in']     = true;
$GLOBALS['spdb_test_user_id']       = 7;
$GLOBALS['spdb_test_capabilities']  = array();
$GLOBALS['spdb_test_member_status'] = 'draft';

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private string $code;
		private string $message;

		public function __construct( string $code = '', string $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}

		public function get_error_code(): string {
			return $this->code;
		}

		public function get_error_message(): string {
			return $this->message;
		}
	}
}

function is_wp_error( $value ): bool {
	return $value instanceof WP_Error;
}

function __( string $text, string $domain = '' ): string {
	return $text;
}

function sanitize_key( $key ): string {
	$key = strtolower( (string) $key );
	return preg_replace( '/[^a-z0-9_\-]/', '', $key ) ?? '';
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

require_once dirname( __DIR__ ) . '/includes/interface-spdb-provider-adapter.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-adapter-registry.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-membership-guard.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-capabilities.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-operation-broker.php';
