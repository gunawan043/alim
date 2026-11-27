<?php

namespace Tests\Feature;

use App\Services\DecreePositionOrdering;
use Tests\TestCase;

/**
 * Urutan GTK pada form SK mengikuti hierarki jabatan yang ketat:
 * Kepala Satuan Pendidikan sampai Tata Usaha.
 *
 * Kolom `urutan` pada master jabatan tidak dipakai sebagai sumber kebenaran
 * karena nilainya bertumpuk antarjabatan dan jenjangnya tidak berurutan.
 */
class DecreePositionOrderingTest extends TestCase
{
    private DecreePositionOrdering $ordering;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ordering = new DecreePositionOrdering;
    }

    public function test_hierarki_jabatan_berurutan_dari_kepala_sampai_tata_usaha(): void
    {
        $expected = [
            'Kepala Satuan Pendidikan',
            'Wakil Kepala Satuan Pendidikan',
            'Kepala Urusan Kurikulum',
            'Koordinator Sarana Prasarana',
            'Guru Umum',
            'Staf Humas',
            'Tata Usaha',
        ];

        $ranks = array_map(fn ($name) => $this->ordering->rank($name), $expected);

        $this->assertSame([1, 2, 3, 4, 5, 6, 7], $ranks);
    }

    public function test_semua_jenis_guru_berada_pada_tingkat_yang_sama(): void
    {
        $guru = [
            'Guru Umum', 'Guru Agama', 'Guru Tahfidz',
            'Guru Kelas', 'Guru Bahasa Arab', 'Guru Hadits',
        ];

        foreach ($guru as $name) {
            $this->assertSame(
                DecreePositionOrdering::RANK_GURU,
                $this->ordering->rank($name),
                "{$name} harus berperingkat guru."
            );
        }
    }

    public function test_guru_tidak_tertukar_menjadi_kepala_atau_staf(): void
    {
        // "Kepala Departemen Tahfidz" memuat kata Tahfidz, tetapi bukan guru.
        $this->assertSame(
            DecreePositionOrdering::RANK_KEPALA_UNIT,
            $this->ordering->rank('Kepala Departemen Tahfidz')
        );
        // "Staf Perpustakaan" bukan koordinator perpustakaan.
        $this->assertSame(
            DecreePositionOrdering::RANK_STAF,
            $this->ordering->rank('Staf Perpustakaan')
        );
        $this->assertSame(
            DecreePositionOrdering::RANK_KOORDINATOR,
            $this->ordering->rank('Koordinator Perpustakaan')
        );
    }

    public function test_wakil_tetap_wakil_walau_membawahi_tata_usaha(): void
    {
        $this->assertSame(
            DecreePositionOrdering::RANK_WAKIL,
            $this->ordering->rank('Wakil Kepala Tata Usaha')
        );
    }

    public function test_variasi_penulisan_tata_usaha_dikenali(): void
    {
        foreach (['Tata Usaha', 'TU Sekolah', 'TU URT', 'Staf Tata Usaha', 'Kepala TU Sekolah'] as $name) {
            $rank = $this->ordering->rank($name);

            if (str_starts_with(mb_strtolower($name), 'kepala')) {
                $this->assertSame(DecreePositionOrdering::RANK_KEPALA_UNIT, $rank, $name);

                continue;
            }

            $this->assertSame(DecreePositionOrdering::RANK_TATA_USAHA, $rank, $name);
        }
    }

    public function test_sort_key_menempatkan_kepala_sebelum_guru_dan_tu(): void
    {
        $people = [
            ['Tata Usaha', 'Ahmad'],
            ['Guru Kelas', 'Budi'],
            ['Kepala Satuan Pendidikan', 'Zainal'],
            ['Wakil Kepala Satuan Pendidikan', 'Citra'],
        ];

        usort($people, fn ($a, $b) => strcmp(
            $this->ordering->sortKey($a[0], $a[1]),
            $this->ordering->sortKey($b[0], $b[1])
        ));

        $this->assertSame(
            ['Zainal', 'Citra', 'Budi', 'Ahmad'],
            array_column($people, 1),
            'Urutan harus jabatan tertinggi dulu, nama hanya penentu bila jabatan sama.'
        );
    }

    public function test_nama_hanya_menentukan_bila_jabatannya_sama(): void
    {
        $people = [
            ['Guru Umum', 'Zulkifli'],
            ['Guru Umum', 'Ahmad'],
            ['Guru Tahfidz', 'Budi'],
        ];

        usort($people, fn ($a, $b) => strcmp(
            $this->ordering->sortKey($a[0], $a[1]),
            $this->ordering->sortKey($b[0], $b[1])
        ));

        // Semua guru satu tingkat, jadi nama yang menentukan urutannya.
        $this->assertSame(['Ahmad', 'Budi', 'Zulkifli'], array_column($people, 1));
    }

    public function test_jabatan_kosong_atau_gtk_ditaruh_paling_akhir(): void
    {
        $this->assertSame(DecreePositionOrdering::RANK_LAINNYA, $this->ordering->rank(null));
        $this->assertSame(DecreePositionOrdering::RANK_LAINNYA, $this->ordering->rank(''));
        $this->assertSame(DecreePositionOrdering::RANK_LAINNYA, $this->ordering->rank('GTK'));

        $this->assertGreaterThan(
            $this->ordering->rank('Tata Usaha'),
            $this->ordering->rank('GTK'),
            'GTK tanpa jabatan tidak boleh muncul di atas Tata Usaha.'
        );
    }

    public function test_sort_key_dengan_master_memakai_urutan_master_saat_jenjang_sama(): void
    {
        $master = ['kepala humas' => 2, 'kepala uks' => 1];

        $humas = $this->ordering->sortKeyWithMaster('Kepala Humas', 'Ahmad', $master);
        $uks = $this->ordering->sortKeyWithMaster('Kepala UKS', 'Zainal', $master);

        $this->assertLessThan($humas, $uks, 'Urutan master dipakai bila peringkat jabatan sama.');
    }
}
