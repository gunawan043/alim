<?php

namespace Database\Seeders;

use App\Models\AssetBuilding;
use App\Models\AssetRoom;
use App\Models\CleaningArea;
use App\Models\CleaningProgram;
use App\Models\CleaningTask;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Database\Seeder;

class SarprasCleaningSeeder extends Seeder
{
    public function run(): void
    {
        $urt = WorkUnit::query()
            ->where('code', 'DEP-URT')
            ->orWhere('name', 'like', '%Unit Rumah Tangga%')
            ->first();

        if (! $urt) {
            $this->command?->warn('SarprasCleaningSeeder: Unit Rumah Tangga tidak ditemukan.');
            return;
        }

        $createdBy = User::query()->where('is_system_admin', true)->value('id') ?? User::query()->value('id');

        $toiletBuildings = [
            'Abu Bakar As Siddiq', 'Umar bin Khattab', 'Ustman bin Affan', 'Ali bin Abi Thalib',
            'Thalhah bin Ubaidillah', 'Zubair bin Awwam', 'Kantor Utama', 'Tower Zam-Zam',
            'Laila Hizam', 'Abdurrahman bin Auf', 'Masjid Jami\'', 'Hafshah', 'Zainab', 'Sarah',
            'Aisyah', 'Shafiyah', 'Hamzah',
        ];

        $areas = [];

        foreach ($toiletBuildings as $buildingName) {
            $building = AssetBuilding::query()->where('building_name', $buildingName)->first();
            $room = AssetRoom::query()
                ->where('building_id', $building?->id)
                ->where('room_type', 'toilet')
                ->first();

            $areas[] = [
                'name' => 'Kamar Mandi '.$buildingName,
                'area_type' => 'kamar_mandi',
                'building_id' => $building?->id,
                'room_id' => $room?->id,
                'school_id' => $building?->school_id,
            ];
        }

        foreach ([
            ['SD IT Putra', 'ruang_kelas', 'SD IT Putra Abu Hurairah Mataram'],
            ['SD IT Putri', 'ruang_kelas', 'SD IT Putri Abu Hurairah Mataram'],
            ['SMP Putra', 'ruang_kelas', 'SMP IT Putra Abu Hurairah Mataram'],
            ['SMP Putri', 'ruang_kelas', 'SMP IT Putri Abu Hurairah Mataram'],
            ['SMA Putri', 'ruang_kelas', 'SMA IT Putri Abu Hurairah Mataram'],
            ['MA Plus', 'ruang_kelas', 'MA Plus Abu Hurairah Mataram'],
            ['Asrama SMP Putra', 'asrama', 'Asrama SMP IT Putra Abu Hurairah Mataram'],
            ['Asrama MA Plus', 'asrama', 'Asrama MA Plus Abu Hurairah Mataram'],
            ['Asrama SMP Putri', 'asrama', 'Asrama SMP IT Putri Abu Hurairah Mataram'],
            ['Asrama SMA Putri', 'asrama', 'Asrama SMA IT Putri Abu Hurairah Mataram'],
        ] as [$name, $type, $unitName]) {
            $unit = WorkUnit::query()->where('name', $unitName)->first() ?? $urt;
            $areas[] = ['name' => $name, 'area_type' => $type, 'work_unit_id' => $unit->id];
        }

        foreach ([
            ['Kantor Utama', 'kantor', 'Kantor Utama'],
            ['UKS Putra', 'uks', 'UKS Putra'],
            ['UKS Putri', 'uks', 'UKS Putri'],
            ['Masjid Jami\'', 'masjid', 'Masjid Jami\''],
            ['Masjid Khadijah', 'masjid', 'Masjid Khadijah'],
            ['Taman & Halaman', 'taman', null],
            ['Gorong-gorong & Saluran', 'gorong_gorong', null],
        ] as [$name, $type, $buildingName]) {
            $building = $buildingName ? AssetBuilding::query()->where('building_name', $buildingName)->first() : null;
            $areas[] = ['name' => $name, 'area_type' => $type, 'building_id' => $building?->id, 'school_id' => $building?->school_id];
        }

        $areaModels = [];

        foreach ($areas as $areaData) {
            $area = CleaningArea::firstOrCreate(
                ['name' => $areaData['name'], 'work_unit_id' => $areaData['work_unit_id'] ?? $urt->id],
                [
                    'area_type' => $areaData['area_type'],
                    'building_id' => $areaData['building_id'] ?? null,
                    'room_id' => $areaData['room_id'] ?? null,
                    'school_id' => $areaData['school_id'] ?? null,
                    'is_active' => true,
                    'created_by' => $createdBy,
                    'location_notes' => 'Data awal dari formulir pemeliharaan kebersihan.',
                ]
            );

            $areaModels[$areaData['name']] = $area;
        }

        $toiletTasks = [
            ['Lantai', 'Bersih dan tidak licin', 'Visual', 'harian'],
            ['Dinding', 'Bersih dan tidak berkerak', 'Visual', 'harian'],
            ['Kloset', 'Bersih dan tidak bau', 'Visual', 'harian'],
            ['Ember air', 'Bersih dan tidak berkerak', 'Visual', 'harian'],
            ['Gayung', 'Bersih dan tidak berkerak', 'Visual', 'harian'],
            ['Kran air', 'Berfungsi baik', 'Visual', 'harian'],
            ['Kondisi air', 'Bersih dan tidak bau', 'Visual', 'harian'],
            ['Gantungan pakaian', 'Ada dan berfungsi baik', 'Visual', 'harian'],
            ['Pintu dan ventilasi', 'Bersih dari sampah dan kotoran', 'Visual', 'harian'],
            ['Lampu', 'Ada dan berfungsi baik', 'Visual', 'harian'],
            ['Saringan / avur', 'Ada dan berfungsi baik', 'Visual', 'harian'],
            ['Plafon', 'Tidak ada sarang laba-laba', 'Visual', 'mingguan'],
        ];

        $generalTasks = [
            ['Sapu lantai', 'Tidak ada debu dan sampah', 'Visual', 'harian'],
            ['Pel lantai', 'Bersih dan tidak licin', 'Visual', 'harian'],
            ['Kosongkan tempat sampah', 'Tidak ada sampah menumpuk', 'Visual', 'harian'],
            ['Bersihkan meja dan kursi', 'Bebas debu', 'Visual', 'harian'],
            ['Bersihkan papan tulis', 'Bersih dan siap pakai', 'Visual', 'harian'],
            ['Bersihkan jendela', 'Bening dan bebas debu', 'Visual', 'mingguan'],
            ['Bersihkan ventilation/AC filter', 'Tidak berdebu', 'Visual', 'bulanan'],
            ['Rapikan perabot', 'Tertata rapi', 'Visual', 'harian'],
        ];

        $gardenTasks = [
            ['Penyiraman tanaman', 'Tanah cukup lembab', 'Visual', 'harian'],
            ['Kebersihan taman', 'Tidak ada sampah/rumput liar', 'Visual', 'mingguan'],
            ['Pemangkasan tanaman', 'Taman tertata rapi', 'Visual', 'bulanan'],
            ['Pemupukan', 'Tanaman tumbuh sehat', 'Visual', 'bulanan'],
            ['Pengecekan hama', 'Bebas hama/penyakit', 'Visual', 'mingguan'],
        ];

        $drainTasks = [
            ['Cek aliran air', 'Air mengalir lancar', 'Visual', 'mingguan'],
            ['Angkat sampah/endapan', 'Saluran bersih', 'Manual', 'mingguan'],
            ['Bilas saluran', 'Tidak bau dan tidak tersumbat', 'Manual', 'bulanan'],
            ['Cek kerusakan tutup saluran', 'Tutup aman', 'Visual', 'bulanan'],
        ];

        foreach ($areaModels as $name => $area) {
            $tasks = match ($area->area_type) {
                'kamar_mandi' => $toiletTasks,
                'taman' => $gardenTasks,
                'gorong_gorong' => $drainTasks,
                default => $generalTasks,
            };

            foreach ($tasks as $index => [$title, $standard, $method, $frequency]) {
                CleaningTask::firstOrCreate(
                    ['area_id' => $area->id, 'title' => $title],
                    [
                        'standard' => $standard,
                        'method' => $method,
                        'frequency' => $frequency,
                        'sort_order' => $index + 1,
                        'is_active' => true,
                        'created_by' => $createdBy,
                    ]
                );
            }
        }

        $programs = [
            ['Pemeliharaan Kipas Pondok Putra - SD IT Putra', 'kipas', 'bulanan', [4, 6, 9, 12]],
            ['Pemeliharaan Kipas Pondok Putra - SD IT Putri', 'kipas', 'bulanan', [4, 6, 9, 12]],
            ['Pemeliharaan Kipas Pondok Putra - SMP Putra', 'kipas', 'bulanan', [6, 12]],
            ['Pemeliharaan Kipas Pondok Putra - MA Plus', 'kipas', 'bulanan', [6, 12]],
            ['Pemeliharaan Kipas Pondok Putra - Asrama SMP Putra', 'kipas', 'bulanan', [4, 6, 9, 12]],
            ['Pemeliharaan Kipas Pondok Putra - Asrama MA Plus', 'kipas', 'bulanan', [4, 6, 9, 12]],
            ['Pemeliharaan Kipas Pondok Putri - SMP Putri', 'kipas', 'bulanan', [1, 4, 6, 12]],
            ['Pemeliharaan Kipas Pondok Putri - SMA Putri', 'kipas', 'bulanan', [1, 4, 6, 12]],
            ['Pemeliharaan Kipas Pondok Putri - Asrama SMP Putri', 'kipas', 'bulanan', [1, 4, 6, 12]],
            ['Pemeliharaan Kipas Pondok Putri - Asrama SMA Putri', 'kipas', 'bulanan', [1, 4, 6, 12]],
            ['Pemeliharaan Gorong-gorong Area SMP Putra', 'gorong_gorong', 'bulanan', [1, 6, 10, 12]],
            ['Pemeliharaan Gorong-gorong Area MA Plus', 'gorong_gorong', 'bulanan', [1, 6, 10, 12]],
            ['Pemeliharaan Gorong-gorong Area Kantor Utama', 'gorong_gorong', 'bulanan', [1, 6, 10, 12]],
            ['Pemeliharaan Gorong-gorong Area SMP Putri', 'gorong_gorong', 'bulanan', [1, 6, 12]],
            ['Pemeliharaan Gorong-gorong Area SMA Putri', 'gorong_gorong', 'bulanan', [1, 6, 12]],
            ['Pemeliharaan Batu Sikat Area SD', 'batu_sikat', 'bulanan', [2, 4, 6, 10, 12]],
            ['Pemeliharaan Batu Sikat Area SMA Putri', 'batu_sikat', 'bulanan', [2, 4, 6, 10, 12]],
            ['Pemeliharaan Batu Sikat Area SMP Putri', 'batu_sikat', 'bulanan', [2, 4, 6, 10, 12]],
            ['Pemeliharaan Kanopi / Konsul Area Asrama Putra', 'kanopi', 'bulanan', [1, 3, 5, 9, 11]],
            ['Pemeliharaan Kanopi / Konsul Area Masjid Ibnu Taimiyah', 'kanopi', 'bulanan', [1, 3, 5, 9, 11]],
            ['Pemeliharaan Kanopi / Konsul Area Masjid Aisyah Lawata', 'kanopi', 'bulanan', [1, 3, 5, 9, 11]],
            ['Pemeliharaan Plang Nama Pondok Jalan Utama', 'plang_nama', 'bulanan', [1, 3, 5, 9, 11]],
            ['Pemeliharaan Taman dan Kolam', 'taman', 'bulanan', [1,2,3,4,5,6,7,8,9,10,11,12]],
            ['Pemangkasan Pohon dan Perawatan Taman', 'pemangkasan', 'bulanan', [1,2,3,4,5,6,7,8,9,10,11,12]],
        ];

        foreach ($programs as [$title, $category, $frequency, $months]) {
            CleaningProgram::firstOrCreate(
                ['title' => $title],
                [
                    'category' => $category,
                    'frequency' => $frequency,
                    'months' => $months,
                    'is_active' => true,
                    'created_by' => $createdBy,
                    'notes' => 'Data awal dari formulir jadwal kebersihan.',
                ]
            );
        }

        $this->command?->info('SarprasCleaningSeeder: '.count($areaModels).' area, '.CleaningTask::count().' uraian, '.count($programs).' program.');
    }
}
