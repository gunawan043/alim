<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RaporGtkController extends Controller
{
    /**
     * Penilaian Akademik GTK
     */
    public function akademik(Request $request, string $userId)
    {
        return view('personalia.rapor-gtk.akademik', compact('userId'));
    }

    /**
     * Penilaian Disiplin GTK
     */
    public function disiplin(Request $request, string $userId)
    {
        return view('personalia.rapor-gtk.disiplin', compact('userId'));
    }

    /**
     * Penilaian Kepribadian GTK
     */
    public function kepribadian(Request $request, string $userId)
    {
        return view('personalia.rapor-gtk.kepribadian', compact('userId'));
    }

    /**
     * Penilaian Administrasi GTK
     */
    public function administrasi(Request $request, string $userId)
    {
        return view('personalia.rapor-gtk.administrasi', compact('userId'));
    }

    /**
     * Rekap Tahunan GTK
     */
    public function tahunan(Request $request, string $userId)
    {
        return view('personalia.rapor-gtk.tahunan', compact('userId'));
    }
}