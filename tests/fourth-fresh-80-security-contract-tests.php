<?php
/** Fourth fresh 80-round contract regressions for plan/privacy/export hardening. */
$root = dirname( __DIR__ );
$admin = file_get_contents( $root . '/includes/class-spdb-admin-settings.php' );
$export = file_get_contents( $root . '/includes/class-spdb-export-service.php' );
$repo = file_get_contents( $root . '/includes/class-spdb-operations-repository.php' );
$ops = file_get_contents( $root . '/includes/class-spdb-operations-service.php' );
$reports = file_get_contents( $root . '/templates/reports.php' );
$fpi = file_get_contents( $root . '/includes/class-spdb-publishing-intelligence.php' );
$tests = 0; $failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void {
    ++$tests; if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};
$assert( false !== strpos( $admin, "'analytics_min_cohort'       => 20" ) && false !== strpos( $admin, 'max( 20,' ), 'Global analytics settings must default/clamp to cohort >=20.' );
$assert( false !== strpos( $admin, "'export_ttl_hours'          => min( 72, max( 24," ), 'Export TTL setting must be constrained to 24-72 hours.' );
$assert( false !== strpos( $export, "'spdb_export_confirmation_required'" ) && false !== strpos( $export, "'reason_hash'   => hash( 'sha256', \$reason )" ), 'High-risk export request must require explicit confirmation/reason and persist only a reason hash.' );
$assert( false !== strpos( $export, 'user_can_export( (int) get_current_user_id(), true )' ) && false !== strpos( $export, '$require_session_two_factor && true !==' ), 'Interactive export authorization must require current File 00 two-factor assertion.' );
$assert( false !== strpos( $reports, 'name="confirm_export"' ) && false !== strpos( $reports, 'name="reason"' ) && false !== strpos( $reports, 'data-confirm="true"' ), 'Reports UI must visibly collect explicit high-risk confirmation and audit reason.' );
$assert( false !== strpos( $export, '$viewer_user_id === (int) $job[\'owner_user_id\']' ), 'Download URL must only be emitted for the current export owner.' );
$assert( false !== strpos( $repo, "'export_request'" ) && false !== strpos( $repo, "'reason_hash' => \$reason_hash" ), 'Export job creation and audit must be transaction-coupled with reason hash evidence.' );
$assert( false !== strpos( $repo, '$effective_threshold = max( 20, $current_threshold' ) && false !== strpos( $ops, '$effective_threshold = max( 20, $threshold' ), 'Metric snapshot storage and cached reads must enforce the current privacy floor.' );
$assert( false !== strpos( $fpi, "'what-if-planner' === \$feature" ) && false !== strpos( $fpi, '! $can_global || ! self::is_institutional( $user_id )' ), 'What-if Planner must require Founder or explicitly authorized institutional global operator context.' );
$assert( false !== strpos( $fpi, "'untrusted_demand_rows_suppressed'" ) && false !== strpos( $fpi, "in_array( 'file26', \$markers, true )" ), 'Opportunity Radar must enforce File 26 demand provenance.' );
$assert( false !== strpos( $fpi, '(string) ( $item[\'review_type\'] ?? \'\' )' ) && false === strpos( $fpi, "(string) ( \$item['due_at'] ?? '' ) . '|' . \$status" ), 'SLA escalation key must remain stable across due-soon/overdue status changes.' );
$assert( false !== strpos( $fpi, 'private static function parse_timestamp' ) && false !== strpos( $fpi, "preg_match( '/^(\\d{4})-(\\d{2})-(\\d{2})" ), 'Publishing intelligence must use an explicit timestamp grammar rather than unconstrained natural-language input.' );
foreach ( array( "'claim'", "'evidence'", "'medical'", "'rights'", "'privacy'", "'headline'" ) as $class ) { $assert( false !== strpos( $fpi, $class ), 'Semantic diff current-plan class missing: ' . $class ); }
$assert( false === strpos( $fpi, "array( 'reduced-motion', 'motion' )" ) && false !== strpos( $fpi, "'prefers-reduced-motion'" ), 'Accessibility readiness must not use generic motion substring evidence.' );
if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} fourth fresh contract regressions failed.\n" ); exit( 1 ); }
echo "All {$tests} fourth fresh File 23 contract regressions passed.\n";
