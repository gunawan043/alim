<?php

namespace App\Listeners;

use App\Services\NotificationUniversalService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

/**
 * Notifikasi in-app saat pengguna login: info perangkat, browser, IP, dan waktu.
 */
class SendLoginNotification
{
    public function __construct(private readonly NotificationUniversalService $notifications) {}

    public function handle(Login $event): void
    {
        try {
            $user = $event->user;
            $agent = (string) request()->userAgent();

            $platform = match (true) {
                str_contains($agent, 'Android') => 'Android',
                str_contains($agent, 'iPhone'), str_contains($agent, 'iPad') => 'iOS',
                str_contains($agent, 'Windows') => 'Windows',
                str_contains($agent, 'Macintosh') => 'macOS',
                str_contains($agent, 'Linux') => 'Linux',
                default => 'Perangkat tidak dikenal',
            };

            $browser = match (true) {
                str_contains($agent, 'Edg') => 'Edge',
                str_contains($agent, 'Chrome') => 'Chrome',
                str_contains($agent, 'Firefox') => 'Firefox',
                str_contains($agent, 'Safari') => 'Safari',
                default => 'Browser',
            };

            $this->notifications->send($user->id, [
                'module' => 'auth',
                'type' => 'warning',
                'action' => 'login',
                'title' => 'Login baru terdeteksi',
                'message' => 'Akun Anda login pada '.$platform.' · '.$browser
                    .' · IP '.request()->ip().' · '.now()->format('d/m/Y H:i').' WITA.'
                    .' Jika bukan Anda, segera ganti kata sandi.',
                'priority' => 'high',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Notif login gagal: '.$e->getMessage());
        }
    }
}
