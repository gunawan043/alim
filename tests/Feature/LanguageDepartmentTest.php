<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GtkEmployment;
use App\Models\LanguageCertification;
use App\Models\LanguageMonthlyActivity;
use App\Models\LanguageMufradatSetoran;
use App\Models\LanguageViolation;
use App\Models\LanguageVocabulary;
use App\Models\LanguageVocabularyPeriod;
use App\Models\NilaiSumatif;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherAdminBook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class LanguageDepartmentTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private StudyGroup $group;

    private Student $student;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Bahasa',
            'code' => 'UBH',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '90909090',
            'name' => 'Sekolah Bahasa',
        ]);

        $this->year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $grade = GradeLevel::create([
            'school_id' => $this->school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'fase' => 'D',
            'is_active' => true,
        ]);

        $this->group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'grade_level_id' => $grade->id,
            'name' => '7A',
            'code' => '7A',
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'school_id' => $this->school->id,
            'name' => 'Santri Bahasa',
            'nisn' => '9090909001',
            'nis' => 'B001',
            'gender' => 'L',
            'status' => 'active',
        ]);

        StudentClassHistory::create([
            'student_id' => $this->student->id,
            'study_group_id' => $this->group->id,
            'academic_year_id' => $this->year->id,
            'is_active' => true,
            'join_date' => now()->toDateString(),
        ]);

        $this->admin = User::create([
            'name' => 'Super Admin Bahasa',
            'email' => 'sa.bahasa@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => true,
        ]);
    }

    public function test_dashboard_dan_periode_mufradat_dapat_dikelola(): void
    {
        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/bahasa")
            ->assertOk()
            ->assertSee('Qismul Lughah');

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/bahasa/mufradat", [
                'title' => 'Pekan 1 Oktober',
                'period_start' => '2026-10-05',
                'period_end' => '2026-10-11',
                'location_school' => 'Mading Utama',
                'location_dorm' => 'Mading Asrama',
                'min_words' => 10,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $period = LanguageVocabularyPeriod::firstOrFail();
        $this->assertSame('Pekan 1 Oktober', $period->title);
        $this->assertSame($this->year->id, $period->academic_year_id);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/bahasa/mufradat")
            ->assertOk()
            ->assertSee('Pekan 1 Oktober')
            ->assertSee('createPeriodModal', false);
    }

    public function test_mufradat_setoran_dan_laporan_dihitung(): void
    {
        $period = LanguageVocabularyPeriod::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'title' => 'Pekan Uji',
            'min_words' => 10,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/bahasa/mufradat/{$period->id}/words", [
                'arabic' => 'كِتَابٌ',
                'meaning' => 'Buku',
                'example_sentence' => 'هَذَا كِتَابٌ',
            ])
            ->assertRedirect();

        $this->assertSame(1, LanguageVocabulary::where('period_id', $period->id)->count());

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/bahasa/mufradat/{$period->id}/attendance", [
                'date' => '2026-10-06',
                'mustami_id' => $this->admin->id,
                'rows' => [
                    [
                        'student_id' => $this->student->id,
                        'present' => 1,
                        'total_words' => 10,
                        'correct_words' => 9,
                        'notes' => 'Baik',
                    ],
                ],
            ])
            ->assertRedirect();

        $setoran = LanguageMufradatSetoran::firstOrFail();
        $this->assertSame('90.00', $setoran->score);
        $this->assertSame('lulus', $setoran->status);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/bahasa/mufradat/{$period->id}")
            ->assertOk()
            ->assertSee('Daftar Mufradat');

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/bahasa/mufradat/{$period->id}/attendance")
            ->assertOk()
            ->assertSee('Peserta Kegiatan Hafalan Mufradat');

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/bahasa/mufradat/{$period->id}/report")
            ->assertOk()
            ->assertSee('Santri Bahasa');

        foreach (['print-list', 'print-attendance', 'print-report'] as $print) {
            $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
                ->get("/{$this->admin->id}/bahasa/mufradat/{$period->id}/{$print}")
                ->assertOk();
        }
    }

    public function test_nilai_akademik_daily_log_dan_sertifikasi_dapat_dibuka(): void
    {
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'ARB',
            'name' => 'Bahasa Arab',
            'credit_hours' => 2,
            'is_active' => true,
        ]);

        $book = TeacherAdminBook::create([
            'teacher_id' => $this->admin->id,
            'subject_id' => $subject->id,
            'study_group_id' => $this->group->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        NilaiSumatif::create([
            'admin_book_id' => $book->id,
            'student_id' => $this->student->id,
            'academic_year_id' => $this->year->id,
            'semester' => 'ganjil',
            'nr_final' => 88,
        ]);

        foreach ([
            '/bahasa/academic?language=arab',
            '/bahasa/academic/progress?language=arab',
            '/bahasa/academic/at-risk?language=arab',
            '/bahasa/academic/leger?language=arab',
            '/bahasa/academic/exams?mode=ujian',
            '/bahasa/academic/report?language=arab',
            '/bahasa/daily?type=percakapan',
            '/bahasa/daily?type=jasus',
            '/bahasa/certifications',
        ] as $path) {
            $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
                ->get("/{$this->admin->id}{$path}")
                ->assertOk();
        }

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/bahasa/academic?language=arab")
            ->assertOk()
            ->assertSee('Santri Bahasa')
            ->assertSee('Bahasa Arab');

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/bahasa/daily", [
                'log_type' => 'percakapan',
                'log_date' => '2026-10-20',
                'theme' => 'Perkenalan',
                'location' => 'Asrama',
                'participants_count' => 12,
                'status' => 'selesai',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('language_daily_logs', ['log_type' => 'percakapan', 'theme' => 'Perkenalan']);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/bahasa/certifications", [
                'student_id' => $this->student->id,
                'certification_name' => 'Sertifikasi Bahasa Arab',
                'language' => 'arab',
                'level' => 'A1',
                'score' => 88,
                'issued_date' => '2026-10-20',
                'status' => 'issued',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('language_certifications', [
            'student_id' => $this->student->id,
            'certification_name' => 'Sertifikasi Bahasa Arab',
            'status' => 'issued',
        ]);

        $certification = LanguageCertification::firstOrFail();
        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/bahasa/certifications/{$certification->id}/print")
            ->assertOk()
            ->assertSee('SERTIFIKAT BAHASA');
    }

    public function test_referensi_hanya_menampilkan_guru_dan_mapel_bahasa(): void
    {
        Subject::create([
            'school_id' => $this->school->id,
            'code' => 'MAT',
            'name' => 'Matematika',
            'credit_hours' => 2,
            'is_active' => true,
        ]);

        Subject::create([
            'school_id' => $this->school->id,
            'code' => 'ENG',
            'name' => 'Bahasa Inggris',
            'credit_hours' => 2,
            'is_active' => true,
        ]);

        $arabTeacher = User::create([
            'name' => 'Guru Arab Uji',
            'email' => 'guru.arab.uji@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $mathTeacher = User::create([
            'name' => 'Guru Matematika Uji',
            'email' => 'guru.mat.uji@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        GtkEmployment::create([
            'user_id' => $arabTeacher->id,
            'school_id' => $this->school->id,
            'jabatan' => 'Guru Bahasa Arab',
            'status_kepegawaian' => 'GTY',
        ]);
        GtkEmployment::create([
            'user_id' => $mathTeacher->id,
            'school_id' => $this->school->id,
            'jabatan' => 'Guru Matematika',
            'status_kepegawaian' => 'GTY',
        ]);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/bahasa/reference/subjects")
            ->assertOk()
            ->assertSee('Bahasa Inggris')
            ->assertDontSee('Matematika');

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/bahasa/reference/teachers")
            ->assertOk()
            ->assertSee('Guru Arab Uji')
            ->assertDontSee('Guru Matematika Uji');
    }

    public function test_pelanggaran_pembinaan_dan_kegiatan_bahasa(): void
    {
        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/bahasa/violations", [
                'student_id' => $this->student->id,
                'violation_date' => '2026-10-06',
                'place' => 'Asrama',
                'class_name' => '7A',
                'violation_type' => 'Berbicara bukan bahasa Arab',
                'points' => 5,
            ])
            ->assertRedirect();

        $violation = LanguageViolation::firstOrFail();
        $this->assertSame(5, $violation->points);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/bahasa/violations/{$violation->id}/coachings", [
                'category' => 'Pembinaan',
                'action' => 'Teguran dan komitmen ulang',
                'coaching_date' => '2026-10-07',
                'pledge_signed' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('language_coachings', ['violation_id' => $violation->id, 'pledge_signed' => 1]);
        $this->assertSame('coached', $violation->fresh()->status);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/bahasa/violations")
            ->assertOk()
            ->assertSee('Catat Pelanggaran Bahasa')
            ->assertSee('createViolationModal', false);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/bahasa/violations/report")
            ->assertOk()
            ->assertSee('Rekap Poin Pelanggaran Bahasa');

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/bahasa/violations/{$violation->id}")
            ->assertOk()
            ->assertSee('Riwayat Pembinaan');

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/bahasa/violations/{$violation->id}/print-pledge")
            ->assertOk();
        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/bahasa/violations/{$violation->id}/print-coaching")
            ->assertOk();

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/bahasa/activities", [
                'period_month' => '2026-10',
                'title' => 'Lomba Pidato Bahasa Arab',
                'activity_type' => 'pidato',
                'activity_date' => '2026-10-20',
                'executor_type' => 'santri',
                'participants_count' => 20,
                'status' => 'planned',
            ])
            ->assertRedirect();

        $activity = LanguageMonthlyActivity::firstOrFail();
        $this->assertSame(1, LanguageMonthlyActivity::count());

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/bahasa/activities")
            ->assertOk()
            ->assertSee('Lomba Pidato Bahasa Arab')
            ->assertSee('createActivityModal', false);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/bahasa/activities/{$activity->id}")
            ->assertOk();
        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/bahasa/activities/{$activity->id}/print")
            ->assertOk();
    }
}
