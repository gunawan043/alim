<?php

namespace App\Support;

use App\Models\BoardingPolicy;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Pengaturan operasional asrama yang dapat diubah dari Pengaturan Sistem.
 */
class AsramaConfig
{
    public const CONSENT_REQUIRED_KEY = 'asrama_consent_required';

    /**
     * Apakah persetujuan data wali (PDP) wajib dicatat sebelum
     * pengajuan kunjungan/izin baru?
     */
    public static function consentRequired(): bool
    {
        $value = setting(self::CONSENT_REQUIRED_KEY);

        if ($value === null || $value === '') {
            return (bool) config('asrama.consent_required', true);
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function setConsentRequired(bool $required): void
    {
        SystemSetting::set(
            self::CONSENT_REQUIRED_KEY,
            $required ? '1' : '0',
            'asrama'
        );

        Cache::forget('system_settings_map');
    }

    /**
     * Nilai efektif untuk satu asrama. Policy asrama dapat override global;
     * NULL berarti mengikuti pengaturan sistem.
     */
    public static function consentRequiredForDormitory(?string $dormitoryId): bool
    {
        if ($dormitoryId) {
            $override = BoardingPolicy::query()
                ->join('dormitory_policy_assignments', 'dormitory_policy_assignments.boarding_policy_id', '=', 'boarding_policies.id')
                ->where('dormitory_policy_assignments.target_id', $dormitoryId)
                ->where('dormitory_policy_assignments.policy_assignment_type', 'dormitory')
                ->where('boarding_policies.is_active', true)
                ->orderByDesc('dormitory_policy_assignments.priority')
                ->orderByDesc('dormitory_policy_assignments.effective_from')
                ->value('boarding_policies.guardian_consent_required');

            if ($override !== null) {
                return (bool) $override;
            }
        }

        return self::consentRequired();
    }
}
