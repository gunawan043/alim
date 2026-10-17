# Student Lifecycle — Event-Driven Architecture

## Context

Sistem ALIM saat ini sudah punya komponen lifecycle (StudentPromotion, StudentMutationOut/In, StudentClassHistory) yang bekerja, tetapi **semua logika bisnis dipicu langsung dari controller** — tanpa event, observer, atau job. Akibatnya:

1. **Bug enum aktif**: `StudentMutationOutController::approve()` baris 215 mencoba menulis `status = 'transfer_out'`, tapi enum di `students.status` (sebelum migrasi) hanya 5 nilai: `active, inactive, graduate, dropped, transfer`. Setiap approval mutasi-keluar dengan `out_type=mutation` gagal dengan SQL `Data truncated` di production.
2. **Gap mutasi-masuk**: `StudentMutationInController::approve()` membuat/ update baris `Student` dengan `status='active'`, **tapi tidak pernah membuat `StudentClassHistory`**. Siswa mutasi-masuk berakhir aktif tanpa rombel.
3. **Tidak ada orkestrasi**: proses naik kelas, lulus, mutasi keluar/masuk tidak memicu event domain. Tidak ada cara untuk menambah side-effect (audit, notifikasi wali, sinkronisasi rapor) tanpa menyentuh controller.
4. **Tidak ada single source of truth**: status siswa di-update paksa dari banyak controller, tanpa observability.
5. **Belum ada job async**: `QUEUE_CONNECTION=sync`, semua proses memblokir HTTP request.

**Tujuan**: Mengubah sistem ini menjadi **lifecycle-aware** — setiap perubahan status siswa memicu event domain. Listener bersifat tipis dan mendispatch job. Job menangani side-effect (audit, notifikasi, sinkronisasi). Status siswa menjadi **observable, konsisten, dan scalable** untuk Dapodik/EMIS-grade use case.

**Mulai dari kondisi saat ini** (migrasi enum dan tabel audit sudah dibuat di file `.skip`, di-apply ke DB lokal): students.status sudah punya 6 nilai, dan tabel `student_lifecycle_audits` sudah tersedia.

---

## Existing Conventions to Reuse

Sistem **sudah** punya sebagian infrastruktur event-driven yang harus kita pakai (bukan duplikasi):

| Komponen | Path | Status |
|---|---|---|
| `App\Events\StudentAssignedToRombel` | `app/Events/StudentAssignedToRombel.php` | ✅ Ada, sudah lengkap dengan DTO + SerializesModels |
| `App\Listeners\ProvisionStudentAcademicDataListener` | `app/Listeners/ProvisionStudentAcademicDataListener.php` | ✅ Ada, implements `ShouldQueue`, queue=`academic-provision` |
| `App\Jobs\ProvisionStudentAcademicDataJob` | `app/Jobs/ProvisionStudentAcademicDataJob.php` | ✅ Ada, sudah handle provisioning rapor/absensi |
| `App\Events\GtkProfileUpdated` + `StudyGroupSubjectChanged` + `TeachingAssignmentChanged` | `app/Events/*` | ✅ Pattern sudah ada di `EventServiceProvider::$listen` |
| Observer convention: `Model::observe(Observer::class)` di `AppServiceProvider::boot()` | `app/Providers/AppServiceProvider.php:92-93, 107-109` | ✅ |
| Queue: sync default, dengan `database-uuids` failed jobs | `config/queue.php` | Sync dipakai local, production rencana ke `database` |

**Observers sudah ada** (dan tidak boleh dibuat ulang):
- `App\Observers\StudentObserver` — sudah ada (existing logic, kita tambahkan)
- `App\Observers\StudentClassHistoryObserver` — sudah ada, hanya warning saat konflik active (kita pertahankan)
- `App\Observers\WaliSantriObserver` — sudah ada

**Yang akan kita tambahkan**: 4 event lifecycle + 1 listener tipis + 1 audit job + 1 enrich observer. Sisanya adalah refactor controller.

---

## Architecture

### Event flow (target)

```
┌─────────────────────────────────────────────────────────────────────┐
│ CONTROLLER                                                          │
│   • StudentPromotionController::execute()                           │
│   • StudentMutationOutController::approve()                         │
│   • StudentMutationInController::approve()                          │
│                                                                     │
│   → Update Student.status (satu sumber kebenaran)                    │
│   → Create / deactivate StudentClassHistory                         │
│   → event(new StudentPromoted(...))   <-- explicit dispatch          │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│ DOMAIN EVENT                                                         │
│   StudentPromoted     → listens to: SyncStudentRombelAfterLifecycle  │
│   StudentGraduated    → listens to: SyncStudentRombelAfterLifecycle  │
│   StudentMutatedOut   → listens to: SyncStudentRombelAfterLifecycle  │
│   StudentMutatedIn    → listens to: SyncStudentRombelAfterLifecycle  │
│                                                                     │
│   All four also → listens to: RecordStudentLifecycleAudit (sync)     │
└─────────────────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┴────────────────┐
              ▼                                ▼
┌──────────────────────────┐   ┌──────────────────────────────────────┐
│ LISTENER (sync)          │   │ LISTENER (ShouldQueue)                │
│ RecordStudentLifecycle   │   │ SyncStudentRombelAfterLifecycle       │
│ AuditListener            │   │   → dispatch RecordLifecycleAuditJob   │
│                          │   │   → dispatch ProvisionStudentAcademic  │
│                          │   │     DataJob (kalau ada rombel baru)   │
└──────────────────────────┘   └──────────────────────────────────────┘
```

### Status state machine (single source of truth)

```
   ┌────────┐  promote  ┌─────────┐  (lulus)  ┌──────────┐
   │ active │ ────────► │ promoted│ ────────► │ graduated│ ──► alumni
   └────┬───┘           └─────────┘           └──────────┘
        │  mutate_out                       (alumni adalah status turunan:
        ▼                                    graduates di-cap tahun ajaran)
   ┌──────────────┐  mutate_in  ┌─────────┐
   │ transfer_out │ ──────────► │ active  │
   └──────────────┘             └─────────┘
        │ drop_out
        ▼
   ┌──────────┐
   │ dropped  │
   └──────────┘
```

Tidak ada perubahan status yang boleh ditulis langsung ke kolom `students.status` selain oleh controller lifecycle atau observer. Event **bukan** pengubah status — event **mencatat** apa yang sudah terjadi.

---

## Phase 1 — Foundation (Files Created)

### 1.1 Event classes (4)

Buat di `app/Events/StudentLifecycle/`:

- **`StudentPromoted.php`** — promoted dari rombel X tahun ajaran A → rombel Y tahun ajaran B
- **`StudentGraduated.php`** — siswa diluluskan
- **`StudentMutatedOut.php`** — siswa keluar (transfer/dropout, bedanya via `reason`)
- **`StudentMutatedIn.php`** — siswa masuk dari sekolah lain

Setiap event:
- `use Dispatchable, SerializesModels;`
- Punya public string fields (UUID + enum reason) — BUKAN object Model di dalam constructor (Laravel queue anti-serialization loop)
- Punya static `dispatchFrom(Student, ...)` helper atau factory method agar controller tidak salah passing argumen

```php
// app/Events/StudentLifecycle/StudentPromoted.php
final class StudentPromoted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $studentId,
        public readonly string $fromStudyGroupId,
        public readonly string $fromAcademicYearId,
        public readonly string $toStudyGroupId,
        public readonly string $toAcademicYearId,
        public readonly string $promotionDetailId,
        public readonly ?string $actorId = null,
    ) {}

    public static function fromDetail(StudentPromotionDetail $d, ?string $actorId = null): self
    {
        $d->loadMissing('promotion', 'student');
        return new self(
            studentId: (string) $d->student_id,
            fromStudyGroupId: (string) $d->promotion->from_study_group_id,
            fromAcademicYearId: (string) $d->promotion->from_academic_year_id,
            toStudyGroupId: (string) $d->promotion->to_study_group_id,
            toAcademicYearId: (string) $d->promotion->to_academic_year_id,
            promotionDetailId: (string) $d->id,
            actorId: $actorId,
        );
    }
}
```

Tiga event lain (Graduated, MutatedOut, MutatedIn) mengikuti pola yang sama, dengan field spesifik.

### 1.2 Listener classes (2)

- **`RecordStudentLifecycleAuditListener`** (`app/Listeners/RecordStudentLifecycleAuditListener.php`)
  - TIDAK implements ShouldQueue — sinkron, ringan
  - `handle()` insert ke `student_lifecycle_audits`
  - 1 listener ini merespons ke-4 event di atas (single-responsibility audit)
- **`SyncStudentRombelAfterLifecycleListener`** (`app/Listeners/SyncStudentRombelAfterLifecycleListener.php`)
  - `implements ShouldQueue`, queue=`lifecycle-sync`
  - Untuk `StudentPromoted` → dispatch `ProvisionStudentAcademicDataJob` untuk rombel baru (existing job — **re-use**)
  - Untuk `StudentMutatedIn` → sama (existing job)
  - Untuk `StudentGraduated` / `StudentMutatedOut` → close semua active rombel siswa (handled di controller + event, listener tidak perlu re-do)
  - **Untuk semua 4** → dispatch `RecordLifecycleAuditJob` ke queue `lifecycle-audit` (async write — lebih cepat dari sync insert di listener)

### 1.3 Job class (1)

- **`RecordLifecycleAuditJob`** (`app/Jobs/RecordLifecycleAuditJob.php`)
  - `implements ShouldQueue`, queue=`lifecycle-audit`
  - Insert ke `student_lifecycle_audits` (idempotent via `event_id` UUID yang dibangkitkan listener)
  - 5 retries, exponential backoff
  - Failed → log + failed_jobs table (sudah ada)

### 1.4 StudentObserver enrichment (edit existing)

`app/Observers/StudentObserver.php` sudah ada. **Tambahkan** (jangan replace):

- `updated()` — saat kolom `status` berubah:
  - Tulis ke log audit (sync, ringkas)
  - Dispatch `StudentStatusChanged` event (tambahan event ke-5) untuk downstream notifikasi wali (FUTURE)

Jangan override logic existing — pakai `if (wasChanged('status'))` guard.

### 1.5 EventServiceProvider registration

Edit `app/Providers/EventServiceProvider.php`. Tambahkan 4 event ke `$listen` array:

```php
protected $listen = [
    // ... existing ...
    StudentLifecycle\StudentPromoted::class => [
        RecordStudentLifecycleAuditListener::class,
        SyncStudentRombelAfterLifecycleListener::class,
    ],
    StudentLifecycle\StudentGraduated::class => [
        RecordStudentLifecycleAuditListener::class,
        SyncStudentRombelAfterLifecycleListener::class,
    ],
    StudentLifecycle\StudentMutatedOut::class => [
        RecordStudentLifecycleAuditListener::class,
        SyncStudentRombelAfterLifecycleListener::class,
    ],
    StudentLifecycle\StudentMutatedIn::class => [
        RecordStudentLifecycleAuditListener::class,
        SyncStudentRombelAfterLifecycleListener::class,
    ],
];
```

Listener yang sudah ada (`StudentAssignedToRombel` → `ProvisionStudentAcademicDataListener`) **tidak boleh** diregistrasi ulang di sini — sudah jalan.

---

## Phase 2 — Controller Refactoring (5 files edited)

### 2.1 `StudentPromotionController::execute()`

**Lokasi**: `app/Http/Controllers/StudentPromotionController.php:227-422`

**Current**: loop di dalam DB transaction, langsung update `Student.status`, `StudentClassHistory.is_active`, dan buat `StudentClassHistory` baru.

**After**:
- Status update **tetap di sini** (controller adalah single-writer untuk status, kecuali observer — observer hanya men-snap status changes, BUKAN jadi source of truth)
- Buat `StudentClassHistory` baru **tetap di sini** (satu sumber kebenaran)
- Tambahkan **`event(new StudentPromoted(...))`** sebelum `DB::commit()` di akhir loop untuk action `promote`
- Tambahkan `event(new StudentGraduated(...))` untuk action `graduate`
- Tambahkan `event(new StudentMutatedOut(...))` untuk action `mutate_out`
- Action `retain` dan `skip` **tidak** memicu event (tidak ada perubahan status)

**Backward compat**: response shape, redirect, error handling **tidak berubah**.

### 2.2 `StudentMutationOutController::approve()`

**Lokasi**: `app/Http/Controllers/StudentMutationOutController.php:202-221`

**Current**:
```php
$newStatus = match ($mutation->out_type) {
    'graduation' => 'graduate',
    'dropout'    => 'dropped',
    default      => 'transfer_out',  // ← BUG: DB tidak punya 'transfer_out' (sebelum migrasi)
};
$mutation->student->update(['status' => $newStatus]);
```

**After**:
- Tambahkan: deactive semua `StudentClassHistory::is_active=true` untuk student ini (`leave_date = now()`)
- Hapus baris `update(['status' => ...])` — biarkan StudentObserver atau controller yang melakukan via event
- **Tambahkan `event(new StudentMutatedOut(...))`** dengan `reason: $mutation->out_type`
- Event listener (`SyncStudentRombelAfterLifecycleListener`) yang akan set `status` final

**Atau** (lebih aman — direct update, event triggered setelah):
- Update status di controller (satu sumber)
- Lalu `event(new StudentMutatedOut(...))` di akhir method

Saya pilih **opsi kedua** — controller sebagai writer, event sebagai auditor/sync-er. Status field adalah factual state, listener **tidak boleh** men-override.

### 2.3 `StudentMutationInController::approve()` (fix the GAP)

**Lokasi**: `app/Http/Controllers/StudentMutationInController.php:145-183`

**Current**: set `status='active'`, no `StudentClassHistory` created.

**After**:
- Setelah set status=`active`, **jika mutation punya `to_study_group_id` (FK baru)**, buat `StudentClassHistory` baru dengan `is_active=true`, `join_date=$mutation->mutation_date`
- `event(new StudentMutatedIn($student, $mutation))` — listener yang sudah ada akan dispatch `StudentAssignedToRombel` sehingga `ProvisionStudentAcademicDataJob` jalan (ini menutup gap besar: siswa mutasi-masuk sekarang punya rapor)

**Migration requirement**: tambah kolom `to_study_group_id` (UUID, nullable) ke `student_mutations_in` agar bisa tahu rombel tujuan. FK ke `study_groups`. **Migration baru**: `2026_06_18_030000_add_to_study_group_to_student_mutations_in.php`.

### 2.4 `BulkPromotionController`

Sama dengan `StudentPromotionController::execute()` — polymorphic dispatcher. Ikuti pola yang sama.

### 2.5 `StudentController` (no changes needed)

CRUD student biasa tidak menyentuh status — biarkan.

---

## Phase 3 — Migration (1 new + 1 applied)

### 3.1 Sudah ada (`.skip` = applied)

- `2026_06_18_020000_extend_students_status_enum.php` → students.status punya 6 nilai (`active, inactive, graduate, dropped, transfer_in, transfer_out`)
- `2026_06_18_020100_create_student_lifecycle_audits_table.php` → tabel audit ada

### 3.2 Migration baru

- **`2026_06_18_030000_add_to_study_group_to_student_mutations_in.php`**
  - `ALTER TABLE student_mutations_in ADD COLUMN to_study_group_id CHAR(36) NULL`
  - `FOREIGN KEY (to_study_group_id) REFERENCES study_groups(id) ON DELETE SET NULL`
  - Nullable karena mutasi-masuk tidak selalu langsung punya rombel (mungkin antri)

---

## Phase 4 — Backward Compatibility & Safety

### Aturan: tidak ada breaking change

- Routes **tidak berubah**
- Response JSON / redirect **tidak berubah**
- View template **tidak berubah**
- DB schema **hanya tambah** (enum extend + audit table + 1 FK column)
- Existing `Student::scopeActive` masih `status='active'` — tetap jalan
- Existing `StudentObserver` ditambah `if` guards, tidak di-replace
- `StudentClassHistoryObserver` **tidak diubah**

### Transaksi

- Controller tetap di dalam `DB::transaction()` seperti sekarang
- Event dispatch di **akhir** transaction, **sebelum commit**, dengan `DB::afterCommit()` jika queue aktif
- Karena default `QUEUE_CONNECTION=sync`, `afterCommit()` tidak relevan — tapi tambahkan untuk production readiness

### Idempotency

- `RecordLifecycleAuditJob` punya `string $auditId` di constructor → idempotent: kalau ada duplicate (mis. retry), insert dengan `id=$auditId` akan replace
- Event listener `RecordStudentLifecycleAuditListener` menghasilkan UUID5 deterministik dari `(event_name, student_id, source_id)` → retry aman

---

## Phase 5 — Testing & Verification

### Unit tests (new)

- `tests/Unit/Events/StudentPromotedTest.php` — event constructor + factory
- `tests/Unit/Events/StudentGraduatedTest.php`
- `tests/Unit/Events/StudentMutatedOutTest.php`
- `tests/Unit/Events/StudentMutatedInTest.php`

### Feature tests (new)

- `tests/Feature/StudentLifecycle/ExecutePromotionDispatchesEventTest.php` — execute 5 siswa (1 promote, 1 graduate, 1 retain, 1 mutate_out, 1 skip) → cek 3 event ter-dispatch, 2 tidak
- `tests/Feature/StudentLifecycle/MutationInCreatesClassHistoryTest.php` — approve mutation_in dengan `to_study_group_id` → StudentClassHistory row ada, `is_active=true`, `ProvisionStudentAcademicDataJob` ter-dispatch
- `tests/Feature/StudentLifecycle/MutationOutDeactivatesClassHistoryTest.php` — approve mutation_out → semua active history siswa di-deactivate, status student berubah
- `tests/Feature/StudentLifecycle/EventListenerInsertsAuditTest.php` — dispatch 1 event → `student_lifecycle_audits` row ada
- `tests/Feature/StudentLifecycle/EventListenerIsIdempotentTest.php` — dispatch event 2x dengan auditId sama → hanya 1 row audit

### Verification (manual)

```bash
# 1. Apply migrations lokal
php artisan migrate

# 2. Run unit + feature tests untuk student lifecycle
php artisan test --compact tests/Feature/StudentLifecycle
php artisan test --compact tests/Unit/Events

# 3. Sanity check: dispatch event manual, lihat audit row
php artisan tinker --execute="App\Events\StudentLifecycle\StudentPromoted::dispatch(...);"

# 4. Cek failed_jobs (kalau queue production sudah jalan)
php artisan queue:failed

# 5. Cek tidak ada error di laravel.log
tail -f storage/logs/laravel.log
```

---

## Critical Files to Modify / Create

### New (8)
1. `app/Events/StudentLifecycle/StudentPromoted.php`
2. `app/Events/StudentLifecycle/StudentGraduated.php`
3. `app/Events/StudentLifecycle/StudentMutatedOut.php`
4. `app/Events/StudentLifecycle/StudentMutatedIn.php`
5. `app/Listeners/RecordStudentLifecycleAuditListener.php`
6. `app/Listeners/SyncStudentRombelAfterLifecycleListener.php`
7. `app/Jobs/RecordLifecycleAuditJob.php`
8. `database/migrations/2026_06_18_030000_add_to_study_group_to_student_mutations_in.php`

### Edited (4)
1. `app/Providers/EventServiceProvider.php` — register 4 event → 2 listener
2. `app/Observers/StudentObserver.php` — tambah `updated()` hook untuk `status` change
3. `app/Http/Controllers/StudentPromotionController.php` — tambah 3 `event()` dispatch
4. `app/Http/Controllers/StudentMutationOutController.php` — deactive history + dispatch event + hapus bug
5. `app/Http/Controllers/StudentMutationInController.php` — buat StudentClassHistory + dispatch event (GAP FIX)
6. `app/Http/Controllers/BulkPromotionController.php` — ikut pola Phase 2.4
7. `app/Models/StudentMutationIn.php` — tambah `to_study_group_id` ke `$fillable`

### New tests (5)
1. `tests/Unit/Events/StudentPromotedTest.php`
2. `tests/Unit/Events/StudentGraduatedTest.php`
3. `tests/Unit/Events/StudentMutatedOutTest.php`
4. `tests/Unit/Events/StudentMutatedInTest.php`
5. `tests/Feature/StudentLifecycle/*` (5 file)

### Reuse (read-only, jangan diubah)
- `app/Events/StudentAssignedToRombel.php`
- `app/Listeners/ProvisionStudentAcademicDataListener.php`
- `app/Jobs/ProvisionStudentAcademicDataJob.php`
- `app/Observers/StudentClassHistoryObserver.php`
- `app/Observers/StudyGroupObserver.php`

---

## Out of Scope (DEFER)

- Queue production setup (redis/supervisor) — tetap sync, hanya struktur job yang siap antri
- WaliSantri notification dispatch — `StudentStatusChanged` event (event ke-5) tidak dibuat di fase ini; observer hanya men-log
- Audit log dashboard UI — back-end siap, view menyusul
- Peminatan-aware promotion (peminatan IPA/IPS/Bahasa) — orthogonal, ditangani di phase terpisah
- `alumni` status — saat ini `graduate` sudah cukup, transisi `graduate → alumni` deferred sampai definisi "tahunajaran X lama" jelas
- Backfill data lama — script untuk mem-backfill `student_lifecycle_audits` dari data existing deferred ke fase terpisah (Fase migrasi data historis)

---

## Risk & Mitigation

| Risk | Mitigation |
|------|-----------|
| Event loop (event → listener → event) | Listener TIDAK dispatch event, hanya job. Job TIDAK dispatch event. Hanya controller dispatch event. |
| Listener terlalu berat (sinkron) | Audit listener sinkron tapi minimal: 1 insert. Sync logic (non-audit) masuk ke job. |
| Migrations `.skip` masih mengganggu | Setelah diverifikasi applied, rename `.skip` → `.php` (konsolidasi di PR) |
| `QUEUE_CONNECTION=sync` menyebabkan job tetap sinkron | `ShouldQueue` tetap di-implement. Production tinggal ubah `.env` ke `database` atau `redis` — tidak perlu code change. |
| Mutasi-masuk dengan `to_study_group_id=null` (antri) | Tetap bikin `status='active'`, TAPI tidak ada StudentClassHistory. Status terpisah dari rombel. UI harus handle ini (existing). |
| Observer double-write dengan controller | Observer **TIDAK** menulis status; observer **TIDAK** membuat history. Observer hanya `Log::info` + dispatch `StudentStatusChanged` (event internal, tidak masuk audit lifecycle). |
