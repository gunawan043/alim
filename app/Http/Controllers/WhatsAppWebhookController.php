<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Webhook resmi Meta WhatsApp Cloud API.
 *
 * GET  /webhook/whatsapp  → verifikasi hub.challenge
 * POST /webhook/whatsapp  → status pengiriman (sent/delivered/read/failed)
 */
class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = (string) $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');
        $expected = trim((string) SystemSetting::get('wa_meta_verify_token', ''));

        if ($mode === 'subscribe' && $expected !== '' && hash_equals($expected, $token)) {
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request)
    {
        $secret = trim((string) SystemSetting::get('wa_meta_app_secret', ''));

        if ($secret !== '' && ! $this->hasValidSignature($request, $secret)) {
            return response()->json(['status' => 'invalid signature'], 403);
        }

        $payload = $request->all();

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                foreach ($value['statuses'] ?? [] as $status) {
                    $messageId = $status['id'] ?? null;
                    $state = strtolower((string) ($status['status'] ?? ''));

                    if (! $messageId || $state === '') {
                        continue;
                    }

                    $dbStatus = match ($state) {
                        'failed' => 'failed',
                        'sent', 'delivered', 'read' => 'sent',
                        default => 'sent',
                    };

                    DB::table('wa_message_logs')
                        ->where('provider_message_id', $messageId)
                        ->update([
                            'status' => $dbStatus,
                            'error' => $state === 'failed'
                                ? mb_substr(json_encode($status['errors'] ?? []), 0, 500)
                                : null,
                            'sent_at' => in_array($state, ['sent', 'delivered', 'read'], true) ? now() : null,
                            'updated_at' => now(),
                        ]);
                }

                // Pesan masuk (opsional): catat untuk audit, tanpa auto-reply.
                foreach ($value['messages'] ?? [] as $message) {
                    Log::info('[WA-INBOUND]', [
                        'from' => $message['from'] ?? null,
                        'type' => $message['type'] ?? null,
                        'id' => $message['id'] ?? null,
                    ]);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function hasValidSignature(Request $request, string $secret): bool
    {
        $signature = (string) $request->header('X-Hub-Signature-256', '');

        if ($signature === '') {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
