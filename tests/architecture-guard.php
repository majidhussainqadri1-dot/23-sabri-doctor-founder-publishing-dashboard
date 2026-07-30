<?php
/** Static architectural boundary guard for File 23 production PHP. */
$root = dirname( __DIR__ );
$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
$violations = array();
$patterns = array(
	'generic action endpoint' => '/POST\s+\/spdb\/v1\/action/i',
	'native post-type ownership' => '/\bregister_post_type\s*\(/i',
	'direct native post insertion' => '/\bwp_insert_post\s*\(/i',
	'direct native post mutation' => '/\bwp_update_post\s*\(/i',
	'direct native post deletion' => '/\bwp_delete_post\s*\(/i',
	'direct native attachment delete' => '/\bwp_delete_attachment\s*\(/i',
	'direct posts table access' => '/\$wpdb\s*->\s*(posts|postmeta|comments|commentmeta)\b/i',
	'legacy provider maturity API' => '/\bget_maturity_state\s*\(/i',
	'caller-supplied environment gate' => '/\bcan_write\s*\([^)]*is_production/i',
);
foreach ( $iterator as $file ) {
	if ( ! $file->isFile() || 'php' !== strtolower( $file->getExtension() ) ) { continue; }
	$path = $file->getPathname();
	$relative = ltrim( str_replace( $root, '', $path ), DIRECTORY_SEPARATOR );
	if ( str_starts_with( $relative, 'tests' . DIRECTORY_SEPARATOR ) || str_starts_with( $relative, 'vendor' . DIRECTORY_SEPARATOR ) ) { continue; }
	$content = file_get_contents( $path );
	if ( false === $content ) { $violations[] = "Unable to read {$relative}"; continue; }
	foreach ( $patterns as $label => $pattern ) { if ( preg_match( $pattern, $content ) ) { $violations[] = "{$relative}: {$label}"; } }
	if ( 'includes' . DIRECTORY_SEPARATOR . 'class-spdb-operation-broker.php' !== $relative && preg_match( '/->\s*execute_operation\s*\(/i', $content ) ) { $violations[] = "{$relative}: provider mutation bypasses the operation broker"; }
	$normalized = preg_replace( '/\s+/', ' ', $content ) ?? $content;
	if ( preg_match( '/CREATE\s+TABLE[^;]*(publication|draft|review|schedule|comment|correction|retraction|source|media|notification|appointment|clinical|prescription|analytics_event|profile|knowledge)/i', $normalized ) ) { $violations[] = "{$relative}: forbidden native-domain table ownership"; }
}
$inventory_controller = $root . '/includes/class-spdb-inventory-rest-controller.php';
if ( is_file( $inventory_controller ) ) {
	$content = file_get_contents( $inventory_controller );
	if ( false === $content ) { $violations[] = 'Unable to read includes/class-spdb-inventory-rest-controller.php'; }
	else {
		if ( preg_match( '/WP_REST_Server::(?:CREATABLE|EDITABLE|DELETABLE)/', $content ) ) { $violations[] = 'includes/class-spdb-inventory-rest-controller.php: inventory endpoints must remain read-only'; }
		if ( preg_match( '/\b(?:POST|PUT|PATCH|DELETE)\b/i', $content ) ) { $violations[] = 'includes/class-spdb-inventory-rest-controller.php: mutation HTTP method detected'; }
	}
}
$inventory_service = $root . '/includes/class-spdb-federated-inventory.php';
if ( is_file( $inventory_service ) ) {
	$content = file_get_contents( $inventory_service );
	if ( false === $content ) { $violations[] = 'Unable to read includes/class-spdb-federated-inventory.php'; }
	else {
		foreach ( array( 'execute_operation', 'wp_insert_post', 'wp_update_post', 'wp_delete_post' ) as $call ) { if ( preg_match( '/\b' . preg_quote( $call, '/' ) . '\s*\(/', $content ) ) { $violations[] = "includes/class-spdb-federated-inventory.php: forbidden mutation call {$call}"; } }
		if ( ! preg_match( "/\['execution_exposed'\]\s*=\s*false\s*;/", $content ) && ! preg_match( "/'execution_exposed'\s*=>\s*false/", $content ) ) { $violations[] = 'includes/class-spdb-federated-inventory.php: mutation execution boundary marker missing'; }
	}
}
$workspace_service = $root . '/includes/class-spdb-role-workspace-service.php';
if ( is_file( $workspace_service ) ) {
	$content = file_get_contents( $workspace_service );
	if ( false === $content ) { $violations[] = 'Unable to read includes/class-spdb-role-workspace-service.php'; }
	else {
		foreach ( array( 'execute_operation', 'wp_insert_post', 'wp_update_post', 'wp_delete_post', 'update_user_meta', 'update_option' ) as $call ) { if ( preg_match( '/\b' . preg_quote( $call, '/' ) . '\s*\(/', $content ) ) { $violations[] = "includes/class-spdb-role-workspace-service.php: forbidden mutation call {$call}"; } }
		foreach ( array( 'derive_context', 'action_contract', 'is_environment_write_eligible', 'MAX_PROVIDERS', 'MAX_ACTIONS', 'gmdate' ) as $marker ) { if ( ! str_contains( $content, $marker ) ) { $violations[] = "includes/class-spdb-role-workspace-service.php: missing corrective gate {$marker}"; } }
	}
}
$validator = $root . '/includes/class-spdb-workspace-projection-validator.php';
if ( is_file( $validator ) ) {
	$content = file_get_contents( $validator );
	if ( false === $content ) { $violations[] = 'Unable to read includes/class-spdb-workspace-projection-validator.php'; }
	else { foreach ( array( 'action_contract', 'spdb_workspace_action_contract_mismatch', 'supported_capabilities', 'profile_timestamp_invalid', 'knowledge_timestamp_invalid' ) as $marker ) { if ( ! str_contains( $content, $marker ) ) { $violations[] = "includes/class-spdb-workspace-projection-validator.php: missing semantic validation {$marker}"; } } }
}
$template = $root . '/templates/workspace.php';
if ( is_file( $template ) ) {
	$content = file_get_contents( $template );
	if ( false === $content ) { $violations[] = 'Unable to read templates/workspace.php'; }
	else {
		if ( preg_match( '/<form\b|<button\b/i', $content ) ) { $violations[] = 'templates/workspace.php: workspace must not contain a native mutation form or button'; }
		if ( preg_match( '/\$profile\[(?:\'|\")edit_destination(?:\'|\")\]|\$profile\[(?:\'|\")public_destination(?:\'|\")\]|\$knowledge\[(?:\'|\")destination(?:\'|\")\]/', $content ) ) { $violations[] = 'templates/workspace.php: direct profile or knowledge destination bypasses the action gate'; }
		if ( ! str_contains( $content, 'Opens native management' ) ) { $violations[] = 'templates/workspace.php: localized management disclosure is missing'; }
	}
}
$css = $root . '/assets/css/workspace.css';
if ( is_file( $css ) ) { $content = file_get_contents( $css ); if ( false !== $content && preg_match( '/content\s*:\s*["\']/', $content ) ) { $violations[] = 'assets/css/workspace.css: user-facing generated text must not be hard-coded in CSS'; } }
if ( $violations ) { fwrite( STDERR, "Architecture guard failed:\n- " . implode( "\n- ", $violations ) . "\n" ); exit( 1 ); }
echo "File 23 architectural boundaries passed.\n";
