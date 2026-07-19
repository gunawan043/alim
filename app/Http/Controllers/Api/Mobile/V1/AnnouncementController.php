<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\DormitoryPost;
use App\Models\WaliSantri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    // ── GET /api/mobile/v1/announcements ─────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId');

        $limit = min((int) $request->query('limit', 20), 100);
        $offset = max((int) $request->query('offset', 0), 0);

        $studentIds = WaliSantri::where('user_id', $user->id)
            ->active()
            ->pluck('student_id');

        $dormitoryIds = \App\Models\DormitoryResident::whereIn('student_id', $studentIds)
            ->where('is_active', true)
            ->pluck('dormitory_id')
            ->unique();

        $query = DormitoryPost::with(['dormitory:id,name', 'creator:id,name'])
            ->where('is_active', true)
            ->whereIn('visibility', ['wali', 'umum'])
            ->where(function ($q) use ($dormitoryIds) {
                $q->whereIn('dormitory_id', $dormitoryIds)
                    ->orWhereNull('dormitory_id');
            });

        if ($schoolId) {
            $query->whereHas('dormitory', fn ($q) => $q->where('school_id', $schoolId));
        }

        $total = $query->count();

        $records = $query->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'content' => $p->content,
                'category' => $p->category,
                'category_label' => $p->category_text,
                'visibility' => $p->visibility,
                'visibility_label' => $p->visibility_text,
                'needs_response' => $p->needs_response,
                'is_pinned' => $p->is_pinned,
                'dormitory' => $p->dormitory ? [
                    'id' => $p->dormitory->id,
                    'name' => $p->dormitory->name,
                ] : null,
                'creator' => $p->creator?->name,
                'attachment_url' => $p->attachment_path
                    ? asset('storage/'.$p->attachment_path)
                    : null,
                'created_at' => $p->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'announcements' => $records,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    // ── GET /api/mobile/v1/announcements/{id} ────────────────────────────

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId');

        $post = DormitoryPost::with(['dormitory:id,name', 'creator:id,name'])
            ->where('id', $id)
            ->where('is_active', true)
            ->first();

        if (! $post) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ANNOUNCEMENT_NOT_FOUND',
                    'message' => 'Pengumuman tidak ditemukan.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $post->id,
                'title' => $post->title,
                'content' => $post->content,
                'category' => $post->category,
                'category_label' => $post->category_text,
                'visibility' => $post->visibility,
                'needs_response' => $post->needs_response,
                'is_pinned' => $post->is_pinned,
                'dormitory' => $post->dormitory ? [
                    'id' => $post->dormitory->id,
                    'name' => $post->dormitory->name,
                ] : null,
                'creator' => $post->creator?->name,
                'attachment_url' => $post->attachment_path
                    ? asset('storage/'.$post->attachment_path)
                    : null,
                'created_at' => $post->created_at?->toIso8601String(),
            ],
        ]);
    }
}
