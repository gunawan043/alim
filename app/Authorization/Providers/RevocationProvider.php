<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\Models\RevokedPermission;
use App\Authorization\ValueObjects\ScopeKey;
use Illuminate\Support\Carbon;

final class RevocationProvider implements PermissionProvider
{
    /**
     * Produce REVOCATION PermissionOrigin entries from the
     * authorization.revoked_permissions table.
     *
     * These origins feed into RevocationResolver so that active
     * revocations are matched against granted origins and cause
     * a deny.
     */
    public function provide(int|string $userId): array
    {
        $now = Carbon::now();

        $records = RevokedPermission::query()
            ->where('user_id', $userId)
            ->where('valid_from', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $now);
            })
            ->get();

        $origins = [];

        foreach ($records as $record) {
            try {
                $scope = ScopeKey::fromHash($record->scope_key);
            } catch (\Throwable) {
                continue;
            }

            $origins[] = new PermissionOrigin(
                provider: 'revocation',
                permission: $record->permission,
                reason: (string) $record->reason,
                scope: $scope,
                source: PermissionSource::REVOCATION,
            );
        }

        return $origins;
    }
}
