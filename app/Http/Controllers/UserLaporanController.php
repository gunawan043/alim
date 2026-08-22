<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use App\Models\Student;
use App\Models\User;

class UserLaporanController extends Controller
{
    /**
     * Dashboard Laporan Pondok untuk Mudir, Wadir, Satuan Pendidikan
     */
    public function index(string $userId)
    {
        $stats = $this->buildStats();

        return view('user.laporan.index', compact('userId', 'stats'));
    }

    /**
     * Laporan Presensi GTK
     */
    public function presensi(string $userId)
    {
        return view('user.laporan.presensi');
    }

    /**
     * Laporan Santri (Hadir, Mutasi, dll)
     */
    public function santri(string $userId)
    {
        return view('user.laporan.santri');
    }

    /**
     * LaporanGTK
     */
    public function gtk(string $userId)
    {
        return view('user.laporan.gtk');
    }

    /**
     * Laporan Keuangan
     */
    public function keuangan(string $userId)
    {
        return view('user.laporan.keuangan');
    }

    /**
     * Laporan Asrama
     */
    public function asrama(string $userId)
    {
        return view('user.laporan.asrama');
    }

    /**
     * Export Laporan
     */
    public function export(string $userId, string $type)
    {
        return response()->json(['message' => 'Export '.$type.' - coming soon']);
    }

    private function buildStats()
    {
        return [
            'total_siswa' => Student::count(),
            'total_gtk' => User::whereNotNull('gtk_profile_id')->count(),
            'total_alumni' => Alumni::count(),
            'alumni_aktif' => Alumni::where('employment_status', 'active')->count(),
        ];
    }
}
