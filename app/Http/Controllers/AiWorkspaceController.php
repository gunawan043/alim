<?php

namespace App\Http\Controllers;

use App\Models\AiChatConversation;
use App\Models\AiProviderSetting;
use App\Services\Ai\AiChatService;
use App\Services\Ai\AiContextService;
use App\Services\Ai\AiProviderPresets;
use App\Services\Ai\AiProviderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Halaman kerja AI penuh: landing, chat, riwayat, dan streaming SSE.
 */
class AiWorkspaceController extends Controller
{
    public function index(
        Request $request,
        string $userId,
        AiProviderService $providers,
        AiContextService $contextService,
    ) {
        $user = $request->user();
        $context = $contextService->build($user, $request);

        $conversations = AiChatConversation::query()
            ->where('user_id', $user->id)
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get(['id', 'title', 'mode', 'status', 'provider', 'model', 'last_message_at']);

        $flashProvider = $this->readyProvider($providers, 'flash');

        return view('ai.index', [
            'userId' => $userId,
            'conversations' => $conversations,
            'suggestions' => (array) config('ai.suggestions', []),
            'scopeLabel' => $contextService->scopeLabel($context),
            'isBroad' => (bool) ($context['is_broad'] ?? false),
            'providerReady' => $flashProvider !== null,
            'providerLabel' => $flashProvider?->name,
            'providerModel' => $flashProvider?->model,
            'models' => [
                'flash' => [
                    'label' => 'Cepat',
                    'model' => $flashProvider?->model ?? (string) config('ai.models.flash', 'deepseek-flash'),
                    'ready' => $flashProvider !== null,
                ],
                'pro' => [
                    'label' => 'Analisis berat',
                    'model' => $flashProvider?->model ?? (string) config('ai.models.pro', 'deepseek-v4-pro'),
                    'ready' => $flashProvider !== null,
                ],
            ],
        ]);
    }

    private function readyProvider(AiProviderService $providers, string $quality): ?AiProviderSetting
    {
        foreach ($providers->providersForQuality($quality) as $provider) {
            $preset = AiProviderPresets::get($provider->provider);
            $needsKey = (bool) ($preset['needs_key'] ?? true);

            if (! $needsKey || $provider->hasApiKey()) {
                return $provider;
            }
        }

        return null;
    }

    public function stream(
        Request $request,
        string $userId,
        AiChatService $chat,
        AiContextService $contextService,
    ): StreamedResponse {
        $validated = $request->validate([
            'message' => 'required|string|max:'.(int) config('ai.max_message_chars', 4000),
            'conversation_id' => 'nullable|uuid',
            'page' => 'nullable|string|max:150',
            'quality' => 'nullable|in:flash,pro',
            'client_message_id' => 'nullable|uuid',
        ]);

        $user = $request->user();
        $context = $contextService->build($user, $request);

        $conversation = null;
        if (! empty($validated['conversation_id'])) {
            $conversation = AiChatConversation::query()
                ->where('user_id', $user->id)
                ->find($validated['conversation_id']);

            if (! $conversation) {
                abort(404, 'Percakapan tidak ditemukan.');
            }
        }

        $quality = $validated['quality'] ?? config('ai.default_quality', 'flash');
        $lock = Cache::lock(
            'ai-chat-send:'.sha1($user->id.'|'.trim($validated['message'])),
            240,
        );

        if ($lock && ! $lock->get()) {
            return response()->stream(function () {
                echo 'event: error'."\n";
                echo 'data: '.json_encode([
                    'message' => 'Pesan ini sedang diproses. Tunggu sebentar.',
                    'duplicate' => true,
                ], JSON_UNESCAPED_UNICODE)."\n\n";
                @ob_flush();
                @flush();
            }, 200, [
                'Content-Type' => 'text/event-stream; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-transform',
                'X-Accel-Buffering' => 'no',
            ]);
        }

        return response()->stream(function () use ($chat, $context, $conversation, $validated, $quality, $user, $lock) {
            @ini_set('zlib.output_compression', '0');
            @set_time_limit(180);
            ignore_user_abort(true);

            $emit = function (string $event, array $payload): void {
                echo 'event: '.$event."\n";
                echo 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";

                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                @flush();
            };

            try {
                $emit('status', ['message' => 'Menyiapkan konteks dan data...']);

                $result = $chat->send(
                    $user,
                    $context,
                    $conversation,
                    trim($validated['message']),
                    $validated['page'] ?? null,
                    $quality,
                    $validated['client_message_id'] ?? null,
                );

                if (! ($result['ok'] ?? false)) {
                    $emit('error', ['message' => $result['message'] ?? 'AI sedang tidak dapat menjawab.']);

                    return;
                }

                $emit('status', ['message' => 'Menyusun jawaban visual...']);

                $text = (string) ($result['reply'] ?? '');
                $length = mb_strlen($text);
                $chunkSize = 36;

                for ($offset = 0; $offset < $length; $offset += $chunkSize) {
                    if (connection_aborted()) {
                        return;
                    }

                    $emit('delta', ['text' => mb_substr($text, $offset, $chunkSize)]);
                    usleep(9000);
                }

                $emit('blocks', [
                    'blocks' => $result['blocks'] ?? [],
                    'sources' => $result['sources'] ?? [],
                ]);

                $emit('done', [
                    'conversation_id' => $result['conversation_id'] ?? null,
                    'message_id' => $result['message_id'] ?? null,
                    'title' => $result['title'] ?? null,
                    'model' => $result['model'] ?? null,
                    'provider' => $result['provider'] ?? null,
                    'quality' => $result['quality'] ?? $quality,
                    'usage' => $result['usage'] ?? [],
                    'cost' => $result['cost'] ?? 0,
                ]);
            } finally {
                $lock?->release();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }
}
