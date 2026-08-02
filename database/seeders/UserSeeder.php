<?php

namespace Database\Seeders;

use App\Models\GtkEmployment;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ── Regular users (no school assignment) ───────────────────
        $users = [
            ['name' => 'Staff Personalia',       'email' => 'personalia@example.com',   'password' => 'password123', 'role' => 'Personalia'],
            ['name' => 'Administrator',          'email' => 'admin@example.com',        'password' => 'password123', 'role' => 'Administrator'],
            ['name' => 'Guru Contoh',           'email' => 'gtk@example.com',          'password' => 'password123', 'role' => 'Guru'],
            ['name' => 'Mudir',                  'email' => 'mudir@example.com',        'password' => 'password123', 'role' => 'Mudir'],
            ['name' => 'Wakil Kepala Sekolah',  'email' => 'wakasek@example.com',     'password' => 'password123', 'role' => 'Wadir 1'],
            ['name' => 'Kepala Sekolah',         'email' => 'kepsek@example.com',       'password' => 'password123', 'role' => 'Kepala Sekolah'],
        ];

        foreach ($users as $u) {
            $this->createUserWithRole($u['name'], $u['email'], $u['password'], $u['role']);
        }

        // ── TU users (assigned to specific school) ─────────────────
        // Admin Tata Usaha = can create/edit mutations
        // Tata Usaha = read-only mutations
        $tuUsers = [
            [
                'name' => 'Admin TU SD IT Putra',
                'email' => 'tu.sdputra@example.com',
                'password' => 'password123',
                'role' => 'Admin Tata Usaha',
                'school' => 'SD IT Putra Abu Hurairah Mataram',
            ],
            [
                'name' => 'Admin TU SD IT Putri',
                'email' => 'tu.sdputri@example.com',
                'password' => 'password123',
                'role' => 'Admin Tata Usaha',
                'school' => 'SD IT Putri Abu Hurairah Mataram',
            ],
            [
                'name' => 'Admin TU SMP IT Putra',
                'email' => 'tu.smpputra@example.com',
                'password' => 'password123',
                'role' => 'Admin Tata Usaha',
                'school' => 'SMP IT Putra Abu Hurairah Mataram',
            ],
            [
                'name' => 'Admin TU SMP IT Putri',
                'email' => 'tu.smpputri@example.com',
                'password' => 'password123',
                'role' => 'Admin Tata Usaha',
                'school' => 'SMP IT Putri Abu Hurairah Mataram',
            ],
            [
                'name' => 'Admin TU SMA IT',
                'email' => 'tu.sma@example.com',
                'password' => 'password123',
                'role' => 'Admin Tata Usaha',
                'school' => 'SMA IT Putra Abu Hurairah Mataram',
            ],
            [
                'name' => 'TU SD IT Putra (Read-Only)',
                'email' => 'tu-readonly@example.com',
                'password' => 'password123',
                'role' => 'Tata Usaha',
                'school' => 'SD IT Putra Abu Hurairah Mataram',
            ],
        ];

        foreach ($tuUsers as $tu) {
            $this->createTuUser($tu['name'], $tu['email'], $tu['password'], $tu['role'], $tu['school']);
        }

        $this->command->info('✅ Sample users created successfully!');
    }

    private function createUserWithRole($name, $email, $password, $roleName)
    {
        try {
            // Use DB facade to avoid model UUID issues in seeder context
            $roleId = DB::table('roles')->where('name', $roleName)->where('guard_name', 'web')->value('id');

            if (! $roleId) {
                $this->command->warn("  ⚠️ Role '{$roleName}' not found.");

                return null;
            }

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    // Pass raw password — User model's 'password' => 'hashed' cast handles hashing
                    'password' => $password,
                    'is_active' => true,
                ]
            );

            // Assign role via direct DB insert to avoid model UUID casting issues
            DB::table('model_has_roles')->updateOrInsert(
                ['model_type' => 'App\Models\User', 'model_id' => $user->id, 'role_id' => $roleId],
                ['role_id' => $roleId]
            );

            $this->command->info("  ✅ {$name} <{$email}> → {$roleName}");

            return $user;
        } catch (\Exception $e) {
            $this->command->error("  ❌ Failed to create user '{$name}': ".$e->getMessage());

            return null;
        }
    }

    /**
     * Create a TU user assigned to a specific school via gtk_employment.
     */
    private function createTuUser(string $name, string $email, string $password, string $roleName, string $schoolName)
    {
        try {
            // Use DB facade to avoid model UUID issues in seeder context
            $roleId = DB::table('roles')->where('name', $roleName)->where('guard_name', 'web')->value('id');
            if (! $roleId) {
                $this->command->warn("  ⚠️ Role '{$roleName}' not found.");

                return null;
            }

            $school = School::where('name', $schoolName)->first();
            if (! $school) {
                $this->command->warn("  ⚠️ School '{$schoolName}' not found — TU user '{$name}' skipped.");

                return null;
            }

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    // Pass raw password — User model's 'password' => 'hashed' cast handles hashing
                    'password' => $password,
                    'is_active' => true,
                ]
            );

            // Assign role via direct DB insert to avoid model UUID casting issues
            DB::table('model_has_roles')->updateOrInsert(
                ['model_type' => 'App\Models\User', 'model_id' => $user->id, 'role_id' => $roleId],
                ['role_id' => $roleId]
            );

            // Assign to school via gtk_employment
            if (! GtkEmployment::where('user_id', $user->id)->where('school_id', $school->id)->exists()) {
                GtkEmployment::create([
                    'user_id' => $user->id,
                    'school_id' => $school->id,
                    'status_kepegawaian' => 'PTY',
                    'jabatan' => str_contains($roleName, 'Admin') ? 'Admin Tata Usaha' : 'Tata Usaha',
                ]);
            }

            $this->command->info("  ✅ {$name} <{$email}> → {$roleName} | Sekolah: {$school->name}");

            return $user;
        } catch (\Exception $e) {
            $this->command->error("  ❌ Failed to create TU user '{$name}': ".$e->getMessage());

            return null;
        }
    }
}
