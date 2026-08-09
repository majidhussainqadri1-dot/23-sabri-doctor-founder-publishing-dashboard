<?php
/** Permanent regression gate for the second fresh ten-round File 23 review. */
$root = dirname( __DIR__ );
$tests = 0;
$failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};
$read = static function ( string $relative ) use ( $root ): string { return (string) file_get_contents( $root . '/' . $relative ); };

$plugin       = $read( 'sabri-publishing-dashboard.php' );
$manifest     = $read( 'includes/class-spdb-module-manifest.php' );
$capabilities = $read( 'includes/class-spdb-capabilities.php' );
$automation   = $read( 'includes/class-spdb-automation-engine.php' );
$workflow     = $read( '.github/workflows/file23-final-release-candidate.yml' );
$build        = $read( 'tools/build-final-release.sh' );
$readme       = $read( 'readme.txt' );
$audit        = $read( 'docs/AUDIT-SECOND-10-ROUND-REVIEW-AND-CORRECTIONS-2026-08-08.md' );

$assert( str_contains( $manifest, 'Complete File 00–26 dependency' ), 'The File 00–26 dependency boundary established by the second review must remain.' );
$assert( str_contains( $manifest, "'26'   => self::definition( 'Search, Discovery and Ranking'" ), 'File 26 search ownership must remain represented without duplication.' );
$assert( str_contains( $capabilities, 'verified_session_capabilities' ) && ( str_contains( $capabilities, "'session_two_factor'" ) || str_contains( $capabilities, 'has_sensitive_session' ) ), 'Privileged current-session assurance enforcement must remain.' );
$assert( str_contains( $plugin, "add_action( 'admin_post_spdb_download_export', 'spdb_export_download_authorization_gate', 1 )" ), 'Download-time export authorization recheck must remain.' );
$assert( str_contains( $automation, 'spdb_automation_owner_mismatch' ) && str_contains( $automation, "SPDB_Membership_Guard::is_user_approved( \$owner_user_id )" ), 'Automation owner revalidation must remain.' );

/* Preserve exactly the precision that the historical audit actually recorded. */
$assert( str_contains( $audit, '4c5fa494' ), 'The second-review audit must retain its historical File 22 pin evidence.' );
$assert( str_contains( $workflow, 'FILE00_SHA: 3a84c32a6ddad151f2ed09d244fa8aa536a58108' ), 'The current File 00 exact contract pin must remain explicit.' );
$assert( 1 === preg_match( '/FILE21_SHA: [0-9a-f]{40}/', $workflow ), 'The current File 21 contract must remain pinned to an immutable reviewed SHA; later reviewed heads may advance.' );
$assert( 1 === preg_match( '/FILE22_SHA: [0-9a-f]{40}/', $workflow ), 'The current File 22 exact contract pin must remain an immutable SHA.' );

$current_version = '';
if ( preg_match( "/define\( 'SPDB_VERSION', '([^']+)' \)/", $plugin, $match ) ) { $current_version = (string) $match[1]; }
$assert( '' !== $current_version && version_compare( $current_version, '1.2.3', '>=' ), 'Later corrected releases must not regress below the second-review Version 1.2.3 baseline.' );
$assert( str_contains( $plugin, 'Version:     ' . $current_version ), 'Plugin header must match current runtime version.' );
$assert( str_contains( $readme, 'Stable tag: ' . $current_version ), 'Readme stable tag must match current runtime version.' );
$assert( str_contains( $build, 'version="' . $current_version . '"' ), 'Deterministic packaging must match current runtime version.' );

if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} second-ten-round review tests failed.\n" ); exit( 1 ); }
echo "All {$tests} second-ten-round review tests passed.\n";
