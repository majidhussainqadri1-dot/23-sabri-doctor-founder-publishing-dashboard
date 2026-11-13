<?php
/**
 * V12.1 privacy data portability: one collection with >5,000 items must
 * be exported across independent, bounded item pages. False-positive LIKE
 * hits (user 7 vs contributor 17) must never leak cross-user item metadata.
 * Source-only mock SQL, no WordPress/Hostinger DB.
 */
require_once __DIR__ . '/bootstrap.php';

$tests = 0; $failed = 0;
$assert = static function ( bool $ok, string $msg ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $ok ) { ++$failed; fwrite( STDERR, "FAIL: {$msg}\n" ); }
};
class SPDB_Privacy_Export_Pages_DB {
	public string $prefix = 'wp_test_';
	public array $arguments = array();
	public int $item_queries = 0;
	public bool $read_failure = false;
	public function prepare( string $sql, ...$args ): string {
		$this->arguments = $args;
		return $sql;
	}
	public function esc_like( string $value ): string { return $value; }
	public function get_results( string $sql, $format = null ) {
		if ( $this->read_failure ) { return false; }
		$limit = (int) ( $this->arguments[count( $this->arguments ) - 2] ?? 100 );
		$offset = (int) ( $this->arguments[count( $this->arguments ) - 1] ?? 0 );
		if ( strpos( $sql, 'INNER JOIN' ) !== false && strpos( $sql, 'spdb_collection_items' ) !== false ) {
			++$this->item_queries;
			$rows = array();
			for ( $id = $offset + 1; $id <= min( $offset + $limit, 6004 ); ++$id ) {
				$owner = $id <= 6001 ? 7 : 8;
				$rows[] = array(
					'id' => $id, 'item_id' => 'item_' . $id, 'collection_id' => 'owned_collection',
					'parent_owner_user_id' => $owner,
					'parent_contributors_json' => $owner === 7 ? '[7,8]' : ( 6004 === $id ? '["7spoof",8]' : '[17,8]' ),
				);
			}
			return $rows;
		}
		if ( strpos( $sql, 'FROM wp_test_spdb_collections WHERE' ) !== false ) {
			return $offset === 0
				? array(
					array( 'id' => 1, 'collection_id' => 'owned_collection', 'owner_user_id' => 7, 'contributors_json' => '[7,8]' ),
					array( 'id' => 2, 'collection_id' => 'not_owned_collection', 'owner_user_id' => 8, 'contributors_json' => '["7spoof",8]' ),
				)
				: array();
		}
		if ( strpos( $sql, 'FROM wp_test_spdb_knowledge_links WHERE' ) !== false ) { return array(); }
		return array();
	}
}
$GLOBALS['wpdb'] = new SPDB_Privacy_Export_Pages_DB();
$repo = ( new ReflectionClass( SPDB_Operations_Repository::class ) )->newInstanceWithoutConstructor();
$method = new ReflectionMethod( SPDB_Operations_Repository::class, 'privacy_collection_export' );
$method->setAccessible( true );
$seen = array();
$last = array();
for ( $page = 1; $page <= 62; ++$page ) {
	$last = $method->invoke( $repo, 7, $page, 100 );
	if ( 1 === $page ) {
		$assert( 1 === count( $last['data']['collections'] ?? array() ) && 'owned_collection' === ( $last['data']['collections'][0]['collection_id'] ?? '' ), 'String 7spoof cannot authorize foreign parent collection export.' );
	}
	$assert( is_array( $last ) && isset( $last['data']['items'] ) && is_array( $last['data']['items'] ), 'Collection item page returns an array.' );
	foreach ( $last['data']['items'] as $item ) {
		$assert( ! isset( $item['parent_owner_user_id'] ) && ! isset( $item['parent_contributors_json'] ), 'Internal parent authority fields are not serialized into exported data.' );
		$seen[] = $item['id'];
	}
	if ( $page <= 60 ) {
		$assert( true === $last['has_more'], 'A full item page must keep privacy export pagination open.' );
	}
}
$assert( 6001 === count( $seen ), 'All 6001 owned item pointers exported, not cut at 5000.' );
$assert( 6001 === count( array_unique( $seen ) ) && max( $seen ) === 6001, 'Item pages have no duplicate or omitted IDs.' );
$assert( 62 === $GLOBALS['wpdb']->item_queries, 'Every 100-row item page bounded at the same WordPress privacy callback page.' );
$assert( false === $last['has_more'], 'Export has finite completion after last item/false-positive candidate page.' );
$assert( ! in_array( 6002, $seen, true ) && ! in_array( 6003, $seen, true ), 'Foreign owner 8 with contributor 17 is not leaked to user 7.' );
$assert( ! in_array( 6004, $seen, true ), 'Malformed string contributor 7spoof must never be coerced into integer authority 7.' );

if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} V12.1 privacy data portability tests failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1 privacy data portability tests passed.\n";
