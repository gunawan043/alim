<?php

namespace Tests\Unit\Evaluasi;

use App\Services\Evaluasi\BloomTaxonomyService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BloomTaxonomyServiceTest extends TestCase
{
    private BloomTaxonomyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BloomTaxonomyService;
    }

    #[DataProvider('classificationProvider')]
    public function test_classifies_bloom_levels(string $question, string $expectedLevel, string $expectedRumpun): void
    {
        $result = $this->service->classify($question);

        $this->assertSame($expectedLevel, $result['level']);
        $this->assertSame($expectedRumpun, $result['rumpun']);
        $this->assertGreaterThan(0, $result['confidence']);
    }

    public static function classificationProvider(): array
    {
        return [
            'C1 mengingat' => ['Sebutkan tiga jenis energi terbarukan yang kamu ketahui.', 'C1_mengingat', 'L1'],
            'C2 memahami' => ['Jelaskan mengapa air dapat berubah menjadi uap saat dipanaskan.', 'C2_memahami', 'L1'],
            'C3 menerapkan' => ['Hitunglah luas persegi panjang dengan panjang 12 cm dan lebar 7 cm.', 'C3_menerapkan', 'L2'],
            'C4 menganalisis' => ['Bandingkan penyebab banjir pada kedua paragraf dan hubungkan dengan kondisi lingkungan.', 'C4_menganalisis', 'L3'],
            'C5 mengevaluasi' => ['Nilai apakah kebijakan tersebut layak diterapkan di sekolah. Berikan alasanmu.', 'C5_mengevaluasi', 'L3'],
            'C6 mencipta' => ['Rancanglah poster kampanye hemat energi untuk lingkungan sekolahmu.', 'C6_mencipta', 'L3'],
        ];
    }

    public function test_indicator_is_prioritized_when_it_is_more_specific(): void
    {
        $result = $this->service->classify(
            'Berikan contoh penerapan persamaan linear dalam kehidupan sehari-hari.',
            'Peserta didik dapat menyelesaikan masalah persamaan linear satu variabel.'
        );

        $this->assertSame('C3_menerapkan', $result['level']);
        $this->assertSame('indikator', $result['source']);
    }

    public function test_returns_heuristic_when_no_verb_is_found(): void
    {
        $result = $this->service->classify('Air adalah sumber kehidupan.');

        $this->assertSame('C2_memahami', $result['level']);
        $this->assertSame('heuristik', $result['source']);
        $this->assertSame(40, $result['confidence']);
    }
}
