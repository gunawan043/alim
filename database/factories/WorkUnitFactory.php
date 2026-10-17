<?php

namespace Database\Factories;

use App\Models\WorkUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WorkUnitFactory extends Factory
{
    protected $model = WorkUnit::class;

    public function definition(): array
    {
        $types = ['Unsur Pimpinan', 'Unit Akademik', 'Unit Penunjang Akademik', 'Unit Administrasi', 'Unit Pelayanan', 'Unit Humas Publikasi'];

        return [
            'id' => (string) Str::uuid(),
            'name' => fake()->words(3, true),
            'code' => strtoupper(fake()->lexify('?????')),
            'type' => fake()->randomElement($types),
            'parent_id' => null,
            'is_active' => true,
            'metadata' => null,
        ];
    }
}
