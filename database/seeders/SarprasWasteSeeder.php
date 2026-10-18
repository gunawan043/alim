<?php

namespace Database\Seeders;

use App\Models\AssetBuilding;
use App\Models\User;
use App\Models\WasteSource;
use App\Models\WorkUnit;
use Illuminate\Database\Seeder;

class SarprasWasteSeeder extends Seeder
{
    public function run(): void
    {
        $urt = WorkUnit::query()->where('code', 'DEP-URT')->orWhere('name', 'like', '%Unit Rumah Tangga%')->first();
        $createdBy = User::query()->where('is_system_admin', true)->value('id') ?? User::query()->value('id');

        $sources = [
            ['Dapur Pondok', 'dapur', null],
            ['Asrama Putra', 'asrama', null],
            ['Asrama Putri', 'asrama', null],
            ['Kelas SD', 'kelas', null],
            ['Kelas SMP', 'kelas', null],
            ['Kelas SMA', 'kelas', null],
            ['Kantor Utama', 'kantor', 'Kantor Utama'],
            ['UKS Putra', 'uks', 'UKS Putra'],
            ['UKS Putri', 'uks', 'UKS Putri'],
            ['Masjid Jami\'', 'masjid', 'Masjid Jami\''],
            ['Masjid Khadijah', 'masjid', 'Masjid Khadijah'],
            ['Kantin', 'kantin', null],
            ['Taman dan Halaman', 'taman', null],
        ];

        foreach ($sources as [$name, $type, $buildingName]) {
            $building = $buildingName ? AssetBuilding::query()->where('building_name', $buildingName)->first() : null;

            WasteSource::firstOrCreate(
                ['name' => $name],
                [
                    'source_type' => $type,
                    'work_unit_id' => $urt?->id,
                    'building_id' => $building?->id,
                    'is_active' => true,
                    'created_by' => $createdBy,
                    'notes' => 'Data awal sumber sampah untuk laporan DLH.',
                ]
            );
        }

        $this->command?->info('SarprasWasteSeeder: '.(count($sources)).' sumber sampah disiapkan.');
    }
}
