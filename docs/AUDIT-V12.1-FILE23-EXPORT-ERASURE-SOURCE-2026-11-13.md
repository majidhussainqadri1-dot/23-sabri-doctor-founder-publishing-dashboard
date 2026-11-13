# فائل 23 — V12.1: نجی برآمدات، حذف کاری اور بڑے ڈیٹا کی حفاظتی و ساختی تنقیح

**تاریخ:** 13 نومبر 2026ء۔ **عملی دائرہ:** File23 Version 1.3.0، GitHub child Draft PR #29؛ یہ repository/source review ہے، Hostinger/staging/live معائنہ نہیں۔  
**حاکم ترتیب:** V12.1 WP-00، WP-04، WP-09، WP-12، WP-13، WP-14، WP-15؛ STO-05، STO-07، STO-09؛ Live-First Exact-Deployed-State Rule۔  
**مراجع:** `includes/class-spdb-export-service.php`، `includes/class-spdb-operations-repository.php`، `docs/FILE23-V12.1-CODING-GAP-BACKLOG-2026-11-12.md`۔

## A — Source-level confidentiality and artifact ownership review

### V12.1-EXPORT-ERASURE-ID-01 — ایک Job کا حوالہ دوسرے Job کی فائل

**اصل وجہ (reproduced from repository source):** 4 حوالہ جاتی حذف کاری راستے `storage_ref` کی صرف `/^export_[a-z0-9]{32}\\.spdb$/` شکل، path containment اور symlink چیک دیکھتے تھے۔ SQL استعلام میں `export_id` موجود نہ تھا؛ اگر ایک expiry/owner record میں دوسرے درست Job کا `storage_ref` آجائے، تو اس دوسرے Job کی ciphertext فائل کو حذف کیا جاسکتا تھا۔ یہ source defect ہے؛ live Hostinger exploitation کا ثبوت نہیں۔

**اصلاح:** `SELECT id, export_id, storage_ref...` ہر متعلقہ استعلام میں؛ مشترک `storage_reference_matches_export_row()` صرف درست ID اور **عین `export_id . '.spdb'`** کو قبول کرتا ہے۔ چاروں راستوں میں empty reference کی موجودہ جائز صورت برقرار؛ non-empty mismatched reference پر fail closed۔ تمام synthetic SQL fixtures کو اصل سکیما کے مطابق `export_id` دیا گیا۔ `tests/v12-1-export-artifact-id-binding-tests.php` میں 25 unit assertions: 4 mismatch denials، 4 صحیح فائل کے deletion cases، foreign file untouched، verify-only denial، active legacy cleanup refusal، اور resolved-path unlink invariant۔ **SHA `03812214fca39a771edaf40014ca0a1da9a7dcae` پر Baseline #1623، Full #674، Final #428 کامیاب۔**

### V12.1-LEGACY-RETENTION-ACTIVE-02 — چلتے ہوئے Job کی expiry صفائی

**اصل وجہ:** legacy `cleanup_expired_files()`، نئے paged function کے برعکس، status `queued/processing` کو روکنے کی شرط استعمال نہیں کرتا تھا۔ active مگر expired job کی valid ciphertext metadata سے deletion کا خطرہ باقی تھا۔

**اصلاح:** legacy استعلام میں status شامل؛ `queued/processing` کے لیے `spdb_export_cleanup_active_job`، فائل بلا حذف۔ legacy `erase_user_files()` میں `unlink($real)` کے بجائے `unlink($path)` تاکہ جانچ کے بعد link/path تبدیل ہونے پر سابق resolve کردہ target کو unlink کرنے کا خطرہ کم ہو۔ 3 نئے regression assertions existing 25-unit suite میں شامل؛ exact source QA green۔

### V12.1-PRIVATE-DIR-SYMLINK-03 — guard لکھنے سے پہلے directory ownership

**اصل وجہ:** `private_directory()` نے `.htaccess`، `index.php`، `web.config` کو سختی سے جانچا، مگر **خود `spdb-private-exports` directory** اگر symlink ہو تو `wp_mkdir_p`، `chmod` اور guard writes بیرونی path تک جاسکتے تھے۔

**اصلاح:** creation سے پہلے `is_link($directory)` رد؛ creation کے بعد `realpath` سے resolved WordPress uploads root کی **عین مطلوبہ child-directory** مطابقت؛ mismatch پر `spdb_export_storage_path_invalid`، guard writes سے پہلے انکار۔ `tests/v12-1-private-storage-directory-binding-tests.php` کے 5 assertions: symlinked external directory denied، بیرونی data/guard untouched، normal path اور guards still valid۔ یہ source hardening ہے، IIS/LiteSpeed/Nginx/CDN کے HTTP edge deny کا بدل نہیں۔

## B — Personal data portability completeness review

### V12.1-PRIVACY-PAGE-CLAMP-04 — صفحہ 10000 پر نامکمل برآمد کا چکر

**اصل وجہ:** `privacy_export()` نے user-requested page کو `min(10000, page)` کردیا؛ WordPress Core اگر 10001 یا اس سے زیادہ صفحہ مانگے تو 10000 دوبارہ واپس آسکتا ہے۔ بھرے صفحات کی صورت میں تکرار یا نامکمل برآمد، اس لیے یہ source logic defect ہے۔

**اصلاح:** اصل page برقرار؛ صرف PHP integer offset overflow کو `spdb_privacy_page_overflow` سے قبل از SQL رد؛ باقی results کو وہی bounded `per_page <= 200` contract۔ `tests/v12-1-privacy-deep-page-tests.php` کے 6 assertions: page 10001 پر item 1000001، offset 1000000، correct completion، `PHP_INT_MAX` پر no-read error۔ اس سے حقیقی million-row WordPress/MariaDB load acceptance یا concurrent pagination stability ثابت نہیں ہوتی۔

## QA, code ownership and release isolation

یہ **4 الگ source findings** ہیں: artifact ID mismatch، legacy active-job retention، linked private directory، اور privacy page clamp۔ `unlink($real)` سے `unlink($path)` دفاعی بہتری کو الگ پانچواں runtime incident نہیں شمار کیا گیا۔ اس نشست میں 3 نئے test files مجموعی **36 executable assertions** (25+5+6)؛ پرانے suite fixtures کو schema parity کے لیے تازہ کیا۔ موجودہ Source regression سبز ہونے سے یہ live verified/resolved نہیں بن جاتے۔

**Source review round A:** identity, private path and deletion authority inspected؛ invalid job-ID binding and directory symlink now fail closed۔  
**Source review round B:** 20,001+ continuation, legacy path and 1,000,001st portability item regression inspected؛ bounded/owner-scoped behavior tests passed۔  
A/B دونوں **داخلی تحلیلی نظرثانیاں** ہیں، دو آزاد انسانی reviews نہیں۔ File24 اور Founder sign-off اب بھی لازم ہیں۔

### ہر ماحول کا الگ ثبوت

| حقیقت | موجودہ معلومات |
|---|---|
| Repository main | `dcae138e6073f4d0ff596623deb05b9940b8271b` (source-only) |
| Parent PR #26 | `df59cb2be8c1a69dc3539bc7e0a58d5d79125ac3`، unmerged |
| Child PR #29 | Exact active HEAD تازہ fetch سے درج ہو؛ Draft |
| Deployed Version | **UNVERIFIED** |
| DB/schema Version | **UNVERIFIED** |
| Migration State | **UNVERIFIED** |
| Live Verification Status | **NOT VERIFIED** |

**مستقل رکاوٹیں:** File23 #30 / File20 #45 trusted deployed attestor؛ File23 #31 real Hostinger >20,001 mixed-owner SQL/cron/restore/rollback؛ 10 cross-module contracts، 13 known limitations، staging اصل URL-origin/CDN denial، دو آزاد reviews، Founder approval۔ Hostinger WordPress.com Jetpack disconnect اور WPVibe کے 530 response سے نہ actual deployed source ثابت ہوتا ہے نہ site-down root cause۔

**NO-GO:** ماخذی ٹیسٹ اور پیکیج تیار ہونا live deploy/approval نہیں۔ بانی کی الگ منظوری سے پہلے کوئی merge/deploy/DB migration نہیں۔  
