<?php

namespace Database\Seeders;

use App\Models\ChecklistTemplate;
use Illuminate\Database\Seeder;

class SarprasChecklistTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'code' => 'CHK-BGN-001',
                'name' => 'Pemeliharaan Gedung / Bangunan',
                'slug' => 'bangunan',
                'items' => [
                    ['Struktur Bangunan', 'Pondasi tidak turun', 'Tidak turun/ambles', 'Visual', 'Per 6 bulan'],
                    ['Struktur Bangunan', 'Pondasi tidak retak', 'Tidak retak', 'Visual', 'Per 6 bulan'],
                    ['Struktur Bangunan', 'Kolom tidak retak', 'Tidak retak', 'Visual', 'Per 6 bulan'],
                    ['Struktur Bangunan', 'Kolom tidak miring', 'Tidak miring', 'Visual', 'Per 6 bulan'],
                    ['Struktur Bangunan', 'Kolom tidak keropos', 'Tidak keropos', 'Visual', 'Per 6 bulan'],
                    ['Struktur Bangunan', 'Balok tidak melengkung', 'Tidak melengkung ke bawah', 'Visual', 'Per 6 bulan'],
                    ['Struktur Bangunan', 'Dak tidak bocor', 'Tidak ada kebocoran', 'Visual', 'Per 6 bulan'],
                    ['Arsitektur', 'Lantai tidak ambles', 'Tidak ambles/turun', 'Visual', 'Bulanan'],
                    ['Arsitektur', 'Keramik tidak pecah', 'Keramik utuh', 'Visual', 'Bulanan'],
                    ['Arsitektur', 'Dinding tidak retak besar', 'Tidak retak besar', 'Visual', 'Bulanan'],
                    ['Arsitektur', 'Dinding tidak berjamur', 'Bebas jamur', 'Visual', 'Bulanan'],
                    ['Arsitektur', 'Atap tidak bocor', 'Tidak bocor', 'Visual', 'Per 6 bulan'],
                    ['Arsitektur', 'Rangka atap tidak lapuk', 'Tidak lapuk', 'Visual', 'Per 6 bulan'],
                    ['Arsitektur', 'Talang tidak tersumbat', 'Air mengalir lancar', 'Visual', 'Bulanan'],
                    ['Arsitektur', 'Plafon tidak berlubang', 'Tidak berlubang', 'Visual', 'Bulanan'],
                    ['Arsitektur', 'Pintu lancar buka tutup', 'Lancar, tidak macet', 'Tes fungsi', 'Bulanan'],
                    ['Arsitektur', 'Engsel pintu tidak longgar', 'Kencang dan aman', 'Visual', 'Bulanan'],
                    ['Arsitektur', 'Kunci pintu berfungsi', 'Berfungsi normal', 'Tes fungsi', 'Bulanan'],
                    ['Arsitektur', 'Jendela lancar buka tutup', 'Lancar, tidak macet', 'Tes fungsi', 'Bulanan'],
                    ['Arsitektur', 'Kaca jendela tidak pecah', 'Kaca utuh', 'Visual', 'Bulanan'],
                    ['Arsitektur', 'Grendel jendela lengkap', 'Lengkap dan berfungsi', 'Visual', 'Bulanan'],
                    ['Arsitektur', 'Cat tidak mengelupas', 'Cat utuh', 'Visual', 'Tahunan'],
                    ['Arsitektur', 'Cat tidak berjamur', 'Bebas jamur', 'Visual', 'Tahunan'],
                ],
            ],
            [
                'code' => 'CHK-AC-001',
                'name' => 'Pemeliharaan AC',
                'slug' => 'ac',
                'items' => [
                    ['Indoor', 'Filter AC bersih', 'Bebas debu', 'Visual', 'Bulanan'],
                    ['Indoor', 'Evaporator bersih', 'Tidak berdebu', 'Visual', 'Per 3 bulan'],
                    ['Indoor', 'Drainase air lancar', 'Air tidak menetes/menggenang', 'Visual', 'Bulanan'],
                    ['Indoor', 'Remote dan mode berfungsi', 'Semua mode normal', 'Tes fungsi', 'Bulanan'],
                    ['Outdoor', 'Kondensor bersih', 'Bebas debu/daun', 'Visual', 'Per 3 bulan'],
                    ['Outdoor', 'Tekanan freon normal', 'Sesuai spesifikasi unit', 'Alat ukur', 'Per 6 bulan'],
                    ['Outdoor', 'Pipa dan isolasi tidak bocor', 'Tidak ada kebocoran', 'Visual', 'Per 3 bulan'],
                    ['Outdoor', 'Suara kompresor halus', 'Tidak kasar/berisik', 'Pendengaran', 'Bulanan'],
                    ['Kelistrikan', 'Tegangan listrik normal', '220V ±10%', 'Alat ukur', 'Per 3 bulan'],
                    ['Kelistrikan', 'Kabel dan MCB aman', 'Tidak panas/terbakar', 'Visual', 'Bulanan'],
                    ['Kelistrikan', 'Jaringan grounding aman', 'Tidak longgar', 'Visual', 'Per 6 bulan'],
                    ['Kinerja', 'Suhu pendinginan sesuai', 'Sesuai setelan', 'Termometer', 'Bulanan'],
                ],
            ],
            [
                'code' => 'CHK-GSN-001',
                'name' => 'Pemeliharaan Genset',
                'slug' => 'genset',
                'items' => [
                    ['Panel & Kontrol', 'Push button dan emergency stop berfungsi', 'Berfungsi baik', 'Tes fungsi', '2 minggu'],
                    ['Panel & Kontrol', 'Panel MCB dan indikator normal', 'Listrik ON, indikator menyala', 'Pengukuran', '2 minggu'],
                    ['Panel & Kontrol', 'Dinamo starter tidak macet', 'Berfungsi baik', 'Tes fungsi', 'Bulanan'],
                    ['Mesin', 'Level oli pelumas normal', 'Antara min dan max', 'Visual', 'Mingguan'],
                    ['Mesin', 'Filter oli dan filter udara bersih', 'Tidak tersumbat', 'Visual', 'Bulanan'],
                    ['Mesin', 'BBM solar cukup', 'Indikator volume normal', 'Visual', 'Harian'],
                    ['Mesin', 'Filter solar tidak berlumpur', 'Bersih', 'Manual', 'Bulanan'],
                    ['Mesin', 'Radiator cukup cairan pendingin', 'Level normal', 'Visual', 'Mingguan'],
                    ['Mesin', 'Sistem pembuangan asap normal', 'Asap tidak pekat', 'Visual', 'Bulanan'],
                    ['Kelistrikan', 'Charger aki normal', 'Voltase pengisian normal', 'Alat ukur', 'Bulanan'],
                    ['Kelistrikan', 'Aki/baterai normal', 'Voltase sesuai', 'Alat ukur', 'Bulanan'],
                    ['Operasional', 'Genset menyala dan take over normal', 'Berfungsi saat uji', 'Tes fungsi', 'Bulanan'],
                ],
            ],
            [
                'code' => 'CHK-PMP-001',
                'name' => 'Pemeliharaan Pompa & Panel',
                'slug' => 'pompa-panel',
                'items' => [
                    ['Panel Pompa', 'Box panel bersih dan tidak karat', 'Bersih, tidak karat', 'Visual', 'Per 6 bulan'],
                    ['Panel Pompa', 'Kunci panel berfungsi', 'Berfungsi normal', 'Tes fungsi', 'Per 6 bulan'],
                    ['Panel Pompa', 'Label panel lengkap', 'Lengkap dan jelas', 'Visual', 'Per 6 bulan'],
                    ['Komponen Listrik', 'MCB normal', 'Berfungsi', 'Tes fungsi', 'Per 6 bulan'],
                    ['Komponen Listrik', 'Kontaktor dan relay normal', 'Berfungsi', 'Tes fungsi', 'Per 6 bulan'],
                    ['Komponen Listrik', 'Lampu indikator menyala', 'Menyala', 'Visual', 'Per 6 bulan'],
                    ['Komponen Listrik', 'Volt meter dan ampere meter normal', 'Terbaca normal', 'Visual', 'Per 6 bulan'],
                    ['Komponen Listrik', 'Konektor tidak longgar', 'Kencang', 'Alat', 'Per 6 bulan'],
                    ['Komponen Listrik', 'Arus beban sesuai kapasitas', 'Sesuai kapasitas', 'Alat ukur', 'Per 6 bulan'],
                    ['Fungsi Operasional', 'Mode otomatis normal', 'Berfungsi', 'Tes fungsi', 'Per 6 bulan'],
                    ['Fungsi Operasional', 'Mode manual normal', 'Berfungsi', 'Tes fungsi', 'Per 6 bulan'],
                    ['Fungsi Operasional', 'Pelampung/radar normal', 'Berjalan normal', 'Tes fungsi', 'Per 6 bulan'],
                    ['Fisik Pompa', 'Pompa tidak korosi', 'Tidak berkarat', 'Visual', 'Per 6 bulan'],
                    ['Fisik Pompa', 'Pelindung pompa tersedia', 'Lengkap', 'Visual', 'Per 6 bulan'],
                    ['Operasional Pompa', 'Pompa menyala normal', 'Menyala normal', 'Alat ukur', 'Per 6 bulan'],
                    ['Operasional Pompa', 'Suara pompa halus', 'Tidak kasar', 'Pendengaran', 'Per 6 bulan'],
                    ['Operasional Pompa', 'Debit air sesuai kapasitas', 'Sesuai kapasitas', 'Alat ukur', 'Per 6 bulan'],
                    ['Keamanan', 'Sambungan kabel tidak bocor', 'Aman', 'Visual/alat', 'Per 6 bulan'],
                    ['Keamanan', 'Seal tidak bocor', 'Tidak bocor', 'Visual', 'Per 6 bulan'],
                    ['Keamanan', 'Proteksi overload bekerja', 'Berfungsi', 'Tes fungsi', 'Per 6 bulan'],
                ],
            ],
            [
                'code' => 'CHK-LST-001',
                'name' => 'Pemeliharaan Jaringan Listrik',
                'slug' => 'jaringan-listrik',
                'items' => [
                    ['Instalasi Dalam Gedung', 'Lampu berfungsi normal', 'Menyala normal', 'Visual', 'Bulanan'],
                    ['Instalasi Dalam Gedung', 'Saklar berfungsi normal', 'Berfungsi', 'Tes fungsi', 'Bulanan'],
                    ['Instalasi Dalam Gedung', 'Stop kontak berfungsi normal', 'Berfungsi dan aman', 'Alat ukur', 'Per 6 bulan'],
                    ['Panel Listrik', 'Tegangan listrik stabil', '220V ±10%', 'Alat ukur', 'Per 6 bulan'],
                    ['Panel Listrik', 'MCB berfungsi normal', 'Tidak trip berulang', 'Tes fungsi', 'Per 6 bulan'],
                    ['Panel Listrik', 'Koneksi panel kencang', 'Tidak longgar', 'Visual', 'Per 6 bulan'],
                    ['Panel Listrik', 'Lampu indikator menyala', 'Menyala', 'Visual', 'Per 6 bulan'],
                    ['Panel Listrik', 'Ampere dan volt meter normal', 'Terbaca normal', 'Visual', 'Per 6 bulan'],
                    ['Panel Listrik', 'Kunci panel berfungsi', 'Berfungsi', 'Tes fungsi', 'Per 6 bulan'],
                    ['Panel Listrik', 'Grounding tidak putus', 'Aman', 'Visual', 'Per 6 bulan'],
                    ['Instalasi Luar Gedung', 'Lampu penerang jalan menyala', 'Berfungsi', 'Visual', 'Bulanan'],
                    ['Instalasi Luar Gedung', 'Lampu sorot dan taman menyala', 'Berfungsi', 'Visual', 'Bulanan'],
                    ['Instalasi Luar Gedung', 'Kabel tidak longgar/putus', 'Aman', 'Visual', 'Per 6 bulan'],
                ],
            ],
            [
                'code' => 'CHK-AIR-001',
                'name' => 'Pemeliharaan Air Minum',
                'slug' => 'air-minum',
                'items' => [
                    ['Sumber Air', 'Debit air cukup', 'Sesuai kebutuhan', 'Alat ukur', 'Bulanan'],
                    ['Filterisasi', 'Lampu UV aktif', 'Menyala dan masa pakai aktif', 'Visual/alat', 'Bulanan'],
                    ['Filterisasi', 'Ballast lampu UV normal', 'Berfungsi', 'Alat ukur', 'Bulanan'],
                    ['Filterisasi', 'Filter PP bersih', 'Tidak ada sedimen/lumut', 'Visual', 'Bulanan'],
                    ['Filterisasi', 'Filter GAC bersih', 'Tidak ada sedimen/lumut', 'Visual', 'Bulanan'],
                    ['Filterisasi', 'Filter CTO bersih', 'Tidak ada sedimen/lumut', 'Visual', 'Bulanan'],
                    ['Tangki', 'Tangki tidak berkarat', 'Tidak berkarat', 'Visual', 'Bulanan'],
                    ['Tangki', 'Tangki bebas lumut', 'Bersih', 'Visual', 'Bulanan'],
                    ['Media Filter', 'Tabung karbon dan silika bersih', 'Tidak berkarat/kotor', 'Visual', 'Bulanan'],
                    ['Media Filter', 'Karbon aktif normal', 'Tidak bau, media normal', 'Penciuman', 'Tahunan'],
                    ['Distribusi', 'Kran distribusi tidak rusak', 'Berfungsi', 'Tes fungsi', 'Bulanan'],
                    ['Kualitas Air', 'Uji lab kualitas air', 'Sesuai Permenkes No. 2/2023', 'Uji lab', 'Per 6 bulan'],
                ],
            ],
            [
                'code' => 'CHK-STP-001',
                'name' => 'Pemeliharaan Septic Tank',
                'slug' => 'septic-tank',
                'items' => [
                    ['Kondisi', 'Septic tank tidak penuh', 'Level normal', 'Visual', 'Per 6 bulan'],
                    ['Kondisi', 'Septic tank tidak bocor', 'Tidak ada rembesan', 'Visual', 'Per 6 bulan'],
                    ['Kondisi', 'Septic tank tidak bau', 'Tidak bau menyengat', 'Penciuman', 'Bulanan'],
                    ['Kondisi', 'Pipa inlet dan outlet lancar', 'Tidak tersumbat', 'Visual', 'Per 6 bulan'],
                    ['Kondisi', 'Tutup septic tank aman', 'Tertutup rapat', 'Visual', 'Bulanan'],
                    ['Tindakan', 'Jadwal pengurasan tercatat', 'Sesuai jadwal', 'Dokumen', 'Tahunan'],
                ],
            ],
            [
                'code' => 'CHK-KMR-001',
                'name' => 'Pemeliharaan Kamar Mandi',
                'slug' => 'kamar-mandi',
                'items' => [
                    ['Air & Sanitasi', 'Kran air berfungsi', 'Tidak bocor', 'Tes fungsi', 'Bulanan'],
                    ['Air & Sanitasi', 'Closet berfungsi', 'Lancar, tidak tersumbat', 'Tes fungsi', 'Bulanan'],
                    ['Air & Sanitasi', 'Gayung dan ember tersedia', 'Lengkap', 'Visual', 'Bulanan'],
                    ['Air & Sanitasi', 'Saluran air lancar', 'Tidak tergenang', 'Visual', 'Bulanan'],
                    ['Kelistrikan', 'Lampu menyala', 'Berfungsi', 'Visual', 'Bulanan'],
                    ['Kelistrikan', 'Fitting lampu aman', 'Tidak rusak', 'Visual', 'Bulanan'],
                    ['Kelistrikan', 'Saklar lampu berfungsi', 'Berfungsi', 'Tes fungsi', 'Bulanan'],
                    ['Bangunan', 'Pintu dan grendel berfungsi', 'Aman, tidak rusak', 'Tes fungsi', 'Bulanan'],
                    ['Bangunan', 'Dinding dan lantai bersih', 'Tidak berlumut', 'Visual', 'Harian'],
                    ['Bangunan', 'Ventilasi berfungsi', 'Sirkulasi baik', 'Visual', 'Bulanan'],
                ],
            ],
        ];

        foreach ($templates as $data) {
            $template = ChecklistTemplate::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'workflow_type' => 'maintenance',
                    'category_slug' => $data['slug'],
                    'is_active' => true,
                    'version' => 1,
                ]
            );

            if ($template->instances()->exists()) {
                continue;
            }

            $template->items()->delete();

            foreach ($data['items'] as $index => $item) {
                $template->items()->create([
                    'sequence' => $index + 1,
                    'group_label' => $item[0],
                    'label' => $item[1],
                    'standard' => $item[2],
                    'method' => $item[3],
                    'frequency' => $item[4],
                    'response_type' => 'boolean',
                    'is_required' => true,
                    'triggers_failure' => true,
                    'failure_severity' => 'medium',
                ]);
            }
        }
    }
}
