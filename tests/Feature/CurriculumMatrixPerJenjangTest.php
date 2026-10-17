<?php

namespace Tests\Feature;

use App\Http\Controllers\SuperAdmin\CurriculumStructureController;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Database\Seeders\CurriculumStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Matriks Kurikulum Pembelajaran per jenjang (Super Admin):
 * SD (I–VI, 40 JP), SMP (VII–IX, 46 JP), SMA (X, XI/XII BHS & IPA).
 */
class CurriculumMatrixPerJenjangTest extends TestCase
{
    use RefreshDatabase;

    private function school(string $level): School
    {
        $wu = (string) Str::uuid();
        DB::table('work_units')->insert(['id' => $wu, 'name' => 'Unit', 'code' => 'WU-'.$level, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);

        return School::create(['work_unit_id' => $wu, 'npsn' => (string) random_int(10000000, 99999999), 'name' => 'Sekolah '.strtoupper($level), 'school_level' => $level]);
    }

    public function test_matriks_per_jenjang_sesuai_struktur_pondok(): void
    {
        $this->school('sd');
        $this->school('smp');
        $this->school('sma');

        Artisan::call('db:seed', ['--class' => CurriculumStructureSeeder::class, '--force' => true]);

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web'], ['level' => 1]);
        $admin = User::create(['name' => 'SA', 'email' => 'sa.matrix@test.local', 'password' => bcrypt('x'), 'is_active' => true]);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        $request = Request::create('/x', 'GET');
        $view = app(CurriculumStructureController::class)->matrix($request);
        $blocks = collect($view->getData()['jenjangs'])->keyBy('nama');

        $this->assertTrue($blocks->has('SD') && $blocks->has('SMP') && $blocks->has('SMA'));

        // SD: 6 kolom (I–VI), total 40 JP tiap kolom.
        $this->assertSame(['I', 'II', 'III', 'IV', 'V', 'VI'], $blocks['SD']['labels']);
        foreach ($blocks['SD']['totals'] as $total) {
            $this->assertSame(40, $total, 'SD 40 JP per pekan.');
        }

        // SMP: 3 kolom, total 46 JP tiap kolom.
        $this->assertSame(['VII', 'VIII', 'IX'], $blocks['SMP']['labels']);
        foreach ($blocks['SMP']['totals'] as $total) {
            $this->assertSame(46, $total, 'SMP 46 JP per pekan.');
        }

        // SMA: 5 kolom dengan peminatan; Matematika Peminatan hanya IPA.
        $this->assertSame(['X', 'XI (BHS)', 'XI (IPA)', 'XII (BHS)', 'XII (IPA)'], $blocks['SMA']['labels']);
        // Kolom JS: kunci peminatan tersedia untuk modal edit/tambah.
        $this->assertSame(
            ['10', '11:bahasa', '11:ipa', '12:bahasa', '12:ipa'],
            collect($blocks['SMA']['columns'])->pluck('key')->all()
        );

        $mpn = collect($blocks['SMA']['rows'])->firstWhere('name', 'Matematika Peminatan');
        $this->assertNotNull($mpn);
        $this->assertSame([null, null, 2, null, 2], $mpn['cells'], 'Matematika Peminatan hanya jalur IPA.');
    }

    public function test_filter_jenjang_dan_tambah_mapel(): void
    {
        $this->school('sd');
        $this->school('sma');
        Artisan::call('db:seed', ['--class' => CurriculumStructureSeeder::class, '--force' => true]);

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web'], ['level' => 1]);
        $admin = User::create(['name' => 'SA2', 'email' => 'sa.matrix2@test.local', 'password' => bcrypt('x'), 'is_active' => true]);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);
        $controller = app(CurriculumStructureController::class);

        // Filter per jenjang: hanya SD yang tampil.
        $view = $controller->matrix(Request::create('/x', 'GET', ['jenjang' => 'SD']));
        $blocks = $view->getData()['jenjangs'];
        $this->assertCount(1, $blocks);
        $this->assertSame('SD', $blocks->first()['nama']);

        // Tambah mapel baru via struktur pada jenjang SD (kode unik agar tidak bentrok mapel seeder).
        $sdSchool = \App\Models\School::where('school_level', 'sd')->firstOrFail();
        $sdGrades = \App\Models\GradeLevel::where('school_id', $sdSchool->id)->where('level', 1)->firstOrFail();
        $store = Request::create('/x', 'POST', [
            'jenjang' => 'SD',
            'code' => 'IPSD',
            'name' => 'Ilmu Pengetahuan Sosial',
            'allocations' => ['1' => 2, '2' => 2],
        ]);
        $controller->storeSubject($store);

        $ips = \App\Models\Subject::where('code', 'IPSD')->where('school_id', $sdSchool->id)->first();
        $this->assertNotNull($ips, 'Mapel baru masuk struktur pondok.');
        $this->assertSame(2, (int) \App\Models\GradeLevelSubject::where('grade_level_id', $sdGrades->id)->where('subject_id', $ips->id)->value('allocation_hours'));
    }

}
