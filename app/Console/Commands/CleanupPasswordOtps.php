<?php

namespace App\Console\Commands;

use App\Models\LoginOtp;
use App\Models\PasswordOtp;
use Illuminate\Console\Command;

class CleanupPasswordOtps extends Command
{
    protected $signature = 'password-otps:cleanup';

    protected $description = 'Hapus OTP reset password dan OTP 2FA yang kedaluwarsa/terpakai.';

    public function handle(): int
    {
        $deletedReset = PasswordOtp::query()
            ->where(function ($query) {
                $query->where('expires_at', '<=', now())
                    ->orWhere(function ($used) {
                        $used->whereNotNull('used_at')
                            ->where('used_at', '<=', now()->subDay());
                    });
            })
            ->delete();

        $deletedLogin = LoginOtp::query()
            ->where(function ($query) {
                $query->where('expires_at', '<=', now())
                    ->orWhere(function ($used) {
                        $used->whereNotNull('used_at')
                            ->where('used_at', '<=', now()->subDay());
                    });
            })
            ->delete();

        $this->info("{$deletedReset} OTP reset dan {$deletedLogin} OTP login lama dibersihkan.");

        return self::SUCCESS;
    }
}
