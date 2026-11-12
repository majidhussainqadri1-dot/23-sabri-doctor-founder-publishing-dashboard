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
		if ( ! $this->current_user_can_export() ) {
			return self::error( 'spdb_export_forbidden', __( 'You are not authorized to export reports.', 'sabri-publishing-dashboard' ), 403 );
		}
		$report_key = sanitize_key( (string) ( $input['report_key'] ?? '' ) );
		$format     = sanitize_key( (string) ( $input['format'] ?? 'csv' ) );
		$scope      = sanitize_key( (string) ( $input['scope'] ?? 'own' ) );
		$confirmed  = true === ( $input['confirm_export'] ?? false );
		$reason     = isset( $input['reason'] ) && is_scalar( $input['reason'] ) ? trim( sanitize_textarea_field( (string) $input['reason'] ) ) : '';
		$reason_len = function_exists( 'mb_strlen' ) ? mb_strlen( $reason ) : strlen( $reason );
		if ( ! $confirmed || $reason_len < 3 || $reason_len > 500 ) {
			return self::error( 'spdb_export_confirmation_required', __( 'Secure export requires explicit confirmation and a brief audit reason.', 'sabri-publishing-dashboard' ), 400 );
		}
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
				'reason_hash'   => hash( 'sha256', $reason ),
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
		if ( ! $this->current_user_can_export() ) {
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
		$owner_user_id = max( 0, (int) ( $job['owner_user_id'] ?? 0 ) );
		if ( ! $this->user_can_export( $owner_user_id ) ) {
			return $this->fail_processing( $export_id, self::error( 'spdb_export_authorization_revoked', __( 'Export authorization is no longer valid for the requesting account.', 'sabri-publishing-dashboard' ), 403 ) );
		}
		if ( 'institution' === (string) ( $job['scope'] ?? '' ) && ! $this->is_institutional( $owner_user_id ) ) {
			return $this->fail_processing( $export_id, self::error( 'spdb_export_scope_revoked', __( 'Institution-wide export authorization is no longer valid.', 'sabri-publishing-dashboard' ), 403 ) );
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
		$content  = $this->render( $job['format'], $model );
		if ( is_wp_error( $content ) ) {
			return $this->fail_processing( $export_id, $content );
		}
		$sealed = $this->seal_content( $content, $export_id );
		if ( is_wp_error( $sealed ) ) {
			return $this->fail_processing( $export_id, $sealed );
		}
		// A job can expire while the provider is rendering; do not write
		// new ciphertext for an already expired retention-bound export.
		if ( '' !== $job['expires_at_gmt'] && strtotime( $job['expires_at_gmt'] . ' UTC' ) <= time() ) {
			return $this->fail_processing( $export_id, self::error( 'spdb_export_expired', __( 'The export expired before its file could be written.', 'sabri-publishing-dashboard' ), 410 ) );
		}
		// An erasure can begin while a queued job is rendering. Re-check
		// the durable owner lock immediately before any ciphertext write.
		if ( self::privacy_erasure_pending( $owner_user_id ) ) {
			return $this->fail_processing( $export_id, self::error( 'spdb_export_erasure_pending', __( 'Owner privacy erasure blocks export generation.', 'sabri-publishing-dashboard' ), 409 ) );
		}
		// Exclusive create prevents retry/symlink overwrite of an existing
		// ciphertext path. The host must still independently deny HTTP access.
		$path = $this->write_ciphertext_exclusive( $directory, $filename, $sealed );
		if ( is_wp_error( $path ) ) {
			return $this->fail_processing( $export_id, $path );
		}
		// A privacy eraser may set its lock between the pre-write check
		// and the exclusive create. Do not publish or retain that artifact.
		if ( self::privacy_erasure_pending( $owner_user_id ) ) {
			@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.unlink_unlink -- Prevent new ciphertext while erasure is pending.
			return $this->fail_processing( $export_id, self::error( 'spdb_export_erasure_pending', __( 'Owner privacy erasure blocks export generation.', 'sabri-publishing-dashboard' ), 409 ) );
		}
		if ( '' !== $job['expires_at_gmt'] && strtotime( $job['expires_at_gmt'] . ' UTC' ) <= time() ) {
			@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.unlink_unlink -- Prevent a late-generated expired ciphertext.
			return $this->fail_processing( $export_id, self::error( 'spdb_export_expired', __( 'The export expired while generating its private file.', 'sabri-publishing-dashboard' ), 410 ) );
		}
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

	/**
	 * Create new encrypted report bytes without following an existing symlink
	 * or replacing an existing export. A normal fopen/write mode would overwrite
	 * files under a reused export ID and could follow a hostile filesystem link.
	 *
	 * @return string|WP_Error Absolute verified artifact path.
	 */
	private function write_ciphertext_exclusive( string $directory, string $filename, string $content ) {
		if ( 1 !== preg_match( '/^export_[a-z0-9]{32}\\.spdb$/', $filename ) ) {
			return self::error( 'spdb_export_file_name_invalid', __( 'The private export filename is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		$real_dir = realpath( $directory );
		if ( false === $real_dir || ! is_dir( $real_dir ) ) {
			return self::error( 'spdb_export_storage_unavailable', __( 'The private export directory is unavailable.', 'sabri-publishing-dashboard' ), 500 );
		}
		$path = trailingslashit( $real_dir ) . $filename;
		if ( is_link( $path ) || file_exists( $path ) ) {
			return self::error( 'spdb_export_file_exists', __( 'An export artifact already exists; overwrite is forbidden.', 'sabri-publishing-dashboard' ), 409 );
		}
		$stream = @fopen( $path, 'xb' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Atomic exclusive local encrypted artifact creation.
		if ( false === $stream ) {
			return self::error( 'spdb_export_file_write_failed', __( 'The encrypted export artifact could not be created exclusively.', 'sabri-publishing-dashboard' ), 500 );
		}
		$size = strlen( $content );
		$offset = 0;
		while ( $offset < $size ) {
			$written = @fwrite( $stream, substr( $content, $offset ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Verify partial write count.
			if ( ! is_int( $written ) || $written <= 0 ) {
				break;
			}
			$offset += $written;
		}
		$flushed = @fflush( $stream ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Verify generated encrypted file is flushed.
		$closed = @fclose( $stream ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Verify generated encrypted file is closed.
		if ( $offset !== $size || ! $flushed || ! $closed || ! @chmod( $path, 0600 ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Fail closed if private permissions cannot be set.
			@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove incomplete private export.
			return self::error( 'spdb_export_file_write_failed', __( 'The encrypted export could not be stored safely.', 'sabri-publishing-dashboard' ), 500 );
		}
		$real = realpath( $path );
		if ( false === $real || 0 !== strpos( $real, trailingslashit( $real_dir ) ) || ! is_file( $real ) ) {
			@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove invalid private export.
			return self::error( 'spdb_export_file_path_invalid', __( 'The encrypted export path failed containment verification.', 'sabri-publishing-dashboard' ), 409 );
		}
		return $real;
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
		if ( ! $this->current_user_can_export() ) {
			wp_die( esc_html__( 'Your current account state is not authorized to download exports.', 'sabri-publishing-dashboard' ), esc_html__( 'Export unavailable', 'sabri-publishing-dashboard' ), array( 'response' => 403 ) );
		}
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


	/**
	 * Delete expired encrypted export artifacts before their metadata rows are purged.
	 * Returning an error deliberately prevents metadata deletion so the cleanup can retry.
	 *
	 * @return int|WP_Error
	 */
	public function cleanup_expired_files() {
		$directory = $this->private_directory();
		if ( is_wp_error( $directory ) ) {
			return $directory;
		}
		$real_dir = realpath( $directory );
		if ( false === $real_dir ) {
			return self::error( 'spdb_export_cleanup_storage_invalid', __( 'The private export directory could not be resolved for retention cleanup.', 'sabri-publishing-dashboard' ), 500 );
		}
		global $wpdb;
		$table    = SPDB_Operations_Schema::table( 'export_jobs' );
		$deleted  = 0;
		$cursor   = 0;
		$now      = current_time( 'mysql', true );
		$finished = false;
		for ( $batch = 0; $batch < 100; ++$batch ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare( "SELECT id, storage_ref FROM {$table} WHERE expires_at_gmt <= %s AND id > %d ORDER BY id ASC LIMIT 200", $now, $cursor ),
				defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A'
			);
			if ( ! is_array( $rows ) ) {
				return self::error( 'spdb_export_cleanup_read_failed', __( 'Expired export artifacts could not be enumerated safely.', 'sabri-publishing-dashboard' ), 500 );
			}
			if ( ! $rows ) {
				$finished = true;
				break;
			}
			foreach ( $rows as $row ) {
				$id = (int) ( $row['id'] ?? 0 );
				if ( $id <= $cursor ) {
					return self::error( 'spdb_export_cleanup_cursor_invalid', __( 'Expired export artifacts could not be enumerated in a safe order.', 'sabri-publishing-dashboard' ), 409 );
				}
				$cursor = $id;
				$storage_ref = (string) ( $row['storage_ref'] ?? '' );
				if ( '' === $storage_ref ) {
					continue;
				}
				if ( 1 !== preg_match( '/^export_[a-z0-9]{32}\\.spdb$/', $storage_ref ) ) {
					return self::error( 'spdb_export_cleanup_reference_invalid', __( 'An expired export storage reference failed validation.', 'sabri-publishing-dashboard' ), 409 );
				}
				$path = trailingslashit( $directory ) . $storage_ref;
				// realpath() follows symlinks: unlinking the resolved target could
				// delete another export instead of this expired reference.
				if ( is_link( $path ) ) {
					return self::error( 'spdb_export_cleanup_path_invalid', __( 'An expired export artifact failed path containment validation.', 'sabri-publishing-dashboard' ), 409 );
				}
				if ( ! file_exists( $path ) ) {
					continue;
				}
				$real = realpath( $path );
				if ( false === $real || 0 !== strpos( $real, trailingslashit( $real_dir ) ) || ! is_file( $real ) ) {
					return self::error( 'spdb_export_cleanup_path_invalid', __( 'An expired export artifact failed path containment validation.', 'sabri-publishing-dashboard' ), 409 );
				}
				if ( ! unlink( $path ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Delete only the validated File23 ciphertext path, never a resolved symlink target.
					return self::error( 'spdb_export_cleanup_delete_failed', __( 'An expired export artifact could not be deleted; metadata retention cleanup was stopped for retry.', 'sabri-publishing-dashboard' ), 500 );
				}
				++$deleted;
			}
			if ( count( $rows ) < 200 ) {
				$finished = true;
				break;
			}
		}
		if ( ! $finished ) {
			return self::error( 'spdb_export_cleanup_batch_incomplete', __( 'Expired export cleanup reached its safe batch limit; metadata retention cleanup must not run before a controlled continuation.', 'sabri-publishing-dashboard' ), 409 );
		}
		return $deleted;
	}

	/**
	 * Bounded expiry cleanup for a frozen UTC cutoff; caller keeps the
	 * continuation cursor in a server-owned background-job payload.
	 *
	 * This method never deletes the corresponding database rows. The caller
	 * MUST complete an independent verification pass with the exact same
	 * cutoff and MUST NOT run metadata retention cleanup on partial state.
	 *
	 * @return array{deleted:int,next_cursor:int,done:bool}|WP_Error
	 */
	public function cleanup_expired_files_page( string $cutoff, int $after_id = 0, int $limit = 200, bool $verify_only = false ) {
		if ( $after_id < 0 || $limit < 1 || $limit > 200 ||
			1 !== preg_match( '/^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}$/', $cutoff ) ||
			false === strtotime( $cutoff . ' UTC' ) || $cutoff > current_time( 'mysql', true ) ) {
			return self::error( 'spdb_export_cleanup_page_invalid', __( 'The expired-export cleanup checkpoint is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		$directory = $this->private_directory();
		if ( is_wp_error( $directory ) ) {
			return $directory;
		}
		$real_dir = realpath( $directory );
		if ( false === $real_dir || ! is_dir( $real_dir ) ) {
			return self::error( 'spdb_export_cleanup_storage_invalid', __( 'The private export directory is unavailable.', 'sabri-publishing-dashboard' ), 500 );
		}
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'export_jobs' );
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT id, storage_ref, status FROM {$table} WHERE expires_at_gmt <= %s AND id > %d ORDER BY id ASC LIMIT %d", $cutoff, $after_id, $limit ),
			defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A'
		);
		if ( ! is_array( $rows ) ) {
			return self::error( 'spdb_export_cleanup_read_failed', __( 'Expired export rows could not be inspected.', 'sabri-publishing-dashboard' ), 500 );
		}
		$deleted = 0;
		$cursor = $after_id;
		foreach ( $rows as $row ) {
			$id = (int) ( $row['id'] ?? 0 );
			if ( $id <= $cursor ) {
				return self::error( 'spdb_export_cleanup_cursor_invalid', __( 'Expired export rows have an invalid cursor order.', 'sabri-publishing-dashboard' ), 409 );
			}
			$cursor = $id;
			if ( in_array( (string) ( $row['status'] ?? '' ), array( 'queued', 'processing' ), true ) ) {
				return self::error( 'spdb_export_cleanup_active_job', __( 'An expired export job is still active; metadata retention is deferred.', 'sabri-publishing-dashboard' ), 409 );
			}
			$storage_ref = (string) ( $row['storage_ref'] ?? '' );
			if ( '' === $storage_ref ) {
				continue;
			}
			if ( 1 !== preg_match( '/^export_[a-z0-9]{32}\\.spdb$/', $storage_ref ) ) {
				return self::error( 'spdb_export_cleanup_reference_invalid', __( 'An expired export storage reference is invalid.', 'sabri-publishing-dashboard' ), 409 );
			}
			$path = trailingslashit( $directory ) . $storage_ref;
			if ( is_link( $path ) ) {
				return self::error( 'spdb_export_cleanup_path_invalid', __( 'An expired export artifact is a symlink.', 'sabri-publishing-dashboard' ), 409 );
			}
			if ( ! file_exists( $path ) ) {
				continue;
			}
			$real = realpath( $path );
			if ( false === $real || 0 !== strpos( $real, trailingslashit( $real_dir ) ) || ! is_file( $real ) ) {
				return self::error( 'spdb_export_cleanup_path_invalid', __( 'An expired export artifact failed private-path containment.', 'sabri-publishing-dashboard' ), 409 );
			}
			if ( $verify_only ) {
				return self::error( 'spdb_export_cleanup_verification_failed', __( 'An expired export artifact remains after cleanup.', 'sabri-publishing-dashboard' ), 409 );
			}
			if ( ! unlink( $path ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Only remove validated expired File23 ciphertext path.
				return self::error( 'spdb_export_cleanup_delete_failed', __( 'An expired export file could not be deleted.', 'sabri-publishing-dashboard' ), 500 );
			}
			++$deleted;
		}
		return array(
			'deleted' => $deleted,
			'next_cursor' => $cursor,
			'done' => count( $rows ) < $limit,
		);
	}

	/**
	 * Bounded owner-specific ciphertext erasure or independent verification.
	 *
	 * Unlike the legacy one-call method, this returns a server-owned
	 * continuation cursor so WordPress privacy erasure can span >20,000 rows
	 * without deleting metadata prematurely. The caller MUST complete a
	 * second verification pass from cursor zero before deleting job records.
	 * The owner-scoped cursor is only an offset; it grants no authority.
	 *
	 * @return array{deleted:int,next_cursor:int,done:bool}|WP_Error
	 */
	public function erase_user_files_page( int $user_id, int $after_id = 0, int $limit = 200, bool $verify_only = false ) {
		if ( $user_id < 1 || $after_id < 0 || $limit < 1 || $limit > 200 ) {
			return self::error( 'spdb_export_erase_page_invalid', __( 'Private export erasure pagination is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		$directory = $this->private_directory();
		if ( is_wp_error( $directory ) ) {
			return self::error( 'spdb_export_erase_storage_unavailable', __( 'Private export storage could not be inspected for erasure.', 'sabri-publishing-dashboard' ), 500 );
		}
		$real_dir = realpath( $directory );
		if ( false === $real_dir || ! is_dir( $real_dir ) ) {
			return self::error( 'spdb_export_erase_storage_unavailable', __( 'Private export storage could not be resolved for erasure.', 'sabri-publishing-dashboard' ), 500 );
		}
		global $wpdb;
		$table = SPDB_Operations_Schema::table( 'export_jobs' );
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT id, storage_ref FROM {$table} WHERE owner_user_id = %d AND id > %d ORDER BY id ASC LIMIT %d", $user_id, $after_id, $limit ),
			defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A'
		);
		if ( ! is_array( $rows ) ) {
			return self::error( 'spdb_export_erase_read_failed', __( 'Private export metadata could not be enumerated.', 'sabri-publishing-dashboard' ), 500 );
		}
		$deleted = 0;
		$cursor = $after_id;
		foreach ( $rows as $row ) {
			$id = (int) ( $row['id'] ?? 0 );
			if ( $id <= $cursor ) {
				return self::error( 'spdb_export_erase_cursor_invalid', __( 'Private export metadata did not maintain a strict owner-specific cursor.', 'sabri-publishing-dashboard' ), 409 );
			}
			$cursor = $id;
			$storage_ref = (string) ( $row['storage_ref'] ?? '' );
			if ( '' === $storage_ref ) {
				continue; // Queued, incomplete or failed job has no published ciphertext reference.
			}
			if ( 1 !== preg_match( '/^export_[a-z0-9]{32}\\.spdb$/', $storage_ref ) ) {
				return self::error( 'spdb_export_erase_reference_invalid', __( 'A private export metadata reference is invalid.', 'sabri-publishing-dashboard' ), 409 );
			}
			$path = trailingslashit( $directory ) . $storage_ref;
			if ( is_link( $path ) ) {
				return self::error( 'spdb_export_erase_path_invalid', __( 'A private export artifact is a symlink.', 'sabri-publishing-dashboard' ), 409 );
			}
			if ( ! file_exists( $path ) ) {
				continue; // Already removed file: safe to retry or verify.
			}
			$real = realpath( $path );
			if ( false === $real || 0 !== strpos( $real, trailingslashit( $real_dir ) ) || ! is_file( $real ) ) {
				return self::error( 'spdb_export_erase_path_invalid', __( 'A private export artifact failed containment verification.', 'sabri-publishing-dashboard' ), 409 );
			}
			if ( $verify_only ) {
				return self::error( 'spdb_export_erase_verification_failed', __( 'A private export artifact still exists after the erasure pass.', 'sabri-publishing-dashboard' ), 409 );
			}
			if ( ! unlink( $path ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Delete only a validated File23 ciphertext path, never a resolved symlink target.
				return self::error( 'spdb_export_erase_delete_failed', __( 'Private export deletion failed and metadata must be retained.', 'sabri-publishing-dashboard' ), 500 );
			}
			++$deleted;
		}
		return array(
			'deleted' => $deleted,
			'next_cursor' => $cursor,
			'done' => count( $rows ) < $limit,
		);
	}

	/**
	 * Erase generated ciphertext files for exactly one owner, before the
	 * corresponding metadata is removed by the WordPress privacy eraser.
	 *
	 * Returning zero for a storage/DB/delete failure is unsafe: the caller
	 * would erase the only remaining reference to an orphaned ciphertext.
	 * Never delete metadata unless this enumeration completed successfully.
	 *
	 * @return int|WP_Error
	 */
	public function erase_user_files( int $user_id ) {
		if ( $user_id < 1 ) {
			return self::error( 'spdb_export_erase_user_invalid', __( 'The export erasure account is invalid.', 'sabri-publishing-dashboard' ), 400 );
		}
		$directory = $this->private_directory();
		if ( is_wp_error( $directory ) ) {
			return self::error( 'spdb_export_erase_storage_unavailable', __( 'Private export storage could not be inspected for erasure.', 'sabri-publishing-dashboard' ), 500 );
		}
		$real_dir = realpath( $directory );
		if ( false === $real_dir ) {
			return self::error( 'spdb_export_erase_storage_unavailable', __( 'Private export storage could not be resolved for erasure.', 'sabri-publishing-dashboard' ), 500 );
		}
		global $wpdb;
		$table    = SPDB_Operations_Schema::table( 'export_jobs' );
		$deleted  = 0;
		$cursor   = 0;
		$finished = false;
		for ( $batch = 0; $batch < 100; ++$batch ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare( "SELECT id, storage_ref FROM {$table} WHERE owner_user_id = %d AND id > %d ORDER BY id ASC LIMIT 200", $user_id, $cursor ),
				defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A'
			);
			if ( ! is_array( $rows ) ) {
				return self::error( 'spdb_export_erase_read_failed', __( 'Private export metadata could not be inspected for erasure.', 'sabri-publishing-dashboard' ), 500 );
			}
			if ( ! $rows ) {
				$finished = true;
				break;
			}
			foreach ( $rows as $row ) {
				$id = (int) ( $row['id'] ?? 0 );
				if ( $id <= $cursor ) {
					return self::error( 'spdb_export_erase_cursor_invalid', __( 'Private export metadata could not be enumerated safely.', 'sabri-publishing-dashboard' ), 409 );
				}
				$cursor = $id;
				$storage_ref = (string) ( $row['storage_ref'] ?? '' );
				// Empty reference means a queued/failed export has no artifact.
				if ( '' === $storage_ref ) {
					continue;
				}
				if ( 1 !== preg_match( '/^export_[a-z0-9]{32}\\.spdb$/', $storage_ref ) ) {
					return self::error( 'spdb_export_erase_reference_invalid', __( 'Private export storage has an invalid reference.', 'sabri-publishing-dashboard' ), 409 );
				}
				$path = trailingslashit( $directory ) . $storage_ref;
				if ( is_link( $path ) ) {
					return self::error( 'spdb_export_erase_path_invalid', __( 'A private export artifact failed containment verification.', 'sabri-publishing-dashboard' ), 409 );
				}
				if ( ! file_exists( $path ) ) {
					continue;
				}
				$real = realpath( $path );
				if ( false === $real || 0 !== strpos( $real, trailingslashit( $real_dir ) ) || ! is_file( $real ) ) {
					return self::error( 'spdb_export_erase_path_invalid', __( 'A private export artifact failed containment verification.', 'sabri-publishing-dashboard' ), 409 );
				}
				if ( ! unlink( $real ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Erase a verified File 23-owned encrypted export.
					return self::error( 'spdb_export_erase_delete_failed', __( 'Private export deletion failed; metadata erasure is deferred for retry.', 'sabri-publishing-dashboard' ), 500 );
				}
				++$deleted;
			}
			if ( count( $rows ) < 200 ) {
				$finished = true;
				break;
			}
		}
		if ( ! $finished ) {
			return self::error( 'spdb_export_erase_batch_incomplete', __( 'Private export erasure reached its safe batch limit and requires another pass before metadata deletion.', 'sabri-publishing-dashboard' ), 409 );
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
		$viewer_user_id = function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0;
		if (
			'ready' === $job['status']
			&& '' !== $job['storage_ref']
			&& strtotime( $job['expires_at_gmt'] . ' UTC' ) > time()
			&& $viewer_user_id > 0
			&& $viewer_user_id === (int) $job['owner_user_id']
			&& ! self::privacy_erasure_pending( $viewer_user_id )
		) {
			$out['download_url'] = $this->download_url( $job['export_id'], $viewer_user_id );
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
			// A pre-existing, replaced or symlinked guard is not evidence of
			// protection. Fail closed instead of serving private export data.
			if ( is_link( $path ) ) {
				return self::error( 'spdb_export_storage_guard_invalid', __( 'Private export storage has an invalid protection file.', 'sabri-publishing-dashboard' ), 500 );
			}
			if ( ! file_exists( $path ) && false === file_put_contents( $path, $content, LOCK_EX ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Required private directory guard.
				return self::error( 'spdb_export_storage_guard_failed', __( 'Private export storage could not be guarded.', 'sabri-publishing-dashboard' ), 500 );
			}
			if ( ! is_file( $path ) || ! is_readable( $path ) ) {
				return self::error( 'spdb_export_storage_guard_invalid', __( 'Private export storage has an invalid protection file.', 'sabri-publishing-dashboard' ), 500 );
			}
			$actual = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Verify the installed server guard exactly.
			if ( ! is_string( $actual ) || ! hash_equals( $content, $actual ) ) {
				return self::error( 'spdb_export_storage_guard_invalid', __( 'Private export storage has an invalid protection file.', 'sabri-publishing-dashboard' ), 500 );
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


	/** Durable, File23-only owner-specific erasure checkpoint. No browser controls this key. */
	public static function privacy_erasure_checkpoint_key( int $user_id ): string {
		return 'spdb_export_erase_progress_v1_' . max( 0, $user_id );
	}

	/** MySQL advisory-lock identifier shared by owner erasure request pages. */
	public static function privacy_erasure_lock_name( int $user_id ): string {
		return 'spdb_privacy_' . substr( hash( 'sha256', 'file23|export|owner|' . max( 0, $user_id ) ), 0, 40 );
	}

	/** The presence of any checkpoint blocks new/queued private report generation. */
	public static function privacy_erasure_pending( int $user_id ): bool {
		return $user_id > 0 && function_exists( 'get_option' )
			&& false !== get_option( self::privacy_erasure_checkpoint_key( $user_id ), false );
	}

	private function current_user_can_export(): bool {
		if ( ! function_exists( 'get_current_user_id' ) ) {
			return false;
		}
		return $this->user_can_export( (int) get_current_user_id(), true );
	}

	private function user_can_export( int $user_id, bool $require_session_two_factor = false ): bool {
		if ( $user_id < 1 || self::privacy_erasure_pending( $user_id ) || ! class_exists( 'SPDB_Membership_Guard' ) || ! class_exists( 'SPDB_Capabilities' ) ) {
			return false;
		}
		$assertions = SPDB_Membership_Guard::assertions( $user_id );
		if (
			! is_array( $assertions )
			|| true !== ( $assertions['approved'] ?? false )
			|| true !== ( $assertions['eligible'] ?? false )
			|| true === ( $assertions['suspended'] ?? true )
			|| ( $require_session_two_factor && true !== ( $assertions['session_two_factor'] ?? false ) )
		) {
			return false;
		}
		if ( function_exists( 'user_can' ) ) {
			return user_can( $user_id, 'spdb_export_reports' );
		}
		return function_exists( 'get_current_user_id' )
			&& $user_id === (int) get_current_user_id()
			&& SPDB_Capabilities::current_user_can( 'spdb_export_reports' );
	}

	private function is_institutional( int $user_id ): bool {
		$assertions = SPDB_Membership_Guard::assertions( $user_id );
		return is_array( $assertions ) && ! empty( $assertions['institutional_account'] );
	}

	private static function error( string $code, string $message, int $status ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
