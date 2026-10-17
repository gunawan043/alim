# ALIM — Implementation Plan: Menutup Gap Konsep Dapodik/EMIS

**Tanggal**: 2026-06-17
**Repository**: /Users/gunawan_wawan43/Movies/Alim/alim
**Tujuan**: Menutup gap antara konsep (sesuai dokumen user) dan implementasi aktual di codebase.
**Pendekatan**: Setiap fase fokus pada SATU konsep flow; deploy per fase agar tidak menggangu produksi.

---

## Ringkasan Prioritas

| # | Fase | Flow Konsep | Status Saat Ini | Target |
|---|------|-------------|----------------|--------|
| 0 | **FOUNDATION** | Register Observers di AppServiceProvider | 4 dari N observer TIDAK aktif | Semua observer aktif |
| 1 | **STUDENT CLASS HISTORY** | Riwayat kelas per siswa | Model ada, controller TIDAK ada | CRUD + UI lengkap |
| 2 | **AUTO-TRIGGER NILAI** | Mapel+Rombel+Penugasan → Nilai awal | Schema sudah ada, TRIGGER tidak ada | Auto-create teacher_admin_book |
| 3 | **ABSENSI AUTO-GENERATE** | Rombel → Daftar absensi harian | Schema ada, auto-gen tidak ada | Observer + view |
| 4 | **RAPOR CONTROLLER + EXPORT** | Cetak rapor per siswa | Model nilai ada, controller tidak ada | Generate + cetak |
| 5 | **ALUMNI OBSERVER + PENDING TRACER** | Tracer study 6 bulan | Model ada, observer tidak ada | Auto grad-date + email reminder |
| 6 | **KONSULTASI OBSERVER** | Konsultasi BK → notifikasi | Tidak ada sama sekali | Observer + notif |
| 7 | **BULK PROMOTION VALIDATION** | Kenaikan kelas massal | Controller belum execute() | Validation + transaction |
| 8 | **MUTATION CASCADE** | Mutasi masuk/keluar → reattach | Model ada, cascade tidak ada | Auto-update rombel |

---

## FASE 0: Foundation — Register Observers

**Alasan**: Tanpa registrasi observer, SEMUA auto-cascade (Fase 1-8) tidak akan jalan. Ini blocking dependency.

### 0.1 Audit Observer Terdaftar Saat Ini

Baca `app/Providers/AppServiceProvider.php` method `boot()` — daftar observer yang sudah di-register.

### 0.2 Observers yang Harus Aktif (dari hasil audit)

| Observer | Model yang di-observe | Tujuan |
|----------|----------------------|--------|
| `StudentClassHistoryObserver` | `StudentClassHistory` | Insert/update history → notifikasi ortu |
| `AlumniObserver` (BARU) | `Alumni` | Auto-set `grad_date` + trigger tracer scan |
| `StudentMutationInObserver` | `StudentMutationIn` | Approve → attach ke rombel baru + generate nilai+absensi |
| `StudentMutationOutObserver` | `StudentMutationOut` | Approve → detach dari rombel + close history |
| `StudentPromotionObserver` | `StudentPromotion` | Execute → update rombel + generate history |
| `TeacherAdminBookObserver` (BARU) | `TeacherAdminBook` | Save nilai → update nilai_sumatif rollup |

### 0.3 File yang akan dimodifikasi

- `app/Providers/AppServiceProvider.php` — tambahkan `Model::observe()` di boot()

### 0.4 Pattern

```php
// di boot()
\App\Models\StudentClassHistory::observe(\App\Observers\StudentClassHistoryObserver::class);
\App\Models\Alumni::observe(\App\Observers\AlumniObserver::class);
// dst...
```

### 0.5 Verification

```bash
# quick smoke test
php artisan tinker
>>> \App\Models\StudentClassHistory::first()?->user; // trigger lazy load, tidak fire observer
>>> \App\Providers\AppServiceProvider::class; // ensure class loads
```

---

## FASE 1: StudentClassHistory — CRUD + UI

**Konsep yang ditutup**: "Riwayat rombel per siswa tersimpan permanen"

### 1.1 Yang Sudah Ada
- Model `StudentClassHistory` (sudah ada relasi ke Student + StudyGroup)
- Observer sudah ada (di folder `app/Observers/`)

### 1.2 Yang Akan Dibuat

**Controller** `app/Http/Controllers/StudentClassHistoryController.php`:
- `index($studentUuid)` — daftar history per siswa
- `create($studentUuid)` — form assign rombel baru
- `store(Request $r, $studentUuid)` — simpan, fire observer
- `show($studentUuid, $id)` — detail history
- `edit(...)` — form edit
- `update(...)` — update
- `destroy(...)` — soft delete

**Form Request** `app/Http/Requests/StoreStudentClassHistoryRequest.php`:
- validation: `student_id` exists, `study_group_id` exists, `academic_year_id` exists, `status` in enum

**Views** di `resources/views/students/class-history/`:
- `index.blade.php` — table history per siswa
- `create.blade.php` — form
- `edit.blade.php` — form edit

### 1.3 Routes (di `routes/web.php` dalam group `students`)

```php
Route::prefix('students/{studentUuid}/class-history')
    ->name('students.class-history.')
    ->group(function () {
        Route::get('/', [StudentClassHistoryController::class, 'index'])->name('index');
        Route::get('/create', [StudentClassHistoryController::class, 'create'])->name('create');
        Route::post('/', [StudentClassHistoryController::class, 'store'])->name('store');
        Route::get('/{historyId}', [StudentClassHistoryController::class, 'show'])->name('show');
        Route::get('/{historyId}/edit', [StudentClassHistoryController::class, 'edit'])->name('edit');
        Route::put('/{historyId}', [StudentClassHistoryController::class, 'update'])->name('update');
        Route::delete('/{historyId}', [StudentClassHistoryController::class, 'destroy'])->name('destroy');
    });
```

### 1.4 Integrasi dengan Student Show Page

`resources/views/students/show.blade.php` — tambahkan section "Riwayat Rombel" dengan link ke `route('students.class-history.index', $student->id)`.

### 1.5 Verification

```bash
php artisan route:list --name=students.class-history
# Trigger: assign student ke rombel baru → cek notification firing
```

---

## FASE 2: Auto-Trigger Nilai Awal

**Konsep yang ditutup**: "Mapel+Rombel+Penugasan → sistem otomatis generate nilai untuk seluruh siswa"

### 2.1 Schema Sudah Ada
- `teacher_admin_books` (table) — sudah ada
- `TeacherAdminBook` model — sudah ada
- Migration `2026_04_16_031955_add_nr_final_weights_to_teacher_admin_books_table.php`

### 2.2 Yang Akan Dibuat

**Service** `app/Services/GenerateNilaiAwalService.php`:

```php
class GenerateNilaiAwalService
{
    public function generateForStudyGroup(StudyGroup $studyGroup): array
    {
        // Ambil semua mapel yang ditugaskan ke rombel ini via teaching_assignments
        // Untuk setiap (mapel, rombel):
        //   - Buat atau ambil TeacherAdminBook
        //   - Untuk setiap siswa aktif di rombel (StudentClassHistory.status = 'active'):
        //     - Buat row NilaiSumatif/NilaiFormatif kosong dengan default values
        // Return array hasil
    }
}
```

**Trigger**: Panggil service dari:
- `TeachingAssignmentController::store()` — setelah simpan penugasan baru
- `StudyGroupSubjectAssignment::created()` — observer (jika observer ditambahkan)

### 2.3 Optional: Observer Baru

`app/Observers/TeachingAssignmentObserver.php`:
- `created()` → panggil `GenerateNilaiAwalService::generateForStudyGroup($studyGroup)`

### 2.4 Verification

```bash
# Unit test approach (jika tidak ada unit test, minimal feature test):
php artisan test --filter=NilaiAutoGenerateTest
# Manual: buat penugasan baru, cek teacher_admin_books + nilai_sumatif rows bertambah
```

---

## FASE 3: Absensi Harian — Auto-Generate

**Konsep yang ditutup**: "Siswa masuk rombel → otomatis muncul di daftar absensi"

### 3.1 Schema Sudah Ada
- Tabel absensi harian (cek `admin_presensi_mapel` atau yang relevan)
- Model `AdminPresensiMapel` (sebagian)

### 3.2 Yang Akan Dibuat

**Observer** `app/Observers/StudyGroupStudentObserver.php`:
- Trigger: `StudentClassHistory` di-create dengan `status='active'`
- Action: untuk hari ini (atau untuk rentang tanggal?), generate absensi harian skeleton

**Atau lebih sederhana**: Gunakan `StudentClassHistoryObserver::created()` (yang sudah ada) — tambahkan logic generate absensi.

### 3.3 View yang Akan Disempurnakan

`resources/views/absensi/harian.blade.php` — view untuk guru/wali kelas melakukan absensi harian (kemungkinan sudah ada, cek dulu).

### 3.4 Verification

- Buat StudentClassHistory baru → cek tabel absensi terkait terisi
- Login sebagai wali kelas → akses halaman absensi → siswa baru muncul otomatis

---

## FASE 4: Rapor Controller + Export

**Konsep yang ditutup**: "Rapor disusun otomatis berdasarkan data nilai + absensi + catatan wali"

### 4.1 Yang Sudah Ada
- Route: `Route::get('/{studyGroupId}/rapor/{studentId}/cetak', [NilaiKelasController::class, 'raporCetak'])` (sudah ada!)
- Method `raporCetak()` di `NilaiKelasController` (perlu dicek implementasinya)

### 4.2 Yang Akan Dibuat/Dilengkapi

**Service** `app/Services/RaporGeneratorService.php`:
```php
class RaporGeneratorService
{
    public function generate(string $studentUuid, string $studyGroupId, string $semester): array
    {
        // Tarik:
        // - nilai_sumatif per mapel dari teacher_admin_books
        // - absensi (jumlah hadir, sakit, izin, alpha)
        // - ekstrakurikuler (jika ada)
        // - catatan wali kelas (input manual)
        // - prestasi (StudentAchievement)
        // Return array struktur rapor
    }

    public function toPdf(array $data): string  // return path atau stream
    {
        // gunakan dompdf
    }
}
```

**Controller enhancement** `app/Http/Controllers/NilaiKelasController.php`:
- Lengkapi method `raporCetak($studyGroupId, $studentId)` — panggil service, return PDF

**Views**:
- `resources/views/rapor/cetak.blade.php` — template rapor
- `resources/views/rapor/preview.blade.php` — preview sebelum cetak

### 4.3 Verification

```bash
# Test:
# 1. Login wali kelas
# 2. Buka halaman nilai-kelas/{rombel}/rapor/{siswa}
# 3. Klik "Cetak Rapor"
# 4. PDF terdownload dengan data lengkap
```

---

## FASE 5: Alumni — Observer + Pending Tracer Email

**Konsep yang ditutup**: "Tracer study: alumni 6 bulan setelah lulus otomatis dikirimi email tracer"

### 5.1 Yang Sudah Ada
- Model `Alumni` (lengkap dengan field tracer)
- Controller `AlumniController` (index, show, edit, update)

### 5.2 Yang Akan Dibuat

**Observer** `app/Observers/AlumniObserver.php`:

```php
class AlumniObserver
{
    public function created(Alumni $alumni): void
    {
        // Set grad_date otomatis jika null (default: today)
        if (!$alumni->grad_date) {
            $alumni->grad_date = now();
            $alumni->saveQuietly();
        }

        // Trigger welcome notification
        NotifyService::sendTracerWelcome($alumni);

        // Dispatch job untuk schedule 6-bulan tracer reminder
        PendingTracerAlertJob::dispatch($alumni)
            ->delay(now()->addMonths(6));
    }

    public function updated(Alumni $alumni): void
    {
        // Jika tracer baru di-submit → notifikasi admin
        if ($alumni->wasChanged('tracer_filled_at') && $alumni->tracer_filled_at) {
            NotifyService::notifyAdminTracerFilled($alumni);
        }
    }
}
```

**Job** `app/Jobs/PendingTracerAlertJob.php`:
```php
class PendingTracerAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Alumni $alumni) {}

    public function handle(): void
    {
        // Kirim email tracer + catat di notification_universal
        if (!$this->alumni->tracer_filled_at) {
            Mail::to($this->alumni->email)->send(new TracerReminderMail($this->alumni));
            NotificationUniversal::create([
                'user_id' => $this->alumni->user_id ?? null,
                'title' => 'Pengingat Tracer Study',
                'message' => 'Mohon isi tracer study ALIM...',
                'type' => 'tracer_reminder',
            ]);
        }
    }
}
```

**Scheduled Command** `app/Console/Commands/ScanPendingTracers.php`:
- Daily cron: scan alumni dengan `grad_date < now()-6months` dan `tracer_filled_at IS NULL`
- Dispatch `PendingTracerAlertJob` untuk masing-masing

**Cron Registration** di `app/Console/Kernel.php`:
```php
$schedule->command('alumni:scan-pending-tracers')->dailyAt('08:00');
```

**Email Template** `resources/views/emails/tracer-reminder.blade.php`:
- Form singkat tracer study (link ke `alumni/{uuid}/edit?tracer=1`)

### 5.3 Helper Service `app/Services/TracerNotificationService.php`:

```php
class TracerNotificationService
{
    public static function sendTracerWelcome(Alumni $a): void { /* ... */ }
    public static function notifyAdminTracerFilled(Alumni $a): void { /* ... */ }
    public static function notifyAlumniTracerReady(Alumni $a): void { /* ... */ }
}
```

### 5.4 Verification

```bash
# 1. tinker: create Alumni → observer fires, PendingTracerAlertJob dispatched
# 2. Check queue: php artisan queue:work --once → cek email
# 3. Cron test: php artisan alumni:scan-pending-tracers --dry-run
```

---

## FASE 6: KonsultasiObserver (BARU - belum ada sama sekali)

**Konsep**: Belum eksplisit di dokumen user, tapi implicit di alur "BK/counseling" yang ada di model.

### 6.1 Yang Sudah Ada
- Model `StudentCounselingRecord`

### 6.2 Yang Akan Dibuat

**Observer** `app/Observers/StudentCounselingRecordObserver.php`:
- `created()` → notifikasi wali kelas + ortu (via `notify()` helper)
- `updated()` jika `status='closed'` → notifikasi结局

**Notification Helper Extension** di `app/Helpers/NotificationHelper.php`:
- Tambah `notify_counseling_opened()`, `notify_counseling_closed()`

### 6.3 Verification

- Buat StudentCounselingRecord baru → cek notifikasi firing ke wali kelas

---

## FASE 7: Bulk Promotion — Validation & Execute

**Konsep yang ditutup**: "Kenaikan kelas massal di akhir tahun ajaran"

### 7.1 Yang Sudah Ada
- Controller `StudentPromotionController` (index, create, store, show, cancel, **execute**?)
- Routes: `/student-promotions/{id}/execute` (sudah ada)
- Model `StudentPromotion` + `StudentPromotionDetail`

### 7.2 Yang Perlu Dilengkapi

Cek `StudentPromotionController::execute()` method — apakah:
- Validasi semua siswa punya nilai lengkap?
- Validasi tidak ada tunggakan?
- Transaction: update StudentClassHistory → set status='graduated' atau 'promoted'
- Trigger AlumniObserver jika ada siswa yang lulus
- Generate notifikasi ke ortu

### 7.3 Yang Akan Dibuat (jika belum ada)

**Service** `app/Services/BulkPromotionService.php`:
```php
class BulkPromotionService
{
    public function execute(StudentPromotion $promotion): array
    {
        DB::transaction(function () use ($promotion) {
            foreach ($promotion->details as $detail) {
                // Close current StudentClassHistory
                // Open new StudentClassHistory (jika promoted)
                // Create Alumni (jika graduated) → trigger observer
                // Generate notifikasi per siswa
            }
            $promotion->update(['executed_at' => now()]);
        });
        return ['promoted' => ..., 'graduated' => ..., 'failed' => ...];
    }
}
```

### 7.4 Verification

```bash
# Manual test dengan cohort kecil:
# 1. Buat StudentPromotion untuk satu rombel
# 2. Set detail: 5 promoted, 3 graduated
# 3. Execute → cek StudentClassHistory updated, Alumni created, notifikasi firing
```

---

## FASE 8: Mutation Cascade

**Konsep yang ditutup**: "Mutasi masuk/keluar otomatis terkaitkan rombel baru"

### 8.1 Yang Sudah Ada
- Models `StudentMutationIn`, `StudentMutationOut`
- Controllers `StudentMutationInController`, `StudentMutationOutController`

### 8.2 Yang Akan Dibuat

**Observer** `app/Observers/StudentMutationInObserver.php`:
- `updated()`: jika status berubah ke 'approved':
  - Create StudentClassHistory baru dengan rombel tujuan
  - Trigger GenerateNilaiAwalService untuk rombel baru
  - Generate absensi skeleton
  - Notifikasi ortu + admin

**Observer** `app/Observers/StudentMutationOutObserver.php`:
- `updated()`: jika status berubah ke 'approved':
  - Update StudentClassHistory aktif → set status='mutated_out', end_date=now()
  - Notifikasi

### 8.3 Verification

- Approve mutasi masuk → cek StudentClassHistory baru + nilai ter-generate

---

## File yang Akan Disentuh (Ringkasan)

| File | Aksi | Fase |
|------|------|------|
| `app/Providers/AppServiceProvider.php` | Edit (register observers) | 0 |
| `app/Http/Controllers/StudentClassHistoryController.php` | Create | 1 |
| `app/Http/Requests/StoreStudentClassHistoryRequest.php` | Create | 1 |
| `resources/views/students/class-history/{index,create,edit}.blade.php` | Create | 1 |
| `resources/views/students/show.blade.php` | Edit (tambah section) | 1 |
| `routes/web.php` | Edit (tambah routes) | 1, 4 |
| `app/Services/GenerateNilaiAwalService.php` | Create | 2 |
| `app/Observers/TeachingAssignmentObserver.php` | Create | 2 |
| `app/Http/Controllers/TeachingAssignmentController.php` | Edit (call service) | 2 |
| `app/Services/RaporGeneratorService.php` | Create | 4 |
| `app/Http/Controllers/NilaiKelasController.php` | Edit (complete raporCetak) | 4 |
| `resources/views/rapor/{cetak,preview}.blade.php` | Create | 4 |
| `app/Observers/AlumniObserver.php` | Create | 5 |
| `app/Jobs/PendingTracerAlertJob.php` | Create | 5 |
| `app/Console/Commands/ScanPendingTracers.php` | Create | 5 |
| `app/Console/Kernel.php` | Edit (schedule) | 5 |
| `app/Services/TracerNotificationService.php` | Create | 5 |
| `resources/views/emails/tracer-reminder.blade.php` | Create | 5 |
| `app/Observers/StudentCounselingRecordObserver.php` | Create | 6 |
| `app/Helpers/NotificationHelper.php` | Edit (tambah helper) | 6 |
| `app/Services/BulkPromotionService.php` | Create | 7 |
| `app/Http/Controllers/StudentPromotionController.php` | Edit (complete execute) | 7 |
| `app/Observers/StudentMutationInObserver.php` | Create | 8 |
| `app/Observers/StudentMutationOutObserver.php` | Create | 8 |

**Total**: ~25 file (17 baru, 8 edit)

---

## Deployment Strategy

Per fase → 1 commit → push → tunggu webhook deploy selesai (cek `deploy.sh`).

**Tidak boleh 1 commit besar** karena:
- Konflik sulit di-resolve
- Rollback sulit
- Test per fase sulit

**Urutan deploy**: 0 → 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8

---

## Testing Strategy

**Per fase**:
1. Manual smoke test (jalankan flow)
2. Cek `php artisan test --compact` (jika ada test relevan)
3. Cek log `storage/logs/laravel.log` untuk error

**Final integration test** (setelah semua fase):
1. Buat student baru → assign ke rombel → absensi auto-generate?
2. Tambah mapel ke rombel → nilai auto-generate?
3. Promote student → graduated → alumni created + email tracer scheduled?
4. Cetak rapor → PDF berisi data lengkap?
5. Trigger konseling → notifikasi firing?

---

## Risk & Mitigation

| Risk | Mitigation |
|------|-----------|
| Observer loop (A triggers B triggers A) | Selalu gunakan `saveQuietly()` di observer, atau `Model::withoutEvents()` |
| Queue tidak jalan di production | Cek `config/queue.php` → production harus `redis` atau `database` |
| Email spam (alumni 6 bulan = 1 email per alumni per hari jika cron jalan terus) | Idempotency: cek `tracer_reminder_sent_at` flag |
| Migration conflict | Setiap fase migration terpisah; jalankan satu-satu |
| Schema field tidak ada (mis. `Alumni.tracer_filled_at`) | Tambah migration dulu sebelum observer |

---

## Open Questions (untuk User)

1. **Untuk Fase 5 (Tracer Email)**: Apakah sudah ada SMTP relay siap? Atau perlu setup Mailgun/Hostinger SMTP dulu?
2. **Untuk Fase 2 (Auto Nilai)**: Apakah generate nilai awal langsung isi rows kosong untuk semua siswa, ATAU on-demand saat guru pertama buka halaman nilai?
3. **Untuk Fase 4 (Rapor PDF)**: Template ada rujukan dari sekolah? Atau desain dari nol?
4. **Untuk Fase 7 (Bulk Promotion)**: Apakah ada aturan khusus (mis. passing grade) yang harus divalidasi?

---

## Estimasi Effort

| Fase | File | Effort |
|------|------|--------|
| 0 | 1 edit | 30 menit |
| 1 | 5 baru + 2 edit | 2-3 jam |
| 2 | 2 baru + 1 edit | 2 jam |
| 3 | 0 baru (extend Fase 1) | 1 jam |
| 4 | 2 baru + 1 edit | 3-4 jam |
| 5 | 5 baru + 2 edit | 3-4 jam |
| 6 | 1 baru + 1 edit | 1 jam |
| 7 | 1 baru + 1 edit | 2-3 jam |
| 8 | 2 baru | 1-2 jam |

**Total**: ~15-20 jam kerja

---

## Next Step Setelah Plan Disetujui

Mulai dari **Fase 0** (foundation) — register observers. Tanpa ini, semua fase lain tidak akan jalan.

Setelah Fase 0 selesai dan terverifikasi, lanjut ke Fase 1 (StudentClassHistory CRUD), dst.
