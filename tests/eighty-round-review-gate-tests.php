<?php
/** Executable gate for the 10 August 2026 File 23 eighty-round review/fix closure. */

$root = dirname( __DIR__ );
$tests = 0;
$failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};

$audit_path = $root . '/docs/AUDIT-80-ROUND-REVIEW-AND-CORRECTIONS-2026-08-10.md';
$audit = is_file( $audit_path ) ? (string) file_get_contents( $audit_path ) : '';
$assert( '' !== $audit, 'Current 80-round audit record is missing.' );
$assert( false !== strpos( $audit, 'Starting exact repository candidate:** `10054f8bf249ffc930d490e440576144042e56f9`' ), 'Audit must identify the exact starting repository candidate.' );
$assert( false !== strpos( $audit, 'Defect-bearing rounds: **43** (Rounds 1–43)' ), 'Audit must record the defect-bearing round count.' );
$assert( false !== strpos( $audit, 'Clean fresh rounds after corrections: **37** (Rounds 44–80)' ), 'Audit must record the clean closure round count.' );

$rounds = array();
if ( preg_match_all( '/^\|\s*(\d{1,2})\s*\|/m', $audit, $matches ) ) {
	$rounds = array_map( 'intval', $matches[1] );
}
$assert( range( 1, 80 ) === $rounds, 'Audit table must contain every ordered round 1 through 80 exactly once.' );
for ( $round = 1; $round <= 43; ++$round ) {
	$assert( 1 === preg_match( '/^\|\s*' . $round . '\s*\|.*\|\s*DEFECT → FIXED\s*\|/m', $audit ), "Round {$round} must record defect → fixed." );
}
for ( $round = 44; $round <= 80; ++$round ) {
	$assert( 1 === preg_match( '/^\|\s*' . $round . '\s*\|.*\|\s*CLEAN\s*\|/m', $audit ), "Round {$round} must record a clean fresh review." );
}

$intelligence = (string) file_get_contents( $root . '/includes/class-spdb-publishing-intelligence.php' );
foreach ( array(
	"SPDB_Capabilities::current_user_can( 'spdb_view_dashboard' )",
	"'permission-simulator' === \$feature",
	"'institution' === \$scope",
	'privacy_threshold()',
	'MIN_PRIVACY_THRESHOLD = 20',
	'contains_sensitive_text',
	'remove_sensitive_keys',
	'click_time_reauthorization',
	'manual_acceptance_required',
	'individual_profiling',
	'question_hash',
	'ranking_owner',
	'private_reviewer_notes_included',
	'escalation_key',
	'destructive_cascade',
	'normalize_evidence_status',
	'raw_patient_documents_owned',
	'founder_only',
	'silent_rewrite',
	'bypass_requires_authorized_reason',
	'final_creation_owner',
	'raw_user_list',
	'public_shaming_or_ranking',
	'provider_disable_path_required',
	'raw_private_comments',
	'normalize_evergreen_status',
	'source_version_mismatch',
	'certification_claim',
	'missing_provenance_status',
	'reviewer_day_key',
	'scenario_hash',
	'safe_public_url',
) as $marker ) {
	$assert( false !== strpos( $intelligence, $marker ), 'Corrected publishing-intelligence implementation is missing marker: ' . $marker );
}

$governing = (string) file_get_contents( $root . '/includes/class-spdb-governing-plan.php' );
$assert( false !== strpos( $governing, "REVISION               = '2026-08-10'" ), 'Executable governing-plan revision must be 2026-08-10.' );
$assert( false !== strpos( $governing, "'search_discovery'       => 'file26'" ), 'File 26 Search/Discovery ownership must remain frozen.' );
$assert( false !== strpos( $governing, "'assurance'              => 'file24'" ), 'File 24 assurance ownership must remain frozen.' );
$assert( false !== strpos( $governing, "'visual_tokens'          => 'file25'" ), 'File 25 visual ownership must remain frozen.' );

$template = (string) file_get_contents( $root . '/templates/dashboard.php' );
$assert( false !== strpos( $template, 'File 00–26 Dependency Manifest' ), 'Dashboard manifest heading must reflect File 00–26.' );
$assert( false === strpos( $template, 'File 00–25 Dependency Manifest' ), 'Stale File 00–25 manifest heading must not remain.' );

$readme = (string) file_get_contents( $root . '/README.md' );
$assert( false !== strpos( $readme, '1.3.0 governing-plan + Future Publishing Intelligence candidate' ), 'README must describe the current 1.3.0/FPI candidate.' );
$assert( false !== strpos( $readme, 'Complete File 00–26 dependency manifest' ), 'README must describe File 00–26 architecture.' );

$status = (string) file_get_contents( $root . '/STATUS.md' );
$assert( false !== strpos( $status, 'Governing plan revision represented in code: `2026-08-10`' ), 'STATUS must use current governing revision.' );
$assert( false !== strpos( $status, 'F23-FPI-01–F23-FPI-24' ), 'STATUS must record the FPI 24 implementation.' );

$trace = json_decode( (string) file_get_contents( $root . '/docs/GOVERNING-PLAN-2026-TRACEABILITY.json' ), true );
$assert( is_array( $trace ) && '2026-08-10' === (string) ( $trace['plan_revision'] ?? '' ), 'Machine traceability must use 2026-08-10 revision.' );
$assert( is_array( $trace['future_publishing_intelligence'] ?? null ) && 24 === (int) ( $trace['future_publishing_intelligence']['count'] ?? 0 ), 'Machine traceability must map all 24 FPI requirements.' );

$signoff = (string) file_get_contents( $root . '/docs/RELEASE-SIGNOFF.md' );
$assert( false !== strpos( $signoff, 'Governing plan revision represented: `2026-08-10`' ), 'Release sign-off must use current plan revision.' );
$assert( false !== strpos( $signoff, 'Current 2026-08-10 eighty-round review/fix record' ), 'Release sign-off must require the current 80-round review evidence.' );

$workflow_files = array(
	$root . '/.github/workflows/file23-final-release-candidate.yml',
	$root . '/.github/workflows/file23-full-plan-completion.yml',
);
foreach ( $workflow_files as $workflow_path ) {
	$workflow = (string) file_get_contents( $workflow_path );
	$assert( false !== strpos( $workflow, 'plan_revision=2026-08-10' ), basename( $workflow_path ) . ' must stamp the current plan revision.' );
	$assert( false !== strpos( $workflow, 'php tests/publishing-intelligence-security-regression-tests.php' ), basename( $workflow_path ) . ' must run FPI security/privacy regressions.' );
	$assert( false !== strpos( $workflow, 'php tests/eighty-round-review-gate-tests.php' ), basename( $workflow_path ) . ' must run the 80-round review gate.' );
	$assert( false !== strpos( $workflow, 'docs/AUDIT-80-ROUND-REVIEW-AND-CORRECTIONS-2026-08-10.md' ), basename( $workflow_path ) . ' must require/package the current review evidence.' );
}

$fpi_test = (string) file_get_contents( $root . '/tests/publishing-intelligence-security-regression-tests.php' );
$assert( false !== strpos( $fpi_test, 'Reviewer daily capacity must reset per calendar date' ), 'FPI regression must lock the reviewer daily-capacity correction.' );
$assert( false !== strpos( $fpi_test, 'Sensitive Ask prompt must be rejected before provider invocation' ), 'FPI regression must lock sensitive Ask input suppression.' );
$assert( false !== strpos( $fpi_test, 'Role/permission simulator must be Founder-only' ), 'FPI regression must lock Founder-only permission simulation.' );

if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} eighty-round review gate assertions failed.\n" ); exit( 1 ); }
echo "All {$tests} File 23 eighty-round review gate assertions passed.\n";
