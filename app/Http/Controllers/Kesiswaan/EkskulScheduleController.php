<?php

namespace App\Http\Controllers\Kesiswaan;

use App\Http\Controllers\Controller;
use App\Models\Ekstrakurikuler;
use Illuminate\Http\Request;

/**
 * Jadwal Latihan Ekstrakurikuler — memakai data `ekstrakurikuler` yang sudah ada
 * (kolom hari, jam_mulai, jam_selesai, lokasi).
 */
class EkskulScheduleController extends Controller
{
    public const HARI = [
        'senin' => 'Senin',
        'selasa' => 'Selasa',
        'rabu' => 'Rabu',
        'kamis' => 'Kamis',
        'jumat' => 'Jumat',
        'sabtu' => 'Sabtu',
        'minggu' => 'Minggu',
    ];

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $search = trim((string) $request->input('search'));

        $ekskuls = Ekstrakurikuler::query()
            ->withCount('anggotaAktif')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($search !== '', fn ($q) => $q->where('nama', 'like', "%{$search}%"))
            ->orderBy('nama')
            ->get();

        // Urutkan berdasarkan hari lalu jam agar terbaca sebagai jadwal.
        $dayOrder = array_flip(array_keys(self::HARI));
        $ekskuls = $ekskuls->sortBy(function ($item) use ($dayOrder) {
            $day = strtolower((string) $item->hari);

            return sprintf('%02d-%s-%s', $dayOrder[$day] ?? 99, (string) $item->jam_mulai, $item->nama);
        })->values();

        $tanpaJadwal = $ekskuls->filter(fn ($e) => blank($e->hari) || blank($e->jam_mulai))->count();

        return view('kesiswaan.ekskul.index', [
            'userId' => $userId,
            'ekskuls' => $ekskuls,
            'search' => $search,
            'hari' => self::HARI,
            'tanpaJadwal' => $tanpaJadwal,
        ]);
    }

    public function update(Request $request, string $userId, string $ekskulId)
    {
        $validated = $request->validate([
            'hari' => 'nullable|in:'.implode(',', array_keys(self::HARI)),
            'jam_mulai' => 'nullable|date_format:H:i',
            'jam_selesai' => 'nullable|date_format:H:i|after:jam_mulai',
            'lokasi' => 'nullable|string|max:120',
            'pembimbing' => 'nullable|string|max:120',
        ]);

        $ekskul = Ekstrakurikuler::findOrFail($ekskulId);

        $ekskul->update([
            'hari' => $validated['hari'] ?: null,
            'jam_mulai' => $validated['jam_mulai'] ?: null,
            'jam_selesai' => $validated['jam_selesai'] ?: null,
            'lokasi' => $validated['lokasi'] ?? $ekskul->lokasi,
            'pembimbing' => $validated['pembimbing'] ?? $ekskul->pembimbing,
        ]);

        return redirect()
            ->route('user.kesiswaan.ekskul-schedules.index', ['userId' => $userId])
            ->with('success', 'Jadwal latihan '.$ekskul->nama.' diperbarui.');
    }
}
