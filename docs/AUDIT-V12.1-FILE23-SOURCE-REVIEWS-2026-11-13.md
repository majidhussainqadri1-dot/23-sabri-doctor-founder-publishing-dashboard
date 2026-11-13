# فائل ۲۳ — جامع منصوبہ V12.1 کی ۱۳ نومبر ۲۰۲۶ء کی نئی سورس نظرثانیاں

**حاکم بنیاد:** فائل ۲۳ کا V12.1 جامع منصوبہ، WP-00، WP-04، WP-09، WP-12 تا WP-15؛ مرکزی V12.1 STO-07/09؛ Live-First Exact-Deployed-State + Root-Cause-First + Evidence-First exact HEAD۔  
**نوعیت:** دو الگ *داخلی* analytical source-review lenses؛ یہ دو **آزاد بیرونی جائزہ کار** نہیں۔ Hostinger، DB، staging اور live کو نہیں چھیڑا گیا۔  
**Working candidate:** File23 Version 1.3.0 child **Draft PR #29**؛ parent PR #26 اور main بدستور الگ رہیں گے۔

## Review A — نجی ڈاؤن لوڈ، encrypted artifact اور حقیقی فائل ID

**تصدیق شدہ سورس نقص: V12.1-STO07-DOWNLOAD-01۔** سابقہ `SPDB_Export_Service::download()` میں ڈیٹابیس کے خام `storage_ref` کو `basename()` سے تبدیل کیا جاتا اور `realpath()` کے بعد صرف اسی private folder کے اندر فائل ہونے اور hash ملنے پر ڈاؤن لوڈ جائز سمجھا جاتا تھا۔ اس سے اصل job ID سے مختلف raw reference اور **اسی directory میں موجود symlink** باقاعدہ مسترد نہیں ہوتے تھے۔ اگر filesystem میں غیر متوقع link/reference ہو تو دفاع کا مطلوبہ invariant کمزور ہوتا تھا۔ یہ **source semantics** کا نقص ہے؛ اصل Hostinger exploitation ثابت نہیں۔

**اصلاح:** `resolve_ciphertext_download_path()` helper اصل `export_ID.spdb` کی ID/نام مساوات، strict 64-hex SHA-256، regular readable file، `is_link()` denial، حقیقی private directory containment اور exact stored checksum کو لازمی قرار دیتا ہے۔ `download()` اسے authentication، current File00 authorization، owner binding اور expiry checks کے بعد استعمال کرتا ہے۔

**آزمائش:** `tests/v12-1-strict-private-download-path-tests.php` میں درست signed-job path، mismatched ID، raw traversal، wrong/blank/malformed hash، same-directory symlink with matching contents، missing artifact، wrong extension اور target integrity کے منفی/مثبت حالات شامل ہیں۔ **Source SHA `487a8165118cefd72ede018d817d63268d99b8be` پر Baseline #1575، Full #626، Final #380 سب SUCCESS۔**

**حد:** یہاں `.htaccess` کی حقیقی Nginx/OpenLiteSpeed/CDN enforcement، upload directory OS ownership، attacker write privileges اور HTTP GET/HEAD 403/404 host-side آزمائش نہیں ہوئی۔

## Review B — ناقص عملی جدولیں، جزوی مائیگریشن اور خودکار مرمت

**تصدیق شدہ سورس نقص: V12.1-DB-SHAPE-02۔** `SPDB_Operations_Schema::verify()` دس جدولوں کی موجودگی اور InnoDB انجن تو پرکھتا تھا، لیکن `SHOW COLUMNS` اور `SHOW INDEX` کے ذریعے ضروری جدولوں کے مکمل کالم/انڈیکس کی پڑتال نہیں کرتا تھا۔ اس لیے یکساں option version `1.1.0` کے باوجود نامکمل/خراب schema بعض صورتوں میں verified ہوسکتا تھا، جبکہ V12.1 کے مطابق DB/schema parity اور migration-state proof لازم ہیں۔

**اصلاحی امیدوار:** DDL سے ماخوذ ۱۰ operational tables کے حقیقی columns اور indexes کی مستقل `requirements()` manifest، موجودہ MySQL structure سے تطبیق، `spdb_operations_schema_column_missing` / `spdb_operations_schema_index_missing` / `spdb_operations_schema_inspection_failed` کی Fail-Closed خطائیں۔ مزید `maybe_upgrade()` اب **اسی recorded version** میں schema drift پر عام admin request کے دوران `dbDelta` کے ذریعے غیرمنظور خودکار مرمت نہیں کرتا؛ پہلے deployed-state freeze اور operator migration verification کا تقاضا ہے۔ نئی تنصیب یا باقاعدہ version upgrade کا اصل راستہ محفوظ ہے۔

**آزمائشی امیدوار:** `tests/v12-1-operations-schema-structure-tests.php`؛ ۱۰ native DDL definitions کے column/index manifest سے عین مطابقت، missing `storage_ref`، missing expiry index، invalid engine، unavailable SQL metadata، missing table اور same-version drift/normal verified case۔

**حالت:** اصلاحی source commits `3cd16803e0fe1a05bcdd6783eb989567a7964a38` اور `e55c2c4dac3b7cf5ac3ad861de92d48204b64f01` ہیں۔ **ان کا exact final SHA CI نتیجہ اس آڈٹ کے وقت ابھی Pending؛ پچھلے سبز workflow کو اس نئی تبدیلی کا ثبوت نہ لکھا جائے۔**

**فنی حد:** کالم/انڈیکس کے نام جانچنا مکمل MySQL type/collation/UNIQUE/constraint/cardinality parity یا حقیقی migration log کا بدل نہیں۔ File20/deployment attestor کو actual DB DDL، versions اور migration completion کو آزادانہ جانچنا ہے۔

## WP-00 — Hostinger installed-state read-only observation, provider نہیں

`tools/file23-readonly-host-evidence.php` مجاز، پہلے سے bootstrap شدہ **staging WP-CLI** پر installed plugin header/version، SHA-256 file-tree inventory، ۱۰ operations + ۳ collection schema verification، option values، symlink/unreadable files اور basic PHP/WP details کو **صرف ناپتا** ہے۔ نہ database-write، نہ activation، نہ deployment، نہ `verified=true` trusted contract۔

`tests/v12-1-readonly-host-evidence-tests.php` میں synthetic bytes پر header drift، altered bytes SHA، schema option mismatch، symlink، inactive plugin، no attestation claim اور unchanged external file آزمائے گئے۔ **`25081380d9467322e18edb0df13806788fb86463` پر Baseline #1576، Full #627، Final #381 سب SUCCESS**۔ Operator handoff `docs/V12.1-FILE23-HOSTINGER-READONLY-PARITY-HANDOFF-2026-11-13.md` میں موجود ہے۔

**ابھی غیر حاصل:** approved manifest سے actual installed identity comparison، real DB schema/migrations، Hostinger origin/CDN deny، File20 trusted attestor، independent reviews، Founder staging signoff، approved merge/rollout اور live parity۔ Jetpack source connector disconnected اور WPVibe site unconnected — کسی نے live source/DB پڑھنے یا تبدیل کرنے کا دعویٰ نہیں کیا۔

## شمار — اس نشست کے مخصوص شواہد

| خانہ | قابلِ دفاع شمار |
|---|---:|
| نئے ماخذی نقائص جو اس نظرثانی میں ملے | **۲** (download path + operational schema shape) |
| اصلاحی source commits | **۲ موضوعاتی اصلاحات**؛ schema gate کی final CI اب بھی تصدیق طلب |
| نئی executable test files | **۳** (download path، readonly host measurement، schema contract) |
| پہلے سے اصلاح شدہ WP-12 >20k مسئلہ | Source-level code موجود؛ **Hostinger قبولیت Pending (#31)** |
| File20 trusted attestor | **غیر مصدقہ (#45 / File23 #30)** |
| معلوم سابق عملی/release limitations | **۱۳**؛ ان میں سے کوئی فرضی runtime defect نہیں |
| لائیو نقائص یہاں ثابت اور لائیو اصلاح شدہ | **۰** |

**عملی فیصلہ:** PR #29 Draft رہے؛ 0 merge/deploy؛ GitHub/Hostinger parity ادھوری۔ اس نظرثانی کا کوئی source-only پاس فائل 23 کو staging-accepted، live-deployed یا production-resolved نہیں بناتا۔
