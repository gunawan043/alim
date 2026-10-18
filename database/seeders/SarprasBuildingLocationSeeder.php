<?php

namespace Database\Seeders;

use App\Models\AssetBuilding;
use App\Models\AssetRoom;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Database\Seeder;

/**
 * Seed master gedung yang nama dan areanya sudah jelas dari formulir pemeliharaan.
 *
 * Peralatan AC, genset, pompa/panel, jaringan listrik, air minum, dan septic tank
 * TIDAK di-seed karena lokasi ruang, unit pemilik, jumlah, merek, dan data perolehan
 * belum cukup jelas — data tersebut akan diisi sebagai data asli.
 */
class SarprasBuildingLocationSeeder extends Seeder
{
    public function run(): void
    {
        $urt = WorkUnit::query()
            ->where('code', 'DEP-URT')
            ->orWhere('name', 'like', '%Unit Rumah Tangga%')
            ->first();

        if (! $urt) {
            $this->command?->warn('SarprasBuildingLocationSeeder: Unit Rumah Tangga tidak ditemukan, seeder dilewati.');
            return;
        }

        $createdBy = User::query()->where('is_system_admin', true)->value('id')
            ?? User::query()->value('id');

        $buildings = [
            ['Pondok Putra', 'Abu Bakar As Siddiq', 'asrama', 'GD-PA-01'],
            ['Pondok Putra', 'Umar bin Khattab', 'asrama', 'GD-PA-02'],
            ['Pondok Putra', 'Ustman bin Affan', 'asrama', 'GD-PA-03'],
            ['Pondok Putra', 'Ali bin Abi Thalib', 'asrama', 'GD-PA-04'],
            ['Pondok Putra', 'Thalhah bin Ubaidillah', 'asrama', 'GD-PA-05'],
            ['Pondok Putra', 'Zubair bin Awwam', 'asrama', 'GD-PA-06'],
            ['Pondok Putra', 'Kantor Utama', 'kantor', 'GD-PA-07'],
            ['Pondok Putra', 'Kantor Pelayanan Umum', 'kantor', 'GD-PA-08'],
            ['Pondok Putra', 'UKS Putra', 'uks', 'GD-PA-09'],
            ['Pondok Putra', 'Tower Zam-Zam', 'lainnya', 'GD-PA-10'],
            ['Pondok Putra', 'Perumahan Ustadz 1', 'lainnya', 'GD-PA-11'],
            ['Pondok Putra', 'Perumahan Ustadz 2', 'lainnya', 'GD-PA-12'],
            ['Pondok Putra', 'Masjid Ibnu Taimiyah', 'masjid', 'GD-PA-13'],
            ['Pondok Putra', 'Kantor Departemen Tahfidz', 'kantor', 'GD-PA-14'],
            ['Pondok Putra', 'Dapur', 'kantin', 'GD-PA-15'],
            ['Pondok Putra', 'Laila Hizam', 'asrama', 'GD-PA-16'],
            ['Pondok Putra', 'Abdurrahman bin Auf', 'asrama', 'GD-PA-17'],
            ['Pondok Putra', 'Masjid Jami\'', 'masjid', 'GD-PA-18'],
            ['Pondok Putri', 'Hafshah', 'asrama', 'GD-PI-01'],
            ['Pondok Putri', 'Masjid Khadijah', 'masjid', 'GD-PI-02'],
            ['Pondok Putri', 'Zainab', 'asrama', 'GD-PI-03'],
            ['Pondok Putri', 'Sarah', 'asrama', 'GD-PI-04'],
            ['Pondok Putri', 'Hawa', 'asrama', 'GD-PI-05'],
            ['Pondok Putri', 'Hajar', 'asrama', 'GD-PI-06'],
            ['Pondok Putri', 'Aisyah', 'asrama', 'GD-PI-07'],
            ['Pondok Putri', 'Shafiyah', 'asrama', 'GD-PI-08'],
            ['Pondok Putri', 'Maimunah', 'asrama', 'GD-PI-09'],
            ['Pondok Putri', 'Saudah', 'asrama', 'GD-PI-10'],
            ['Pondok Putri', 'Ruang Staf', 'kantor', 'GD-PI-11'],
            ['Pondok Putri', 'Ruang Kepala Pengasuhan', 'kantor', 'GD-PI-12'],
            ['Pondok Putri', 'UKS Putri', 'uks', 'GD-PI-13'],
            ['Pondok Putri', 'Ummu Kultsum', 'asrama', 'GD-PI-14'],
            ['Pondok Putri', 'Ruqayyah', 'asrama', 'GD-PI-15'],
            ['Lawata', 'Hamzah', 'asrama', 'GD-LW-01'],
            ['Lawata', 'Usamah', 'asrama', 'GD-LW-02'],
            ['Lawata', 'Muawiyah', 'asrama', 'GD-LW-03'],
        ];

        $created = 0;
        $skipped = 0;

        foreach ($buildings as [$area, $name, $type, $code]) {
            $existing = AssetBuilding::query()
                ->where('work_unit_id', $urt->id)
                ->where('building_name', $name)
                ->first();

            if ($existing) {
                $skipped++;
                continue;
            }

            $code = $this->uniqueCode($code);

            AssetBuilding::create([
                'work_unit_id' => $urt->id,
                'building_code' => $code,
                'building_name' => $name,
                'building_type' => $type,
                'structure_condition' => 'baik',
                'ownership_status' => 'milik_sendiri',
                'is_active' => true,
                'created_by' => $createdBy,
                'notes' => 'Data awal dari formulir pemeliharaan — Area '.$area.'. Lengkapi luas, tahun, dan IMB bila tersedia.',
            ]);

            $created++;
        }

        $toiletBuildings = [
            'Abu Bakar As Siddiq', 'Umar bin Khattab', 'Ustman bin Affan', 'Ali bin Abi Thalib',
            'Thalhah bin Ubaidillah', 'Zubair bin Awwam', 'Kantor Utama', 'Tower Zam-Zam',
            'Laila Hizam', 'Abdurrahman bin Auf', 'Masjid Jami\'', 'Hafshah', 'Zainab', 'Sarah',
            'Aisyah', 'Shafiyah', 'Hamzah',
        ];

        $roomsCreated = 0;

        foreach ($toiletBuildings as $buildingName) {
            $building = AssetBuilding::query()
                ->where('work_unit_id', $urt->id)
                ->where('building_name', $buildingName)
                ->first();

            if (! $building || ! $building->school_id) {
                // Satuan pendidikan/unit kamar belum jelas — jangan membuat data asli.
                continue;
            }

            $room = AssetRoom::firstOrCreate(
                [
                    'building_id' => $building->id,
                    'room_name' => 'Kamar Mandi',
                    'room_type' => 'toilet',
                ],
                [
                    'work_unit_id' => $building->work_unit_id,
                    'school_id' => $building->school_id,
                    'condition' => 'baik',
                    'is_bookable' => false,
                    'is_active' => true,
                    'notes' => 'Data awal dari formulir pemeliharaan kamar mandi. Lengkapi jumlah/tipe bak bila tersedia.',
                ]
            );

            $roomsCreated += $room->wasRecentlyCreated ? 1 : 0;
        }

        $this->command?->info("SarprasBuildingLocationSeeder: {$created} gedung dibuat, {$skipped} sudah ada, {$roomsCreated} kamar mandi dibuat.");
    }

    private function uniqueCode(string $base): string
    {
        $code = $base;
        $counter = 2;

        while (AssetBuilding::where('building_code', $code)->exists()) {
            $code = substr($base, 0, 27).'-'.$counter++;
        }

        return $code;
    }
}
