<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\TahfidzJuzProgress;
use App\Models\TahfidzSetoran;
use App\Models\WaliSantri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TahfidzController extends Controller
{
    // ── GET /api/mobile/v1/tahfidz ─────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId');

        $studentIds = WaliSantri::where('user_id', $user->id)
            ->active()
            ->pluck('student_id');

        if ($schoolId) {
            // Tenant scope: only return setoran for students in the same school
            $studentIds = \App\Models\Student::whereIn('id', $studentIds)
                ->where('school_id', $schoolId)
                ->pluck('id');
        }

        if ($studentIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => ['mutabaah' => [], 'total' => 0],
            ]);
        }

        $statusFilter = $request->query('status');
        $query = TahfidzSetoran::with(['student:id,name', 'musyrif:id,name'])
            ->whereIn('student_id', $studentIds);

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $records = $query->orderByDesc('setoran_date')
            ->take(30)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'student_name' => $s->student?->name,
                'setoran_date' => $s->setoran_date?->toDateString(),
                'status' => $s->status,
                'status_label' => $s->getStatusLabelAttribute(),
                'juz_number' => $s->juz_number,
                'surah_name' => $s->surah_name,
                'ayat_from' => $s->ayat_from,
                'ayat_to' => $s->ayat_to,
                'nilai' => $s->nilai,
                'catatan' => $s->catatan,
                'musyrif' => $s->musyrif?->name,
                'is_verified' => $s->is_verified,
            ]);

        return response()->json([
            'success' => true,
            'data' => ['mutabaah' => $records, 'total' => $records->count()],
        ]);
    }

    // ── GET /api/mobile/v1/tahfidz/progress ────────────────────────────

    public function progress(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId');

        $studentIds = WaliSantri::where('user_id', $user->id)
            ->active()
            ->pluck('student_id');

        if ($schoolId) {
            $studentIds = \App\Models\Student::whereIn('id', $studentIds)
                ->where('school_id', $schoolId)
                ->pluck('id');
        }

        $academicYearId = $request->query('academic_year_id');
        $query = TahfidzJuzProgress::with(['student:id,name'])
            ->whereIn('student_id', $studentIds);

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        $records = $query->orderBy('juz_number')->get()->map(fn ($p) => [
            'id' => $p->id,
            'student_name' => $p->student?->name,
            'juz_number' => $p->juz_number,
            'status' => $p->status,
            'status_label' => $this->juzStatusLabel($p->status),
            'halaman_completed' => (float) $p->halaman_completed,
            'total_halaman_juz' => (float) $p->total_halaman_juz,
            'percentage' => (float) $p->percentage,
            'last_setoran_date' => $p->last_setoran_date?->toDateString(),
            'ziyadah_started_at' => $p->ziyadah_started_at?->toDateString(),
            'ziyadah_completed_at' => $p->ziyadah_completed_at?->toDateString(),
            'avg_nilai_setoran' => $p->avg_nilai_setoran ? (float) $p->avg_nilai_setoran : null,
        ]);

        return response()->json([
            'success' => true,
            'data' => ['juz_progress' => $records],
        ]);
    }

    private function juzStatusLabel(?string $status): string
    {
        return match ($status) {
            'belum' => 'Belum Mulai',
            'sedang' => 'Sedang Dikerjakan',
            'selesai_ziyadah' => 'Selesai Ziyadah',
            'sudah_tasmi' => 'Tasmi',
            default => $status ?? 'Unknown',
        };
    }
}
