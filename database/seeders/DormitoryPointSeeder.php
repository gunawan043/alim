<?php

namespace Database\Seeders;

use App\Models\DormitoryPointCatalog;
use App\Models\DormitoryPointRule;
use Illuminate\Database\Seeder;

class DormitoryPointSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Binaan Ringan', 1, 14, 'Pembinaan oleh wali kamar', false, ['wali_kamar'], 1],
            ['Binaan Sedang', 15, 49, 'Panggilan wali dan pembinaan asrama', false, ['kepala_asrama'], 2],
            ['Peringatan Berat', 50, 100, 'Surat peringatan / rekomendasi skorsing', true, ['kepala_asrama'], 3],
            ['Sangat Berat', 101, null, 'Rekomendasi dikeluarkan dari pesantren', true, ['kepala_asrama', 'superadmin'], 4],
        ] as [$name, $min, $max, $action, $approval, $roles, $order]) {
            DormitoryPointRule::updateOrCreate(['name' => $name], [
                'min_points' => $min, 'max_points' => $max, 'action' => $action,
                'requires_approval' => $approval, 'notify_roles' => $roles,
                'is_active' => true, 'sort_order' => $order,
            ]);
        }

        /*
         * Daftar resmi pelanggaran dan poin (101–415).
         * Format: [kode, nama, kategori, poin_min, poin_max, catatan].
         * Poin_max null berarti tidak ada batas angka (mis. "50 s/d dikeluarkan").
         */
        $violations = [
            // Kedisiplinan
            ['101', 'Datang terlambat ke kelas 3x', 'kedisiplinan', 3, 3, null],
            ['102', 'Tidak menghadiri liqo maftuh/hiwar', 'kedisiplinan', 5, 5, null],
            ['103', 'Menyalahgunakan perizinan', 'kedisiplinan', 5, 5, null],
            ['104', 'Terkunci di kamar saat kegiatan pembelajaran', 'kedisiplinan', 5, 5, null],
            ['105', 'Masuk kamar saat kegiatan pembelajaran tanpa izin', 'kedisiplinan', 5, 5, null],
            ['106', 'Melampaui batas waktu perizinan (dalam/luar kota)', 'kedisiplinan', 10, 10, '10 poin per hari'],
            ['107', 'Melampaui batas waktu perizinan liburan', 'kedisiplinan', 50, null, '50 poin + 10 poin per hari'],
            ['108', 'Tidak menetap di masjid pada jam kajian/murojaah/tahfizh/ekskul', 'kedisiplinan', 5, 5, null],
            ['109', 'Tidak mengikuti acara yang diselenggarakan OSIP/Mudabbir', 'kedisiplinan', 5, 5, null],
            ['110', 'Tidak berada di kamar pada jam istirahat malam pukul 22.30', 'kedisiplinan', 5, 5, null],
            ['111', 'Keluar dari lingkungan pesantren tanpa izin', 'kedisiplinan', 25, 50, null],
            ['112', 'Bermalam di luar lingkungan pesantren tanpa izin', 'kedisiplinan', 30, 50, null],
            ['113', 'Tidak mengikuti kegiatan pesantren tanpa izin', 'kedisiplinan', 20, 20, null],
            ['114', 'Terlambat shalat fardhu berjamaah di masjid (3x)', 'kedisiplinan', 5, 5, null],
            ['115', 'Tidak ikut shalat fardhu berjamaah di masjid', 'kedisiplinan', 5, 10, null],

            // Etika, kesusilaan, dan perkelahian
            ['201', 'Masuk ruang guru atau kantor tanpa izin', 'etika', 10, 10, null],
            ['202', 'Menggunakan sarana prasarana tanpa izin', 'etika', 10, 10, null],
            ['203', 'Menyalahgunakan sarana prasarana', 'etika', 15, 15, null],
            ['204', 'Mengadakan kegiatan di dalam atau di luar pesantren tanpa izin', 'etika', 30, 30, null],
            ['205', 'Membuat kegaduhan atau keributan', 'etika', 5, 20, null],
            ['206', 'Memanjangkan, mewarnai, dan menyemir rambut (khusus putra)', 'etika', 5, 20, null],
            ['207', 'Potongan rambut yang menyalahi syariat', 'etika', 25, 25, null],
            ['208', 'Memanjangkan kuku', 'etika', 5, 5, null],
            ['209', 'Tidak memakai seragam yang telah ditentukan', 'etika', 5, 5, null],
            ['210', 'Menambah atau mengurangi atribut seragam', 'etika', 5, 5, null],
            ['211', 'Memakai seragam santri lain', 'etika', 5, 5, null],
            ['212', "Berpakaian tidak layak atau tidak memakai peci saat shalat berjamaah", 'etika', 5, 5, null],
            ['213', 'Berbohong', 'etika', 25, 25, null],
            ['214', 'Menggunakan barang orang lain tanpa izin', 'etika', 5, 25, null],
            ['215', 'Musik (bermain atau mendengarkan), bernyanyi, berjoget/dancing', 'etika', 10, 30, null],
            ['216', 'Menonton TV, VCD/DVD film secara ilegal', 'etika', 10, 30, null],
            ['217', 'Menonton di bioskop dan sejenisnya', 'etika', 30, 30, null],
            ['218', "Chatting dan surat menyurat yang tidak syar'i", 'etika', 25, 25, null],
            ['219', 'Mengeluarkan kata kotor atau ejekan dalam bentuk lisan, isyarat, atau tulisan', 'etika', 25, 25, null],
            ['220', 'Berpakaian tidak islami (isbal dan yang sejenisnya)', 'etika', 25, 25, null],
            ['221', 'Memakai aksesoris identik orang-orang fasik', 'etika', 25, 25, null],
            ['222', 'Berbuat tidak sopan di lingkungan pondok pesantren', 'etika', 10, 10, null],
            ['223', 'Menyalahgunakan perizinan ke luar pondok pesantren', 'etika', 30, 30, null],
            ['224', 'Masuk pondok putra/putri tanpa izin', 'etika', 50, 50, null],
            ['225', 'Mengintimidasi atau mengancam santri lain', 'etika', 50, 50, null],
            ['226', 'Menolak sanksi', 'etika', 100, 100, 'Skorsing'],
            ['227', 'Pacaran', 'etika', 50, 200, 'Skorsing'],
            ['228', 'Mengadu domba', 'etika', 50, 50, null],
            ['229', 'Perkelahian dengan terencana', 'etika', 100, 100, null],
            ['230', 'Perkelahian tidak terencana (emosi spontan)', 'etika', 50, 50, null],
            ['231', 'Sumpah dan/atau persaksian palsu', 'etika', 50, 50, null],
            ['232', 'Menonton film porno (BF)', 'etika', 80, 80, 'Skorsing'],
            ['233', 'Berjualan di pondok', 'etika', 5, 5, null],
            ['234', 'Membuang sampah sembarangan', 'etika', 5, 5, null],
            ['235', 'Makan di kelas atau kamar', 'etika', 5, 5, null],
            ['236', 'Menonton semua jenis film yang bertentangan dengan akidah dan akhlak islam', 'etika', 50, 150, null],
            ['237', 'Sengaja meninggalkan sholat fardhu', 'etika', 50, 50, null],
            ['238', 'Diantar/dijemput oleh yang bukan mahrom', 'etika', 50, 100, null],
            ['239', 'Mencemarkan dan/atau melecehkan nama baik pondok pesantren dan PTK secara lisan atau tulisan', 'etika', 100, 100, 'Skorsing'],
            ['240', 'Mengancam PTK', 'etika', 100, 100, 'Skorsing'],
            ['241', 'Menentang peraturan pesantren', 'etika', 100, 100, 'Skorsing'],
            ['242', 'Gejala homoseksual/lesbian', 'etika', 100, 100, 'Skorsing'],
            ['243', 'Berzina, homoseksual/lesbian', 'etika', 1000, null, 'Dikeluarkan'],
            ['244', 'Melakukan pelanggaran tata tertib OSIPP', 'etika', 10, 10, null],
            ['245', 'Mencorat-coret atau merusak peraturan atau administrasi mudabbir atau organisasi santri', 'etika', 10, 10, null],
            ['246', 'Mencorat-coret inventaris dan fasilitas umum ponpes', 'etika', 10, 10, null],
            ['247', 'Mengubah atau memalsukan surat izin (ttd/stempel)', 'etika', 25, 25, null],
            ['248', '10 kali menggunakan bahasa daerah', 'etika', 30, 30, null],
            ['249', 'Tidak menggunakan bahasa arab atau bahasa inggris', 'etika', 10, 10, '10 poin per pekan'],
            ['250', 'Tidak menggunakan seprei di ranjang asrama', 'etika', 5, 5, null],
            ['251', 'Tidak menggunakan kopiah saat sholat', 'etika', 5, 5, null],
            ['252', 'Menyalahgunakan perizinan di luar kelas', 'etika', 5, 5, null],
            ['253', 'Tidak membawa buku, alat-alat sekolah/kajian', 'etika', 5, 5, null],
            ['254', 'Terbukti mencontek atau memberi contekan ketika ujian', 'etika', 100, 100, 'Skorsing'],
            ['255', 'Memalsukan administrasi pesantren', 'etika', 25, 25, null],
            ['256', 'Bermain bukan pada tempat/waktu yang ditentukan', 'etika', 5, 5, null],
            ['257', 'Melompati gerbang besi pembatas asrama', 'etika', 10, 10, null],
            ['258', 'Menjemur pakaian bukan pada waktu/tempat yang ditentukan', 'etika', 5, 5, null],
            ['259', 'Makan di luar tempat yang telah ditentukan', 'etika', 5, 5, null],
            ['260', 'Memakai alat kecantikan/kosmetik seperti lipstik, bedak, eyeliner, eyeshadow, softlens, maskara, parfum, dll.', 'etika', 20, 20, null],

            // Permainan dan barang/benda terlarang
            ['301', 'Membawa, menyimpan, menyewa, memiliki, membeli, dan membuat gambar artis', 'barang_terlarang', 10, 10, null],
            ['302', 'Membawa HP dalam bentuk atau jenis apapun (termasuk smartwatch dengan sim card atau wifi) ke lingkungan PAH', 'barang_terlarang', 100, 100, 'Skorsing; poin permanen'],
            ['303', 'Meminjam, menyimpan, atau menggunakan HP dalam bentuk atau jenis apapun', 'barang_terlarang', 25, 25, null],
            ['304', 'Penyalahgunaan HP atau alat elektronik lainnya untuk hal-hal yang dilarang di PAH', 'barang_terlarang', 25, 25, null],
            ['305', 'Membawa smartwatch yang berisi aplikasi terlarang (musik, game, radio, dll.)', 'barang_terlarang', 25, 25, null],
            ['306', 'Membawa, menyimpan, memiliki, membeli, atau mengisap rokok dalam bentuk apapun', 'barang_terlarang', 75, 75, 'Skorsing'],
            ['307', 'Permainan monopoli, karambol, catur, kartu, dan sejenisnya', 'barang_terlarang', 10, 10, null],
            ['308', "Menyimpan koran/majalah, dll. (tidak syar'i)", 'barang_terlarang', 10, 10, null],
            ['309', 'Membeli, membawa, menyimpan, menyewa, dan membaca komik atau yang sejenisnya', 'barang_terlarang', 10, 10, null],
            ['310', 'Membawa peralatan elektronik, MP3, MP4, dan sejenisnya', 'barang_terlarang', 25, 25, null],
            ['311', 'Membawa, menyimpan, memiliki, membeli, atau menggunakan petasan dan sejenisnya', 'barang_terlarang', 20, 20, null],
            ['312', 'Menyimpan media atau gambar porno, semi porno, film porno, dan situs porno', 'barang_terlarang', 30, 50, null],
            ['313', 'Menyimpan dan membawa senjata tajam atau senjata api', 'barang_terlarang', 50, 100, null],
            ['314', 'Membawa, menyimpan, atau menggunakan barang terlarang', 'barang_terlarang', 50, 50, null],
            ['315', 'Menyewa dan meminjam motor atau mobil', 'barang_terlarang', 50, 100, null],
            ['316', 'Membawa, menyimpan, dan memiliki alat kecantikan', 'barang_terlarang', 10, 10, null],

            // Tindakan pengerusakan dan kriminal
            ['401', 'Merusak atau menghilangkan sarana dan prasarana pesantren', 'kriminal', 10, 50, 'Ganti rugi'],
            ['402', 'Melakukan tindakan mencederai orang lain (pemukulan dan sejenisnya)', 'kriminal', 50, null, '50 s/d dikeluarkan'],
            ['403', 'Membuat oplosan yang membahayakan', 'kriminal', 100, 100, null],
            ['404', 'Mengedarkan atau mempertontonkan gambar/video porno atau semi porno', 'kriminal', 50, 100, null],
            ['405', 'Pemukulan terhadap saksi pelanggaran', 'kriminal', 100, 100, null],
            ['406', 'Mencuri di dalam atau di luar lingkungan pesantren', 'kriminal', 50, null, '50 s/d dikeluarkan'],
            ['407', 'Melakukan tindak pemerasan terhadap orang lain', 'kriminal', 50, null, '50 s/d dikeluarkan'],
            ['408', 'Berjudi', 'kriminal', 50, null, '50 s/d dikeluarkan'],
            ['409', 'Mengkonsumsi miras, narkoba, psikotropika, atau sejenisnya di dalam atau di luar lingkungan pesantren', 'kriminal', 1000, null, 'Dikeluarkan'],
            ['410', 'Penganiayaan yang menyebabkan cidera', 'kriminal', 50, null, '50 s/d dikeluarkan'],
            ['411', 'Terlibat tindak kriminal yang ditangani kepolisian', 'kriminal', 1000, null, 'Dikeluarkan'],
            ['412', 'Pencemaran nama baik agama, lembaga, dan GTK', 'kriminal', 50, null, '50 s/d dikeluarkan'],
            ['415', 'Menganiaya GTK', 'kriminal', 1000, null, 'Dikeluarkan'],
        ];

        $permanentCodes = ['243', '302', '409', '411', '415'];

        foreach ($violations as [$code, $name, $category, $min, $max, $note]) {
            $severity = match (true) {
                $min <= 14 => 'ringan',
                $min <= 49 => 'sedang',
                $min <= 100 => 'berat',
                default => 'sangat_berat',
            };

            DormitoryPointCatalog::updateOrCreate(['code' => $code], [
                'kind' => 'violation', 'name' => $name, 'category' => $category,
                'severity' => $severity,
                'points_min' => $min, 'points_max' => $max,
                'is_permanent' => in_array($code, $permanentCodes, true),
                'is_active' => true, 'sort_order' => (int) $code,
                'description' => $note,
            ]);
        }

        /*
         * Daftar resmi reward. Format: [kode, nama, kategori, sub, poin, catatan].
         * Poin 0 dipakai hanya untuk butir yang tidak mencantumkan angka pada daftar resmi.
         */
        $rewards = [
            // Reward prestasi — akademik
            ['R-01', 'Peringkat 1, 2, dan 3', 'prestasi', 'akademik', 25, null],
            ['R-02', 'Peringkat 4 s/d 10', 'prestasi', 'akademik', 15, null],
            ['R-03', 'Juara classmeeting', 'prestasi', 'akademik', 5, null],
            ['R-04', 'Tanpa alpha, izin, skorsing, dan sakit', 'prestasi', 'akademik', 20, null],
            ['R-05', 'Nilai tertinggi per kelas', 'prestasi', 'akademik', 30, null],
            ['R-06', 'Juara umum sekolah/madrasah', 'prestasi', 'akademik', 50, null],
            ['R-07', 'Lomba mewakili PAH — Kecamatan', 'prestasi', 'akademik', 30, null],
            ['R-08', 'Lomba mewakili PAH — Kabupaten', 'prestasi', 'akademik', 40, null],
            ['R-09', 'Lomba mewakili PAH — Provinsi', 'prestasi', 'akademik', 50, null],
            ['R-10', 'Lomba mewakili PAH — Nasional', 'prestasi', 'akademik', 60, null],
            ['R-11', 'Lomba mewakili PAH — Regional/Internasional', 'prestasi', 'akademik', 100, null],
            ['R-12', 'Juara lomba mewakili sekolah/PAH — Kecamatan', 'prestasi', 'akademik', 30, null],
            ['R-13', 'Juara lomba mewakili sekolah/PAH — Kabupaten', 'prestasi', 'akademik', 50, null],
            ['R-14', 'Juara lomba mewakili sekolah/PAH — Provinsi', 'prestasi', 'akademik', 70, null],
            ['R-15', 'Juara lomba mewakili sekolah/PAH — Nasional', 'prestasi', 'akademik', 100, null],
            ['R-16', 'Juara lomba mewakili sekolah/PAH — Regional/Internasional', 'prestasi', 'akademik', 150, null],
            ['R-17', 'Nilai tertinggi UN', 'prestasi', 'akademik', 0, 'Poin belum ditetapkan pada daftar resmi'],
            ['R-18', 'Juara lomba intern ponpes', 'prestasi', 'akademik', 0, 'Poin belum ditetapkan pada daftar resmi'],
            ['R-19', 'Santri teladan berbahasa', 'prestasi', 'akademik', 0, 'Poin belum ditetapkan pada daftar resmi'],
            ['R-20', 'Hafal quran melebihi target', 'prestasi', 'tahfidz', 20, '20 poin per juz'],
            ['R-21', 'Hafal hadits melebihi target', 'prestasi', 'tahfidz', 5, '5 poin per 10 hadits'],

            // Reward prestasi — organisasi
            ['R-22', 'Ketua kamar', 'prestasi', 'organisasi', 20, null],
            ['R-23', 'Ketua kelas', 'prestasi', 'organisasi', 20, null],
            ['R-24', 'Ketua OSIP', 'prestasi', 'organisasi', 50, null],
            ['R-25', 'Pengurus OSIP (tidak pernah diganti)', 'prestasi', 'organisasi', 30, null],
            ['R-26', 'Lomba santri teladan per kelas', 'prestasi', 'organisasi', 20, null],
            ['R-27', 'Lomba santri teladan asrama', 'prestasi', 'organisasi', 20, null],
            ['R-28', 'Lomba kelas terbersih', 'prestasi', 'organisasi', 20, null],
            ['R-29', 'Lomba kamar terbersih', 'prestasi', 'organisasi', 20, null],
            ['R-30', 'Lomba lain antar kelas (terbaik)', 'prestasi', 'organisasi', 20, null],

            // Reward sikap
            ['R-31', 'Reward sikap interpersonal (kesadaran mengakui kesalahan, tanggung jawab, kedisiplinan, kebersihan diri, keteladanan)', 'sikap', 'interpersonal', 25, 'Maksimal 25 poin per semester'],
            ['R-32', 'Reward sikap sosial (kerja sama, mediator, fasilitator, inspirator, empati, simpati)', 'sikap', 'sosial', 25, 'Maksimal 25 poin per semester'],
        ];

        foreach ($rewards as [$code, $name, $category, $sub, $points, $note]) {
            DormitoryPointCatalog::updateOrCreate(['code' => $code], [
                'kind' => 'reward', 'name' => $name, 'category' => $category,
                'severity' => $sub, 'points_min' => $points, 'points_max' => $points,
                'is_permanent' => false, 'is_active' => true,
                'sort_order' => (int) substr($code, 2),
                'description' => $note,
            ]);
        }
    }
}
