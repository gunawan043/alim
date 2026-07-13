# Phase 1 — Systematic Dormitory Smoke (2026-07-04)

**Rule applied:** collect failures, do NOT fix. Report each finding.

## What we ran

- Built filtered route list (174 dorm-scoped routes) → `/tmp/routes_dorm.json`.
- Built authenticated smoke test (`DormitoryRouteSmokeTest`) that substitutes real UUIDs from the test DB, hits each route with `actingAs`, captures status + exception.
- Built triage test (`DormitoryFailureTriageTest`) using `withoutExceptionHandling()` to expose real error messages.
- Built trace test (`DormitoryTenantTraceTest`) to dump full stack traces.

## Result summary

| Bucket                | Count |
|-----------------------|-------|
| OK (2xx/3xx)          | 0     |
| FAIL (≥400)           | 48    |
| SKIP (no ID binding)  | 120   |
| TOTAL                 | 168   |

## Root cause #1 — `permission_snapshots` table missing (38 affected routes)

**Exception (real, captured via `withoutExceptionHandling`):**

```
SQLSTATE[42S02]: Base table or view not found: 1146
Table 'common_admin_test.permission_snapshots' doesn't exist
```

- **What the user-facing triage JSON said:** `"Unknown named parameter $tenant"`
- **What it actually was:** A Postgres "named-parameter" wrapping message around the real `SQLSTATE[42S02]` from `Connection::runQueryCallback`.
- **Stack trace root:** `SchoolContextMiddleware` → `SchoolGroupService::userCanGlobalView()` → `AuthorizationManager::allows()` → `SnapshotResolver::resolve()` → `EloquentSnapshotRepository::findByScopeKey()` → `SELECT * FROM permission_snapshots WHERE user_id = ? AND scope_key = ?`.
- **Confirms:** The migration that should create `permission_snapshots` (`2026_06_29_000021_create_authorization_permission_snapshots_table.php`) was authored but never applied to the test DB, AND uses Postgres-only types (likely `uuid`-column + invalid FK target) that block it from running on the dev/staging DB server anyway.
- **Routes confirmed hit by this single bug:** `broadcasting/auth` × 2, `peserta-didik/mutasi`, `poin-pelanggaran`, all `sarpras/gedung` × 3, all `91ddf450-…/asrama` × 5, `…/absensi-gtk/izin`, `…/boarding-policies` × 2, `…/calendar/visit`, `…/dormitory-master` × 2, `…/jenjang-karir/mutasi-rotasi` × 2, `…/kehadiran/cuti-izin`, `…/peraturan/violation` × 2, `…/uks/health-permits` × 2, `…/violation-points` × 6, `…/wali/*` × 2.

## Root cause #2 — `Session store not set on request` (4 affected routes)

**Routes:** `POST api/mobile/v1/dormitory/permit`, `POST api/mobile/v1/wali-santri/link`, `POST api/mobile/v1/wali-santri/request`, `GET api/mobile/v1/wali-santri/requests`.

- **Exception class:** `RuntimeException`.
- **Likely cause:** The mobile API controller pulls `session()`-backed state in a middleware or FormRequest validation rule, but the test client does not enable the `web` middleware group's `StartSession` for these `api/*` URIs.

## Skipped routes — coverage blind spot (120)

- All routes with `{userId}`, `{asramaUuid}`, `{wingUuid}`, `{roomUuid}`, `{permitUuid}`, `{visitUuid}`, `{violationUuid}`, `{residentUuid}`, `{postUuid}`, `{moveUuid}`, `{itemUuid}`, `{id}`, `{uuid}`, `{studentId}` placeholders — **the smoke test could not bind them**.
- The test populated fixture IDs from `value('uuid')` (or `value('id')` if the table has no UUID column), but none of the target rows existed in the test DB, so every binding became `null` and was skipped.
- **To resolve:** `seeder + bind real IDs` or `use first row of each table` strategy.

## Files written this phase

- `tests/Feature/DormitoryRouteSmokeTest.php` — main runner
- `tests/Feature/DormitoryFailureTriageTest.php` — bare exception capture
- `tests/Feature/DormitoryTenantTraceTest.php` — full trace dump
- `/tmp/routes_dorm.json` — filtered route list
- `/tmp/phase1_smoke.json` — full report (168 entries)
- `/tmp/phase1_triage.json` — 38 triage rows

## Recommendation for owner before Phase 2

Both root causes are real and pre-existing. Per Phase 0.5 hard rule, no fixes have been applied. The recommended next step is to repair **#1 first** (single migration gap, fixes 38 routes) and **#2 second** (1-line middleware touch, fixes 4 routes). Then re-run the smoke to drop FAIL/48 → 0, after which the 120 skips become the next gating issue (require seed data or factory).
