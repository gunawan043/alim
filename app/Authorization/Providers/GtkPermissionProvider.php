<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\User;

final class GtkPermissionProvider implements PermissionProvider
{
    /**
     * Core GTK permissions granted to any authenticated GTK/Staff member.
     *
     * Source hierarchy:
     *   employment status (active)    -> ASSIGNMENT
     *   GTK additional task           -> DELEGATION
     *   GTK transfer request approved -> DELEGATION
     *
     * This provider does NOT perform authorization itself — it only
     * yields PermissionOrigin objects representing the GTK domain.
     */
    public function provide(int|string $userId): array
    {
        $user = User::withTrashed()->find($userId);

        if (! $user instanceof User) {
            return [];
        }

        $origins = [];

        // Employment-based permissions (basic GTK scope)
        if ($user->employment && $user->employment->is_active) {
            $origins[] = new PermissionOrigin(
                provider: 'gtk',
                permission: 'gtk.read',
                reason: 'active_employment',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::EMPLOYMENT,
            );

            $origins[] = new PermissionOrigin(
                provider: 'gtk',
                permission: 'gtk.write',
                reason: 'active_employment',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::EMPLOYMENT,
            );
        }

        // Role-level elevations: higher role = more GTK permissions
        $roleLevel = $user->roles()->min('level');
        if ($roleLevel !== null && (int) $roleLevel <= 10) {
            // Admin Tata Usaha / Admin Kasir / Personalia / Administrator
            $origins[] = new PermissionOrigin(
                provider: 'gtk',
                permission: 'gtk.delete',
                reason: 'admin_role_level',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );

            $origins[] = new PermissionOrigin(
                provider: 'gtk',
                permission: 'gtk.approve',
                reason: 'admin_role_level',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        if ($roleLevel !== null && (int) $roleLevel <= 6) {
            // Administrator / Personalia +
            $origins[] = new PermissionOrigin(
                provider: 'gtk',
                permission: 'gtk.assign',
                reason: 'high_admin_role_level',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        if ($roleLevel !== null && (int) $roleLevel <= 2) {
            // Mudir / Wadir + Super Admin
            $origins[] = new PermissionOrigin(
                provider: 'gtk',
                permission: 'gtk.transfer',
                reason: 'executive_role_level',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Additional task delegation → gtk.assign (temporary)
        foreach ($user->additionalTasks()->where('end_date', '>=', now())->get() as $task) {
            if ((bool) $task->is_admin) {
                $origins[] = new PermissionOrigin(
                    provider: 'gtk',
                    permission: 'gtk.assign',
                    reason: sprintf('additional_task_%s', $task->title),
                    scope: ScopeKey::forUser($user),
                    source: PermissionSource::DELEGATION,
                );
            }
        }

        // Transfer request permissions
        $approvedTransfers = $user->transferRequests()
            ->where('status', 'approved')
            ->get();
        foreach ($approvedTransfers as $transfer) {
            if ($transfer->performed_at === null || strtotime($transfer->performed_at) >= time()) {
                $origins[] = new PermissionOrigin(
                    provider: 'gtk',
                    permission: 'gtk.read',
                    reason: sprintf('transfer_request_approved_%s', $transfer->target_work_unit_id),
                    scope: ScopeKey::forUser($user),
                    source: PermissionSource::DELEGATION,
                );
            }
        }

        return $origins;
    }
}
