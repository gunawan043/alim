<?php

namespace Database\Seeders;

use App\Models\AssetCategory;
use Illuminate\Database\Seeder;

class SarprasInventoryCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'INV-A', 'name' => 'Elektronik & IT', 'inventory_group' => 'A. Elektronik & IT', 'description' => 'Laptop, komputer, monitor, printer, proyektor, TV, CCTV, router, fingerprint, kabel.'],
            ['code' => 'INV-B', 'name' => 'Audio & Multimedia', 'inventory_group' => 'B. Audio & Multimedia', 'description' => 'Speaker, amplifier, mixer, microphone, stand mic, power supply.'],
            ['code' => 'INV-C', 'name' => 'Furniture & Interior / Peralatan Kelas', 'inventory_group' => 'C. Furniture & Interior / Peralatan Kelas', 'description' => 'Meja, kursi, lemari, loker, rak, ranjang, kasur, karpet, papan tulis, jam dinding.'],
            ['code' => 'INV-D', 'name' => 'Kebersihan & Umum', 'inventory_group' => 'D. Kebersihan & Umum', 'description' => 'Sapu, pengki, pel, kemoceng, tong sampah, ember, alat kebersihan.'],
            ['code' => 'INV-E', 'name' => 'Pendingin & Ventilasi', 'inventory_group' => 'E. Pendingin & Ventilasi', 'description' => 'AC, kipas angin, exhaust, blower.'],
            ['code' => 'INV-F', 'name' => 'Dapur & Konsumsi', 'inventory_group' => 'F. Dapur & Konsumsi', 'description' => 'Kompor, tabung gas, kulkas, dispenser, blender, microwave, peralatan masak dan saji.'],
            ['code' => 'INV-G', 'name' => 'Alat Teknik & Kebun', 'inventory_group' => 'G. Alat Teknik & Kebun', 'description' => 'Bor, gergaji, mesin potong rumput, jet pump, tangga, tang, obeng, cangkul.'],
            ['code' => 'INV-H', 'name' => 'Peralatan Medis & Kendaraan', 'inventory_group' => 'H. Peralatan Medis & Kendaraan', 'description' => 'Tensimeter, termometer, oxymeter, timbangan, tabung oksigen, kursi roda, ambulans, mobil, motor.'],
            ['code' => 'INV-I', 'name' => 'Laboratorium', 'inventory_group' => 'I. Laboratorium', 'description' => 'Mikroskop, neraca analitik, jangka sorong, alat gelas, kit fisika, bahan kimia.'],
        ];

        foreach ($categories as $category) {
            AssetCategory::updateOrCreate(
                ['code' => $category['code']],
                [
                    'name' => $category['name'],
                    'inventory_group' => $category['inventory_group'],
                    'description' => $category['description'],
                    'asset_type' => 'bergerak',
                    'depreciation_years' => 5,
                    'is_active' => true,
                ]
            );
        }

        AssetCategory::where('code', 'INV-ASR')->update([
            'inventory_group' => 'C. Furniture & Interior / Peralatan Kelas',
            'depreciation_years' => 5,
        ]);
    }
}
