<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\WaliSantri;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Daftar sekolah yang dapat dipilih wali.
 *
 * Wali yang memiliki anak di lebih dari satu sekolah harus mengirim
 * X-Active-School-Id pada request berikutnya. Endpoint ini menjadi sumber
 * pilihan sekolah untuk aplikasi mobile.
 */
class WaliSchoolController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $links = WaliSantri::with(['school:id,name,school_code,is_active'])
            ->where('user_id', $request->user()->id)
            ->where('status', WaliSantri::STATUS_ACTIVE)
            ->whereNotNull('school_id')
            ->get();

        $schools = $links
            ->groupBy('school_id')
            ->map(function ($schoolLinks, string $schoolId) {
                $school = $schoolLinks->first()?->school;

                return [
                    'id' => $schoolId,
                    'name' => $school?->name ?? 'Satuan Pendidikan',
                    'school_code' => $school?->school_code,
                    'is_active' => (bool) ($school?->is_active ?? true),
                    'students_count' => $schoolLinks->count(),
                ];
            })
            ->sortBy('name')
            ->values()
            ->all();

        $activeSchoolId = $request->attributes->get('schoolContextId');

        return $this->ok([
            'schools' => $schools,
            'active_school_id' => $activeSchoolId,
            'requires_selection' => count($schools) > 0 && $activeSchoolId === null,
        ], 'Daftar sekolah berhasil diambil.');
    }
}
