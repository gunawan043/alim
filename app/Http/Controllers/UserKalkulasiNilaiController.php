<?php

namespace App\Http\Controllers;

class UserKalkulasiNilaiController extends Controller
{
    /**
     * Dashboard Kalkulasi Nilai untuk Mudir, Wadir, Satuan Pendidikan
     */
    public function index(string $userId)
    {
        return view('user.kalkulasi-nilai.index', compact('userId'));
    }

    /**
     * Detail Kalkulasi Nilai per Kelas
     */
    public function show(string $userId)
    {
        return view('user.kalkulasi-nilai.show', compact('userId'));
    }

    /**
     * Proses Kalkulasi
     */
    public function calculate(string $userId)
    {
        return response()->json(['message' => 'Kalkulasi nilai - coming soon']);
    }
}
