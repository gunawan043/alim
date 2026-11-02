<?php

namespace App\Jobs;

use App\Services\Notifications\WhatsAppGatewayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job pengiriman WA universal (dipakai NotificationUniversalService).
 * Delegasi ke WhatsAppGatewayService (fonnte/wablas/generic/log).
 */
class SendUniversalWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public mixed $user,
        public mixed $notification,
        public string $phone,
        public array $context = [],
    ) {}

    public function handle(WhatsAppGatewayService $gateway): void
    {
        $message = (string) ($this->notification->message ?? $this->notification->title ?? '');

        if ($message === '') {
            return;
        }

        $message = $this->appendActionLink($message, $this->notification->action_url ?? null);

        $gateway->send($this->phone, $message, array_merge($this->context, [
            'notification_id' => $this->notification->id ?? null,
            'user_id' => $this->user->id ?? null,
        ]));
    }

    /**
     * Tambahkan tautan halaman terkait agar pesan WA bisa langsung dibuka
     * dari ponsel petugas. URL relatif (hasil normalisasi model) dijadikan
     * absolut memakai APP_URL.
     */
    private function appendActionLink(string $message, ?string $actionUrl): string
    {
        $actionUrl = trim((string) $actionUrl);

        if ($actionUrl === '' || str_contains($message, $actionUrl)) {
            return $message;
        }

        $link = str_starts_with($actionUrl, 'http') ? $actionUrl : url($actionUrl);

        return $message."\n\n".$link;
    }
}
