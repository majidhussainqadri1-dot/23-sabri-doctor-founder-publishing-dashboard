<?php
/** Dynamic privacy and scope isolation regressions for cached File 23 analytics. */
define( 'SMC_VERSION', '1.0.1' );
function smc_user_status( int $id ): string { return 'approved'; }
function smc_is_founder( int $id ): bool { return 7 === $id; }
function smc_is_trusted_publisher( int $id ): bool { return false; }
function get_locale(): string { return 'en_US'; }
require_once __DIR__ . '/bootstrap.php';

final class SPDB_Metric_Privacy_Test_DB {
	public string $prefix = 'wp_';
	public array $rows = array();
	public array $stored = array();
	public string $last_sql = '';
	public function prepare( string $sql, ...$args ): string { return $sql; }
	public function replace( string $table, array $data ): int { $this->stored = $data; return 1; }
	public function get_results( string $sql, $output = null ): array {
		$this->last_sql = $sql;
		if ( false !== strpos( $sql, "scope = 'institution'" ) ) {
			return array_values( array_filter( $this->rows, static fn( array $r ): bool => 'institution' === $r['scope'] ) );
		}
		if ( false !== strpos( $sql, "scope = 'own'" ) ) {
			return array_values( array_filter( $this->rows, static fn( array $r ): bool => 'own' === $r['scope'] && 7 === (int) $r['owner_user_id'] ) );
		}
		return $this->rows;
	}
}

$wpdb = new SPDB_Metric_Privacy_Test_DB();
$GLOBALS['wpdb'] = $wpdb;
$GLOBALS['spdb_test_capabilities']['spdb_view_own_analytics'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_view_global_analytics'] = true;
$repository = new SPDB_Operations_Repository();
$service = new SPDB_Operations_Service( new SPDB_Adapter_Registry(), $repository );
$checks = 0;
$failures = 0;
$assert = static function ( bool $ok, string $description ) use ( &$checks, &$failures ): void {
	++$checks;
	if ( ! $ok ) {
		++$failures;
		fwrite( STDERR, "FAIL: {$description}\n" );
	}
};
$sample = static function ( int $cohort, int $stored_threshold = 5, string $scope = 'own', int $owner = 7 ): array {
	return array(
		'snapshot_id' => 'metric_fixture', 'provider_key' => 'file21', 'metric_key' => 'views',
		'scope' => $scope, 'owner_user_id' => $owner,
		'period_start_gmt' => '2026-10-01 00:00:00', 'period_end_gmt' => '2026-10-02 00:00:00',
		'definition_hash' => str_repeat( 'a', 64 ),
		'value_json' => json_encode( array( 'value' => 987, 'unit' => 'count', 'label' => 'Views', 'definition' => 'Aggregate views', 'interval' => 'daily' ) ),
		'cohort_count' => $cohort, 'privacy_threshold' => $stored_threshold,
		'generated_at_gmt' => '2026-10-02 00:00:00', 'expires_at_gmt' => '2026-11-01 00:00:00',
	);
};

foreach ( array( array( 8, 20, true ), array( 20, 20, false ), array( 30, 50, true ), array( 60, 50, false ) ) as $case ) {
	[$cohort, $admin_min, $must_suppress] = $case;
	$GLOBALS['spdb_test_options']['spdb_settings_v3'] = array( 'analytics_min_cohort' => $admin_min );
	$wpdb->rows = array( $sample( $cohort ) );
	$read = $repository->list_metric_snapshots( 7, false, 20 );
	$assert( is_array( $read ) && 1 === count( $read ), "Read cached cohort {$cohort}" );
	$metric = is_array( $read ) ? ( $read[0] ?? array() ) : array();
	$assert( $must_suppress ? null === ( $metric['value'] ?? null ) : 987 === ( $metric['value'] ?? null ), "Repository masks cohort {$cohort} against admin {$admin_min}" );
	$assert( $admin_min === ( $metric['privacy_threshold'] ?? 0 ), "Effective minimum tracks configured cohort {$admin_min}" );
	$response = $service->analytics( array( 'scope' => 'own' ) );
	$assert( is_array( $response ) && 'bounded_cached_snapshot' === ( $response['source'] ?? '' ), "Cached fallback available for cohort {$cohort}" );
	$first = is_array( $response ) ? ( $response['metrics'][0] ?? array() ) : array();
	$assert( $must_suppress ? ( true === ( $first['suppressed'] ?? false ) && null === ( $first['value'] ?? null ) ) : ( false === ( $first['suppressed'] ?? true ) && 987 === ( $first['value'] ?? null ) ), "REST payload suppresses cohort {$cohort} against admin {$admin_min}" );
	$stored = $sample( $cohort );
	$stored['value'] = 987;
	$stored['unit'] = 'count';
	$stored['label'] = 'Views';
	$stored['definition'] = 'Aggregate views';
	$stored['interval'] = 'daily';
	$result = $repository->store_metric_snapshot( $stored );
	$assert( true === $result, "Cached cohort {$cohort} stores safely" );
	$raw = json_decode( (string) ( $wpdb->stored['value_json'] ?? '' ), true );
	$assert( $must_suppress ? null === ( $raw['value'] ?? null ) : 987 === ( $raw['value'] ?? null ), "Storage masks cohort {$cohort} before persisting" );
}
$GLOBALS['spdb_test_options']['spdb_settings_v3'] = array( 'analytics_min_cohort' => 20 );
$wpdb->rows = array( $sample( 35, 20, 'own', 8 ), $sample( 35, 20, 'own', 7 ), $sample( 40, 20, 'institution', 0 ) );
$institution = $repository->list_metric_snapshots( 7, true, 20 );
$assert( false !== strpos( $wpdb->last_sql, "scope = 'institution'" ), 'Institution SQL explicitly filters institution scope' );
$assert( is_array( $institution ) && 1 === count( $institution ) && 'institution' === ( $institution[0]['scope'] ?? '' ), 'Institution never receives doctor-owned snapshots' );
$own = $repository->list_metric_snapshots( 7, false, 20 );
$assert( is_array( $own ) && 1 === count( $own ) && 7 === ( $own[0]['owner_user_id'] ?? 0 ), 'Own analytics excludes other doctors and institution' );
if ( $failures ) { fwrite( STDERR, "{$failures} / {$checks} cached privacy tests failed.\n" ); exit( 1 ); }
echo "All {$checks} cached analytics privacy and scope tests passed.\n";
