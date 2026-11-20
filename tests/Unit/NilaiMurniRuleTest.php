<?php

namespace Tests\Unit;

use App\Support\NilaiMurniRule;
use PHPUnit\Framework\TestCase;

class NilaiMurniRuleTest extends TestCase
{
    public function test_tahfidz_tidak_wajib_nilai_murni(): void
    {
        foreach (['Tahfidz', 'Tahfidz Al-Qur’an', 'Tahfidzul Qur’an', 'Tahfiz', 'Tahfidzh'] as $name) {
            $this->assertTrue(NilaiMurniRule::optionalFor($name, 'smp', 7), $name);
        }
    }

    public function test_hadits_tidak_wajib_nilai_murni(): void
    {
        foreach (['Hadits', 'Hadis', 'Hadist', 'Al-Hadits', 'Hadits Arbain', 'Hafalan Hadits', 'Ilmu Hadits', 'Mustholahul Hadits'] as $name) {
            $this->assertTrue(NilaiMurniRule::optionalFor($name, 'sma', 10), $name);
        }
    }

    public function test_bahasa_inggris_hanya_kelas_1_sampai_3_sd(): void
    {
        $this->assertTrue(NilaiMurniRule::optionalFor('Bahasa Inggris', 'sd', 1));
        $this->assertTrue(NilaiMurniRule::optionalFor('Bahasa Inggris', 'sd', 2));
        $this->assertTrue(NilaiMurniRule::optionalFor('Bahasa Inggris', 'sd', 3));
        $this->assertFalse(NilaiMurniRule::optionalFor('Bahasa Inggris', 'sd', 4));
        $this->assertFalse(NilaiMurniRule::optionalFor('Bahasa Inggris', 'smp', 7));
    }

    public function test_mapel_lain_tetap_wajib(): void
    {
        $this->assertFalse(NilaiMurniRule::optionalFor('Matematika', 'sd', 1));
        $this->assertFalse(NilaiMurniRule::optionalFor('Al-Qur’an', 'smp', 8));
        $this->assertFalse(NilaiMurniRule::optionalFor(null, 'sd', 1));
        $this->assertFalse(NilaiMurniRule::optionalFor('', 'sd', 1));
    }
}
