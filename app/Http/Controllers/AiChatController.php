<?php

namespace App\Http\Controllers;

use App\Models\AiChatConversation;
use App\Services\Ai\AiChatService;
use App\Services\Ai\AiContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Chat AI untuk pegawai. Percakapan bersifat pribadi; Super Admin dapat
 * memantau melalui AiChatMonitorController.
 */
class AiChatController extends Controller
{
    public function send(Request $request, string $userId, AiChatService $chat, ?AiContextService $contextService = null)
    {
        $contextService ??= app(AiContextService::class);

        $validated = $request->validate([
            'message' => 'required|string|max:'.(int) config('ai.max_message_chars', 4000),
            'conversation_id' => 'nullable|uuid',
            'page' => 'nullable|string|max:150',
            'mode' => 'nullable|in:ai,support',
            'quality' => 'nullable|in:flash,pro',
            'client_message_id' => 'nullable|uuid',
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
        $context = $contextService->build($user, $request);

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

        $clientMessageId = $validated['client_message_id'] ?? null;
        $lock = Cache::lock(
            'ai-chat-send:'.sha1($request->user()->id.'|'.trim($validated['message'])),
            240,
        );

        if ($lock && ! $lock->get()) {
            return response()->json([
                'ok' => false,
                'duplicate' => true,
                'message' => 'Pesan ini sedang diproses. Tunggu sebentar.',
            ], 409);
        }

        try {
            $result = $chat->send(
                $request->user(),
                $context,
                $conversation,
                trim($validated['message']),
                $validated['page'] ?? null,
                $validated['quality'] ?? config('ai.default_quality', 'flash'),
                $clientMessageId,
            );

            return response()->json($result, $result['ok'] ? 200 : 503);
        } finally {
            $lock?->release();
        }
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

    public function feedback(Request $request, string $userId, string $conversationId)
    {
        $validated = $request->validate([
            'message_id' => 'required|uuid',
            'rating' => 'required|in:up,down',
        ]);

        $conversation = AiChatConversation::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($conversationId);

        $message = $conversation->messages()
            ->where('id', $validated['message_id'])
            ->where('role', 'assistant')
            ->firstOrFail();

        $metadata = is_array($message->metadata) ? $message->metadata : [];
        $metadata['rating'] = $validated['rating'];
        $metadata['rated_at'] = now()->toIso8601String();

        $message->update(['metadata' => $metadata]);

        return response()->json(['ok' => true, 'rating' => $validated['rating']]);
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
            'provider' => $conversation->provider,
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
            ->get(['id', 'role', 'content', 'metadata', 'created_at'])
            ->map(function ($message) {
                $metadata = is_array($message->metadata) ? $message->metadata : [];

                return [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'blocks' => $metadata['blocks'] ?? null,
                    'sources' => $metadata['sources'] ?? [],
                    'usage' => $metadata['usage'] ?? [],
                    'cost' => $metadata['cost'] ?? 0,
                    'model' => $metadata['model'] ?? null,
                    'rating' => $metadata['rating'] ?? null,
                    'created_at' => $message->created_at?->toIso8601String(),
                ];
            })
            ->all();
    }
}
