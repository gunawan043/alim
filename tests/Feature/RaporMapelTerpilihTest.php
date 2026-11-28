<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherAdminBook;
use App\Models\User;
use App\Services\RaporSubjectOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Mapel yang dicetak di rapor:
 *  - Kelas 1–10 : satu daftar untuk seluruh rombel di tingkat itu.
 *  - Kelas 11/12: daftar per rombel karena ada penjurusan.
 */
class RaporMapelTerpilihTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $ay;

    private School $school;

    private User $user;

    /** @var array<string, Subject> */
    private array $subjects = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Mapel Rapor',
            'code' => 'UMR',
            'type' => 'Unit Akademik',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '70000099',
            'name' => 'Sekolah Mapel Rapor',
            'school_level' => 'SMA',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Uji Mapel Rapor',
            'email' => 'mapel-rapor-'.random_int(1000, 9999).'@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => false,
        ]);
        $this->user->assignRole(Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web']));

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $this->user->id,
            'school_id' => $this->school->id,
            'jabatan' => 'KOORDINATOR KURIKULUM',
            'status_kepegawaian' => 'PTT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['Matematika' => 'MTK', 'Fiqih' => 'FQH', 'Bahasa Arab' => 'BAR'] as $name => $code) {
            $this->subjects[$code] = Subject::create([
                'school_id' => $this->school->id,
                'code' => $code,
                'name' => $name,
                'is_active' => true,
            ]);
        }
    }

    public function test_tingkat_biasa_satu_daftar_untuk_semua_rombel(): void
    {
        $grade = $this->grade(7, 'Kelas 7');
        $a = $this->group($grade, '7A');
        $b = $this->group($grade, '7B');
        $this->books($grade, [$a, $b]);

        $this->simpan($a, ['MTK', 'FQH']);

        $service = app(RaporSubjectOrderService::class);
        $ids = $this->ids(['MTK', 'FQH']);

        $this->assertFalse($service->perStudyGroup($grade->id));
        $this->assertEqualsCanonicalizing($ids, $service->reportSubjectIds($this->school->id, $this->ay->id, 'ganjil', $grade->id, $a->id));
        // Rombel lain di tingkat yang sama ikut, tanpa mengatur sendiri.
        $this->assertEqualsCanonicalizing($ids, $service->reportSubjectIds($this->school->id, $this->ay->id, 'ganjil', $grade->id, $b->id));
        $this->assertSame(0, DB::table('rapor_subjects')->whereNotNull('study_group_id')->count());
    }

    public function test_kelas_11_disimpan_per_rombel_tanpa_mengubah_rombel_lain(): void
    {
        $grade = $this->grade(11, 'Kelas 11');
        $ipa = $this->group($grade, 'XI IPA');
        $ips = $this->group($grade, 'XI IPS');
        $this->books($grade, [$ipa, $ips]);

        $this->simpan($ipa, ['MTK', 'FQH']);
        $this->simpan($ips, ['BAR']);

        $service = app(RaporSubjectOrderService::class);

        $this->assertTrue($service->perStudyGroup($grade->id));
        $this->assertEqualsCanonicalizing(
            $this->ids(['MTK', 'FQH']),
            $service->reportSubjectIds($this->school->id, $this->ay->id, 'ganjil', $grade->id, $ipa->id),
        );
        $this->assertEqualsCanonicalizing(
            $this->ids(['BAR']),
            $service->reportSubjectIds($this->school->id, $this->ay->id, 'ganjil', $grade->id, $ips->id),
        );
        // Tidak ada baris tingkat yang ikut terbentuk.
        $this->assertSame(0, DB::table('rapor_subjects')->whereNull('study_group_id')->count());
    }

    public function test_kelas_10_tetap_per_tingkat_walau_satu_sekolah_dengan_kelas_11(): void
    {
        $grade10 = $this->grade(10, 'Kelas 10');
        $a = $this->group($grade10, '10A');
        $b = $this->group($grade10, '10B');
        $this->books($grade10, [$a, $b]);

        $this->simpan($a, ['MTK']);

        $service = app(RaporSubjectOrderService::class);
        $this->assertFalse($service->perStudyGroup($grade10->id));
        $this->assertSame(
            $this->ids(['MTK']),
            $service->reportSubjectIds($this->school->id, $this->ay->id, 'ganjil', $grade10->id, $b->id),
        );
    }

    public function test_halaman_rapor_menampilkan_centang_sesuai_pilihan(): void
    {
        $grade = $this->grade(7, 'Kelas 7');
        $a = $this->group($grade, '7A');
        $b = $this->group($grade, '7B');
        $this->books($grade, [$a, $b]);

        $this->simpan($a, ['MTK', 'FQH']);

        $response = $this->actingAs($this->user)->get(
            '/'.$this->user->id.'/schools/nilai-kelas/'.$a->id.'/rapor'
            .'?academic_year_id='.$this->ay->id.'&semester=ganjil'
        )->assertOk();

        $this->assertEqualsCanonicalizing(
            $this->ids(['MTK', 'FQH']),
            $response->viewData('selectedSubjectIds'),
        );

        $html = $response->getContent();
        foreach (['MTK', 'FQH'] as $code) {
            $this->assertMatchesRegularExpression(
                '/name="rapor_subjects\[\]"\s+value="'.$this->subjects[$code]->id.'"\s+checked/',
                $html,
            );
        }
        $barRow = $this->checkboxRow($html, $this->subjects['BAR']->id);

        $this->assertNotSame('', $barRow, 'Mapel yang tidak dipilih tetap tampil agar bisa dicentang.');
        $this->assertStringNotContainsString('checked', $barRow, 'Mapel yang tidak dipilih tidak boleh tercentang.');
    }

    /**
     * Potongan HTML satu input centang mapel sampai penutup tagnya.
     */
    private function checkboxRow(string $html, string $subjectId): string
    {
        $pos = strpos($html, 'value="'.$subjectId.'"');
        if ($pos === false) {
            return '';
        }

        $start = strrpos(substr($html, 0, $pos), 'name="rapor_subjects[]"');
        if ($start === false) {
            return '';
        }

        return substr($html, $start, strpos($html, '>', $pos) - $start + 1);
    }

    private function grade(int $level, string $name): GradeLevel
    {
        return GradeLevel::create([
            'school_id' => $this->school->id,
            'level' => $level,
            'name' => $name,
            'code' => str_replace(' ', '-', $name),
            'fase' => 'D',
            'is_active' => true,
        ]);
    }

    private function group(GradeLevel $grade, string $name): StudyGroup
    {
        return StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $grade->id,
            'name' => $name,
            'code' => $name,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<int, StudyGroup>  $groups
     */
    private function books(GradeLevel $grade, array $groups): void
    {
        foreach ($this->subjects as $subject) {
            GradeLevelSubject::create([
                'academic_year_id' => $this->ay->id,
                'grade_level_id' => $grade->id,
                'subject_id' => $subject->id,
                'allocation_hours' => 4,
                'is_active' => true,
            ]);

            foreach ($groups as $group) {
                TeacherAdminBook::create([
                    'school_id' => $this->school->id,
                    'teacher_id' => $this->user->id,
                    'subject_id' => $subject->id,
                    'study_group_id' => $group->id,
                    'academic_year_id' => $this->ay->id,
                    'semester' => 'ganjil',
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * @param  array<int, string>  $codes
     */
    private function simpan(StudyGroup $group, array $codes): void
    {
        $this->actingAs($this->user)
            ->post('/'.$this->user->id.'/schools/nilai-kelas/'.$group->id.'/rapor/mapel', [
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
                'rapor_subjects' => $this->ids($codes),
            ])
            ->assertRedirect();
    }

    /**
     * @param  array<int, string>  $codes
     * @return array<int, string>
     */
    private function ids(array $codes): array
    {
        return array_map(fn ($code) => $this->subjects[$code]->id, $codes);
    }
}
