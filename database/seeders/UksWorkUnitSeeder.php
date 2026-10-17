<?php

namespace Database\Seeders;

use App\Models\WorkUnit;
use Illuminate\Database\Seeder;

class UksWorkUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * UKS units for Putra and Putri dormitories.
     */
    public function run(): void
    {
        $units = [
            [
                'name' => 'UKS Putra',
                'code' => 'uks_p',
                'type' => 'Unit Pelayanan',
            ],
            [
                'name' => 'UKS Putri',
                'code' => 'uks_putri',
                'type' => 'Unit Pelayanan',
            ],
        ];

        foreach ($units as $unitData) {
            WorkUnit::firstOrCreate(
                ['name' => $unitData['name']],
                $unitData
            );
        }

        $this->command->info('UKS work units seeded.');
    }
}
