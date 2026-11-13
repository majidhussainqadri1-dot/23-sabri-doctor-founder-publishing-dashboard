<?php
/**
 * V12.1-STO-09: SQL errors in a WordPress personal-data export must stop the
 * export (WP_Error), never convert a failed SELECT into an empty/complete
 * response. WordPress core wp_ajax_wp_privacy_export_personal_data() natively
 * handles WP_Error from exporter callbacks and reports an Ajax failure.
 */
require_once __DIR__ . '/bootstrap.php';
if ( ! class_exists( 'WP_User' ) ) { class WP_User { public int $ID = 7; } }
if ( ! function_exists( 'get_user_by' ) ) {
	function get_user_by( string $field, string $value ) {
		return 'email' === $field && 'privacy@test.invalid' === $value ? new WP_User() : false;
	}
}
class SPDB_Privacy_Export_Failure_DB {
	public string $prefix = 'wp_test_';
	public string $fail_on = '';
	public bool $fail_preferences = false;
	public string $last_error = '';
	public function prepare( string $sql, ...$args ): string { return $sql; }
	public function get_results( string $sql, $format = null ) {
		return '' !== $this->fail_on && false !== strpos( $sql, $this->fail_on ) ? false : array();
	}
	public function get_row( string $sql, $format = null ) {
		$this->last_error = $this->fail_preferences && false !== strpos( $sql, 'spdb_preferences' ) ? 'synthetic database error' : '';
		return null;
	}
	public function esc_like( string $s ): string { return $s; }
}
$GLOBALS['wpdb'] = new SPDB_Privacy_Export_Failure_DB();
$repo = ( new ReflectionClass( SPDB_Operations_Repository::class ) )->newInstanceWithoutConstructor();
$exports = ( new ReflectionClass( SPDB_Export_Service::class ) )->newInstanceWithoutConstructor();
$integration = new SPDB_Privacy_Integration( $repo, $exports );
$tests = 0; $failed = 0;
$assert = static function ( bool $ok, string $label ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $ok ) { ++$failed; fwrite( STDERR, "FAIL: {$label}\n" ); }
};

$scenarios = array(
	array( 'table' => 'spdb_tasks', 'code' => 'spdb_tasks_read_failed' ),
	array( 'table' => 'spdb_saved_views', 'code' => 'spdb_privacy_export_read_failed' ),
	array( 'table' => 'spdb_delegations', 'code' => 'spdb_privacy_export_read_failed' ),
	array( 'table' => 'spdb_automation_rules', 'code' => 'spdb_privacy_export_read_failed' ),
	array( 'table' => 'spdb_export_jobs', 'code' => 'spdb_privacy_export_read_failed' ),
	array( 'table' => 'spdb_collections', 'code' => 'spdb_privacy_collection_read_failed' ),
	array( 'table' => 'spdb_collection_items', 'code' => 'spdb_privacy_collection_read_failed' ),
	array( 'table' => 'spdb_knowledge_links', 'code' => 'spdb_privacy_collection_read_failed' ),
);
foreach ( $scenarios as $case ) {
	$GLOBALS['wpdb']->fail_on = $case['table'];
	$result = $repo->privacy_export( 7, 1, 100 );
	$assert( is_wp_error( $result ) && $case['code'] === $result->get_error_code(), 'Repository rejects failed read: ' . $case['table'] );
	$callback = $integration->export_personal_data( 'privacy@test.invalid', 1 );
	$assert( is_wp_error( $callback ) && $case['code'] === $callback->get_error_code(), 'WordPress privacy callback propagates WP_Error: ' . $case['table'] );
}
$GLOBALS['wpdb']->fail_on = '';
$GLOBALS['wpdb']->fail_preferences = true;
$preferences_failed = $repo->privacy_export( 7, 1, 100 );
$assert( is_wp_error( $preferences_failed ) && 'spdb_privacy_preferences_read_failed' === $preferences_failed->get_error_code(), 'Preferences SQL error is not treated as an absent preferences row.' );
$callback_failed = $integration->export_personal_data( 'privacy@test.invalid', 1 );
$assert( is_wp_error( $callback_failed ) && 'spdb_privacy_preferences_read_failed' === $callback_failed->get_error_code(), 'WordPress exporter propagates preferences SQL errors.' );
$GLOBALS['wpdb']->fail_preferences = false;
$empty = $repo->privacy_export( 7, 1, 100 );
$assert( is_array( $empty ) && true === ( $empty['_done'] ?? false ), 'Valid empty database returns a complete, not erroneous, page.' );
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} V12.1 privacy-export read failures tests failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1 privacy-export read failures tests passed.\n";
