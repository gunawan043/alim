<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dormitory_violations')
            && ! Schema::hasColumn('dormitory_violations', 'source_type')) {
            Schema::table('dormitory_violations', function (Blueprint $table) {
                $table->string('source_type', 60)->nullable()->after('point_rule_id')->index();
                $table->uuid('source_id')->nullable()->after('source_type')->index();
                $table->boolean('is_automatic')->default(false)->after('source_id');
                $table->unique(['source_type', 'source_id'], 'dv_source_unique');
            });
        }

        if (! Schema::hasTable('dormitory_point_automations')) {
            Schema::create('dormitory_point_automations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('source_key', 80)->unique();
                $table->string('label', 191);
                $table->foreignUuid('point_catalog_id')->nullable()->constrained('dormitory_point_catalogs')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $this->ensureCatalog('116', 'Tidak hadir pada absensi asrama', 5, 116);
        $this->ensureCatalog('117', 'Tidak hadir kelas tanpa izin', 5, 117);

        $catalog = fn (string $code) => DB::table('dormitory_point_catalogs')->where('code', $code)->value('id');

        $defaults = [
            ['dormitory_attendance_alpa', 'Absensi asrama: alpa', $catalog('116')],
            ['activity_alpa', 'Kegiatan asrama: alpa', $catalog('113')],
            ['academic_attendance_alpa', 'Kehadiran kelas: alpa', $catalog('117')],
            ['permit_late_return', 'Izin: kembali melewati batas waktu', $catalog('106')],
        ];

        foreach ($defaults as [$sourceKey, $label, $catalogId]) {
            if (DB::table('dormitory_point_automations')->where('source_key', $sourceKey)->exists()) {
                continue;
            }

            DB::table('dormitory_point_automations')->insert([
                'id' => (string) Str::uuid(),
                'source_key' => $sourceKey,
                'label' => $label,
                'point_catalog_id' => $catalogId,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_point_automations');

        if (Schema::hasTable('dormitory_violations')
            && Schema::hasColumn('dormitory_violations', 'source_type')) {
            Schema::table('dormitory_violations', function (Blueprint $table) {
                $table->dropUnique('dv_source_unique');
                $table->dropIndex(['source_type']);
                $table->dropIndex(['source_id']);
                $table->dropColumn(['source_type', 'source_id', 'is_automatic']);
            });
        }
    }

    private function ensureCatalog(string $code, string $name, int $points, int $sortOrder): void
    {
        if (DB::table('dormitory_point_catalogs')->where('code', $code)->exists()) {
            return;
        }

        DB::table('dormitory_point_catalogs')->insert([
            'id' => (string) Str::uuid(),
            'kind' => 'violation',
            'code' => $code,
            'name' => $name,
            'category' => 'kedisiplinan',
            'severity' => $points <= 14 ? 'ringan' : 'sedang',
            'points_min' => $points,
            'points_max' => $points,
            'is_permanent' => false,
            'is_active' => true,
            'sort_order' => $sortOrder,
            'description' => 'Dibuat otomatis dari integrasi kehadiran/kegiatan.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
