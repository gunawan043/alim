<?php

namespace App\Services;

use App\Models\User;
use App\Services\Notifications\WhatsAppGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Notifikasi keamanan akun (login, lupa password, reset password).
 *
 * Email transaksional untuk OTP / password berubah tetap dikirim oleh
 * controller terkait. Service ini memastikan peristiwa keamanan juga masuk
 * ke notifikasi sistem (lonceng) dan WhatsApp, serta email untuk login.
 */
class SecurityNotificationService
{
    public function __construct(
        private readonly NotificationUniversalService $notifications,
        private readonly WhatsAppGatewayService $gateway,
    ) {}

    public function login(User $user, Request $request): void
    {
        /*
         * Sesi impersonate (Super Admin masuk sebagai GTK) bukan login
         * pengguna. Jangan kirim notifikasi "Login Berhasil" ke akun GTK
         * yang sedang dimasuki.
         */
        if ($request->hasSession() && $request->session()->has('impersonate.actor_id')) {
            return;
        }

        if ((string) setting('security_login_notify_mode', 'all') === 'new_device') {
            try {
                $fingerprint = hash('sha256', (string) $request->ip().'|'.(string) $request->userAgent());
                $cacheKey = "login-device:{$user->id}:{$fingerprint}";

                if (Cache::has($cacheKey)) {
                    return;
                }

                Cache::put($cacheKey, true, now()->addYear());
            } catch (\Throwable $e) {
                Log::warning('Gagal menyimpan fingerprint perangkat login', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->send(
            $user,
            'login',
            'Login Berhasil',
            'Login ke akun ALIM berhasil. Pengguna: '.$user->name.' ('.$user->email.'). '
                .'Waktu '.$this->timestamp().', IP '.$request->ip()
                .', perangkat '.$this->deviceSummary($request->userAgent())
                .'. Jika bukan Anda, segera ubah password.',
            $request,
            sendEmail: true,
        );

        $this->notifyCentralLogin($user, $request);
    }

    public function passwordForgot(User $user, Request $request): void
    {
        $this->send(
            $user,
            'password_forgot',
            'Permintaan Lupa Password',
            'Permintaan reset password diterima pada '.$this->timestamp().' dari IP '.$request->ip()
                .'. Jika bukan Anda, abaikan permintaan ini.',
            $request,
            sendEmail: false,
        );
    }

    public function passwordReset(User $user, Request $request): void
    {
        $this->send(
            $user,
            'password_reset',
            'Password Berhasil Direset',
            'Password akun ALIM Anda berhasil direset pada '.$this->timestamp().' dari IP '.$request->ip().'.',
            $request,
            sendEmail: false,
        );
    }

    public function passwordChanged(User $user, Request $request): void
    {
        $this->send(
            $user,
            'password_changed',
            'Password Akun Diubah',
            'Password akun ALIM Anda diubah pada '.$this->timestamp().' dari IP '.$request->ip().'.',
            $request,
            sendEmail: false,
        );
    }

    private function send(
        User $user,
        string $action,
        string $title,
        string $message,
        Request $request,
        bool $sendEmail,
    ): void {
        if ((string) setting('security_notify_'.$action, '1') === '0') {
            return;
        }

        try {
            $this->notifications->send($user->id, [
                'module' => 'system',
                'action' => $action,
                'type' => $action === 'login' ? 'info' : 'warning',
                'title' => $title,
                'message' => $message,
                'priority' => $action === 'login' ? 'medium' : 'high',
                'send_email' => $sendEmail,
                'send_whatsapp' => true,
                'send_push' => true,
                'data' => [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'action' => $action,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi keamanan', [
                'user_id' => $user->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Salinan notifikasi login ke nomor admin ALIM (mode pengiriman central
     * atau both) agar admin dapat memeriksa siapa yang sedang login.
     * Pesan ke nomor pengguna sendiri sudah dikirim lewat channel universal.
     */
    private function notifyCentralLogin(User $user, Request $request): void
    {
        if ((string) setting('security_notify_login', '1') === '0'
            || (string) setting('whatsapp_notifications', '1') === '0') {
            return;
        }

        if (! in_array($this->gateway->deliveryMode(), ['central', 'both'], true)) {
            return;
        }

        $central = $this->gateway->centralNumber();

        if (! $central) {
            return;
        }

        $userPhone = $this->notifications->phoneFor($user);

        if ($userPhone && $this->gateway->normalizePhone($userPhone) === $central) {
            return;
        }

        $message = "Login berhasil — ALIM\n\n"
            ."Nama: {$user->name}\n"
            ."Email: {$user->email}\n"
            ."Waktu: {$this->timestamp()}\n"
            ."IP: {$request->ip()}\n"
            .'Perangkat: '.$this->deviceSummary($request->userAgent())."\n\n"
            .'Jika bukan Anda, segera ubah kata sandi.';

        try {
            $this->gateway->send($central, $message, [
                'event' => 'login',
                'user_id' => $user->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi login ke nomor admin', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function deviceSummary(?string $userAgent): string
    {
        $agent = (string) $userAgent;

        $platform = match (true) {
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone'), str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Macintosh') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'perangkat tidak dikenal',
        };

        $browser = match (true) {
            str_contains($agent, 'Edg') => 'Edge',
            str_contains($agent, 'Chrome') => 'Chrome',
            str_contains($agent, 'Firefox') => 'Firefox',
            str_contains($agent, 'Safari') => 'Safari',
            default => 'Browser',
        };

        return $browser.' · '.$platform;
    }

    private function timestamp(): string
    {
        return now()->locale('id')->translatedFormat('d F Y H:i');
    }
}
