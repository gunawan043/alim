<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\Role;
use App\Models\School;
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
}
