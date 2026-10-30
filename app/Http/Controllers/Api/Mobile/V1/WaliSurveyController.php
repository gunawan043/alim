<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\HumasSatisfactionResponse;
use App\Models\HumasSatisfactionSurvey;
use App\Models\WaliSantri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Survei kepuasan untuk wali santri.
 *
 * Cakupan: survei audience all/wali, global (school_id null) atau sekolah
 * milik anak yang terhubung. Wali dengan anak di beberapa sekolah melihat
 * survei dari sekolah-sekolah tersebut.
 */
class WaliSurveyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $schoolIds = $this->schoolIds($request->user()->id);

        $surveys = HumasSatisfactionSurvey::active()
            ->whereIn('audience', ['all', 'wali'])
            ->where(function ($query) use ($schoolIds) {
                $query->whereNull('school_id');

                if ($schoolIds->isNotEmpty()) {
                    $query->orWhereIn('school_id', $schoolIds);
                }
            })
            ->with(['responses' => fn ($query) => $query->where('user_id', $request->user()->id)])
            ->latest()
            ->get()
            ->map(fn (HumasSatisfactionSurvey $survey) => $this->format($survey, $request->user()->id));

        return response()->json([
            'success' => true,
            'data' => ['surveys' => $surveys],
        ]);
    }

    public function show(Request $request, string $userId, string $id): JsonResponse
    {
        $survey = $this->findSurvey($request, $id);

        if (! $survey) {
            return $this->notFound();
        }

        return response()->json([
            'success' => true,
            'data' => $this->format($survey, $request->user()->id),
        ]);
    }

    public function submit(Request $request, string $userId, string $id): JsonResponse
    {
        $survey = $this->findSurvey($request, $id);

        if (! $survey) {
            return $this->notFound();
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        HumasSatisfactionResponse::updateOrCreate(
            [
                'humas_satisfaction_survey_id' => $survey->id,
                'user_id' => $request->user()->id,
            ],
            [
                'rating' => $validated['rating'],
                'feedback' => $validated['feedback'] ?? null,
                'submitted_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih, penilaian Anda sudah tersimpan.',
        ]);
    }

    private function findSurvey(Request $request, string $id): ?HumasSatisfactionSurvey
    {
        $schoolIds = $this->schoolIds($request->user()->id);

        return HumasSatisfactionSurvey::active()
            ->whereIn('audience', ['all', 'wali'])
            ->where(function ($query) use ($schoolIds) {
                $query->whereNull('school_id');

                if ($schoolIds->isNotEmpty()) {
                    $query->orWhereIn('school_id', $schoolIds);
                }
            })
            ->with(['responses' => fn ($query) => $query->where('user_id', $request->user()->id)])
            ->find($id);
    }

    /**
     * @return Collection<int, string>
     */
    private function schoolIds(string $userId)
    {
        return WaliSantri::where('user_id', $userId)
            ->active()
            ->whereNotNull('school_id')
            ->pluck('school_id')
            ->unique()
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function format(HumasSatisfactionSurvey $survey, string $userId): array
    {
        $response = $survey->responses->firstWhere('user_id', $userId);

        return [
            'id' => $survey->id,
            'title' => $survey->title,
            'description' => $survey->description,
            'audience' => $survey->audience,
            'scope' => $survey->school_id ? 'school' : 'global',
            'school_id' => $survey->school_id,
            'starts_at' => $survey->starts_at?->toIso8601String(),
            'ends_at' => $survey->ends_at?->toIso8601String(),
            'has_responded' => $response !== null,
            'my_rating' => $response?->rating,
            'my_feedback' => $response?->feedback,
            'submitted_at' => $response?->submitted_at?->toIso8601String(),
        ];
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'SURVEY_NOT_FOUND',
                'message' => 'Survei tidak ditemukan atau tidak tersedia untuk Anda.',
            ],
        ], 404);
    }
}
