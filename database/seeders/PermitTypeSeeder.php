<?php

namespace Database\Seeders;

use App\Models\PermitType;
use Illuminate\Database\Seeder;

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
            ['code' => 'pulang',              'label' => 'Izin Pulang',                       'category' => 'default',   'icon' => 'ri-home-4-line',        'color' => 'primary',   'sort_order' => 10],
            ['code' => 'musibah',             'label' => 'Izin Musibah',                      'category' => 'special',   'icon' => 'ri-emotion-sad-line',   'color' => 'danger',    'sort_order' => 20],
            ['code' => 'walimah_keluarga',    'label' => 'Walimah Orang Tua/Saudara Kandung', 'category' => 'special',   'icon' => 'ri-heart-2-line',       'color' => 'warning',   'sort_order' => 30],
            ['code' => 'haji_umrah',          'label' => 'Izin Haji/Umrah',                   'category' => 'special',   'icon' => 'ri-flight-takeoff-line', 'color' => 'info',      'sort_order' => 40],
            ['code' => 'wisuda_keluarga',     'label' => 'Wisuda Orang Tua/Saudara Kandung',  'category' => 'special',   'icon' => 'ri-graduation-cap-line', 'color' => 'info',      'sort_order' => 50],
            ['code' => 'sakit',               'label' => 'Izin Sakit',                        'category' => 'special',   'icon' => 'ri-hospital-line',      'color' => 'danger',    'sort_order' => 60],
            ['code' => 'berobat',             'label' => 'Izin Berobat',                      'category' => 'special',   'icon' => 'ri-stethoscope-line',   'color' => 'success',   'sort_order' => 70],
            ['code' => 'keperluan_penting',   'label' => 'Keperluan Penting Lainnya',         'category' => 'special',   'icon' => 'ri-more-line',          'color' => 'secondary', 'sort_order' => 80],
            ['code' => 'keluar_kota',         'label' => 'Izin Keluar Kota',                  'category' => 'special',   'icon' => 'ri-roadster-line',      'color' => 'info',      'sort_order' => 90],
            ['code' => 'keperluan_keluarga',  'label' => 'Izin Keperluan Keluarga',           'category' => 'special',   'icon' => 'ri-family-line',        'color' => 'warning',   'sort_order' => 100],
            ['code' => 'darurat',             'label' => 'Izin Darurat',                      'category' => 'emergency', 'icon' => 'ri-alarm-warning-line', 'color' => 'danger',    'sort_order' => 110],
            ['code' => 'lainnya',             'label' => 'Lainnya',                           'category' => 'special',   'icon' => 'ri-more-line',          'color' => 'secondary', 'sort_order' => 120],
        ];

        foreach ($defaults as $d) {
            PermitType::updateOrCreate(
                ['code' => $d['code']],
                array_merge($d, [
                    'description' => null,
                    'is_active' => true,
                ])
            );
        }
    }
}
