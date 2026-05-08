<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        $years = [
            // 2023/2024
            ['name' => '2023/2024', 'semester' => 'ganjil', 'start' => '2023-07-10', 'end' => '2023-12-22'],
            ['name' => '2023/2024', 'semester' => 'genap', 'start' => '2024-01-08', 'end' => '2024-06-07'],
            // 2024/2025
            ['name' => '2024/2025', 'semester' => 'ganjil', 'start' => '2024-07-15', 'end' => '2024-12-20'],
            ['name' => '2024/2025', 'semester' => 'genap', 'start' => '2025-01-06', 'end' => '2025-06-06'],
            // 2025/2026 (active)
            ['name' => '2025/2026', 'semester' => 'ganjil', 'start' => '2025-07-14', 'end' => '2025-12-19', 'is_active' => true],
            ['name' => '2025/2026', 'semester' => 'genap', 'start' => '2026-01-05', 'end' => '2026-06-05'],
            // 2026/2027
            ['name' => '2026/2027', 'semester' => 'ganjil', 'start' => '2026-07-13', 'end' => '2026-12-18'],
        ];

        $count = 0;
        foreach ($years as $year) {
            $exists = AcademicYear::where('name', $year['name'])
                ->where('semester', $year['semester'])
                ->exists();

            if ($exists) {
                continue;
            }

            AcademicYear::create([
                'id'                    => (string) Str::uuid(),
                'name'                  => $year['name'],
                'semester'              => $year['semester'],
                'is_active'            => $year['is_active'] ?? false,
                'start_date'           => $year['start'],
                'end_date'             => $year['end'],
                'registration_start'   => date('Y-m-d', strtotime($year['start'] . ' -60 days')),
                'registration_end'     => date('Y-m-d', strtotime($year['start'] . ' -1 day')),
            ]);
            $count++;
        }

        $this->command->info("✅ AcademicYearSeeder: {$count} tahun ajaran dibuat.");
    }
}
