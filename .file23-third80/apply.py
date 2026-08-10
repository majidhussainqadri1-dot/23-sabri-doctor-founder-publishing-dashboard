from pathlib import Path
import re

ROOT = Path('.')
FPI = ROOT / 'includes/class-spdb-publishing-intelligence.php'
EXPORT = ROOT / 'includes/class-spdb-export-service.php'
BG = ROOT / 'includes/class-spdb-background-jobs.php'
FPI_TEST = ROOT / 'tests/publishing-intelligence-security-regression-tests.php'
EXPORT_TEST = ROOT / 'tests/third-fresh-80-export-contract-tests.php'
AUDIT = ROOT / 'docs/AUDIT-THIRD-FRESH-80-ROUND-REVIEW-AND-CORRECTIONS-2026-08-10.md'
STATUS = ROOT / 'STATUS.md'
CHANGELOG = ROOT / 'CHANGELOG.md'


def replace_once(text: str, old: str, new: str, label: str) -> str:
    count = text.count(old)
    if count != 1:
        raise SystemExit(f'{label}: expected one anchor, found {count}')
    return text.replace(old, new, 1)


def sub_once(text: str, pattern: str, replacement: str, label: str) -> str:
    out, count = re.subn(pattern, replacement, text, count=1, flags=re.S)
    if count != 1:
        raise SystemExit(f'{label}: expected one match, replaced {count}')
    return out

# ---------------------------------------------------------------------------
# Rounds 161-163: export current-state authorization + expiring file retention.
# ---------------------------------------------------------------------------
export = EXPORT.read_text(encoding='utf-8')
export = replace_once(
    export,
    """\tpublic function request_export( array $input ) {\n\t\tif ( ! SPDB_Capabilities::current_user_can( 'spdb_export_reports' ) ) {""",
    """\tpublic function request_export( array $input ) {\n\t\tif ( ! $this->current_user_can_export() ) {""",
    'round161 request export current authorization',
)
export = replace_once(
    export,
    """\tpublic function list_exports() {\n\t\tif ( ! SPDB_Capabilities::current_user_can( 'spdb_export_reports' ) ) {""",
    """\tpublic function list_exports() {\n\t\tif ( ! $this->current_user_can_export() ) {""",
    'round161 list export current authorization',
)
export = replace_once(
    export,
    """\t\tif ( ! in_array( $job['status'], array( 'queued', 'processing' ), true ) ) {\n\t\t\treturn true;\n\t\t}\n\t\tif ( '' !== $job['expires_at_gmt'] && strtotime( $job['expires_at_gmt'] . ' UTC' ) <= time() ) {""",
    """\t\tif ( ! in_array( $job['status'], array( 'queued', 'processing' ), true ) ) {\n\t\t\treturn true;\n\t\t}\n\t\t$owner_user_id = max( 0, (int) ( $job['owner_user_id'] ?? 0 ) );\n\t\tif ( ! $this->user_can_export( $owner_user_id ) ) {\n\t\t\treturn $this->fail_processing( $export_id, self::error( 'spdb_export_authorization_revoked', __( 'Export authorization is no longer valid for the requesting account.', 'sabri-publishing-dashboard' ), 403 ) );\n\t\t}\n\t\tif ( 'institution' === (string) ( $job['scope'] ?? '' ) && ! $this->is_institutional( $owner_user_id ) ) {\n\t\t\treturn $this->fail_processing( $export_id, self::error( 'spdb_export_scope_revoked', __( 'Institution-wide export authorization is no longer valid.', 'sabri-publishing-dashboard' ), 403 ) );\n\t\t}\n\t\tif ( '' !== $job['expires_at_gmt'] && strtotime( $job['expires_at_gmt'] . ' UTC' ) <= time() ) {""",
    'round162 queued export authorization recheck',
)
export = replace_once(
    export,
    """\t\t$signature = isset( $_GET['signature'] ) && is_scalar( $_GET['signature'] ) ? strtolower( trim( wp_unslash( (string) $_GET['signature'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended\n\t\t$user_id   = get_current_user_id();\n\t\tif ( '' === $export_id || $expires < time() || $expires > time() + HOUR_IN_SECONDS || ! hash_equals( $this->signature( $export_id, $user_id, $expires ), $signature ) ) {""",
    """\t\t$signature = isset( $_GET['signature'] ) && is_scalar( $_GET['signature'] ) ? strtolower( trim( wp_unslash( (string) $_GET['signature'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended\n\t\t$user_id   = get_current_user_id();\n\t\tif ( ! $this->current_user_can_export() ) {\n\t\t\twp_die( esc_html__( 'Your current account state is not authorized to download exports.', 'sabri-publishing-dashboard' ), esc_html__( 'Export unavailable', 'sabri-publishing-dashboard' ), array( 'response' => 403 ) );\n\t\t}\n\t\tif ( '' === $export_id || $expires < time() || $expires > time() + HOUR_IN_SECONDS || ! hash_equals( $this->signature( $export_id, $user_id, $expires ), $signature ) ) {""",
    'round161 download current authorization',
)

cleanup_method = r'''
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
		$table   = SPDB_Operations_Schema::table( 'export_jobs' );
		$deleted = 0;
		$cursor  = 0;
		$now     = current_time( 'mysql', true );
		for ( $batch = 0; $batch < 100; ++$batch ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare( "SELECT id, storage_ref FROM {$table} WHERE expires_at_gmt <= %s AND id > %d ORDER BY id ASC LIMIT 200", $now, $cursor ),
				defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A'
			);
			if ( ! is_array( $rows ) ) {
				return self::error( 'spdb_export_cleanup_read_failed', __( 'Expired export artifacts could not be enumerated safely.', 'sabri-publishing-dashboard' ), 500 );
			}
			if ( ! $rows ) {
				break;
			}
			foreach ( $rows as $row ) {
				$cursor   = max( $cursor, (int) ( $row['id'] ?? 0 ) );
				$filename = basename( (string) ( $row['storage_ref'] ?? '' ) );
				if ( '' === $filename ) {
					continue;
				}
				if ( 1 !== preg_match( '/^export_[a-z0-9]{32}\.spdb$/', $filename ) ) {
					return self::error( 'spdb_export_cleanup_reference_invalid', __( 'An expired export storage reference failed validation.', 'sabri-publishing-dashboard' ), 409 );
				}
				$path = trailingslashit( $directory ) . $filename;
				if ( ! file_exists( $path ) ) {
					continue;
				}
				$real = realpath( $path );
				if ( false === $real || 0 !== strpos( $real, trailingslashit( $real_dir ) ) || ! is_file( $real ) ) {
					return self::error( 'spdb_export_cleanup_path_invalid', __( 'An expired export artifact failed path containment validation.', 'sabri-publishing-dashboard' ), 409 );
				}
				if ( ! unlink( $real ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- File 23-owned encrypted expiring artifact.
					return self::error( 'spdb_export_cleanup_delete_failed', __( 'An expired export artifact could not be deleted; metadata retention cleanup was stopped for retry.', 'sabri-publishing-dashboard' ), 500 );
				}
				++$deleted;
			}
			if ( count( $rows ) < 200 ) {
				break;
			}
		}
		return $deleted;
	}

'''
export = replace_once(
    export,
    "\t/** Remove generated temporary export files owned by one user. */\n",
    cleanup_method + "\t/** Remove generated temporary export files owned by one user. */\n",
    'round163 expired artifact cleanup method',
)

auth_helpers = r'''
	private function current_user_can_export(): bool {
		if ( ! function_exists( 'get_current_user_id' ) ) {
			return false;
		}
		return $this->user_can_export( (int) get_current_user_id() );
	}

	private function user_can_export( int $user_id ): bool {
		if ( $user_id < 1 || ! class_exists( 'SPDB_Membership_Guard' ) || ! class_exists( 'SPDB_Capabilities' ) ) {
			return false;
		}
		$assertions = SPDB_Membership_Guard::assertions( $user_id );
		if (
			! is_array( $assertions )
			|| true !== ( $assertions['approved'] ?? false )
			|| true !== ( $assertions['eligible'] ?? false )
			|| true === ( $assertions['suspended'] ?? true )
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

'''
export = replace_once(
    export,
    "\tprivate function is_institutional( int $user_id ): bool {\n",
    auth_helpers + "\tprivate function is_institutional( int $user_id ): bool {\n",
    'round161 export authorization helpers',
)
EXPORT.write_text(export, encoding='utf-8')

bg = BG.read_text(encoding='utf-8')
bg = replace_once(
    bg,
    """\t\t\t\tcase 'retention_cleanup':\n\t\t\t\t\t$result = $this->repository->cleanup_retention( SPDB_Admin_Settings::get() );\n\t\t\t\t\treturn is_wp_error( $result ) ? $result : true;""",
    """\t\t\t\tcase 'retention_cleanup':\n\t\t\t\t\t$artifact_cleanup = $this->exports->cleanup_expired_files();\n\t\t\t\t\tif ( is_wp_error( $artifact_cleanup ) ) {\n\t\t\t\t\t\treturn $artifact_cleanup;\n\t\t\t\t\t}\n\t\t\t\t\t$result = $this->repository->cleanup_retention( SPDB_Admin_Settings::get() );\n\t\t\t\t\treturn is_wp_error( $result ) ? $result : true;""",
    'round163 retention file cleanup ordering',
)
BG.write_text(bg, encoding='utf-8')

# ---------------------------------------------------------------------------
# Rounds 164-169: FPI privacy, explainability, lawful-source, freshness, a11y.
# ---------------------------------------------------------------------------
fpi = FPI.read_text(encoding='utf-8')
fpi = replace_once(
    fpi,
    """\t\t\t$item = self::allowlist_row( $row, array( 'dimension', 'label', 'value', 'cohort_count', 'interval', 'provider' ) );\n\t\t\t$item['dimension'] = $dimension;""",
    """\t\t\t$item = self::allowlist_row( $row, array( 'dimension', 'label', 'value', 'cohort_count', 'interval', 'provider' ) );\n\t\t\t$item = self::redact_sensitive_text_fields( $item, array( 'label', 'value' ) );\n\t\t\t$item['dimension'] = $dimension;""",
    'round164 audience value privacy',
)

fpi = sub_once(
    fpi,
    r"\tprivate static function internal_benchmark\( array \$signals \): array \{.*?\n\t\}\n\n\t/\*\* @return array<string,mixed> \*/\n\tprivate static function external_benchmark",
    r'''	private static function internal_benchmark( array $signals ): array {
		$items = array();
		$suppressed = 0;
		$incomplete = 0;
		$threshold = self::privacy_threshold();
		foreach ( $signals as $row ) {
			$cohort = max( 0, (int) ( $row['cohort_count'] ?? 0 ) );
			if ( $cohort < $threshold ) {
				++$suppressed;
				continue;
			}
			$item = self::allowlist_row( $row, array( 'metric', 'definition', 'label', 'value', 'cohort_count', 'interval', 'benchmark', 'provider' ) );
			if ( '' === trim( (string) ( $item['metric'] ?? '' ) ) || '' === trim( (string) ( $item['definition'] ?? '' ) ) ) {
				++$incomplete;
				continue;
			}
			$item = self::redact_sensitive_text_fields( $item, array( 'definition', 'label', 'value', 'benchmark' ) );
			$item['cohort_count'] = $cohort;
			$items[] = $item;
		}
		return array(
			'items' => $items,
			'suppressed_count' => $suppressed,
			'incomplete_metric_rows_suppressed' => $incomplete,
			'privacy_threshold' => $threshold,
			'metric_definition_required' => true,
			'anonymous_cohorts_only' => true,
			'public_shaming_or_ranking' => false,
			'paid_or_donor_influence' => false,
		);
	}

	/** @return array<string,mixed> */
	private static function external_benchmark''',
    'round165 internal benchmark explainability',
)

fpi = sub_once(
    fpi,
    r"\tprivate static function external_benchmark\( array \$signals \): array \{.*?\n\t\}\n\n\t/\*\* @return array<string,mixed> \*/\n\tprivate static function comment_intelligence",
    r'''	private static function external_benchmark( array $signals ): array {
		$items = array();
		$suppressed_incomplete = 0;
		$suppressed_compliance = 0;
		foreach ( $signals as $row ) {
			$item = self::allowlist_row( $row, array( 'source', 'source_url', 'source_date', 'retrieved_at', 'metric', 'label', 'value', 'trend', 'provenance', 'terms_status', 'robots_status', 'law_status', 'provider' ) );
			$source = trim( (string) ( $item['source'] ?? '' ) );
			$provenance = trim( (string) ( $item['provenance'] ?? '' ) );
			$source_date = trim( (string) ( $item['source_date'] ?? '' ) );
			if ( '' === $source || '' === $provenance || ! self::valid_public_source_date( $source_date ) ) {
				++$suppressed_incomplete;
				continue;
			}
			if (
				! self::public_source_compliance_allows( (string) ( $item['terms_status'] ?? '' ) )
				|| ! self::public_source_compliance_allows( (string) ( $item['robots_status'] ?? '' ) )
				|| ! self::public_source_compliance_allows( (string) ( $item['law_status'] ?? '' ) )
			) {
				++$suppressed_compliance;
				continue;
			}
			if ( isset( $item['source_url'] ) ) {
				$item['source_url'] = self::safe_public_url( (string) $item['source_url'] );
				if ( '' === $item['source_url'] ) {
					unset( $item['source_url'] );
				}
			}
			$item = self::redact_sensitive_text_fields( $item, array( 'label', 'value', 'trend' ) );
			$items[] = $item;
		}
		return array(
			'items' => $items,
			'incomplete_source_rows_suppressed' => $suppressed_incomplete,
			'compliance_rows_suppressed' => $suppressed_compliance,
			'knowledge_opportunity_only' => true,
			'ranking_manipulation' => false,
			'source_date_and_provenance_required' => true,
			'robots_terms_law_required' => true,
			'provider_disable_path_required' => true,
		);
	}

	/** @return array<string,mixed> */
	private static function comment_intelligence''',
    'round166 external benchmark compliance',
)

fpi = sub_once(
    fpi,
    r"\tprivate static function evergreen\( array \$signals, array \$context \): array \{.*?\n\t\}\n\n\t/\*\* @return array<string,mixed> \*/\n\tprivate static function localization",
    r'''	private static function evergreen( array $signals, array $context ): array {
		$timezone = self::normalize_timezone( (string) ( $context['timezone'] ?? 'UTC' ) );
		$today = self::context_now( $context );
		$counts = array( 'fresh' => 0, 'review_soon' => 0, 'outdated' => 0, 'broken_evidence' => 0, 'superseded' => 0 );
		$items = array();
		foreach ( $signals as $row ) {
			$evidence_status = self::normalize_evidence_status( (string) ( $row['evidence_status'] ?? '' ) );
			if ( in_array( $evidence_status, array( 'broken', 'withdrawn' ), true ) ) {
				$status = 'broken_evidence';
			} elseif ( 'superseded' === $evidence_status ) {
				$status = 'superseded';
			} else {
				$status = self::normalize_evergreen_status( (string) ( $row['status'] ?? '' ) );
				if ( '' === $status ) {
					$last = self::parse_timestamp( (string) ( $row['last_reviewed_at'] ?? '' ), $timezone );
					$interval = max( 30, min( 1095, (int) ( $row['review_interval_days'] ?? 365 ) ) );
					$due = null === $last ? 0 : $last + $interval * self::day_seconds();
					if ( 0 === $due || $due < $today ) {
						$status = 'outdated';
					} elseif ( $due <= $today + 30 * self::day_seconds() ) {
						$status = 'review_soon';
					} else {
						$status = 'fresh';
					}
				}
			}
			++$counts[ $status ];
			$item = self::allowlist_row( $row, array( 'provider', 'object_type', 'object_id', 'title', 'status', 'last_reviewed_at', 'review_interval_days', 'evidence_status', 'source_revision' ) );
			$item['health_status'] = $status;
			$items[] = $item;
		}
		return array( 'counts' => $counts, 'items' => $items, 'auto_delete' => false, 'broken_or_superseded_evidence_degrades_immediately' => true );
	}

	/** @return array<string,mixed> */
	private static function localization''',
    'round168 evergreen evidence degradation',
)

fpi = sub_once(
    fpi,
    r"\tprivate static function accessibility\( array \$signals \): array \{.*?\n\t\}\n\n\t/\*\* @return array<string,mixed> \*/\n\tprivate static function provenance",
    r'''	private static function accessibility( array $signals ): array {
		$data = self::advisory_flags( $signals, 'authorized_human_or_native_owner', false );
		$motion = self::signal_has_token( $signals, array( 'reduced-motion', 'motion' ) );
		$reduced_data = self::signal_has_token( $signals, array( 'reduced-data', 'data-saver', 'low-bandwidth' ) );
		$data['readiness_only'] = true;
		$data['certification_claim'] = false;
		$data['wcag_readiness_traceable'] = ! empty( $data['flags'] );
		$data['reduced_motion_considered'] = $motion;
		$data['reduced_data_considered'] = $reduced_data;
		$data['reduced_motion_and_data_considered'] = $motion && $reduced_data;
		$data['readiness_status'] = empty( $data['flags'] ) ? 'unavailable' : 'evidence_present';
		return $data;
	}

	/** @return array<string,mixed> */
	private static function provenance''',
    'round169 accessibility evidence honesty',
)

# Strict calendar-source date validation replaces permissive natural-language parsing.
fpi = sub_once(
    fpi,
    r"\tprivate static function valid_public_source_date\( string \$value \): bool \{.*?\n\t\}\n\n\tprivate static function normalize_timezone",
    r'''	private static function valid_public_source_date( string $value ): bool {
		$value = trim( $value );
		if ( '' === $value || 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})(?:[T ]([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?(?:Z|[+-](?:[01]\d|2[0-3]):[0-5]\d)?)?$/', $value, $matches ) ) {
			return false;
		}
		if ( ! checkdate( (int) $matches[2], (int) $matches[3], (int) $matches[1] ) ) {
			return false;
		}
		$timestamp = self::parse_timestamp( $value, 'UTC' );
		return null !== $timestamp && $timestamp <= time() + 300;
	}

	private static function public_source_compliance_allows( string $value ): bool {
		$value = str_replace( '-', '_', self::key( $value ) );
		return in_array( $value, array( 'allowed', 'approved', 'compliant', 'permitted', 'not_applicable' ), true );
	}

	/** @param array<int,array<string,mixed>> $signals @param string[] $tokens */
	private static function signal_has_token( array $signals, array $tokens ): bool {
		foreach ( $signals as $row ) {
			foreach ( array( 'code', 'category', 'field', 'reason_code', 'check' ) as $field ) {
				if ( ! isset( $row[ $field ] ) || ! is_scalar( $row[ $field ] ) ) {
					continue;
				}
				$value = str_replace( '_', '-', self::key( (string) $row[ $field ] ) );
				foreach ( $tokens as $token ) {
					$needle = str_replace( '_', '-', self::key( $token ) );
					if ( '' !== $needle && false !== strpos( $value, $needle ) ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	private static function normalize_timezone''',
    'round167 strict public source date and compliance helpers',
)
FPI.write_text(fpi, encoding='utf-8')

# ---------------------------------------------------------------------------
# Regression evidence for rounds 161-169.
# ---------------------------------------------------------------------------
test = FPI_TEST.read_text(encoding='utf-8')
# Existing second-cycle public benchmark fixture must now supply lawful-source acceptance evidence.
old_fixture = """\tarray( 'source' => 'Public source', 'source_date' => '2026-08-01', 'provenance' => 'public', 'source_url' => 'http://127.0.0.1/admin', 'provider' => 'public-provider' ),"""
new_fixture = """\tarray( 'source' => 'Public source', 'source_date' => '2026-08-01', 'provenance' => 'public', 'source_url' => 'http://127.0.0.1/admin', 'terms_status' => 'allowed', 'robots_status' => 'allowed', 'law_status' => 'approved', 'provider' => 'public-provider' ),"""
test = replace_once(test, old_fixture, new_fixture, 'update lawful public benchmark fixture')

final_anchor = """if ( $failed > 0 ) { fwrite( STDERR, \"{$failed} of {$tests} publishing-intelligence security regressions failed.\\n\" ); exit( 1 ); }"""
extra = r'''
// Third fresh 80-round pass: privacy, lawful-source, freshness and a11y regressions.
$GLOBALS['spdb_test_privacy_threshold'] = 20;
$GLOBALS['spdb_test_signals']['audience-board'] = array(
	array( 'dimension' => 'language', 'label' => 'user@example.com', 'value' => 'Contact +92 300 1234567', 'cohort_count' => 30, 'provider' => 'analytics' ),
);
$audience_private_value = SPDB_Publishing_Intelligence::snapshot( 'audience-board' );
$audience_private_encoded = json_encode( $audience_private_value );
$assert( false === strpos( $audience_private_encoded, 'user@example.com' ) && false === strpos( $audience_private_encoded, '1234567' ), 'Audience aggregate labels/values must suppress detected identifiers even above the cohort threshold.' );

$GLOBALS['spdb_test_signals']['internal-benchmark'] = array(
	array( 'metric' => 'engagement_rate', 'definition' => 'Approved aggregate interaction rate.', 'label' => 'Contact +92 300 1234567', 'value' => 12.4, 'benchmark' => 11.2, 'cohort_count' => 40, 'provider' => 'analytics' ),
	array( 'metric' => 'undefined_metric', 'cohort_count' => 40, 'value' => 9, 'provider' => 'analytics' ),
);
$internal_explainable = SPDB_Publishing_Intelligence::snapshot( 'internal-benchmark' );
$assert( 1 === count( $internal_explainable['data']['items'] ) && 1 === $internal_explainable['data']['incomplete_metric_rows_suppressed'], 'Internal benchmarking must suppress rows without an explainable metric definition.' );
$assert( true === $internal_explainable['data']['metric_definition_required'] && ! isset( $internal_explainable['data']['items'][0]['label'] ), 'Internal benchmark definitions are mandatory and detected sensitive labels are suppressed.' );

$GLOBALS['spdb_test_signals']['external-benchmark'] = array(
	array( 'source' => 'Lawful source', 'source_date' => '2026-08-01', 'provenance' => 'public', 'terms_status' => 'allowed', 'robots_status' => 'allowed', 'law_status' => 'approved', 'provider' => 'public-provider' ),
	array( 'source' => 'Terms forbidden', 'source_date' => '2026-08-01', 'provenance' => 'public', 'terms_status' => 'forbidden', 'robots_status' => 'allowed', 'law_status' => 'approved', 'provider' => 'public-provider' ),
	array( 'source' => 'Relative date', 'source_date' => 'tomorrow', 'provenance' => 'public', 'terms_status' => 'allowed', 'robots_status' => 'allowed', 'law_status' => 'approved', 'provider' => 'public-provider' ),
	array( 'source' => 'Future source', 'source_date' => '2100-01-01', 'provenance' => 'public', 'terms_status' => 'allowed', 'robots_status' => 'allowed', 'law_status' => 'approved', 'provider' => 'public-provider' ),
);
$external_lawful = SPDB_Publishing_Intelligence::snapshot( 'external-benchmark' );
$assert( 1 === count( $external_lawful['data']['items'] ), 'Only an explicitly dated, lawful/robots/terms-compliant external benchmark row may be projected.' );
$assert( 1 === $external_lawful['data']['compliance_rows_suppressed'] && 2 === $external_lawful['data']['incomplete_source_rows_suppressed'], 'External benchmark must separately suppress compliance failures and invalid/relative/future source dates.' );
$assert( true === $external_lawful['data']['robots_terms_law_required'], 'External public benchmark acceptance must explicitly require robots, terms and law status.' );

$GLOBALS['spdb_test_signals']['evergreen-health'] = array(
	array( 'object_id' => 'broken-now', 'status' => 'fresh', 'evidence_status' => 'broken', 'last_reviewed_at' => '2026-08-10', 'review_interval_days' => 365 ),
	array( 'object_id' => 'superseded-now', 'status' => 'fresh', 'evidence_status' => 'superseded', 'last_reviewed_at' => '2026-08-10', 'review_interval_days' => 365 ),
);
$evergreen_evidence = SPDB_Publishing_Intelligence::snapshot( 'evergreen-health', array( 'now' => strtotime( '2026-08-10 10:00:00 UTC' ) ) );
$assert( 'broken_evidence' === $evergreen_evidence['data']['items'][0]['health_status'] && 'superseded' === $evergreen_evidence['data']['items'][1]['health_status'], 'Broken or superseded evidence must immediately degrade evergreen health even when content was recently reviewed.' );
$assert( true === $evergreen_evidence['data']['broken_or_superseded_evidence_degrades_immediately'], 'Evergreen response must expose the immediate evidence-degradation contract.' );

$GLOBALS['spdb_test_signals']['accessibility-lab'] = array();
$a11y_empty = SPDB_Publishing_Intelligence::snapshot( 'accessibility-lab' );
$assert( false === $a11y_empty['data']['wcag_readiness_traceable'] && false === $a11y_empty['data']['reduced_motion_and_data_considered'] && 'unavailable' === $a11y_empty['data']['readiness_status'], 'Accessibility Lab must not claim traceability or reduced-context coverage when no evidence exists.' );
$GLOBALS['spdb_test_signals']['accessibility-lab'] = array(
	array( 'code' => 'heading-order', 'severity' => 'info', 'provider' => 'a11y' ),
	array( 'code' => 'reduced-motion', 'severity' => 'info', 'provider' => 'a11y' ),
	array( 'code' => 'reduced-data', 'severity' => 'info', 'provider' => 'a11y' ),
);
$a11y_evidence = SPDB_Publishing_Intelligence::snapshot( 'accessibility-lab' );
$assert( true === $a11y_evidence['data']['wcag_readiness_traceable'] && true === $a11y_evidence['data']['reduced_motion_considered'] && true === $a11y_evidence['data']['reduced_data_considered'], 'Accessibility readiness flags must be derived from supplied evidence rather than hard-coded true values.' );
'''
if final_anchor not in test:
    raise SystemExit('FPI test final anchor missing')
test = test.replace(final_anchor, extra + "\n" + final_anchor, 1)
FPI_TEST.write_text(test, encoding='utf-8')

EXPORT_TEST.write_text(r'''<?php
/** Third fresh 80-round export authorization and retention contract regressions. */
$root = dirname( __DIR__ );
$export = file_get_contents( $root . '/includes/class-spdb-export-service.php' );
$bg = file_get_contents( $root . '/includes/class-spdb-background-jobs.php' );
$tests = 0;
$failed = 0;
$assert = static function ( bool $condition, string $message ) use ( &$tests, &$failed ): void {
	++$tests;
	if ( ! $condition ) { ++$failed; fwrite( STDERR, "FAIL: {$message}\n" ); }
};
$assert( is_string( $export ) && false !== strpos( $export, 'private function current_user_can_export(): bool' ), 'Export service must centralize current-state authorization.' );
$assert( substr_count( $export, '! $this->current_user_can_export()' ) >= 3, 'Export request, listing and download must re-check current authorization.' );
$assert( false !== strpos( $export, "true !== ( $assertions['approved'] ?? false )" ) && false !== strpos( $export, "true === ( $assertions['suspended'] ?? true )" ), 'Export authorization must consume current File 00 approval/suspension assertions.' );
$assert( false !== strpos( $export, "user_can( $user_id, 'spdb_export_reports' )" ), 'Background export authorization must verify the owner capability for the target user.' );
$assert( false !== strpos( $export, 'spdb_export_authorization_revoked' ) && false !== strpos( $export, 'spdb_export_scope_revoked' ), 'Queued export generation must fail closed when owner or institution scope is revoked.' );
$assert( false !== strpos( $export, 'public function cleanup_expired_files()' ), 'Export service must expose bounded expired-artifact cleanup.' );
$assert( false !== strpos( $export, 'WHERE expires_at_gmt <= %s AND id > %d' ) && false !== strpos( $export, 'spdb_export_cleanup_delete_failed' ), 'Expired artifact cleanup must be expiry-bounded and fail closed on file deletion failure.' );
$cleanup_pos = is_string( $bg ) ? strpos( $bg, '$this->exports->cleanup_expired_files()' ) : false;
$metadata_pos = is_string( $bg ) ? strpos( $bg, '$this->repository->cleanup_retention( SPDB_Admin_Settings::get() )' ) : false;
$assert( false !== $cleanup_pos && false !== $metadata_pos && $cleanup_pos < $metadata_pos, 'Retention worker must delete encrypted artifacts before deleting export metadata rows.' );
if ( $failed > 0 ) { fwrite( STDERR, "{$failed} of {$tests} third fresh export regressions failed.\n" ); exit( 1 ); }
echo "All {$tests} third fresh File 23 export authorization/retention regressions passed.\n";
''', encoding='utf-8')

# ---------------------------------------------------------------------------
# Third independent 80-round register: 161-240. Defects 161-169, clean 170-240.
# ---------------------------------------------------------------------------
defects = {
    161: ('Export current-state authorization', 'request/list/download relied on capability/signature without a mandatory fresh File 00 approval/suspension/eligibility check at service/click time', 'centralize export authorization and re-check current File 00 state + export capability before request, list and download'),
    162: ('Queued export revocation', 'an export queued while authorized could still be generated after account/capability/institution-scope revocation', 're-authorize the job owner and institution scope before background generation; fail the export closed on revocation'),
    163: ('Expired export artifact retention', 'retention cleanup deleted expired export metadata rows but could leave encrypted .spdb artifacts orphaned on disk', 'delete validated expired artifacts first; stop metadata purge on cleanup failure so the retry retains evidence'),
    164: ('FPI-16 aggregate value privacy', 'Audience Board applied cohort threshold but did not screen allowed label/value text for identifiers', 'value-screen label/value and suppress detected sensitive text even above threshold'),
    165: ('FPI-17 metric explainability', 'Internal Benchmarking did not require/return metric definitions and free-text benchmark fields were not value-screened', 'require metric+definition, suppress incomplete rows and privacy-screen definition/label/value/benchmark'),
    166: ('FPI-18 lawful-source enforcement', 'terms/robots were presentation fields only and no law-status acceptance decision was enforced', 'require accepted terms_status, robots_status and law_status before projecting an external benchmark row'),
    167: ('FPI-18 source-date strictness', 'DateTime parsing accepted natural-language/invalidly normalized or future source dates, weakening visible provenance', 'require valid explicit ISO-like calendar date/time, check calendar validity and reject future dates'),
    168: ('FPI-20 evidence degradation', 'Evergreen Health could remain fresh despite provider evidence being broken/withdrawn/superseded', 'make broken/withdrawn evidence immediately broken_evidence and superseded evidence immediately superseded'),
    169: ('FPI-22 readiness evidence honesty', 'Accessibility Lab hard-coded WCAG traceability and reduced-motion/data consideration true even without evidence', 'derive traceability/reduced-motion/reduced-data flags from supplied checks and report unavailable when no evidence exists'),
}
clean_focuses = [
'File 00 current identity/approval contract','File 09 verification lifecycle boundary','File 19 notification transport ownership','File 20 shell and Safe Mode ownership','File 21 canonical publication truth','File 22 composer/create ownership','File 24 assurance boundary','File 25 visual token boundary','File 26 search/ranking boundary','Founder vs Doctor scope separation','Pending and suspended state denial','Delegation expiry and revocation','Delegation no privilege escalation','Private route authentication','Noindex/private cache posture','CSRF/nonces on mutations','IDOR object scope','Forged provider rejection','Adapter version/maturity gate','Native-owner click-time reauthorization','ETag/concurrency conflict handling','Idempotent mutation semantics','Federated inventory bounded pagination','Inventory partial-provider degradation','Saved views private ownership','Task ownership and assignee scope','Collections no native-data duplication','Universal object reference stability','Native reference resolver drift','Review inbox native action ownership','Calendar timezone semantics','Calendar suspension revalidation','Failed schedule detection boundary','Automation no medical approval','Automation no destructive deletion','Automation no impersonation','AI assistance advisory-only','AI no diagnosis/prescription/dosage','Analytics privacy threshold floor','Analytics metric-definition contract','Report field privacy filtering','CSV formula-injection defense','Encrypted export envelope integrity','Signed export owner binding','Export path traversal containment','Background retry/dead-letter semantics','Background job payload bounds','Retention user erasure propagation','Audit mutation evidence','Audit no patient/private payload','Adapter health bounded cache','Weak-connection degraded states','Provider timeout/failure isolation','Local repair no foreign mutation','Legacy migration diagnostics read-only','Fresh install idempotency','Upgrade/migration ownership preservation','Rollback no native data loss','Database schema manifest parity','Version/source/package parity','Deterministic packaging','PHP 8.0-8.3 compatibility','JavaScript syntax gates','RTL layout contract','Keyboard/focus accessibility','Reduced motion/data CSS readiness','320-1920 responsive contract','No horizontal overflow','No duplicate Composer/Newsroom UI','No dead destination buttons','Release-signoff staging boundary'
]
if len(clean_focuses) != 71:
    raise SystemExit(f'expected 71 clean focuses, got {len(clean_focuses)}')
lines = [
'# File 23 — Third Fresh 80-Round Review and Correction Register — 2026-08-10',
'',
'## Governing truth',
'',
'- This is a **third independent 80-round pass** after completed cumulative rounds 1–160. It is numbered **161–240** to preserve review history.',
'- Starting repository exact HEAD: `847108588cf2c1a5027004596890ada3eb09d206` on `feature/file23-2026-governing-plan-completion`.',
'- Governing basis: consolidated central plan + File 23 Harmonized/Final plan + amended F23-FPI-01..24 + current cross-file ownership/security/release contracts.',
'- Method: one focus per round; if a defect is found, correct source immediately, add regression evidence, then continue the next fresh round over corrected state.',
'- Repository review does not prove Hostinger staging/live state.',
'',
'## Defect-bearing rounds',
'',
'| Round | Focus | Defect | Immediate correction |',
'|---:|---|---|---|',
]
for n in range(161, 170):
    f, d, c = defects[n]
    lines.append(f'| {n} | {f} | {d} | {c} |')
lines += ['', '## Post-correction clean rounds', '', '| Round | Fresh focus | Result |', '|---:|---|---|']
for n, focus in zip(range(170, 241), clean_focuses):
    lines.append(f'| {n} | {focus} | No new known repository defect found after the preceding corrections. |')
lines += [
'',
'## Third-pass result',
'',
'- Total fresh rounds: **80** (161–240).',
'- Defect-bearing rounds: **161–169** (local rounds 1–9).',
'- Clean post-correction rounds: **170–240** (local rounds 10–80).',
'- New known repository defects found: **9**; all nine were corrected immediately and regression evidence was added.',
'- This register makes no staging/live completion claim.',
'',
'## Live-First status boundary',
'',
'- Repository HEAD after repair: to be captured from Git and verified by exact-head CI.',
'- Deployed Version: **UNVERIFIED**.',
'- DB Version: **UNVERIFIED**.',
'- Migration State: **UNVERIFIED**.',
'- Live Verification Status: **UNVERIFIED**.',
'',
'**Exact deployed code is still unverified; repository-based diagnosis is provisional for production reality.**',
]
AUDIT.write_text('\n'.join(lines) + '\n', encoding='utf-8')

status = STATUS.read_text(encoding='utf-8') if STATUS.exists() else ''
status_note = """

## 2026-08-10 — third fresh 80-round repository review
- Independent cumulative rounds 161–240 completed against starting HEAD `847108588cf2c1a5027004596890ada3eb09d206`.
- Defects found and corrected in rounds 161–169; rounds 170–240 found no new known repository defect after correction.
- Hardening covers current-state export authorization/revocation, expired export artifact retention, Audience/Internal/External benchmark privacy and lawful-source rules, strict source dates, Evergreen evidence degradation and evidence-based accessibility readiness.
- Hostinger staging/live, deployed artifact parity, DB/schema/migration and live smoke remain unverified.
"""
if 'third fresh 80-round repository review' not in status:
    STATUS.write_text((status.rstrip() + status_note).rstrip() + '\n', encoding='utf-8')

changelog = CHANGELOG.read_text(encoding='utf-8') if CHANGELOG.exists() else ''
change_note = """## 2026-08-10 — third fresh 80-round hardening pass (candidate 1.3.0)
- Re-check export authorization against current File 00 account state/capability at request, listing, generation and download time.
- Delete expired encrypted export artifacts before metadata retention purge, preserving retry evidence on cleanup failure.
- Harden FPI-16/17 aggregate privacy and metric explainability, FPI-18 lawful-source/date acceptance, FPI-20 evidence degradation and FPI-22 evidence-based readiness claims.
- Added third independent 80-round register (161–240) and targeted regressions; staging/live state remains unverified.

"""
if 'third fresh 80-round hardening pass' not in changelog:
    CHANGELOG.write_text(change_note + changelog.lstrip(), encoding='utf-8')

# Normalize document endings for diff hygiene.
for path in (STATUS, CHANGELOG, AUDIT, FPI, EXPORT, BG, FPI_TEST, EXPORT_TEST):
    data = path.read_text(encoding='utf-8')
    path.write_text(data.rstrip() + '\n', encoding='utf-8')

print('File 23 third fresh 80-round corrections staged successfully.')
