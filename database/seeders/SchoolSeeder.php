<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\WorkUnit;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        // ── Map sekolah → work_unit code (sesuai WorkUnitSeeder) ────────
        $schoolMap = [
            'UAK-001' => [
                'name' => 'SD IT Putra Abu Hurairah Mataram',
                'npsn' => '52010101', 'nss' => '527101001',
                'level' => 'sd', 'gender' => 'putra',
            ],
            'UAK-002' => [
                'name' => 'SD IT Putri Abu Hurairah Mataram',
                'npsn' => '52010102', 'nss' => '527101002',
                'level' => 'sd', 'gender' => 'putri',
            ],
            'UAK-003' => [
                'name' => 'SMP IT Putra Abu Hurairah Mataram',
                'npsn' => '52010203', 'nss' => '527102001',
                'level' => 'smp', 'gender' => 'putra',
            ],
            'UAK-004' => [
                'name' => 'SMP IT Putri Abu Hurairah Mataram',
                'npsn' => '52010204', 'nss' => '527102002',
                'level' => 'smp', 'gender' => 'putri',
            ],
            'UAK-005' => [
                'name' => 'SMP & SMA IT Putra Abu Hurairah Mataram',
                'npsn' => '52010205', 'nss' => '527102003',
                'level' => 'sma', 'gender' => 'putra',
            ],
            'UAK-006' => [
                'name' => 'SMA IT Putri Abu Hurairah Mataram',
                'npsn' => '52010306', 'nss' => '527103001',
                'level' => 'sma', 'gender' => 'putri',
            ],
            'UAK-007' => [
                'name' => 'MA Plus Abu Hurairah Mataram',
                'npsn' => '52010307', 'nss' => '527103002',
                'level' => 'sma', 'gender' => 'putra',
            ],
            'UAK-008' => [
                'name' => 'PPS Diniyah Abu Hurairah Mataram',
                'npsn' => '52010408', 'nss' => '527104001',
                'level' => 'sma', 'gender' => 'putra',
            ],
        ];

        // ── Ambil work_unit_id dari code ───────────────────────────────
        $workUnits = WorkUnit::whereIn('code', array_keys($schoolMap))->get();
        $wuMap = $workUnits->keyBy('code');

        $created = 0;
        $updated = 0;

        foreach ($schoolMap as $code => $info) {
            $wu = $wuMap->get($code);
            if (! $wu) {
                $this->command->warn("  ⚠️ WorkUnit '$code' tidak ditemukan — skip sekolah {$info['name']}");

                continue;
            }

            $data = [
                'work_unit_id' => $wu->id,
                'school_code' => $code,
                'npsn' => $info['npsn'],
                'nss' => $info['nss'],
                'name' => $info['name'],
                'address' => 'Jalan Majapahit No. 54 B Punia, Mataram',
                'province_code' => '52',
                'city_code' => '5271',
                'district_code' => '527102',
                'village_code' => '5271021012',
                'postal_code' => '83115',
                'phone' => '(0370) 633295',
                'school_level' => $info['level'],
                'school_gender' => $info['gender'],
                'school_status' => 'swasta',
                'accreditation' => 'A',
                'accreditation_year' => 2024,
                'operational_hours' => 'full_day',
                'is_active' => true,
            ];

            $school = School::where('npsn', $info['npsn'])->first();
            if ($school) {
                $school->update($data);
                $updated++;
            } else {
                School::create($data);
                $created++;
            }
            $this->command->info("  ✅ Sekolah: {$info['name']} (NPSN: {$info['npsn']})");
        }

        $this->command->info("✅ SchoolSeeder selesai — Created: {$created}, Updated: {$updated}");
        $this->command->info('   Schools total: '.School::count());
    }
}
