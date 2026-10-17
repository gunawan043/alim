<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SupervisiInstrumen;
use App\Models\SupervisiInstrumenBagian;
use App\Models\SupervisiInstrumenItem;
use Illuminate\Database\Seeder;

/**
 * Instrumen Supervisi Pembelajaran bawaan (template global, school_id = null):
 * - Guru Umum
 * - Guru Agama
 *
 * Idempoten: aman dijalankan berulang; posisi bagian/item diperbarui, tidak digandakan.
 */
class SupervisiInstrumenSeeder extends Seeder
{
    public function run(): void
    {
        $umum = $this->instrumen(
            jenis: SupervisiInstrumen::JENIS_GURU_UMUM,
            nama: 'Instrumen Supervisi Pembelajaran — Guru Umum',
            deskripsi: 'Instrumen supervisi pembelajaran untuk guru umum (persiapan, kegiatan pembelajaran, penutup).',
        );

        $this->bagian($umum, 'I', 'PERSIAPAN', 1, [
            ['Program Tahunan'],
            ['Program Semester'],
            ['Silabus'],
            ['KKM untuk KD yang dibahas'],
            ['Rencana Pelaksanaan Pembelajaran'],
            ['Buku nilai memuat semua tagihan yang telah dilaksanakan'],
        ]);

        $this->bagian($umum, 'IIA', 'PENDAHULUAN', 2, [
            ['Kesiapan alat bantu & media Pembelajaran'],
            ['Motivasi'],
            ['Apersepsi'],
            ['Kejelasan kompetensi dasar/indikator'],
            ['Kesiapan bahan ajar'],
        ]);

        $this->bagian($umum, 'IIB', 'KEGIATAN POKOK', 3, [
            ['Penguasaan materi'],
            ['Pengelolaan kelas'],
            ['Pengeloaan waktu'],
            ['Metode/pendekatan yang bervariasi'],
            ['Penggunaan alat bantu/media pembelajaran'],
            ['Peran guru sebagai fasilitator/bimbingan yang'],
            ['Teknik belajar'],
            ['Penggunaan papan tulis'],
            ['Interaksi guru – peserta didik'],
            ['Interaksi antarpeserta didik'],
            ['Aktivitas peserta didik:', [
                'Menggali informasi dari berbagai sumber',
                'Mengelolah informasi',
                'Melakukan penelitian/memecahkan masalah',
                'Berkomunikasi lisan/tertulis (mempresentasikan hasil/pidato/mengarang, dsb',
                'Mengajukan pertanyaan yang berbobot/ide kreatif',
                'Menghubungkan materi pembelajaran dengan budi pekerti/teknologi/kehidupan sehari-hari lingkungan',
                'Mengambil keputusan/menarik kesimpulan',
            ]],
            ['Sikap/minat peserta didik dalam pembelajaran:', [
                'Kehadiran',
                'Membawa buku yang relevan',
                'Buku catatan rapi',
            ]],
            ['Pencapaian kompetensi dasar dan atau indikator'],
        ]);

        $this->bagian($umum, 'IIC', 'PENUTUP', 4, [
            ['Siswa membuat rangkuman/kesimpulan dibimbing guru'],
            ['Membersihkan alat/bahan yang selesai digunakan'],
            ['Tugas untuk pertemuan berikutnya'],
        ]);

        $agama = $this->instrumen(
            jenis: SupervisiInstrumen::JENIS_GURU_AGAMA,
            nama: 'Instrumen Supervisi Pembelajaran — Guru Agama',
            deskripsi: 'Instrumen supervisi pembelajaran untuk guru agama (persiapan, kegiatan pembelajaran, penutup).',
        );

        $this->bagian($agama, 'I', 'PERSIAPAN', 1, [
            ['Program Tahunan'],
            ['Program Semester'],
            ['Rencana Pelaksanaan Pembelajaran'],
            ['Buku nilai memuat semua tagihan yang telah dilaksanakan'],
            ['Kepribadian dan Kerapihan'],
        ]);

        $this->bagian($agama, 'IIA', 'PENDAHULUAN', 2, [
            ['Menyiapkan fisik dan psikis peserta didik dengan menyapa dan memberi salam.'],
            ['Menyampaikan rencana kegiatan baik, individual, kerja kelompok, dan melakukan observasi.'],
            ['Menyampaikan manfaat materi pembelajaran'],
            ['Mengaitkan materi dengan materi pembelajaran sebelumnya'],
            ['Menyampaikan Tujuan Pembelajaran'],
        ]);

        $this->bagian($agama, 'IIB', 'KEGIATAN POKOK', 3, [
            ['Penguasaan materi'],
            ['Pengelolaan kelas'],
            ['Pengeloaan waktu'],
            ['Pendekatan pembelajaran yang bervariasi'],
            ['Penggunaan media pembelajaran'],
            ['Interaksi guru dengan peserta didik'],
            ['Interaksi antar peserta didik'],
            ['Melakukan penilaian sesuai dengan tujuan pembelajaran'],
            ['Sikap/minat peserta didik dalam pembelajaran:'],
            ['Menggunakan Lughatutawassul (50% MA/SMA, 30% SMP dan 25% SD)'],
            ['Menyajikan materi secara sistematis (dari sulit ke mudah, dan abstrak ke konkrit )'],
        ]);

        $this->bagian($agama, 'IIC', 'PENUTUP', 4, [
            ['Memfasilitasi dan membimbing peserta didik merangkum materi pelajaran.'],
            ['Melaksanakan asesmen akhir sebagai penguatan'],
            ['Melaksanakan tindak lanjut dengan memberikan arahan kegiatan berikutnya dan tugas perbaikan dan pengayaan secara individu atau kelompok.'],
            ['Menyampaikan pesan moral / nasehat'],
        ]);
    }

    private function instrumen(string $jenis, string $nama, string $deskripsi): SupervisiInstrumen
    {
        return SupervisiInstrumen::withTrashed()->updateOrCreate(
            ['school_id' => null, 'jenis' => $jenis],
            ['nama' => $nama, 'deskripsi' => $deskripsi, 'is_active' => true, 'deleted_at' => null],
        );
    }

    /**
     * @param  array<int, array{0: string, 1?: array<int, string>}>  $items
     */
    private function bagian(SupervisiInstrumen $instrumen, string $kode, string $nama, int $urutan, array $items): void
    {
        $bagian = SupervisiInstrumenBagian::updateOrCreate(
            ['instrumen_id' => $instrumen->id, 'kode' => $kode],
            ['nama' => $nama, 'urutan' => $urutan, 'is_active' => true],
        );

        foreach (array_values($items) as $i => $row) {
            $teks = $row[0];
            $subs = $row[1] ?? [];
            $posisi = $i + 1;

            $parent = SupervisiInstrumenItem::updateOrCreate(
                ['bagian_id' => $bagian->id, 'parent_item_id' => null, 'urutan' => $posisi],
                ['teks' => $teks, 'bobot' => 4, 'butuh_kondisi' => true, 'is_active' => true],
            );

            foreach (array_values($subs) as $j => $subTeks) {
                SupervisiInstrumenItem::updateOrCreate(
                    ['bagian_id' => $bagian->id, 'parent_item_id' => $parent->id, 'urutan' => $j + 1],
                    ['teks' => $subTeks, 'bobot' => 4, 'butuh_kondisi' => true, 'is_active' => true],
                );
            }
        }
    }
}
