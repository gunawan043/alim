<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DokumenIsoSeeder extends Seeder
{
    public function run(): void
    {
        $divisiMap = $this->buildDivisiMap();
        $documents = $this->getDocuments();

        $inserted = 0;
        foreach ($documents as $idx => $doc) {
            $divisiId = $this->findDivisiId($doc['kode_dokumen'], $divisiMap);

            DB::table('dokumen_iso')->insert([
                'id' => Str::uuid()->toString(),
                'nama_dokumen' => $doc['nama_dokumen'],
                'prosedur_konsultan' => null,
                'pasal' => null,
                'kode_dokumen' => $doc['kode_dokumen'],
                'tanggal_berlaku' => $doc['tanggal_berlaku'],
                'revisi_ke' => $doc['revisi_ke'],
                'keterangan' => null,
                'kategori' => $doc['kategori'],
                'link_dokumen' => null,
                'divisi_id' => $divisiId,
                'is_active' => 1,
                'sort_order' => $idx,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $inserted++;
        }

        $total = DB::table('dokumen_iso')->count();
        $this->command->info("Dokumen ISO seeder done. Total: $total (inserted: $inserted)");
    }

    private function buildDivisiMap(): array
    {
        $divisis = DB::table('divisis')->get(['id', 'kode', 'nama']);
        $map = [];
        foreach ($divisis as $d) {
            $map[$d->kode] = $d;
        }

        return $map;
    }

    private function findDivisiId(string $kode, array $divisiMap): ?string
    {
        $patterns = [
            'PAH-MANUAL MUTU' => 'PAH-MR',
            'PAH-MR-PROS' => 'PAH-MR',
            'PAH-MR-FORM' => 'PAH-MR',
            'PAH-MR-' => 'PAH-MR',
            'PAH-MDR-' => 'PAH-MDR',
            'PAH-WADIR AK-' => 'PAH-WADIR AK',
            'PAH-WADIR PU-' => 'PAH-WADIR PU',
            'PAH-KSP-' => 'PAH-KSP',
            'PAH-KP-' => 'PAH-KP',
            'PAH-DEPT-TAH-' => 'PAH-TAH',
            'PAH-DEPT-BHS-' => 'PAH-BHS',
            'PAH-PERPUS-' => 'PAH-PERPUS',
            'PAH-LAB-' => 'PAH-LAB',
            'PAH-KUPT-' => 'PAH-KUPT',
            'PAH-SATPAM-' => 'PAH-SATPAM',
            'PAH-UGL-' => 'PAH-UGL',
            'PAH-KOOR-KE-' => 'PAH-KOOR-KE',
            'PAH-TIJ-' => 'PAH-TIJ',
            'PAH-KEU-' => 'PAH-KEU',
            'PAH-HUMAS-' => 'PAH-HUMAS',
            'PAH-HKS-' => 'PAH-HUMAS',
        ];

        foreach ($patterns as $prefix => $divisiKode) {
            if (str_starts_with($kode, $prefix)) {
                return $divisiMap[$divisiKode]->id ?? null;
            }
        }

        return null;
    }

    private function parseDate(string $date): string
    {
        $bulan = [
            'January' => '01', 'January' => '01',
            'February' => '02', 'Februari' => '02',
            'March' => '03', 'Maret' => '03',
            'April' => '04',
            'May' => '05', 'Mei' => '05',
            'June' => '06', 'Juni' => '06',
            'July' => '07', 'Juli' => '07',
            'August' => '08', 'Agustus' => '08',
            'September' => '09', 'September' => '09',
            'October' => '10', 'Oktober' => '10',
            'November' => '11', 'November' => '11',
            'December' => '12', 'Desember' => '12',
        ];

        $parts = preg_split('/[\s,\.]+/', trim($date));
        if (count($parts) < 2) {
            return date('Y-m-d');
        }

        $day = $parts[0];
        $monthName = $parts[1];
        $year = $parts[2] ?? date('Y');

        $month = $bulan[$monthName] ?? '01';

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    private function doc(string $nama, string $kode, string $tanggal, string $revisi, string $kategori): array
    {
        return [
            'nama_dokumen' => $nama,
            'kode_dokumen' => $kode,
            'tanggal_berlaku' => $this->parseDate($tanggal),
            'revisi_ke' => $revisi,
            'kategori' => $kategori,
        ];
    }

    private function pros(string $nama, string $kode, string $tanggal, string $revisi): array
    {
        return $this->doc($nama, $kode, $tanggal, $revisi, 'PROSEDUR');
    }

    private function form(string $nama, string $kode, string $tanggal, string $revisi): array
    {
        return $this->doc($nama, $kode, $tanggal, $revisi, 'FORMULIR');
    }

    private function getDocuments(): array
    {
        return [
            // ============================================================
            // PROSEDUR INDUK — MANUAL MUTU (PAH-MR)
            // PROS-01
            $this->pros('PROSEDUR IDENTIFIKASI KONTEKS ORGANISASI', 'PAH-MR-PROS-01', '3 January 2025', '00'),
            $this->form('PIHAK BERKEPENTINGAN', 'PAH-MR-FORM-01-01', '3 January 2025', '00'),
            $this->form('KONTEKS ORGANISASI', 'PAH-MR-FORM-01-02', '3 January 2025', '00'),
            // PROS-02
            $this->pros('PROSEDUR PENGENDALIAN DOKUMEN', 'PAH-MR-PROS-02', '3 January 2025', '00'),
            $this->form('CONTOH CAP', 'PAH-MR-FORM-02-01', '3 January 2025', '00'),
            $this->form('USULAN PEMBUATAN & REVISI DOKUMEN', 'PAH-MR-FORM-02-02', '3 January 2025', '00'),
            $this->form('DAFTAR INDUK DOKUMEN INTERNAL', 'PAH-MR-FORM-02-03', '3 January 2025', '00'),
            $this->form('DAFTAR INDUK DOKUMEN EKSTERNAL', 'PAH-MR-FORM-02-04', '3 January 2025', '00'),
            $this->form('BERITA ACARA PEMUSNAHAN DOKUMEN', 'PAH-MR-FORM-02-05', '3 January 2025', '00'),
            $this->form('DAFTAR DISTRIBUSI & PENARIKAN DOKUMEN', 'PAH-MR-FORM-02-06', '3 January 2025', '00'),
            // PROS-03
            $this->pros('PROSEDUR PENGENDALIAN REKAMAN', 'PAH-MR-PROS-03', '3 January 2025', '00'),
            $this->form('DAFTAR INDUK REKAMAN', 'PAH-MR-FORM-03-01', '3 January 2025', '00'),
            // PROS-04
            $this->pros('PROSEDUR AUDIT INTERNAL', 'PAH-MR-PROS-04', '3 January 2025', '00'),
            $this->form('PROGRAM AUDIT', 'PAH-MR-FORM-04-01', '3 January 2025', '00'),
            $this->form('RENCANA AUDIT INTERNAL', 'PAH-MR-FORM-04-02', '3 January 2025', '00'),
            $this->form('PEMILIHAN EVALUASI AUDITOR', 'PAH-MR-FORM-04-03', '3 January 2025', '00'),
            $this->form('JADWAL AUDIT INTERNAL', 'PAH-MR-FORM-04-04', '3 January 2025', '00'),
            $this->form('DAFTAR PERIKSA AUDIT INTERNAL', 'PAH-MR-FORM-04-05', '3 January 2025', '00'),
            $this->form('LAPORAN AUDIT INTERNAL', 'PAH-MR-FORM-04-06', '3 January 2025', '00'),
            $this->form('DAFTAR HADIR AUDIT INTERNAL', 'PAH-MR-FORM-04-07', '3 January 2025', '00'),
            // PROS-05
            $this->pros('PROSEDUR KETIDAKSESUAIAN DAN PERBAIKAN', 'PAH-MR-PROS-05', '3 January 2025', '00'),
            $this->form('KETIDAKSESUAIAN DAN TINDAK PERBAIKAN', 'PAH-MR-FORM-05-01', '3 January 2025', '00'),
            // PROS-06
            $this->pros('PROSEDUR TINJAUAN MANAJEMEN', 'PAH-MR-PROS-06', '3 January 2025', '00'),
            $this->form('UNDANGAN RAPAT TINJAUAN MANAJEMEN', 'PAH-MR-FORM-06-01', '3 January 2025', '00'),
            $this->form('DAFTAR HADIR RAPAT TINJAUAN MANAJEMEN', 'PAH-MR-FORM-06-02', '3 January 2025', '00'),
            $this->form('LAPORAN RAPAT TINJAUAN MANAJEMEN', 'PAH-MR-FORM-06-03', '3 January 2025', '00'),
            // PROS-07
            $this->pros('PROSEDUR ANALISA RESIKO DAN SASARAN MUTU', 'PAH-MR-PROS-07', '3 January 2025', '00'),
            $this->form('IDENTIFIKASI RESIKO', 'PAH-MR-FORM-07-01', '3 January 2025', '00'),
            $this->form('SASARAN MUTU', 'PAH-MR-FORM-07-02', '3 January 2025', '00'),

            // ============================================================
            // A. MUDIR PAH MATARAM (PAH-MDR)
            // PROS-01
            $this->pros('PROSEDUR PENYUSUNAN PROGRAM KERJA', 'PAH-MDR-PROS-01', '3 January 2025', '00'),
            $this->form('PROGRAM KERJA PONDOK', 'PAH-MDR-FORM-01-01', '3 January 2025', '00'),
            $this->form('TEMPLATE RANCANGAN PROGRAM KERJA', 'PAH-MDR-FORM-01-02', '3 January 2025', '00'),
            $this->form('FORMULIR RAPAT KOORDINASI', 'PAH-MDR-FORM-01-03', '3 January 2025', '00'),
            $this->form('FORMULIR REVISI PROGRAM KERJA', 'PAH-MDR-FORM-01-04', '3 January 2025', '00'),
            $this->form('TEMPLATE LAPORAN MONITORING DAN EVALUASI', 'PAH-MDR-FORM-01-05', '3 January 2025', '00'),
            $this->form('SURAT PENGESAHAN PROGRAM KERJA', 'PAH-MDR-FORM-01-06', '3 January 2025', '00'),
            $this->form('DAFTAR TIM PENYUSUN', 'PAH-MDR-FORM-01-07', '3 January 2025', '00'),
            // PROS-02
            $this->pros('PROSEDUR RAPAT PIMPINAN', 'PAH-MDR-PROS-02', '3 January 2025', '00'),
            $this->form('UNDANGAN RAPAT', 'PAH-MDR-FORM-02-01', '3 January 2025', '00'),
            $this->form('AGENDA RAPAT', 'PAH-MDR-FORM-02-02', '3 January 2025', '00'),
            $this->form('NOTULEN RAPAT', 'PAH-MDR-FORM-02-03', '3 January 2025', '00'),
            // PROS-03
            $this->pros('PROSEDUR MONITORING & EVALUASI', 'PAH-MDR-PROS-03', '3 January 2025', '00'),
            $this->form('FORMAT KUESIONER EVALUASI KINERJA GTK', 'PAH-MDR-FORM-03-01', '3 January 2025', '00'),
            $this->form('FORMAT OBSERVASI PELAKSANAAN PROGRAM KERJA', 'PAH-MDR-FORM-03-02', '3 January 2025', '00'),
            $this->form('JADWAL EVALUASI DAN MONITORING', 'PAH-MDR-FORM-03-03', '3 January 2025', '00'),
            $this->form('FORMAT LAPORAN HASIL EVALUASI DAN MONITORING', 'PAH-MDR-FORM-03-04', '3 January 2025', '00'),
            $this->form('STRUKTUR ORGANISASI TIM EVALUASI INTERNAL', 'PAH-MDR-FORM-03-05', '3 January 2025', '00'),
            // PROS-04
            $this->pros('PROSEDUR PENGANGKATAN GTK', 'PAH-MDR-PROS-04', '3 January 2025', '00'),
            // PROS-05
            $this->pros('PROSEDUR MUTASI, PROMOSI, & DEMOSI GTK', 'PAH-MDR-PROS-05', '3 January 2025', '00'),
            $this->form('FORMULIR PENGAJUAN MUTASI/PROMOSI/DEMOSI', 'PAH-MDR-FORM-05-01', '3 January 2025', '00'),
            $this->form('SURAT KEPUTUSAN (SK) MUTASI, PROMOSI, DAN DEMOSI', 'PAH-MDR-FORM-05-02', '3 January 2025', '00'),
            $this->form('LAPORAN EVALUASI KINERJA GTK', 'PAH-MDR-FORM-05-03', '3 January 2025', '00'),
            $this->form('BERITA ACARA WAWANCARA ATAU TES KOMPETENSI', 'PAH-MDR-FORM-05-04', '3 January 2025', '00'),
            // PROS-06
            $this->pros('PROSEDUR PEMBINAAN GTK', 'PAH-MDR-PROS-06', '3 January 2025', '00'),
            $this->form('JADWAL PEMBINAAN GTK', 'PAH-MDR-FORM-06-01', '3 January 2025', '00'),
            $this->form('DAFTAR HADIR PESERTA PEMBINAAN', 'PAH-MDR-FORM-06-02', '3 January 2025', '00'),
            $this->form('FORMAT EVALUASI PEMBINAAN GTK', 'PAH-MDR-FORM-06-03', '3 January 2025', '00'),
            $this->form('MATERI PEMBINAAN', 'PAH-MDR-FORM-06-04', '3 January 2025', '00'),
            $this->form('FORMULIR FEEDBACK GTK', 'PAH-MDR-FORM-06-05', '3 January 2025', '00'),
            // PROS-07
            $this->pros('PROSEDUR PEMBERIAN SP-3 KE GTK', 'PAH-MDR-PROS-07', '3 January 2025', '00'),
            $this->form('FORMAT SURAT PERINGATAN 3 (SP-3)', 'PAH-MDR-FORM-07-01', '3 January 2025', '00'),
            $this->form('DAFTAR PELANGGARAN YANG DAPAT MENGARAKIBATKAN SP-3', 'PAH-MDR-FORM-07-02', '3 January 2025', '00'),
            $this->form('BUKTI PENERIMAAN SP-3 OLEH GTK', 'PAH-MDR-FORM-07-03', '3 January 2025', '00'),
            // PROS-08
            $this->pros('PROSEDUR PEMUTUSAN HUBUNGAN KERJA GTK', 'PAH-MDR-PROS-08', '3 January 2025', '00'),
            $this->form('FORMULIR USULAN PHK DARI ATASAN LANGSUNG', 'PAH-MDR-FORM-08-01', '3 January 2025', '00'),
            $this->form('SURAT PEMBERITAHUAN PHK', 'PAH-MDR-FORM-08-02', '3 January 2025', '00'),
            $this->form('DAFTAR HAK-HAK GTK YANG DIBERIKAN', 'PAH-MDR-FORM-08-03', '3 January 2025', '00'),
            // PROS-09
            $this->pros('PROSEDUR LAPORAN BERKALA KE YAYASAN', 'PAH-MDR-PROS-09', '3 January 2025', '00'),
            $this->form('FORMAT LAPORAN AKADEMIK', 'PAH-MDR-FORM-09-01', '3 January 2025', '00'),
            $this->form('FORMAT LAPORAN PELAYANAN', 'PAH-MDR-FORM-09-02', '3 January 2025', '00'),
            $this->form('FORMAT LAPORAN KEUANGAN', 'PAH-MDR-FORM-09-03', '3 January 2025', '00'),
            $this->form('FORMAT LAPORAN KONSOLIDASI', 'PAH-MDR-FORM-09-04', '3 January 2025', '00'),

            // ============================================================
            // B. WADIR AKADEMIK & PENGASUHAN PAH MATARAM (PAH-WADIR AK)
            // PROS-01
            $this->pros('PROSEDUR PENYUSUNAN KURIKULUM PAH MATARAM SHOHIH', 'PAH-WADIR AK-PROS-01', '3 January 2025', '00'),
            $this->form('SK TIM PENYUSUN DAN PENGEMBANGAN KURIKULUM', 'PAH-WADIR AK-FORM-01-01', '3 January 2025', '00'),
            $this->form('LEMBAR PENGESAHAN KURIKULUM', 'PAH-WADIR AK-FORM-01-02', '3 January 2025', '00'),
            $this->form('LEMBAR PENETAPAN KURIKULUM', 'PAH-WADIR AK-FORM-01-03', '3 January 2025', '00'),
            $this->form('SILABUS MATA PELAJARAN', 'PAH-WADIR AK-FORM-01-04', '3 January 2025', '00'),
            $this->form('LEMBAR MONITORING PELAKSANAAN KURIKULUM', 'PAH-WADIR AK-FORM-01-05', '3 January 2025', '00'),
            // PROS-02
            $this->pros('PROSEDUR PENETAPAN PROFIL MUTU LULUSAN', 'PAH-WADIR AK-PROS-02', '3 January 2025', '00'),
            // PROS-03
            $this->pros('PROSEDUR PEMBERIAN SP-2 KE GTK', 'PAH-WADIR AK-PROS-03', '3 January 2025', '00'),
            // PROS-04
            $this->pros('PROSEDUR PERSETUJUAN STUDY BANDING', 'PAH-WADIR AK-PROS-04', '3 January 2025', '00'),

            // ============================================================
            // C. WADIR PELAYANAN UMUM PAH MATARAM (PAH-WADIR PU)
            // PROS-01
            $this->pros('PROSEDUR PENINGKATAN & PENJAMIN MUTU PELAYANAN UMUM', 'PAH-WADIR PU-PROS-01', '3 January 2025', '00'),
            // PROS-02
            $this->pros('PROSEDUR PEMBERIAN SP-2 KE PEGAWAI', 'PAH-WADIR PU-PROS-02', '3 January 2025', '00'),

            // ============================================================
            // D. KEPALA SATUAN PENDIDIKAN PAH MATARAM (PAH-KSP)
            // PROS-01
            $this->pros('LAPORAN BERKALA KEPADA WADIR AKADEMIK DAN PENGASUHAN', 'PAH-KSP-PROS-01', '10 September 2025', '01'),
            $this->form('LAPORAN PRESENSI GTK DAN PESERTA DIDIK PADA HARI PERTAMA BELAJAR', 'PAH-KSP-FORM-01-01', '10 September 2025', '01'),
            $this->form('LAPORAN BULANAN', 'PAH-KSP-FORM-01-02', '10 September 2025', '01'),
            $this->form('LAPORAN REKAPITULASI HASIL BELAJAR STS DAN SAS', 'PAH-KSP-FORM-01-03', '10 September 2025', '01'),
            $this->form('LAPORAN REKAPITULASI HASIL SURVEI KEPUASAN ORANG TUA', 'PAH-KSP-FORM-01-04', '10 September 2025', '01'),
            // PROS-02
            $this->pros('PROSEDUR RAPAT PEMBAGIAN TUGAS', 'PAH-KSP-PROS-02', '10 September 2025', '01'),
            $this->form('UNDANGAN RAPAT PEMBAGIAN TUGAS AWAL TAHUN AJARAN', 'PAH-KSP-FORM-02-01', '10 September 2025', '01'),
            $this->form('BERITA ACARA RAPAT PEMBAGIAN TUGAS AWAL TAHUN AJARAN', 'PAH-KSP-FORM-02-02', '10 September 2025', '01'),
            $this->form('DAFTAR HADIR RAPAT PEMBAGIAN TUGAS AWAL TAHUN AJARAN', 'PAH-KSP-FORM-02-03', '10 September 2025', '01'),
            // PROS-03
            $this->pros('PROSEDUR PEMANTAUAN GURU DI JAM PEMBELAJARAN', 'PAH-KSP-PROS-03', '10 September 2025', '01'),
            $this->form('FORMULIR REKAPITULASI KEHADIRAN GURU DISETIAP PERANTIAN JAM', 'PAH-KSP-FORM-03-01', '10 September 2025', '01'),
            // PROS-04
            $this->pros('PROSEDUR PELAKSANAAN SUPERVISI', 'PAH-KSP-PROS-04', '10 September 2025', '01'),
            $this->form('SURAT PERMAKLUMAN SUPERVISI PEMBELAJARAN', 'PAH-KSP-FORM-04-01', '10 September 2025', '01'),
            $this->form('SK TIM SUPERVISI PEMBELAJARAN', 'PAH-KSP-FORM-04-02', '10 September 2025', '01'),
            $this->form('JADWAL SUPERVISI PEMBELAJARAN', 'PAH-KSP-FORM-04-03', '10 September 2025', '01'),
            $this->form('INSTRUMEN SUPERVISI KEPALA MADRASAH', 'PAH-KSP-FORM-04-04', '10 September 2025', '01'),
            $this->form('LAPORAN HASIL SUPERVISI', 'PAH-KSP-FORM-04-05', '10 September 2025', '01'),
            // PROS-05
            $this->pros('PROSEDUR USULAN PROMOSI GTK', 'PAH-KSP-PROS-05', '10 September 2025', '01'),
            $this->form('USULAN PROMOSI STATUS GTK', 'PAH-KSP-FORM-05-01', '10 September 2025', '01'),
            // PROS-06
            $this->pros('PROSEDUR PEMBINAAN GTK', 'PAH-KSP-PROS-06', '10 September 2025', '01'),
            $this->form('CATATAN PEMBINAAN GTK', 'PAH-KSP-FORM-06-01', '10 September 2025', '01'),
            $this->form('BERITA ACARA PEMBINAAN GTK', 'PAH-KSP-FORM-06-02', '10 September 2025', '01'),
            // PROS-07
            $this->pros('PROSEDUR PEMBERIAN SP-1 KE GTK', 'PAH-KSP-PROS-07', '10 September 2025', '01'),
            $this->form('SURAT PERINGATAN PERTAMA (SP-1)', 'PAH-KSP-FORM-07-01', '10 September 2025', '01'),
            // PROS-08
            $this->pros('PROSEDUR PENYUSUNAN ADMINISTRASI PEMBELAJARAN', 'PAH-KSP-PROS-08', '10 September 2025', '01'),
            $this->form('DAFTAR NILAI', 'PAH-KSP-FORM-08-01', '10 September 2025', '01'),
            $this->form('JURNAL KELAS', 'PAH-KSP-FORM-08-02', '10 September 2025', '01'),
            // PROS-09
            $this->pros('PROSEDUR PERSIAPAN PEMBELAJARAN', 'PAH-KSP-PROS-09', '10 September 2025', '01'),
            $this->form('ANALISIS PEKAN EFEKTIF', 'PAH-KSP-FORM-09-01', '10 September 2025', '01'),
            $this->form('PROGRAM TAHUNAN (PROTA)', 'PAH-KSP-FORM-09-02', '10 September 2025', '01'),
            $this->form('PROGRAM SEMESTER (PROSEM)', 'PAH-KSP-FORM-09-03', '10 September 2025', '01'),
            $this->form('RPM MAPEL UMUM', 'PAH-KSP-FORM-09-04', '10 September 2025', '01'),
            $this->form('RPM MAPEL AGAMA', 'PAH-KSP-FORM-09-05', '10 September 2025', '01'),
            // PROS-10
            $this->pros('PROSEDUR PROSES PELAKSANAAN PEMBELAJARAN', 'PAH-KSP-PROS-10', '10 September 2025', '01'),
            // PROS-11
            $this->pros('PROSEDUR PENYUSUNAN SOAL STS DAN SAS', 'PAH-KSP-PROS-11', '10 September 2025', '01'),
            $this->form('PENANGGUNG JAWAB PENYUSUN SOAL STS & SAS', 'PAH-KSP-FORM-11-01', '10 September 2025', '01'),
            $this->form('KISI-KISI SOAL', 'PAH-KSP-FORM-11-02', '10 September 2025', '01'),
            $this->form('KARTU SOAL', 'PAH-KSP-FORM-11-03', '10 September 2025', '01'),
            $this->form('BERITA ACARA FINALISASI DAN PLENO SOAL', 'PAH-KSP-FORM-11-04', '10 September 2025', '01'),
            // PROS-12
            $this->pros('PROSEDUR PELAKSANAAN STS DAN SAS', 'PAH-KSP-PROS-12', '10 September 2025', '01'),
            $this->form('SK KEPANITIAN', 'PAH-KSP-FORM-12-01', '10 September 2025', '01'),
            $this->form('JADWAL SUMATIF', 'PAH-KSP-FORM-12-02', '10 September 2025', '01'),
            $this->form('BERITA ACARA', 'PAH-KSP-FORM-12-03', '10 September 2025', '01'),
            $this->form('TATA TERTIB PESERTA UJIAN', 'PAH-KSP-FORM-12-04', '10 September 2025', '01'),
            $this->form('TATA TERTIB PENGAWAS', 'PAH-KSP-FORM-12-05', '10 September 2025', '01'),
            $this->form('DAFTAR HADIR PESERTA DIDIK', 'PAH-KSP-FORM-12-06', '10 September 2025', '01'),
            $this->form('DAFTAR HADIR PENGAWAS', 'PAH-KSP-FORM-12-07', '10 September 2025', '01'),
            $this->form('REKAPITULASI NILAI MURNI STS DAN SAS', 'PAH-KSP-FORM-12-08', '10 September 2025', '01'),
            $this->form('LAPORAN PELAKSANAAN STS DAN SAS', 'PAH-KSP-FORM-12-09', '10 September 2025', '01'),
            // PROS-13
            $this->pros('PROSEDUR REMEDIAL DAN PENGAYAAN PEMBELAJARAN', 'PAH-KSP-PROS-13', '10 September 2025', '01'),
            $this->form('PROGRAM REMEDIAL DAN PENGAYAAN', 'PAH-KSP-FORM-13-01', '10 September 2025', '01'),
            // PROS-14
            $this->pros('PROSEDUR PENYUSUNAN LAPORAN HASIL BELAJAR', 'PAH-KSP-PROS-14', '10 September 2025', '01'),
            $this->form('FORMULIR LEGER', 'PAH-KSP-FORM-14-01', '10 September 2025', '01'),
            $this->form('LAPORAN HASIL BELAJAR TENGAH SEMESTER', 'PAH-KSP-FORM-14-02', '10 September 2025', '01'),
            $this->form('LAPORAN HASIL BELAJAR AKHIR SEMESTER', 'PAH-KSP-FORM-14-03', '10 September 2025', '01'),
            // PROS-15
            $this->pros('PROSEDUR PEMBAGIAN LAPORAN HASIL BELAJAR', 'PAH-KSP-PROS-15', '10 September 2025', '01'),
            $this->form('SURAT PEMBERITAHUAN PEMBAGIAN LAPORAN HASIL BELAJAR', 'PAH-KSP-FORM-15-01', '10 September 2025', '01'),
            $this->form('BUKTI PENGAMBILAN LAPORAN HASIL BELAJAR', 'PAH-KSP-FORM-15-02', '10 September 2025', '01'),
            // PROS-16
            $this->pros('PROSEDUR PEMBINAAN PESERTA DIDIK', 'PAH-KSP-PROS-16', '10 September 2025', '01'),
            $this->form('SURAT PERNYATAAN PEMBINAAN PESERTA DIDIK', 'PAH-KSP-FORM-16-01', '10 September 2025', '01'),
            $this->form('SURAT PERNYATAAN PESERTA DIDIK', 'PAH-KSP-FORM-16-02', '10 September 2025', '01'),
            $this->form('BERITA ACARA PEMBINAAN PESERTA DIDIK', 'PAH-KSP-FORM-16-03', '10 September 2025', '01'),
            // PROS-17
            $this->pros('PROSEDUR PEMANGGILAN ORANG TUA PESERTA DIDIK', 'PAH-KSP-PROS-17', '10 September 2025', '01'),
            $this->form('SURAT PEMANGGILAN ORANG TUA PESERTA DIDIK', 'PAH-KSP-FORM-17-01', '10 September 2025', '01'),
            $this->form('SURAT PERNYATAAN ORANG TUA PESERTA DIDIK', 'PAH-KSP-FORM-17-02', '10 September 2025', '01'),
            // PROS-18
            $this->pros('PROSEDUR SKORSING', 'PAH-KSP-PROS-18', '10 September 2025', '01'),
            $this->form('SURAT PERINGATAN PERTAMA', 'PAH-KSP-FORM-18-01', '10 September 2025', '01'),
            $this->form('SURAT PERINGATAN KEDUA', 'PAH-KSP-FORM-18-02', '10 September 2025', '01'),
            $this->form('SURAT KEPUTUSAN SKORSING', 'PAH-KSP-FORM-18-03', '10 September 2025', '01'),
            $this->form('BERITA ACARA SKORSING', 'PAH-KSP-FORM-18-04', '10 September 2025', '01'),
            // PROS-19
            $this->pros('PROSEDUR PELAKSANAAN EKSTRAKURIKULER', 'PAH-KSP-PROS-19', '10 September 2025', '01'),
            $this->form('FORMULIR PENDAFTARAN PESERTA', 'PAH-KSP-FORM-19-01', '10 September 2025', '01'),
            $this->form('JADWAL KEGIATAN EKSTRAKURIKULER', 'PAH-KSP-FORM-19-02', '10 September 2025', '01'),
            $this->form('FORMULIR RENCANA ANGGARAN BIAYA EKSTRAKURIKULER', 'PAH-KSP-FORM-19-03', '10 September 2025', '01'),
            $this->form('FORMULIR LAPORAN HASIL KEGIATAN EKSTRAKURIKULER', 'PAH-KSP-FORM-19-04', '10 September 2025', '01'),
            $this->form('DAFTAR HADIR', 'PAH-KSP-FORM-19-05', '10 September 2025', '01'),
            // PROS-20
            $this->pros('PROSEDUR RAPAT SATUAN PENDIDIKAN', 'PAH-KSP-PROS-20', '10 September 2025', '01'),
            $this->form('UNDANGAN RAPAT', 'PAH-KSP-FORM-20-01', '10 September 2025', '01'),
            $this->form('DAFTAR HADIR RAPAT', 'PAH-KSP-FORM-20-02', '10 September 2025', '01'),
            $this->form('BERITA ACARA RAPAT', 'PAH-KSP-FORM-20-03', '10 September 2025', '01'),
            // PROS-21
            $this->pros('PROSEDUR PENGELOLAAN DATA PESERTA DIDIK', 'PAH-KSP-PROS-21', '10 September 2025', '01'),
            $this->form('FORMULIR IDENTITAS PESERTA DIDIK', 'PAH-KSP-FORM-21-01', '10 September 2025', '01'),
            $this->form('FORMULIR CHECKLIST KELENGKAPAN BERKAS ADMINISTRASI PESERTA DIDIK', 'PAH-KSP-FORM-21-02', '10 September 2025', '01'),
            $this->form('FORMULIR PERUBAHAN IDENTITAS PESERTA DIDIK DAPODIK', 'PAH-KSP-FORM-21-03', '10 September 2025', '01'),
            $this->form('FORMULIR LAPORAN DATA PESERTA DIDIK DAPODIK', 'PAH-KSP-FORM-21-04', '10 September 2025', '01'),
            // PROS-22
            $this->pros('PROSEDUR PENGELOLAAN SURAT MENYURAT', 'PAH-KSP-PROS-22', '10 September 2025', '01'),
            $this->form('FORMULIR SURAT UNDANGAN', 'PAH-KSP-FORM-22-01', '10 September 2025', '01'),
            $this->form('FORMULIR SURAT TUGAS', 'PAH-KSP-FORM-22-02', '10 September 2025', '01'),
            $this->form('FORMULIR SURAT PEMBERITAHUAN', 'PAH-KSP-FORM-22-03', '10 September 2025', '01'),
            $this->form('FORMULIR SURAT PERMOHONAN', 'PAH-KSP-FORM-22-04', '10 September 2025', '01'),
            $this->form('FORMULIR SURAT PENGANTAR', 'PAH-KSP-FORM-22-05', '10 September 2025', '01'),
            $this->form('FORMULIR SURAT KETERANGAN', 'PAH-KSP-FORM-22-06', '10 September 2025', '01'),
            $this->form('FORMULIR SURAT PERINGATAN', 'PAH-KSP-FORM-22-07', '10 September 2025', '01'),
            $this->form('FORMULIR BUKU AGENDA SURAT MASUK', 'PAH-KSP-FORM-22-08', '10 September 2025', '01'),
            $this->form('FORMULIR BUKU AGENDA SURAT KELUAR', 'PAH-KSP-FORM-22-09', '10 September 2025', '01'),
            $this->form('FORMULIR BUKU EKSPEDISI', 'PAH-KSP-FORM-22-10', '10 September 2025', '01'),
            // PROS-23
            $this->pros('PROSEDUR PENGELOLAAN MUTASI PESERTA DIDIK', 'PAH-KSP-PROS-23', '10 September 2025', '01'),
            $this->form('FORMULIR SURAT KETERANGAN MUTASI KELUAR', 'PAH-KSP-FORM-23-01', '10 September 2025', '01'),
            $this->form('FORMULIR SURAT REKOMENDASI MENERIMA', 'PAH-KSP-FORM-23-02', '10 September 2025', '01'),
            $this->form('FORMULIR SURAT REKOMENDASI DAFTAR ULANG', 'PAH-KSP-FORM-23-03', '10 September 2025', '01'),
            $this->form('FORMULIR AGENDA MUTASI MASUK PESERTA DIDIK', 'PAH-KSP-FORM-23-04', '10 September 2025', '01'),
            $this->form('FORMULIR AGENDA MUTASI KELUAR PESERTA DIDIK', 'PAH-KSP-FORM-23-05', '10 September 2025', '01'),
            // PROS-24
            $this->pros('PROSEDUR PENGELOLAAN IJAZAH PESERTA DIDIK', 'PAH-KSP-PROS-24', '10 September 2025', '01'),
            $this->form('FORMULIR BUKTI PENGAMBILAN IJAZAH SANTRI', 'PAH-KSP-FORM-24-01', '10 September 2025', '01'),
            // PROS-25
            $this->pros('PROSEDUR PENGELOLAAN DATA GTK SATUAN PENDIDIKAN', 'PAH-KSP-PROS-25', '10 September 2025', '01'),
            $this->form('FORMULIR DATA GURU', 'PAH-KSP-FORM-25-01', '10 September 2025', '01'),
            $this->form('LAPORAN DATA GURU', 'PAH-KSP-FORM-25-02', '10 September 2025', '01'),
            // PROS-26
            $this->pros('PROSEDUR PENELUSURAN ALUMNI', 'PAH-KSP-PROS-26', '10 September 2025', '01'),
            $this->form('FORMULIR PENELUSURAN ALUMNI', 'PAH-KSP-FORM-26-01', '10 September 2025', '01'),
            $this->form('FORMULIR LAPORAN PENELUSURAN ALUMNI', 'PAH-KSP-FORM-26-02', '10 September 2025', '01'),
            // PROS-27
            $this->pros('PROSEDUR PENGELOLAAN DANA BOS', 'PAH-KSP-PROS-27', '10 September 2025', '01'),
            $this->form('FORMULIR RENCANA KERJA ANGGARAN SEKOLAH (RKAS)', 'PAH-KSP-FORM-27-01', '10 September 2025', '01'),
            $this->form('FORMULIR BUKU KAS UMUM (BKU)', 'PAH-KSP-FORM-27-02', '10 September 2025', '01'),
            $this->form('FORMULIR BUKU PEMBANTU TUNAI', 'PAH-KSP-FORM-27-03', '10 September 2025', '01'),
            $this->form('FORMULIR BUKU PEMBANTU BANK', 'PAH-KSP-FORM-27-04', '10 September 2025', '01'),
            $this->form('FORMULIR BUKU PEMBANTU PAJAK', 'PAH-KSP-FORM-27-05', '10 September 2025', '01'),
            $this->form('FORMULIR REKAPITULASI REALISASI PENGGUNAAN DANA', 'PAH-KSP-FORM-27-06', '10 September 2025', '01'),
            $this->form('FORMULIR KWITANSI BOS', 'PAH-KSP-FORM-27-07', '10 September 2025', '01'),
            // PROS-28
            $this->pros('PROSEDUR PERMINTAAN DAN LEGALISIR DOKUMEN', 'PAH-KSP-PROS-28', '10 September 2025', '01'),
            $this->form('STEMPEL LEGALISIR', 'PAH-KSP-FORM-28-01', '10 September 2025', '01'),
            $this->form('FORMULIR DAFTAR LEGALISIR DOKUMEN', 'PAH-KSP-FORM-28-02', '10 September 2025', '01'),

            // ============================================================
            // E. KEPALA PENGASUHAN (PAH-KP)
            // PROS-01
            $this->pros('PROSEDUR KEGIATAN SANTRI DI LUAR PONDOK PESANTREN', 'PAH-KP-PROS-01', '10 September 2025', '01'),
            $this->form('FORMULIR PENGAJUAN IZIN KEGIATAN', 'PAH-KP-FORM-01-01', '10 September 2025', '01'),
            $this->form('FORMULIR LAPORAN KEGIATAN', 'PAH-KP-FORM-01-02', '10 September 2025', '01'),
            // PROS-02
            $this->pros('PROSEDUR KEGIATAN SANTRI DI DALAM PONDOK PESANTREN', 'PAH-KP-PROS-02', '10 September 2025', '01'),
            $this->form('FORMULIR PENGAJUAN IZIN KEGIATAN', 'PAH-KP-FORM-02-01', '10 September 2025', '01'),
            $this->form('FORMULIR LAPORAN KEGIATAN', 'PAH-KP-FORM-02-02', '10 September 2025', '01'),
            // PROS-03
            $this->pros('PROSEDUR PEMBINAAN SANTRI', 'PAH-KP-PROS-03', '10 September 2025', '01'),
            $this->form('CATATAN HASIL OBSERVASI', 'PAH-KP-FORM-03-01', '10 September 2025', '01'),
            $this->form('FORMULIR PEMBINAAN', 'PAH-KP-FORM-03-02', '10 September 2025', '01'),
            $this->form('FORMULIR PEMBINAAN KELOMPOK', 'PAH-KP-FORM-03-03', '10 September 2025', '01'),
            $this->form('FORMULIR LAPORAN PEMBINAAN INDIVIDU DAN KELOMPOK', 'PAH-KP-FORM-03-04', '10 September 2025', '01'),
            // PROS-04
            $this->pros('PROSEDUR PEMELIHARAAN KEBERSIHAN DAN KERAPIAN ASRAMA', 'PAH-KP-PROS-04', '10 September 2025', '01'),
            $this->form('FORMULIR JADWAL KEBERSIHAN DAN KERAPIAN ASRAMA', 'PAH-KP-FORM-04-01', '10 September 2025', '01'),
            $this->form('FORMULIR JADWAL KEBERSIHAN LINGKUNGAN ASRAMA', 'PAH-KP-FORM-04-02', '10 September 2025', '01'),
            $this->form('TATA TERTIB KEBERSIHAN DAN KERAPIAN ASRAMA', 'PAH-KP-FORM-04-03', '10 September 2025', '01'),
            $this->form('CHECKLIST KEBERSIHAN', 'PAH-KP-FORM-04-04', '10 September 2025', '01'),
            // PROS-05
            $this->pros('PROSEDUR PENANGANAN PELANGGARAN SANTRI', 'PAH-KP-PROS-05', '10 September 2025', '01'),
            $this->form('DAFTAR PELANGGARAN DAN POIN', 'PAH-KP-FORM-05-01', '10 September 2025', '01'),
            $this->form('FORMULIR LAPORAN PELANGGARAN', 'PAH-KP-FORM-05-02', '10 September 2025', '01'),
            $this->form('FORMULIR PENANGANAN PELANGGARAN', 'PAH-KP-FORM-05-03', '10 September 2025', '01'),
            $this->form('SURAT PERNYATAAN SANTRI', 'PAH-KP-FORM-05-04', '10 September 2025', '01'),
            $this->form('SURAT PERNYATAAN WALI SANTRI', 'PAH-KP-FORM-05-05', '10 September 2025', '01'),
            $this->form('FORMULIR JURNAL PELANGGARAN SANTRI', 'PAH-KP-FORM-05-06', '10 September 2025', '01'),
            // PROS-06
            $this->pros('PROSEDUR PENERIMAAN KEDATANGAN SANTRI', 'PAH-KP-PROS-06', '10 September 2025', '01'),
            $this->form('FORMULIR CHECKLIST KEDATANGAN SANTRI KELAS 7', 'PAH-KP-FORM-06-01', '10 September 2025', '01'),
            $this->form('FORMAT LAPORAN KEDATANGAN SANTRI', 'PAH-KP-FORM-06-02', '10 September 2025', '01'),
            // PROS-07
            $this->pros('PROSEDUR PENGAWASAN AKTIVITAS HARIAN SANTRI', 'PAH-KP-PROS-07', '10 September 2025', '01'),
            $this->form('JADWAL 24 JAM ASRAMA', 'PAH-KP-FORM-07-01', '10 September 2025', '01'),
            $this->form('TATA TERTIB ASRAMA', 'PAH-KP-FORM-07-02', '10 September 2025', '01'),
            $this->form('FORMULIR JADWAL PIKET STAF PENGASUHAN', 'PAH-KP-FORM-07-03', '10 September 2025', '01'),
            $this->form('FORMULIR LAPORAN PENGAWASAN', 'PAH-KP-FORM-07-04', '10 September 2025', '01'),
            // PROS-08
            $this->pros('PROSEDUR PENANGANAN BARANG SITAAN', 'PAH-KP-PROS-08', '10 September 2025', '01'),
            $this->form('FORMULIR PENYITAAN BARANG', 'PAH-KP-FORM-08-01', '10 September 2025', '01'),
            $this->form('FORMULIR PENGEMBALIAN BARANG SITAAN', 'PAH-KP-FORM-08-02', '10 September 2025', '01'),
            $this->form('BERITA ACARA PEMUSNAHAN BARANG SITAAN', 'PAH-KP-FORM-08-03', '10 September 2025', '01'),
            // PROS-09
            $this->pros('PROSEDUR PERIZINAN SANTRI', 'PAH-KP-PROS-09', '10 September 2025', '01'),
            $this->form('KARTU IZIN KELUAR ASRAMA', 'PAH-KP-FORM-09-01', '10 September 2025', '01'),
            $this->form('KARTU PENGUNJUNG', 'PAH-KP-FORM-09-02', '10 September 2025', '01'),
            $this->form('FORMULIR JURNAL PERIZINAN', 'PAH-KP-FORM-09-03', '10 September 2025', '01'),

            // ============================================================
            // F. KEPALA DEPARTEMEN TAHFIZH (PAH-DEPT-TAH)
            // PROS-01
            $this->pros('PROSEDUR PENYUSUNAN MUQORROR TAHFIZH', 'PAH-DEPT-TAH-PROS-01', '3 Januari 2025', '00'),
            $this->form('FORMULIR DIAGRAM ALUR TARGET MUQORROR TAHFIZH', 'PAH-DEPT-TAH-FORM-01-01', '3 Januari 2025', '00'),
            $this->form('FORMULIR TABEL PENYUSUNAN MUQORROR TAHFIZH', 'PAH-DEPT-TAH-FORM-01-02', '3 Januari 2025', '00'),
            $this->form('FORMULIR PENYUSUNAN RPP TAHFIZH', 'PAH-DEPT-TAH-FORM-01-03', '3 Januari 2025', '00'),
            // PROS-02
            $this->pros('PROSEDUR PELAKSANAAN PEMBELAJARAN', 'PAH-DEPT-TAH-PROS-02', '3 Januari 2025', '00'),
            $this->form('FORMULIR MONITORING PERKEMBANGAN HAFALAN (WARAQATUL MUTABA\'AH)', 'PAH-DEPT-TAH-FORM-02-01', '3 Januari 2025', '00'),
            $this->form('FORMULIR LAPORAN PERKEMBANGAN HAFALAN BULANAN', 'PAH-DEPT-TAH-FORM-02-02', '3 Januari 2025', '00'),
            $this->form('FORMULIR RENCANA PEMBELAJARAN IQRO\'', 'PAH-DEPT-TAH-FORM-02-03', '3 Januari 2025', '00'),
            $this->form('FORMULIR RENCANA PEMBELAJARAN TAHFIZH AL-QUR\'AN', 'PAH-DEPT-TAH-FORM-02-04', '3 Januari 2025', '00'),
            // PROS-03
            $this->pros('SOP PELAKSANAAN TASMI\'AN', 'PAH-DEPT-TAH-PROS-03', '3 Januari 2025', '00'),
            $this->form('FORMULIR INSTRUMEN PENILAIAN TASMI\'', 'PAH-DEPT-TAH-FORM-03-01', '3 Januari 2025', '00'),
            $this->form('FORMULIR PENGISIAN DATA NILAI SERTIFIKAT TASMI\'', 'PAH-DEPT-TAH-FORM-03-02', '3 Januari 2025', '00'),
            $this->form('FORMULIR DESAIN SERTIFIKAT TASMI\'', 'PAH-DEPT-TAH-FORM-03-03', '3 Januari 2025', '00'),
            // PROS-04
            $this->pros('PROSEDUR PELAKSANAAN UTHQ', 'PAH-DEPT-TAH-PROS-04', '3 Januari 2025', '00'),
            $this->form('FORMULIR PENDAFTARAN PESERTA AUDISI UTHQ', 'PAH-DEPT-TAH-FORM-04-01', '3 Januari 2025', '00'),
            $this->form('FORMULIR INSTRUMEN PENILAIAN AUDISI UTHQ', 'PAH-DEPT-TAH-FORM-04-02', '3 Januari 2025', '00'),
            $this->form('FORMULIR REKAPITULASI HASIL AUDISI UTHQ', 'PAH-DEPT-TAH-FORM-04-03', '3 Januari 2025', '00'),
            $this->form('FORMULIR INSTRUMEN PENILAIAN FINAL UTHQ', 'PAH-DEPT-TAH-FORM-04-04', '3 Januari 2025', '00'),
            $this->form('FORMULIR REKAPITULASI HASIL FINAL UTHQ', 'PAH-DEPT-TAH-FORM-04-05', '3 Januari 2025', '00'),
            $this->form('FORMULIR DESAIN SERTIFIKAT UTHQ', 'PAH-DEPT-TAH-FORM-04-06', '3 Januari 2025', '00'),
            // PROS-05
            $this->pros('PROSEDUR PELAKSANAAN TAKRIM', 'PAH-DEPT-TAH-PROS-05', '14 Juli 2025', '01'),
            $this->form('FORMULIR DESAIN SERTIFIKAT TAKRIM', 'PAH-DEPT-TAH-FORM-05-01', '14 Juli 2025', '01'),
            $this->form('FORMULIR PENGAJUAN PENCETAKAN SERTIFIKAT TAKRIM', 'PAH-DEPT-TAH-FORM-05-02', '14 Juli 2025', '01'),
            // PROS-06
            $this->pros('PROSEDUR RAPAT EVALUASI HASIL BELAJAR', 'PAH-DEPT-TAH-PROS-06', '14 Juli 2025', '01'),
            $this->form('FORMULIR KEHADIRAN RAPAT EVALUASI HASIL BELAJAR', 'PAH-DEPT-TAH-FORM-06-01', '14 Juli 2025', '01'),
            $this->form('FORMULIR REKAPITULASI KEHADIRAN', 'PAH-DEPT-TAH-FORM-06-02', '14 Juli 2025', '01'),
            $this->form('FORM BERITA ACARA KEGIATAN', 'PAH-DEPT-TAH-FORM-06-03', '14 Juli 2025', '01'),
            // PROS-07
            $this->pros('PROSEDUR UJIAN ITQON GURU', 'PAH-DEPT-TAH-PROS-07', '3 Januari 2025', '00'),
            $this->form('FORMULIR REGISTRASI / PENGAJUAN MATERI UJIAN ITQON', 'PAH-DEPT-TAH-FORM-07-01', '3 Januari 2025', '00'),
            $this->form('FORMULIR INSTRUMEN PENILAIAN UJIAN ITQON', 'PAH-DEPT-TAH-FORM-07-02', '3 Januari 2025', '00'),
            $this->form('FORMULIR REKAPITULASI HASIL UJIAN ITQON', 'PAH-DEPT-TAH-FORM-07-03', '3 Januari 2025', '00'),
            $this->form('FORMULIR DESAIN SERTIFIKAT PESERTA UJIAN ITQON', 'PAH-DEPT-TAH-FORM-07-04', '3 Januari 2025', '00'),
            // PROS-08
            $this->pros('PROSEDUR PENILAIAN KINERJA GURU TAHFIZH', 'PAH-DEPT-TAH-PROS-08', '3 Januari 2025', '00'),
            $this->form('FORMULIR LEDGER DATA RAPOR GURU TAHFIZH', 'PAH-DEPT-TAH-FORM-08-01', '3 Januari 2025', '00'),
            $this->form('FORMULIR DESAIN TEMPLATE RAPOR GURU TAHFIZH', 'PAH-DEPT-TAH-FORM-08-02', '3 Januari 2025', '00'),
            // PROS-09
            $this->pros('PROSEDUR SERTIFIKASI HAFALAN GURU', 'PAH-DEPT-TAH-PROS-09', '14 Juli 2025', '01'),
            $this->form('FORMULIR SERTIFIKASI HAFALAN GURU', 'PAH-DEPT-TAH-FORM-09-01', '14 Juli 2025', '01'),
            $this->form('FORMULIR PENDAFTARAN SERTIFIKASI GURU (GFORM)', 'PAH-DEPT-TAH-FORM-09-02', '14 Juli 2025', '01'),
            $this->form('FORMULIR REKAP PENDAFTARAN PESERTA SERTIFIKASI GURU', 'PAH-DEPT-TAH-FORM-09-03', '14 Juli 2025', '01'),
            $this->form('FORMULIR DESAIN SERTIFIKAT SERTIFIKASI GURU', 'PAH-DEPT-TAH-FORM-09-04', '14 Juli 2025', '01'),

            // ============================================================
            // G. KEPALA DEPARTEMEN BAHASA DAN KADERISASI (PAH-DEPT-BHS)
            // PROS-01
            $this->pros('PROSEDUR RAPAT EVALUASI GURU BAHASA ARAB', 'PAH-DEPT-BHS-PROS-01', '10 September 2025', '01'),
            $this->form('FORMULIR DAFTAR HADIR PESERTA RAPAT', 'PAH-DEPT-BHS-FORM-01-01', '10 September 2025', '01'),
            $this->form('FORMULIR DATA DAN HASIL EVALUASI', 'PAH-DEPT-BHS-FORM-01-02', '10 September 2025', '01'),
            $this->form('FORMULIR HASIL NOTULEN RAPAT', 'PAH-DEPT-BHS-FORM-01-03', '10 September 2025', '01'),
            // PROS-02
            $this->pros('PROSEDUR PELAKSANAAN FESTIVAL BAHASA', 'PAH-DEPT-BHS-PROS-02', '10 September 2025', '01'),
            $this->form('FORMULIR DAFTAR PESERTA FESTIVAL BAHASA', 'PAH-DEPT-BHS-FORM-02-01', '10 September 2025', '01'),
            $this->form('FORMULIR PENILAIAN LOMBA FESTIVAL BAHASA', 'PAH-DEPT-BHS-FORM-02-02', '10 September 2025', '01'),
            $this->form('FORMULIR EVALUASI INTERNAL KEGIATAN', 'PAH-DEPT-BHS-FORM-02-03', '10 September 2025', '01'),
            // PROS-03
            $this->pros('PROSEDUR HAFALAN MUFRADAT', 'PAH-DEPT-BHS-PROS-03', '10 September 2025', '01'),
            $this->form('FORMULIR DAFTAR MUFRADAT', 'PAH-DEPT-BHS-FORM-03-01', '10 September 2025', '01'),
            $this->form('FORMULIR DAFTAR HADIR SETORAN HAFALAN MUFRADAT', 'PAH-DEPT-BHS-FORM-03-02', '10 September 2025', '01'),
            $this->form('FORMULIR LAPORAN EVALUASI HAFALAN MUFRADAT', 'PAH-DEPT-BHS-FORM-03-03', '10 September 2025', '01'),
            // PROS-04
            $this->pros('PROSEDUR PENANGANAN PELANGGARAN BAHASA', 'PAH-DEPT-BHS-PROS-04', '10 September 2025', '01'),
            $this->form('FORMULIR PELANGGARAN BAHASA', 'PAH-DEPT-BHS-FORM-04-01', '10 September 2025', '01'),
            $this->form('FORMULIR LOG PEMBINAAN BAHASA ARAB', 'PAH-DEPT-BHS-FORM-04-02', '10 September 2025', '01'),
            $this->form('FORMULIR SURAT PERJANJIAN PEMBINAAN', 'PAH-DEPT-BHS-FORM-04-03', '10 September 2025', '01'),
            // PROS-05
            $this->pros('PROSEDUR 90% BAHASA PENGANTAR GURU SAAT KBM MENGGUNAKAN BAHASA ARAB', 'PAH-DEPT-BHS-PROS-05', '10 September 2025', '01'),
            $this->form('FORMULIR RPM', 'PAH-DEPT-BHS-FORM-05-01', '10 September 2025', '01'),
            $this->form('FORMULIR DAFTAR HADIR PESERTA DIDIK DALAM KBM', 'PAH-DEPT-BHS-FORM-05-02', '10 September 2025', '01'),
            $this->form('FORMULIR LAPORAN EVALUASI KBM', 'PAH-DEPT-BHS-FORM-05-03', '10 September 2025', '01'),
            // PROS-06
            $this->pros('PROSEDUR 95% SANTRI BERKOMUNIKASI DENGAN BAHASA ARAB', 'PAH-DEPT-BHS-PROS-06', '10 September 2025', '01'),
            $this->form('FORMULIR LAPORAN KEMAJUAN BAHASA ARAB', 'PAH-DEPT-BHS-FORM-06-01', '10 September 2025', '01'),
            $this->form('FORMULIR PENDAFTARAN KEGIATAN BAHASA ARAB', 'PAH-DEPT-BHS-FORM-06-02', '10 September 2025', '01'),
            $this->form('FORMULIR PENGAWASAN PELANGGARAN BAHASA', 'PAH-DEPT-BHS-FORM-06-03', '10 September 2025', '01'),
            // PROS-07
            $this->pros('PROSEDUR PROGRAM NASYATH SYAHRI', 'PAH-DEPT-BHS-PROS-07', '10 September 2025', '01'),
            $this->form('FORMULIR JADWAL NASYATH SYAHRI', 'PAH-DEPT-BHS-FORM-07-01', '10 September 2025', '01'),
            $this->form('FORMULIR DAFTAR HADIR PESERTA KEGIATAN', 'PAH-DEPT-BHS-FORM-07-02', '10 September 2025', '01'),
            $this->form('FORMULIR PENGAJUAN DANA KEGIATAN', 'PAH-DEPT-BHS-FORM-07-03', '10 September 2025', '01'),
            // PROS-08
            $this->pros('PROSEDUR RAPAT EVALUASI QISM LUGHAH SANTRI', 'PAH-DEPT-BHS-PROS-08', '10 September 2025', '01'),
            $this->form('FORMULIR DAFTAR HADIR PESERTA RAPAT', 'PAH-DEPT-BHS-FORM-08-01', '10 September 2025', '01'),
            $this->form('FORMULIR NOTULEN RAPAT', 'PAH-DEPT-BHS-FORM-08-02', '10 September 2025', '01'),
            $this->form('FORMULIR RENCANA TINDAK LANJUT HASIL EVALUASI', 'PAH-DEPT-BHS-FORM-08-03', '10 September 2025', '01'),

            // ============================================================
            // H. KOORDINATOR PERPUSTAKAAN (PAH-PERPUS)
            // PROS-01
            $this->pros('PROSEDUR PEMINJAMAN KOLEKSI PERPUSTAKAAN', 'PAH-PERPUS-PROS-01', '10 September 2025', '01'),
            $this->form('FORMULIR KARTU ANGGOTA', 'PAH-PERPUS-FORM-01-01', '10 September 2025', '01'),
            $this->form('FORMULIR KARTU BIRU (KARTU PINJAM)', 'PAH-PERPUS-FORM-01-02', '10 September 2025', '01'),
            $this->form('FORMULIR KARTU MERAH (KARTU BUKU)', 'PAH-PERPUS-FORM-01-03', '10 September 2025', '01'),
            $this->form('FORMULIR DATABASE PEMINJAMAN BUKU', 'PAH-PERPUS-FORM-01-04', '10 September 2025', '01'),
            // PROS-02
            $this->pros('PROSEDUR PENYUSUNAN PENGADAAN BUKU', 'PAH-PERPUS-PROS-02', '10 September 2025', '01'),
            $this->form('FORMULIR USULAN PENGADAAN BUKU BARU', 'PAH-PERPUS-FORM-02-01', '10 September 2025', '01'),
            $this->form('FORMULIR PEMESANAN BUKU', 'PAH-PERPUS-FORM-02-02', '10 September 2025', '01'),
            // PROS-03
            $this->pros('PROSEDUR KUNJUNGAN PERPUSTAKAAN', 'PAH-PERPUS-PROS-03', '10 September 2025', '01'),
            $this->form('FORMULIR KUNJUNGAN PERPUSTAKAAN', 'PAH-PERPUS-FORM-03-01', '10 September 2025', '01'),
            $this->form('FORMULIR TATA TERTIB PERPUSTAKAAN', 'PAH-PERPUS-FORM-03-02', '10 September 2025', '01'),
            $this->form('FORMULIR LAPORAN KUNJUNGAN HARIAN', 'PAH-PERPUS-FORM-03-03', '10 September 2025', '01'),
            // PROS-04
            $this->pros('PROSEDUR SURAT BEBAS PEMINJAMAN', 'PAH-PERPUS-PROS-04', '10 September 2025', '01'),
            $this->form('FORMULIR SURAT BEBAS PEMINJAMAN', 'PAH-PERPUS-FORM-04-01', '10 September 2025', '01'),
            // PROS-05
            $this->pros('PROSEDUR STOCK OPNAME', 'PAH-PERPUS-PROS-05', '10 September 2025', '01'),
            // PROS-06
            $this->pros('PROSEDUR PENYIANGAN', 'PAH-PERPUS-PROS-06', '10 September 2025', '01'),
            $this->form('FORMULIR BUKU HIBAH', 'PAH-PERPUS-FORM-06-01', '10 September 2025', '01'),
            $this->form('LAPORAN HIBAH BUKU', 'PAH-PERPUS-FORM-06-02', '10 September 2025', '01'),

            // ============================================================
            // I. KOORDINATOR LABORATORIUM (PAH-LAB)
            // PROS-01
            $this->pros('PROSEDUR PENGELOLAAN INVENTARIS DAN KALIBRASI ALAT LABORATORIUM', 'PAH-LAB-PROS-01', '3 Januari 2025', '00'),
            $this->form('FORMULIR DAFTAR INVENTARIS LABORATORIUM', 'PAH-LAB-FORM-01-01', '3 Januari 2025', '00'),
            $this->form('FORMULIR PEMERIKSAAN BERKALA ALAT DAN BAHAN LABORATORIUM', 'PAH-LAB-FORM-01-02', '3 Januari 2025', '00'),
            $this->form('JADWAL KALIBRASI ALAT LABORATORIUM', 'PAH-LAB-FORM-01-03', '3 Januari 2025', '00'),
            $this->form('FORMULIR PENGHAPUSAN INVENTARIS LABORATORIUM', 'PAH-LAB-FORM-01-04', '3 Januari 2025', '00'),
            $this->form('FORMULIR PEMINJAMAN DAN PENGEMBALIAN ALAT LABORATORIUM IPA', 'PAH-LAB-FORM-01-05', '3 Januari 2025', '00'),
            // PROS-02
            $this->pros('PROSEDUR PENGELOLAAN LABORATORIUM', 'PAH-LAB-PROS-02', '3 Januari 2025', '00'),
            $this->form('BERITA ACARA PRAKTIKUM', 'PAH-LAB-FORM-02-01', '3 Januari 2025', '00'),
            $this->form('IZIN PENGGUNAAN FASILITAS LABORATORIUM', 'PAH-LAB-FORM-02-02', '3 Januari 2025', '00'),
            $this->form('JADWAL PENGGUNAAN LABORATORIUM', 'PAH-LAB-FORM-02-03', '3 Januari 2025', '00'),
            $this->form('DAFTAR HADIR PENGGUNAAN LABORATORIUM', 'PAH-LAB-FORM-02-04', '3 Januari 2025', '00'),
            $this->form('TATA TERTIB LABORATORIUM', 'PAH-LAB-FORM-02-05', '3 Januari 2025', '00'),
            $this->form('MODUL PRAKTIKUM', 'PAH-LAB-FORM-02-06', '3 Januari 2025', '00'),
            $this->form('LAPORAN PRAKTIKUM', 'PAH-LAB-FORM-02-07', '3 Januari 2025', '00'),
            // PROS-03
            $this->pros('PROSEDUR KESELAMATAN DAN KEAMANAN LABORATORIUM', 'PAH-LAB-PROS-03', '3 Januari 2025', '00'),
            $this->form('FORMULIR INSPEKSI KESELAMATAN LABORATORIUM', 'PAH-LAB-FORM-03-01', '3 Januari 2025', '00'),
            $this->form('FORMULIR LAPORAN INSIDEN', 'PAH-LAB-FORM-03-02', '3 Januari 2025', '00'),

            // ============================================================
            // J. KEPALA UNIT PELAYANAN TERPADU (PAH-KUPT)
            // PROS-01
            $this->pros('PROSEDUR PELAYANAN UKS', 'PAH-KUPT-PROS-01', '3 Januari 2025', '00'),
            $this->form('SURAT REKOMENDASI', 'PAH-KUPT-FORM-01-02', '3 Januari 2025', '00'),
            $this->form('SURAT KETERANGAN SAKIT', 'PAH-KUPT-FORM-01-03', '3 Januari 2025', '00'),
            $this->form('KRITERIA PASIEN OBSERVASI', 'PAH-KUPT-FORM-01-04', '3 Januari 2025', '00'),
            $this->form('ALUR PENANGANAN SANTRI', 'PAH-KUPT-FORM-01-05', '3 Januari 2025', '00'),
            // PROS-02
            $this->pros('PROSEDUR PENYULUHAN', 'PAH-KUPT-PROS-02', '3 Januari 2025', '00'),
            $this->form('LAPORAN KEGIATAN PENYULUHAN', 'PAH-KUPT-FORM-02-01', '3 Januari 2025', '00'),
            $this->form('LAPORAN KEGIATAN SCABIES', 'PAH-KUPT-FORM-02-02', '3 Januari 2025', '00'),
            $this->form('LAPORAN KEGIATAN PHBS', 'PAH-KUPT-FORM-02-03', '3 Januari 2025', '00'),
            $this->form('LAPORAN KEGIATAN PENYULUHAN BAHAYA LGBT', 'PAH-KUPT-FORM-02-04', '3 Januari 2025', '00'),
            // PROS-03
            $this->pros('PROSEDUR PENANGANAN KECELAKAAN DAN KEJADIAN KEGAWATDARURATAN', 'PAH-KUPT-PROS-03', '3 Januari 2025', '00'),
            $this->form('LAPORAN RUJUKAN SANTRI SAKIT', 'PAH-KUPT-FORM-03-01', '3 Januari 2025', '00'),
            // PROS-04
            $this->pros('PROSEDUR PIKET JAGA PERAWAT DI UNIT KESEHATAN SEKOLAH (UKS)', 'PAH-KUPT-PROS-04', '3 Januari 2025', '00'),
            $this->form('JADWAL PIKET', 'PAH-KUPT-FORM-04-01', '3 Januari 2025', '00'),
            // PROS-05
            $this->pros('PROSEDUR PENGADAAN BARANG', 'PAH-KUPT-PROS-05', '3 Januari 2025', '00'),
            $this->form('KARTU STOK BARANG', 'PAH-KUPT-FORM-05-01', '3 Januari 2025', '00'),
            $this->form('DAFTAR KEBUTUHAN BARANG', 'PAH-KUPT-FORM-05-02', '3 Januari 2025', '00'),
            $this->form('PURCHASE ORDER (PO)', 'PAH-KUPT-FORM-05-03', '3 Januari 2025', '00'),
            $this->form('BERITA ACARA PENERIMAAN BARANG', 'PAH-KUPT-FORM-05-04', '3 Januari 2025', '00'),
            // PROS-06
            $this->pros('PROSEDUR PENJUALAN BARANG', 'PAH-KUPT-PROS-06', '10 September 2025', '01'),
            $this->form('FORMULIR PENCATATAN TRANSAKSI', 'PAH-KUPT-FORM-06-01', '10 September 2025', '01'),
            $this->form('LAPORAN HARIAN PENJUALAN', 'PAH-KUPT-FORM-06-02', '10 September 2025', '01'),
            $this->form('PURCHASE ORDER (PO)', 'PAH-KUPT-FORM-06-03', '10 September 2025', '01'),
            // PROS-07
            $this->pros('PROSEDUR PELAPORAN BARANG', 'PAH-KUPT-PROS-07', '3 Januari 2025', '00'),
            $this->form('PENCATATAN STOK HARIAN', 'PAH-KUPT-FORM-07-01', '3 Januari 2025', '00'),
            $this->form('LAPORAN STOK MINGGUAN/BULANAN', 'PAH-KUPT-FORM-07-02', '3 Januari 2025', '00'),
            $this->form('BERITA ACARA AUDIT STOK', 'PAH-KUPT-FORM-07-03', '3 Januari 2025', '00'),
            $this->form('FORMULIR PERMINTAAN BARANG', 'PAH-KUPT-FORM-07-04', '3 Januari 2025', '00'),
            // PROS-08
            $this->pros('PROSEDUR KERJA SAMA DENGAN PIHAK KETIGA', 'PAH-KUPT-PROS-08', '3 Januari 2025', '00'),
            $this->form('DOKUMEN KONTRAK KERJA SAMA', 'PAH-KUPT-FORM-08-01', '3 Januari 2025', '00'),
            $this->form('EVALUASI KINERJA MITRA', 'PAH-KUPT-FORM-08-02', '3 Januari 2025', '00'),
            // PROS-09
            $this->pros('PROSEDUR PENANGANAN KOMPLAIN KONSUMEN', 'PAH-KUPT-PROS-09', '3 Januari 2025', '00'),
            $this->form('FORMULIR KOMPLAIN PELANGGAN', 'PAH-KUPT-FORM-09-01', '3 Januari 2025', '00'),
            $this->form('LAPORAN KOMPLAIN BULANAN', 'PAH-KUPT-FORM-09-02', '3 Januari 2025', '00'),
            // PROS-10
            $this->pros('PROSEDUR LAUNDRY', 'PAH-KUPT-PROS-10', '10 September 2025', '01'),
            $this->form('SERAH TERIMA PAKAIAN KOTOR', 'PAH-KUPT-FORM-10-01', '10 September 2025', '01'),
            $this->form('BERITA ACARA PENERIMAAN PAKAIAN', 'PAH-KUPT-FORM-10-02', '10 September 2025', '01'),
            $this->form('SERAH TERIMA PAKAIAN BERSIH', 'PAH-KUPT-FORM-10-03', '10 September 2025', '01'),
            $this->form('FORMULIR PENITIPAN PAKAIAN', 'PAH-KUPT-FORM-10-04', '10 September 2025', '01'),

            // ============================================================
            // K. SATUAN KEAMANAN (PAH-SATPAM)
            // PROS-01
            $this->pros('PROSEDUR PENJAGAAN SATPAM', 'PAH-SATPAM-PROS-01', '3 Januari 2025', '00'),
            $this->form('BUKU KUNJUNGAN', 'PAH-SATPAM-FORM-01-01', '3 Januari 2025', '00'),
            $this->form('JADWAL PIKET SATPAM', 'PAH-SATPAM-FORM-01-02', '3 Januari 2025', '00'),
            // PROS-02
            $this->pros('PROSEDUR PENANGANAN GANGGUAN KEAMANAN SATPAM', 'PAH-SATPAM-PROS-02', '3 Januari 2025', '00'),
            $this->form('LAPORAN KEJADIAN', 'PAH-SATPAM-FORM-02-01', '3 Januari 2025', '00'),
            $this->form('TEMUAN PATROLI KEAMANAN', 'PAH-SATPAM-FORM-02-02', '3 Januari 2025', '00'),
            // PROS-03
            $this->pros('PROSEDUR KOORDINASI SATPAM', 'PAH-SATPAM-PROS-03', '3 Januari 2025', '00'),
            $this->form('BUKU SERAH TERIMA PIKET', 'PAH-SATPAM-FORM-03-01', '3 Januari 2025', '00'),
            $this->form('FORMULIR TINDAKAN KEAMANAN', 'PAH-SATPAM-FORM-03-02', '3 Januari 2025', '00'),
            // PROS-04
            $this->pros('PROSEDUR LAYANAN SATPAM', 'PAH-SATPAM-PROS-04', '3 Januari 2025', '00'),
            $this->form('DAFTAR NOMOR TELEPON PENTING PONDOK', 'PAH-SATPAM-FORM-04-01', '3 Januari 2025', '00'),
            $this->form('FORMULIR BARANG KIRIMAN', 'PAH-SATPAM-FORM-04-02', '3 Januari 2025', '00'),

            // ============================================================
            // L. KEPALA UNIT GIZI DAN LOGISTIK (PAH-UGL)
            // PROS-01
            $this->pros('PROSEDUR PENGATURAN JADWAL DAN MENU', 'PAH-UGL-PROS-01', '3 Januari 2025', '00'),
            $this->form('FORMULIR JADWAL MAKAN', 'PAH-UGL-FORM-01-01', '3 Januari 2025', '00'),
            $this->form('FORM MENU PERIODIK', 'PAH-UGL-FORM-01-02', '3 Januari 2025', '00'),
            $this->form('KUESIONER SANTRI', 'PAH-UGL-FORM-01-03', '3 Januari 2025', '00'),
            // PROS-02
            $this->pros('PROSEDUR BELANJA BAHAN MAKANAN', 'PAH-UGL-PROS-02', '3 Januari 2025', '00'),
            $this->form('PERMINTAAN & PENERIMAAN PEMBELIAN', 'PAH-UGL-FORM-02-01', '3 Januari 2025', '00'),
            $this->form('FORM MONITORING BAHAN POKOK', 'PAH-UGL-FORM-02-02', '3 Januari 2025', '00'),
            $this->form('DAFTAR PEMASOK BARANG', 'PAH-UGL-FORM-02-03', '3 Januari 2025', '00'),
            $this->form('FORM LAPORAN UMUM BELANJA', 'PAH-UGL-FORM-02-04', '3 Januari 2025', '00'),
            // PROS-03
            $this->pros('PROSEDUR PENYAJIAN DAN DISTRIBUSI MAKANAN', 'PAH-UGL-PROS-03', '3 Januari 2025', '00'),
            $this->form('FORMULIR PEMBAGIAN LAUK-PAUK', 'PAH-UGL-FORM-03-01', '3 Januari 2025', '00'),
            $this->form('FORMAT SKEMA TUGAS PIKET SANTRI', 'PAH-UGL-FORM-03-02', '3 Januari 2025', '00'),
            $this->form('LAYOUT PENYAJIAN DAN DISTRIBUSI', 'PAH-UGL-FORM-03-03', '3 Januari 2025', '00'),
            $this->form('FORMULIR CATATAN WAKTU PENYAJIAN MENU', 'PAH-UGL-FORM-03-04', '3 Januari 2025', '00'),
            // PROS-04
            $this->pros('PROSEDUR PENGELOLAAN KEBERSIHAN', 'PAH-UGL-PROS-04', '3 Januari 2025', '00'),
            $this->form('FORMULIR INSPEKSI KEBERSIHAN', 'PAH-UGL-FORM-04-01', '3 Januari 2025', '00'),
            $this->form('DAFTAR BAHAN PEMBERSIH DAN SANITASI', 'PAH-UGL-FORM-04-02', '3 Januari 2025', '00'),
            // PROS-05
            $this->pros('PROSEDUR PEMELIHARAAN ALAT DAPUR', 'PAH-UGL-PROS-05', '3 Januari 2025', '00'),
            $this->form('FORMULIR PEMELIHARAAN ALAT DAPUR', 'PAH-UGL-FORM-05-01', '3 Januari 2025', '00'),
            $this->form('LAPORAN INVENTARIS ALAT DAPUR', 'PAH-UGL-FORM-05-02', '3 Januari 2025', '00'),
            // PROS-06
            $this->pros('PROSEDUR EVALUASI DAN TINDAKAN DARURAT', 'PAH-UGL-PROS-06', '3 Januari 2025', '00'),
            $this->form('FORM MONITORING BERKALA KEGIATAN', 'PAH-UGL-FORM-06-01', '3 Januari 2025', '00'),
            $this->form('LAPORAN KERACUNAN MAKANAN', 'PAH-UGL-FORM-06-02', '3 Januari 2025', '00'),
            $this->form('LAPORAN KECELAKAAN KERJA', 'PAH-UGL-FORM-06-03', '3 Januari 2025', '00'),

            // ============================================================
            // M. KOORDINATOR KEAMANAN — kosong

            // ============================================================
            // N. KEPALA UNIT SISTEM TEKNOLOGI INFORMASI & JARINGAN (PAH-TIJ)
            // PROS-01
            $this->pros('PROSEDUR PEMELIHARAAN JARINGAN INTERNET', 'PAH-TIJ-PROS-01', '3 Januari 2025', '00'),
            $this->form('FORMULIR PERANGKAT JARINGAN', 'PAH-TIJ-FORM-01-01', '3 Januari 2025', '00'),
            $this->form('FORMULIR LAPORAN INSIDEN JARINGAN', 'PAH-TIJ-FORM-01-02', '3 Januari 2025', '00'),
            $this->form('FORMULIR JADWAL PEMELIHARAAN JARINGAN', 'PAH-TIJ-FORM-01-03', '3 Januari 2025', '00'),
            // PROS-02
            $this->pros('PROSEDUR KEAMANAN DAN PEMELIHARAAN SERVER', 'PAH-TIJ-PROS-02', '3 Januari 2025', '00'),
            $this->form('JADWAL PEMELIHARAAN DAN BACKUP SERVER', 'PAH-TIJ-FORM-02-01', '3 Januari 2025', '00'),
            $this->form('FORMULIR LAPORAN INSIDEN SERVER DAN KEAMANAN', 'PAH-TIJ-FORM-02-02', '3 Januari 2025', '00'),
            $this->form('FORMULIR DAFTAR PERANGKAT LUNAK YANG DIGUNAKAN', 'PAH-TIJ-FORM-02-03', '3 Januari 2025', '00'),
            $this->form('CEKLIS MINGGUAN AC DAN UPS RUANG SERVER', 'PAH-TIJ-FORM-02-04', '3 Januari 2025', '00'),
            // PROS-03
            $this->pros('PROSEDUR PENGELOLAAN APLIKASI SIPAHAM', 'PAH-TIJ-PROS-03', '3 Januari 2025', '00'),
            $this->form('FORMULIR PENAMBAHAN FITUR DAN SISTEM', 'PAH-TIJ-FORM-03-01', '3 Januari 2025', '00'),
            $this->form('FORMULIR ERP', 'PAH-TIJ-FORM-03-02', '3 Januari 2025', '00'),
            // PROS-04
            $this->pros('PROSEDUR PENGELOLAAN WEBSITE PONDOK', 'PAH-TIJ-PROS-04', '3 Januari 2025', '00'),
            $this->form('LAPORAN INSIDEN WEB', 'PAH-TIJ-FORM-04-01', '3 Januari 2025', '00'),
            $this->form('PERMINTAAN DAN PERSETUJUAN MODIFIKASI SISTEM WEB', 'PAH-TIJ-FORM-04-02', '3 Januari 2025', '00'),
            $this->form('PERMINTAAN DAN PERSETUJUAN PERUBAHAN KONTEN', 'PAH-TIJ-FORM-04-03', '3 Januari 2025', '00'),

            // ============================================================
            // O. KEPALA KEUANGAN (PAH-KEU)
            // PROS-01
            $this->pros('PROSEDUR PENYUSUNAN RENCANA ANGGARAN BELANJA (RAB) PONDOK', 'PAH-KEU-PROS-01', '10 September 2025', '01'),
            $this->form('FORMULIR KEBIJAKAN UMUM ANGGARAN (KUA)', 'PAH-KEU-FORM-01-01', '10 September 2025', '01'),
            $this->form('FORMULIR RENCANA ANGGARAN PENERIMAAN DAN BELANJA PONDOK (RAPBP)', 'PAH-KEU-FORM-01-02', '10 September 2025', '01'),
            // PROS-02
            $this->pros('PROSEDUR PENERIMAAN IBS', 'PAH-KEU-PROS-02', '10 September 2025', '01'),
            $this->form('FORM BUKTI PEMBAYARAN IBS', 'PAH-KEU-FORM-02-01', '10 September 2025', '01'),
            $this->form('FORM PENERIMAAN HARIAN TELLER', 'PAH-KEU-FORM-02-02', '10 September 2025', '01'),
            // PROS-03
            $this->pros('PROSEDUR PENITIPAN UANG BELANJA PESERTA DIDIK', 'PAH-KEU-PROS-03', '3 Januari 2025', '00'),
            $this->form('FORMAT BUKTI TRANSFER TITIPAN UANG BELANJA', 'PAH-KEU-FORM-03-01', '3 Januari 2025', '00'),
            $this->form('FORM JURNAL BELANJA PESERTA DIDIK', 'PAH-KEU-FORM-03-02', '3 Januari 2025', '00'),
            $this->form('FORM BUKTI PENGAMBILAN TITIPAN UANG BELANJA', 'PAH-KEU-FORM-03-03', '3 Januari 2025', '00'),
            // PROS-04
            $this->pros('PROSEDUR ALUR KERJA PENCAIRAN DANA DAN PENCATATAN KEUANGAN', 'PAH-KEU-PROS-04', '3 Januari 2025', '00'),
            $this->form('FORMULIR PENCAIRAN DANA', 'PAH-KEU-FORM-04-01', '3 Januari 2025', '00'),
            $this->form('FORMULIR LAPORAN PENGGUNAAN DANA', 'PAH-KEU-FORM-04-02', '3 Januari 2025', '00'),
            $this->form('BUKTI KAS', 'PAH-KEU-FORM-04-03', '3 Januari 2025', '00'),
            $this->form('PERMOHONAN AKSES DANA EMERGENCY', 'PAH-KEU-FORM-04-04', '3 Januari 2025', '00'),
            $this->form('PERMOHONAN PENAMBAHAN ANGGARAN BARU', 'PAH-KEU-FORM-04-05', '3 Januari 2025', '00'),
            // PROS-05
            $this->pros('PROSEDUR MEKANISME PENGGAJIAN', 'PAH-KEU-PROS-05', '3 Januari 2025', '00'),
            $this->form('DOKUMEN SK', 'PAH-KEU-FORM-05-01', '3 Januari 2025', '00'),
            $this->form('DOKUMEN SURAT CUTI', 'PAH-KEU-FORM-05-02', '3 Januari 2025', '00'),
            $this->form('FORMULIR REKAP KEHADIRAN GTK', 'PAH-KEU-FORM-05-03', '3 Januari 2025', '00'),
            $this->form('FORMULIR REKAP POTONGAN PINJAMAN DAN KASBON', 'PAH-KEU-FORM-05-04', '3 Januari 2025', '00'),
            $this->form('FORMULIR REKAP POTONGAN SPP ANAK PTK', 'PAH-KEU-FORM-05-05', '3 Januari 2025', '00'),
            $this->form('FORMULIR REKAP TITIPAN', 'PAH-KEU-FORM-05-06', '3 Januari 2025', '00'),
            $this->form('KITIR GAJI', 'PAH-KEU-FORM-05-07', '3 Januari 2025', '00'),
            $this->form('FORM KEBUTUHAN UANG TUNAI', 'PAH-KEU-FORM-05-08', '3 Januari 2025', '00'),
            $this->form('SURAT JALAN PENARIKAN UANG', 'PAH-KEU-FORM-05-09', '3 Januari 2025', '00'),
            $this->form('BERITA ACARA SERAH TERIMA UANG', 'PAH-KEU-FORM-05-10', '3 Januari 2025', '00'),
            $this->form('BUKTI PENERIMAAN GAJI', 'PAH-KEU-FORM-05-11', '3 Januari 2025', '00'),
            $this->form('FORMULIR KELUHAN GAJI', 'PAH-KEU-FORM-05-12', '3 Januari 2025', '00'),
            $this->form('FORMULIR REKAP PENANGANAN KELUHAN GAJI', 'PAH-KEU-FORM-05-13', '3 Januari 2025', '00'),
            // PROS-06
            $this->pros('PROSEDUR PEMBERIAN TUNJANGAN', 'PAH-KEU-PROS-06', '3 Januari 2025', '00'),
            $this->form('FORMULIR REKAPITULASI PEMBERIAN TUNJANGAN', 'PAH-KEU-FORM-06-01', '3 Januari 2025', '00'),
            $this->form('FORM KITIR THR', 'PAH-KEU-FORM-06-02', '3 Januari 2025', '00'),
            $this->form('FORM BUKTI PENERIMAAN THR', 'PAH-KEU-FORM-06-03', '3 Januari 2025', '00'),
            $this->form('FORM KITIR TUNJANGAN AKHIR TAHUN PELAJARAN', 'PAH-KEU-FORM-06-04', '3 Januari 2025', '00'),
            $this->form('FORM BUKTI PENERIMAAN TUNJANGAN AKHIR TAHUN PELAJARAN', 'PAH-KEU-FORM-06-05', '3 Januari 2025', '00'),
            // PROS-07
            $this->pros('PROSEDUR PINJAMAN GTK', 'PAH-KEU-PROS-07', '3 Januari 2025', '00'),
            $this->form('FORMULIR PERMOHONAN PINJAMAN', 'PAH-KEU-FORM-07-01', '3 Januari 2025', '00'),
            $this->form('FORM REKAP KASBON GAJI', 'PAH-KEU-FORM-07-02', '3 Januari 2025', '00'),
            $this->form('FORMULIR SYARAT DAN KETENTUAN PINJAMAN', 'PAH-KEU-FORM-07-03', '3 Januari 2025', '00'),
            // PROS-08
            $this->pros('PROSEDUR LAPORAN KEUANGAN', 'PAH-KEU-PROS-08', '3 Januari 2025', '00'),
            $this->form('FORMAT LAPORAN KEUANGAN PONDOK', 'PAH-KEU-FORM-08-01', '3 Januari 2025', '00'),
            // PROS-09
            $this->pros('PROSEDUR RAPAT EVALUASI ANGGARAN', 'PAH-KEU-PROS-09', '3 Januari 2025', '00'),
            $this->form('FORM UNDANGAN RAPAT', 'PAH-KEU-FORM-09-01', '3 Januari 2025', '00'),
            $this->form('FORM DAFTAR HADIR RAPAT', 'PAH-KEU-FORM-09-02', '3 Januari 2025', '00'),
            $this->form('FORM NOTULEN RAPAT EVALUASI ANGGARAN', 'PAH-KEU-FORM-09-03', '3 Januari 2025', '00'),
            // PROS-10
            $this->pros('PROSEDUR OBSERVASI DAN PENANGANAN TUNGGAKAN IBS DAN UP', 'PAH-KEU-PROS-10', '10 September 2025', '01'),
            $this->form('FORM REKAPAN DATA TUNGGAKAN IBS', 'PAH-KEU-FORM-10-01', '10 September 2025', '01'),
            $this->form('FORM TARGET OBSERVASI DAN SURVEI BULANAN', 'PAH-KEU-FORM-10-02', '10 September 2025', '01'),
            $this->form('FORMAT TEMPLATE REMINDER PEMBAYARAN', 'PAH-KEU-FORM-10-03', '10 September 2025', '01'),
            $this->form('FORMAT FLYER IBS', 'PAH-KEU-FORM-10-04', '10 September 2025', '01'),
            $this->form('FORMAT SURAT PEMBERITAHUAN (SP)', 'PAH-KEU-FORM-10-05', '10 September 2025', '01'),
            $this->form('FORM OBSERVASI DAN SURVEI', 'PAH-KEU-FORM-10-06', '10 September 2025', '01'),
            $this->form('FORM REKAPITULASI HASIL OBSERVASI DAN SURVEI', 'PAH-KEU-FORM-10-07', '10 September 2025', '01'),
            $this->form('FORMAT SURAT PERJANJIAN PEMBAYARAN TUNGGAKAN', 'PAH-KEU-FORM-10-08', '10 September 2025', '01'),
            $this->form('FORMULIR MONITORING PEMBAYARAN TUNGGAKAN', 'PAH-KEU-FORM-10-09', '10 September 2025', '01'),
            $this->form('FORMAT KARTU UJIAN', 'PAH-KEU-FORM-10-10', '10 September 2025', '01'),
            // PROS-11
            $this->pros('PROSEDUR PEMBERIAN SUBSIDI IBS', 'PAH-KEU-PROS-11', '10 September 2025', '01'),
            $this->form('FORM KERINGANAN IBS PONDOK', 'PAH-KEU-FORM-11-01', '10 September 2025', '01'),
            $this->form('FORM KERINGANAN YAYASAN', 'PAH-KEU-FORM-11-02', '10 September 2025', '01'),
            // PROS-12
            $this->pros('PROSEDUR TALANGAN YAYASAN', 'PAH-KEU-PROS-12', '10 September 2025', '01'),
            $this->form('FORM TALANGAN YAYASAN', 'PAH-KEU-FORM-12-01', '10 September 2025', '01'),
            $this->form('FORM REKAP TALANGAN YAYASAN', 'PAH-KEU-FORM-12-02', '10 September 2025', '01'),
            // PROS-13
            $this->pros('PROSEDUR PENGELOLAAN DATA MUTASI', 'PAH-KEU-PROS-13', '10 September 2025', '01'),
            $this->form('FORM MUTASI DATA', 'PAH-KEU-FORM-13-01', '10 September 2025', '01'),
            $this->form('FORM MUTASI DATA LANJUTAN', 'PAH-KEU-FORM-13-02', '10 September 2025', '01'),
            // PROS-14
            $this->pros('PROSEDUR PERMOHONAN PEMBAYARAN', 'PAH-KEU-PROS-14', '10 September 2025', '01'),
            $this->form('FORMULIR PERMOHONAN PEMBAYARAN', 'PAH-KEU-FORM-14-01', '10 September 2025', '01'),
            // PROS-15
            $this->pros('PROSEDUR TITIPAN TRANSFER', 'PAH-KEU-PROS-15', '10 September 2025', '01'),
            $this->form('FORMULIR TITIPAN TRANSFER', 'PAH-KEU-FORM-15-01', '10 September 2025', '01'),
            // PROS-16
            $this->pros('PROSEDUR TABUNGAN', 'PAH-KEU-PROS-16', '10 September 2025', '01'),
            $this->form('FORMULIR PEMBUKAAN TABUNGAN', 'PAH-KEU-FORM-16-01', '10 September 2025', '01'),
            $this->form('BUKU TABUNGAN', 'PAH-KEU-FORM-16-02', '10 September 2025', '01'),

            // ============================================================
            // P. HUMAS PERSONALIA (PAH-HUMAS)
            // PROS-01
            $this->pros('PROSEDUR PENGELOLAAN DATA GTK & SANTRI', 'PAH-HUMAS-PROS-01', '10 September 2025', '01'),
            $this->form('FORMULIR DATA GTK', 'PAH-HUMAS-FORM-01-01', '10 September 2025', '01'),
            $this->form('FORMULIR DATA PESERTA DIDIK', 'PAH-HUMAS-FORM-01-02', '10 September 2025', '01'),
            $this->form('FORMULIR PERMINTAAN LAPORAN BERKALA KE SATUAN KERJA', 'PAH-HUMAS-FORM-01-03', '10 September 2025', '01'),
            $this->form('FORMAT LAPORAN BERKALA SEKOLAH', 'PAH-HUMAS-FORM-01-04', '10 September 2025', '01'),
            $this->form('FORMULIR PERSETUJUAN PENGGUNAAN DATA SISWA & GTK', 'PAH-HUMAS-FORM-01-05', '10 September 2025', '01'),
            // PROS-02
            $this->pros('PROSEDUR REKRUTMEN DAN PENGANGKATAN GTK', 'PAH-HUMAS-PROS-02', '10 September 2025', '01'),
            $this->form('FORMULIR IDENTIFIKASI KEBUTUHAN GTK', 'PAH-HUMAS-FORM-02-01', '10 September 2025', '01'),
            $this->form('FORMULIR LOWONGAN GTK', 'PAH-HUMAS-FORM-02-02', '10 September 2025', '01'),
            $this->form('FORMULIR PENGUMUMAN SELEKSI ADMINISTRASI GTK', 'PAH-HUMAS-FORM-02-03', '10 September 2025', '01'),
            $this->form('FORMULIR INSTRUMEN PENILAIAN REKRUTMEN GTK', 'PAH-HUMAS-FORM-02-04', '10 September 2025', '01'),
            $this->form('FORMULIR INSTRUMEN PENILAIAN WAWANCARA GTK', 'PAH-HUMAS-FORM-02-05', '10 September 2025', '01'),
            $this->form('FORMULIR REKAPITULASI TES GTK', 'PAH-HUMAS-FORM-02-06', '10 September 2025', '01'),
            $this->form('SK PENGUMUMAN SELEKSI GTK', 'PAH-HUMAS-FORM-02-07', '10 September 2025', '01'),
            $this->form('SK PENGANGKATAN PEGAWAI PERCOBAAN', 'PAH-HUMAS-FORM-02-08', '10 September 2025', '01'),
            $this->form('FORMULIR PENGAJUAN KENAIKAN STATUS PTK', 'PAH-HUMAS-FORM-02-09', '10 September 2025', '01'),
            $this->form('FORMULIR PENILAIAN KINERJA GTK', 'PAH-HUMAS-FORM-02-10', '10 September 2025', '01'),
            $this->form('SK PENGANGKATAN PEGAWAI TETAP', 'PAH-HUMAS-FORM-02-11', '10 September 2025', '01'),
            $this->form('FORMULIR LAPORAN REKRUTMEN GTK', 'PAH-HUMAS-FORM-02-12', '10 September 2025', '01'),
            // PROS-03
            $this->pros('PROSEDUR KEMITRAAN', 'PAH-HUMAS-PROS-03', '10 September 2025', '01'),
            $this->form('FORMULIR SURAT PERMOHONAN KERJASAMA', 'PAH-HUMAS-FORM-03-01', '10 September 2025', '01'),
            $this->form('PROPOSAL KERJASAMA', 'PAH-HUMAS-FORM-03-02', '10 September 2025', '01'),
            // PROS-04
            $this->pros('PROSEDUR PUBLIKASI', 'PAH-HUMAS-PROS-04', '10 September 2025', '01'),
            $this->form('FORMULIR SURAT UNDANGAN RAPAT PUBLIKASI', 'PAH-HUMAS-FORM-04-01', '10 September 2025', '01'),
            $this->form('DAFTAR HADIR RAPAT PUBLIKASI', 'PAH-HUMAS-FORM-04-02', '10 September 2025', '01'),
            $this->form('FORMULIR NOTULEN RAPAT PERENCANAAN PUBLIKASI', 'PAH-HUMAS-FORM-04-03', '10 September 2025', '01'),
            $this->form('TIMELINE PAH', 'PAH-HUMAS-FORM-04-04', '10 September 2025', '01'),
            $this->form('LAPORAN MONITORING & EVALUASI PUBLIKASI', 'PAH-HUMAS-FORM-04-05', '10 September 2025', '01'),
            // PROS-05
            $this->pros('PROSEDUR PENERIMAAN TAMU', 'PAH-HKS-PROS-05', '10 September 2025', '01'),
            $this->form('FORMULIR BUKU KUNJUNGAN', 'PAH-HKS-FORM-05-01', '10 September 2025', '01'),
            $this->form('FORMULIR BUKU TAMU', 'PAH-HKS-FORM-05-02', '10 September 2025', '01'),
            // PROS-06
            $this->pros('PROSEDUR SPMB', 'PAH-HUMAS-PROS-06', '10 September 2025', '01'),
            $this->form('SK PANITIA PPDB', 'PAH-HUMAS-FORM-06-01', '10 September 2025', '01'),
            $this->form('FORMULIR PENDAFTARAN SPMB', 'PAH-HUMAS-FORM-06-02', '10 September 2025', '01'),
            $this->form('FORMULIR DAFTAR KELULUSAN PESERTA DIDIK BARU', 'PAH-HUMAS-FORM-06-03', '10 September 2025', '01'),
            // PROS-07
            $this->pros('PROSEDUR PENGAJUAN CUTI', 'PAH-HUMAS-PROS-07', '10 September 2025', '01'),
            $this->form('FORMULIR CUTI GTK', 'PAH-HUMAS-FORM-07-01', '10 September 2025', '01'),
            $this->form('DAFTAR GTK YANG MENGAJUKAN CUTI', 'PAH-HUMAS-FORM-07-02', '10 September 2025', '01'),
            // PROS-08
            $this->pros('PROSEDUR PEMBERIAN SP & PHK', 'PAH-HUMAS-PROS-08', '10 September 2025', '01'),
            $this->form('FORMULIR SURAT PERINGATAN', 'PAH-HUMAS-FORM-08-01', '10 September 2025', '01'),
            $this->form('DAFTAR PELANGGARAN GTK', 'PAH-HUMAS-FORM-08-02', '10 September 2025', '01'),
            $this->form('SP 3 DAN PHK', 'PAH-HUMAS-FORM-08-03', '10 September 2025', '01'),
            // PROS-09
            $this->pros('PROSEDUR PHK', 'PAH-HUMAS-PROS-09', '10 September 2025', '01'),
            $this->form('SURAT PEMANGGILAN PHK', 'PAH-HUMAS-FORM-09-01', '10 September 2025', '01'),
            $this->form('SURAT KEPUTUSAN PHK', 'PAH-HUMAS-FORM-09-02', '10 September 2025', '01'),
            // PROS-10
            $this->pros('PROSEDUR PENINGKATAN KOMPETENSI GTK', 'PAH-HUMAS-PROS-10', '10 September 2025', '01'),
            $this->form('FORMULIR MATRIKS KOMPETENSI GTK', 'PAH-HUMAS-FORM-10-01', '10 September 2025', '01'),
            $this->form('FORMULIR KUALIFIKASI JABATAN', 'PAH-HUMAS-FORM-10-02', '10 September 2025', '01'),
            $this->form('FORMULIR LAPORAN PELAKSANAAN PELATIHAN', 'PAH-HUMAS-FORM-10-03', '10 September 2025', '01'),
            $this->form('FORMULIR SERAH TERIMA JOBDESK', 'PAH-HUMAS-FORM-10-04', '10 September 2025', '01'),
            // PROS-11
            $this->pros('PROSEDUR SELEKSI SERTIFIKASI GURU NON DAPODIK / EMIS', 'PAH-HUMAS-PROS-11', '10 September 2025', '01'),
            $this->form('FORMULIR KRITERIA PESERTA SERTIFIKASI', 'PAH-HUMAS-FORM-11-01', '10 September 2025', '01'),
            $this->form('FORMULIR PERSYARATAN ADMINISTRASI SERTIFIKASI', 'PAH-HUMAS-FORM-11-02', '10 September 2025', '01'),
            $this->form('FORMULIR INDIKATOR PENILAIAN SERTIFIKASI', 'PAH-HUMAS-FORM-11-03', '10 September 2025', '01'),
            // PROS-12
            $this->pros('PROSEDUR MUTASI', 'PAH-HUMAS-PROS-12', '10 September 2025', '01'),
            $this->form('FORMULIR PENGAJUAN MUTASI', 'PAH-HUMAS-FORM-12-01', '10 September 2025', '01'),
            $this->form('SK PROMOSI', 'PAH-HUMAS-FORM-12-02', '10 September 2025', '01'),
            $this->form('SK DEMOSI', 'PAH-HUMAS-FORM-12-03', '10 September 2025', '01'),
            $this->form('SK MUTASI', 'PAH-HUMAS-FORM-12-04', '10 September 2025', '01'),
            // PROS-13
            $this->pros('PROSEDUR PENGELOLAAN SURAT MENYURAT', 'PAH-HUMAS-PROS-13', '10 September 2025', '01'),
            $this->form('SURAT KEPUTUSAN', 'PAH-HUMAS-FORM-13-01', '10 September 2025', '01'),
            $this->form('SURAT UNDANGAN', 'PAH-HUMAS-FORM-13-02', '10 September 2025', '01'),
            $this->form('SURAT PERMOHONAN', 'PAH-HUMAS-FORM-13-03', '10 September 2025', '01'),
            $this->form('SURAT PEMBERITAHUAN', 'PAH-HUMAS-FORM-13-04', '10 September 2025', '01'),
            $this->form('SURAT PENGANTAR', 'PAH-HUMAS-FORM-13-05', '10 September 2025', '01'),
            $this->form('SURAT TUGAS', 'PAH-HUMAS-FORM-13-06', '10 September 2025', '01'),
            $this->form('SURAT KETERANGAN', 'PAH-HUMAS-FORM-13-07', '10 September 2025', '01'),
            $this->form('SURAT REKOMENDASI', 'PAH-HUMAS-FORM-13-08', '10 September 2025', '01'),
            $this->form('SURAT PERINGATAN', 'PAH-HUMAS-FORM-13-11', '10 September 2025', '01'),
            $this->form('SURAT PHK', 'PAH-HUMAS-FORM-13-12', '10 September 2025', '01'),
            $this->form('SURAT KUASA', 'PAH-HUMAS-FORM-13-13', '10 September 2025', '01'),
            $this->form('SURAT EDARAN', 'PAH-HUMAS-FORM-13-14', '10 September 2025', '01'),
            $this->form('FORMULIR BUKU EKSPEDISI', 'PAH-HUMAS-FORM-13-15', '10 September 2025', '01'),
            $this->form('SURAT DISPOSISI', 'PAH-HUMAS-FORM-13-16', '10 September 2025', '01'),
            $this->form('FORMULIR BUKU AGENDA SURAT KELUAR', 'PAH-HUMAS-FORM-13-17', '10 September 2025', '01'),
            $this->form('FORMULIR BUKU AGENDA SURAT MASUK', 'PAH-HUMAS-FORM-13-18', '10 September 2025', '01'),
            // PROS-14
            $this->pros('PROSEDUR PRESENSI GTK', 'PAH-HUMAS-PROS-14', '10 September 2025', '01'),
        ];
    }
}
