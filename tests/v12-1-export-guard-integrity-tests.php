<?php
/**
 * V12.1 private export guard integrity regression.
 *
 * Exercises the actual export directory guard with an isolated temporary
 * WordPress uploads root. This is repository QA, not Hostinger acceptance.
 */
require_once __DIR__ . '/bootstrap.php';

$GLOBALS['spdb_guard_test_upload_root'] = sys_get_temp_dir() . '/spdb-v121-guard-' . bin2hex( random_bytes( 8 ) );

function wp_upload_dir( $time = null, $create_dir = true ): array {
	return array( 'basedir' => $GLOBALS['spdb_guard_test_upload_root'], 'error' => false );
}

function wp_mkdir_p( string $path ): bool {
	return is_dir( $path ) || mkdir( $path, 0700, true );
}

function trailingslashit( string $path ): string {
	return rtrim( $path, '/\\' ) . '/';
}

require_once dirname( __DIR__ ) . '/includes/class-spdb-export-service.php';

$service = ( new ReflectionClass( SPDB_Export_Service::class ) )->newInstanceWithoutConstructor();
$method  = new ReflectionMethod( SPDB_Export_Service::class, 'private_directory' );
$method->setAccessible( true ); // PHP 8.0 private-method reflection compatibility.
$tests   = 0;
$failed  = 0;
$assert  = static function ( bool $passed, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $passed ) {
		++$failed;
		fwrite( STDERR, 'FAIL: ' . $message . "\n" );
	}
};

try {
	$directory = $method->invoke( $service );
	$assert( is_string( $directory ) && is_dir( $directory ), 'Fresh private export directory must be created.' );
	$guard_names = array( '.htaccess', 'index.php', 'web.config' );
	foreach ( $guard_names as $name ) {
		$assert( is_file( $directory . '/' . $name ), 'Missing private guard: ' . $name );
	}
	$assert( $directory === $method->invoke( $service ), 'Untampered directory must be accepted on subsequent reads.' );

	$htaccess = $directory . '/.htaccess';
	$original = file_get_contents( $htaccess );
	file_put_contents( $htaccess, "Allow from all\n" );
	$result = $method->invoke( $service );
	$assert( is_wp_error( $result ) && 'spdb_export_storage_guard_invalid' === $result->get_error_code(), 'Tampered Apache/LiteSpeed guard must fail closed.' );
	$assert( "Allow from all\n" === file_get_contents( $htaccess ), 'Corrupted guard must not be silently accepted or rewritten.' );
	file_put_contents( $htaccess, $original );

	$web_guard = $directory . '/web.config';
	$web_original = file_get_contents( $web_guard );
	file_put_contents( $web_guard, '<configuration></configuration>' );
	$result = $method->invoke( $service );
	$assert( is_wp_error( $result ) && 'spdb_export_storage_guard_invalid' === $result->get_error_code(), 'Tampered IIS guard must fail closed.' );
	file_put_contents( $web_guard, $web_original );

	$php_guard = $directory . '/index.php';
	unlink( $php_guard );
	$result = $method->invoke( $service );
	$assert( is_string( $result ) && is_file( $php_guard ), 'Missing guard must be recreated before use.' );

	if ( function_exists( 'symlink' ) ) {
		unlink( $php_guard );
		if ( @symlink( $htaccess, $php_guard ) ) {
			$result = $method->invoke( $service );
			$assert( is_wp_error( $result ) && 'spdb_export_storage_guard_invalid' === $result->get_error_code(), 'Symlinked guard must fail closed.' );
			unlink( $php_guard );
		}
	}
} finally {
	$directory = $GLOBALS['spdb_guard_test_upload_root'] . '/spdb-private-exports';
	if ( is_dir( $directory ) ) {
		foreach ( scandir( $directory ) ?: array() as $entry ) {
			if ( '.' !== $entry && '..' !== $entry ) {
				unlink( $directory . '/' . $entry );
			}
		}
		rmdir( $directory );
	}
	if ( is_dir( $GLOBALS['spdb_guard_test_upload_root'] ) ) {
		rmdir( $GLOBALS['spdb_guard_test_upload_root'] );
	}
}

if ( $failed ) {
	fwrite( STDERR, "{$failed} of {$tests} File 23 V12.1 export-guard tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} File 23 V12.1 export-guard tests passed.\n";
