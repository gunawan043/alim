<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AdditionalTaskSubject;
use App\Models\AdditionalTaskType;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\JenisGtk;
use App\Models\OtherTeacherTask;
use App\Models\OtherTeacherTaskSubject;
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
 * Halaman Tugas Tambahan Guru (/other-teacher-tasks):
 * Guru Pendamping hanya memilih mapel & kelas — tanpa isian Jam/Minggu —
 * dan JP-nya dihitung dari Peta Kurikulum.
 */
class OtherTeacherTaskPendampingTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private User $admin;

    private User $guru;

    private Subject $matematika;

    private Subject $ipa;

    private StudyGroup $kelasA;

    private StudyGroup $kelasB;

    protected function setUp(): void
    {
        parent::setUp();

        $unit = WorkUnit::create([
            'name' => 'Sekolah Tugas', 'code' => 'SCH-OTT', 'type' => 'Unit Akademik', 'is_active' => true,
        ]);
        $this->school = School::create([
            'work_unit_id' => $unit->id, 'npsn' => '60606060', 'name' => 'SMP Tugas',
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
            'school_id' => $this->school->id, 'name' => 'Ilmu Pengetahuan Alam', 'code' => 'IPA', 'is_active' => true,
        ]);

        // Peta Kurikulum: Matematika 6 JP, IPA 5 JP untuk tingkat 7.
        foreach ([[$this->matematika, 6], [$this->ipa, 5]] as [$subject, $hours]) {
            GradeLevelSubject::create([
                'academic_year_id' => $this->year->id,
                'grade_level_id' => $grade->id,
                'subject_id' => $subject->id,
                'allocation_hours' => $hours,
                'is_active' => true,
            ]);
        }

        JenisGtk::create(['id' => (string) Str::uuid(), 'nama' => 'Satuan Pendidikan', 'is_active' => true]);
        $this->seed(AdditionalTaskTypeSeeder::class);

        // Mapel yang boleh didampingi, dari master tipe tugas.
        $type = AdditionalTaskType::guruPendamping();
        foreach ([$this->matematika, $this->ipa] as $i => $subject) {
            AdditionalTaskSubject::create([
                'additional_task_type_id' => $type->id,
                'subject_id' => $subject->id,
                'urutan' => $i,
            ]);
        }

        $this->admin = User::create([
            'name' => 'Admin Personalia', 'email' => 'admin.ott@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);
        $this->guru = User::create([
            'name' => 'Guru Pendamping Satu', 'email' => 'guru.ott@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
    }

    private function url(string $path = ''): string
    {
        return '/'.$this->admin->id.'/other-teacher-tasks'.$path;
    }

    public function test_halaman_menampilkan_panel_mapel_dan_tanpa_jam_manual_saat_guru_pendamping(): void
    {
        $response = $this->actingAs($this->admin)->get($this->url());

        $response->assertOk()
            ->assertSee('pendampingPanel', false)
            ->assertSee('data-pendamping="1"', false)
            ->assertSee('Matematika')
            ->assertSee('Cari nama mapel')
            ->assertSee('other-teacher-tasks.js', false);

        // Isian JP manual tetap ada untuk tugas lain, tetapi punya wadah yang
        // disembunyikan script ketika Guru Pendamping dipilih.
        $response->assertSee('id="hoursField"', false);

        $js = file_get_contents(public_path('build/js/other-teacher-tasks.js'));
        $this->assertStringContainsString('hoursField', $js);
        $this->assertStringContainsString('select2:select', $js);
        $this->assertStringNotContainsString('weekly_hours', $js, 'JS tidak boleh mengirim JP manual.');
    }

    public function test_jp_dihitung_dari_peta_kurikulum_untuk_pasangan_mapel_kelas(): void
    {
        $response = $this->actingAs($this->admin)->post($this->url(), [
            'teacher_id' => $this->guru->id,
            'academic_year_id' => $this->year->id,
            'school_id' => $this->school->id,
            'additional_task_type_id' => AdditionalTaskType::guruPendamping()->id,
            'notes' => 'Pendamping mapel',
            // Matematika di 7A & 7B (6+6), IPA hanya di 7B (5) => 17 JP.
            'pendamping' => [
                $this->matematika->id => [$this->kelasA->id, $this->kelasB->id],
                $this->ipa->id => [$this->kelasB->id],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $task = OtherTeacherTask::where('teacher_id', $this->guru->id)->firstOrFail();

        $this->assertSame('Guru Pendamping', $task->task_name);
        $this->assertSame(17, $task->weekly_hours, 'JP harus dijumlahkan dari Peta Kurikulum.');
        $this->assertNull($task->study_group_id);

        $this->assertSame(3, OtherTeacherTaskSubject::where('other_teacher_task_id', $task->id)->count());
        $this->assertDatabaseHas('other_teacher_task_subjects', [
            'other_teacher_task_id' => $task->id,
            'subject_id' => $this->ipa->id,
            'study_group_id' => $this->kelasB->id,
        ]);
        $this->assertDatabaseMissing('other_teacher_task_subjects', [
            'other_teacher_task_id' => $task->id,
            'subject_id' => $this->ipa->id,
            'study_group_id' => $this->kelasA->id,
        ]);
    }

    public function test_guru_pendamping_wajib_memilih_mapel(): void
    {
        $response = $this->actingAs($this->admin)->post($this->url(), [
            'teacher_id' => $this->guru->id,
            'academic_year_id' => $this->year->id,
            'school_id' => $this->school->id,
            'additional_task_type_id' => AdditionalTaskType::guruPendamping()->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(0, OtherTeacherTask::count());
    }

    public function test_daftar_menampilkan_mapel_dan_kelas_yang_didampingi(): void
    {
        $task = OtherTeacherTask::create([
            'teacher_id' => $this->guru->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'additional_task_type_id' => AdditionalTaskType::guruPendamping()->id,
            'task_name' => 'Guru Pendamping',
            'task_code' => 'guru-pendamping',
            'weekly_hours' => 11,
            'is_active' => true,
        ]);
        OtherTeacherTaskSubject::create([
            'other_teacher_task_id' => $task->id,
            'subject_id' => $this->matematika->id,
            'study_group_id' => $this->kelasA->id,
        ]);

        $this->actingAs($this->admin)
            ->get($this->url())
            ->assertOk()
            ->assertSee('Matematika')
            ->assertSee('7A');
    }

    public function test_tugas_lain_tetap_memakai_jam_manual(): void
    {
        // Tipe non-Guru Pendamping dan bukan tugas berbasis rombel.
        $type = AdditionalTaskType::query()
            ->whereNotIn('kode', ['guru-pendamping', 'wali-kelas'])
            ->whereHas('jenisGtk', fn ($q) => $q->where('nama', 'like', '%Satuan Pendidikan%'))
            ->firstOrFail();

        $this->actingAs($this->admin)->post($this->url(), [
            'teacher_id' => $this->guru->id,
            'academic_year_id' => $this->year->id,
            'school_id' => $this->school->id,
            'additional_task_type_id' => $type->id,
            'weekly_hours' => 4,
        ])->assertSessionHasNoErrors();

        $task = OtherTeacherTask::where('teacher_id', $this->guru->id)->firstOrFail();

        $this->assertSame($type->nama, $task->task_name);
        $this->assertSame(4, $task->weekly_hours);
        $this->assertSame(0, OtherTeacherTaskSubject::count());
    }

    public function test_endpoint_jp_mengembalikan_angka_peta_kurikulum(): void
    {
        $this->actingAs($this->admin)
            ->getJson($this->url('/pendamping-hours?task_type_id='.AdditionalTaskType::guruPendamping()->id
                .'&group_ids[]='.$this->kelasA->id))
            ->assertOk()
            ->assertJson(['total' => 11]);
    }
}
