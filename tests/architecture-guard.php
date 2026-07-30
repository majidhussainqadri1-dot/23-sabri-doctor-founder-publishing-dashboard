<?php
/** Static architectural boundary guard for File 23 production PHP. */
$root = dirname( __DIR__ );
$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
$violations = array();
$patterns = array(
	'generic action endpoint' => '/(?:POST\s+\/spdb\/v1\/action|register_rest_route\s*\([^;]*["\']\/action["\'])/i',
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
	foreach ( $patterns as $label => $pattern ) {
		if ( preg_match( $pattern, $content ) ) { $violations[] = "{$relative}: {$label}"; }
	}
	if ( ! in_array( $relative, array( 'includes' . DIRECTORY_SEPARATOR . 'class-spdb-operation-broker.php', 'includes' . DIRECTORY_SEPARATOR . 'class-spdb-review-calendar-rest-controller.php' ), true ) && preg_match( '/->\s*execute_operation\s*\(/i', $content ) ) {
		$violations[] = "{$relative}: provider mutation bypasses the operation broker";
	}
	$normalized = preg_replace( '/\s+/', ' ', $content ) ?? $content;
	$is_metadata_schema = 'includes' . DIRECTORY_SEPARATOR . 'class-spdb-collections-schema.php' === $relative;
	if ( ! $is_metadata_schema && preg_match( '/CREATE\s+TABLE/i', $normalized ) ) {
		$violations[] = "{$relative}: unauthorized table ownership";
	}
	if ( ! $is_metadata_schema && preg_match( '/CREATE\s+TABLE[^;]*(publication|draft|review|schedule|calendar|reviewer|comment|correction|retraction|source|media|notification|appointment|clinical|prescription|analytics_event|profile|knowledge)/i', $normalized ) ) {
		$violations[] = "{$relative}: forbidden native-domain table ownership";
	}
}

$schema_path = $root . '/includes/class-spdb-collections-schema.php';
if ( ! is_file( $schema_path ) ) {
	$violations[] = 'includes/class-spdb-collections-schema.php: Phase 23F metadata schema missing';
} else {
	$schema = file_get_contents( $schema_path );
	if ( false === $schema ) { $violations[] = 'Unable to read includes/class-spdb-collections-schema.php'; }
	else {
		foreach ( array( 'spdb_collections', 'spdb_collection_items', 'spdb_knowledge_links', 'dbDelta', 'idempotency_hash', 'owner_user_id', 'version' ) as $marker ) {
			if ( ! str_contains( $schema, $marker ) ) { $violations[] = "includes/class-spdb-collections-schema.php: missing metadata control {$marker}"; }
		}
		if ( preg_match( '/\b(?:content_body|post_content|patient_id|prescription|diagnosis|phone|email|cnic|passport|media_binary|raw_analytics)\b/i', $schema ) ) {
			$violations[] = 'includes/class-spdb-collections-schema.php: native or sensitive data column detected';
		}
		if ( 3 !== substr_count( $schema, 'CREATE TABLE' ) ) {
			$violations[] = 'includes/class-spdb-collections-schema.php: exactly three metadata tables are required';
		}
	}
}

$inventory_controller = $root . '/includes/class-spdb-inventory-rest-controller.php';
if ( is_file( $inventory_controller ) ) {
	$content = file_get_contents( $inventory_controller );
	if ( false === $content ) { $violations[] = 'Unable to read includes/class-spdb-inventory-rest-controller.php'; }
	elseif ( preg_match( '/WP_REST_Server::(?:CREATABLE|EDITABLE|DELETABLE)|\b(?:POST|PUT|PATCH|DELETE)\b/i', $content ) ) { $violations[] = 'includes/class-spdb-inventory-rest-controller.php: inventory endpoints must remain read-only'; }
}

$services = array(
	'includes/class-spdb-federated-inventory.php' => array( 'execution_exposed', 'MAX_PROVIDERS' ),
	'includes/class-spdb-role-workspace-service.php' => array( 'derive_context', 'action_contract', 'is_environment_write_eligible', 'MAX_PROVIDERS', 'MAX_ACTIONS' ),
	'includes/class-spdb-review-calendar-service.php' => array( 'SPDB_Review_Calendar_Provider_Adapter', 'authorize_operation', 'reviewer_target_is_eligible', 'operation_contract', 'accessible_total', 'MAX_PROVIDERS', 'MAX_ITEMS' ),
);
foreach ( $services as $relative => $markers ) {
	$path = $root . '/' . $relative;
	if ( ! is_file( $path ) ) { continue; }
	$content = file_get_contents( $path );
	if ( false === $content ) { $violations[] = "Unable to read {$relative}"; continue; }
	foreach ( $markers as $marker ) { if ( ! str_contains( $content, $marker ) ) { $violations[] = "{$relative}: missing architecture gate {$marker}"; } }
}

$review_controller = $root . '/includes/class-spdb-review-calendar-rest-controller.php';
if ( is_file( $review_controller ) ) {
	$content = file_get_contents( $review_controller );
	if ( false === $content ) { $violations[] = 'Unable to read includes/class-spdb-review-calendar-rest-controller.php'; }
	else {
		foreach ( array( '/approve', '/request-changes', '/reject', '/assign-reviewer', '/schedule', '/reschedule', '/unschedule', 'valid_rest_nonce', 'authorize_operation', 'object_version', 'idempotency_key', 'audit_reason', 'spdb_native_confirmation_invalid', 'native_refetched' ) as $marker ) {
			if ( ! str_contains( $content, $marker ) ) { $violations[] = "includes/class-spdb-review-calendar-rest-controller.php: missing explicit operation control {$marker}"; }
		}
		if ( ! str_contains( $content, '$this->broker->execute' ) ) { $violations[] = 'includes/class-spdb-review-calendar-rest-controller.php: guarded operation broker is not used'; }
	}
}

$policy = $root . '/includes/class-spdb-collections-policy.php';
if ( ! is_file( $policy ) ) { $violations[] = 'includes/class-spdb-collections-policy.php: Phase 23F policy missing'; }
else {
	$content = file_get_contents( $policy );
	if ( false === $content ) { $violations[] = 'Unable to read includes/class-spdb-collections-policy.php'; }
	else {
		foreach ( array( 'validate_collection', 'validate_knowledge_link', 'current_user_is_founder', 'idempotency_key', 'audit_reason', 'spdb_campaign_ethics_invalid', 'spdb_knowledge_self_link_invalid' ) as $marker ) {
			if ( ! str_contains( $content, $marker ) ) { $violations[] = "includes/class-spdb-collections-policy.php: missing Phase 23F policy {$marker}"; }
		}
		if ( preg_match( '/\b(?:content_body|post_content|patient_id|prescription|diagnosis)\b/i', $content ) ) {
			$violations[] = 'includes/class-spdb-collections-policy.php: native or clinical field ownership detected';
		}
	}
}

foreach ( array( 'workspace.php', 'review.php', 'calendar.php' ) as $template_name ) {
	$template = $root . '/templates/' . $template_name;
	if ( ! is_file( $template ) ) { continue; }
	$content = file_get_contents( $template );
	if ( false === $content ) { $violations[] = "Unable to read templates/{$template_name}"; continue; }
	if ( 'workspace.php' !== $template_name && preg_match( '/<form\b|<button\b/i', $content ) ) { $violations[] = "templates/{$template_name}: projection must not contain direct mutation forms or buttons"; }
	if ( in_array( $template_name, array( 'review.php', 'calendar.php' ), true ) && ! str_contains( $content, 'Accessible validated window' ) ) { $violations[] = "templates/{$template_name}: truthful accessible-window total missing"; }
	if ( 'workspace.php' === $template_name && preg_match( '/<form\b|<button\b/i', $content ) ) { $violations[] = 'templates/workspace.php: workspace must not contain a native mutation form or button'; }
}

$workspace_css = $root . '/assets/css/workspace.css';
if ( is_file( $workspace_css ) ) { $content = file_get_contents( $workspace_css ); if ( false !== $content && preg_match( '/content\s*:\s*["\']/', $content ) ) { $violations[] = 'assets/css/workspace.css: user-facing generated text must not be hard-coded in CSS'; } }
$review_css = $root . '/assets/css/review-calendar.css';
if ( is_file( $review_css ) ) { $content = file_get_contents( $review_css ); if ( false === $content || ! str_contains( $content, '.spdb-pagination' ) || ! str_contains( $content, ':focus-visible' ) ) { $violations[] = 'assets/css/review-calendar.css: corrective pagination/focus controls missing'; } }

if ( $violations ) { fwrite( STDERR, "Architecture guard failed:\n- " . implode( "\n- ", $violations ) . "\n" ); exit( 1 ); }
echo "File 23 architectural boundaries passed.\n";
