<?php

namespace Tests\Feature;

use App\Http\Controllers\Evaluasi\KisiKisiController;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\KisiKisiSoal;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Cetak kisi-kisi: dokumen A4 berisi identitas + tabel item.
 */
class KisiKisiCetakTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $user;

    private KisiKisiSoal $kisi;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Uji Kisi', 'code' => 'UJI',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '33333333', 'name' => 'MTs Uji Kisi',
            'address' => 'Jl. Pendidikan No. 1', 'principal_name' => 'Kepala Uji',
        ]);

        $this->user = User::create([
            'name' => 'Guru Kisi', 'email' => 'guru.kisi@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $ay = AcademicYear::create(['name' => '2025/2026', 'semester' => 'ganjil', 'is_active' => true]);

        $gradeLevelId = (string) Str::uuid();
        DB::table('grade_levels')->insert([
            'id' => $gradeLevelId, 'school_id' => $this->school->id,
            'level' => 7, 'name' => 'Kelas 7', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id, 'code' => 'FQH', 'name' => 'Fiqih',
            'credit_hours' => 2, 'is_active' => true,
        ]);

        $this->kisi = KisiKisiSoal::create([
            'school_id' => $this->school->id,
            'subject_id' => $subject->id,
            'grade_level_id' => $gradeLevelId,
            'academic_year_id' => $ay->id,
            'semester' => 'ganjil',
            'jenis_ujian' => 'sts',
            'judul' => 'Kisi-Kisi STS Fiqih',
            'tingkat_sekolah' => 'mts',
            'total_soal_target' => 20,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_cetak_kisi_kisi_render(): void
    {
        $request = Request::create('/kisi-kisi-soal/'.$this->kisi->id.'/cetak', 'GET');
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->user);

        $this->actingAs($this->user);

        $view = app(KisiKisiController::class)->cetak($request, $this->user->id, $this->kisi->id);
        $html = $view->render();

        $this->assertStringContainsString('Kisi-Kisi Soal', $html);
        $this->assertStringContainsString('MTs Uji Kisi', $html);
        $this->assertStringContainsString('Fiqih', $html);
        $this->assertStringContainsString('Kisi-Kisi STS Fiqih', $html);
        $this->assertStringContainsString('Kepala Uji', $html);
        $this->assertStringContainsString('Guru Kisi', $html);
        $this->assertStringContainsString('window.print()', $html);
    }
}
