---
name: authorization-implementation-transition-plan
description: Implementation transition plan — roadmap review, dependency matrix, phase specifications, ADR planning, feature flags, rollback/regression matrices, success metrics, readiness checklist for ALIM authorization rollout
metadata:
  type: project
---

# Authorization Implementation Transition Plan

> Status: **Final — Ready for Implementation Phase**
> Purpose: Menerjemahkan arsitektur menjadi rencana eksekusi yang aman, bertahap, dapat diuji.
> Supersedes: Tidak ada — dokumen ini *menjembatani* dokumen desain dengan implementasi.
> Binds: Semua developer + AI assistant yang akan mengeksekusi roadmap authorization.

---

## Preamble

Dokumen ini **bukan** dokumen arsitektur baru.

Dokumen ini **adalah** kontrak operasional yang menjawab:

> "Bagaimana cara mengeksekusi roadmap tanpa merusak sistem yang sudah berjalan?"

Semua keputusan di sini tunduk pada:
- `authorization-domain-model.md`
- `authorization-governance-operational-architecture.md`
- `authorization-architecture-validation.md`
- `authorization-engineering-constitution.md`
- ADR yang telah dan akan disetujui

Jika dokumen ini bertentangan dengan dokumen di atas, dokumen di atas yang menang dan dokumen ini diperbaiki via ADR.

---

## Daftar Isi

1. Roadmap Review
2. Dependency Matrix
3. Implementation Specification per Phase
4. ADR Planning
5. Feature Flag Strategy
6. Rollback Matrix
7. Regression Matrix
8. Success Metrics
9. Phase Readiness Checklist
10. Execution Order & Critical Path

---

## 1. Roadmap Review

### 1.1. Roadmap Asal (dari validation doc)

```
Phase 0 (3d)  → 6 ADR
Phase 1 (1w)  → Schema + CI guard, no behavior
Phase 2 (1w)  → Parity test harness
Phase 3 (2w)  → Builder shadow mode
Phase 4 (1.5w) → Middleware + cache + conflict detector
Phase 5 (1w)  → Cutover gradual
Phase 6 (1w)  → Cleanup + docs
```

### 1.2. Review Hasil

#### Temuan 1 — Phase 0 terlalu ambisius

Menulis 6 ADR dalam 3 hari tanpa track record akan menghasilkan ADR prematur. Sebaiknya Phase 0 fokus pada **3 ADR yang paling kritikal** (schema, guard, snapshot retention) dan sisanya menyusul sesuai kebutuhan phase.

#### Temuan 2 — Phase 2 (parity) lebih cocok mendahului Phase 1 (schema)

Parity test butuh snapshot existing yang akan dibandingkan. Jika schema belum ada, parity test tidak bisa dibangun. **Urutan dibalik**: Phase 1 (schema) → Phase 2 (parity) **tetap benar**.

Tapi Phase 1 bisa dipecah: **Phase 1a (schema only)**, **Phase 1b (CI guard)**, **Phase 1c (snapshot model + factory)**.

#### Temuan 3 — Phase 3 (Builder shadow) terlalu besar

Builder + provider + rule engine + cache + listener + event semuanya dalam 2 minggu = risiko kualitas rendah. Sebaiknya dipecah:

- **Phase 3a**: Identity + Employment provider (sumber paling stabil).
- **Phase 3b**: Assignment + Homeroom provider.
- **Phase 3c**: Delegation + Acting + Revocation provider.
- **Phase 3d**: Rule engine + facts + evaluator.

#### Temuan 4 — Phase 4 conflict detector terlalu dini

Conflict detector lebih baik setelah cutover (Phase 5), karena sebelum cutover sistem belum punya sumber data lengkap untuk dibandingkan. Dipindah ke **Phase 5b**.

#### Temuan 5 — Phase 6 (cleanup) terlalu singkat

Cleanup butuh validasi produksi + monitoring + post-mortem review. Sebaiknya **2 minggu** (Phase 6a: monitoring, Phase 6b: cleanup).

### 1.3. Roadmap Revisi (Final — 11 Phases)

| Phase | Durasi | Output | Risiko | Bisa rollback? |
|-------|--------|--------|--------|----------------|
| **0a** | 1d | ADR-016, ADR-017 (schema + write-guard) | Rendah | N/A (dokumen) |
| **0b** | 1d | ADR-021, ADR-022 (snapshot retention + cache strategy) | Rendah | N/A |
| **0c** | 1d | ADR-023, ADR-024 (parity harness + audit retention) | Rendah | N/A |
| **1a** | 2d | Migration: `permission_snapshots`, `authorization_rules`, `revoked_permissions` | Rendah | Ya (drop table) |
| **1b** | 1d | Model + factory + index untuk snapshot | Rendah | Ya |
| **1c** | 2d | CI guard: static analysis + naming lint + forbidden-pattern detector | Rendah | Ya |
| **2a** | 2d | Parity harness CLI + Spatie output recorder | Medium | Ya |
| **2b** | 2d | Parity fixture (100 skenario existing user) | Medium | Ya |
| **2c** | 1d | Parity CI step (drift 0% threshold) | Rendah | Ya |
| **3a** | 2w | Identity + Employment provider + tests + shadow mode | Medium-High | Ya (shadow = no production impact) |
| **3b** | 1w | Assignment + Homeroom provider + tests | Medium | Ya (shadow) |
| **3c** | 1w | Delegation + Acting + Revocation provider + tests | Medium | Ya (shadow) |
| **3d** | 1w | Rule engine + 5 built-in facts + evaluator | Medium-High | Ya (shadow) |
| **4a** | 1w | OrganizationContext + Middleware | Medium | Ya (flag off) |
| **4b** | 1w | Snapshot resolver + cache + listener async | Medium | Ya (flag off) |
| **4c** | 0.5w | SidebarComposer refactor (read dari snapshot) | Medium | Ya (flag off) |
| **5a** | 1w | Cutover sekolah pilot (1 sekolah → 100%) | High | Ya (feature flag) |
| **5b** | 1w | Conflict detector v1 + dashboard | Medium | Ya (flag off) |
| **5c** | 0.5w | Cutover seluruh sekolah | High | Ya (per-school flag) |
| **6a** | 1w | Observability + alert + runbook + post-mortem template | Rendah | N/A |
| **6b** | 1w | Cleanup legacy code + final documentation | Medium | N/A |

**Total: 13 minggu** (vs 8 minggu roadmap awal).

**Trade-off:** Roadmap lebih panjang tapi **jauh lebih aman** karena:
- Setiap sub-phase = 1 PR atau 1 sprint kecil.
- Rollback halus per sub-phase.
- Quality gate di setiap sub-phase.
- Tidak ada "big bang" delivery.

### 1.4. Dependency Graph Antar-Phase

```
0a → 0b → 0c
            ↓
1a → 1b → 1c → 2a → 2b → 2c
                         ↓
                  3a → 3b → 3c → 3d
                              ↓
                        4a → 4b → 4c
                                    ↓
                              5a → 5b → 5c
                                       ↓
                                 6a → 6b
```

Tidak ada parallel phase di awal karena setiap phase butuh fixture dari phase sebelumnya. Setelah Phase 3a, beberapa sub-phase bisa berjalan parallel (3b + sebagian 3d).

---

## 2. Dependency Matrix

### Phase 0a — ADR Schema + Write-Guard

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Constitution + domain model sudah disetujui |
| **Modul yang disentuh** | Tidak ada (hanya dokumen) |
| **Modul yang tidak boleh berubah** | Semua kode existing |
| **Service yang bergantung** | Tidak ada |
| **Event yang bergantung** | Tidak ada |
| **Migration** | Tidak ada |
| **Test yang wajib lulus** | Tidak ada |
| **Output** | 2 ADR |

### Phase 0b — ADR Snapshot + Cache

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Phase 0a |
| **Modul yang disentuh** | Tidak ada |
| **Modul yang tidak boleh berubah** | Semua kode existing |
| **Service yang bergantung** | Tidak ada |
| **Event yang bergantung** | Tidak ada |
| **Migration** | Tidak ada |
| **Test yang wajib lulus** | Tidak ada |
| **Output** | 2 ADR |

### Phase 0c — ADR Parity + Audit Retention

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Phase 0b |
| **Modul yang disentuh** | Tidak ada |
| **Modul yang tidak boleh berubah** | Semua kode existing |
| **Service yang bergantung** | Tidak ada |
| **Event yang bergantung** | Tidak ada |
| **Migration** | Tidak ada |
| **Test yang wajib lulus** | Tidak ada |
| **Output** | 2 ADR |

### Phase 1a — Migration Snapshot Tables

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Phase 0a, 0b (schema ADR disetujui) |
| **Modul yang disentuh** | `database/migrations/` |
| **Modul yang tidak boleh berubah** | `app/Models/User.php`, `app/Http/Controllers/*` |
| **Service yang bergantung** | Tidak ada (tabel kosong) |
| **Event yang bergantung** | Tidak ada |
| **Migration** | 3 migration baru (snapshot, rules, revoked) |
| **Test yang wajib lulus** | Migration test (up + down + rollback) |
| **Output** | Schema ter-install tanpa error |
| **Risk** | Schema clash dengan tabel existing (cek nama dulu) |

### Phase 1b — Model + Factory + Index

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Phase 1a |
| **Modul yang disentuh** | `app/Models/PermissionSnapshot.php`, `app/Models/AuthorizationRule.php`, `app/Models/RevokedPermission.php` (BARU) |
| **Modul yang tidak boleh berubah** | Model existing |
| **Service yang bergantung** | Tidak ada |
| **Event yang bergantung** | Tidak ada |
| **Migration** | Tidak ada |
| **Test yang wajib lulus** | Factory test (create + read + delete) |
| **Output** | Model siap pakai untuk testing |

### Phase 1c — CI Guard

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Phase 0c (parity ADR) |
| **Modul yang disentuh** | `tools/ci/*`, `.github/workflows/*` |
| **Modul yang tidak boleh berubah** | Production code |
| **Service yang bergantung** | Tidak ada |
| **Event yang bergantung** | Tidak ada |
| **Migration** | Tidak ada |
| **Test yang wajib lulus** | Lint test (contoh test violation pattern, harus terdeteksi) |
| **Output** | CI gagal jika ada `assignRole()` di controller |

### Phase 2a — Parity Harness CLI

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Phase 1a, 1b, 1c |
| **Modul yang disentuh** | `app/Authorization/Console/ParityRecordCommand.php`, `app/Authorization/Console/ParityVerifyCommand.php` |
| **Modul yang tidak boleh berubah** | Spatie code (kita baca saja) |
| **Service yang bergantung** | `Spatie\Permission\PermissionRegistrar` |
| **Event yang bergantung** | Tidak ada |
| **Migration** | Tidak ada |
| **Test yang wajib lulus** | Parity record test (1 fixture user → record output → verify deterministic) |
| **Output** | CLI mampu rekam & verifikasi output Spatie |

### Phase 2b — Parity Fixture 100 Skenario

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Phase 2a |
| **Modul yang disentuh** | `tests/Fixtures/ParityFixtures.php`, fixture data (CSV/JSON) |
| **Modul yang tidak boleh berubah** | Production code |
| **Service yang bergantung** | `ParityRecordCommand` |
| **Event yang bergantung** | Tidak ada |
| **Migration** | Tidak ada |
| **Test yang wajib lulus** | 100 skenario menghasilkan output valid |
| **Output** | Baseline parity fixture |
| **Risk** | 100 skenario butuh data realistic dari production (anonymized) |

### Phase 2c — Parity CI

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Phase 2b |
| **Modul yang disentuh** | `.github/workflows/ci.yml` (tambah step) |
| **Modul yang tidak boleh berubah** | Production code |
| **Service yang bergantung** | `ParityVerifyCommand` |
| **Event yang bergantung** | Tidak ada |
| **Migration** | Tidak ada |
| **Test yang wajib lulus** | Parity drift 0% (existing) |
| **Output** | CI gagal jika Builder ≠ Spatie |

### Phase 3a — Identity + Employment Provider

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Phase 2c |
| **Modul yang disentuh** | `app/Authorization/Builders/*` (BARU), `app/Authorization/Contracts/*` (BARU), `app/Authorization/DTO/*` (BARU) |
| **Modul yang tidak boleh berubah** | `Spatie\Permission\*`, `User.php` (existing) |
| **Service yang bergantung** | `UserRepository`, `GtkEmploymentRepository` |
| **Event yang bergantung** | Tidak ada (read-only) |
| **Migration** | Tidak ada |
| **Test yang wajib lulus** | Unit (provider) + parity (existing user) + integration |
| **Output** | Builder menghasilkan output identik dengan Spatie untuk identity + employment |
| **Risk** | Risk terbesar di sini — Builder harus match Spatie exactly untuk user existing |

### Phase 3b — Assignment + Homeroom Provider

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Phase 3a |
| **Modul yang disentuh** | `app/Authorization/Builders/AssignmentProvider.php`, `app/Authorization/Builders/HomeroomProvider.php` |
| **Modul yang tidak boleh berubah** | `TeachingAssignment.php`, `StudyGroup.php` |
| **Service yang bergantung** | `TeachingAssignmentRepository`, `StudyGroupRepository` |
| **Event yang bergantung** | Tidak ada (read-only) |
| **Migration** | Tidak ada |
| **Test yang wajib lulus** | Parity + feature (wali kelas & teaching assignment scenarios) |
| **Output** | Assignment-derived permission di Builder |
| **Risk** | Spatie tidak punya assignment concept, jadi ini NET-NEW (tidak ada parity) |

### Phase 3c — Delegation + Acting + Revocation Provider

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Phase 3b |
| **Modul yang disentuh** | `app/Authorization/Builders/DelegationProvider.php`, `app/Authorization/Builders/ActingPositionProvider.php`, `app/Authorization/Builders/RevocationProvider.php` |
| **Modul yang tidak boleh berubah** | Tabel baru (belum ada listener di production) |
| **Service yang bergantung** | `DelegationService` (skeleton), `ActingPositionService` (skeleton) |
| **Event yang bergantung** | Tidak ada (read-only) |
| **Migration** | Tidak ada |
| **Test yang wajib lulus** | Provider unit test (simulasi 5 skenario) |
| **Output** | Provider siap, tapi belum ada source data |
| **Risk** | Rendah karena belum ada data |

### Phase 3d — Rule Engine

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Phase 3c |
| **Modul yang disentuh** | `app/Authorization/Rules/*`, `app/Authorization/Registry/RuleRegistry.php`, `app/Authorization/Registry/FactRegistry.php`, `config/authorization/rules.php` |
| **Modul yang tidak boleh berubah** | Builder, Provider |
| **Service yang bergantung** | Tidak ada |
| **Event yang bergantung** | Tidak ada |
| **Migration** | Tidak ada (rules in config dulu) |
| **Test yang wajib lulus** | Rule unit test + integration (5 sample rules) |
| **Output** | Rule engine evaluasi benar untuk 5 rules dasar |
| **Risk** | Rule engine bisa salah evaluasi → defense: extensive unit test |

### Phase 4a — OrganizationContext + Middleware

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Phase 3d |
| **Modul yang disentuh** | `app/Authorization/Contexts/*`, `app/Authorization/Middleware/OrganizationContextMiddleware.php` |
| **Modul yang tidak boleh berubah** | Routes existing (middleware ditambahkan tanpa breaking) |
| **Service yang bergantung** | Tidak ada |
| **Event yang bergantung** | Tidak ada |
| **Migration** | Tidak ada |
| **Test yang wajib lulus** | Middleware integration test |
| **Output** | Context tersedia di Request |
| **Risk** | Medium — context bisa salah populate → authorization salah |

### Phase 4b — Snapshot Resolver + Cache + Listener

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Phase 4a |
| **Modul yang disentuh** | `app/Authorization/Services/PermissionSnapshotResolver.php`, `app/Authorization/Snapshots/SnapshotStore.php`, `app/Authorization/Listeners/RebuildPermissionsListener.php` |
| **Modul yang tidak boleh berubah** | Builder, Provider |
| **Service yang bergantung** | `Cache`, `Queue`, `Redis` (existing) |
| **Event yang bergantung** | Event source (GtkEmploymentActivated, dll.) — BARU, hanya di-dispatch dari test fixture |
| **Migration** | Tidak ada |
| **Test yang wajib lulus** | Cache hit/miss + listener idempotent + rebuild deterministic |
| **Output** | Snapshot read/write/cache bekerja |
| **Risk** | Tinggi — cache invalidation bug = stale permission |

### Phase 4c — SidebarComposer Refactor

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Phase 4b |
| **Modul yang disentuh** | `app/Http/View/Composers/SidebarComposer.php` (atau equivalent) |
| **Modul yang tidak boleh berubah** | Blade templates existing |
| **Service yang bergantung** | `PermissionSnapshotResolver` |
| **Event yang bergantung** | Tidak ada |
| **Migration** | Tidak ada |
| **Test yang wajib lulus** | Feature (sidebar visibility per role) |
| **Output** | Sidebar filter via snapshot |
| **Risk** | Medium — bisa hide menu yang harusnya visible |

### Phase 5a — Cutover Sekolah Pilot

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Phase 4c |
| **Modul yang disentuh** | Config `authorization.driver = builder` (flag), `app/Http/Middleware/EffectivePermissionMiddleware.php` |
| **Modul yang tidak boleh berubah** | Spatie code (tetap jalan sebagai fallback) |
| **Service yang bergantung** | `Builder`, `SnapshotResolver` |
| **Event yang bergantung** | Semua event (existing + baru) |
| **Migration** | Tidak ada |
| **Test yang wajib lulus** | Full integration test di sekolah pilot |
| **Output** | 1 sekolah berjalan via Builder + Spatie sebagai fallback |
| **Risk** | Tinggi — production traffic |

### Phase 5b — Conflict Detector + Dashboard

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Phase 5a |
| **Modul yang disentuh** | `app/Authorization/Services/AuthorizationConflictDetector.php`, `app/Http/Controllers/SuperAdmin/ConflictController.php` |
| **Modul yang tidak boleh berubah** | Authorization core |
| **Service yang bergantung** | Tidak ada |
| **Event yang bergantung** | Tidak ada (detector runs on demand) |
| **Migration** | `auth_conflict_log` table |
| **Test yang wajib lulus** | Detector unit test + dashboard feature test |
| **Output** | Conflict dashboard live |
| **Risk** | Rendah — detector read-only |

### Phase 5c — Cutover Seluruh Sekolah

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Phase 5a (1 sekolah pilot sukses 7 hari) |
| **Modul yang disentuh** | Config flag untuk semua sekolah |
| **Modul yang tidak boleh berubah** | Spatie code |
| **Service yang bergantung** | Semua |
| **Event yang bergantung** | Semua |
| **Migration** | Backfill snapshot semua user (`auth:backfill`) |
| **Test yang wajib lulus** | Full integration + load test + post-cutover smoke test |
| **Output** | 100% sekolah via Builder |
| **Risk** | Sangat tinggi — semua production traffic |

### Phase 6a — Observability

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Phase 5c |
| **Modul yang disentuh** | `tools/observability/*`, monitoring config |
| **Modul yang tidak boleh berubah** | Authorization core |
| **Service yang bergantung** | Monitoring stack (existing) |
| **Event yang bergantung** | Tidak ada |
| **Migration** | Tidak ada |
| **Test yang wajib lulus** | Smoke test alert firing |
| **Output** | Dashboard + alert production-ready |
| **Risk** | Rendah |

### Phase 6b — Cleanup + Documentation

| Aspek | Detail |
|-------|--------|
| **Prerequisite** | Phase 6a |
| **Modul yang disentuh** | Legacy code removal (Spatie → identity only), final docs |
| **Modul yang tidak boleh berubah** | Identity-role via Spatie (tetap) |
| **Service yang bergantung** | Tidak ada |
| **Event yang bergantung** | Tidak ada |
| **Migration** | Tidak ada |
| **Test yang wajib lulus** | Full regression suite |
| **Output** | Legacy code removed, doc final |
| **Risk** | Medium — menghapus code yang sudah ada |

---

## 3. Implementation Specification per Phase

### Phase 0a — ADR Schema + Write-Guard

| Field | Detail |
|-------|--------|
| **Objective** | Mendokumentasikan keputusan schema database authorization dan kebijakan write-time guard. |
| **Scope** | 2 ADR dokumen saja. |
| **Out of Scope** | Kode implementasi. |
| **Files baru** | `docs/adr/ADR-016-permission-snapshot-schema.md`, `docs/adr/ADR-017-write-time-authorization-guard.md` |
| **Files diubah** | Tidak ada. |
| **Migration** | Tidak ada. |
| **Event** | Tidak ada. |
| **Listener** | Tidak ada. |
| **Service** | Tidak ada. |
| **Observer** | Tidak ada. |
| **Test** | Tidak ada. |
| **Acceptance criteria** | 2 ADR format benar, status Accepted, linkable dari constitution. |

### Phase 0b — ADR Snapshot + Cache

| Field | Detail |
|-------|--------|
| **Objective** | Mendokumentasikan kebijakan retention snapshot dan cache strategy. |
| **Scope** | 2 ADR. |
| **Out of Scope** | Implementasi. |
| **Files baru** | `docs/adr/ADR-021-snapshot-retention-policy.md`, `docs/adr/ADR-022-authorization-cache-strategy.md` |
| **Files diubah** | Tidak ada. |
| **Test** | Tidak ada. |
| **Acceptance criteria** | 2 ADR Accepted. |

### Phase 0c — ADR Parity + Audit Retention

| Field | Detail |
|-------|--------|
| **Objective** | Mendokumentasikan strategi parity test dan audit log retention. |
| **Scope** | 2 ADR. |
| **Out of Scope** | Implementasi. |
| **Files baru** | `docs/adr/ADR-023-spatie-builder-parity-harness.md`, `docs/adr/ADR-024-audit-log-retention.md` |
| **Test** | Tidak ada. |
| **Acceptance criteria** | 2 ADR Accepted. |

### Phase 1a — Migration Snapshot Tables

| Field | Detail |
|-------|--------|
| **Objective** | Install schema tanpa mengubah behavior. |
| **Scope** | 3 migration baru (snapshot, rules, revoked). |
| **Out of Scope** | Application code yang baca/tulis ke tabel ini. |
| **Files baru** | `database/migrations/{ts}_create_permission_snapshots_table.php`, `{ts}_create_authorization_rules_table.php`, `{ts}_create_revoked_permissions_table.php` |
| **Files diubah** | Tidak ada. |
| **Migration** | 3 (lihat atas). |
| **Test** | `php artisan migrate` sukses + `migrate:rollback` sukses + migration test dengan nama tabel tidak clash. |
| **Acceptance criteria** | Tabel exist, schema match ADR-016, rollback bersih, no app change. |

### Phase 1b — Model + Factory + Index

| Field | Detail |
|-------|--------|
| **Objective** | Model siap untuk testing tanpa dipakai production. |
| **Scope** | 3 Eloquent model + 3 factory + index verification. |
| **Out of Scope** | Service / Listener yang gunakan model. |
| **Files baru** | `app/Models/PermissionSnapshot.php`, `app/Models/AuthorizationRule.php`, `app/Models/RevokedPermission.php`, 3 file factory. |
| **Migration** | Tidak ada. |
| **Test** | `tests/Unit/Models/PermissionSnapshotTest.php` (create, read, scope current, scope historic). |
| **Acceptance criteria** | Factory create row valid, query by fingerprint works, index ada. |

### Phase 1c — CI Guard

| Field | Detail |
|-------|--------|
| **Objective** | CI otomatis gagal jika ada forbidden pattern. |
| **Scope** | Lint script + GitHub Actions step. |
| **Out of Scope** | Lint untuk non-authorization code. |
| **Files baru** | `tools/ci/authorization-guard.sh`, `tools/ci/permission-lint.php`, `.github/workflows/authorization-guard.yml` |
| **Files diubah** | `.github/workflows/ci.yml` (tambah step). |
| **Test** | `tools/ci/permission-lint.php` di-test dengan sample violation (assignRole di controller tiruan) → harus exit non-zero. |
| **Acceptance criteria** | PR dengan `assignRole()` di controller → CI fail dengan pesan jelas. |

### Phase 2a — Parity Harness CLI

| Field | Detail |
|-------|--------|
| **Objective** | CLI mampu rekam output Spatie dan verifikasi Builder cocok. |
| **Scope** | 2 artisan command. |
| **Out of Scope** | Implementasi Builder. |
| **Files baru** | `app/Authorization/Console/ParityRecordCommand.php`, `app/Authorization/Console/ParityVerifyCommand.php` |
| **Files diubah** | `app/Console/Kernel.php` (auto-discover). |
| **Test** | `tests/Feature/Console/ParityRecordCommandTest.php` — rekam 1 user → file JSON valid. `ParityVerifyCommandTest.php` — verify dengan data sama → exit 0. |
| **Acceptance criteria** | `php artisan auth:parity:record --user=ID` menghasilkan JSON. `auth:parity:verify --input=FILE` exit 0 jika cocok, 1 jika drift. |

### Phase 2b — Parity Fixture 100 Skenario

| Field | Detail |
|-------|--------|
| **Objective** | 100 skenario existing user → fixture file. |
| **Scope** | Generate + verify fixture. |
| **Out of Scope** | Builder implementation. |
| **Files baru** | `tests/Fixtures/ParityFixtures.php` (100 user factory), `tests/Fixtures/data/parity-baseline.json` |
| **Files diubah** | Tidak ada. |
| **Test** | `tests/Feature/ParityFixtureTest.php` — load fixture → setiap entry dapat direkam ulang deterministically. |
| **Acceptance criteria** | 100 skenario terekonsiliasi, file JSON <= 5MB, repeatable run hasilkan hash sama. |

### Phase 2c — Parity CI

| Field | Detail |
|-------|--------|
| **Objective** | CI jalankan parity check otomatis. |
| **Scope** | 1 CI step. |
| **Out of Scope** | Builder optimization. |
| **Files baru** | Tidak ada. |
| **Files diubah** | `.github/workflows/ci.yml`. |
| **Test** | CI step success path (fixture valid → step green). |
| **Acceptance criteria** | `git push` → CI jalankan `auth:parity:verify` → step pass. |

### Phase 3a — Identity + Employment Provider

| Field | Detail |
|-------|--------|
| **Objective** | Builder menghasilkan output identik dengan Spatie untuk identity + employment. |
| **Scope** | 2 provider + Builder + DTO + interface. |
| **Out of Scope** | Assignment, delegation, rule engine. |
| **Files baru** | `app/Authorization/Contracts/PermissionProvider.php`, `app/Authorization/Builders/IdentityProvider.php`, `app/Authorization/Builders/EmploymentProvider.php`, `app/Authorization/Builders/EffectivePermissionBuilder.php`, `app/Authorization/DTO/PermissionBag.php`, `app/Authorization/DTO/Origin.php`, `app/Authorization/DTO/EffectivePermission.php`, `app/Authorization/Support/HasEffectivePermissions.php` (trait stub) |
| **Files diubah** | Tidak ada (User model existing tidak diubah — trait di-include via composition di test dulu). |
| **Event** | Tidak ada. |
| **Listener** | Tidak ada. |
| **Test** | Unit (provider + builder + DTO), Integration (100 parity fixture, drift 0%), Feature (build untuk sample user). |
| **Acceptance criteria** | Parity 0% drift untuk 100 skenario. Builder pure function (verified by property test). |

### Phase 3b — Assignment + Homeroom Provider

| Field | Detail |
|-------|--------|
| **Objective** | Assignment-derived permission masuk Builder. |
| **Scope** | 2 provider + supporting repos. |
| **Out of Scope** | Delegation, rule engine. |
| **Files baru** | `app/Authorization/Builders/AssignmentProvider.php`, `app/Authorization/Builders/HomeroomProvider.php`, `app/Authorization/DTO/ScopedPermission.php` |
| **Files diubah** | `app/Authorization/Builders/EffectivePermissionBuilder.php` (register provider). |
| **Test** | Unit (provider), Feature (wali kelas & teaching assignment scenario dari validation doc), Parity (existing — tidak boleh break). |
| **Acceptance criteria** | Pak Budi input nilai untuk XII-1 Matematika = true via Builder. Parity fixture lama masih pass. |

### Phase 3c — Delegation + Acting + Revocation Provider

| Field | Detail |
|-------|--------|
| **Objective** | Provider untuk delegation + acting + revocation siap (read-only). |
| **Scope** | 3 provider + 5 skeleton service. |
| **Out of Scope** | Real data source / production event. |
| **Files baru** | `app/Authorization/Builders/DelegationProvider.php`, `app/Authorization/Builders/ActingPositionProvider.php`, `app/Authorization/Builders/RevocationProvider.php`, `app/Authorization/Services/DelegationService.php` (skeleton), `app/Authorization/Services/ActingPositionService.php` (skeleton), `app/Authorization/Services/RevocationService.php` (skeleton) |
| **Files diubah** | `app/Authorization/Builders/EffectivePermissionBuilder.php` (register). |
| **Test** | Unit (5 skenario simulasi per provider). |
| **Acceptance criteria** | Simulasi delegation / acting / revocation menghasilkan bag yang sesuai. |

### Phase 3d — Rule Engine

| Field | Detail |
|-------|--------|
| **Objective** | Rule engine evaluasi 5 rule dasar. |
| **Scope** | Rule engine + 5 built-in facts + RuleRegistry + FactRegistry. |
| **Out of Scope** | Custom rule admin UI. |
| **Files baru** | `app/Authorization/Rules/RuleEvaluator.php`, `app/Authorization/Rules/RuleDefinition.php`, `app/Authorization/Rules/Fact/IdentityFact.php`, `app/Authorization/Rules/Fact/TeachingAssignmentFact.php`, `app/Authorization/Rules/Fact/HomeroomFact.php`, `app/Authorization/Rules/Fact/AcademicYearActiveFact.php`, `app/Authorization/Rules/Fact/TimeInWindowFact.php`, `app/Authorization/Registry/RuleRegistry.php`, `app/Authorization/Registry/FactRegistry.php`, `config/authorization/rules.php`, `config/authorization/facts.php` |
| **Files diubah** | `app/Providers/AuthServiceProvider.php` (register rule registry). |
| **Test** | Unit (5 rule × 3 skenario per rule), Integration (rule evaluation end-to-end). |
| **Acceptance criteria** | 5 rule evaluasi benar untuk happy path, false positive rate < 1% pada test set. |

### Phase 4a — OrganizationContext + Middleware

| Field | Detail |
|-------|--------|
| **Objective** | Context tersedia di Request, diisi middleware. |
| **Scope** | 1 DTO + 1 resolver + 1 middleware. |
| **Out of Scope** | Refactor controllers untuk pakai context. |
| **Files baru** | `app/Authorization/Contexts/OrganizationContext.php`, `app/Authorization/Contexts/ContextResolver.php`, `app/Authorization/Middleware/OrganizationContextMiddleware.php`, `app/Authorization/Middleware/EffectivePermissionMiddleware.php` |
| **Files diubah** | `app/Http/Kernel.php` (register middleware tapi tidak dipasang ke route existing). |
| **Test** | Integration (request ke sample route → middleware dipanggil → context ter-set). |
| **Acceptance criteria** | ContextResolver mengekstrak school/year/group/subject dari route param dengan benar. |

### Phase 4b — Snapshot Resolver + Cache + Listener

| Field | Detail |
|-------|--------|
| **Objective** | Snapshot read/write/cache bekerja. Listener rebuild async. |
| **Scope** | 3 service + 1 listener + 5 event + 5 observer (thin). |
| **Out of Scope** | Refactor controllers / policies pakai snapshot. |
| **Files baru** | `app/Authorization/Services/PermissionSnapshotResolver.php`, `app/Authorization/Services/PermissionSnapshotStore.php`, `app/Authorization/Snapshots/SnapshotFingerprint.php`, `app/Authorization/Listeners/RebuildPermissionsListener.php`, `app/Authorization/Listeners/InvalidateAuthCacheListener.php`, `app/Authorization/Listeners/RecordAuditListener.php`, `app/Authorization/Events/GtkEmploymentActivated.php`, `app/Authorization/Events/TeachingAssigned.php`, `app/Authorization/Events/HomeroomAssigned.php`, `app/Authorization/Events/DelegationCreated.php`, `app/Authorization/Events/ActingPositionAssigned.php` |
| **Files diubah** | `app/Providers/EventServiceProvider.php` (register listener). |
| **Observer** | 5 thin observer (GtkEmployment, TeachingAssignment, StudyGroup, Delegation, ActingPosition) — hanya dispatch event. |
| **Test** | Cache hit/miss (deterministic), Listener idempotent (dispatch 2x → state sama), Fingerprint deterministic. |
| **Acceptance criteria** | Snapshot round-trip works. Cache invalidation works on event dispatch. Rebuild idempotent. |

### Phase 4c — SidebarComposer Refactor

| Field | Detail |
|-------|--------|
| **Objective** | Sidebar baca menu visibility dari snapshot (read-only, gated by flag). |
| **Scope** | 1 composer refactor + 1 feature flag. |
| **Out of Scope** | Refactor semua menu. |
| **Files baru** | `app/Authorization/Support/Flag.php` (simple env-based flag). |
| **Files diubah** | `app/Http/View/Composers/SidebarComposer.php` (conditional: if flag on, use snapshot; else use existing Spatie). |
| **Test** | Feature (sidebar visibility untuk 5 role). |
| **Acceptance criteria** | Flag off → behavior identik dengan existing. Flag on → behavior identik untuk sample role (parity). |

### Phase 5a — Cutover Sekolah Pilot

| Field | Detail |
|-------|--------|
| **Objective** | 1 sekolah running via Builder dengan Spatie fallback. |
| **Scope** | 1 feature flag + 1 policy base class + monitoring. |
| **Out of Scope** | Cutover sekolah lain. |
| **Files baru** | `app/Authorization/Policies/BasePolicy.php` (common base), monitoring dashboard. |
| **Files diubah** | Existing policies → extend BasePolicy. Gate definitions. Routes untuk 1 sekolah pilot → aktifkan `authorization.driver = builder`. |
| **Migration** | `php artisan auth:backfill --school=ID` (backfill snapshot untuk sekolah pilot). |
| **Test** | Full integration (10 user × 10 endpoint di sekolah pilot). |
| **Acceptance criteria** | 7 hari running tanpa incident. Decision parity 100%. Spatie fallback siap jika ada masalah. |

### Phase 5b — Conflict Detector + Dashboard

| Field | Detail |
|-------|--------|
| **Objective** | Conflict detector + dashboard live. |
| **Scope** | 1 detector + 1 controller + 1 view + 1 migration. |
| **Out of Scope** | Auto-resolution. |
| **Files baru** | `app/Authorization/Services/AuthorizationConflictDetector.php`, `app/Authorization/DTO/ConflictReport.php`, `app/Authorization/Console/AuthDriftCommand.php`, `app/Http/Controllers/SuperAdmin/ConflictController.php`, `resources/views/superadmin/conflicts/index.blade.php`, `database/migrations/{ts}_create_auth_conflict_log_table.php`. |
| **Test** | Unit (detector 5 conflict type), Feature (dashboard render). |
| **Acceptance criteria** | Detector identify 5 conflict type dengan benar. Dashboard menampilkan list. |

### Phase 5c — Cutover Seluruh Sekolah

| Field | Detail |
|-------|--------|
| **Objective** | 100% sekolah via Builder. |
| **Scope** | Feature flag flip + backfill semua + monitoring ketat. |
| **Out of Scope** | Cleanup legacy. |
| **Files diubah** | `.env` → `AUTHORIZATION_DRIVER=builder` (semua sekolah). |
| **Migration** | `php artisan auth:backfill --all` (bulk backfill). |
| **Test** | Load test (simulasi 100 concurrent request), Smoke test 100 endpoint di 5 sekolah berbeda. |
| **Acceptance criteria** | 7 hari running tanpa incident. Snapshot 100% backfilled. Cache hit ratio > 95%. |

### Phase 6a — Observability

| Field | Detail |
|-------|--------|
| **Objective** | Dashboard + alert + runbook production-ready. |
| **Scope** | 4 metric + 4 alert + 1 runbook. |
| **Out of Scope** | Real-time monitoring (cukup near-real-time). |
| **Files baru** | `tools/observability/dashboards/authorization.json`, `tools/observability/alerts/authorization.yml`, `docs/runbook/authorization-incident.md` |
| **Test** | Smoke test alert firing (inject failure → alert muncul). |
| **Acceptance criteria** | Dashboard menampilkan 4 metric. Alert firing pada kondisi failure. Runbook punya step-by-step. |

### Phase 6b — Cleanup + Documentation

| Field | Detail |
|-------|--------|
| **Objective** | Legacy code removal + final documentation. |
| **Scope** | Remove forbidden patterns + final docs. |
| **Out of Scope** | ADR baru. |
| **Files diubah** | `app/Http/Controllers/*` — hapus `assignRole()` last remnants. `Spatie` — biarkan untuk identity only. Final doc update. |
| **Test** | Full regression suite. CI guard lint pass. |
| **Acceptance criteria** | CI guard 0 violation. Full regression 100% pass. Final doc published. |

---

## 4. ADR Planning

ADR yang **benar-benar dibutuhkan** untuk implementasi. Bukan ADR dokumentasi, tapi ADR **pendukung eksekusi**.

| ID | Judul | Phase yang butuhkan | Prioritas | Wajib sebelum coding? |
|----|-------|---------------------|-----------|------------------------|
| ADR-016 | Permission Snapshot Schema | 1a, 1b | P1 | **Ya** |
| ADR-017 | Write-Time Authorization Guard | 1c, semua phase | P1 | **Ya** |
| ADR-018 | Max Delegation Depth | 3c | P2 | Ya sebelum 3c |
| ADR-019 | Snapshot Backfill Strategy | 5a, 5c | P1 | **Ya** |
| ADR-020 | Feature Flag Schema | 5a, 5c | P1 | **Ya** |
| ADR-021 | Snapshot Retention Policy | 1a, 6a | P1 | **Ya** |
| ADR-022 | Authorization Cache Strategy | 4b | P1 | **Ya** |
| ADR-023 | Spatie-Builder Parity Harness | 2a, 2b, 2c | P1 | **Ya** |
| ADR-024 | Audit Log Retention | 6a | P2 | Tidak sebelum 6a |
| ADR-025 | Conflict Detector Scope (v1) | 5b | P2 | Ya sebelum 5b |
| ADR-026 | Rule Engine Scope (v1) | 3d | P2 | Ya sebelum 3d |
| ADR-027 | Performance Baseline | 5c | P2 | Ya sebelum 5c |
| ADR-028 | Cutover Rollback Policy | 5a, 5c | P1 | **Ya** |

**Total: 13 ADR.**

- **Wajib sebelum coding**: 8 (P1 dengan marker "Ya wajib").
- **Bisa ditunda**: 5 (P2 atau marker "tidak sebelum phase X").

**Rekomendasi eksekusi:**
- Phase 0a: ADR-016, ADR-017.
- Phase 0b: ADR-021, ADR-022.
- Phase 0c: ADR-023, ADR-028 (parity + rollback policy).
- Phase 5a: ADR-019, ADR-020.
- Phase 3c: ADR-018.
- Phase 3d: ADR-026.
- Phase 5b: ADR-025.
- Phase 5c: ADR-027.
- Phase 6a: ADR-024.

Tidak ada ADR lain yang dibutuhkan.

---

## 5. Feature Flag Strategy

Feature flag **wajib** untuk setiap perubahan yang menyentuh runtime authorization decision.

### Flag Inventory

| Flag | Default | Activated Phase | Removed Phase | Fallback |
|------|---------|------------------|----------------|----------|
| `AUTHORIZATION_DRIVER` | `spatie` | 5a (sekolah pilot) | 6b (cleanup) | `spatie` |
| `AUTHORIZATION_PARITY_RECORD` | `false` | 2a | 3a | (off) |
| `AUTHORIZATION_SNAPSHOT_WRITE` | `false` | 4b | 6b | `false` = read-only |
| `AUTHORIZATION_SIDEBAR_BUILDER` | `false` | 4c | 6b | `false` = existing Spatie |
| `AUTHORIZATION_LISTENER_ASYNC` | `false` | 4b (after success sync test) | 6b | `false` = sync rebuild |
| `AUTHORIZATION_CONFLICT_DETECTOR` | `false` | 5b | (stay optional) | `false` = off |
| `AUTHORIZATION_RULE_ENGINE` | `false` | 3d (shadow) | 6b | `false` = position-only |
| `AUTHORIZATION_PHASE_PILOT_SCHOOL` | `null` | 5a | 5c | `null` = no pilot |

### Per-Flag Detail

#### `AUTHORIZATION_DRIVER`

- **Apa:** Switch utama antara `spatie` (existing) dan `builder` (new).
- **Default:** `spatie` (backward compat).
- **Activated:** Phase 5a, hanya untuk 1 sekolah pilot.
- **Removed:** Phase 6b setelah 30 hari stable.
- **Fallback:** `spatie` (existing behavior).
- **Cara kerja:**
  - `null` / `spatie`: semua authorize() pakai Spatie.
  - `builder`: authorize() delegasi ke EffectivePermissionMiddleware, fallback ke Spatie jika Builder exception.

#### `AUTHORIZATION_PARITY_RECORD`

- **Apa:** Izinkan CLI `auth:parity:record` menulis fixture.
- **Default:** `false`.
- **Activated:** Phase 2a (dev only, tidak production).
- **Removed:** Phase 3a (saat Builder sudah jadi SSOT).
- **Fallback:** `false` = record only print to console.

#### `AUTHORIZATION_SNAPSHOT_WRITE`

- **Apa:** Izinkan listener tulis snapshot baru.
- **Default:** `false`.
- **Activated:** Phase 4b (testing only).
- **Removed:** Phase 6b.
- **Fallback:** `false` = listener no-op (read from cache only).

#### `AUTHORIZATION_SIDEBAR_BUILDER`

- **Apa:** SidebarComposer baca dari snapshot.
- **Default:** `false` (existing Spatie).
- **Activated:** Phase 4c.
- **Removed:** Phase 6b.
- **Fallback:** `false` = existing Spatie.

#### `AUTHORIZATION_LISTENER_ASYNC`

- **Apa:** RebuildPermissionsListener pakai queue.
- **Default:** `false` (sync).
- **Activated:** Phase 4b setelah sync tested OK.
- **Removed:** Phase 6b (always async after).
- **Fallback:** `false` = sync rebuild (slower but deterministic).

#### `AUTHORIZATION_CONFLICT_DETECTOR`

- **Apa:** Enable detector + dashboard.
- **Default:** `false`.
- **Activated:** Phase 5b.
- **Removed:** Tidak di-remove (permanent observability).
- **Fallback:** `false` = detector off.

#### `AUTHORIZATION_RULE_ENGINE`

- **Apa:** Enable rule engine evaluation di Builder.
- **Default:** `false` (position-only).
- **Activated:** Phase 3d shadow, Phase 4 enable.
- **Removed:** Phase 6b.
- **Fallback:** `false` = Builder tanpa rule (position-derived only).

#### `AUTHORIZATION_PHASE_PILOT_SCHOOL`

- **Apa:** School ID yang running Builder.
- **Default:** `null`.
- **Activated:** Phase 5a.
- **Removed:** Phase 5c (semua sekolah).
- **Fallback:** `null` = all schools pakai Spatie.

### Prinsip Feature Flag

1. **Setiap flag default ke `false` / `null` / `spatie`.**
2. **Flag off = behavior identik dengan hari ini.**
3. **Tidak ada flag yang "permanent on" tanpa ADR.**
4. **Flag removal butuh ADR + 7 hari observasi.**

---

## 6. Rollback Matrix

### Phase 0a — ADR

| Aspek | Detail |
|-------|--------|
| **Kondisi rollback** | ADR direject. |
| **Langkah rollback** | Hapus file ADR. |
| **Data dipulihkan** | Tidak ada. |
| **Dampak** | Tidak ada. |
| **Verifikasi** | File tidak exist. |

### Phase 0b, 0c — Sama seperti 0a.

### Phase 1a — Migration

| Aspek | Detail |
|-------|--------|
| **Kondisi rollback** | Migration clash dengan tabel existing, atau schema error. |
| **Langkah rollback** | `php artisan migrate:rollback --step=N` (rollback 3 migration). |
| **Data dipulihkan** | Tabel `permission_snapshots`, `authorization_rules`, `revoked_permissions` di-drop. |
| **Dampak** | Tidak ada (tabel kosong). |
| **Verifikasi** | `php artisan migrate:status` → migration rolled back. Tidak ada error di existing query. |

### Phase 1b — Model

| Aspek | Detail |
|-------|--------|
| **Kondisi rollback** | Model conflict dengan existing. |
| **Langkah rollback** | `git revert` commit. `composer dump-autoload`. |
| **Data dipulihkan** | Tidak ada (tidak dipakai production). |
| **Dampak** | Tidak ada. |
| **Verifikasi** | Tidak ada autoload error. Test pass. |

### Phase 1c — CI Guard

| Aspek | Detail |
|-------|--------|
| **Kondisi rollback** | CI step false positive (gagal padahal seharusnya pass). |
| **Langkah rollback** | Disable CI step via config atau revert commit. |
| **Data dipulihkan** | Tidak ada. |
| **Dampak** | CI normal kembali (tapi tidak ada guard). |
| **Verifikasi** | CI pass pada PR yang seharusnya pass. |

### Phase 2a — Parity Harness

| Aspek | Detail |
|-------|--------|
| **Kondisi rollback** | CLI crash atau output corrupt. |
| **Langkah rollback** | `git revert` commit. Hapus file fixture. |
| **Data dipulihkan** | File JSON fixture di-revert atau di-delete. |
| **Dampak** | Tidak ada (CLI dev only). |
| **Verifikasi** | `php artisan auth:parity:record --help` return help. |

### Phase 2b — Fixture 100 Skenario

| Aspek | Detail |
|-------|--------|
| **Kondisi rollback** | Fixture data corrupt atau tidak reproducible. |
| **Langkah rollback** | Re-generate fixture dari 0. |
| **Data dipulihkan** | File JSON di-overwrite. |
| **Dampak** | Tidak ada. |
| **Verifikasi** | Re-run fixture generation → hash sama. |

### Phase 2c — Parity CI

| Aspek | Detail |
|-------|--------|
| **Kondisi rollback** | CI selalu fail karena drift atau bug. |
| **Langkah rollback** | Disable CI step. Investigate. |
| **Data dipulihkan** | Tidak ada. |
| **Dampak** | CI normal kembali. |
| **Verifikasi** | PR valid → CI pass. |

### Phase 3a — Identity + Employment Provider

| Aspek | Detail |
|-------|--------|
| **Kondisi rollback** | Builder parity drift > 0% atau Builder exception. |
| **Langkah rollback** | `git revert` semua commit phase 3a. Feature flag `AUTHORIZATION_DRIVER=spatie`. |
| **Data dipulihkan** | Tidak ada (shadow mode = no DB write production). |
| **Dampak** | Tidak ada (semua di shadow). |
| **Verifikasi** | Existing Spatie code tidak berubah. Parity fixture pass. |

### Phase 3b — Assignment + Homeroom Provider

| Aspek | Detail |
|-------|--------|
| **Kondisi rollback** | Provider return wrong permission untuk wali kelas / teaching. |
| **Langkah rollback** | `git revert`. Disable provider via config `authorization.providers.assignment = false`. |
| **Data dipulihkan** | Tidak ada. |
| **Dampak** | Tidak ada (shadow). |
| **Verifikasi** | Builder behavior identical dengan sebelum phase 3b. |

### Phase 3c — Delegation + Acting + Revocation

| Aspek | Detail |
|-------|--------|
| **Kondisi rollback** | Provider exception atau wrong scope. |
| **Langkah rollback** | `git revert`. |
| **Data dipulihkan** | Tidak ada. |
| **Dampak** | Tidak ada (read-only, belum ada data source). |
| **Verifikasi** | Unit test pass. Integration test pass. |

### Phase 3d — Rule Engine

| Aspek | Detail |
|-------|--------|
| **Kondisi rollback** | Rule evaluator return wrong result, false positive tinggi. |
| **Langkah rollback** | Disable `AUTHORIZATION_RULE_ENGINE`. Revert rule registry. |
| **Data dipulihkan** | Tidak ada. |
| **Dampak** | Tidak ada (rule engine di skip). |
| **Verifikasi** | Builder tanpa rule = Builder position-only. |

### Phase 4a — Context + Middleware

| Aspek | Detail |
|-------|--------|
| **Kondisi rollback** | Context salah populate → authorization salah. |
| **Langkah rollback** | Revert commit. Hapus middleware dari route list. |
| **Data dipulihkan** | Tidak ada. |
| **Dampak** | Tidak ada (middleware belum dipakai). |
| **Verifikasi** | Request tanpa middleware → existing flow works. |

### Phase 4b — Snapshot Resolver + Cache + Listener

| Aspek | Detail |
|-------|--------|
| **Kondisi rollback** | Cache stale, listener tidak rebuild, queue overflow. |
| **Langkah rollback** | Disable `AUTHORIZATION_SNAPSHOT_WRITE` + `AUTHORIZATION_LISTENER_ASYNC`. `php artisan cache:forget auth:*`. |
| **Data dipulihkan** | Snapshot rows existing di-DB (tidak di-drop). Cache cleared. |
| **Dampak** | Snapshot ditulis sync lagi atau tidak ditulis sama sekali. |
| **Verifikasi** | `php artisan auth:cache:verify` → cache state consistent. Existing flow works. |

### Phase 4c — SidebarComposer

| Aspek | Detail |
|-------|--------|
| **Kondisi rollback** | Menu salah visible / hidden. |
| **Langkah rollback** | Disable `AUTHORIZATION_SIDEBAR_BUILDER`. |
| **Data dipulihkan** | Tidak ada. |
| **Dampak** | Tidak ada (existing Spatie). |
| **Verifikasi** | Sidebar identical dengan sebelum phase 4c. |

### Phase 5a — Cutover Sekolah Pilot

| Aspek | Detail |
|-------|--------|
| **Kondisi rollback** | 403 error, performance degrade, parity drift di sekolah pilot. |
| **Langkah rollback** | `AUTHORIZATION_DRIVER=spatie`, `AUTHORIZATION_PHASE_PILOT_SCHOOL=null`. Verify. |
| **Data dipulihkan** | Snapshot rows existing di-DB (tidak di-drop). Cache cleared (`auth:*`). |
| **Dampak** | Sekolah pilot kembali ke Spatie. Snapshot tidak terpakai sampai re-enabled. |
| **Verifikasi** | 1. `php artisan auth:snapshot:test --user=ID` → builder vs snapshot consistent. 2. Manual test 10 endpoint di sekolah pilot. 3. Monitoring tidak ada error spike. |

### Phase 5b — Conflict Detector

| Aspek | Detail |
|-------|--------|
| **Kondisi rollback** | Detector crash atau false positive massal. |
| **Langkah rollback** | Disable `AUTHORIZATION_CONFLICT_DETECTOR`. |
| **Data dipulihkan** | `auth_conflict_log` rows existing (tidak di-drop). |
| **Dampak** | Detector off. Dashboard 404. |
| **Verifikasi** | `php artisan auth:conflict:detect --user=ID` → exit 0 (silent). |

### Phase 5c — Cutover Seluruh Sekolah

| Aspek | Detail |
|-------|--------|
| **Kondisi rollback** | Insiden massal. Banyak user 403 / 500. |
| **Langkah rollback** | `AUTHORIZATION_DRIVER=spatie`. Clear cache. Monitor. |
| **Data dipulihkan** | Snapshot rows tetap ada di DB (untuk investigasi). Cache cleared. |
| **Dampak** | Semua sekolah kembali ke Spatie. |
| **Verifikasi** | 1. `php artisan auth:drift` → 0 user dengan builder snapshot. 2. Manual smoke test 10 endpoint × 5 sekolah. 3. Error rate turun ke baseline. |

### Phase 6a — Observability

| Aspek | Detail |
|-------|--------|
| **Kondisi rollback** | Dashboard / alert false positive. |
| **Langkah rollback** | Disable alert rule. Hide dashboard. |
| **Data dipulihkan** | Metric retention di-config existing. |
| **Dampak** | Tidak ada (observability opsional). |
| **Verifikasi** | Alert silent. Dashboard tidak error. |

### Phase 6b — Cleanup

| Aspek | Detail |
|-------|--------|
| **Kondisi rollback** | Setelah legacy code dihapus, ada use case yang luput. |
| **Langkah rollback** | `git revert` cleanup commit. Kembalikan code. |
| **Data dipulihkan** | Tidak ada (code only). |
| **Dampak** | Kode kembali ke state sebelum cleanup. |
| **Verifikasi** | Full regression test pass. CI guard tidak flag. |

---

## 7. Regression Matrix

### Phase 1a — Migration

| Aspek | Detail |
|-------|--------|
| **Modul dites ulang** | Semua model existing. |
| **Skenario regresi** | `php artisan migrate` + `migrate:rollback` + cek query existing. |
| **Prioritas** | P1. |
| **Estimasi** | 2 jam. |

### Phase 3a — Identity + Employment Provider

| Aspek | Detail |
|-------|--------|
| **Modul dites ulang** | Auth flow (login, logout, role check). GTK CRUD. Employment CRUD. |
| **Skenario regresi** | 1. Login dengan 5 role berbeda. 2. Assign GTK ke sekolah. 3. Mutasi GTK. 4. Cek `hasRole()` masih jalan. 5. Cek Gate masih jalan. |
| **Prioritas** | P1. |
| **Estimasi** | 8 jam. |

### Phase 3b — Assignment + Homeroom

| Aspek | Detail |
|-------|--------|
| **Modul dites ulang** | Teaching assignment CRUD. Homeroom assignment. Input nilai. |
| **Skenario regresi** | 1. Assign guru ke rombel + mapel. 2. Wali kelas untuk rombel. 3. Pak Budi input nilai XII-1 Matematika (happy path). 4. Pak Budi TIDAK boleh input nilai XII-2 (negative path). 5. Pak Budi TIDAK boleh input nilai mapel lain di XII-1 (negative path). |
| **Prioritas** | P1. |
| **Estimasi** | 8 jam. |

### Phase 3c — Delegation + Acting + Revocation

| Aspek | Detail |
|-------|--------|
| **Modul dites ulang** | (Belum ada modul - data source belum ada). |
| **Skenario regresi** | Unit test simulasi 5 skenario per provider. |
| **Prioritas** | P2. |
| **Estimasi** | 4 jam. |

### Phase 3d — Rule Engine

| Aspek | Detail |
|-------|--------|
| **Modul dites ulang** | Authorization flow yang melibatkan rule. |
| **Skenario regresi** | 5 rule × 3 skenario. Negative path (rule false). Edge case (null context). |
| **Prioritas** | P1. |
| **Estimasi** | 6 jam. |

### Phase 4a — Context + Middleware

| Aspek | Detail |
|-------|--------|
| **Modul dites ulang** | Route middleware stack. Multi-school URL. |
| **Skenario regresi** | 1. Request ke `/nilai/input/{rombel}/{mapel}` → context ter-set. 2. Request tanpa route param → context fallback. 3. Multi-school URL → school context correct. |
| **Prioritas** | P1. |
| **Estimasi** | 4 jam. |

### Phase 4b — Snapshot + Cache + Listener

| Aspek | Detail |
|-------|--------|
| **Modul dites ulang** | Cache invalidation. Event dispatch. Listener idempotent. |
| **Skenario regresi** | 1. Ubah GtkEmployment → snapshot rebuild. 2. Dispatch event 2x → snapshot tetap 1 row. 3. Cache stale → listener invalidate. 4. Queue down → listener fallback sync. |
| **Prioritas** | P1. |
| **Estimasi** | 8 jam. |

### Phase 4c — SidebarComposer

| Aspek | Detail |
|-------|--------|
| **Modul dites ulang** | Sidebar rendering per role. |
| **Skenario regresi** | 1. Login 5 role → sidebar correct. 2. Menu visibility match Spatie. 3. Menu count match. |
| **Prioritas** | P1. |
| **Estimasi** | 4 jam. |

### Phase 5a — Cutover Pilot

| Aspek | Detail |
|-------|--------|
| **Modul dites ulang** | SEMUA modul authorization di sekolah pilot. |
| **Skenario regresi** | 1. Full smoke test 50 endpoint. 2. Login 10 user berbeda. 3. Cek parity decision-by-decision. 4. Load test 100 concurrent. 5. Monitoring alert firing test. |
| **Prioritas** | P1. |
| **Estimasi** | 16 jam (2 hari). |

### Phase 5b — Conflict Detector

| Aspek | Detail |
|-------|--------|
| **Modul dites ulang** | Dashboard rendering. Detector accuracy. |
| **Skenario regresi** | 1. Inject 3 conflict → detector identify. 2. Dashboard menampilkan. 3. Performance: detect 100 user < 30 detik. |
| **Prioritas** | P2. |
| **Estimasi** | 4 jam. |

### Phase 5c — Cutover All

| Aspek | Detail |
|-------|--------|
| **Modul dites ulang** | Semua sekolah. |
| **Skenario regresi** | 1. Smoke test 50 endpoint × 5 sekolah. 2. Load test 100 concurrent × 5 sekolah. 3. Cache hit ratio > 95%. 4. Decision parity 100%. |
| **Prioritas** | P1. |
| **Estimasi** | 24 jam (3 hari). |

### Phase 6b — Cleanup

| Aspek | Detail |
|-------|--------|
| **Modul dites ulang** | Semua. Full regression. |
| **Skenario regresi** | Full test suite (semua test di `tests/Feature` dan `tests/Unit`). |
| **Prioritas** | P1. |
| **Estimasi** | 8 jam. |

---

## 8. Success Metrics

Setiap phase punya indikator keberhasilan terukur.

### Phase 0a / 0b / 0c

| Metrik | Target |
|--------|--------|
| ADR ditulis | 6 |
| ADR status | Accepted |
| Review | Minimal 2 reviewer (architect + tech lead) |

### Phase 1a

| Metrik | Target |
|--------|--------|
| Migration count | 3 sukses |
| Migration test | 100% pass (up + down) |
| No app behavior change | ✅ (semua test existing pass) |

### Phase 1b

| Metrik | Target |
|--------|--------|
| Model count | 3 |
| Factory test | 100% pass |
| Index verified | ✅ |

### Phase 1c

| Metrik | Target |
|--------|--------|
| Forbidden patterns covered | 5 (assignRole, hasRole position, magic string, direct query, service locator) |
| CI step | Active + green |
| False positive rate | < 1% |

### Phase 2a

| Metrik | Target |
|--------|--------|
| CLI command count | 2 |
| Deterministic test | 100% (run 10× → hash sama) |

### Phase 2b

| Metrik | Target |
|--------|--------|
| Fixture scenarios | ≥ 100 |
| Fixture size | < 5MB |
| Reproducibility | 100% |

### Phase 2c

| Metrik | Target |
|--------|--------|
| Parity drift | 0% |
| CI step pass rate | 100% |

### Phase 3a

| Metrik | Target |
|--------|--------|
| Parity drift (100 fixture) | 0% |
| Builder pure function | ✅ (verified by property test) |
| Provider coverage | ≥ 90% |
| Builder coverage | ≥ 90% |

### Phase 3b

| Metrik | Target |
|--------|--------|
| Provider coverage | ≥ 90% |
| Feature test scenarios | ≥ 20 (10 happy + 10 negative) |
| Parity lama | 0% drift |

### Phase 3c

| Metrik | Target |
|--------|--------|
| Provider coverage | ≥ 85% |
| Unit test per provider | ≥ 5 |

### Phase 3d

| Metrik | Target |
|--------|--------|
| Rule count | 5 |
| Rule unit test | ≥ 15 (5 rule × 3 skenario) |
| Rule integration test | ≥ 10 |
| False positive rate | < 1% |

### Phase 4a

| Metrik | Target |
|--------|--------|
| Middleware coverage | 100% |
| Context test scenarios | ≥ 10 |

### Phase 4b

| Metrik | Target |
|--------|--------|
| Cache hit ratio (warm) | ≥ 95% |
| Cache hit ratio (cold) | ≥ 60% (within 5 min) |
| Listener idempotent | 100% (dispatch 2x → 1 effect) |
| Snapshot rebuild latency (cold) | < 200ms |
| Snapshot read latency (warm) | < 5ms |

### Phase 4c

| Metrik | Target |
|--------|--------|
| Sidebar decision parity | 100% |
| Composer coverage | ≥ 90% |

### Phase 5a

| Metrik | Target |
|--------|--------|
| Production incident | 0 (during 7-day pilot) |
| Decision parity (production sample) | 100% (n=100) |
| Decision latency | < 50ms p95 |
| Cache hit ratio | > 90% |
| Error rate | < baseline + 0.1% |

### Phase 5b

| Metrik | Target |
|--------|--------|
| Conflict detection accuracy | ≥ 95% (5 conflict type) |
| Detector latency | < 30s for 100 user |
| Dashboard render | < 2s |

### Phase 5c

| Metrik | Target |
|--------|--------|
| Production incident | 0 (during 7-day post-cutover) |
| Decision parity | 100% |
| Cache hit ratio | > 95% |
| Decision latency p95 | < 50ms |
| Decision latency p99 | < 200ms |
| Snapshot backfill success | 100% |

### Phase 6a

| Metrik | Target |
|--------|--------|
| Dashboard metric count | 4 |
| Alert count | 4 |
| Runbook step | ≥ 10 |

### Phase 6b

| Metrik | Target |
|--------|--------|
| CI guard violations | 0 |
| Full regression | 100% pass |
| Coverage overall | ≥ 80% |
| Final doc published | ✅ |

---

## 9. Phase Readiness Checklist

Sebelum sebuah phase dimulai, SEMUA kondisi di bawah harus terpenuhi.

### Generic Readiness (Semua Phase)

- [ ] **Dependency phase sebelumnya selesai.**
- [ ] **ADR yang dibutuhkan phase ini sudah Accepted.**
- [ ] **Feature flag tersedia** (jika phase ini butuh flag).
- [ ] **Rollback plan tertulis dan tested** (jika applicable).
- [ ] **Test plan tersedia** (jika phase ini punya test).
- [ ] **Acceptance criteria jelas dan measurable.**
- [ ] **Code review sudah di-schedule** (minimal 1 architect).
- [ ] **Timeline di-agree** dengan lead.

### Per-Phase Specific

#### Phase 0a — ADR

- [ ] Constitution approved.
- [ ] Domain model reviewed (read-only reference).
- [ ] 2 reviewer lined up.

#### Phase 1a — Migration

- [ ] ADR-016 Accepted.
- [ ] No tabel existing bernama sama.
- [ ] DB backup procedure ready.
- [ ] Migration dapat di-run di staging environment sukses.

#### Phase 1c — CI Guard

- [ ] Forbidden patterns list finalized.
- [ ] Sample violation file untuk testing tersedia.
- [ ] CI step tidak conflict dengan existing lint.

#### Phase 2a — Parity Harness

- [ ] ADR-023 Accepted.
- [ ] Spatie code dapat di-introspect (return `permissions` & `roles` array).

#### Phase 3a — Builder

- [ ] Phase 2c green (parity CI pass dengan fixture existing).
- [ ] Identity + employment source stable (no recent schema change).

#### Phase 3d — Rule Engine

- [ ] ADR-026 Accepted.
- [ ] 5 rule definitions finalized dengan product owner.
- [ ] Fact registry contract defined.

#### Phase 4a — Context + Middleware

- [ ] Routes existing di-mapping ke context source.
- [ ] Default value strategy untuk missing context.

#### Phase 4b — Snapshot + Cache

- [ ] ADR-022 Accepted.
- [ ] Cache backend Redis (existing) confirmed ready.
- [ ] Queue backend configured (database atau redis).
- [ ] Listener priority chain reviewed.

#### Phase 5a — Cutover Pilot

- [ ] Phase 4c green (sidebar parity 100%).
- [ ] ADR-019, ADR-020, ADR-028 Accepted.
- [ ] Pilot school dipilih dan informed.
- [ ] Rollback runbook tested di staging.
- [ ] Monitoring dashboard siap.
- [ ] On-call rotation informed.
- [ ] Backfill snapshot sukses untuk pilot user.

#### Phase 5c — Cutover All

- [ ] Phase 5a sukses 7 hari.
- [ ] ADR-027 Accepted.
- [ ] Load test pass di staging.
- [ ] Backfill snapshot untuk ALL user sukses.
- [ ] Rollback runbook ready dan tested.
- [ ] Incident response team on standby.

#### Phase 6b — Cleanup

- [ ] Phase 5c sukses 14 hari.
- [ ] CI guard tidak flag existing code.
- [ ] Final documentation outline ready.

### Readiness Sign-Off

Setiap phase butuh sign-off dari:

| Role | Sign-Off yang dibutuhkan |
|------|--------------------------|
| **Tech Lead** | Implementation, code review, test. |
| **Architect** | Architecture compliance, ADR. |
| **Product Owner** | Acceptance criteria, business validation (Phase 5+). |
| **DevOps** | Migration plan, rollback, monitoring (Phase 1a, 5a, 5c). |

---

## 10. Execution Order & Critical Path

### 10.1. Critical Path

Critical path adalah fase yang jika tertunda, menunda seluruh rollout:

```
Phase 0a → 1a → 1c → 2a → 2b → 2c → 3a → 4b → 5a → 5c → 6b
```

Total: 12 minggu (critical path).

### 10.2. Parallel Opportunities

Beberapa phase bisa berjalan parallel **setelah dependency siap**:

| Phase | Bisa parallel dengan | Catatan |
|-------|----------------------|---------|
| 3b | 3d (sebagian) | 3d butuh provider contract, bukan impl detail |
| 4a | 3d selesai | Strict sequential |
| 4c | 5a sebagian | 4c butuh 4b selesai |
| 5b | 6a | Conflict detector independent dari observability |
| 6a | 6b sebagian | Runbook bisa mulai saat 5c |

**Rekomendasi:** Untuk efisiensi, **3b dan 3d bisa overlap 1 minggu** (3d mulai minggu ke-2 dari 3b).

### 10.3. Milestone Summary

| Milestone | Tanggal (estimasi dari mulai) | Pencapaian |
|-----------|------------------------------|------------|
| **M1** | Week 1 | 6 ADR Accepted. |
| **M2** | Week 2 | Schema ready, CI guard active, parity harness functional. |
| **M3** | Week 3 | 100 parity fixture, CI parity green. |
| **M4** | Week 5 | Builder identity+employment parity 0%. |
| **M5** | Week 8 | Builder full (assignment + rule) parity + functional. |
| **M6** | Week 10 | Middleware + cache + sidebar parity 100%. |
| **M7** | Week 11 | 1 sekolah pilot running via Builder 7 hari. |
| **M8** | Week 12 | 100% sekolah via Builder 7 hari. |
| **M9** | Week 13 | Observability + cleanup complete. **DONE.** |

### 10.4. Decision Gates

Setiap milestone adalah **decision gate**. Jika milestone tidak tercapai:

| Tidak tercapai | Tindakan |
|----------------|----------|
| M1 | Stop. Review ADR. Jangan lanjut. |
| M2 | Pause 1 minggu. Investigate schema/guard issue. |
| M3 | Perbesar fixture ke 200 skenario. |
| M4 | **STOP**. Investigate parity drift. Jangan lanjut ke 3b. |
| M5 | Perpanjang 3d 1 minggu. Tambah rule test. |
| M6 | Perpanjang 4c 3 hari. Tambah feature test. |
| M7 | **STOP cutover 5c**. Investigate production issue. |
| M8 | **STOP cleanup**. Investigate drift. |
| M9 | Done. |

### 10.5. Final Sanity Check

Sebelum M9 di-declare DONE, jalankan:

```
[ ] Full regression test pass
[ ] CI guard 0 violation
[ ] All acceptance criteria phase 1-6b pass
[ ] All success metrics target met
[ ] All ADR Accepted
[ ] Final documentation published
[ ] Team retrospective done
[ ] Post-mortem template created
[ ] On-call trained
[ ] Product Owner sign-off
```

**Jika salah satu tidak terpenuhi, M9 belum DONE.**

---

## Penutup

Dokumen ini adalah **jembatan** antara arsitektur dan implementasi.

Setelah dokumen ini disetujui:

1. ✅ **Tulis 6 ADR Phase 0** (sesuai urutan 0a → 0c).
2. ✅ **Implementasi Phase 1a** (schema only).
3. ✅ **Implementasi Phase 1b-1c** (model + CI guard).
4. ✅ **Lanjutkan sesuai roadmap §10.1 critical path**.

Tidak ada lagi dokumen arsitektur baru. Tidak ada lagi layer baru.

Setiap perubahan hanya melalui:
- ADR baru (untuk keputusan arsitektur baru).
- Code change (untuk implementasi).

> END OF TRANSITION PLAN

---

## Lampiran A — Quick Reference: Total Estimates

| Kategori | Detail |
|----------|--------|
| **Total durasi critical path** | 13 minggu |
| **Total phase count** | 22 sub-phase (0a-0c, 1a-1c, 2a-2c, 3a-3d, 4a-4c, 5a-5c, 6a-6b) |
| **Total ADR baru** | 13 (8 wajib sebelum coding, 5 ditunda) |
| **Total feature flag** | 8 |
| **Total rollback runbook** | 22 |
| **Total regression test scenarios** | ≥ 150 |
| **Total success metrics** | ≥ 60 |
| **Decision gates** | 9 |

## Lampiran B — Decision Template per Phase

Copy-paste untuk setiap phase start:

```
PHASE [ID]: [Name]
============================================
Start date: YYYY-MM-DD
Tech Lead: @username
Architect: @username

Readiness:
[ ] Dependency terpenuhi
[ ] ADR required Accepted
[ ] Feature flag configured
[ ] Rollback plan tested
[ ] Test plan ready
[ ] Acceptance criteria agreed

Implementation:
- Files baru: [list]
- Files diubah: [list]
- Migrations: [list]
- Events: [list]
- Listeners: [list]
- Tests: [list]

Success metrics:
- [metric 1]: [target]
- [metric 2]: [target]

Sign-off:
- Tech Lead: [date]
- Architect: [date]
- Product Owner: [date] (if applicable)
- DevOps: [date] (if applicable)
```

---

> Dokumen ini final.
> Setelah approval, langsung lanjut ke penulisan ADR Phase 0a (016 + 017).
> Tidak ada lagi dokumen tambahan.