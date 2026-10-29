<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\InstitutionDecree;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Smoke UI halaman master area Kurikulum (pola gtk/index):
 * Tahun Ajaran, Mata Pelajaran, Data Kelas, Rombel,
 * Plotting Guru Mengajar, Jadwal Pelajaran, Jam Pelajaran.
 */
class KurikulumMasterUiTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private StudyGroup $group;

    private Subject $subject;

    private User $kurikulum;

    private User $guru;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('positions')) {
            Schema::create('positions', function ($table) {
                $table->uuid('id')->primary();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        $this->seedFixture();
    }

    public function test_halaman_tahun_ajaran_tampil_dengan_pola_gtk(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/academic-years")
            ->assertOk()
            ->assertSee('Daftar Tahun Ajaran')
            ->assertSee('Filter Cepat')
            ->assertSee('card-animate')
            ->assertSee('2026/2027');
    }

    public function test_halaman_mata_pelajaran_tampil_dengan_pola_gtk(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/subjects")
            ->assertOk()
            ->assertSee('Daftar Mata Pelajaran')
            ->assertSee('Filter Cepat')
            ->assertSee('card-animate')
            ->assertSee('Matematika');
    }

    public function test_halaman_data_kelas_tampil_dan_filter_status(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/grade-levels")
            ->assertOk()
            ->assertSee('Daftar Tingkat Kelas')
            ->assertSee('Filter Cepat')
            ->assertSee('card-animate')
            ->assertSee('Kelas 7');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/grade-levels?is_active=1")
            ->assertOk()
            ->assertSee('Kelas 7');
    }

    public function test_halaman_rombel_tampil_dan_filter_semua_tahun_ajaran(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/study-groups")
            ->assertOk()
            ->assertSee('Daftar Rombongan Belajar')
            ->assertSee('Filter Cepat')
            ->assertSee('card-animate')
            ->assertSee('7A');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/study-groups?semua_ta=1")
            ->assertOk()
            ->assertSee('7A');
    }

    public function test_halaman_plotting_guru_tampil_dan_filter_status(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/teaching-assignments")
            ->assertOk()
            ->assertSee('Daftar Plotting Guru Mengajar')
            ->assertSee('card-animate')
            ->assertSee('Guru Matematika');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/teaching-assignments?status=active")
            ->assertOk()
            ->assertSee('Guru Matematika');
    }

    public function test_dropdown_penugasan_tidak_bocor_lintas_sekolah(): void
    {
        $otherSchool = School::create([
            'work_unit_id' => $this->school->work_unit_id,
            'name' => 'SMP Sekolah Lain',
            'npsn' => '99999999',
            'school_level' => 'smp',
            'is_active' => true,
        ]);

        Subject::create([
            'school_id' => $otherSchool->id,
            'code' => 'BHS-ASING',
            'name' => 'Bahasa Sekolah Lain',
            'is_active' => true,
        ]);

        // Satuan pendidikan biasa: dropdown hanya mapel sekolahnya sendiri.
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/teaching-assignments/create")
            ->assertOk()
            ->assertSee('Matematika')
            ->assertDontSee('Bahasa Sekolah Lain');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/teaching-assignments/bulk")
            ->assertOk()
            ->assertSee('Input Massal')
            ->assertDontSee('Bahasa Sekolah Lain');
    }

    public function test_input_massal_penugasan_membuat_sk_otomatis(): void
    {
        $fisika = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'FIS',
            'name' => 'Fisika',
            'credit_hours' => 4,
            'is_active' => true,
        ]);

        // Halaman bulk menampilkan pilihan guru & rombel (regression: dropdown kosong).
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/teaching-assignments/bulk")
            ->assertOk()
            ->assertSee('Input Massal')
            ->assertSee('Guru Matematika')
            ->assertSee($this->group->name);

        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/teaching-assignments/bulk", [
                'academic_year_id' => $this->ay->id,
                'rows' => [
                    [
                        'teacher_id' => $this->guru->id,
                        'subject_id' => $fisika->id,
                        'study_group_id' => $this->group->id,
                        'weekly_hours' => 4,
                    ],
                ],
            ])
            ->assertRedirect();

        // SK otomatis dibuat + penugasan tersimpan.
        $this->assertDatabaseHas('institution_decrees', [
            'school_id' => $this->school->id,
            'decree_type' => 'SK Pembagian Tugas',
            'title' => 'SK Pembagian Tugas (Input Massal)',
        ]);

        $this->assertSame(2, TeachingAssignment::where('school_id', $this->school->id)->count());
    }

    public function test_mapel_tidak_boleh_digandakan_kecuali_tahfidz_dan_hadits(): void
    {
        $fiqih = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'FQH',
            'name' => 'Fiqih',
            'credit_hours' => 2,
            'is_active' => true,
        ]);

        // Guru pertama mengajar Fiqih di rombel tsb → tersimpan.
        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/teaching-assignments/bulk", [
                'academic_year_id' => $this->ay->id,
                'rows' => [
                    ['teacher_id' => $this->guru->id, 'subject_id' => $fiqih->id, 'study_group_id' => $this->group->id, 'weekly_hours' => 2],
                ],
            ])->assertRedirect();

        $afterFirst = TeachingAssignment::where('school_id', $this->school->id)->count();

        // Guru lain untuk mapel & rombel yang sama → ditolak (tidak bertambah).
        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/teaching-assignments/bulk", [
                'academic_year_id' => $this->ay->id,
                'rows' => [
                    ['teacher_id' => $this->kurikulum->id, 'subject_id' => $fiqih->id, 'study_group_id' => $this->group->id, 'weekly_hours' => 2],
                ],
            ])->assertRedirect();

        $this->assertSame($afterFirst, TeachingAssignment::where('school_id', $this->school->id)->count());

        // Pengecualian: Tahfidz boleh diajar lebih dari satu guru di rombel yang sama.
        $tahfidz = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'THF',
            'name' => "Tahfidzul Qur'an",
            'credit_hours' => 4,
            'is_active' => true,
        ]);

        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/teaching-assignments/bulk", [
                'academic_year_id' => $this->ay->id,
                'rows' => [
                    ['teacher_id' => $this->guru->id, 'subject_id' => $tahfidz->id, 'study_group_id' => $this->group->id, 'weekly_hours' => 4],
                    ['teacher_id' => $this->kurikulum->id, 'subject_id' => $tahfidz->id, 'study_group_id' => $this->group->id, 'weekly_hours' => 4],
                ],
            ])->assertRedirect();

        $this->assertSame($afterFirst + 2, TeachingAssignment::where('school_id', $this->school->id)->count());
    }

    public function test_dropdown_pencarian_dan_master_nama_tugas_tambahan(): void
    {
        $jenis = \App\Models\JenisGtk::create(['nama' => 'Satuan Pendidikan Uji', 'is_active' => true, 'urutan' => 1]);

        \App\Models\AdditionalTaskType::create([
            'jenis_gtk_id' => $jenis->id,
            'nama' => 'Wali Kelas Uji',
            'kode' => 'WKU',
            'urutan' => 1,
            'is_active' => true,
        ]);

        // Halaman Tugas Tambahan: nama tugas kini dropdown dari master + pencarian nama guru.
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/other-teacher-tasks")
            ->assertOk()
            ->assertSee('Wali Kelas Uji')
            ->assertSee('js-select2')
            ->assertSee('select2');

        // Input massal & form penugasan: dropdown pencarian nama.
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/teaching-assignments/bulk")
            ->assertOk()
            ->assertSee('select2');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/teaching-assignments/create")
            ->assertOk()
            ->assertSee('js-search-select');
    }

    public function test_tugas_tambahan_satuan_terfilter_dan_wali_kelas_wajib_rombel(): void
    {
        $jenisSatuan = \App\Models\JenisGtk::create(['nama' => 'Tenaga Kependidikan Satuan Pendidikan Uji', 'is_active' => true, 'urutan' => 1]);
        $jenisAsrama = \App\Models\JenisGtk::create(['nama' => 'Tenaga Pengasuhan & Keasramaan Uji', 'is_active' => true, 'urutan' => 2]);

        \App\Models\AdditionalTaskType::create(['jenis_gtk_id' => $jenisSatuan->id, 'nama' => 'Wali Kelas', 'kode' => 'wali-kelas', 'urutan' => 1, 'is_active' => true]);
        \App\Models\AdditionalTaskType::create(['jenis_gtk_id' => $jenisAsrama->id, 'nama' => 'Wali Kamar', 'kode' => 'wali-kamar', 'urutan' => 1, 'is_active' => true]);

        // Hanya tugas yang relevan di satuan pendidikan yang tampil.
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/other-teacher-tasks")
            ->assertOk()
            ->assertSee('Wali Kelas')
            ->assertDontSee('Wali Kamar');

        // Wali Kelas tanpa rombel → ditolak dengan pesan.
        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/other-teacher-tasks", [
                'teacher_id' => $this->guru->id,
                'academic_year_id' => $this->ay->id,
                'task_name' => 'Wali Kelas',
                'task_code' => 'wali-kelas',
                'weekly_hours' => 2,
            ])
            ->assertSessionHas('error');

        // Dengan rombel → tersimpan & rombel tampil di daftar.
        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/other-teacher-tasks", [
                'teacher_id' => $this->guru->id,
                'academic_year_id' => $this->ay->id,
                'task_name' => 'Wali Kelas',
                'task_code' => 'wali-kelas',
                'study_group_id' => $this->group->id,
                'weekly_hours' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('other_teacher_tasks', [
            'teacher_id' => $this->guru->id,
            'task_name' => 'Wali Kelas',
            'study_group_id' => $this->group->id,
        ]);
    }

    public function test_data_gtk_tampil_untuk_satuan_via_work_unit(): void
    {
        // Data nyata: school_id kepegawaian kosong; tautan guru lewat Work Unit.
        $workUnitId = School::whereKey($this->school->id)->value('work_unit_id');

        if (! $workUnitId) {
            $workUnitId = (string) Str::uuid();
            School::whereKey($this->school->id)->update(['work_unit_id' => $workUnitId]);
        }

        DB::table('gtk_employments')->where('user_id', $this->guru->id)->update(['school_id' => null]);

        DB::table('gtk_work_unit')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $this->guru->id,
            'work_unit_id' => $workUnitId,
            'jabatan' => 'GURU UMUM',
            'is_primary' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Daftar Guru (jenis_gtk "Pendidik / Guru") menampilkan guru dari work unit sekolah.
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/gtk/guru")
            ->assertOk()
            ->assertSee('Guru Matematika');

        // Daftar semua GTK juga menampilkan.
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/gtk")
            ->assertOk()
            ->assertSee('Guru Matematika');
    }

    public function test_generate_jadwal_default_ta_berpenugasan_dan_seluruh_rombel_terpilih(): void
    {
        // TA aktif lain tanpa penugasan — halaman harus tetap memilih TA yang punya penugasan.
        AcademicYear::where('is_active', true)->update(['is_active' => false]);
        AcademicYear::create([
            'name' => '2030/2031',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2030-07-01',
            'end_date' => '2031-06-30',
        ]);

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/jadwal-kbm/generate")
            ->assertOk()
            ->assertSee('Generate Jadwal Sekolah')
            ->assertSee('1 mapel')
            ->assertDontSee('Belum ada penugasan');
    }

    public function test_penugasan_membuat_buku_nilai_dan_notifikasi_individu(): void
    {
        $fisika = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'FIS-B',
            'name' => 'Fisika B',
            'credit_hours' => 4,
            'is_active' => true,
        ]);

        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/teaching-assignments/bulk", [
                'academic_year_id' => $this->ay->id,
                'rows' => [
                    [
                        'teacher_id' => $this->guru->id,
                        'subject_id' => $fisika->id,
                        'study_group_id' => $this->group->id,
                        'weekly_hours' => 4,
                    ],
                ],
            ])
            ->assertRedirect();

        // Observer penugasan kini membuat BUKU NILAI untuk guru.
        $this->assertDatabaseHas('teacher_admin_books', [
            'subject_id' => $fisika->id,
            'study_group_id' => $this->group->id,
            'teacher_id' => $this->guru->id,
        ]);

        // Notifikasi individu ke guru penerima penugasan.
        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $this->guru->id,
            'action' => 'teaching_assignment_created',
        ]);
    }

    public function test_buku_nilai_berpindah_kepemilikan_saat_guru_diganti(): void
    {
        $ipa = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'IPA-B',
            'name' => 'IPA B',
            'credit_hours' => 4,
            'is_active' => true,
        ]);

        // Guru A ditugaskan.
        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/teaching-assignments/bulk", [
                'academic_year_id' => $this->ay->id,
                'rows' => [
                    ['teacher_id' => $this->guru->id, 'subject_id' => $ipa->id, 'study_group_id' => $this->group->id, 'weekly_hours' => 4],
                ],
            ])->assertRedirect();

        $book = \App\Models\TeacherAdminBook::where('subject_id', $ipa->id)->firstOrFail();
        $this->assertSame($this->guru->id, $book->teacher_id);

        $assignment = \App\Models\TeachingAssignment::where('subject_id', $ipa->id)->firstOrFail();

        // Tengah tahun: guru diganti ke guru B — buku harus BERPINDAH tanpa reset data.
        $this->actingAs($this->kurikulum)
            ->put("/{$this->kurikulum->id}/teaching-assignments/{$assignment->id}", [
                'academic_year_id' => $this->ay->id,
                'study_group_id' => $this->group->id,
                'subject_id' => $ipa->id,
                'teacher_id' => $this->kurikulum->id,
                'role' => 'guru_mapel',
                'weekly_hours' => 4,
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertSame($this->kurikulum->id, $book->fresh()->teacher_id);
    }

    public function test_command_penugasan_otomatis_dari_mapel_rombel(): void
    {
        $seni = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'SNB',
            'name' => 'Seni Budaya',
            'credit_hours' => 2,
            'is_active' => true,
        ]);

        \App\Models\StudyGroupSubject::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->group->id,
            'subject_id' => $seni->id,
            'weekly_hours' => 2,
            'is_active' => true,
        ]);

        $this->artisan('akademik:assign-teachers', [
            '--school' => $this->school->id,
            '--ay' => $this->ay->id,
        ])->assertExitCode(0);

        // Penugasan dibuat untuk mapel–rombel baru + tautan SGS ikut terisi.
        $this->assertDatabaseHas('teaching_assignments', [
            'school_id' => $this->school->id,
            'study_group_id' => $this->group->id,
            'subject_id' => $seni->id,
            'teacher_id' => $this->guru->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('study_group_subjects', [
            'study_group_id' => $this->group->id,
            'subject_id' => $seni->id,
            'teacher_id' => $this->guru->id,
        ]);
    }

    public function test_hapus_rombel_aman_walau_punya_jadwal(): void
    {
        // Jadwal dengan FK restriktif — sebelumnya menghalangi hapus rombel.
        DB::table('jadwal_kbms')->insert([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->group->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->guru->id,
            'day_of_week' => 1,
            'slot_index' => 1,
            'start_time' => '07:30:00',
            'end_time' => '08:05:00',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->kurikulum)
            ->delete("/{$this->kurikulum->id}/study-groups/{$this->group->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('study_groups', ['id' => $this->group->id]);
        $this->assertDatabaseMissing('jadwal_kbms', ['study_group_id' => $this->group->id]);
    }

    public function test_halaman_detail_dan_cetak_sk_tampil_matriks(): void
    {
        $decree = InstitutionDecree::firstOrFail();
        $decree->update(['decree_type' => 'SK Pembagian Tugas']);

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/institution-decrees/{$decree->id}")
            ->assertOk()
            ->assertSee('Matriks Pembagian Tugas Mengajar')
            ->assertSee('Guru Matematika');

        $print = $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/institution-decrees/{$decree->id}/print");
        $print->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', (string) $print->getContent());
    }

    public function test_halaman_jadwal_pelajaran_tampil_dan_filter_terjadwal(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/jadwal-kbm")
            ->assertOk()
            ->assertSee('Daftar Jadwal per Rombel')
            ->assertSee('Filter Cepat')
            ->assertSee('card-animate')
            ->assertSee('Rombel Aktif');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/jadwal-kbm?status=belum")
            ->assertOk()
            ->assertSee('7A');
    }

    public function test_halaman_jam_pelajaran_tampil_dengan_statistik_gtk(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/jam-pelajaran")
            ->assertOk()
            ->assertSee('Pengaturan Jam Pelajaran')
            ->assertSee('Total Slot')
            ->assertSee('Slot KBM')
            ->assertSee('card-animate');
    }

    public function test_halaman_form_dan_detail_tahun_ajaran_tampil(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/academic-years/create")
            ->assertOk()
            ->assertSee('Form Tahun Ajaran');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/academic-years/{$this->ay->id}/edit")
            ->assertOk()
            ->assertSee('Edit Tahun Ajaran');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/academic-years/{$this->ay->id}")
            ->assertOk()
            ->assertSee('Zona Berbahaya');
    }

    public function test_halaman_form_dan_detail_mata_pelajaran_tampil(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/subjects/create")
            ->assertOk()
            ->assertSee('Form Mata Pelajaran');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/subjects/{$this->subject->id}/edit")
            ->assertOk()
            ->assertSee('Edit Mata Pelajaran');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/subjects/{$this->subject->id}")
            ->assertOk()
            ->assertSee($this->subject->name);
    }

    public function test_halaman_form_dan_detail_data_kelas_tampil(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/grade-levels/create")
            ->assertOk()
            ->assertSee('Form Tingkat Kelas');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/grade-levels/{$this->grade->id}/edit")
            ->assertOk()
            ->assertSee('Edit Tingkat Kelas');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/grade-levels/{$this->grade->id}")
            ->assertOk()
            ->assertSee('Informasi Tingkat');
    }

    public function test_halaman_form_dan_detail_rombel_tampil(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/study-groups/create")
            ->assertOk()
            ->assertSee('Form Rombongan Belajar');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/study-groups/{$this->group->id}/edit")
            ->assertOk()
            ->assertSee('Edit Rombongan Belajar');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/study-groups/{$this->group->id}")
            ->assertOk()
            ->assertSee('Anggota Rombel');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/study-groups/{$this->group->id}/subjects/create")
            ->assertOk()
            ->assertSee('Pilih Mata Pelajaran');
    }

    public function test_halaman_form_dan_detail_plotting_guru_tampil(): void
    {
        $assignment = TeachingAssignment::firstOrFail();

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/teaching-assignments/create")
            ->assertOk()
            ->assertSee('Form Penugasan Mengajar');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/teaching-assignments/{$assignment->id}/edit")
            ->assertOk()
            ->assertSee('Edit Penugasan Mengajar');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/teaching-assignments/{$assignment->id}")
            ->assertOk()
            ->assertSee('Jam Pelajaran / Minggu');
    }

    public function test_halaman_generate_dan_detail_jadwal_pelajaran_tampil(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/jadwal-kbm/generate")
            ->assertOk()
            ->assertSee('Generate Jadwal Kegiatan Belajar');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/jadwal-kbm/{$this->group->id}")
            ->assertOk()
            ->assertSee('Kembali');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/jadwal-kbm/{$this->group->id}/edit")
            ->assertOk()
            ->assertSee('Edit Jadwal Manual');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/jadwal-kbm/teacher/{$this->guru->id}")
            ->assertOk()
            ->assertSee('Jadwal Mengajar');
    }

    public function test_halaman_matriks_pembagian_tugas_tampil(): void
    {
        $decreeId = DB::table('institution_decrees')->value('id');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/teaching-assignments/matrix/{$decreeId}/edit")
            ->assertOk()
            ->assertSee('Edit Matriks Pembagian Tugas');
    }

    public function test_halaman_buat_sk_dan_matriks_tampil(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/institution-decrees/create")
            ->assertOk()
            ->assertSee('Buat SK & Matriks Pembagian Tugas')
            ->assertSee('Nama')
            ->assertSee('Mapel')
            ->assertSee('Tugas Tambahan');
    }

    public function test_halaman_edit_sk_memakai_format_buat_sk(): void
    {
        $decreeId = DB::table('institution_decrees')->value('id');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/institution-decrees/{$decreeId}/edit")
            ->assertOk()
            ->assertSee('Edit SK & Matriks Pembagian Tugas')
            ->assertSee('Informasi Surat Keputusan')
            ->assertSee('Simpan Perubahan');
    }

    public function test_pengaturan_cetak_sk_dapat_disimpan(): void
    {
        $decreeId = DB::table('institution_decrees')->value('id');

        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/institution-decrees/{$decreeId}/print-settings", [
                'paper_size' => 'folio',
                'orientation' => 'landscape',
                'margin_top' => 12,
                'margin_right' => 10,
                'margin_bottom' => 12,
                'margin_left' => 15,
                'title_font_size' => 14,
                'info_font_size' => 10,
                'table_font_size' => 9,
                'line_height' => 1.2,
                'show_kop' => 1,
                'show_ttd_ksp' => 1,
                'show_stamp' => 1,
                'ttd_width' => 30,
                'ttd_height' => 15,
                'ttd_margin' => 2,
                'stamp_width' => 30,
                'stamp_height' => 30,
                'stamp_margin' => 2,
                'city' => 'Mataram',
            ])
            ->assertRedirect();

        $decree = InstitutionDecree::findOrFail($decreeId);
        $this->assertSame('folio', $decree->print_settings['paper_size']);
        $this->assertSame(12.0, (float) $decree->print_settings['margin_top']);
        $this->assertTrue((bool) $decree->print_settings['show_kop']);
    }

    // ─────────────────────────────────────────────────────────────
    // FIXTURE
    // ─────────────────────────────────────────────────────────────

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji UI Kurikulum',
            'code' => 'UUIK',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '88888888',
            'name' => 'Sekolah Uji UI Kurikulum',
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $this->grade = GradeLevel::create([
            'school_id' => $this->school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'code' => 'VII',
            'fase' => 'D',
            'is_active' => true,
        ]);

        $this->group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'name' => '7A',
            'code' => '7A',
            'capacity' => 30,
            'room' => '701',
            'is_active' => true,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'MAT',
            'name' => 'Matematika',
            'credit_hours' => 6,
            'is_active' => true,
        ]);

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        \App\Models\Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);
        \App\Models\Permission::firstOrCreate(['name' => 'teacher-attendance_manual', 'guard_name' => 'web']);
        \App\Models\Permission::firstOrCreate(['name' => 'teacher-attendance_view', 'guard_name' => 'web']);
        \App\Models\Permission::firstOrCreate(['name' => 'subject-all-access', 'guard_name' => 'web']);
        \App\Models\Permission::firstOrCreate(['name' => 'teaching-assignment-all-access', 'guard_name' => 'web']);

        $this->kurikulum = User::create([
            'name' => 'Koor Kurikulum UI',
            'email' => 'koor.ui@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->kurikulum->assignRole($role);

        $this->guru = User::create([
            'name' => 'Guru Matematika',
            'email' => 'guru.ui@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->guru->assignRole($role);

        $this->seedSnapshot($this->kurikulum);
        $this->seedSnapshot($this->guru);

        Schema::disableForeignKeyConstraints();
        try {
            foreach ([
                [$this->kurikulum, 'Pimpinan & Struktural Pendidikan', 'Koordinator Kurikulum'],
                [$this->guru, 'Pendidik / Guru', 'Guru Mapel'],
            ] as [$user, $jenis, $jabatan]) {
                DB::table('gtk_employments')->insert([
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'school_id' => $this->school->id,
                    'status_kepegawaian' => 'GTY',
                    'jenis_gtk' => $jenis,
                    'jabatan' => $jabatan,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $decreeId,
            'decree_number' => 'TEST/UI/001',
            'decree_type' => 'teaching_assignment',
            'title' => 'SK Uji UI Kurikulum',
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'issued_date' => now()->format('Y-m-d'),
            'effective_date' => now()->format('Y-m-d'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TeachingAssignment::create([
            'decree_id' => $decreeId,
            'teacher_id' => $this->guru->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->group->id,
            'subject_id' => $this->subject->id,
            'weekly_hours' => 6,
            'status' => 'active',
        ]);
    }

    private function seedSnapshot(User $user): void
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
            'permissions' => json_encode(['jadwalkbm.read', 'jadwalkbm.write', 'jadwalkbm.publish']),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);
    }
}
