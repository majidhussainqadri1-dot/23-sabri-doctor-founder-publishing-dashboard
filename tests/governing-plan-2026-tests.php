<?php
/**
 * Executable regression gate for the 6–7 August 2026 central/File 23 plans.
 */

$root   = dirname( __DIR__ );
$tests  = 0;
$failed = 0;

$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
};

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $root . '/' );
}
require_once $root . '/includes/class-spdb-governing-plan.php';

$expected = array();
foreach ( array( array( 50, 62 ), array( 229, 285 ) ) as $range ) {
	for ( $number = $range[0]; $number <= $range[1]; ++$number ) {
		$expected[] = sprintf( 'CV-%03d', $number );
	}
}
$actual = SPDB_Governing_Plan::inherited_requirements();
$assert( 70 === count( $actual ), 'The amended File 23 plan must expose exactly 70 inherited CV requirements.' );
$assert( $expected === $actual, 'The executable CV requirement set must be CV-050–062 plus CV-229–285 with no gaps or extras.' );
$assert( 70 === count( array_unique( $actual ) ), 'Inherited CV requirement IDs must be unique.' );
$assert( '#087A4E' === SPDB_Governing_Plan::PRIMARY_GREEN_FALLBACK, 'Sabri Green fallback must be #087A4E.' );
$assert( 26 === SPDB_Governing_Plan::FILE_COUNT_MAX, 'The governing architecture must include File 26.' );

$journeys = SPDB_Governing_Plan::acceptance_journeys();
foreach ( array( 'AJ-06', 'AJ-09', 'AJ-10', 'AJ-24', 'AJ-25', 'AJ-26', 'AJ-31', 'AJ-32', 'AJ-33', 'AJ-34', 'AJ-35', 'AJ-36', 'AJ-37', 'AJ-38', 'AJ-39', 'AJ-40' ) as $journey ) {
	$assert( in_array( $journey, $journeys, true ), "Missing amended File 23 acceptance journey {$journey}." );
}

$owners = SPDB_Governing_Plan::canonical_owners();
$assert( 'file00' === ( $owners['identity_authorization'] ?? '' ), 'File 00 must remain the identity/authorization owner.' );
$assert( 'file20' === ( $owners['application_shell'] ?? '' ), 'File 20 must remain the shell owner.' );
$assert( 'file21' === ( $owners['publication_truth'] ?? '' ), 'File 21 must remain the publication truth owner.' );
$assert( 'file22' === ( $owners['composer'] ?? '' ), 'File 22 must remain the composer owner.' );
$assert( 'file23' === ( $owners['publishing_dashboard'] ?? '' ), 'File 23 must own only the publishing dashboard domain.' );
$assert( 'file24' === ( $owners['assurance'] ?? '' ), 'File 24 must remain the assurance owner.' );
$assert( 'file25' === ( $owners['visual_tokens'] ?? '' ), 'File 25 must remain the visual-token owner.' );
$assert( 'file26' === ( $owners['search_discovery'] ?? '' ), 'File 26 must remain Search/Discovery/Ranking owner.' );

$guardrails = SPDB_Governing_Plan::guardrails();
foreach ( array( 'one_canonical_owner', 'single_free_tier', 'file26_enabled', 'staging_first', 'two_fresh_reviews' ) as $key ) {
	$assert( true === ( $guardrails[ $key ] ?? null ), "Governing guardrail {$key} must be enabled." );
}
foreach ( array( 'direct_domain_table_write', 'duplicate_domain_truth', 'donor_advantage', 'paid_or_donor_ranking_bias', 'ai_clinical_authority' ) as $key ) {
	$assert( false === ( $guardrails[ $key ] ?? null ), "Forbidden governing behavior {$key} must remain disabled." );
}

$manifest = (string) file_get_contents( $root . '/includes/class-spdb-module-manifest.php' );
foreach ( array( 'File 00–26', "'26'", 'Search, Discovery and Ranking', "'search_discovery_owner'   => 'file26'", "'commercial_access_policy' => 'single_free_tier'", "'direct_domain_table_write'=> false" ) as $marker ) {
	$assert( false !== strpos( $manifest, $marker ), "Dependency/assurance manifest is missing: {$marker}." );
}

$caps = (string) file_get_contents( $root . '/includes/class-spdb-capabilities.php' );
foreach ( array( 'spdb_view_teacher_studio', 'spdb_view_admin_studio' ) as $capability ) {
	$assert( false !== strpos( $caps, $capability ), "Missing studio capability {$capability}." );
}

$installer = (string) file_get_contents( $root . '/includes/class-spdb-capability-installer.php' );
$assert( false !== strpos( $installer, "private const SCHEMA_VERSION     = '5';" ), 'Capability schema must be version 5.' );
$assert( false !== strpos( $installer, "'sabri_teacher'" ), 'Existing sabri_teacher roles must receive the bounded Teacher Studio matrix without role creation.' );

$resolver = (string) file_get_contents( $root . '/includes/class-spdb-workspace-resolver.php' );
foreach ( array( "'admin'", "'teacher'", 'spdb_view_admin_studio', 'spdb_view_teacher_studio' ) as $marker ) {
	$assert( false !== strpos( $resolver, $marker ), "Workspace resolver is missing studio marker {$marker}." );
}

$workspace = (string) file_get_contents( $root . '/includes/class-spdb-role-workspace-service.php' );
foreach ( array( 'admin_federated', 'teacher_educational', 'founder_official', 'doctor_reviewed' ) as $mode ) {
	$assert( false !== strpos( $workspace, $mode ), "Role workspace service is missing publishing mode {$mode}." );
}
$assert( false !== strpos( $workspace, "if ( \$contract['founder_only'] && empty( \$context['is_founder'] ) )" ), 'Founder-only actions must remain Founder-only after Admin Studio introduction.' );

$css = (string) file_get_contents( $root . '/assets/css/dashboard-corrections.css' );
$assert( false !== strpos( $css, 'var(--sabri-color-primary, #087A4E)' ), 'File 23 must consume File 25 primary token with Sabri Green fallback.' );
$assert( false !== strpos( $css, 'prefers-reduced-data: reduce' ), 'File 23 must expose a local reduced-data presentation guard.' );

$trace_path = $root . '/docs/GOVERNING-PLAN-2026-TRACEABILITY.json';
$assert( is_file( $trace_path ), 'Machine-readable governing-plan traceability is missing.' );
$trace = is_file( $trace_path ) ? json_decode( (string) file_get_contents( $trace_path ), true ) : null;
$assert( is_array( $trace ), 'Governing-plan traceability JSON must decode successfully.' );
$trace_ids = array();
if ( is_array( $trace ) ) {
	foreach ( (array) ( $trace['groups'] ?? array() ) as $group ) {
		foreach ( (array) ( $group['ids'] ?? array() ) as $id ) {
			$trace_ids[] = (string) $id;
		}
	}
}
$assert( $expected === $trace_ids, 'Machine-readable traceability must contain the same exact 70 CV IDs.' );
$assert( '2026-08-07' === (string) ( $trace['plan_revision'] ?? '' ), 'Traceability must identify the amended governing-plan revision.' );
$assert( isset( $trace['file_specific']['F23-CEN-01'] ), 'Traceability must include F23-CEN-01.' );

$plugin = (string) file_get_contents( $root . '/sabri-publishing-dashboard.php' );
$readme = (string) file_get_contents( $root . '/readme.txt' );
foreach ( array( 'Version:     1.3.0', "define( 'SPDB_VERSION', '1.3.0' )", 'Complete File 00–26 discovery manifest' ) as $marker ) {
	$assert( false !== strpos( $plugin, $marker ), "Plugin bootstrap is missing 1.3.0 governing marker: {$marker}." );
}
foreach ( array( 'Stable tag: 1.3.0', 'File 26', 'Teacher', 'Admin', '#087A4E', 'single-free-tier' ) as $marker ) {
	$assert( false !== stripos( $readme, $marker ), "Readme is missing governing marker: {$marker}." );
}

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} governing-plan tests failed.\n" );
	exit( 1 );
}

echo "All {$tests} File 23 governing-plan 2026 tests passed.\n";
