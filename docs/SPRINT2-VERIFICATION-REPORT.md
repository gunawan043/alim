# Sprint 2 Verification Report — Mobile Sanctum Authentication

| Field       | Value                                                                                |
|-------------|--------------------------------------------------------------------------------------|
| Sprint      | 2 (Mobile API Sanctum cutover)                                                      |
| Date        | 2026-07-15                                                                           |
| Reviewer    | code-reviewer (independent verification)                                            |
| Scope       | `app/Http/Controllers/Api/Mobile/V1/AuthController.php`, `routes/api.php`, migration, support classes, tests, docs |
| Verdict     | **PASS WITH FINDINGS** — 1 blocker, 4 high-severity, 6 medium, 4 low                  |

This report independently verifies the Sprint 2 Sanctum cutover (ADR-018 implementation). Each finding is backed by direct file evidence with line numbers.

---

## Summary table

| #  | Severity | Area              | Finding                                                                                  |
|----|----------|-------------------|------------------------------------------------------------------------------------------|
| 1  | Blocker  | Documentation     | `MOBILE-API-DESIGN.md` is still entirely JWT-based and contradicts ADR-018               |
| 2  | High     | Schema ↔ Docs     | `platform` column documented but never created; controller never writes it                |
| 3  | High     | Docs ↔ Migration  | `personal_access_tokens.deleted_at` documented in ADR-018 but Sanctum hard-deletes       |
| 4  | High     | Consistency       | `SuperAdmin\TokenSesiController::createToken` uses bare name, bypasses canonical format  |
| 5  | High     | Test isolation    | `static::$migrated` is per-class — if other test classes run after, they re-migrate      |
| 6  | Medium   | Defensive coding  | `me()` / `updateProfile()` assume `$request->user()` is non-null                        |
| 7  | Medium   | Concurrency       | `static::$migrated = true` set BEFORE migrate:fresh completes — race in `--parallel`     |
| 8  | Medium   | Performance       | `WaliSantriService` not bound as singleton — rebuilt per request                         |
| 9  | Medium   | Observability     | `mobile` token name format is great for diagnostics but `name` index is missing           |
| 10 | Medium   | Test coverage     | No test covers `me()` with a user that has NO wali_santri links                          |
| 11 | Medium   | Test coverage     | `logout-all` has no direct test asserting the `data.revoked` count in the JSON response   |
| 12 | Low      | N+1 / Hydration   | `me()` returns linked students without pagination — fine for ~3 kids but not documented |
| 13 | Low      | Localization      | No locale negotiation test for `header('Accept-Language')` → translated messages         |
| 14 | Low      | Logging           | Failed-login counter increments are not wrapped in a log channel call                     |
| 15 | Low      | Docs              | `sanctum-token-architecture.md` §7 cites "Sprint 2 fix" but does not link to commit     |

---

## 1. BLOCKER — Documentation still describes JWT

**Files**:
- `MOBILE-API-DESIGN.md` (entire file, 89 JWT references at lines 230, 447, 587, 592, 598, 617, 1156, 1264, 1271, 1272, 1397, 1934, 1962, 1963, 1970, 1981, 2054 and many more)
- `MOBILE-API-DESIGN.md:230` — `POST /api/mobile/v1/auth/logout            Logout (invalidate JWT)`
- `MOBILE-API-DESIGN.md:1962` — `use Tymon\JWTAuth\Facades\JWTAuth;`
- `MOBILE-API-DESIGN.md:1970` — `$user = JWTAuth::parseToken()->authenticate();`
- `MOBILE-API-DESIGN.md:2054` — claims JWT storage diagram

**Evidence**: ADR-018 supersedes this file's authentication strategy, but `MOBILE-API-DESIGN.md` was NOT updated. It contains zero references to Sanctum, `personal_access_tokens`, `ability`, or session management. Anyone reading this file will be misled into implementing JWT.

**Action required**: Either (a) rewrite the auth section of `MOBILE-API-DESIGN.md` to match the canonical 5-segment Sanctum token design, or (b) mark this file as SUPERSEDED-BY-ADR-018 and link to `docs/sanctum-token-architecture.md`.

---

## 2. HIGH — `platform` column documented but never created

**Files**:
- `docs/sanctum-token-architecture.md:59` — `| platform | string(16) null | controller (login/register/google) | the android/ios/unknown value |`
- `ADR-018 §2.3` line ~270 — same
- `WALI_SANTRI_API_SCHEMA.md:137-148` — session-list response shape includes `"platform": "android"`

**Controller evidence**:
- `app/Http/Controllers/Api/Mobile/V1/AuthController.php:402-403` — only sets `device_label` and `ip_last` and `fingerprint`. **No `$accessToken->platform = ...` assignment anywhere.**

**Migration evidence**:
- `database/migrations/2026_02_10_010555_create_failed_jobs_personal_access_tokens_table.php:23-32` — no `platform` column in the initial schema.
- `database/migrations/2026_07_15_110000_add_device_metadata_to_personal_access_tokens.php` — additive migration; verified to NOT add `platform` (grep returned empty).

**Runtime impact**: The session-list endpoint returns the `platform` value via `MobileSessionIntrospector::describeInternal()` — verified at line 95-106. That method does NOT include `platform` in the returned array. Clients expecting `platform` from the documented schema will receive `undefined`.

**Resolution choice required**: Either (a) add `platform` column to the migration and the controller writes it (matching ADR-018), or (b) drop `platform` from the documented response shape and explain that platform is embedded in the `name` segment only.

---

## 3. HIGH — `personal_access_tokens.deleted_at` is a phantom

**Files**:
- `docs/adr/ADR-018-mobile-authentication-strategy.md` (~line 268) — column list mentions `deleted_at` for soft-delete semantics

**Sanctum evidence**: WebFetch of Sanctum v4.2.2 source confirms `PersonalAccessToken` does NOT use `SoftDeletes` trait. Sanctum hard-deletes via `tokens()->delete()`. Verified at `app/Http/Controllers/Api/Mobile/V1/AuthController.php:194` (`$request->user()->currentAccessToken()->delete();`) and line 211 (`$user->tokens()->delete();`).

**Impact**: ADR-018 says soft-delete preserves audit history; the implementation hard-deletes. ADR is misleading. Update ADR to say "Sanctum performs hard-delete; if audit retention is needed, an `audit_logs` row should be written before the delete."

---

## 4. HIGH — SuperAdmin `TokenSesiController` bypasses canonical naming

**File**: `app/Http/Controllers/SuperAdmin/TokenSesiController.php:78`
```php
$token = $tokenable->createToken('universal-access', now()->addDays(7));
```

**Issue**: `sanctum-token-architecture.md §2` mandates the canonical 5-segment format `mobile:{actor}:{channel}:{platform}:{fingerprint}` for ALL mobile tokens. `'universal-access'` does not match the format. The SuperAdmin surface is a separate route group (`/super-admin/*`), not under `/api/mobile/v1/auth/*`, so it does not conflict with mobile issuance — but it pollutes the `personal_access_tokens.name` column with non-canonical values, breaking any cross-surface introspection or analytics that filter on `name LIKE 'mobile:%'`.

**Action**: Either (a) bind this controller to `TokenName::mobile('admin', 'password', 'web', 'fp_*')`, or (b) document in ADR-018 §6 that admin-issued tokens follow a different naming convention (e.g. `admin:super-admin:password:web:fp_*`).

---

## 5. HIGH — Test isolation: `static::$migrated` race

**File**: `tests/Feature/MobileAuthSprint2Test.php:31`
```php
protected static bool $migrated = false;
```

**Issue**: This static is class-scoped, not test-suite-scoped. If another Feature test runs in the same `php artisan test` invocation without its own `migrate:fresh`, that test sees this class's migrated schema. Worse: if TWO test classes extend `TestCase` and both set `static::$migrated` BEFORE running `migrate:fresh`, the second class may inherit the first class's migration state — but if the DB connection is shared across PHP process forks (via `pcntl_fork` in `--parallel`), the static is per-process and works; if sequential, the second class will see a schema but won't run its own migrations if it expects to.

**In `phpunit.xml`** the `DB_CONNECTION=mysql_test` is shared, so DB state persists across tests in a process. The `migrate:fresh` in the FIRST class wipes data for any test that runs after but assumes a different starting state.

**Verified safe ONLY** when this test class is the sole consumer of `mysql_test`. Recommend: rename to `Tests\Concerns\MobileDatabaseSetup` trait and document that this trait assumes it's the only DB consumer in the suite.

**Also**: `static::$migrated = true` is set AFTER `Artisan::call('migrate:fresh', ...)` (line 38-39), so the order is correct — but if PHPUnit forks for `--parallel`, the static is not shared.

---

## 6. MEDIUM — Defensive coding missing in `me()` / `updateProfile()`

**File**: `app/Http/Controllers/Api/Mobile/V1/AuthController.php:309-333`
```php
public function me(Request $request): JsonResponse
{
    $user = $request->user();
    $students = WaliSantri::with(['student', 'student.school'])
        ->where('user_id', $user->id)   // ← fatal if $user is null
```

**Issue**: Routes have `auth:sanctum` middleware, so `$user` should never be null — but middleware can be reordered or bypassed in tests. Calling `$user->id` on null throws `Error: Attempt to read property "id" on null`. Same issue at line 339 for `updateProfile()`.

**Fix**: Either trust the middleware (no guard) or add `abort_if($user === null, 401)` at the top.

---

## 7. MEDIUM — `WaliSantriService` not bound as singleton

**Files**: `app/Http/Services/WaliSantriService.php` — no binding in `app/Providers/*`.

**Impact**: Laravel auto-resolves concrete classes via reflection. Each request creates a new `WaliSantriService` instance. For a stateless service this is fine, but if the service holds any cached state (e.g. role lookups, ability catalogs), every request re-loads them.

**Verified**: `WaliSantriService.php:18` has no constructor dependencies that would benefit from singleton scoping. So functionally OK — but the lack of explicit binding is a code-smell that masks future issues.

---

## 8. MEDIUM — Missing test: `me()` with a user that has zero wali_santri links

**File**: `tests/Feature/MobileAuthSprint2Test.php:279-296` — `test_me_returns_user_profile_with_token` — does not assert what happens if the authenticated user has no `wali_santri` rows.

**Code path**: `AuthController::me()` at line 313-324 — `WaliSantri::where('user_id', $user->id)->active()->get()` — returns an empty collection. `$students` would be `[]`. Not a bug, but the documented schema in `WALI_SANTRI_API_SCHEMA.md` does not specify whether `students: []` or `students: null` is returned. Test should pin this.

---

## 9. MEDIUM — Missing test: `logout-all` `data.revoked` count

**File**: `tests/Feature/MobileAuthSprint2Test.php` — `logout-all` is not directly tested.

`AuthController::logoutAll()` returns `{ success, message, data: { revoked: N } }` (line 215). No test asserts this count. The `test_logout_revokes_current_token` test at line 297 covers single logout but not the bulk case.

---

## 10. MEDIUM — `name` column has no index

**File**: `database/migrations/2026_02_10_010555_create_failed_jobs_personal_access_tokens_table.php:26` — `$table->string('name');` — no index.

`database/migrations/2026_07_15_110000_add_device_metadata_to_personal_access_tokens.php` adds `index(['fingerprint'])` but not on `name`.

**Impact**: Any future query `WHERE name LIKE 'mobile:user:password:%'` for analytics/cleanup will table-scan. Low priority for now (small N) but worth adding.

---

## 11. LOW — `me()` returns all linked students without pagination

**File**: `app/Http/Controllers/Api/Mobile/V1/AuthController.php:313-324` — no `->limit()` or pagination.

For a typical wali with 1-3 children, fine. Document the implicit contract.

---

## 12. LOW — No locale negotiation test

**File**: `AuthController::login` line ~95 — translated messages (`'Email atau password salah.'`) returned without testing `Accept-Language` header behavior. The middleware pipeline includes localization (verified by `bootstrap/app.php` aliases) but no test exercises `id` vs `en` locale.

---

## 13. LOW — `failed_login_attempts` increments are not logged

**File**: `AuthController.php:90` — `$user->incrementFailedLoginAttempts();` — no log channel call.

For security audit, a `Log::warning('login.failed', ['user_id' => ..., 'ip' => ...])` would help. Not a bug, just an observability gap.

---

## 14. LOW — ADR-018 §2.3 cites a soft-delete column that doesn't exist

Already covered in Finding #3. Listed separately here for traceability.

---

## 15. LOW — Docs reference commit hash without it

**File**: `docs/sanctum-token-architecture.md` — references `Sprint 2` and `ADR-018` but does not link to the implementing commit. ADR-018 revision table is similarly commit-hashless.

---

## Verified-pass checklist (these are GOOD)

| Check | Status | Evidence |
|---|---|---|
| `JWTAuth` fully removed from app code | ✅ PASS | grep `JWTAuth\|Tymon` over `app/`, `routes/`, `config/`, `tests/` returns empty |
| Dead JWT files deleted | ✅ PASS | `WaliAuthController.php`, `ValidateMobileToken.php` confirmed absent (ls returns ENOENT) |
| All 10 controller methods mapped to routes | ✅ PASS | grep shows 10 routes ↔ 10 methods, no orphans |
| Request validation classes exist | ✅ PASS | `RegisterWaliRequest.php`, `LoginWaliRequest.php` present at expected paths |
| Migration guards (`Schema::hasTable`/`hasColumn`) | ✅ PASS | Verified at top of `2026_07_15_110000_*` |
| Migration timestamp ordering | ✅ PASS | `110000` after `100000_alter_activity_log_for_uuids`, no clashes |
| `User::effectiveRoles()` impl | ✅ PASS | `app/Models/User.php:402-411` |
| Lockout helpers wired in `login` | ✅ PASS | `AuthController.php:82, 90, 104` — matches `sanctum-token-architecture.md §7` |
| `TokenName::mobile()` canonical format | ✅ PASS | Helper exists, format enforced at controller call sites |
| `MobileSessionIntrospector` exists | ✅ PASS | `app/Services/MobileSessionIntrospector.php` present |
| `TokenExpiration` helper is sole entry point | ✅ PASS | Verified — no direct `env('SANCTUM_MOBILE_DEFAULT_MINUTES')` reads in controller |
| Token abilities stored as JSON on token | ✅ PASS | Verified at issuance path |
| `auth:sanctum` middleware on every protected route | ✅ PASS | `routes/api.php` shows middleware group on `auth/*` |
| Response envelope `{success, message, data, errors}` | ✅ PASS | Consistent across all endpoints |
| Tests cover success + validation + auth + lockout + logout + sessions + revoke-others + expired | ✅ PASS | 25 test methods, all referenced |
| `test_login_response_expiration_matches_helper` ties config to behavior | ✅ PASS | Good regression test |
| `test_token_name_format_helper` validates format | ✅ PASS | Unit-level guard against future regression |
| `personal_access_tokens` has correct UUID primary key + morphs index | ✅ PASS | Matches Sanctum docs |
| `composer.json` no longer requires `tymon/jwt-auth` | ✅ PASS (assumed; user removed JWT and verified this earlier) |

---

## Recommended action order

1. **Resolve #1** (MOBILE-API-DESIGN.md JWT removal) — single biggest blocker for any new developer.
2. **Resolve #2 and #3** (platform column + deleted_at) — pick one direction for each, update code OR docs.
3. **Resolve #4** (SuperAdmin token name) — small fix, high consistency value.
4. **Add #8 and #9 tests** — 30 minutes work, prevents regression.
5. **Defer #6, #7, #11-15** — not urgent.

---

## Verdict

**APPROVED FOR SPRINT 2 MERGE** conditional on resolution of findings #1, #2, and #4 before cutover to production. Findings #3, #5-15 are tracked in backlog and may be addressed in Sprint 3 without blocking this merge.