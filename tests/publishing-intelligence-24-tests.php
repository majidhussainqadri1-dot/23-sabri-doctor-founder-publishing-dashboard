<?php
/** Future Publishing Intelligence 24-feature completion and boundary tests. */
require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/class-spdb-publishing-intelligence.php';

$tests = 0;
$failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
};

$catalog = SPDB_Publishing_Intelligence::catalog();
$assert( 24 === count( $catalog ), 'The Future Publishing Intelligence catalogue must contain exactly 24 approved facilities.' );
$ids = array_column( $catalog, 'id' );
$assert( 24 === count( array_unique( $ids ) ), 'All 24 F23-FPI IDs must be unique.' );
for ( $i = 1; $i <= 24; ++$i ) {
	$id = sprintf( 'F23-FPI-%02d', $i );
	$assert( in_array( $id, $ids, true ), "Missing governed feature {$id}." );
}
foreach ( $catalog as $feature ) {
	$assert( false === $feature['canonical_write_authority'], $feature['id'] . ' must not have canonical write authority.' );
	$assert( true === $feature['advisory_or_projection_only'], $feature['id'] . ' must remain advisory/projection only.' );
	$assert( 'file26' === $feature['search_ranking_owner'], $feature['id'] . ' must preserve File 26 search/ranking ownership.' );
	$assert( false === $feature['donor_payment_influence'], $feature['id'] . ' must preserve donor/payment neutrality.' );
	$assert( false === $feature['auto_publish'] && false === $feature['auto_schedule'], $feature['id'] . ' must never auto-publish or auto-schedule.' );
}

$by_id = array_column( $catalog, null, 'id' );
$assert( 'file26' === $by_id['F23-FPI-05']['canonical_owner'], 'Content Opportunity Radar must preserve File 26 canonical ownership.' );
$assert( 'file19' === $by_id['F23-FPI-07']['delivery_owner'], 'Review SLA escalation delivery must remain File 19-owned.' );
$assert( 'file22' === $by_id['F23-FPI-15']['final_creation_owner'], 'Cross-format repurposing final creation must remain File 22-owned.' );
$assert( 'file25' === $by_id['F23-FPI-22']['public_visual_owner'], 'Accessibility Publishing Lab must preserve File 25 public visual ownership.' );

$GLOBALS['spdb_test_capabilities']['spdb_view_dashboard'] = true;
$GLOBALS['spdb_test_member_status'] = 'verified';
$service = new SPDB_Publishing_Intelligence();
$service->register_routes();
foreach ( array(
	'spdb/v1/intelligence/catalog',
	'spdb/v1/intelligence/(?P<feature>F23-FPI-\\d{2})',
	'spdb/v1/intelligence/ask',
	'spdb/v1/intelligence/simulate',
) as $route ) {
	$assert( isset( $GLOBALS['spdb_test_rest_routes'][ $route ] ), "Private intelligence route missing: {$route}." );
}

$source = (string) file_get_contents( dirname( __DIR__ ) . '/includes/class-spdb-publishing-intelligence.php' );
foreach ( array( 'wp_insert_post(', 'wp_update_post(', 'wp_delete_post(', 'register_post_type(', 'update_post_meta(' ) as $forbidden ) {
	$assert( false === strpos( $source, $forbidden ), "Publishing intelligence must not contain native mutation call {$forbidden}." );
}
$assert( false !== strpos( $source, 'DEFAULT_PRIVACY_THRESHOLD = 20' ), 'Default small-cohort privacy threshold must be at least 20.' );
$assert( false !== strpos( $source, "spdb/publishing_intelligence_signals" ), 'Federated signal intake contract is required.' );
$assert( false !== strpos( $source, "spdb/publishing_intelligence_ask" ), 'Approved conversational provider hook is required.' );
$assert( false !== strpos( $source, "'side_effects'              => false" ), 'What-if simulation must be side-effect free.' );

// Regression: cohort evidence is mandatory for thresholded intelligence snapshots.
foreach ( array(
	array( 'signals' => array( 'metric' => 'private-aggregate' ), 'suppressed' => true, 'visible' => false ),
	array( 'signals' => array( 'cohort_count' => 'invalid', 'metric' => 'private-aggregate' ), 'suppressed' => true, 'visible' => false ),
	array( 'signals' => array( 'cohort_count' => 19, 'metric' => 'private-aggregate' ), 'suppressed' => true, 'visible' => false ),
	array( 'signals' => array( 'cohort_count' => 20, 'metric' => 'eligible-aggregate' ), 'suppressed' => false, 'visible' => true ),
) as $case ) {
	$GLOBALS['wp_filter']['spdb/publishing_intelligence_signals'] = array(
		static function ( $value, $context ) use ( $case ) { return $case['signals']; },
	);
	$view = $service->rest_feature( new WP_REST_Request( '', array( 'feature' => 'F23-FPI-03' ) ) );
	$assert( $case['suppressed'] === $view['suppressed'], 'Thresholded feature must fail closed when cohort is absent, invalid or small.' );
	$assert( $case['visible'] === isset( $view['signals']['metric'] ), 'Suppression must not return an aggregate metric.' );
}
$GLOBALS['wp_filter']['spdb/publishing_intelligence_signals'] = array(
	static function ( $value, $context ) { return array( 'editorial_status' => 'available' ); },
);
$unthresholded = $service->rest_feature( new WP_REST_Request( '', array( 'feature' => 'F23-FPI-01' ) ) );
$assert( false === $unthresholded['suppressed'] && 'available' === $unthresholded['signals']['editorial_status'], 'Non-thresholded operational metadata must remain available.' );
unset( $GLOBALS['wp_filter']['spdb/publishing_intelligence_signals'] );

if ( $failed ) {
	fwrite( STDERR, "{$failed} of {$tests} publishing-intelligence tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} Future Publishing Intelligence tests passed.\n";
