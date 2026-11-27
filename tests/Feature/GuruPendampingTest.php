<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AdditionalTaskSubject;
use App\Models\AdditionalTaskType;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\GtkAdditionalTask;
use App\Models\JenisGtk;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\User;
use App\Models\WorkUnit;
use Database\Seeders\AdditionalTaskTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tugas tambahan "Guru Pendamping": memilih mapel & kelas, JP diambil dari
 * Peta Kurikulum, dan tidak menghasilkan buku nilai / perangkat ajar.
 */
class GuruPendampingTest extends TestCase
{
    use RefreshDatabase;

    private WorkUnit $unit;

    private School $school;

    private AcademicYear $year;

    private User $admin;

    private Subject $matematika;

    private Subject $ipa;

    private StudyGroup $kelasA;

    private StudyGroup $kelasB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = WorkUnit::create([
            'name' => 'Sekolah Pendamping', 'code' => 'SCH-GP', 'type' => 'Unit Akademik', 'is_active' => true,
        ]);
        $this->school = School::create([
            'work_unit_id' => $this->unit->id, 'npsn' => '50505050', 'name' => 'SMP Pendamping',
            'school_level' => 'SMP', 'is_active' => true,
        ]);
        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $grade = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 7, 'name' => 'Kelas 7', 'fase' => 'D', 'is_active' => true,
        ]);

        $this->kelasA = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'grade_level_id' => $grade->id, 'name' => '7A', 'code' => '7A', 'is_active' => true,
        ]);
        $this->kelasB = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'grade_level_id' => $grade->id, 'name' => '7B', 'code' => '7B', 'is_active' => true,
        ]);

        $this->matematika = Subject::create([
            'school_id' => $this->school->id, 'name' => 'Matematika', 'code' => 'MAT', 'is_active' => true,
        ]);
        $this->ipa = Subject::create([
            'school_id' => $this->school->id, 'name' => 'IPA', 'code' => 'IPA', 'is_active' => true,
        ]);

        // Peta Kurikulum: Matematika 6 JP, IPA 5 JP di tingkat 7.
        foreach ([[$this->matematika, 6], [$this->ipa, 5]] as [$subject, $hours]) {
            GradeLevelSubject::create([
                'academic_year_id' => $this->year->id,
                'grade_level_id' => $grade->id,
                'subject_id' => $subject->id,
                'allocation_hours' => $hours,
                'is_active' => true,
            ]);
        }

        // Master jenis GTK + seeder tipe tugas tambahan.
        JenisGtk::create(['id' => (string) Str::uuid(), 'nama' => 'Satuan Pendidikan', 'is_active' => true]);
        $this->seed(AdditionalTaskTypeSeeder::class);

        $this->admin = User::create([
            'name' => 'Admin Personalia', 'email' => 'admin.gp@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);
    }

    public function test_guru_pendamping_tersedia_sebagai_tipe_tugas_tambahan(): void
    {
        $type = AdditionalTaskType::guruPendamping();

        $this->assertNotNull($type, 'Tipe tugas tambahan Guru Pendamping harus ada.');
        $this->assertSame('Guru Pendamping', $type->nama);
        $this->assertTrue($type->isGuruPendamping());

        // Hanya untuk satuan pendidikan.
        $this->assertSame('Satuan Pendidikan', $type->jenisGtk?->nama);
    }

    public function test_panel_mapel_tampil_pada_form_tugas_tambahan(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('user.gtk-additional-tasks.create', ['userId' => $this->admin->id]));

        $response->assertOk()
            ->assertSee('Guru Pendamping')
            ->assertSee('Mapel yang Didampingi')
            ->assertSee('Kelas yang Didampingi')
            ->assertSee('Matematika')
            ->assertSee('7A');
    }

    public function test_jp_dihitung_dari_peta_kurikulum(): void
    {
        $response = $this->actingAs($this->admin)->getJson(
            route('user.gtk-additional-tasks.pendamping.hours', [
                'userId' => $this->admin->id,
                'nama_tugas' => 'Guru Pendamping',
                'subject_ids' => [$this->matematika->id, $this->ipa->id],
                'group_ids' => [$this->kelasA->id],
            ])
        );

        $response->assertOk()->assertJson(['total' => 11]);
        $response->assertJsonFragment(['group_name' => '7A', 'total' => 11]);
    }

    public function test_beberapa_kelas_menjumlahkan_jp(): void
    {
        $response = $this->actingAs($this->admin)->getJson(
            route('user.gtk-additional-tasks.pendamping.hours', [
                'userId' => $this->admin->id,
                'nama_tugas' => 'Guru Pendamping',
                'subject_ids' => [$this->matematika->id],
                'group_ids' => [$this->kelasA->id, $this->kelasB->id],
            ])
        );

        // 6 JP di 7A + 6 JP di 7B.
        $response->assertOk()->assertJson(['total' => 12]);
    }

    public function test_simpan_guru_pendamping_menyimpan_mapel_kelas_dan_jp(): void
    {
        $teacher = User::create([
            'name' => 'Guru Pendamping', 'email' => 'gp.guru@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('user.gtk-additional-tasks.store', ['userId' => $this->admin->id]),
            [
                'user_id' => $teacher->id,
                'nama_tugas' => 'Guru Pendamping',
                'hours_per_week' => 0,
                'pendamping_subjects' => [$this->matematika->id, $this->ipa->id],
                'pendamping_groups' => [$this->kelasA->id],
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $task = GtkAdditionalTask::where('user_id', $teacher->id)->firstOrFail();

        // JP ditimpa hasil Peta Kurikulum, bukan isian manual.
        $this->assertSame(11, (int) $task->hours_per_week);
        $this->assertSame([$this->kelasA->id], $task->studyGroups()->pluck('study_groups.id')->all());

        $type = AdditionalTaskType::guruPendamping();
        $this->assertCount(2, AdditionalTaskSubject::where('additional_task_type_id', $type->id)->get());
    }

    public function test_guru_pendamping_wajib_memilih_mapel_dan_kelas(): void
    {
        $teacher = User::create([
            'name' => 'Guru Tanpa Mapel', 'email' => 'gp.kosong@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('user.gtk-additional-tasks.store', ['userId' => $this->admin->id]),
            ['user_id' => $teacher->id, 'nama_tugas' => 'Guru Pendamping']
        );

        $response->assertSessionHasErrors(['pendamping_subjects', 'pendamping_groups']);
        $this->assertSame(0, GtkAdditionalTask::count());
    }

    public function test_tugas_lain_tetap_menyimpan_jp_manual(): void
    {
        $teacher = User::create([
            'name' => 'Wali Kelas Uji', 'email' => 'gp.wali@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post(
            route('user.gtk-additional-tasks.store', ['userId' => $this->admin->id]),
            ['user_id' => $teacher->id, 'nama_tugas' => 'Wali Kelas', 'hours_per_week' => 2]
        )->assertRedirect();

        $task = GtkAdditionalTask::where('user_id', $teacher->id)->firstOrFail();
        $this->assertSame(2, (int) $task->hours_per_week);
        $this->assertSame(0, $task->studyGroups()->count());
    }

    public function test_guru_pendamping_tidak_mendapat_buku_nilai_maupun_perangkat(): void
    {
        $teacher = User::create([
            'name' => 'Pendamping Tanpa Buku', 'email' => 'gp.nobuku@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        GtkAdditionalTask::create([
            'user_id' => $teacher->id,
            'nama_tugas' => 'Guru Pendamping',
            'hours_per_week' => 11,
            'additional_task_type_id' => AdditionalTaskType::guruPendamping()?->id,
        ]);

        // Buku Nilai & Perangkat hanya membaca TeachingAssignment — tugas
        // tambahan tidak boleh menjadi sumber buku.
        $this->assertSame(0, \App\Models\TeachingAssignment::where('teacher_id', $teacher->id)->count());
        $this->assertSame(
            0,
            \App\Models\TeacherAdminBook::query()->forTeacherWorkspace($teacher->id)->count(),
            'Guru Pendamping tidak boleh mendapat buku nilai.'
        );
    }

    public function test_panel_menanggapi_pemilihan_lewat_select2(): void
    {
        // Dropdown tugas tambahan memakai select2, yang TIDAK memicu event
        // 'change' native. Tanpa penanganan select2, panel tidak pernah
        // terbuka walau "Guru Pendamping" sudah dipilih.
        $js = file_get_contents(public_path('build/js/guru-pendamping.js'));

        $this->assertStringContainsString('select2:select', $js);
        $this->assertStringContainsString('select2:unselect', $js);
    }

    public function test_halaman_form_memuat_script_panel_guru_pendamping(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('user.gtk-additional-tasks.create', ['userId' => $this->admin->id]));

        $response->assertOk()
            ->assertSee('guruPendampingPanel', false)
            ->assertSee('build/js/guru-pendamping.js', false)
            ->assertSee('data-hours-url', false);
    }
}
