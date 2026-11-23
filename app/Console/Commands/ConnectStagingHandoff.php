<?php

namespace App\Console\Commands;

use App\Exceptions\ConnectSsoException;
use App\Models\User;
use App\Services\Connect\ConnectHandoffService;
use Illuminate\Console\Command;

/**
 * Terbitkan satu kode handoff SSO baru yang BELUM dipakai untuk uji Connect.
 *
 * Kode hanya disimpan sebagai SHA-256 di Core, jadi kode mentah harus
 * ditangkap sekarang dan diserahkan ke operator Connect lewat kanal rahasia.
 * Perintah ini mencetak kode ke stdout — itu disengaja, karena satu-satunya
 * cara kode bisa sampai ke Connect adalah diambil pada saat diterbitkan.
 * Jangan me-redirect output ke berkas di dalam repository.
 *
 * TTL default 60 detik (`CONNECT_SSO_HANDOFF_TTL`), jadi terbitkan kode saat
 * uji siap dijalankan, bukan sebelumnya.
 *
 *   php artisan alim:connect-staging-handoff --env=staging --user=sso.penguji@staging.invalid
 */
class ConnectStagingHandoff extends Command
{
    protected $signature = 'alim:connect-staging-handoff
        {--user=sso.penguji@staging.invalid : Email pengguna Core pemegang izin whatsapp_connect_view}
        {--json : Keluarkan JSON untuk diserahkan ke operator}';

    protected $description = 'Terbitkan kode handoff SSO sekali pakai untuk uji integrasi ALIM Connect (staging)';

    public function handle(ConnectHandoffService $handoffs): int
    {
        if (app()->environment() !== 'staging') {
            $this->error('Perintah ini hanya untuk APP_ENV=staging.');

            return self::FAILURE;
        }

        $email = (string) $this->option('user');
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("Pengguna '{$email}' tidak ditemukan. Jalankan db:seed --class=ConnectStagingTestDataSeeder lebih dulu.");

            return self::FAILURE;
        }

        try {
            $result = $handoffs->issue($user);
        } catch (ConnectSsoException $e) {
            $this->error('Gagal menerbitkan handoff: '.$e->errorCode);

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->output->writeln(json_encode([
                'user_email' => $user->email,
                'core_user_id' => $user->id,
                'code' => $result['code'],
                'audience' => $result['audience'],
                'scope' => $result['scope'],
                'expires_at' => $result['expires_at'],
            ], JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info('Kode handoff baru (sekali pakai) untuk '.$user->email.':');
        $this->line($result['code']);
        $this->newLine();
        $this->line('Berlaku sampai: '.$result['expires_at'].' (audience '.$result['audience'].', scope '.$result['scope'].')');
        $this->warn('Serahkan lewat kanal rahasia; jangan tampilkan di chat, laporan, atau repository.');

        return self::SUCCESS;
    }
}
