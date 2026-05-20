<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AbsensiGtkController extends Controller
{
    public function index(Request $request, string $userId)
    {
        return view('personalia.absensi-gtk.index', compact('userId'));
    }

    public function harian(Request $request, string $userId)
    {
        return view('personalia.absensi-gtk.harian', compact('userId'));
    }

    public function rekapBulanan(Request $request, string $userId)
    {
        return view('personalia.absensi-gtk.rekap-bulanan', compact('userId'));
    }

    public function izin(Request $request, string $userId)
    {
        return view('personalia.absensi-gtk.izin', compact('userId'));
    }

    public function settings(Request $request, string $userId)
    {
        return view('personalia.absensi-gtk.settings', compact('userId'));
    }

    public function settingsStore(Request $request, string $userId)
    {
        // TODO: Implement settings store logic
        return redirect()->back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    public function store(Request $request, string $userId)
    {
        // TODO: Implement store logic
        return redirect()->back()->with('success', 'Data absensi berhasil disimpan.');
    }

    public function update(Request $request, string $userId, string $id)
    {
        // TODO: Implement update logic
        return redirect()->back()->with('success', 'Data absensi berhasil diupdate.');
    }

    public function datatable(Request $request, string $userId)
    {
        // TODO: Return JSON datatable response
        return response()->json(['data' => []]);
    }
}