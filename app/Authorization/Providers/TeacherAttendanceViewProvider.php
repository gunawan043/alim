<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\User;

/**
 * Grants teacher-attendance_view to all active GTK members.
 *
 * Any authenticated GTK user (active employment) may view the teacher QR
 * attendance dashboard, history, and perform manual check-out operations.
 */
final class TeacherAttendanceViewProvider implements PermissionProvider
{
    public function provide(int|string $userId): array
    {
        $user = User::withTrashed()->find($userId);

        if (! $user instanceof User) {
            return [];
        }

        // Active employment grants the view permission
        if ($user->employment && $user->employment->is_active) {
            return [
                new PermissionOrigin(
                    provider: 'teacher-attendance-view',
                    permission: 'teacher-attendance_view',
                    reason: 'active_gtk_employment',
                    scope: ScopeKey::forUser($user),
                    source: PermissionSource::EMPLOYMENT,
                ),
            ];
        }

        return [];
    }
}
