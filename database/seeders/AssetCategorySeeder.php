<?php

namespace Database\Seeders;

use App\Models\AssetCategory;
use Illuminate\Database\Seeder;

class AssetCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'MUB-001', 'name' => 'Meubelair (Alat Rumah Tangga)', 'asset_type' => 'bergerak', 'depreciation_years' => 5],
            ['code' => 'ELE-001', 'name' => 'Elektronik (Alat Elektronik)', 'asset_type' => 'bergerak', 'depreciation_years' => 3],
            ['code' => 'BGP-001', 'name' => 'Bangunan & Gedung', 'asset_type' => 'tidak_bergerak', 'depreciation_years' => 20],
            ['code' => 'LHN-001', 'name' => 'Lahan & Tanah', 'asset_type' => 'tidak_bergerak', 'depreciation_years' => 0],
            ['code' => 'PKR-001', 'name' => 'Peralatan Kantor', 'asset_type' => 'bergerak', 'depreciation_years' => 5],
            ['code' => 'LAB-001', 'name' => 'Peralatan Laboratorium', 'asset_type' => 'bergerak', 'depreciation_years' => 5],
            ['code' => 'OLG-001', 'name' => 'Peralatan Olahraga', 'asset_type' => 'bergerak', 'depreciation_years' => 5],
            ['code' => 'MUS-001', 'name' => 'Peralatan Musik', 'asset_type' => 'bergerak', 'depreciation_years' => 5],
            ['code' => 'PKS-001', 'name' => 'Peralatan PKS / Prakarya', 'asset_type' => 'bergerak', 'depreciation_years' => 5],
            ['code' => 'HBS-001', 'name' => 'Habis Pakai', 'asset_type' => 'habis_pakai', 'depreciation_years' => 1],
            ['code' => 'BUK-001', 'name' => 'Buku & Perpustakaan', 'asset_type' => 'bergerak', 'depreciation_years' => 5],
            ['code' => 'SRK-001', 'name' => 'Sarana Keagamaan', 'asset_type' => 'bergerak', 'depreciation_years' => 5],
            ['code' => 'UKS-001', 'name' => 'Peralatan UKS', 'asset_type' => 'bergerak', 'depreciation_years' => 5],
            ['code' => 'KOM-001', 'name' => 'Komputer & IT', 'asset_type' => 'bergerak', 'depreciation_years' => 3],
            ['code' => 'ATK-001', 'name' => 'Alat Tulis Kantor (ATK)', 'asset_type' => 'habis_pakai', 'depreciation_years' => 1],
            ['code' => 'LAP-001', 'name' => 'Peralatan Lapangan', 'asset_type' => 'bergerak', 'depreciation_years' => 5],
            ['code' => 'SRP-001', 'name' => 'Seragam & Pakaian', 'asset_type' => 'habis_pakai', 'depreciation_years' => 1],
            ['code' => 'KSN-001', 'name' => 'Kesenian', 'asset_type' => 'bergerak', 'depreciation_years' => 5],
        ];

        foreach ($categories as $cat) {
            AssetCategory::updateOrCreate(
                ['code' => $cat['code']],
                [
                    'name' => $cat['name'],
                    'asset_type' => $cat['asset_type'],
                    'depreciation_years' => $cat['depreciation_years'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('AssetCategorySeeder: '.count($categories).' kategori dibuat.');
    }
}
