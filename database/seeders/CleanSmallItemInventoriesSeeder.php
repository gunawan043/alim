<?php

namespace Database\Seeders;

use App\Models\DormitoryInventory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CleanSmallItemInventoriesSeeder extends Seeder
{
    public function run(): void
    {
        $smallItems = [
            'bantal', 'cermin', 'sprei', 'talang air', 'tempat wudhu',
            'gantungan baju', 'guling', 'lampu tidur', 'tempat sampah',
            'mukena', 'sarung', 'pengharum ruangan',
        ];

        // Lowercase untuk case-insensitive
        $lower = array_map('strtolower', $smallItems);

        $deleted = DormitoryInventory::whereIn(DB::raw('LOWER(item_name)'), $lower)->forceDelete();

        $this->command->info("✓ Deleted {$deleted} small item records from dormitory_inventories.");
    }
}
