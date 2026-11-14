<?php
/**
 * V12.3 WP-07/10: never confuse unavailable providers or failed cached SQL
 * with a healthy, empty live-provider response.
 *
 * Source-only dynamic regression; it does not certify any Hostinger runtime.
 */
require_once __DIR__ . '/bootstrap.php';

final class SPDB_V123_Truth_DB {
    public string $prefix = 'wp_';
    public bool $fail_reads = false;
    public function prepare( string $sql, ...$args ): string { return $sql; }
    public function get_results( string $sql, $output = null ) {
        return $this->fail_reads ? null : array();
    }
}

final class SPDB_V123_Failed_Source implements SPDB_Provider_Adapter, SPDB_Operational_Projection_Provider {
    public function get_provider_key(): string { return 'failed_source'; }
    public function get_provider_name(): string { return 'Synthetic Native Source'; }
    public function get_provider_version(): string { return '1.0.0'; }
    public function get_minimum_contract_version(): string { return '2.0.0'; }
    public function get_maximum_contract_version(): string { return '2.9.9'; }
    public function get_declared_capability_state(): string { return SPDB_Adapter_Registry::CAPABILITY_READ_ONLY; }
    public function get_object_types(): array { return array( 'source_record' ); }
    public function get_privacy_classifications(): array { return array( 'public_content' ); }
    public function get_supported_capabilities(): array { return array( 'spdb_view_own_content' ); }
    public function get_operation_definitions(): array { return array(); }
    public function health_check(): array { return array( 'healthy' => false ); }
    public function list_items( array $query ) { return array( 'items' => array(), 'total' => 0 ); }
    public function get_item( string $object_type, string $object_id ) { return new WP_Error( 'provider_unavailable', 'Unavailable' ); }
    public function get_allowed_operations( string $object_type, string $object_id ): array { return array(); }
    public function execute_operation( string $operation_key, string $object_type, string $object_id, array $payload ) { return new WP_Error( 'provider_unavailable', 'No writes' ); }
    public function get_operational_projection_domains(): array { return array( 'sources' ); }
    public function list_operational_projections( string $domain, array $query ) { return new WP_Error( 'source_read_unavailable', 'Private native failure text must not leak.' ); }
}

$checks = 0;
$failed = 0;
$check = static function ( bool $valid, string $message ) use ( &$checks, &$failed ): void {
    ++$checks;
    if ( ! $valid ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};

$GLOBALS['spdb_test_capabilities']['spdb_view_own_content'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_view_own_analytics'] = true;
$database = new SPDB_V123_Truth_DB();
$GLOBALS['wpdb'] = $database;
$repository = new SPDB_Operations_Repository();

$empty = new SPDB_Operations_Service( new SPDB_Adapter_Registry(), $repository );
$database->fail_reads = true;
$response = $empty->analytics( array( 'scope' => 'own' ) );
$check( is_wp_error( $response ), 'Failed SQL cached-analytics read must never claim live-provider success.' );
$check( is_wp_error( $response ) && 'spdb_metric_read_failed' === $response->get_error_code(), 'Return the typed bounded repository read error.' );

$database->fail_reads = false;
$response = $empty->analytics( array( 'scope' => 'own' ) );
$check( is_array( $response ) && array() === ( $response['metrics'] ?? null ), 'No providers + healthy empty cache: zero metrics.' );
$check( is_array( $response ) && 'no_compatible_provider' === ( $response['source'] ?? '' ), 'Healthy empty cache is not live_provider.' );

$registry = new SPDB_Adapter_Registry();
$registration = $registry->register( new SPDB_V123_Failed_Source() );
$check( true === $registration, 'Failing synthetic native source registers as a readable adapter.' );
$service = new SPDB_Operations_Service( $registry, $repository );
$projection = $service->projections( 'sources' );
$check( is_array( $projection ) && 'source_read_unavailable' === ( $projection['provider_errors']['failed_source'] ?? '' ), 'Native source typed failure preserved without error message.' );
$check( is_array( $projection ) && 'provider_unavailable' === ( $projection['empty_reason'] ?? '' ), 'Known failing provider cannot be falsely reported as absent.' );
$check( is_array( $projection ) && false === ( $projection['data_complete'] ?? null ), 'Failed native projection never claims complete data.' );

if ( $failed ) { fwrite( STDERR, "{$failed} / {$checks} V12.3 provider truth checks failed.\n" ); exit( 1 ); }
echo "All {$checks} V12.3 provider truth checks passed; Hostinger runtime remains unverified.\n";
