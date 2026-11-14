<?php
/**
 * V12.3 WP-07/10: never confuse unavailable providers or failed cached SQL
 * with a healthy, empty live-provider response.
 *
 * Source-only dynamic regression; it does not certify any Hostinger runtime.
 */
define( 'SMC_VERSION', '1.0.1' );
function smc_user_status( int $id ): string { return 'approved'; }
function smc_is_founder( int $id ): bool { return 7 === $id; }
function smc_is_trusted_publisher( int $id ): bool { return false; }
function get_locale(): string { return 'en_US'; }
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
    public static bool $provide_page = false;
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
    public function list_operational_projections( string $domain, array $query ) {
        if ( self::$provide_page ) {
            return array( 'items' => array( array(
                'object_type' => 'source_record', 'object_id' => 'src-1', 'native_version' => 'v1',
                'title' => 'Source', 'status' => 'verified', 'updated_at' => '2026-11-14T00:00:00Z',
                'privacy_class' => 'restricted'
            ) ), 'total' => 2, 'has_more' => true, 'generated_at' => '2026-11-14T00:00:00Z' );
        }
        return new WP_Error( 'source_read_unavailable', 'Private native failure text must not leak.' );
    }
}

$checks = 0;
$failed = 0;
$check = static function ( bool $valid, string $message ) use ( &$checks, &$failed ): void {
    ++$checks;
    if ( ! $valid ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};

$GLOBALS['spdb_test_capabilities']['spdb_view_own_content'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_view_own_analytics'] = true;
$GLOBALS['spdb_test_capabilities']['spdb_export_reports'] = true;
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

$partial_from_error = $service->report( 'source_completeness', 'own' );
$check( is_array( $partial_from_error ) && 'partial' === ( $partial_from_error['completeness'] ?? '' ), 'Report with provider error must explicitly disclose partial completeness.' );
$check( is_array( $partial_from_error ) && false !== stripos( (string) ( $partial_from_error['limitations'] ?? '' ), 'incomplete' ), 'Incomplete report must carry a human-readable limitation.' );

SPDB_V123_Failed_Source::$provide_page = true;
$more = $service->report( 'source_completeness', 'own' );
$check( is_array( $more ) && 1 === count( $more['rows'] ?? array() ), 'Partial native page still exports its one allowed row.' );
$check( is_array( $more ) && 'partial' === ( $more['completeness'] ?? '' ), 'Paged report cannot silently claim full coverage.' );
$check( is_array( $more ) && true === ( $more['more_records_available'] ?? false ), 'Report must expose native has_more evidence.' );
SPDB_V123_Failed_Source::$provide_page = false;

if ( $failed ) { fwrite( STDERR, "{$failed} / {$checks} V12.3 provider truth checks failed.\n" ); exit( 1 ); }
echo "All {$checks} V12.3 provider truth checks passed; Hostinger runtime remains unverified.\n";
