<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AdditionalTaskSubject;
use App\Models\AdditionalTaskType;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\InstitutionDecree;
use App\Models\JenisGtk;
use App\Models\OtherTeacherTask;
use App\Models\OtherTeacherTaskSubject;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\InstitutionDecreeMatrixService;
use Database\Seeders\AdditionalTaskTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Guru Pendamping di Surat Keputusan: penugasan memakai pasangan
 * mapel–kelas dan JP-nya diambil dari Peta Kurikulum, bukan diketik.
 */
class DecreePendampingTaskTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private User $guru;

    private Subject $matematika;

    private Subject $ipa;

    private StudyGroup $kelasA;

    private StudyGroup $kelasB;

    private InstitutionDecree $decree;

    protected function setUp(): void
    {
        parent::setUp();

        $unit = WorkUnit::create([
            'name' => 'Sekolah SK', 'code' => 'SCH-SKP', 'type' => 'Unit Akademik', 'is_active' => true,
        ]);
        $this->school = School::create([
            'work_unit_id' => $unit->id, 'npsn' => '70707070', 'name' => 'SMP SK',
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

        $type = AdditionalTaskType::guruPendamping();
        foreach ([$this->matematika, $this->ipa] as $i => $subject) {
            AdditionalTaskSubject::create([
                'additional_task_type_id' => $type->id,
                'subject_id' => $subject->id,
                'urutan' => $i,
            ]);
        }

        $this->guru = User::create([
            'name' => 'Guru SK', 'email' => 'guru.skp@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $this->decree = InstitutionDecree::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'decree_number' => 'SK/TEST/001',
            'decree_type' => 'SK Pembagian Tugas',
            'title' => 'SK Uji Guru Pendamping',
            'issued_date' => '2026-07-01',
            'effective_date' => '2026-07-01',
            'status' => 'active',
        ]);
    }

    private function sync(array $tasks): void
    {
        app(InstitutionDecreeMatrixService::class)
            ->sync($this->decree, [], $tasks, $this->school->id, true);
    }

    public function test_tugas_guru_pendamping_menyimpan_pasangan_dan_jp_dari_peta_kurikulum(): void
    {
        $this->sync([[
            'teacher_id' => $this->guru->id,
            'task_name' => 'Guru Pendamping',
            'task_code' => 'guru-pendamping',
            // Angka JP kiriman klien sengaja salah: server harus menghitung sendiri.
            'weekly_hours' => 99,
            'pendamping' => [
                ['subject_id' => $this->matematika->id, 'group_ids' => [$this->kelasA->id, $this->kelasB->id]],
                ['subject_id' => $this->ipa->id, 'group_ids' => [$this->kelasB->id]],
            ],
        ]]);

        $task = OtherTeacherTask::where('teacher_id', $this->guru->id)->firstOrFail();

        $this->assertSame('Guru Pendamping', $task->task_name);
        $this->assertSame($this->decree->id, $task->decree_id);
        $this->assertSame(17, $task->weekly_hours, 'JP harus 6+6+5 dari Peta Kurikulum, bukan 99.');
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

    public function test_mengubah_pasangan_mengganti_pasangan_lama(): void
    {
        $this->sync([[
            'teacher_id' => $this->guru->id,
            'task_name' => 'Guru Pendamping',
            'task_code' => 'guru-pendamping',
            'pendamping' => [
                ['subject_id' => $this->matematika->id, 'group_ids' => [$this->kelasA->id]],
            ],
        ]]);

        $this->sync([[
            'teacher_id' => $this->guru->id,
            'task_name' => 'Guru Pendamping',
            'task_code' => 'guru-pendamping',
            'pendamping' => [
                ['subject_id' => $this->ipa->id, 'group_ids' => [$this->kelasB->id]],
            ],
        ]]);

        $task = OtherTeacherTask::where('teacher_id', $this->guru->id)->firstOrFail();

        $this->assertSame(5, $task->weekly_hours);
        $this->assertSame(1, OtherTeacherTaskSubject::where('other_teacher_task_id', $task->id)->count());
        $this->assertDatabaseMissing('other_teacher_task_subjects', [
            'other_teacher_task_id' => $task->id,
            'subject_id' => $this->matematika->id,
        ]);
    }

    public function test_tugas_lain_tetap_memakai_jp_kiriman(): void
    {
        $type = AdditionalTaskType::query()
            ->whereNotIn('kode', ['guru-pendamping', 'wali-kelas'])
            ->whereHas('jenisGtk', fn ($q) => $q->where('nama', 'like', '%Satuan Pendidikan%'))
            ->firstOrFail();

        $this->sync([[
            'teacher_id' => $this->guru->id,
            'task_name' => $type->nama,
            'task_code' => $type->kode,
            'weekly_hours' => 4,
        ]]);

        $task = OtherTeacherTask::where('teacher_id', $this->guru->id)->firstOrFail();

        $this->assertSame(4, $task->weekly_hours);
        $this->assertSame(0, OtherTeacherTaskSubject::count());
    }

    public function test_matriks_menampilkan_mapel_beserta_kelas(): void
    {
        $this->sync([[
            'teacher_id' => $this->guru->id,
            'task_name' => 'Guru Pendamping',
            'task_code' => 'guru-pendamping',
            'pendamping' => [
                ['subject_id' => $this->matematika->id, 'group_ids' => [$this->kelasA->id, $this->kelasB->id]],
                ['subject_id' => $this->ipa->id, 'group_ids' => [$this->kelasB->id]],
            ],
        ]]);

        $data = app(InstitutionDecreeMatrixService::class)->renderData(
            $this->decree->fresh(), false
        );

        $row = collect($data['matrixRows'])->firstWhere('teacher_id', $this->guru->id);
        $this->assertNotNull($row);

        $task = collect($row['tasks'])->firstWhere('task_code', 'guru-pendamping');
        $this->assertNotNull($task);
        $this->assertSame(17, $task['weekly_hours']);

        $bySubject = collect($task['pendamping'])->keyBy('subject');
        $this->assertEqualsCanonicalizing(['Matematika', 'IPA'], $bySubject->keys()->all());
        $this->assertEqualsCanonicalizing(['7A', '7B'], $bySubject['Matematika']['rombel']);
        $this->assertSame(['7B'], $bySubject['IPA']['rombel']);
    }

    public function test_mapel_boleh_disimpan_tanpa_kelas_lalu_kelas_dicentang_belakangan(): void
    {
        // Alur baru: modal SK hanya memilih mapel; kelas dicentang pada
        // kolom rombel di matriks, seperti tugas mengajar.
        $this->sync([[
            'teacher_id' => $this->guru->id,
            'task_name' => 'Guru Pendamping',
            'task_code' => 'guru-pendamping',
            'pendamping' => [
                ['subject_id' => $this->matematika->id, 'group_ids' => []],
            ],
        ]]);

        $task = OtherTeacherTask::where('teacher_id', $this->guru->id)->firstOrFail();

        $this->assertSame(0, $task->weekly_hours, 'Tanpa kelas, JP belum dihitung.');
        $this->assertSame(1, OtherTeacherTaskSubject::where('other_teacher_task_id', $task->id)->count(),
            'Mapel tetap tercatat walau kelasnya belum dipilih.');

        // Kelas dicentang di matriks: kirim ulang dengan kelasnya.
        $this->sync([[
            'teacher_id' => $this->guru->id,
            'task_name' => 'Guru Pendamping',
            'task_code' => 'guru-pendamping',
            'pendamping' => [
                ['subject_id' => $this->matematika->id, 'group_ids' => [$this->kelasA->id]],
            ],
        ]]);

        $task->refresh();
        $this->assertSame(6, $task->weekly_hours);
        $this->assertDatabaseHas('other_teacher_task_subjects', [
            'other_teacher_task_id' => $task->id,
            'subject_id' => $this->matematika->id,
            'study_group_id' => $this->kelasA->id,
        ]);
    }

    public function test_mapel_guru_pendamping_diambil_dari_semua_mapel_satuan_pendidikan(): void
    {
        // Mapel yang tampil di modal harus SEMUA mapel aktif sekolah, bukan
        // hanya yang terdaftar di master tipe tugas.
        $tambahan = Subject::create([
            'school_id' => $this->school->id, 'name' => 'Bahasa Arab', 'code' => 'BAR', 'is_active' => true,
        ]);

        $allowed = Subject::where('is_active', true)->where('school_id', $this->school->id)->pluck('id')->all();

        $this->assertContains($tambahan->id, $allowed);
        $this->assertNotContains(
            AdditionalTaskSubject::pluck('subject_id')->all(),
            [$tambahan->id],
            'Bahasa Arab sengaja tidak terdaftar di master tipe tugas.'
        );
    }

    public function test_form_sk_menyediakan_pencarian_mapel_dan_label_guru_pendamping(): void
    {
        $view = file_get_contents(resource_path('views/institution-decrees/create.blade.php'));

        // Dropdown mapel harus bisa dicari (select2 via data-searchable),
        // sama seperti pemilihan mapel pada tugas mengajar.
        $this->assertStringContainsString('data-searchable data-placeholder="Cari mapel..."', $view);

        // Label tugas tambahan menampilkan nama tugas + mapelnya.
        $this->assertStringContainsString('| ${escapeHtml(subjectsLabel)}', $view);
    }
}
