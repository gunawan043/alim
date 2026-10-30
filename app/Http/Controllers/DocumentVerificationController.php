<?php

namespace App\Http\Controllers;

use App\Services\Documents\DocumentSigningService;
use Illuminate\Http\Request;

/**
 * Halaman publik verifikasi dokumen. Tidak memerlukan login.
 *
 * Hanya menampilkan metadata non-sensitif. Nama santri/nilai tidak
 * ditampilkan di halaman publik.
 */
class DocumentVerificationController extends Controller
{
    public function show(Request $request, string $token, DocumentSigningService $signing)
    {
        $document = $signing->findByToken($token);

        if (! $document) {
            return response()->view('documents.verify', [
                'document' => null,
                'token' => $token,
                'integrity' => false,
            ], 404);
        }

        $signing->markScanned($document);

        return view('documents.verify', [
            'document' => $document,
            'token' => $token,
            'integrity' => $signing->verifyIntegrity($document),
        ]);
    }
}
