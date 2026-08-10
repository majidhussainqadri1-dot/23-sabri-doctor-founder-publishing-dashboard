<?php
/** Standalone regression tests for File 23 Future Publishing Intelligence 24. */

define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
$GLOBALS['spdb_test_signals'] = array();
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

if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} publishing-intelligence tests failed.\n" ); exit( 1 ); }
echo "All {$tests} File 23 Publishing Intelligence 24 tests passed.\n";
