<?php

namespace Database\Factories;

use App\Models\Dormitory;
use App\Models\School;
use App\Models\WorkUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DormitoryFactory extends Factory
{
    protected $model = Dormitory::class;

    public function definition(): array
    {
        $workUnit = WorkUnit::firstOrCreate(
            ['code' => 'ASRAMA-DEFAULT-'.strtoupper(Str::random(3))],
            [
                'id' => (string) Str::uuid(),
                'name' => $this->faker->unique()->word().' 单位',
                'type' => $this->faker->randomElement(['Unsur Pimpinan', 'Unit Penunjang Akademik', 'Unit Administrasi']),
                'is_active' => true,
            ]
        );

        $school = School::firstOrCreate(
            ['school_code' => 'SKH-DEFAULT-'.strtoupper(Str::random(3))],
            [
                'id' => (string) Str::uuid(),
                'work_unit_id' => $workUnit->id,
                'npsn' => $this->faker->numerify('##########'),
                'nss' => $this->faker->numerify('############'),
                'name' => 'Default School',
                'is_active' => true,
            ]
        );

        return [
            'id' => (string) Str::uuid(),
            'work_unit_id' => $workUnit->id,
            'school_id' => $school->id,
            'code' => strtoupper($this->faker->randomElement(['putra', 'putri', 'campuran'])).'-'.$this->faker->unique()->numberBetween(1, 9999),
            'name' => null,
            'gender' => $this->faker->randomElement(['putra', 'putri', 'campuran']),
            'address' => $this->faker->streetAddress(),
            'phone' => $this->faker->phoneNumber(),
            'capacity' => $this->faker->numberBetween(50, 500),
            'total_rooms' => $this->faker->numberBetween(10, 100),
            'total_wings' => $this->faker->numberBetween(1, 5),
            'head_id' => null,
            'is_active' => true,
            'logo_path' => null,
            'notes' => null,
        ];
    }

    public function withHead(string $userId): static
    {
        return $this->state(fn (array $attributes) => ['head_id' => $userId]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
