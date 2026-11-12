<?php
/**
 * V12.1-STO-05/07: encrypted export artifacts must be created exclusively,
 * never by following a pre-existing symlink or overwriting existing bytes.
 * This tests File23 source behavior only, not Hostinger path hardening.
 */
require_once __DIR__ . '/bootstrap.php';

$tests = 0; $failed = 0;
$assert = static function ( bool $pass, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $pass ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};
$assert( method_exists( SPDB_Export_Service::class, 'write_ciphertext_exclusive' ), 'Exclusive ciphertext writer must exist.' );
if ( ! method_exists( SPDB_Export_Service::class, 'write_ciphertext_exclusive' ) ) { exit( 1 ); }
$service = ( new ReflectionClass( SPDB_Export_Service::class ) )->newInstanceWithoutConstructor();
$writer = new ReflectionMethod( SPDB_Export_Service::class, 'write_ciphertext_exclusive' );
$writer->setAccessible( true );
$directory = sys_get_temp_dir() . '/spdb-exclusive-' . bin2hex( random_bytes( 7 ) );
mkdir( $directory, 0700, true );
$filename = 'export_' . str_repeat( 'a', 32 ) . '.spdb';
$outside = sys_get_temp_dir() . '/spdb-external-' . bin2hex( random_bytes( 7 ) );
try {
	$created = $writer->invoke( $service, $directory, $filename, 'sealed payload 1' );
	$assert( is_string( $created ) && is_file( $created ), 'New ciphertext is created in the private directory.' );
	$assert( is_string( $created ) && 'sealed payload 1' === file_get_contents( $created ), 'Created ciphertext bytes match exact input.' );
	$again = $writer->invoke( $service, $directory, $filename, 'malicious overwrite' );
	$assert( is_wp_error( $again ) && 'spdb_export_file_exists' === $again->get_error_code(), 'Retry must not overwrite an existing ciphertext.' );
	$assert( 'sealed payload 1' === file_get_contents( $directory . '/' . $filename ), 'Existing ciphertext remains unchanged after overwrite attempt.' );
	unlink( $directory . '/' . $filename );

	file_put_contents( $outside, 'outside untouched' );
	if ( function_exists( 'symlink' ) && @symlink( $outside, $directory . '/' . $filename ) ) {
		$symlink = $writer->invoke( $service, $directory, $filename, 'must not write outside' );
		$assert( is_wp_error( $symlink ) && 'spdb_export_file_exists' === $symlink->get_error_code(), 'Existing symlink must fail closed.' );
		$assert( 'outside untouched' === file_get_contents( $outside ), 'Symlink destination must remain untouched.' );
		unlink( $directory . '/' . $filename );
	}

	$bad = $writer->invoke( $service, $directory, '../escape.spdb', 'unsafe' );
	$assert( is_wp_error( $bad ) && 'spdb_export_file_name_invalid' === $bad->get_error_code(), 'Traversal/invalid artifact name must fail.' );
	$assert( ! file_exists( dirname( $directory ) . '/escape.spdb' ), 'Traversal must not write outside private directory.' );
} finally {
	foreach ( scandir( $directory ) ?: array() as $entry ) {
		if ( '.' !== $entry && '..' !== $entry ) { @unlink( $directory . '/' . $entry ); }
	}
	@rmdir( $directory );
	if ( file_exists( $outside ) ) { unlink( $outside ); }
}
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} exclusive export tests failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1 exclusive export tests passed.\n";
