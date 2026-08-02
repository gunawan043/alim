<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KehadiranController extends Controller
{
    /**
     * Rekap Kehadiran GTK - Pergantian Jam
     */
    public function pergantianJam(Request $request, string $userId)
    {
        return view('personalia.kehadiran.pergantian-jam', compact('userId'));
    }

    /**
     * Rekap Kehadiran GTK
     */
    public function rekap(Request $request, string $userId)
    {
        return view('personalia.kehadiran.rekap', compact('userId'));
    }

    /**
     * Cuti & Izin GTK
     */
    public function cutiIzin(Request $request, string $userId)
    {
        return view('personalia.kehadiran.cuti-izin', compact('userId'));
    }
}
