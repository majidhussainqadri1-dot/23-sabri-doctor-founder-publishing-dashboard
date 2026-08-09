<?php
/**
 * Static architectural boundary guard for File 23 production PHP.
 *
 * File 23 may own bounded dashboard-operational metadata only. The guard is
 * deliberately schema-aware: comments and approved pointer-table names must not
 * be mistaken for forbidden native storage, while actual forbidden columns or
 * mutation APIs still fail the build.
 */

$root       = dirname( __DIR__ );
$iterator   = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
$violations = array();

$global_patterns = array(
	'generic action endpoint'           => '/(?:POST\s+\/spdb\/v1\/action|register_rest_route\s*\([^;]*["\']\/action["\'])/i',
	'native post-type ownership'        => '/\bregister_post_type\s*\(/i',
	'direct native post insertion'      => '/\bwp_insert_post\s*\(/i',
	'direct native post mutation'       => '/\bwp_update_post\s*\(/i',
	'direct native post deletion'       => '/\bwp_delete_post\s*\(/i',
	'direct native attachment deletion' => '/\bwp_delete_attachment\s*\(/i',
	'direct native WordPress tables'    => '/\$wpdb\s*->\s*(posts|postmeta|comments|commentmeta)\b/i',
	'legacy provider maturity API'      => '/\bget_maturity_state\s*\(/i',
	'caller-supplied environment gate'  => '/\bcan_write\s*\([^)]*is_production/i',
);

$schema_files = array(
	'includes/class-spdb-collections-schema.php',
	'includes/class-spdb-operations-schema.php',
	'includes/class-spdb-rest-rate-limiter.php',
);
$broker_execution_files = array(
	'includes/class-spdb-operation-broker.php',
	'includes/class-spdb-review-calendar-rest-controller.php',
);

/** Extract only SQL CREATE TABLE string fragments, excluding comments/prose. @return string[] */
$schema_fragments = static function ( string $content ): array {
	$matches = array();
	preg_match_all( '/CREATE\s+TABLE\s+.*?\)\s*ENGINE\s*=\s*InnoDB[^;]*;/is', $content, $matches );
	return isset( $matches[0] ) && is_array( $matches[0] ) ? $matches[0] : array();
};

foreach ( $iterator as $file ) {
	if ( ! $file->isFile() || 'php' !== strtolower( $file->getExtension() ) ) { continue; }
	$relative = str_replace( DIRECTORY_SEPARATOR, '/', ltrim( str_replace( $root, '', $file->getPathname() ), DIRECTORY_SEPARATOR ) );
	if ( str_starts_with( $relative, 'tests/' ) || str_starts_with( $relative, 'vendor/' ) ) { continue; }
	$content = file_get_contents( $file->getPathname() );
	if ( false === $content ) { $violations[] = "Unable to read {$relative}"; continue; }

	foreach ( $global_patterns as $label => $pattern ) {
		if ( preg_match( $pattern, $content ) ) { $violations[] = "{$relative}: {$label}"; }
	}
	if ( ! in_array( $relative, $broker_execution_files, true ) && preg_match( '/->\s*execute_operation\s*\(/i', $content ) ) {
		$violations[] = "{$relative}: provider mutation bypasses the operation broker";
	}
	if ( ! in_array( $relative, $schema_files, true ) && preg_match( '/CREATE\s+TABLE/i', $content ) ) {
		$violations[] = "{$relative}: unauthorized table ownership";
	}

	if ( in_array( $relative, $schema_files, true ) ) {
		foreach ( $schema_fragments( $content ) as $sql ) {
			if ( preg_match( '/\b(?:post_content|publication_body|review_body|comment_body|message_body|source_body|media_binary|notification_delivery|patient_id|patient_name|diagnosis|prescription|remedy|potency|identity_document|passport|national_id|payment_secret|card_number|cvv|raw_analytics_event)\b/i', $sql ) ) {
				$violations[] = "{$relative}: forbidden native/sensitive schema column";
			}
		}
	}
}

/** @param string[] $markers */
$require_markers = static function ( string $relative, array $markers, string $label = 'required boundary' ) use ( $root, &$violations ): string {
	$path = $root . '/' . $relative;
	if ( ! is_file( $path ) ) { $violations[] = "{$relative}: required file missing"; return ''; }
	$content = file_get_contents( $path );
	if ( false === $content ) { $violations[] = "Unable to read {$relative}"; return ''; }
	foreach ( $markers as $marker ) {
		if ( ! str_contains( $content, $marker ) ) { $violations[] = "{$relative}: missing {$label} {$marker}"; }
	}
	return $content;
};

$inventory = $require_markers( 'includes/class-spdb-inventory-rest-controller.php', array( 'WP_REST_Server::READABLE', 'permission_callback' ), 'inventory control' );
if ( '' !== $inventory && ( preg_match( '/WP_REST_Server::(?:CREATABLE|EDITABLE|DELETABLE)/', $inventory ) || preg_match( '/\b(?:POST|PUT|PATCH|DELETE)\b/i', $inventory ) ) ) {
	$violations[] = 'includes/class-spdb-inventory-rest-controller.php: inventory endpoints must remain read-only';
}

$inventory_service = $require_markers( 'includes/class-spdb-federated-inventory.php', array( 'execution_exposed', 'false' ), 'inventory boundary' );
foreach ( array( 'execute_operation', 'wp_insert_post', 'wp_update_post', 'wp_delete_post' ) as $call ) {
	if ( '' !== $inventory_service && preg_match( '/\b' . preg_quote( $call, '/' ) . '\s*\(/', $inventory_service ) ) { $violations[] = "includes/class-spdb-federated-inventory.php: forbidden mutation call {$call}"; }
}

$workspace = $require_markers( 'includes/class-spdb-role-workspace-service.php', array( 'derive_context', 'action_contract', 'is_environment_write_eligible', 'MAX_PROVIDERS', 'MAX_ACTIONS', 'gmdate' ), 'workspace gate' );
foreach ( array( 'execute_operation', 'wp_insert_post', 'wp_update_post', 'wp_delete_post', 'update_user_meta', 'update_option' ) as $call ) {
	if ( '' !== $workspace && preg_match( '/\b' . preg_quote( $call, '/' ) . '\s*\(/', $workspace ) ) { $violations[] = "includes/class-spdb-role-workspace-service.php: forbidden mutation call {$call}"; }
}

$review = $require_markers( 'includes/class-spdb-review-calendar-service.php', array( 'SPDB_Review_Calendar_Provider_Adapter', 'authorize_operation', 'reviewer_target_is_eligible', 'operation_contract', 'get_allowed_operations', 'is_environment_write_eligible', 'provider_query', 'accessible_total', 'MAX_PROVIDERS', 'MAX_ITEMS' ), 'review/calendar gate' );
foreach ( array( 'execute_operation', 'wp_insert_post', 'wp_update_post', 'wp_delete_post', 'update_post_meta', 'update_option' ) as $call ) {
	if ( '' !== $review && preg_match( '/\b' . preg_quote( $call, '/' ) . '\s*\(/', $review ) ) { $violations[] = "includes/class-spdb-review-calendar-service.php: forbidden native mutation call {$call}"; }
}

$require_markers( 'includes/class-spdb-review-calendar-rest-controller.php', array( '/approve', '/request-changes', '/reject', '/assign-reviewer', '/schedule', '/reschedule', '/unschedule', 'valid_rest_nonce', 'authorize_operation', 'spdb_operation_payload_field_invalid', 'object_version', 'idempotency_key', 'audit_reason', 'spdb_native_confirmation_invalid', 'native_refetched', '$this->broker->execute' ), 'explicit operation control' );
$require_markers( 'includes/class-spdb-workspace-projection-validator.php', array( 'action_contract', 'spdb_workspace_action_contract_mismatch', 'supported_capabilities', 'profile_timestamp_invalid', 'knowledge_timestamp_invalid' ), 'workspace semantic validation' );
$require_markers( 'includes/class-spdb-review-calendar-validator.php', array( 'normalize_query', 'operation_contract', 'MAX_REPORTED_TOTAL', 'normalize_utc_timestamp', 'strict_nonnegative_integer', 'required_text', 'normalize_flags', 'separation_required', 'native_timezone', 'native_version', 'last_synced_at', 'assigned_reviewer_id' ), 'review projection validation' );

$collections_schema = $require_markers( 'includes/class-spdb-collections-schema.php', array( 'spdb_collections', 'spdb_collection_items', 'spdb_knowledge_links', 'dbDelta', 'table_exists', 'reference_hash', 'relation_hash', 'actor_idempotency', 'owner_user_id', 'version' ), 'Collections schema marker' );
if ( '' !== $collections_schema ) {
	if ( 3 !== substr_count( $collections_schema, 'CREATE TABLE' ) ) { $violations[] = 'Collections must own exactly three bounded metadata tables'; }
	foreach ( $schema_fragments( $collections_schema ) as $sql ) {
		if ( preg_match( '/\b(?:content_body|post_content|patient_id|prescription|diagnosis|phone|email|cnic|passport|media_binary|raw_analytics|native_destination|final_report_url|results_summary|progress)\b/i', $sql ) ) { $violations[] = 'Collections schema contains native, sensitive, destination, or parallel-results data'; }
	}
}

$operations_schema = $require_markers( 'includes/class-spdb-operations-schema.php', array( 'spdb_preferences', 'spdb_saved_views', 'spdb_tasks', 'spdb_delegations', 'spdb_automation_rules', 'spdb_metric_snapshots', 'spdb_export_jobs', 'spdb_adapter_health', 'spdb_background_jobs', 'spdb_dashboard_audit', 'dbDelta' ), 'operational schema marker' );
if ( '' !== $operations_schema ) {
	if ( 10 !== substr_count( $operations_schema, 'CREATE TABLE' ) ) { $violations[] = 'Operational schema must own exactly ten bounded metadata tables'; }
	foreach ( $schema_fragments( $operations_schema ) as $sql ) {
		if ( preg_match( '/\b(?:publication_body|post_content|patient_id|prescription|diagnosis|message_body|notification_delivery|source_body|media_binary|raw_analytics_event|identity_document|payment_secret)\b/i', $sql ) ) { $violations[] = 'Operational schema contains a native-domain or sensitive data column'; }
	}
}

$rate_schema = $require_markers( 'includes/class-spdb-rest-rate-limiter.php', array( 'spdb_rest_rate_limits', 'CREATE TABLE', 'ENGINE=InnoDB', 'ON DUPLICATE KEY UPDATE', 'rest_pre_dispatch', 'spdb_rate_limit_exceeded', "'REMOTE_ADDR'" ), 'REST rate-limit boundary' );
if ( '' !== $rate_schema ) {
	if ( 1 !== substr_count( $rate_schema, 'CREATE TABLE' ) ) { $violations[] = 'REST rate limiter must own exactly one bounded counter table'; }
	if ( str_contains( $rate_schema, 'HTTP_X_FORWARDED_FOR' ) ) { $violations[] = 'REST rate limiter must not trust forwarding headers for anonymous actor identity'; }
	foreach ( $schema_fragments( $rate_schema ) as $sql ) {
		if ( preg_match( '/\b(?:raw_ip|ip_address|request_body|query_string|cookie|nonce_value|auth_token|patient_id|message_body)\b/i', $sql ) ) { $violations[] = 'REST rate-limit schema contains unapproved request or sensitive data storage'; }
	}
}

$require_markers( 'includes/class-spdb-collections-policy.php', array( 'validate_collection', 'validate_knowledge_link', 'mutation_authority', 'spdb_manage_campaigns', 'spdb_manage_own_content', 'text_length', 'scope', 'idempotency_key', 'audit_reason', 'spdb_campaign_ethics_invalid', 'spdb_knowledge_self_link_invalid' ), 'Collections policy marker' );
$collections_service = $require_markers( 'includes/class-spdb-collections-service.php', array( 'SPDB_PHASE23F_WRITES_ENABLED', 'resolve_reference', 'validate_contributors', 'idempotency_hash', 'relation_hash', 'SPDB_Collections_Service_Readiness_Consumer', 'require_read_ready', 'require_write_ready' ), 'Collections runtime marker' );
if ( '' !== $collections_service && preg_match( '/register_rest_route\s*\(/i', $collections_service ) ) { $violations[] = 'Collections service must not expose mutation REST routes directly'; }

$require_markers( 'includes/class-spdb-plugin.php', array( 'class-spdb-operations-service.php', 'class-spdb-background-jobs.php', 'class-spdb-privacy-integration.php', 'class-spdb-module-manifest.php', 'spdb/file24_assurance_evidence', 'SPDB_Operations_REST_Controller', 'SPDB_Activation_Wizard' ), 'full-plan composition marker' );
$require_markers( 'includes/class-spdb-local-repair.php', array( "global_safe_mode_owner' => 'file20", 'local_repair', 'install_rate_limit_schema' ), 'local repair boundary' );
$require_markers( 'includes/class-spdb-activation-wizard.php', array( 'source_commit', 'package_sha256', 'role_matrix_evidence', 'provider_contract_evidence', 'cache_privacy_evidence', 'accessibility_evidence', 'backup_restore_evidence', 'rollback_evidence', 'acceptance_version_valid', 'rate_limit_schema' ), 'staging acceptance evidence' );
$require_markers( 'includes/class-spdb-module-manifest.php', array( "'00'", "'01-a'", "'01-b'", "'02'", "'03'", "'04'", "'05'", "'06'", "'07'", "'08'", "'09'", "'10'", "'11'", "'12'", "'13'", "'14'", "'15'", "'16'", "'17'", "'18'", "'19'", "'20'", "'21'", "'22'", "'23'", "'24'", "'25'" ), 'module manifest entry' );

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
	fwrite( STDERR, "Architecture guard failed:\n- " . implode( "\n- ", array_values( array_unique( $violations ) ) . "\n" ) );
	exit( 1 );
}

echo "File 23 architectural boundaries passed.\n";
