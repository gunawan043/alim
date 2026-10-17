<?php

// database/migrations/2024_01_01_000012_add_school_columns_to_gtk_employments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtk_employments', function (Blueprint $table) {
            // Tambahkan kolom untuk relasi ke sekolah
            $table->foreignUuid('school_id')->nullable()->after('user_id')
                ->constrained('schools')
                ->nullOnDelete();

            $table->foreignUuid('academic_year_id')->nullable()->after('school_id')
                ->constrained('academic_years')
                ->nullOnDelete();

            $table->foreignUuid('study_group_id')->nullable()->after('academic_year_id')
                ->constrained('study_groups')
                ->nullOnDelete();

            // Kolom tambahan untuk penempatan di sekolah
            $table->enum('position_type', [
                'guru_mapel',
                'guru_kelas',
                'guru_bk',
                'kepala_sekolah',
                'wakasek',
                'tendik',
            ])->default('guru_mapel')->after('jabatan');

            $table->boolean('is_homeroom')->default(false)->after('position_type');
            $table->string('decree_number', 100)->nullable()->after('nomor_sk');
            $table->date('decree_date')->nullable()->after('tanggal_sk');

            // Index
            $table->index('school_id');
            $table->index('academic_year_id');
            $table->index('is_homeroom');
        });
    }

    public function down(): void
    {
        Schema::table('gtk_employments', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropForeign(['academic_year_id']);
            $table->dropForeign(['study_group_id']);

            $table->dropColumn([
                'school_id',
                'academic_year_id',
                'study_group_id',
                'position_type',
                'is_homeroom',
                'decree_number',
                'decree_date',
            ]);
        });
    }
};
