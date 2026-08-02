<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\User;

final class LibraryPermissionProvider implements PermissionProvider
{
    /**
     * Library-domain permissions: book catalog, lending, member lookup.
     *
     * Library access is paired with student/staff lookup; the library admin
     * simply inherits the global read on students/gtk scope plus reports.
     */
    public function provide(int|string $userId): array
    {
        $user = User::withTrashed()->find($userId);

        if (! $user instanceof User) {
            return [];
        }

        $origins = [];
        $roleLevel = $user->roles()->min('level');

        // All staff can read reports/library catalog
        if ($roleLevel !== null) {
            $origins[] = new PermissionOrigin(
                provider: 'library',
                permission: 'reports.read',
                reason: 'assigned_role',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );

            // Library admin needs to look up borrowers (students)
            $origins[] = new PermissionOrigin(
                provider: 'library',
                permission: 'students.read',
                reason: 'library_borrower_lookup',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Sarpras+ handles inventory writes
        if ($roleLevel !== null && (int) $roleLevel <= 22) {
            $origins[] = new PermissionOrigin(
                provider: 'library',
                permission: 'gtk.read',
                reason: 'sarpras_staff_lookup',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Admin TU+ manages lending workflows
        if ($roleLevel !== null && (int) $roleLevel <= 9) {
            $origins[] = new PermissionOrigin(
                provider: 'library',
                permission: 'students.write',
                reason: 'admin_tata_usaha_lending_admin',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        return $origins;
    }
}
