# File23 V12.1 — Unicode PDF Runtime and Real MariaDB QA Register

**Date:** 13 November 2026 (Pakistan). **Target:** File23 Draft PR #29, candidate plugin Version 1.3.0. **Governing requirements:** WP-00, WP-04, WP-06, WP-08, WP-09, WP-12, WP-13, WP-14, WP-15; V12.1-STO-05, STO-07, STO-09. This is a source QA register, **NOT** a Hostinger release approval.

## 1. Root-cause evidence: existing Issue #32 (PDF data loss)

`SPDB_Export_Service::render_pdf()` previously rendered only `array_slice($rows, 0, 120)` and `array_slice($columns, 0, 5)`; its built-in Helvetica writer substituted every non-ASCII byte with `?`. The requested PDF was therefore silently incomplete even though the native report projection could include 125+ rows and 7 columns, including Urdu and Arabic. Synthetic source evidence and the original code are in Issue #32.

## 2. Candidate correction: complete Unicode/RTL mPDF

- Replaced the old handcrafted Helvetica writer with `includes/class-spdb-report-pdf-renderer.php`; the old `render_pdf()` is a narrow compatibility wrapper.
- Added `composer.json` and **`composer.lock`** resolved under PHP 8.0.30, pinning the source runtime to `mpdf/mpdf v8.3.1` plus its transitive packages. Requirements include PHP 8.0+, GD and mbstring. Release CI also uses Intl **for Unicode test normalization**, not as an extra production dependency.
- Built-in mPDF Unicode fonts and OpenType/RTL font-selection are enabled. Complete authorized, bounded rows/columns are rendered as a real HTML table; source text is UTF-8 validated and HTML escaped. Urdu/Arabic content is not converted to ASCII or rendered as raw active HTML.
- Any unsupported nested cell, duplicate/invalid column, malformed UTF-8, invalid control characters, out-of-budget model, unavailable PDF runtime/extensions, failed renderer or invalid/oversized PDF returns a **typed `WP_Error`**; no truncated/success-looking PDF is published.
- Every render uses a random, mode-0700 temporary directory outside WordPress document root. Recursive cleanup does not follow symlinks; an incomplete cleanup now prevents reporting PDF success. The approved mPDF code and fonts are bundled in the deterministic installable WordPress ZIP with the actual Composer lock and third-party license.
- `tools/build-final-release.sh` checks the actual embedded mPDF source, DejaVuSans, XB Riyaz and GPL license files and still constructs two bit-identical plugin ZIPs before accepting the archive.

## 3. Source and real-PDF acceptance tests

**Source contract:** `tests/v12-1-pdf-unicode-source-contract-tests.php` uses a recording mPDF test double to inspect the complete HTML of 121 rows × 7 columns, including the last row/column, Urdu/Arabic text, sanitized script markup, invalid UTF-8/nested data, complete temp cleanup and linked-file non-deletion. A mocked class is **not** proof of a real PDF.

**Real library integration:** `tests/v12-1-pdf-mpdf-integration-tests.php` invokes actual Composer-locked mPDF on 125 rows × 7 columns and confirms the returned PDF framing, file size, and (in the mandatory final-release job) Poppler `pdftotext`/ `pdfinfo` / `pdffonts` extraction. Arabic presentation forms are compared only **after Unicode NFKC normalization**, which restores their compatibility mappings. This is not visual acceptance of every Nastaliq ligature.

**PHP coverage:** `Baseline Integrity` and `Full Plan Completion` run on PHP 8.0, 8.1, 8.2 and 8.3 with pinned Composer installation. `File 23 Final Release Candidate` includes the real PDF extraction and deterministic bundled-installable-ZIP checks.

**Remaining:** Real Hostinger PHP GD/mbstring/runtime memory, upload-size limit, origin/CDN private-export URL deny, RTL screenshots with actual fonts, Urdu and Arabic copy/paste, PDF/CSV row-by-row parity, concurrent generation, File24/license review, and Founder sign-off. Issue #32 **must remain OPEN** until these are evidenced, even though source candidate now has corrected code.

## 4. WP-12: real SQL container is not the production database

Added `tests/v12-1-mariadb-private-erasure-integration.php`, run in a **disposable MariaDB 11.4 container** through `.github/workflows/file23-mariadb-source-integration.yml`. It exercises actual InnoDB `export_jobs` columns and indexes, 20,002 synthetic rows with two different owners, owner-limited keyset traversal, the exact `export_id`/filename invariant, two complete delete/verification passes, expiry with a frozen cutoff and row upper fence, active-expired-job denial, no cross-owner deletion and SQL transaction rollback. No real Hostinger database, user data, WordPress settings or production source artifact is read or changed.

**Source boundaries still open:** real File23 #31 Hostinger 20,001+ job interruption/resume, WordPress Cron lock behavior, File20/Hostinger deployed-byte and DB/migration attestation (#30/#45), backups and rollback, 10 native-owner contracts, 13 external/operational limits, two independent security reviews and Founder approval.

## 5. Required release-state reporting

| Field | Truth |
|---|---|
| Repository candidate | GitHub child Draft PR #29, exact final HEAD to be re-fetched |
| Parent PR / main | Unmerged and unchanged by this task |
| Deployed Version / actual installed ZIP | **UNVERIFIED** |
| DB/schema Version | **UNVERIFIED** |
| Migration State | **UNVERIFIED** |
| Live Verification Status | **NOT VERIFIED** |
| Source/CI | Report exact final-HEAD run IDs only after they complete |
| Merge / deploy / live retest | **NONE** |

**NO-GO for production:** Source tests and the GitHub artifact cannot certify the live Hostinger installed state. Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے.
