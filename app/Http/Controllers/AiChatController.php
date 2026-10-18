<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AiChatConversation;
use App\Models\TeachingAssignment;
use App\Services\Ai\AiChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Chat AI untuk pegawai. Percakapan bersifat pribadi; Super Admin dapat
 * memantau melalui AiChatMonitorController.
 */
class AiChatController extends Controller
{
    public function send(Request $request, string $userId, AiChatService $chat)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:'.(int) config('ai_chat.max_message_chars', 2000),
            'conversation_id' => 'nullable|uuid',
            'page' => 'nullable|string|max:150',
            'mode' => 'nullable|in:ai,support',
        ]);

        $conversation = null;

        if (! empty($validated['conversation_id'])) {
            $conversation = AiChatConversation::query()
                ->where('user_id', $request->user()->id)
                ->find($validated['conversation_id']);

            if (! $conversation) {
                return response()->json(['ok' => false, 'message' => 'Percakapan tidak ditemukan.'], 404);
            }
        }

        $user = $request->user();
        $isSuper = method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
        $schoolId = $request->attributes->get('schoolContextId');
        $activeYear = AcademicYear::query()->where('is_active', true)->first();

        $assignments = TeachingAssignment::query()
            ->with(['studyGroup:id,name,grade_level_id', 'studyGroup.gradeLevel:id,name', 'subject:id,name'])
            ->where('teacher_id', $user->id)
            ->where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id))
            ->get();

        $classes = $assignments->pluck('studyGroup')->filter()->unique('id')->map(fn ($group) => [
            'id' => $group->id,
            'name' => $group->name,
            'grade_level' => $group->gradeLevel?->name,
        ])->values()->all();

        $subjects = $assignments->pluck('subject')->filter()->unique('id')->pluck('name')->values()->all();

        $context = [
            'school_id' => $schoolId,
            'global' => (bool) $request->attributes->get('isGlobalView', false),
            'my_study_group_ids' => $assignments->pluck('study_group_id')->filter()->unique()->values()->all(),
            'my_teaching' => [
                'academic_year' => $activeYear?->name,
                'classes' => $classes,
                'subjects' => $subjects,
            ],
            'can_view_students' => $isSuper
                || canPermission('student_view')
                || canPermission('student-all-access'),
            'can_view_gtk' => $isSuper
                || canPermission('gtk_view')
                || canPermission('gtk_detail_view')
                || canPermission('gtk-view-all')
                || canPermission('gtk-view-school'),
            'can_view_gtk_detail' => $isSuper
                || canPermission('gtk_detail_view')
                || canPermission('gtk-view-all'),
        ];

        $requestedMode = $validated['mode'] ?? 'ai';
        $mode = $requestedMode === 'support' ? 'support' : ($conversation?->mode ?? 'ai');

        if ($mode === 'support') {
            $result = $chat->sendSupport(
                $request->user(),
                $context,
                $conversation,
                trim($validated['message']),
            );

            return response()->json($result, $result['ok'] ? 200 : 503);
        }

        $result = $chat->send(
            $request->user(),
            $context,
            $conversation,
            trim($validated['message']),
            $validated['page'] ?? null,
        );

        return response()->json($result, $result['ok'] ? 200 : 503);
    }

    public function conversations(Request $request, string $userId)
    {
        $items = AiChatConversation::query()
            ->where('user_id', $request->user()->id)
            ->withCount('messages')
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn (AiChatConversation $conversation) => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'mode' => $conversation->mode ?: 'ai',
                'status' => $conversation->status ?: 'open',
                'messages_count' => $conversation->messages_count,
                'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            ])->all();

        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function latest(Request $request, string $userId)
    {
        $conversation = AiChatConversation::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->first();

        if (! $conversation) {
            return response()->json(['ok' => true, 'conversation' => null, 'messages' => []]);
        }

        return response()->json([
            'ok' => true,
            'conversation' => $this->conversationPayload($conversation),
            'messages' => $this->messagePayload($conversation),
        ]);
    }

    public function show(Request $request, string $userId, string $conversationId)
    {
        $conversation = AiChatConversation::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($conversationId);

        return response()->json([
            'ok' => true,
            'conversation' => $this->conversationPayload($conversation),
            'messages' => $this->messagePayload($conversation),
        ]);
    }

    public function resumeAi(Request $request, string $userId, string $conversationId)
    {
        $conversation = AiChatConversation::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($conversationId);

        if ($conversation->mode !== 'support') {
            return response()->json([
                'ok' => true,
                'conversation_id' => $conversation->id,
                'mode' => 'ai',
                'status' => $conversation->status ?: 'open',
            ]);
        }

        $conversation->forceFill([
            'mode' => 'ai',
            'status' => 'open',
            'closed_at' => null,
        ])->save();

        return response()->json([
            'ok' => true,
            'conversation_id' => $conversation->id,
            'mode' => 'ai',
            'status' => 'open',
        ]);
    }

    public function destroy(Request $request, string $userId, string $conversationId)
    {
        $conversation = AiChatConversation::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($conversationId);

        DB::transaction(function () use ($conversation) {
            $conversation->messages()->delete();
            $conversation->delete();
        });

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function conversationPayload(AiChatConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'title' => $conversation->title,
            'model' => $conversation->model,
            'mode' => $conversation->mode ?: 'ai',
            'status' => $conversation->status ?: 'open',
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function messagePayload(AiChatConversation $conversation): array
    {
        return $conversation->messages()
            ->get(['id', 'role', 'content', 'created_at'])
            ->map(fn ($message) => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'created_at' => $message->created_at?->toIso8601String(),
            ])
            ->all();
    }
}
