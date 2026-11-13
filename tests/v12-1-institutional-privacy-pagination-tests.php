<?php
/**
 * V12.1-STO-09: institutional collection contributor privacy erasure must
 * not silently finish at LIMIT 5000, and invalid JSON must not be rewritten
 * into an empty unrelated contributor list. Synthetic database only.
 */
require_once __DIR__ . '/bootstrap.php';

$tests = 0; $failed = 0;
$assert = static function ( bool $ok, string $label ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $ok ) { ++$failed; fwrite( STDERR, "FAIL: {$label}\n" ); }
};

class SPDB_Institutional_Privacy_DB {
	public string $prefix = 'wp_test_';
	public array $rows = array();
	private array $args = array();
	public bool $fail_read = false;
	public bool $fail_update = false;
	public int $reads = 0;
	public int $updates = 0;
	public function prepare( string $sql, ...$args ): string {
		$this->args = $args;
		return $sql;
	}
	public function esc_like( string $s ): string { return $s; }
	public function get_results( string $sql, $format = null ) {
		++$this->reads;
		if ( $this->fail_read ) { return false; }
		$cursor = (int) ( $this->args[0] ?? 0 );
		$owner = (int) ( $this->args[1] ?? 0 );
		$limit = (int) ( $this->args[3] ?? 200 );
		$out = array();
		foreach ( $this->rows as $row ) {
			if ( $row['id'] > $cursor && ( $row['owner_user_id'] === $owner || strpos( $row['contributors_json'], (string) $owner ) !== false ) ) {
				$out[] = $row;
				if ( count( $out ) >= $limit ) { break; }
			}
		}
		return $out;
	}
	public function update( string $table, array $data, array $where ) {
		if ( $this->fail_update ) { return false; }
		foreach ( $this->rows as &$row ) {
			if ( $row['collection_id'] === $where['collection_id'] && 'institution' === $where['scope'] ) {
				$row = array_merge( $row, $data );
				++$this->updates;
				return 1;
			}
		}
		return 0;
	}
}
$GLOBALS['wpdb'] = new SPDB_Institutional_Privacy_DB();
$repo = ( new ReflectionClass( SPDB_Operations_Repository::class ) )->newInstanceWithoutConstructor();
$assert( method_exists( SPDB_Operations_Repository::class, 'pseudonymize_institutional_collections' ), 'Dedicated bounded collection privacy scanner exists.' );
if ( ! method_exists( SPDB_Operations_Repository::class, 'pseudonymize_institutional_collections' ) ) { exit( 1 ); }
$method = new ReflectionMethod( SPDB_Operations_Repository::class, 'pseudonymize_institutional_collections' );
$method->setAccessible( true );
for ( $i = 1; $i <= 5001; ++$i ) {
	$GLOBALS['wpdb']->rows[] = array(
		'id' => $i,
		'collection_id' => 'col_' . str_pad( (string) $i, 32, '0', STR_PAD_LEFT ),
		'owner_user_id' => 7,
		'contributors_json' => '[7,8]',
	);
}
$GLOBALS['wpdb']->rows[] = array(
	'id' => 5002, 'collection_id' => 'col_foreign' . str_repeat( 'a', 22 ),
	'owner_user_id' => 8, 'contributors_json' => '[17,8]',
);
$result = $method->invoke( $repo, 7, '2026-11-13 01:00:00' );
$assert( 5001 === $result, 'All 5,001 matching institutional records are handled, not silently cut at 5,000.' );
$assert( $GLOBALS['wpdb']->reads >= 26, 'The scanner traverses the complete result set using bounded pages.' );
$assert( 5001 === $GLOBALS['wpdb']->updates, 'Foreign false positive is not rewritten.' );
$assert( 0 === $GLOBALS['wpdb']->rows[0]['owner_user_id'] && '[8]' === $GLOBALS['wpdb']->rows[0]['contributors_json'], 'Owner pseudonymized and non-target contributor retained.' );
$assert( 0 === $GLOBALS['wpdb']->rows[5000]['owner_user_id'] && '[8]' === $GLOBALS['wpdb']->rows[5000]['contributors_json'], 'Final row beyond former limit is pseudonymized.' );
$assert( 8 === $GLOBALS['wpdb']->rows[5001]['owner_user_id'] && '[17,8]' === $GLOBALS['wpdb']->rows[5001]['contributors_json'], 'Unrelated contributor 17 and owner 8 preserved.' );

$GLOBALS['wpdb']->rows = array(
	array( 'id' => 1, 'collection_id' => 'col_corrupt' . str_repeat( 'b', 21 ), 'owner_user_id' => 7, 'contributors_json' => '{garbage' ),
);
$GLOBALS['wpdb']->reads = 0;
$GLOBALS['wpdb']->updates = 0;
$corrupt = $method->invoke( $repo, 7, '2026-11-13 01:00:00' );
$assert( is_wp_error( $corrupt ) && 'spdb_privacy_contributors_invalid' === $corrupt->get_error_code(), 'Malformed JSON aborts instead of silently clearing unrelated contributor records.' );
$assert( 0 === $GLOBALS['wpdb']->updates, 'Corrupt record is never rewritten.' );
$GLOBALS['wpdb']->rows[0]['contributors_json'] = '[7,8]';
$GLOBALS['wpdb']->fail_read = true;
$read = $method->invoke( $repo, 7, '2026-11-13 01:00:00' );
$assert( is_wp_error( $read ) && 'spdb_privacy_collection_read_failed' === $read->get_error_code(), 'SQL page read error aborts without deleting references.' );
$GLOBALS['wpdb']->fail_read = false;
$GLOBALS['wpdb']->fail_update = true;
$write = $method->invoke( $repo, 7, '2026-11-13 01:00:00' );
$assert( is_wp_error( $write ) && 'spdb_privacy_pseudonymize_failed' === $write->get_error_code(), 'DB pseudonymization failure propagates and retains metadata.' );

if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} V12.1 institution-privacy pagination tests failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1 institution-privacy pagination tests passed.\n";
