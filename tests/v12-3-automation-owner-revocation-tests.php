<?php
/**
 * V12.3 File23 negative execution guards: a queued automation may outlive
 * its original owner authorization or be presented under another job owner.
 *
 * The native provider filter must never receive any of these denied jobs.
 * Source-only test, not an assertion about installed Hostinger code.
 */
declare(strict_types=1);
define( 'SMC_VERSION', '1.0.1' );
$GLOBALS['spdb_v123_status'] = 'approved';
function smc_user_status( int $id ): string {
	return $GLOBALS['spdb_v123_status'] ?? 'dependency_unavailable';
}
function smc_is_founder( int $id ): bool { return false; }
function smc_is_trusted_publisher( int $id ): bool { return false; }
require_once __DIR__ . '/bootstrap.php';

final class SPDB_V123_Automation_DB {
	public string $prefix = 'wp_';
	public int $reads = 0;
	public array $rule = array();
	public function prepare( string $sql, ...$args ): string { return $sql; }
	public function get_row( string $sql, $format = null ) {
		++$this->reads;
		return $this->rule;
	}
}
$wpdb = new SPDB_V123_Automation_DB();
$GLOBALS['wpdb'] = $wpdb;
$wpdb->rule = array(
	'rule_id' => 'rule_' . str_repeat( 'a', 32 ),
	'owner_user_id' => 7,
	'rule_type' => 'stale_draft_reminder',
	'event_key' => 'draft_aged',
	'condition_json' => '{}',
	'action_json' => '{"action_key":"send_reminder","human_confirmation":true,"idempotent":true}',
	'status' => 'enabled',
	'version' => 3,
);
$GLOBALS['spdb_test_options']['spdb_settings_v3'] = array( 'automation_enabled' => true );
$GLOBALS['spdb_test_capabilities']['spdb_manage_automation_rules'] = true;
$filter_calls = 0;
add_filter( 'spdb/automation_action_result', static function ( $previous, $rule, $event, $job ) use ( &$filter_calls ) {
	++$filter_calls;
	return new WP_Error( 'synthetic_native_action_stopped', 'Do not perform an actual mutation.' );
} );

$engine = new SPDB_Automation_Engine( new SPDB_Operations_Repository() );
$job = array(
	'owner_user_id' => 7,
	'payload' => array( 'rule_id' => $wpdb->rule['rule_id'], 'rule_version' => 3, 'event' => array( 'kind' => 'safe' ) ),
);
$checks = 0; $failed = 0;
$check = static function ( bool $test, string $message ) use ( &$checks, &$failed ): void {
	++$checks;
	if ( ! $test ) { ++$failed; fwrite( STDERR, 'FAIL: ' . $message . "\n" ); }
};

$GLOBALS['spdb_test_user_id'] = 8;
$wrong_owner = $job;
$wrong_owner['owner_user_id'] = 8;
$res = $engine->execute( $wrong_owner );
$check( is_wp_error( $res ) && 'spdb_automation_owner_mismatch' === $res->get_error_code(), 'Queued job owner 8 must not run canonical rule owner 7.' );
$check( 0 === $filter_calls, 'Mismatched owner must not reach a native action.' );

$GLOBALS['spdb_test_user_id'] = 7;
$GLOBALS['spdb_v123_status'] = 'suspended';
$res = $engine->execute( $job );
$check( is_wp_error( $res ) && 'spdb_automation_owner_unapproved' === $res->get_error_code(), 'Suspended owner must be denied at execution, despite prior enqueue.' );
$check( 0 === $filter_calls, 'Suspended owner must not reach native provider callback.' );

$GLOBALS['spdb_v123_status'] = 'approved';
$GLOBALS['spdb_test_capabilities']['spdb_manage_automation_rules'] = false;
$res = $engine->execute( $job );
$check( is_wp_error( $res ) && 'spdb_automation_capability_revoked' === $res->get_error_code(), 'Revoked automation capability must be denied at execution.' );
$check( 0 === $filter_calls, 'Revoked capability must not reach native provider callback.' );

$GLOBALS['spdb_test_capabilities']['spdb_manage_automation_rules'] = true;
$res = $engine->execute( $job );
$check( is_wp_error( $res ) && 'synthetic_native_action_stopped' === $res->get_error_code(), 'Fresh approved owner may reach native provider guard.' );
$check( 1 === $filter_calls, 'Native action reached exactly once for approved owner.' );

$GLOBALS['spdb_test_user_id'] = 8;
$res = $engine->execute( $job );
$check( is_wp_error( $res ) && 'spdb_automation_owner_mismatch' === $res->get_error_code(), 'Mismatched WordPress user context must not execute a different owner job.' );
$check( 1 === $filter_calls, 'Wrong current session must not invoke native provider.' );

// The version is an immutable queued-job precondition, not a best-effort
// integer cast. A noncanonical version must never reach a native handler.
$GLOBALS['spdb_test_user_id'] = 7;
$GLOBALS['spdb_v123_status'] = 'approved';
$GLOBALS['spdb_test_capabilities']['spdb_manage_automation_rules'] = true;
foreach ( array( '3spoof', '03', 3.5, null ) as $bad_version ) {
	$bad_job = $job;
	$bad_job['payload']['rule_version'] = $bad_version;
	$res = $engine->execute( $bad_job );
	$check( is_wp_error( $res ) && 'spdb_automation_job_invalid' === $res->get_error_code(), 'Malformed queued rule version must be rejected, not cast to 3.' );
	$check( 1 === $filter_calls, 'Malformed queued version must not execute a native callback.' );
}
$missing_version = $job;
unset( $missing_version['payload']['rule_version'] );
$wpdb->rule['version'] = 1;
$res = $engine->execute( $missing_version );
$check( is_wp_error( $res ) && 'spdb_automation_job_invalid' === $res->get_error_code(), 'Missing queued version cannot silently become version 1.' );
$check( 1 === $filter_calls, 'Missing version must not run the first rule revision.' );
$wpdb->rule['version'] = 3;

if ( $failed ) { fwrite( STDERR, "{$failed} / {$checks} V12.3 automation owner/revocation checks failed.\n" ); exit( 1 ); }
echo "All {$checks} V12.3 automation owner/revocation checks passed (source only).\n";
