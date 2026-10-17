<?php

use App\Models\DormitoryWing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Add nullable FK column first so we can fill it
        Schema::table('dormitory_wings', function (Blueprint $table) {
            $table->foreignUuid('sarpras_building_id')->nullable()->after('dormitory_id')
                ->constrained('sarpras_buildings')->nullOnDelete();
            $table->index('sarpras_building_id');
        });

        // Migrate: create one sarpras building per (dormitory_id, code) group, link wings
        $groups = DormitoryWing::with('dormitory')->orderBy('dormitory_id')->orderBy('code')->get()
            ->groupBy(fn ($w) => $w->dormitory_id.'|'.strtoupper($w->code));

        $sysAdmin = User::where('is_system_admin', true)->first();

        foreach ($groups as $rows) {
            $sample = $rows->first();
            $buildingName = explode(' — ', $sample->name)[0] ?? $sample->name;

            // Determine gender — if all same, use it; else 'campur'
            $genders = $rows->pluck('gender')->unique()->values()->all();
            $gender = count($genders) === 1 ? $genders[0] : 'campur';

            $buildingId = (string) Str::uuid();
            DB::table('sarpras_buildings')->insert([
                'id' => $buildingId,
                'school_id' => $sample->dormitory?->school_id,
                'code' => strtoupper($sample->code),
                'name' => $buildingName,
                'gender' => $gender,
                'is_active' => true,
                'created_by' => $sysAdmin?->id,
                'updated_by' => $sysAdmin?->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            DB::table('dormitory_wings')
                ->where('dormitory_id', $sample->dormitory_id)
                ->where('code', $sample->code)
                ->update(['sarpras_building_id' => $buildingId]);
        }
    }

    public function down(): void
    {
        Schema::table('dormitory_wings', function (Blueprint $table) {
            $table->dropForeign(['sarpras_building_id']);
            $table->dropIndex(['sarpras_building_id']);
            $table->dropColumn('sarpras_building_id');
        });
    }
};
