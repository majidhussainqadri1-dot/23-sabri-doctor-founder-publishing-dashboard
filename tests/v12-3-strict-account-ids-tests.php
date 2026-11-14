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
if ($failures) { fwrite(STDERR, "{$failures} of {$checks} strict account-ID tests failed.\n"); exit(1); }
echo "All {$checks} strict task account-ID checks passed. Source only.\n";
