# File 23 — چالیس ادوار کی جامع نظرِ ثانی و اصلاحِ نقائص

**Candidate:** Version 1.2.0  
**Scope:** File 23-owned source، tests، documentation اور release tooling  
**Governing plans:** Definitive Master Plan v3.0؛ File 23 Final Central-Plan-Harmonized v3.0  
**تاریخ:** 04 اگست 2026ء — پاکستان معیاری وقت

## حاکم طریقۂ کار

ہر دور میں تازہ review → ثابت شدہ defect correction → targeted retest ہوا۔ جہاں نیا defect نہ ملا وہاں سابقہ invariant کی fresh regression verification درج ہے؛ فرضی defect نہیں بنایا گیا۔ یہ رپورٹ source/automated-QA evidence ہے، Hostinger staging، live deployment یا operational completion کا دعویٰ نہیں۔

### دور 01 — منصوباتی Traceability اور Release Truth

**مرکزِ نظرِ ثانی:** دونوں حاکم منصوبوں کے File 23 scope، owners، release statuses اور Definition of Done کو source tree اور deliverables کے مقابل پر دوبارہ نقش کیا گیا۔

**دریافت:** کوئی نئی scope omission نہیں ملی؛ سابقہ records میں source-complete اور staging/live-complete کے درمیان فرق برقرار تھا۔

**اصلاح:** Requirements traceability اور STATUS/RELEASE-SIGNOFF کو Version 1.2.0 candidate کے مطابق ہم آہنگ کیا گیا؛ staging/live claims بدستور ممنوع رکھے گئے۔

**دوبارہ آزمائش:** final-deliverables gate، traceability markers اور truthful-status assertions دوبارہ چلائے گئے۔

### دور 02 — Canonical Ownership اور No-Duplicate Backend

**مرکزِ نظرِ ثانی:** File 21، File 22، File 20، File 24 اور File 25 کی ملکیت کے خلاف direct writes، duplicate tables، duplicate shell یا Safe Mode engine تلاش کیے گئے۔

**دریافت:** کوئی native publication/profile/clinical/payment/message write نہیں ملا؛ مگر capability documentation میں File 23 Safe Mode authority کا stale اندراج موجود تھا۔

**اصلاح:** stale Safe Mode capability کو retired migration key بنایا اور documentation سے operational authority ہٹائی گئی۔

**دوبارہ آزمائش:** architecture guard اور direct-mutation searches دوبارہ کامیاب ہوئے۔

### دور 03 — File 00 Authority Freshness

**مرکزِ نظرِ ثانی:** ہر Founder، trusted publisher، institutional اور approved decision کو canonical File 00 assertions کے خلاف جانچا گیا۔

**دریافت:** متعدد services براہِ راست `smc_is_founder()`/`smc_is_trusted_publisher()` استعمال کررہی تھیں، جس سے canonical institutional assertion کو bypass کرنے کا امکان تھا۔

**اصلاح:** Founder/trusted resolution کو `SPDB_Membership_Guard` کے strict canonical helpers پر منتقل کیا گیا؛ malformed/incompatible canonical contract پر legacy fallback بند رکھا گیا۔

**دوبارہ آزمائش:** workspace، review/calendar، collection، inventory اور native-reference tests دوبارہ کامیاب ہوئے۔

### دور 04 — Capability Least Privilege

**مرکزِ نظرِ ثانی:** ہر role کے expected اور actual File 23 capabilities، stale grants اور restricted states جانچے گئے۔

**دریافت:** installer صرف grants reconcile کرتا تھا؛ پرانی یا منسوخ capability باقی رہ سکتی تھی۔

**اصلاح:** capability schema 4، exact reconciliation اور unauthorized/retired capability removal نافذ کیا گیا۔

**دوبارہ آزمائش:** capability-installer tests نے grant، removal، idempotency اور restricted-role boundaries پاس کیں۔

### دور 05 — Global Safe Mode Ownership

**مرکزِ نظرِ ثانی:** File 20 کے global Safe Mode پر File 23 کے کسی capability، option یا repair action کا قبضہ تلاش کیا گیا۔

**دریافت:** legacy `spdb_manage_safe_mode` capability role state اور docs میں باقی تھی۔

**اصلاح:** capability کو `retired()` migration list میں رکھا، تمام roles سے remove کیا اور File 20 ownership markers برقرار رکھے۔

**دوبارہ آزمائش:** architecture guard، dashboard state اور capability tests سبز ہوئے۔

### دور 06 — Restricted Account States

**مرکزِ نظرِ ثانی:** pending، rejected، expired، appeal-review، erasure-pending اور suspended accounts کے read/write paths جانچے گئے۔

**دریافت:** loose truth coercion اور scattered role helpers authority ambiguity پیدا کرسکتے تھے۔

**اصلاح:** approved/eligible/suspended/session booleans strict کیے گئے اور mutable actions کو current approved state سے باندھا گیا۔

**دوبارہ آزمائش:** workspace، REST permission اور security adversarial tests دوبارہ پاس ہوئے۔

### دور 07 — Task Ownership اور Assignment

**مرکزِ نظرِ ثانی:** personal/institution tasks، owner/assignee updates، reassignment اور stale versions جانچے گئے۔

**دریافت:** personal task دوسرے user کو assign ہوسکتا تھا اور assignee owner fields تبدیل کرسکتا تھا۔

**اصلاح:** own-scope assignment self-only، approved assignee check، assignee status-only updates، version conflict اور privacy-safe reason نافذ کیے گئے۔

**دوبارہ آزمائش:** governance markers، task repository tests اور full-plan adversarial suite کامیاب ہوئے۔

### دور 08 — Delegation Attenuation اور MFA

**مرکزِ نظرِ ثانی:** principal/delegate authority، start/expiry، revocation، providers اور execution-time revalidation دیکھی گئی۔

**دریافت:** delegate capability اور principal current authority کی مکمل attenuation ثابت نہیں تھی؛ sensitive domains delegate ہوسکتے تھے۔

**اصلاح:** current MFA، دونوں فریقوں کی capabilities، 90-day max، expiry/revocation، اور clinical/payment/message/identity/security provider denylist نافذ ہوئی۔

**دوبارہ آزمائش:** delegation security assertions اور regression suite پاس ہوئے۔

### دور 09 — Automation Governance

**مرکزِ نظرِ ثانی:** rule creation، enable/disable، event payload، autonomous actions اور replay behavior جانچا گیا۔

**دریافت:** high-risk fields اور audit reasons کی coverage نامکمل تھی۔

**اصلاح:** bounded payload، prohibited clinical/payment/message/autopublish fields، human governance، status reason اور atomic run evidence نافذ کیے گئے۔

**دوبارہ آزمائش:** automation source gates اور adversarial tests کامیاب ہوئے۔

### دور 10 — Provider Acceptance Maturity

**مرکزِ نظرِ ثانی:** provider registration، staged/production acceptance، version binding، revocation اور environment claims جانچے گئے۔

**دریافت:** runtime acceptance صرف registry strings سے ظاہر ہوسکتی تھی اور exact evidence binding مستقل نہ تھی۔

**اصلاح:** File 23-owned `SPDB_Adapter_Acceptance` service، exact provider/contract/plugin versions، evidence hash، MFA، staging-before-production اور revocation نافذ ہوئے۔

**دوبارہ آزمائش:** provider registration/acceptance markers اور full suite پاس ہوئے۔

### دور 11 — REST Authentication اور Nonce

**مرکزِ نظرِ ثانی:** ہر File 23-owned mutating route کے nonce، account state اور route-policy coverage جانچے گئے۔

**دریافت:** پرانے write permission checks میں explicit cross-cutting nonce evidence کافی نہ تھا۔

**اصلاح:** Operational Mutation Guard نے `X-WP-Nonce`/`wp_rest` verification ہر declared mutating route پر لازم کی۔

**دوبارہ آزمائش:** operational mutation guard اور security adversarial tests کامیاب ہوئے۔

### دور 12 — Same-Origin اور CSRF

**مرکزِ نظرِ ثانی:** Origin/Referer parsing، mixed ports، credentials-in-URL اور missing-origin behavior جانچا گیا۔

**دریافت:** URL user-info (`user:pass@host`) same-origin comparison کو مبہم بناسکتا تھا۔

**اصلاح:** candidate اور home URLs میں user/pass components صریح طور پر مسترد کیے گئے؛ browser writes پر origin/referer fail-closed رہا۔

**دوبارہ آزمائش:** same-origin unit assertions اور adversarial suite پاس ہوئے۔

### دور 13 — Idempotency اور Replay

**مرکزِ نظرِ ثانی:** task/delegation/rule/export/settings/repair/activation retries، altered payloads اور pending receipts جانچے گئے۔

**دریافت:** تمام File 23 mutations پر یکساں high-entropy idempotency contract پہلے نافذ نہیں تھا۔

**اصلاح:** payload-bound keys، duplicate replay، altered-payload conflict، pending/stale receipt states اور browser retry key retention نافذ ہوئے۔

**دوبارہ آزمائش:** 10,000-operation model اور mutation-guard tests کامیاب ہوئے۔

### دور 14 — Payload Bounds اور Resource Exhaustion

**مرکزِ نظرِ ثانی:** nested arrays، node count، long strings، malformed JSON اور response replay size جانچے گئے۔

**دریافت:** operational payloads پر depth/node/string limits نامکمل تھیں۔

**اصلاح:** depth 8، nodes 500، string 8192 اور replay response 65535 bytes limits نافذ ہوئے۔

**دوبارہ آزمائش:** payload-bound adversarial markers اور syntax/regression suite پاس ہوئے۔

### دور 15 — Object References اور Versions

**مرکزِ نظرِ ثانی:** provider/object IDs، native versions، control characters، unknown operations اور confirmation shape جانچی گئی۔

**دریافت:** object-version shape اور provider confirmation mismatch کی handling کمزور تھی۔

**اصلاح:** strict canonical IDs، bounded native version، control-character rejection، supported operation checks اور authoritative re-read/confirmation matching نافذ ہوئے۔

**دوبارہ آزمائش:** operation-broker contract اور review/calendar tests کامیاب ہوئے۔

### دور 16 — Nested Authority Injection

**مرکزِ نظرِ ثانی:** payload کے nested role، owner، user، approval، capability، environment اور acceptance keys تلاش کیے گئے۔

**دریافت:** صرف top-level field checks nested authority spoofing کو مکمل نہیں روکتے تھے۔

**اصلاح:** recursive authority-field and sensitive-domain rejection شامل کی گئی۔

**دوبارہ آزمائش:** contract tests اور full-plan adversarial suite پاس ہوئے۔

### دور 17 — Transactional Storage Engine

**مرکزِ نظرِ ثانی:** تمام File 23-owned tables کے engine اور transaction assumptions جانچے گئے۔

**دریافت:** transaction/rollback موجود تھا مگر InnoDB کی صریح installation/verification guarantee نہیں تھی۔

**اصلاح:** Operational schema 1.1.0 اور Collections schema 4 میں `ENGINE=InnoDB`، engine inspection اور controlled upgrade نافذ ہوئے۔

**دوبارہ آزمائش:** schema repository tests اور architecture guard پاس ہوئے۔

### دور 18 — Nested Atomic Mutations

**مرکزِ نظرِ ثانی:** REST outer transactions، background inner writes اور nested operations کے commit/rollback semantics جانچے گئے۔

**دریافت:** worker-side writes outer transaction کے بغیر audit کے ساتھ atomic نہیں تھے۔

**اصلاح:** repository `atomic()` helper نے transaction یا savepoint کے ذریعے rule-run، export completion/failure، privacy erasure اور retention cleanup کو atomic کیا۔

**دوبارہ آزمائش:** PHP lint، repository tests اور full regressions کامیاب ہوئے۔

### دور 19 — Audit Chain Concurrency

**مرکزِ نظرِ ثانی:** simultaneous audit inserts، previous hash selection، lock release اور malformed chain جانچی گئی۔

**دریافت:** last-hash read پر concurrent writers chain fork پیدا کرسکتے تھے۔

**اصلاح:** MySQL advisory `GET_LOCK`، `FOR UPDATE`، finally release اور chain verification نافذ کیے گئے۔

**دوبارہ آزمائش:** audit integrity markers اور architecture/security suites پاس ہوئے۔

### دور 20 — Export State Machine اور At-Rest Protection

**مرکزِ نظرِ ثانی:** queued→processing→ready/failed transitions، duplicate workers، file commit اور direct-file exposure جانچے گئے۔

**دریافت:** mark-processing result نظرانداز تھا؛ completion failure orphan file چھوڑ سکتی تھی؛ upload directory میں generated report plaintext تھا۔

**اصلاح:** state conflicts fail-closed، transition results checked، orphan cleanup، AES-256-GCM encrypted `.spdb` envelope اور ciphertext hash نافذ کیے گئے۔

**دوبارہ آزمائش:** export security markers، PHP lint اور full-plan tests پاس ہوئے۔

### دور 21 — Export Download Integrity

**مرکزِ نظرِ ثانی:** owner-bound signature، expiry، path traversal، symlink، hash، decryption اور response headers جانچے گئے۔

**دریافت:** path/hash checks موجود تھے مگر plaintext storage direct-server exposure کا residual risk تھا۔

**اصلاح:** basename/realpath boundary برقرار رکھتے ہوئے ciphertext verification، authenticated decryption، no-cache، nosniff اور plaintext-only authorized response نافذ ہوا۔

**دوبارہ آزمائش:** download source assertions اور security suite پاس ہوئے۔

### دور 22 — Background Job Claiming اور Locks

**مرکزِ نظرِ ثانی:** stale locks، concurrent claim، worker identity، retry and complete transitions جانچے گئے۔

**دریافت:** stale-lock recovery query failure خاموش رہ سکتی تھی اور claim failure observable نہیں تھی۔

**اصلاح:** stale recovery failure explicit error اور `spdb/background_job_claim_failed` observability event بنایا گیا۔

**دوبارہ آزمائش:** background-job markers اور regression suite پاس ہوئے۔

### دور 23 — Dead-Letter Atomicity

**مرکزِ نظرِ ثانی:** retry count، backoff، dead-letter write، audit اور operator notification جانچے گئے۔

**دریافت:** dead-letter DB transition audit failure کے باوجود committed رہ سکتی تھی۔

**اصلاح:** retry/dead-letter transition کو repository atomic/savepoint boundary میں رکھا اور audit failure پر rollback کیا۔

**دوبارہ آزمائش:** dead-letter/security markers اور PHP/full suite پاس ہوئے۔

### دور 24 — Privacy Export، Erasure اور Data Minimization

**مرکزِ نظرِ ثانی:** preferences، views، tasks، delegations، rules، exports، receipts، collections، knowledge links اور files جانچے گئے۔

**دریافت:** privacy exporter ایک page پر محدود تھا؛ tasks/delegations/rules/collections/receipts مکمل erase/export نہیں ہوتے تھے؛ export-file cleanup 100 rows تک تھا۔

**اصلاح:** paginated export، own-data deletion، institutional pseudonymization، encrypted-file cursor cleanup، receipt export/erase اور collection/link coverage نافذ ہوئی۔

**دوبارہ آزمائش:** privacy integration markers، REST privacy tests اور full regressions پاس ہوئے۔

### دور 25 — Retention Lifecycle

**مرکزِ نظرِ ثانی:** expired metrics/exports/health، completed/dead jobs، tasks اور audit evidence جانچے گئے۔

**دریافت:** retention deletes کے بعد audit failure پر partial committed cleanup ممکن تھا۔

**اصلاح:** retention cleanup کو atomic transaction/savepoint اور mandatory audit کے ساتھ نافذ کیا گیا۔

**دوبارہ آزمائش:** retention source gate اور regression suite پاس ہوئے۔

### دور 26 — Projection Privacy

**مرکزِ نظرِ ثانی:** provider DTOs، public/private fields، sensitive key patterns اور degraded projections جانچے گئے۔

**دریافت:** کوئی نیا source defect نہیں ملا؛ clinical/patient/message/payment/raw event fields پہلے ہی rejected تھے۔

**اصلاح:** existing validator boundaries برقرار رکھے گئے اور new privacy erasure/export work سے cross-check کیا گیا۔

**دوبارہ آزمائش:** operational validator اور privacy/security suites پاس ہوئے۔

### دور 27 — Analytics Thresholds اور Cache

**مرکزِ نظرِ ثانی:** cohort threshold، aggregate-only data، owner scope، expiry اور cache dimensions جانچے گئے۔

**دریافت:** کوئی raw analytics warehouse نہیں ملا؛ institutional check میں loose `!empty` باقی تھا۔

**اصلاح:** institutional analytics/report checks strict approved/eligible/non-suspended assertions پر منتقل کیے گئے۔

**دوبارہ آزمائش:** analytics/full-plan tests پاس ہوئے۔

### دور 28 — Optional AI Safety

**مرکزِ نظرِ ثانی:** AI request types، evidence citations، autonomous authority، sensitive payload اور audit failure جانچے گئے۔

**دریافت:** AI assistance result audit failure کے باوجود user کو واپس ہوسکتا تھا۔

**اصلاح:** audit failure fail-closed کیا گیا؛ AI بدستور suggestion-only، cited، human-reviewed اور execution-disabled ہے۔

**دوبارہ آزمائش:** AI safety markers اور adversarial tests پاس ہوئے۔

### دور 29 — Degraded، Error اور Recovery States

**مرکزِ نظرِ ثانی:** provider exceptions، malformed responses، unavailable dependencies، queue failures اور no false success جانچے گئے۔

**دریافت:** بعض background enqueue/transition failures خاموش رہ سکتے تھے۔

**اصلاح:** bounded failure actions، transition checks، explicit error codes اور manual/degraded guidance نافذ ہوئے۔

**دوبارہ آزمائش:** error-state source checks اور full regressions پاس ہوئے۔

### دور 30 — Accessibility Semantics

**مرکزِ نظرِ ثانی:** focus-visible، dialog focus restoration، keyboard، reduced motion، forced colors اور status messaging جانچا گیا۔

**دریافت:** کوئی نیا source blocker نہیں ملا؛ سابقہ accessible modal/focus fixes برقرار تھیں۔

**اصلاح:** existing semantics برقرار رکھ کر new routes/docs کو accessibility gate میں شامل کیا گیا۔

**دوبارہ آزمائش:** static accessibility gates اور dashboard tests پاس ہوئے۔

### دور 31 — Responsive، RTL اور Low-Bandwidth

**مرکزِ نظرِ ثانی:** 320–1920px CSS boundaries، RTL selectors، horizontal overflow، offline/retry behavior اور compact navigation جانچے گئے۔

**دریافت:** کوئی نئی regression marker نہیں ملی۔

**اصلاح:** existing RTL/reduced-motion/forced-color/offline behavior برقرار رکھا گیا؛ staging evidence بدستور external gate ہے۔

**دوبارہ آزمائش:** CSS/JS syntax اور static responsive markers پاس ہوئے۔

### دور 32 — Pagination اور Query Bounds

**مرکزِ نظرِ ثانی:** inventory، operations، collections، saved data اور privacy export pagination جانچی گئی۔

**دریافت:** متعدد page limits 100,000 تک تھیں، جو expensive offsets اور abuse risk بڑھاتی تھیں۔

**اصلاح:** interactive page ceiling 1,000 اور privacy cursor/page bounds نافذ کیے گئے؛ per-page limits برقرار رہیں۔

**دوبارہ آزمائش:** workspace/collections/full-plan tests پاس ہوئے۔

### دور 33 — Private Cache، Noindex اور Response Isolation

**مرکزِ نظرِ ثانی:** dashboard headers، REST responses، idempotent replay، download responses اور LiteSpeed risk جانچا گیا۔

**دریافت:** transaction commit failure کے بعد cached completed receipt false-success replay کرسکتی تھی۔

**اصلاح:** rollback کے بعد option cache clear اور failed receipt finalization شامل کی گئی؛ private/no-store/noindex headers برقرار رکھے گئے۔

**دوبارہ آزمائش:** mutation guard tests اور private-cache static gate پاس ہوئے۔

### دور 34 — Migration اور File 04 Boundary

**مرکزِ نظرِ ثانی:** legacy File 04 diagnostics، cutover evidence، native writes اور destructive migration paths جانچے گئے۔

**دریافت:** کوئی direct File 04 write یا silent migration نہیں ملا۔

**اصلاح:** migration-only/no-write boundary اور accepted cutover evidence gate برقرار رکھا گیا۔

**دوبارہ آزمائش:** legacy migration diagnostics اور architecture guard پاس ہوئے۔

### دور 35 — Activation اور Acceptance Evidence

**مرکزِ نظرِ ثانی:** Founder identity، MFA، environment، commit/package hashes، role/provider/cache/accessibility/backup/rollback evidence جانچا گیا۔

**دریافت:** provider acceptance کے لیے الگ exact version/evidence registry درکار تھی۔

**اصلاح:** activation wizard کے ساتھ provider acceptance route/service، rollback-on-audit-failure اور production preconditions شامل ہوئے۔

**دوبارہ آزمائش:** activation/provider acceptance source gates پاس ہوئے۔

### دور 36 — Backup، Restore اور Rollback Readiness

**مرکزِ نظرِ ثانی:** schema compatibility، non-destructive uninstall، File 20 boundary، staging runbook اور sign-off requirements جانچے گئے۔

**دریافت:** کوئی source-level destructive rollback path نہیں ملا؛ حقیقی rehearsal ابھی external ہے۔

**اصلاح:** InnoDB/schema versions، runbook references اور pending sign-off truth برقرار رکھی گئی۔

**دوبارہ آزمائش:** final-deliverables and documentation gates پاس ہوئے۔

### دور 37 — Versioning، Packaging اور Source Parity

**مرکزِ نظرِ ثانی:** نئی security/schema/privacy changes کے باوجود package identity 1.1.0 رہنے کا خطرہ جانچا گیا۔

**دریافت:** ایک ہی 1.1.0 version کے مختلف source artifacts بننے سے immutable release identity ٹوٹتی۔

**اصلاح:** plugin/package candidate Version 1.2.0 کیا گیا؛ schema versions الگ رکھے؛ deterministic installable + complete-source artifacts اور manifests update کیے گئے۔

**دوبارہ آزمائش:** version alignment، build-script اور package tests چلائے گئے/CI gate میں لازم کیے گئے۔

### دور 38 — Exact Companion Contracts اور CI

**مرکزِ نظرِ ثانی:** File 00/21/22 pins، real contracts، complete test loop اور artifact paths جانچے گئے۔

**دریافت:** 40-round gate اور audit document permanent workflow میں explicit نہیں تھے۔

**اصلاح:** final release workflow میں forty-round gate/document checks اور Version 1.2.0 artifact evidence شامل کیا گیا۔

**دوبارہ آزمائش:** local workflow marker tests پاس ہوئے؛ exact-head GitHub CI الگ immutable gate ہے۔

### دور 39 — Full Adversarial Regression

**مرکزِ نظرِ ثانی:** تمام PHP/JS syntax، every `tests/*-tests.php`، architecture guard، source markers اور deterministic build دوبارہ چلائے گئے۔

**دریافت:** اس fresh local regression میں سامنے آنے والے test/document mismatches اسی دور میں درست کیے گئے۔

**اصلاح:** تمام failing assertions کی اصل source یا truthful test correction کی گئی؛ tests کو محض bypass نہیں کیا گیا۔

**دوبارہ آزمائش:** حتمی local run میں zero failures لازم رکھا گیا۔

### دور 40 — Final Fresh Review اور Stop Condition

**مرکزِ نظرِ ثانی:** پچھلے 39 ادوار کی تمام اصلاحات، regressions، versioning، documentation، package اور release truth ازسرِنو دیکھی گئی۔

**دریافت:** مقررہ source scope میں کوئی معلوم unresolved blocker/critical/high defect باقی نہیں ملا؛ staging/live evidence موجود نہیں۔

**اصلاح:** کوئی مزید code change لازم نہ ہونے پر source review بند کیا گیا؛ PR Draft/unmerged اور production writes fail-closed رکھے گئے۔

**دوبارہ آزمائش:** 40/40 executable gate، full local suite، deterministic artifacts اور بعد از commit exact-head CI کو final evidence chain مقرر کیا گیا۔

## اختتامی فیصلہ

چالیس مستقل thematic ادوار مکمل ہوئے۔ مقررہ File 23-owned source scope میں معلوم unresolved blocker/critical/high defect صفر ہے۔ حتمی source status صرف **Coded، Reviewed، Packaged اور Automated-QA Green candidate** ہوسکتا ہے؛ Hostinger staging، real providers/roles، browser/accessibility، backup/restore/rollback، Founder sign-off، merge اور live deployment الگ gates ہیں۔
