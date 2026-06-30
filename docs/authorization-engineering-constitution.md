---
name: authorization-engineering-constitution
description: Engineering constitution, implementation contract, code-review standard, CI/CD guardrails for ALIM authorization — binding rules for all developers and AI assistants
metadata:
  type: project
---

# Authorization Engineering Constitution & Implementation Contract

> Status: **Final — Engineering Constitution**
> Supersedes: All previously suggested "engineering practice" notes scattered in implementation phases.
> Binds: All human developers + all AI coding assistants working on ALIM authorization code.

---

## Preamble — The Single Question

> **Bagaimana memastikan implementasi selalu konsisten dengan arsitektur yang telah disetujui?**

Jawaban satu kalimat:

> **Setiap baris kode yang menyentuh authorization harus tunduk pada aturan yang tertulis dalam dokumen ini. Jika kode bertentangan dengan dokumen ini, kode tersebut salah meskipun ia berjalan.**

Dokumen ini adalah **konstitusi**. Bukan rekomendasi. Bukan best-practice. Bukan diskusi. Dokumen ini **wajib** dipatuhi:

- Saat menulis kode baru.
- Saat code review.
- Saat CI/CD validation.
- Saat QA.
- Saat refactoring.
- Saat onboarding developer baru.
- Saat AI coding assistant (termasuk Claude) menghasilkan kode.

Jika implementasi terlihat benar secara runtime tapi melanggar dokumen ini, itu tetap **cacat** dan wajib di-refactor.

---

## Daftar Isi

1. Engineering Principles
2. Non-Negotiable Engineering Rules
3. Folder & Project Structure Standard
4. Naming Convention
5. Coding Standard
6. Implementation Pattern
7. Code Review Checklist
8. CI/CD Validation Rules
9. Definition of Done
10. Architecture Compliance Matrix
11. Technical Debt Policy
12. ADR Governance
13. Engineering Playbook
14. Architecture Compliance Assessment

---

## 1. Engineering Principles

Sepuluh prinsip utama. Semua keputusan engineering pada akhirnya harus konsisten dengan prinsip ini.

### 1.1. Architecture First

**Artinya:** Arsitektur yang telah disetujui (lihat `authorization-domain-model.md`, `authorization-architecture-validation.md`, ADR set) adalah sumber kebenaran tertinggi. Kode tunduk pada arsitektur, bukan sebaliknya.

**Diterapkan di ALIM:**
- Sebelum menulis fitur authorization baru, baca domain model.
- Jika merasa perlu layer baru → **jangan tulis**, ajukan ADR dulu.
- Refactor kode existing untuk memenuhi arsitektur, bukan sebaliknya.

### 1.2. Domain Driven

**Artinya:** Struktur domain authorization (Identity → Profile → Employment → Assignment → Temporary Assignment → Revocation → Effective Permission) adalah pondasi. Semua service dan repository mengikuti domain ini.

**Diterapkan di ALIM:**
- Namespace mengikuti domain: `App\Authorization\Identity`, `App\Authorization\Employment`, dst.
- Tidak ada folder "authorization-helper" atau "auth-utils" tanpa ownership domain.

### 1.3. Event Driven

**Artinya:** Setiap perubahan pada state sumber authorization (employment, assignment, dll.) memicu event. Listener menangani reaksi.

**Diterapkan di ALIM:**
- Observer tidak berisi business logic — observer hanya mendispatch event.
- Event adalah immutable DTO.
- Listener idempotent (lihat §2).

### 1.4. Single Source of Truth

**Artinya:** Tidak boleh ada dua sistem yang masing-masing menjadi sumber kebenaran untuk hal yang sama. Contoh: identity role hanya di Spatie `roles` table. Position tidak boleh "direplikasi" ke tabel lain.

**Diterapkan di ALIM:**
- Spatie roles → hanya identity (`super_admin`, `gtk`, `peserta_didik`, `wali_santri`, `alumni`).
- Position → hanya di `config/authorization/positions.php`.
- Assignment → hanya di tabel `teaching_assignments`, `homeroom_assignments`, dst.
- Permission yang granted ke user → hanya hasil Builder, **tidak pernah** di-insert manual ke Spatie.

### 1.5. Explainable Authorization

**Artinya:** Setiap permission decision dapat dijelaskan asalnya. "Mengapa user X bisa input nilai?" → jawabnya tersedia via `whyAllows()` atau `audit:trace`.

**Diterapkan di ALIM:**
- Tidak ada permission anonymous (anonymous grant = bug).
- Origin index wajib diisi pada setiap snapshot.
- Audit log menyimpan `permission + context + origin`.

### 1.6. Context First

**Artinya:** Permission selalu dievaluasi dalam konteks. Tidak ada permission "global tanpa scope" kecuali identity.

**Diterapkan di ALIM:**
- Setiap `authorize()` call **wajib** membawa konteks (school_id, academic_year_id, study_group_id, subject_id, dst.).
- Policy method **wajib** menerima context.
- Middleware wajib memasang OrganizationContext.

### 1.7. Backward Compatible

**Artinya:** Sistem existing tidak boleh rusak. Migrasi dilakukan gradual dengan parity test dan rollback plan.

**Diterapkan di ALIM:**
- Phase cutover: shadow mode → gradual enable → full enforcement.
- Setiap perubahan authorization wajib memiliki **parity test** (Spatie output vs Builder output) sebelum enforcement.

### 1.8. No Hidden Logic

**Artinya:** Tidak ada logika authorization yang tersembunyi. Tidak ada "dianggap" atau "diasumsikan".

**Diterapkan di ALIM:**
- Tidak ada permission default yang implicit.
- Tidak ada short-circuit logic yang membuat permission tanpa trace.
- Tidak ada "kalau dia GTK berarti boleh" tanpa melalui Builder.

### 1.9. Explicit over Implicit

**Artinya:** Lebih baik kode verbose tapi eksplisit, daripada singkat tapi tersembunyi.

**Diterapkan di ALIM:**
- Magic string permission: **dilarang** (lihat §2).
- Magic number weight: **dilarang** — pakai konstanta.
- Implicit context: **dilarang** — selalu oper context eksplisit.

### 1.10. Convention over Configuration

**Artinya:** Ikuti konvensi Laravel + ALIM. Tidak perlu konfigurasi baru kecuali benar-benar diperlukan.

**Diterapkan di ALIM:**
- Folder mengikuti Laravel standard.
- Service container pattern standar Laravel.
- Event/Listener mengikuti Laravel event dispatcher.

---

## 2. Non-Negotiable Engineering Rules

Dua belas aturan yang **tidak boleh dilanggar**. Setiap aturan disertai: alasan, contoh benar, contoh salah, dampak pelanggaran.

---

### R-01. Tidak boleh memanggil `assignRole()` secara langsung di Controller.

**Alasan:** Identity role hanya boleh diubah oleh listener setelah event perubahan state user (mis. user registration). Pemanggilan langsung di Controller membuka peluang inkonsistensi (snapshot tidak ke-update, audit log tidak ada, parity test gagal).

**Contoh Benar:**
```php
// App\Http\Controllers\Admin\GtkController.php
public function store(StoreGtkRequest $request)
{
    $gtk = $this->gtkRegistrationService->create($request->validated());
    // Identity role 'gtk' di-set di dalam service via event listener.
}
```

**Contoh Salah:**
```php
// ❌ JANGAN PERNAH
public function store(StoreGtkRequest $request)
{
    $user = User::create([...]);
    $user->assignRole('gtk');  // ❌
}
```

**Dampak jika dilanggar:** Audit trail kosong. Snapshot tidak ter-rebuild. Parity drift. CI/CD lint akan reject.

---

### R-02. Tidak boleh menggunakan `hasRole()` untuk position di Controller.

**Alasan:** `hasRole('gtk')` untuk identity boleh. `hasRole('wakil_kepala_sekolah')` untuk position **dilarang** — posisi harus melalui Builder.

**Contoh Benar:**
```php
// ✅ Identity check (diperbolehkan)
if ($user->hasRole('gtk')) { ... }

// ✅ Permission check via authorization (wajib)
if ($user->can('gtk.wakasek.monitoring')) { ... }
```

**Contoh Salah:**
```php
// ❌ Position check via hasRole
if ($user->hasRole('wakil_kepala_sekolah')) { ... }
```

**Dampak:** Position role tidak context-aware. Snapshot drift. Cannot scale ke multi-school.

---

### R-03. Tidak boleh membuat permission hardcoded.

**Alasan:** Permission adalah entitas domain, bukan string yang boleh ditulis ad-hoc di controller. Setiap permission harus terdaftar di registry.

**Contoh Benar:**
```php
// ✅ Pakai konstanta
use App\Authorization\Registry\PermissionRegistry;
if ($user->can(PermissionRegistry::NILAI_INPUT)) { ... }
```

**Contoh Salah:**
```php
// ❌ Magic string
if ($user->can('nilai.input')) { ... }
```

**Dampak:** Typo tidak terdeteksi. Permission registry tidak sinkron. Search refactor gagal.

---

### R-04. Tidak boleh mengakses tabel organisasi secara langsung dari Controller.

**Alasan:** Controller tidak boleh query langsung ke tabel `gtk_employments`, `teaching_assignments`, dll. Akses lewat Service. Ini memastikan setiap akses melewati authorization.

**Contoh Benar:**
```php
// Controller
$nilai = $this->nilaiService->fetchForClass($rombel, $mapel);
```

**Contoh Salah:**
```php
// ❌ Query langsung di Controller
$nilai = DB::table('nilai')->where('study_group_id', $rombel->id)->get();
```

**Dampak:** Authorization bypass. Tidak ada audit. Cache tidak invalid.

---

### R-05. Semua perubahan organisasi wajib melalui Domain Event.

**Alasan:** Perubahan GtkEmployment, TeachingAssignment, dst. wajib memicu event. Listener yang menangani rebuild snapshot.

**Contoh Benar:**
```php
// Service mengubah employment, lalu dispatch event
$employment = GtkEmployment::create([...]);
event(new GtkEmploymentActivated($employment->user_id, $employment->position_code, $employment->valid_from));
```

**Contoh Salah:**
```php
// ❌ Update langsung tanpa event
GtkEmployment::where('user_id', $id)->update(['valid_until' => now()]);
```

**Dampak:** Snapshot tidak ke-rebuild. Permission cache stale. Audit gap.

---

### R-06. Semua authorization harus melalui Authorization Layer.

**Alasan:** Authorization adalah keputusan domain. Bukan keputusan controller. Layer ini bisa di-test, di-audit, dan di-trace.

**Contoh Benar:**
```php
// Controller
$this->authorize('nilai.input', [$rombel, $mapel]);
```

**Contoh Salah:**
```php
// ❌ Inline check
if ($user->id !== $rombel->homeroom_teacher_id) abort(403);
```

**Dampak:** Authorization tidak melalui Builder. Origin tidak tercatat. Tidak ada scope check.

---

### R-07. Semua business logic harus berada di Service atau Domain Layer.

**Alasan:** Controller adalah thin layer. Business logic di Service. Domain logic di Domain Model.

**Contoh Benar:**
```php
// Controller
$this->nilaiService->saveScore($data);

// Service
class NilaiService {
    public function saveScore(array $data): Nilai { ... }
}
```

**Contoh Salah:**
```php
// ❌ Business logic di Controller
$nilai = new Nilai($data);
$nilai->compute();
$nilai->save();
$nilai->notifyWaliKelas();
```

**Dampak:** Tidak reusable. Tidak bisa di-test tanpa HTTP. Reuse logic scattered.

---

### R-08. Semua Event harus idempotent.

**Alasan:** Event mungkin di-dispatch lebih dari sekali (network retry, queue redelivery). Listener harus menghasilkan efek yang sama.

**Contoh Benar:**
```php
// Listener
public function handle(GtkEmploymentActivated $event): void
{
    // upsert snapshot: kalau sudah ada dengan fingerprint sama, tidak ada perubahan
    $this->snapshotStore->upsertForUser($event->userId, $event->reason);
}
```

**Contoh Salah:**
```php
// ❌ Tambah tanpa cek
public function handle(GtkEmploymentActivated $event): void
{
    GtkEmploymentLog::create(['user_id' => $event->userId, ...]);
}
```

**Dampak:** Duplicated logs. Inflated audit. Wrong counts.

---

### R-09. Semua Listener harus queue-safe.

**Alasan:** Listener yang berat (mis. snapshot rebuild) harus di-queue. Listener sync hanya untuk hal yang trivial.

**Contoh Benar:**
```php
class RebuildPermissionsListener implements ShouldQueue
{
    use Queueable;
    public int $tries = 3;
    public int $backoff = 30;
}
```

**Contoh Salah:**
```php
// ❌ Sync listener yang berat
class RebuildPermissionsListener
{
    public function handle(...) { /* heavy computation */ }
}
```

**Dampak:** Request latency tinggi saat ada rebuild. HTTP timeouts.

---

### R-10. Semua Snapshot bersifat immutable.

**Alasan:** Snapshot adalah audit trail. Row yang sudah ditulis tidak boleh di-update. Perubahan menghasilkan row baru.

**Contoh Benar:**
```php
$snapshot = PermissionSnapshot::create([
    'user_id' => $userId,
    'fingerprint' => $newFingerprint,
    'is_current' => true,
]);
// Row lama di-set is_current = false (bukan update nilainya)
DB::table('permission_snapshots')
    ->where('user_id', $userId)
    ->where('id', '!=', $snapshot->id)
    ->update(['is_current' => false]);
```

**Contoh Salah:**
```php
// ❌ Update snapshot existing
$snapshot = PermissionSnapshot::where('user_id', $userId)->first();
$snapshot->update(['fingerprint' => $newFingerprint]);
```

**Dampak:** Audit trail rusak. Histori hilang. ADR-016 retention tidak berlaku.

---

### R-11. Semua perubahan authorization harus dapat diaudit.

**Alasan:** Setiap perubahan (grant, revoke, conflict, snapshot rebuild) wajib tercatat di audit log.

**Contoh Benar:**
```php
// Service yang mengubah authorization state
$this->auditLogger->log('permission_granted', [
    'user_id' => $userId,
    'permission' => 'nilai.input',
    'origin' => 'TeachingAssignment',
    'source_id' => $assignmentId,
]);
```

**Contoh Salah:**
```php
// ❌ Perubahan tanpa audit
UserPermission::create(['user_id' => $id, 'permission' => 'x']);
```

**Dampak:** Tidak bisa rekonstruksi state. Compliance fail. Debugging impossible.

---

### R-12. Semua perubahan authorization harus memiliki test.

**Alasan:** Tidak ada perubahan authorization yang merge ke main tanpa test yang memadai (unit + integration + parity).

**Contoh Benar:**
```php
// tests/Feature/Authorization/NilaiInputAuthorizationTest.php
test('guru dapat input nilai untuk rombel yang diajarnya', function () {
    $guru = User::factory()->gtk()->create();
    TeachingAssignment::factory()->create([
        'user_id' => $guru->id,
        'study_group_id' => $rombel->id,
    ]);
    expect($guru->can('nilai.input', [$rombel, $mapel]))->toBeTrue();
});
```

**Contoh Salah:**
```php
// ❌ Tanpa test
"tambah permission baru"  // commit tanpa test
```

**Dampak:** Regressi tidak terdeteksi. CI/CD tidak catch. Incident berulang.

---

## 3. Folder & Project Structure Standard

```
app/
└── Authorization/
    ├── Builders/
    │   ├── EffectivePermissionBuilder.php
    │   ├── IdentityProvider.php
    │   ├── ProfileProvider.php
    │   ├── EmploymentProvider.php
    │   ├── AssignmentProvider.php
    │   ├── DelegationProvider.php
    │   ├── ActingPositionProvider.php
    │   ├── RevocationProvider.php
    │   └── FactResolver.php
    │
    ├── Contexts/
    │   ├── OrganizationContext.php
    │   └── ContextResolver.php
    │
    ├── Contracts/
    │   ├── PositionSource.php
    │   ├── PermissionProvider.php
    │   ├── FactInterface.php
    │   ├── RuleInterface.php
    │   ├── SnapshotStore.php
    │   └── ConflictDetector.php
    │
    ├── DTO/
    │   ├── EffectivePermission.php
    │   ├── ScopedPermission.php
    │   ├── Origin.php
    │   ├── TracedPermission.php
    │   ├── PermissionBag.php
    │   └── ConflictReport.php
    │
    ├── Events/
    │   ├── IdentityAssigned.php
    │   ├── GtkEmploymentActivated.php
    │   ├── GtkEmploymentRevoked.php
    │   ├── TeachingAssigned.php
    │   ├── TeachingRevoked.php
    │   ├── HomeroomAssigned.php
    │   ├── HomeroomRevoked.php
    │   ├── AdditionalTaskAssigned.php
    │   ├── AdditionalTaskRevoked.php
    │   ├── GtkResigned.php
    │   ├── GtkPensionActivated.php
    │   ├── GtkMutatedOut.php
    │   ├── GtkMutatedIn.php
    │   ├── AcademicYearRolloverTriggered.php
    │   ├── ActingPositionAssigned.php
    │   ├── ActingPositionRevoked.php
    │   ├── ActingPositionExpired.php
    │   ├── DelegationCreated.php
    │   ├── DelegationRevoked.php
    │   ├── RevocationApplied.php
    │   ├─�� UserToggleActive.php
    │   └── UserDeleted.php
    │
    ├── Listeners/
    │   ├── RebuildPermissionsListener.php
    │   ├── InvalidateAuthCacheListener.php
    │   ├── DetectConflictsListener.php
    │   ├── SyncActingStatusListener.php
    │   └── RecordAuditListener.php
    │
    ├── Policies/
    │   ├── NilaiPolicy.php
    │   ├── RaporPolicy.php
    │   ├── PresensiPolicy.php
    │   ├── GtkPolicy.php
    │   ├── PesertaDidikPolicy.php
    │   └── (per-domain policy classes)
    │
    ├── Registry/
    │   ├── PositionRegistry.php
    │   ├── PermissionRegistry.php
    │   ├── FactRegistry.php
    │   └── RuleRegistry.php
    │
    ├── Rules/
    │   ├── RuleDefinition.php
    │   ├── RuleEvaluator.php
    │   ├── Fact/
    │   │   ├── IdentityFact.php
    │   │   ├── TeachingAssignmentFact.php
    │   │   ├── HomeroomFact.php
    │   │   ├── AcademicYearActiveFact.php
    │   │   ├── TimeInWindowFact.php
    │   │   └── (other built-in facts)
    │   └── (custom rule classes)
    │
    ├── Services/
    │   ├── PermissionSnapshotResolver.php
    │   ├── PermissionSnapshotStore.php
    │   ├── EffectivePermissionService.php
    │   ├── AuthorizationConflictDetector.php
    │   ├── DelegationService.php
    │   ├── ActingPositionService.php
    │   ├── RevocationService.php
    │   └── AuthorizationAuditLogger.php
    │
    ├── Snapshots/
    │   ├── PermissionSnapshot.php (model)
    │   ├── PermissionSnapshotQuery.php
    │   └── SnapshotFingerprint.php
    │
    ├── Support/
    │   ├── HasEffectivePermissions.php (trait)
    │   ├── TemporalEvaluator.php
    │   ├── ScopeMatcher.php
    │   └── ConflictSeverity.php (enum)
    │
    ├── Exceptions/
    │   ├── AuthorizationDenied.php
    │   ├── SnapshotNotFound.php
    │   ├── BuilderException.php
    │   ├── RuleEvaluationException.php
    │   └── ConflictBlocking.php
    │
    ├── Middleware/
    │   ├── OrganizationContextMiddleware.php
    │   ├── EffectivePermissionMiddleware.php
    │   └── SnapshotLoadMiddleware.php
    │
    ├── Console/
    │   ├── AuthTraceCommand.php
    │   ├── AuthDiffCommand.php
    │   ├── AuthDriftCommand.php
    │   ├── AuthBackfillCommand.php
    │   ├── AuthReconcileCommand.php
    │   ├── AuthVerifySchemaCommand.php
    │   ├── ParityRecordCommand.php
    │   └── (other CLI tools)
    │
    └── Tests/
        ├── Fixtures/
        │   ├── GtkScenarioFixtures.php
        │   ├── ParityFixtures.php
        │   └── ConflictFixtures.php
        ├── Factories/
        │   ├── GtkEmploymentFactory.php
        │   ├── TeachingAssignmentFactory.php
        │   └── (other model factories)
        └── Helpers/
            ├── PermissionTestHelper.php
            └── SnapshotTestHelper.php
```

### Tanggung Jawab Masing-masing Folder

| Folder | Tanggung Jawab | Tidak Boleh Berisi |
|--------|---------------|-------------------|
| **Builders/** | Logika compute permission dari berbagai source. | Query HTTP, response format. |
| **Contexts/** | Resolusi OrganizationContext dari request. | Permission logic. |
| **Contracts/** | Interface saja. Tidak ada implementasi. | Implementasi konkret. |
| **DTO/** | Immutable data container. | Method dengan side-effect. |
| **Events/** | Immutable event payload. | Listener logic. |
| **Listeners/** | Reaksi terhadap event. | Business logic lain. |
| **Policies/** | Decision method per resource. | Query langsung ke DB tanpa service. |
| **Registry/** | Lookup table untuk position, permission, fact, rule. | Computed value. |
| **Rules/** | Rule definition & evaluator. | HTTP atau persistence. |
| **Services/** | Orchestration business logic. | HTTP response. |
| **Snapshots/** | Model + query untuk snapshot. | Logic rebuild. |
| **Support/** | Trait, helper, evaluator non-domain. | Business decision. |
| **Exceptions/** | Custom exception classes. | Handler logic. |
| **Middleware/** | HTTP middleware. | Domain computation. |
| **Console/** | Artisan command. | Runtime behavior. |
| **Tests/** | Test fixture dan helper. | Production code. |

**Prinsip:** Tidak boleh ada overlap. Setiap class hanya di satu folder.

---

## 4. Naming Convention

### 4.1. Event

**Pattern:** `PastTenseAction` atau `EntityActionPastTense`.
**Namespace:** `App\Authorization\Events`.

| Type | Convention | Contoh |
|------|------------|--------|
| State change event | `{Entity}{Action}` | `GtkEmploymentActivated` |
| Snapshot lifecycle | `Snapshot{Action}` | `SnapshotRebuilt`, `SnapshotInvalidated` |
| Conflict detection | `{Kind}ConflictDetected` | `TeachingAssignmentConflictDetected` |
| Lifecycle user | `User{Action}` | `UserToggleActive`, `UserDeleted` |

**Suffix wajib:** `PastTense`. **Tidak pernah** `GtkEmploymentActivate` (present tense).

---

### 4.2. Listener

**Pattern:** `{Action}Listener` atau `{Event}Listener`.
**Namespace:** `App\Authorization\Listeners`.

| Tipe | Convention | Contoh |
|------|------------|--------|
| Reaksi event | `{Action}Listener` | `RebuildPermissionsListener` |
| Side-effect khusus | `{Event}Listener` | `DetectConflictsListener` |
| Cross-cutting | `Sync{Layer}Listener` | `SyncActingStatusListener` |

**Suffix wajib:** `Listener`. **Dilarang** nama generic seperti `HandleEvent`, `ProcessEvent`.

---

### 4.3. Builder

**Pattern:** `{What}Builder`.
**Namespace:** `App\Authorization\Builders`.

| Tipe | Convention | Contoh |
|------|------------|--------|
| Aggregate builder | `EffectivePermissionBuilder` |
| Provider khusus source | `{Source}Provider` | `EmploymentProvider`, `TeachingAssignmentProvider` |

**Suffix wajib:** `Builder` atau `Provider`. **Dilarang** `BuilderFactory`, `BuilderService`.

---

### 4.4. Policy

**Pattern:** `{Resource}Policy`.
**Namespace:** `App\Authorization\Policies`.

| Tipe | Convention | Contoh |
|------|------------|--------|
| Per-resource | `{Resource}Policy` | `NilaiPolicy`, `RaporPolicy` |
| Multi-resource | `{Domain}Policy` | `GtkManagementPolicy` |

**Suffix wajib:** `Policy`. **Dilarang** `Permission`, `Access`, `Authorizer`.

---

### 4.5. Service

**Pattern:** `{Action}Service` atau `{Domain}Service`.
**Namespace:** `App\Authorization\Services`.

| Tipe | Convention | Contoh |
|------|------------|--------|
| Orchestration | `{Domain}Service` | `PermissionSnapshotResolver` (read), `PermissionSnapshotStore` (write) |
| Side-effect coordinator | `{Action}Service` | `DelegationService`, `ActingPositionService` |

**Suffix wajib:** `Service`, `Resolver`, atau `Store`. **Dilarang** `Manager`, `Helper`.

---

### 4.6. Rule

**Pattern:** `{Domain}Rule` atau `Rule{Behavior}`.
**Namespace:** `App\Authorization\Rules`.

| Tipe | Convention | Contoh |
|------|------------|--------|
| Built-in rule | `{Domain}Rule` | `GtkPengajarRule`, `HomeroomAccessRule` |
| Behavior-based | `Rule{Behavior}` | `RuleRevocation`, `RuleOverride` |

**Suffix wajib:** `Rule`. **Dilarang** `Condition`, `Criterion`.

---

### 4.7. Snapshot

**Pattern:** `Permission{Suffix}`.
**Namespace:** `App\Authorization\Snapshots`.

| Tipe | Convention | Contoh |
|------|------------|--------|
| Model | `PermissionSnapshot` |
| Builder helper | `SnapshotFingerprint` |
| Query | `PermissionSnapshotQuery` |

---

### 4.8. Context

**Pattern:** `{Domain}Context`.
**Namespace:** `App\Authorization\Contexts`.

| Tipe | Convention | Contoh |
|------|------------|--------|
| Primary | `OrganizationContext` |
| Resolver | `ContextResolver` |

---

### 4.9. DTO

**Pattern:** `{Concept}` (tanpa suffix DTO).
**Namespace:** `App\Authorization\DTO`.

| Tipe | Convention | Contoh |
|------|------------|--------|
| Immutable data | `{Concept}` | `EffectivePermission`, `ScopedPermission`, `Origin`, `TracedPermission`, `ConflictReport` |

**Dilarang** suffix `DTO`, `Data`, `Model`. Namespace `DTO\` sudah cukup.

---

### 4.10. Exception

**Pattern:** `{Concept}Exception`.
**Namespace:** `App\Authorization\Exceptions`.

| Tipe | Convention | Contoh |
|------|------------|--------|
| Domain exception | `{Concept}Exception` | `SnapshotNotFoundException`, `BuilderException`, `RuleEvaluationException`, `ConflictBlockingException` |

---

### 4.11. Interface

**Pattern:** `{Capability}` atau `{Role}Interface`.
**Namespace:** `App\Authorization\Contracts`.

| Tipe | Convention | Contoh |
|------|------------|--------|
| Capability contract | `{Capability}` | `PermissionProvider`, `PositionSource`, `FactInterface`, `RuleInterface`, `SnapshotStore`, `ConflictDetector` |
| Role contract | `{Role}Interface` | (jarang dipakai) |

**Dilarang** suffix `Interface` pada nama interface. Namespace `Contracts\` sudah cukup.

---

### 4.12. Test

**Pattern:** `{Subject}{Type}Test`.
**Location:** `tests/Unit/Authorization/`, `tests/Feature/Authorization/`.

| Tipe | Convention | Contoh |
|------|------------|--------|
| Unit test | `{Class}Test` | `EffectivePermissionBuilderTest` |
| Feature test | `{Feature}Test` | `GtkEmploymentEventTest` |
| Authorization test | `{Feature}AuthorizationTest` | `NilaiInputAuthorizationTest` |
| Parity test | `{Feature}ParityTest` | `GtkSnapshotParityTest` |

---

### 4.13. Migration

**Pattern:** `{YYYY_MM_DD_HHMMSS}_{action}_{table}.php`.

| Tipe | Convention | Contoh |
|------|------------|--------|
| Create table | `create_{table}_table` | `2026_06_29_080000_create_permission_snapshots_table.php` |
| Add column | `add_{column}_to_{table}_table` | `2026_06_29_080100_add_fingerprint_to_permission_snapshots_table.php` |
| Modify index | `add_index_to_{table}_table` | `2026_06_29_080200_add_index_to_permission_snapshots_table.php` |

---

### 4.14. Contoh Benar vs Salah

**Service:**

```php
// ✅ Benar
class PermissionSnapshotResolver { ... }
class DelegationService { ... }
class AuthorizationConflictDetector { ... }

// ❌ Salah
class SnapshotHelper { ... }
class AuthManager { ... }
class DelegationManager { ... }
```

**Listener:**

```php
// ✅ Benar
class RebuildPermissionsListener { ... }
class DetectConflictsListener { ... }

// ❌ Salah
class RebuildListener { ... }
class HandleEvent { ... }
```

**Builder:**

```php
// ✅ Benar
class EffectivePermissionBuilder { ... }
class TeachingAssignmentProvider { ... }

// ❌ Salah
class BuildPermissions { ... }
class TeacherProviderFactory { ... }
```

---

## 5. Coding Standard

### 5.1. Service harus single responsibility.

**Standar:** Satu service = satu orchestration domain. Tidak ada service yang handle 3+ domain berbeda.

**Alasan teknis:** Easier to test, easier to mock, easier to reason about side-effects.

**Contoh Benar:**
```php
class DelegationService {
    // hanya urus delegation lifecycle
    public function create(array $data): Delegation { ... }
    public function revoke(int $id, string $reason): void { ... }
}
```

**Contoh Salah:**
```php
class AuthorizationManager {
    public function createDelegation() { ... }
    public function rebuildSnapshot() { ... }
    public function detectConflict() { ... }
    public function sendNotification() { ... }
}
```

---

### 5.2. Builder harus pure function.

**Standar:** `EffectivePermissionBuilder::build()` tidak boleh menulis ke DB, tidak boleh call external service, tidak boleh mutate global state. Pure: input → output.

**Alasan teknis:** Testable tanpa mock. Cacheable. Deterministic.

**Contoh Benar:**
```php
final class EffectivePermissionBuilder
{
    public function build(User $user, ?OrganizationContext $context = null): EffectivePermission
    {
        $providers = [...];
        $bag = new PermissionBag();
        foreach ($providers as $p) {
            $bag = $p->contribute($bag, $user, $context);
        }
        return new EffectivePermission(...);
    }
}
```

**Contoh Salah:**
```php
class EffectivePermissionBuilder
{
    public function build(User $user)
    {
        // ❌ Side effect
        PermissionAuditLog::create([...]);
        Cache::forget("user:{$user->id}");
        return ...;
    }
}
```

---

### 5.3. Observer tidak boleh berisi business logic.

**Standar:** Observer Eloquent hanya memanggil `event()`. Tidak ada logika selain dispatch.

**Alasan teknis:** Observer yang berisi logic sulit di-test dan di-replay. Event dispatcher sudah async-safe.

**Contoh Benar:**
```php
class GtkEmploymentObserver
{
    public function created(GtkEmployment $employment): void
    {
        event(new GtkEmploymentActivated(
            userId: $employment->user_id,
            positionCode: $employment->position_code,
            validFrom: $employment->valid_from,
        ));
    }
}
```

**Contoh Salah:**
```php
class GtkEmploymentObserver
{
    public function created(GtkEmployment $employment): void
    {
        // ❌ Logic di observer
        Cache::forget("user:{$employment->user_id}");
        User::find($employment->user_id)->update([...]);
    }
}
```

---

### 5.4. Event harus immutable.

**Standar:** Property event `readonly`. Tidak ada setter.

**Alasan teknis:** Event boleh di-replay (queue redelivery). Mutable event = inconsistent state.

**Contoh Benar:**
```php
final class GtkEmploymentActivated
{
    public function __construct(
        public readonly string $userId,
        public readonly string $positionCode,
        public readonly Carbon $validFrom,
    ) {}
}
```

**Contoh Salah:**
```php
class GtkEmploymentActivated
{
    public string $userId;
    public function setUserId(string $id): void { $this->userId = $id; }
}
```

---

### 5.5. Listener harus idempotent.

**Standar:** Listener yang memproses event yang sama dua kali harus menghasilkan efek identik.

**Alasan teknis:** Queue retry. Job duplicate. Tidak boleh double-write.

**Contoh Benar:**
```php
class RebuildPermissionsListener
{
    public function handle(GtkEmploymentActivated $event): void
    {
        $this->snapshotStore->upsertForUser($event->userId, 'GtkEmploymentActivated');
    }
}
```

**Contoh Salah:**
```php
class RebuildPermissionsListener
{
    public function handle(GtkEmploymentActivated $event): void
    {
        RebuildLog::create(['user_id' => $event->userId]); // ❌ Duplicate log
    }
}
```

---

### 5.6. DTO harus immutable.

**Standar:** Semua property `readonly`. Constructor hanya set property. Tidak ada method yang mengubah state.

**Alasan teknis:** DTO adalah data container. Mutability = bug tersembunyi.

**Contoh Benar:**
```php
final class EffectivePermission
{
    public function __construct(
        public readonly array $globalPermissions,
        public readonly array $scopedPermissions,
        public readonly array $originIndex,
        public readonly string $fingerprint,
        public readonly Carbon $computedAt,
    ) {}
}
```

---

### 5.7. Context harus strongly typed.

**Standar:** `OrganizationContext` property adalah typed (UUID, Carbon, enum). Tidak ada `array` atau `mixed`.

**Alasan teknis:** Context salah tipe = authorization decision salah. Type safety adalah lapisan pertahanan pertama.

**Contoh Benar:**
```php
final class OrganizationContext
{
    public function __construct(
        public readonly ?SchoolId $schoolId,
        public readonly ?AcademicYearId $academicYearId,
        public readonly ?StudyGroupId $studyGroupId,
        public readonly ?SubjectId $subjectId,
        public readonly Carbon $now,
    ) {}
}
```

**Contoh Salah:**
```php
class OrganizationContext
{
    public array $context; // ❌ tidak typed
}
```

---

### 5.8. Tidak boleh ada magic string.

**Standar:** Semua permission name, position code, fact name, rule id harus melalui registry.

**Alasan teknis:** Typo tidak terdeteksi di static analysis. Refactor search gagal.

**Contoh Benar:**
```php
use App\Authorization\Registry\PermissionRegistry;

if ($user->can(PermissionRegistry::NILAI_INPUT)) { ... }
```

**Contoh Salah:**
```php
if ($user->can('nilai.input')) { ... } // ❌ Magic string
```

---

### 5.9. Tidak boleh ada duplicated business rule.

**Standar:** Satu rule didefinisikan sekali. Tidak ada hardcoded di dua tempat berbeda.

**Alasan teknis:** Inkonsistensi aturan = bug. Audit susah.

**Contoh Benar:**
```php
// Rule didefinisikan di registry
RuleRegistry::register('gtk.input_nilai', [...]);

// Dipakai di policy
return $user->effectivePermissions()->allows(
    PermissionRegistry::NILAI_INPUT,
    $context
);
```

**Contoh Salah:**
```php
// ❌ Rule hardcoded di policy AND di tempat lain
// Policy
if ($user->hasRole('gtk') && $user->id === $rombel->homeroom_teacher_id) { ... }
// OtherController
if ($user->hasRole('gtk') && $rombel->homeroom_teacher_id === $user->id) { ... }
```

---

### 5.10. Constructor injection; tidak boleh `app()` atau `resolve()` di method.

**Standar:** Dependency via constructor type-hint. Tidak ada service locator pattern.

**Alasan teknis:** Testability. Explicit dependency graph.

**Contoh Benar:**
```php
class RebuildPermissionsListener
{
    public function __construct(
        private readonly PermissionSnapshotStore $snapshotStore,
        private readonly AuthorizationAuditLogger $auditLogger,
    ) {}
}
```

**Contoh Salah:**
```php
class RebuildPermissionsListener
{
    public function handle($event)
    {
        $store = app(PermissionSnapshotStore::class); // ❌ Service locator
    }
}
```

---

### 5.11. Exception harus spesifik.

**Standar:** Tidak ada `throw new \Exception(...)`. Pakai domain exception spesifik.

**Alasan teknis:** Catch yang tepat. Monitoring yang akurat. Debugging cepat.

**Contoh Benar:**
```php
throw new SnapshotNotFoundException(
    "Snapshot for user {$userId} not found",
    ['user_id' => $userId]
);
```

**Contoh Salah:**
```php
throw new \Exception("snapshot not found");
```

---

### 5.12. Setiap public method harus punya return type.

**Standar:** PHP return type declaration pada setiap public method.

**Alasan teknis:** Static analysis. IDE autocomplete. Refactor safety.

**Contoh Benar:**
```php
public function build(User $user): EffectivePermission
public function revoke(int $id, string $reason): void
public function findCurrent(string $userId): ?PermissionSnapshot
```

---

## 6. Implementation Pattern

Pola resmi untuk setiap request authorization di ALIM.

```
┌─────────────────────────────────────────────────────────────────┐
│  1. REQUEST                                                     │
│     HTTP request masuk (GET/POST/PUT/DELETE)                    │
└────────────────────────────┬───────────────────────────���────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  2. AUTHENTICATION                                              │
│     Laravel Auth middleware → session/token valid               │
│     → User loaded                                              │
└────────────────────────────┬────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  3. CONTEXT RESOLUTION                                          │
│     OrganizationContextMiddleware:                              │
│     - Extract school_id, academic_year_id, study_group_id,      │
│       subject_id from route/session                             │
│     - Bind to Request                                           │
└────────────────────────────┬────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  4. SNAPSHOT LOOKUP                                             │
│     SnapshotLoadMiddleware:                                     │
│     - Check cache: auth:snap:{user_id}                          │
│     - Hit: load snapshot to Request                             │
│     - Miss: enqueue RebuildPermissionsListener (async)          │
│            + return snapshot from last known good (if exists)   │
└────────────────────────────┬────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  5. AUTHORIZATION (controller)                                  │
│     $this->authorize('permission', [$resource1, $resource2])    │
│     → Gate::authorize → Policy::method                          │
└────────────────────────────┬────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  6. POLICY EVALUATION                                           │
│     Policy::method($user, $resource, ...):                       │
│     - $user->effectivePermissions()                             │
│       ->allows($permission, $context)                           │
│     - returns true / false                                      │
└────────────────────────────┬────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  7. EFFECTIVE PERMISSION BUILDER                                │
│     - IdentityProvider                                          │
│     - EmploymentProvider                                        │
│     - AssignmentProvider                                        │
│     - DelegationProvider                                        │
│     - ActingPositionProvider                                    │
│     - RevocationProvider                                        │
│     - FactResolver → RuleEvaluator                              │
│     - Returns EffectivePermission with origin_index             │
└────────────────────────────┬────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  8. SERVICE EXECUTION                                           │
│     Controller delegates to Service                             │
│     Service performs business logic                             │
│     Service dispatches event on state change                    │
└────────────────────────────┬────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  9. EVENT DISPATCH                                              │
│     event(new XxxEvent(...))                                    │
│     → Listener queue (sync untuk trivial, async untuk rebuild)  │
│     → RebuildPermissionsListener (async)                        │
│     → Snapshot refresh                                          │
└────────────────────────────┬────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  10. RESPONSE                                                   │
│      Service returns DTO                                       │
│      Controller transforms to HTTP response                     │
│      AuditLogger.log() writes decision if AUDIT_VERBOSE        │
└─────────────────────────────────────────────────────────────────┘
```

**Aturan tambahan:**
- Setiap fitur authorization baru harus mengikuti pola ini.
- Setiap shortcut (mis. langsung query tanpa service) adalah pelanggaran.
- Setiap layer boleh di-skip **hanya jika** ada ADR yang menjelaskan alasannya (sangat jarang).

---

## 7. Code Review Checklist

Checklist wajib untuk setiap code review authorization-related.

### 7.1. Architecture

- [ ] **Layer compliance**: Kode hanya di folder sesuai tanggung jawab (§3).
- [ ] **No new layer**: Tidak ada folder/class baru di luar struktur.
- [ ] **No new architectural pattern**: Tidak ada pattern baru tanpa ADR.
- [ ] **Domain alignment**: Namespace sesuai domain layer.

### 7.2. Authorization

- [ ] **No direct `assignRole()`** (R-01).
- [ ] **No `hasRole()` for position** in Controller (R-02).
- [ ] **No hardcoded permission strings** (R-03).
- [ ] **No direct DB query** ke tabel organisasi dari Controller (R-04).
- [ ] **All state changes via event** (R-05).
- [ ] **All authorization via Authorization Layer** (R-06).
- [ ] **No business logic in Controller** (R-07).
- [ ] **No authorization logic in Observer/Event** (R-07 + event = thin).

### 7.3. Event & Listener

- [ ] **Event immutable** (R-04 + §5.4).
- [ ] **Event past tense** (naming).
- [ ] **Listener idempotent** (R-08).
- [ ] **Listener queue-safe** (R-09) jika berat.
- [ ] **Observer thin** (hanya dispatch event, §5.3).
- [ ] **No logic in event handler beyond side-effects declared in listener**.

### 7.4. Snapshot

- [ ] **Snapshot immutable** (R-10): row baru, bukan update.
- [ ] **Fingerprint computed correctly**.
- [ ] **Origin index populated** (no anonymous grant).
- [ ] **Cache invalidation handled**.
- [ ] **Retention policy respected** (per ADR-016).

### 7.5. Builder & Rule

- [ ] **Builder pure function** (§5.2): no DB write, no side-effect.
- [ ] **Provider complete**: contributes to bag, doesn't skip source.
- [ ] **Rule registered**, not inline.
- [ ] **Fact named correctly** (fact registry).
- [ ] **Scope handled** (no global leak).
- [ ] **Time-based evaluated** via TemporalEvaluator.
- [ ] **No duplicated rule** (§5.9).

### 7.6. Context

- [ ] **OrganizationContext populated** before authorization.
- [ ] **Context strongly typed** (§5.7).
- [ ] **All required keys present** before authorize().
- [ ] **Context not mutated after set**.

### 7.7. Policy & Gate

- [ ] **Policy method accepts context** (via constructor).
- [ ] **Policy delegates to Builder**, not reimplementing rule.
- [ ] **Gate thin**: delegates to Policy or Builder.
- [ ] **No `deny` based on string match** outside policy.

### 7.8. Testing

- [ ] **Unit test** untuk Builder / Provider / Fact.
- [ ] **Integration test** untuk full event → snapshot flow.
- [ ] **Authorization test** untuk use case yang baru.
- [ ] **Parity test** jika mengubah Builder behavior.
- [ ] **Regression test** jika memperbaiki bug.
- [ ] **Coverage** untuk kode baru: ≥ 80%.

### 7.9. Performance

- [ ] **No N+1 query**: gunakan `whereIn` atau eager load.
- [ ] **Cache used appropriately**: snapshot via cache, not rebuild on hot path.
- [ ] **No query in Builder**: builder reads via repos/services.
- [ ] **Listener queue**: jika > 100ms, harus queue.
- [ ] **No sync wait**: tidak ada `usleep`, `sleep`, blocking wait.

### 7.10. Audit & Observability

- [ ] **Audit log written** untuk perubahan authorization (R-11).
- [ ] **Metric incremented** jika significant action.
- [ ] **No PII in logs**.
- [ ] **No log of password / token**.

### 7.11. Documentation

- [ ] **ADR updated** jika ada perubahan arsitektur (R-12 + §12).
- [ ] **Inline docs** untuk rule kompleks.
- [ ] **No dead code** (commented-out / unused).
- [ ] **Migration documented** jika ada schema change.

### 7.12. Security

- [ ] **No privilege escalation path** via new code.
- [ ] **No `givePermissionTo`** di non-identity path.
- [ ] **No `RoleMiddleware`** untuk position-based check.
- [ ] **No eval / dynamic include**.
- [ ] **No SQL injection** (selalu pakai Eloquent atau parameterized query).

---

## 8. CI/CD Validation Rules

Validasi otomatis yang harus ada di CI pipeline. CI **wajib gagal** jika salah satu rule ini violated.

### 8.1. Static Analysis Guard

| Rule | Check |
|------|-------|
| **`assignRole()` di Controller** | `grep -r "assignRole" app/Http/Controllers` → 0 results (kecuali di-exclude dengan komentar ADR). |
| **`hasRole()` untuk position** | Custom lint: setiap `hasRole('xxx')` di Controller, value `xxx` harus di whitelist identity list. |
| **`givePermissionTo` di non-identity** | Custom lint: hanya allowed di service dengan `// ADR-021: identity-only` annotation. |
| **Magic string permission** | Custom lint: regex `'[a-z_]+\.[a-z_]+'` di Controller → flagged. |
| **Direct query ke tabel org** | `grep -rE "DB::table\('(gtk_employments|teaching_assignments|...)" app/Http/Controllers` → 0 results. |
| **Service locator pattern** | `grep -rE "\\\\app\\\\(|\\\\resolve\\\\(" app/Authorization` → 0 results. |
| **Inline authorization** | `grep -rE "abort\\(40[13]\\)|throw new AuthorizationDenied" app/Authorization/Builders` → 0 results (harus di policy/exception handler). |
| **Magic number weight** | `grep -rE "weight.*=.*[0-9]+" app/Authorization` → flagged jika bukan dari `Weight::XXX`. |

### 8.2. Test Coverage

| Target | Threshold |
|--------|-----------|
| **Authorization code coverage** | ≥ 80% lines, ≥ 70% branches. |
| **Builder coverage** | ≥ 90% (critical path). |
| **Policy coverage** | ≥ 85%. |
| **Listener coverage** | ≥ 80%. |
| **Event coverage** | ≥ 70% (most are simple DTO). |

CI fails if coverage below threshold.

### 8.3. Parity Test

```bash
php artisan auth:parity:record --output=tests/Fixtures/parity-spatie.json
php artisan auth:parity:verify --input=tests/Fixtures/parity-spatie.json
```

**CI must fail** if parity drift > 0%.

### 8.4. Snapshot Test

```bash
php artisan auth:snapshot:test --diff-zero
```

**CI must fail** if snapshot rebuild output differs from fixture for any tested user.

### 8.5. Architecture Test

Pakai package seperti `pestphp/pest` architecture test atau custom script.

```php
// tests/Architecture/AuthorizationArchitectureTest.php
test('Authorization code tidak query langsung ke DB', function () {
    expect(['App\Authorization\Builders', 'App\Authorization\DTO'])
        ->toNotUse(DB::class);
});
```

### 8.6. Dependency Analysis

- `app/Authorization/Builders/**` must NOT depend on `Illuminate\Http\Request`.
- `app/Authorization/DTO/**` must NOT have `use` statements of any service.
- `app/Authorization/Events/**` must NOT have `use` statements of any service (only Carbon, DTO, primitive).

### 8.7. Naming Convention Check

```bash
php artisan auth:lint:naming
```

CI fails jika:
- Listener tidak ber-suffix `Listener`.
- Builder tidak ber-suffix `Builder` atau `Provider`.
- Service tidak ber-suffix `Service`, `Resolver`, atau `Store`.
- Event tidak past tense.

### 8.8. Migration Validation

```bash
php artisan auth:verify:schema
```

CI fails jika:
- Tabel baru tanpa `user_id` index (untuk performance).
- Snapshot table tanpa `fingerprint` index.
- Migration tanpa `down()` method.

### 8.9. Required Checks Summary

```
✓ Architecture lint (no forbidden patterns)
✓ Naming convention lint
✓ Static analysis (PHPStan level 8)
✓ Test coverage (above threshold)
✓ Parity test (0% drift)
✓ Snapshot test (0% drift)
✓ Architecture test (no forbidden dependency)
✓ Migration validation
✓ All unit tests pass
✓ All feature tests pass
```

**Branch tidak boleh merge jika salah satu gagal.**

---

## 9. Definition of Done

Sebuah fitur authorization dianggap **selesai** hanya jika **semua** kondisi di bawah terpenuhi. Tidak ada pengecualian.

### 9.1. Functional

- [ ] Fitur berjalan sesuai use case.
- [ ] Tidak ada regression pada use case existing.
- [ ] Edge case sudah di-handle (empty state, boundary, invalid input).

### 9.2. Testing

- [ ] Unit test untuk Builder/Provider/Fact baru.
- [ ] Feature test untuk full event flow.
- [ ] Authorization test untuk use case baru.
- [ ] Parity test (jika mengubah Builder).
- [ ] Coverage sesuai threshold (§8.2).
- [ ] No skipped test (`->skip()`) tanpa ADR exception.

### 9.3. Documentation

- [ ] Kode memiliki docblock untuk public method.
- [ ] Jika ada perubahan arsitektur, ADR dibuat dan di-merge.
- [ ] README updated jika ada cara pakai baru.
- [ ] CHANGELOG updated.

### 9.4. Code Review

- [ ] Code review oleh minimal 1 peer + 1 architect.
- [ ] Semua komentar review di-resolve.
- [ ] PR description lengkap (apa, mengapa, bagaimana).
- [ ] Tidak ada TODO / FIXME yang ditinggalkan.

### 9.5. Architecture Compliance

- [ ] Tidak melanggar 12 aturan di §2.
- [ ] Tidak menambah layer baru.
- [ ] Tidak menambah magic string / magic number.
- [ ] Folder structure sesuai §3.
- [ ] Naming sesuai §4.
- [ ] Coding standard sesuai §5.

### 9.6. Observability

- [ ] Metric baru ditambahkan jika significant.
- [ ] Log ditulis untuk audit (R-11).
- [ ] Dashboard updated jika metric baru.
- [ ] Alert rule ditambahkan jika applicable.

### 9.7. Audit Trail

- [ ] Setiap perubahan authorization tercatat di audit log.
- [ ] Origin index populated untuk permission baru.
- [ ] Snapshot rebuilt jika ada perubahan state sumber.

### 9.8. CI/CD

- [ ] Semua CI check pass (§8).
- [ ] No new lint warnings.
- [ ] No new PHPStan errors.

### 9.9. Operational Readiness

- [ ] Runbook updated jika ada prosedur baru.
- [ ] On-call rotation informed jika ada perubahan alerting.
- [ ] Backup strategy updated jika ada data baru.

---

## 10. Architecture Compliance Matrix

Matriks yang menghubungkan setiap prinsip arsitektur ke aturan engineering, code pattern, review checklist, CI validation, dan acceptance criteria.

| Architecture Principle | Engineering Rule | Code Pattern | Review Checklist | CI Validation | Test | Acceptance Criteria |
|------------------------|------------------|--------------|-------------------|---------------|------|---------------------|
| **Architecture First** | R-01..R-12 | §6 | §7.1 | §8.1 | — | Tidak ada file/kelas baru tanpa ADR. |
| **Domain Driven** | §3 folder structure | Namespace sesuai | §7.1 | §8.6 | Architecture test | Class di folder sesuai. |
| **Event Driven** | R-05, R-08, R-09 | Observer thin, Listener queue-safe | §7.3 | §8.1 | Event flow test | Perubahan state → event → listener. |
| **Single Source of Truth** | R-01, R-02 | Spatie roles = identity only | §7.2 | §8.1 | Parity test | Snapshot source = canonical. |
| **Explainable Authorization** | R-11 | Origin index populated | §7.4 | §8.4 | Snapshot test | `whyAllows()` returns full trace. |
| **Context First** | §5.7 | OrganizationContext typed | §7.6 | §8.1 | Context test | Context present before authorize(). |
| **Backward Compatible** | Parity test | Spatie + Builder dual | — | §8.3 | Parity 0% drift | Existing flows unchanged. |
| **No Hidden Logic** | §5.9 | No duplicated rule | §7.5 | §8.1 | Rule test | One rule per concept. |
| **Explicit over Implicit** | R-03, §5.8 | Registry-driven | §7.2 | §8.1 | Naming test | No magic strings. |
| **Convention over Configuration** | §3, §4 | Laravel standard | §7.1 | §8.7 | — | Folder & naming standard. |

**Cara baca matriks:**
- Setiap baris = 1 prinsip.
- Setiap kolom = mekanisme kontrol.
- Jika sel kosong → mekanisme kontrol belum ada (tambahkan!).

---

## 11. Technical Debt Policy

### 11.1. Kapan Boleh Memakai Workaround

Workaround **hanya** boleh dipakai jika:

1. Mendesak (production blocker).
2. Root cause tidak bisa diperbaiki dalam sprint yang sama.
3. Sudah di-discuss dengan lead dan ada ADR Sementara.

**Workaround tidak boleh dipakai jika:**
- Alasan: "lebih cepat".
- Alasan: "tidak ada waktu".
- Alasan: "sudah terlanjur".

### 11.2. Cara Mencatat Technical Debt

Setiap technical debt harus:

1. **Dicatat di file** `TECHNICAL_DEBT.md` di root repository.
2. **Format:**

```markdown
## TD-001 — Title
**Date:** YYYY-MM-DD
**Owner:** @username
**Priority:** P1 | P2 | P3
**SLA:** YYYY-MM-DD
**Description:** [Apa yang jadi workaround]
**Reason:** [Mengapa belum diperbaiki]
**Acceptance Criteria:** [Kapan bisa di-close]
**ADR Reference:** ADR-NNN (jika ada)
```

3. **Issue tracking:** Buat issue di repository dengan label `tech-debt`.

### 11.3. SLA Penyelesaian

| Priority | SLA | Contoh |
|----------|-----|--------|
| **P1** | 7 hari | Security workaround. |
| **P2** | 30 hari | Performance workaround. |
| **P3** | 90 hari | Code smell / minor inconsistency. |

Lewat SLA → masuk sprint planning berikutnya sebagai prioritas.

### 11.4. Prioritas

- **P1:** Wajib di-fix dalam 1 sprint.
- **P2:** Dijadwalkan dalam 2 sprint.
- **P3:** Backlog. Direview tiap quarter.

### 11.5. Siapa yang Boleh Menyetujui Pengecualian

| Jenis | Approver |
|-------|----------|
| Workaround baru | Tech Lead |
| Lewat SLA P1/P2 | Architect + Tech Lead |
| Lewat SLA P3 | Tech Lead |
| Pengecualian aturan §2 | **Architect + Product Owner** (wajib ADR) |

**Tidak ada** pengecualian aturan §2 tanpa ADR yang disetujui.

---

## 12. ADR Governance

### 12.1. Kapan ADR Wajib Dibuat

ADR wajib dibuat (atau existing ADR di-revise) jika perubahan termasuk salah satu:

1. **Perubahan arsitektur** — layer baru, pattern baru, struktur folder berubah.
2. **Perubahan domain model** — entity baru, relasi baru, field baru.
3. **Perubahan authorization flow** — alur authorization berubah (gate, policy, middleware).
4. **Perubahan event utama** — event baru, listener baru, signature berubah.
5. **Perubahan snapshot strategy** — cara compute fingerprint, retention, storage.
6. **Perubahan rule engine** — rule baru, fact baru, operator baru.
7. **Perubahan context** — context key baru, sumber data baru.
8. **Pengecualian aturan §2** — workaround resmi.
9. **Penggunaan dependency baru** — package baru.
10. **Perubahan retention policy** — durasi simpan snapshot, archive strategy.

### 12.2. Format ADR

```markdown
# ADR-NNN — Title

**Status:** Proposed | Accepted | Superseded by ADR-XXX | Deprecated
**Date:** YYYY-MM-DD
**Deciders:** @architect, @techlead, ...
**Supersedes:** ADR-XXX (if applicable)

## Context
[Apa masalah / situasi yang membutuhkan keputusan]

## Decision
[Keputusan yang diambil]

## Consequences
**Positive:**
- ...

**Negative:**
- ...

**Neutral:**
- ...

## Alternatives Considered
- ...

## Implementation Notes
- ...

## Rollback Plan
- ...
```

### 12.3. Proses Persetujuan

```
1. Author membuat ADR dengan status "Proposed"
2. Author presentasi di Architecture Review (mingguan)
3. Diskusi terbuka
4. Keputusan: Accepted / Rejected / Needs Revision
5. Jika Accepted: ADR di-merge, implementasi dimulai
6. ADR adalah sumber kebenaran, update jika implementasi menyimpang
```

### 12.4. Daftar ADR Aktif (binding)

Semua ADR berikut **wajib** dipatuhi oleh seluruh implementasi. Lihat `authorization-domain-model.md` dan `authorization-architecture-validation.md` untuk daftar lengkap 25 ADR.

---

## 13. Engineering Playbook

Panduan operasional untuk developer yang akan menambahkan elemen authorization baru.

### 13.1. Menambah Permission Baru

**Kapan:** Bisnis butuh fitur yang butuh permission baru (mis. `gtk.izin.export`).

**Langkah:**

1. **Daftarkan di PermissionRegistry.**
   ```php
   // app/Authorization/Registry/PermissionRegistry.php
   public const GTK_IZIN_EXPORT = 'gtk.izin.export';
   ```

2. **Buat Rule (jika logic non-trivial).**
   ```php
   // app/Authorization/Rules/GtkIzinExportRule.php
   class GtkIzinExportRule implements RuleInterface {
       public function evaluate(...): bool { ... }
   }
   ```

3. **Daftarkan Rule di RuleRegistry.**
   ```php
   RuleRegistry::register('gtk.izin.export', GtkIzinExportRule::class);
   ```

4. **Buat Policy (jika ada resource).**
   ```php
   // app/Authorization/Policies/IzinPolicy.php
   class IzinPolicy {
       public function export(User $user): bool {
           return $user->effectivePermissions()->allows(
               PermissionRegistry::GTK_IZIN_EXPORT,
               $this->context,
           );
       }
   }
   ```

5. **Update Gate (jika dipakai di route middleware).**
   ```php
   // app/Providers/AuthServiceProvider.php
   Gate::define(PermissionRegistry::GTK_IZIN_EXPORT, [IzinPolicy::class, 'export']);
   ```

6. **Test.**
   - Unit test untuk Rule.
   - Integration test untuk Policy.
   - Feature test untuk endpoint.

7. **Update dokumentasi** (permission list).

---

### 13.2. Menambah Context Baru

**Kapan:** Bisnis butuh dimension baru di authorization (mis. `jurusan_id`).

**Langkah:**

1. **Tambah field di OrganizationContext.**
   ```php
   public readonly ?JurusanId $jurusanId;
   ```

2. **Update ContextResolver** untuk extract dari request.

3. **Update ScopeMatcher** untuk include dalam scope matching.

4. **Update PermissionRegistry** jika ada permission khusus jurusan.

5. **Update rule** yang menggunakan context.

6. **Test semua kombinasi**.

7. **Update ADR** jika ini dimension baru (ADR-NNN: Context Jurusan).

---

### 13.3. Membuat Assignment Baru

**Kapan:** Tabel assignment baru (mis. `gtk_pembina_eskul` untuk pembina eskul).

**Langkah:**

1. **Buat migration tabel** sesuai naming convention.

2. **Buat model + factory**.

3. **Buat PositionSource implementation.**
   ```php
   // app/Authorization/Builders/EskulPembinaProvider.php
   class EskulPembinaProvider implements PositionSource {
       public function contribute(PermissionBag $bag, User $user, ?OrganizationContext $context): PermissionBag {
           $assignments = GtkPembinaEskul::activeFor($user->id);
           foreach ($assignments as $a) {
               $bag->addScopedPermission(
                   PermissionRegistry::ESKUL_MANAGE,
                   scope: ['eskul_id' => $a->eskul_id],
                   origin: new Origin('GtkPembinaEskul', $a->id, 'pembina', 5),
               );
           }
           return $bag;
       }
   }
   ```

4. **Daftarkan Provider di EffectivePermissionBuilder.**

5. **Buat Event.**
   ```php
   final class EskulPembinaAssigned {
       public function __construct(
           public readonly string $userId,
           public readonly string $eskulId,
       ) {}
   }
   ```

6. **Buat Observer** (thin) + Listener (idempotent).

7. **Tambah test fixtures** untuk use case baru.

8. **Tambah unit test** untuk Provider.

9. **Tambah feature test** untuk event flow.

10. **Update domain model doc** jika ini sumber baru.

---

### 13.4. Membuat Event Baru

**Kapan:** Ada perubahan state baru yang butuh memicu rebuild.

**Langkah:**

1. **Buat class event** di `app/Authorization/Events/` dengan property readonly.

2. **Tentukan listener yang handle** (biasanya RebuildPermissionsListener).

3. **Dispatch dari observer** atau service.

4. **Test:**
   - Event dispatch test.
   - Listener handle test.
   - Snapshot rebuild test.

5. **Update ADR** jika event ini signifikan.

---

### 13.5. Membuat Listener Baru

**Kapan:** Butuh reaksi spesifik yang tidak ter-handle existing listener.

**Langkah:**

1. **Cek dulu** apakah RebuildPermissionsListener / DetectConflictsListener sudah cukup.

2. **Jika tidak**, buat listener baru dengan:
   - Implements `ShouldQueue` jika berat.
   - `handle()` idempotent.
   - Constructor injection.

3. **Daftarkan di EventServiceProvider** atau auto-discovery.

4. **Test listener.**

---

### 13.6. Membuat Snapshot Baru

**Kapan:** Tipe snapshot baru (mis. untuk entity selain user).

**Langkah:**

1. **Tambah kolom di permission_snapshots table** atau buat tabel baru.

2. **Tambah model + factory**.

3. **Tambah SnapshotStore method** untuk entity baru.

4. **Tambah Service method** untuk rebuild.

5. **Test fixtures + parity**.

6. **Update ADR** jika signifikan.

---

### 13.7. Membuat Policy Baru

**Kapan:** Resource baru yang butuh authorization (mis. `RaporPolicy`).

**Langkah:**

1. **Buat class** di `app/Authorization/Policies/`.

2. **Setiap method** yang authorize melakukan:
   ```php
   return $user->effectivePermissions()->allows(
       PermissionRegistry::XXX,
       $this->context->with(['key' => $value]),
   );
   ```

3. **Daftarkan di Gate** jika dipakai di route middleware.

4. **Test semua skenario** (allow + deny + edge case).

---

### 13.8. Decision Tree: Di Mana Taruh Kode Ini?

```
Ada perubahan state organisasi
  → Event
  → Listener (idempotent)
  → Snapshot rebuild

Ada decision "boleh atau tidak"
  → Policy
  → Delegate to Builder
  → Use Registry constants

Ada context baru
  → OrganizationContext field
  → ContextResolver extract
  → ScopeMatcher include

Ada rule baru
  → RuleRegistry::register
  → Implement RuleInterface
  → Test

Ada query data authorization
  → Service
  → Never directly from Controller
  → Use Provider/Repository
```

---

## 14. Architecture Compliance Assessment

Evaluasi terhadap seluruh dokumen arsitektur yang telah dibuat, untuk memastikan tidak ada kontradiksi atau area yang belum memiliki standar.

### 14.1. Konsistensi Antar-Dokumen

| Dokumen | Konsisten dengan Constitution? | Catatan |
|---------|-------------------------------|---------|
| `authorization-domain-model.md` | ✅ Ya | Layer 1-10 selaras dengan §3 folder. |
| `authorization-governance-operational-architecture.md` | ✅ Ya | SLA, observability, runbook selaras. |
| `authorization-architecture-validation.md` | ✅ Ya | 15 skenario + 8 use case flow selaras dengan §6. |
| ADR-001..ADR-025 (proposed) | ✅ Ya | Tidak ada yang bertentangan. |

### 14.2. Kontradiksi yang Ditemukan

| # | Kontradiksi | Lokasi | Resolusi |
|---|-------------|--------|----------|
| (none found) | — | — | — |

### 14.3. Duplikasi yang Ditemukan

| # | Duplikasi | Lokasi | Resolusi |
|---|-----------|--------|----------|
| D-01 | "No direct DB query" disebut di banyak dokumen. | Domain model §13 + Constitution §2 R-04 | Constitution adalah reference. Domain model referensi silang ke Constitution. |
| D-02 | Snapshot retention policy 90 hari di domain model, ADR-016 (per validation doc) 1 tahun. | Domain model §8.4 vs Validation §8.3 | **ADR-016 yang berlaku (1 tahun untuk non-archived).** |

### 14.4. Area yang Belum Memiliki Standar Implementasi

| Area | Status |
|------|--------|
| **Delegation chain detection** | Standar di §13.3 (provider), belum ada threshold policy. → Rekomendasi: tambah ADR untuk max delegation depth = 2. |
| **Conflict resolution UI** | Belum ada standar UI. → Rekomendasi: ADR untuk UI pattern di Phase 7. |
| **Snapshot archival procedure** | ADR-016 menentukan retention, belum ada prosedur cleanup. → Rekomendasi: tambah CLI command `auth:snapshot:archive` + runbook. |
| **Permission versioning** | Rule versioning ada di domain model §10.6, belum ada mekanisme migrasi rule lama. → Rekomendasi: ADR rule migration. |
| **Audit log retention** | Domain model tidak menentukan durasi simpan audit log. → Rekomendasi: ADR audit retention = 7 tahun (compliance). |
| **Performance regression test** | §8.3 parity test, belum ada performance regression baseline. → Rekomendasi: tambah CI step `auth:benchmark`. |

### 14.5. Area yang Terlalu Kompleks

| Area | Kompleksitas | Rekomendasi |
|------|-------------|-------------|
| **Rule engine dengan 15 facts + DB rules + versioning** | Tinggi | Implementasikan 5 facts di Phase 3, tambah fakta lain per use case. Jangan preload semua. |
| **Conflict detector 7 categories** | Tinggi | Implementasi Phase 4: 3 kategori saja (identity, position, assignment). Sisanya belakangan. |
| **5-tier graceful degradation** | Medium | Implementasi 3 tier (deny / partial / full). Tier 4-5 hanya dokumentasi, tidak ada code. |
| **Multi-school mode** | Tinggi (deferred) | Jangan aktifkan flag di v1. Schema sudah siap. |
| **Acting position + delegation** | Tinggi | Implementasi bertahap: Acting dulu (Phase 6), delegation belakangan (Phase 8). |

### 14.6. Area Berpotensi Technical Debt

| Area | Risiko | Mitigasi |
|------|--------|----------|
| **Rule engine learning curve** | Developer baru tidak paham | Training session + cheatsheet di playbook. |
| **Builder complexity** | Sulit di-debug | Tambah CLI `auth:trace` dari awal. |
| **Parity test fragility** | False positive saat Spatie update | Pin Spatie version + rebuild parity fixture on upgrade. |
| **Origin index growth** | Snapshot jadi besar | Cap origin count, archive old origins. |
| **Listener queue saturation** | Year rollover overwhelm | Priority queue + chunking. |
| **Audit log volume** | Storage membengkak | Retention policy + archive strategy. |

### 14.7. Rekomendasi Tanpa Menambah Kompleksitas

Berikut rekomendasi untuk menjaga konsistensi tanpa menambah layer atau fitur baru.

| # | Rekomendasi | Tujuan |
|---|------------|--------|
| RC-01 | **Buat ADR untuk max delegation depth = 2** (A→B→C ditolak). | Cegah delegation abuse. |
| RC-02 | **Tambah CLI `auth:permission:list`** di Phase 3. | Memudahkan developer list semua permission. |
| RC-03 | **Tambah CLI `auth:rule:test`** di Phase 3. | Memudahkan admin test rule sebelum deploy. |
| RC-04 | **Tambah `audit_retention_days = 2555` (7 tahun) di config authorization.** | Compliance-ready tanpa ADR baru. |
| RC-05 | **Buat `ARCHITECTURE_GUARDRAILS.md` index** yang link ke constitution + ADRs. | Onboarding developer lebih cepat. |
| RC-06 | **Tambah CI step `auth:permission:coverage`** — list semua permission dan cek ada test untuk masing-masing. | Cegah untested permission. |
| RC-07 | **Tambah CLI `auth:snapshot:archive`** (cron-ready) di Phase 5. | Operational tool tanpa layer baru. |
| RC-08 | **Buat `RULE_ENGINE_CHEATSHEET.md`** yang berisi 5 rule paling umum. | Onboarding developer. |

**Tidak ada rekomendasi di atas yang menambah layer, namespace, atau fitur baru.** Semuanya adalah CLI, ADR, atau dokumen pendukung.

---

## Penutup

### Ringkasan

Dokumen ini adalah **konstitusi implementasi**. Setelah disetujui:

1. ✅ **Tidak ada lagi** penambahan dokumen desain.
2. ✅ **Tidak ada lagi** penambahan layer baru.
3. ✅ **Tidak ada lagi** redesign authorization.
4. ✅ **Semua perubahan** selanjutnya hanya boleh melalui ADR.

### Lokasi Dokumen

`docs/authorization-engineering-constitution.md`

### Referensi Wajib

- `docs/authorization-domain-model.md` — arsitektur.
- `docs/authorization-governance-operational-architecture.md` — operasional.
- `docs/authorization-architecture-validation.md` — validasi.
- 25 ADR — keputusan arsitektur.
- `docs/authorization-engineering-constitution.md` — **dokumen ini**.

### Setelah Approval

Langkah selanjutnya:
1. **Phase 0** (3 hari): Tulis 6 ADR baru (016, 021, 022, 023, 024, 025).
2. **Phase 1** (1 minggu): Schema + scaffolding.
3. **Phase 2-6** sesuai roadmap di validation doc.

Target: **kode yang berjalan DAN konsisten dengan arsitektur selama bertahun-tahun**.

> END OF CONSTITUTION

---

## Lampiran A — Quick Reference Card

Tempelkan di setiap repo / wiki / onboarding doc:

```
┌──────────────────────────────────────────────────────────────────────────┐
│  ALIM AUTHORIZATION — ENGINEERING CONSTITUTION QUICK REFERENCE          │
│                                                                          │
│  📖 Full: docs/authorization-engineering-constitution.md                 │
│                                                                          │
│  ✅ DO                              ❌ DON'T                              │
│  ─────                              ──────────                            │
│  $user->can(PermissionRegistry::X)  $user->can('string')                  │
│  via Policy                          inline check                         │
│  via Gate::authorize                 direct query                         │
│  event() on state change             assignRole() in Controller          │
│  upsert snapshot                     update snapshot                      │
│  constructor injection               app() / resolve()                    │
│  readonly properties                 mutable DTO                          │
│  ShouldQueue for heavy               sync listener for heavy              │
│  Builder pure                        Builder with side-effect             │
│                                                                          │
│  📍 Folder: app/Authorization/{Builders,Policies,Events,...}            │
│  📍 Test:   tests/{Unit,Feature}/Authorization/                          │
│  📍 Doc:    docs/authorization-engineering-constitution.md                │
│                                                                          │
│  Questions? → Tech Lead or Architect                                    │
│  Need exception? → Open ADR first                                       │
└──────────────────────────────────────────────────────────────────────────┘
```

---

> Dokumen ini adalah dokumen terakhir fase desain.
> Setelahnya: implementasi sesuai Phase 0 → Phase 6 dalam validation roadmap.
> Setiap baris kode yang melawan dokumen ini adalah bug — meskipun ia berjalan.