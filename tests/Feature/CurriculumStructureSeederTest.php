<?php

namespace Tests\Feature;

use App\Models\School;
use Database\Seeders\CurriculumStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Seeder WAJIB struktur kurikulum: tingkat, mapel, alokasi jam per pekan
 * (SD 40 JP, SMP 46 JP, SMA dengan peminatan BAHASA/IPA) — idempoten.
 */
class CurriculumStructureSeederTest extends TestCase
{
    use RefreshDatabase;

    private function makeSchool(string $level): School
    {
        $wu = (string) Str::uuid();
        DB::table('work_units')->insert(['id' => $wu, 'name' => 'Unit '.$level, 'code' => 'WU-'.strtoupper($level), 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);

        return School::create(['work_unit_id' => $wu, 'npsn' => (string) random_int(10000000, 99999999), 'name' => 'Sekolah '.strtoupper($level), 'school_level' => $level]);
    }

    public function test_seeder_sd_dan_smp_jam_per_pekan_sesuai_struktur(): void
    {
        $sd = $this->makeSchool('sd');
        $smp = $this->makeSchool('smp');

        Artisan::call('db:seed', ['--class' => CurriculumStructureSeeder::class, '--force' => true]);

        // SD: 6 tingkat, total 40 JP per tingkat.
        $sdGrades = DB::table('grade_levels')->where('school_id', $sd->id)->get();
        $this->assertCount(6, $sdGrades);
        foreach ($sdGrades as $g) {
            $this->assertSame(40, (int) DB::table('grade_level_subjects')->where('grade_level_id', $g->id)->sum('allocation_hours'), 'SD total 40 JP per pekan.');
        }

        // SMP: 3 tingkat, total 46 JP per tingkat.
        $smpGrades = DB::table('grade_levels')->where('school_id', $smp->id)->get();
        $this->assertCount(3, $smpGrades);
        foreach ($smpGrades as $g) {
            $this->assertSame(46, (int) DB::table('grade_level_subjects')->where('grade_level_id', $g->id)->sum('allocation_hours'), 'SMP total 46 JP per pekan.');
        }
    }

    public function test_seeder_sma_peminatan_dan_idempoten(): void
    {
        $sma = $this->makeSchool('sma');

        Artisan::call('db:seed', ['--class' => CurriculumStructureSeeder::class, '--force' => true]);

        $gradeIds = DB::table('grade_levels')->where('school_id', $sma->id)->pluck('id');

        // Peminatan BAHASA/IPA terisi (contoh: Matematika Peminatan khusus IPA).
        $mpn = DB::table('subjects')->where('school_id', $sma->id)->where('code', 'MPN')->first();
        $this->assertNotNull($mpn);
        $tracks = DB::table('grade_level_subjects')->where('subject_id', $mpn->id)->pluck('track')->unique()->values()->all();
        $this->assertSame(['ipa'], $tracks);

        // Idempoten: dijalankan ulang tidak menambah baris.
        $before = DB::table('grade_level_subjects')->whereIn('grade_level_id', $gradeIds)->count();
        $subjectsBefore = DB::table('subjects')->where('school_id', $sma->id)->count();

        Artisan::call('db:seed', ['--class' => CurriculumStructureSeeder::class, '--force' => true]);

        $this->assertSame($before, DB::table('grade_level_subjects')->whereIn('grade_level_id', $gradeIds)->count());
        $this->assertSame($subjectsBefore, DB::table('subjects')->where('school_id', $sma->id)->count());
    }
}
