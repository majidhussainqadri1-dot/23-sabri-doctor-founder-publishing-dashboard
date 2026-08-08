<?php
/** Permanent regression gate for the third fresh ten-round File 23 review. */
$root = dirname( __DIR__ );
$tests = 0;
$failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};
$read = static function ( string $relative ) use ( $root ): string { return (string) file_get_contents( $root . '/' . $relative ); };

$main       = $read( 'sabri-publishing-dashboard.php' );
$installer  = $read( 'includes/class-spdb-capability-installer.php' );
$membership = $read( 'includes/class-spdb-membership-guard.php' );
$workspace  = $read( 'includes/class-spdb-workspace-resolver.php' );
$validator  = $read( 'includes/class-spdb-operational-projection-validator.php' );
$oversight  = $read( 'includes/class-spdb-ai-teacher-oversight-rest.php' );
$workflow   = $read( '.github/workflows/file23-final-release-candidate.yml' );
$build      = $read( 'tools/build-final-release.sh' );
$readme     = $read( 'readme.txt' );
$history    = $read( 'tests/second-ten-round-review-tests.php' );

$assert( str_contains( $installer, "SCHEMA_VERSION     = '6'" ), 'Round 1/3: capability schema must force current File 00 role reconciliation.' );
foreach ( array( 'sabri_doctor_pending', 'sabri_doctor_verified', 'sabri_membership_reviewer', 'sabri_membership_senior_reviewer' ) as $role ) {
	$assert( str_contains( $installer, "'{$role}'" ), "Round 1/3: canonical File 00 role missing: {$role}." );
}
$assert( str_contains( $membership, "'institutional_ai'" ) && str_contains( $membership, "'publishing'" ), 'Round 1: current File 00 institutional AI and publishing assertions must be consumed.' );
$assert( str_contains( $membership, 'is_user_verified_doctor' ) && str_contains( $workspace, 'is_user_verified_doctor' ), 'Round 1: Doctor workspace identity must require a doctor assertion rather than generic approval.' );
$assert( str_contains( $workspace, "'institutional_ai'" ) && str_contains( $workspace, "'reviewer'" ) && str_contains( $workspace, "'moderator'" ), 'Round 1: non-doctor workspace identities must remain explicit.' );

$assert( str_contains( $validator, "'ai_teacher'" ), 'Round 2: AI Teacher must be a bounded operational projection domain.' );
$assert( str_contains( $main, 'class-spdb-ai-teacher-oversight-rest.php' ) && str_contains( $main, 'SPDB_AI_Teacher_Oversight_REST::register' ), 'Round 2: AI Teacher oversight controller must load and register.' );
$assert( str_contains( $oversight, "SPDB_Capabilities::current_user_can( 'spdb_view_assurance_status' )" ), 'Round 2: AI Teacher oversight must require a privileged human assurance capability.' );
$assert( str_contains( $oversight, 'is_user_institutional_ai' ) && str_contains( $oversight, 'self_oversight_forbidden' ), 'Round 2: the institutional AI account must not authorize its own oversight.' );
$assert( str_contains( $oversight, "projections( 'ai_teacher'" ), 'Round 2: oversight must reuse federated provider projections rather than create an AI publication backend.' );

$assert( str_contains( $membership, "'guardian_pending'" ), 'Round 4: current File 00 guardian_pending lifecycle state must remain recognized.' );
$assert( str_contains( $workflow, 'FILE22_SHA: 4008521f9860e6181560ac07ff1c7e75868f1982' ), 'Round 9: File 22 exact integration pin must match the latest reviewed candidate used by this audit.' );

$assert( str_contains( $history, "version_compare( \$current_version, '1.2.3', '>=' )" ), 'Round 10: the prior review gate must preserve history without freezing future corrected versions.' );
$assert( str_contains( $main, 'Version:     1.2.4' ) && str_contains( $main, "define( 'SPDB_VERSION', '1.2.4' )" ), 'Round 10: runtime identity must be Version 1.2.4.' );
$assert( str_contains( $readme, 'Stable tag: 1.2.4' ), 'Round 10: readme stable tag must match Version 1.2.4.' );
$assert( str_contains( $build, 'version="1.2.4"' ) && str_contains( $build, '1\\.2\\.4' ), 'Round 10: deterministic packaging must build and verify Version 1.2.4.' );

if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} third-ten-round review tests failed.\n" ); exit( 1 ); }
echo "All {$tests} third-ten-round review tests passed.\n";
