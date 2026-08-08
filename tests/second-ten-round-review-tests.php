<?php
/** Permanent regression gate for the second fresh ten-round File 23 review. */
$root = dirname( __DIR__ );
$tests = 0;
$failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
};
$read = static function ( string $relative ) use ( $root ): string {
	return (string) file_get_contents( $root . '/' . $relative );
};

$plugin       = $read( 'sabri-publishing-dashboard.php' );
$manifest     = $read( 'includes/class-spdb-module-manifest.php' );
$capabilities = $read( 'includes/class-spdb-capabilities.php' );
$automation   = $read( 'includes/class-spdb-automation-engine.php' );
$workflow     = $read( '.github/workflows/file23-final-release-candidate.yml' );
$build        = $read( 'tools/build-final-release.sh' );
$readme       = $read( 'readme.txt' );

$assert( str_contains( $manifest, 'Complete File 00–26 dependency' ), 'Round 1: dependency manifest must explicitly cover File 00–26.' );
$assert( str_contains( $manifest, "'26'   => self::definition( 'Search, Discovery and Ranking'" ), 'Round 1: File 26 Search/Discovery/Ranking must be represented without ownership duplication.' );
$assert( str_contains( $plugin, 'Complete File 00–26 discovery manifest' ), 'Round 1: public dependency-manifest contract must describe 00–26 coverage.' );

$assert( str_contains( $capabilities, 'verified_session_capabilities' ), 'Round 2: privileged capabilities must have a dedicated current-session MFA gate.' );
$assert( str_contains( $capabilities, "'session_two_factor'" ), 'Round 2: the File 00 session_two_factor assertion must be enforced.' );
foreach ( array( 'spdb_manage_delegations', 'spdb_export_reports', 'spdb_manage_dashboard_settings', 'spdb_repair_owned_data' ) as $capability ) {
	$assert( str_contains( $capabilities, "'{$capability}'" ), "Round 2: {$capability} must remain inside the reviewed capability policy." );
}

$assert( str_contains( $plugin, "add_action( 'admin_post_spdb_download_export', 'spdb_export_download_authorization_gate', 1 )" ), 'Round 5: export download must run a pre-delivery authorization recheck.' );
$assert( str_contains( $plugin, "SPDB_Capabilities::current_user_can( 'spdb_export_reports' )" ), 'Round 5: export download recheck must consume the canonical File 23/File 00 capability path.' );

$assert( str_contains( $automation, "SPDB_Membership_Guard::is_user_approved( \$owner_user_id )" ), 'Round 6: automation execution must revalidate current owner approval.' );
$assert( str_contains( $automation, "user_can( \$owner_user_id, 'spdb_manage_automation_rules' )" ), 'Round 6: automation execution must revalidate the owner capability.' );
$assert( str_contains( $automation, 'spdb_automation_owner_mismatch' ), 'Round 6: queued owner and current rule owner must remain bound.' );

$assert( str_contains( $workflow, 'FILE00_SHA: 3a84c32a6ddad151f2ed09d244fa8aa536a58108' ), 'Round 9: exact File 00 contract pin must be current for this review.' );
$assert( str_contains( $workflow, 'FILE21_SHA: d00f60ce0ca4d1c9860d724a2beb57e3d03e5d5b' ), 'Round 9: exact File 21 contract pin must be current for this review.' );
$assert( str_contains( $workflow, 'FILE22_SHA: 4c5fa4946cca6d89e9d42e0df22659f222451398' ), 'Round 9: exact plan-complete File 22 core contract pin must replace the superseded pin.' );

$assert( str_contains( $plugin, 'Version:     1.2.3' ) && str_contains( $plugin, "define( 'SPDB_VERSION', '1.2.3' )" ), 'Round 10: runtime identity must be Version 1.2.3.' );
$assert( str_contains( $readme, 'Stable tag: 1.2.3' ), 'Round 10: readme stable tag must match Version 1.2.3.' );
$assert( str_contains( $build, 'version="1.2.3"' ) && str_contains( $build, '1\\.2\\.3' ), 'Round 10: deterministic packaging must build and verify Version 1.2.3.' );

if ( $failed ) {
	fwrite( STDERR, "{$failed} of {$tests} second-ten-round review tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} second-ten-round review tests passed.\n";
