<?php
/**
 * Source-level File23 PDF contract. A recording mPDF test-double inspects
 * the complete generated HTML; it is not a real glyph/RTL renderer test.
 */
namespace Mpdf {
	final class Mpdf {
		public static array $config = array();
		public static string $html = '';
		public static bool $fail = false;
		public function __construct( array $config ) { self::$config = $config; }
		public function WriteHTML( string $html ): void {
			self::$html = $html;
			if ( self::$fail ) { throw new \RuntimeException( 'synthetic mPDF failure' ); }
		}
		public function Output( string $name = '', string $dest = '' ): string {
			return "%PDF-1.4\nrecorded-\n%%EOF";
		}
	}
}
namespace {
	require_once __DIR__ . '/bootstrap.php';
	$service = ( new \ReflectionClass( \SPDB_Export_Service::class ) )->newInstanceWithoutConstructor();
	$render = new \ReflectionMethod( \SPDB_Export_Service::class, 'render_pdf' );
	$render->setAccessible( true );

	$tests = 0; $failed = 0;
	$assert = static function ( bool $pass, string $label ) use ( &$tests, &$failed ): void {
		++$tests;
		if ( ! $pass ) { ++$failed; fwrite( STDERR, 'FAIL: ' . $label . "\n" ); }
	};

	$columns = array( 'col_a', 'col_b', 'col_c', 'col_d', 'col_e', 'col_f', 'col_g' );
	$rows = array();
	for ( $i = 1; $i <= 121; ++$i ) {
		$row = array();
		foreach ( $columns as $column ) { $row[ $column ] = "row_{$i}_{$column}"; }
		$rows[] = $row;
	}
	$rows[120]['col_a'] = 'اردو ٹیسٹ — بِسْمِ اللّٰہِ';
	$rows[120]['col_b'] = 'العربية: السلام عليكم';
	$rows[120]['col_g'] = 'LastColumnValue_121';
	$rows[0]['col_g'] = '<script>alert("unsafe")</script>';
	$model = array( 'report_key' => 'publication_history', 'columns' => $columns, 'rows' => $rows, 'limitations' => 'Privacy-filtered. Complete bounded report.', 'generated_at_gmt' => '2026-11-13T00:00:00+00:00' );
	$pdf = $render->invoke( $service, $model );
	$html = \Mpdf\Mpdf::$html;

	$assert( is_string( $pdf ) && str_starts_with( $pdf, '%PDF-'), 'Unicode PDF renderer returns a PDF document rather than unescaped HTML.' );
	$assert( substr_count( $html, '<tr' ) >= 122, 'PDF HTML contains all 121 report rows plus header.' );
	$assert( str_contains( $html, 'LastColumnValue_121' ), 'Seventh column of last row is not silently dropped.' );
	$assert( str_contains( $html, 'row_121_col_f' ), 'All intermediate report columns are retained.' );
	$assert( str_contains( $html, 'اردو ٹیسٹ' ) && str_contains( $html, 'السلام عليكم' ), 'Original Urdu and Arabic UTF-8 text is passed without ASCII question-mark substitution.' );
	$assert( ! str_contains( $html, '<script>' ) && str_contains( $html, '&lt;script&gt;'), 'Provider text is HTML escaped, not active markup.' );
	$assert( true === (bool) ( \Mpdf\Mpdf::$config['autoScriptToLang'] ?? false ) && true === (bool) ( \Mpdf\Mpdf::$config['autoLangToFont'] ?? false ), 'PDF renderer requests Arabic/Urdu automatic font selection.' );
	$assert( isset( \Mpdf\Mpdf::$config['tempDir'] ) && ! is_dir( \Mpdf\Mpdf::$config['tempDir'] ), 'Private temporary mPDF cache is removed after generation.' );
	$assert( ! str_contains( $html, 'http://' ) && ! str_contains( $html, 'https://'), 'PDF HTML has no remote stylesheet, image, or resource references.' );

	$rows[0]['col_a'] = array( 'nested' => 'sensitive' );
	$rejected = $render->invoke( $service, array_merge( $model, array( 'rows' => $rows ) ) );
	$assert( is_wp_error( $rejected ) && 'spdb_pdf_model_invalid' === $rejected->get_error_code(), 'Nested/unexpected provider data cannot be silently replaced with an empty PDF cell.' );
	$rows[0]['col_a'] = "\xC3\x28";
	$invalid = $render->invoke( $service, array_merge( $model, array( 'rows' => $rows ) ) );
	$assert( is_wp_error( $invalid ) && 'spdb_pdf_unicode_invalid' === $invalid->get_error_code(), 'Malformed UTF-8 fails closed, never coerced to question marks.' );

	\Mpdf\Mpdf::$fail = true;
	$failed_render = $render->invoke( $service, $model );
	$assert( is_wp_error( $failed_render ) && 'spdb_pdf_render_failed' === $failed_render->get_error_code(), 'Rendering error cannot be published as a successful encrypted export.' );

	$overlarge = $model;
	$overlarge['rows'] = array_fill( 0, 10001, $rows[1] );
	$too_many = $render->invoke( $service, $overlarge );
	$assert( is_wp_error( $too_many ) && 'spdb_pdf_model_too_large' === $too_many->get_error_code(), 'Out-of-budget PDFs are explicitly refused, never silently truncated.' );

	if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} V12.1 PDF source-contract tests failed.\n" ); exit( 1 ); }
	echo "All {$tests} V12.1 PDF source-contract tests passed.\n";
}
