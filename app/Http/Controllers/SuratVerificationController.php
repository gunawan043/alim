<?php

namespace App\Http\Controllers;

use App\Models\SuratKeluar;
use App\Models\SuratMasuk;

/**
 * Halaman publik verifikasi keaslian surat via QR.
 */
class SuratVerificationController extends Controller
{
    public function show(string $token)
    {
        $surat = SuratMasuk::with('school')->where('verification_token', $token)->first();
        $jenis = 'masuk';

        if (! $surat) {
            $surat = SuratKeluar::with('school')->where('verification_token', $token)->first();
            $jenis = 'keluar';
        }

        $valid = $surat !== null
            && ! ($jenis === 'keluar' && $surat->status === SuratKeluar::STATUS_DIBATALKAN);

        return view('surat.verifikasi', compact('surat', 'jenis', 'valid', 'token'));
    }
}
