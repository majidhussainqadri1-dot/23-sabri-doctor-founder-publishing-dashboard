<?php
/**
 * V12.1 WP-00: source-only executable probe contract, with synthetic WP and
 * synthetic plugin bytes. NEVER contacts Hostinger or reads actual site data.
 */
define( 'WP_CLI', true );
define( 'ABSPATH', sys_get_temp_dir() . '/spdb-probe-wp-' . bin2hex( random_bytes( 7 ) ) . '/' );
define( 'WP_PLUGIN_DIR', ABSPATH . 'wp-content/plugins' );
define( 'SPDB_VERSION', '1.3.0' );
$plugin_root = WP_PLUGIN_DIR . '/sabri-publishing-dashboard';
mkdir( $plugin_root . '/includes', 0700, true );
$GLOBALS['spdb_probe_options'] = array(
	'spdb_operations_schema_version' => '1.1.0',
	'spdb_collections_schema_version' => '4',
);
$GLOBALS['spdb_probe_active'] = true;
function get_bloginfo( string $field ): string { return '6.9.1'; }
function get_option( string $key, $default = false ) { return $GLOBALS['spdb_probe_options'][ $key ] ?? $default; }
function get_plugin_data( string $file, bool $markup = false, bool $translate = false ): array {
	$raw = file_get_contents( $file );
	preg_match( '/Version:\s*([^\n]+)/', $raw, $matches );
	return array( 'Version' => trim( $matches[1] ?? '' ) );
}
function is_plugin_active( string $slug ): bool {
	return 'sabri-publishing-dashboard/sabri-publishing-dashboard.php' === $slug && $GLOBALS['spdb_probe_active'];
}
function wp_get_environment_type(): string { return 'staging'; }
function wp_json_encode( $value, int $flags = 0 ): string { return json_encode( $value, $flags ); }
final class SPDB_Operations_Schema {
	public const VERSION = '1.1.0';
	public static function verify(): bool { return true; }
}
final class SPDB_Collections_Schema {
	public const VERSION = '4';
	public static function verify(): bool { return true; }
}
$source = __DIR__ . '/../tools/file23-readonly-host-evidence.php';
file_put_contents( $plugin_root . '/sabri-publishing-dashboard.php', "<?php\n/*\nPlugin Name: File 23 Test\nVersion: 1.3.0\n*/\n" );
file_put_contents( $plugin_root . '/includes/test.php', "<?php /* fake plugin file */\n" );
$tests = 0; $failed = 0;
$assert = static function ( bool $ok, string $msg ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $ok ) { ++$failed; fwrite( STDERR, "FAIL: {$msg}\n" ); }
};
$probe = static function () use ( $source ): array {
	ob_start();
	include $source;
	$raw = ob_get_clean();
	$decoded = json_decode( $raw, true );
	return is_array( $decoded ) ? $decoded : array();
};
try {
	$baseline = $probe();
	$assert( 'file23_readonly_host_observation_v1' === ( $baseline['contract'] ?? '' ), 'Versioned observation contract emitted.' );
	$assert( false === ( $baseline['verified'] ?? true ) && false === ( $baseline['is_attestation'] ?? true ), 'Observation never claims trusted deployment verification.' );
	$assert( 'staging' === ( $baseline['environment'] ?? '' ), 'Actual environment rather than guessed production.' );
	$assert( true === ( $baseline['active'] ?? false ) && '1.3.0' === ( $baseline['runtime_file23_version'] ?? '' ), 'Runtime active/version measured.' );
	$assert( 2 === ( $baseline['file_count'] ?? -1 ), 'Only actual plugin byte files counted.' );
	$assert( 0 === ( $baseline['nonregular_file_count'] ?? -1 ) && empty( $baseline['errors'] ), 'Regular valid files produce no probe errors.' );
	$assert( 'file23_schema_verified__external_migration_evidence_still_required' === ( $baseline['migration_state'] ?? '' ), 'Schema check not confused with full migration attestation.' );
	$assert( is_string( $baseline['installed_file_inventory_sha256'] ?? null ) && 64 === strlen( $baseline['installed_file_inventory_sha256'] ), 'Deterministic measured inventory SHA-256.' );

	file_put_contents( $plugin_root . '/sabri-publishing-dashboard.php', "<?php\n/*\nPlugin Name: File 23 Test\nVersion: 1.2.0\n*/\n" );
	$mismatch = $probe();
	$assert( in_array( 'header_runtime_version_mismatch', $mismatch['errors'] ?? array(), true ), 'Same runtime but changed on-disk header reports mismatch.' );
	$assert( $mismatch['installed_file_inventory_sha256'] !== $baseline['installed_file_inventory_sha256'], 'Byte change measurably changes inventory checksum.' );

	$GLOBALS['spdb_probe_options']['spdb_collections_schema_version'] = '3';
	$schema_drift = $probe();
	$assert( 'incomplete_or_unverified' === $schema_drift['migration_state'], 'Stale schema option cannot be accepted as migrated.' );

	$outside = ABSPATH . 'outside-private.txt';
	file_put_contents( $outside, 'outside unchanged' );
	if ( function_exists( 'symlink' ) && @symlink( $outside, $plugin_root . '/includes/hostile-link.php' ) ) {
		$link = $probe();
		$assert( $link['nonregular_file_count'] > 0 && in_array( 'nonregular_or_unreadable_installed_file', $link['errors'] ?? array(), true ), 'Symlink in plugin tree invalidates clean inventory.' );
		$assert( 'outside unchanged' === file_get_contents( $outside ), 'Probe never follows/writes symlink destination.' );
		unlink( $plugin_root . '/includes/hostile-link.php' );
	}
	// Symlinked *directories* must be detected too, not only symlinked
	// files; the private operator must never scan outside installed bytes.
	$outside_dir = ABSPATH . 'outside-tree';
	mkdir( $outside_dir, 0700, true );
	file_put_contents( $outside_dir . '/must-not-follow.php', '<?php // outside fixture' );
	if ( function_exists( 'symlink' ) && @symlink( $outside_dir, $plugin_root . '/includes/hostile-directory' ) ) {
		$dir_link = $probe();
		$assert( $dir_link['nonregular_file_count'] > 0 && in_array( 'nonregular_or_unreadable_installed_file', $dir_link['errors'] ?? array(), true ), 'Linked directories invalidate full installed-file inventory.' );
		$assert( 2 === $dir_link['file_count'], 'Operator inventory never follows external directory contents.' );
		unlink( $plugin_root . '/includes/hostile-directory' );
	}
	@unlink( $outside_dir . '/must-not-follow.php' );
	@rmdir( $outside_dir );
	$GLOBALS['spdb_probe_active'] = false;
	$inactive = $probe();
	$assert( in_array( 'file23_plugin_not_active', $inactive['errors'] ?? array(), true ), 'Inactive plugin explicitly reported.' );
} finally {
	if ( file_exists( ABSPATH . 'outside-private.txt' ) ) { unlink( ABSPATH . 'outside-private.txt' ); }
	@unlink( $plugin_root . '/includes/test.php' );
	@unlink( $plugin_root . '/sabri-publishing-dashboard.php' );
	@rmdir( $plugin_root . '/includes' );
	@rmdir( $plugin_root );
	@rmdir( WP_PLUGIN_DIR );
	@rmdir( dirname( WP_PLUGIN_DIR ) );
	@rmdir( ABSPATH );
}
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} readonly host evidence tests failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1 readonly host evidence tests passed.\n";
