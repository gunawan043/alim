<?php

namespace Database\Seeders;

use App\Models\AssetCategory;
use App\Models\DormitoryInventory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssignCategoriesToInventoriesSeeder extends Seeder
{
    /**
     * Run the database seeders.
     */
    public function run(): void
    {
        // Mapping item name lowercase -> category ID
        $categoryMap = [
            'kipas angin' => AssetCategory::where('code', 'ELE-001')->firstOrFail()->id,
            'meja belajar' => AssetCategory::where('code', 'MUB-001')->firstOrFail()->id,
            'lemari pakaian' => AssetCategory::where('code', 'MUB-001')->firstOrFail()->id,
            'kursi belajar' => AssetCategory::where('code', 'MUB-001')->firstOrFail()->id,
            'kasur busa' => AssetCategory::where('code', 'MUB-001')->firstOrFail()->id,
            'stop kontak' => AssetCategory::where('code', 'ELE-001')->firstOrFail()->id,
            'al quran' => AssetCategory::where('code', 'SRK-001')->firstOrFail()->id,
            'al-quran' => AssetCategory::where('code', 'SRK-001')->firstOrFail()->id,
            'quran' => AssetCategory::where('code', 'SRK-001')->firstOrFail()->id,
        ];

        $updated = 0;

        foreach ($categoryMap as $itemName => $categoryId) {
            $affected = DormitoryInventory::whereIn(DB::raw('LOWER(item_name)'), [$itemName])
                ->whereNull('category_id')
                ->update(['category_id' => $categoryId]);

            if ($affected > 0) {
                Log::info("AssignCategoriesToInventoriesSeeder: Set category {$categoryId} for item '{$itemName}' ({$affected} records)");
                $this->command->info("  ✓ Assigned '{$itemName}' -> Category ({$affected} x)");
                $updated += $affected;
            }
        }

        $this->command->info("\n✅ Total updated: {$updated} records with category_id.");
        Log::info("AssignCategoriesToInventoriesSeeder: Updated {$updated} records.");
    }
}
