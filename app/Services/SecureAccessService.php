<?php

namespace App\Services;

use App\Models\SecureAccessToken;
use Illuminate\Support\Str;

class SecureAccessService
{
    public function generate()
    {
        return hash('sha256', Str::random(40).microtime(true));
    }

    public function createForUser($user, $role)
    {
        return SecureAccessToken::create([
            'user_id' => $user->id,
            'role_name' => $role,
            'token' => $this->generate(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'expires_at' => now()->addMinutes(5),
        ]);
    }
}
