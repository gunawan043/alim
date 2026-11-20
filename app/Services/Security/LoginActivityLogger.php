<?php

namespace App\Services\Security;

use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class LoginActivityLogger
{
    /**
     * Catat satu peristiwa login. Tidak boleh membuat proses login gagal
     * hanya karena audit trail bermasalah.
     */
    public function record(
        string $event,
        ?Request $request = null,
        ?User $user = null,
        ?string $identity = null,
        ?string $reason = null,
        array $metadata = [],
    ): ?LoginActivity {
        try {
            $ip = $request?->ip();
            $identity = $identity !== null ? Str::limit(trim($identity), 255, '') : null;
            $email = $user?->email;

            if (! $email && $identity && filter_var($identity, FILTER_VALIDATE_EMAIL)) {
                $email = $identity;
            }

            return LoginActivity::create([
                'user_id' => $user?->id,
                'identity' => $identity,
                'email' => $email ? Str::limit($email, 255, '') : null,
                'event' => $event,
                'reason' => $reason ? Str::limit($reason, 255, '') : null,
                'ip_address' => $ip,
                'user_agent' => $request?->userAgent(),
                'is_suspicious' => $this->shouldFlag($event, $user, $identity, $ip),
                'metadata' => $metadata !== [] ? $metadata : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal mencatat aktivitas login.', [
                'event' => $event,
                'user_id' => $user?->id,
                'identity' => $identity,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function shouldFlag(string $event, ?User $user, ?string $identity, ?string $ip): bool
    {
        if (in_array($event, ['login_blocked', 'account_locked', 'ip_blocked', 'otp_failed', '2fa_failed'], true)) {
            return true;
        }

        if (! in_array($event, ['login_failed', 'login_success'], true)) {
            return false;
        }

        if (! $ip && ! $identity && ! $user) {
            return false;
        }

        $window = now()->subMinutes(15);

        $failed = LoginActivity::query()
            ->where('event', 'login_failed')
            ->where('created_at', '>=', $window)
            ->where(function ($query) use ($user, $identity, $ip) {
                if ($ip) {
                    $query->orWhere('ip_address', $ip);
                }
                if ($user) {
                    $query->orWhere('user_id', $user->id);
                }
                if ($identity) {
                    $query->orWhere('identity', $identity);
                }
            })
            ->count();

        if ($event === 'login_success') {
            return $failed >= 3;
        }

        // Percobaan gagal ketiga dalam 15 menit dianggap perlu perhatian.
        return $failed >= 2;
    }
}
