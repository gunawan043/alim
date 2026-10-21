<?php

namespace App\Console\Commands;

use App\Services\Arsip\BackupReminderService;
use Illuminate\Console\Command;

/**
 * Preflight keamanan untuk deployment produksi.
 *
 * Command ini tidak mengubah apa pun. Ia memeriksa konfigurasi yang paling
 * sering menyebabkan kebocoran data: debug aktif, cookie tidak aman, backup
 * tidak tersedia, dan 2FA tidak aktif.
 */
class SecurityPreflightCommand extends Command
{
    protected $signature = 'security:preflight';

    protected $description = 'Periksa konfigurasi keamanan minimum sebelum/ setelah deploy produksi';

    public function handle(): int
    {
        $isProduction = app()->environment('production');
        $checks = [];

        $this->addCheck($checks, 'APP_ENV', $isProduction, $isProduction, 'Produksi harus APP_ENV=production.');
        $this->addCheck($checks, 'APP_DEBUG', ! config('app.debug'), $isProduction, 'APP_DEBUG harus false.');
        $this->addCheck($checks, 'APP_KEY', filled(config('app.key')), true, 'APP_KEY wajib diisi.');

        $appUrl = (string) config('app.url');
        $this->addCheck($checks, 'APP_URL HTTPS', str_starts_with($appUrl, 'https://'), $isProduction, 'APP_URL produksi harus https://.');
        $this->addCheck($checks, 'Session secure cookie', (bool) config('session.secure'), $isProduction, 'SESSION_SECURE_COOKIE harus true.');
        $this->addCheck($checks, 'Session HttpOnly', (bool) config('session.http_only'), true, 'Cookie session harus HttpOnly.');
        $this->addCheck($checks, 'Session SameSite', in_array(config('session.same_site'), ['lax', 'strict'], true), true, 'SameSite harus lax/strict.');
        $this->addCheck($checks, 'Log level', ! in_array(strtolower((string) config('logging.level')), ['debug'], true), $isProduction, 'LOG_LEVEL produksi jangan debug.');

        $dbUsername = (string) config('database.connections.mysql.username', '');
        $this->addCheck($checks, 'Database bukan root', $dbUsername !== '' && strtolower($dbUsername) !== 'root', $isProduction, 'Aplikasi tidak boleh memakai user database root.');

        $trustedProxies = (string) config('trustedproxy.proxies', '');
        $this->addCheck($checks, 'Trusted proxies', $trustedProxies !== '', false, 'Isi TRUSTED_PROXIES jika berada di balik Nginx/Cloudflare.');

        $twoFactorEnabled = false;
        $adminAllowlist = '';
        try {
            $twoFactorEnabled = (string) setting('security_2fa_enabled', '0') === '1';
            $adminAllowlist = trim((string) setting('security_admin_ip_allowlist', ''));
        } catch (\Throwable) {
            // Pemeriksaan tetap berjalan meski database belum siap.
        }

        $this->addCheck($checks, '2FA aktif', $twoFactorEnabled, $isProduction, 'Aktifkan 2FA untuk akun berwenang.');
        $this->addCheck($checks, 'Admin IP allowlist', $adminAllowlist !== '', false, 'Pertimbangkan allowlist IP untuk area Super Admin.');

        $backup = app(BackupReminderService::class);
        $latestBackup = $backup->latestBackupAt();
        $backupOk = $latestBackup !== null && ! $backup->isDue();
        $this->addCheck($checks, 'Backup tersedia', $backupOk, $isProduction, 'Belum ada backup baru atau backup sudah kedaluwarsa.');

        $rows = array_map(fn (array $check) => [
            'label' => $check['label'],
            'status' => $check['status'],
            'hint' => $check['hint'],
        ], $checks);

        $this->table(['Pemeriksaan', 'Status', 'Keterangan'], $rows);

        $criticalFailures = collect($checks)->where('critical', true)->where('ok', false)->count();

        if ($criticalFailures > 0) {
            $this->newLine();
            $this->error("Ada {$criticalFailures} pemeriksaan kritis yang gagal. Jangan anggap deployment produksi aman.");

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Tidak ada kegagalan kritis pada konfigurasi yang diperiksa.');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $checks
     */
    private function addCheck(array &$checks, string $label, bool $ok, bool $critical, string $hint): void
    {
        $checks[] = [
            'label' => $label,
            'status' => $ok ? '<fg=green>OK</>' : ($critical ? '<fg=red>GAGAL</>' : '<fg=yellow>PERIKSA</>'),
            'hint' => $ok ? '-' : $hint,
            'ok' => $ok,
            'critical' => $critical,
        ];
    }
}
