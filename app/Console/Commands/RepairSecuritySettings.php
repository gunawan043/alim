<?php

namespace App\Console\Commands;

use App\Models\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RepairSecuritySettings extends Command
{
    protected $signature = 'security:repair-settings
        {--enable-security-notifications : Aktifkan channel untuk event keamanan}
        {--enable-all-notifications : Aktifkan semua channel pada matriks notifikasi}
        {--disable-2fa : Nonaktifkan 2FA (break-glass jika email bermasalah)}
        {--clear-admin-ip : Kosongkan allowlist IP admin (break-glass jika terkunci)}';

    protected $description = 'Perbaiki default pengaturan keamanan dan matriks notifikasi yang kosong/tidak lengkap.';

    public function handle(): int
    {
        $defaults = [
            'otp_expiry_minutes' => '10',
            'password_min_length' => '8',
            'max_login_attempts' => '9',
            'lockout_duration_minutes' => '1440',
            'session_lifetime_minutes' => (string) config('session.lifetime', 120),
            'security_2fa_enabled' => '0',
            'security_2fa_mobile_enabled' => '0',
            'security_2fa_scope' => 'all',
            'security_notify_login' => '1',
            'security_login_notify_mode' => 'all',
            'security_notify_password_forgot' => '1',
            'security_notify_password_reset' => '1',
            'security_notify_password_changed' => '1',
            'email_notifications' => '1',
            'whatsapp_notifications' => '1',
            'push_notifications' => '1',
        ];

        foreach ($defaults as $key => $value) {
            if (! SystemSetting::where('key', $key)->exists()) {
                SystemSetting::set($key, $value, 'security');
                $this->line("Dibuat: {$key}={$value}");
            }
        }

        if ($this->option('disable-2fa')) {
            SystemSetting::set('security_2fa_enabled', '0', 'security');
            $this->warn('2FA dinonaktifkan. Login berikutnya tidak meminta kode email.');
        }

        if ($this->option('clear-admin-ip')) {
            SystemSetting::set('security_admin_ip_allowlist', '', 'security');
            $this->warn('Allowlist IP admin dikosongkan. Semua IP dapat mengakses area admin.');
        }

        $matrix = json_decode((string) SystemSetting::get('notification_channel_matrix', '[]'), true);
        $matrix = is_array($matrix) ? $matrix : [];
        $added = 0;

        $securityEvents = ['login', 'password_forgot', 'password_reset', 'password_changed'];

        foreach (array_keys(config('notifications.events', [])) as $event) {
            foreach (array_keys(config('notifications.channels', [])) as $channel) {
                if (! array_key_exists($channel, $matrix[$event] ?? [])) {
                    $matrix[$event][$channel] = true;
                    $added++;
                }

                if ($this->option('enable-all-notifications')
                    || ($this->option('enable-security-notifications') && in_array($event, $securityEvents, true))) {
                    $matrix[$event][$channel] = true;
                }
            }
        }

        SystemSetting::set('notification_channel_matrix', json_encode($matrix), 'notifications');
        Cache::forget('system_settings');
        Cache::forget('system_settings_map');

        $this->info("Matriks notifikasi disimpan ({$added} channel baru ditambahkan).");
        $this->info('Pengaturan keamanan siap digunakan.');

        return self::SUCCESS;
    }
}
