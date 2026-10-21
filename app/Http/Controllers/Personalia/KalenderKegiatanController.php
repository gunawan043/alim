<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use App\Models\Kaldik;
use App\Models\KinerjaPenilaian;
use App\Models\KontrakKerja;
use App\Models\Pelatihan;
use App\Models\School;
use Illuminate\Http\Request;

class KalenderKegiatanController extends Controller
{
    /**
     * Agenda Kegiatan (Kalender Akademik).
     *
     * Sumber data TUNGGAL = tabel `kaldik` (kategori agenda) — tidak ada sumber
     * terpisah. Scope mengikuti kebijakan Kaldik:
     *   - agenda satuan: hanya GTK dalam satuan kerja tersebut;
     *   - agenda pribadi: HANYA pemiliknya.
     */
    public function akademik(Request $request, string $userId)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId');
        $school = $schoolId ? School::find($schoolId) : null;
        $workUnitId = $school?->work_unit_id;

        $today = now()->toDateString();

        $query = Kaldik::query()
            ->where('category', Kaldik::CATEGORY_AGENDA)
            ->where('is_active', true)
            ->with(['academicYear', 'workUnit'])
            ->orderBy('start_date');

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->input('academic_year_id'));
        }

        // Scope: agenda satuan kerja sendiri + agenda pribadi sendiri.
        $query->where(function ($q) use ($workUnitId, $user) {
            if ($workUnitId) {
                $q->orWhere(function ($q2) use ($workUnitId) {
                    $q2->where('visibility', '!=', Kaldik::VISIBILITY_PRIBADI)
                        ->where('work_unit_id', $workUnitId);
                });
            }

            $q->orWhere(function ($q2) use ($user) {
                $q2->where('visibility', Kaldik::VISIBILITY_PRIBADI)
                    ->where('owner_user_id', $user->id);
            });
        });

        $agendaList = $query->get();

        $stats = [
            'total' => $agendaList->count(),
            'berlangsung' => $agendaList->filter(fn ($a) => $a->start_date?->toDateString() <= $today && $a->end_date?->toDateString() >= $today)->count(),
            'mendatang' => $agendaList->filter(fn ($a) => $a->start_date?->toDateString() > $today)->count(),
            'selesai' => $agendaList->filter(fn ($a) => $a->end_date?->toDateString() < $today)->count(),
        ];

        $academicYears = \App\Models\AcademicYear::orderByDesc('name')->get(['id', 'name']);

        return view('personalia.kalender-kegiatan.akademik', compact('userId', 'agendaList', 'stats', 'academicYears'));
    }

    /**
     * Kalender Kontrak GTK
     */
    public function kontrak(Request $request, string $userId)
    {
        $kontrakList = KontrakKerja::with('gtk.user')
            ->orderBy('tanggal_selesai')
            ->limit(200)->get()
            ->map(function (KontrakKerja $kontrak) {
                $sisa = $kontrak->tanggal_selesai && $kontrak->tanggal_selesai->gte(today())
                    ? (int) today()->diffInDays($kontrak->tanggal_selesai)
                    : 0;
                $status = $sisa <= 0 ? 'habis' : ($sisa <= 30 ? 'akan_habis' : 'aktif');

                return [
                    'nama' => $kontrak->gtk?->nama ?? $kontrak->gtk?->user?->name ?? '-',
                    'jenis' => $kontrak->jenis_kontrak,
                    'tanggal_mulai' => $kontrak->tanggal_mulai?->format('d/m/Y'),
                    'tanggal_selesai' => $kontrak->tanggal_selesai?->format('d/m/Y'),
                    'sisa_hari' => $sisa,
                    'status' => $status,
                ];
            });

        return view('personalia.kalender-kegiatan.kontrak', compact('userId', 'kontrakList'));
    }

    /**
     * Jadwal Evaluasi GTK
     */
    public function evaluasi(Request $request, string $userId)
    {
        $evaluasiList = KinerjaPenilaian::with(['user', 'penilai', 'periode'])
            ->orderByDesc('tanggal_penilaian')->limit(200)->get()
            ->map(fn (KinerjaPenilaian $item) => [
                'nama_gtk' => $item->user?->name ?? '-',
                'tanggal' => $item->tanggal_penilaian?->format('d/m/Y') ?? '-',
                'penilai' => $item->penilai?->name ?? '-',
                'status' => $item->status === 'final' ? 'selesai' : 'menunggu',
            ]);

        return view('personalia.kalender-kegiatan.evaluasi', compact('userId', 'evaluasiList'));
    }

    /**
     * Training & Workshop
     */
    public function training(Request $request, string $userId)
    {
        $trainingList = Pelatihan::withCount('pesertas')
            ->orderByDesc('tanggal_mulai')->limit(200)->get()
            ->map(fn (Pelatihan $item) => [
                'nama' => $item->nama,
                'tanggal_mulai' => $item->tanggal_mulai?->format('d/m/Y'),
                'tanggal_selesai' => $item->tanggal_selesai?->format('d/m/Y'),
                'peserta' => $item->pesertas_count,
                'penyelenggara' => $item->vendor ?: 'Internal',
                'status' => $item->status === 'selesai' ? 'selesai' : ($item->tanggal_mulai?->isPast() ? 'berlangsung' : 'akan_datang'),
            ]);

        return view('personalia.kalender-kegiatan.training', compact('userId', 'trainingList'));
    }
}
