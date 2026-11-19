<?php

namespace App\Services;

use App\Mail\LoginOtpMail;
use App\Models\User;
use App\Services\Notifications\WhatsAppGatewayService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Pengiriman kode OTP login.
 *
 * Email dicoba lebih dulu. Bila email gagal terkirim, kode dikirim ulang
 * melalui WhatsApp ke nomor pengguna. Driver WA `log` tidak dianggap
 * terkirim karena hanya menulis ke log, bukan mengirim pesan.
 */
class LoginOtpNotifier
{
    public function __construct(
        private readonly NotificationUniversalService $notifications,
        private readonly WhatsAppGatewayService $gateway,
    ) {}

    /**
     * @return array{channel: ?string, email: bool, whatsapp: bool}
     */
    public function send(User $user, string $otp, int $expiryMinutes): array
    {
        if ($this->sendEmail($user, $otp, $expiryMinutes)) {
            return ['channel' => 'email', 'email' => true, 'whatsapp' => false];
        }

        if ($this->sendWhatsApp($user, $otp, $expiryMinutes)) {
            return ['channel' => 'whatsapp', 'email' => false, 'whatsapp' => true];
        }

        return ['channel' => null, 'email' => false, 'whatsapp' => false];
    }

    private function sendEmail(User $user, string $otp, int $expiryMinutes): bool
    {
        if (! filled($user->email)) {
            return false;
        }

        try {
            Mail::to($user->email)->send(new LoginOtpMail($otp, $user->name, $expiryMinutes));

            return true;
        } catch (\Throwable $e) {
            Log::warning('OTP email gagal dikirim, mencoba WhatsApp', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function sendWhatsApp(User $user, string $otp, int $expiryMinutes): bool
    {
        if ($this->gateway->provider() === 'log') {
            Log::warning('OTP WhatsApp dilewati: provider WA masih mode log.', [
                'user_id' => $user->id,
            ]);

            return false;
        }

        $phone = $this->notifications->phoneFor($user);

        if (! $phone) {
            Log::warning('OTP WhatsApp gagal: nomor WA pengguna tidak tersedia.', [
                'user_id' => $user->id,
            ]);

            return false;
        }

        $message = "Kode verifikasi ALIM\n\n"
            ."Halo {$user->name}, kode OTP login Anda: {$otp}\n"
            ."Berlaku {$expiryMinutes} menit. Jangan bagikan kode ini kepada siapa pun.\n\n"
            .'Jika Anda tidak merasa sedang login, segera ubah kata sandi.';

        return $this->gateway->send($phone, $message, ['event' => 'login_otp']);
    }
}
