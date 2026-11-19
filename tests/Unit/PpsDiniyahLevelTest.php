<?php

namespace Tests\Unit;

use App\Models\School;
use PHPUnit\Framework\TestCase;

/**
 * PPS Diniyah menempuh 6 tahun: Wustho (kelas 7–9) lalu Ulya (kelas 10–12).
 * Kelas 9 bukan akhir jenjang — santri naik ke Ulya, bukan diluluskan.
 */
class PpsDiniyahLevelTest extends TestCase
{
    public function test_kelas_akhir_pps_hanya_kelas_12(): void
    {
        $this->assertSame([12], School::finalLevelsFor('pps'));
    }

    public function test_kelas_9_bukan_akhir_jenjang_pps(): void
    {
        $school = new School(['school_level' => 'pps']);

        $this->assertFalse($school->isFinalLevel(9));
        $this->assertTrue($school->isFinalLevel(12));
    }

    public function test_jenjang_lain_tetap_memakai_kelas_akhir_lama(): void
    {
        $this->assertSame([6], School::finalLevelsFor('sd'));
        $this->assertSame([9], School::finalLevelsFor('smp'));
        $this->assertSame([12], School::finalLevelsFor('sma'));
        $this->assertSame([12], School::finalLevelsFor('smk'));
    }

    public function test_label_jenjang_pps_menyebut_wustho_dan_ulya(): void
    {
        $school = new School(['school_level' => 'pps']);

        $this->assertSame('PPS Diniyah (Wustho & Ulya)', $school->level_text);
    }
}
