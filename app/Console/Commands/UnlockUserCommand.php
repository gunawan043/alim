<?php

namespace App\Console\Commands;

use App\Models\FailedLoginAttempt;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Buka akun yang terkunci karena percobaan login gagal.
 *
 * Dibutuhkan saat yang terkunci adalah akun Super Admin sendiri: halaman
 * Manajemen User tidak bisa dibuka karena tidak bisa login.
 */
class UnlockUserCommand extends Command
{
    protected $signature = 'user:unlock
        {identity? : Email atau NUPY akun yang dibuka}
        {--all : Buka semua akun yang sedang terkunci}
        {--ip=* : Buka blokir IP tertentu (boleh lebih dari satu)}
        {--list : Tampilkan akun dan IP yang sedang terkunci}';

    protected $description = 'Buka akun/IP yang terkunci karena percobaan login gagal.';

    public function handle(): int
    {
        if ($this->option('list')) {
            $this->listLocked();

            return self::SUCCESS;
        }

        $didSomething = false;

        foreach ((array) $this->option('ip') as $ip) {
            $ip = trim((string) $ip);
            if ($ip === '') {
                continue;
            }

            $deleted = FailedLoginAttempt::forIp($ip)->delete();
            $this->info("IP {$ip}: {$deleted} catatan percobaan dihapus.");
            $didSomething = true;
        }

        if ($this->option('all')) {
            $users = User::query()
                ->whereNotNull('locked_until')
                ->where('locked_until', '>', now())
                ->get();

            if ($users->isEmpty()) {
                $this->info('Tidak ada akun yang terkunci.');
            }

            foreach ($users as $user) {
                $this->unlock($user);
                $this->info("Akun dibuka: {$user->name} <{$user->email}>");
            }

            $didSomething = $didSomething || $users->isNotEmpty();
        }

        $identity = trim((string) $this->argument('identity'));

        if ($identity !== '') {
            $user = $this->findUser($identity);

            if (! $user) {
                $this->error("User dengan email/NUPY '{$identity}' tidak ditemukan.");

                return self::FAILURE;
            }

            $this->unlock($user);
            $this->info("Akun dibuka: {$user->name} <{$user->email}>");
            $didSomething = true;
        }

        if (! $didSomething) {
            $this->warn('Tidak ada yang diproses. Contoh: php artisan user:unlock admin@sekolah.id');
            $this->line('Gunakan --list untuk melihat akun/IP yang terkunci.');

            return self::INVALID;
        }

        return self::SUCCESS;
    }

    private function findUser(string $identity): ?User
    {
        $identity = strtolower($identity);

        return User::query()
            ->where('email', $identity)
            ->orWhereHas('employment', function ($query) use ($identity) {
                $query->whereRaw("LOWER(REPLACE(nupy, '-', '')) = ?", [str_replace('-', '', $identity)]);
            })
            ->first();
    }

    private function unlock(User $user): void
    {
        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_at' => null,
            'locked_until' => null,
        ])->save();

        FailedLoginAttempt::query()
            ->where('email', strtolower((string) $user->email))
            ->delete();
    }

    private function listLocked(): void
    {
        $users = User::query()
            ->whereNotNull('locked_until')
            ->where('locked_until', '>', now())
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'failed_login_attempts', 'locked_until']);

        if ($users->isEmpty()) {
            $this->info('Tidak ada akun terkunci.');
        } else {
            $this->table(
                ['Nama', 'Email', 'Percobaan', 'Terbuka pada'],
                $users->map(fn (User $user) => [
                    $user->name,
                    $user->email,
                    $user->failed_login_attempts,
                    $user->locked_until?->format('d/m/Y H:i'),
                ])->all()
            );
        }

        $blocks = FailedLoginAttempt::query()
            ->where('locked_reason', 'ip')
            ->whereNotNull('locked_until')
            ->where('locked_until', '>', now())
            ->orderBy('ip_address')
            ->get(['ip_address', 'email', 'attempts', 'locked_until']);

        if ($blocks->isNotEmpty()) {
            $this->newLine();
            $this->table(
                ['IP', 'Email terakhir', 'Percobaan', 'Terbuka pada'],
                $blocks->map(fn (FailedLoginAttempt $block) => [
                    $block->ip_address,
                    $block->email,
                    $block->attempts,
                    $block->locked_until?->format('d/m/Y H:i'),
                ])->all()
            );
        }
    }
}
