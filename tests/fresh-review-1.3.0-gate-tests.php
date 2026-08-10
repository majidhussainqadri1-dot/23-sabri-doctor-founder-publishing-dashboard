<?php
/**
 * Executable two-fresh-review law gate for File 23 Version 1.3.0.
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

$review_a_path = $root . '/docs/FILE23-1.3.0-FRESH-REVIEW-A.md';
$review_b_path = $root . '/docs/FILE23-1.3.0-FRESH-REVIEW-B.md';
$review_a = is_file( $review_a_path ) ? (string) file_get_contents( $review_a_path ) : '';
$review_b = is_file( $review_b_path ) ? (string) file_get_contents( $review_b_path ) : '';

$assert( '' !== $review_a, 'Fresh Review A evidence is missing.' );
$assert( '' !== $review_b, 'Fresh Review B evidence is missing.' );

foreach ( array( 'Version 1.3.0', '2026-08-07', 'Review A', 'RA-01', 'RA-05', 'Corrected', 'second independent fresh Review B' ) as $marker ) {
	$assert( false !== stripos( $review_a, $marker ), "Fresh Review A is missing marker: {$marker}." );
}
foreach ( array( 'Version 1.3.0', '2026-08-07', 'Review B', 'Run #275', 'RB-01', 'RB-02', 'zero known unresolved blocker/critical', 'Hostinger staging', 'final branch head' ) as $marker ) {
	$assert( false !== stripos( $review_b, $marker ), "Fresh Review B is missing marker: {$marker}." );
}

$assert( false !== strpos( $review_a, '01973c02ff9e49b5776fbbb8a8925d96b1732dfd' ), 'Review A must identify its corrective closure head.' );
$assert( false !== strpos( $review_b, '1f98e4121e5cc71c54f1436ceb852255389485e2' ), 'Review B must identify the exact runtime/source head it independently reviewed.' );
$assert( false !== stripos( $review_b, 'conclusion **success**' ), 'Review B must identify successful exact-head automated evidence before its independent runtime/source conclusion.' );
$assert( false !== strpos( $review_b, '4df9a2de0f372213d05fc84ba7da1c4de22eaf91' ), 'Review B must identify the File 23 full-plan workflow correction commit.' );
$assert( false !== strpos( $review_b, '7709e00a031eab2e14bdc32ad5fb3daddb44184f' ), 'Review B must identify the baseline workflow correction commit.' );

$runtime = (string) file_get_contents( $root . '/includes/class-spdb-role-workspace-service.php' );
$assert( false !== strpos( $runtime, '$can_institution = $is_founder;' ), 'Post-Review-A runtime must keep institution scope Founder-only.' );
$assert( false === strpos( $runtime, '$can_institution = $is_founder || $is_admin;' ), 'The corrected runtime must not reintroduce Admin inheritance of Founder institution scope.' );

$capability_test = (string) file_get_contents( $root . '/tests/capability-installer-tests.php' );
$assert( false !== strpos( $capability_test, "'5' === get_option( 'spdb_capability_schema_version'" ), 'Review A schema-5 regression correction must remain enforced.' );
$assert( false !== strpos( $capability_test, "'sabri_teacher'" ), 'Review A Teacher Studio regression correction must remain enforced.' );

$full_plan_test = (string) file_get_contents( $root . '/tests/full-plan-completion-tests.php' );
$assert( false !== strpos( $full_plan_test, 'Version:     1.3.0' ), 'Review A 1.3.0 release-identity regression correction must remain enforced.' );
$assert( false !== strpos( $full_plan_test, 'Search, Discovery and Ranking' ), 'Current File 26 ownership regression must remain enforced.' );

$studio_test = (string) file_get_contents( $root . '/tests/studio-boundary-tests.php' );
foreach ( array( 'Admin Studio must not inherit Founder-only institution scope', 'Teacher Studio must remain own-scope', 'Founder identity must take precedence', 'Admin institution projection must fail closed' ) as $marker ) {
	$assert( false !== strpos( $studio_test, $marker ), "Studio-boundary regression is missing marker: {$marker}." );
}

$full_workflow = (string) file_get_contents( $root . '/.github/workflows/file23-full-plan-completion.yml' );
$baseline_workflow = (string) file_get_contents( $root . '/.github/workflows/baseline-integrity.yml' );
$assert( false !== strpos( $full_workflow, 'group: file23-full-plan-${{ github.event.pull_request.number || github.ref }}' ), 'Review B RB-01 fix must keep full-plan workflow concurrency scoped only to PR/ref at workflow level.' );
$assert( false === strpos( $full_workflow, 'group: file23-full-plan-${{ github.event.pull_request.number || github.ref }}-${{ github.job }}-${{ matrix.php' ), 'The invalid pre-RB-01 full-plan concurrency expression must not return.' );
$assert( false !== strpos( $full_workflow, 'php tests/fresh-review-1.3.0-gate-tests.php' ), 'Full-plan workflow must execute this two-fresh-review gate.' );
$assert( false !== strpos( $baseline_workflow, 'group: file23-baseline-${{ github.event.pull_request.number || github.ref }}' ), 'Review B RB-02 fix must keep baseline workflow concurrency scoped only to PR/ref at workflow level.' );
$assert( false === strpos( $baseline_workflow, 'group: file23-baseline-${{ github.event.pull_request.number || github.ref }}-${{ matrix.php' ), 'The invalid pre-RB-02 baseline concurrency expression must not return.' );
$assert( false !== strpos( $baseline_workflow, 'Version:     1.3.0' ) && false !== strpos( $baseline_workflow, 'Stable tag: 1.3.0' ), 'Baseline workflow must enforce current Version 1.3.0 release identity.' );
$assert( false !== strpos( $baseline_workflow, 'Complete File 00–26' ) && false !== strpos( $baseline_workflow, "'search_discovery_owner'   => 'file26'" ), 'Baseline workflow must enforce File 00–26 and File 26 ownership.' );
$assert( false !== strpos( $baseline_workflow, 'php tests/fresh-review-1.3.0-gate-tests.php' ), 'Baseline workflow must execute this two-fresh-review gate.' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} fresh-review law tests failed.\n" );
	exit( 1 );
}

echo "All {$tests} File 23 Version 1.3.0 two-fresh-review law tests passed.\n";
