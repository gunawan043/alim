<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom konfigurasi eksplisit untuk izin pulang (periode fleksibel)
     * dan mode kuota izin khusus (none / shared_with_pulang / own_quota).
     */
    public function up(): void
    {
        Schema::table('dormitory_leave_policies', function (Blueprint $table) {
            $table->unsignedSmallInteger('pulang_quota')
                ->nullable()
                ->comment('Jumlah maksimum izin pulang dalam satu periode (null = tanpa batas).');

            $table->enum('pulang_quota_period', ['monthly', 'quarterly', 'semester', 'yearly'])
                ->nullable()
                ->comment('Periode reset kuota pulang. Null = tanpa batas.');

            $table->enum('special_quota_mode', ['none', 'shared_with_pulang', 'own_quota'])
                ->default('none')
                ->comment('Mode kuota izin khusus: none=tanpa batas, shared_with_pulang=hitung bareng pulang, own_quota=pakai quota_per_week/month/semester/year.');
        });

        // Backfill pulang_quota dari quota_per_month untuk policy pulang existing
        // supaya migrasi in-place tidak langsung kehilangan aturan lama.
        \DB::statement("
            UPDATE dormitory_leave_policies
            SET pulang_quota = quota_per_month,
                pulang_quota_period = 'monthly'
            WHERE permit_type = 'pulang'
              AND pulang_quota IS NULL
              AND quota_per_month IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('dormitory_leave_policies', function (Blueprint $table) {
            $table->dropColumn(['pulang_quota', 'pulang_quota_period', 'special_quota_mode']);
        });
    }
};
