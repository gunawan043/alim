<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\NilaiSumatif;
use App\Models\PembiasaanPagi;
use App\Models\RaportRegistration;
use App\Models\StudentHealthCheckup;
use App\Models\WaliSantri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RaportController extends Controller
{
    // ── GET /api/mobile/v1/raport/status ───────────────────────────────

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId');

        $studentIds = WaliSantri::where('user_id', $user->id)
            ->active()
            ->pluck('student_id');

        $query = RaportRegistration::whereIn('student_id', $studentIds);

        if ($schoolId) {
            // RaportRegistration has academic_year_id, join SchoolYear
            $query->whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId));
        }

        $registrations = $query->orderByDesc('created_at')
            ->take(10)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'student_id' => $r->student_id,
                'student_name' => $r->student?->name,
                'study_group' => $r->studyGroup?->name,
                'semester' => $r->semester,
                'tahun_ajaran' => $r->academicYear?->name ?? '',
                'status' => $r->status,
                'status_label' => $this->raportStatusLabel($r->status),
                'finalized_at' => $r->finalized_at?->toIso8601String(),
            ]);

        $anyPublished = $registrations->contains(fn ($r) => $r['status'] === 'published');

        return response()->json([
            'success' => true,
            'data' => [
                'registrations' => $registrations,
                'is_published' => $anyPublished,
                'tahun_ajaran' => AcademicYear::orderByDesc('start_date')->first()?->name,
            ],
        ]);
    }

    // ── GET /api/mobile/v1/raport/{student_id} ────────────────────────

    public function show(Request $request, string $studentId): JsonResponse
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId');

        $waliLink = WaliSantri::where('user_id', $user->id)
            ->where('student_id', $studentId)
            ->active()
            ->first();

        if (! $waliLink) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'STUDENT_NOT_FOUND',
                    'message' => 'Santri tidak ditemukan dalam daftar wali.',
                ],
            ], 403);
        }

        $query = RaportRegistration::where('student_id', $studentId);

        if ($schoolId) {
            $query->whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId));
        }

        // Get the latest finalized one, or current one
        $reg = $query->orderByDesc('created_at')->first();

        if (! $reg) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RAPORT_NOT_FOUND',
                    'message' => 'Belum ada data raport untuk santri ini.',
                ],
            ], 404);
        }

        $regWithRelations = $reg->loadMissing(['academicYear', 'student']);

        // Fetch NilaiSumatif entries
        $nilai = NilaiSumatif::query()
            ->join('subjects', 'nilai_sumatifs.subject_id', '=', 'subjects.id')
            ->where('nilai_sumatifs.student_id', $studentId)
            ->where('nilai_sumatifs.academic_year_id', $reg->academic_year_id)
            ->where('nilai_sumatifs.semester', $reg->semester)
            ->select(
                'subjects.name as subject_name',
                'nilai_sumatifs.id',
                'nilai_sumatifs.s1',
                'nilai_sumatifs.s2',
                'nilai_sumatifs.s3',
                'nilai_sumatifs.s4',
                'nilai_sumatifs.s5',
                'nilai_sumatifs.s6',
                'nilai_sumatifs.rs',
                'nilai_sumatifs.sts',
                'nilai_sumatifs.raport_sts',
                'nilai_sumatifs.sas',
                'nilai_sumatifs.rsa',
                'nilai_sumatifs.nr_murni',
                'nilai_sumatifs.nr_final',
                'nilai_sumatifs.ket'
            )
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'subject_name' => $row->subject_name,
                's1' => $row->s1,
                's2' => $row->s2,
                's3' => $row->s3,
                's4' => $row->s4,
                's5' => $row->s5,
                's6' => $row->s6,
                'rs' => $row->rs,
                'sts' => $row->sts,
                'raport_sts' => $row->raport_sts,
                'sas' => $row->sas,
                'rsa' => $row->rsa,
                'nr_murni' => $row->nr_murni,
                'nr_final' => $row->nr_final,
                'ket' => $row->ket,
            ]);

        // Get Pembiasaan data
        $pembiasaan = PembiasaanPagi::where('student_id', $studentId)
            ->where('academic_year_id', $reg->academic_year_id)
            ->where('semester', $reg->semester)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'skor_doa' => $p->skor_doa,
                'skor_hiwar' => $p->skor_hiwar,
                'skor_conversation' => $p->skor_conversation,
            ]);

        // Get Health data
        $health = StudentHealthCheckup::where('student_id', $studentId)
            ->where('academic_year_id', $reg->academic_year_id)
            ->orderByDesc('checkup_date')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'registrasi' => [
                    'id' => $reg->id,
                    'student_id' => $reg->student_id,
                    'student_name' => $regWithRelations->student?->name,
                    'study_group' => $regWithRelations->studyGroup?->name,
                    'semester' => $reg->semester,
                    'tahun_ajaran' => $regWithRelations->academicYear?->name ?? '',
                    'status' => $reg->status,
                    'status_label' => $this->raportStatusLabel($reg->status),
                    'finalized_at' => $reg->finalized_at?->toIso8601String(),
                ],
                'nilai' => $nilai,
                'pembiasaan' => $pembiasaan,
                'health_check' => $health ? [
                    'checkup_date' => $health->checkup_date?->toIso8601String(),
                    'checkup_type' => $health->checkup_type,
                    'height_cm' => $health->height_cm,
                    'weight_kg' => $health->weight_kg,
                    'bmi' => $health->bmi,
                    'bmi_category' => $health->bmi_category,
                    'vision_left' => $health->vision_left,
                    'vision_right' => $health->vision_right,
                    'notes' => $health->notes,
                ] : null,
            ],
        ]);
    }

    private function raportStatusLabel(?string $status): string
    {
        return match ($status) {
            'registered' => 'Terdaftar',
            'in_progress' => 'Dalam Proses',
            'completed' => 'Selesai',
            'published' => 'Diterbitkan',
            default => $status ?? 'Unknown',
        };
    }
}
