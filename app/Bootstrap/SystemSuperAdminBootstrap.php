<?php

namespace App\Bootstrap;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Ensures a single "Power User" / Super Admin account exists and is fully
 * provisioned after every database reset (including `migrate:fresh`).
 *
 * Idempotent — safe to call on every request boot.
 *
 * What it guarantees:
 *   - roles table has 'Super Admin' (web guard, level 1)
 *   - permissions table has 'impersonate_role' (web guard)
 *   - Super Admin role has all permissions + impersonate_role
 *   - One stable user: super.admin@alim.local / 'Super Administrator'
 *     — can switch sidebar/role views for testing without re-login
 *     — survives migrate:fresh because we recreate on every boot
 *
 * If the schema does not exist yet (fresh install before migrations run),
 * this is a no-op.
 */
class SystemSuperAdminBootstrap
{
    public const SUPER_ADMIN_EMAIL = 'super.admin@alim.local';

    public const SUPER_ADMIN_NAME = 'Super Administrator';

    public const SUPER_ADMIN_PASSWORD = 'SuperAdmin#2026';

    public const SUPER_ADMIN_ROLE = 'Super Admin';

    public const IMPERSONATE_PERMISSION = 'impersonate_role';

    public static function ensure(): void
    {
        // Bail out if the DB schema is not yet migrated (avoids crashing
        // artisan commands that boot before migrations run).
        if (! Schema::hasTable('users') || ! Schema::hasTable('roles') || ! Schema::hasTable('permissions')) {
            return;
        }

        try {
            // ── 1. ensure Super Admin role exists ────────────────
            $role = Role::where('name', self::SUPER_ADMIN_ROLE)
                ->where('guard_name', 'web')
                ->first();
            if (! $role) {
                $role = new Role([
                    'id' => (string) Str::uuid(),
                    'name' => self::SUPER_ADMIN_ROLE,
                    'guard_name' => 'web',
                    'level' => 1,
                    'description' => 'Full system access — Power User',
                ]);
                $role->save();
            }

            // ── 2. ensure impersonate_role permission exists ─────
            $perm = Permission::where('name', self::IMPERSONATE_PERMISSION)
                ->where('guard_name', 'web')
                ->first();
            if (! $perm) {
                $perm = Permission::create([
                    'id' => (string) Str::uuid(),
                    'name' => self::IMPERSONATE_PERMISSION,
                    'guard_name' => 'web',
                ]);
            }

            // ── 3. grant Super Admin ALL permissions ─────────────
            // Use the spatie helper to sync all perms → role.
            // (clear cache so re-sync picks up newly-created perms)
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            $allPerms = Permission::where('guard_name', 'web')->get();
            if (! $allPerms->isEmpty()) {
                DB::table('role_has_permissions')->where('role_id', $role->id)->delete();
                $rows = [];
                $now = now();
                foreach ($allPerms as $perm) {
                    $rows[] = [
                        'permission_id' => (string) $perm->id,
                        'role_id' => (string) $role->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                DB::table('role_has_permissions')->insert($rows);
            }

            // ���─ 4. ensure Super Admin user exists ────────────────
            $user = User::where('email', self::SUPER_ADMIN_EMAIL)->first();
            if (! $user) {
                $user = User::create([
                    'id' => (string) Str::uuid(),
                    'name' => self::SUPER_ADMIN_NAME,
                    'email' => self::SUPER_ADMIN_EMAIL,
                    'password' => self::SUPER_ADMIN_PASSWORD, // hashed via cast
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
            } else {
                // Keep the Power User alive across resets: re-activate + reset
                // password so the canonical credential always works.
                $user->forceFill([
                    'is_active' => true,
                    'password' => self::SUPER_ADMIN_PASSWORD,
                    'name' => self::SUPER_ADMIN_NAME,
                ])->save();
            }

            // ── 5. assign Super Admin role via direct insert (idempotent) ──
            DB::table('model_has_roles')->updateOrInsert(
                [
                    'role_id' => $role->id,
                    'model_type' => User::class,
                    'model_id' => $user->id,
                ],
                [
                    'role_id' => $role->id,
                    'model_type' => User::class,
                    'model_id' => $user->id,
                ]
            );
        } catch (\Throwable $e) {
            // Bootstrap must never crash the app — log and continue.
            if (function_exists('logger')) {
                logger()->warning('SystemSuperAdminBootstrap failed: '.$e->getMessage());
            }
        }
    }
}
