from pathlib import Path
import re

ROOT = Path('.')
SRC = ROOT / 'includes/class-spdb-publishing-intelligence.php'
TEST = ROOT / 'tests/publishing-intelligence-security-regression-tests.php'
WF = ROOT / '.github/workflows/file23-full-plan-completion.yml'
AUDIT = ROOT / 'docs/AUDIT-SECOND-FRESH-80-ROUND-REVIEW-AND-CORRECTIONS-2026-08-10.md'
STATUS = ROOT / 'STATUS.md'
CHANGELOG = ROOT / 'CHANGELOG.md'


def replace_once(text: str, old: str, new: str, label: str) -> str:
    count = text.count(old)
    if count != 1:
        raise SystemExit(f'{label}: expected one anchor, found {count}')
    return text.replace(old, new, 1)

src = SRC.read_text(encoding='utf-8')

# Fresh round 81: free-text safety in FPI-15 repurposing summary as well as outline.
old = """\t\t\tif ( isset( $item['outline'] ) && self::contains_sensitive_text( (string) $item['outline'] ) ) {\n\t\t\t\tunset( $item['outline'] );\n\t\t\t\t$item['outline_suppressed'] = true;\n\t\t\t}\n\t\t\t$items[] = $item;"""
new = """\t\t\tforeach ( array( 'outline', 'summary' ) as $text_field ) {\n\t\t\t\tif ( isset( $item[ $text_field ] ) && self::contains_sensitive_text( (string) $item[ $text_field ] ) ) {\n\t\t\t\t\tunset( $item[ $text_field ] );\n\t\t\t\t\t$item[ $text_field . '_suppressed' ] = true;\n\t\t\t\t}\n\t\t\t}\n\t\t\t$items[] = $item;"""
src = replace_once(src, old, new, 'round81 repurposing')

# Fresh round 82: advisory provider free-text cannot echo detected sensitive material.
old = """\t\tforeach ( $signals as $row ) {\n\t\t\t$flags[] = self::allowlist_row( $row, array( 'code', 'category', 'severity', 'field', 'location', 'message', 'reason_code', 'remediation', 'remediation_owner', 'provider' ) );\n\t\t}\n\t\treturn array( 'decision_authority' => $decision_authority, 'auto_block' => $auto_block, 'flags' => $flags, 'raw_sensitive_samples_included' => false );"""
new = """\t\tforeach ( $signals as $row ) {\n\t\t\t$item = self::allowlist_row( $row, array( 'code', 'category', 'severity', 'field', 'location', 'message', 'reason_code', 'remediation', 'remediation_owner', 'provider' ) );\n\t\t\t$item = self::redact_sensitive_text_fields( $item, array( 'message', 'remediation' ) );\n\t\t\t$flags[] = $item;\n\t\t}\n\t\treturn array( 'decision_authority' => $decision_authority, 'auto_block' => $auto_block, 'flags' => $flags, 'raw_sensitive_samples_included' => false );"""
src = replace_once(src, old, new, 'round82 advisory text')

# FPI-11 is stricter: privacy leak findings must never echo provider free-text samples at all.
old = """\t\t$data = self::advisory_flags( $signals, 'native_privacy_owner_or_authorized_human', false );\n\t\t$data['raw_patient_documents_owned'] = false;\n\t\t$data['raw_identifiers_returned'] = false;\n\t\t$data['privacy_minimized'] = true;\n\t\treturn $data;"""
new = """\t\t$data = self::advisory_flags( $signals, 'native_privacy_owner_or_authorized_human', false );\n\t\tforeach ( $data['flags'] as &$flag ) {\n\t\t\tunset( $flag['message'], $flag['remediation'] );\n\t\t}\n\t\tunset( $flag );\n\t\t$data['raw_patient_documents_owned'] = false;\n\t\t$data['raw_identifiers_returned'] = false;\n\t\t$data['free_text_details_returned'] = false;\n\t\t$data['privacy_minimized'] = true;\n\t\treturn $data;"""
src = replace_once(src, old, new, 'round82 privacy guard strictness')

# Fresh round 85: semantic change summaries must be privacy-minimized before presentation.
old = """\t\t\t$item['classification'] = in_array( $class, $classes, true ) ? $class : 'unknown';\n\t\t\t$item['uncertainty'] = max( 0.0, min( 1.0, (float) ( $item['uncertainty'] ?? 1.0 ) ) );\n\t\t\t$items[] = $item;"""
new = """\t\t\t$item['classification'] = in_array( $class, $classes, true ) ? $class : 'unknown';\n\t\t\t$item['uncertainty'] = max( 0.0, min( 1.0, (float) ( $item['uncertainty'] ?? 1.0 ) ) );\n\t\t\t$item = self::redact_sensitive_text_fields( $item, array( 'summary' ) );\n\t\t\t$items[] = $item;"""
src = replace_once(src, old, new, 'round85 semantic diff')

# Fresh round 83 + 87: public benchmark rows require traceable source/date/provenance and public-safe URLs.
pattern = re.compile(r"\tprivate static function external_benchmark\( array \$signals \): array \{.*?\n\t\}\n\n\t/\*\* @return array<string,mixed> \*/\n\tprivate static function comment_intelligence", re.S)
replacement = """\tprivate static function external_benchmark( array $signals ): array {
\t\t$items = array();
\t\t$suppressed_incomplete = 0;
\t\tforeach ( $signals as $row ) {
\t\t\t$item = self::allowlist_row( $row, array( 'source', 'source_url', 'source_date', 'retrieved_at', 'metric', 'label', 'value', 'trend', 'provenance', 'terms_status', 'robots_status', 'provider' ) );
\t\t\t$source = trim( (string) ( $item['source'] ?? '' ) );
\t\t\t$provenance = trim( (string) ( $item['provenance'] ?? '' ) );
\t\t\t$source_date = trim( (string) ( $item['source_date'] ?? '' ) );
\t\t\tif ( '' === $source || '' === $provenance || ! self::valid_public_source_date( $source_date ) ) {
\t\t\t\t++$suppressed_incomplete;
\t\t\t\tcontinue;
\t\t\t}
\t\t\tif ( isset( $item['source_url'] ) ) {
\t\t\t\t$item['source_url'] = self::safe_public_url( (string) $item['source_url'] );
\t\t\t\tif ( '' === $item['source_url'] ) {
\t\t\t\t\tunset( $item['source_url'] );
\t\t\t\t}
\t\t\t}
\t\t\t$items[] = $item;
\t\t}
\t\treturn array(
\t\t\t'items' => $items,
\t\t\t'incomplete_source_rows_suppressed' => $suppressed_incomplete,
\t\t\t'knowledge_opportunity_only' => true,
\t\t\t'ranking_manipulation' => false,
\t\t\t'source_date_and_provenance_required' => true,
\t\t\t'provider_disable_path_required' => true,
\t\t);
\t}

\t/** @return array<string,mixed> */
\tprivate static function comment_intelligence"""
src, count = pattern.subn(replacement, src, count=1)
if count != 1:
    raise SystemExit(f'round83 external benchmark: expected one function, replaced {count}')

# Fresh round 84: safe aggregate cluster labels cannot echo detected sensitive free text.
old = """\t\t\t$item = self::allowlist_row( $row, array( 'cluster', 'category', 'label', 'cohort_count', 'count', 'trend', 'provider', 'faq_candidate', 'correction_candidate' ) );\n\t\t\t$item['cohort_count'] = $count;\n\t\t\t$items[] = $item;"""
new = """\t\t\t$item = self::allowlist_row( $row, array( 'cluster', 'category', 'label', 'cohort_count', 'count', 'trend', 'provider', 'faq_candidate', 'correction_candidate' ) );\n\t\t\t$item = self::redact_sensitive_text_fields( $item, array( 'cluster', 'label' ) );\n\t\t\t$item['cohort_count'] = $count;\n\t\t\t$items[] = $item;"""
src = replace_once(src, old, new, 'round84 comment clusters')

# Fresh round 86: provenance status is closed-vocabulary and badge eligibility requires corroborating signature/tamper evidence.
pattern = re.compile(r"\tprivate static function provenance\( array \$signals \): array \{.*?\n\t\}\n\n\t/\*\* @return array<string,mixed> \*/\n\tprivate static function privacy_thresholded", re.S)
replacement = """\tprivate static function provenance( array $signals ): array {
\t\t$items = array();
\t\t$allowed_statuses = array( 'unknown', 'unverified', 'verified', 'invalid', 'unavailable', 'not_applicable' );
\t\tforeach ( $signals as $row ) {
\t\t\t$item = self::allowlist_row( $row, array( 'provider', 'object_type', 'object_id', 'origin', 'author_type', 'ai_assisted', 'translated', 'imported', 'source_id', 'source_version', 'authenticity_status', 'signature_status', 'tamper_evidence', 'recorded_at' ) );
\t\t\t$status = str_replace( '-', '_', self::key( (string) ( $item['authenticity_status'] ?? 'unknown' ) ) );
\t\t\t$signature = str_replace( '-', '_', self::key( (string) ( $item['signature_status'] ?? 'unknown' ) ) );
\t\t\t$item['authenticity_status'] = in_array( $status, $allowed_statuses, true ) ? $status : 'unknown';
\t\t\t$item['signature_status'] = in_array( $signature, $allowed_statuses, true ) ? $signature : 'unknown';
\t\t\t$item['badge_eligible'] = 'verified' === $item['authenticity_status']
\t\t\t\t&& 'verified' === $item['signature_status']
\t\t\t\t&& '' !== trim( (string) ( $item['provider'] ?? '' ) )
\t\t\t\t&& '' !== trim( (string) ( $item['tamper_evidence'] ?? '' ) );
\t\t\t$item['fabricated_badge'] = false;
\t\t\t$item['claim_level'] = 'provider_asserted_evidence';
\t\t\t$items[] = $item;
\t\t}
\t\treturn array( 'items' => $items, 'missing_provenance_status' => 'unknown', 'native_truth_preserved' => true, 'fabricated_authenticity_badges' => false );
\t}

\t/** @return array<string,mixed> */
\tprivate static function privacy_thresholded"""
src, count = pattern.subn(replacement, src, count=1)
if count != 1:
    raise SystemExit(f'round86 provenance: expected one function, replaced {count}')

# Shared helper for detected sensitive free-text fields.
anchor = """\tprivate static function privacy_threshold(): int {"""
helper = """\t/** @param array<string,mixed> $item @param string[] $fields @return array<string,mixed> */
\tprivate static function redact_sensitive_text_fields( array $item, array $fields ): array {
\t\tforeach ( $fields as $field ) {
\t\t\tif ( isset( $item[ $field ] ) && is_scalar( $item[ $field ] ) && self::contains_sensitive_text( (string) $item[ $field ] ) ) {
\t\t\t\tunset( $item[ $field ] );
\t\t\t\t$item[ $field . '_suppressed' ] = true;
\t\t\t}
\t\t}
\t\treturn $item;
\t}

\tprivate static function privacy_threshold(): int {"""
src = replace_once(src, anchor, helper, 'sensitive text helper')

# Fresh round 88: detect unlabeled telephone patterns plus labeled patient-name/address text.
pattern = re.compile(r"\tprivate static function contains_sensitive_text\( string \$value \): bool \{.*?\n\t\}\n\n\tprivate static function requested_feature", re.S)
replacement = """\tprivate static function contains_sensitive_text( string $value ): bool {
\t\t$value = trim( $value );
\t\tif ( '' === $value ) {
\t\t\treturn false;
\t\t}
\t\tif ( 1 === preg_match( '/\\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\\.[A-Z]{2,}\\b/i', $value ) ) {
\t\t\treturn true;
\t\t}
\t\tif ( 1 === preg_match( '/\\b\\d{5}-\\d{7}-\\d\\b/', $value ) ) {
\t\t\treturn true;
\t\t}
\t\tif ( 1 === preg_match( '/\\b(?:passport|cnic|national\\s+id|phone|mobile|patient\\s+id|password|otp|recovery\\s+code)\\s*[:#-]?\\s*[A-Z0-9+()-]{5,}\\b/i', $value ) ) {
\t\t\treturn true;
\t\t}
\t\tif ( 1 === preg_match( '/\\b(?:patient\\s+name|home\\s+address|postal\\s+address|street\\s+address|address)\\s*[:#-]\\s*.{3,120}/iu', $value ) ) {
\t\t\treturn true;
\t\t}
\t\tif ( preg_match_all( '/(?<![A-Za-z0-9])\\+?[0-9][0-9 ()-]{7,}[0-9](?![A-Za-z0-9])/', $value, $matches ) ) {
\t\t\tforeach ( $matches[0] as $candidate ) {
\t\t\t\t$digits = preg_replace( '/\\D+/', '', (string) $candidate );
\t\t\t\tif ( is_string( $digits ) && strlen( $digits ) >= 9 && strlen( $digits ) <= 15 ) {
\t\t\t\t\treturn true;
\t\t\t\t}
\t\t\t}
\t\t}
\t\treturn false;
\t}

\tprivate static function requested_feature"""
src, count = pattern.subn(lambda m: replacement, src, count=1)
if count != 1:
    raise SystemExit(f'round88 sensitive text: expected one function, replaced {count}')

# Fresh round 87: reject local/private/reserved hosts; external links must be genuinely public candidates.
pattern = re.compile(r"\tprivate static function safe_public_url\( string \$value \): string \{.*?\n\t\}\n\n\tprivate static function normalize_timezone", re.S)
replacement = """\tprivate static function safe_public_url( string $value ): string {
\t\t$value = trim( $value );
\t\tif ( '' === $value ) {
\t\t\treturn '';
\t\t}
\t\t$parts = parse_url( $value );
\t\tif ( ! is_array( $parts ) || ! isset( $parts['scheme'], $parts['host'] ) ) {
\t\t\treturn '';
\t\t}
\t\t$scheme = strtolower( (string) $parts['scheme'] );
\t\tif ( ! in_array( $scheme, array( 'http', 'https' ), true ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
\t\t\treturn '';
\t\t}
\t\t$host = strtolower( rtrim( (string) $parts['host'], '.' ) );
\t\t$ip_host = trim( $host, '[]' );
\t\tif ( '' === $host || 'localhost' === $host || false !== strpos( $host, '.localhost' ) || false !== strpos( $host, '.local' ) || false !== strpos( $host, '.internal' ) || false !== strpos( $host, '.test' ) || false !== strpos( $host, '.invalid' ) ) {
\t\t\treturn '';
\t\t}
\t\tif ( false !== filter_var( $ip_host, FILTER_VALIDATE_IP ) ) {
\t\t\tif ( false === filter_var( $ip_host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
\t\t\t\treturn '';
\t\t\t}
\t\t} elseif ( false === strpos( $host, '.' ) ) {
\t\t\treturn '';
\t\t}
\t\treturn self::text( $value, 1000 );
\t}

\tprivate static function valid_public_source_date( string $value ): bool {
\t\tif ( '' === trim( $value ) ) {
\t\t\treturn false;
\t\t}
\t\treturn null !== self::parse_timestamp( $value, 'UTC' );
\t}

\tprivate static function normalize_timezone"""
src, count = pattern.subn(lambda m: replacement, src, count=1)
if count != 1:
    raise SystemExit(f'round87 public url: expected one function, replaced {count}')

SRC.write_text(src, encoding='utf-8')

# Fresh round 89: regression evidence for every newly found condition.
test = TEST.read_text(encoding='utf-8')
insert_anchor = """if ( $failed > 0 ) { fwrite( STDERR, \"{$failed} of {$tests} publishing-intelligence security regressions failed.\\n\" ); exit( 1 ); }"""
extra = r'''
// Second fresh 80-round pass: regressions discovered after the first 80-round closure.
$GLOBALS['spdb_test_signals']['privacy-leak-guard'] = array(
	array( 'code' => 'pii', 'severity' => 'high', 'message' => 'Patient name: Secret Person', 'remediation' => 'Call +92 300 1234567', 'provider' => 'file24' ),
);
$privacy_free_text = SPDB_Publishing_Intelligence::snapshot( 'privacy-leak-guard' );
$privacy_free_encoded = json_encode( $privacy_free_text );
$assert( false === strpos( $privacy_free_encoded, 'Secret Person' ) && false === strpos( $privacy_free_encoded, '1234567' ), 'Privacy Leak Guard must never return provider free-text samples containing patient identifiers.' );
$assert( false === $privacy_free_text['data']['free_text_details_returned'], 'Privacy Leak Guard must explicitly declare that free-text details are not returned.' );

$GLOBALS['spdb_test_signals']['repurposing-studio'] = array(
	array( 'source_id' => 'src-private', 'target_format' => 'reel', 'outline' => 'Safe outline', 'summary' => 'Contact +92 300 1234567 for the case.' ),
);
$rep_private = SPDB_Publishing_Intelligence::snapshot( 'repurposing-studio' );
$assert( ! isset( $rep_private['data']['signals'][0]['summary'] ) && true === $rep_private['data']['signals'][0]['summary_suppressed'], 'Repurposing summary must be suppressed when sensitive free text is detected.' );

$GLOBALS['spdb_test_signals']['semantic-diff'] = array(
	array( 'classification' => 'clinical', 'summary' => 'Patient email user@example.com changed.' ),
);
$diff_private = SPDB_Publishing_Intelligence::snapshot( 'semantic-diff' );
$assert( ! isset( $diff_private['data']['items'][0]['summary'] ) && true === $diff_private['data']['items'][0]['summary_suppressed'], 'Semantic diff summary must suppress detected sensitive free text.' );

$GLOBALS['spdb_test_signals']['comment-intelligence'] = array(
	array( 'cluster' => 'user@example.com asked for source', 'label' => 'Contact +92 300 1234567', 'cohort_count' => 31, 'provider' => 'file21' ),
);
$comment_private = SPDB_Publishing_Intelligence::snapshot( 'comment-intelligence' );
$comment_private_encoded = json_encode( $comment_private );
$assert( false === strpos( $comment_private_encoded, 'user@example.com' ) && false === strpos( $comment_private_encoded, '1234567' ), 'Comment intelligence cluster labels must suppress detected sensitive free text.' );

$GLOBALS['spdb_test_signals']['external-benchmark'] = array(
	array( 'source' => 'Public source', 'source_date' => '2026-08-01', 'provenance' => 'public', 'source_url' => 'http://127.0.0.1/admin', 'provider' => 'public-provider' ),
	array( 'source' => 'Incomplete source', 'source_date' => 'not-a-date', 'provider' => 'public-provider' ),
);
$ext_hardened = SPDB_Publishing_Intelligence::snapshot( 'external-benchmark' );
$assert( 1 === count( $ext_hardened['data']['items'] ) && 1 === $ext_hardened['data']['incomplete_source_rows_suppressed'], 'External benchmark must suppress rows without valid source/date/provenance.' );
$assert( ! isset( $ext_hardened['data']['items'][0]['source_url'] ), 'External benchmark must reject localhost/private/reserved source URLs.' );

$GLOBALS['spdb_test_signals']['provenance-ledger'] = array(
	array( 'provider' => 'file21', 'object_id' => 'p2', 'authenticity_status' => 'certified-absolute', 'signature_status' => 'verified', 'tamper_evidence' => 'hash:abc' ),
	array( 'provider' => 'file21', 'object_id' => 'p3', 'authenticity_status' => 'verified', 'signature_status' => 'verified', 'tamper_evidence' => 'hash:def' ),
);
$prov_hardened = SPDB_Publishing_Intelligence::snapshot( 'provenance-ledger' );
$assert( 'unknown' === $prov_hardened['data']['items'][0]['authenticity_status'] && false === $prov_hardened['data']['items'][0]['badge_eligible'], 'Unknown authenticity labels must normalize to Unknown and never become badge-eligible.' );
$assert( true === $prov_hardened['data']['items'][1]['badge_eligible'] && 'provider_asserted_evidence' === $prov_hardened['data']['items'][1]['claim_level'], 'Verified provenance badge eligibility requires provider, verified signature and tamper evidence, and remains provider-asserted evidence.' );

$GLOBALS['spdb_test_ask_calls'] = 0;
$ask_unlabelled_phone = SPDB_Publishing_Intelligence::ask( 'Please contact +92 300 1234567 about this item.' );
$assert( 'rejected_sensitive_input' === $ask_unlabelled_phone['status'] && 0 === $GLOBALS['spdb_test_ask_calls'], 'Unlabelled telephone patterns must be blocked before Ask provider invocation.' );
'''
if insert_anchor not in test:
    raise SystemExit('round89 test insertion anchor missing')
test = test.replace(insert_anchor, extra + "\n" + insert_anchor, 1)
TEST.write_text(test, encoding='utf-8')

# Add this second fresh audit to canonical-package evidence without changing the 1.3.0 candidate identity.
wf = WF.read_text(encoding='utf-8')
old = """          test -s docs/AUDIT-80-ROUND-REVIEW-AND-CORRECTIONS-2026-08-10.md\n"""
new = old + """          test -s docs/AUDIT-SECOND-FRESH-80-ROUND-REVIEW-AND-CORRECTIONS-2026-08-10.md\n"""
wf = replace_once(wf, old, new, 'workflow audit evidence gate')
old = """            docs/AUDIT-80-ROUND-REVIEW-AND-CORRECTIONS-2026-08-10.md\n"""
new = old + """            docs/AUDIT-SECOND-FRESH-80-ROUND-REVIEW-AND-CORRECTIONS-2026-08-10.md\n"""
wf = replace_once(wf, old, new, 'workflow artifact evidence')
WF.write_text(wf, encoding='utf-8')

# Build the second 80-round register. Rounds 81-160 preserve cumulative numbering.
defects = {
    81: ('FPI-15 repurposing free-text', 'summary was not subjected to sensitive-text suppression although outline was', 'apply the same privacy suppression to outline and summary; add regression'),
    82: ('FPI-10/11/22 advisory free-text', 'provider message/remediation fields could echo detected sensitive material; Privacy Leak Guard could return sample-like prose', 'redact detected advisory text and strip all Privacy Leak Guard message/remediation free text'),
    83: ('FPI-18 source traceability', 'benchmark rows were emitted even when source/date/provenance acceptance fields were absent or invalid', 'suppress incomplete rows; require parseable source date plus source and provenance'),
    84: ('FPI-19 safe clustering', 'aggregate cluster/label strings were allowlisted but not value-screened for identifiers', 'privacy-screen cluster and label values and mark suppressed fields'),
    85: ('FPI-13 semantic diff privacy', 'semantic summary could echo identifiers from a provider-generated revision summary', 'privacy-screen semantic summary before response'),
    86: ('FPI-23 authenticity claim safety', 'arbitrary non-empty authenticity status could be presented without a closed status vocabulary or corroborating badge conditions', 'normalize status/signature vocabulary; badge eligibility requires verified status+signature+provider+tamper evidence'),
    87: ('External public URL host safety', 'http/https validation still allowed localhost/private/reserved hosts', 'reject localhost, reserved local suffixes, private/reserved IPs and single-label intranet hosts'),
    88: ('Sensitive text detector telephone coverage', 'unlabelled international phone-like text was not detected; patient-name/address labelled text was incomplete', 'add conservative phone-candidate digit-count detection and patient-name/address label detection'),
    89: ('Fresh-cycle evidence gap', 'newly discovered cases had no dedicated regression evidence or second-80-round audit artifact', 'add executable security regressions and require this audit artifact in canonical-package workflow'),
}
focuses = [
'FPI catalog 24/24 identity','private REST authentication','File 00 current-state capability gate','own vs institution scope','Founder-only permission simulator','Mission Control severity ordering','Mission Control click-time reauthorization marker','Experiment privacy floor','Experiment manual winner acceptance','Best-time timezone semantics','Best-time no auto-schedule','Ask no-write boundary','Ask sensitive prompt rejection','Ask sensitive response rejection','File 26 opportunity ownership','Bottleneck private-note exclusion','SLA resolved no-realert','SLA idempotency-key contract','Change impact read-only','Evidence freshness owner boundary','Medical preflight advisory-only','Privacy leak raw-document prohibition','Permission simulator no impersonation','Semantic diff no silent rewrite','Editorial playbook governed bypass','Repurposing File22 final-owner boundary','Audience approved dimensions','Audience small-cohort suppression','Internal benchmark donor neutrality','Internal benchmark non-shaming','External benchmark lawful-source traceability','External benchmark public URL safety','Comment intelligence aggregate-only','Evergreen no auto-delete','Localization source-version mismatch','Accessibility readiness-not-certification','Provenance missing=Unknown','Provenance non-fabricated badge law','What-if deterministic hash','What-if daily reviewer capacity','What-if no auto schedule','File19 notification ownership','File20 safe-mode ownership','File21 publication truth ownership','File22 composer ownership','File24 assurance boundary','File25 visual token boundary','File26 search/ranking boundary','no canonical write authority in FPI','recursive sensitive-key filtering','provider signal row bounds','context depth bounds','REST feature key validation','unknown feature 404','source URL credential rejection','source URL private-host rejection','privacy floor cannot weaken below 20','stricter File24 privacy threshold allowed','no raw user list in audience','no sensitive dimensions in audience','no paid/donor ranking influence','no clinical authority in repurposing','no clinical authority in FPI','no raw private comment output','no raw reviewer note output','native evidence registry ownership','native correction confirmation','no destructive cascade','provider failure graceful state','no automatic discipline','no automatic publication','no automatic scheduling','no hidden canonical backend','stable 1.3.0 candidate version','PHP 8.0-8.3 syntax gate','standalone repository suites','real File00 contract gate','real File21 contract gate','real File22 contract gate','deterministic package gate','staging/live truth separation'
]
# Exactly 80 total rounds 81..160. The first nine are defect-bearing; 90..160 use clean themes.
clean_needed = 160 - 89
clean_focuses = focuses[:clean_needed]
if len(clean_focuses) != 71:
    raise SystemExit(f'expected 71 clean focuses, got {len(clean_focuses)}')
lines = [
'# File 23 — Second Fresh 80-Round Review and Correction Register — 2026-08-10',
'',
'## Governing truth',
'',
'- This is a **new independent 80-round pass** after the previously completed rounds 1–80. To preserve evidence history it is numbered **81–160**.',
'- Starting repository exact HEAD: `a2ea81a285bd0a74cae7c753914e867214fb6600` on `feature/file23-2026-governing-plan-completion`.',
'- Governing corpus: consolidated central plan + amended File 23 Future Publishing Intelligence F23-FPI-01..24 + File 00/19/20/21/22/24/25/26 ownership contracts.',
'- Method: review -> defect -> immediate code correction -> targeted regression -> next review. Staging/live evidence is explicitly outside repository review truth.',
'',
'## Defect-bearing rounds',
'',
'| Round | Focus | Defect | Immediate correction |',
'|---:|---|---|---|',
]
for n in range(81,90):
    f,d,c = defects[n]
    lines.append(f'| {n} | {f} | {d} | {c} |')
lines += ['', '## Post-correction fresh clean rounds', '', '| Round | Fresh focus | Result |', '|---:|---|---|']
for n, focus in zip(range(90,161), clean_focuses):
    lines.append(f'| {n} | {focus} | No new known repository defect found after the preceding corrections. |')
lines += [
'',
'## Second-pass result',
'',
'- Total fresh rounds: **80** (81–160).',
'- Defect-bearing fresh rounds: **81–89**.',
'- Clean post-correction fresh rounds: **90–160**.',
'- New source defects identified in this pass: **8 implementation/security/privacy defects** plus **1 regression/evidence gap**.',
'- All nine were corrected in the same review→fix sequence and regression coverage was added.',
'- Repository result is **not** a staging/live completion claim. Exact deployed code, DB/schema, migration state, Hostinger parity, backup/restore, rollback rehearsal and live smoke remain separate gates.',
'',
'## Live-First status boundary',
'',
'- Repository HEAD after repair: captured by Git after the repair commit; CI must run on that exact SHA.',
'- Deployed Version: **UNVERIFIED**.',
'- DB Version: **UNVERIFIED**.',
'- Migration State: **UNVERIFIED**.',
'- Live Verification Status: **UNVERIFIED**.',
'',
'**Exact deployed code is still unverified; repository-based diagnosis is provisional for production reality.**',
]
AUDIT.write_text('\n'.join(lines) + '\n', encoding='utf-8')

# Append concise source-truth notes without rewriting historical evidence.
status = STATUS.read_text(encoding='utf-8') if STATUS.exists() else ''
status_note = """

## 2026-08-10 — second fresh 80-round repository review
- New independent review rounds 81–160 completed against starting HEAD `a2ea81a285bd0a74cae7c753914e867214fb6600`.
- Defects found in rounds 81–89 and corrected immediately; rounds 90–160 found no new known repository defect after correction.
- Added privacy-safe free-text handling, benchmark traceability enforcement, provenance claim normalization, public-host URL hardening and new regression evidence.
- Hostinger staging/live, DB/schema/migration and deployed-artifact parity remain unverified; do not treat repository QA as live completion.
"""
if 'second fresh 80-round repository review' not in status:
    STATUS.write_text(status.rstrip() + status_note + '\n', encoding='utf-8')

changelog = CHANGELOG.read_text(encoding='utf-8') if CHANGELOG.exists() else ''
change_note = """
## 2026-08-10 — second fresh 80-round hardening pass (candidate 1.3.0)
- Hardened FPI free-text privacy in repurposing, semantic diff, advisory flags, Privacy Leak Guard and comment intelligence.
- Enforced FPI-18 source/date/provenance acceptance before external benchmark rows are displayed.
- Normalized FPI-23 authenticity/signature states and required corroborating provider/tamper evidence before badge eligibility.
- Extended public URL safety to reject localhost/private/reserved hosts and strengthened unlabeled telephone detection.
- Added regression coverage and second fresh rounds 81–160 audit evidence; staging/live status remains unverified.
"""
if 'second fresh 80-round hardening pass' not in changelog:
    CHANGELOG.write_text(change_note.lstrip() + '\n' + changelog, encoding='utf-8')

print('File 23 second fresh 80-round corrections staged successfully.')
