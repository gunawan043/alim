<?php

namespace App\Services\ArabicName\Contracts;

/**
 * Kontrak generator nama Arab.
 *
 * Implementasi bawaan: kaidah transliterasi deterministik.
 * Implementasi AI dapat dipilih lewat config `arabic_names.driver = ai`.
 */
interface ArabicNameGeneratorContract
{
    /**
     * Hasilkan nama Arab dari nama Latin. String kosong bila gagal.
     */
    public function generate(?string $latinName): string;

    /**
     * Nama driver aktif: `rule` atau `ai`.
     */
    public function driver(): string;
}
