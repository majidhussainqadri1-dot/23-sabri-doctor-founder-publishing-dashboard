<?php
/**
 * V12.3 WP-03/10: background-job idempotency is NOT permission to reuse
 * another owner's prior job or an unrelated payload under the same key.
 *
 * Synthetic DB rows and a simulated duplicate-key insert race only.
 */
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

final class SPDB_V123_Idempotency_DB {
	public string $prefix = 'wp_';
	public array $existing = array();
	public bool $collision_after_insert = false;
	public int $reads = 0;
	public int $inserts = 0;
	public function prepare( string $sql, ...$args ): string { return $sql; }
	public function get_row( string $sql, $output = null ) {
		++$this->reads;
		if ( $this->collision_after_insert && 1 === $this->reads ) { return null; }
		return $this->existing;
	}
	public function insert( string $table, array $data ) {
		++$this->inserts;
		return false; // Simulate unique-key insertion race with an existing job.
	}
	public function reset_reads(): void { $this->reads = 0; $this->inserts = 0; }
}
$wpdb = new SPDB_V123_Idempotency_DB();
$GLOBALS['wpdb'] = $wpdb;
$wpdb->existing = array(
	'job_id' => 'job_' . str_repeat( 'a', 32 ),
	'job_type' => 'automation_rule',
	'owner_user_id' => 7,
	'payload_json' => wp_json_encode( array( 'rule_id' => 'rule_original' ) ),
	'status' => 'queued',
	'attempts' => 0,
	'max_attempts' => 5,
	'idempotency_key' => 'automation:existing:version:hash',
	'available_at_gmt' => '2026-11-14 00:00:00',
	'created_at_gmt' => '2026-11-14 00:00:00',
	'updated_at_gmt' => '2026-11-14 00:00:00',
);
$repo = new SPDB_Operations_Repository();
$checks=0; $failed=0;
$check = static function ( bool $valid, string $message ) use ( &$checks, &$failed ): void {
	++$checks;
	if ( ! $valid ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};

$other_owner = $repo->enqueue_job( 'automation_rule', 8, array( 'rule_id' => 'rule_original' ), 'automation:existing:version:hash' );
$check( is_wp_error( $other_owner ) && 'spdb_job_owner_conflict' === $other_owner->get_error_code(), 'Idempotency collision must not disclose or reuse another owner job.' );
$check( 0 === $wpdb->inserts, 'Existing foreign job collision must avoid writes.' );

$wpdb->reset_reads();
$different_payload = $repo->enqueue_job( 'automation_rule', 7, array( 'rule_id' => 'rule_different' ), 'automation:existing:version:hash' );
$check( is_wp_error( $different_payload ) && 'spdb_job_idempotency_payload_conflict' === $different_payload->get_error_code(), 'Same owner cannot reuse an idempotency key for a different action payload.' );

$wpdb->reset_reads();
$exact_duplicate = $repo->enqueue_job( 'automation_rule', 7, array( 'rule_id' => 'rule_original' ), 'automation:existing:version:hash' );
$check( is_array( $exact_duplicate ) && 7 === $exact_duplicate['owner_user_id'], 'True same-owner, same-payload retry still returns its existing job.' );
$check( 0 === $wpdb->inserts, 'Correct idempotent retry adds no duplicate row.' );

$wpdb->reset_reads();
$wpdb->collision_after_insert = true;
$race_owner = $repo->enqueue_job( 'automation_rule', 8, array( 'rule_id' => 'rule_original' ), 'automation:existing:version:hash' );
$check( is_wp_error( $race_owner ) && 'spdb_job_owner_conflict' === $race_owner->get_error_code(), 'Insert uniqueness race still rejects an existing foreign owner.' );
$check( 1 === $wpdb->inserts, 'Synthetic race reached the failed unique-key insert exactly once.' );

$wpdb->reset_reads();
$race_payload = $repo->enqueue_job( 'automation_rule', 7, array( 'rule_id' => 'rule_different' ), 'automation:existing:version:hash' );
$check( is_wp_error( $race_payload ) && 'spdb_job_idempotency_payload_conflict' === $race_payload->get_error_code(), 'Insert race also enforces exact original payload.' );

if ( $failed ) { fwrite( STDERR, "{$failed} / {$checks} V12.3 job-idempotency scope checks failed.\n" ); exit( 1 ); }
echo "All {$checks} V12.3 job-idempotency scope checks passed.\n";
