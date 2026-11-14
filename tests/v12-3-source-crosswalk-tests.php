<?php
/**
 * V12.3 File 23 additive requirement-census and evidence-truth regression.
 *
 * This is structural/source verification, not a behavioral/staging/live pass.
 */
declare(strict_types=1);
$root = dirname( __DIR__ );
$filename = $root . '/docs/V12.3-REQUIREMENT-CROSSWALK.json';
$raw = file_get_contents( $filename );
$data = is_string( $raw ) ? json_decode( $raw, true ) : null;
$count = 0; $failed = 0;
$check = static function ( bool $valid, string $reason ) use ( &$count, &$failed ): void {
    ++$count;
    if ( ! $valid ) { ++$failed; fwrite( STDERR, "FAIL: {$reason}\n" ); }
};
$check( is_array( $data ), 'V12.3 crosswalk must be well-formed JSON.' );
if ( ! is_array( $data ) ) { exit( 1 ); }
$check( 'file23-v12.3-crosswalk-v1' === ( $data['schema'] ?? null ), 'Crosswalk schema/version.' );
$check( null === ( $data['census']['source_completion_percent'] ?? null ), 'Do not fabricate coding percentage.' );
$check( 'NO-GO' === ( $data['reality']['release_decision'] ?? null ), 'Never claim live release from source mapping.' );
foreach ( array( 'deployed_version', 'deployed_checksum', 'db_version', 'migration_state', 'live_verification' ) as $field ) {
    $check( 'unverified' === ( $data['reality'][ $field ] ?? null ), 'Live state must remain unverified: ' . $field );
}
$rows = $data['requirements'] ?? array();
$check( is_array( $rows ) && 187 === count( $rows ), 'Exactly 187 scoped requirement identities, including ten chapter-10 V12.3 corrections.' );
$ids = array();
$by_id = array();
$kinds = array();
foreach ( (array) $rows as $entry ) {
    $id = is_array( $entry ) ? (string) ( $entry['id'] ?? '' ) : '';
    $check( '' !== $id && ! isset( $ids[$id] ), 'Duplicate/empty ID: ' . $id );
    $ids[$id] = true;
    $by_id[$id] = $entry;
    $kind = (string) ( $entry['kind'] ?? '' );
    $kinds[$kind] = (int) ( $kinds[$kind] ?? 0 ) + 1;
    $check( ! empty( $entry['canonical_owner'] ) && ! empty( $entry['file23_consumer_boundary'] ), $id . ': canonical owner/boundary.' );
    $check( 'pending' === ( $entry['staging_acceptance'] ?? '' ) && 'unverified' === ( $entry['live_verification'] ?? '' ), $id . ': live/staging status.' );
    $check( 'not-yet-verified' === ( $entry['individual_source_test'] ?? '' ), $id . ': no fabricated item-level proof.' );
    $paths = $entry['source_evidence'] ?? null;
    $check( is_array( $paths ), $id . ': evidence must be an array.' );
    foreach ( is_array( $paths ) ? $paths : array() as $path ) {
        $check( is_string( $path ) && is_file( $root . '/' . $path ), $id . ': missing evidence path ' . (string) $path );
    }
}
$expected_counts = array(
    'inherited_cv' => 70, 'inherited_aj' => 16, 'fpi' => 24,
    'file23_central' => 1, 'retained_sto_act' => 17, 'v12_3_mission' => 12,
    'v12_3_acceptance' => 10, 'ux_retained' => 14, 'v12_3_central_policy' => 9,
    'retained_decision' => 4, 'v12_3_chapter_10_correction' => 10,
);
ksort( $expected_counts );
ksort( $kinds );
$reported_counts = (array) ( $data['census']['by_kind'] ?? array() );
ksort( $reported_counts );
$check( $expected_counts === $kinds, 'Exact historical and additive census by category.' );
$check( $expected_counts === $reported_counts, 'Census metadata matches actual entries.' );
for ( $i = 1; $i <= 24; $i++ ) {
    $check( isset( $ids[sprintf( 'F23-FPI-%02d', $i )] ), 'Missing FPI ' . $i );
}
$qa_by_mission = array(
    'M01' => 'QA-15', 'M02' => 'QA-16', 'M03' => 'QA-17',
    'M04' => 'QA-18', 'M05' => 'QA-18', 'M06' => 'QA-19',
    'M07' => 'QA-20', 'M08' => 'QA-21', 'M09' => 'QA-22',
    'M10' => 'QA-22', 'M11' => 'QA-24', 'M12' => 'QA-23',
);
foreach ( $qa_by_mission as $mission => $qa ) {
    $check( isset( $by_id[$mission] ), 'Missing mission ' . $mission );
    $check( array( $qa ) === ( $by_id[$mission]['qa_refs'] ?? null ), $mission . ' must map to ' . $qa );
}
for ( $i = 1; $i <= 10; ++$i ) {
    $id = sprintf( 'V12.3-%02d', $i );
    $check( isset( $by_id[$id] ), 'V12.3 Chapter 10 correction must be mapped: ' . $id );
    $check( ! isset( $by_id[$id] ) || 'v12_3_chapter_10_correction' === ( $by_id[$id]['kind'] ?? null ),
        $id . ' must retain its distinct source-correction classification.' );
}
for ( $n = 15; $n <= 24; ++$n ) {
    $check( isset( $by_id['QA-' . $n] ), 'QA-' . $n . ' exists.' );
}
foreach ( $rows as $entry ) {
    foreach ( $entry['qa_refs'] ?? array() as $qa ) {
        $check( isset( $by_id[$qa] ), $entry['id'] . ' references an unknown QA ' . $qa );
    }
}
if ( $failed ) { fwrite( STDERR, "{$failed} of {$count} V12.3 source crosswalk checks failed.\n" ); exit( 1 ); }
echo "All {$count} V12.3 source crosswalk checks passed. No staging/live claims.\n";
