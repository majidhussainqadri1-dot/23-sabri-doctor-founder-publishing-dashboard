<?php
/** Static architectural boundary guard for File 23 production PHP. */
$root = dirname( __DIR__ );
$violations = array();
$schema_files = array(
	'includes/class-spdb-collections-schema.php',
	'includes/class-spdb-operations-schema.php',
	'includes/class-spdb-rest-rate-limiter.php',
);
$broker_files = array(
	'includes/class-spdb-operation-broker.php',
	'includes/class-spdb-review-calendar-rest-controller.php',
);
$global_patterns = array(
	'generic action endpoint' => '/(?:POST\s+\/spdb\/v1\/action|register_rest_route\s*\([^;]*["\']\/action["\'])/i',
	'native post-type ownership' => '/\bregister_post_type\s*\(/i',
	'direct native post insertion' => '/\bwp_insert_post\s*\(/i',
	'direct native post mutation' => '/\bwp_update_post\s*\(/i',
	'direct native post deletion' => '/\bwp_delete_post\s*\(/i',
	'direct native attachment deletion' => '/\bwp_delete_attachment\s*\(/i',
	'direct native WordPress tables' => '/\$wpdb\s*->\s*(posts|postmeta|comments|commentmeta)\b/i',
	'legacy provider maturity API' => '/\bget_maturity_state\s*\(/i',
	'caller-supplied environment gate' => '/\bcan_write\s*\([^)]*is_production/i',
);
/** @return string[] */
$schema_fragments = static function ( string $content ): array {
	$matches = array();
	preg_match_all( '/CREATE\s+TABLE\s+.*?\)\s*ENGINE\s*=\s*InnoDB[^;]*;/is', $content, $matches );
	return isset( $matches[0] ) && is_array( $matches[0] ) ? $matches[0] : array();
};
$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
foreach ( $iterator as $file ) {
	if ( ! $file->isFile() || 'php' !== strtolower( $file->getExtension() ) ) { continue; }
	$relative = str_replace( DIRECTORY_SEPARATOR, '/', ltrim( str_replace( $root, '', $file->getPathname() ), DIRECTORY_SEPARATOR ) );
	if ( str_starts_with( $relative, 'tests/' ) || str_starts_with( $relative, 'vendor/' ) ) { continue; }
	$content = file_get_contents( $file->getPathname() );
	if ( false === $content ) { $violations[] = "Unable to read {$relative}"; continue; }
	foreach ( $global_patterns as $label => $pattern ) {
		if ( preg_match( $pattern, $content ) ) { $violations[] = "{$relative}: {$label}"; }
	}
	if ( ! in_array( $relative, $broker_files, true ) && preg_match( '/->\s*execute_operation\s*\(/i', $content ) ) {
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
$require = static function ( string $relative, array $markers, string $label ) use ( $root, &$violations ): string {
	$path = $root . '/' . $relative;
	if ( ! is_file( $path ) ) { $violations[] = "{$relative}: required file missing"; return ''; }
	$content = file_get_contents( $path );
	if ( false === $content ) { $violations[] = "Unable to read {$relative}"; return ''; }
	foreach ( $markers as $marker ) {
		if ( ! str_contains( $content, $marker ) ) { $violations[] = "{$relative}: missing {$label} {$marker}"; }
	}
	return $content;
};

$inventory = $require( 'includes/class-spdb-inventory-rest-controller.php', array( 'WP_REST_Server::READABLE', 'permission_callback' ), 'inventory control' );
if ( '' !== $inventory && ( preg_match( '/WP_REST_Server::(?:CREATABLE|EDITABLE|DELETABLE)/', $inventory ) || preg_match( '/\b(?:POST|PUT|PATCH|DELETE)\b/i', $inventory ) ) ) {
	$violations[] = 'includes/class-spdb-inventory-rest-controller.php: inventory endpoints must remain read-only';
}
$inventory_service = $require( 'includes/class-spdb-federated-inventory.php', array( 'execution_exposed', 'false' ), 'inventory boundary' );
foreach ( array( 'execute_operation', 'wp_insert_post', 'wp_update_post', 'wp_delete_post' ) as $call ) {
	if ( '' !== $inventory_service && preg_match( '/\b' . preg_quote( $call, '/' ) . '\s*\(/', $inventory_service ) ) { $violations[] = "includes/class-spdb-federated-inventory.php: forbidden mutation {$call}"; }
}
$workspace = $require( 'includes/class-spdb-role-workspace-service.php', array( 'derive_context', 'action_contract', 'is_environment_write_eligible', 'MAX_PROVIDERS', 'MAX_ACTIONS', 'gmdate' ), 'workspace gate' );
foreach ( array( 'execute_operation', 'wp_insert_post', 'wp_update_post', 'wp_delete_post', 'update_user_meta', 'update_option' ) as $call ) {
	if ( '' !== $workspace && preg_match( '/\b' . preg_quote( $call, '/' ) . '\s*\(/', $workspace ) ) { $violations[] = "includes/class-spdb-role-workspace-service.php: forbidden mutation {$call}"; }
}
$review = $require( 'includes/class-spdb-review-calendar-service.php', array( 'SPDB_Review_Calendar_Provider_Adapter', 'authorize_operation', 'reviewer_target_is_eligible', 'operation_contract', 'get_allowed_operations', 'is_environment_write_eligible', 'provider_query', 'accessible_total', 'MAX_PROVIDERS', 'MAX_ITEMS' ), 'review/calendar gate' );
foreach ( array( 'execute_operation', 'wp_insert_post', 'wp_update_post', 'wp_delete_post', 'update_post_meta', 'update_option' ) as $call ) {
	if ( '' !== $review && preg_match( '/\b' . preg_quote( $call, '/' ) . '\s*\(/', $review ) ) { $violations[] = "includes/class-spdb-review-calendar-service.php: forbidden native mutation {$call}"; }
}
$require( 'includes/class-spdb-review-calendar-rest-controller.php', array( '/approve', '/request-changes', '/reject', '/assign-reviewer', '/schedule', '/reschedule', '/unschedule', 'valid_rest_nonce', 'authorize_operation', 'object_version', 'idempotency_key', 'audit_reason', '$this->broker->execute' ), 'operation control' );
$require( 'includes/class-spdb-workspace-projection-validator.php', array( 'action_contract', 'spdb_workspace_action_contract_mismatch', 'supported_capabilities', 'profile_timestamp_invalid', 'knowledge_timestamp_invalid' ), 'workspace validation' );
$require( 'includes/class-spdb-review-calendar-validator.php', array( 'normalize_query', 'operation_contract', 'MAX_REPORTED_TOTAL', 'normalize_utc_timestamp', 'separation_required', 'native_timezone', 'native_version', 'assigned_reviewer_id' ), 'review validation' );

$collections = $require( 'includes/class-spdb-collections-schema.php', array( 'spdb_collections', 'spdb_collection_items', 'spdb_knowledge_links', 'dbDelta', 'reference_hash', 'relation_hash', 'actor_idempotency' ), 'collection schema' );
if ( '' !== $collections && 3 !== substr_count( $collections, 'CREATE TABLE' ) ) { $violations[] = 'Collections must own exactly three bounded metadata tables'; }
$operations = $require( 'includes/class-spdb-operations-schema.php', array( 'spdb_preferences', 'spdb_saved_views', 'spdb_tasks', 'spdb_delegations', 'spdb_automation_rules', 'spdb_metric_snapshots', 'spdb_export_jobs', 'spdb_adapter_health', 'spdb_background_jobs', 'spdb_dashboard_audit', 'dbDelta' ), 'operational schema' );
if ( '' !== $operations && 10 !== substr_count( $operations, 'CREATE TABLE' ) ) { $violations[] = 'Operational schema must own exactly ten bounded metadata tables'; }
$rate = $require( 'includes/class-spdb-rest-rate-limiter.php', array( 'spdb_rest_rate_limits', 'CREATE TABLE', 'ENGINE=InnoDB', 'ON DUPLICATE KEY UPDATE', 'rest_pre_dispatch', 'spdb_rate_limit_exceeded', "'REMOTE_ADDR'" ), 'REST rate-limit boundary' );
if ( '' !== $rate ) {
	if ( 1 !== substr_count( $rate, 'CREATE TABLE' ) ) { $violations[] = 'REST rate limiter must own exactly one bounded counter table'; }
	if ( str_contains( $rate, 'HTTP_X_FORWARDED_FOR' ) ) { $violations[] = 'REST rate limiter must not trust forwarding headers'; }
}

$require( 'includes/class-spdb-collections-policy.php', array( 'validate_collection', 'validate_knowledge_link', 'mutation_authority', 'spdb_manage_campaigns', 'spdb_manage_own_content', 'idempotency_key', 'audit_reason' ), 'collection policy' );
$collections_service = $require( 'includes/class-spdb-collections-service.php', array( 'SPDB_PHASE23F_WRITES_ENABLED', 'resolve_reference', 'validate_contributors', 'idempotency_hash', 'relation_hash', 'require_read_ready', 'require_write_ready' ), 'collection runtime' );
if ( '' !== $collections_service && preg_match( '/register_rest_route\s*\(/i', $collections_service ) ) { $violations[] = 'Collections service must not expose REST routes directly'; }
$require( 'includes/class-spdb-plugin.php', array( 'class-spdb-operations-service.php', 'class-spdb-background-jobs.php', 'class-spdb-privacy-integration.php', 'class-spdb-module-manifest.php', 'spdb/file24_assurance_evidence', 'SPDB_Operations_REST_Controller', 'SPDB_Activation_Wizard' ), 'composition' );
$require( 'includes/class-spdb-local-repair.php', array( "global_safe_mode_owner' => 'file20", 'local_repair', 'install_rate_limit_schema' ), 'local repair boundary' );
$require( 'includes/class-spdb-activation-wizard.php', array( 'source_commit', 'package_sha256', 'cache_privacy_evidence', 'accessibility_evidence', 'backup_restore_evidence', 'rollback_evidence', 'rate_limit_schema' ), 'activation evidence' );
$require( 'includes/class-spdb-module-manifest.php', array( "'00'", "'01-a'", "'01-b'", "'04'", "'16'", "'19'", "'20'", "'21'", "'22'", "'23'", "'24'", "'25'" ), 'module ownership' );

foreach ( array( 'workspace.php', 'review.php', 'calendar.php' ) as $name ) {
	$path = $root . '/templates/' . $name;
	if ( ! is_file( $path ) ) { continue; }
	$content = file_get_contents( $path );
	if ( false === $content ) { $violations[] = "Unable to read templates/{$name}"; continue; }
	if ( preg_match( '/<form\b|<button\b/i', $content ) ) { $violations[] = "templates/{$name}: direct native mutation control detected"; }
	if ( in_array( $name, array( 'review.php', 'calendar.php' ), true ) && ! str_contains( $content, 'Accessible validated window' ) ) { $violations[] = "templates/{$name}: truthful accessible-window total missing"; }
}
foreach ( array( 'assets/css/workspace.css', 'assets/css/review-calendar.css' ) as $relative ) {
	$path = $root . '/' . $relative;
	if ( ! is_file( $path ) ) { $violations[] = "{$relative}: required style missing"; }
}
if ( $violations ) {
	fwrite( STDERR, "Architecture guard failed:\n- " . implode( "\n- ", array_values( array_unique( $violations ) ) ) . "\n" );
	exit( 1 );
}
echo "File 23 architectural boundaries passed.\n";
