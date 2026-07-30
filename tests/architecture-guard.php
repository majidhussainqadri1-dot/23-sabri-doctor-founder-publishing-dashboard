<?php
/** Static architectural boundary guard for File 23 production PHP. */
$root = dirname( __DIR__ );
$violations = array();
$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
$forbidden = array(
	'generic action endpoint' => '/(?:POST\s+\/spdb\/v1\/action|register_rest_route\s*\([^;]*["\']\/action["\'])/i',
	'native post-type ownership' => '/\bregister_post_type\s*\(/i',
	'direct native post insertion' => '/\bwp_insert_post\s*\(/i',
	'direct native post mutation' => '/\bwp_update_post\s*\(/i',
	'direct native post deletion' => '/\bwp_delete_post\s*\(/i',
	'direct attachment deletion' => '/\bwp_delete_attachment\s*\(/i',
	'direct native core-table access' => '/\$wpdb\s*->\s*(posts|postmeta|comments|commentmeta)\b/i',
	'caller-supplied environment gate' => '/\bcan_write\s*\([^)]*is_production/i',
);
foreach ( $iterator as $file ) {
	if ( ! $file->isFile() || 'php' !== strtolower( $file->getExtension() ) ) { continue; }
	$relative = ltrim( str_replace( $root, '', $file->getPathname() ), DIRECTORY_SEPARATOR );
	if ( str_starts_with( $relative, 'tests' . DIRECTORY_SEPARATOR ) || str_starts_with( $relative, 'vendor' . DIRECTORY_SEPARATOR ) ) { continue; }
	$content = file_get_contents( $file->getPathname() );
	if ( false === $content ) { $violations[] = "Unable to read {$relative}"; continue; }
	foreach ( $forbidden as $label => $pattern ) {
		if ( preg_match( $pattern, $content ) ) { $violations[] = "{$relative}: {$label}"; }
	}
	$is_broker = 'includes' . DIRECTORY_SEPARATOR . 'class-spdb-operation-broker.php' === $relative;
	$is_review_controller = 'includes' . DIRECTORY_SEPARATOR . 'class-spdb-review-calendar-rest-controller.php' === $relative;
	if ( ! $is_broker && ! $is_review_controller && preg_match( '/->\s*execute_operation\s*\(/i', $content ) ) {
		$violations[] = "{$relative}: provider mutation bypasses the operation broker";
	}
	$is_metadata_schema = 'includes' . DIRECTORY_SEPARATOR . 'class-spdb-collections-schema.php' === $relative;
	if ( ! $is_metadata_schema && preg_match( '/CREATE\s+TABLE/i', $content ) ) {
		$violations[] = "{$relative}: unauthorized table ownership";
	}
}

$schema_path = $root . '/includes/class-spdb-collections-schema.php';
if ( ! is_file( $schema_path ) ) {
	$violations[] = 'includes/class-spdb-collections-schema.php: Phase 23F schema missing';
} else {
	$schema = file_get_contents( $schema_path );
	if ( false === $schema ) { $violations[] = 'Unable to read Phase 23F schema'; }
	else {
		foreach ( array( 'spdb_collections', 'spdb_collection_items', 'spdb_knowledge_links', 'dbDelta', 'idempotency_hash', 'owner_user_id', 'version' ) as $marker ) {
			if ( ! str_contains( $schema, $marker ) ) { $violations[] = "Phase 23F schema missing {$marker}"; }
		}
		if ( 3 !== substr_count( $schema, 'CREATE TABLE' ) ) { $violations[] = 'Phase 23F must own exactly three metadata tables'; }
		if ( preg_match( '/\b(?:content_body|post_content|patient_id|prescription|diagnosis|phone|email|cnic|passport|media_binary|raw_analytics)\b/i', $schema ) ) {
			$violations[] = 'Phase 23F schema contains a native or sensitive data column';
		}
	}
}

$policy_path = $root . '/includes/class-spdb-collections-policy.php';
if ( ! is_file( $policy_path ) ) {
	$violations[] = 'includes/class-spdb-collections-policy.php: Phase 23F policy missing';
} else {
	$policy = file_get_contents( $policy_path );
	if ( false === $policy ) { $violations[] = 'Unable to read Phase 23F policy'; }
	else {
		foreach ( array( 'validate_collection', 'validate_knowledge_link', 'current_user_is_founder', 'idempotency_key', 'audit_reason', 'spdb_campaign_ethics_invalid', 'spdb_knowledge_self_link_invalid' ) as $marker ) {
			if ( ! str_contains( $policy, $marker ) ) { $violations[] = "Phase 23F policy missing {$marker}"; }
		}
	}
}

$inventory_controller = $root . '/includes/class-spdb-inventory-rest-controller.php';
if ( is_file( $inventory_controller ) ) {
	$content = file_get_contents( $inventory_controller );
	if ( false === $content ) { $violations[] = 'Unable to read inventory REST controller'; }
	elseif ( preg_match( '/WP_REST_Server::(?:CREATABLE|EDITABLE|DELETABLE)|\b(?:POST|PUT|PATCH|DELETE)\b/i', $content ) ) { $violations[] = 'Inventory endpoints must remain read-only'; }
}

$review_controller = $root . '/includes/class-spdb-review-calendar-rest-controller.php';
if ( is_file( $review_controller ) ) {
	$content = file_get_contents( $review_controller );
	if ( false === $content ) { $violations[] = 'Unable to read review/calendar controller'; }
	else {
		foreach ( array( 'valid_rest_nonce', 'authorize_operation', '$this->broker->execute', 'object_version', 'idempotency_key', 'audit_reason', 'spdb_native_confirmation_invalid', 'native_refetched' ) as $marker ) {
			if ( ! str_contains( $content, $marker ) ) { $violations[] = "Review/calendar controller missing {$marker}"; }
		}
	}
}

foreach ( array( 'review.php', 'calendar.php' ) as $template_name ) {
	$path = $root . '/templates/' . $template_name;
	if ( ! is_file( $path ) ) { continue; }
	$content = file_get_contents( $path );
	if ( false === $content ) { $violations[] = "Unable to read {$template_name}"; continue; }
	if ( preg_match( '/<form\b|<button\b/i', $content ) ) { $violations[] = "{$template_name}: direct mutation UI detected"; }
	if ( ! str_contains( $content, 'Accessible validated window' ) ) { $violations[] = "{$template_name}: truthful accessible-window total missing"; }
}

$review_css = $root . '/assets/css/review-calendar.css';
if ( is_file( $review_css ) ) {
	$content = file_get_contents( $review_css );
	if ( false === $content || ! str_contains( $content, '.spdb-pagination' ) || ! str_contains( $content, ':focus-visible' ) ) { $violations[] = 'Review/calendar pagination or focus controls missing'; }
}

if ( $violations ) {
	fwrite( STDERR, "Architecture guard failed:\n- " . implode( "\n- ", $violations ) . "\n" );
	exit( 1 );
}
echo "File 23 architectural boundaries passed.\n";
