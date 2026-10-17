<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use App\Models\Kaldik;
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
        return view('personalia.kalender-kegiatan.kontrak', compact('userId'));
    }

    /**
     * Jadwal Evaluasi GTK
     */
    public function evaluasi(Request $request, string $userId)
    {
        return view('personalia.kalender-kegiatan.evaluasi', compact('userId'));
    }

    /**
     * Training & Workshop
     */
    public function training(Request $request, string $userId)
    {
        return view('personalia.kalender-kegiatan.training', compact('userId'));
    }
}
