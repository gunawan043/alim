<?php

namespace App\Http\Controllers;

use App\Support\Qr\QrBranding;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QrLogoController extends Controller
{
    /**
     * Sajikan logo QR aktif. File disimpan di storage/app/qr-logo agar
     * tidak terhapus saat deploy (deploy meng-exclude folder storage).
     */
    public function show(): BinaryFileResponse
    {
        $path = QrBranding::logoPath();

        abort_unless($path !== null && is_file($path), 404);

        return response()
            ->file($path)
            ->setPublic()
            ->setMaxAge(604800);
    }
}
