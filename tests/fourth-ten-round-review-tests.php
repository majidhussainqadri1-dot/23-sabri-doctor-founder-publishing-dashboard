<?php
/** Permanent regression gate for the fourth fresh ten-round File 23 review. */
$root = dirname( __DIR__ );
$tests = 0;
$failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};
$read = static function ( string $relative ) use ( $root ): string { return is_file( $root . '/' . $relative ) ? (string) file_get_contents( $root . '/' . $relative ) : ''; };

$main        = $read( 'sabri-publishing-dashboard.php' );
$membership  = $read( 'includes/class-spdb-membership-guard.php' );
$workspace   = $read( 'includes/class-spdb-workspace-resolver.php' );
$rolews      = $read( 'includes/class-spdb-role-workspace-service.php' );
$caps        = $read( 'includes/class-spdb-capabilities.php' );
$sensitive   = $read( 'includes/class-spdb-sensitive-session-guard.php' );
$workflow    = $read( '.github/workflows/file23-final-release-candidate.yml' );
$history     = $read( 'tests/second-ten-round-review-tests.php' );
$prior       = $read( 'tests/third-ten-round-review-tests.php' );
$limitations = $read( 'docs/KNOWN-LIMITATIONS.md' );
$signoff     = $read( 'docs/RELEASE-SIGNOFF.md' );
$audit       = $read( 'docs/AUDIT-FOURTH-10-ROUND-REVIEW-AND-CORRECTIONS-2026-08-09.md' );
$readme      = $read( 'readme.txt' );
$build       = $read( 'tools/build-final-release.sh' );

$assert( str_contains( $membership, "'doctor_verification_claim'" ) && str_contains( $membership, "'verified_doctor' ===" ), 'Round 1: verified Doctor identity must be derived from canonical File 00 publishing assertions.' );
$assert( ! preg_match( "/membership_type[^\n]{0,160}doctor/", $membership ), 'Round 1: membership_type must not be a verified-Doctor fallback.' );
$assert( str_contains( $membership, 'has_sensitive_session' ) && str_contains( $membership, 'can_user_open_composer' ), 'Round 2: current strong-session and composer assertions must remain explicit.' );
$assert( str_contains( $workspace, '! SPDB_Membership_Guard::has_sensitive_session' ) && str_contains( $workspace, 'current_user_can_open_composer' ), 'Round 2: writable workspace/Create visibility must remain bound to current File 00 assurance.' );
$assert( str_contains( $rolews, "'institutional_ai'" ) && str_contains( $rolews, "'reviewer'" ) && str_contains( $rolews, "'moderator'" ) && str_contains( $rolews, "'restricted'" ), 'Round 3: role workspace projection must explicitly classify non-doctor identities.' );
$assert( str_contains( $main, 'class-spdb-sensitive-session-guard.php' ) && str_contains( $main, 'SPDB_Sensitive_Session_Guard::register' ), 'Round 4: central sensitive-session mutation guard must load and register.' );
$assert( str_contains( $sensitive, 'current_user_has_sensitive_session' ) && str_contains( $sensitive, 'same_origin_request' ), 'Rounds 4/5: sensitive writes require current File 00 assurance and same-origin validation.' );
foreach ( array( 'approve|request-changes|reject|assign-reviewer', 'schedule|reschedule|unschedule' ) as $marker ) {
	$assert( str_contains( $sensitive, $marker ), 'Round 5: native review/calendar mutation coverage is missing: ' . $marker );
}
$assert( 1 === preg_match( '/FILE21_SHA: [0-9a-f]{40}/', $workflow ), 'Round 6: File 21 must remain pinned to an immutable reviewed 40-character head; later reviewed heads may supersede the historical fourth-review pin.' );
$assert( str_contains( $caps, 'verified_publishing_identity_capabilities' ) && str_contains( $caps, 'is_user_verified_doctor' ) && str_contains( $caps, 'has_sensitive_session' ), 'Round 7: stale WordPress capabilities must not replace current File 00 publishing identity/session assurance.' );
$assert( str_contains( $history, "preg_match( '/FILE21_SHA: [0-9a-f]{40}/'" ), 'Round 8: historical second-review gate must allow a later immutable reviewed File 21 pin.' );
$assert( str_contains( $limitations, 'F23-LIM-011' ) && str_contains( $limitations, '3a84c32a6ddad151f2ed09d244fa8aa536a58108' ) && str_contains( $limitations, 'Critical/High' ), 'Round 9: current File 00 production blocker must remain explicit.' );
$assert( str_contains( $signoff, 'Current File 00 exact head has no unresolved production-blocking Critical/High' ), 'Round 9: File 00 corrective acceptance must remain an explicit release gate.' );
$assert( substr_count( $audit, '**DEFECT**' ) === 10 && str_contains( $audit, '10 of 10' ), 'Round 10: fourth-review audit must truthfully record all ten defect-bearing rounds.' );
$assert( str_contains( $prior, "version_compare( \$current_version, '1.2.4', '>=' )" ), 'Round 10: third-review evidence must be forward-compatible without losing the 1.2.4 baseline.' );
$current = '';
if ( preg_match( "/define\( 'SPDB_VERSION', '([^']+)' \)/", $main, $m ) ) { $current = (string) $m[1]; }
$assert( '' !== $current && version_compare( $current, '1.2.5', '>=' ), 'Round 10: later corrected runtime identities may advance but must not regress below Version 1.2.5.' );
$assert( '' !== $current && str_contains( $readme, 'Stable tag: ' . $current ), 'Round 10: readme stable tag must match the current corrected runtime identity.' );
$assert( '' !== $current && str_contains( $build, 'version="' . $current . '"' ), 'Round 10: deterministic packaging must follow the current corrected runtime identity.' );

if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} fourth-ten-round review tests failed.\n" ); exit( 1 ); }
echo "All {$tests} fourth-ten-round review tests passed.\n";
