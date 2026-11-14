<?php
/**
 * File23 V12.3 WP-07: static FPI-24 per-ID code catalogue / evaluator trace.
 * No assertion that real native owners, staging or live have been verified.
 */
declare(strict_types=1);
$root = dirname(__DIR__);
$data = json_decode((string) file_get_contents($root.'/docs/V12.3-FPI24-SOURCE-AUDIT.json'), true);
$source = (string) file_get_contents($root.'/includes/class-spdb-publishing-intelligence.php');
$crosswalk = json_decode((string) file_get_contents($root.'/docs/V12.3-REQUIREMENT-CROSSWALK.json'), true);
$tests = 0; $failed = 0;
$check = static function (bool $okay, string $label) use (&$tests,&$failed): void {
    ++$tests;
    if (!$okay) { ++$failed; fwrite(STDERR,'FAIL: '.$label."\n"); }
};
$check(is_array($data), 'The FPI24 source audit is valid JSON.');
if (!is_array($data)) { exit(1); }
$rows = $data['rows'] ?? null;
$check(is_array($rows) && 24 === count($rows), 'All 24 V12.3 FPI facilities have a source mapping.');
$catalog_ids = array();
$catalog_slugs = array();
$priority_counts = array('P0'=>0,'P1'=>0,'P2'=>0);
$crosswalk_ids = array();
foreach (($crosswalk['requirements'] ?? array()) as $entry) {
    if (is_array($entry)) { $crosswalk_ids[$entry['id'] ?? ''] = true; }
}
foreach (is_array($rows) ? $rows : array() as $i => $row) {
    if (!is_array($row)) { continue; }
    $id = (string)($row['id'] ?? '');
    $slug = (string)($row['slug'] ?? '');
    $name = (string)($row['name'] ?? '');
    $priority = (string)($row['priority'] ?? '');
    $expr = (string)($row['evaluate_expression'] ?? '');
    $check($id === sprintf('F23-FPI-%02d',$i+1), 'Each FPI ID retains approved numeric order.');
    $check($slug !== '' && !isset($catalog_slugs[$slug]), 'Canonical slug is unique: '.$slug);
    $check(!isset($catalog_ids[$id]), 'No duplicated ID: '.$id);
    $check(isset($crosswalk_ids[$id]), 'Every FPI ID appears in the full 187-ID crosswalk.');
    $check(isset($priority_counts[$priority]), 'Each FPI has P0/P1/P2 approved priority.');
    $catalog_slugs[$slug] = true; $catalog_ids[$id] = true;
    if (isset($priority_counts[$priority])) { ++$priority_counts[$priority]; }
    // Verify both the actual catalogue declaration and its executable source
    // evaluation dispatch without accepting a generic fallback as a feature.
    $catalogue_line = "'".$slug."' => self::feature( '".$id."', '".$name."', '".$priority."'";
    $dispatch_line = "case '".$slug."': return ".$expr.";";
    $check(str_contains($source, $catalogue_line), $id.' catalogue declaration and priority match source.');
    $check($expr !== '' && str_contains($source,$dispatch_line), $id.' has a dedicated evaluator / owned advisory route.');
    $check(($row['staging'] ?? '') === 'pending' && ($row['live'] ?? '') === 'unverified',
        $id.' does not assert live provider readiness.');
}
$check($priority_counts === array('P0'=>12,'P1'=>10,'P2'=>2),
    'Priority tiers agree with V12.3 (12 P0, 10 P1, 2 P2).');
$check(($data['counts']['features'] ?? null) === 24, 'Source-audit count matches catalogue.');
if ($failed) { fwrite(STDERR,"{$failed}/{$tests} V12.3 FPI source checks failed.\n"); exit(1); }
echo "All {$tests} V12.3 FPI source-to-evaluator checks passed; real providers unverified.\n";
