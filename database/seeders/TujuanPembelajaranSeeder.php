<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Subject;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class TujuanPembelajaranSeeder extends Seeder
{
    public function run(): void
    {
        $tps = config('f9.tujuan_pembelajaran', []);

        if (empty($tps)) {
            $this->command?->warn('[TujuanPembelajaranSeeder] No TP config found at config/f9.php');

            return;
        }

        $created = 0;
        $skipped = 0;

        foreach ($tps as $row) {
            $subject = Subject::where('code', $row['subject_code'])->first();
            if (! $subject) {
                $skipped++;
                $this->command?->warn("[TujuanPembelajaranSeeder] Subject code '{$row['subject_code']}' not found, skipping TP {$row['kode_tp']}");
                Log::warning('TP seed skipped: subject not found', $row);

                continue;
            }

            $grade = GradeLevel::where('level', $row['grade_level'])->first();
            if (! $grade) {
                $skipped++;
                $this->command?->warn("[TujuanPembelajaranSeeder] Grade level '{$row['grade_level']}' not found, skipping TP {$row['kode_tp']}");

                continue;
            }

            $year = AcademicYear::where('name', $row['academic_year_name'])->first()
                ?? AcademicYear::where('is_active', true)->first();

            $createdBy = User::where('email', $row['created_by_email'] ?? 'admin@alim.local')->first();

            TujuanPembelajaran::firstOrCreate(
                [
                    'subject_id' => $subject->id,
                    'grade_level_id' => $grade->id,
                    'academic_year_id' => $year?->id,
                    'kode_tp' => $row['kode_tp'],
                ],
                [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'school_id' => $subject->school_id,
                    'semester' => $row['semester'],
                    'fase' => $row['fase'],
                    'elemen' => $row['elemen'] ?? null,
                    'deskripsi' => $row['deskripsi'],
                    'alokasi_waktu' => $row['alokasi_waktu'] ?? null,
                    'urutan' => $row['urutan'] ?? 1,
                    'is_active' => true,
                    'created_by' => $createdBy?->id,
                ]
            );
            $created++;
        }

        $this->command?->info("[TujuanPembelajaranSeeder] Done. created/updated: {$created}, skipped: {$skipped}");
    }
}
