<?php

namespace Database\Seeders;

use App\Models\Domain;
use Illuminate\Database\Seeder;

class DomainSeeder extends Seeder
{
    /**
     * Seed the 14 official ALIM domains.
     *
     * These domains correspond exactly to the 14 Spatie roles defined in
     * RoleSeeder — they are the workspace-level permission buckets.
     * Position-level roles (Kepala UKS, Guru, etc.) stay inside their
     * parent domain as assignments, not as new domains.
     */
    public function run(): void
    {
        $domains = [
            ['code' => 'super_admin',          'name' => 'Super Admin',          'description' => 'Full system access — Power User'],
            ['code' => 'pimpinan',             'name' => 'Pimpinan',             'description' => 'Pimpinan Pondok (Mudir & Wakil)'],
            ['code' => 'satuan_pendidikan',    'name' => 'Satuan Pendidikan',    'description' => 'Unit Pendidikan (Sekolah)'],
            ['code' => 'asrama',               'name' => 'Asrama',               'description' => 'Unit Asrama Santri'],
            ['code' => 'uks',                  'name' => 'UKS',                  'description' => 'Unit Kesehatan Siswa'],
            ['code' => 'departemen_tahfidz',   'name' => 'Departemen Tahfidz',   'description' => 'Departemen Tahfidz'],
            ['code' => 'departemen_bahasa',    'name' => 'Departemen Bahasa',    'description' => 'Departemen Bahasa Arab'],
            ['code' => 'perpustakaan',         'name' => 'Perpustakaan',         'description' => 'Unit Perpustakaan'],
            ['code' => 'satuan_keamanan',      'name' => 'Satuan Keamanan',      'description' => 'Unit Keamanan & Ketertiban'],
            ['code' => 'humas_personalia',     'name' => 'Humas Personalia',     'description' => 'Unit Humas & Personalia'],
            ['code' => 'unit_rumah_tangga',    'name' => 'Unit Rumah Tangga',    'description' => 'Unit Sarana Prasarana & Tata Usaha'],
            ['code' => 'keuangan',             'name' => 'Keuangan',             'description' => 'Unit Keuangan'],
            ['code' => 'teknologi_informasi',  'name' => 'Teknologi Informasi',  'description' => 'Unit Teknologi Informasi'],
            ['code' => 'unit_pelayanan_gizi',  'name' => 'Unit Pelayanan Gizi',  'description' => 'Unit Gizi & Logistik'],
        ];

        foreach ($domains as $domainData) {
            Domain::updateOrCreate(
                ['code' => $domainData['code']],
                [
                    'name' => $domainData['name'],
                    'description' => $domainData['description'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('✅ DomainSeeder selesai. Total '.Domain::count().' domain.');
    }
}
