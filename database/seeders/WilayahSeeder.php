<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WilayahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $this->seedProvinces();
        $this->seedRegencies();
        $this->seedDistricts();
        $this->seedVillages();
    }

    private function seedProvinces()
    {
        $this->command->info('Seeding provinces...');

        $provinces = [
            ['id' => '11', 'name' => 'ACEH'],
            ['id' => '12', 'name' => 'SUMATERA UTARA'],
            ['id' => '13', 'name' => 'SUMATERA BARAT'],
            ['id' => '14', 'name' => 'RIAU'],
            ['id' => '15', 'name' => 'JAMBI'],
            ['id' => '16', 'name' => 'SUMATERA SELATAN'],
            ['id' => '17', 'name' => 'BENGKULU'],
            ['id' => '18', 'name' => 'LAMPUNG'],
            ['id' => '19', 'name' => 'KEPULAUAN BANGKA BELITUNG'],
            ['id' => '21', 'name' => 'KEPULAUAN RIAU'],
            ['id' => '31', 'name' => 'DKI JAKARTA'],
            ['id' => '32', 'name' => 'JAWA BARAT'],
            ['id' => '33', 'name' => 'JAWA TENGAH'],
            ['id' => '34', 'name' => 'DI YOGYAKARTA'],
            ['id' => '35', 'name' => 'JAWA TIMUR'],
            ['id' => '36', 'name' => 'BANTEN'],
            ['id' => '51', 'name' => 'BALI'],
            ['id' => '52', 'name' => 'NUSA TENGGARA BARAT'],
            ['id' => '53', 'name' => 'NUSA TENGGARA TIMUR'],
            ['id' => '61', 'name' => 'KALIMANTAN BARAT'],
            ['id' => '62', 'name' => 'KALIMANTAN TENGAH'],
            ['id' => '63', 'name' => 'KALIMANTAN SELATAN'],
            ['id' => '64', 'name' => 'KALIMANTAN TIMUR'],
            ['id' => '65', 'name' => 'KALIMANTAN UTARA'],
            ['id' => '71', 'name' => 'SULAWESI UTARA'],
            ['id' => '72', 'name' => 'SULAWESI TENGAH'],
            ['id' => '73', 'name' => 'SULAWESI SELATAN'],
            ['id' => '74', 'name' => 'SULAWESI TENGGARA'],
            ['id' => '75', 'name' => 'GORONTALO'],
            ['id' => '76', 'name' => 'SULAWESI BARAT'],
            ['id' => '81', 'name' => 'MALUKU'],
            ['id' => '82', 'name' => 'MALUKU UTARA'],
            ['id' => '91', 'name' => 'PAPUA BARAT'],
            ['id' => '92', 'name' => 'PAPUA'],
            ['id' => '93', 'name' => 'PAPUA SELATAN'],
            ['id' => '94', 'name' => 'PAPUA TENGAH'],
            ['id' => '95', 'name' => 'PAPUA PEGUNUNGAN'],
            ['id' => '96', 'name' => 'PAPUA BARAT DAYA'],
        ];

        foreach ($provinces as $province) {
            DB::table('indonesia_provinces')->updateOrInsert(
                ['code' => $province['id']],
                ['id' => $province['id'], 'name' => $province['name'], 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function seedRegencies()
    {
        $this->command->info('Seeding regencies...');

        // Contoh data untuk beberapa provinsi (NTB = 52)
        $ntbRegencies = [
            ['id' => '5201', 'province_id' => '52', 'name' => 'KABUPATEN LOMBOK BARAT'],
            ['id' => '5202', 'province_id' => '52', 'name' => 'KABUPATEN LOMBOK TENGAH'],
            ['id' => '5203', 'province_id' => '52', 'name' => 'KABUPATEN LOMBOK TIMUR'],
            ['id' => '5204', 'province_id' => '52', 'name' => 'KABUPATEN SUMBAWA'],
            ['id' => '5205', 'province_id' => '52', 'name' => 'KABUPATEN DOMPU'],
            ['id' => '5206', 'province_id' => '52', 'name' => 'KABUPATEN BIMA'],
            ['id' => '5207', 'province_id' => '52', 'name' => 'KABUPATEN SUMBAWA BARAT'],
            ['id' => '5208', 'province_id' => '52', 'name' => 'KABUPATEN LOMBOK UTARA'],
            ['id' => '5271', 'province_id' => '52', 'name' => 'KOTA MATARAM'],
            ['id' => '5272', 'province_id' => '52', 'name' => 'KOTA BIMA'],
        ];

        foreach ($ntbRegencies as $regency) {
            DB::table('indonesia_cities')->updateOrInsert(
                ['code' => $regency['id']],
                [
                    'id' => $regency['id'],
                    'province_code' => $regency['province_id'],
                    'name' => $regency['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Anda bisa menambahkan data untuk provinsi lainnya sesuai kebutuhan
    }

    private function seedDistricts()
    {
        $this->command->info('Seeding districts...');

        // Contoh data untuk beberapa kabupaten di NTB
        $districts = [
            ['id' => '520101', 'regency_id' => '5201', 'name' => 'GERUNG'],
            ['id' => '520102', 'regency_id' => '5201', 'name' => 'KEDIRI'],
            ['id' => '520103', 'regency_id' => '5201', 'name' => 'NARMADA'],
            ['id' => '520107', 'regency_id' => '5201', 'name' => 'SEKOTONG'],
            ['id' => '520108', 'regency_id' => '5201', 'name' => 'LABUAPI'],
            ['id' => '520109', 'regency_id' => '5201', 'name' => 'GUNUNGSARI'],
            ['id' => '520112', 'regency_id' => '5201', 'name' => 'LINGSAR'],
            ['id' => '520113', 'regency_id' => '5201', 'name' => 'LEMBAR'],
            ['id' => '520114', 'regency_id' => '5201', 'name' => 'BATU LAYAR'],
            ['id' => '520115', 'regency_id' => '5201', 'name' => 'KURIPAN'],
            // Kota Mataram — untuk SchoolSeeder
            ['id' => '527102', 'regency_id' => '5271', 'name' => 'MATARAM'],
        ];

        foreach ($districts as $district) {
            DB::table('indonesia_districts')->updateOrInsert(
                ['code' => $district['id']],
                [
                    'id' => $district['id'],
                    'city_code' => $district['regency_id'],
                    'name' => $district['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedVillages()
    {
        $this->command->info('Seeding villages...');

        // Contoh data untuk beberapa kecamatan
        $gerungVillages = [
            ['id' => '5201012001', 'district_id' => '520101', 'name' => 'GERUNG'],
            ['id' => '5201012002', 'district_id' => '520101', 'name' => 'GELOGOR'],
            ['id' => '5201012003', 'district_id' => '520101', 'name' => 'DASAN GERES'],
            ['id' => '5201012004', 'district_id' => '520101', 'name' => 'KEBON AYER'],
            ['id' => '5201012005', 'district_id' => '520101', 'name' => 'BABUSSALAM'],
            ['id' => '5201012006', 'district_id' => '520101', 'name' => 'GUNUNG SARI'],
            ['id' => '5201012007', 'district_id' => '520101', 'name' => 'TAMAN AYUN'],
            ['id' => '5201012008', 'district_id' => '520101', 'name' => 'BANYU URIP'],
            ['id' => '5201012009', 'district_id' => '520101', 'name' => 'NYUR LEMBANG'],
            ['id' => '5201012010', 'district_id' => '520101', 'name' => 'BELEKE'],
            ['id' => '5201012011', 'district_id' => '520101', 'name' => 'JAGARAGA'],
            ['id' => '5201012012', 'district_id' => '520101', 'name' => 'DARMASARI'],
            ['id' => '5201012013', 'district_id' => '520101', 'name' => 'GIRI MULYO'],
            ['id' => '5201012014', 'district_id' => '520101', 'name' => 'MENDIRAN'],
        ];

        $mataramVillages = [
            ['id' => '5271021012', 'district_id' => '527102', 'name' => 'PUNIA'],
        ];
        foreach ($mataramVillages as $village) {
            DB::table('indonesia_villages')->updateOrInsert(
                ['code' => $village['id']],
                [
                    'id' => $village['id'],
                    'district_code' => $village['district_id'],
                    'name' => $village['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        foreach ($gerungVillages as $village) {
            DB::table('indonesia_villages')->updateOrInsert(
                ['code' => $village['id']],
                [
                    'id' => $village['id'],
                    'district_code' => $village['district_id'],
                    'name' => $village['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
