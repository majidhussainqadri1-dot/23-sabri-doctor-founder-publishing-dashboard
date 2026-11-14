<?php
/**
 * V12.3 WP-04/WP-12: a queued export processor may not execute a different
 * user's export under the currently impersonated WP-Cron account.
 *
 * The fake DB intentionally denies the first status update; no report bytes,
 * private files, Hostinger SQL or real account data are involved.
 */
declare(strict_types=1);
define( 'SMC_VERSION', '1.0.1' );
function smc_user_status( int $id ): string { return 'approved'; }
function smc_is_founder( int $id ): bool { return false; }
function smc_is_trusted_publisher( int $id ): bool { return true; }
require_once __DIR__ . '/bootstrap.php';

final class SPDB_V123_Export_Owner_DB {
	public string $prefix = 'wp_';
	public array $export = array();
	public int $status_updates = 0;
	public function prepare( string $sql, ...$args ): string { return $sql; }
	public function get_row( string $sql, $output = null ) { return $this->export; }
	public function update( string $table, array $data, array $where ) {
		++$this->status_updates;
		return false; // Stop before rendering or touching the host filesystem.
	}
}
$wpdb = new SPDB_V123_Export_Owner_DB();
$GLOBALS['wpdb'] = $wpdb;
$wpdb->export = array(
	'export_id' => 'export_' . str_repeat( 'a', 32 ),
	'owner_user_id' => 7,
	'report_key' => 'publication_history',
	'format' => 'csv',
	'scope' => 'own',
	'status' => 'queued',
	'filters_json' => '{}',
	'reason_hash' => str_repeat( 'b', 64 ),
	'created_at_gmt' => '2026-11-14 00:00:00',
	'updated_at_gmt' => '2026-11-14 00:00:00',
	'expires_at_gmt' => '2099-01-01 00:00:00',
);
$GLOBALS['spdb_test_user_capabilities'][7]['spdb_export_reports'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_export_reports'] = true;
$registry = new SPDB_Adapter_Registry();
$repository = new SPDB_Operations_Repository();
$operations = new SPDB_Operations_Service( $registry, $repository );
$calendar = new SPDB_Review_Calendar_Service( $registry );
$exports = new SPDB_Export_Service( $operations, $calendar, $repository );
$checks=0; $failed=0;
$check = static function ( bool $valid, string $reason ) use ( &$checks, &$failed ): void {
	++$checks;
	if ( ! $valid ) { ++$failed; fwrite( STDERR, "FAIL: {$reason}\n" ); }
};

$GLOBALS['spdb_test_user_id'] = 8;
$res = $exports->process( $wpdb->export['export_id'] );
$check( is_wp_error( $res ) && 'spdb_export_job_owner_mismatch' === $res->get_error_code(), 'WP-Cron user 8 cannot process private export owned by user 7.' );
$check( 0 === $wpdb->status_updates, 'Cross-account export must not mark the foreign owner export processing.' );

$GLOBALS['spdb_test_user_id'] = 0;
$res = $exports->process( $wpdb->export['export_id'] );
$check( is_wp_error( $res ) && 'spdb_export_job_owner_mismatch' === $res->get_error_code(), 'Unauthenticated/zero-current-user cron context cannot generate an owner export.' );
$check( 0 === $wpdb->status_updates, 'Missing owner impersonation must not mutate an export job.' );

$GLOBALS['spdb_test_user_id'] = 7;
$res = $exports->process( $wpdb->export['export_id'] );
$check( is_wp_error( $res ) && 'spdb_export_update_failed' === $res->get_error_code(), 'The correctly impersonated owner reaches its own test database gate.' );
$check( 1 === $wpdb->status_updates, 'Authorized owner tries exactly one safe status transition before the synthetic DB failure.' );

if ( $failed ) { fwrite( STDERR, "{$failed} / {$checks} V12.3 export-job owner checks failed.\n" ); exit( 1 ); }
echo "All {$checks} V12.3 export-job owner checks passed; staging and live remain unverified.\n";
