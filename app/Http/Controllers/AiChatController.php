<?php

namespace App\Http\Controllers;

use App\Models\AiChatConversation;
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

        $context = [
            'school_id' => $request->attributes->get('schoolContextId'),
            'global' => (bool) $request->attributes->get('isGlobalView', false),
        ];

        $result = $chat->send(
            $request->user(),
            $context,
            $conversation,
            trim($validated['message']),
            $validated['page'] ?? null,
        );

        return response()->json($result, $result['ok'] ? 200 : 503);
    }

    public function latest(Request $request)
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
