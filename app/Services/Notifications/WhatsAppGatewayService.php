<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gateway WhatsApp provider-agnostic (alternatif tanpa API resmi).
 *
 * Driver: fonnte | wablas | generic | log (gratis/manual).
 * Konfigurasi: config/whatsapp.php, dapat ditimpa lewat settings:
 *   wa.enabled, wa.provider, wa.token, wa.url, wa.sender
 */
class WhatsAppGatewayService
{
    private string $lastResponseBody = '';

    public function enabled(): bool
    {
        return (bool) $this->config('enabled', true);
    }

    /**
     * Detail respons provider terakhir (untuk pesan flash/uji kirim).
     */
    public function lastResponseDetail(): string
    {
        $json = json_decode($this->lastResponseBody, true);

        if (is_array($json)) {
            return (string) ($json['detail'] ?? $json['reason'] ?? mb_substr($this->lastResponseBody, 0, 160));
        }

        return mb_substr($this->lastResponseBody, 0, 160);
    }

    /**
     * Cek status device di provider (khusus Fonnte) untuk diagnosa.
     *
     * @return array<string, mixed>
     */
    public function checkDevice(): array
    {
        $token = (string) $this->config('token');

        if ($token === '') {
            return ['ok' => false, 'error' => 'Token belum diisi.'];
        }

        try {
            $response = Http::withHeaders(['Authorization' => $token])
                ->post('https://api.fonnte.com/device');

            $json = $response->json() ?? [];

            return [
                'ok' => (bool) ($json['status'] ?? false),
                'http' => $response->status(),
                'device' => $json['device'] ?? null,
                'device_status' => $json['device_status'] ?? null,
                'quota' => $json['quota'] ?? null,
                'expired' => $json['expired'] ?? null,
                'package' => $json['package'] ?? null,
                'raw' => mb_substr((string) $response->body(), 0, 300),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /** Nomor admin ALIM untuk pemberitahuan terpusat. */
    public function centralNumber(): ?string
    {
        $number = $this->config('central_number');

        return $number ? $this->normalizePhone((string) $number) : null;
    }

    /**
     * Mode penerima: central (default — semua ke nomor admin ALIM),
     * guardian (ke wali), both (keduanya).
     */
    public function deliveryMode(): string
    {
        $mode = strtolower((string) $this->config('delivery_mode', 'central'));

        return in_array($mode, ['central', 'guardian', 'both'], true) ? $mode : 'central';
    }

    /**
     * Kirim pesan WA. Mengembalikan true bila berhasil (atau dicatat pada driver log).
     */
    public function send(?string $phone, string $message, array $context = []): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $target = $this->normalizePhone($phone);

        if (! $target || trim($message) === '') {
            return false;
        }

        $provider = strtolower((string) $this->config('provider', 'log'));

        try {
            $sent = match ($provider) {
                'fonnte' => $this->sendViaFonnte($target, $message),
                'wablas' => $this->sendViaWablas($target, $message),
                'generic' => $this->sendViaGeneric($target, $message),
                default => $this->sendViaLog($target, $message, $context),
            };

            $this->logDelivery($target, $message, $context, $provider, $sent);
            $this->persistLog($target, $message, $context, $provider, $sent);

            return $sent;
        } catch (\Throwable $e) {
            Log::warning('WhatsAppGateway gagal mengirim', [
                'provider' => $provider,
                'target' => $target,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Normalisasi nomor Indonesia: 08xxx / 8xxx → 628xxx.
     */
    public function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    // ── Drivers ─────────────────────────────────────────────────────

    private function sendViaFonnte(string $target, string $message): bool
    {
        $response = Http::withHeaders([
            'Authorization' => (string) $this->config('token'),
        ])->asForm()->post($this->config('url') ?: 'https://api.fonnte.com/send', [
            'target' => $target,
            'message' => $message,
            'countryCode' => '62',
        ]);

        $this->lastResponseBody = (string) $response->body();

        // Fonnte mengembalikan status:true meski HTTP 200 — hormati field itu.
        $json = $response->json();

        if (is_array($json) && array_key_exists('status', $json)) {
            return (bool) $json['status'];
        }

        return $response->successful();
    }

    private function sendViaWablas(string $target, string $message): bool
    {
        $url = rtrim((string) ($this->config('url') ?: ''), '/');

        if ($url === '') {
            return false;
        }

        $response = Http::withHeaders([
            'Authorization' => (string) $this->config('token'),
        ])->asForm()->post($url.'/api/send-message', [
            'phone' => $target,
            'message' => $message,
        ]);

        return $response->successful();
    }

    private function sendViaGeneric(string $target, string $message): bool
    {
        $url = (string) ($this->config('url') ?: '');

        if ($url === '') {
            return false;
        }

        $response = Http::withHeaders([
            'Authorization' => (string) $this->config('token'),
        ])->post($url, [
            'phone' => $target,
            'message' => $message,
            'sender' => $this->config('sender'),
        ]);

        return $response->successful();
    }

    /**
     * Mode GRATIS: catat pesan ke log + link wa.me untuk dikirim manual.
     */
    private function sendViaLog(string $target, string $message, array $context): bool
    {
        Log::info('[WA-LOG] Pesan wali santri siap dikirim manual', [
            'target' => $target,
            'wa_me' => 'https://wa.me/'.$target.'?text='.rawurlencode($message),
            'message' => $message,
            'context' => $context,
        ]);

        return true;
    }

    /**
     * Catat pengiriman ke IntegrationEventLog (riwayat sukses/gagal).
     */
    private function logDelivery(string $target, string $message, array $context, string $provider, bool $sent): void
    {
        try {
            \App\Models\IntegrationEventLog::record(
                eventName: 'wa.message',
                sourceModule: 'notifications',
                targetModule: 'whatsapp',
                aggregateId: (string) ($context['reference_id'] ?? $target),
                aggregateType: 'WhatsAppMessage',
                payload: [
                    'event' => $context['event'] ?? null,
                    'provider' => $provider,
                    'target' => $target,
                    'sent' => $sent,
                    'preview' => mb_substr($message, 0, 140),
                ],
                status: $sent
                    ? \App\Models\IntegrationEventLog::STATUS_PROCESSED
                    : \App\Models\IntegrationEventLog::STATUS_FAILED,
            );
        } catch (\Throwable) {
            // Riwayat tidak boleh memblokir pengiriman.
        }
    }

    /**
     * Simpan riwayat pengiriman ke wa_message_logs (tanpa memblokir alur).
     */
    private function persistLog(string $target, string $message, array $context, string $provider, bool $sent): void
    {
        try {
            \Illuminate\Support\Facades\DB::table('wa_message_logs')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'phone' => $target,
                'event' => $context['event'] ?? null,
                'reference_type' => $context['reference_type'] ?? null,
                'reference_id' => $context['reference_id'] ?? null,
                'provider' => $provider,
                'status' => $sent ? ($provider === 'log' ? 'logged' : 'sent') : 'failed',
                'message' => $message,
                'error' => $sent ? null : ($this->lastResponseBody !== '' ? mb_substr($this->lastResponseBody, 0, 500) : 'Pengiriman gagal'),
                'sent_at' => $sent ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            // riwayat tidak boleh memblokir
        }
    }

    private function config(string $key, mixed $default = null): mixed
    {
        if (function_exists('setting')) {
            $candidates = ['wa.'.$key, 'wa_'.$key];

            // Halaman pengaturan menyimpan token/URL sebagai wa_api_token/wa_api_url.
            if ($key === 'token') { $candidates[] = 'wa_api_token'; }
            if ($key === 'url') { $candidates[] = 'wa_api_url'; }

            foreach ($candidates as $settingKey) {
                $value = setting($settingKey, null);

                if ($value !== null && $value !== '') {
                    return $value;
                }
            }
        }

        return config('whatsapp.'.$key, $default);
    }
}
