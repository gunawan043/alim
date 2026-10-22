<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gtk_health_records')) {
            return;
        }

        Schema::table('gtk_health_records', function (Blueprint $table) {
            if (! Schema::hasColumn('gtk_health_records', 'examined_at')) {
                $table->timestamp('examined_at')->nullable()->after('check_date');
            }
            if (! Schema::hasColumn('gtk_health_records', 'weight_kg')) {
                $table->decimal('weight_kg', 5, 1)->nullable()->after('weight');
            }
            if (! Schema::hasColumn('gtk_health_records', 'height_cm')) {
                $table->decimal('height_cm', 5, 1)->nullable()->after('height');
            }
            if (! Schema::hasColumn('gtk_health_records', 'pulse_bpm')) {
                $table->unsignedSmallInteger('pulse_bpm')->nullable()->after('pulse');
            }
            if (! Schema::hasColumn('gtk_health_records', 'temperature_c')) {
                $table->decimal('temperature_c', 4, 1)->nullable()->after('temperature');
            }
            if (! Schema::hasColumn('gtk_health_records', 'resp_rate')) {
                $table->unsignedSmallInteger('resp_rate')->nullable()->after('respiration_rate');
            }
        });

        DB::table('gtk_health_records')->update([
            'examined_at' => DB::raw('check_date'),
            'weight_kg' => DB::raw('weight'),
            'height_cm' => DB::raw('height'),
            'pulse_bpm' => DB::raw('pulse'),
            'temperature_c' => DB::raw('temperature'),
            'resp_rate' => DB::raw('respiration_rate'),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('gtk_health_records')) {
            return;
        }

        Schema::table('gtk_health_records', function (Blueprint $table) {
            foreach (['examined_at', 'weight_kg', 'height_cm', 'pulse_bpm', 'temperature_c', 'resp_rate'] as $column) {
                if (Schema::hasColumn('gtk_health_records', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
