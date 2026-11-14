<?php
/**
 * V12.3 WP-10/WP-12 bounded audit-chain verification.
 *
 * Synthetic source test: 2,005 append-only records, mid-chain tampering and
 * a failed database page. It does not attest a real Hostinger audit table.
 */
require_once __DIR__ . '/bootstrap.php';

final class SPDB_V123_Audit_Page_DB {
    public string $prefix = 'wp_';
    public array $rows = array();
    public int $bounded_queries = 0;
    public int $unbounded_queries = 0;
    public ?int $fail_after_cursor = null;
    public function prepare( string $sql, ...$args ): string { return vsprintf( $sql, $args ); }
    public function get_results( string $sql, $output = null ) {
        if ( ! preg_match( '/WHERE id > (\d+) ORDER BY id ASC LIMIT (\d+)/', $sql, $matches ) ) {
            ++$this->unbounded_queries;
            return null; // Unbounded read forbidden by the memory-safety test.
        }
        ++$this->bounded_queries;
        $cursor = (int) $matches[1];
        $limit = (int) $matches[2];
        if ( $limit < 1 || $limit > 1000 ) { return null; }
        if ( null !== $this->fail_after_cursor && $cursor >= $this->fail_after_cursor ) {
            return null;
        }
        $out = array();
        foreach ( $this->rows as $row ) {
            if ( (int) $row['id'] > $cursor ) {
                $out[] = $row;
                if ( count( $out ) >= $limit ) { break; }
            }
        }
        return $out;
    }
}

$failed = 0;
$checks = 0;
$check = static function ( bool $yes, string $message ) use ( &$checks, &$failed ): void {
    ++$checks;
    if ( ! $yes ) { ++$failed; fwrite( STDERR, 'FAIL: ' . $message . "\n" ); }
};
$database = new SPDB_V123_Audit_Page_DB();
$GLOBALS['wpdb'] = $database;
$previous = str_repeat( '0', 64 );
for ( $i = 1; $i <= 2005; ++$i ) {
    $row = array(
        'id' => $i,
        'event_id' => 'audit_' . $i,
        'actor_user_id' => 7,
        'event_key' => 'source_review',
        'object_ref_hash' => hash( 'sha256', 'example:' . $i ),
        'payload_json' => '{"status":"safe"}',
        'previous_hash' => $previous,
        'created_at_gmt' => '2026-11-14 00:00:00',
    );
    $row['event_hash'] = hash( 'sha256', implode( '|', array(
        $row['event_id'], (string) $row['actor_user_id'], $row['event_key'],
        $row['object_ref_hash'], $row['payload_json'], $previous,
        $row['created_at_gmt'],
    ) ) );
    $previous = $row['event_hash'];
    $database->rows[] = $row;
}
$repository = new SPDB_Operations_Repository();
$health = $repository->audit_health();
$check( true === ( $health['healthy'] ?? null ), '2,005 valid audit-chain events must verify without memory-unbounded SQL.' );
$check( 2005 === ( $health['events'] ?? null ), 'Audit health must count every verified event.' );
$check( $database->bounded_queries >= 3, 'Audit reader must issue multiple keyset pages.' );
$check( 0 === $database->unbounded_queries, 'No SELECT * of the entire audit log.' );

$database->rows[1509]['payload_json'] = '{"status":"tampered"}';
$corrupt = $repository->audit_health();
$check( false === ( $corrupt['healthy'] ?? true ) && 'chain_invalid' === ( $corrupt['code'] ?? '' ), 'Tampered event in a later page must fail the hash chain.' );
$database->rows[1509]['payload_json'] = '{"status":"safe"}';

$database->fail_after_cursor = 1000;
$read_failure = $repository->audit_health();
$check( false === ( $read_failure['healthy'] ?? true ) && 'read_failed' === ( $read_failure['code'] ?? '' ), 'Failed later SQL page must not be reported healthy.' );
$database->fail_after_cursor = null;
$database->rows = array();
$empty = $repository->audit_health();
$check( true === ( $empty['healthy'] ?? false ) && 0 === ( $empty['events'] ?? -1 ), 'Empty intact audit is healthy with zero events.' );

if ( $failed ) { fwrite( STDERR, "{$failed} of {$checks} V12.3 bounded audit checks failed.\n" ); exit( 1 ); }
echo "All {$checks} V12.3 bounded audit checks passed.\n";
