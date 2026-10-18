<?php

namespace App\Services\Ai;

use App\Models\AiChatConversation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Orkestrasi chat AI: system prompt, riwayat, tool data agregat, dan simpan
 * percakapan. Tidak pernah mengirim identitas pribadi ke model.
 */
class AiChatService
{
    public function __construct(
        private readonly AiProviderService $providers,
        private readonly AiDataToolService $dataTools,
    ) {}

    /**
     * @param  array{school_id?: ?string, global?: bool}  $context
     * @return array{ok: bool, reply?: string, conversation_id?: string, title?: string, model?: string, message?: string}
     */
    public function send(
        User $user,
        array $context,
        ?AiChatConversation $conversation,
        string $message,
        ?string $page = null,
    ): array {
        $provider = $this->providers->defaultProvider();

        if (! $provider) {
            return ['ok' => false, 'message' => 'AI belum aktif. Super Admin perlu mengaktifkan provider di Pengaturan AI.'];
        }

        $conversation ??= AiChatConversation::create([
            'user_id' => $user->id,
            'school_id' => $context['school_id'] ?? null,
            'title' => Str::limit($message, 140, ''),
        ]);

        $history = $conversation->messages()
            ->latest()
            ->limit((int) config('ai_chat.max_history_messages', 10))
            ->get()
            ->reverse();

        $snapshot = $this->dataTools->snapshot($context);

        $system = rtrim((string) config('ai_chat.system_prompt'))
            ."\n\n".rtrim((string) config('ai_chat.app_guide'))
            ."\n\nRINGKASAN DATA SAAT INI (agregat, bukan data pribadi):\n"
            .json_encode($snapshot, JSON_UNESCAPED_UNICODE)
            ."\n\nHALAMAN AKTIF: ".($page ?: '-');

        $messages = [['role' => 'system', 'content' => $system]];

        foreach ($history as $item) {
            if (! in_array($item->role, ['user', 'assistant'], true) || trim((string) $item->content) === '') {
                continue;
            }

            $messages[] = ['role' => $item->role, 'content' => (string) $item->content];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        $tools = $this->dataTools->definitions($context);
        $toolsEnabled = true;
        $usedTools = [];
        $reply = null;

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $result = $this->providers->chat($messages, [
                'temperature' => (float) config('ai_chat.temperature', 0.3),
                'max_tokens' => (int) config('ai_chat.max_output_tokens', 900),
                'timeout' => 60,
                'tools' => $toolsEnabled ? $tools : [],
            ], $provider);

            if (! $result['ok']) {
                $error = (string) ($result['error'] ?? '');

                // Sebagian endpoint OpenAI-compatible belum mendukung tools.
                if ($toolsEnabled && $attempt === 0 && preg_match('/tool|function/i', $error)) {
                    $toolsEnabled = false;
                    $attempt--;

                    continue;
                }

                return ['ok' => false, 'message' => 'AI sedang tidak dapat menjawab. Coba lagi beberapa saat lagi.'];
            }

            if (! empty($result['tool_calls'])) {
                $messages[] = [
                    'role' => 'assistant',
                    'content' => $result['content'] !== '' ? $result['content'] : null,
                    'tool_calls' => $result['tool_calls'],
                ];

                foreach ($result['tool_calls'] as $call) {
                    $name = (string) data_get($call, 'function.name', '');
                    $arguments = json_decode((string) data_get($call, 'function.arguments', '{}'), true);
                    $arguments = is_array($arguments) ? $arguments : [];

                    if ($name === '') {
                        continue;
                    }

                    $usedTools[] = ['name' => $name, 'arguments' => $arguments];
                    $output = $this->dataTools->execute($name, $arguments, $context);

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => (string) data_get($call, 'id', ''),
                        'name' => $name,
                        'content' => json_encode($output, JSON_UNESCAPED_UNICODE),
                    ];
                }

                continue;
            }

            $reply = trim((string) $result['content']);
            break;
        }

        if ($reply === null || $reply === '') {
            return ['ok' => false, 'message' => 'AI belum menghasilkan jawaban. Coba pertanyaan yang lebih spesifik.'];
        }

        DB::transaction(function () use ($conversation, $message, $reply, $provider, $usedTools) {
            $conversation->messages()->create([
                'role' => 'user',
                'content' => $message,
            ]);

            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $reply,
                'metadata' => [
                    'provider' => $provider->name,
                    'model' => $provider->model,
                    'tools' => $usedTools,
                ],
            ]);

            $conversation->forceFill([
                'provider' => $provider->name,
                'model' => $provider->model,
                'title' => $conversation->title ?: Str::limit($message, 140, ''),
                'last_message_at' => now(),
            ])->save();
        });

        return [
            'ok' => true,
            'reply' => $reply,
            'conversation_id' => $conversation->id,
            'title' => $conversation->title,
            'model' => $provider->model,
        ];
    }
}
