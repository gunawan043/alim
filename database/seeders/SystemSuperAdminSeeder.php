<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SystemSuperAdminSeeder extends Seeder
{
    /**
     * Standalone seeder for the System Administrator (Super Admin) account.
     *
     * This seeder is intentionally separate from the other seeders so that
     * the super admin account is always present in production, regardless of
     * whether the other seeders (sample data, dormitory fixtures, etc.) are
     * run.
     *
     * Idempotent — safe to call multiple times. It will:
     *   1. Ensure the 'Super Admin' role exists.
     *   2. Ensure a user with the configured email exists.
     *   3. If a previous super admin user exists with the same username but
     *      a different email, repurpose that user (keeps the same UUID so
     *      audit logs and other FK references remain intact).
     *   4. Assign the 'Super Admin' role to the user.
     *   5. Force the user to be active, is_system_admin=true, is_permanent=true,
     *      and reset the password to the configured value.
     */
    public function run(): void
    {
        $email = 'superadmin@alim.abuhurairah.id';
        $password = '*Masda043';
        $username = 'superadmin';
        $roleName = 'Super Admin';

        // 1. Ensure the Super Admin role exists.
        $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
        if (! $role) {
            $role = Role::create([
                'id' => (string) Str::uuid(),
                'name' => $roleName,
                'guard_name' => 'web',
                'level' => 0,
                'description' => 'Full system access — Power User',
            ]);
        }

        // 2. Resolve the user: prefer matching email, fall back to matching username.
        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::where('username', $username)->first();
        }

        // 3. Create the user if neither email nor username matched.
        if (! $user) {
            $user = User::create([
                'id' => (string) Str::uuid(),
                'name' => 'Administrator Sistem',
                'username' => $username,
                'email' => $email,
                'password' => $password, // Model casts `password` => 'hashed'.
                'is_active' => true,
                'is_system_admin' => true,
                'is_permanent' => true,
                'email_verified_at' => now(),
            ]);
            $this->command->info("  Created System Admin user ({$email}).");
        } else {
            // Reuse existing user. Update email/credentials/flags in case any
            // of them drifted.
            $user->forceFill([
                'email' => $email,
                'username' => $username,
                'password' => $password, // Model casts `password` => 'hashed'.
                'is_active' => true,
                'is_system_admin' => true,
                'is_permanent' => true,
            ])->save();
            $this->command->info("  Updated existing user (ID: {$user->id}) to System Admin ({$email}).");
        }

        // 4. Assign the Super Admin role (idempotent via Spatie).
        if (! $user->hasRole($roleName)) {
            $user->assignRole($roleName);
            $this->command->info("  Assigned Role '{$roleName}' to user ({$email}).");
        } else {
            $this->command->info("  User ({$email}) already has Role '{$roleName}'.");
        }

        $this->command->info("  ✅ System Admin ({$email}) is ready: is_system_admin=true, is_permanent=true, role='{$roleName}'.");
    }
}
