<?php

namespace App\Http\Middleware;

/**
 * Guard API POS / Kasir (prefix konfigurasi: pos).
 */
class VerifyPosApiKey extends VerifyIntegrationApiKey
{
    protected function configPrefix(): string
    {
        return 'pos';
    }

    protected function integrationName(): string
    {
        return 'POS';
    }
}
