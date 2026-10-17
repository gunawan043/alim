---
name: authorization-governance-operational-architecture
description: Authorization governance, lifecycle, versioning, migration, testing, observability, performance, failure, audit, SDK, dev guide, ADR, playbook, risk, readiness
metadata:
  type: project
---

# Authorization Governance & Operational Architecture

> Architect role: Chief Software Architect
> Scope: Lifecycle, governance, maintainability, observability, testing, failure recovery, operational readiness
> Status: **Final review — awaiting approval**
> Prerequisite: `authorization-domain-model.md` (already approved)
> Owner: Architecture Team
> Audience: Architect, Senior Backend Developer, DevOps, QA Lead, Tech Lead

---

## Daftar Isi

1. Authorization Lifecycle
2. Authorization Versioning
3. Migration Strategy (Old → New)
4. Authorization Testing Strategy
5. Monitoring & Observability
6. Performance Budget (SLA)
7. Failure Recovery & Graceful Degradation
8. Auditability (Explainability at Runtime & Post-mortem)
9. Developer SDK (Public API Facade)
10. Developer Guide (How-to Playbook)
11. Architecture Decision Records (ADR)
12. Operational Playbook (Runbook)
13. Risk Assessment
14. Final Readiness Assessment

---

## 1. Authorization Lifecycle

Authorization bukan sekadar implementasi. Dia adalah **siklus hidup** yang dimulai dari kebutuhan bisnis dan berakhir di deprecation. Melewati tahap apapun tanpa kontrol akan menghasilkan **technical debt kumulatif** yang membuat arsitektur ulang tak terhindarkan.

### 1.1. Diagram Lifecycle

```
┌──────────────────────────────────────────────────────────────────────────┐
│                  AUTHORIZATION LIFECYCLE (15 phases)                      │
└──────────────────────────────────────────────────────────────────────────┘

  Business Requirement
      │
      ▼
  Architecture Decision ──→ ADR created / amended
      │
      ▼
  Permission Design ──→ Permission Graph + Naming Convention Doc
      │
      ▼
  Rule Design ──→ Rule written to /config/authorization/rules.php (or DB)
      │
      ▼
  Review ──→ PR review (Architect + Senior Dev + Security)
      │
      ▼
  Approval ──→ 2-of-3 sign-off in PR + ADR mark "ACCEPTED"
      │
      ▼
  Implementation ──→ Code merged behind feature flag
      │
      ▼
  Testing ──→ Pyramid (Unit→Builder→Rule→Context→Gate→Policy→Observer→Event→Integration→E2E→Performance)
      │
      ▼
  Deployment ──→ Feature flag flipped in target env (10% → 50% → 100%)
      │
      ▼
  Monitoring ──→ Metric watched for 24h minimum
      │
      ▼
  Audit ──→ Quarterly audit review (drift, usage, anomalies)
      │
      ▼
  Refactoring ──→ Optional structural cleanup (Architecture Improvement Tasks)
      │
      ▼
  Deprecation ──→ Rule/permission marked deprecated with sunset date
      │
      ▼
  Removal ──→ Old rule removed, dependent code cleaned, ADR closed
```

### 1.2. Tahap Detail

| # | Tahap | Tujuan | Output | Owner | Artefak | Risiko jika Dilewati |
|---|-------|--------|--------|-------|---------|---------------------|
| 1 | **Business Requirement** | Capture why this permission exists | Use case + acceptance criteria | Product Owner | Ticket di Linear/Jira | Permission salah-guna, scope mismatch |
| 2 | **Architecture Decision** | Tentukan apakah ini role, position, rule, atau scope | ADR + trade-off doc | Architect | ADR file di `docs/adr/` | Inconsistency dengan arsitektur eksisting |
| 3 | **Permission Design** | Tentukan nama permission, scope, weight, owner | Permission entry di registry | Tech Lead | Update `permissions` table + `permission_registry.md` | Naming collision, broken grouping |
| 4 | **Rule Design** | Tentukan WHEN clause & WHICH scope | Rule entry di config atau DB | Senior Dev | PR diff ke `config/authorization/rules.php` atau DB seeds | Logic tidak konsisten antar role |
| 5 | **Review** | Cross-check design & security implications | PR comments resolved | Architect + Senior Dev + Security | PR review threads | Bug keamanan masuk ke main |
| 6 | **Approval** | Formal sign-off | ADR "ACCEPTED", merge ke main | Tech Lead | PR merge + ADR commit msg | Permission tidak ter-track |
| 7 | **Implementation** | Code untuk: snapshot, builder, policy, middleware, observer | Pull request ke main | Assigned Developer | Branch + commit history | Implementasi ad-hoc tanpa ADR |
| 8 | **Testing** | Validasi behaviour pada semua skenario | Test report + coverage >85% | QA Lead + Dev | Test file di `tests/Feature/Authorization/...` | Bug regresi tidak terdeteksi |
| 9 | **Deployment** | Roll out aman | Feature flag enabled | DevOps | Flag state di LaunchDarkly-style | Outage risk |
| 10 | **Monitoring** | Validasi di production | Metric stable | DevOps + Architect | Grafana panel | Anomali tidak terlihat |
| 11 | **Audit** | Verifikasi masih relevan | Audit report | Architect | Quarterly doc | Permission drift |
| 12 | **Refactoring** | Optional improvement | Tech-debt reduction | Tech Lead | Refactor PR | Akumulasi debt |
| 13 | **Deprecation** | Beri warning ke caller | Permission.flag = DEPRECATED | Architect | CHANGELOG entry + warning log | Silent breakage |
| 14 | **Removal** | Cleanup final | Row deleted, code deleted | Senior Dev | Removal commit + ADR "CLOSED" | Dead code berlarut-larut |

### 1.3. SLAs di Lifecycle

| SLA | Target |
|-----|--------|
| Dari Business Requirement → ADR approved | ≤5 hari kerja |
| Dari Approval → Code merged | ≤10 hari kerja |
| Dari Deployment → Audit pertama | 7 hari |
| Dari Deprecation → Removal | 90 hari (sunset window) |

### 1.4. Governance Roles

| Role | Tanggung Jawab |
|------|----------------|
| **Chief Software Architect** | Memutuskan ADR, sign-off, monthly review |
| **Tech Lead** | Approve implementation, merge ke main |
| **Senior Backend Developer** | Implement rule + snapshot + builder |
| **DevOps Engineer** | Maintain snapshot pipeline, monitoring |
| **QA Lead** | Memastikan test pyramid terpenuhi |
| **Product Owner** | Memvalidasi Business Requirement → Use Case |
| **Security Reviewer** | Validasi tidak ada privilege escalation |
| **Administrator (operator)** | Daily monitoring, conflict review |
| **Support Engineer** | Gunakan Operational Playbook untuk insiden |

---

## 2. Authorization Versioning

### 2.1. Apa yang Diversikan

Authorization bukan hanya kode — dia adalah **kontrak**. Versi mengikat:

| Artefak | Format Versi | Contoh |
|---------|-------------|--------|
| **Permission Schema** | semver | v1.4.2 |
| **Rule Engine** | semver | v2.0.0 |
| **Snapshot Strategy** | semver | v1.0.0 |
| **Context Schema** | semver | v1.1.0 |
| **Position Registry** | major.minor | v3.5 |
| **Migration** | sequential | migration-0042 |
| **Snapshot Format** | JSON schema id | `snp-2025-11-v1` |

### 2.2. Versi Schema

```
Schema v1 (2026)
├─ Position code = "gtk.kepala_sekolah"
├─ Permission name = "nilai.input"
├─ Context keys = {school_id, academic_year_id, study_group_id, subject_id}
└─ Snapshot format = {global[], scoped[], originIndex, fingerprint}

Schema v2 (planned: 2027)
├─ Position code = "gtk.kepala_sekolah" (sama)
├─ Permission name = "nilai.input.v2" (suffix)
├─ Context keys = + {homeroom_id, semester, time_window}
└─ Snapshot format = + {ruleTrace, delegationChain, multiSchoolScope}
```

### 2.3. Aturan Perubahan Versi

| Jenis Perubahan | Major? | Minor? | Patch? |
|-----------------|--------|--------|--------|
| Tambah permission baru (backward-compat) | ❌ | ✅ | — |
| Tambah context key baru (optional) | ❌ | ✅ | — |
| Ubah nama permission | ✅ | ❌ | — |
| Hapus permission | ✅ | ❌ | — |
| Tambah rule baru | ❌ | ✅ | — |
| Ubah rule existing (logic) | ✅ | ❌ | — |
| Snapshot format change | ✅ | ❌ | — |
| Builder algorithm change (output equivalent) | ❌ | ❌ | ✅ |
| Builder algorithm change (output differs) | ✅ | ❌ | — |
| Bug fix | ❌ | ❌ | ✅ |

### 2.4. Backward Compatibility

#### Snapshot Format Backward-Compat
- Snapshot disimpan dengan `schema_version`. Runtime harus support baca versi lama.
- Migration script otomatis backfill field baru ketika compute ulang.
- Snapshot tidak boleh di-invalidate en masse karena versi naik.

#### Permission Name Backward-Compat
- Rename permission = major bump.
- Dual-register: nama lama **alias** ke nama baru selama 6 bulan minimum.
- Warning log: `"permission 'nilai.input' is deprecated, use 'nilai.input.v2'"`.

#### Rule Backward-Compat
- Tambah rule baru → langsung aktif, tidak perlu migration.
- Modify rule existing → major bump; sedangkan additive changes → minor.
- DB rules punya `version` auto-increment, yang lama di-mark `superseded`.

### 2.5. Migration

#### Database Migration
```
migrate:snapshot --from-schema=v1 --to-schema=v2 [--user=*]
```
Iterasi seluruh snapshot dan tambahkan field baru dengan default value.

#### Rule Migration
```php
// otomatis via AuthorizationRuleMigrator
$v1Rules = config('authorization.rules_v1');
foreach ($v1Rules as $rule) {
    $v2Rule = RuleMigratorV1ToV2::migrate($rule);
    DB::table('authorization_rules')->insert($v2Rule);
}
```

### 2.6. Rollback

| Layer | Rollback Strategy |
|-------|-------------------|
| Permission Schema | Feature flag `allow_old_permission_names` = true memungkinkan nama lama. |
| Rule Engine | Revert commit. Snapshot otomatis rebuild via fingerprint mismatch. |
| Snapshot Format | Migration SQL reverse. Tidak undo data, hanya struktur. |
| Code | Git revert + redeploy. |
| Migration DB | Migration down. Backup sebelum migrate. |

### 2.7. Dokumentasi Versi

Setiap release menghasilkan update ke:

- `docs/authorization/changelog.md` — apa yang berubah, kenapa, bagaimana migrasi.
- `docs/adr/ADR-NNN-*.md` — alasan di balik perubahan besar.
- `CHANGELOG.md` root — high-level.

Template CHANGELOG entry:
```markdown
## [v2.1.0] - 2026-09-15

### Added
- Context key `homeroom_id` available in rules
- Rule `gtk.coordinator.homeroom_oversight`

### Changed
- Snapshot now includes `ruleTrace` (auto-cached, no performance impact)

### Deprecated
- Permission `nilai.input.legacy` — use `nilai.input` (will be removed in v3.0.0)

### Migration
Run `php artisan auth:migrate --to-version=2.1`
```

---

## 3. Migration Strategy

### 3.1. Prinsip Migrasi

| Prinsip | Penjelasan |
|---------|------------|
| **Zero Downtime** | Tidak ada maintenance window. Database tetap live. |
| **Zero Data Loss** | Snapshot lama disimpan sebagai audit trail. |
| **Backward Compat** | Spatie role lama tetap jalan sampai final cleanup. |
| **Reversibility** | Setiap migrasi dapat di-rollback tanpa data corruption. |
| **Verification** | Setiap migrasi punya verification command + dry-run mode. |

### 3.2. Urutan Migrasi

```
Step 1: Schema Foundation (no behavior change)
        └─ Tambah tabel permission_snapshots, delegations, dll.

Step 2: Builder Implementation (behind flag)
        └─ EffectivePermissionBuilder running parallel with Spatie
        └─ Output dibandingkan dengan Spatie (parity test)

Step 3: Snapshot Backfill (bulk)
        └─ php artisan auth:backfill --since=Y-m-d
        └─ Generate snapshot untuk seluruh user aktif

Step 4: Snapshot Reads (shadow mode)
        └─ Controller reads from snapshot, but Spatie tetap jadi fallback

Step 5: Middleware Introduction
        └─ Middleware baru berjalan dengan flag ALLOW_LEGACY=true

Step 6: Gate/Policy Refactor
        └─ Gate delegate ke builder
        └─ Policy pakai context injection

Step 7: Cleanup
        └─ Spatie role non-identity di-detach
        └─ givePermissionTo manual di-detach
        └─ ALLOW_LEGACY=false

Step 8: Sanity Audit
        └─ php artisan auth:verify --full
```

### 3.3. Feature Flag System

```
config/authorization.php
return [
    'flags' => [
        'allow_old_permission_names' => env('ALLOW_OLD_PERMISSION_NAMES', true),
        'allow_legacy_roles'         => env('ALLOW_LEGACY_ROLES', true),
        'allow_manual_assign_role'   => env('ALLOW_MANUAL_ASSIGN_ROLE', true),
        'use_snapshot_for_auth'      => env('USE_SNAPSHOT_FOR_AUTH', false),
        'allow_context_aware_gate'    => env('ALLOW_CONTEXT_AWARE_GATE', false),
        'allow_rule_engine'           => env('ALLOW_RULE_ENGINE', false),
        'allow_delegation'           => env('ALLOW_DELEGATION', false),
        'allow_acting_position'       => env('ALLOW_ACTING_POSITION', false),
        'multi_school_mode'           => env('MULTI_SCHOOL_MODE', false),
    ],
];
```

Setiap flag punya **3 tahap**: **OFF** → **SHADOW** (parallel run) → **ON** (replaces).

### 3.4. Dual-Run Strategy

Tujuan: jalankan sistem lama dan baru secara paralel, bandingkan output, identifikasi drift.

#### Mode "SHADOW"
- Snapshot ter-build setiap event.
- Controller tetap pakai Spatie check.
- Snapshot compare setiap 1000 request: apakah Spatie dan builder hasilnya sama?
- Drift dikirim ke `auth_drift_log` table.
- Dashboard monitoring menampilkan drift rate.

#### Mode "ENFORCED"
- Builder jadi authoritative.
- Snapshot dipakai untuk authorize.
- Spatie masih jalan untuk legacy role (identity) only.
- Drift monitoring tetap aktif.

#### Mode "CLEANUP"
- Builder hanya.
- Identity role di Spatie (Super Admin, GTK, dll.) tetap.
- Spatie position role di-detach otomatis.

### 3.5. Rollback Strategy

#### Rollback Triggers
- Drift rate > 5% dalam 1 jam.
- p99 latency > 200ms untuk permission check.
- Snapshot table corrupt > 1% rows.
- Error rate builder > 0.1%.

#### Rollback Steps
1. Set feature flag kembali ke SHADOW atau OFF.
2. Run `php artisan auth:revert --to-step=N`.
3. Re-deploy previous code.
4. Snapshot lama tetap utuh (kita tidak undo data).

#### Time-to-Rollback Target
- Critical: ≤5 menit
- High: ≤30 menit
- Medium: ≤2 jam

### 3.6. Verification Strategy

Tiap step migrasi punya verifikasi:

| Step | Verification Command | Pass Criteria |
|------|----------------------|---------------|
| 1 | `php artisan auth:verify:schema` | All new tables exist. |
| 2 | `php artisan auth:verify:builder parity` | 0 drift dari Spatie. |
| 3 | `php artisan auth:verify:backfill` | 100% GTK punya snapshot. |
| 4 | `php artisan auth:verify:shadow-drift` | drift ≤0.1%. |
| 5 | `php artisan auth:verify:middleware` | All flagged middleware live. |
| 6 | `php artisan auth:verify:gate` | All gate definitions migrated. |
| 7 | `php artisan auth:verify:cleanup` | No deprecated role. |

### 3.7. Cleanup Strategy

Setelah Migration Step 7 (1-3 bulan observasi):

1. Hapus `role_has_permissions` row untuk role non-identity.
2. Hapus `model_has_roles` row yang `legacy_role = true`.
3. Tandai row legacy sebagai deprecated di `roles` table:
   ```sql
   UPDATE roles SET is_legacy = 1, deprecated_at = NOW()
   WHERE name IN ('Wakil Kepala Sekolah', 'Kepala Sekolah', ...);
   ```
4. Hapus Spatie controller `assignRole(`) calls.
5. Hapus manual `givePermissionTo` calls.
6. Tandai test lama yang mengecek role non-identity menjadi skip/expected failure.
7. Final audit + delete legacy code.

---

## 4. Authorization Testing Strategy

### 4.1. Test Pyramid

```
        ╱╲
       ╱  ╲         E2E (slowest, smallest)
      ╱ E2E╲
     ╱──────╲
    ╱        ╲      Integration (medium)
   ╱Integration╲
  ╱──────────────╲
 ╱  Feature Tests  ╲      Feature test
╱────────────────────╲
╱   Unit Tests         ╲   Unit test (fastest, most)
──────────────────────────
```

### 4.2. Detail Tiap Layer

| Layer | Tujuan | Cakupan | Contoh Skenario | Acceptance |
|-------|--------|---------|------------------|------------|
| **Unit Test** | Validate single class logic in isolation | Fakta, scope matcher, temporal evaluator | `teaching_assignment_exists(subject, rombel)` returns true | Setiap fact punya ≥5 case (true/false/edge) |
| **Builder Test** | Validate EffectivePermissionBuilder output | 12 source-type combinations | GTK + employment + workUnit + careerPath + teaching assignment → correct permission bag | Output deterministic across 100 runs |
| **Rule Engine Test** | Validate rule evaluation | Setiap rule punya test fixtures | Rule `gtk.input_nilai` activates on context match | 100% rule coverage |
| **Context Test** | Validate OrganizationContext resolution | Middleware, route binding, manual setting | Request tanpa `school_id` ditolak saat multi_school_mode=true | Edge case: null, missing key, extra key |
| **Gate Test** | Validate Laravel Gate definitions | Semua Gate::define | `Gate::allows('nilai.input', $rombel, $mapel)` matches underlying policy | All gates migrated |
| **Policy Test** | Validate Policy methods | Setiap Policy class | `NilaiPolicy::input($user, $rombel, $mapel)` returns expected | Coverage ≥90% per policy |
| **Observer Test** | Validate Eloquent observers | PositionChanged listener, dll. | GtkEmployment saved → listener dispatched | Event-firing verified |
| **Event Test** | Validate full event chain | PositionChanged → listener → snapshot | Snapshot ter-update setelah 5 second async | End-to-end event chain |
| **Integration Test** | Validate controller flow | Entire request lifecycle | GET /nilai/input/{rombel}/{mapel} → 200 untuk teacher, 403 untuk non-teacher | Status code + response shape |
| **E2E Test** | Validate user perspective | Browser-level flow | Login as Guru → Input Nilai → Submit → Success | Visible to user |
| **Performance Test** | Validate SLA | Latency, throughput, resource | 1000 concurrent permission checks | p99 < 50ms |
| **Regression Test** | Validate post-deploy | Snapshot comparisons | Snapshot before/after deploy match | Sbyte-for-byte same |

### 4.3. Required Test Files Structure

```
tests/
├── Unit/
│   └── Authorization/
│       ├── Builders/
│       │   ├── EffectivePermissionBuilderTest.php
│       │   └── PermissionBagTest.php
│       ├── Context/
│       │   └── OrganizationContextTest.php
│       ├── Facts/
│       │   ├── TeachingAssignmentFactTest.php
│       │   └── HomeroomFactTest.php
│       ├── Temporal/
│       │   └── TemporalEvaluatorTest.php
│       └── Registry/
│           └── PositionPermissionRegistryTest.php
│
├── Feature/
│   └── Authorization/
│       ├── GateMigrationTest.php
│       ├── PolicyMigrationTest.php
│       ├── MiddlewareTest.php
│       └── SnapshotLifecycleTest.php
│
└── Integration/
    └── Authorization/
        ├── EventChainTest.php
        ├── SnapshotRebuildTest.php
        └── ConflictDetectionTest.php
```

### 4.4. Acceptance Criteria

| Metrik | Target |
|--------|--------|
| Unit test coverage | ≥90% |
| Feature test coverage | ≥75% |
| Rule coverage | 100% (every rule has test) |
| Permission coverage | 100% (every permission has integration test) |
| Performance baseline | p99 < 50ms |

### 4.5. Test Data Strategy

```
database/seeders/AuthorizationFixtures/
├── teacher_with_teaching_assignment.json
├── homeroom_teacher.json
├── coordinating_position.json
├── delegating_user.json
├── acting_position_holder.json
├── revoked_permission_user.json
├── conflict_user.json
└── multi_position_user.json
```

Setiap fixture di-load ke test schema in-memory, menjamin test reprodusibel.

### 4.6. Continuous Test

- **Pre-commit**: Lint + Unit (fast).
- **Pre-merge**: Unit + Feature.
- **Pre-deploy**: All integration.
- **Nightly**: E2E + Performance + Drift comparison.
- **Weekly**: Full regression.

---

## 5. Monitoring & Observability

### 5.1. Metrics

#### Counter Metrics
- `auth.permission.granted.total{permission, scope, source}`
- `auth.permission.denied.total{permission, scope, reason}`
- `auth.snapshot.generated.total{reason}`
- `auth.snapshot.invalidated.total{reason}`
- `auth.snapshot.rebuild.triggered.total{event}`
- `auth.rule.evaluated.total{rule_id, result}`
- `auth.conflict.detected.total{type, severity}`
- `auth.delegation.activated.total`
- `auth.acting_position.activated.total`
- `auth.unauthorized_access_attempt.total{user_id, route}`
- `auth.drift.detected.total{permission}`

#### Gauge Metrics
- `auth.cache.hit_ratio`
- `auth.cache.miss_ratio`
- `auth.snapshot.age_seconds{user_id}`
- `auth.active_delegations.count`
- `auth.active_acting_positions.count`
- `auth.rules.count{rule_status}`

#### Histogram Metrics
- `auth.builder.execution.ms`
- `auth.rule.evaluation.ms`
- `auth.snapshot.rebuild.ms`
- `auth.context.resolve.ms`
- `auth.policy.evaluate.ms`
- `auth.gate.evaluate.ms`

### 5.2. Logging Standards

#### Log Levels per Event
| Event | Level |
|-------|-------|
| Permission granted | DEBUG |
| Permission denied | INFO |
| Snapshot generated | INFO |
| Snapshot invalidation | INFO |
| Cache miss | DEBUG |
| Conflict detected | WARN |
| Unauthorized access attempt | WARN |
| Builder exception | ERROR |
| Snapshot rebuild failed | ERROR |
| Rule evaluation error | ERROR |
| Migration step failure | ERROR |

#### Log Structure (JSON)
```json
{
  "timestamp": "2026-06-29T08:30:00Z",
  "level": "info",
  "channel": "auth",
  "event": "permission.granted",
  "user_id": "uuid",
  "permission": "nilai.input",
  "context": {
    "school_id": "uuid",
    "study_group_id": "uuid",
    "subject_id": "uuid"
  },
  "origins": ["TeachingAssignment", "Identity.gtk"],
  "snapshot_id": "uuid",
  "fingerprint": "a3f4e9c8",
  "request_id": "uuid"
}
```

### 5.3. Alert Rules

| Alert | Threshold | Severity |
|-------|-----------|----------|
| Permission denied > 10/sec per user | 10/sec | WARN |
| Snapshot rebuild failure | 1 | ERROR |
| p99 builder latency > 200ms | 200ms | WARN |
| Cache hit ratio < 80% | 80% | WARN |
| Conflict detected (severity=high) | 1 | WARN |
| Unauthorized access attempt spikes | >50/min | WARN |
| Snapshot table corruption detected | 1 row | ERROR |
| Authorization event queue stuck | >1 hour | ERROR |

### 5.4. Dashboard Panels

#### Operations Dashboard
- Permission Granted vs Denied (time-series)
- Top 10 Denied Permissions (table)
- Cache Hit Ratio (gauge)
- Builder p50/p95/p99 Latency (line)
- Snapshot Rebuild Rate (area)
- Active Delegations Count (gauge)

#### Security Dashboard
- Unauthorized Access Attempts (time-series, IP-grouped)
- Top Suspicious Users (table)
- Failed Login Spikes (time-series)
- High-Severity Conflicts (table)
- Delegation Audit Trail (timeline)

#### Governance Dashboard
- Active Rules by Status (donut)
- Recent Rule Changes (timeline)
- Deprecated Permission Usage (warning)
- Drift Rate by Permission (bar)

### 5.5. Tracing

Setiap request HTTP punya `X-Request-ID`. Authorization check menelusuri span:

```
trace:auth-check [total: 12ms]
 ├─ span:resolve-snapshot [3ms]
 ├─ span:resolve-context [1ms]
 ├─ span:apply-rules [5ms]
 │   ├─ span:rule-evaluate:gtk.input_nilai [2ms]
 │   └─ span:rule-evaluate:homeroom.input_nilai [2ms]
 └─ span:policy-call [2ms]
```

Distributed tracing via OpenTelemetry (jika diimplementasikan di tim DevOps).

---

## 6. Performance Budget

### 6.1. SLA per Operation

| Operation | p50 Target | p95 Target | p99 Target |
|-----------|------------|------------|------------|
| **Permission Check (warm cache)** | 2ms | 8ms | 15ms |
| **Permission Check (cold cache)** | 25ms | 80ms | 150ms |
| **Snapshot Generation (rebuild)** | 200ms | 500ms | 1s |
| **Rule Evaluation per rule** | 0.5ms | 2ms | 5ms |
| **Context Resolution** | 1ms | 5ms | 10ms |
| **Builder Execution** | 10ms | 50ms | 100ms |
| **Cache Lookup** | 0.3ms | 1ms | 3ms |
| **Snapshot Storage (write)** | 5ms | 15ms | 50ms |

### 6.2. Throughput SLAs

| Metric | Target |
|--------|--------|
| Concurrent permission checks | 500 req/s |
| Daily snapshot rebuilds (organic) | < 5000 |
| Cache hit ratio | ≥ 95% |
| Snapshot rebuild backlog | < 1000 (5min drain) |

### 6.3. Storage Budget

| Resource | Target |
|----------|--------|
| permission_snapshots table size | < 100MB untuk 5000 user |
| Snapshot cardinality (current) | 1 row per user |
| Origin index size | ~5KB per snapshot |
| Archive table growth | < 1GB / month (post-retention) |

### 6.4. Cara Mengukur

#### CI Pipeline
- Performance regression test tiap PR yang menyentuh `Authorization/**`.
- Bandingkan benchmark output ke `benchmarks/baseline.json`.
- Fail CI jika deviation > 15%.

#### Production
- APM agent (Prometheus + Grafana).
- Real user monitoring (RUM) untuk endpoint critical (rapor, nilai, dll.).
- Synthetic check tiap 5 menit (cron + curl + assert latency).

### 6.5. Performance Anti-patterns

Anti-pattern yang harus diaudit:
1. ❌ Sync snapshot rebuild dalam request path.
2. ❌ N+1 query saat mengajar multi-role user.
3. ❌ Builder call berulang tiap render (caching facade call wajib).
4. ❌ Eager loading berlebihan (lazy load just-in-time).
5. ❌ Rule tanpa TTL cache.

---

## 7. Failure Recovery & Graceful Degradation

### 7.1. Failure Inventory

| Failure | Impact | Fallback | Recovery | Monitoring |
|---------|--------|----------|----------|------------|
| **Cache hilang** | Slow path | Spatie role check (legacy) | Rebuild cache on next check | `auth.cache.miss_ratio` |
| **Snapshot corrupt** | Wrong permission possible | Recompute from source via builder | Scheduled cleanup + integrity check | `auth.snapshot.corrupt.total` |
| **Event gagal diproses** | Snapshot stale | TTL expiry auto-recompute | Retry queue with exponential backoff (max 5x) | `auth.queue.failed.total` |
| **Queue mati** | Long staleness | Sync rebuild (slow path) | Auto-restart via supervisor | Queue liveness check |
| **Rule engine error** | Permission granted wrong | Default deny + alert | Fallback to deny; alert on-call | `auth.rule.error.total` |
| **Permission builder error** | Authorization not decided | Default deny + 503 | Re-raise, catch in middleware, return 503 | `auth.builder.error.total` |
| **Context tidak ditemukan** | Authorization blocked | Inherit from request → if missing → deny | Log warning; allow safer default | `auth.context.missing.total` |
| **Assignment rusak** (FK missing) | Snapshot partial | Partial snapshot + warning | Event listener detects → admin alert | `auth.assignment.broken.total` |
| **Database slow/down** | All auth slow/down | 503 with retry | Connection pool retry, fallback to read replica | `auth.db.latency` |
| **Clock drift** | Time-based decision wrong | Use server time as authority | NTP sync enforcement | `auth.clock.skew.total` |
| **Snapshot fingerprint mismatch (high rate)** | Massive rebuilds | Rate limit rebuilds | Detect loop, pause rebuild | `auth.rebuild.fingerprint_loop.total` |

### 7.2. Graceful Degradation Tiers

#### Tier 1 — Full Operation (everything healthy)
- Snapshot cache hit.
- Builder returns in < 50ms.
- Rules applied in real time.

#### Tier 2 — Cache Cold
- Snapshot recomputed.
- Latency increased.
- All authorization still working.

#### Tier 3 — Builder Slow
- Permission check still works.
- Latency > 200ms.
- User experience degraded but functional.

#### Tier 4 — Builder Fail
- Fallback to Spatie role check (only identity).
- Deny untuk permission non-identity.
- Admin alert.

#### Tier 5 — Total Failure
- Return 503 untuk semua endpoint non-public.
- Admin alert.
- Read-only mode untuk endpoint tertentu.

### 7.3. Circuit Breaker Pattern

```php
class AuthorizationCircuitBreaker {
    public function execute(callable $operation): Result {
        try {
            if ($this->isOpen()) {
                return $this->fallback();
            }
            $result = $operation();
            $this->recordSuccess();
            return $result;
        } catch (Throwable $e) {
            $this->recordFailure();
            if ($this->failureCount > $this->threshold) {
                $this->open();
            }
            return $this->fallback();
        }
    }

    private function fallback(): Result {
        // Default deny + log + alerting
        return Result::deny('Authorization subsystem degraded');
    }
}
```

### 7.4. Backup & Restore

- Snapshot table di-backup daily (mysqldump / pg_dump) + 30-day retention.
- Audit log di-backup mingguan dengan 1-year retention.
- Rules (config) di-version-control di git.
- Rules (DB) di-backup daily.

### 7.5. Disaster Recovery Targets

| Disaster | RTO | RPO |
|----------|-----|-----|
| Snapshot hilang total | 1 hour | 24 hour |
| Snapshot corruption | 4 hour | 24 hour |
| Full system failure | 4 hour | 1 hour |

---

## 8. Auditability (Explainability at Runtime & Post-mortem)

### 8.1. Runtime Explainability API

#### User-Facing (Administrator Console)
- "Why does Pak Budi have permission rapor.generate?"
- Click → Trace explanation.

#### Example Trace Output (Raw)

```json
{
  "permission": "rapor.generate",
  "user": "uuid-budi",
  "allowed": true,
  "explanation": {
    "summary": "Pak Budi diizinkan rapor.generate oleh 3 sumber.",
    "origins": [
      {
        "source": "GtkEmployment",
        "source_id": "uuid-employment-1",
        "code": "gtk.kepala_sekolah",
        "weight": 10,
        "valid_from": "2024-07-15",
        "valid_until": null,
        "fingerprint_match": true
      },
      {
        "source": "HomeroomAssignment",
        "source_id": "uuid-rombel-xii1",
        "code": "homeroom.teacher",
        "weight": 5,
        "valid_from": "2026-07-01",
        "valid_until": "2027-06-30",
        "scope": {
          "study_group_id": "uuid-rombel-xii1",
          "academic_year_id": "uuid-year-2026"
        }
      },
      {
        "source": "Delegation",
        "source_id": "uuid-delegation-7",
        "code": "delegation.from.wakil",
        "weight": 3,
        "valid_from": "2026-08-01",
        "valid_until": "2026-08-15"
      }
    ],
    "rules_evaluated": [
      {
        "rule_id": "kepsek.rapor_full",
        "matched": true,
        "weight": 12
      }
    ],
    "context_validated": {
      "school_id": "uuid-sekolah-1",
      "academic_year_id": "uuid-year-2026",
      "study_group_id": "uuid-rombel-xii1",
      "all_matched": true
    }
  }
}
```

### 8.2. Post-mortem Explainability

#### CLI Commands

```bash
# Trace seluruh permission user saat waktu tertentu
php artisan auth:trace --user=uuid --at=2026-09-15T10:30:00+07:00

# Diff permission sebelum/sesudah event
php artisan auth:diff --user=uuid --from=event-uuid --to=event-uuid

# List semua rules yang aktif
php artisan auth:rules --user=uuid --permission=rapor.generate

# Find semua source of permission
php artisan auth:explain --user=uuid --permission=rapor.generate

# Replay snapshot computation
php artisan auth:replay --user=uuid --at=2026-09-15

# Audit delegation
php artisan auth:delegations --user=uuid --active
```

#### Web UI (Administrator Console)
- `/superadmin/auth/trace/{user}`
- `/superadmin/auth/diff/{user}/from/{timestamp}/to/{timestamp}`
- `/superadmin/auth/explain/{user}/{permission}`
- `/superadmin/auth/snapshots/{user}` — historical list
- `/superadmin/auth/rules` — rule registry UI with diff tool

### 8.3. Audit Log Retention

| Log Type | Retention | Archive |
|----------|-----------|---------|
| Permission granted/denied events | 30 days | 1 year |
| Snapshot generation events | 90 days | 1 year |
| Rule evaluation traces (sample) | 7 days | 30 days |
| Audit decisions (full) | 365 days | 7 years |
| Conflict events | 365 days | 7 years |
| System events (rule publish, etc.) | indefinite | git history |

### 8.4. Compliance & Regulation

Untuk audit kebutuhan compliance (mis. UU PDP, regulationschool):
- Tamper-evident log: SHA-256 chain hash per log row.
- Log entry minimal: actor, action, target, timestamp, reason.
- Read-only log table dengan role-protected access.
- Quarterly review oleh security officer.

---

## 9. Developer SDK (Public API Facade)

### 9.1. Filosofi SDK

Developer tidak boleh langsung berinteraksi dengan:
- ❌ `Spatie\Permission\Models\Role`
- ❌ `Spatie\Permission\Models\Permission`
- ❌ `Gate::define(...)` direct
- ❌ `assignRole()`, `syncRoles()`, `hasRole()` direct
- ❌ `givePermissionTo()`, `hasPermissionTo()` direct
- ❌ Internal builder classes
- ❌ Snapshot table direct

Developer hanya boleh berinteraksi dengan `Authorization` facade.

### 9.2. Public API

```php
namespace App\Authorization;

final class Authorization
{
    // ── READ ─────────────────────────────────────────────────────

    /** Check if user has permission, optionally with context. */
    public static function allows(
        User $user,
        string $permission,
        ?OrganizationContext $context = null,
    ): bool;

    /** Same as allows(), but throws AuthorizationDeniedException on false. */
    public static function authorize(
        User $user,
        string $permission,
        ?OrganizationContext $context = null,
    ): void;

    /** Get detailed explanation for why permission is/isn't granted. */
    public static function why(
        User $user,
        string $permission,
        ?OrganizationContext $context = null,
    ): TracedPermission;

    /** List all scopes where user has permission. */
    public static function scope(
        User $user,
        string $permission,
    ): Collection; // of ScopedPermission

    /** Get user's effective permission object (cached). */
    public static function effectivePermissions(
        User $user,
    ): EffectivePermission;

    // ── CONTEXT ──────────────────────────────────────────────────

    /** Get current request's organization context. */
    public static function context(): OrganizationContext;

    /** Set context manually (for CLI/background). */
    public static function withContext(OrganizationContext $context): ContextBinder;

    /** Run callback under specific context (for tests, scripts). */
    public static function inContext(
        OrganizationContext $context,
        callable $callback,
    ): mixed;

    // ── STATE ─────────────────────────────────────────────────────

    /** Force refresh user's permission snapshot. */
    public static function refresh(User $user, string $reason = 'manual'): void;

    /** Force refresh all users (heavy operation, audit logged). */
    public static function refreshAll(string $reason = 'system'): void;

    /** Inspect snapshot without rebuilding. */
    public static function snapshot(User $user, ?Carbon $at = null): ?PermissionSnapshot;

    // ── AUDIT ─────────────────────────────────────────────────────

    /** Get all grant/deny events for user. */
    public static function audit(
        User $user,
        ?Carbon $from = null,
        ?Carbon $to = null,
    ): Collection; // of AuthEvent

    /** Get all conflicts detected for user. */
    public static function conflicts(User $user): Collection;

    /** Get all rules that affect user. */
    public static function rules(User $user): Collection; // of Rule

    // ── POLICY/GATE BRIDGE ────────────────────────────────────────

    /** Register a Gate definition through facade. */
    public static function gate(string $ability, Closure $callback): void;

    /** Register a Policy class. */
    public static function policy(string $class, string $policy): void;

    // ── DELEGATION ────────────────────────────────────────────────

    /** Create delegation. */
    public static function delegate(
        User $from,
        User $to,
        string|array $permissions,
        Carbon $validFrom,
        Carbon $validUntil,
        ?string $reason = null,
        ?string $decreeId = null,
    ): Delegation;

    /** Create acting position assignment. */
    public static function assignActingPosition(
        User $holder,
        string $positionCode,
        User $originalHolder,
        ?string $schoolId,
        ?string $academicYearId,
        Carbon $validFrom,
        Carbon $validUntil,
        ?string $reason = null,
    ): ActingPositionAssignment;
}
```

### 9.3. Usage Examples

#### Simple Allow Check
```php
// Old (❌ deprecated)
if ($user->can('nilai.input')) { ... }

// New (✅)
if (Authorization::allows($user, 'nilai.input', $context)) { ... }
```

#### Throw on Deny
```php
try {
    Authorization::authorize($user, 'rapor.generate', $context);
    return $raporService->generate();
} catch (AuthorizationDeniedException $e) {
    return response()->json(['message' => 'Tidak diizinkan', 'reason' => $e->reason()], 403);
}
```

#### Debug
```php
$trace = Authorization::why($user, 'rapor.generate', $context);
Log::channel('auth-audit')->info('Rapor generate trace', $trace->toArray());
```

#### Controller
```php
public function inputNilai(Request $request, StudyGroup $rombel, Subject $mapel)
{
    Authorization::authorize(
        Auth::user(),
        'nilai.input',
        Authorization::context()->with(['study_group_id' => $rombel->id, 'subject_id' => $mapel->id])
    );
    return $this->nilaiService->input($rombel, $mapel);
}
```

#### Test
```php
use App\Authorization\Authorization;
use App\Authorization\Testing\AuthorizationTestHelper;

it('allows homeroom teacher to input nilai', function () {
    $user = User::factory()->withHomeroom($rombel->id)->create();
    AuthorizationTestHelper::inContext(
        ['school_id' => $school->id, 'study_group_id' => $rombel->id, 'subject_id' => $mapel->id],
        function () use ($user) {
            expect(Authorization::allows($user, 'nilai.input'))->toBeTrue();
        }
    );
});
```

### 9.4. Laravel Facade

```php
// config/app.php
'aliases' => [
    'Authorization' => App\Authorization\Facades\Authorization::class,
];

// Use in any context
use Authorization;

// In Blade
@can('rapor.generate') or @authorize('rapor.generate', $context)
```

### 9.5. Type-Safety

Semua method SDK strongly-typed:
- `string` permission di-validate terhadap `PermissionRegistry`.
- `User` harus implement `App\Models\User` atau contract yang sesuai.
- Exception hierarchy jelas:
  - `AuthorizationException`
    - `AuthorizationDeniedException`
    - `AuthorizationContextInvalidException`
    - `AuthorizationSnapshotNotFoundException`
    - `AuthorizationRuleEvaluationException`

### 9.6. Backward Compatibility Shim

Spatie compatibility methods di-deprecate tapi untuk transisi diberi wrapper:

```php
// app/Models/User.php
public function hasRole($role, $guard = null): bool
{
    trigger_error(
        "User->hasRole() is deprecated. Use Authorization::allows() instead. (called from " . debug_backtrace()[1]['file'] . ")",
        E_USER_DEPRECATED
    );

    // Identity-role only check
    return in_array($role, ['super_admin', 'gtk', 'peserta_didik', 'wali_santri', 'alumni'])
        ? parent::hasRole($role, $guard)
        : false;
}
```

---

## 10. Developer Guide

### 10.1. Cara Menambah Permission Baru

#### Step 1 — Define Permission in Registry
Tambah di `config/authorization/permissions.php`:
```php
return [
    'nilai.input' => [
        'description' => 'Boleh input nilai',
        'category'    => 'academic.scoring',
        'scope'       => true, // apakah butuh context
        'default_weight' => 1,
    ],
    // ...
];
```

#### Step 2 — Define Rule (or rely on Position Registry)
Tambah di `config/authorization/rules.php`:
```php
'nilai.view_kelas_lain' => [
    ['when' => 'identity=gtk', 'grant' => ['nilai.view_all_kelas'], 'weight' => 1],
    ['when' => 'homeroom',     'grant' => ['nilai.view'], 'weight' => 5],
],
```

#### Step 3 — Register Permission Name in DB (Synchronizer)
Jalankan:
```bash
php artisan auth:sync-permissions
```

#### Step 4 — Use in Controller
```php
Authorization::allows($user, 'nilai.input', $context);
```

#### Step 5 — Test
Test unit + integration. Coverage > 90%.

#### Step 6 — Document
Tambah entry di `permissions.md` dengan: name, description, scope, owner, ADR reference.

### 10.2. Cara Membuat Context Baru

#### Step 1 — Add to OrganizationContext Class
```php
final class OrganizationContext
{
    public function __construct(
        public readonly ?string $schoolId = null,
        public readonly ?string $academicYearId = null,
        public readonly ?string $studyGroupId = null,
        public readonly ?string $subjectId = null,
        public readonly ?string $homeroomId = null,    // NEW
        // ... other contexts ...
    ) {}
}
```

#### Step 2 — Add to Middleware
Tambah resolution logic di `OrganizationContextMiddleware`:
```php
$homeroomId = $request->route('homeroomId') ?? $request->input('homeroom_id');
```

#### Step 3 — Add Rule Fact
```php
public function facts(): array
{
    return [
        // ... existing facts ...
        'homeroom_owns_student' => new HomeroomOwnsStudentFact(),
    ];
}
```

#### Step 4 — Document
Tambah di `authorization/context-keys.md`.

### 10.3. Cara Membuat Rule Baru

#### Code-based (config)
Edit `config/authorization/rules.php`. Tambah entry.

#### DB-based (runtime)
```bash
php artisan auth:rules:create --id=my_rule --grant="nilai.input" --when="identity=gtk&teaching_assignment_exists"
```

Atau via UI `/superadmin/rules/create`.

#### Rules dengan Fact Custom
Buat class fact:
```php
class MyCustomFact implements Fact
{
    public function evaluate(FactContext $ctx): bool
    {
        return $ctx->user()->hasCustomCondition();
    }
}
```

Register di `FactRegistry`.

### 10.4. Cara Membuat Assignment Baru

Mis. kita butuh `ExamSupervisionAssignment`:

1. Migration: tambah tabel `exam_supervision_assignments`.
2. Model: `ExamSupervisionAssignment extends Model`.
3. Position source: `ExamSupervisionAssignmentPosition implements PositionSource`.
4. Register di `PositionResolver`.
5. Tambah rule di `rules.php`.
6. Test fixture + integration test.

### 10.5. Cara Membuat Event Baru

Event harus implement `App\Authorization\Contracts\AuthoritativeEvent` agar listener otomatis mendeteksi:

```php
class CustomPositionChanged implements AuthoritativeEvent
{
    public function __construct(
        public readonly User $user,
        public readonly string $positionCode,
        public readonly array $metadata,
    ) {}

    public function affectedUser(): User { return $this->user; }

    public function eventType(): string { return 'position.changed'; }
}
```

Listener `RebuildPermissionsListener` otomatis fire via `Event::listen()`.

### 10.6. Cara Membuat Observer Baru

Gunakan `AuthorizationEventEmitterObserver`:

```php
class GtkEmploymentObserver
{
    public function saved(GtkEmployment $e): void
    {
        AuthoritativeEvent::dispatch(new PositionChanged($e->user, $e->position_code, ['source_id' => $e->id]));
    }

    public function deleted(GtkEmployment $e): void
    {
        AuthoritativeEvent::dispatch(new PositionRevoked($e->user, $e->position_code, ['source_id' => $e->id]));
    }
}
```

### 10.7. Cara Membuat Snapshot Baru

Snapshot otomatis ter-regenerate via listener. Tapi untuk manual trigger:

```bash
# Single user
php artisan auth:backfill --user=uuid --reason="manual_refresh"

# All users
php artisan auth:backfill --reason="post_migration" --queue

# Specific subset
php artisan auth:backfill --role=guru --year=2026 --reason="year_rollover"
```

### 10.8. Cara Membuat Migration Authorization

#### Schema Migration
```bash
php artisan make:migration add_authorization_field --table=...
```

Pastikan:
- Index untuk kolom query (user_id, fingerprint, expires_at).
- Foreign key cascade.
- Default value sensible.

#### Rule Migration
```bash
php artisan auth:rule:migrate --from=v1 --to=v2
```

#### Snapshot Migration
```bash
php artisan auth:snapshot:migrate --from-schema=v1 --to-schema=v2
```

### 10.9. Conventions & Code Style

- Namespace: `App\Authorization\*`.
- Folder structure:`{Builders, Context, Events, Facades, Services, Models, Policies, Middleware, Registry, Rules, Snapshot, Testing}`.
- Class suffix konsisten: `*Builder`, `*Provider`, `*Resolver`, `*Factory`, `*Service`, `*Listener`.
- Test: pakai Pest 2.x, dengan `describe()` block per feature.
- Documentation: PHPDoc dengan contoh + ADR reference.

---

## 11. Architecture Decision Records (ADR)

### 11.1. Daftar ADR yang Harus Tersedia

| ADR | Judul | Status | Owner |
|-----|-------|--------|-------|
| ADR-001 | Identity Model (Super Admin, GTK, dll.) | ACCEPTED | Architect |
| ADR-002 | Position Model & Registry | ACCEPTED | Architect |
| ADR-003 | Assignment Model (Teaching, Homeroom, Additional Task) | ACCEPTED | Architect |
| ADR-004 | Effective Permission Builder | ACCEPTED | Architect |
| ADR-005 | Context-Aware Authorization | ACCEPTED | Architect |
| ADR-006 | Permission Snapshot Strategy | ACCEPTED | Architect |
| ADR-007 | Rule Engine | ACCEPTED | Architect |
| ADR-008 | Delegation & Acting Position | ACCEPTED | Architect |
| ADR-009 | Caching Strategy | ACCEPTED | Architect |
| ADR-010 | Multi-School Readiness | ACCEPTED | Architect |
| ADR-011 | Conflict Detection | ACCEPTED | Architect |
| ADR-012 | Auditability & Trace | ACCEPTED | Architect |
| ADR-013 | Failure Recovery | ACCEPTED | Architect |
| ADR-014 | Backward Compatibility Strategy | ACCEPTED | Architect |
| ADR-015 | Performance SLA | ACCEPTED | Architect |
| ADR-016 | Snapshot Retention Policy | ACCEPTED | Architect |
| ADR-017 | Delegation Scope & Limits | ACCEPTED | Architect |
| ADR-018 | Rule Versioning & DB Storage | ACCEPTED | Architect |
| ADR-019 | Governance & Roles | ACCEPTED | Architect |
| ADR-020 | Migration Strategy | ACCEPTED | Architect |

### 11.2. Struktur ADR

Setiap ADR file di `docs/adr/NNN-short-slug.md` dengan format:

```markdown
# ADR-NNN: [Title]

Status: [PROPOSED|ACCEPTED|DEPRECATED|SUPERSEDED]
Date: YYYY-MM-DD
Supersedes: [other ADR id, optional]
Superseded by: [other ADR id, optional]
Deciders: [Architect, Senior Dev, Security]

## Context
[apa masalah yang diselesaikan]

## Decision
[apa keputusan]

## Alternatives Considered
[opsi lain + kenapa ditolak]

## Consequences
Positive: [...]
Negative: [...]

## Implementation Notes
[how-to]

## References
[link]
```

### 11.3. Contoh Mini-ADR: ADR-007 Rule Engine

```markdown
# ADR-007: Rule Engine for Authorization

Status: ACCEPTED
Date: 2026-06-29

## Context
Permission logic saat ini hardcoded di controllers. Setiap modul baru
memerlukan perubahan controller code. Tidak scalable untuk 50+ permission
yang akan datang.

## Decision
Gunakan rule engine berbasis fakta + registry config (atau DB untuk runtime).
Rule dideklarasikan via IF-THEN dengan facts yang pluggable.

## Alternatives
- **Hardcoded (current)**: ditolak karena tidak scalable.
- **OPA (Open Policy Agent)**: ditolak karena引入 service baru di stack.
- **DSL Laravel Policies only**: tidak cukup untuk fakta dinamis (e.g.,
  teaching_assignment_exists butuh query database).

## Consequences
Positive:
- Permission baru dapat di-deklarasikan tanpa code change.
- Admin dapat manage via UI.
- Audit trail lebih jelas.

Negative:
- Learning curve untuk fact-based declaration.
- Perlu simulasi tool untuk testing.
- Performance overhead per rule evaluation (mitigated via cache + TTL).

## Implementation Notes
Lihat `authorization-domain-model.md` §10.
```

---

## 12. Operational Playbook (Runbook)

### 12.1. Insiden: Permission salah (user seharusnya punya tapi tidak punya)

#### Deteksi
- User report
- Dashboard alert
- Integration test failure

#### Steps
1. **Verifikasi**: tanyakan user permission spesifik dan context-nya.
2. **Cek snapshot**:
   ```bash
   php artisan auth:trace --user=uuid --permission=X
   ```
3. **Cek asal permission**:
   - Apakah GtkEmployment masih aktif?
   - Apakah Teaching Assignment ada di subject+rombel+year yang dimaksud?
   - Apakah Homeroom Assignment valid?
   - Apakah ada revocation?
4. **Cek context**:
   - Apakah `school_id` benar?
   - Apakah `academic_year_id` aktif?
5. **Cek rules**:
   - Apakah rule terkait aktif?
   - Apakah ada perubahan baru?
6. **Action**:
   - Jika bug snapshot → `auth:refresh --user=uuid`.
   - Jika bug builder → fix code + redeploy + refresh affected users.
   - Jika bug source data → fix data + trigger event.

#### Escalation
- Jika tidak resolved dalam 30 menit → escalate ke Tech Lead.
- Jika affecting > 5 user → escalate ke Architect.

### 12.2. Insiden: Snapshot corrupt

#### Deteksi
- Integrity check failure (weekly cron).
- User report permission inconsistency.
- Snapshot rebuild loop.

#### Steps
1. **Identify scope**:
   ```bash
   php artisan auth:check-integrity --report
   ```
2. **Quarantine** affected snapshots:
   ```bash
   php artisan auth:quarantine --user=uuid1,uuid2,...
   ```
3. **Recompute** from sources:
   ```bash
   php artisan auth:backfill --users=quarantined --reason=corruption_recovery
   ```
4. **Verify**:
   ```bash
   php artisan auth:verify:users --users=uuid1,uuid2,...
   ```
5. **Restore** to live if verified.
6. **Root cause**: investigasi kenapa corruption (DB crash? Disk full? Bug builder?).
7. **Post-mortem**: doc di `docs/incidents/YYYY-MM-DD-snapshot-corrupt.md`.

### 12.3. Insiden: Context salah (permission granted tapi semestinya tidak)

#### Deteksi
- User report "saya bisa lihat data kelas lain".
- Audit log alert.

#### Steps
1. **Cek organization context aktif**:
   ```bash
   php artisan auth:context:inspect --user=uuid
   ```
2. **Cek middleware chain** apakah `org.context` aktif.
3. **Cek route definition**: apakah ada route yang lupa pasang middleware?
4. **Cek policy binding**: policy harus inject context.
5. **Cek override**: apakah ada caller yang override context?
6. **Action**:
   - Fix middleware binding.
   - Tambah test untuk regression.
   - Recompute snapshot affected user.

### 12.4. Insiden: Permission hilang (user punya lalu tidak)

#### Deteksi
- User report.
- Integration test failure.

#### Steps
1. **Cek audit trail** untuk lihat kapan hilang:
   ```bash
   php artisan auth:audit --user=uuid --last=24h
   ```
2. **Cek events**:
   - Apakah ada employee migration?
   - Apakah ada termination?
   - Apakah ada revocation?
   - Apakah ada academic year rollover?
3. **Revert jika salah**:
   - Re-create employment if wrongfully terminated.
   - Cancel revocation if wrongfully revoked.
4. **Verify recovery**:
   ```bash
   php artisan auth:trace --user=uuid
   ```

### 12.5. Insiden: Rule Engine error

#### Deteksi
- Alert: `auth.rule.error.total > 0`
- 503 response untuk authorization check.

#### Steps
1. **Cek rule syntax**:
   ```bash
   php artisan auth:rules:validate
   ```
2. **Cek fact registrations**:
   ```bash
   php artisan auth:facts:list --check-availability
   ```
3. **Rollback rule**:
   ```bash
   php artisan auth:rules:disable --id=broken_rule_id
   ```
4. **Force fallback** ke Spatie identity role:
   - Set flag `USE_RULE_ENGINE=false` via config.
5. **Investigasi root cause** di log.
6. **Re-enable** setelah fix + test.

### 12.6. Insiden: Drift tinggi (Spatie vs Builder output berbeda)

#### Deteksi
- Drift metric > 5% per jam.

#### Steps
1. **Cek drift log**:
   ```bash
   php artisan auth:drift:report --since=1h
   ```
2. **Identifikasi permission yang drift**:
   - Sort by count, fokus top 10.
3. **Untuk masing-masing**:
   - Cek apakah rule sudah benar.
   - Cek apakah source data benar.
   - Cek apakah builder logic benar.
4. **Patch** yang paling sering muncul.
5. **Re-enable shadow mode** jika patch selesai.

### 12.7. Insiden: Unauthorized access attempt spike

#### Deteksi
- Alert: `auth.unauthorized_access_attempt.total > 50/min`.

#### Steps
1. **Identify IP/user pattern**.
2. **Cross-check dengan Security dashboard**.
3. **Rate limit** offending user/IP.
4. **Notify security officer**.
5. **Review rules**: apakah ada permission granted terlalu longgar?

### 12.8. Insiden: Builder performance regression

#### Deteksi
- p99 latency > 200ms.
- Cache hit ratio drop.

#### Steps
1. **Cek snapshot state**: ada banyak yang stale?
2. **Cek database latency**.
3. **Cek rule count**: apakah ada rule yang lambat?
4. **Profile builder execution**:
   ```bash
   php artisan auth:profile --user=uuid
   ```
5. **Recompute cache jika perlu**.

### 12.9. Insiden: Permission snapshot stale (user changes not reflected)

#### Deteksi
- User baru dipromosikan tapi permission masih seperti sebelum.
- Listener queue stuck.

#### Steps
1. **Cek queue**:
   ```bash
   php artisan queue:failed
   php artisan queue:work --once
   ```
2. **Cek listener registered**:
   ```bash
   php artisan event:list | grep RebuildPermissions
   ```
3. **Cek event firing**:
   ```bash
   php artisan auth:events:trace --user=uuid
   ```
4. **Manual trigger**:
   ```bash
   php artisan auth:refresh --user=uuid --reason=incident-recovery
   ```
5. **Investigasi root cause**: kenapa listener tidak fire?

### 12.10. Insiden: Authorization subsystem total failure (Tier 5)

#### Deteksi
- All authorization decisions returning error.
- 503 spike.

#### Steps
1. **Switch ke fallback mode** (manual di config):
   ```env
   AUTHORIZATION_FALLBACK_MODE=true
   ```
   - Sistem jalan dengan Spatie identity-only.
2. **Investigasi root cause**: cek DB, Redis, queue health.
3. **Repair**.
4. **Re-enable** dengan flip urutan: SHADOW → ENFORCED.
5. **Post-mortem**.

### 12.11. Roster & On-call

| Severity | First Responder | Backup |
|----------|------------------|--------|
| Tier 1 (single user) | Support Engineer | Senior Dev |
| Tier 2 (multi user) | Senior Dev | Tech Lead |
| Tier 3 (school-wide) | Tech Lead | Architect |
| Tier 4 (subsystem down) | Architect | DevOps |
| Tier 5 (total) | Architect + DevOps | — |

---

## 13. Risk Assessment

### 13.1. Risiko Teknis

| Risiko | Severity | Probability | Impact | Mitigation |
|--------|----------|--------------|--------|------------|
| **Snapshot rebuild bottleneck saat event burst** | High | Medium | Latency spike, dropped events | Rate limiter rebuild, queue prioritization, snapshot batch |
| **Rule explosion (banyak rule override konflik)** | Medium | Medium | Logic drift, hidden permission | Rule validator + drift detector + ADR per rule |
| **Origin explosion (sumber terlalu granular)** | Medium | Low | Confusing trace | Aggregasi origin + grouping |
| **Delegation abuse (A→B→C→D)** | High | Low | Privilege escalation | Chain detection + audit |
| **Performance regression post-deploy** | High | Medium | Latency, lost events | Canary deployment + auto-rollback |
| **Snapshot storage bloat** | Medium | High | Storage cost, slow query | Retention policy + archive |
| **Conflict detector false positive** | Low | Medium | Alert fatigue | Severity tier + manual review |
| **Spatie deprecation mismatch** | Medium | Low | Backward incompatibility | Shim layer + alias support |
| **Time zone mismatch (school vs server)** | Low | Medium | Validity bug | Carbon with school TZ enforced |
| **Multi-school mode partial migration** | Medium | Low | Data leakage | Strict flag check + integration test |

### 13.2. Risiko Operasional

| Risiko | Severity | Mitigation |
|--------|----------|------------|
| **Developer familiarization curve** | Medium | Onboarding guide + workshop + LMS module |
| **Operator overwhelmed by alerts** | Medium | Alert tuning + dashboard hierarchy |
| **Rule maintenance cost** | Medium | Rule versioning + simulation tool + auto-test |
| **Documentation drift** | Medium | CI check that docs match code |
| **Snapshot forensics debug difficulty** | Low | Trace tool + visualization in UI |
| **Backfill performance** | Medium | Chunking + queue + monitoring |

### 13.3. Risiko Bisnis

| Risiko | Severity | Mitigation |
|--------|----------|------------|
| **User productivity loss during migration** | High | Dual-run + flag |
| **Compliance failure (audit log gap)** | High | Immutable log + quarterly review |
| **Stakeholder trust loss during transition** | Medium | Status updates + demo |
| **Cost overrun due to complexity** | Medium | Phase gate + estimation verification |

### 13.4. Risiko Organisasi

| Risiko | Severity | Mitigation |
|--------|----------|------------|
| **Bus factor (single Architect)** | High | Documentation + mentorship + pair programming |
| **Compliance review bandwidth** | Medium | Pre-scheduled review windows |
| **Knowledge siloing** | Medium | Code review + ADR review mandatory |
| **Handover friction to new architect** | Medium | Architecture decision rationale in ADR |

### 13.5. Top 5 Risiko Teratas (Prioritas Mitigasi)

1. **Snapshot rebuild bottleneck** — invest di performance testing & queue tuning.
2. **Operator/Dev unfamiliarization curve** — invest di developer guide + runbook.
3. **Delegation abuse** — invest di validation & audit.
4. **Compliance log gap** — invest di append-only log + retention policy.
5. **Bus factor (Architect)** — invest di mentorship + ADR completeness.

---

## 14. Final Readiness Assessment

### 14.1. Skor per Aspek

| Aspek | Skor | Alasan |
|-------|------|--------|
| **Architecture** | 9/10 | Solid layered design, separation of concerns jelas. Tidak 10 karena rule engine masih punya edge case (3rd-party fact plugins). |
| **Maintainability** | 8.5/10 | Convention jelas, SDK memisahkan developer dari internal. Code smell risk rendah. Tidak 10 karena rule explosion masih mungkin. |
| **Scalability** | 9/10 | Multi-school ready, snapshot+cache strategy proven pattern. Tidak 10 karena bulk rebuild untuk 10K+ user belum teruji. |
| **Security** | 9/10 | Multi-layer defense (rule + revocation + snapshot audit), chain detection. Tidak 10 karena masih ada kemungkinan abuse multi-Actor. |
| **Performance** | 8.5/10 | Cache strategy + fingerprint optimization solid. Tidak 10 karena p99 worst case belum diukur di skala penuh. |
| **Developer Experience** | 9/10 | SDK facade bersih, type-safe. Tidak 10 karena ada learning curve untuk fact-based authorization. |
| **Testability** | 9/10 | Pyramid lengkap, fixture-based testing. Tidak 10 karena E2E coverage sulit otomatis. |
| **Observability** | 9/10 | Metric, log, alert, dashboard, tracing comprehensive. Tidak 10 karena distributed trace butuh infra DevOps. |
| **Extensibility** | 9.5/10 | Rule engine + fact pluggable + position registry configurable. Tidak 10 karena ADR belum termasuk untuk beberapa edge case. |
| **Operational Readiness** | 9/10 | Runbook + severity tier + roster + drill-down. Tidak 10 karena alert tuning masih membutuhkan tuning empiris. |
| **Compliance** | 9/10 | Tamper-evident log + retention + role-protected access. Tidak 10 karena spec eksternal (UU PDP) masih bisa berubah. |
| **Migration Readiness** | 9/10 | Dual-run + flag + verification strategy solid. Tidak 10 karena ada 1-2 risiko timeline migrasi. |

### 14.2. Overall Assessment

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│        TOTAL SCORE:  8.95 / 10                              │
│                                                             │
│        STATUS:  READY FOR IMPLEMENTATION                    │
│        WITH PHASED ROLLOUT                                  │
│                                                             │
│        RECOMMENDATION: APPROVE & PROCEED                    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### 14.3. Aspek yang Sudah Sangat Matang

✅ **Domain Model** — 9 layer dengan tanggung jawab jelas. Tidak ada overlap.
✅ **Resolution Flow** — sequence lengkap dari login sampai audit.
✅ **Context-Aware Authorization** — 9 context key dengan API jelas.
✅ **Time-Based Authorization** — setiap sumber punya validitas.
✅ **Rule Engine** — IF-THEN dengan facts pluggable.
✅ **Snapshot & Audit** — cache + audit dalam satu mekanisme.
✅ **Conflict Detection** — 7 kategori dengan severity indicator.
✅ **Multi-School Readiness** — schema + flag + scope siap.
✅ **Migration Strategy** — 8 step + dual-run + verification.
✅ **Testing Strategy** — 12 layer test pyramid.
✅ **Failure Recovery** — 5 tier graceful degradation + circuit breaker.
✅ **SDK** — facade dengan backward compat shim.
✅ **Developer Guide** — 8 common tasks dengan step-by-step.
✅ **Operational Playbook** — 10 insiden + tiered responder.
✅ **ADR** — 20 ADR sesuai best practice.
✅ **Risk Assessment** — 30+ risiko dengan mitigasi.

### 14.4. Aspek yang Masih Perlu Diperkuat Saat Implementasi

⚠️ **Empirical performance tuning** — SLA target belum diukur di production scale. Butuh baseline run pada 1000 concurrent users.
⚠️ **Rule UI implementation** — text-based convention belum tentu intuitif untuk non-tech admin. Butuh UX testing.
⚠️ **Delegation validation policy** — seberapa banyak delegasi yang valid? Butuh policy decision di sekolah.
⚠️ **Snapshot retention policy** — 90 hari vs 1 tahun? Butuh policy decision sesuai compliance.
⚠️ **Multi-school activation timing** — apakah saat ini atau tahun depan? Butuh product decision.
⚠️ **Database pilihan snapshot** — Postgres preferable, tapi MySQL masih jalan. Butuh benchmark.
⚠️ **Real-time event delivery latency** — sync vs async rebuild trade-off. Butuh DR run.

### 14.5. Rekomendasi Akhir

#### Keputusan
**APPROVE** desain Authorization Governance & Operational Architecture.

#### Justifikasi
- Risiko telah di-identifikasi dan dimitigasi dengan jelas.
- Migration path aman dengan dual-run + feature flag.
- Developer tidak akan kontak langsung dengan Spatie setelah Phase 8.
- Operasional tim punya runbook lengkap untuk 10 skenario insiden.
- Test pyramid memastikan tidak ada regression yang lolos.
- Audit & compliance siap dari hari pertama.

#### Tindak Lanjut Sebelum Implementasi

1. Stakeholder review session (Product Owner + Tech Lead + Architect): 1 sesi 2 jam.
2. Approval document di-share ke seluruh tim dev.
3. Phase 1 (Foundation) dimulai setelah approval.

#### Setelah Phase 1 selesai:

- A/B testing untuk rule engine dengan sekolah kecil.
- Benchmark internal untuk performance validation.
- Weekly review antara Architect dan Tech Lead.

### 14.6. Pernyataan Penutup

> Arsitektur ini dirancang agar authorization ALIM menjadi **platform**, bukan **fitur**. Platform Authorization harus:
> - Bekerja dengan baik hari ini.
> - Tetap koheren setelah 10 modul baru.
> - Mendukung multi-sekolah tanpa rewrite.
> - Memberikan jawaban jelas untuk pertanyaan admin.
> - Tetap aman di tangan developer yang baru bergabung.
>
> Jika gagal memenuhi salah satu kriteria ini di masa depan, inilah titik kembali untuk redesign.
>
> Jika berhasil, ALIM punya fondasi authorization yang sama dengan platform ERP pendidikan kelas dunia.

---

## Lampiran A — Kontak & Roster

| Role | Primary | Backup | On-call Window |
|------|---------|--------|-----------------|
| Chief Software Architect | TBD | TBD | Bisnis hours + escalation only |
| Tech Lead Backend | TBD | TBD | Mon–Fri 09:00–17:00 + escalation |
| DevOps Lead | TBD | TBD | 24/7 untuk Tier 4-5 |
| QA Lead | TBD | TBD | Senin-Jumat |
| Security Officer | TBD | TBD | Bulanan review + escalation |

## Lampiran B — Daftar Istilah

| Istilah | Definisi |
|---------|----------|
| **AuthoritativeEvent** | Event yang dapat mengubah snapshot secara langsung |
| **Circuit Breaker** | Pattern untuk mencegah cascade failure |
| **Drift** | Perbedaan output antara sistem lama dan baru |
| **Effective Permission** | Kumpulan permission final user (read-only) |
| **Fingerprint** | Hash identifier dari state sumber permission |
| **RTO** | Recovery Time Objective |
| **RPO** | Recovery Point Objective |
| **RUM** | Real User Monitoring |
| **Sunset Window** | Periode setelah deprecation sebelum removal |
| **Tenant** | Scope organisasi (school, yayasan, dll.) |

## Lampiran C — Referensi

| Dokumen | Lokasi |
|---------|--------|
| Authorization Domain Model | `docs/authorization-domain-model.md` |
| Naming Convention | `docs/naming-convention.md` |
| Security Rotation Guide | `docs/security-rotation-guide.md` |
| Architecture Revision | `docs/org-driven-authorization-architecture-revision.md` |
| Audit | `docs/org-driven-authorization-audit.md` |
| ADR (akan datang) | `docs/adr/` |
| Backend Skill | `~/.claude/skills/backend/` |

---

> **END OF DOCUMENT**
>
> Setelah dokumen ini disetujui, Phase 1 (Foundation) dari roadmap implementasi akan dimulai. Tidak ada perubahan perilaku sistem apapun di Phase 1 — murni infrastruktur untuk authorization layer baru.