<?php

namespace App\Observers;

use App\Models\AbsensiGtk;
use App\Services\AcademicPeriodService;

/**
 * ARSIP PERIODE: absensi GTK berbasis tanggal — resolusi tahun ajaran
 * dari rentang tanggal. Periode yang ditutup (terkunci) tidak dapat diubah,
 * dan periode non-aktif bersifat arsip (baca-saja).
 *
 * Ditempatkan sebagai observer agar melindungi SEMUA jalur tulis
 * (form personalia maupun alur QR) tanpa duplikasi guard di setiap controller.
 */
class AbsensiGtkPeriodObserver
{
    public function __construct(private readonly AcademicPeriodService $periods) {}

    public function saving(AbsensiGtk $absensi): void
    {
        $academicYearId = $this->periods->academicYearIdForDate($absensi->tanggal);

        if ($academicYearId) {
            $this->periods->assertWritable($academicYearId, 'Absensi GTK');
        }
    }
}
