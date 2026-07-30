<?php
/**
 * Static architectural boundary guard for File 23 production PHP.
 */

$root       = dirname( __DIR__ );
$iterator   = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
$violations = array();

$patterns = array(
	'generic action endpoint'          => '/POST\s+\/spdb\/v1\/action/i',
	'native post-type ownership'       => '/\bregister_post_type\s*\(/i',
	'direct native post insertion'     => '/\bwp_insert_post\s*\(/i',
	'direct native post mutation'      => '/\bwp_update_post\s*\(/i',
	'direct native post deletion'      => '/\bwp_delete_post\s*\(/i',
	'direct native attachment delete'  => '/\bwp_delete_attachment\s*\(/i',
	'direct posts table access'        => '/\$wpdb\s*->\s*(posts|postmeta|comments|commentmeta)\b/i',
	'legacy provider maturity API'     => '/\bget_maturity_state\s*\(/i',
	'caller-supplied environment gate' => '/\bcan_write\s*\([^)]*is_production/i',
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
	if ( preg_match( '/CREATE\s+TABLE[^;]*(publication|draft|review|schedule|comment|correction|retraction|source|media|notification|appointment|clinical|prescription|analytics_event|profile|knowledge)/i', $normalized ) ) {
		$violations[] = "{$relative}: forbidden native-domain table ownership";
	}
}

$inventory_controller = $root . '/includes/class-spdb-inventory-rest-controller.php';
if ( is_file( $inventory_controller ) ) {
	$inventory_content = file_get_contents( $inventory_controller );
	if ( false === $inventory_content ) {
		$violations[] = 'Unable to read includes/class-spdb-inventory-rest-controller.php';
	} else {
		if ( preg_match( '/WP_REST_Server::(?:CREATABLE|EDITABLE|DELETABLE)/', $inventory_content ) ) {
			$violations[] = 'includes/class-spdb-inventory-rest-controller.php: inventory endpoints must remain read-only';
		}
		if ( preg_match( '/\b(?:POST|PUT|PATCH|DELETE)\b/i', $inventory_content ) ) {
			$violations[] = 'includes/class-spdb-inventory-rest-controller.php: mutation HTTP method detected';
		}
	}
}

$inventory_service = $root . '/includes/class-spdb-federated-inventory.php';
if ( is_file( $inventory_service ) ) {
	$inventory_content = file_get_contents( $inventory_service );
	if ( false === $inventory_content ) {
		$violations[] = 'Unable to read includes/class-spdb-federated-inventory.php';
	} else {
		foreach ( array( 'execute_operation', 'wp_insert_post', 'wp_update_post', 'wp_delete_post' ) as $forbidden_call ) {
			if ( preg_match( '/\b' . preg_quote( $forbidden_call, '/' ) . '\s*\(/', $inventory_content ) ) {
				$violations[] = "includes/class-spdb-federated-inventory.php: forbidden mutation call {$forbidden_call}";
			}
		}
		if ( ! preg_match( "/\['execution_exposed'\]\s*=\s*false\s*;/", $inventory_content ) && ! preg_match( "/'execution_exposed'\s*=>\s*false/", $inventory_content ) ) {
			$violations[] = 'includes/class-spdb-federated-inventory.php: mutation execution boundary marker missing';
		}
	}
}

$workspace_service = $root . '/includes/class-spdb-role-workspace-service.php';
if ( is_file( $workspace_service ) ) {
	$workspace_content = file_get_contents( $workspace_service );
	if ( false === $workspace_content ) {
		$violations[] = 'Unable to read includes/class-spdb-role-workspace-service.php';
	} else {
		foreach ( array( 'execute_operation', 'wp_insert_post', 'wp_update_post', 'wp_delete_post', 'update_user_meta', 'update_option' ) as $forbidden_call ) {
			if ( preg_match( '/\b' . preg_quote( $forbidden_call, '/' ) . '\s*\(/', $workspace_content ) ) {
				$violations[] = "includes/class-spdb-role-workspace-service.php: forbidden mutation call {$forbidden_call}";
			}
		}
		if ( ! str_contains( $workspace_content, 'is_environment_write_eligible' ) || ! str_contains( $workspace_content, 'founder_only' ) ) {
			$violations[] = 'includes/class-spdb-role-workspace-service.php: required action gates are missing';
		}
	}
}

$workspace_template = $root . '/templates/workspace.php';
if ( is_file( $workspace_template ) ) {
	$template_content = file_get_contents( $workspace_template );
	if ( false === $template_content ) {
		$violations[] = 'Unable to read templates/workspace.php';
	} elseif ( preg_match( '/<form\b|<button\b/i', $template_content ) ) {
		$violations[] = 'templates/workspace.php: Phase 23D workspace must not contain a native mutation form or button';
	}
}

if ( $violations ) {
	fwrite( STDERR, "Architecture guard failed:\n- " . implode( "\n- ", $violations ) . "\n" );
	exit( 1 );
}

echo "File 23 architectural boundaries passed.\n";
