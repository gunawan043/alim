<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academic\RombelSubjectProvisioner;
use App\Services\RaporSubjectOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Mapel yang ditandai jurusan di Peta Kurikulum hanya boleh sampai ke rombel
 * dengan jurusan itu; mapel umum tetap ke semua rombel.
 */
class PetaJurusanRombelTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private GradeLevel $grade;

    private Subject $umum;

    private Subject $bahasa;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Jurusan', 'code' => 'UJR',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '98765432',
            'name' => 'Sekolah Jurusan', 'school_level' => 'sma', 'is_active' => true,
        ]);

        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2026-12-31',
        ]);

        $this->grade = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 11, 'name' => 'Kelas 11', 'code' => '11',
        ]);

        $this->umum = Subject::create([
            'school_id' => $this->school->id, 'code' => 'PAI', 'name' => 'Pendidikan Agama',
            'category' => 'pondok', 'credit_hours' => 3,
        ]);

        $this->bahasa = Subject::create([
            'school_id' => $this->school->id, 'code' => 'BHS', 'name' => 'Bahasa Arab Lanjut',
            'category' => 'pondok', 'credit_hours' => 4,
        ]);

        GradeLevelSubject::create([
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->year->id,
            'subject_id' => $this->umum->id,
            'allocation_hours' => 3,
            'track' => null,
            'is_active' => true,
        ]);

        GradeLevelSubject::create([
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->year->id,
            'subject_id' => $this->bahasa->id,
            'allocation_hours' => 4,
            'track' => 'bahasa',
            'is_active' => true,
        ]);
    }

    private function group(string $name, ?string $peminatan): StudyGroup
    {
        return StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'grade_level_id' => $this->grade->id,
            'name' => $name,
            'code' => $name,
            'peminatan' => $peminatan,
            'capacity' => 30,
            'is_active' => true,
        ]);
    }

    private function activeSubjects(StudyGroup $group): array
    {
        return StudyGroupSubject::query()
            ->where('study_group_id', $group->id)
            ->where('is_active', true)
            ->pluck('subject_id')
            ->all();
    }

    public function test_mapel_jurusan_hanya_masuk_rombel_jurusan_itu(): void
    {
        $rombelIpa = $this->group('11A', 'ipa');
        $rombelBahasa = $this->group('11B', 'bahasa');

        $ipaSubjects = $this->activeSubjects($rombelIpa);
        $bahasaSubjects = $this->activeSubjects($rombelBahasa);

        $this->assertContains($this->umum->id, $ipaSubjects, 'Mapel umum harus masuk semua rombel.');
        $this->assertContains($this->umum->id, $bahasaSubjects);
        $this->assertNotContains($this->bahasa->id, $ipaSubjects, 'Mapel jurusan Bahasa tidak boleh masuk rombel IPA.');
        $this->assertContains($this->bahasa->id, $bahasaSubjects);
    }

    public function test_ganti_jurusan_melepas_dan_mengaktifkan_mapel(): void
    {
        $group = $this->group('11C', 'ipa');
        $this->assertNotContains($this->bahasa->id, $this->activeSubjects($group));

        $group->update(['peminatan' => 'bahasa']);

        $this->assertContains($this->bahasa->id, $this->activeSubjects($group));
        $this->assertContains($this->umum->id, $this->activeSubjects($group));

        // Rombel diajukan jurusannya lewat formulir rombel, lalu dikosongkan.
        $user = User::create([
            'name' => 'Admin Jurusan', 'email' => 'admin.ganti@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);
        $url = route('user.study-groups.update', ['userId' => $user->id, 'id' => $group->id]);

        $this->actingAs($user)->put($url, [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'grade_level_id' => $this->grade->id,
            'name' => '11C',
            'code' => '11C',
            'peminatan' => '',
            'capacity' => 30,
            'is_active' => 1,
        ])->assertRedirect();

        $group->refresh();
        $this->assertNull($group->peminatan);

        // Tanpa jurusan = rombel menerima semua mapel tingkat.
        $this->assertContains($this->bahasa->id, $this->activeSubjects($group));
    }

    public function test_provisioner_tidak_memberi_mapel_jurusan_lain(): void
    {
        // Rombel tanpa jurusan menerima semua mapel tingkat (termasuk mapel jurusan).
        $group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'grade_level_id' => $this->grade->id,
            'name' => '11E',
            'code' => '11E',
            'capacity' => 30,
            'is_active' => true,
        ]);

        $this->assertContains($this->bahasa->id, $this->activeSubjects($group), 'Rombel tanpa jurusan menerima semua mapel tingkat.');

        $group->forceFill(['peminatan' => 'ipa'])->save();
        $this->assertNotContains($this->bahasa->id, $this->activeSubjects($group));
    }

    public function test_urutan_rapor_memakai_baris_jurusan_rombel(): void
    {
        $ipaMeta = GradeLevelSubject::query()
            ->where('grade_level_id', $this->grade->id)
            ->where('subject_id', $this->umum->id)
            ->firstOrFail();

        // Dua baris mapel yang sama: satu jurusan IPA (urutan 5), satu umum (urutan 1).
        GradeLevelSubject::create([
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->year->id,
            'subject_id' => $this->umum->id,
            'allocation_hours' => 3,
            'track' => 'ipa',
            'sort_order' => 5,
            'subject_source' => 'pondok',
            'is_active' => true,
        ]);
        $ipaMeta->update(['sort_order' => 1, 'subject_source' => 'dinas']);

        $ordered = app(RaporSubjectOrderService::class)->sortSubjects(
            Subject::whereIn('id', [$this->umum->id])->get(),
            $this->grade->id,
            $this->year->id,
            'ipa',
        );

        $this->assertSame($this->umum->id, $ordered->first()->id);

        $meta = app(RaporSubjectOrderService::class)->structureMeta(
            $this->grade->id,
            $this->year->id,
            [$this->umum->id],
            'ipa',
        );

        $this->assertSame('ipa', $meta->get($this->umum->id)->track);
        $this->assertSame(5, $meta->get($this->umum->id)->sort_order);
    }

    public function test_peminatan_divalidasi_dari_form_rombel(): void
    {
        $user = User::create([
            'name' => 'Admin Jurusan', 'email' => 'admin.jurusan@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);

        $this->actingAs($user)->post(route('user.study-groups.store', ['userId' => $user->id]), [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'grade_level_id' => $this->grade->id,
            'name' => '11E',
            'code' => '11E',
            'peminatan' => 'ipa',
            'capacity' => 30,
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertSame('ipa', StudyGroup::where('name', '11E')->value('peminatan'));
    }
}
