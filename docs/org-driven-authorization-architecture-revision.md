---
name: org-driven-authorization-architecture-revision
description: Revised architecture — separating Identity, Position, and Assignment from Permissions in ALIM
metadata:
  type: project
---

# Architecture Revision: Organization-Driven Authorization

> Revision date: 2026-06-29
> Supersedes: `org-driven-authorization-architecture-audit.md`
> Status: **Proposal — awaiting approval before any code is written**

---

## 1. Kritik terhadap Desain Sebelumnya

### 1.1. Audit awal masih menggunakan model RBAC tradisional
Desain lama menyamaratakan **Role** dengan **Position** (jabatan). Itu asumsi yang salah untuk ERP pendidikan.

Karena struktur organisasi pesantren bukan daftar tugas yang flat, melainkan jaringan posisi yang kompleks (seorang GTK bisa Guru + Wali Kelas + Koordinator Tahfidz + Tim Kurikulum sekaligus), maka pendekatan RBAC akan menghasilkan **Role Explosion**: setiap kombinasi posisi akan menciptakan role baru.

### 1.2. Permission dihitung sekali, dari satu sumber
Desain lama menjadikan **role Spatie** sebagai satu-satunya sumber permission. Padahal posisi organisasi memengaruhi permission secara aditif, bukan tunggal.

### 1.3. Trigger sinkronisasi terlalu sempit
Audit hanya menyebut perubahan jabatan. Padahal ada banyak sumber lain yang harus memicu sinkronisasi:

| Sumber | Implikasi |
|--------|-----------|
| Mutasi | Pindah work unit, jabatan, satuan kerja |
| Promosi | Naik jenjang karir, role naik |
| Demosi | Turun jenjang |
| Pensiun | Kehilangan semua permission GTK |
| Resign | Kehilangan semua permission GTK |
| Homeroom assignment | Mendapat permission wali kelas |
| Pengangkatan kembali | Kembali punya permission GTK |
| Inactive user | Semua permission ditarik |

### 1.4. Spatie Permission dipaksakan sebagai fondasi
Spatie Permission cocok sebagai **permission registry** (daftar nama permission, UUID, group). Tapi memaksakannya sebagai **business logic layer** akan menyebabkan kekakuan. Action verifikasi yang detil seperti “hanya wali kelas dari study group X yang boleh edit nilai SANTRI di rombel X” akan sulit jika cuma mengandalkan role check.

### 1.5. Hardcoded `hasRole()` di controller belum dibahas tuntas
Ada ~80+ penggunaan `hasRole()` di controllers. Audit lama menyebutnya gap, tapi tidak memberikan strategi refactor yang sistematis.

### 1.6. Tidak ada strategi hierarki jabatan
Tanpa inheritance, daftar permission GTK/Kepala Sekolah akan dipenuhi copy-paste.

---

## 2. Kelemahan Pendekatan RBAC Tradisional untuk ERP Pendidikan

### 2.1. Sekolah adalah jaringan posisi, bukan daftar peran
Di ERP bisnis, karyawan biasanya punya satu role tetap: “Senior Accountant”, “Sales Manager”, dsb. Di Alim, seorang GTK bisa merangkap banyak posisi yang berubah setiap tahun ajaran.

### 2.2. Banyak posisi, lebih banyak kombinasi
Dengan 13 sumber posisi utama (GtkEmployment, GtkWorkUnit, GtkAdditionalTask, GtkCareerPath, TeachingAssignment, Homeroom Assignment, dll.) dan setiap GTK memiliki banyak kombinasi, pendekatan “satu role Spatie per kombinasi posisi” akan menciptakan ratusan role.

### 2.3. Permission sering kontekstual (resource-scoped)
Contoh: “edit nilai” harusnya dicek terhadap `(study_group_id, subject_id, academic_year_id)` — bukan hanya role. RBAC tradisional tidak mendukung ini tanpa ABAC.

### 2.4. Riwayat organisasi harus berkontribusi
GTK yang pensiun tahun lalu tidak boleh punya permission GTK yang pensiun tahun ini, meskipun keduanya menyimpan record GtkEmployment. Permission harus bergantung pada status keaktifan.

### 2.5. Validitas sementara (effective-dated)
GtkCareerPath dan GtkAdditionalTask punya `tmt` (mulai) dan `tst` (selesai). Permission harus otomatis mengikuti tanggal tersebut, dan menang/mati bersamaan.

### 2.6. Inheritance vs override sering bentrok
Seorang Guru yang menjadi Kepala Sekolah harus mewarisi permission Guru +permission Kepala Sekolah. Tapi seorang Guru yang juga Personalia tidak boleh mewarisi permission Personalia. RBAC tidak bisa menjelaskan aturan ini dengan natural.

### 2.7. Spatie model cache menyebabkan drift
Spatie menyimpan role-permission dalam tabel pivot dan di-cache. Jika business logic membuat keputusan berdasarkan tabel pivot secara langsung, akan sulit menambahkan sumber lain.

---

## 3. Arsitektur Baru: Four-Layer Authorization

### 3.1. Empat layer pemisahan concern

```
┌────────────────────────────────────────────────────────────────┐
│ LAYER 1 — IDENTITY                                             │
│ Siapa dia? Stabil, jarang berubah.                              │
│                                                                │
│ • User                                                          │
│ • User.primary_role  (Super Admin|GTK|Peserta Didik|          │
│                        Wali Santri|Alumni|Guest)               │
└────────────────────────┬───────────────────────────────────────┘
                         │
┌────────────────────────▼───────────────────────────────────────┐
│ LAYER 2 — PROFILE (jika GTK/Personalia)                          │
│ Profil personal GTK, dipakai sebagai gating                    │
│                                                                │
│ • GtkProfile                                                    │
│   (nik, jenis_kelamin, status_perkawinan, dll.)                │
└────────────────────────┬───────────────────────────────────────┘
                         │
┌────────────────────────▼───────────────────────────────────────┐
│ LAYER 3 — ORGANIZATIONAL POSITION (jabatan organisasional)      │
│ Posisi yang melekat pada user dalam struktur.                  │
│                                                                │
│ • GtkEmployment           ─ Primary position + status          │
│ • GtkWorkUnit            ─ Primary + secondary placements      │
│ • GtkCareerPath           ─ Functional career position          │
│                                                                │
│ → Hasilnya: PositionSet, daftar posisi dgn weight & validity    │
└────────────────────────┬───────────────────────────────────────┘
                         │
┌────────────────────────▼───────────────────────────────────────┐
│ LAYER 4 — ASSIGNMENT (tugas tambahan + unit-specific)            │
│ Tugasan yang diberikan ke user, bisa bertanggal & resource-      │
│ scoped.                                                          │
│                                                                │
│ • TeachingAssignment       ─ subject+rombel role                │
│ • GtkAdditionalTask        ─ extra duties (decree, hours)      │
│ • HomeroomAssignment       ─ wali kelas (study_group.homeroom) │
│ • StudyGroupPlacement      ─ penugasan ke study_group          │
│ • TemporaryAssignment      ─ Plt, Plh, penugasan sementara     │
│ • InstitutionalMembership  ─ kepanitiaan/panitia khusus          │
│                                                                │
│ → Hasilnya: AssignmentSet                                       │
└────────────────────────┬───────────────────────────────────────┘
                         │
┌────────────────────────▼───────────────────────────────────────┐
│ LAYER 5 — EFFECTIVE PERMISSION                                  │
│ Kalkulasi final yang dipakai Gate/Policy/Middleware.            │
│                                                                │
│ EffectivePermission =                                           │
│   Role-permissions                                             │
│ + Sum(positional_permissions × weight × validity)            │
│ + Sum(assignment_permissions × scope × validity)             │
│ + Sum(temporary_permissions × ttl)                             │
│ - Revoked_permissions                                           │
│                                                                │
│ → Disimpan ke permission_snapshots, di-cache                   │
└────────────────────────────────────────────────────────────────┘
```

### 3.2. Definisi Tiap Layer

#### Layer 1: Identity
- `User`
- `User.primary_role` adalah satu role stabil (maks 1) yang dipegang sepanjang hidup user.
- Ditentukan sekali saat user dibuat, **tidak berubah** kecuali user terdaftar sebagai kategori identitas yang berbeda (mis. alumni yang jadi GTK lagi).
- Dipakai oleh **routing guard** (apakah GTK boleh akses modul GTK? Peserta Didik boleh akses modul Peserta Didik?) — bukan oleh fine-grained permission.

#### Layer 2: Profile
- Berlaku untuk user dengan primary_role = GTK.
- Berisi data personal GTK.
- Saat ini tidak banyak kontribusi permission, tapi ke depan: GTK yang masa percobaan mungkin punya permission terbatas, dsb.
- **Pemicu permission**: status GTK, jenis kelamin (untuk beberapa modul yang terpisah), masa kerja.

#### Layer 3: Organizational Position (PositionSet)
- **Satu Primary Position**: GtkEmployment (status_kepegawaian, jabatan, school_id, academic_year_id).
- **N Additional Position**: GtkWorkUnit, GtkCareerPath.
- Tiap Position memiliki:
  - `code`: kode referensi (mis. ‘ks_smp’, ‘waka_kurikulum’)
  - `weight`: prioritas (lebih tinggi = lebih kuat)
  - `validity`: tmt/tst / academic year scope
- **Hasil: PositionSet object**.

#### Layer 4: Assignment (AssignmentSet)
- Tugasan spesifik yang diberikan:
  - **TeachingAssignment**: subject+rombel+year specific
  - **GtkAdditionalTask**: decree-linked extra duties (dengan masa berlaku)
  - **HomeroomAssignment**: dari StudyGroup.homeroom_teacher_id (derived)
  - **TemporaryAssignment**: Plt/Plh dengan masa berlaku
- Tiap Assignment menghasilkan permission **scoped** ke resource tertentu.
- **Hasil: AssignmentSet object** berisi assignment dengan scope (study_group, subject, dll).

#### Layer 5: Effective Permission
- Builder menggabungkan semua sumber.
- Cache strategy: setiap user punya **1 snapshot** yang invalid jika underlying data berubah.
- Output: daftar flat permission (string name) + daftar resource-scoped permission (seperti dict).
- Dipakai oleh:
  - `Gate` (untuk global permission)
  - `Policy` (untuk resource-scoped)
  - `Blade @can` directive
  - `SidebarComposer` (untuk menu)

---

## 4. Diagram Hubungan Entitas

```
                    ┌──────────────────────────────┐
                    │           User               │
                    │                              │
                    │  primary_role (string)       │──┐
                    │  identity_kind (enum)        │  │
                    └──────────┬───────────────────┘  │
                               │                      │
          ┌────────────────────┴─────────────┐        │
          │                                  │        │
          ▼                                  ▼        ▼
┌─────────────────────┐          ┌─────────────────────────┐
│   GtkProfile        │          │   AuthorizationRole     │
│                     │          │   (Spatie storage —     │
│   NIK, TTL, dll.    │          │    kept for compat)     │
└──────────┬──────────┘          └─────────────────────────┘
           │
           │ 1:1
           ▼
┌──────────────────────────────┐
│   GtkEmployment (PRIMARY)    │
│                              │
│   - jabatan (free text)      │
│   - position_type            │
│   - status_kepegawaian       │
│   - academic_year_id         │
│   - school_id                │
└──────┬───────────┬───────────┘
       │ 1:N       │ 1:N
       ▼           ▼
┌──────────────────┐  ┌─────────────────────────┐
│  GtkWorkUnit     │  │  GtkCareerPath          │
│                  │  │                         │
│  - work_unit_id  │  │  - jabatan_fungsi       │
│  - jabatan       │  │  - tmt, tst             │
│  - is_primary    │  │                         │
└──────────────────┘  └─────────────────────────┘

       │ 1:N             │ 1:N
       ▼                 ▼
┌──────────────────┐  ┌─────────────────────────┐
│ GtkAdditionalTask│  │ TeachingAssignment      │
│                  │  │                         │
│ - decree_id      │  │ - subject+rombel+year   │
│ - hours_per_week │  │ - role (koordinator)    │
│ - tmt, tst       │  │ - is_coordinator        │
└──────────────────┘  └─────────────────────────┘

                  ┌──────────────────┐
                  │  StudyGroup       │──── homeroom_teacher_id (User)
                  └──────────────────┘
                  │
                  ▼ Derived HomeroomAssignment

                  ┌─────────────────────────────┐
                  │  GtkTransferRequest          │
                  │  - status (PENDING|APPROVED) │
                  └─────────────────────────────┘
                  │
                  ▼ On APPROVED → PositionChanged

                  ┌──────────────────┐
                  │  User.is_active   │
                  └──────────────────┘
                  │
                  ▼ On toggle → PermissionRevoked

                  ┌──────────────────────────────┐
                  │  Effective Permission Set     │
                  │  ─────────────────────────    │
                  │  permission_snapshots table   │
                  │                              │
                  │  user_id, snapshot (JSON),   │
                  │  expires_at, fingerprint     │
                  └──────────────────────────────┘
```

---

## 5. Event-Driven Synchronization Flow

### 5.1. Source-to-Event Map

Setiap perubahan pada layer manapun **harus** memicu event. Event kemudian didengar oleh listener yang membangun ulang permission user.

```
┌─────────────────────────────────────────────────────────────────────┐
│ ORGANIZATIONAL CHANGE SOURCE                                         │
└────────────────────────┬────────────────────────────────────────────┘
                         │
        ┌────────────────┼─────────────────────────────┐
        │                │                             │
        ▼                ▼                             ▼
   IDENTITY           PROFILE/GTK                 ORGANIZATIONAL
   primary_role       GtkProfile create/update    GtkEmployment *
                     employment_status           GtkWorkUnit *
                                                GtkCareerPath *
                                                GtkAdditionalTask *
                                                GtkTransferRequest *
                                                TeachingAssignment *
                                                StudyGroup.homeroom_teacher_id change
                                                User.is_active toggle
                                                User deletion
                                                Academic year change
```

### 5.2. Domain Events

| Event | Trigger | Listener |
|-------|---------|----------|
| `IdentityAssigned` | User.created | `AssignIdentityListener` |
| `IdentityKindChanged` | User.primary_role changed | `ReconcileIdentityListener` |
| `GtkProfileUpserted` | GtkProfileObserver created/updated | `RebuildPermissionsListener` |
| `PositionUpserted` | GtkEmploymentObserver created/updated | `RebuildPermissionsListener` |
| `PositionDeleted` | GtkEmploymentObserver deleted | `RebuildPermissionsListener` |
| `WorkUnitUpserted` | GtkWorkUnitObserver created/updated | `RebuildPermissionsListener` |
| `WorkUnitDeleted` | GtkWorkUnitObserver deleted | `RebuildPermissionsListener` |
| `CareerPathUpserted` | GtkCareerPathObserver created/updated | `RebuildPermissionsListener` |
| `CareerPathDeleted` | GtkCareerPathObserver deleted | `RebuildPermissionsListener` |
| `AdditionalTaskUpserted` | GtkAdditionalTaskObserver created/updated | `RebuildPermissionsListener` |
| `AdditionalTaskDeleted` | GtkAdditionalTaskObserver deleted | `RebuildPermissionsListener` |
| `TeachingAssignmentUpserted` | TeachingAssignmentObserver created/updated | `RebuildPermissionsListener` |
| `TeachingAssignmentDeleted` | TeachingAssignmentObserver deleted | `RebuildPermissionsListener` |
| `HomeroomChanged` | StudyGroupObserver homeroom_teacher_id changed | `RebuildPermissionsListener` (×2: old & new teacher) |
| `TransferRequestApproved` | GtkTransferRequestObserver status→APPROVED | `RebuildPermissionsListener` |
| `UserToggledActive` | UserObserver is_active changed | `RevokePermissionsListener` |
| `UserDeleted` | UserObserver deleted | `RevokePermissionsListener` |
| `AcademicYearActivated` | AcademicYearObserver is_active toggled | `RebuildPermissionsBatchListener` (for affected GTK) |
| `GtkStatusChanged` | GtkEmploymentObserver status_kepegawaian changed | `RebuildPermissionsListener` |
| `GtkMutated` | GtkTransferRequestObserver status→APPROVED (extended payload) | `RebuildPermissionsListener` |
| `GtkRetired` | GtkPensionObserver status→COMPLETED | `RevokePermissionsListener` |
| `GtkResigned` | GtkEmploymentObserver status='RESIGNED' | `RevokePermissionsListener` |

### 5.3. Listener Pipeline

```
[EVENT] → Queue → [Listener::handle] → [PermissionBuilder::build] →
[SnapshotStore::save] → [PermissionCache::bust] → [AuditLog::write]
```

Semua listener **queue:async**, kecuali event yang sangat latency-sensitive (mis. `UserToggledActive` untuk kasus suspending akun, boleh sync).

### 5.4. Event Carriers (immutable payloads)

Setiap event membawa:

```php
final class PositionChanged
{
    public function __construct(
        public readonly string $userId,
        public readonly string $triggerSource, // 'GtkEmployment.update'
        public readonly string $triggerField,  // 'jabatan'
        public readonly array  $oldValue,      // hashed
        public readonly array  $newValue,      // hashed
        public readonly string $causationId,   // ULID for tracing
    ) {}
}
```

Satu event untuk semua sumber; listener tidak perlu tahu sumber detail (observer tidak perlu membedakan).

---

## 6. Effective Permission Builder

### 6.1. Class & Responsibility

`App\Authorization\Builders\EffectivePermissionBuilder` adalah single-entry-point.

Input: `User` (atau string userId)
Output: `EffectivePermission` object

```
build(User $user): EffectivePermission
buildFor(Request $request, string $resourceScope): EffectivePermission
```

### 6.2. Composition Steps

```php
class EffectivePermissionBuilder
{
    public function build(User $user): EffectivePermission
    {
        if (! $user->is_active) {
            return EffectivePermission::empty();
        }

        $identity = $this->resolveIdentity($user);
        $profile  = $this->resolveProfile($user);
        $positions = $this->collectPositions($user);
        $assignments = $this->collectAssignments($user);
        $temporary = $this->collectTemporary($user);
        $revoked = $this->collectRevoked($user);

        $bag = new PermissionBag();

        // 1. Identity-level
        $bag->add($identity->permissions());

        // 2. Profile-derived
        $bag->add($profile->permissions());

        // 3. Position-level (per position, with hierarchy inheritance)
        foreach ($positions as $position) {
            $permission = $this->positionPermissionRegistry
                ->resolve($position);
            $bag->add($permission);
        }

        // 4. Assignment-level (resource-scoped)
        foreach ($assignments as $assignment) {
            $bag->addScoped($assignment->permissions(), $assignment->scope());
        }

        // 5. Temporary boost
        $bag->add($temporary->permissions());

        // 6. Revoke
        foreach ($revoked as $r) {
            $bag->remove($r);
        }

        return $bag->finalize();
    }
}
```

### 6.3. Registry-based Position Permission

Bukan PHP array hard-coded. Pakai **`PositionPermissionRegistry`** berbasis config + database.

Setiap posisi organisasional punya:
- **Code** (mis. `gtk.guru`, `gtk.wali_kelas`)
- **Parent** (untuk inheritance)
- **Permissions** (flat list)
- **Exclude** (permissions yang dikurangi jika punya parent tertentu)

```php
// config/authorization/positions.php
return [
    'gtk.kepala_sekolah' => [
        'inherits' => ['gtk.wakil_kepala_sekolah'],
        'permissions' => [
            'gtk.manage',
            'gtk.assign_position',
            'gtk.approve_transfer',
            'academic.curriculum.set',
            'laporan.institutional.view',
        ],
    ],

    'gtk.wakil_kepala_sekolah' => [
        'inherits' => ['gtk.koordinator'],
        'permissions' => [
            'gtk.manage',
            'gtk.assign_position',
            'academic.curriculum.edit',
        ],
    ],

    'gtk.koordinator' => [
        'inherits' => ['gtk.guru'],
        'permissions' => [
            'gtk.coordinate',
            'subject.score.aggregate',
        ],
    ],

    'gtk.guru' => [
        'permissions' => [
            'gtk.teach',
            'student.read.self_class',
            'subject.score.input',
            'gtk.profile.read.self',
            'gtk.profile.edit.self',
            'laporan.learning.submit',
        ],
    ],

    'gtk.wali_kelas' => [
        'permissions' => [
            'student.read.homeroom',
            'student.attendance.input.homeroom',
            'student.counseling.input.homeroom',
            'laporan.homeroom.submit',
        ],
    ],

    'gtk.homeroom_assignment' => [
        'permissions_scoped' => [
            'homeroom.score.finalize',
            'homeroom.report_card.print',
        ],
    ],

    // ...
];
```

### 6.4. Hierarchical Inheritance

`gtk.kepala_sekolah` mewarisi seluruh permission `gtk.wakil_kepala_sekolah` → `gtk.koordinator` → `gtk.guru`. Inheritance menggunakan DAG (boleh multiple inherit) + cycle detection pada boot.

### 6.5. Assignment Scoping

Mis. `GtkAdditionalTask` untuk “Koordinator Tahfidz” → permission `tahfidz.coordinate` di-scope ke seluruh sekolah. `TeachingAssignment` untuk “Matematika XII-A” → permission `subject.score.input` di-scope ke `(subject_id, study_group_id, academic_year_id)`.

```php
final class ScopedPermission
{
    public function __construct(
        public readonly string $permission,
        public readonly array  $scope,    // ['subject_id' => '...', 'study_group_id' => '...']
        public readonly Carbon $validFrom,
        public readonly ?Carbon $validUntil,
    ) {}

    public function applies(?array $context = null): bool
    {
        if (! $this->inWindow()) return false;

        foreach ($context as $key => $value) {
            if (isset($this->scope[$key]) && $this->scope[$key] !== $value) {
                return false;
            }
        }
        return true;
    }
}
```

### 6.6. Temporary Assignment & Revocation

`temporary_permissions` dan `revoked_permissions` disimpan di tabel khusus (`temporary_permissions`, `revoked_permissions`) dengan TTL. Rebuild akan mengurus expiry.

---

## 7. Permission Cache & Snapshot Strategy

### 7.1. Mengapa cache

Tanpa cache, setiap request harus:
- Query GtkEmployment
- Query GtkWorkUnit
- Query GtkAdditionalTask
- Query GtkCareerPath
- Query TeachingAssignment
- Query StudyGroup.homeroom
- Resolve inheritance chains
- Calculate final set

Itu mahal per-request.

### 7.2. Two-tier Strategy

**Tier 1: Snapshot Table**
- Tabel `permission_snapshots`:
  - `user_id` (FK, UUID)
  - `fingerprint` (SHA-256 dari structural state saat snapshot dibuat)
  - `permissions` (JSON flat array)
  - `scoped_permissions` (JSON array of {permission, scope, valid_from, valid_until})
  - `computed_at` (datetime)
  - `expires_at` (datetime — TTL, default 24 jam)
- Saat listener rebuild, snapshot ditimpa.
- Saat reads, jika fingerprint cocok → cache hit; jika tidak → trigger rebuild.

**Tier 2: Process-level Memory Cache**
- In-memory cache (`Cache::remember('perm:'.$userId, ...)`) dengan TTL 60 menit.
- Key: `permissions:user:{userId}:{fingerprint}`.
- Fingerprint: hash dari structural state identifier (bukan isi, agar cepat).
- Listener bust cache ini.

### 7.3. Fingerprint Strategy

```php
final class UserPermissionFingerprint
{
    public static function of(User $user): string
    {
        $parts = [
            // Identity
            'u' . $user->id,
            'ia' . $user->is_active,

            // Profile last-modified
            'p' . optional($user->gtkProfile)->updated_at?->timestamp ?? 0,

            // Employment
            'e' . optional($user->employment)->updated_at?->timestamp ?? 0
              . 'es' . (optional($user->employment)->status_kepegawaian ?? 'x')
              . 'ej' . (optional($user->employment)->jabatan ?? 'x'),

            // Work units
            'wu' . $user->gtkWorkUnits()
                       ->selectRaw('MAX(updated_at), COUNT(*)')
                       ->get()->toJson(),

            // Career paths
            'cp' . $user->careerPaths()->active()->count(),

            // Additional tasks
            'at' . $user->additionalTasks()->active()->count(),

            // Teaching assignments
            'ta' . $user->teachingAssignments()->count()
              . 'tas' . $user->teachingAssignments()->sum('updated_at'),

            // Homeroom
            'hr' . StudyGroup::where('homeroom_teacher_id', $user->id)->count(),
        ];

        return hash('sha256', implode('|', $parts));
    }
}
```

Listener triggers rebuild hanya jika fingerprint berubah. Snapshot juga fingerprint-aware.

### 7.4. Cache Invalidation Rules

| Trigger | Action |
|---------|--------|
| `PositionChanged` listener complete | Bust `Cache::tags(['perms:'.$userId])` |
| User logs in | Bust session-level cache (security) |
| Snapshot TTL elapsed | Rebuild on next read |
| Admin explicitly clears cache | `php artisan auth:cache-reset --user=...` |
| `UserToggledActive` | Bust + rebuild to empty |
| `UserDeleted` | Clear snapshot, clear cache |

---

## 8. Hierarki Jabatan

### 8.1. Hierarchical Tree

```
gtk.pimpinan_yayasan (Mudir)
└── gtk.wakil_pimpinan (Wadir 1, Wadir 2)
│   └── gtk.kepala_sekolah
│       └── gtk.wakil_kepala_sekolah
│           └── gtk.koordinator_kurikulum / gtk.koordinator_tahfidz
│               └── gtk.kepala_program / gtk.ketua_jurusan
│                   └── gtk.guru
│                       └── gtk.guru_honor
│
gtk.tenaga_kependidikan
├── gtk.keuangan
├── gtk.operator
├── gtk.kepala_tu
│   └── gtk.staf_tu
└── gtk.pembina_asrama
    └── gtk.pengurus_asrama
```

### 8.2. Cycle Detection

Pada boot, registry melakukan topo-sort terhadap seluruh inherits graph. Dilarang ada cycle — jika dijumpai, boot fail dengan exception.

### 8.3. Effective Level

Untuk middleware seperti `MinRoleLevel`, kita tidak lagi menggunakan level tabel `roles`. Sebagai gantinya, kita gunakan **computed level** dari position tertinggi:

```php
$positionLevel = max(array_map(fn($p) => $p->effectiveLevel, $positions));
```

Tiap position punya `effectiveLevel: int` dari config (`gtk.kepala_sekolah = 1`, `gtk.guru = 5`, dst.). Admin muda (gtk.operator) = 10, dst.

---

## 9. Mapping Sumber Permission: Tabel → Resource Position

### 9.1. Position Sources

| Tabel | EffectivePosition Class | Input ke builder? |
|-------|--------------------------|---------------------|
| `gtk_employments` | `PrimaryPosition` | ✅ jabatan + school_id + academic_year_id + status_kepegawaian |
| `gtk_work_unit` | `WorkUnitPlacement` | ✅ (jika primary) atau additional |
| `gtk_career_paths` | `CareerPosition` | ✅ jabatan_fungsi + tmt/tst |
| `gtk_additional_tasks` | `AdditionalTask` | ✅ decree + hours + tmt/tst |
| `teaching_assignments` | `TeachingAssignment` | ✅ subject+rombel+year + status |
| `study_groups.homeroom_teacher_id` | `HomeroomAssignment` (derived) | ✅ via StudyGroupObserver |
| `gtk_transfer_requests` | `TransferEffect` | ✅ on status→APPROVED |
| `gtk_pensions` | `RetirementEffect` | ✅ on status='completed' |
| `users.is_active` | `IdentityEffect` | ✅ always |
| `academic_years.is_active` | `YearEffect` | ✅ on year toggle |
| `gtk_personal_divisions` (`user_divisi_subscriptions`) | `DivisionMembership` | ⏳ future |

### 9.2. Primary Position Resolution

```
User GtkProfile exists?
  NO  → return null (user belum GTK)
  YES → load GtkEmployment where status_kepegawaian is active
         if multiple → pick most recent via tmt
         if none → GTK position with status "not_assigned_yet"
```

### 9.3. Position Validation Rules

- `tmt` > `tst` → invalid (validation error saat create)
- Position dengan `tst` di masa lalu → tidak contribute ke permission
- `academic_year_id` harus dalam window tahun ajaran aktif (validator)
- Multiple GtkEmployment untuk satu user: hanya yang terbaru (berdasarkan `tmt`) yang dipakai sebagai Primary. Sisanya menjadi historical.

### 9.4. Specific Scoped Permissions

| Assignment | Scoped permissions | Resources |
|------------|---------------------|-----------|
| `TeachingAssignment` (subject X, rombel Y) | `subject.score.input`, `subject.score.finalize`, `subject.material.upload` | subject_id, study_group_id, academic_year_id |
| `StudyGroup.homeroom_teacher_id` (Y) | `student.read.homeroom`, `student.counsel.input.homeroom`, `homeroom.report.finalize` | study_group_id |
| `GtkAdditionalTask` (Koordinator Tahfidz) | `tahfidz.coordinate`, `tahfidz.assessment.input` | school_id |
| `GtkAdditionalTask` (Pelatih Ekskul) | `ekskul.score.input` | ekskul_id (via decree metadata) |
| `GtkCareerPath` (Tenaga Kependidikan → Operator) | `operator.dashboard.view`, `dokumen_iso.manage` | school_id |
| `GtkTransferRequest` (approved) | triggers rebuild on transition date | user_id |
| `GtkPension` | revoke non-Super-Admin permissions on effective date | user_id |

---

## 10. Spatie Permission: Tetap Pakai atau Buang?

### 10.1. Rekomendasi: Spatie Jadi Permission Registry Only

**Alasan**:

1. Sidebar (`sidebar_menu_role` table) sudah menyimpan pivot Spatie-style. Mengganti ini berarti migrasi besar dengan dampak ke seluruh menu sidebar.
2. Middleware `role:`, `permission:` Laravel, dan trait HasRoles dipakai luas di tests dan views.
3. Spatie adalah identitas yang stabil — `Super Admin` adalah identitas, ‘gtk’ adalah identitas. Tidak salah untuk Spatie menyimpan **role identitas**.

**Tapi**: Secara bertahap kita kurangi ketergantungan pada Spatie sebagai **pengambil keputusan**.

### 10.2. Strategi Bertahap

| Phase | Tindakan |
|-------|----------|
| Sekarang | Spatie menyimpan role identitas (GTK, Super Admin, Wali Santri, Peserta Didik, Alumni, Guest) saja. |
| Phase 4-5 | Controllers dilarang `assignRole('Kepala Sekolah')`. Hanya `assignRole('gtk')` (identity) yang boleh dipanggil. Jabatan via Spatie dihapus pelan-pelan. |
| Phase 6 | Role Spatie yang bukan identitas (Wakil Kepala Sekolah, dst.) diberi flag deprecated dan dikecualikan dari sync. |
| Phase 7 (future) | Tabel `roles` dan `model_has_roles` bisa dihapus seluruhnya. Identitas disimpan di `users.primary_role` (string). |

### 10.3. Transitional Phase

Untuk sementara, keduanya hidup berdampingan:

- **Identity Role** (Spatie, stabil): GTK, Super Admin, Wali Santri, Peserta Didik, Alumni
- **Position Role** (Spatie, deprecated): Wakil Kepala Sekolah, dst — TETAP di database tapi **dikeluarkan** dari RoleSynchronizationService

Listeners **hanya boleh assign identity roles**, bukan position roles. Position-based permission datang dari `EffectivePermissionBuilder`.

### 10.4. `HasPermissionThroughIdentity`

User masih `HasRoles` tapi role-nya hanya identitas:

```php
$user->hasRole('gtk');                  // OK (identity)
$user->hasRole('Wadir 1');              // DEPRECATED (now via permission)
$user->can('gtk.coordinate');           // OK (effective permission)
```

`HasRoles` trait ditambah helper baru:

```php
public function can(string $ability, ...$args): bool
{
    // 1. Normal Spatie can() check
    if (parent::can($ability, ...$args)) return true;

    // 2. EffectivePermissionBuilder check
    return app(EffectivePermissionBuilder::class)
        ->forUser($this)
        ->can($ability, ...$args);
}
```

---

## 11. Strategi Refactor Controller: dari `hasRole()` ke `can()`

### 11.1. Inventory Awal (jumlah penggunaan)

| Pola | Estimasi | Lokasi |
|------|----------|--------|
| `$user->hasRole('X')` | ~80+ | controllers, middleware, services |
| `$user->hasAnyRole([...])` | ~15 | controllers |
| `$user->getRoleNames()` | ~10 | middleware, sidebar composer |
| `$user->can(...)` | existing | controllers |
| `middleware('role:X')` | none (pakai RoleMiddleware) | routes/web.php |
| `middleware('permission:X')` | none | routes/web.php |

### 11.2. Refactor Plan per-controller

Untuk setiap controller yang punya `hasRole('GTK')`:

1. Identifikasi **kemampuan** yang sebenarnya dicek (‘siswa boleh lihat profil sendiri’).
2. Buat permission name baru (`gtk.profile.read.self`).
3. Update `PositionPermissionRegistry` agar role/position tertentu punya permission itu.
4. Ganti `$user->hasRole('GTK')` dengan `$user->can('gtk.profile.read.self')`.
5. Tests remain green.

Pengerjaan dilakukan per-modul, prioritas: yang paling banyak duplikasi (AbsensiHarianController, NilaiKelasController, GtkAdditionalTaskController).

### 11.3. Testing Strategy

- Unit test: `EffectivePermissionBuilder` per skenario (tanpa dan dengan assignments).
- Integration test: trigger event → assert snapshot rebuild → assert can().
- Drift test: `php artisan auth:drift` compares current state vs computed state.

---

## 12. Audit Metode Lama dan Rekomendasi

Berikut ringkasan audit pola authorization yang sudah ada di Alim dan rekomendasi di arsitektur baru.

### 12.1. Pola Spatie langsung

| Pola | Status sekarang | Rekomendasi di arsitektur baru |
|------|-----------------|--------------------------------|
| `$user->assignRole('GTK')` | Dipakai di GtkWizardController, PersonaliaController, SchoolSeeder, UserController, CandidateController | Hanya untuk **identity role**. Panggil dari listener saja. Controllers tidak boleh langsung `assignRole`. |
| `$user->syncRoles($validated['roles'])` | Dipakai di SuperAdmin\UserController, PersonaliaController#changeRole | Deprecated untuk position roles. Hanya identitas boleh disync. Controller lain tidak boleh langsung mengubah role. |
| `$user->roles()->detach()` | GtkController@destroy, GtkController#bulkDelete | Dilarang. Pakai `RevokePermissionsListener` yang destroy akan picu. |
| `$user->hasRole('GTK')` | 80+ instances | Tetap boleh **hanya untuk identity check** (Super Admin, GTK, Wali Santri). Dilarang untuk position. |
| `$user->hasRole('Wakil Kepala Sekolah')` | di AbsensiHarianController dll | Refactor ke `$user->can('gtk.coordinate')` style. |
| `$user->hasAnyRole([...])` | controllers | Refactor ke `$user->canAny([...])` atau pecah jadi beberapa `can()` calls. |
| `$user->hasPermissionTo(...)` | routes + middleware | Tetap boleh untuk permission identity level. |
| `$user->givePermissionTo(...)` | none direct | Dilarang di controller. Permission adalah derived artifact. |
| `$user->syncPermissions(...)` | none direct | Dilarang di controller. |
| `$user->revokePermissionTo(...)` | none direct | Dilarang. Effect via revoke table. |

### 12.2. Gate & Middleware

| Pola | Status | Rekomendasi |
|------|--------|-------------|
| `Gate::define(...)` | rare usage | OK; buat gates baru yang delegate ke EffectivePermissionBuilder. |
| `middleware('role:X')` | tidak dipakai (pakai RoleMiddleware custom) | OK untuk identity. |
| `middleware('permission:X')` | tidak dipakai | OK, tambahkan PermissionMiddleware jika perlu. |
| `RoleMiddleware` | ✅ | Tetap, hanya untuk identity role. |
| `MinRoleLevel` | ✅ | Tinggalkan jika pakai Effective Level. Pertahankan sebagai legacy fallback. |
| `RoleEnforced` | ✅ | OK untuk secure token. |
| `EnsureEmployeeAccess` | ✅ | Tetap, redirect no-identity users. |

### 12.3. Sidebar & Menu

| Pola | Status | Rekomendasi |
|------|--------|-------------|
| `SidebarComposer` pakai `$user->roles` | Ada | Refactor ke `$user->effectivePermissions` plus menu_role_acl. |
| `SidebarMenu::scopeAccessibleBy($roleIds)` | Ada | Pakai `$user->effectivePermissions->asRoleIds()` agar compatible. |
| `sidebar_menu_role` table | Ada | Tetap. Pakai sebagai row-level menu configuration. |
| `config/sidebar.php` | Ada | Tambah flag `requires_permission: 'gtk.coordinate'`. |

---

## 13. Strategi Migrasi Bertahap (tanpa break fitur)

### Phase 1 — Foundation & Parallel Build (3-4 hari)

**Tujuan**: Membangun infrastruktur tanpa mengubah perilaku yang ada.

1. Tabel `permission_snapshots` (+ migration).
2. Tabel `temporary_permissions` (+ migration) — kolom: user_id, permission, valid_from, valid_until.
3. Tabel `revoked_permissions` (+ migration) — kolom: user_id, permission, reason, revoked_at.
4. Tabel `position_permission_overrides` (+ migration) — kolom: position_code, permission, mode (add|remove), scope.
5. Class `App\Authorization\Contracts\PositionSource` (interface).
6. Class `App\Authorization\PositionResolver` — collects semua positions dari User.
7. Class `App\Authorization\Builders\PermissionBag` — flat set + scoped set.
8. Class `App\Authorization\EffectivePermission` — immutable result.
9. Config `config/authorization/positions.php` — placeholder.
10. Trait `HasEffectivePermissions` on User — first stub returning empty.

**Pass criteria**: Tidak ada controller, route, atau middleware yang berubah. Tidak ada test yang fail.

### Phase 2 — Registry & Effective Permission (5-7 hari)

**Tujuan**: Resolve semua position ke permission.

1. PositionPermissionRegistry — resolve + inheritance + cycle detection.
2. Position source classes:
   - `GtkEmploymentPosition`
   - `GtkWorkUnitPosition`
   - `GtkCareerPathPosition`
   - `GtkAdditionalTaskPosition`
   - `TeachingAssignmentPosition`
   - `HomeroomPosition` (derived)
   - `RetirementPosition`
   - `ResignationPosition`
3. `EffectivePermissionBuilder::build()` — gabungkan semuanya.
4. Snapshot generator + store.
5. Cache strategy (fingerprint + memory cache).
6. Unit tests untuk builder di semua skenario.

**Pass criteria**: Builder lulus unit test. Snapshot terisi. Cache aktif.

### Phase 3 — Events & Observers (4-5 hari)

**Tujuan**: Setiap perubahan organisasi memicu rebuild.

1. Event class `PositionChanged`, `IdentityAssigned`, `UserToggledActive`, `UserDeleted`, `TransferRequestApproved`, `GtkRetired`.
2. Listeners:
   - `RebuildPermissionsListener` (queue:async, default)
   - `RevokePermissionsListener` (sync, untuk UserToggledActive dan deletion)
3. Observers (created/updated/deleted → dispatch event):
   - GtkEmploymentObserver
   - GtkWorkUnitObserver
   - GtkCareerPathObserver
   - GtkAdditionalTaskObserver
   - TeachingAssignmentObserver (extend existing)
   - GtkTransferRequestObserver
   - StudyGroupObserver (extended to homeroom_teacher_id)
   - GtkPensionObserver
   - UserObserver (extend existing)
4. Register semua di AppServiceProvider.
5. Integration test: trigger → snapshot terupdate.

**Pass criteria**: Setiap perubahan organisasional akan mengubah snapshot & cache.

### Phase 4 — Controller Refactor (Bertahap) (10-15 hari)

**Tujuan**: Hilangkan hardcoded `hasRole()` di controllers.

Pendekatan per-modul:

1. Mulai dari modul **GTK** (paling banyak duplikasi).
2. Pilih 1 controller paling sederhana (GtkController).
3. Daftar setiap `hasRole('X')` di controller.
4. Tentukan permission name untuk X.
5. Update `PositionPermissionRegistry` agar posisi-organisasional yang relevan punya permission tsb.
6. Refactor controller.
7. Test ulang modul.
8. Lanjut ke controller berikutnya.

Bukan boleh memakai aggressive sweep; kerjakan satu per satu untuk meminimalkan blast radius.

### Phase 5 — Admin Mode Switch (1-2 hari)

**Tujuan**: Matikan hard edit role.

1. Flag `ALLOW_MANUAL_ROLES=false` di env.
2. `BlockManualRoleEdit` middleware mengaktifkan dirinya berdasarkan flag.
3. `changeRole()` di PersonaliaController, `assignRoles()` di UserController dipindah ke “Request Role Change” workflow.
4. Tests green.

### Phase 6 — Drift Detection & Cleanup (2-3 hari)

**Tujuan**: Pastikan sistem tetap stabil jangka panjang.

1. `php artisan auth:drift --json` — mendeteksi user dengan snapshot-fingerprint yang tidak match.
2. CI hook: jalankan drift di deploy.
3. Dashboard widget Super Admin menampilkan drift count.
4. Cleanup deprecated Spatie roles (beri flag `legacy=true` di config/permission).
5. Documentasikan arsitektur resmi di `docs/authorization-architecture.md`.

### Phase 7 (future, opsional) — Lepas Spatie

1. Migrasi `roles` & `model_has_roles` ke tabel internal.
2. `users.primary_role` jadi authoritative identity source.
3. Hapus HasRoles trait.

---

## 14. Tambahan: Resiko yang Diidentifikasi Selama Redesign

| Resiko | Mitigasi |
|--------|----------|
| Spatie cache drift | Listener bust cache setelah rebuild. Drift detection di CI. |
| Cycle di inheritance graph | Cycle detection saat boot. |
| Performa recalc tiap request | Snapshot + memory cache (fingerprint-keyed). |
| Banyak event menyebabkan cascade | Event coalescing — jika event beruntun dalam 5 detik, batch mereka. |
| Migration dari role-based UI ke permission-based UI | Refactor bertahap per-modul. |
| Drift pada production existing | Jalankan backfill script `--rebuild-all-users` setelah Phase 3. |

---

## 15. Ringkasan Perubahan Struktural (one-pager)

**Dari**:
```
Users→SpRole→SpPermission→Menu/Policies
GtkXxx tables (5+) → tidak terkait langsung ke permission
```

**Menjadi**:
```
User (identity_role) ─┐
                       │
GtkProfile ────────────┤
                       ├─→ EffectivePermissionBuilder ─→ Snapshot ─→ Cache ─→ Gate/Policy/Middleware/Sidebar
GtkEmployment ─────────┤                ↑
                       │            PositionPermissionRegistry
GtkWorkUnit ───────────┤              (+ inheritance + revocation)
                       │            ↑
GtkCareerPath ─────────┤            │
                       │     Scoped assignments
GtkAdditionalTask ─────┤     (TeachingAssignment, Homeroom, dll.)
                       │
TeachingAssignment ────┤
                       │
StudyGroup.homeroom ───┤
                       │
GtkTransferRequest ────┤
GtkPension ────────────┘
```

**Perubahan terbesar dari desain sebelumnya**:
1. Spatie jadi registry identitas, bukan business logic.
2. Effective Permission digabung dari banyak sumber.
3. Inheritansi hierarki jabatan.
4. Snapshot + fingerprint cache.
5. Setiap perubahan organisasional memicu event dan rebuild.
6. Controllers tidak boleh `assignRole`/`syncRoles`/`hasRole('Position')`.
7. Resource-scoped permission untuk assessment, nilai, dsb.

---

## 16. Kesimpulan

Desain sebelumnya (audit awal) menyederhanakan masalah sebagai “synchronize role dari GTK data”. Padahal masalah sebenarnya bukan “sync role” tetapi **membangun ulang authorization system yang source-of-truth-nya adalah struktur organisasi**, dengan Role hanya salah satu dari beberapa input.

Desain baru ini:
- Memisahkan empat concern: identity, profile, position, assignment.
- Menggabungkan semuanya di Effective Permission Builder.
- Meng-cache hasilnya dengan snapshot + fingerprint.
- Memicu rebuild otomatis dari event organizasional manapun.
- Mempertahankan Spatie untuk transisi tanpa break fitur.

Setelah disetujui, saya akan mulai dari Phase 1 (Foundation & Parallel Build) sesuai urutan di §13, satu phase pada satu waktu, dengan pass-criteria ketat agar tidak ada regresi.
