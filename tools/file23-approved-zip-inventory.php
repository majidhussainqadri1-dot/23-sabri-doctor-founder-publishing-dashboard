<?php
/**
 * File23 V12.1 WP-00 — read-only candidate ZIP inventory observation.
 *
 * Usage: php tools/file23-approved-zip-inventory.php /private/approved-plugin.zip
 *
 * A ZIP filename or sha256 received from a caller is NEVER a trusted CI
 * manifest. This tool performs only local byte measurement; an authorized
 * File20/Hostinger operator must establish independent build approval,
 * installed-tree identity, DB/schema/migration parity and freshness.
 *
 * The inventory hash uses the same algorithm as
 * tools/file23-readonly-host-evidence.php:
 *   sort(relative_filename . NUL . byte_length . NUL . sha256(contents));
 *   sha256(join("\n", entries))
 */
if ( 'cli' !== PHP_SAPI || ! class_exists( 'ZipArchive' ) || 2 !== ( $argc ?? 0 ) ) {
	fwrite( STDERR, "Usage: php file23-approved-zip-inventory.php /private/approved-plugin.zip (PHP CLI with ZipArchive)\n" );
	exit( 2 );
}

$path = (string) $argv[1];
if ( '' === $path || is_link( $path ) || ! is_file( $path ) || ! is_readable( $path ) ) {
	fwrite( STDERR, "The installable ZIP must be a regular readable, non-symlinked local file.\n" );
	exit( 2 );
}

$zip = new ZipArchive();
if ( true !== $zip->open( $path, ZipArchive::RDONLY ) ) {
	fwrite( STDERR, "The supplied installable ZIP could not be opened.\n" );
	exit( 2 );
}
$root       = 'sabri-publishing-dashboard/';
$entries    = array();
$seen       = array();
$total_size = 0;
$error      = '';
$limit_size = 100 * 1024 * 1024;

if ( $zip->numFiles < 1 || $zip->numFiles > 10000 ) {
	$error = 'invalid_file_count';
}

for ( $i = 0; '' === $error && $i < $zip->numFiles; ++$i ) {
	$name = $zip->getNameIndex( $i );
	if ( ! is_string( $name ) || ! str_starts_with( $name, $root ) ) {
		$error = 'archive_entry_outside_plugin_root';
		break;
	}
	$relative = substr( $name, strlen( $root ) );
	if (
		str_contains( $relative, "\0" ) ||
		str_contains( $relative, '\\' ) ||
		str_contains( $relative, '//' ) ||
		preg_match( '~(^|/)\.\.?(/|$)~', $relative )
	) {
		$error = 'invalid_archive_entry_name';
		break;
	}
	if ( str_ends_with( $name, '/' ) ) {
		continue; // ZIP directory entries are not installed file bytes.
	}
	if ( '' === $relative || isset( $seen[ $relative ] ) ) {
		$error = 'duplicate_or_empty_filename';
		break;
	}
	$seen[ $relative ] = true;

	$opsys = 0;
	$attributes = 0;
	if ( $zip->getExternalAttributesIndex( $i, $opsys, $attributes ) && $opsys === ZipArchive::OPSYS_UNIX ) {
		$mode = ( $attributes >> 16 ) & 0170000;
		if ( 0120000 === $mode ) {
			$error = 'linked_entry_not_allowed';
			break;
		}
	}

	$stat = $zip->statIndex( $i );
	if ( ! is_array( $stat ) || ! isset( $stat['size'] ) || $stat['size'] < 0 || $stat['size'] > 25 * 1024 * 1024 ) {
		$error = 'invalid_file_length';
		break;
	}
	$total_size += (int) $stat['size'];
	if ( $total_size > $limit_size ) {
		$error = 'uncompressed_archive_limit_exceeded';
		break;
	}

	$stream = $zip->getStream( $name );
	if ( ! is_resource( $stream ) ) {
		$error = 'archive_stream_unavailable';
		break;
	}
	$hash   = hash_init( 'sha256' );
	$actual = 0;
	while ( ! feof( $stream ) ) {
		$chunk = fread( $stream, 65536 );
		if ( false === $chunk || ( '' === $chunk && ! feof( $stream ) ) ) {
			$error = 'archive_stream_read_failed';
			break;
		}
		$actual += strlen( $chunk );
		if ( $actual > (int) $stat['size'] ) {
			$error = 'archive_length_mismatch';
			break;
		}
		hash_update( $hash, $chunk );
	}
	fclose( $stream );
	if ( '' !== $error || $actual !== (int) $stat['size'] ) {
		$error = '' !== $error ? $error : 'archive_length_mismatch';
		break;
	}
	$entries[] = $relative . "\0" . (string) $actual . "\0" . hash_final( $hash );
}
$zip->close();

if ( '' === $error && ! isset( $seen['sabri-publishing-dashboard.php'] ) ) {
	$error = 'missing_canonical_plugin_entry';
}
if ( '' !== $error ) {
	fwrite( STDERR, "File23 ZIP inventory denied: {$error}\n" );
	exit( 1 );
}

sort( $entries, SORT_STRING );
$archive_sha = hash_file( 'sha256', $path );
if ( ! is_string( $archive_sha ) ) {
	fwrite( STDERR, "File23 ZIP archive bytes could not be hashed.\n" );
	exit( 1 );
}

echo json_encode(
	array(
		'contract'                      => 'file23_approved_zip_observation_v1',
		'is_attestation'                => false,
		'verified'                      => false,
		'file_count'                    => count( $entries ),
		'uncompressed_bytes'           => $total_size,
		'installable_zip_sha256'       => $archive_sha,
		'installed_file_inventory_sha256' => hash( 'sha256', implode( "\n", $entries ) ),
		'conclusion'                    => 'candidate_zip_measured_only__independent_approval_and_host_comparison_required',
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
) . "\n";
