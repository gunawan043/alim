<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\JadwalKbm;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherClassAttendance;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\JadwalGeneratorService;
use App\Services\QrTokenService;
use App\Authorization\ValueObjects\ScopeKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class JadwalPergantianJamTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private User $admin;

    private User $teacher1;

    private User $teacher2;

    private StudyGroup $sgA;

    private StudyGroup $sgB;

    private Subject $math;

    private Subject $arabic;

    private TeachingAssignment $assignMathA;

    private TeachingAssignment $assignArabicA;

    private TeachingAssignment $assignMathB;

    private TeachingAssignment $assignArabicB;

    protected function setUp(): void
    {
        parent::setUp();

        // SQLite menyimpan definisi FK lama gtk_employments → positions (tabel sudah di-drop
        // oleh migrasi merge). Buat stub agar insert fixture tidak gagal.
        if (! \Illuminate\Support\Facades\Schema::hasTable('positions')) {
            \Illuminate\Support\Facades\Schema::create('positions', function ($table) {
                $table->uuid('id')->primary();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        $this->seedFixture();

        // Jangan hubungi Pusher saat test event QR broadcast.
        config(['broadcasting.default' => 'null']);
        app(\Illuminate\Broadcasting\BroadcastManager::class)->forgetDrivers();
    }

    // ─────────────────────────────────────────────────────────────
    // QR KELAS & HALAMAN-HALAMAN QR
    // ─────────────────────────────────────────────────────────────

    public function test_qr_class_pages_and_scan_pages_render(): void
    {
        $this->actingAs($this->admin);

        // Daftar QR kelas
        $this->get("/{$this->admin->id}/qr")
            ->assertOk()
            ->assertSee('QR Kelas')
            ->assertSee($this->sgA->name);

        // Gambar QR (SVG, tanpa imagick)
        $image = $this->get("/{$this->admin->id}/qr/{$this->sgA->id}/image");
        if ($image->getStatusCode() !== 200) {
            dump('QR IMAGE 404 BODY: '.substr($image->getContent(), 0, 400));
        }
        $image->assertOk();
        $this->assertStringContainsString('image/svg+xml', $image->headers->get('Content-Type'));
        $this->assertStringContainsString('<svg', $image->getContent());

        // Halaman cetak
        $this->get("/{$this->admin->id}/qr/{$this->sgA->id}")
            ->assertOk()
            ->assertSee('QR Kelas');
        $this->get("/{$this->admin->id}/qr/{$this->sgA->id}/print")
            ->assertOk()
            ->assertSee('Cetak QR Kelas');

        // Regenerate — token lama expired, token baru aktif
        $this->post("/{$this->admin->id}/qr/{$this->sgA->id}/regenerate")
            ->assertRedirect();

        $activeCount = \App\Models\QrClassToken::where('study_group_id', $this->sgA->id)
            ->where(fn ($q) => $q->whereNull('qr_url_expires_at')->orWhere('qr_url_expires_at', '>', now()))
            ->count();
        $this->assertSame(1, $activeCount, 'Harus ada tepat satu token aktif setelah regenerate');
        $this->assertSame(1, \App\Models\QrClassToken::where('study_group_id', $this->sgA->id)->count());

        // Halaman scan guru — tampilkan tab wizard + tombol scan
        $this->makeJadwal($this->teacher1, $this->sgA, $this->math, (int) today()->dayOfWeekIso, 1, '07:00:00', '07:45:00');

        $this->actingAs($this->teacher1);
        $this->get("/{$this->teacher1->id}/absensi-guru-mapel-qr/scan")
            ->assertOk()
            ->assertSee('Absensi Kehadiran Guru')
            ->assertSee('Belum Absen')
            ->assertSee('Scan Masuk')
            ->assertSee('const scanUrls', false)
            // Guru biasa tidak boleh melihat tab Manual (absensi manual terbatas jabatan)
            ->assertDontSee('id="manual-tab"', false);

        // Halaman manual / waka dashboard / history (admin)
        $this->actingAs($this->admin);
        $this->get("/{$this->admin->id}/absensi-guru-mapel-qr/history")
            ->assertOk();
        $this->get("/{$this->admin->id}/absensi-guru-mapel-qr/waka-dashboard")
            ->assertOk();
        $this->get("/{$this->admin->id}/absensi-guru-mapel-qr/manual")
            ->assertOk()
            ->assertSee($this->teacher1->name);
    }

    // ─────────────────────────────────────────────────────────────
    // ABSENSI MANUAL — HANYA JABATAN BERWENANG
    // ─────────────────────────────────────────────────────────────

    public function test_manual_attendance_restricted_to_authorized_positions(): void
    {
        // Guru biasa (tanpa permission manual) → semua akses manual ditolak
        $this->actingAs($this->teacher1);

        $this->get("/{$this->teacher1->id}/absensi-guru-mapel-qr/manual")->assertForbidden();
        $this->post("/{$this->teacher1->id}/absensi-guru-mapel-qr/manual-checkin", [])->assertForbidden();
        $this->post("/{$this->teacher1->id}/absensi-guru-mapel-qr/manual-checkout", [])->assertForbidden();

        // Admin (berwenang, permission ada di snapshot) → boleh membuka halaman manual
        $this->actingAs($this->admin);
        $this->get("/{$this->admin->id}/absensi-guru-mapel-qr/manual")->assertOk();
    }

    // ─────────────────────────────────────────────────────────────
    // MASTER JAM PELAJARAN
    // ─────────────────────────────────────────────────────────────

    public function test_jam_pelajaran_crud_and_validation(): void
    {
        $this->actingAs($this->admin);

        // Halaman index
        $this->get("/{$this->admin->id}/jam-pelajaran")
            ->assertOk()
            ->assertSee('Pengaturan Jam Pelajaran');

        // Tambah slot
        $this->post("/{$this->admin->id}/jam-pelajaran", [
            'day_of_week' => 1,
            'slot_number' => 10,
            'label' => 'Jam Uji',
            'time_start' => '13:00',
            'time_end' => '13:45',
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('class_schedule_slots', [
            'school_id' => $this->school->id,
            'day_of_week' => 1,
            'slot_number' => 10,
            'label' => 'Jam Uji',
        ]);

        // Overlap ditolak
        $this->post("/{$this->admin->id}/jam-pelajaran", [
            'day_of_week' => 1,
            'slot_number' => 11,
            'label' => 'Bentrok',
            'time_start' => '13:30',
            'time_end' => '14:15',
            'is_active' => 1,
        ])->assertSessionHasErrors('time_start');

        $this->assertDatabaseMissing('class_schedule_slots', ['label' => 'Bentrok']);

        // Nomor jam duplikat ditolak
        $this->post("/{$this->admin->id}/jam-pelajaran", [
            'day_of_week' => 1,
            'slot_number' => 10,
            'label' => 'Duplikat',
            'time_start' => '14:00',
            'time_end' => '14:45',
            'is_active' => 1,
        ])->assertSessionHasErrors('slot_number');

        // Template untuk hari lain (hari 2 dikosongkan dulu karena fixture sudah mengisi master slot)
        DB::table('class_schedule_slots')->where('school_id', $this->school->id)->where('day_of_week', 2)->delete();
        $this->post("/{$this->admin->id}/jam-pelajaran/template", ['day_of_week' => 2])
            ->assertRedirect();
        $this->assertSame(6, DB::table('class_schedule_slots')->where('school_id', $this->school->id)->where('day_of_week', 2)->count());
        $this->assertSame(1, DB::table('class_schedule_slots')->where('school_id', $this->school->id)->where('day_of_week', 2)->where('is_break', 1)->count());

        // Template ditolak bila hari sudah terisi
        $this->post("/{$this->admin->id}/jam-pelajaran/template", ['day_of_week' => 2])
            ->assertSessionHas('warning');

        // Update slot
        $slot = DB::table('class_schedule_slots')->where('school_id', $this->school->id)->where('label', 'Jam Uji')->first();
        $this->put("/{$this->admin->id}/jam-pelajaran/{$slot->id}", [
            'day_of_week' => 1,
            'slot_number' => 10,
            'label' => 'Jam Uji (Revisi)',
            'time_start' => '13:00',
            'time_end' => '13:40',
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('class_schedule_slots', ['id' => $slot->id, 'label' => 'Jam Uji (Revisi)']);

        // Hapus slot
        $this->delete("/{$this->admin->id}/jam-pelajaran/{$slot->id}")->assertRedirect();
        $this->assertDatabaseMissing('class_schedule_slots', ['id' => $slot->id]);
    }

    // ─────────────────────────────────────────────────────────────
    // GENERATOR
    // ─────────────────────────────────────────────────────────────

    public function test_generator_uses_assignments_and_master_slots_without_conflicts(): void
    {
        $service = app(JadwalGeneratorService::class);
        $results = $service->generateBulk([$this->sgA->id, $this->sgB->id], $this->ay->id, 'ganjil');

        $this->assertCount(2, $results);

        foreach ($results as $result) {
            $this->assertTrue($result['has_assignments']);
            $this->assertSame([], $result['conflicts']);
            $this->assertSame([], $result['shortages']);
            $this->assertSame(10, $result['requested']);
            $this->assertSame(10, $result['generated']);
        }

        // JP sesuai weekly_hours assignment
        $this->assertSame(6, JadwalKbm::where('study_group_id', $this->sgA->id)->where('subject_id', $this->math->id)->count());
        $this->assertSame(4, JadwalKbm::where('study_group_id', $this->sgA->id)->where('subject_id', $this->arabic->id)->count());

        // Tidak ada bentrok guru / rombel
        $this->assertSame([], $service->verifyConflicts([$this->sgA->id, $this->sgB->id], $this->ay->id));

        // Slot istirahat (slot 3) tidak pernah dipakai
        $this->assertSame(0, JadwalKbm::where('slot_index', 3)->count());

        // Jam mengikuti master slot sekolah
        foreach (JadwalKbm::all() as $row) {
            $master = DB::table('class_schedule_slots')
                ->where('school_id', $this->school->id)
                ->where('day_of_week', $row->day_of_week)
                ->where('slot_number', $row->slot_index)
                ->first();

            $this->assertNotNull($master, "Slot {$row->slot_index} hari {$row->day_of_week} tidak ada di master");
            $this->assertSame(substr($master->time_start, 0, 5), substr($row->start_time, 0, 5));
            $this->assertSame(substr($master->time_end, 0, 5), substr($row->end_time, 0, 5));
        }
    }

    public function test_generator_reports_shortage_instead_of_silently_dropping(): void
    {
        // Kapasitas guru1: 5 hari × 5 slot mengajar = 25 slot; minta 40 JP utk Matematika.
        $this->assignMathA->update(['weekly_hours' => 40]);

        $service = app(JadwalGeneratorService::class);
        $result = $service->generateForStudyGroup($this->sgA->id, $this->ay->id, 'ganjil');

        $this->assertSame(44, $result['requested']); // 40 math + 4 arabic
        $this->assertNotEmpty($result['shortages']);

        $missing = collect($result['shortages'])->sum('missing');
        $this->assertGreaterThan(0, $missing);
        $this->assertSame($result['requested'], $result['generated'] + $missing);
    }

    // ─────────────────────────────────────────────────────────────
    // HTTP — HALAMAN JADWAL (NON SUPER ADMIN)
    // ─────────────────────────────────────────────────────────────

    public function test_jadwal_pages_work_for_non_super_admin_with_permission(): void
    {
        $this->actingAs($this->admin);

        $this->get("/{$this->admin->id}/jadwal-kbm")
            ->assertOk()
            ->assertSee('Jadwal Kegiatan Belajar');

        $this->get("/{$this->admin->id}/jadwal-kbm/generate")
            ->assertOk()
            ->assertSee('Generate Jadwal Kegiatan Belajar');

        // Bulk generate via form
        $response = $this->post("/{$this->admin->id}/jadwal-kbm/generate", [
            'study_group_ids' => [$this->sgA->id, $this->sgB->id],
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'overwrite' => 1,
        ]);
        $response->assertRedirect(route('user.jadwal-kbm.index', ['userId' => $this->admin->id]));

        $this->assertSame(20, JadwalKbm::count());

        // Laporan generate tampil di index
        $this->get("/{$this->admin->id}/jadwal-kbm")
            ->assertOk()
            ->assertSee('Laporan Generate Jadwal');
    }

    public function test_per_rombel_generate_and_manual_update_conflict_validation(): void
    {
        $this->actingAs($this->admin);

        // Generate per rombel
        $this->post("/{$this->admin->id}/jadwal-kbm/generate/{$this->sgA->id}", [
            'overwrite' => 1,
            'semester' => 'ganjil',
        ])->assertRedirect(route('user.jadwal-kbm.show', ['userId' => $this->admin->id, 'studyGroupId' => $this->sgA->id]));

        $this->assertSame(10, JadwalKbm::where('study_group_id', $this->sgA->id)->count());

        // Halaman show + edit render
        $this->get("/{$this->admin->id}/jadwal-kbm/{$this->sgA->id}")->assertOk();
        $this->get("/{$this->admin->id}/jadwal-kbm/{$this->sgA->id}/edit")
            ->assertOk()
            ->assertSee($this->teacher1->name);

        // Pindahkan entri ke slot yang sudah terisi guru yang sama -> konflik
        $rowA = JadwalKbm::where('study_group_id', $this->sgA->id)->where('teacher_id', $this->teacher1->id)->firstOrFail();
        $rowB = JadwalKbm::where('study_group_id', $this->sgA->id)
            ->where('teacher_id', $this->teacher1->id)
            ->where('id', '!=', $rowA->id)
            ->firstOrFail();

        $before = $rowB->only(['day_of_week', 'slot_index']);

        $this->put("/{$this->admin->id}/jadwal-kbm/{$this->sgA->id}", [
            'entries' => [[
                'id' => $rowB->id,
                'day_of_week' => $rowA->day_of_week,
                'slot_index' => $rowA->slot_index,
                'subject_id' => $rowB->subject_id,
                'teacher_id' => $rowB->teacher_id,
            ]],
        ])->assertSessionHas('conflict_warnings');

        $this->assertSame($before, $rowB->fresh()->only(['day_of_week', 'slot_index']));

        // Pindah ke slot istirahat (slot 3) ditolak
        $this->put("/{$this->admin->id}/jadwal-kbm/{$this->sgA->id}", [
            'entries' => [[
                'id' => $rowB->id,
                'day_of_week' => 1,
                'slot_index' => 3,
                'subject_id' => $rowB->subject_id,
                'teacher_id' => $rowB->teacher_id,
            ]],
        ])->assertSessionHas('conflict_warnings');

        $this->assertSame($before, $rowB->fresh()->only(['day_of_week', 'slot_index']));

        // Pindah ke slot bebas -> sukses + jam mengikuti master
        $freeSlot = null;
        foreach ([1, 2, 4, 5, 6] as $day) {
            foreach ([1, 2, 4, 5, 6] as $slot) {
                $busy = JadwalKbm::where('study_group_id', $this->sgA->id)
                    ->where('day_of_week', $day)->where('slot_index', $slot)
                    ->where('id', '!=', $rowB->id)->exists();
                $teacherBusy = JadwalKbm::where('teacher_id', $this->teacher1->id)
                    ->where('day_of_week', $day)->where('slot_index', $slot)
                    ->where('academic_year_id', $this->ay->id)
                    ->where('id', '!=', $rowB->id)->exists();

                if (! $busy && ! $teacherBusy) {
                    $freeSlot = ['day' => $day, 'slot' => $slot];
                    break 2;
                }
            }
        }

        $this->assertNotNull($freeSlot, 'Tidak menemukan slot bebas untuk uji update');

        $this->put("/{$this->admin->id}/jadwal-kbm/{$this->sgA->id}", [
            'entries' => [[
                'id' => $rowB->id,
                'day_of_week' => $freeSlot['day'],
                'slot_index' => $freeSlot['slot'],
                'subject_id' => $rowB->subject_id,
                'teacher_id' => $rowB->teacher_id,
            ]],
        ])->assertRedirect(route('user.jadwal-kbm.show', ['userId' => $this->admin->id, 'studyGroupId' => $this->sgA->id]));

        $updated = $rowB->fresh();
        $this->assertSame($freeSlot['day'], (int) $updated->day_of_week);
        $this->assertSame($freeSlot['slot'], (int) $updated->slot_index);

        $master = DB::table('class_schedule_slots')
            ->where('school_id', $this->school->id)
            ->where('day_of_week', $freeSlot['day'])
            ->where('slot_number', $freeSlot['slot'])
            ->first();
        $this->assertSame(substr($master->time_start, 0, 5), substr($updated->start_time, 0, 5));
    }

    // ─────────────────────────────────────────────────────────────
    // QR SCAN END-TO-END
    // ─────────────────────────────────────────────────────────────

    public function test_qr_scan_checkin_checkout_end_to_end(): void
    {
        $now = now();
        $dayOfWeek = (int) $now->dayOfWeekIso;

        // Jadwal hari ini untuk guru1 dengan jam di sekitar sekarang (dalam window scan)
        $jadwal = JadwalKbm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->sgA->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->teacher1->id,
            'day_of_week' => $dayOfWeek,
            'slot_index' => 6,
            'start_time' => $now->copy()->subMinutes(2)->format('H:i:s'),
            'end_time' => $now->copy()->format('H:i:s'),
            'room' => '701',
            'is_active' => true,
        ]);

        $this->actingAs($this->teacher1);

        $token = app(QrTokenService::class)->findOrCreate($this->sgA, $this->ay->id);
        $signedUrl = app(QrTokenService::class)->generateSignedUrl($token);

        // Check-in
        $first = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->get($signedUrl);
        $first->assertOk();
        $first->assertJson(['success' => true, 'type' => 'check_in']);

        $attendance = TeacherClassAttendance::where('jadwal_kbm_id', $jadwal->id)->first();
        $this->assertNotNull($attendance, 'Record absensi check-in tidak dibuat');
        $this->assertNotNull($attendance->actual_time_in);
        $this->assertSame('hadir', $attendance->status_masuk);

        // Check-out (scan kedua)
        $second = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->get($signedUrl);
        $second->assertOk();
        $second->assertJson(['success' => true, 'type' => 'check_out']);

        $attendance->refresh();
        $this->assertNotNull($attendance->actual_time_out);
        $this->assertSame('selesai', $attendance->status_keluar);

        // Scan ketiga ditolak (sudah lengkap)
        $third = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->get($signedUrl);
        $third->assertOk();
        $third->assertJson(['success' => false]);
    }

    // ─────────────────────────────────────────────────────────────
    // REKAP PERGANTIAN JAM
    // ─────────────────────────────────────────────────────────────

    public function test_rekap_pergantian_jam_and_export(): void
    {
        $today = now();
        $yesterday = $today->copy()->subDay();

        // J1 (tepat): guru1 hari ini
        $j1 = $this->makeJadwal($this->teacher1, $this->sgA, $this->math, (int) $today->dayOfWeekIso, 1, '07:00:00', '07:45:00');
        $this->makeAttendance($j1, $today, [
            'scheduled_start_time' => '07:00:00',
            'scheduled_end_time' => '07:45:00',
            'actual_time_in' => '06:58:00',
            'actual_time_out' => '07:45:00',
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'duration_minutes' => 47,
            'status_masuk' => 'hadir',
            'status_keluar' => 'selesai',
        ]);

        // J2 (terlambat + keluar cepat): guru2 kemarin
        $j2 = $this->makeJadwal($this->teacher2, $this->sgA, $this->arabic, (int) $yesterday->dayOfWeekIso, 2, '07:45:00', '08:30:00');
        $this->makeAttendance($j2, $yesterday, [
            'scheduled_start_time' => '07:45:00',
            'scheduled_end_time' => '08:30:00',
            'actual_time_in' => '07:55:00',
            'actual_time_out' => '08:20:00',
            'late_minutes' => 10,
            'early_leave_minutes' => 10,
            'duration_minutes' => 25,
            'status_masuk' => 'terlambat',
            'status_keluar' => 'keluar_cepat',
        ]);

        // J3: tanpa absensi -> Tidak Hadir
        $this->makeJadwal($this->teacher1, $this->sgB, $this->math, (int) $today->dayOfWeekIso, 2, '07:45:00', '08:30:00');

        $this->actingAs($this->teacher1);

        $query = '?start_date='.$today->copy()->subDays(3)->format('Y-m-d').'&end_date='.$today->format('Y-m-d');

        $page = $this->get("/{$this->teacher1->id}/kehadiran/pergantian-jam{$query}");
        $page->assertOk()
            ->assertSee('Rekap Kehadiran Pergantian Jam')
            ->assertSee('Tidak Hadir')
            ->assertSee('Tepat')
            ->assertSee('Terlambat');

        // Filter guru + status
        $filtered = $this->get("/{$this->teacher1->id}/kehadiran/pergantian-jam{$query}&teacher_id={$this->teacher2->id}&status=terlambat");
        $filtered->assertOk();
        $filtered->assertSee($this->teacher2->name);

        // Export excel
        $export = $this->get("/{$this->teacher1->id}/kehadiran/pergantian-jam/export{$query}");
        $export->assertOk();
        $export->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $content = $export->streamedContent();
        $this->assertSame('PK', substr($content, 0, 2), 'File export bukan XLSX valid');
    }

    // ─────────────────────────────────────────────────────────────
    // FIXTURE
    // ─────────────────────────────────────────────────────────────

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Test Jadwal',
            'code' => 'UTJ',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '99999999',
            'name' => 'Sekolah Uji Jadwal',
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2025/2026',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        // Master slot: 7 hari × 6 slot (slot 3 = istirahat)
        $times = [
            1 => ['07:00:00', '07:45:00'],
            2 => ['07:45:00', '08:30:00'],
            3 => ['08:30:00', '09:00:00'],
            4 => ['09:00:00', '09:45:00'],
            5 => ['09:45:00', '10:30:00'],
            6 => ['10:30:00', '11:15:00'],
        ];

        foreach (range(1, 7) as $day) {
            foreach ($times as $slot => [$start, $end]) {
                DB::table('class_schedule_slots')->insert([
                    'id' => (string) Str::uuid(),
                    'school_id' => $this->school->id,
                    'slot_number' => $slot,
                    'label' => $slot === 3 ? 'Istirahat' : "Jam {$slot}",
                    'time_start' => $start,
                    'time_end' => $end,
                    'is_break' => $slot === 3 ? 1 : 0,
                    'day_of_week' => $day,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $gradeLevelId = (string) Str::uuid();
        DB::table('grade_levels')->insert([
            'id' => $gradeLevelId,
            'school_id' => $this->school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->sgA = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $gradeLevelId,
            'name' => '7A',
            'code' => '7A',
            'room' => '701',
            'is_active' => true,
        ]);

        $this->sgB = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $gradeLevelId,
            'name' => '7B',
            'code' => '7B',
            'room' => '702',
            'is_active' => true,
        ]);

        $this->math = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'MAT',
            'name' => 'Matematika',
            'credit_hours' => 6,
            'is_active' => true,
        ]);

        $this->arabic = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'BAR',
            'name' => 'Bahasa Arab',
            'credit_hours' => 4,
            'is_active' => true,
        ]);

        // Users + roles
        $adminRole = Role::create(['name' => 'Satuan Pendidikan', 'guard_name' => 'web', 'level' => 8]);
        $teacherRole = Role::create(['name' => 'Guru', 'guard_name' => 'web', 'level' => 12]);

        // Permission yang dicek langsung oleh layout (tidak harus dimiliki).
        \App\Models\Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);
        \App\Models\Permission::firstOrCreate(['name' => 'teacher-attendance_manual', 'guard_name' => 'web']);
        \App\Models\Permission::firstOrCreate(['name' => 'teacher-attendance_view', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Jadwal',
            'email' => 'admin.jadwal@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->admin->assignRole($adminRole);

        $this->teacher1 = User::create([
            'name' => 'Guru Satu',
            'email' => 'guru.satu@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->teacher1->assignRole($teacherRole);

        $this->teacher2 = User::create([
            'name' => 'Guru Dua',
            'email' => 'guru.dua@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->teacher2->assignRole($teacherRole);

        // Snapshot permission (bypass rebuild — rebuild membuka transaksi yang tidak
        // kompatibel dengan transaksi RefreshDatabase pada SQLite).
        $this->seedPermissionSnapshot($this->admin, [
            'jadwalkbm.read', 'jadwalkbm.write', 'jadwalkbm.publish',
            'teacher-attendance_manual', 'teacher-attendance_view',
        ]);
        $this->seedPermissionSnapshot($this->teacher1, ['jadwalkbm.read']);
        $this->seedPermissionSnapshot($this->teacher2, ['jadwalkbm.read']);

        // Employment: school context + roster fallback (jenis_gtk guru)
        // Catatan: SQLite masih memegang FK lama gtk_employments → positions (sudah di-drop),
        // jadi FK check dimatikan sementara untuk insert fixture.
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        try {
            foreach ([[$this->teacher1, 'Pendidik / Guru'], [$this->teacher2, 'Pendidik / Guru'], [$this->admin, 'Pimpinan & Struktural Pendidikan']] as [$user, $jenis]) {
                DB::table('gtk_employments')->insert([
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'school_id' => $this->school->id,
                    'status_kepegawaian' => 'GTY',
                    'jenis_gtk' => $jenis,
                    'jabatan' => $jenis === 'Pendidik / Guru' ? 'Guru Mapel' : 'Kepala Satuan Pendidikan',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } finally {
            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
        }

        // SK + assignment (sumber JP generator)
        $decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $decreeId,
            'decree_number' => 'TEST/SK/001',
            'decree_type' => 'teaching_assignment',
            'title' => 'SK Pembagian Tugas Mengajar (Uji)',
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'issued_date' => now()->format('Y-m-d'),
            'effective_date' => now()->format('Y-m-d'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assignMathA = TeachingAssignment::create([
            'decree_id' => $decreeId,
            'teacher_id' => $this->teacher1->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->sgA->id,
            'subject_id' => $this->math->id,
            'weekly_hours' => 6,
            'status' => 'active',
        ]);

        $this->assignArabicA = TeachingAssignment::create([
            'decree_id' => $decreeId,
            'teacher_id' => $this->teacher2->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->sgA->id,
            'subject_id' => $this->arabic->id,
            'weekly_hours' => 4,
            'status' => 'active',
        ]);

        $this->assignMathB = TeachingAssignment::create([
            'decree_id' => $decreeId,
            'teacher_id' => $this->teacher1->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->sgB->id,
            'subject_id' => $this->math->id,
            'weekly_hours' => 6,
            'status' => 'active',
        ]);

        $this->assignArabicB = TeachingAssignment::create([
            'decree_id' => $decreeId,
            'teacher_id' => $this->teacher2->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->sgB->id,
            'subject_id' => $this->arabic->id,
            'weekly_hours' => 4,
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function seedPermissionSnapshot(User $user, array $permissions): void
    {
        $roleDimension = implode(',', $user->fresh()->effectiveRoles()) ?: 'default';

        $scopeKey = ScopeKey::fromComponents(
            schoolId: $this->school->id,
            academicYearId: 'global',
            roleDimension: $roleDimension,
            tenantId: 'local',
        )->value;

        DB::table('permission_snapshots')->insert([
            'user_id' => $user->id,
            'scope_key' => $scopeKey,
            'scope_school_id' => $this->school->id,
            'fingerprint' => hash('sha256', $user->id.$scopeKey),
            'permissions' => json_encode(array_values($permissions)),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);
    }

    private function makeJadwal(User $teacher, StudyGroup $group, Subject $subject, int $day, int $slot, string $start, string $end): JadwalKbm
    {
        return JadwalKbm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $group->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => $day,
            'slot_index' => $slot,
            'start_time' => $start,
            'end_time' => $end,
            'room' => $group->room,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeAttendance(JadwalKbm $jadwal, Carbon $date, array $overrides): TeacherClassAttendance
    {
        return TeacherClassAttendance::create(array_merge([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $jadwal->study_group_id,
            'jadwal_kbm_id' => $jadwal->id,
            'teacher_id' => $jadwal->teacher_id,
            'attendance_date' => $date->format('Y-m-d'),
            'scheduled_start_time' => $jadwal->start_time,
            'scheduled_end_time' => $jadwal->end_time,
            'actual_time_in' => null,
            'actual_time_out' => null,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'duration_minutes' => 0,
            'status_masuk' => 'hadir',
            'status_keluar' => 'belum_keluar',
        ], $overrides));
    }
}
