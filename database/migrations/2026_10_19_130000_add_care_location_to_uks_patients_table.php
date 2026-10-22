<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uks_patients', function (Blueprint $table) {
            $table->string('care_location', 30)->nullable()->default('uks')->after('queue_status');
            $table->text('location_notes')->nullable()->after('care_location');
            $table->timestamp('location_updated_at')->nullable()->after('location_notes');
            $table->foreignUuid('location_updated_by')->nullable()->after('location_updated_at')
                ->constrained('users')->nullOnDelete();

            $table->index(['school_id', 'care_location'], 'uks_patients_care_location_idx');
        });

        $now = now();

        DB::table('uks_patients')->update([
            'care_location' => 'uks',
            'location_updated_at' => $now,
        ]);

        DB::table('uks_patients')
            ->where('status', 'kembali_ke_asrama')
            ->update(['care_location' => 'asrama']);

        DB::table('uks_patients')
            ->where('status', 'kembali_ke_sekolah')
            ->update(['care_location' => 'sekolah']);

        DB::table('uks_patients')
            ->whereIn('status', ['dijemput_wali', 'pulang', 'selesai', 'completed'])
            ->update(['care_location' => 'rumah']);

        DB::table('uks_patients')
            ->whereIn('status', ['dirujuk_ke_klinik', 'dirujuk_ke_rumah_sakit', 'dirujuk'])
            ->update(['care_location' => 'faskes']);

        DB::table('uks_patients')
            ->whereIn('status', ['rawat_uks', 'istirahat_di_uks'])
            ->update(['care_location' => 'uks']);
    }

    public function down(): void
    {
        Schema::table('uks_patients', function (Blueprint $table) {
            $table->dropIndex('uks_patients_care_location_idx');
            $table->dropForeign(['location_updated_by']);
            $table->dropColumn([
                'care_location',
                'location_notes',
                'location_updated_at',
                'location_updated_by',
            ]);
        });
    }
};
