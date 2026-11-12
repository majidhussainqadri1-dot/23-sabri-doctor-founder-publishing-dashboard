# فائل ۲۳ — جامع مرکزی منصوبہ V12.1 کے تحت باقی کوڈنگ، تقاضے اور قبولیتی رجسٹر
**تاریخِ مطالعہ:** ۱۲ نومبر ۲۰۲۶ء (پاکستان)  
**اصل مربوط منصوبہ:** «فائل ۲۳ — صابری بانی و ڈاکٹر اشاعتی ڈیش بورڈ — جامع تنقیدی منصوبۂ کوڈنگ و تکمیل — مرکزی پلان V12.1»، ۱۲ نومبر ۲۰۲۶ء، بالخصوص ابواب ۴، ۷–۱۶۔  
**حاکم منصوبہ:** «صابری سوشل ہومیوپیتھی پلیٹ فارم — جامع مرکزی ماسٹر پلان V12.1»، ۱۰ نومبر ۲۰۲۶ء، بالخصوص باب ۱۲ (صفحہ ۲۱۹–۲۲۷)۔  
**اس دستاویز کا کام:** مذکورہ **اسی ۱۶ ورک پیکیجز** والے منصوبے کا قابلِ تجدید، ثبوتی «بقایا کوڈنگ و قبولیت» ضمیمہ۔ پرانا منصوبہ منسوخ یا متوازی مادر منصوبہ نہیں بنتا۔

## تازہ عملی پیش رفت — ۱۲ نومبر ۲۰۲۶ء، فائل 23 سورس آڈٹ (بعد از ابتدائی رجسٹر)

**اطلاق:** اس رجسٹر کی اصل تاریخی snapshot ذیل میں برقرار ہے۔ یہ حصہ اس کے بعد کے source commits کی تازہ تکمیل اور باقی رکاوٹوں کو شمار کرتا ہے۔ GitHub PR #29 اسی Version 1.3.0 candidate کا Draft child ہے؛ `main`، Hostinger اور DB کو اس رپورٹ سے ہرگز تبدیل شدہ یا verified نہ سمجھیں۔

| شناخت | ماخذ سے ثابت اصل نقص | کوڈنگ و آزمائش کی حالت | قابلِ قبول باقی ثبوت |
|---|---|---|---|
| V12.1-GUARD-01 (پہلا مرحلہ) | نجی export guard files کی موجودگی تو دیکھی جاتی تھی مگر سالمیت نہیں | Guard type/content/symlink fail-closed؛ مخصوص regression green | واقعی Hostinger origin/CDN access-denial |
| **V12.1-STO09-ERASE-01** | `erase_user_files()` فولڈر/SQL/حذف کی ناکامی پر خاموش صفر دے سکتا تھا، جب کہ `erase_personal_data()` پھر متعلقہ metadata erase کرتا تھا؛ orphan ciphertext/false completion کا خطرہ | دونوں source methods patched؛ SQL failure، malformed ref، symlink، cursor، 20,000-record cap، idempotent retry اور record-preservation regressions؛ PHP 8.0–8.3 CI source-pass | حقیقی filesystem permission failure، deletion concurrency، retentions، Hostinger staging |
| **V12.1-STO05-EXPORT-02** | `process()` کا `file_put_contents` پہلے سے موجود report path/symlink پر overwrite کرسکتا تھا | Exclusive `fopen(...,'xb')` writer، path/name validation، partial-write cleanup، encrypted-before-write gate اور dedicated regression؛ **اس تازہ ترمیم کا exact-head CI الگ دوبارہ درکار** | Hostinger temp/private path permissions، report retries/concurrency، CDN denial |
| **V12.1-STO09-EXPIRED-03** | `cleanup_expired_files()` کا `realpath` کے بعد `unlink($real)` دوسری فائل کی Symlink منزل حذف کرسکتا تھا؛ 100 مکمل بیچوں کے بعد بھی غیر جانچے رہ جانے والے expired records کے باوجود success آتا تھا | Raw reference validation، symlink denial، `unlink($path)`، cursor monotonic، truncation پر WP_Error؛ `tests/v12-1-expired-export-retention-tests.php`؛ source QA | اصل Hostinger path، صفائی کا متواتر عمل، 20k سے زائد ریکارڈ کا محفوظ continuation | 
| **GAP-CODE-01 / Issue #30** | Staging acceptance کو حقیقی deployed bytes اور DB/schema/migration سے منسلک کرنا | File23 consumer fail-closed source موجود؛ حقیقی trusted owner attestor کی عدم تصدیق برقرار | File20 owner issue #45، real measured Hostinger bytes، negative migration tests، Founder staging acceptance |

**نیا مصدقہ source-audit tally:** اس تازہ دور میں **۳** مزید source defects دریافت اور اصلاحی کوڈ درج؛ سابقہ GUARD-01 کے ساتھ **۴ source-level repairs candidate**؛ سابقہ ایک test-fixture defect درست؛ release-attestation مالک/نصب-حالت کی **۱ کھلی رکاوٹ**؛ ۱۳ known operational limitations برقرار۔ یہ صفر لائیو defects کا دعویٰ نہیں۔

**تینوں نئی source اصلاحات کا منتخب کوڈ/ٹیسٹ HEAD:** `7ce22a488129d5cbb76be4d540ab5a3ae0494468`؛ اس SHA کے GitHub workflows کی حالت کو آخری docs-only commit کے بعد دوبارہ جانچنا لازم ہوگا۔

**نئے کوڈ کا مقام:** `includes/class-spdb-export-service.php` اور `includes/class-spdb-privacy-integration.php`؛ ٹیسٹ: `tests/v12-1-personal-erasure-safety-tests.php`، `tests/v12-1-export-exclusive-write-tests.php`، `tests/v12-1-expired-export-retention-tests.php`؛ پرانی test-continuity شرط کو `tests/forty-round-review-gate-tests.php` میں جدید encrypted-before-exclusive معیار کے مطابق بدلا گیا۔

**قبولیت کا حکم:** ورک پیکیجز WP-04، WP-05 اور WP-09 کے source-hardening حصے آگے بڑھے، لیکن WP-12 کی real 10k+ DB/migration recovery، WP-13 کی آخری SHA پر جامع CI اور دو بیرونی آزاد reviews، WP-14 Staging، WP-15 Live سب ابھی اصل ثبوت مانگتے ہیں۔ Issue #30 اور File20 #45 اختتامی blocker ہیں۔ اس طرح ۱۶ میں سے ۱۶ ورک پیکیجز کے الگ acceptance gates برقرار ہیں؛ کسی کو صرف source patch پر مکمل Release نہیں لکھا جائے گا۔

### نئی قابلِ شمار باقی کوڈنگ — WP-12 اور Issue #31

STO-09 کی دونوں اصلاحات میں 20,000 سے زائد ریکارڈ پر نامکمل حذف کاری کو واضح `WP_Error` کے ساتھ روکا گیا ہے، **لیکن یہ اختتامی کارکردگی نہیں**: اگلی کوشش پہلے ہی SQL ریکارڈ سے دوبارہ شروع ہونے کی وجہ سے بار بار اسی حد پر رک سکتی ہے۔ لہٰذا بڑا ڈیٹاسیٹ محفوظ انداز میں مکمل کرنے کے لیے الگ audit-bound، مالک سے مربوط، resumable deletion/retention protocol درکار ہے۔ اس کا مخصوص source/operational acceptance backlog **File23 GitHub Issue #31** میں درج ہے۔ اسے پروڈکشن پر پیش آنے والا ثابت شدہ بگ نہ کہا جائے؛ یہ **سورس میں ثابت شدہ scalable-continuation gap** ہے۔

**موجودہ الگ باقی کام کی گنتی:** (۱) File20/Hostinger کے حقیقی deployed-byte attestation کا انضمام — File23 Issue #30 اور File20 Issue #45؛ (۲) File23 کے 20k+ ریکارڈ کے لیے محفوظ قابلِ استمرار صفائی — Issue #31؛ (۳) سابقہ ۱۳ بیرونی release/operation حدود۔ ان میں سے کسی کو ۳۱ نئے runtime defects یا ۱۰۰٪ مکمل coding نہ سمجھا جائے۔

## ۱۔ تحقیق کا متعین قطعہ اور موجودہ حقیقت

| حقیقت | بنیاد / حد |
|---|---|
| مرکزی Repository `main` | `dcae138e6073f4d0ff596623deb05b9940b8271b`؛ نسخہ 1.2.0 |
| اصل ترقیاتی PR #26 | `df59cb2be8c1a69dc3539bc7e0a58d5d79125ac3`؛ نسخہ 1.3.0 |
| نئی حفاظتی Draft PR #29 | الگ child branch؛ اس میں V12.1 نقشہ بندی، فائل محافظ، ٹیسٹ اور یہ رجسٹر |
| آخری **جانچا گیا** سورس/ٹیسٹ HEAD (قبل از اس رجسٹر) | `c4e8e1140f2a822278292a2e7ebdd0a18506eeb1`؛ GitHub Actions #1522، #573، #327 کامیاب |
| لائیو نصب نسخہ، ZIP hash، ڈیٹابیس/سکیما، migration، logs | **غیر تصدیق شدہ**؛ GitHub سے ان کا استنباط ممنوع |
| لائیو حفاظتی نقائص کی قطعی تعداد | **نامعلوم**؛ Live Reality Freeze اور حقیقی معائنہ باقی |

**قانونِ شمار:** «کوڈنگ کا ثابت شدہ نقص»، «کسی اور مالک کے معاہدے/انضمام کی غیر تصدیق شدہ حالت»، «عملی staging/live جانچ»، اور «منصوبے کی دستاویزی کمی» چار الگ خانے ہیں۔ ایک ہی مسئلے کو چار بار نہ گنا جائے۔ صرف فائل کا موجود ہونا مکمل رفتار/حفاظت/فائدہ ثابت نہیں کرتا۔

## ۲۔ موجودہ کوڈنگ کی باقی کمی — قابلِ دفاع گنتی

| شمار کا خانہ | ثابت شدہ تعداد | علمی نتیجہ |
|---|---:|---|
| **ابھی کھلا، خاص File 23 سورس/ریلیز کنٹرول کا نقص** | **۱** | GAP-CODE-01: `class-spdb-activation-wizard.php` میں سابق staging acceptance کی پڑتال نصب شدہ exact artifact اور schema/migration سے مستقل مطابقت ثابت نہیں کرتی؛ issue #30 |
| سابقہ ماخذی حفاظتی نقص جس کا پیچ و CI ہوچکا | **۱ اصلاح شدہ** | `private_directory()` میں موجودہ protection files کی نوعیت اور مواد کی جانچ؛ PR #29 |
| نئے ٹیسٹ میں پیدا ہونے والی خرابی، بعد میں درست | **۱ اصلاح شدہ** | MFA regression میں duplicate File00 class؛ correction `12ff57e14b105623615808357d8eeedf0acce327`؛ بعد کے CI سب کامیاب |
| V12.1 کی نئی ۱۷ شقیں جو File 23 کا خود مختار کوڈ بنانے کا حکم **نہیں** دیتیں | **۱۰ owner-contract / runtime-acceptance pending** | CF04، File00/02/24 اور دیگر اصل مالکان کا ثبوت درکار؛ انہیں ۱۰ bugs نہ لکھیں |
| ۱۷ شقوں میں File 23 کا سورس/صارف معاہدہ موجود مگر runtime ابھی غیر ثابت | **۷ source-evidenced** | STO-05/07/08/09؛ ACT-03/04/06؛ حقیقی میزبان پر آزمائش ضروری |
| معلوم operational / release limitations | **۱۳** | `F23-LIM-001..013`؛ ۱۱ پرانی + ۲ V12.1 اضافی؛ یہ ۱۳ لازماً coding defects نہیں |
| غیر مکمل قبولیتی اختتامی مراحل | **۲** | WP-14 staging sign-off اور WP-15 live parity؛ ان کا نہ ہونا «قائم شدہ runtime bug» نہیں |
| **سارے منصوبے کی باقی coding bugs کی قطعی کل تعداد** | **ابھی متعین نہیں** | مکمل source-line/code-to-requirement اور real integration audit کے بغیر قطعی ۱/۱۰/۱۳ یا فیصدی تکمیل کا دعویٰ غلط ہوگا |

**تصدیق کا مفہوم:** یہ جدول متعلقہ *معائنہ شدہ دائرے* کا شمار ہے؛ پوری Repository یا تمام ۲،۰۶۶ مرکزی IDs کا قطعی defect audit نہیں۔

## ۳۔ منصوبے کے ۱۲۸ متعلقہ اندراج: موجودگی، نہ کہ بے شرط تکمیل

- ۷۰ منتخب `CV` IDs (CV-050..062 اور CV-229..285): موجودہ source traceability میں درج؛ فی-ID اصل owner، runtime action، منفی test اور اصل DB/state کی علیحدہ item-level تحقیق کا بقیہ کام۔
- ۱۶ `AJ` قبولیتی سفر: source mapping موجود؛ حقیقی user journey، File00/02/19/20/21/22/24/25/26 اور دیگر native providers پر sign-off نہیں۔
- ۲۴ `F23-FPI` advisory features: catalog/UI/tests موجود؛ source advisory-only اور donor-neutral؛ real provider input، privacy، quota، latency اور Founder outcome ابھی قابلِ جانچ۔
- `F23-CEN-01`: dashboard صرف federated command center؛ File21/22/26 یا CF04 data truth کی نئی نقل نہیں۔
- V12.1 کے ۱۷ نئے `STO-01..10` اور `ACT-01..07`: child PR #29 میں additive owner/consumer crosswalk؛ **۷ سورس/consumer-evidenced، ۱۰ owner-contract pending**؛ کسی کو لائیو accepted نہ کہا جائے۔

**128 = 70 + 16 + 24 + 1 + 17؛** یہ *منصوباتی coverage units* ہیں، **128 کامیاب operational features** نہیں۔

## ۴۔ ۱۶ ورک پیکیجز کا تفصیلی باقی کام، موجودہ کوڈ اور Go/No-Go

| WP | موجودہ source شواہد / حقیقی حالت | باقی کام / کوڈنگ تقاضا | قبولیت اور درجہ |
|---|---|---|---|
| **WP-00 — Truth Freeze** | `main`، PR26 اور PR29 exact SHA/CI source freeze ہوا | Hostinger installed plugin version، active files، artifact digest، WP/PHP، DB schema، `wp_options`، migration، errors اور provider versions کے مستند شواہد؛ discrepancy پر Deployment-Parity Audit | **Source frozen؛ Live evidence Pending (E)** |
| **WP-01 — Requirement Crosswalk** | `docs/GOVERNING-PLAN-2026-TRACEABILITY.json` میں 70 CV، 16 AJ، 24 FPI اور نیا 17-ID ضمیمہ | ہر منتخب CV/AJ/FPI کے لیے اپنی read/write boundary، مثبت/منفی test، file/line trace، version/policy اور staging outcome کا الگ ریکارڈ؛ نئی STO/ACT native-owner compatibility | **جزوی/منصوباتی؛ مکمل item-level proof Pending (G)** |
| **WP-02 — Dependencies / Contract Parity** | `class-spdb-module-manifest.php` میں File00–26 discovery اور versioned adapters | CF01/04/05/06 سمیت اصل مطلوبہ provider contract/semantic version/privacy class/timeout/expiry، unknown/degraded اور exact deployed version؛ source manifest کو live سمجھنے سے اجتناب | **کوڈ موجود؛ حقیقی contract parity Pending (E)** |
| **WP-03 — Sensitive Actions & MFA** | Membership Guard، mutation guard، capability checks، تین V12.1 test suites میں MFA source consumer covered | real File00/02 session two-factor، suspended/pending/guardian/revoked roles، action-by-action object scope؛ stamp/acknowledgement کو MFA کا بدل نہ سمجھنا | **source evidence؛ live integration Pending (E)** |
| **WP-04 — Privacy / Exports** | AES-GCM encrypted reports، owner-HMAC، TTL، cohort >=20، guard fail-closed patch | نجی export URLs کا actual Hostinger/LiteSpeed/Nginx/CDN انکار، current object/rights revocation، sensitive-data leakage، slicing/nested metrics اور erasure reconciliation | **source patched؛ host denial Pending (E)** |
| **WP-05 — Storage Owner Integration** | Media reference projections/manifest موجود؛ CF04 canonical owner ہے | CF04 quarantine/scan/ready/reject، signed/private grants، EXIF/PDF derivative status، scanner outage، restore tombstones کی typed consumption؛ **نیا File23 storage backend ممنوع** | **native-owner contract Pending (E)** |
| **WP-06 — Review, Calendar, Tasks** | Role workspace، review calendar، operations service/broker، queue/idempotency code/tests | native File21/22 واقعی decision outcomes، retries، version collision، timezone/DST، partial-bulk authorization؛ live roles کے غلط اجازت والے tests | **source candidate؛ provider journeys Pending (E)** |
| **WP-07 — FPI 24** | `class-spdb-publishing-intelligence.php`، Analytics UI اور 24-ID tests | ہر 24 feature پر real provider provenance، scope/permission، output privacy، stale/unavailable state، advisory-only، no AI diagnosis/no-write؛ P0 پہلے | **source candidate؛ 24 real integrations Pending (E)** |
| **WP-08 — UX/RTL/Accessibility** | File25 green token consumer، RTL/LTR، focus/reduced-motion/low-data CSS | حقیقی Urdu/Arabic/English screens، screen reader، 200–400% zoom، keyboard، mobile/tablet، low bandwidth، duplicate IDs/focus order | **static code؛ browser acceptance Pending (E)** |
| **WP-09 — Private Cache / XSS / IDOR** | REST private/no-store، authorization/nonce, escaping/projection validation | LiteSpeed cross-user caching، anonymous-private leakage، header inspection، foreign cursor/IDs، revoked-session browser, proxy bypass | **source controls؛ real cross-user denial Pending (E)** |
| **WP-10 — Observability / Incident** | Audit chain، background job health، File24 assurance projection، runbooks | File24 sanitized incident evidence، on-call names، alert thresholds، key rotation، failover, lock/cron/retry, incident drill؛ no false "healthy" | **source candidate؛ runtime evidence Pending (E)** |
| **WP-11 — Editorial Ergonomics** | Founder/Doctor/Teacher/Admin studio and editorial UI code | actual user flow، source warning/correction impact، same-owner results، translation, no phantom counts/duplicate edit/composer | **source candidate؛ Founder UX sign-off Pending (E)** |
| **WP-12 — Load / Recovery** | bounded queries and modeled 10k operations، cleanup/repair tooling | **حقیقی 10k+ database objects**، query/time budget، restoration/rollback, affected delete/tombstone verification، sample recovery time and DB indexes | **modeled test only؛ real benchmark Pending (E)** |
| **WP-13 — Reviews/CI/Package** | PR29 exact code HEAD `c4e8e11...` پر three workflow green; 2 internal source review passes | source کی آخری تبدیلی کے بعد independent A/B reviews، reported found/fixed/remaining count؛ exact **final** SHA، deterministic ZIP + checksums، companion pins؛ historical runs reuse نہیں | **automated source QA passed; formal independent acceptance Pending (E)** |
| **WP-14 — Staging Acceptance** | Release sign-off اور activation wizard source موجود | **GAP-CODE-01**: same-version different installed artifact کو falsely accepted نہ رہنے دینا؛ trusted deployed-byte attestation, schema/migration parity، owner contracts؛ پھر Hostinger smoke, LiteSpeed/roles/rollback | **۱ معلوم open code/release gap + staging not accepted (D/E)** |
| **WP-15 — Live Rollout / Parity** | live deployment کا ثبوت نہیں | approved merge/package، stage-to-live parity، DB/migration/options، real role smoke, revoke/purge, monitoring/rollback + Founder approval | **not deployed/not live verified (E)** |

### ۴۔۱ حقیقی کوڈنگ کے لیے لازم نئی تکمیلی قبولیت
1. ہر نئی code تبدیلی `requirement ID → owner → reproducible failing test → root cause → patch → passing retest → fresh review → exact SHA` کے ساتھ ہو۔
2. UI، source metadata اور actual provider status تینوں مختلف ہوں تو authoritative native owner فیصلہ کرے؛ File 23 اپنا shadow backend نہ بنائے۔
3. 1.3.0 کی feature-list «code presence» ہے؛ all 24 FPI اور all 16 AJ عملی طور پر قبول کرنے کے لیے provider-specific tests ضروری ہیں۔
4. File23 Scope کے باہر File00/02/24/CF04 نقص ملے تو متعلقہ مالک کے ریپو میں الگ issue/PR ہو؛ File23 میں افقی patch-stack نہیں۔
5. Same-version different build، altered plugin file، stale/absent attestation، schema mismatch، migration pending، wrong environment، expired/revoked verification اور Founder approval absent سب release denied کریں۔
6. Protected private exports کو raw public upload URLs سے براہ راست حاصل کرنا ممنوع ہو، صرف authentication guard کے code کا موجود ہونا کافی نہیں؛ actual edge/origin deny test لازم ہے۔

## ۵۔ ثابت شدہ واحد کھلا کوڈنگ/ریلیز نقص: GAP-CODE-01

**اصل مقام:** `includes/class-spdb-activation-wizard.php::state()` اور `::record_acceptance()`؛ **Issue:** #30۔  
**Evidence:** صارف کے فراہم کردہ `source_commit` / `package_sha256` کی صرف hex syntax اور قبول شدہ نسخہ کا `SPDB_VERSION` سے تقابل ہوتا ہے؛ staging state پر نصب کردہ *موجودہ* build/DB/migration کی ایک آزاد trusted runtime attestation code میں لازم نہیں۔ یکساں version مگر مختلف files پر سابقہ acceptance خودبخود stale ہو سکتی ہے۔

**ضروری source remediation:**
- ایک owner-authorized، server-side، versioned deployed-attestation contract مقرر ہو (File20/deployment operator؛ File24 assurance consumer)؛ **browser input، arbitrary `wp_option` یا user-supplied checksum خود proof نہیں**۔
- Signature/trust + actual active artifact identity + relevant schema/migration + environment + freshness/revocation، ہر state/read پر fail-closed check؛ نہ ہو تو `staging_accepted=false` اور `live_activation_allowed=false`، اور وجہ واضح۔
- File23 acceptance میں old evidence preserved/auditable ہو، مگر valid current release predicate سے جدا ہو؛ same-version rebuild stale record قبول نہ کرے۔
- کم از کم ۸ منفی tests: missing attestation، forged bytes، wrong plugin version، same-version changed hash، schema/migration drift، stale timestamp، wrong environment، revoked acceptance؛ ساتھ valid trusted staging case۔
- Hostinger پر real package/files hash اور schema/migration evidence کے بغیر صرف Unit Test کو issue closure نہ سمجھا جائے۔

**قابلِ قبول اختتام:** source tests سب green، fresh postchange review، trusted cross-file contract، actual Hostinger installed identity check، Founder sign-off اور live smoke؛ Issue #30 صرف appropriate evidence کے بعد بند ہوگا۔

## ۶۔ پہلے مرحلے کے ۸ عملی اقدامات

1. موجودہ `main/PR26/PR29` exact freeze اور ۱۶ WP × ۱۷ new IDs × 24 FPI mapping کو review-register میں درج کرنا (نئی تبدیلی کا date+SHA)۔
2. **GAP-CODE-01** کے failing acceptance-identity tests پہلے؛ مختصر guarded patch اس کے بعد؛ code owner/real deployment attestation نہ ملنے پر truthful fail-closed state اور issue کھلا رکھنا۔
3. V12.1 `STO-07` رپورٹ URL origin/CDN block کی staging-specific independent negative checklist؛ File23 encoded guard کا test اس کا بدل نہیں۔
4. `ACT-03/06` approved/suspended/2FA/session revoke عملی native File00/02 journey؛ `ACT-04` privacy.
5. P0 FPI-01..12 کے real-provider truth, authorization, no-write/clinical safety tests؛ عارضی mock کو evidence نہ کہنا۔
6. تمام ۱۶ AJ اور ۷۰ CV کے لیے individual test-owner crosswalk؛ verified source paths اور missing proof الگ نشان زد۔
7. دو code-change بعد fresh independent reviews + exact-head PHP 8.0–8.3، JS, privacy, architecture, deterministic ZIP, package digest۔
8. Hostinger staging/rollback/real load/Founder approval؛ اس کے بعد ہی `main` merge و live rollout کی الگ مجاز کارروائی۔

## ۶۔۱ ۱۲ نومبر ۲۰۲۶ء کا پہلا کوڈنگ مرحلہ — GAP-CODE-01 کی ماخذی اصلاح

اصل 1.3.0 Activation Wizard کی سورس خامی کی منفی آزمائش پہلے `tests/v12-1-deployed-attestation-tests.php` میں شامل کی گئی؛ اس وقت `669cf26852c2038a815f853498e697034232d6c3` پر تین GitHub Actions ناکام ہوئے، کیونکہ مطلوبہ تصدیقی ضابطہ موجود نہیں تھا۔

اصلاح `2bd422657f859c328518c38f2082d7b477ef0cc8`: `class-spdb-activation-wizard.php` اب `spdb/file23_deployed_artifact_attestation` کے ذریعے سرور-سائیڈ، موجودہ (۵ منٹ سے کم قدیم) attestation نہ ملنے پر staging acceptance اور live activation کو **false** رکھتا ہے۔ مصدر commit، package SHA-256، plugin version، environment، operations/collections schema اور completed migration کی مطابقت شرط ہے۔ Founder کی دی گئی hex شکل کو installed hash کی حقیقی پیمائش نہیں سمجھا جاتا۔

**اصل deployment provider کے لیے مربوط معاہدہ:** `docs/FILE23-DEPLOYED-ATTESTATION-CONTRACT-V1.md`؛ ۱۰ حقیقی staging منفی آزمائشیں اور File20/Hostinger/File24 ذمہ داریاں متعین۔ اس دستاویز کی موجودگی provider کے نصب یا محفوظ ہونے کا ثبوت نہیں۔

**تصدیق شدہ source QA:** اسی exact SHA پر Baseline Integrity #1527، Full Plan Completion #578، Final Release Candidate #332 تینوں GitHub Actions کامیاب؛ نئے negative test میں ۲۲ assertions کامیاب۔

**تبدیل شدہ شمار:** `GAP-CODE-01` کی **File 23 سورس سطح کی fail-closed اصلاح موجود ہے**؛ مگر **ایک حقیقی external deployment-attestor integration/acceptance blocker** باقی ہے، لہٰذا issue #30 اور F23-LIM-013 کو بند نہیں کیا گیا۔ اصل Hostinger package hash، DB/migration، trusted attestor اور staging/live tests غیر تصدیق شدہ ہیں۔ پہلی جدول اس کام سے *پہلے* کے frozen audit snapshot کو بیان کرتی ہے؛ نئے ماخذی patch کے بعد «ایک کھلا غیر مصحح File23 source guard» کہنا درست نہیں ہوگا، اور «لائیو مسئلہ حل» کہنا بھی غلط ہوگا۔

## ۷۔ ہر نشست کی قابلِ شمار رپورٹ کا غیر متغیر قالب

- Repository main SHA: ___؛ PR branch SHA: ___؛ plugin version: ___۔
- Confirmed source defects: اس نشست سے پہلے ___؛ نئے ___؛ اس نشست میں درست ___؛ passing retest ___؛ باقی ___۔
- Missing source functions (confirmed only): ___؛ incomplete source coverage requiring review: ___۔
- Native-owner/contract pending: ___؛ staging pending: ___؛ live pending: ___۔
- Critical/High/Medium/Low counts: ___؛ root cause / fixing SHA / test URL: ___۔
- Deployed plugin version: ___؛ deployed code checksum: ___؛ DB/schema version: ___؛ migration state: ___؛ live verification status: ___۔
- Founder's release approval: ___؛ remaining blockers: ___۔

**حاکم فیصلہ:** ۱ ثابت شدہ کھلا source/release assurance gap، ۱۰ V12.1 مالک-معاہداتی غیر تصدیق شدہ امور، ۱۳ مجموعی operational limits، ۲ آخر کے staging/live مراحل۔ ان سب کو جمع کرکے «۲۶ coding bugs» کہنا غلط ہوگا۔ اس رجسٹر کا حقیقی defect count مزید tests اور real evidence ملنے پر بدلے گا؛ ابھی مکمل ویب سائٹ کی coding 100% یا live صحت کی کوئی سند نہیں۔
