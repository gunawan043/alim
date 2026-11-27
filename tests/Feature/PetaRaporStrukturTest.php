<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\SubjectKktp;
use App\Models\TeacherAdminBook;
use App\Services\RaporSubjectOrderService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Susunan rapor (urutan, Pondok/Dinas, daftar mapel) dan KKTP ditetapkan per
 * TINGKAT di Peta Kurikulum — bukan per rombel.
 */
class PetaRaporStrukturTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $ay;

    private School $school;

    private GradeLevel $grade;

    private StudyGroup $groupA;

    private StudyGroup $groupB;

    private User $kurikulum;

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
            'name' => 'SMP IT Uji',
            'code' => 'SMP-UJI',
            'type' => 'Unit Akademik',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '70000002',
            'name' => 'SMP IT Uji',
            'school_level' => 'SMP',
            'is_active' => true,
        ]);

        $this->grade = GradeLevel::create([
            'school_id' => $this->school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'code' => 'VII',
            'fase' => 'D',
            'is_active' => true,
        ]);

        $this->groupA = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'name' => '7A',
            'code' => '7A',
            'is_active' => true,
        ]);

        $this->groupB = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'name' => '7B',
            'code' => '7B',
            'is_active' => true,
        ]);

        foreach (['Matematika' => 'MTK', 'Fiqih' => 'FQH', 'Bahasa Arab' => 'BAR'] as $name => $code) {
            $this->subjects[$code] = Subject::create([
                'school_id' => $this->school->id,
                'code' => $code,
                'name' => $name,
                'is_active' => true,
            ]);

            GradeLevelSubject::create([
                'academic_year_id' => $this->ay->id,
                'grade_level_id' => $this->grade->id,
                'subject_id' => $this->subjects[$code]->id,
                'allocation_hours' => 2,
                'is_active' => true,
            ]);
        }

        $this->kurikulum = User::create([
            'name' => 'Uji Kurikulum',
            'email' => 'kurikulum-'.random_int(1000, 9999).'@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => false,
        ]);

        $this->kurikulum->assignRole(Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web']));

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $this->kurikulum->id,
            'school_id' => $this->school->id,
            'jabatan' => 'KOORDINATOR KURIKULUM',
            'status_kepegawaian' => 'PTT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([$this->groupA, $this->groupB] as $group) {
            foreach ($this->subjects as $subject) {
                TeacherAdminBook::create([
                    'school_id' => $this->school->id,
                    'teacher_id' => $this->kurikulum->id,
                    'subject_id' => $subject->id,
                    'study_group_id' => $group->id,
                    'academic_year_id' => $this->ay->id,
                    'semester' => 'ganjil',
                    'is_active' => true,
                ]);
            }
        }
    }

    /** Urutan mapel yang benar-benar dicetak untuk rombel ini. */
    private function order(StudyGroup $group): array
    {
        $service = app(RaporSubjectOrderService::class);
        $subjects = Subject::query()
            ->whereIn('id', array_map(fn ($s) => $s->id, $this->subjects))
            ->get();

        $subjects = $service->sortSubjects($subjects, $group->grade_level_id, $this->ay->id);

        $reportIds = $service->reportSubjectIds($this->school->id, $this->ay->id, 'ganjil', $group->grade_level_id);
        if ($reportIds !== null) {
            $subjects = $subjects->filter(fn ($s) => in_array($s->id, $reportIds, true))->values();
        }

        return $subjects->pluck('code')->all();
    }

    public function test_urutan_rapor_pondok_dulu_lalu_dinas_dan_sama_untuk_semua_rombel(): void
    {
        GradeLevelSubject::where('subject_id', $this->subjects['MTK']->id)->update(['subject_source' => 'dinas', 'sort_order' => 1]);
        GradeLevelSubject::where('subject_id', $this->subjects['FQH']->id)->update(['subject_source' => 'pondok', 'sort_order' => 2]);
        GradeLevelSubject::where('subject_id', $this->subjects['BAR']->id)->update(['subject_source' => 'pondok', 'sort_order' => 1]);

        // Meski Matematika bernomor 1, blok Pondok tetap dicetak lebih dulu.
        $this->assertSame(['BAR', 'FQH', 'MTK'], $this->order($this->groupA));
        // Rombol berbeda, tingkat sama → urutan identik.
        $this->assertSame($this->order($this->groupA), $this->order($this->groupB));
    }

    public function test_daftar_mapel_rapor_disimpan_per_tingkat(): void
    {
        $service = app(RaporSubjectOrderService::class);

        $service->saveReportSubjects(
            $this->school->id,
            $this->ay->id,
            'ganjil',
            $this->grade->id,
            [$this->subjects['BAR']->id, $this->subjects['MTK']->id],
        );

        $ids = $service->reportSubjectIds($this->school->id, $this->ay->id, 'ganjil', $this->grade->id);
        sort($ids);

        $expected = [$this->subjects['BAR']->id, $this->subjects['MTK']->id];
        sort($expected);

        $this->assertSame($expected, array_values($ids));
        $this->assertCount(2, DB::table('rapor_subjects')->get());
    }

    public function test_kktp_satu_untuk_semua_rombel_di_tingkat_yang_sama(): void
    {
        SubjectKktp::create([
            'subject_id' => $this->subjects['FQH']->id,
            'school_id' => $this->school->id,
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'kkm_score' => 78,
            'kktp_score' => 78,
            'created_by' => $this->kurikulum->id,
        ]);

        $bookA = TeacherAdminBook::where('study_group_id', $this->groupA->id)
            ->where('subject_id', $this->subjects['FQH']->id)->first();
        $bookB = TeacherAdminBook::where('study_group_id', $this->groupB->id)
            ->where('subject_id', $this->subjects['FQH']->id)->first();

        // Tautan buku sengaja dibiarkan kosong: rapor tetap membaca KKTP tingkat.
        $this->assertNull($bookA->kktp_id);
        $this->assertSame(78.0, \App\Support\AcademicNilai::kkm($bookA));
        $this->assertSame(78.0, \App\Support\AcademicNilai::kkm($bookB));
    }

    public function test_simpan_pengaturan_dari_peta_kurikulum_berlaku_untuk_seluruh_tingkat(): void
    {
        $response = $this->actingAs($this->kurikulum)->post(
            '/'.$this->kurikulum->id.'/kurikulum/struktur-rapor',
            [
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
                'struktur' => [
                    $this->grade->id => [
                        $this->subjects['MTK']->id => ['urutan' => 2, 'sumber' => 'dinas', 'rapor' => '1'],
                        $this->subjects['FQH']->id => ['urutan' => 1, 'sumber' => 'pondok', 'rapor' => '1'],
                        $this->subjects['BAR']->id => ['urutan' => 3, 'sumber' => 'pondok', 'rapor' => '0'],
                    ],
                ],
            ]
        );

        $response->assertRedirect();

        $this->assertSame('pondok', GradeLevelSubject::where('subject_id', $this->subjects['FQH']->id)->value('subject_source'));

        $ids = app(RaporSubjectOrderService::class)
            ->reportSubjectIds($this->school->id, $this->ay->id, 'ganjil', $this->grade->id);

        $this->assertNotContains($this->subjects['BAR']->id, $ids);
        $this->assertSame(['FQH', 'MTK'], $this->order($this->groupA));
        $this->assertSame(['FQH', 'MTK'], $this->order($this->groupB));
    }

    private function move(string $code, string $direction)
    {
        return $this->actingAs($this->kurikulum)->get(route(
            'user.kurikulum.struktur.move',
            ['userId' => $this->kurikulum->id] + [
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
                'grade_level_id' => $this->grade->id,
                'subject_id' => $this->subjects[$code]->id,
                'direction' => $direction,
            ])
        );
    }

    private function orders(): array
    {
        $rows = GradeLevelSubject::where('grade_level_id', $this->grade->id)
            ->pluck('sort_order', 'subject_id');

        $result = [];
        foreach (['BAR', 'FQH', 'MTK'] as $code) {
            $result[$code] = $rows->get($this->subjects[$code]->id);
        }

        return $result;
    }

    public function test_panah_naik_turun_mengubah_urutan_rapor_satu_tingkat(): void
    {
        GradeLevelSubject::where('subject_id', $this->subjects['MTK']->id)->update(['subject_source' => 'dinas', 'sort_order' => 3]);
        GradeLevelSubject::where('subject_id', $this->subjects['FQH']->id)->update(['subject_source' => 'pondok', 'sort_order' => 2]);
        GradeLevelSubject::where('subject_id', $this->subjects['BAR']->id)->update(['subject_source' => 'pondok', 'sort_order' => 1]);

        $this->assertSame(['BAR', 'FQH', 'MTK'], $this->order($this->groupA));

        // Turunkan Bahasa Arab (teratas) → bertukar dengan Fiqih.
        $this->move('BAR', 'down')->assertRedirect()->assertSessionMissing('success');
        $this->assertSame(['FQH', 'BAR', 'MTK'], $this->order($this->groupA));
        $this->assertSame(['FQH', 'BAR', 'MTK'], $this->order($this->groupB));
        $this->assertSame(['BAR' => 2, 'FQH' => 1, 'MTK' => 3], $this->orders());

        // Naikkan lagi → kembali ke atas.
        $this->move('BAR', 'up')->assertRedirect();
        $this->assertSame(['BAR', 'FQH', 'MTK'], $this->order($this->groupA));
        $this->assertSame(['BAR' => 1, 'FQH' => 2, 'MTK' => 3], $this->orders());
    }

    public function test_panah_naik_pada_mapel_nomor_satu_tetap_menaikkan_ke_atas(): void
    {
        // Fiqih sudah nomor 1 (teratas): panah ▲ harus tetap menaikkannya.
        GradeLevelSubject::where('subject_id', $this->subjects['MTK']->id)->update(['subject_source' => 'dinas', 'sort_order' => 3]);
        GradeLevelSubject::where('subject_id', $this->subjects['FQH']->id)->update(['subject_source' => 'pondok', 'sort_order' => 1]);
        GradeLevelSubject::where('subject_id', $this->subjects['BAR']->id)->update(['subject_source' => 'pondok', 'sort_order' => 2]);

        $this->move('FQH', 'up')->assertRedirect();
        $this->assertSame(0, (int) $this->orders()['FQH']);
        $this->assertSame(['FQH', 'BAR', 'MTK'], $this->order($this->groupA));

        // Ditekan lagi: makin ke atas, bukan tertahan.
        $this->move('FQH', 'up')->assertRedirect();
        $this->assertSame(-1, (int) $this->orders()['FQH']);
        $this->assertSame(['FQH', 'BAR', 'MTK'], $this->order($this->groupA));
    }

    public function test_panah_bekerja_saat_urutan_belum_pernah_diatur(): void
    {
        // Semua nomor kosong → posisi tampil dipakai sebagai titik awal.
        $this->assertSame(['BAR', 'FQH', 'MTK'], $this->order($this->groupA));

        $this->move('MTK', 'up')->assertRedirect();

        $this->assertSame(['BAR', 'MTK', 'FQH'], $this->order($this->groupA));
        $this->assertSame(1, (int) $this->orders()['BAR']);
        $this->assertSame(2, (int) $this->orders()['MTK']);
        $this->assertSame(3, (int) $this->orders()['FQH']);
    }

    public function test_satu_tombol_menyimpan_jp_susunan_dan_kktp_sekaligus(): void
    {
        $response = $this->actingAs($this->kurikulum)->post(
            '/'.$this->kurikulum->id.'/kurikulum/peta',
            [
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
                'jp' => [
                    $this->grade->id => [
                        $this->subjects['MTK']->id => 5,
                    ],
                ],
                'struktur' => [
                    $this->grade->id => [
                        $this->subjects['MTK']->id => ['urutan' => 3, 'sumber' => 'dinas', 'rapor' => '1'],
                        $this->subjects['FQH']->id => ['urutan' => 1, 'sumber' => 'pondok_dinas', 'rapor' => '1'],
                        $this->subjects['BAR']->id => ['urutan' => 2, 'sumber' => 'pondok', 'rapor' => '1'],
                    ],
                ],
                'kktp' => [
                    $this->grade->id => [
                        $this->subjects['MTK']->id => 72,
                    ],
                ],
            ]
        );

        $response->assertRedirect()->assertSessionHas('success');

        $this->assertSame(5, (int) GradeLevelSubject::where('subject_id', $this->subjects['MTK']->id)->value('allocation_hours'));
        $this->assertSame('pondok_dinas', GradeLevelSubject::where('subject_id', $this->subjects['FQH']->id)->value('subject_source'));
        $this->assertSame(72, (int) SubjectKktp::where('subject_id', $this->subjects['MTK']->id)
            ->where('grade_level_id', $this->grade->id)
            ->value('kktp_score'));

        // Pondok → Pondok + Dinas → Dinas.
        $this->assertSame(['BAR', 'FQH', 'MTK'], $this->order($this->groupA));
        $this->assertSame(['BAR', 'FQH', 'MTK'], $this->order($this->groupB));
    }
}
