<?php

namespace Database\Seeders;

use App\Models\AssetBuilding;
use App\Models\AssetRoom;
use App\Models\Dormitory;
use App\Models\School;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Database\Seeder;

/**
 * Seed master gedung dan kamar mandi yang nama/areanya jelas dari formulir pemeliharaan.
 *
 * Pemilik dianalisis dari kelompok gedung:
 * - asrama putra/putri dan MA/SMA;
 * - gedung umum (kantor, masjid, UKS, dapur, tower, perumahan) → Unit Rumah Tangga.
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

        $owners = $this->buildOwnerMap($urt);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($buildings as [$area, $name, $type, $code]) {
            [$ownerUnitId, $ownerSchoolId, $ownerLabel] = $owners[$name] ?? [$urt->id, null, 'Unit Rumah Tangga'];

            $existing = AssetBuilding::query()->where('building_name', $name)->first();

            if ($existing) {
                if ($existing->work_unit_id === $urt->id && str_starts_with((string) $existing->notes, 'Data awal dari formulir')) {
                    $existing->update([
                        'work_unit_id' => $ownerUnitId,
                        'school_id' => $ownerSchoolId,
                        'notes' => 'Data awal dari formulir pemeliharaan — Area '.$area.'; pemilik: '.$ownerLabel.'. Lengkapi luas, tahun, dan IMB bila tersedia.',
                    ]);
                    $updated++;
                } else {
                    $skipped++;
                }
                continue;
            }

            AssetBuilding::create([
                'work_unit_id' => $ownerUnitId,
                'school_id' => $ownerSchoolId,
                'building_code' => $this->uniqueCode($code),
                'building_name' => $name,
                'building_type' => $type,
                'structure_condition' => 'baik',
                'ownership_status' => 'milik_sendiri',
                'is_active' => true,
                'created_by' => $createdBy,
                'notes' => 'Data awal dari formulir pemeliharaan — Area '.$area.'; pemilik: '.$ownerLabel.'. Lengkapi luas, tahun, dan IMB bila tersedia.',
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
            $building = AssetBuilding::query()->where('building_name', $buildingName)->first();

            if (! $building) {
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

        $this->command?->info("SarprasBuildingLocationSeeder: {$created} gedung dibuat, {$updated} diperbarui, {$skipped} dilewati, {$roomsCreated} kamar mandi dibuat.");
    }

    /**
     * @return array<string, array{0:string,1:?string,2:string}>
     */
    private function buildOwnerMap(WorkUnit $urt): array
    {
        $map = [];

        $assign = function (string $unitName, array $buildingNames, ?string $fallbackLabel = null) use (&$map, $urt) {
            $unit = WorkUnit::query()->where('name', $unitName)->first() ?? $urt;
            $schoolId = Dormitory::query()->where('work_unit_id', $unit->id)->value('school_id')
                ?? School::query()->where('work_unit_id', $unit->id)->value('id');
            $label = $fallbackLabel ?? $unit->name;

            foreach ($buildingNames as $name) {
                $map[$name] = [$unit->id, $schoolId, $label];
            }
        };

        $assign('Asrama SMP IT Putra Abu Hurairah Mataram', [
            'Abu Bakar As Siddiq', 'Umar bin Khattab', 'Ustman bin Affan',
            'Ali bin Abi Thalib', 'Thalhah bin Ubaidillah', 'Zubair bin Awwam',
        ]);
        $assign('Asrama MA Plus Abu Hurairah Mataram', ['Laila Hizam', 'Abdurrahman bin Auf']);
        $assign('Asrama SMP IT Putri Abu Hurairah Mataram', [
            'Hafshah', 'Zainab', 'Sarah', 'Hawa', 'Hajar', 'Aisyah',
        ]);
        $assign('Asrama SMA IT Putri Abu Hurairah Mataram', [
            'Shafiyah', 'Maimunah', 'Saudah', 'Ummu Kultsum', 'Ruqayyah',
        ]);
        $assign('SMP & SMA IT Putra Abu Hurairah Mataram', ['Hamzah', 'Usamah', 'Muawiyah']);
        $assign('Departemen Tahfidz', ['Kantor Departemen Tahfidz']);

        return $map;
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
