<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\WaliSantri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicController extends Controller
{
    // ── GET /api/mobile/v1/academic/summary ────────────────────────────

    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId');

        $studentIds = WaliSantri::where('user_id', $user->id)->active()->pluck('student_id');

        // Pembiasaan score
        $pembiasaan = \App\Models\PembiasaanPagi::whereIn('student_id', $studentIds);

        if ($schoolId) {
            $pembiasaan->whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId));
        }

        $pembiasaanStats = $pembiasaan->selectRaw('AVG(skor_doa) as avg_doa, AVG(skor_hiwar) as avg_hiwar, AVG(skor_conversation) as avg_conv, COUNT(*) as total_records')
            ->first();

        // Penghargaan counts
        $penghargaan = \App\Models\PenghargaanAkademik::whereIn('student_id', $studentIds)
            ->where('is_active', true);

        if ($schoolId) {
            $penghargaan->whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId));
        }

        $penghargaanCount = $penghargaan->count();

        // Latest predikat
        $latestPredikat = $penghargaan->orderByDesc('created_at')->take(5)->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'student_name' => $p->student?->name,
                'category' => $p->kategori,
                'jujur' => $p->jujur,
                'disiplin' => $p->disiplin,
                'peduli' => $p->peduli,
                'adab' => $p->adab,
                'kehadiran' => $p->kehadiran,
                'keaktifan' => $p->keaktifan,
                'nr_final' => $p->nr_final,
                'ket' => $p->ket,
                'created_at' => $p->created_at?->toIso8601String(),
            ]);

        // Pembiasaan data
        $pembiasaanRecords = $pembiasaan->with(['student:id,name'])
            ->orderByDesc('created_at')
            ->take(10)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'student_name' => $p->student?->name,
                'skor_doa' => $p->skor_doa,
                'skor_hiwar' => $p->skor_hiwar,
                'skor_conversation' => $p->skor_conversation,
                'total_score' => (float) $p->skor_doa + (float) $p->skor_hiwar + (float) $p->skor_conversation,
                'recorded_at' => $p->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'pembiasaan_stats' => [
                    'avg_doa' => $pembiasaanStats->avg_doa ? round((float) $pembiasaanStats->avg_doa, 2) : 0,
                    'avg_hiwar' => $pembiasaanStats->avg_hiwar ? round((float) $pembiasaanStats->avg_hiwar, 2) : 0,
                    'avg_conversation' => $pembiasaanStats->avg_conv ? round((float) $pembiasaanStats->avg_conv, 2) : 0,
                    'total_records' => (int) $pembiasaanStats->total_records,
                ],
                'penghargaan_count' => $penghargaanCount,
                'latest_penghargaan' => $latestPredikat,
                'pembiasaan_records' => $pembiasaanRecords,
            ],
        ]);
    }

    // ── GET /api/mobile/v1/academic/rewards ──────────────────���─────────

    public function rewards(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId');

        $studentIds = WaliSantri::where('user_id', $user->id)->active()->pluck('student_id');

        $query = \App\Models\PenghargaanAkademik::whereIn('student_id', $studentIds)
            ->where('is_active', true);

        if ($schoolId) {
            $query->whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId));
        }

        $records = $query->orderByDesc('created_at')->get()->map(fn ($p) => [
            'id' => $p->id,
            'student_id' => $p->student_id,
            'student_name' => $p->student?->name,
            'kategori' => $p->kategori,
            'kategori_label' => $this->kategoriLabel($p->kategori),
            'nr_final' => $p->nr_final,
            'ket' => $p->ket,
            'created_at' => $p->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'data' => ['penghargaan' => $records, 'total' => $records->count()],
        ]);
    }

    private function kategoriLabel(?string $kategori): string
    {
        return match ($kategori) {
            'akademik' => 'Akademik',
            'non_akademik' => 'Non-Akademik',
            'sikap' => 'Sikap',
            'kehadiran' => 'Kehadiran',
            default => ucfirst($kategori ?? ''),
        };
    }
}
