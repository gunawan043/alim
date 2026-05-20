<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KalenderKegiatanController extends Controller
{
    /**
     * Kalender Akademik
     */
    public function akademik(Request $request, string $userId)
    {
        return view('personalia.kalender-kegiatan.akademik', compact('userId'));
    }

    /**
     * Kalender Kontrak GTK
     */
    public function kontrak(Request $request, string $userId)
    {
        return view('personalia.kalender-kegiatan.kontrak', compact('userId'));
    }

    /**
     * Jadwal Evaluasi GTK
     */
    public function evaluasi(Request $request, string $userId)
    {
        return view('personalia.kalender-kegiatan.evaluasi', compact('userId'));
    }

    /**
     * Training & Workshop
     */
    public function training(Request $request, string $userId)
    {
        return view('personalia.kalender-kegiatan.training', compact('userId'));
    }
}