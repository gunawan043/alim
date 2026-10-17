<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\Ekstrakurikuler;
use App\Models\EkstrakurikulerAnggota;
use Illuminate\Http\Request;

class EkstrakurikulerAnggotaController extends Controller
{
    public function store(Request $request, string $ekstrakurikulerId)
    {
        $ekskul = Ekstrakurikuler::findOrFail($ekstrakurikulerId);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'tanggal_bergabung' => 'nullable|date',
            'status' => 'required|in:aktif,keluar,lulus',
            'keterangan' => 'nullable|string',
        ]);

        $validated['ekstrakurikuler_id'] = $ekskul->id;
        if (empty($validated['tanggal_bergabung'])) {
            $validated['tanggal_bergabung'] = now()->toDateString();
        }

        // cek duplikat aktif
        $existing = EkstrakurikulerAnggota::where('ekstrakurikuler_id', $ekskul->id)
            ->where('student_id', $validated['student_id'])
            ->where('status', EkstrakurikulerAnggota::STATUS_AKTIF)
            ->first();
        if ($existing) {
            return back()->withErrors([
                'student_id' => 'Santri sudah menjadi anggota aktif pada kegiatan ini.',
            ])->withInput();
        }

        EkstrakurikulerAnggota::create($validated);

        return back()->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function update(Request $request, string $ekstrakurikulerId, string $id)
    {
        $ekskul = Ekstrakurikuler::findOrFail($ekstrakurikulerId);
        $anggota = EkstrakurikulerAnggota::where('ekstrakurikuler_id', $ekskul->id)->findOrFail($id);

        $validated = $request->validate([
            'tanggal_bergabung' => 'nullable|date',
            'tanggal_keluar' => 'nullable|date|after_or_equal:tanggal_bergabung',
            'status' => 'required|in:aktif,keluar,lulus',
            'keterangan' => 'nullable|string',
        ]);

        $anggota->update($validated);

        return back()->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id)
    {
        $anggota = EkstrakurikulerAnggota::findOrFail($id);
        $anggota->delete();

        return back()->with('success', 'Anggota berhasil dihapus.');
    }
}
