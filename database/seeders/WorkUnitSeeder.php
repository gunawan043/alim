<?php

namespace Database\Seeders;

use App\Models\Divisi;
use App\Models\WorkUnit;
use Illuminate\Database\Seeder;

class WorkUnitSeeder extends Seeder
{
    public function run(): void
    {
        // ── Ambil ID semua divisi yang baru saja di-seed ────────────────
        $divisi = [];
        foreach (['SP', 'PNG', 'TAH', 'BHS', 'PERPUS', 'LAB', 'UKS', 'URT', 'SATPAM', 'UPGIZI', 'TIJ', 'KEU', 'HUMAS'] as $kode) {
            $d = Divisi::where('kode', $kode)->first();
            $divisi[$kode] = $d ? $d->id : null;
        }

        // ── 1. Unit Akademik (bukan sub-divisi, bukan anak divisi) ─────
        // 8 sekolah di bawah divisi Satuan Pendidikan
        $akademik = [
            ['code' => 'UAK-001', 'name' => 'SD IT Putra Abu Hurairah Mataram',    'divisi' => 'SP'],
            ['code' => 'UAK-002', 'name' => 'SD IT Putri Abu Hurairah Mataram',    'divisi' => 'SP'],
            ['code' => 'UAK-003', 'name' => 'SMP IT Putra Abu Hurairah Mataram',   'divisi' => 'SP'],
            ['code' => 'UAK-004', 'name' => 'SMP IT Putri Abu Hurairah Mataram',   'divisi' => 'SP'],
            ['code' => 'UAK-005', 'name' => 'SMP & SMA IT Putra Abu Hurairah Mataram', 'divisi' => 'SP'],
            ['code' => 'UAK-006', 'name' => 'SMA IT Putri Abu Hurairah Mataram',   'divisi' => 'SP'],
            ['code' => 'UAK-007', 'name' => 'MA Plus Abu Hurairah Mataram',        'divisi' => 'SP'],
            ['code' => 'UAK-008', 'name' => 'PPS Diniyah Abu Hurairah Mataram',    'divisi' => 'SP'],
        ];

        // ── 2. Pengasuhan (5 asrama di bawah divisi Pengasuhan) ──────────
        $pengasuhan = [
            ['code' => 'PNG-001', 'name' => 'Asrama SMP IT Putra Abu Hurairah Mataram', 'divisi' => 'PNG'],
            ['code' => 'PNG-002', 'name' => 'Asrama SMP IT Putri Abu Hurairah Mataram', 'divisi' => 'PNG'],
            ['code' => 'PNG-003', 'name' => 'Asrama SMA IT Putri Abu Hurairah Mataram', 'divisi' => 'PNG'],
            ['code' => 'PNG-004', 'name' => 'Asrama MA Plus Abu Hurairah Mataram',      'divisi' => 'PNG'],
            ['code' => 'PNG-005', 'name' => 'Asrama PPS Diniyah Abu Hurairah Mataram',  'divisi' => 'PNG'],
        ];

        // ── 3. Divisi lain → nama satuan kerja = nama divisi ────────────
        $divisiOnly = [
            ['code' => 'DEP-TAH', 'name' => 'Departemen Tahfidz',             'divisi' => 'TAH'],
            ['code' => 'DEP-BHS', 'name' => 'Departemen Bahasa',              'divisi' => 'BHS'],
            ['code' => 'DEP-PERPUS', 'name' => 'Perpustakaan',                'divisi' => 'PERPUS'],
            ['code' => 'DEP-LAB', 'name' => 'Laboratorium',                   'divisi' => 'LAB'],
            ['code' => 'DEP-UKS', 'name' => 'UKS',                            'divisi' => 'UKS'],
            ['code' => 'DEP-URT', 'name' => 'Unit Rumah Tangga',              'divisi' => 'URT'],
            ['code' => 'DEP-SATPAM', 'name' => 'Satuan Keamanan',            'divisi' => 'SATPAM'],
            ['code' => 'DEP-UPGIZI', 'name' => 'Unit Pelayanan Gizi',         'divisi' => 'UPGIZI'],
            ['code' => 'DEP-TIJ', 'name' => 'Teknologi Informasi & Jaringan', 'divisi' => 'TIJ'],
            ['code' => 'DEP-KEU', 'name' => 'Keuangan',                       'divisi' => 'KEU'],
            ['code' => 'DEP-HUMAS', 'name' => 'Humas & Personalia',           'divisi' => 'HUMAS'],
        ];

        // ── Insert semua satuan kerja ────────────────────────────────────
        $wus = [];
        foreach (array_merge($akademik, $pengasuhan, $divisiOnly) as $item) {
            $wu = WorkUnit::firstOrCreate(
                ['code' => $item['code']],
                [
                    'name' => $item['name'],
                    'type' => $this->getType($item['divisi']),
                    'divisi_id' => $divisi[$item['divisi']],
                    'is_active' => true,
                ]
            );
            $wus[$item['code']] = $wu->id;
            $divNama = $wu->divisi ? $wu->divisi->nama : $item['divisi'];
            $this->command->info("  ✅ {$item['name']} [{$divNama}]");
        }

        $this->command->info('✅ WorkUnit seeder done. Total work units: '.WorkUnit::count());
    }

    /**
     * Tentukan type berdasarkan divisi kode.
     */
    private function getType(string $divisiKode): string
    {
        return match ($divisiKode) {
            'SP' => 'Unit Akademik',
            'PNG' => 'Unit Penunjang Akademik',
            default => 'Unit Pelayanan',
        };
    }
}
