---
name: authorization-domain-model
description: Final authorization architecture — domain model, resolution flow, context-aware, time-based, delegation, rule engine, multi-school, audit
metadata:
  type: project
---

# Authorization Domain Model — Final Architecture

> Revision date: 2026-06-29
> Status: **Final review — awaiting approval**
> Supersedes: `org-driven-authorization-audit.md` & `org-driven-authorization-architecture-revision.md`

---

## Daftar Isi

1. Visi & Prinsip Arsitektur
2. Authorization Domain Model
3. Authorization Resolution Flow
4. Context-Aware Authorization
5. Time-Based Authorization
6. Delegation & Acting Position
7. Effective Permission Builder (Explainable)
8. Permission Snapshot Strategy & Audit
9. Conflict Detection
10. Authorization Rule Engine
11. Multi-School Readiness
12. Authorization Graph
13. Compatibility Review (Spatie, Laravel Gate/Policy, Middleware)
14. Risiko & Trade-off
15. Roadmap Implementasi Bertahap

---

## 1. Visi & Prinsip Arsitektur

ALIM membutuhkan authorization system yang:

| Prinsip | Definisi |
|---------|----------|
| **Organization Driven** | Sumber kebenaran tunggal adalah struktur organisasi, bukan tabel role. |
| **Context Aware** | Permission bukan hanya nama — dia punya konteks (school, study_group, subject, academic_year). |
| **Event Driven** | Setiap perubahan organisasi memicu re-evaluasi permission secara otomatis. |
| **Explainable** | Setiap permission dapat dijelaskan sumbernya (origin). Admin dapat menjawab “mengapa user X punya akses Y?”. |
| **Auditable** | Snapshot bukan sekadar cache, melainkan trail historis yang dapat dilacak ke event pemicu. |
| **Scalable** | Mendukung pertumbuhan ke multi-unit (SD/SMP/SMA/SMK/Pesantren). |
| **Future Proof** | Mudah beradaptasi dengan modul-modul baru yang akan datang. |
| **Open Rule Engine** | Permission baru dapat dideklarasikan via rule, tanpa menulis controller code. |

---

## 2. Authorization Domain Model

### 2.1. Definisi Layer

```
┌──────────────────────────────────────────────────────────────────────┐
│ LAYER 1 — IDENTITY (WHO)                                              │
│ Siapakah dia? Stabil sepanjang hidup.                                │
│                                                                       │
│   User                                                                 │
│   └─ identity_kind (Super Admin|GTK|Peserta Didik|Wali Santri|       │
│                     Alumni|Guest)                                    │
└──────────────────────────────┬────────────────────────────────────────┘
                               │
┌──────────────────────────────▼────────────────────────────────────────┐
│ LAYER 2 — PROFILE (WHAT IS HE)                                        │
│ Profil personal: GTKProfile, StudentProfile, ParentProfile           │
│                                                                       │
│   GtkProfile     (nik, ttl, jenis_kelamin, status_aktif, dll.)       │
│   StudentProfile (nis, status_pd, tahun_masuk, dst.)                 │
└──────────────────────────────┬────────────────────────────────────────┘
                               │
┌──────────────────────────────▼────────────────────────────────────────┐
│ LAYER 3 — EMPLOYMENT & PRIMARY POSITION                              │
│ Jabatan utama yang melekat secara legal/formal.                      │
│                                                                       │
│   GtkEmployment                                                       │
│   ├─ position_type (organizational position code)                     │
│   ├─ status_kepegawaian (PNS|HONORER|KONTRAK|PHKI|...)              │
│   ├─ school_id                                                         │
│   └─ academic_year_id                                                 │
└──────────────────────────────┬────────────────────────────────────────┘
                               │
┌──────────────────────────────▼────────────────────────────────────────┐
│ LAYER 4 — ADDITIONAL POSITION                                          │
│ Jabatan tambahan / struktural yang dipegang.                         │
│                                                                       │
│   GtkWorkUnit                (penugasan unit, is_primary)            │
│   GtkCareerPath              (jabatan fungsional, tmt/tst)           │
└──────────────────────────────┬────────────────────────────────────────┘
                               │
┌──────────────────────────────▼────────────────────────────────────────┐
│ LAYER 5 — ASSIGNMENT (RESOURCE-SCOPED)                                │
│ Tugasan yang konteksnya spesifik terhadap resource.                  │
│                                                                       │
│   TeachingAssignment         (subject+rombel+year)                    │
│   StudyGroup.homeroom_teacher  (homeroom)                              │
│   StudyGroupPlacement        (student → study_group)                  │
│   GtkAdditionalTask          (tugas tambahan decree-based)            │
└──────────────────────────────┬────────────────────────────────────────┘
                               │
┌──────────────────────────────▼────────────────────────────────────────┐
│ LAYER 6 — TEMPORARY ASSIGNMENT                                         │
│ Penugasan sementara dengan TTL.                                      │
│                                                                       │
│   DelegatedAssignment         (delegation to other user)              │
│   ActingPositionAssignment   (PLH/PLT sementara)                     │
└──────────────────────────────┬────────────────────────────────────────┘
                               │
┌──────────────────────────────▼────────────────────────────────────────┐
│ LAYER 7 — REVOCATION                                                   │
│ Pencabutan permission eksplisit (override).                          │
│                                                                       │
│   UserRevokedPermission                                          │
│   EffectiveRevocationPolicy                                          │
└──────────────────────────────┬────────────────────────────────────────┘
                               │
┌──────────────────────────────▼────────────────────────────────────────┐
│ LAYER 8 — EFFECTIVE PERMISSION                                        │
│ Builder menghasilkan:                                                 │
│                                                                       │
│   {                                                                  │
│     global_permissions: string[],                                    │
│     scoped_permissions: [{permission, scope, valid_from,             │
│                           valid_until, origin}],                      │
│     origin_index: { 'rapor.generate' => ['GTK.employment',         │
│                                          'homeroom_assignment'], }   │
│     fingerprint: string,                                             │
│     computed_at: datetime,                                           │
│   }                                                                  │
└──────────────────────────────┬────────────────────────────────────────┘
                               │
┌──────────────────────────────▼────────────────────────────────────────┐
│ LAYER 9 — POLICY / GATE / MIDDLEWARE                                  │
│                                                                     │
│   Gate (Laravel)        ─→ fast boolean check, no DB                │
│   Policy               ─→ resource-aware decision                   │
│   Sidebar Composer     ─→ menu visibility                          │
│   Menu/Fitur Resource   ─→ UI rendering                             │
└──────────────────────────────┬────────────────────────────────────────┘
                               │
┌──────────────────────────────▼──────────────────��─────────────────────┐
│ LAYER 10 — FEATURE / API / ACTION                                     │
│                                                                     │
│   Fitur UI (page-level can)                                          │
│   API endpoint (api-level can)                                       │
│   Business Action (action-level can, sering di Policy)                │
└──────────────────────────────────────────────────────────────────────┘
```

### 2.2. Definisi Tiap Layer

#### Layer 1 — Identity
`User` + `identity_kind` adalah **identifier kategori primer**. Tidak pernah berubah kecuali user terdaftar ulang dengan identitas berbeda (mis. alumni yang jadi GTK lagi, dalam hal ini biasanya membuat user baru atau user lama di-retire + baru).

#### Layer 2 — Profile
Profil personal. Saat ini memberi kontribusi ringan ke permission (mis. GTK cuti tidak boleh dapat penilaian mengajar). Ke depan: profil bisa menentukan status “probation” yang membatasi permission.

#### Layer 3 — Employment & Primary Position
`GtkEmployment` adalah sumber **primary position**. Primary position menentukan:
- Jabatan organisasi (referensi ke config `positions.php`)
- Sekolah (school_id)
- Tahun ajaran (academic_year_id)
- Status kepegawaian (PNS/HONORER/dll.)

#### Layer 4 — Additional Position
- `GtkWorkUnit`: penugasan unit organisasi (jurusan, program, divisi). Satu primary, sisanya secondary.
- `GtkCareerPath`: jabatan fungsional historis (guru pertama → guru muda → guru madya → guru besar).

#### Layer 5 — Assignment
**Resource-scoped** permissions:
- `TeachingAssignment(subject, study_group, academic_year)` → permission terhadap `(subject, study_group, year)`.
- `StudyGroup.homeroom_teacher_id` → permission wali kelas `(study_group, year)`.
- `StudyGroupPlacement(student → study_group)` → student ownership terhadap study_group.
- `GtkAdditionalTask(decree, tmt, tst)` → tugas tambahan (Koordinator Tahfidz, Pembina Asrama, dll.).

#### Layer 6 — Temporary Assignment
- **Delegation**: User A mendelegasikan sebagian permission ke User B (mis. Wakil Kepala Sekolah mendelegasikan approval rapor ke Kepala Program).
- **Acting Position (PLH/PLT)**: User C menjadi Plt Kepala Sekolah selama Kepala Sekolah cuti.

#### Layer 7 — Revocation
Eksplisit mencabut permission walau sumber lain memberikan. Mis. GTK yang sedang cuti panjang → semua permission mengajar dicabut meski teaching assignment masih aktif.

#### Layer 8 — Effective Permission
Builder hasil akhir. Bersifat **explainable** (origin tracked).

#### Layer 9 — Gate / Policy / Middleware
Pakai `Gate` Laravel untuk boolean check. `Policy` untuk resource-scoped. Middleware untuk route-level filter.

#### Layer 10 — Feature / API / Action
Setiap fitur / endpoint / action bisnis punya **izin** yang harus dideklarasikan. Deklarasi izin via Rule Engine (Lihat §10).

---

## 3. Authorization Resolution Flow

Contoh: **Pak Budi (User)** membuka halaman **Input Nilai Sumatif**.

### 3.1. Tahap 1 — Authentication
- Pak Budi login via `/login`.
- Sistem memvalidasi kredensial via `Auth::attempt`.
- User terautentikasi; token sesi tercipta.

### 3.2. Tahap 2 — Identity Validation
- Middleware `Authenticate` memverifikasi sesi valid.
- Middleware `EnsureEmployeeAccess` memverifikasi `user.identity_kind` ada (Super Admin, GTK, dll.) — Guest ditolak.

### 3.3. Tahap 3 — Load Effective Permission Snapshot
- Middleware `ResolveEffectivePermission`:
  ```php
  $snapshot = PermissionSnapshotResolver::resolve($user);
  // orqanizationContext binds ke Request (school_id, academic_year_id)
  ```
- Snapshot di-load dari cache (`Cache::tags(['perms:'.$userId])`).
- Jika expired (fingerprint mismatch atau TTL elapsed) → rebuild via `RebuildPermissionsListener::dispatchSync()`.

### 3.4. Tahap 4 — Request Context Resolution
- Middleware `OrganizationContext`:
  ```php
  $request->setContext([
      'school_id'       => resolve from session or URL,
      'academic_year_id'=> active academic year,
      'study_group_id'  => from URL parameter,
      'subject_id'      => from URL parameter,
  ]);
  ```
- Untuk Super Admin: context bisa dipilih manual via dropdown.

### 3.5. Tahap 5 — Organization & Assignment Resolution
- Controller (atau Service) meminta resolver:
  ```php
  $context = $request->organizationContext();
  $effective = $user->effectivePermissions();
  $resolved = $effective->resolveFor($context);
  ```

### 3.6. Tahap 6 — Gate Evaluation
- `Gate::allows('nilai.input', [StudyGroup $rombel, Subject $mapel])`:
  - Gate `nilai.input` mendelegasikan ke `Policy\NilaiPolicy::input($user, $rombel, $mapel)`.
  - Policy menanyakan ke `EffectivePermission`:
    ```php
    return $user->effectivePermissions()->hasScoped(
        'nilai.input',
        scope: ['subject_id' => $mapel->id, 'study_group_id' => $rombel->id],
        context: $context,
    );
    ```

### 3.7. Tahap 7 — Controller Logic
- Controller `NilaiController@input`:
  ```php
  $this->authorize('nilai.input', [$rombel, $mapel]);
  $scores = $nilaiService->fetch($rombel, $mapel, $context);
  return view('nilai.input', compact('scores'));
  ```

### 3.8. Tahap 8 — Sidebar Visibility
- `SidebarComposer` memfilter menu berdasarkan `$user->effectivePermissions()`:
  ```php
  $menus = $menuConfig->filter(fn($node) =>
      $user->effectivePermissions()->canAccess($node->required_permission, $context)
  );
  ```

### 3.9. Tahap 9 — Audit Log
- Setiap authorize() call bisa dicatat via `AuthorizationAuditService::logDecision(...)` (kecuali kalau `AUDIT_VERBOSE=false`).

### 3.10. Tahap 10 — Cache Refresh Trigger (eventual)
- Jika underlying data berubah (mis. Pak Budi dimutasi), event listener bust cache. Sampai saat itu, snapshot tetap valid.

### 3.11. Diagram Sequence

```
┌────────┐         ┌──────────┐         ┌──────────────┐         ┌──────────┐
│Browser │ ─req─→ │Laravel   │ ─load─→ │Snapshot      │ ─miss─→ │Builder   │
│(Budi)  │        │Middleware│        │Resolver      │         │(rebuild) │
└────────┘        └────┬─────┘        └──────┬───────┘        └────┬─────┘
                        │                    │                     │
                        │ set context        │ snapshot valid      │
                        │                    │                     │
                        │   authorize('nilai.input', ...)          │
                        │ ──────────────────────────────────────────┘
                        ▼
                   ┌────────────┐
                   │ Gate       │ ─→ Policy ─→ EffectivePermissions
                   └──────┬─────┘
                          │ result: true/false + origin
                          ▼
                   ┌────────────┐
                   │ Controller │ ─→ Service ─→ DB
                   └────────────┘
```

---

## 4. Context-Aware Authorization

### 4.1. Prinsip: Permission ≠ Boolean

Permission name seperti `nilai.input` saja tidak cukup. Yang dibutuhkan adalah: **siapa boleh melakukan apa, dalam konteks apa**.

### 4.2. Context Sources

Context yang dipakai untuk memutuskan permission:

| Context Key | Sumber | Contoh |
|-------------|--------|--------|
| `school_id` | GtkEmployment, session, URL | Hanya GTK sekolah A boleh input nilai di sekolah A. |
| `academic_year_id` | URL param, session | Permission mengajar hanya aktif untuk tahun ajaran 2026/2027. |
| `semester` | URL param | Hanya semester ganjil/genap yang aktif. |
| `study_group_id` | URL param | Guru A boleh input nilai untuk rombel XII-1. |
| `subject_id` | URL param | Guru A boleh input Matematika tapi bukan Bahasa Indonesia. |
| `student_id` | URL param | Wali kelas boleh lihat data student miliknya. |
| `homeroom_id` | derived from student.study_group | Wali kelas lihat semua student di homeroomnya. |
| `assignment_id` | optional, untuk audit | Untuk tracing ke TeachingAssignment tertentu. |
| `time_window` | now | Untuk validasi `valid_from <= now < valid_until`. |

### 4.3. Resolution API

#### Method 1: Resource-Scoped Boolean
```php
$user->effectivePermissions()->allows(
    permission: 'nilai.input',
    context: [
        'school_id'        => $schoolId,
        'academic_year_id' => $yearId,
        'study_group_id'   => $rombel->id,
        'subject_id'       => $mapel->id,
    ],
);
```

#### Method 2: Origin-Aware Trace
```php
$user->effectivePermissions()->whyAllows(
    'nilai.input',
    context: $ctx,
);
// returns:
[
    ['source' => 'TeachingAssignment', 'id' => $ta->id, 'weight' => 5],
    ['source' => 'Identity.gtk', 'id' => null, 'weight' => 1],
    ['source' => 'HomeroomAssignment', 'id' => null, 'weight' => 3, 'note' => 'inherited via assignment'],
];
```

#### Method 3: Scope Query
```php
$user->effectivePermissions()->scopesFor('nilai.input');
// returns:
[
    ['scope' => ['subject_id' => 'subj-1', 'study_group_id' => 'rg-1'], 'valid_until' => '2027-06-30'],
    ['scope' => ['subject_id' => 'subj-2', 'study_group_id' => 'rg-1'], 'valid_until' => '2027-06-30'],
];
```

### 4.4. Bagaimana Gate & Policy Memperoleh Context

#### Pattern 1: Explicit Context via Middleware
```php
Route::middleware(['auth', 'org.context', 'effective.permission'])
    ->post('/nilai/input/{rombel}/{mapel}', [NilaiController::class, 'input']);
```

Middleware `org.context`:
```php
public function handle($request, Closure $next)
{
    $request->setOrganizationContext([
        'school_id'        => $request->route('schoolId') ?? session('school_id'),
        'academic_year_id' => $request->route('yearId') ?? active_academic_year()->id,
        'study_group_id'   => $request->route('rombel'),
        'subject_id'       => $request->route('mapel'),
    ]);
    return $next($request);
}
```

#### Pattern 2: Context via Policy Constructor
```php
class NilaiPolicy
{
    public function __construct(protected OrganizationContext $context) {}

    public function input(User $user, StudyGroup $rombel, Subject $mapel): bool
    {
        return $user->effectivePermissions()->allows(
            permission: 'nilai.input',
            context: $this->context->with([
                'study_group_id' => $rombel->id,
                'subject_id'     => $mapel->id,
            ]),
        );
    }
}
```

#### Pattern 3: Auto-Inference from Route Model
Middleware otomatis extract context dari route model binding (`{rombel}`, `{mapel}`) → `OrganizationContext::fromRequest($request)`.

### 4.5. Implicit vs Explicit Scope

Permission **tanpa scope** (`can('gtk.coordinate')`) berarti global — boleh untuk konteks apapun.
Permission **dengan scope** (`can('nilai.input', [...])`) artinya scope harus match.

---

## 5. Time-Based Authorization

### 5.1. Prinsip

Setiap source of permission (Position, Assignment, Temporary) membawa `valid_from` dan `valid_until`. Permission hanya aktif jika `valid_from <= now < valid_until`.

### 5.2. Sumber dengan Validitas Temporal

| Sumber | Field Validitas | Default |
|--------|-----------------|---------|
| `GtkEmployment` | `tmt`, `tst` | tmt=created_at, tst=null (open) |
| `GtkWorkUnit` | `tmt`, `tst` | open |
| `GtkCareerPath` | `tmt`, `tst` | open |
| `TeachingAssignment` | `academic_year_id` (start/end) + soft `is_active` | year-bounded |
| `StudyGroup.homeroom_teacher_id` | `academic_year_id` of study_group | year-bounded |
| `GtkAdditionalTask` | `tmt`, `tst`, `decree.expiry_date` | decree-bounded |
| `GtkTransferRequest` | `effective_at` | date-bounded |
| `GtkPension` | `effective_at` | date-bounded |
| `AcademicYear` | `start_date`, `end_date` | year |
| `DelegatedAssignment` | `valid_from`, `valid_until` | TTL |
| `ActingPositionAssignment` | `valid_from`, `valid_until` | TTL |

### 5.3. Engine Internal

```php
final class TemporalEvaluator
{
    public function isLive(
        Carbon $now,
        ?Carbon $validFrom,
        ?Carbon $validUntil,
    ): bool {
        if ($validFrom && $now->lt($validFrom)) return false;
        if ($validUntil && $now->gte($validUntil)) return false;
        return true;
    }
}
```

### 5.4. Academic Year Boundaries

ACL ditambah rule eksplisit:
- Teaching assignment hanya live selama `academic_year.is_active = true`.
- Homeroom assignment hanya live selama study_group's academic year aktif.
- Jika academic year non-aktif (closed), semua input score menjadi read-only.

### 5.5. Scheduler Hook

Event listener untuk **academic year rollover**:
- `AcademicYearActivated` event
- Bulk rebuild snapshot seluruh GTK
- Auto-disable Homeroom assignment tahun ajaran lama
- Auto-revoke TeachingAssignment tahun ajaran lama (atau migrate, tergantung kebijakan)

---

## 6. Delegation & Acting Position

### 6.1. Definisi Formal

| Konsep | Definisi |
|--------|----------|
| **Delegation** | User A memberikan sebagian permission-nya ke User B untuk sementara waktu. User A tidak kehilangan permission-nya (kecuali di-explicit revoke). |
| **Acting Position (PLH/PLT)** | User A **menggantikan** User B dalam jabatan B untuk sementara. A mendapat permission jabatan B selama periode tersebut. |

### 6.2. Data Model

#### Delegation
```
delegations
├─ delegator_id (FK users)
├─ delegatee_id (FK users)
├─ delegated_permissions JSON (list of permission names)
├─ scoped_permissions JSON (scoped permissions)
├─ valid_from datetime
├─ valid_until datetime
├─ decree_id FK (optional, ke decree GTK)
├─ reason text
├─ status enum (PENDING|ACTIVE|EXPIRED|REVOKED)
└─ revoked_at datetime nullable
```

#### Acting Position
```
acting_position_assignments
├─ holder_id (FK users, yang menerima)
├─ position_code (FK ke config position)
├─ original_holder_id (FK users, yang digantikan)
├─ school_id (FK schools)
├─ academic_year_id FK
├─ valid_from datetime
├─ valid_until datetime
├─ reason text (PLH Kepala Sekolah selama cuti, dst.)
├─ decree_id FK
└─ status enum (PENDING|ACTIVE|EXPIRED|REVOKED)
```

### 6.3. Algoritma Effective Permission (delegation)

```
1. Resolve primary positions (Employment, WorkUnit, CareerPath) — sama seperti biasa
2. Resolve assignments (Teaching, Homeroom, AdditionalTask) — sama seperti biasa
3. Resolve temporary assignments:
   a. Acting Position (PLH/PLT):
      - Berlaku permission yang sesuai dengan position_code.
      - Scope = scope dari position (mis. school_id).
   b. Delegation (to me):
      - Tambah delegated_permissions ke global set.
      - Tambah delegated scoped_permissions ke scoped set.
4. Apply revocations:
   - Revoked permissions oleh diri sendiri tidak muncul.
   - Revoked delegated permission juga tidak muncul.
5. Finalize.
```

### 6.4. Audit Trail untuk Delegasi

Setiap delegasi / PLH menghasilkan:
- Log entry ke `auth_audit_log`.
- Snapshot entry dengan `origin = 'delegation:ID'`.
- Notification ke holder & delegator pada activation/expiry.

### 6.5. Limit & Conflict

- Delegator tidak boleh mendelegasikan **lebih dari** permission yang dia sendiri punya. Validasi di service layer.
- Delegation chain (A→B→C) dicegah; chain detection pada creation.
- Acting Position conflict: User hanya boleh punya 1 active Acting Position per `(position_code, school_id)` tuple.

### 6.6. Contoh Skenario

**Kepala Sekolah cuti, Wakil menjadi PLH.**

```php
// Create ActingPositionAssignment
ActingPositionAssignment::create([
    'holder_id'          => $wakil->id,         // Wakil
    'position_code'      => 'gtk.kepala_sekolah',
    'original_holder_id' => $kepsek->id,         // Kepala Sekolah
    'school_id'          => $kepsek->employment->school_id,
    'academic_year_id'   => active_year()->id,
    'valid_from'         => now(),
    'valid_until'        => now()->addDays(14),
    'reason'             => 'Kepala Sekolah cuti tahunan',
    'decree_id'          => $skPlh->id,
    'status'             => 'ACTIVE',
]);
```

Wakil kini punya permission `gtk.kepala_sekolah` selama 14 hari. Setelah lewat, listener auto-revoke.

---

## 7. Effective Permission Builder (Explainable)

### 7.1. Class Design

```php
final class EffectivePermissionBuilder
{
    public function build(User $user, ?OrganizationContext $context = null): EffectivePermission;

    public function buildWithTrace(User $user, ?OrganizationContext $context = null): TracedPermission;
}
```

### 7.2. EffectivePermission (immutable)

```php
final class EffectivePermission
{
    /** @var string[] */
    public readonly array $globalPermissions;
    /** @var ScopedPermission[] */
    public readonly array $scopedPermissions;
    /** @var array<string, Origin[]>  permission => origin chain */
    public readonly array $originIndex;
    public readonly string $fingerprint;
    public readonly Carbon $computedAt;
    public readonly Carbon $expiresAt;
}
```

### 7.3. TracedPermission (explainable)

```php
final class TracedPermission
{
    public function __construct(
        public readonly bool $allowed,
        public readonly array $origins,         // origin details
        public readonly array $failedChecks,    // why checks failed (jika tidak allowed)
        public readonly string $permission,
        public readonly ?array $context,
    ) {}

    public function explain(): string;  // human-readable explanation
}
```

### 7.4. Origin Structure

```php
final class Origin
{
    public function __construct(
        public readonly string $source,         // 'GtkEmployment', 'TeachingAssignment', dll.
        public readonly ?string $sourceId,      // UUID row
        public readonly string $code,           // position_code atau assignment code
        public readonly string $weight,         // weight untuk tie-break
        public readonly ?Carbon $validFrom,
        public readonly ?Carbon $validUntil,
        public readonly array $metadata = [],
    ) {}
}
```

### 7.5. Contoh Output `whyAllows`

User: Pak Budi (GTK, Wali Kelas XII-1, Guru Matematika XII-1 & XII-2)

```php
$user->effectivePermissions()->whyAllows('nilai.input', $ctx);
// Returns:
[
    'permission' => 'nilai.input',
    'allowed'    => true,
    'context'    => ['school_id' => 'sch-1', 'academic_year_id' => 'y-2026',
                     'study_group_id' => 'rg-1', 'subject_id' => 'subj-mat'],
    'origins'    => [
        [
            'source'   => 'TeachingAssignment',
            'source_id' => 'ta-uuid-1',
            'code'     => 'subject_assignment',
            'weight'   => 5,
            'note'     => 'Matematika XII-1 tahun 2026/2027',
        ],
        [
            'source'   => 'Identity',
            'source_id' => null,
            'code'     => 'gtk',
            'weight'   => 1,
            'note'     => 'Base GTK identity',
        ],
    ],
    'failures'   => [],
    'computed_at'=> '2026-06-29T08:30:00Z',
    'fingerprint'=> 'a3f4e9c8...',
];
```

### 7.6. Contoh Output `explain` (human-readable)

```
Pak Budi DIBERIKAN permission "nilai.input" karena:
1. Teaching Assignment: Matematika untuk study group XII-1
   (TA ID: ta-uuid-1, aktif 2026-07-01 → 2027-06-30)
2. Identity Role: GTK (base)

Konteks dicek:
- school_id        = sch-1 (matched GtkEmployment.school_id) ✓
- academic_year_id = y-2026 (matched TeachingAssignment.academic_year_id) ✓
- study_group_id   = rg-1 (matched TeachingAssignment.study_group_id) ✓
- subject_id       = subj-mat (matched TeachingAssignment.subject_id) ✓
```

---

## 8. Permission Snapshot Strategy & Audit

### 8.1. Snapshot Schema

```
permission_snapshots
├─ id uuid PK
├─ user_id uuid FK
├─ fingerprint string (sha256)
├─ global_permissions jsonb
├─ scoped_permissions jsonb
├─ origin_index jsonb
├─ context_snapshot jsonb (school/year saat snapshot, untuk audit)
├─ computed_at timestamp
├─ expires_at timestamp
├─ regeneration_reason string ('event:PositionChanged', 'manual:admin', 'scheduled:year_rollover')
├─ regeneration_source_id string (event UUID, decree ID, dsb.)
└─ is_current boolean
```

### 8.2. Mengapa Snapshot = Audit

Setiap snapshot row adalah:
- Frozen state permission user pada titik waktu tertentu
- Dilengkapi dengan `fingerprint` (state identifier) dan `origin_index` (provenance)
- Index `(user_id, computed_at)` untuk query histori

Administrator dapat melakukan:
- `php artisan auth:trace --user=UUID --date=YYYY-MM-DD`
  → Menampilkan snapshot user pada tanggal tersebut.
- `php artisan auth:diff --user=UUID --from=date1 --to=date2`
  → Menampilkan perubahan permission antar tanggal.

### 8.3. Fingerprint Strategy (recap)

Fingerprint adalah hash dari **structural state**:
```
fingerprint = sha256(
  user.id +
  user.is_active +
  user.identity_kind +
  GtkProfile.updated_at +
  GtkEmployment.updated_at +
  GtkWorkUnit.max(updated_at).count +
  GtkCareerPath.max(updated_at).count +
  GtkAdditionalTask.max(updated_at).count +
  TeachingAssignment.max(updated_at).count +
  StudyGroup.homeroom_count +
  AcademicYear.is_active +
  Delegation.max(updated_at).count +
  ActingAssignment.max(updated_at).count +
  Revocation.max(updated_at).count
)
```

Listener hanya menulis snapshot baru jika fingerprint berubah.

### 8.4. Retention Policy

- Snapshot `is_current = true` disimpan selamanya.
- Snapshot historis disimpan 90 hari, lalu di-archive ke tabel `permission_snapshots_archive`.

---

## 9. Conflict Detection

### 9.1. Conflict Categories

| Tipe | Contoh |
|------|--------|
| **Identity Conflict** | User adalah GTK dan Peserta Didik sekaligus (tidak masuk akal) |
| **Position Conflict** | Dua GtkEmployment primary yang aktif bersamaan |
| **Assignment Conflict** | Dua TeachingAssignment untuk subject+rombel+year yang sama |
| **Scope Conflict** | Acting Position untuk posisi yang sebenarnya dipegang sendiri |
| **Delegation Conflict** | A→B dan A→C untuk permission yang sama dalam waktu yang sama |
| **Inheritance Conflict** | Position cycle di config |
| **Revocation Conflict** | Revoked permission tapi ada active delegation untuk permission yang sama |

### 9.2. Detector Service

`App\Authorization\Services\AuthorizationConflictDetector`

Methods:
```php
public function detect(User $user): ConflictReport;

public function detectAll(): Collection; // bulk scan semua user
```

Methods khusus:
```php
public function detectIdentityConflict(User $user): ?Conflict;
public function detectEmploymentConflict(User $user): ?Conflict;
public function detectTeachingAssignmentConflict(User $user): ?Conflict;
public function detectDelegationConflict(User $user): ?Conflict;
public function detectCycleInheritance(): Collection;
```

### 9.3. Saat Conflict Terdeteksi

- Conflict dicatat ke `auth_conflict_log`.
- Listener event-based: setiap perubahan, jalankan detector untuk user yang berubah.
- Periodic scheduler: scan seluruh user harian.
- Dashboard Super Admin: tampilkan konflik aktif dengan severity indicator.
- Bisa auto-revoke / auto-flag (configurable).

### 9.4. Contoh Output

```
ConflictReport {
  user_id: 'user-budi',
  conflicts: [
    {
      type: 'TeachingAssignmentConflict',
      severity: 'medium',
      description: 'Pak Budi punya 2 Teaching Assignment aktif untuk Matematika XII-1 tahun 2026/2027',
      source_ids: ['ta-1', 'ta-2'],
      recommendation: 'Non-aktifkan salah satu assignment manual',
    },
    {
      type: 'IdentityConflict',
      severity: 'high',
      description: 'Pak Budi terdaftar sebagai GTK dan Wali Santri',
      source_ids: [],
      recommendation: 'Tinjau apakah Wali Santri harus identitas terpisah',
    },
  ],
}
```

### 9.5. Hard Rules vs Soft Rules

- **Hard Rules**: Harus zero conflict. Mis. tidak boleh ada cycle inheritance.
- **Soft Rules**: Conflict dilaporkan tapi tidak menggagalkan operation. Mis. dual GTK+Wali Santri mungkin sengaja.

---

## 10. Authorization Rule Engine

### 10.1. Mengapa Rule Engine

Tanpa rule engine, setiap permission harus hardcode di PHP. Itu:
- Sulit di-maintain saat modul baru datang.
- Tidak transparan untuk admin non-tech.
- Sulit di-versioning.

### 10.2. Rule Structure

```
{
  id: 'rule:nilai.input',
  description: 'Boleh input nilai',
  when: {
    all: [
      { fact: 'identity', op: 'is', value: 'gtk' },
      { fact: 'teaching_assignment_exists', args: { subject_id: '{{context.subject_id}}', study_group_id: '{{context.study_group_id}}' } },
      { fact: 'academic_year_active', args: { academic_year_id: '{{context.academic_year_id}}' } },
      { fact: 'time_in_window', args: { source: 'teaching_assignment' } },
    ],
  },
  grant: ['nilai.input'],
  weight: 5,
}
```

### 10.3. Rule Components

| Komponen | Deskripsi |
|----------|-----------|
| `fact` | Fungsi yang mengembalikan boolean atau value. Mis. `teaching_assignment_exists(subject, rombel)`. |
| `op` | Operator: `is`, `in`, `>=`, `<`, `exists`, `not_exists`. |
| `value` | Nilai yang dibandingkan. Bisa berupa string atau `{{template}}` untuk context substitution. |
| `all` / `any` | Logic combination. |
| `grant` | Daftar permission yang diberikan jika rule match. |
| `weight` | Tie-break order. |
| `scope` | Opsional. Scope permission yang diberikan. |

### 10.4. Built-in Facts

| Fact | Args | Returns |
|------|------|---------|
| `identity` | none | user's identity_kind |
| `has_role` | role | bool |
| `employment_status` | none | current employment status |
| `employment_active` | none | bool |
| `in_school` | school_id | bool |
| `in_year` | academic_year_id | bool |
| `teaching_assignment_exists` | subject_id, study_group_id | bool |
| `homeroom_of` | study_group_id | bool |
| `additional_task_active` | task_code | bool |
| `career_path_active` | code | bool |
| `academic_year_active` | academic_year_id | bool |
| `time_in_window` | source | bool |
| `delegated_to_me` | permission | bool |
| `acting_position_active` | position_code | bool |
| `revoked` | permission | bool |

### 10.5. Custom Facts

Developer dapat menambah fact baru via:

```php
$this->app->tag(FactRegistry::class)->extend('my_custom_fact', function ($app) {
    return new MyCustomFact($app->make(MyService::class));
});
```

### 10.6. Rule Storage & Versioning

- Rule disimpan di `config/authorization/rules.php` (code-defined).
- Rule yang sering berubah disimpan di tabel `authorization_rules` (DB-defined) dengan versioning.
- DB rules di-load ke registry saat boot, override config rules.
- Setiap rule punya `version` (auto-increment) untuk audit.

### 10.7. Rule Evaluation Flow

```
RuleRequest {
  user, permission, context
}
  ↓
RuleRegistry::rulesFor(permission)
  ↓ (cocok rule yang grant berisi permission)
For each rule:
  Evaluate FactResolver(facts)
    ↓
  Return match/no-match
  ↓
Combine via OR (default) atau AND (configurable)
  ↓
If match → grant permission (with scope & weight)
```

### 10.8. Contoh Rules

```php
// R1: GTK boleh input nilai untuk teaching assignment-nya sendiri
[
    'id' => 'gtk.input_nilai',
    'description' => 'Boleh input nilai untuk subject & rombel yang diajar',
    'when' => [
        'all' => [
            ['fact' => 'identity', 'op' => 'is', 'value' => 'gtk'],
            ['fact' => 'teaching_assignment_exists'],
            ['fact' => 'academic_year_active'],
            ['fact' => 'time_in_window', 'args' => ['source' => 'teaching_assignment']],
        ],
    ],
    'grant' => ['nilai.input', 'nilai.edit'],
    'weight' => 5,
    'scope_from' => 'teaching_assignment',
],

// R2: Wali kelas boleh input nilai untuk semua mapel di rombelnya
[
    'id' => 'homeroom.input_nilai',
    'description' => 'Wali kelas boleh input nilai untuk semua mapel di rombel',
    'when' => [
        'all' => [
            ['fact' => 'homeroom_of'],
            ['fact' => 'academic_year_active'],
        ],
    ],
    'grant' => ['nilai.input', 'nilai.edit'],
    'weight' => 3, // lebih rendah dari R1
    'scope_from' => 'homeroom',
],

// R3: Kepala Sekolah boleh input nilai untuk seluruh sekolah (override)
[
    'id' => 'kepsek.input_nilai_override',
    'description' => 'Kepala Sekolah override semua nilai di sekolah',
    'when' => [
        'all' => [
            ['fact' => 'career_path_active', 'args' => ['code' => 'gtk.kepala_sekolah']],
            ['fact' => 'academic_year_active'],
        ],
    ],
    'grant' => ['nilai.input', 'nilai.edit', 'nilai.finalize'],
    'weight' => 10, // override teratas
    'scope_from' => 'school',
],

// R4: Academic year closed → tidak boleh input
[
    'id' => 'revoke_input_nilai_year_closed',
    'description' => 'Cabut input nilai jika tahun ajaran tutup',
    'when' => [
        'all' => [
            ['fact' => 'academic_year_closed', 'args' => ['academic_year_id' => '{{context.academic_year_id}}']],
        ],
    ],
    'revoke' => ['nilai.input'],
    'weight' => 0,
],
```

### 10.9. Rule UI

Admin dapat:
- Browse semua rule di `/superadmin/rules`
- Lihat rule yang sedang aktif
- Versioning history
- Test rule dengan simulator (input user + context → lihat granted permission)

---

## 11. Multi-School Readiness

### 11.1. Prinsip

Walaupun saat ini ALIM dipakai satu sekolah, arsitektur harus siap multi-school (Yayasan dengan SD/SMP/SMA/SMK/Pesantren).

### 11.2. Data Model Adjustment

Tabel-tabel yang saat ini menyimpan `school_id`:
- `gtk_employments.school_id` ✓ (sudah)
- `gtk_work_unit` → `work_unit_id` → `work_units.school_id`
- `teaching_assignments` → `study_groups.academic_year_id` → studi group milik sekolah mana?
- `gtk_additional_tasks.school_id` (akan ditambah jika belum)

Tabel yang perlu ditambah `school_id`:
- `study_groups` (saat ini via academic_year?)
- `subjects` (subject master per sekolah atau global?)
- `permissions_snapshots.context_snapshot.school_id`

### 11.3. Multi-School Authorization Decisions

#### Decision 1: Global vs Local Permission
- **Identity role** (Super Admin, GTK, dll.) = global, tidak sekolah-specific.
- **Position role** (Wakil Kepala Sekolah) = sekolah-specific, di-scope ke `school_id`.
- **Assignment** (Teaching Assignment) = sekolah-specific via study_group.school_id.

#### Decision 2: Cross-School Access
- Super Admin bisa lintas sekolah.
- GTK hanya sekolahnya sendiri secara default.
- Acting Position dapat di-scope lintas sekolah (mis. Yayasan punya satu Wakil Kepala Sekolah Yayasan yang override multi-school).

#### Decision 3: School Context Middleware
```php
Route::middleware(['org.context'])->group(function () {
    // semua routes di sini wajib ada school_id dalam context
});
```

### 11.4. Schema untuk School Scope

```
permission_scopes (pada snapshot):
{
  school_id: 'uuid',
  academic_year_id: 'uuid',
  study_group_id: 'uuid|null',
  subject_id: 'uuid|null',
}
```

Tiap scoped permission membawa school_id. Check pada authorize() selalu verify `context.school_id == permission.school_id`.

### 11.5. Multi-School Mode Switch

Flag di config:
```php
// config/authorization.php
return [
    'multi_school_mode' => env('MULTI_SCHOOL_MODE', false),
];
```

Saat `false`: school context optional (backward-compat).
Saat `true`: school context mandatory.

### 11.6. Yayasan Hierarchy (future)

```
Yayasan
├─ SD
├─ SMP
├─ SMA
├─ SMK
└─ Pesantren
```

Untuk multi-unit, tambahkan `unit_id` di organization hierarchy dan parent_id untuk inheritance sekolah. Ini out-of-scope untuk implementasi awal tapi struktur sudah siap.

---

## 12. Authorization Graph

Diagram lengkap hubungan antara seluruh komponen authorization di ALIM.

```
                            ┌─────────────────────┐
                            │   External Caller    │
                            │ (HTTP/CLI/Scheduler) │
                            └──────────┬──────────┘
                                       │
        ┌──────────────────────────────▼──────────────────────────────┐
        │                  AUTHENTICATION                                │
        │    Auth::attempt → session/token                              │
        └──────────────────────────────┬──────────────────────────────┘
                                       │
        ┌──────────────────────────────▼──────────────────────────────┐
        │              IDENTITY VALIDATION                              │
        │    Identity Kind (Super Admin|GTK|...)                       │
        └──────────────────────────────┬──────────────────────────────┘
                                       │
        ┌──────────────────────────────▼──────────────────────────────┐
        │          ORGANIZATION CONTEXT RESOLUTION                      │
        │    school_id, academic_year_id, semester                       │
        │    study_group_id, subject_id (dari URL/session)              │
        └──────────────────────────────┬──────────────────────────────┘
                                       │
        ┌──────────────────────────────▼──────────────────────────────┐
        │             SNAPSHOT RESOLUTION                                │
        │    Fingerprint → Cache hit/miss                                │
        │    Cache hit  → load snapshot                                  │
        │    Cache miss → RebuildPermissionsListener → write snapshot  │
        └──────────────────────────────┬──────────────────────────────┘
                                       │
        ┌──────────────────────────────▼──────────────────────────────┐
        │         EFFECTIVE PERMISSION BUILDER                          │
        │                                                                    │
        │   Identity ─→ IdentityPermissionProvider                       │
        │   Profile  ─→ ProfilePermissionProvider                         │
        │   Employment ─→ PositionPermissionProvider (registry)           │
        │   WorkUnit  ─→ PositionPermissionProvider                       │
        │   CareerPath ─→ PositionPermissionProvider                      │
        │   TeachingAssignment ─→ AssignmentPermissionProvider           │
        │   Homeroom (derived) ─→ AssignmentPermissionProvider           │
        │   AdditionalTask ─→ AssignmentPermissionProvider               │
        │   DelegatedAssignment ─→ DelegationPermissionProvider          │
        │   ActingPosition ─→ ActingPositionPermissionProvider           │
        │   RevokedPermission ─→ RevocationProvider                       │
        │                                                                    │
        │   Output: PermissionBag(global + scoped) + OriginIndex         │
        └──────────────────────────────┬──────────────────────────────┘
                                       │
        ┌──────────────────────────────▼──────────────────────────────┐
        │                RULE ENGINE                                      │
        │   RuleRegistry::evaluate(request, context)                     │
        │   Apply additional/revoke rules                                │
        │   Combine with position-derived permissions                    │
        └──────────────────────────────┬──────────────────────────────┘
                                       │
        ┌──────────────────────────────▼──────────────────────────────┐
        │         AUTHORIZATION DECISION                                  │
        │   effectivePermission.allows(perm, context)                    │
        │   effectivePermission.whyAllows(perm, context)                  │
        └──────────────────────────────┬──────────────────────────────┘
                                       │
        ┌──────────────────────────────▼──────────────────────────────┐
        │              POLICY / GATE                                      │
        │   Gate (boolean) ←→ Policy (resource-aware)                    │
        │   → Middleware('permission', 'permission_name')                │
        │   → SidebarComposer (menu visibility)                          │
        └──────────────────────────────┬──────────────────────────────┘
                                       │
        ┌──────────────────────────────▼──────────────────────────────┐
        │              FEATURE / API / ACTION                              │
        │   Controller · Service · Form Request                           │
        │   → $this->authorize(...)                                       │
        └──────────────────────────────┬──────────────────────────────┘
                                       │
        ┌──────────────────────────────▼──────────────────────────────┐
        │              AUDIT & OBSERVABILITY                               │
        │   PermissionSnapshot (audit trail)                              │
        │   AuthAuditLog (decision log)                                   │
        │   AuthorizationConflictLog                                      │
        │   RuleEvaluationTrace                                            │
        └────────────────────────────────────────────────────────────────┘

════════════════════════════════════════════════════════════════════════
                       EVENT-DRIVEN INVALIDATION CHAIN
════════════════════════════════════════════════════════════════════════

[EVENT SOURCES]
UserIdentityChanged     ─┐
GtkEmploymentChanged     ─┤
GtkWorkUnitChanged       ─┤
GtkCareerPathChanged     ─┤
TeachingAssignmentChanged─┤
HomeroomChanged          ─┤   ┌────────────────────────┐
AdditionalTaskChanged    ─┼──→│ PositionChanged event   │
TransferRequestApproved  ─┤   └───────────┬────────────┘
GtkPensionCompleted      ─┤               │
AcademicYearActivated    ─┤               ▼
DelegationCreated        ─┤   ┌────────────────────────┐
ActingPositionCreated    ─┤   │ RebuildPermissionsListener│
RevocationCreated        ─┤   └───────────┬────────────┘
UserToggleActive         ─┤               │
UserDeleted              ─┘               ▼
                          ┌────────────────────────────┐
                          │ SnapshotStore::save + bust  │
                          │ Cache::tags(['perms:X']) bust│
                          │ AuthAuditLog::log           │
                          │ ConflictDetector::run       │
                          └────────────────────────────┘
```

---

## 13. Compatibility Review

### 13.1. Spatie Permission

**Status**: Tetap dipakai sebagai **Identity & Permission Registry**.

| Fitur | Status | Rekomendasi |
|-------|--------|-------------|
| `roles` table (Super Admin, GTK, Wali Santri, Peserta Didik, Alumni) | ✅ Dipakai | Pertahankan. Identity role stabil. |
| `model_has_roles` pivot | ✅ Dipakai | Pertahankan untuk identity. |
| `permissions` table | ✅ Dipakai | Pertahankan. |
| `role_has_permissions` pivot | ✅ Dipakai | Pertahankan untuk identity-role permission. |
| `HasRoles` trait | ✅ Dipakai | Extend dengan `HasEffectivePermissions`. |
| `assignRole()` di controller | ⚠️ Perlu refactor | Hanya identity role, dari listener. |
| `syncRoles()` | ⚠️ Perlu refactor | Hanya identity. |
| `hasRole('gtk')` (identity) | ✅ Dipakai | Tetap boleh. |
| `hasRole('Wakil Kepala Sekolah')` | ❌ Deprecated | Ganti ke `can('gtk.wakil_kepala_sekolah.permission')`. |
| `givePermissionTo()` | ❌ Deprecated | Permissions derived dari builder, bukan assignment manual. |
| `RoleMiddleware` (route) | ✅ Dipakai | Tetap, hanya identity. |

**Ringkasan**: Spatie jadi fondasi registry; business logic pindah ke Authorization Layer.

### 13.2. Laravel Gate

**Status**: Tetap dipakai, tetapi tidak lagi sebagai **primary decision-maker**.

```php
// Saat ini:
Gate::define('nilai.input', fn($user) => $user->hasRole('gtk'));

// Baru:
Gate::define('nilai.input', fn($user, $rombel = null, $mapel = null) =>
    app(EffectivePermissionBuilder::class)->build($user)->allows(
        permission: 'nilai.input',
        context: [
            'study_group_id' => $rombel?->id,
            'subject_id'     => $mapel?->id,
        ],
    )
);
```

**Atau lebih baik**: Gate hanya jadi thin wrapper di Policy.

### 13.3. Laravel Policy

**Status**: Tetap dipakai, dengan extension.

- Policy menerima `OrganizationContext` via constructor.
- `User->can('nilai.input', $rombel, $mapel)` delegates ke Policy.
- Policy delegasi ke `EffectivePermissionBuilder`.

### 13.4. Middleware

| Middleware | Status | Rekomendasi |
|------------|--------|-------------|
| `Authenticate` | ✅ | Tetap |
| `EnsureEmployeeAccess` | ✅ | Tetap, mungkin extend |
| `RoleMiddleware` | ✅ | Identity role only |
| `PermissionMiddleware` | ✅ | Tetap, delegasi ke EffectivePermission |
| `SchoolContextMiddleware` | ✅ | Tetap, jadi `OrganizationContextMiddleware` |
| `MinRoleLevel` | ⚠️ | Tetap untuk legacy compatibility, tambahkan `MinEffectiveLevel` |
| `RoleEnforced` | ✅ | Tetap |
| `active.academic_year` | ✅ | Tetap |
| `effective.permission` (NEW) | 🆕 | Akan ditambah, menyatukan semua permission-related middleware |

### 13.5. Custom Middleware

`App\Http\Middleware\EffectivePermissionMiddleware` (akan dibuat) menjadi **single entry point** untuk authorization. Middleware lain menjadi **thin wrappers** yang delegate ke middleware ini.

---

## 14. Risiko & Trade-off

| Resiko | Severity | Mitigasi |
|--------|----------|----------|
| **Kompleksitas naik** — banyak layer | Medium | Tahapan migrasi jelas, dokumentasi detail, training tim. |
| **Performance overhead** — recalc tiap event | High | Snapshot + cache + fingerprint. Batch rebuild. |
| **Backwards incompatibility** — Spatie role existing | Medium | Phase 6 cleanup bertahap. Legacy flag untuk role lama. |
| **Rule engine bisa salah evaluasi** | High | Unit test ekstensif + integration test. RuleVersioning. |
| **Multi-school migrasi nanti** | Medium | Schema sudah sekolah-scoped, tinggal switch flag. |
| **Conflict detector bisa false positive** | Low | Severity indicator + manual review workflow. |
| **Snapshot storage membengkak** | Low | Retention policy 90 hari, archive table. |
| **Delegation abuse** | High | Audit trail + decree requirement + validasi bisnis. |
| **Time-based evaluation bug (DST, leap year)** | Low | Pakai Carbon dengan timezone sekolah, test edge cases. |
| **Rule engine learning curve** | Medium | Built-in rule examples + UI simulator + dokumentasi. |

**Trade-off yang disadari**:
- **Lebih banyak abstraksi** = lebih banyak file dan kelas. Tapi ini investasi untuk maintainability.
- **Lebih lambat untuk fitur sederhana** = setup rule sedikit lebih lama. Tapi reusable.
- **Initial development lebih berat** = butuh effort besar di Phase 1-3. Tapi payoff di modul-modul selanjutnya.

---

## 15. Roadmap Implementasi Bertahap

### Phase 1 — Foundation (3-4 hari)

**Deliverables**:
1. Tabel `permission_snapshots` (+ migration).
2. Tabel `temporary_permissions`, `revoked_permissions`, `delegations`, `acting_position_assignments` (+ migrations).
3. Tabel `authorization_rules` (+ migration).
4. Interface `App\Authorization\Contracts\PositionSource`.
5. Class `App\Authorization\PositionResolver`.
6. Class `App\Authorization\Builders\PermissionBag`.
7. Class `App\Authorization\EffectivePermission` (immutable).
8. Class `App\Authorization\OrganizationContext`.
9. Config `config/authorization.php` dengan multi_school_mode, position registry, dll.
10. Trait `HasEffectivePermissions` (stub).

**Pass criteria**: Tidak ada perubahan perilaku sistem. Tests green.

### Phase 2 — Position Resolvers & Builder (5-7 hari)

**Deliverables**:
1. Position source classes:
   - `GtkEmploymentPosition`
   - `GtkWorkUnitPosition`
   - `GtkCareerPathPosition`
   - `GtkAdditionalTaskPosition`
   - `TeachingAssignmentPosition`
   - `HomeroomPosition`
   - `RetirementPosition`
   - `ResignationPosition`
2. `PositionPermissionRegistry` dengan inheritance graph + cycle detection.
3. `EffectivePermissionBuilder::build()` complete.
4. `ScopedPermission` class.
5. `TemporalEvaluator`.
6. `Origin` & `TracedPermission`.
7. Unit tests untuk semua skenario.

**Pass criteria**: Builder menghasilkan output yang benar untuk semua kombinasi sumber.

### Phase 3 — Snapshot, Cache & Rule Engine (5-7 hari)

**Deliverables**:
1. `PermissionSnapshotResolver` + `PermissionSnapshotStore`.
2. Cache strategy (fingerprint + memory cache + tag-based invalidation).
3. `RuleRegistry` + `FactResolver`.
4. Built-in facts (15 facts).
5. Config-based rules + DB rules loading.
6. Sample rules (gtk.input_nilai, dll.).
7. Integration tests.

**Pass criteria**: Snapshot ter-build dan ter-cache. Rule engine evaluate benar.

### Phase 4 — Events & Observers (4-5 hari)

**Deliverables**:
1. Event classes: `PositionChanged`, `IdentityAssigned`, `UserToggledActive`, `UserDeleted`, `TransferRequestApproved`, `GtkRetired`, `HomeroomChanged`, `AcademicYearActivated`, `DelegationActivated`, `ActingPositionActivated`, `RevocationApplied`.
2. Listeners:
   - `RebuildPermissionsListener` (queue:async).
   - `RevokePermissionsListener` (sync untuk UserToggledActive/deletion).
3. Observers untuk semua source.
4. `AppServiceProvider::boot()` registration.
5. `AuthorizationConflictDetector` (event-triggered).

**Pass criteria**: Perubahan GTK Employment → snapshot ter-rebuild.

### Phase 5 — Context-Aware Layer (4-5 hari)

**Deliverables**:
1. `OrganizationContext` service + middleware.
2. `EffectivePermissionMiddleware` (consolidates permission-related middleware).
3. Policy base class dengan context injection.
4. Update Gate definitions untuk delegate ke builder.
5. Update `authorize()` calls di controllers yang ada.
6. SidebarComposer refactor.

**Pass criteria**: Context-aware checks work di sample routes.

### Phase 6 — Time-Based & Delegation (4-5 hari)

**Deliverables**:
1. Temporal evaluator integration ke semua resolvers.
2. `DelegationService` + UI di SuperAdmin.
3. `ActingPositionService` + UI di SuperAdmin.
4. Auto-expire scheduler (hourly).
5. Delegation/Acting conflict detection.

**Pass criteria**: Delegation dan PLH work dengan audit trail.

### Phase 7 — Rule Engine UI & Conflict UI (3-4 hari)

**Deliverables**:
1. `/superadmin/rules` index + edit.
2. Rule versioning UI.
3. Rule simulator.
4. `/superadmin/conflicts` dashboard.
5. `php artisan auth:trace`, `auth:diff`, `auth:drift` commands.

**Pass criteria**: Admin dapat manage rules & lihat conflict.

### Phase 8 — Backfill & Migration (3-4 hari)

**Deliverables**:
1. `php artisan auth:backfill --user=...` — generate snapshot untuk semua GTK existing.
2. `php artisan auth:reconcile` — detect & fix inconsistencies.
3. Legacy role flagging (Spatie roles yang bukan identity diberi flag).
4. `ALLOW_MANUAL_ROLES=false` rollout.
5. Comprehensive integration tests untuk seluruh flow.

**Pass criteria**: Production snapshot ter-backfill. Tidak ada regressions.

### Phase 9 — Multi-School Mode (future, ~5 hari)

**Deliverables**:
1. `MULTI_SCHOOL_MODE=true` flag activation.
2. School context enforcement.
3. Yayasan hierarchy (jika applicable).

**Pass criteria**: Multi-school activation seamless.

---

## Lampiran A — Definisi Istilah

| Istilah | Definisi |
|---------|----------|
| **Identity** | Kategori primer user (GTK, Peserta Didik, dll.). Stabil. |
| **Profile** | Data personal user. |
| **Position** | Jabatan struktural dalam organisasi. |
| **Assignment** | Tugasan resource-scoped. |
| **Scope** | Konteks (school, year, subject, dsb.) di mana permission berlaku. |
| **Origin** | Sumber permission (employment, assignment, delegation, dll.). |
| **Fingerprint** | Hash identifier dari structural state user. |
| **Snapshot** | Frozen state permission pada titik waktu tertentu. |
| **Rule** | Deklarasi IF-THEN untuk permission. |
| **Fact** | Fungsi evaluation untuk rule (e.g., teaching_assignment_exists). |
| **Delegation** | Penyerahan sebagian permission ke user lain. |
| **Acting Position** | Penggantian sementara untuk posisi user lain. |
| **Revocation** | Pencabutan eksplisit permission. |
| **Conflict** | Keadaan organisasi yang tidak konsisten. |
| **Multi-school** | Mode dimana satu database berisi banyak sekolah. |

---

## Lampiran B — Migration Checklist dari Spatie-Centric ke Layered

| Aspek | Saat Ini | Target |
|-------|----------|--------|
| Identity role | Spatie roles table | Spatie roles table (stabil) |
| Position role | Spatie roles (Wakil Kepala Sekolah, dst.) | config/authorization/positions.php |
| Assignment permission | Manual di controller | builder + rule engine |
| Permission cache | Spatie internal cache | snapshot + memory cache + fingerprint |
| Audit trail | Minim | snapshot + audit log + conflict log |
| Delegation | Tidak ada | delegations table + service |
| Acting position | Tidak ada | acting_position_assignments table + service |
| Conflict detection | Tidak ada | detector service + dashboard |
| Multi-school | Tidak ready | config flag + context middleware |
| Rule customization | Hardcoded | rule engine + UI |
| Explainability | Tidak ada | trace API + dashboard |

---

## Penutup

Arsitektur ini adalah **fondasi authorization** ALIM untuk tahun-tahun ke depan. Ini bukan sekadar tambahan fitur, melainkan **re-platforming authorization layer** yang berdiri di atas Spatie Permission dan Laravel Gate yang sudah ada.

Setelah disetujui, saya akan mulai Phase 1 (Foundation) yang merupakan pure infrastructure tanpa perubahan perilaku apapun, sehingga risiko terhadap sistem existing minimal.