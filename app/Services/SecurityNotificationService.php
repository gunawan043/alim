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

    /**
     * Peringatan langsung via WhatsApp saat terjadi percobaan login gagal
     * berulang. Dikirim ke nomor pengguna dan nomor admin ALIM.
     */
    public function failedLogin(User $user, Request $request, int $attempts, bool $locked, ?string $reason = null): void
    {
        if ((string) setting('security_notify_login', '1') === '0'
            || (string) setting('whatsapp_notifications', '1') === '0') {
            return;
        }

        $status = $locked
            ? 'Akun dikunci sementara sampai '.($user->locked_until?->format('d/m/Y H:i') ?? 'waktu yang ditentukan').'.'
            : 'Akun belum dikunci, tetapi aktivitas ini perlu diperiksa.';

        $message = "⚠️ Peringatan Keamanan ALIM\n\n"
            ."Percobaan login gagal berulang terdeteksi.\n\n"
            ."Nama: {$user->name}\n"
            ."Email: {$user->email}\n"
            ."Jumlah percobaan: {$attempts}x\n"
            ."Waktu: {$this->timestamp()}\n"
            ."IP: {$request->ip()}\n"
            .'Perangkat: '.$this->deviceSummary($request->userAgent())."\n"
            .'Alasan: '.($reason ?: 'Password salah')."\n"
            ."Status: {$status}\n\n"
            ."Tindak lanjut:\n"
            ."1. Jika ini bukan Anda, segera ubah password melalui ".route('password.request').".\n"
            ."2. Hubungi Super Admin untuk membuka akun jika terkunci.\n\n"
            .'Abaikan pesan ini jika percobaan tersebut memang dari Anda.';

        $this->sendSecurityWhatsApp($user, $message, [
            'event' => 'security_login_failed',
            'user_id' => $user->id,
            'attempts' => $attempts,
            'locked' => $locked,
        ]);
    }

    /**
     * Peringatan ke admin saat sebuah IP diblokir karena percobaan login.
     */
    public function ipBlocked(string $ip, Request $request, int $attempts): void
    {
        if ((string) setting('security_notify_login', '1') === '0'
            || (string) setting('whatsapp_notifications', '1') === '0') {
            return;
        }

        $message = "🚫 Peringatan Keamanan ALIM\n\n"
            ."IP diblokir sementara karena percobaan login gagal berulang.\n\n"
            ."IP: {$ip}\n"
            ."Jumlah percobaan: {$attempts}x\n"
            ."Waktu: {$this->timestamp()}\n"
            .'Perangkat: '.$this->deviceSummary($request->userAgent())."\n\n"
            ."Buka menu Super Admin → Log & Monitoring → Aktivitas Login untuk melihat detail dan membuka blokir.";

        $this->sendSecurityWhatsApp(null, $message, [
            'event' => 'security_ip_blocked',
            'ip_address' => $ip,
            'attempts' => $attempts,
        ]);
    }

    private function sendSecurityWhatsApp(?User $user, string $message, array $context = []): void
    {
        $numbers = [];

        if ($user && ($phone = $this->notifications->phoneFor($user))) {
            $numbers[$this->gateway->normalizePhone($phone) ?: $phone] = $phone;
        }

        if ($central = $this->gateway->centralNumber()) {
            $numbers[$central] = $central;
        }

        foreach ($numbers as $number) {
            try {
                $this->gateway->send($number, $message, $context);
            } catch (\Throwable $e) {
                Log::warning('Gagal mengirim WhatsApp peringatan keamanan', [
                    'target' => $number,
                    'event' => $context['event'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
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
