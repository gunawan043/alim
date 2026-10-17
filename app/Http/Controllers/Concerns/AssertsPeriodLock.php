<?php

namespace App\Http\Controllers\Concerns;

use App\Services\AcademicPeriodService;

/**
 * ARSIP PERIODE: helper guard untuk dokumen/konfigurasi kurikulum.
 *
 * Dokumen kurikulum (ATP, PROTA, PROSEM, Perangkat/KKTP) boleh disiapkan
 * untuk tahun ajaran mendatang, sehingga hanya periode yang SUDAH DITUTUP
 * (terkunci) yang memblokir perubahan.
 */
trait AssertsPeriodLock
{
    protected function assertPeriodNotLocked(?string $academicYearId, string $context = 'Dokumen'): void
    {
        app(AcademicPeriodService::class)->assertNotLocked($academicYearId, $context);
    }
}
