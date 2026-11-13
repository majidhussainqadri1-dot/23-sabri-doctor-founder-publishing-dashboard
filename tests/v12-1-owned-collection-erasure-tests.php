<?php
/**
 * V12.1 WP-12/Privacy: deleting File23-owned collection items must be one
 * owner-and-scope-bound SQL join, not an unbounded get_col() + IN(...) list.
 * Synthetic SQL gateway only; does not claim live MySQL execution.
 */
require_once __DIR__ . '/bootstrap.php';

$tests = 0; $failed = 0;
$assert = static function ( bool $ok, string $description ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $ok ) { ++$failed; fwrite( STDERR, "FAIL: {$description}\n" ); }
};

class SPDB_Own_Collection_Deletion_DB {
	public string $prefix = 'wp_test_';
	public string $query_text = '';
	public array $args = array();
	public bool $fail = false;
	public int $executions = 0;
	public function prepare( string $sql, ...$args ): string {
		$this->args = $args;
		return $sql;
	}
	public function query( string $sql ) {
		$this->query_text = $sql;
		++$this->executions;
		return $this->fail ? false : 5001;
	}
}
$GLOBALS['wpdb'] = new SPDB_Own_Collection_Deletion_DB();
$repo = ( new ReflectionClass( SPDB_Operations_Repository::class ) )->newInstanceWithoutConstructor();
$assert( method_exists( SPDB_Operations_Repository::class, 'erase_owned_collection_items' ), 'Set-based owner-scoped erasure helper exists.' );
if ( ! method_exists( SPDB_Operations_Repository::class, 'erase_owned_collection_items' ) ) { exit( 1 ); }
$method = new ReflectionMethod( SPDB_Operations_Repository::class, 'erase_owned_collection_items' );
$method->setAccessible( true );
$rows = $method->invoke( $repo, 7 );
$assert( 5001 === $rows, 'More than 5,000 owned items can be removed in one scoped set operation.' );
$assert( 1 === $GLOBALS['wpdb']->executions, 'No unbounded PHP object-list enumeration or N+1 deletes.' );
$assert( array( 7 ) === $GLOBALS['wpdb']->args, 'DB operation is bound to precisely the requested owner ID.' );
$assert( false !== strpos( $GLOBALS['wpdb']->query_text, 'wp_test_spdb_collection_items' ) && false !== strpos( $GLOBALS['wpdb']->query_text, 'wp_test_spdb_collections' ), 'Only the File23-owned collections/items tables participate.' );
$assert( false !== strpos( $GLOBALS['wpdb']->query_text, "own_col.scope = 'own'" ) && false !== strpos( $GLOBALS['wpdb']->query_text, 'own_col.owner_user_id = %d' ), 'Both own-scope and owner predicates are mandatory.' );
$assert( false !== strpos( $GLOBALS['wpdb']->query_text, 'item_rows.collection_id = own_col.collection_id' ), 'Items are joined by their exact parent collection ID.' );
$assert( false === strpos( $GLOBALS['wpdb']->query_text, 'LIMIT ' ) && false === strpos( $GLOBALS['wpdb']->query_text, ' IN (' ), 'Deletion is not silently capped or based on unbounded IN placeholders.' );
$bad = $method->invoke( $repo, 0 );
$assert( is_wp_error( $bad ) && 1 === $GLOBALS['wpdb']->executions, 'Invalid owner rejected before SQL execution.' );
$GLOBALS['wpdb']->fail = true;
$bad = $method->invoke( $repo, 7 );
$assert( is_wp_error( $bad ) && 'spdb_privacy_collection_erase_failed' === $bad->get_error_code(), 'SQL deletion failure rolls back through parent transaction.' );
$source = (string) file_get_contents( dirname( __DIR__ ) . '/includes/class-spdb-operations-repository.php' );
$assert( false === strpos( $source, '$own_ids = $wpdb->get_col(' ), 'Privacy erasure no longer materializes an unbounded personal collection ID list.' );
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} File23 own-collection set-delete tests failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1 owned-collection set-delete tests passed.\n";
