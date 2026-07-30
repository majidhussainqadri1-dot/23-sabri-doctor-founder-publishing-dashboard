<?php
/**
 * Static architectural boundary guard for File 23 production PHP.
 */

$root       = dirname( __DIR__ );
$iterator   = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
$violations = array();

$patterns = array(
	'generic action endpoint'         => '/POST\s+\/spdb\/v1\/action/i',
	'native post-type ownership'      => '/\bregister_post_type\s*\(/i',
	'direct native post insertion'    => '/\bwp_insert_post\s*\(/i',
	'direct native post mutation'     => '/\bwp_update_post\s*\(/i',
	'direct native post deletion'     => '/\bwp_delete_post\s*\(/i',
	'direct native attachment delete' => '/\bwp_delete_attachment\s*\(/i',
	'direct posts table access'       => '/\$wpdb\s*->\s*(posts|postmeta|comments|commentmeta)\b/i',
	'legacy provider maturity API'    => '/\bget_maturity_state\s*\(/i',
	'caller-supplied environment gate'=> '/\bcan_write\s*\([^)]*is_production/i',
);

foreach ( $iterator as $file ) {
	if ( ! $file->isFile() || 'php' !== strtolower( $file->getExtension() ) ) {
		continue;
	}

	$path     = $file->getPathname();
	$relative = ltrim( str_replace( $root, '', $path ), DIRECTORY_SEPARATOR );
	if ( str_starts_with( $relative, 'tests' . DIRECTORY_SEPARATOR ) || str_starts_with( $relative, 'vendor' . DIRECTORY_SEPARATOR ) ) {
		continue;
	}

	$content = file_get_contents( $path );
	if ( false === $content ) {
		$violations[] = "Unable to read {$relative}";
		continue;
	}

	foreach ( $patterns as $label => $pattern ) {
		if ( preg_match( $pattern, $content ) ) {
			$violations[] = "{$relative}: {$label}";
		}
	}

	if ( 'includes' . DIRECTORY_SEPARATOR . 'class-spdb-operation-broker.php' !== $relative && preg_match( '/->\s*execute_operation\s*\(/i', $content ) ) {
		$violations[] = "{$relative}: provider mutation bypasses the operation broker";
	}

	$normalized = preg_replace( '/\s+/', ' ', $content ) ?? $content;
	if ( preg_match( '/CREATE\s+TABLE[^;]*(publication|comment|correction|retraction|source|media|notification|appointment|clinical|prescription)/i', $normalized ) ) {
		$violations[] = "{$relative}: forbidden native-domain table ownership";
	}
}

if ( $violations ) {
	fwrite( STDERR, "Architecture guard failed:\n- " . implode( "\n- ", $violations ) . "\n" );
	exit( 1 );
}

echo "File 23 architectural boundaries passed.\n";
