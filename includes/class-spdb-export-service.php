<?php
/**
 * Privacy-filtered, expiring File 23 report exports.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Export_Service {
	private const DOWNLOAD_ACTION = 'spdb_download_export';
	private const ENVELOPE_MAGIC = 'SPDBEXP1';

	private SPDB_Operations_Service $operations;
	private SPDB_Review_Calendar_Service $calendar;
	private SPDB_Operations_Repository $repository;

	public function __construct(
		SPDB_Operations_Service $operations,
		SPDB_Review_Calendar_Service $calendar,
		SPDB_Operations_Repository $repository
	) {
		$this->operations = $operations;
		$this->calendar   = $calendar;
		$this->repository = $repository;
	}

	public function register(): void {
		add_action( 'admin_post_' . self::DOWNLOAD_ACTION, array( $this, 'download' ) );
	}

	/**
	 * @param array<string,mixed> $input Raw export request.
	 * @return array<string,mixed>|WP_Error
	 */
	public function request_export( array $input ) {
		if ( ! SPDB_Capabilities::current_user_can( 'spdb_export_reports' ) ) {
			return self::error( 'spdb_export_forbidden', __( 'You are not authorized to export reports.', 'sabri-publishing-dashboard' ), 403 );
		}
		$report_key = sanitize_key( (string) ( $input['report_key'] ?? '' ) );
		$format     = sanitize_key( (string) ( $input['format'] ?? 'csv' ) );
		$scope      = sanitize_key( (string) ( $input['scope'] ?? 'own' ) );
		$formats    = array( 'csv', 'json', 'html', 'pdf', 'ics' );
		$reports    = array( 'publication_history', 'content_performance', 'review_history', 'knowledge_portfolio', 'comment_response', 'monthly_summary', 'institution_publishing', 'doctor_contributions', 'editorial_backlog', 'campaign_results', 'corrections_retractions', 'source_completeness', 'safety_incidents', 'content_gaps', 'calendar_health' );
		if ( ! in_array( $report_key, $reports, true ) || ! in_array( $format, $formats, true ) || ! in_array( $scope, array( 'own', 'institution' ), true ) ) {
			return self::error( 'spdb_export_invalid', __( 'The export format or scope is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		if ( 'institution' === $scope && ! $this->is_institutional( get_current_user_id() ) ) {
			return self::error( 'spdb_export_scope_forbidden', __( 'Institution-wide exports are not authorized.', 'sabri-publishing-dashboard' ), 403 );
		}
		$filters = isset( $input['filters'] ) && is_array( $input['filters'] ) ? $this->sanitize_filters( $input['filters'] ) : array();
		$ttl     = (int) SPDB_Admin_Settings::get()['export_ttl_hours'];
		$job     = $this->repository->create_export_job(
			array(
				'owner_user_id' => get_current_user_id(),
				'report_key'    => $report_key,
				'format'        => $format,
				'scope'         => $scope,
				'filters'       => $filters,
				'expires_at_gmt' => gmdate( 'Y-m-d H:i:s', time() + $ttl * HOUR_IN_SECONDS ),
			)
		);
		if ( is_wp_error( $job ) ) {
			return $job;
		}
		$queued = $this->repository->enqueue_job(
			'export_generate',
			get_current_user_id(),
			array( 'export_id' => $job['export_id'] ),
			'export:' . $job['export_id'],
			(int) SPDB_Admin_Settings::get()['job_max_attempts']
		);
		if ( is_wp_error( $queued ) ) {
			$transition = $this->repository->fail_export_job( $job['export_id'], $queued->get_error_code() );
			return is_wp_error( $transition ) ? $transition : $queued;
		}
		return $this->public_export( $job );
	}

	/** @return array<int,array<string,mixed>>|WP_Error */
	public function list_exports() {
		if ( ! SPDB_Capabilities::current_user_can( 'spdb_export_reports' ) ) {
			return self::error( 'spdb_export_forbidden', __( 'You are not authorized to view export jobs.', 'sabri-publishing-dashboard' ), 403 );
		}
		$rows = $this->repository->list_export_jobs( get_current_user_id(), $this->is_institutional( get_current_user_id() ) );
		if ( is_wp_error( $rows ) ) {
			return $rows;
		}
		return array_map( array( $this, 'public_export' ), $rows );
	}

	/**
	 * Process one queued export. Called only by the bounded background worker.
	 *
	 * @return true|WP_Error
	 */
	public function process( string $export_id ) {
		$job = $this->repository->get_export_job( $export_id, 0, true );
		if ( is_wp_error( $job ) ) {
			return $job;
		}
		if ( ! in_array( $job['status'], array( 'queued', 'processing' ), true ) ) {
			return true;
		}
		if ( '' !== $job['expires_at_gmt'] && strtotime( $job['expires_at_gmt'] . ' UTC' ) <= time() ) {
			return $this->fail_processing( $export_id, self::error( 'spdb_export_expired', __( 'The export request expired before generation.', 'sabri-publishing-dashboard' ), 410 ) );
		}

		if ( 'queued' === $job['status'] ) {
			$claimed = $this->repository->mark_export_processing( $export_id );
			if ( is_wp_error( $claimed ) ) {
				return $claimed;
			}
		}

		if ( 'ics' === $job['format'] ) {
			$model = $this->calendar_model( $job );
		} else {
			$model = $this->operations->report( $job['report_key'], $job['scope'], $job['filters'] );
		}
		if ( is_wp_error( $model ) ) {
			return $this->fail_processing( $export_id, $model );
		}

		$directory = $this->private_directory();
		if ( is_wp_error( $directory ) ) {
			return $this->fail_processing( $export_id, $directory );
		}
		$filename = $export_id . '.spdb';
		$path     = trailingslashit( $directory ) . $filename;
		$content  = $this->render( $job['format'], $model );
		if ( is_wp_error( $content ) ) {
			return $this->fail_processing( $export_id, $content );
		}
		$sealed = $this->seal_content( $content, $export_id );
		if ( is_wp_error( $sealed ) ) {
			return $this->fail_processing( $export_id, $sealed );
		}
		if ( false === file_put_contents( $path, $sealed, LOCK_EX ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Protected generated artifact directory.
			return $this->fail_processing( $export_id, self::error( 'spdb_export_file_write_failed', __( 'The export file could not be written.', 'sabri-publishing-dashboard' ), 500 ) );
		}
		@chmod( $path, 0640 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Best-effort hardening.
		$hash = hash_file( 'sha256', $path );
		if ( ! is_string( $hash ) || 64 !== strlen( $hash ) ) {
			@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.unlink_unlink -- Cleanup generated file.
			return $this->fail_processing( $export_id, self::error( 'spdb_export_hash_failed', __( 'The export file could not be verified.', 'sabri-publishing-dashboard' ), 500 ) );
		}
		$row_count = isset( $model['rows'] ) && is_array( $model['rows'] ) ? count( $model['rows'] ) : ( isset( $model['events'] ) && is_array( $model['events'] ) ? count( $model['events'] ) : 0 );
		$completed = $this->repository->complete_export_job( $export_id, $filename, $hash, $row_count );
		if ( is_wp_error( $completed ) ) {
			@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove an uncommitted generated artifact.
			return $completed;
		}
		return true;
	}

	/** Download only through an authenticated, owner-bound, expiring signature. */
	public function download(): void {
		if ( ! is_user_logged_in() ) {
			auth_redirect();
			exit;
		}
		$export_id = isset( $_GET['export'] ) && is_scalar( $_GET['export'] ) ? sanitize_key( wp_unslash( (string) $_GET['export'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- HMAC is the one-time authorization proof.
		$expires   = isset( $_GET['expires'] ) ? (int) $_GET['expires'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$signature = isset( $_GET['signature'] ) && is_scalar( $_GET['signature'] ) ? strtolower( trim( wp_unslash( (string) $_GET['signature'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$user_id   = get_current_user_id();
		if ( '' === $export_id || $expires < time() || $expires > time() + HOUR_IN_SECONDS || ! hash_equals( $this->signature( $export_id, $user_id, $expires ), $signature ) ) {
			wp_die( esc_html__( 'The export download link is invalid or expired.', 'sabri-publishing-dashboard' ), esc_html__( 'Export unavailable', 'sabri-publishing-dashboard' ), array( 'response' => 403 ) );
		}
		$job = $this->repository->get_export_job( $export_id, $user_id, false );
		if ( is_wp_error( $job ) || 'ready' !== ( $job['status'] ?? '' ) || '' === ( $job['storage_ref'] ?? '' ) || '' === ( $job['file_hash'] ?? '' ) ) {
			wp_die( esc_html__( 'The export file is unavailable.', 'sabri-publishing-dashboard' ), esc_html__( 'Export unavailable', 'sabri-publishing-dashboard' ), array( 'response' => 404 ) );
		}
		if ( '' !== $job['expires_at_gmt'] && strtotime( $job['expires_at_gmt'] . ' UTC' ) <= time() ) {
			wp_die( esc_html__( 'The export file has expired.', 'sabri-publishing-dashboard' ), esc_html__( 'Export expired', 'sabri-publishing-dashboard' ), array( 'response' => 410 ) );
		}
		$directory = $this->private_directory();
		if ( is_wp_error( $directory ) ) {
			wp_die( esc_html__( 'The export storage is unavailable.', 'sabri-publishing-dashboard' ), esc_html__( 'Export unavailable', 'sabri-publishing-dashboard' ), array( 'response' => 500 ) );
		}
		$filename = basename( (string) $job['storage_ref'] );
		$path     = trailingslashit( $directory ) . $filename;
		$real_dir = realpath( $directory );
		$real     = realpath( $path );
		if ( false === $real_dir || false === $real || 0 !== strpos( $real, trailingslashit( $real_dir ) ) || ! is_file( $real ) || ! hash_equals( (string) $job['file_hash'], (string) hash_file( 'sha256', $real ) ) ) {
			wp_die( esc_html__( 'The export file failed integrity verification.', 'sabri-publishing-dashboard' ), esc_html__( 'Export unavailable', 'sabri-publishing-dashboard' ), array( 'response' => 409 ) );
		}
		$sealed = file_get_contents( $real ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Verified private generated file.
		$content = is_string( $sealed ) ? $this->open_content( $sealed, $export_id ) : self::error( 'spdb_export_file_read_failed', __( 'The export file could not be read.', 'sabri-publishing-dashboard' ), 500 );
		if ( is_wp_error( $content ) ) {
			wp_die( esc_html__( 'The export file could not be decrypted safely.', 'sabri-publishing-dashboard' ), esc_html__( 'Export unavailable', 'sabri-publishing-dashboard' ), array( 'response' => 409 ) );
		}
		$mime = $this->mime_type( (string) $job['format'] );
		nocache_headers();
		header( 'Content-Type: ' . $mime );
		header( 'Content-Disposition: attachment; filename="' . rawurlencode( 'file23-' . $job['report_key'] . '-' . gmdate( 'Ymd-His' ) . '.' . $job['format'] ) . '"' );
		header( 'Content-Length: ' . (string) strlen( $content ) );
		header( 'X-Content-Type-Options: nosniff' );
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Authorized binary-safe download body.
		exit;
	}

	/** Remove generated temporary export files owned by one user. */
	public function erase_user_files( int $user_id ): int {
		if ( $user_id < 1 ) {
			return 0;
		}
		$directory = $this->private_directory();
		if ( is_wp_error( $directory ) ) {
			return 0;
		}
		global $wpdb;
		$table   = SPDB_Operations_Schema::table( 'export_jobs' );
		$deleted = 0;
		$cursor  = 0;
		for ( $batch = 0; $batch < 100; ++$batch ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare( "SELECT id, storage_ref FROM {$table} WHERE owner_user_id = %d AND id > %d ORDER BY id ASC LIMIT 200", $user_id, $cursor ),
				defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A'
			);
			if ( ! is_array( $rows ) || ! $rows ) {
				break;
			}
			foreach ( $rows as $row ) {
				$cursor   = max( $cursor, (int) ( $row['id'] ?? 0 ) );
				$filename = basename( (string) ( $row['storage_ref'] ?? '' ) );
				if ( '' === $filename || 1 !== preg_match( '/^export_[a-z0-9]{32}\.spdb$/', $filename ) ) {
					continue;
				}
				$path = trailingslashit( $directory ) . $filename;
				if ( is_file( $path ) && unlink( $path ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Deleting File 23-owned encrypted temporary export only.
					++$deleted;
				}
			}
			if ( count( $rows ) < 200 ) {
				break;
			}
		}
		return $deleted;
	}

	/** @param array<string,mixed> $job @return array<string,mixed> */
	public function public_export( array $job ): array {
		$out = array(
			'export_id'       => $job['export_id'],
			'report_key'      => $job['report_key'],
			'format'          => $job['format'],
			'scope'           => $job['scope'],
			'status'          => $job['status'],
			'row_count'       => $job['row_count'],
			'error_code'      => $job['error_code'],
			'created_at_gmt'  => $job['created_at_gmt'],
			'updated_at_gmt'  => $job['updated_at_gmt'],
			'expires_at_gmt'  => $job['expires_at_gmt'],
			'download_url'    => '',
		);
		if ( 'ready' === $job['status'] && '' !== $job['storage_ref'] && strtotime( $job['expires_at_gmt'] . ' UTC' ) > time() ) {
			$out['download_url'] = $this->download_url( $job['export_id'], (int) $job['owner_user_id'] );
		}
		return $out;
	}

	private function download_url( string $export_id, int $user_id ): string {
		$expires = time() + 15 * MINUTE_IN_SECONDS;
		return add_query_arg(
			array(
				'action'    => self::DOWNLOAD_ACTION,
				'export'    => $export_id,
				'expires'   => $expires,
				'signature' => $this->signature( $export_id, $user_id, $expires ),
			),
			admin_url( 'admin-post.php' )
		);
	}

	private function signature( string $export_id, int $user_id, int $expires ): string {
		return hash_hmac( 'sha256', $export_id . '|' . $user_id . '|' . $expires, wp_salt( 'auth' ) );
	}

	/** @return string|WP_Error */
	private function private_directory() {
		$upload = wp_upload_dir( null, false );
		if ( ! is_array( $upload ) || ! empty( $upload['error'] ) || empty( $upload['basedir'] ) ) {
			return self::error( 'spdb_export_storage_unavailable', __( 'Private export storage is unavailable.', 'sabri-publishing-dashboard' ), 500 );
		}
		$directory = trailingslashit( (string) $upload['basedir'] ) . 'spdb-private-exports';
		if ( ! wp_mkdir_p( $directory ) ) {
			return self::error( 'spdb_export_storage_unavailable', __( 'Private export storage could not be created.', 'sabri-publishing-dashboard' ), 500 );
		}
		@chmod( $directory, 0700 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Best-effort private-directory hardening.
		$guards = array(
			'.htaccess' => "Deny from all\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n",
			'index.php' => "<?php\nhttp_response_code( 404 );\nexit;\n",
			'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?><configuration><system.webServer><authorization><deny users=\"*\" /></authorization></system.webServer></configuration>",
		);
		foreach ( $guards as $file => $content ) {
			$path = trailingslashit( $directory ) . $file;
			if ( ! file_exists( $path ) && false === file_put_contents( $path, $content, LOCK_EX ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Required private directory guard.
				return self::error( 'spdb_export_storage_guard_failed', __( 'Private export storage could not be guarded.', 'sabri-publishing-dashboard' ), 500 );
			}
		}
		return $directory;
	}

	/** @return WP_Error */
	private function fail_processing( string $export_id, WP_Error $cause ): WP_Error {
		$transition = $this->repository->fail_export_job( $export_id, $cause->get_error_code() );
		return is_wp_error( $transition ) ? $transition : $cause;
	}

	/** @return string|WP_Error */
	private function seal_content( string $content, string $export_id ) {
		if ( ! function_exists( 'openssl_encrypt' ) || ! function_exists( 'wp_salt' ) ) {
			return self::error( 'spdb_export_encryption_unavailable', __( 'The export encryption service is unavailable.', 'sabri-publishing-dashboard' ), 503 );
		}
		try {
			$iv = random_bytes( 12 );
		} catch ( Throwable $error ) {
			return self::error( 'spdb_export_entropy_unavailable', __( 'Secure export encryption entropy is unavailable.', 'sabri-publishing-dashboard' ), 503 );
		}
		$key        = hash_hmac( 'sha256', 'file23-export|' . $export_id, wp_salt( 'secure_auth' ), true );
		$tag        = '';
		$ciphertext = openssl_encrypt( $content, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, 'file23|' . $export_id, 16 );
		if ( ! is_string( $ciphertext ) || 16 !== strlen( $tag ) ) {
			return self::error( 'spdb_export_encryption_failed', __( 'The export could not be encrypted.', 'sabri-publishing-dashboard' ), 500 );
		}
		return self::ENVELOPE_MAGIC . $iv . $tag . $ciphertext;
	}

	/** @return string|WP_Error */
	private function open_content( string $sealed, string $export_id ) {
		if ( ! function_exists( 'openssl_decrypt' ) || strlen( $sealed ) < 36 || ! hash_equals( self::ENVELOPE_MAGIC, substr( $sealed, 0, 8 ) ) ) {
			return self::error( 'spdb_export_envelope_invalid', __( 'The export encryption envelope is invalid.', 'sabri-publishing-dashboard' ), 409 );
		}
		$iv         = substr( $sealed, 8, 12 );
		$tag        = substr( $sealed, 20, 16 );
		$ciphertext = substr( $sealed, 36 );
		$key        = hash_hmac( 'sha256', 'file23-export|' . $export_id, wp_salt( 'secure_auth' ), true );
		$plaintext  = openssl_decrypt( $ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, 'file23|' . $export_id );
		return is_string( $plaintext )
			? $plaintext
			: self::error( 'spdb_export_decryption_failed', __( 'The export file could not be decrypted.', 'sabri-publishing-dashboard' ), 409 );
	}

	/** @param array<string,mixed> $job @return array<string,mixed> */
	private function calendar_model( array $job ): array {
		$result = $this->calendar->calendar( array_merge( $job['filters'], array( 'scope' => $job['scope'], 'page' => 1, 'per_page' => 200 ) ) );
		$events = array();
		foreach ( is_array( $result['items'] ?? null ) ? $result['items'] : array() as $item ) {
			$events[] = array(
				'uid'         => hash( 'sha256', $item['provider_key'] . '|' . $item['object_type'] . '|' . $item['object_id'] ) . '@sabrihomeopathy.com',
				'summary'     => $item['title'],
				'dtstart'     => $item['scheduled_at_utc'],
				'description' => $item['provider_key'] . ' / ' . $item['object_type'] . ' / ' . $item['status'],
			);
		}
		return array( 'events' => $events, 'generated_at_gmt' => gmdate( 'c' ) );
	}

	/** @param array<string,mixed> $model @return string|WP_Error */
	private function render( string $format, array $model ) {
		switch ( $format ) {
			case 'csv':
				return $this->render_csv( $model );
			case 'json':
				$json = wp_json_encode( $model, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
				return false === $json ? self::error( 'spdb_export_render_failed', __( 'The JSON export could not be generated.', 'sabri-publishing-dashboard' ), 500 ) : $json . "\n";
			case 'html':
				return $this->render_html( $model );
			case 'pdf':
				return $this->render_pdf( $model );
			case 'ics':
				return $this->render_ics( $model );
		}
		return self::error( 'spdb_export_format_invalid', __( 'The export format is invalid.', 'sabri-publishing-dashboard' ), 400 );
	}

	/** @param array<string,mixed> $model */
	private function render_csv( array $model ): string {
		$stream = fopen( 'php://temp', 'w+b' );
		if ( false === $stream ) {
			return '';
		}
		$columns = is_array( $model['columns'] ?? null ) ? $model['columns'] : array();
		$rows    = is_array( $model['rows'] ?? null ) ? $model['rows'] : array();
		fputcsv( $stream, $columns );
		foreach ( $rows as $row ) {
			$line = array();
			foreach ( $columns as $column ) {
				$value  = is_array( $row ) ? ( $row[ $column ] ?? '' ) : '';
				$line[] = is_scalar( $value ) ? self::spreadsheet_safe( (string) $value ) : '';
			}
			fputcsv( $stream, $line );
		}
		rewind( $stream );
		$content = stream_get_contents( $stream );
		fclose( $stream );
		return "\xEF\xBB\xBF" . ( is_string( $content ) ? $content : '' );
	}

	/** @param array<string,mixed> $model */
	private function render_html( array $model ): string {
		$columns = is_array( $model['columns'] ?? null ) ? $model['columns'] : array();
		$rows    = is_array( $model['rows'] ?? null ) ? $model['rows'] : array();
		$html = '<!doctype html><html><head><meta charset="utf-8"><title>File 23 Report</title><style>body{font-family:system-ui,sans-serif;margin:2rem;color:#161616}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ccc;padding:.5rem;text-align:start}th{background:#f5f5f5}</style></head><body>';
		$html .= '<h1>' . esc_html( ucwords( str_replace( '_', ' ', (string) ( $model['report_key'] ?? 'File 23 Report' ) ) ) ) . '</h1>';
		$html .= '<p>' . esc_html( (string) ( $model['limitations'] ?? '' ) ) . '</p><table><thead><tr>';
		foreach ( $columns as $column ) {
			$html .= '<th>' . esc_html( ucwords( str_replace( '_', ' ', (string) $column ) ) ) . '</th>';
		}
		$html .= '</tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$html .= '<tr>';
			foreach ( $columns as $column ) {
				$value = is_array( $row ) && is_scalar( $row[ $column ] ?? '' ) ? (string) $row[ $column ] : '';
				$html .= '<td>' . esc_html( $value ) . '</td>';
			}
			$html .= '</tr>';
		}
		return $html . '</tbody></table></body></html>';
	}

	/** @param array<string,mixed> $model */
	private function render_pdf( array $model ): string {
		$lines   = array( 'Sabri Social Homeopathy Platform', 'File 23 Report: ' . ucwords( str_replace( '_', ' ', (string) ( $model['report_key'] ?? 'report' ) ) ), 'Generated: ' . (string) ( $model['generated_at_gmt'] ?? gmdate( 'c' ) ), '' );
		$columns = is_array( $model['columns'] ?? null ) ? $model['columns'] : array();
		$rows    = is_array( $model['rows'] ?? null ) ? $model['rows'] : array();
		foreach ( array_slice( $rows, 0, 120 ) as $row ) {
			$parts = array();
			foreach ( array_slice( $columns, 0, 5 ) as $column ) {
				$value = is_array( $row ) && is_scalar( $row[ $column ] ?? '' ) ? (string) $row[ $column ] : '';
				$parts[] = ucwords( str_replace( '_', ' ', (string) $column ) ) . ': ' . $value;
			}
			$lines[] = implode( ' | ', $parts );
		}
		$pages = array_chunk( $lines, 45 );
		$objects = array();
		$page_ids = array();
		$font_id = 3;
		$next_id = 4;
		foreach ( $pages as $page_lines ) {
			$content_id = $next_id++;
			$page_id    = $next_id++;
			$page_ids[] = $page_id;
			$stream = "BT\n/F1 10 Tf\n50 790 Td\n";
			foreach ( $page_lines as $index => $line ) {
				$line = preg_replace( '/[^\x20-\x7E]/', '?', (string) $line );
				$line = substr( (string) $line, 0, 120 );
				if ( $index > 0 ) {
					$stream .= "0 -16 Td\n";
				}
				$stream .= '(' . str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $line ) . ") Tj\n";
			}
			$stream .= "ET\n";
			$objects[ $content_id ] = "<< /Length " . strlen( $stream ) . " >>\nstream\n{$stream}endstream";
			$objects[ $page_id ] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 {$font_id} 0 R >> >> /Contents {$content_id} 0 R >>";
		}
		$objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
		$kids = implode( ' ', array_map( static function ( $id ) { return $id . ' 0 R'; }, $page_ids ) );
		$objects[2] = "<< /Type /Pages /Kids [{$kids}] /Count " . count( $page_ids ) . ' >>';
		$objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
		ksort( $objects );
		$pdf = "%PDF-1.4\n";
		$offsets = array( 0 );
		$max_id = max( array_keys( $objects ) );
		for ( $id = 1; $id <= $max_id; $id++ ) {
			$offsets[ $id ] = strlen( $pdf );
			$pdf .= $id . " 0 obj\n" . ( $objects[ $id ] ?? '<<>>' ) . "\nendobj\n";
		}
		$xref = strlen( $pdf );
		$pdf .= "xref\n0 " . ( $max_id + 1 ) . "\n0000000000 65535 f \n";
		for ( $id = 1; $id <= $max_id; $id++ ) {
			$pdf .= sprintf( "%010d 00000 n \n", $offsets[ $id ] );
		}
		$pdf .= "trailer\n<< /Size " . ( $max_id + 1 ) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
		return $pdf;
	}

	/** @param array<string,mixed> $model */
	private function render_ics( array $model ): string {
		$lines = array( 'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Sabri Social Homeopathy Platform//File 23//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH' );
		foreach ( is_array( $model['events'] ?? null ) ? $model['events'] : array() as $event ) {
			$timestamp = strtotime( (string) ( $event['dtstart'] ?? '' ) );
			if ( false === $timestamp ) {
				continue;
			}
			$lines[] = 'BEGIN:VEVENT';
			$lines[] = 'UID:' . self::ics_escape( (string) ( $event['uid'] ?? wp_generate_uuid4() ) );
			$lines[] = 'DTSTAMP:' . gmdate( 'Ymd\THis\Z' );
			$lines[] = 'DTSTART:' . gmdate( 'Ymd\THis\Z', $timestamp );
			$lines[] = 'SUMMARY:' . self::ics_escape( (string) ( $event['summary'] ?? 'Scheduled publication' ) );
			$lines[] = 'DESCRIPTION:' . self::ics_escape( (string) ( $event['description'] ?? '' ) );
			$lines[] = 'END:VEVENT';
		}
		$lines[] = 'END:VCALENDAR';
		return implode( "\r\n", $lines ) . "\r\n";
	}

	/** @param array<string,mixed> $filters @return array<string,mixed> */
	private function sanitize_filters( array $filters ): array {
		$out = array();
		foreach ( array( 'date_from', 'date_to', 'provider', 'search', 'status' ) as $key ) {
			if ( ! isset( $filters[ $key ] ) || ! is_scalar( $filters[ $key ] ) ) {
				continue;
			}
			$value = trim( sanitize_text_field( (string) $filters[ $key ] ) );
			if ( strlen( $value ) <= 200 ) {
				$out[ $key ] = $value;
			}
		}
		return $out;
	}

	private static function spreadsheet_safe( string $value ): string {
		return 1 === preg_match( '/^[=+\-@]/', ltrim( $value ) ) ? "'" . $value : $value;
	}

	private static function ics_escape( string $value ): string {
		$value = preg_replace( '/[\r\n]+/', '\\n', $value );
		return str_replace( array( '\\', ';', ',' ), array( '\\\\', '\\;', '\\,' ), (string) $value );
	}

	private function mime_type( string $format ): string {
		$types = array( 'csv' => 'text/csv; charset=utf-8', 'json' => 'application/json; charset=utf-8', 'html' => 'text/html; charset=utf-8', 'pdf' => 'application/pdf', 'ics' => 'text/calendar; charset=utf-8' );
		return $types[ $format ] ?? 'application/octet-stream';
	}

	private function is_institutional( int $user_id ): bool {
		$assertions = SPDB_Membership_Guard::assertions( $user_id );
		return is_array( $assertions ) && ! empty( $assertions['institutional_account'] );
	}

	private static function error( string $code, string $message, int $status ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
