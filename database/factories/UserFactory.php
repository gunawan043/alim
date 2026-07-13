<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => (string) Str::uuid(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'password',
            'remember_token' => Str::random(10),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function ($user) {
            // Attach a default role if the user has none.
            // Use App\Models\Role (not \Spatie\Permission\Models\Role) so
            // the UUID-backed id column type is preserved.
            if (method_exists($user, 'roles') && $user->roles()->count() === 0) {
                $role = \App\Models\Role::firstOrCreate(
                    ['name' => 'Staff'],
                    ['guard_name' => 'web']
                );
                $user->assignRole($role);
            }
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
