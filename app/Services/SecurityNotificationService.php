<?php

namespace App\Services;

use App\Models\User;
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
    ) {}

    public function login(User $user, Request $request): void
    {
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
            'Login ke akun ALIM berhasil pada '.$this->timestamp().' dari IP '.$request->ip()
                .'. Jika bukan Anda, segera ubah password.',
            $request,
            sendEmail: true,
        );
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

    private function timestamp(): string
    {
        return now()->locale('id')->translatedFormat('d F Y H:i');
    }
}
