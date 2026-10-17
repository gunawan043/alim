<?php

namespace Database\Seeders;

use App\Models\Dormitory;
use App\Models\School;
use App\Models\WorkUnit;
use Illuminate\Database\Seeder;

class DormitorySeeder extends Seeder
{
    public function run(): void
    {
        // ── Ambil semua work_unit & school ─────────────────────────────
        $wus = WorkUnit::all()->keyBy('code');
        $schools = School::all()->keyBy('npsn');

        // ── Daftar asrama: code, name, gender, npsn_sekolah ────────────
        $dormitoryData = [
            'PNG-001' => [
                'name' => 'Asrama SMP IT Putra Abu Hurairah Mataram',
                'gender' => 'putra',
                'npsn' => '52010203',
            ],
            'PNG-002' => [
                'name' => 'Asrama SMP IT Putri Abu Hurairah Mataram',
                'gender' => 'putri',
                'npsn' => '52010204',
            ],
            'PNG-003' => [
                'name' => 'Asrama SMA IT Putri Abu Hurairah Mataram',
                'gender' => 'putri',
                'npsn' => '52010306',
            ],
            'PNG-004' => [
                'name' => 'Asrama MA Plus Abu Hurairah Mataram',
                'gender' => 'putra',
                'npsn' => '52010307',
            ],
            'PNG-005' => [
                'name' => 'Asrama PPS Diniyah Abu Hurairah Mataram',
                'gender' => 'putra',
                'npsn' => '52010408',
            ],
        ];

        foreach ($dormitoryData as $wuCode => $data) {
            $wu = $wus->get($wuCode);
            if (! $wu) {
                $this->command->warn("  ⚠️ WorkUnit '$wuCode' tidak ditemukan — skip {$data['name']}");

                continue;
            }

            $school = $schools->first(fn ($s) => $s->npsn === $data['npsn']);

            $dorm = Dormitory::firstOrCreate(
                ['code' => strtoupper(str_replace(' ', '_', $wuCode)).'-001'],
                [
                    'work_unit_id' => $wu->id,
                    'school_id' => $school ? $school->id : null,
                    'gender' => $data['gender'],
                    'address' => 'Jl. Pondok, Mataram NTB',
                    'phone' => '0878-1234-5678',
                    'capacity' => 40,
                    'total_rooms' => 0,
                    'total_wings' => 0,
                    'head_id' => null, // Dikosongkan terlebih dahulu
                    'is_active' => true,
                ]
            );

            $this->command->info("  ✅ Asrama: {$data['name']} (ID: {$dorm->id})");
        }

        $this->command->info('✅ Dormitory seeder completed!');
    }
}
