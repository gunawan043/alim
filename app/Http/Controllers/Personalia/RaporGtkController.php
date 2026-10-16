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
        // Tahun ajaran aktif (angka tahun mulai) — dipakai judul rekap.
        // Sebelumnya view memakai $tahunAjaran tanpa dikirim → "Undefined variable".
        $activeYear = \App\Models\AcademicYear::where('is_active', true)->first();
        $tahunAjaran = $activeYear?->start_date
            ? (int) $activeYear->start_date->format('Y')
            : (int) date('Y');

        // Daftar filter: GTK satuan (via employment) + tahun ajaran.
        $schoolId = $request->attributes->get('schoolContextId');
        $gtks = $schoolId
            ? \App\Models\GtkProfile::query()
                ->with('user:id,name')
                ->whereIn('user_id', \App\Models\GtkEmployment::query()->where('school_id', $schoolId)->pluck('user_id'))
                ->get()
                ->sortBy(fn ($gtk) => mb_strtolower((string) $gtk->user?->name))
                ->values()
            : collect();

        $academicYears = \App\Models\AcademicYear::orderByDesc('name')->get(['id', 'name']);

        return view('personalia.rapor-gtk.tahunan', compact('userId', 'tahunAjaran', 'gtks', 'academicYears'));
    }
}
