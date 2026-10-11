<?php

namespace Tests\Feature;

use App\Http\Controllers\SubjectController;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\CurriculumStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Menu struktur kurikulum & kebijakan Mapel Pemadatan:
 *  - Seeder menandai mapel pondok (source=pondok).
 *  - Satuan pendidikan bebas menambah mapel pemadatan + jamnya.
 *  - Jam mapel PONDOK terkunci untuk satuan pendidikan (hanya nama yang bisa diubah).
 */
class CurriculumStructureMenuTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private GradeLevel $grade;

    private function seedSchool(string $level = 'sd'): void
    {
        $wu = (string) Str::uuid();
        DB::table('work_units')->insert(['id' => $wu, 'name' => 'Unit', 'code' => 'WU', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->school = School::create(['work_unit_id' => $wu, 'npsn' => '11223344', 'name' => 'Sekolah Uji', 'school_level' => $level]);
    }

    public function test_seeder_menandai_mapel_pondok(): void
    {
        $this->seedSchool('sd');

        Artisan::call('db:seed', ['--class' => CurriculumStructureSeeder::class, '--force' => true]);

        $pondok = Subject::where('school_id', $this->school->id)->where('source', 'pondok')->count();
        $this->assertGreaterThan(0, $pondok, 'Mapel hasil seeder ditandai source=pondok.');
        $this->assertSame(0, Subject::where('school_id', $this->school->id)->where('source', 'pemadatan')->count());
    }

    public function test_satuan_menambah_mapel_pemadatan_dan_jam_pondok_terkunci(): void
    {
        $this->seedSchool('sd');
        Artisan::call('db:seed', ['--class' => CurriculumStructureSeeder::class, '--force' => true]);

        $this->grade = GradeLevel::where('school_id', $this->school->id)->orderBy('level')->firstOrFail();

        $user = User::create(['name' => 'Admin Satuan', 'email' => 'admin.satuan@test.local', 'password' => bcrypt('x'), 'is_active' => true]);
        $this->actingAs($user);

        // 1) Tambah MAPEL PEMADATAN (jam bebas ditentukan satuan).
        $request = Request::create('/x', 'POST', [
            'code' => 'IPS',
            'name' => 'Ilmu Pengetahuan Sosial',
            'allocations' => [$this->grade->id => 2],
        ]);
        $request->attributes->set('schoolContextId', $this->school->id);

        app(SubjectController::class)->storePemadatan($request, $user->id);

        $ips = Subject::where('school_id', $this->school->id)->where('code', 'IPS')->firstOrFail();
        $this->assertSame('pemadatan', $ips->source);
        $this->assertSame(2, (int) GradeLevelSubject::where('grade_level_id', $this->grade->id)->where('subject_id', $ips->id)->value('allocation_hours'));

        // 2) Mapel PONDOK: nama boleh diubah, jam/kredit TERKUNCI.
        $pondok = Subject::where('school_id', $this->school->id)->where('code', 'MAT')->firstOrFail();
        $before = (int) $pondok->credit_hours;

        $update = Request::create('/x', 'PUT', [
            'code' => $pondok->code,
            'name' => 'Matematika (Nama Satuan)',
            'category' => $pondok->category ?? 'nasional',
            'credit_hours' => 10,
            'description' => 'diubah satuan',
        ]);
        $update->attributes->set('schoolContextId', $this->school->id);
        app(SubjectController::class)->update($update, $user->id, $pondok->id);

        $pondok->refresh();
        $this->assertSame('Matematika (Nama Satuan)', $pondok->name, 'Nama mapel pondok dapat disesuaikan satuan.');
        $this->assertSame($before, (int) $pondok->credit_hours, 'Kredit/jam mapel pondok TETAP (terkunci).');
    }
}
