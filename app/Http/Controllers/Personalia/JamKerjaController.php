<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use App\Models\JamKerja;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JamKerjaController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $jamKerjas = JamKerja::orderBy('jam_masuk')->paginate(20);
        $shifts = Shift::with('jamKerja')->orderBy('tanggal_mulai', 'desc')->limit(10)->get();

        $stats = [
            'total_jam_kerja' => JamKerja::count(),
            'total_shift' => Shift::count(),
            'active_jam_kerja' => JamKerja::where('is_active', true)->count(),
        ];

        return view('personalia.jam-kerja.index', compact('userId', 'jamKerjas', 'shifts', 'stats'));
    }

    public function create(Request $request, string $userId)
    {
        return view('personalia.jam-kerja.create', compact('userId'));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'jam_masuk' => 'required|date_format:H:i',
            'jam_pulang' => 'required|date_format:H:i|after:jam_masuk',
            'istirahat_menit' => 'nullable|integer|min:0|max:480',
            'istirahat_mulai' => 'nullable|date_format:H:i',
            'keterangan' => 'nullable|string',
            'is_active' => 'nullable',
        ]);

        JamKerja::create([
            'nama' => $validated['nama'],
            'jam_masuk' => $validated['jam_masuk'],
            'jam_pulang' => $validated['jam_pulang'],
            'istirahat_menit' => $validated['istirahat_menit'] ?? 60,
            'istirahat_mulai' => $validated['istirahat_mulai'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'dibuat_oleh' => Auth::id(),
        ]);

        return redirect()->route('user.jam-kerja.index', $userId)
            ->with('success', 'Jam kerja berhasil disimpan.');
    }

    public function edit(Request $request, string $userId, string $id)
    {
        $jamKerja = JamKerja::findOrFail($id);

        return view('personalia.jam-kerja.edit', compact('userId', 'jamKerja'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $jamKerja = JamKerja::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'jam_masuk' => 'required|date_format:H:i',
            'jam_pulang' => 'required|date_format:H:i',
            'istirahat_menit' => 'nullable|integer|min:0|max:480',
            'istirahat_mulai' => 'nullable|date_format:H:i',
            'keterangan' => 'nullable|string',
            'is_active' => 'nullable',
        ]);

        $jamKerja->update([
            'nama' => $validated['nama'],
            'jam_masuk' => $validated['jam_masuk'],
            'jam_pulang' => $validated['jam_pulang'],
            'istirahat_menit' => $validated['istirahat_menit'] ?? 60,
            'istirahat_mulai' => $validated['istirahat_mulai'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('user.jam-kerja.index', $userId)
            ->with('success', 'Jam kerja berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $jamKerja = JamKerja::findOrFail($id);
        // Block deletion if shifts still reference it
        $referenced = Shift::where('jam_kerja_id', $id)->exists();
        if ($referenced) {
            return redirect()->back()->with('error', 'Jam kerja masih dipakai oleh shift aktif.');
        }
        $jamKerja->delete();

        return redirect()->route('user.jam-kerja.index', $userId)
            ->with('success', 'Jam kerja berhasil dihapus.');
    }

    public function shift(Request $request, string $userId)
    {
        $shifts = Shift::with('jamKerja')->orderBy('tanggal_mulai', 'desc')->paginate(20);

        return view('personalia.jam-kerja.shift', compact('userId', 'shifts'));
    }

    public function kalender(Request $request, string $userId)
    {
        $shifts = Shift::with('jamKerja')
            ->whereYear('tanggal_mulai', $request->get('tahun', now()->year))
            ->orderBy('tanggal_mulai')
            ->get();

        return view('personalia.jam-kerja.kalender', compact('userId', 'shifts'));
    }

    public function datatable(Request $request, string $userId)
    {
        $query = JamKerja::query()
            ->when($request->get('is_active') !== null, fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('jam_masuk');

        return datatables()->of($query)
            ->addColumn('durasi', function ($r) {
                try {
                    $start = \Carbon\Carbon::parse($r->jam_masuk);
                    $end = \Carbon\Carbon::parse($r->jam_pulang);
                    $minutes = $end->diffInMinutes($start);
                    $hours = intdiv($minutes, 60);
                    $mins = $minutes % 60;

                    return sprintf('%d jam %d menit', $hours, $mins);
                } catch (\Throwable $e) {
                    return '-';
                }
            })
            ->addColumn('status_badge', fn ($r) => $r->is_active
                ? '<span class="badge bg-success-subtle text-success">Aktif</span>'
                : '<span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>')
            ->rawColumns(['status_badge'])
            ->make(true);
    }
}
