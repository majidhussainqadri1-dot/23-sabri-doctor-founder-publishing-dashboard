<?php
/**
 * File23 V12.1 WP-04/08: real Composer-pinned mPDF output integration.
 *
 * Unlike a mocked HTML builder, this exercises genuine PDF generation,
 * Ubuntu PDF extraction (when available), Urdu/Arabic font inclusion,
 * table completeness and no silent data truncation.
 */
require_once __DIR__ . '/bootstrap.php';
$autoload = dirname( __DIR__ ) . '/vendor/autoload.php';
$tests = 0; $failed = 0;
$assert = static function ( bool $ok, string $label ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $ok ) { ++$failed; fwrite( STDERR, "FAIL: {$label}\n" ); }
};
$assert( is_file( $autoload ), 'Exact Composer-locked mPDF autoload is installed for real PHP integration.' );
if ( ! is_file( $autoload ) ) { exit( 1 ); }
require_once $autoload;
$assert( class_exists( 'Mpdf\\Mpdf' ) && extension_loaded( 'mbstring' ) && extension_loaded( 'gd' ), 'Real PDF renderer and mandatory extensions are available.' );

$columns = array( 'provider', 'type', 'title', 'status', 'updated_at', 'section_six', 'section_seven' );
$rows = array();
for ( $i = 1; $i <= 125; ++$i ) {
	$rows[] = array(
		'provider' => 'file23',
		'type' => 'draft',
		'title' => 125 === $i ? 'اردو کتاب اور العربية السلام عليكم' : 'Source row ' . $i,
		'status' => 'ready',
		'updated_at' => '2026-11-13 00:00:00',
		'section_six' => 'Sixth column ' . $i,
		'section_seven' => 'LAST_COLUMN_125_' . $i,
	);
}
$model = array(
	'report_key' => 'publication_history',
	'columns' => $columns,
	'rows' => $rows,
	'limitations' => 'Privacy-filtered, complete bounded report. No raw native records.',
	'generated_at_gmt' => '2026-11-13T00:00:00Z',
);
$service = ( new ReflectionClass( SPDB_Export_Service::class ) )->newInstanceWithoutConstructor();
$method = new ReflectionMethod( SPDB_Export_Service::class, 'render_pdf' );
$method->setAccessible( true );
$pdf = $method->invoke( $service, $model );
$assert( is_string( $pdf ), 'Genuine mPDF generates a PDF without typed WP_Error.' );
if ( is_wp_error( $pdf ) ) {
	fwrite( STDERR, "Runtime PDF failure code: " . $pdf->get_error_code() . "\n" );
}
if ( is_string( $pdf ) ) {
	$assert( str_starts_with( $pdf, '%PDF-') && str_contains( substr( $pdf, -2048 ), '%%EOF' ), 'Actual binary PDF has valid framing.' );
	$assert( strlen( $pdf ) > 10000 && strlen( $pdf ) < 33554432, 'Embedded-font PDF has plausible bounded byte size.' );

	$raw = sys_get_temp_dir() . '/spdb-source-pdf-test-' . bin2hex( random_bytes( 8 ) ) . '.pdf';
	try {
		file_put_contents( $raw, $pdf );
		$command = trim( (string) shell_exec( 'command -v pdftotext' ) );
		if ( '' !== $command ) {
			$text = shell_exec( escapeshellarg( $command ) . ' -layout -enc UTF-8 ' . escapeshellarg( $raw ) . ' - 2>/dev/null' );
			$assert( is_string( $text ) && str_contains( $text, 'LAST_COLUMN_125_125' ), 'Real PDF text extraction includes the seventh column of last (125th) row.' );
			$assert( is_string( $text ) && str_contains( $text, 'Source row 120' ), 'Rows before the former 120-row cap remain extractable.' );
			$assert( is_string( $text ) && str_contains( $text, 'اردو' ), 'Real generated PDF preserves extractable Urdu glyph mapping.' );
			// PDF extraction may expose GSUB Arabic presentation-form codepoints
			// rather than Unicode base characters. Compare after standard
			// Unicode NFKC compatibility normalization, not raw glyph codes.
			$normalized = is_string( $text ) && class_exists( 'Normalizer' )
				? Normalizer::normalize( $text, Normalizer::FORM_KC )
				: false;
			$assert( is_string( $normalized ) && str_contains( $normalized, 'السلام' ), 'Real generated PDF preserves Arabic text after presentation-form normalization.' );
			$assert( is_string( $text ) && ! str_contains( $text, '???' ), 'Urdu/Arabic is not replaced by the old ASCII question-mark fallback.' );
		} else {
			if ( '1' === getenv( 'FILE23_REQUIRE_PDF_TEXT_EXTRACT' ) ) {
				$assert( false, 'Release-candidate PDF acceptance requires real pdftotext Unicode extraction.' );
			} else {
				fwrite( STDERR, "NOTICE: pdftotext unavailable: binary PDF produced but Unicode extraction remains unverified in this environment.\n" );
			}
		}
	} finally {
		@unlink( $raw );
	}
}
if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} V12.1 real mPDF rendering tests failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1 real mPDF rendering tests passed.\n";
