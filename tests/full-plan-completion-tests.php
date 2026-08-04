<?php
/** Static and behavioral plan-to-code completion gate for File 23 v3.0. */
$root = dirname( __DIR__ );
$tests = 0;
$failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $condition ) {
		++$failed;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
};
$read = static function ( string $path ) use ( $root ): string {
	$content = file_get_contents( $root . '/' . $path );
	return is_string( $content ) ? $content : '';
};

$plugin  = $read( 'includes/class-spdb-plugin.php' );
$router  = $read( 'includes/class-spdb-dashboard-router.php' );
$schema  = $read( 'includes/class-spdb-operations-schema.php' );
$caps    = $read( 'includes/class-spdb-capabilities.php' );
$state   = $read( 'includes/class-spdb-system-state.php' );
$rest    = $read( 'includes/class-spdb-operations-rest-controller.php' );
$jobs    = $read( 'includes/class-spdb-background-jobs.php' );
$export  = $read( 'includes/class-spdb-export-service.php' );
$privacy = $read( 'includes/class-spdb-privacy-integration.php' );
$repair  = $read( 'includes/class-spdb-local-repair.php' );
$legacy  = $read( 'includes/class-spdb-legacy-migration-diagnostics.php' );
$guard   = $read( 'includes/class-spdb-operational-mutation-guard.php' );
$client  = $read( 'assets/js/operations.js' );
$settings = $read( 'templates/settings.php' );
$main    = $read( 'sabri-publishing-dashboard.php' );
$readme  = $read( 'readme.txt' );

$assert( str_contains( $main, "Version:     1.1.0" ) && str_contains( $main, "SPDB_VERSION', '1.1.0" ), 'Plugin release identity must be 1.1.0.' );
$assert( str_contains( $readme, 'Stable tag: 1.1.0' ), 'Readme stable tag must match 1.1.0.' );
$assert( str_contains( $main, 'class-spdb-operational-mutation-guard.php' ) && str_contains( $main, 'SPDB_Operational_Mutation_Guard::register' ), 'Operational mutation guard must be loaded and registered before runtime boot.' );

$views = array( 'overview', 'create', 'workspace', 'inventory', 'review', 'calendar', 'collections', 'knowledge', 'sources', 'media', 'interactions', 'revisions', 'analytics', 'notifications', 'tasks', 'reports', 'settings', 'saved-views', 'system-status' );
foreach ( $views as $view ) {
	$assert( str_contains( $router, "'{$view}'" ), "Dashboard router must expose the {$view} view." );
}

$classes = array(
	'class-spdb-operational-projection-validator.php', 'class-spdb-operations-schema.php',
	'class-spdb-operations-repository.php', 'class-spdb-operations-service.php',
	'class-spdb-governance-service.php', 'class-spdb-export-service.php',
	'class-spdb-automation-engine.php', 'class-spdb-background-jobs.php',
	'class-spdb-privacy-integration.php', 'class-spdb-local-repair.php',
	'class-spdb-activation-wizard.php', 'class-spdb-operations-rest-controller.php',
	'class-spdb-module-manifest.php', 'class-spdb-legacy-migration-diagnostics.php',
);
foreach ( $classes as $class ) {
	$assert( str_contains( $plugin, $class ), "Plugin composition root must load {$class}." );
}

$tables = array( 'preferences', 'saved_views', 'tasks', 'delegations', 'automation_rules', 'metric_snapshots', 'export_jobs', 'adapter_health', 'background_jobs', 'dashboard_audit' );
foreach ( $tables as $table ) {
	$assert( str_contains( $schema, "'{$table}'" ), "Operations schema must include {$table}." );
}
$assert( ! preg_match( '/CREATE TABLE[^;]*(?:publication_body|message_body|patient_record|media_binary|raw_analytics)/is', $schema ), 'File 23 schema must not duplicate native content, messages, patient records, media, or raw analytics.' );
$assert( ! str_contains( $guard, 'CREATE TABLE' ), 'Mutation replay protection must not create an alternate data schema.' );

$required_caps = array( 'spdb_manage_tasks', 'spdb_manage_delegations', 'spdb_manage_automation_rules', 'spdb_view_assurance_status', 'spdb_request_ai_assistance', 'spdb_reconcile_projections', 'spdb_export_reports' );
foreach ( $required_caps as $cap ) {
	$assert( str_contains( $caps, "'{$cap}'" ), "Capability {$cap} must be registered." );
}

$routes = array( '/operations/', '/analytics', '/tasks', '/delegations', '/automation-rules', '/exports', '/ai-assistance', '/preferences', '/settings', '/legacy-migration', '/system-check', '/activation' );
foreach ( $routes as $route ) {
	$assert( str_contains( $rest, $route ), "Private REST controller must expose {$route}." );
}

$assert( str_contains( $guard, 'X-WP-Nonce' ) && str_contains( $guard, 'Origin' ) && str_contains( $guard, 'Referer' ), 'Every File 23-owned operational mutation must pass explicit nonce and same-origin enforcement.' );
$assert( str_contains( $guard, 'Idempotency-Key' ) && str_contains( $guard, 'payload_hash' ) && str_contains( $guard, 'spdb_mutation_idempotency_conflict' ), 'Operational mutations must enforce payload-bound idempotency and conflict detection.' );
$assert( str_contains( $guard, 'START TRANSACTION' ) && str_contains( $guard, 'COMMIT' ) && str_contains( $guard, 'ROLLBACK' ), 'File 23 local writes and audit evidence must use explicit transactional commit/rollback.' );
$assert( str_contains( $guard, 'SPDB_Operations_Repository' ) && str_contains( $guard, 'mutation_requested' ), 'Mutation evidence must enter the canonical hash-chained dashboard audit.' );
$assert( str_contains( $client, "'Idempotency-Key'" ) && str_contains( $client, 'data-spdb-idempotency-key' ), 'The dashboard client must preserve one idempotency key across a repeated submission.' );
$assert( str_contains( $settings, 'name="audit_reason"' ) && str_contains( $settings, 'minlength="10"' ), 'High-risk settings changes must require a meaningful audit reason.' );

$assert( str_contains( $jobs, 'dead_letter' ) && str_contains( $jobs, 'fail_or_retry_job' ) && stripos( $jobs, 'idempotent' ) !== false, 'Background jobs must implement retry, dead-letter, and idempotency boundaries.' );
$assert( str_contains( $export, 'hash_hmac' ) && str_contains( $export, 'expires_at_gmt' ) && str_contains( $export, 'spreadsheet_safe' ), 'Exports must be expiring, signed, and spreadsheet-injection resistant.' );
$assert( str_contains( $privacy, 'wp_privacy_personal_data_exporters' ) && str_contains( $privacy, 'wp_privacy_personal_data_erasers' ), 'File 23-owned data must participate in WordPress privacy export and erasure.' );
$assert( str_contains( $repair, "'global_safe_mode_owner' => 'file20'" ) && ! str_contains( $repair, 'update_option( \'file20_safe_mode' ), 'File 23 repair must preserve File 20 global Safe Mode ownership.' );
$assert( str_contains( $legacy, "'mutation_supported'         => false" ) && str_contains( $legacy, "'canonical_owner'            => 'file21'" ), 'Legacy File 04 handling must remain read-only and File 21-owned.' );
$assert( str_contains( $plugin, "do_action( 'spdb/file24_assurance_evidence'" ), 'Sanitized File 24 assurance evidence must be emitted.' );
$assert( str_contains( $state, "'production_writes'       => false" ) && str_contains( $state, "'staging_accepted'" ), 'Completion states must remain truthful and production writes fail-closed before staging.' );

$forbidden = array( 'wp_insert_post(', 'wp_update_post(', 'wp_delete_post(', 'register_post_type(' );
$services = $read( 'includes/class-spdb-operations-service.php' ) . $read( 'includes/class-spdb-governance-service.php' ) . $read( 'includes/class-spdb-export-service.php' );
foreach ( $forbidden as $call ) {
	$assert( ! str_contains( $services, $call ), "File 23-owned services must not mutate native publication data through {$call}." );
}

if ( $failed ) {
	fwrite( STDERR, "{$failed} of {$tests} full-plan completion tests failed.\n" );
	exit( 1 );
}
echo "All {$tests} full-plan completion tests passed.\n";
