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
	if ( 'includes' . DIRECTORY_SEPARATOR . 'class-spdb-operation-broker.php' !== $relative && 'includes' . DIRECTORY_SEPARATOR . 'class-spdb-review-calendar-rest-controller.php' !== $relative && preg_match( '/->\s*execute_operation\s*\(/i', $content ) ) {
		$violations[] = "{$relative}: provider mutation bypasses the operation broker";
	}
	$is_metadata_schema = 'includes' . DIRECTORY_SEPARATOR . 'class-spdb-collections-schema.php' === $relative;
	if ( ! $is_metadata_schema && preg_match( '/CREATE\s+TABLE/i', $content ) ) {
		$violations[] = "{$relative}: unauthorized table ownership";
	}
	$normalized = preg_replace( '/\s+/', ' ', $content ) ?? $content;
	if ( ! $is_metadata_schema && preg_match( '/CREATE\s+TABLE[^;]*(publication|draft|review|schedule|calendar|reviewer|comment|correction|retraction|source|media|notification|appointment|clinical|prescription|analytics_event|profile|knowledge)/i', $normalized ) ) {
		$violations[] = "{$relative}: forbidden native-domain table ownership";
	}
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
		foreach ( array( 'execute_operation', 'wp_insert_post', 'wp_update_post', 'wp_delete_post' ) as $call ) {
			if ( preg_match( '/\b' . preg_quote( $call, '/' ) . '\s*\(/', $content ) ) { $violations[] = "includes/class-spdb-federated-inventory.php: forbidden mutation call {$call}"; }
		}
		if ( ! preg_match( "/\['execution_exposed'\]\s*=\s*false\s*;/", $content ) && ! preg_match( "/'execution_exposed'\s*=>\s*false/", $content ) ) { $violations[] = 'includes/class-spdb-federated-inventory.php: mutation execution boundary marker missing'; }
	}
}

$workspace_service = $root . '/includes/class-spdb-role-workspace-service.php';
if ( is_file( $workspace_service ) ) {
	$content = file_get_contents( $workspace_service );
	if ( false === $content ) { $violations[] = 'Unable to read includes/class-spdb-role-workspace-service.php'; }
	else {
		foreach ( array( 'execute_operation', 'wp_insert_post', 'wp_update_post', 'wp_delete_post', 'update_user_meta', 'update_option' ) as $call ) {
			if ( preg_match( '/\b' . preg_quote( $call, '/' ) . '\s*\(/', $content ) ) { $violations[] = "includes/class-spdb-role-workspace-service.php: forbidden mutation call {$call}"; }
		}
		foreach ( array( 'derive_context', 'action_contract', 'is_environment_write_eligible', 'MAX_PROVIDERS', 'MAX_ACTIONS', 'gmdate' ) as $marker ) {
			if ( ! str_contains( $content, $marker ) ) { $violations[] = "includes/class-spdb-role-workspace-service.php: missing corrective gate {$marker}"; }
		}
	}
}

$review_service = $root . '/includes/class-spdb-review-calendar-service.php';
if ( is_file( $review_service ) ) {
	$content = file_get_contents( $review_service );
	if ( false === $content ) { $violations[] = 'Unable to read includes/class-spdb-review-calendar-service.php'; }
	else {
		foreach ( array( 'execute_operation', 'wp_insert_post', 'wp_update_post', 'wp_delete_post', 'update_post_meta', 'update_option' ) as $call ) {
			if ( preg_match( '/\b' . preg_quote( $call, '/' ) . '\s*\(/', $content ) ) { $violations[] = "includes/class-spdb-review-calendar-service.php: forbidden native mutation call {$call}"; }
		}
		foreach ( array( 'SPDB_Review_Calendar_Provider_Adapter', 'authorize_operation', 'reviewer_target_is_eligible', 'operation_contract', 'get_allowed_operations', 'is_environment_write_eligible', 'provider_query', 'accessible_total', 'MAX_PROVIDERS', 'MAX_ITEMS' ) as $marker ) {
			if ( ! str_contains( $content, $marker ) ) { $violations[] = "includes/class-spdb-review-calendar-service.php: missing corrective Phase 23E gate {$marker}"; }
		}
	}
}

$review_controller = $root . '/includes/class-spdb-review-calendar-rest-controller.php';
if ( is_file( $review_controller ) ) {
	$content = file_get_contents( $review_controller );
	if ( false === $content ) { $violations[] = 'Unable to read includes/class-spdb-review-calendar-rest-controller.php'; }
	else {
		foreach ( array( '/approve', '/request-changes', '/reject', '/assign-reviewer', '/schedule', '/reschedule', '/unschedule', 'valid_rest_nonce', 'authorize_operation', 'spdb_operation_payload_field_invalid', 'object_version', 'idempotency_key', 'audit_reason', 'spdb_native_confirmation_invalid', 'native_refetched' ) as $marker ) {
			if ( ! str_contains( $content, $marker ) ) { $violations[] = "includes/class-spdb-review-calendar-rest-controller.php: missing explicit operation control {$marker}"; }
		}
		if ( ! str_contains( $content, '$this->broker->execute' ) ) { $violations[] = 'includes/class-spdb-review-calendar-rest-controller.php: guarded operation broker is not used'; }
	}
}

$workspace_validator = $root . '/includes/class-spdb-workspace-projection-validator.php';
if ( is_file( $workspace_validator ) ) {
	$content = file_get_contents( $workspace_validator );
	if ( false === $content ) { $violations[] = 'Unable to read includes/class-spdb-workspace-projection-validator.php'; }
	else {
		foreach ( array( 'action_contract', 'spdb_workspace_action_contract_mismatch', 'supported_capabilities', 'profile_timestamp_invalid', 'knowledge_timestamp_invalid' ) as $marker ) {
			if ( ! str_contains( $content, $marker ) ) { $violations[] = "includes/class-spdb-workspace-projection-validator.php: missing semantic validation {$marker}"; }
		}
	}
}

$review_validator = $root . '/includes/class-spdb-review-calendar-validator.php';
if ( is_file( $review_validator ) ) {
	$content = file_get_contents( $review_validator );
	if ( false === $content ) { $violations[] = 'Unable to read includes/class-spdb-review-calendar-validator.php'; }
	else {
		foreach ( array( 'normalize_query', 'operation_contract', 'MAX_REPORTED_TOTAL', 'normalize_utc_timestamp', 'strict_nonnegative_integer', 'required_text', 'normalize_flags', 'separation_required', 'native_timezone', 'native_version', 'last_synced_at', 'assigned_reviewer_id' ) as $marker ) {
			if ( ! str_contains( $content, $marker ) ) { $violations[] = "includes/class-spdb-review-calendar-validator.php: missing corrective projection validation {$marker}"; }
		}
	}
}

$schema_path = $root . '/includes/class-spdb-collections-schema.php';
if ( ! is_file( $schema_path ) ) {
	$violations[] = 'includes/class-spdb-collections-schema.php: Phase 23F schema missing';
} else {
	$schema = file_get_contents( $schema_path );
	if ( false === $schema ) { $violations[] = 'Unable to read Phase 23F schema'; }
	else {
		foreach ( array( 'spdb_collections', 'spdb_collection_items', 'spdb_knowledge_links', 'dbDelta', 'table_exists', 'reference_hash', 'relation_hash', 'actor_idempotency', 'owner_user_id', 'version' ) as $marker ) {
			if ( ! str_contains( $schema, $marker ) ) { $violations[] = "Phase 23F schema missing {$marker}"; }
		}
		if ( 3 !== substr_count( $schema, 'CREATE TABLE' ) ) { $violations[] = 'Phase 23F must own exactly three metadata tables'; }
		if ( preg_match( '/\b(?:content_body|post_content|patient_id|prescription|diagnosis|phone|email|cnic|passport|media_binary|raw_analytics|native_destination|final_report_url|results_summary|progress)\b/i', $schema ) ) {
			$violations[] = 'Phase 23F schema contains a native, sensitive, destination, or parallel-results column';
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
		foreach ( array( 'validate_collection', 'validate_knowledge_link', 'mutation_authority', 'spdb_manage_campaigns', 'spdb_manage_own_content', 'text_length', 'scope', 'idempotency_key', 'audit_reason', 'spdb_campaign_ethics_invalid', 'spdb_knowledge_self_link_invalid' ) as $marker ) {
			if ( ! str_contains( $policy, $marker ) ) { $violations[] = "Phase 23F policy missing {$marker}"; }
		}
	}
}

$collections_service = $root . '/includes/class-spdb-collections-service.php';
if ( ! is_file( $collections_service ) ) {
	$violations[] = 'includes/class-spdb-collections-service.php: Phase 23F runtime service missing';
} else {
	$content = file_get_contents( $collections_service );
	if ( false === $content ) { $violations[] = 'Unable to read Phase 23F runtime service'; }
	else {
		foreach ( array( 'SPDB_PHASE23F_WRITES_ENABLED', 'resolve_reference', 'validate_contributors', 'idempotency_hash', 'relation_hash', 'repository_available', 'resolver_available' ) as $marker ) {
			if ( ! str_contains( $content, $marker ) ) { $violations[] = "Phase 23F runtime service missing {$marker}"; }
		}
		if ( preg_match( '/register_rest_route\s*\(/i', $content ) ) { $violations[] = 'Phase 23F runtime service must not expose mutation REST routes'; }
	}
}

foreach ( array( 'workspace.php', 'review.php', 'calendar.php' ) as $template_name ) {
	$template = $root . '/templates/' . $template_name;
	if ( ! is_file( $template ) ) { continue; }
	$content = file_get_contents( $template );
	if ( false === $content ) { $violations[] = "Unable to read templates/{$template_name}"; continue; }
	if ( preg_match( '/<form\b|<button\b/i', $content ) ) { $violations[] = "templates/{$template_name}: direct native mutation form or button detected"; }
	if ( in_array( $template_name, array( 'review.php', 'calendar.php' ), true ) && ! str_contains( $content, 'Accessible validated window' ) ) { $violations[] = "templates/{$template_name}: truthful accessible-window total missing"; }
	if ( 'workspace.php' === $template_name && preg_match( '/\$profile\[(?:\'|\")edit_destination(?:\'|\")\]|\$profile\[(?:\'|\")public_destination(?:\'|\")\]|\$knowledge\[(?:\'|\")destination(?:\'|\")\]/', $content ) ) { $violations[] = 'templates/workspace.php: direct profile or knowledge destination bypasses the action gate'; }
}

$workspace_css = $root . '/assets/css/workspace.css';
if ( is_file( $workspace_css ) ) {
	$content = file_get_contents( $workspace_css );
	if ( false !== $content && preg_match( '/content\s*:\s*["\']/', $content ) ) { $violations[] = 'assets/css/workspace.css: user-facing generated text must not be hard-coded in CSS'; }
}
$review_css = $root . '/assets/css/review-calendar.css';
if ( is_file( $review_css ) ) {
	$content = file_get_contents( $review_css );
	if ( false === $content || ! str_contains( $content, '.spdb-pagination' ) || ! str_contains( $content, ':focus-visible' ) ) { $violations[] = 'assets/css/review-calendar.css: corrective pagination/focus controls missing'; }
}

if ( $violations ) {
	fwrite( STDERR, "Architecture guard failed:\n- " . implode( "\n- ", $violations ) . "\n" );
	exit( 1 );
}
echo "File 23 architectural boundaries passed.\n";
