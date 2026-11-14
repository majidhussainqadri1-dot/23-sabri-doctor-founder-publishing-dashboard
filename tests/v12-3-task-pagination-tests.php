<?php
/**
 * V12.3 WP-12: task list pagination must not overflow PHP integer offsets.
 * Source-only synthetic wpdb test; no live database evidence.
 */
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

final class SPDB_Pagination_Test_DB {
    public string $prefix = 'wp_';
    public int $queries = 0;
    public array $last_arguments = array();
    public function prepare( string $sql, ...$args ): string {
        $this->last_arguments = $args;
        return $sql;
    }
    public function get_results( string $sql, $mode = null ): array {
        ++$this->queries;
        return array();
    }
}
$GLOBALS['wpdb'] = new SPDB_Pagination_Test_DB();
$repo = (new ReflectionClass(SPDB_Operations_Repository::class))->newInstanceWithoutConstructor();
$tests = 0; $fail = 0;
$check = static function (bool $ok, string $reason) use (&$tests,&$fail): void {
    ++$tests;
    if (!$ok) { ++$fail; fwrite(STDERR, 'FAIL: ' . $reason . "\n"); }
};

$small = $repo->list_tasks(7, false, 2, 100);
$check(is_array($small), 'Second task page is readable with synthetic DB.');
$check(array(7, 7, 100, 100) === $GLOBALS['wpdb']->last_arguments,
    'Page 2, size 100 must use bounded integral OFFSET 100.');
$before = $GLOBALS['wpdb']->queries;
$overflow = $repo->list_tasks(7, false, PHP_INT_MAX, 100);
$check(is_wp_error($overflow) && 'spdb_tasks_page_invalid' === $overflow->get_error_code(),
    'PHP_INT_MAX page must fail before computing an overflowing OFFSET.');
$check($before === $GLOBALS['wpdb']->queries,
    'Oversized task pagination must not reach the database.');
if ($fail) { fwrite(STDERR, "{$fail} of {$tests} File23 V12.3 pagination checks failed.\n"); exit(1); }
echo "All {$tests} File23 V12.3 pagination checks passed. Source only.\n";
