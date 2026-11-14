<?php
/**
 * V12.3 WP-03: account IDs in task/delegation requests require exact positive
 * integer representation, not PHP's (int) coercion of "7spoof"/"07"/float.
 * Synthetic WordPress membership and non-writing repository only.
 */
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
if ( ! defined( 'SMC_VERSION' ) ) { define( 'SMC_VERSION', '1.0.1' ); }
if ( ! function_exists( 'smc_user_status' ) ) {
    function smc_user_status( $uid ) { return 'approved'; }
}
if ( ! function_exists( 'smc_is_founder' ) ) {
    function smc_is_founder( $uid ) { return false; }
}
if ( ! function_exists( 'smc_is_trusted_publisher' ) ) {
    function smc_is_trusted_publisher( $uid ) { return true; }
}
$GLOBALS['spdb_test_logged_in'] = true;
$GLOBALS['spdb_test_user_id'] = 7;
$GLOBALS['spdb_test_capabilities']['spdb_manage_tasks'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_manage_delegations'] = true;
$GLOBALS['spdb_test_user_capabilities'][7]['spdb_manage_tasks'] = true;

final class SPDB_V123_No_Writes_DB {
    public int $writes = 0;
    public function insert( $table, $row ) { ++$this->writes; return false; }
}
$GLOBALS['wpdb'] = new SPDB_V123_No_Writes_DB();
$repo = (new ReflectionClass(SPDB_Operations_Repository::class))->newInstanceWithoutConstructor();
$service = new SPDB_Governance_Service( $repo );
$checks = 0; $failures = 0;
$check = static function ( bool $pass, string $label ) use (&$checks,&$failures): void {
  ++$checks;
  if (!$pass) { ++$failures; fwrite(STDERR, 'FAIL: '.$label."\n"); }
};
$invalid = array( '7spoof', '07', '7.0', '7e0', ' 7', '7 ', '92233720368547758070', -7, 7.5, true );
foreach ( $invalid as $raw ) {
    $r = $service->create_task( array(
        'title' => 'Synthetic task', 'description' => 'No personal data', 'scope' => 'own',
        'assignee_user_id' => $raw, 'priority' => 'normal',
        'audit_reason' => 'Source-only test for exact account identifier'
    ) );
    $check( is_wp_error($r) && 'spdb_task_invalid' === $r->get_error_code(),
        'Malformed assignee ID cannot be cast to a real account: '.var_export($raw,true) );
}
$check(0 === $GLOBALS['wpdb']->writes, 'Malformed IDs must be rejected before a storage write.');
// A canonical integer ID still passes the guard and reaches repository storage.
$valid = $service->create_task( array(
    'title' => 'Valid identifier', 'description' => 'Synthetic positive control',
    'scope' => 'own', 'assignee_user_id' => 7, 'priority' => 'normal',
    'audit_reason' => 'Positive control for explicit numeric user ID'
) );
$check(is_wp_error($valid) && 'spdb_task_create_failed' === $valid->get_error_code(),
    'Canonical integer ID remains eligible for repository processing.');
$check(1 === $GLOBALS['wpdb']->writes, 'Exactly one approved task attempt reaches the fake repository.');
// Delegated account authority must reject ambiguous identifiers before DB access.
$GLOBALS['spdb_test_user_id'] = 9;
$GLOBALS['spdb_test_capabilities']['spdb_view_own_content'] = true;
$GLOBALS['spdb_test_user_capabilities'][7]['spdb_view_own_content'] = true;
$start = gmdate('Y-m-d\\TH:i:s\\Z', time() + 120);
$expiry = gmdate('Y-m-d\\TH:i:s\\Z', time() + 3600);
foreach (array('7spoof', '07', '7.0', ' 7', true) as $bad_id) {
    $delegation = $service->create_delegation(array(
        'delegate_user_id' => $bad_id, 'scope' => array('view_content'),
        'starts_at_gmt' => $start, 'expires_at_gmt' => $expiry,
        'reason' => 'Synthetic scoped delegation negative test'
    ));
    $check(is_wp_error($delegation) && 'spdb_delegation_invalid' === $delegation->get_error_code(),
        'Delegation must reject noncanonical account ID: '.var_export($bad_id,true));
}
$check(1 === $GLOBALS['wpdb']->writes, 'Malformed delegations cannot reach persistence.');
if ($failures) { fwrite(STDERR, "{$failures} of {$checks} strict account-ID tests failed.\n"); exit(1); }
echo "All {$checks} strict task account-ID checks passed. Source only.\n";
