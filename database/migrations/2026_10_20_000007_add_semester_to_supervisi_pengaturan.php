<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supervisi_pengaturan', function (Blueprint $table) {
            if (! Schema::hasColumn('supervisi_pengaturan', 'semester')) {
                $table->unsignedTinyInteger('semester')->default(1)->after('academic_year_id');
            }
        });

        // Unique lama tidak membedakan semester.
        try {
            Schema::table('supervisi_pengaturan', function (Blueprint $table) {
                $table->dropUnique('supervisi_pengaturan_unique');
            });
        } catch (\Throwable $e) {
            // Abaikan bila index sudah tidak ada.
        }

        Schema::table('supervisi_pengaturan', function (Blueprint $table) {
            $table->unique(
                ['school_id', 'academic_year_id', 'semester', 'supervisor_gtk_id', 'supervisee_gtk_id'],
                'supervisi_pengaturan_semester_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('supervisi_pengaturan', function (Blueprint $table) {
            try {
                $table->dropUnique('supervisi_pengaturan_semester_unique');
            } catch (\Throwable $e) {
                // Abaikan.
            }
            if (Schema::hasColumn('supervisi_pengaturan', 'semester')) {
                $table->dropColumn('semester');
            }
        });
    }
};
