<?php

namespace Database\Seeders;

use App\Models\CutiPeriod;
use App\Models\CutiTemplate;
use App\Models\JenisPelatihan;
use App\Models\KinerjaIndikator;
use App\Models\KinerjaKomponen;
use App\Models\KinerjaPeriode;
use App\Models\KontrakTemplate;
use App\Models\PeraturanKategori;
use Illuminate\Database\Seeder;

class PersonaliaMasterSeeder extends Seeder
{
    /**
     * Master data minimum agar modul Personalia dapat dipakai.
     * Idempotent: data yang sudah ada tidak ditimpa.
     */
    public function run(): void
    {
        $cutiTemplates = [
            ['nama' => 'Cuti Tahunan', 'jenis' => 'TAHUNAN', 'jumlah_hari' => 12, 'paid' => true, 'urutan' => 1],
            ['nama' => 'Cuti Sakit', 'jenis' => 'SAKIT', 'jumlah_hari' => 14, 'paid' => true, 'urutan' => 2],
            ['nama' => 'Cuti Besar', 'jenis' => 'BESAR', 'jumlah_hari' => 30, 'paid' => true, 'urutan' => 3],
            ['nama' => 'Izin Tidak Berbayar', 'jenis' => 'LAINNYA', 'jumlah_hari' => 30, 'paid' => false, 'urutan' => 4],
        ];

        foreach ($cutiTemplates as $template) {
            CutiTemplate::firstOrCreate(
                ['nama' => $template['nama']],
                $template + ['is_active' => true]
            );
        }

        $year = (int) now()->year;
        CutiPeriod::firstOrCreate(
            ['name' => (string) $year],
            ['start_date' => $year.'-01-01', 'end_date' => $year.'-12-31', 'is_active' => true]
        );

        $komponen = [
            ['nama' => 'Disiplin', 'bobot_persen' => 30, 'urutan' => 1, 'indikator' => ['Kehadiran tepat waktu', 'Kepatuhan terhadap aturan']],
            ['nama' => 'Kinerja', 'bobot_persen' => 45, 'urutan' => 2, 'indikator' => ['Kualitas hasil kerja', 'Ketepatan penyelesaian tugas']],
            ['nama' => 'Kepribadian', 'bobot_persen' => 25, 'urutan' => 3, 'indikator' => ['Sikap dan etika kerja', 'Kerja sama tim']],
        ];

        foreach ($komponen as $row) {
            $item = KinerjaKomponen::firstOrCreate(
                ['nama' => $row['nama']],
                ['bobot_persen' => $row['bobot_persen'], 'urutan' => $row['urutan'], 'is_active' => true]
            );

            foreach ($row['indikator'] as $index => $nama) {
                KinerjaIndikator::firstOrCreate(
                    ['kinerja_komponen_id' => $item->id, 'nama' => $nama],
                    ['bobot_persen' => round($row['bobot_persen'] / count($row['indikator']), 2), 'urutan' => $index + 1, 'is_active' => true]
                );
            }
        }

        if (! KinerjaPeriode::where('status', 'aktif')->exists()) {
            KinerjaPeriode::firstOrCreate(
                ['nama' => $year.' - Tahunan'],
                ['tanggal_mulai' => $year.'-01-01', 'tanggal_selesai' => $year.'-12-31', 'status' => 'aktif']
            );
        }

        foreach (['Pelatihan Internal', 'Workshop', 'Seminar', 'Sertifikasi'] as $index => $nama) {
            JenisPelatihan::firstOrCreate(
                ['nama' => $nama],
                ['deskripsi' => 'Jenis pelatihan '.$nama, 'is_active' => true, 'urutan' => $index + 1]
            );
        }

        foreach (['SOP', 'SK Yayasan', 'Kebijakan Personalia', 'Peraturan Kerja'] as $index => $nama) {
            PeraturanKategori::firstOrCreate(
                ['nama' => $nama],
                ['deskripsi' => 'Kategori dokumen '.$nama, 'is_active' => true, 'urutan' => $index + 1]
            );
        }

        KontrakTemplate::firstOrCreate(
            ['nama' => 'Template PKWT Standar'],
            [
                'jenis' => 'pkwt',
                'isi_template' => "PERJANJIAN KERJA\n\nNama: {{nama}}\nJabatan: {{jabatan}}\nLokasi: {{lokasi}}\nMasa kerja: {{tanggal_mulai}} s.d. {{tanggal_selesai}}\nGaji pokok: {{gaji}}\n\nDemikian perjanjian ini dibuat.",
                'is_active' => true,
            ]
        );
    }
}
