<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->enum('schedule_type', ['reguler', 'pengganti', 'tambahan', 'remedial'])
                  ->default('reguler')
                  ->after('is_active')
                  ->comment('Jenis jadwal: reguler = jadwal normal mingguan');
        });
    }
 
    public function down(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropColumn('schedule_type');
        });
    }
};
