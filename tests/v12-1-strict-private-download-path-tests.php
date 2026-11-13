<?php
/**
 * V12.1-STO-07: downloadable File23 ciphertext must belong to exactly the
 * requested job, be a regular file (never a symlink) and match its hash.
 * Synthetic local fixtures; does not assert Hostinger HTTP denial.
 */
require_once __DIR__ . '/bootstrap.php';
if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( string $path ): string { return rtrim( $path, '/\\' ) . '/'; }
}
$tests = 0; $failures = 0;
$assert = static function ( bool $ok, string $description ) use ( &$tests, &$failures ): void {
	++$tests;
	if ( ! $ok ) { ++$failures; fwrite( STDERR, "FAIL: {$description}\n" ); }
};
$assert( method_exists( SPDB_Export_Service::class, 'resolve_ciphertext_download_path' ), 'Canonical strict download resolver must exist.' );
if ( ! method_exists( SPDB_Export_Service::class, 'resolve_ciphertext_download_path' ) ) { exit( 1 ); }
$service = ( new ReflectionClass( SPDB_Export_Service::class ) )->newInstanceWithoutConstructor();
$resolver = new ReflectionMethod( SPDB_Export_Service::class, 'resolve_ciphertext_download_path' );
$resolver->setAccessible( true );
$root = sys_get_temp_dir() . '/spdb-download-path-' . bin2hex( random_bytes( 7 ) );
mkdir( $root, 0700, true );
$export_id = 'export_' . str_repeat( 'a', 32 );
$ref = $export_id . '.spdb';
$other_ref = 'export_' . str_repeat( 'b', 32 ) . '.spdb';
$content = 'synthetic encrypted report fixture';
$hash = hash( 'sha256', $content );
try {
	file_put_contents( $root . '/' . $ref, $content );
	$good = $resolver->invoke( $service, $root, $export_id, $ref, $hash );
	$assert( is_string( $good ) && $good === realpath( $root . '/' . $ref ), 'Correct job, byte hash, name, regular path accepted.' );
	$assert( is_wp_error( $resolver->invoke( $service, $root, $export_id, $other_ref, $hash ) ), 'Wrong job reference rejected before reading.' );
	$assert( is_wp_error( $resolver->invoke( $service, $root, $export_id, '../' . $ref, $hash ) ), 'Raw path traversal rejected, not basename-normalized.' );
	$assert( is_wp_error( $resolver->invoke( $service, $root, $export_id, $ref, str_repeat( '0', 64 ) ) ), 'Modified hash denied.' );
	$assert( is_wp_error( $resolver->invoke( $service, $root, $export_id, $ref, '' ) ), 'Blank expected checksum denied.' );
	file_put_contents( $root . '/' . $other_ref, $content );
	unlink( $root . '/' . $ref );
	if ( function_exists( 'symlink' ) && @symlink( $root . '/' . $other_ref, $root . '/' . $ref ) ) {
		$assert( is_wp_error( $resolver->invoke( $service, $root, $export_id, $ref, $hash ) ), 'Same-directory symlink refused, even with a matching hash.' );
		$assert( $content === file_get_contents( $root . '/' . $other_ref ), 'Symlink target remains untouched.' );
		unlink( $root . '/' . $ref );
	}
	$assert( is_wp_error( $resolver->invoke( $service, $root, $export_id, $ref, $hash ) ), 'Missing ciphertext cannot be downloaded.' );
	$assert( is_wp_error( $resolver->invoke( $service, $root, $export_id, $ref . '.php', $hash ) ), 'Unexpected extension denied.' );
	$assert( is_wp_error( $resolver->invoke( $service, $root, $export_id, $ref, $hash . 'a' ) ), 'Malformed checksum denied.' );
} finally {
	foreach ( scandir( $root ) ?: array() as $entry ) {
		if ( '.' !== $entry && '..' !== $entry ) { @unlink( $root . '/' . $entry ); }
	}
	@rmdir( $root );
}
if ( $failures ) { fwrite( STDERR, "{$failures} of {$tests} V12.1 download path tests failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1 strict download-path tests passed.\n";
