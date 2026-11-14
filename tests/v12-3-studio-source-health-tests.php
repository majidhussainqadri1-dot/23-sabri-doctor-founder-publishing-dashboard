<?php
/**
 * V12.3 WP-11: database read failures in private task/delegation/rule studio
 * must never masquerade as an authoritative "empty" dataset.
 */
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
final class SPDB_V123_Read_Failure_DB {
    public string $prefix = 'wp_';
    public int $read_count = 0;
    public function prepare(string $sql, ...$params): string { return $sql; }
    public function get_results(string $sql, $output = null) { ++$this->read_count; return null; }
}
$GLOBALS['wpdb'] = new SPDB_V123_Read_Failure_DB();
$GLOBALS['spdb_test_user_id'] = 7;
$repo = (new ReflectionClass(SPDB_Operations_Repository::class))->newInstanceWithoutConstructor();
$service = new SPDB_Governance_Service($repo);
$result = $service->snapshot();
$tests=0; $failed=0;
$check = static function(bool $condition, string $reason) use (&$tests,&$failed): void {
 ++$tests;
 if (!$condition) { ++$failed; fwrite(STDERR,'FAIL: '.$reason."\n"); }
};
$check(is_array($result), 'A privacy-safe private studio envelope is returned.');
$check(($result['source_health']['tasks'] ?? '') === 'unavailable',
    'Failed SQL task read must not be represented as an authoritative empty list.');
$check(($result['source_health']['delegations'] ?? '') === 'unavailable',
    'Failed SQL delegation read must not be represented as an authoritative empty list.');
$check(($result['source_health']['automation_rules'] ?? '') === 'unavailable',
    'Failed SQL rule read must not be represented as an authoritative empty list.');
$check(($result['data_complete'] ?? true) === false,
    'Workspace projection must explicitly announce incomplete data after SQL failure.');
$check(!empty($result['source_error_codes']['tasks']) && !empty($result['source_error_codes']['delegations']),
    'Source errors are surfaced as typed, non-sensitive codes.');
if ($failed) { fwrite(STDERR, "{$failed}/{$tests} private studio source-health tests failed.\n"); exit(1); }
echo "All {$tests} private studio source-health tests passed; real database not tested.\n";
