<?php

namespace Database\Seeders;

use App\Models\PermitType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seed master data default PermitType.
 *
 * Aman dijalankan berulang (idempotent): jika code sudah ada, lewati.
 * Migration create_permit_types_table sudah menanam data ini, tapi
 * seeder ini berguna untuk environment yang butuh reset ulang.
 */
class PermitTypeSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['code' => 'pulang',              'label' => 'Izin Pulang',                  'category' => 'default',   'icon' => 'ri-home-4-line',        'color' => 'primary',   'sort_order' => 10],
            ['code' => 'keluar_kota',         'label' => 'Izin Keluar Kota',             'category' => 'special',   'icon' => 'ri-roadster-line',      'color' => 'info',      'sort_order' => 20],
            ['code' => 'berobat',             'label' => 'Izin Berobat',                 'category' => 'special',   'icon' => 'ri-stethoscope-line',   'color' => 'success',   'sort_order' => 30],
            ['code' => 'sakit',               'label' => 'Izin Sakit',                   'category' => 'special',   'icon' => 'ri-hospital-line',      'color' => 'danger',    'sort_order' => 40],
            ['code' => 'keperluan_keluarga',  'label' => 'Izin Keperluan Keluarga',      'category' => 'special',   'icon' => 'ri-family-line',        'color' => 'warning',   'sort_order' => 50],
            ['code' => 'darurat',             'label' => 'Izin Darurat',                 'category' => 'emergency', 'icon' => 'ri-alarm-warning-line', 'color' => 'danger',    'sort_order' => 60],
            ['code' => 'lainnya',             'label' => 'Lainnya',                      'category' => 'special',   'icon' => 'ri-more-line',          'color' => 'secondary', 'sort_order' => 70],
        ];

        foreach ($defaults as $d) {
            PermitType::firstOrCreate(
                ['code' => $d['code']],
                array_merge($d, [
                    'id' => (string) Str::uuid(),
                    'description' => null,
                    'is_active' => true,
                ])
            );
        }
    }
}
