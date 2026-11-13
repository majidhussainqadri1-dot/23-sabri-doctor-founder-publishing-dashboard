<?php
/**
 * File23 V12.1 private storage directory: never create guard files or
 * chmod a linked directory that redirects outside the WP upload root.
 * Synthetic temporary filesystem; no Hostinger site connection.
 */
require_once __DIR__ . '/bootstrap.php';

$GLOBALS['spdb_guard_dir_root'] = sys_get_temp_dir() . '/spdb-private-dir-v121-' . bin2hex( random_bytes( 8 ) );
function wp_upload_dir( $time = null, $create_dir = true ): array {
	return array( 'basedir' => $GLOBALS['spdb_guard_dir_root'] . '/uploads', 'error' => false );
}
function wp_mkdir_p( string $dir ): bool { return is_dir( $dir ) || mkdir( $dir, 0700, true ); }
function trailingslashit( string $dir ): string { return rtrim( $dir, '/\\' ) . '/'; }

$tests = 0; $failed = 0;
$assert = static function ( bool $ok, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $ok ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};
$root = $GLOBALS['spdb_guard_dir_root'];
$uploads = $root . '/uploads';
$outside = $root . '/outside-secret-directory';
$private = $uploads . '/spdb-private-exports';
$service = ( new ReflectionClass( SPDB_Export_Service::class ) )->newInstanceWithoutConstructor();
$method = new ReflectionMethod( SPDB_Export_Service::class, 'private_directory' );
$method->setAccessible( true );
mkdir( $uploads, 0700, true );
mkdir( $outside, 0700, true );
file_put_contents( $outside . '/sentinel.txt', 'outside must not be touched' );
try {
	if ( function_exists( 'symlink' ) && @symlink( $outside, $private ) ) {
		$res = $method->invoke( $service );
		$assert( is_wp_error( $res ) && 'spdb_export_storage_path_invalid' === $res->get_error_code(), 'Private directory linked outside uploads must be denied.' );
		$assert( 'outside must not be touched' === file_get_contents( $outside . '/sentinel.txt' ), 'Symlinked directory target content remains untouched.' );
		$assert( ! file_exists( $outside . '/.htaccess' ) && ! file_exists( $outside . '/index.php' ) && ! file_exists( $outside . '/web.config' ), 'No guard content is ever written outside uploads via linked directory.' );
		unlink( $private );
	}
	$valid = $method->invoke( $service );
	$assert( is_string( $valid ) && $private === $valid && is_dir( $valid ) && ! is_link( $valid ), 'Canonical private directory still initializes as a normal directory.' );
	$assert( is_file( $private . '/.htaccess' ) && is_file( $private . '/index.php' ) && is_file( $private . '/web.config' ), 'Guards remain mandatory for valid canonical private storage.' );
} finally {
	if ( is_link( $private ) ) { unlink( $private ); }
	if ( is_dir( $private ) ) {
		foreach ( scandir( $private ) ?: array() as $file ) {
			if ( '.' !== $file && '..' !== $file ) { @unlink( $private . '/' . $file ); }
		}
		@rmdir( $private );
	}
	@unlink( $outside . '/sentinel.txt' );
	@rmdir( $outside );
	@rmdir( $uploads );
	@rmdir( $root );
}
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} V12.1 private-directory binding tests failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1 private-directory binding tests passed.\n";
