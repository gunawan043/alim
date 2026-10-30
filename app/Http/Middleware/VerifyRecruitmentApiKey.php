<?php

namespace App\Http\Middleware;

/**
 * Guard API integrasi recruitment (prefix konfigurasi: recruitment).
 */
class VerifyRecruitmentApiKey extends VerifyIntegrationApiKey
{
    protected function configPrefix(): string
    {
        return 'recruitment';
    }

    protected function integrationName(): string
    {
        return 'Recruitment';
    }
}
