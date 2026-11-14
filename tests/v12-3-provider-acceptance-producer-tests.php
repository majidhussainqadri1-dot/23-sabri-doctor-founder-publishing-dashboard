<?php
/**
 * V12.3 WP-02: staging may not mint "production_accepted" records even if a
 * legacy release option happens to claim Founder/staging signoff.
 *
 * No user/Hostinger/deployed source state is inferred by this synthetic test.
 */
declare(strict_types=1);
define( 'SMC_VERSION', '1.0.1' );
function smc_user_status( int $id ): string { return 'approved'; }
function smc_is_founder( int $id ): bool { return 7 === $id; }
function smc_is_trusted_publisher( int $id ): bool { return false; }
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/fixtures/class-test-adapter.php';

final class SPDB_V123_Acceptance_Audit_DB {
	public string $prefix = 'wp_';
	public string $last_error = '';
	public int $writes = 0;
	public function prepare( string $sql, ...$args ): string { return $sql; }
	public function get_var( string $sql ) {
		if ( false !== strpos( $sql, 'GET_LOCK(' ) || false !== strpos( $sql, 'RELEASE_LOCK(' ) ) { return '1'; }
		return null;
	}
	public function insert( string $table, array $data ) { ++$this->writes; return 1; }
}
$wpdb = new SPDB_V123_Acceptance_Audit_DB();
$GLOBALS['wpdb'] = $wpdb;
$GLOBALS['spdb_test_user_id'] = 7;
$GLOBALS['spdb_test_logged_in'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_manage_dashboard_settings'] = true;
$GLOBALS['spdb_test_options']['spdb_activation_acceptance_v1'] = array(
	'staging_accepted' => true,
	'founder_accepted' => true,
	'plugin_version' => SPDB_VERSION,
);
$GLOBALS['spdb_test_environment'] = 'staging';
$registry = new SPDB_Adapter_Registry();
$registered = $registry->register( new SPDB_Test_Adapter() );
if ( true !== $registered ) { throw new RuntimeException( 'Test provider could not register.' ); }
$service = new SPDB_Adapter_Acceptance( $registry, new SPDB_Operations_Repository() );
$payload = array(
	'state' => 'production_accepted',
	'evidence_id' => 'synthetic:test:version',
	'reason' => 'Synthetic source-only approval provenance verification.',
);
$checks = 0; $failures = 0;
$check = static function ( bool $good, string $message ) use ( &$checks, &$failures ): void {
	++$checks;
	if ( ! $good ) { ++$failures; fwrite( STDERR, "FAIL: {$message}\n" ); }
};
$result = $service->record( 'provider_one', $payload );
$check( is_wp_error( $result ) && 'spdb_adapter_acceptance_production_required' === $result->get_error_code(), 'A staging instance may not issue production-accepted provider records.' );
$check( array() === get_option( 'spdb_adapter_acceptance_records_v1', array() ), 'Staging production-approval denial must make no wp_options changes.' );
$check( 0 === $wpdb->writes, 'Staging production-approval denial must not write an audit asserting acceptance.' );

$GLOBALS['spdb_test_environment'] = 'production';
$result = $service->record( 'provider_one', $payload );
$check( is_array( $result ) && 'production_accepted' === ( $result['record']['state'] ?? '' ), 'With separate synthetic release options, production-origin acceptance remains possible.' );
$stored = get_option( 'spdb_adapter_acceptance_records_v1', array() );
$check( is_array( $stored ) && 'production' === ( $stored['provider_one']['environment'] ?? null ), 'A production record binds its own environment explicitly.' );
$check( 1 === $wpdb->writes, 'Approved synthetic production acceptance produces one bounded audit entry.' );

if ( $failures ) { fwrite( STDERR, "{$failures} of {$checks} V12.3 production-acceptance producer checks failed.\n" ); exit( 1 ); }
echo "All {$checks} V12.3 production-acceptance producer source checks passed.\n";
