<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

/**
 * Menolak request dengan Host header yang tidak dikenal (mitigasi host-header
 * injection / cache poisoning). Daftar host diatur via TRUSTED_HOSTS; bila
 * kosong, host APP_URL + subdomainnya dan localhost dianggap tepercaya.
 */
class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts()
    {
        $configured = array_values(array_filter((array) config('trustedhost.hosts', [])));

        if ($configured !== []) {
            return $configured;
        }

        return array_values(array_filter([
            $this->allSubdomainsOfApplicationUrl(),
            'localhost',
            '127\.0\.0\.1',
            '\[::1\]',
            '::1',
        ]));
    }
}
