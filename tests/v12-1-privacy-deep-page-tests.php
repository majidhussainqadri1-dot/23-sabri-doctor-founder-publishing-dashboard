<?php
/**
 * File23 V12.1 privacy portability: avoid replaying the final page when
 * item count exceeds 1,000,000. Core may request page 10,001 and beyond.
 * Reject integer-offset overflow before sending any SQL, not by clamping.
 */
require_once __DIR__ . '/bootstrap.php';
class SPDB_Privacy_Deep_Page_DB {
	public string $prefix = 'wp_test_';
	public array $last_args = array();
	public array $offsets = array();
	public int $reads = 0;
	public function prepare( string $sql, ...$args ): string {
		$this->last_args = $args;
		return $sql;
	}
	public function esc_like( string $s ): string { return $s; }
	public function get_results( string $sql, $format = null ) {
		++$this->reads;
		if ( strpos( $sql, 'spdb_collection_items' ) !== false ) {
			$offset = (int) ( $this->last_args[3] ?? -1 );
			$this->offsets[] = $offset;
			return 1000000 === $offset
				? array( array( 'id' => 1000001, 'item_id' => 'item_1000001', 'collection_id' => 'collection_1', 'parent_owner_user_id' => 7, 'parent_contributors_json' => '[7]' ) )
				: array();
		}
		return array();
	}
}
$GLOBALS['wpdb'] = new SPDB_Privacy_Deep_Page_DB();
$repo = ( new ReflectionClass( SPDB_Operations_Repository::class ) )->newInstanceWithoutConstructor();
$tests=0; $failed=0;
$assert=static function ( bool $ok, string $name ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $ok ) { ++$failed; fwrite( STDERR, "FAIL: {$name}\n" ); }
};
$result = $repo->privacy_export( 7, 10001, 100 );
$items = $result['collections']['items'] ?? array();
$assert( is_array( $result ) && 1 === count( $items ), 'Privacy page 10001 is not clamped to page 10000.' );
$assert( 1000001 === (int) ( $items[0]['id'] ?? 0 ), 'Page 10001 contains the expected item 1,000,001.' );
$assert( in_array( 1000000, $GLOBALS['wpdb']->offsets, true ), 'WordPress core receives the correct page-offset in bounded SQL.' );
$assert( true === ( $result['_done'] ?? false ), 'Valid last deep page correctly completes.' );
$reads_before = $GLOBALS['wpdb']->reads;
$overflow = $repo->privacy_export( 7, PHP_INT_MAX, 200 );
$assert( is_wp_error( $overflow ) && 'spdb_privacy_page_overflow' === $overflow->get_error_code(), 'Overflowed page count must fail before SQL rather than repeat earlier rows.' );
$assert( $reads_before === $GLOBALS['wpdb']->reads, 'Unrepresentable offsets make zero additional database reads.' );
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} V12.1 privacy deep-page tests failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1 privacy deep-page tests passed.\n";
