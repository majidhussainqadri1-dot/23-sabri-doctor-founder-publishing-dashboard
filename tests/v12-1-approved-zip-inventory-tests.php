<?php
/**
 * V12.1 WP-00: candidate ZIP observation must match the installed-tree
 * byte inventory algorithm; malicious ZIPs must be rejected. No Hostinger
 * state or build approval is implied by passing this source regression.
 */
require_once __DIR__ . '/bootstrap.php';

if ( ! class_exists( 'ZipArchive' ) || ! function_exists( 'proc_open' ) ) {
	fwrite( STDERR, "FAIL: ZipArchive and proc_open required for V12.1 candidate ZIP observation regression.\n" );
	exit( 1 );
}
$tests=0; $failed=0;
$assert=static function ( bool $ok, string $msg ) use ( &$tests, &$failed ): void {
	++$tests; if ( ! $ok ) { ++$failed; fwrite( STDERR, "FAIL: {$msg}\n" ); }
};
$root = sys_get_temp_dir() . '/file23-zip-observation-' . bin2hex( random_bytes( 6 ) );
mkdir( $root, 0700, true );
$main = "<?php\n/*\nPlugin Name: File23\nVersion: 1.3.0\n*/\n";
$nested = "<?php // approved\n";
$tool = dirname( __DIR__ ) . '/tools/file23-approved-zip-inventory.php';

$build = static function ( string $path, array $entries, array $links = array() ): void {
	$zip = new ZipArchive();
	if ( true !== $zip->open( $path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
		throw new RuntimeException( 'Cannot create synthetic ZIP' );
	}
	foreach ( $entries as $name => $bytes ) {
		$zip->addFromString( $name, $bytes );
		if ( in_array( $name, $links, true ) && method_exists( $zip, 'setExternalAttributesName' ) ) {
			$zip->setExternalAttributesName( $name, ZipArchive::OPSYS_UNIX, ( 0120777 << 16 ) );
		}
	}
	$zip->close();
};
$run = static function ( string $archive ) use ( $tool ): array {
	$command = array( PHP_BINARY, $tool, $archive );
	$pipes = array();
	$process = proc_open( $command, array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes );
	if ( ! is_resource( $process ) ) { return array( 'code' => 999, 'output' => array() ); }
	fclose( $pipes[0] );
	$out = stream_get_contents( $pipes[1] );
	$err = stream_get_contents( $pipes[2] );
	fclose( $pipes[1] );
	fclose( $pipes[2] );
	$code = proc_close( $process );
	return array( 'code' => $code, 'output' => json_decode( (string) $out, true ), 'error' => (string) $err );
};
try {
	$zip1 = $root . '/approved.zip';
	$files = array(
		'sabri-publishing-dashboard/sabri-publishing-dashboard.php' => $main,
		'sabri-publishing-dashboard/includes/example.php' => $nested,
	);
	$build( $zip1, $files );
	$baseline = $run( $zip1 );
	$assert( 0 === $baseline['code'] && is_array( $baseline['output'] ), 'Approved synthetic archive can be measured.' );
	$observed = $baseline['output'];
	$assert( 'file23_approved_zip_observation_v1' === ( $observed['contract'] ?? '' ), 'Output advertises a versioned observation contract, not attestation.' );
	$assert( false === ( $observed['verified'] ?? true ) && false === ( $observed['is_attestation'] ?? true ), 'Source ZIP alone never claims Hostinger installation or trusted approval.' );
	$assert( 2 === ( $observed['file_count'] ?? -1 ), 'Counts only two actual installable plugin files.' );

	$expected = array(
		'sabri-publishing-dashboard.php' . "\0" . strlen( $main ) . "\0" . hash( 'sha256', $main ),
		'includes/example.php' . "\0" . strlen( $nested ) . "\0" . hash( 'sha256', $nested ),
	);
	sort( $expected, SORT_STRING );
	$assert( hash( 'sha256', implode( "\n", $expected ) ) === ( $observed['installed_file_inventory_sha256'] ?? '' ), 'Approved ZIP tree fingerprint equals read-only installed-tree algorithm.' );
	$assert( hash_file( 'sha256', $zip1 ) === ( $observed['installable_zip_sha256'] ?? '' ), 'ZIP archive byte checksum is separately measured.' );

	$zip2 = $root . '/changed.zip';
	$changed = $files;
	$changed['sabri-publishing-dashboard/includes/example.php'] = $nested . "// modified\n";
	$build( $zip2, $changed );
	$diff = $run( $zip2 );
	$assert( 0 === $diff['code'] && ( $diff['output']['installed_file_inventory_sha256'] ?? '' ) !== ( $observed['installed_file_inventory_sha256'] ?? '' ), 'Identical header version but modified PHP bytes change tree digest.' );

	$zip3 = $root . '/traversal.zip';
	$bad = $files;
	$bad['sabri-publishing-dashboard/../outside.php'] = 'outside';
	$build( $zip3, $bad );
	$traversal = $run( $zip3 );
	$assert( 0 !== $traversal['code'] && str_contains( $traversal['error'], 'invalid_archive_entry_name' ), 'Archive with path traversal is refused before any extraction.' );

	$zip4 = $root . '/foreign.zip';
	$bad = $files;
	$bad['outside/foreign.php'] = 'outside';
	$build( $zip4, $bad );
	$foreign = $run( $zip4 );
	$assert( 0 !== $foreign['code'] && str_contains( $foreign['error'], 'archive_entry_outside_plugin_root' ), 'File outside canonical plugin root is refused.' );

	$zip5 = $root . '/missing-main.zip';
	$build( $zip5, array( 'sabri-publishing-dashboard/includes/test.php' => $nested ) );
	$missing = $run( $zip5 );
	$assert( 0 !== $missing['code'] && str_contains( $missing['error'], 'missing_canonical_plugin_entry' ), 'Archive without canonical plugin PHP is refused.' );

	$zip6 = $root . '/linked.zip';
	$bad = $files;
	$bad['sabri-publishing-dashboard/includes/linked.php'] = 'outside';
	$build( $zip6, $bad, array( 'sabri-publishing-dashboard/includes/linked.php' ) );
	$link = $run( $zip6 );
	$assert( 0 !== $link['code'] && str_contains( $link['error'], 'linked_entry_not_allowed' ), 'Unix symlink entry is refused, not counted as an approved plugin file.' );
	$missing_path = $run( $root . '/no-file.zip' );
	$assert( 0 !== $missing_path['code'], 'Absent ZIP file never produces a plausible fingerprint.' );
} finally {
	foreach ( scandir( $root ) ?: array() as $file ) {
		if ( '.' !== $file && '..' !== $file ) { @unlink( $root . '/' . $file ); }
	}
	@rmdir( $root );
}
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} V12.1 ZIP inventory tests failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1 ZIP inventory tests passed.\n";
