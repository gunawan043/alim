<?php

namespace App\Services\Pdf;

use Dompdf\Dompdf;

/**
 * Font PDF dokumen kurikulum.
 *
 * Cambria adalah font proprietary yang tidak selalu tersedia di server.
 * Caladea adalah font open-source metric-compatible dengan Cambria, sehingga
 * dokumen tetap memakai nama keluarga "Cambria" dan layout tetap konsisten.
 */
class CurriculumPdfStyle
{
    public function registerCambria(Dompdf $dompdf): void
    {
        $fontDir = resource_path('fonts');
        $dompdf->getOptions()->setChroot(array_values(array_unique([base_path(), storage_path()])));
        $fontMetrics = $dompdf->getFontMetrics();

        $fonts = [
            ['weight' => 'normal', 'style' => 'normal', 'file' => 'Caladea-Regular.ttf'],
            ['weight' => 'bold', 'style' => 'normal', 'file' => 'Caladea-Bold.ttf'],
            ['weight' => 'normal', 'style' => 'italic', 'file' => 'Caladea-Italic.ttf'],
            ['weight' => 'bold', 'style' => 'italic', 'file' => 'Caladea-BoldItalic.ttf'],
        ];

        foreach ($fonts as $font) {
            $path = $fontDir.'/'.$font['file'];
            if (! is_file($path)) {
                continue;
            }

            try {
                $fontMetrics->registerFont([
                    'family' => 'Cambria',
                    'weight' => $font['weight'],
                    'style' => $font['style'],
                ], $path);
            } catch (\Throwable) {
                // Fallback ke DejaVu Serif bila registrasi font gagal.
            }
        }
    }
}
