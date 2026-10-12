<?php
/** Standalone regression tests for File 23 Future Publishing Intelligence 24. */

define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
$GLOBALS['spdb_test_signals'] = array();
$GLOBALS['spdb_test_configured_cohort'] = 20;
class SPDB_Admin_Settings {
	public static function get(): array { return array( 'analytics_min_cohort' => $GLOBALS['spdb_test_configured_cohort'] ); }
}
function apply_filters( $hook, $value, ...$args ) {
	if ( 'spdb/publishing_intelligence_signals' === $hook ) {
		$feature = (string) ( $args[0] ?? '' );
		return $GLOBALS['spdb_test_signals'][ $feature ] ?? array();
	}
	if ( 'spdb/publishing_intelligence_ask' === $hook ) { return null; }
	return $value;
}
require_once dirname( __DIR__ ) . '/includes/class-spdb-publishing-intelligence.php';

$tests = 0;
$failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};

$catalog = SPDB_Publishing_Intelligence::catalog();
$assert( 24 === count( $catalog ), 'Exactly 24 future publishing-intelligence facilities must be registered.' );
$ids = array_column( array_values( $catalog ), 'id' );
$assert( 'F23-FPI-01' === $ids[0] && 'F23-FPI-24' === $ids[23], 'Requirement IDs must span F23-FPI-01 through F23-FPI-24.' );
$assert( 24 === count( array_unique( $ids ) ), 'All 24 requirement IDs must be unique.' );
foreach ( $catalog as $feature ) {
	$assert( false === $feature['canonical_write_authority'], $feature['id'] . ' must not gain canonical write authority.' );
	$assert( true === $feature['advisory_or_projection'], $feature['id'] . ' must remain advisory/projection based.' );
	$assert( true === $feature['advisory_or_projection_only'], $feature['id'] . ' must preserve prior advisory contract.' );
	$assert( 'file26' === $feature['search_ranking_owner'], $feature['id'] . ' must preserve File 26 search ownership.' );
	$assert( false === $feature['donor_payment_influence'], $feature['id'] . ' must prohibit donor ranking influence.' );
	$assert( false === $feature['auto_publish'] && false === $feature['auto_schedule'], $feature['id'] . ' must prohibit autonomous publication/scheduling.' );
}

$GLOBALS['spdb_test_signals']['best-time'] = array(
	array( 'window' => 'Tuesday 09:00', 'score' => 0.92, 'observations' => 35 ),
	array( 'window' => 'Friday 14:00', 'score' => 0.99, 'observations' => 8 ),
	array( 'window' => 'Monday 11:00', 'score' => 0.71, 'observations' => 25 ),
);
$best = SPDB_Publishing_Intelligence::snapshot( 'best-time' );
$assert( 2 === count( $best['data']['recommended_windows'] ), 'Best-time intelligence must suppress cohorts below the privacy threshold.' );
$assert( 'Tuesday 09:00' === $best['data']['recommended_windows'][0]['window'], 'Best-time windows must be ranked by score after privacy suppression.' );
$assert( false === $best['data']['auto_schedule'], 'Best-time intelligence must never auto-schedule.' );

$GLOBALS['spdb_test_configured_cohort'] = 40;
$GLOBALS['spdb_test_signals']['best-time'] = array(
	array( 'window' => 'Under threshold', 'score' => 0.99, 'observations' => 35 ),
	array( 'window' => 'Allowed threshold', 'score' => 0.90, 'observations' => 50 ),
);
$strict_best = SPDB_Publishing_Intelligence::snapshot( 'best-time' );
$assert( 1 === count( $strict_best['data']['recommended_windows'] ) && 'Allowed threshold' === $strict_best['data']['recommended_windows'][0]['window'], 'Configured cohort threshold 40 must not be lowered to the default 20.' );
$GLOBALS['spdb_test_configured_cohort'] = 20;

$GLOBALS['spdb_test_signals']['opportunity-radar'] = array(
	array( 'topic' => 'A', 'demand' => 0.9, 'coverage' => 0.2, 'demand_provider' => 'file26' ),
	array( 'topic' => 'B', 'demand' => 0.7, 'coverage' => 0.6, 'demand_provider' => 'file26' ),
);
$opportunities = SPDB_Publishing_Intelligence::snapshot( 'opportunity-radar' );
$assert( 'A' === $opportunities['data']['opportunities'][0]['topic'], 'Opportunity Radar must rank larger demand-versus-coverage gaps first.' );
$assert( 'file26' === $opportunities['data']['ranking_owner'], 'File 26 must remain the ranking/search owner.' );
$assert( false === $opportunities['data']['paid_or_donor_influence'], 'Paid/donor influence must remain forbidden.' );

$GLOBALS['spdb_test_signals']['audience-board'] = array(
	array( 'label' => 'large cohort', 'cohort_count' => 42, 'value' => 10 ),
	array( 'label' => 'small cohort', 'cohort_count' => 9, 'value' => 99 ),
);
$audience = SPDB_Publishing_Intelligence::snapshot( 'audience-board' );
$assert( 1 === count( $audience['data']['items'] ), 'Audience Intelligence must suppress small cohorts.' );
$assert( 1 === $audience['data']['suppressed_count'], 'Audience Intelligence must report suppressed aggregates.' );

$playbooks = SPDB_Publishing_Intelligence::snapshot( 'editorial-playbooks' );
$assert( isset( $playbooks['data']['playbooks']['successful_case'] ), 'Successful Case editorial playbook must exist.' );
$assert( in_array( 'patient-consent', $playbooks['data']['playbooks']['successful_case']['checks'], true ), 'Successful Case playbook must require patient consent.' );
$assert( in_array( 'impact-map', $playbooks['data']['playbooks']['correction']['checks'], true ), 'Correction playbook must require change-impact mapping.' );

$ask = SPDB_Publishing_Intelligence::ask( 'What should I review today?' );
$assert( 'provider_unavailable' === $ask['status'], 'Ask Dashboard must fail safely when no approved AI provider exists.' );
$assert( false === $ask['auto_action'], 'Ask Dashboard must never auto-execute an action.' );

$simulation = SPDB_Publishing_Intelligence::simulate( array( 'reviewer_daily_capacity' => 2, 'items' => array(
	array( 'scheduled_at' => '2026-08-11 10:00:00', 'reviewer' => 'r1', 'campaign' => 'c1' ),
	array( 'scheduled_at' => '2026-08-11 11:00:00', 'reviewer' => 'r1', 'campaign' => 'c1' ),
	array( 'scheduled_at' => '2026-08-11 12:00:00', 'reviewer' => 'r1', 'campaign' => 'c2' ),
	array( 'scheduled_at' => '2026-08-11 13:00:00', 'reviewer' => 'r2', 'campaign' => 'c2' ),
) ) );
$assert( 'attention' === $simulation['risk_level'], 'What-if planner must surface collision or reviewer-overload risk.' );
$assert( false === $simulation['auto_schedule'], 'What-if planner must remain read-only/advisory.' );


$test_variants = array(
	array( 'experiment_id' => 'nested-privacy', 'cohort_count' => 120, 'variant_metrics' => array(
		'small_arm' => array( 'cohort_count' => 8, 'value' => 876 ),
		'large_arm' => array( 'cohort_count' => 60, 'value' => 123 ),
		'no_cohort_arm' => array( 'value' => 321 ),
		'custom_metric_arm' => array( 'ctr' => 0.83 ),
	) ),
);
$GLOBALS['spdb_test_configured_cohort'] = 20;
$GLOBALS['spdb_test_signals']['experiment-lab'] = $test_variants;
$variant_result = SPDB_Publishing_Intelligence::snapshot( 'experiment-lab' );
$variants = $variant_result['data']['experiments'][0]['variant_metrics'] ?? array();
$assert( true === ( $variants['small_arm']['suppressed'] ?? false ) && ! array_key_exists( 'value', $variants['small_arm'] ?? array() ), 'FPI-02 individual variant below 20 must not expose metric value despite large parent cohort.' );
$assert( 123 === ( $variants['large_arm']['value'] ?? null ), 'FPI-02 eligible variant preserves privacy-safe aggregate.' );
$assert( true === ( $variants['no_cohort_arm']['suppressed'] ?? false ) && ! array_key_exists( 'value', $variants['no_cohort_arm'] ?? array() ), 'FPI-02 variant with unknown cohort must fail closed.' );
$assert( true === ( $variants['custom_metric_arm']['suppressed'] ?? false ) && ! array_key_exists( 'ctr', $variants['custom_metric_arm'] ?? array() ), 'FPI-02 provider-defined numeric metric without a cohort must fail closed.' );
$GLOBALS['spdb_test_configured_cohort'] = 50;
$GLOBALS['spdb_test_signals']['experiment-lab'] = array( array( 'experiment_id' => 'nested-admin-privacy', 'cohort_count' => 120, 'variant_metrics' => array(
	'below_admin' => array( 'cohort_count' => 30, 'value' => 901 ),
	'above_admin' => array( 'cohort_count' => 60, 'value' => 902 ),
) ) );
$admin_variant_result = SPDB_Publishing_Intelligence::snapshot( 'experiment-lab' );
$admin_variants = $admin_variant_result['data']['experiments'][0]['variant_metrics'] ?? array();
$assert( true === ( $admin_variants['below_admin']['suppressed'] ?? false ) && ! array_key_exists( 'value', $admin_variants['below_admin'] ?? array() ), 'FPI-02 stricter admin threshold 50 must suppress variant cohort 30.' );
$assert( 902 === ( $admin_variants['above_admin']['value'] ?? null ), 'FPI-02 stricter admin threshold must preserve eligible cohort 60.' );
$GLOBALS['spdb_test_configured_cohort'] = 20;


/** M13: malformed/coerced cohort strings cannot authorize aggregates. */
$GLOBALS['spdb_test_configured_cohort'] = 20;
$GLOBALS['spdb_test_signals']['audience-board'] = array(
 array( 'dimension' => 'language', 'label' => 'Unverified', 'value' => 99, 'cohort_count' => '20people' ),
 array( 'dimension' => 'language', 'label' => 'Decimal', 'value' => 98, 'cohort_count' => '20.5' ),
 array( 'dimension' => 'language', 'label' => 'Scientific', 'value' => 97, 'cohort_count' => '2e2' ),
 array( 'dimension' => 'language', 'label' => 'Canonical string', 'value' => 96, 'cohort_count' => '25' ),
);
$m13_audience = SPDB_Publishing_Intelligence::snapshot( 'audience-board' );
$assert( 1 === count( $m13_audience['data']['items'] ) && 'Canonical string' === $m13_audience['data']['items'][0]['label'], 'M13 malformed audience count fails closed, canonical digits supported.' );
$GLOBALS['spdb_test_signals']['best-time'] = array( array( 'window' => 'Invalid', 'score' => 0.8, 'observations' => '25fake' ) );
$m13_best = SPDB_Publishing_Intelligence::snapshot( 'best-time' );
$assert( 0 === count( $m13_best['data']['recommended_windows'] ), 'M13 invalid best-time observations must be rejected.' );
$GLOBALS['spdb_test_signals']['experiment-lab'] = array( array( 'experiment_id' => 'fake-parent', 'cohort_count' => '100foo', 'uplift' => 999, 'variant_metrics' => array( 'arm' => array( 'cohort_count' => 50, 'value' => 999 ) ) ) );
$m13_exp = SPDB_Publishing_Intelligence::snapshot( 'experiment-lab' );
$assert( true === $m13_exp['data']['experiments'][0]['suppressed'] && ! isset( $m13_exp['data']['experiments'][0]['uplift'] ), 'M13 invalid parent experiment cohort fails closed.' );
$GLOBALS['spdb_test_signals']['experiment-lab'] = array( array( 'experiment_id' => 'fake-arm', 'cohort_count' => 100, 'variant_metrics' => array( 'arm' => array( 'cohort_count' => '50invalid', 'value' => 12345 ) ) ) );
$m13_arm = SPDB_Publishing_Intelligence::snapshot( 'experiment-lab' );
$assert( true === ( $m13_arm['data']['experiments'][0]['variant_metrics']['arm']['suppressed'] ?? false ) && ! isset( $m13_arm['data']['experiments'][0]['variant_metrics']['arm']['value'] ), 'M13 nested invalid arm cohort fails closed.' );
$GLOBALS['spdb_test_signals']['comment-intelligence'] = array( array( 'cluster' => 'small', 'count' => '30fake' ) );
$m13_comment = SPDB_Publishing_Intelligence::snapshot( 'comment-intelligence' );
$assert( 0 === count( $m13_comment['data']['items'] ), 'M13 malformed comment count fails closed.' );
$GLOBALS['spdb_test_signals']['internal-benchmark'] = array( array( 'metric' => 'engagement', 'definition' => 'Test ratio', 'cohort_count' => '200bad', 'value' => 1.23 ) );
$m13_bench = SPDB_Publishing_Intelligence::snapshot( 'internal-benchmark' );
$assert( 0 === count( $m13_bench['data']['items'] ), 'M13 malformed benchmark count fails closed.' );
$GLOBALS['spdb_test_configured_cohort'] = 50;
$GLOBALS['spdb_test_signals']['audience-board'] = array( array( 'dimension' => 'language', 'label' => 'not enough', 'value' => 88, 'cohort_count' => '60invalid' ) );
$m13_strict = SPDB_Publishing_Intelligence::snapshot( 'audience-board' );
$assert( 0 === count( $m13_strict['data']['items'] ), 'M13 admin threshold 50 cannot be bypassed by leading digits.' );
$GLOBALS['spdb_test_configured_cohort'] = 20;

if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} publishing-intelligence tests failed.\n" ); exit( 1 ); }
echo "All {$tests} File 23 Publishing Intelligence 24 tests passed.\n";
