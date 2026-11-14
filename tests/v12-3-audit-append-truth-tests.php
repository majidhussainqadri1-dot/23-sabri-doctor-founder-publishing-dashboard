<?php
/**
 * V12.3 WP-10: an audit append must never silently restart a hash chain if
 * the prior event could not be read or has a malformed stored event hash.
 * Synthetic isolated source test; no real Hostinger DB touched.
 */
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

final class SPDB_V123_Audit_Append_DB {
	public string $prefix = 'wp_';
	public string $last_error = '';
	public ?string $previous_hash = null;
	public bool $fail_previous_read = false;
	public array $inserted = array();
	public int $locks_acquired = 0;
	public int $locks_released = 0;
	public function prepare( string $sql, ...$params ): string { return $sql; }
	public function get_var( string $sql ) {
		if ( false !== strpos( $sql, 'GET_LOCK(' ) ) {
			++$this->locks_acquired;
			$this->last_error = '';
			return '1';
		}
		if ( false !== strpos( $sql, 'RELEASE_LOCK(' ) ) {
			++$this->locks_released;
			$this->last_error = '';
			return '1';
		}
		if ( false !== strpos( $sql, 'SELECT event_hash FROM' ) ) {
			if ( $this->fail_previous_read ) {
				$this->last_error = 'A private simulated MariaDB error';
				return null;
			}
			$this->last_error = '';
			return $this->previous_hash;
		}
		throw new RuntimeException( 'Unexpected SQL in isolated audit test' );
	}
	public function insert( string $table, array $data ) {
		$this->inserted[] = $data;
		return 1;
	}
}
$wpdb = new SPDB_V123_Audit_Append_DB();
$GLOBALS['wpdb'] = $wpdb;
$repo = new SPDB_Operations_Repository();
$checks = 0; $failed = 0;
$check = static function ( bool $ok, string $label ) use ( &$checks, &$failed ): void {
	++$checks;
	if ( ! $ok ) { ++$failed; fwrite( STDERR, 'FAIL: ' . $label . "\n" ); }
};
$reset = static function () use ( $wpdb ): void {
	$wpdb->inserted = array();
	$wpdb->last_error = '';
	$wpdb->previous_hash = null;
	$wpdb->fail_previous_read = false;
};

$reset();
$result = $repo->append_audit( 7, 'source_checked', 'source:1', array( 'count' => 1 ) );
$check( true === $result && 1 === count( $wpdb->inserted ), 'Truly empty audit table may append exactly one genesis event.' );
$check( str_repeat( '0', 64 ) === ( $wpdb->inserted[0]['previous_hash'] ?? null ), 'Genesis must use the all-zero previous hash.' );

$reset();
$wpdb->fail_previous_read = true;
$result = $repo->append_audit( 7, 'source_checked', 'source:2', array( 'count' => 1 ) );
$check( is_wp_error( $result ) && 'spdb_audit_previous_read_failed' === $result->get_error_code(), 'Failed prior event SQL must return a typed error, not fabricate an empty chain.' );
$check( 0 === count( $wpdb->inserted ), 'Failed prior audit SELECT must not write a new event.' );

$reset();
$wpdb->previous_hash = str_repeat( 'X', 64 );
$result = $repo->append_audit( 7, 'source_checked', 'source:3', array() );
$check( is_wp_error( $result ) && 'spdb_audit_previous_hash_invalid' === $result->get_error_code(), 'Malformed 64-character stored hash must fail closed.' );
$check( 0 === count( $wpdb->inserted ), 'Malformed prior hash must not reset the chain and insert.' );

$reset();
$wpdb->previous_hash = '';
$result = $repo->append_audit( 7, 'source_checked', 'source:4', array() );
$check( is_wp_error( $result ) && 'spdb_audit_previous_hash_invalid' === $result->get_error_code(), 'A non-null blank stored prior hash is corrupt, not genesis.' );

$reset();
$wpdb->previous_hash = str_repeat( 'a', 64 );
$result = $repo->append_audit( 7, 'source_checked', 'source:5', array( 'safe' => true ) );
$check( true === $result && 1 === count( $wpdb->inserted ), 'Valid existing chain may append.' );
$check( str_repeat( 'a', 64 ) === ( $wpdb->inserted[0]['previous_hash'] ?? null ), 'Valid previous hash must be preserved verbatim.' );

$check( $wpdb->locks_acquired === $wpdb->locks_released, 'Every audit GET_LOCK must be released, including denied writes.' );
if ( $failed ) { fwrite( STDERR, "{$failed} / {$checks} V12.3 audit-append integrity checks failed.\n" ); exit( 1 ); }
echo "All {$checks} V12.3 audit-append integrity checks passed.\n";
