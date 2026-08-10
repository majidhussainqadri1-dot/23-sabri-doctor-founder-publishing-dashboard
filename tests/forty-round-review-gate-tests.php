<?php
/**
 * Executable continuity gate for the forty independent File 23 review/fix rounds.
 *
 * The forty-round record is historical hardening evidence from 2026-08-04.
 * This gate verifies that its core controls remain present in 1.3.0; it is not a
 * substitute for the two fresh post-change 1.3.0 review/fix rounds.
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
$read = static fn( string $path ): string => is_file( $path ) ? (string) file_get_contents( $path ) : '';

$audit_path = $root . '/docs/AUDIT-40-ROUND-REVIEW-AND-CORRECTIONS-2026-08-04.md';
$audit      = $read( $audit_path );
$assert( '' !== $audit, 'The forty-round audit document is missing.' );
preg_match_all( '/^### دور (\d{2}) — /mu', $audit, $round_matches );
$assert( 40 === count( $round_matches[1] ?? array() ), 'The audit must contain exactly forty numbered rounds.' );
$expected_rounds = array_map(
	static fn( int $number ): string => sprintf( '%02d', $number ),
	range( 1, 40 )
);
$assert( $expected_rounds === ( $round_matches[1] ?? array() ), 'The audit round sequence must be exactly 01 through 40.' );
foreach ( array( 'مرکزِ نظرِ ثانی', 'دریافت', 'اصلاح', 'دوبارہ آزمائش' ) as $label ) {
	$assert( 40 === substr_count( $audit, '**' . $label . ':**' ), "Every round must contain {$label}." );
}

$files = array(
	'caps'        => $read( $root . '/includes/class-spdb-capabilities.php' ),
	'installer'   => $read( $root . '/includes/class-spdb-capability-installer.php' ),
	'membership'  => $read( $root . '/includes/class-spdb-membership-guard.php' ),
	'governance'  => $read( $root . '/includes/class-spdb-governance-service.php' ),
	'acceptance'  => $read( $root . '/includes/class-spdb-adapter-acceptance.php' ),
	'guard'       => $read( $root . '/includes/class-spdb-operational-mutation-guard.php' ),
	'broker'      => $read( $root . '/includes/class-spdb-operation-broker.php' ),
	'ops_schema'  => $read( $root . '/includes/class-spdb-operations-schema.php' ),
	'col_schema'  => $read( $root . '/includes/class-spdb-collections-schema.php' ),
	'repository'  => $read( $root . '/includes/class-spdb-operations-repository.php' ),
	'exports'     => $read( $root . '/includes/class-spdb-export-service.php' ),
	'background'  => $read( $root . '/includes/class-spdb-background-jobs.php' ),
	'privacy'     => $read( $root . '/includes/class-spdb-privacy-integration.php' ),
	'operations'  => $read( $root . '/includes/class-spdb-operations-service.php' ),
	'rest'        => $read( $root . '/includes/class-spdb-operations-rest-controller.php' ),
	'migration'   => $read( $root . '/includes/class-spdb-legacy-migration-diagnostics.php' ),
	'activation'  => $read( $root . '/includes/class-spdb-activation-wizard.php' ),
	'build'       => $read( $root . '/tools/build-final-release.sh' ),
	'workflow'    => $read( $root . '/.github/workflows/file23-final-release-candidate.yml' ),
	'main'        => $read( $root . '/sabri-publishing-dashboard.php' ),
);

$gates = array(
	array( 'main', array( 'Version:     1.3.0', "SPDB_VERSION', '1.3.0" ) ),
	array( 'caps', array( "return array( 'spdb_manage_safe_mode' )", 'spdb_view_teacher_studio', 'spdb_view_admin_studio' ) ),
	array( 'installer', array( 'private const SCHEMA_VERSION', "= '5';", 'remove_cap' ) ),
	array( 'membership', array( 'canonical_contract_present', 'is_user_founder', 'is_user_trusted_publisher', 'true === $assertions[\'approved\']' ) ),
	array( 'governance', array( 'spdb_task_assignee_invalid', 'spdb_delegation_provider_forbidden', 'spdb_delegation_capability_invalid' ) ),
	array( 'acceptance', array( 'ACCEPTANCE_PRODUCTION_ACCEPTED', 'evidence_hash', 'provider_acceptance_changed', 'return $audit' ) ),
	array( 'guard', array( 'wp_verify_nonce', 'same_origin_value', 'MAX_PAYLOAD_DEPTH', 'spdb_mutation_idempotency_conflict' ) ),
	array( 'guard', array( 'clear_receipt_cache', 'spdb_mutation_commit_failed', 'privacy_export_receipts', 'erase_user_receipts' ) ),
	array( 'broker', array( 'validate_payload', 'spdb_native_reconciliation_required', 'idempotency_key' ) ),
	array( 'ops_schema', array( "VERSION = '1.1.0'", 'ENGINE=InnoDB', 'ensure_transactional_tables' ) ),
	array( 'col_schema', array( "VERSION = '4'", 'ENGINE=InnoDB', 'ensure_transactional_tables' ) ),
	array( 'repository', array( 'private function atomic', 'SAVEPOINT', 'ROLLBACK TO SAVEPOINT' ) ),
	array( 'repository', array( 'GET_LOCK', 'FOR UPDATE', 'RELEASE_LOCK' ) ),
	array( 'repository', array( 'spdb_export_state_conflict', 'export_generated', 'export_failed' ) ),
	array( 'exports', array( 'ENVELOPE_MAGIC', 'aes-256-gcm', 'seal_content', 'open_content' ) ),
	array( 'exports', array( 'realpath', 'hash_file', 'authenticated, owner-bound, expiring signature' ) ),
	array( 'background', array( 'spdb/background_job_claim_failed', 'spdb/background_job_transition_failed' ) ),
	array( 'repository', array( 'job_fail_retry', 'background_job_dead_lettered' ) ),
	array( 'privacy', array( 'mutation_receipts', 'privacy_export', 'erase_user_receipts' ) ),
	array( 'repository', array( 'privacy_collection_export', 'items_pseudonymized', 'privacy_erase' ) ),
	array( 'repository', array( 'retention_cleanup', 'retention_cleanup_completed' ) ),
	array( 'operations', array( 'spdb_ai_evidence_insufficient', 'ai_assistance_requested', 'execution_allowed' ) ),
	array( 'operations', array( 'min( 1000', 'institutional_account', 'false === ( $assertions[\'suspended\']' ) ),
	array( 'rest', array( '/provider-acceptance/', 'record_provider_acceptance', 'write_permission' ) ),
	array( 'migration', array( 'canonical_owner', 'migration' ) ),
	array( 'activation', array( 'backup_restore_evidence', 'rollback_evidence', 'source_commit' ) ),
	array( 'build', array( 'version="1.3.0"', 'git archive', 'SOURCE-MANIFEST' ) ),
	array( 'workflow', array( 'forty-round-review-gate-tests.php', 'AUDIT-40-ROUND-REVIEW-AND-CORRECTIONS', 'file23-1.3.0' ) ),
);
foreach ( $gates as $index => $gate ) {
	foreach ( $gate[1] as $marker ) {
		$assert( false !== strpos( $files[ $gate[0] ], $marker ), sprintf( 'Review gate %02d is missing marker %s.', $index + 1, $marker ) );
	}
}

$all_source = implode( "\n", array_map( $read, glob( $root . '/includes/*.php' ) ?: array() ) );
foreach ( array( 'wp_insert_post(', 'wp_update_post(', 'wp_delete_post(', 'update_post_meta(', 'delete_post_meta(' ) as $forbidden ) {
	$assert( false === strpos( $all_source, $forbidden ), "File 23 must not directly mutate native publication data through {$forbidden}." );
}
$assert( false === strpos( $files['caps'], "'spdb_manage_safe_mode'," ), 'Retired Safe Mode authority must not remain in the canonical capability list.' );
$assert( false !== strpos( $files['exports'], 'file_put_contents( $path, $sealed, LOCK_EX )' ), 'Generated exports must be written only after authenticated encryption.' );
$assert( false === strpos( $files['exports'], 'Protected generated artifact directory.\n' ), 'Legacy plaintext generated-export write must not remain.' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} of {$tests} forty-round review gates failed.\n" );
	exit( 1 );
}
echo "All {$tests} forty-round review continuity gates passed.\n";
