<?php

namespace App\Services\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;

/**
 * Satu pintu untuk seluruh cetak dokumen resmi.
 *
 * Semua endpoint cetak harus mengembalikan PDF, bukan HTML yang masih
 * membutuhkan tombol "Cetak / Simpan PDF" di browser.
 */
class DocumentPdfService
{
    public function fromView(
        string $view,
        array $data,
        string $filename,
        string $orientation = 'portrait',
        string $paper = 'A4',
        string $disposition = 'inline',
    ): Response {
        return $this->fromHtml(view($view, $data)->render(), $filename, $orientation, $paper, $disposition);
    }

    public function fromHtml(
        string $html,
        string $filename,
        string $orientation = 'portrait',
        string $paper = 'A4',
        string $disposition = 'inline',
    ): Response {
        $safe = $this->safeFilename($filename);

        return response($this->renderHtml($html, $orientation, $paper), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$safe.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Render HTML menjadi bytes PDF tanpa membungkus response.
     * Dipakai alur tanda tangan dokumen yang perlu menghitung hash final.
     */
    public function renderHtml(string $html, string $orientation = 'portrait', string $paper = 'A4'): string
    {
        // Toolbar browser tidak boleh ikut tercetak oleh Dompdf.
        $html = $this->withoutBrowserToolbar($html);

        $options = new Options;
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->setChroot(array_values(array_unique([
            base_path(),
            storage_path(),
            public_path(),
            resource_path(),
        ])));

        $fontDir = storage_path('fonts');
        if (! is_dir($fontDir)) {
            mkdir($fontDir, 0755, true);
        }
        $options->setFontDir($fontDir);
        $options->setFontCache($fontDir);

        $dompdf = new Dompdf($options);
        app(CurriculumPdfStyle::class)->registerCambria($dompdf);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();

        return (string) $dompdf->output();
    }

    private function withoutBrowserToolbar(string $html): string
    {
        $style = '<style>.no-print,.no-print-area,.toolbar,.print-btn,.btn-print{display:none!important;visibility:hidden!important;}</style>';

        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head>/i', $style.'</head>', $html, 1) ?? $html;
        }

        return $style.$html;
    }

    private function safeFilename(string $filename): string
    {
        $safe = preg_replace('/[^A-Za-z0-9\-_]+/', '-', trim($filename)) ?: 'Dokumen';
        $safe = trim($safe, '-_');

        return $safe !== '' ? $safe : 'Dokumen';
    }
}
