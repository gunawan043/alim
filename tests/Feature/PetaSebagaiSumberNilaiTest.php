<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\Role;
use App\Models\NilaiSumatif;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\Subject;
use App\Models\SubjectKktp;
use App\Models\TeacherAdminBook;
use App\Models\User;
use App\Services\RaporSubjectOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Leger, buku nilai, dan rapor WAJIB memakai pengaturan Peta Kurikulum
 * (urutan, KKTP, bobot per TINGKAT). Mengubahnya dari leger/rapor menulis
 * balik ke Peta dan berlaku untuk semua rombel di tingkat yang sama.
 */
class PetaSebagaiSumberNilaiTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $ay;

    private School $school;

    private GradeLevel $grade;

    private StudyGroup $groupA;

    private StudyGroup $groupB;

    private User $user;

    /** @var array<string, Subject> */
    private array $subjects = [];

    /** @var array<string, TeacherAdminBook> */
    private array $booksA = [];

    /** @var array<string, TeacherAdminBook> */
    private array $booksB = [];

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
            'code' => 'SMP-UJI-NILAI',
            'type' => 'Unit Akademik',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '70000003',
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

        $this->user = User::create([
            'name' => 'Uji Nilai',
            'email' => 'nilai-'.random_int(1000, 9999).'@test.local',
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
            $subject = Subject::create([
                'school_id' => $this->school->id,
                'code' => $code,
                'name' => $name,
                'is_active' => true,
            ]);
            $this->subjects[$code] = $subject;

            GradeLevelSubject::create([
                'academic_year_id' => $this->ay->id,
                'grade_level_id' => $this->grade->id,
                'subject_id' => $subject->id,
                'allocation_hours' => 4,
                'is_active' => true,
            ]);

            foreach ([$this->groupA, $this->groupB] as $index => $group) {
                $book = TeacherAdminBook::create([
                    'school_id' => $this->school->id,
                    'teacher_id' => $this->user->id,
                    'subject_id' => $subject->id,
                    'study_group_id' => $group->id,
                    'academic_year_id' => $this->ay->id,
                    'semester' => 'ganjil',
                    'is_active' => true,
                ]);

                if ($index === 0) {
                    $this->booksA[$code] = $book;
                } else {
                    $this->booksB[$code] = $book;
                }

                StudyGroupSubject::firstOrCreate(
                    [
                        'academic_year_id' => $this->ay->id,
                        'study_group_id' => $group->id,
                        'subject_id' => $subject->id,
                    ],
                    [
                        'school_id' => $this->school->id,
                        'weekly_hours' => 4,
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    private function legerPayload(array $extra): array
    {
        return array_merge([
            'tab' => 'leger',
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'leger_mode' => 'sas',
        ], $extra);
    }

    public function test_kktp_dari_leger_tersimpan_ke_tingkat_dan_semua_buku_nilai(): void
    {
        $response = $this->actingAs($this->user)->post(
            '/'.$this->user->id.'/schools/nilai-kelas/'.$this->groupA->id.'/sts',
            $this->legerPayload([
                'leger_kkm' => [$this->booksA['MTK']->id => 78],
            ]),
        );

        $response->assertRedirect();

        $kktp = SubjectKktp::where('subject_id', $this->subjects['MTK']->id)
            ->where('grade_level_id', $this->grade->id)
            ->first();

        $this->assertNotNull($kktp, 'KKTP harus tersimpan pada tingkat.');
        $this->assertSame(78.0, (float) $kktp->kkm_score);

        // Buku nilai rombel lain ikut tertaut supaya Leger/rapor rombel itu sama.
        $this->assertSame($kktp->id, $this->booksB['MTK']->fresh()->kktp_id);
    }

    public function test_bobot_dari_leger_menjadi_bobot_tingkat_untuk_semua_rombel(): void
    {
        $this->actingAs($this->user)->post(
            '/'.$this->user->id.'/schools/nilai-kelas/'.$this->groupA->id.'/sts',
            $this->legerPayload([
                'leger_bobot' => [$this->subjects['MTK']->id => '3,5'],
            ]),
        )->assertRedirect()->assertSessionMissing('error');


        $structure = GradeLevelSubject::where('subject_id', $this->subjects['MTK']->id)->first();
        $this->assertSame(3.5, (float) $structure->bobot);

        $bobot = app(RaporSubjectOrderService::class)->bobotForGradeLevel(
            $this->grade->id,
            $this->ay->id,
            [$this->subjects['MTK']->id],
        );

        $this->assertSame(3.5, $bobot[$this->subjects['MTK']->id]);

        // Rombol lain membaca bobot yang sama (bukan bobot per rombel).
        $rombelB = StudyGroupSubject::where('study_group_id', $this->groupB->id)
            ->where('subject_id', $this->subjects['MTK']->id)
            ->first();

        $this->assertSame(3.5, $rombelB->effectiveBobot());
    }

    public function test_bobot_kosong_kembali_ke_jp_dibagi_dua(): void
    {
        GradeLevelSubject::where('subject_id', $this->subjects['BAR']->id)->update(['bobot' => 9]);

        app(RaporSubjectOrderService::class)->saveBobotGradeLevel(
            $this->grade->id,
            $this->ay->id,
            [$this->subjects['BAR']->id => ''],
        );

        $bobot = app(RaporSubjectOrderService::class)->bobotForGradeLevel(
            $this->grade->id,
            $this->ay->id,
            [$this->subjects['BAR']->id],
        );

        // JP 4 → 4 ÷ 2 = 2
        $this->assertSame(2.0, $bobot[$this->subjects['BAR']->id]);
    }

    public function test_halaman_rapor_memakai_urutan_dan_bobot_dari_tingkat(): void
    {
        GradeLevelSubject::where('subject_id', $this->subjects['MTK']->id)->update(['subject_source' => 'dinas', 'sort_order' => 3, 'bobot' => 4]);
        GradeLevelSubject::where('subject_id', $this->subjects['FQH']->id)->update(['subject_source' => 'pondok', 'sort_order' => 2, 'bobot' => 2]);
        GradeLevelSubject::where('subject_id', $this->subjects['BAR']->id)->update(['subject_source' => 'pondok', 'sort_order' => 1, 'bobot' => 1]);

        $response = $this->actingAs($this->user)->get(
            '/'.$this->user->id.'/schools/nilai-kelas/'.$this->groupA->id.'/rapor'
        );

        $response->assertOk();

        // Halaman rapor menampilkan nomor urut dari Peta (BAR 1, FQH 2, MTK 3).
        $response->assertSeeInOrder(['Bahasa Arab', 'Fiqih', 'Matematika']);
    }

    public function test_filter_leger_pondok_menghitung_rata_rata_dari_mapel_pondok_saja(): void
    {
        // Pondok: Bahasa Arab; Pondok + Dinas: Fiqih; Dinas: Matematika.
        GradeLevelSubject::where('subject_id', $this->subjects['BAR']->id)->update(['subject_source' => 'pondok']);
        GradeLevelSubject::where('subject_id', $this->subjects['FQH']->id)->update(['subject_source' => 'pondok_dinas']);
        GradeLevelSubject::where('subject_id', $this->subjects['MTK']->id)->update(['subject_source' => 'dinas']);

        $student = Student::create([
            'school_id' => $this->school->id,
            'name' => 'SANTRI FILTER',
            'gender' => 'L',
            'status' => 'active',
        ]);

        StudentClassHistory::create([
            'student_id' => $student->id,
            'study_group_id' => $this->groupA->id,
            'academic_year_id' => $this->ay->id,
            'attendance_number' => 1,
            'is_active' => true,
        ]);

        $nilai = [
            ['BAR', 60],
            ['FQH', 90],
            ['MTK', 30],
        ];

        foreach ($nilai as [$code, $score]) {
            NilaiSumatif::create([
                'admin_book_id' => $this->booksA[$code]->id,
                'student_id' => $student->id,
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
                'sts' => $score,
            ]);
        }

        $avg = function (string $sumber) use ($student): float {
            $response = $this->actingAs($this->user)->get(
                '/'.$this->user->id.'/schools/nilai-kelas/'.$this->groupA->id.'/sts'
                .'?tab=leger&leger=sts&sumber='.$sumber
            )->assertOk();

            return (float) $response->viewData('legerAggMap')[$student->id];
        };

        // Filter juga menentukan mapel yang tampil di tabel.
        $this->assertSame(['Bahasa Arab', 'Fiqih'], $this->filteredSubjectNames('pondok'));
        $this->assertSame(['Fiqih', 'Matematika'], $this->filteredSubjectNames('dinas'));
        $this->assertSame(['Bahasa Arab', 'Fiqih', 'Matematika'], $this->filteredSubjectNames('semua'));

        // Pondok: BAR 60 + FQH 90 (pondok+dinas) = 75; Matematika tidak ikut.
        $this->assertSame(75.0, $avg('pondok'));
        // Dinas: FQH 90 + MTK 30 = 60; Bahasa Arab tidak ikut.
        $this->assertSame(60.0, $avg('dinas'));
        // Semua: (60 + 90 + 30) ÷ 3 = 60.
        $this->assertSame(60.0, $avg('semua'));
    }

    public function test_sumber_mapel_diatur_di_peta_lalu_terbaca_di_filter_leger(): void
    {
        // Belum ada yang ditandai → kedua filter kosong.
        $this->assertSame([], $this->filteredSubjectNames('dinas'));

        // Ditandai dari Peta Kurikulum (satu pengaturan untuk seluruh tingkat).
        $this->actingAs($this->user)->post(route('user.kurikulum.peta.save', ['userId' => $this->user->id]), [
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'struktur' => [
                $this->grade->id => [
                    $this->subjects['BAR']->id => ['sumber' => 'pondok', 'rapor' => '1'],
                    $this->subjects['FQH']->id => ['sumber' => 'pondok_dinas', 'rapor' => '1'],
                    $this->subjects['MTK']->id => ['sumber' => 'dinas', 'rapor' => '1'],
                ],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame(['Bahasa Arab', 'Fiqih'], $this->filteredSubjectNames('pondok'));
        $this->assertSame(['Fiqih', 'Matematika'], $this->filteredSubjectNames('dinas'));
    }

    public function test_leger_sts_tidak_terpengaruh_nilai_sas(): void
    {
        $student = Student::create([
            'school_id' => $this->school->id,
            'name' => 'SANTRI STS SAJA',
            'gender' => 'L',
            'status' => 'active',
        ]);

        StudentClassHistory::create([
            'student_id' => $student->id,
            'study_group_id' => $this->groupA->id,
            'academic_year_id' => $this->ay->id,
            'attendance_number' => 1,
            'is_active' => true,
        ]);

        // Nilai STS 80, tetapi sudah ada NR Final 90 (jalur SAS) dengan SAS 95.
        NilaiSumatif::create([
            'admin_book_id' => $this->booksA['MTK']->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'sts' => 80,
            'sas' => 95,
            'nr_final' => 90,
        ]);

        GradeLevelSubject::where('subject_id', $this->booksA['MTK']->subject_id)->update(['bobot' => 1]);

        $leger = fn (string $mode) => $this->actingAs($this->user)->get(
            '/'.$this->user->id.'/schools/nilai-kelas/'.$this->groupA->id.'/sts?tab=leger&leger='.$mode
        )->assertOk();

        $sts = $leger('sts');
        $this->assertSame(80.0, $sts->viewData('legerValueMap')[$student->id][$this->booksA['MTK']->id]);
        $this->assertSame(80.0, (float) $sts->viewData('legerAggMap')[$student->id]);
        $this->assertSame(80.0, (float) $sts->viewData('legerJumlahMap')[$student->id]);
        $this->assertSame(1, $sts->viewData('rankMap')[$student->id]);

        $sas = $leger('sas');
        $this->assertSame(90.0, $sas->viewData('legerValueMap')[$student->id][$this->booksA['MTK']->id]);
        $this->assertSame(90.0, (float) $sas->viewData('legerAggMap')[$student->id]);
    }

    public function test_kolom_jumlah_leger_sts_tanpa_pengali_bobot(): void
    {
        $student = Student::create([
            'school_id' => $this->school->id,
            'name' => 'SANTRI JUMLAH',
            'gender' => 'L',
            'status' => 'active',
        ]);

        StudentClassHistory::create([
            'student_id' => $student->id,
            'study_group_id' => $this->groupA->id,
            'academic_year_id' => $this->ay->id,
            'attendance_number' => 1,
            'is_active' => true,
        ]);

        foreach (['BAR' => 60, 'FQH' => 90, 'MTK' => 30] as $code => $score) {
            NilaiSumatif::create([
                'admin_book_id' => $this->booksA[$code]->id,
                'student_id' => $student->id,
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
                'sts' => $score,
                'sas' => $score,
            ]);
        }

        GradeLevelSubject::whereIn('subject_id', collect($this->booksA)->pluck('subject_id'))
            ->update(['bobot' => 2]);

        // STS tanpa pengali: 60 + 90 + 30 = 180, walaupun bobot 2.
        $sts = $this->actingAs($this->user)->get(
            '/'.$this->user->id.'/schools/nilai-kelas/'.$this->groupA->id.'/sts?tab=leger&leger=sts'
        )->assertOk();
        $this->assertSame(180.0, (float) $sts->viewData('legerJumlahMap')[$student->id]);
        $this->assertMatchesRegularExpression(
            '/data-col="total"[^>]*>\s*180,0\s*</',
            str_replace('.', ',', $sts->getContent())
        );

        // SAS tetap berbobot: (60 + 90 + 30) × 2 = 360.
        $sas = $this->actingAs($this->user)->get(
            '/'.$this->user->id.'/schools/nilai-kelas/'.$this->groupA->id.'/sts?tab=leger&leger=sas'
        )->assertOk();
        $this->assertSame(360.0, (float) $sas->viewData('legerJumlahMap')[$student->id]);
    }

    private function filteredSubjectNames(string $sumber): array
    {
        $response = $this->actingAs($this->user)->get(
            '/'.$this->user->id.'/schools/nilai-kelas/'.$this->groupA->id.'/sts'
            .'?tab=leger&leger=sts&sumber='.$sumber
        )->assertOk();

        return $response->viewData('legerSubjectMap')
            ->map(fn ($subject) => $subject->name)
            ->values()
            ->all();
    }

    public function test_filter_kosong_memberi_arahan_ke_peta_kurikulum(): void
    {
        // Tidak ada mapel yang ditandai Pondok/Dinas untuk tingkat ini.
        GradeLevelSubject::where('grade_level_id', $this->grade->id)->update(['subject_source' => null]);

        $response = $this->actingAs($this->user)->get(
            '/'.$this->user->id.'/schools/nilai-kelas/'.$this->groupA->id.'/sts'
            .'?tab=leger&leger=sts&sumber=dinas'
        )->assertOk();

        $response->assertSee('Belum ada mapel yang ditandai');
        $response->assertSee('Peta Kurikulum');
    }

    public function test_filter_tidak_kosong_tanpa_pemberitahuan(): void
    {
        GradeLevelSubject::where('subject_id', $this->subjects['BAR']->id)->update(['subject_source' => 'pondok']);
        GradeLevelSubject::where('subject_id', $this->subjects['MTK']->id)->update(['subject_source' => 'dinas']);

        $response = $this->actingAs($this->user)->get(
            '/'.$this->user->id.'/schools/nilai-kelas/'.$this->groupA->id.'/sts'
            .'?tab=leger&leger=sts&sumber=pondok'
        )->assertOk();

        $response->assertDontSee('Belum ada mapel yang ditandai');
    }
}
