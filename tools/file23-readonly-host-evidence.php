<?php
/**
 * File23 V12.1 WP-00 — operator-only, read-only installed-plugin evidence.
 *
 * Usage (operator's *authorized* staging shell only):
 *   wp eval-file /path/to/file23-readonly-host-evidence.php
 *
 * Does NOT verify the source SHA/ZIP hash, authenticate an attestation,
 * connect to GitHub, install, activate, repair, migrate or write a database row.
 * The measured fingerprint must be independently compared by File20/host
 * against an approved and signed, exact installable-build manifest.
 */
if ( PHP_SAPI !== 'cli' || ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'ABSPATH' ) || ! defined( 'WP_PLUGIN_DIR' ) ) {
	http_response_code( 404 );
	exit;
}

$slug = 'sabri-publishing-dashboard';
$folder = WP_PLUGIN_DIR . '/' . $slug;
$entry = $folder . '/sabri-publishing-dashboard.php';
$result = array(
	'contract' => 'file23_readonly_host_observation_v1',
	'is_attestation' => false,
	'verified' => false,
	'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'unknown',
	'observed_at_unix' => time(),
	'utc' => gmdate( 'c' ),
	'wp_version' => get_bloginfo( 'version' ),
	'php_version' => PHP_VERSION,
	'installed_plugin_header_version' => null,
	'runtime_file23_version' => defined( 'SPDB_VERSION' ) ? SPDB_VERSION : null,
	'active' => false,
	'file_count' => 0,
	'nonregular_file_count' => 0,
	'installed_file_inventory_sha256' => null,
	'operations_schema_option' => null,
	'collections_schema_option' => null,
	'operations_schema_verified' => false,
	'collections_schema_verified' => false,
	'migration_state' => 'unverified',
	'conclusion' => 'measured_only__operator_comparison_and_trusted_attestation_required',
	'errors' => array(),
);

if ( ! is_dir( $folder ) || ! is_file( $entry ) || is_link( $folder ) ) {
	$result['errors'][] = 'installed_plugin_directory_unavailable_or_symlink';
} else {
	if ( ! function_exists( 'get_plugin_data' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	if ( function_exists( 'get_plugin_data' ) ) {
		$meta = get_plugin_data( $entry, false, false );
		$result['installed_plugin_header_version'] = isset( $meta['Version'] ) ? (string) $meta['Version'] : null;
	}
	if ( function_exists( 'is_plugin_active' ) ) {
		$result['active'] = is_plugin_active( $slug . '/sabri-publishing-dashboard.php' );
	}

	// Inventory content bytes, relative file names and exact sizes. Do not
	// follow symlinks, disclose absolute paths or print PHP/SQL/secrets.
	$entries = array();
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $folder, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::SELF_FIRST
	);
	$prefix = rtrim( str_replace( '\\', '/', realpath( $folder ) ), '/' ) . '/';
	foreach ( $iterator as $item ) {
		$absolute = $item->getPathname();
		// Visit directory entries as well as files: a symlinked *directory*
		// must invalidate the fingerprint rather than being skipped as a
		// non-leaf. Never recurse into linked directories.
		if ( $item->isDir() && ! $item->isLink() ) {
			continue;
		}
		if ( $item->isLink() || ! $item->isFile() || ! $item->isReadable() ) {
			++$result['nonregular_file_count'];
			continue;
		}
		$normalized = str_replace( '\\', '/', $absolute );
		if ( 0 !== strpos( $normalized, $prefix ) ) {
			++$result['nonregular_file_count'];
			continue;
		}
		$relative = substr( $normalized, strlen( $prefix ) );
		$file_hash = hash_file( 'sha256', $absolute );
		$file_size = filesize( $absolute );
		if ( ! is_string( $file_hash ) || false === $file_size ) {
			++$result['nonregular_file_count'];
			continue;
		}
		$entries[] = $relative . "\0" . (string) $file_size . "\0" . $file_hash;
	}
	sort( $entries, SORT_STRING );
	$result['file_count'] = count( $entries );
	$result['installed_file_inventory_sha256'] = hash( 'sha256', implode( "\n", $entries ) );
	if ( $result['nonregular_file_count'] ) {
		$result['errors'][] = 'nonregular_or_unreadable_installed_file';
	}
}

$result['operations_schema_option'] = (string) get_option( 'spdb_operations_schema_version', '' );
$result['collections_schema_option'] = (string) get_option( 'spdb_collections_schema_version', '' );
if ( $result['active'] && class_exists( 'SPDB_Operations_Schema' ) && class_exists( 'SPDB_Collections_Schema' ) ) {
	$operations = SPDB_Operations_Schema::verify();
	$collections = SPDB_Collections_Schema::verify();
	$result['operations_schema_verified'] = true === $operations;
	$result['collections_schema_verified'] = true === $collections;
	$result['migration_state'] =
		$result['operations_schema_verified']
		&& $result['collections_schema_verified']
		&& SPDB_Operations_Schema::VERSION === $result['operations_schema_option']
		&& SPDB_Collections_Schema::VERSION === $result['collections_schema_option']
		? 'file23_schema_verified__external_migration_evidence_still_required'
		: 'incomplete_or_unverified';
} else {
	$result['errors'][] = 'active_file23_schema_api_unavailable';
}

if ( ! $result['active'] ) {
	$result['errors'][] = 'file23_plugin_not_active';
}

if ( defined( 'SPDB_VERSION' ) && is_string( $result['installed_plugin_header_version'] )
	&& ! hash_equals( SPDB_VERSION, $result['installed_plugin_header_version'] ) ) {
	$result['errors'][] = 'header_runtime_version_mismatch';
}

echo wp_json_encode( $result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Operator-only CLI JSON, no personal information.
