<?php
/**
 * File23 V12.3 WP-06: GMT-labelled tasks and delegation windows must not
 * depend on mutable PHP default timezone, and malformed dates must fail.
 * Isolated repository test; not a live Hostinger runtime claim.
 */
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
$service = (new ReflectionClass(SPDB_Governance_Service::class))->newInstanceWithoutConstructor();
$parse = new ReflectionMethod(SPDB_Governance_Service::class, 'datetime');
$assertions = 0; $failed = 0;
$assert = static function (bool $ok, string $why) use (&$assertions, &$failed): void {
    ++$assertions;
    if (!$ok) { ++$failed; fwrite(STDERR, "FAIL: {$why}\n"); }
};
$original_tz = date_default_timezone_get();
try {
    date_default_timezone_set('Asia/Karachi');
    $gmt = $parse->invoke($service, '2026-11-15T10:30', false);
    $assert('2026-11-15 10:30:00' === $gmt,
        'A datetime-local value explicitly labelled GMT must be interpreted as UTC regardless of PHP timezone');
    $explicit_offset = $parse->invoke($service, '2026-11-15T10:30:00+05:00', false);
    $assert('2026-11-15 05:30:00' === $explicit_offset,
        'Explicit offset remains authoritative and is normalized to GMT');
    $explicit_zulu = $parse->invoke($service, '2026-11-15T10:30:00Z', false);
    $assert('2026-11-15 10:30:00' === $explicit_zulu,
        'Explicit UTC timestamp remains UTC');
    $optional_blank = $parse->invoke($service, '', true);
    $assert(null === $optional_blank, 'Optional blank task due date remains null');
    $required_blank = $parse->invoke($service, '', false);
    $assert(is_wp_error($required_blank) && 'spdb_datetime_invalid' === $required_blank->get_error_code(),
        'Required blank delegation date never becomes current clock time');
    $impossible = $parse->invoke($service, '2026-02-31T10:30', false);
    $assert(is_wp_error($impossible) && 'spdb_datetime_invalid' === $impossible->get_error_code(),
        'Invalid calendar day cannot silently normalize to a different month');
} finally {
    date_default_timezone_set($original_tz);
}
if ($failed) { fwrite(STDERR, "{$failed} of {$assertions} File23 V12.3 GMT checks failed.\n"); exit(1); }
echo "All {$assertions} File23 V12.3 GMT normalization checks passed. Source only.\n";
