<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\CutiBalance;
use App\Models\CutiPeriod;
use App\Models\CutiRequest;
use App\Models\CutiTemplate;
use App\Models\WaliSantri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermitController extends Controller
{
    // ── POST /api/mobile/v1/permit/request ─────────────────────────────

    public function request(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'student_id' => 'required|uuid|exists:students,id',
            'type' => 'required|in:izin,sakit,diserahkan_lainnya',
            'reason' => 'required|string|min:10|max:500',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $waliLink = WaliSantri::where('user_id', $user->id)
            ->where('student_id', $validated['student_id'])
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

        // Find the correct cuti template for the permit type
        $template = match ($validated['type']) {
            'sakit' => CutiTemplate::where('jenis', CutiTemplate::JENIS_SAKIT)
                ->where('is_active', true)
                ->first(),
            default => CutiTemplate::where('jenis', 'IZIN')
                ->where('is_active', true)
                ->first(),
        };

        if (! $template) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TEMPLATE_NOT_FOUND',
                    'message' => 'Template cuti untuk jenis ini belum diatur.',
                ],
            ], 422);
        }

        // Lookup student's cuti balance via template relationship
        $academicYearId = $request->attributes->get('academicYearId');
        $periodId = null;

        if ($academicYearId) {
            $period = CutiPeriod::find($academicYearId);
            if ($period) {
                $periodId = $period->id;
            }
        }

        $balance = CutiBalance::where('user_id', $user->id)
            ->where('cuti_template_id', $template->id)
            ->whereNull('cuti_period_id')
            ->sum('tersisa');

        if ((int) $balance <= 0) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INSUFFICIENT_BALANCE',
                    'message' => 'Tidak tersedia sisa cuti untuk periode ini.',
                ],
            ], 422);
        }

        $cuti = CutiRequest::create([
            'user_id' => $user->id,
            'cuti_template_id' => $template->id,
            'cuti_period_id' => $periodId,
            'tanggal_mulai' => $validated['start_date'],
            'tanggal_selesai' => $validated['end_date'],
            'alasan' => $validated['reason'],
            'status' => CutiRequest::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permohonan izin/sakit berhasil diajukan.',
            'data' => [
                'id' => $cuti->id,
                'type' => $validated['type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => CutiRequest::STATUS_PENDING,
            ],
        ], 201);
    }

    // ── GET /api/mobile/v1/permits ─────────────────────────────────────

    public function myPermits(Request $request): JsonResponse
    {
        $user = $request->user();

        $limit = min((int) $request->query('limit', 20), 100);
        $offset = max((int) $request->query('offset', 0), 0);

        $statusFilter = $request->query('status');
        $query = CutiRequest::with(['approver:id,name', 'template:id,nama,jenis'])
            ->where('user_id', $user->id);

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $total = $query->count();

        $records = $query->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'start_date' => $c->tanggal_mulai?->toDateString(),
                'end_date' => $c->tanggal_selesai?->toDateString(),
                'status' => $c->status,
                'status_label' => $c->getStatusLabelAttribute(),
                'reason' => $c->alasan,
                'approved_at' => $c->approved_at?->toIso8601String(),
                'approver' => $c->approver?->name,
                'created_at' => $c->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'permits' => $records,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }
}