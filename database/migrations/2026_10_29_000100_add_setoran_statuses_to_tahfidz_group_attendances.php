<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `tahfidz_group_attendances`
                MODIFY COLUMN `status`
                ENUM('hadir','izin','sakit','alpa','belum_setor','belum_siap')
                NOT NULL DEFAULT 'hadir'");
        } else {
            Schema::table('tahfidz_group_attendances', function (Blueprint $table) {
                $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa', 'belum_setor', 'belum_siap'])
                    ->default('hadir')
                    ->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `tahfidz_group_attendances`
                MODIFY COLUMN `status`
                ENUM('hadir','izin','sakit','alpa')
                NOT NULL DEFAULT 'hadir'");
        } else {
            Schema::table('tahfidz_group_attendances', function (Blueprint $table) {
                $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa'])
                    ->default('hadir')
                    ->change();
            });
        }
    }
};
