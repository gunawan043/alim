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

        $gateway->send($this->phone, $message, array_merge($this->context, [
            'notification_id' => $this->notification->id ?? null,
            'user_id' => $this->user->id ?? null,
        ]));
    }
}
