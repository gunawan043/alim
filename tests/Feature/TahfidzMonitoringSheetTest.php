<?php

namespace Tests\Feature;

use App\Http\Controllers\Tahfidz\TahfidzGradebookController;
use App\Http\Controllers\Tahfidz\TahfidzMonitoringSheetController;
use App\Http\Controllers\Tahfidz\TahfidzReportController;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Student;
use App\Models\TahfidzGroup;
use App\Models\TahfidzMuqorrorMonth;
use App\Models\TahfidzMuqorrorPlan;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzSetoran;
use App\Models\User;
use App\Services\QuranPositionService;
use App\Services\TahfidzPresenceService;
use App\Services\TahfidzTargetService;
use Database\Seeders\TahfidzAyatPositionSeeder;
use Database\Seeders\TahfidzSurahMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class TahfidzMonitoringSheetTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private School $school;

    private AcademicYear $year;

    private TahfidzGroup $group;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Tahfidz Monitoring',
            'code' => 'UTM',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '91919191',
            'name' => 'Sekolah Monitoring Tahfidz',
        ]);

        $this->year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $this->teacher = User::create([
            'name' => 'Guru Tahfidz Monitoring',
            'email' => 'guru.tahfidz.monitoring@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->group = TahfidzGroup::create([
            'work_unit_id' => $workUnitId,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Halaqah Monitoring',
            'gender' => 'putra',
            'teacher_id' => $this->teacher->id,
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'school_id' => $this->school->id,
            'name' => 'Santri Monitoring',
            'nisn' => '9191919101',
            'nis' => 'M-01',
            'gender' => 'L',
            'status' => 'active',
        ]);

        TahfidzGroupMember::create([
            'tahfidz_group_id' => $this->group->id,
            'student_id' => $this->student->id,
            'academic_year_id' => $this->year->id,
            'join_date' => '2026-07-01',
            'status' => 'aktif',
        ]);

        Auth::login($this->teacher);
        URL::defaults(['userId' => $this->teacher->id]);
    }

    public function test_monitoring_sheet_stores_dynamic_week_and_lahn_values(): void
    {
        $this->seed(TahfidzSurahMasterSeeder::class);
        $this->seed(TahfidzAyatPositionSeeder::class);

        $request = $this->request('POST', [
            'tahfidz_group_id' => $this->group->id,
            'student_id' => $this->student->id,
            'setoran_date' => '2026-10-05',
            'setoran_type' => 'robet',
            'metode_pembelajaran' => 'Talaqqi',
            'juz' => 30,
            'surah_start_name' => 'An-Naba',
            'ayat_start' => 1,
            'surah_end_name' => 'An-Naba',
            'ayat_end' => 5,
            'halaman_start' => 582,
            'halaman_end' => 582,
            'capaian_target' => 'tercapai',
            'keterangan_capaian' => 'Target pekan terpenuhi',
            'tikrar_task' => 'Ulangi 5 kali',
            'tikrar_repetitions' => 5,
            'nilai_tikrar' => 88,
            'robet_status' => 'siap',
            'hasil_hafalan_catatan' => 'Lancar',
            'lahn_khofi_count' => 2,
            'lahn_jali_count' => 1,
            'nilai_lahn' => 96,
        ]);

        $response = app(TahfidzMonitoringSheetController::class)->store($request, $this->teacher->id);
        $this->assertSame(302, $response->getStatusCode());

        $entry = TahfidzSetoran::where('student_id', $this->student->id)->firstOrFail();
        $this->assertSame('robet', $entry->setoran_type);
        $this->assertSame(2, $entry->week_number);
        $this->assertSame('88.00', $entry->nilai_tikrar);
        $this->assertSame('5', (string) $entry->tikrar_repetitions);
        $this->assertSame('siap', $entry->robet_status);
        $this->assertSame('2', (string) $entry->lahn_khofi_count);
        $this->assertSame('1', (string) $entry->lahn_jali_count);
        $this->assertSame('96.00', $entry->nilai_lahn);
        $this->assertSame('3', (string) $entry->baris_start);
        $this->assertSame('pending', $entry->verification_status);
    }

    public function test_madinah_quran_position_is_resolved_by_surah_and_ayah(): void
    {
        $this->seed(TahfidzAyatPositionSeeder::class);

        $position = app(QuranPositionService::class)->range(34, 10);

        $this->assertSame(429, $position['page_start']);
        $this->assertSame(5, $position['line_start']);
        $this->assertSame(429, $position['page_end']);
        $this->assertSame(6, $position['line_end']);
    }

    public function test_presence_indicator_marks_student_who_already_deposited(): void
    {
        $this->createEntry('2026-10-05', 'ziyadah', 'Materi awal');

        $presence = app(TahfidzPresenceService::class)
            ->forStudent($this->student->id, '2026-10-05');

        $this->assertSame('sudah_setor', $presence['status']);
        $this->assertTrue($presence['has_setoran']);
    }

    public function test_monitoring_sheet_groups_rows_by_calendar_week(): void
    {
        $this->createEntry('2026-10-05', 'ziyadah', 'Materi awal');
        $this->createEntry('2026-10-12', 'murajaah', 'Materi pekan kedua');

        $request = $this->request('GET', [
            'student_id' => $this->student->id,
            'group_id' => $this->group->id,
            'month' => '2026-10',
        ]);

        $view = app(TahfidzMonitoringSheetController::class)->index($request, $this->teacher->id);
        $data = $view->getData();

        $this->assertTrue($data['weekGroups']->has(2));
        $this->assertTrue($data['weekGroups']->has(3));
        $this->assertSame(1, $data['weekGroups']->get(2)->count());
        $this->assertSame(1, $data['weekGroups']->get(3)->count());
        $this->assertSame('Materi awal', $data['weekGroups']->get(2)->first()->hasil_hafalan_catatan);

        $html = $view->render();
        $this->assertStringContainsString('Lembar Monitoring Hafalan Al-Qur’an', $html);
        $this->assertStringContainsString('Tugas Tikrar Hafalan', $html);
    }

    public function test_monitoring_sheet_updates_entry_without_calculating_final_score(): void
    {
        $entry = $this->createEntry('2026-10-05', 'ziyadah', 'Materi awal');

        $request = $this->request('PUT', [
            'tahfidz_group_id' => $this->group->id,
            'student_id' => $this->student->id,
            'setoran_date' => '2026-10-05',
            'setoran_type' => 'tikror',
            'capaian_target' => 'melampaui',
            'nilai_tikrar' => 91,
            'lahn_khofi_count' => 1,
            'lahn_jali_count' => 0,
            'nilai_lahn' => 99,
        ]);

        app(TahfidzMonitoringSheetController::class)->update($request, $this->teacher->id, $entry->id);
        $entry->refresh();

        $this->assertSame('tikror', $entry->setoran_type);
        $this->assertSame('melampaui', $entry->capaian_target);
        $this->assertNull($entry->nilai_setoran);
    }

    public function test_target_muqorror_applies_direction_for_non_alumni_and_alumni(): void
    {
        $plan = TahfidzMuqorrorPlan::create([
            'work_unit_id' => $this->group->work_unit_id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'semester' => 'ganjil',
            'title' => 'Muqorror Uji Arah Juz',
            'status' => 'active',
            'is_active' => true,
            'created_by' => $this->teacher->id,
        ]);

        TahfidzMuqorrorMonth::create([
            'plan_id' => $plan->id,
            'month' => 7,
            'target_pages' => 5,
            'alumni_material' => 'Juz 1 halaman awal',
            'non_alumni_material' => 'Juz 30 halaman akhir',
            'sort_order' => 0,
        ]);

        $service = app(TahfidzTargetService::class);
        $count = $service->applyToGroup($this->group, $this->teacher->id);
        $this->assertSame(1, $count);

        $target = \App\Models\TahfidzStudentTarget::where('student_id', $this->student->id)->firstOrFail();
        $this->assertSame(30, $target->juz_start);
        $this->assertStringContainsString('30 → 1 (non-alumni)', $target->notes);
        $this->assertStringContainsString('Juz 30 halaman akhir', $target->notes);

        $this->student->origin_school_id = $this->school->id;
        $this->student->save();
        $this->group->unsetRelation('members');

        $service->applyToGroup($this->group, $this->teacher->id);
        $target->refresh();
        $this->assertSame(1, $target->juz_start);
        $this->assertStringContainsString('1 → 30 (alumni)', $target->notes);
    }

    public function test_gradebook_aggregates_raw_tahfidz_scores(): void
    {
        $this->createEntry('2026-10-05', 'ziyadah', 'Materi awal');

        $request = $this->request('GET', [
            'group_id' => $this->group->id,
            'semester' => 'ganjil',
        ]);

        $view = app(TahfidzGradebookController::class)->index($request, $this->teacher->id);
        $data = $view->getData();

        $this->assertSame(1, $data['stats']['students']);
        $this->assertSame(1, $data['stats']['setoran']);
        $this->assertSame(1, $data['rows']->first()['ziyadah']);
        $this->assertNull($data['rows']->first()['setoran_avg']);
    }

    public function test_tahfidz_report_exposes_monthly_chart_data(): void
    {
        $this->createEntry('2026-10-05', 'ziyadah', 'Materi awal');

        $request = $this->request('GET', [
            'group_id' => $this->group->id,
            'month' => '2026-10',
        ]);

        $view = app(TahfidzReportController::class)->index($request, $this->teacher->id);
        $data = $view->getData();

        $this->assertSame(1, $data['stats']['setoran']);
        $this->assertSame(1, $data['typeCounts']['ziyadah']);
        $this->assertSame(1, $data['daily'][5]);
        $this->assertCount(31, $data['daily']);
    }

    private function createEntry(string $date, string $type, string $result): TahfidzSetoran
    {
        return TahfidzSetoran::create([
            'tahfidz_group_id' => $this->group->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academic_year_id' => $this->year->id,
            'setoran_date' => $date,
            'week_number' => 2,
            'setoran_type' => $type,
            'surah_start_name' => 'An-Naba',
            'ayat_start' => 1,
            'capaian_target' => 'tercapai',
            'hasil_hafalan_catatan' => $result,
            'status' => 'lulus',
            'verification_status' => 'pending',
        ]);
    }

    private function request(string $method, array $payload): Request
    {
        $request = Request::create('/'.$this->teacher->id.'/tahfidz/monitoring/sheet', $method, $payload);
        $request->setUserResolver(fn () => $this->teacher);
        $request->attributes->set('schoolContextId', $this->school->id);
        app()->instance('request', $request);

        return $request;
    }
}
