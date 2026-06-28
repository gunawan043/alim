<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\NotificationUniversal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // ── GET /api/mobile/v1/notifications ───────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $limit = min((int) $request->query('limit', 20), 100);
        $offset = max((int) $request->query('offset', 0), 0);
        $unreadOnly = filter_var($request->query('unread_only', false), FILTER_VALIDATE_BOOLEAN);

        $query = NotificationUniversal::where('user_id', $user->id)
            ->notArchived()
            ->notExpired()
            ->orderByDesc('created_at');

        if ($unreadOnly) {
            $query->unread();
        }

        $total = $query->count();
        $unreadCount = NotificationUniversal::where('user_id', $user->id)
            ->unread()
            ->notArchived()
            ->notExpired()
            ->count();

        $records = $query->offset($offset)->limit($limit)->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'module' => $n->module,
                'module_label' => $n->module_label,
                'type' => $n->type,
                'title' => $n->title,
                'message' => $n->message,
                'data' => $n->data,
                'priority' => $n->priority,
                'priority_label' => strip_tags($n->priority_label),
                'is_read' => $n->is_read,
                'read_at' => $n->read_at?->toIso8601String(),
                'action_url' => $n->action_url,
                'action_text' => $n->action_text,
                'reference_type' => $n->reference_type,
                'reference_id' => $n->reference_id,
                'created_at' => $n->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $records,
                'total' => $total,
                'unread_count' => $unreadCount,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    // ── PUT /api/mobile/v1/notifications/{id}/read ─────────────────────────

    public function markRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $notification = NotificationUniversal::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $notification) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOTIFICATION_NOT_FOUND',
                    'message' => 'Notifikasi tidak ditemukan.',
                ],
            ], 404);
        }

        if (! $notification->is_read) {
            $notification->is_read = true;
            $notification->read_at = now();
            $notification->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai telah dibaca.',
            'data' => [
                'id' => $notification->id,
                'is_read' => $notification->is_read,
                'read_at' => $notification->read_at?->toIso8601String(),
            ],
        ]);
    }
}
