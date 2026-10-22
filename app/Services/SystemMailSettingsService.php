<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Terapkan konfigurasi email dari database di atas fallback .env.
 *
 * Password SMTP disimpan terenkripsi dan tidak pernah dikembalikan ke view.
 */
class SystemMailSettingsService
{
    public function apply(): void
    {
        try {
            $mailer = (string) $this->value('mail_mailer', config('mail.default', 'smtp'));

            if (! in_array($mailer, ['smtp', 'log', 'sendmail', 'array', 'failover'], true)) {
                $mailer = (string) config('mail.default', 'smtp');
            }

            config(['mail.default' => $mailer]);

            if ($mailer === 'smtp') {
                $host = trim((string) $this->value('mail_host', config('mail.mailers.smtp.host', '')));
                $port = (int) $this->value('mail_port', config('mail.mailers.smtp.port', 587));
                $username = trim((string) $this->value('mail_username', config('mail.mailers.smtp.username', '')));
                $encryption = $this->value('mail_encryption', config('mail.mailers.smtp.encryption'));

                if ($host !== '') {
                    config(['mail.mailers.smtp.host' => $host]);
                }

                if ($port > 0) {
                    config(['mail.mailers.smtp.port' => $port]);
                }

                config([
                    'mail.mailers.smtp.username' => $username !== '' ? $username : config('mail.mailers.smtp.username'),
                    'mail.mailers.smtp.encryption' => $encryption === 'none' ? null : ($encryption ?: config('mail.mailers.smtp.encryption')),
                ]);

                $encryptedPassword = (string) $this->value('mail_password', '');

                if ($encryptedPassword !== '') {
                    try {
                        config(['mail.mailers.smtp.password' => Crypt::decryptString($encryptedPassword)]);
                    } catch (\Throwable $e) {
                        Log::warning('Password SMTP tersimpan tidak dapat didekripsi', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            $fromAddress = trim((string) $this->value('mail_from_address', config('mail.from.address', '')));
            $fromName = trim((string) $this->value('mail_from_name', config('mail.from.name', '')));

            if ($fromAddress !== '') {
                config(['mail.from.address' => $fromAddress]);
            }

            if ($fromName !== '') {
                config(['mail.from.name' => $fromName]);
            }

            $logoUrl = trim((string) $this->value('mail_logo_url', ''));

            if ($logoUrl !== '') {
                config(['app.email_logo_url' => $logoUrl]);
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal menerapkan pengaturan email sistem', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Ambil setting tanpa bergantung pada helper global yang baru dimuat
     * setelah AppServiceProvider boot (dipakai juga saat boot/CLI).
     */
    private function value(string $key, mixed $default = null): mixed
    {
        try {
            if (function_exists('setting')) {
                return setting($key, $default);
            }

            return SystemSetting::query()->where('key', $key)->value('value') ?? $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * Nilai efektif untuk ditampilkan di form tanpa membocorkan password.
     *
     * @return array<string, mixed>
     */
    public function displayValues(): array
    {
        return [
            'mail_mailer' => (string) $this->value('mail_mailer', config('mail.default', 'smtp')),
            'mail_host' => (string) $this->value('mail_host', config('mail.mailers.smtp.host', '')),
            'mail_port' => (string) $this->value('mail_port', config('mail.mailers.smtp.port', 587)),
            'mail_username' => (string) $this->value('mail_username', config('mail.mailers.smtp.username', '')),
            'mail_encryption' => (string) $this->value('mail_encryption', config('mail.mailers.smtp.encryption', 'tls')),
            'mail_from_address' => (string) $this->value('mail_from_address', config('mail.from.address', '')),
            'mail_from_name' => (string) $this->value('mail_from_name', config('mail.from.name', '')),
            'mail_logo_url' => (string) $this->value('mail_logo_url', ''),
            'mail_password_set' => (string) $this->value('mail_password', '') !== ''
                || (string) config('mail.mailers.smtp.password', '') !== '',
        ];
    }
}
