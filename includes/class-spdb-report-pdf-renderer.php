<?php
/**
 * File 23 Unicode/RTL PDF report renderer.
 *
 * File23 owns only privacy-filtered, bounded report projections; never
 * imports native records. Composer-pinned mPDF is shipped inside the
 * installable plugin ZIP. There is no ASCII-degrading PDF fallback.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Report_Pdf_Renderer {
	private const MAX_ROWS = 10000;
	private const MAX_COLUMNS = 32;
	private const MAX_HTML_BYTES = 8388608;
	private const MAX_PDF_BYTES = 33554432;

	/** @param array<string,mixed> $model @return string|WP_Error */
	public static function render( array $model ) {
		// The sanitization and UTF-8 validation below use mbstring; without
		// it WordPress must receive a retryable error, never a PHP fatal.
		if ( ! function_exists( 'mb_check_encoding' ) || ! extension_loaded( 'gd' ) ) {
			return self::error( 'spdb_pdf_dependency_unavailable', __( 'The approved Unicode PDF runtime extensions are unavailable.', 'sabri-publishing-dashboard' ), 503 );
		}
		$markup = self::build_html( $model );
		if ( is_wp_error( $markup ) ) {
			return $markup;
		}

		// Never accept an unpinned package discovered from a remote URL,
		// client-supplied path, or a WordPress hook as a PDF runtime.
		$autoload = dirname( __DIR__ ) . '/vendor/autoload.php';
		if ( ! class_exists( 'Mpdf\\Mpdf', false ) && is_file( $autoload ) ) {
			require_once $autoload;
		}
		if ( ! class_exists( 'Mpdf\\Mpdf' ) || ! extension_loaded( 'mbstring' ) || ! extension_loaded( 'gd' ) ) {
			return self::error( 'spdb_pdf_dependency_unavailable', __( 'The approved Unicode PDF renderer or required PHP extensions are unavailable.', 'sabri-publishing-dashboard' ), 503 );
		}

		$temp = self::private_temp_directory();
		if ( is_wp_error( $temp ) ) {
			return $temp;
		}
		$result = null;
		try {
			$columns = $model['columns'];
			$mpdf = new \Mpdf\Mpdf(
				array(
					'mode'             => 'utf-8',
					'format'           => count( $columns ) > 5 ? 'A4-L' : 'A4',
					'tempDir'          => $temp,
					'default_font'     => 'dejavusans',
					'autoScriptToLang' => true,
					'autoLangToFont'   => true,
					'autoArabic'       => true,
				)
			);
			$mpdf->WriteHTML( $markup );
			$pdf = $mpdf->Output( '', 'S' );
			$result = (
				! is_string( $pdf ) || ! str_starts_with( $pdf, '%PDF-' ) ||
				strlen( $pdf ) > self::MAX_PDF_BYTES ||
				false === strpos( substr( $pdf, -2048 ), '%%EOF' )
			)
				? self::error( 'spdb_pdf_output_invalid', __( 'The PDF renderer returned an invalid or oversized document.', 'sabri-publishing-dashboard' ), 500 )
				: $pdf;
		} catch ( \Throwable $exception ) {
			// Do not expose file paths, HTML, private report values, or stack
			// traces in WordPress/user-visible errors.
			$result = self::error( 'spdb_pdf_render_failed', __( 'The private PDF report could not be rendered safely.', 'sabri-publishing-dashboard' ), 500 );
		} finally {
			self::remove_private_temp_directory( $temp );
		}
		if ( is_dir( $temp ) || is_link( $temp ) ) {
			// A leftover cache may contain sensitive render intermediates.
			// Never report a completed PDF until private cache removal works.
			return self::error( 'spdb_pdf_temp_cleanup_failed', __( 'Private PDF temporary files could not be removed completely.', 'sabri-publishing-dashboard' ), 500 );
		}
		return $result;
	}

	/** @param array<string,mixed> $model @return string|WP_Error */
	private static function build_html( array $model ) {
		$columns = $model['columns'] ?? null;
		$rows    = $model['rows'] ?? null;
		if (
			! is_array( $columns ) || ! is_array( $rows ) ||
			count( $columns ) < 1 || count( $columns ) > self::MAX_COLUMNS ||
			count( $rows ) > self::MAX_ROWS
		) {
			return self::error( 'spdb_pdf_model_too_large', __( 'The PDF report exceeds its safe capacity or has no valid columns.', 'sabri-publishing-dashboard' ), 422 );
		}
		$labels = array();
		foreach ( $columns as $column ) {
			if ( ! is_string( $column ) || ! preg_match( '/^[a-zA-Z][a-zA-Z0-9_]{0,79}$/', $column ) || isset( $labels[ $column ] ) ) {
				return self::error( 'spdb_pdf_model_invalid', __( 'The report contains an invalid or duplicated column key.', 'sabri-publishing-dashboard' ), 422 );
			}
			$labels[ $column ] = ucwords( str_replace( '_', ' ', $column ) );
		}

		$title = self::safe_text( (string) ( $model['report_key'] ?? 'report' ) );
		$limitation = self::safe_text( (string) ( $model['limitations'] ?? '' ) );
		$generated = self::safe_text( (string) ( $model['generated_at_gmt'] ?? gmdate( 'c' ) ) );
		if ( is_wp_error( $title ) || is_wp_error( $limitation ) || is_wp_error( $generated ) ) {
			return self::error( 'spdb_pdf_unicode_invalid', __( 'The report metadata contains invalid UTF-8 or unsafe text.', 'sabri-publishing-dashboard' ), 422 );
		}

		// No external image, stylesheet, script, URI, or untrusted HTML
		// enters mPDF. Native sources arrive as escaped projection text.
		$html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
		$html .= '<style>@page{margin:14mm} body{font-family:dejavusans,sans-serif;font-size:9pt;color:#183126}';
		$html .= 'h1{font-size:15pt;color:#087A4E;margin:0 0 5mm}p{font-size:8pt}';
		$html .= 'table{width:100%;border-collapse:collapse}th{background:#e6f3eb;color:#0d5134;font-weight:bold}';
		$html .= 'th,td{border:0.3mm solid #cfded5;padding:2.2mm;vertical-align:top;overflow-wrap:break-word}';
		$html .= 'tr{page-break-inside:avoid}</style></head><body><h1>Sabri Social Homeopathy Platform — File 23 Report</h1>';
		$html .= '<p>Report: ' . $title . ' | Generated (UTC): ' . $generated . '</p>';
		$html .= '<p>Rows: ' . count( $rows ) . ' | Columns: ' . count( $columns ) . '</p>';
		if ( '' !== $limitation ) { $html .= '<p>' . $limitation . '</p>'; }
		$html .= '<table><thead><tr>';
		foreach ( $labels as $label ) {
			$html .= '<th>' . htmlspecialchars( $label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) . '</th>';
		}
		$html .= '</tr></thead><tbody>';

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				return self::error( 'spdb_pdf_model_invalid', __( 'A PDF report row has an unsupported structure.', 'sabri-publishing-dashboard' ), 422 );
			}
			$html .= '<tr>';
			foreach ( $labels as $column => $label ) {
				$value = $row[ $column ] ?? '';
				if ( ! is_scalar( $value ) && null !== $value ) {
					return self::error( 'spdb_pdf_model_invalid', __( 'A PDF report cell contains unsupported data.', 'sabri-publishing-dashboard' ), 422 );
				}
				$raw = null === $value ? '' : (string) $value;
				$text = self::safe_text( $raw );
				if ( is_wp_error( $text ) ) { return $text; }
				$rtl = 1 === preg_match( '/\p{Arabic}/u', $raw );
				$html .= $rtl
					? '<td lang="ur" dir="rtl" style="text-align:right;direction:rtl">' . nl2br( $text, false ) . '</td>'
					: '<td>' . nl2br( $text, false ) . '</td>';
			}
			$html .= '</tr>';
			if ( strlen( $html ) > self::MAX_HTML_BYTES ) {
				return self::error( 'spdb_pdf_model_too_large', __( 'The complete PDF report exceeds its safe rendering budget; no truncated file was created.', 'sabri-publishing-dashboard' ), 422 );
			}
		}
		$html .= '</tbody></table></body></html>';
		return $html;
	}

	/** @return string|WP_Error */
	private static function safe_text( string $raw ) {
		if ( ! mb_check_encoding( $raw, 'UTF-8' ) || preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $raw ) ) {
			return self::error( 'spdb_pdf_unicode_invalid', __( 'The report contains invalid Unicode or unsupported control characters.', 'sabri-publishing-dashboard' ), 422 );
		}
		return htmlspecialchars( $raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}

	/** @return string|WP_Error */
	private static function private_temp_directory() {
		$base = realpath( sys_get_temp_dir() );
		$site = realpath( ABSPATH );
		if (
			! is_string( $base ) || ! is_dir( $base ) ||
			( is_string( $site ) && ( $base === $site || str_starts_with( $base, rtrim( $site, '/\\' ) . DIRECTORY_SEPARATOR ) ) )
		) {
			return self::error( 'spdb_pdf_temp_unavailable', __( 'A private, non-public temporary PDF directory is unavailable.', 'sabri-publishing-dashboard' ), 503 );
		}
		try {
			$temp = $base . '/spdb-pdf-' . bin2hex( random_bytes( 12 ) );
		} catch ( \Throwable $error ) {
			return self::error( 'spdb_pdf_temp_unavailable', __( 'Secure temporary PDF storage cannot be initialized.', 'sabri-publishing-dashboard' ), 503 );
		}
		if ( ! mkdir( $temp, 0700 ) || is_link( $temp ) || ! is_dir( $temp ) ) {
			return self::error( 'spdb_pdf_temp_unavailable', __( 'Private PDF temporary storage could not be created safely.', 'sabri-publishing-dashboard' ), 503 );
		}
		return $temp;
	}

	private static function remove_private_temp_directory( string $path ): void {
		if ( is_link( $path ) ) {
			@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Cleanup only our own linked temp entry.
			return;
		}
		if ( ! is_dir( $path ) ) { return; }
		try {
			$iter = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $path, \FilesystemIterator::SKIP_DOTS ),
				\RecursiveIteratorIterator::CHILD_FIRST
			);
			foreach ( $iter as $item ) {
				if ( $item->isDir() && ! $item->isLink() ) {
					@rmdir( $item->getPathname() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Temp cleanup.
				} else {
					@unlink( $item->getPathname() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Temp cleanup.
				}
			}
			@rmdir( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Temp cleanup.
		} catch ( \Throwable $error ) {
			// A cleanup failure does not convert a failed PDF into a success.
			// The private directory is 0700 and must remain outside docroot.
		}
	}

	private static function error( string $code, string $message, int $status ): \WP_Error {
		return new \WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
