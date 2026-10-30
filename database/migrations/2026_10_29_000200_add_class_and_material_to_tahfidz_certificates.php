<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'class_name' => fn (Blueprint $table) => $table->string('class_name', 100)->nullable()->after('student_id'),
            'materi' => fn (Blueprint $table) => $table->text('materi')->nullable()->after('total_juz_completed'),
        ] as $column => $definition) {
            if (! Schema::hasColumn('tahfidz_certificates', $column)) {
                Schema::table('tahfidz_certificates', $definition);
            }
        }
    }

    public function down(): void
    {
        foreach (['materi', 'class_name'] as $column) {
            if (Schema::hasColumn('tahfidz_certificates', $column)) {
                Schema::table('tahfidz_certificates', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
