<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tahfidz_ayat_positions')) {
            Schema::create('tahfidz_ayat_positions', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('surah');
                $table->unsignedSmallInteger('ayah');
                $table->unsignedSmallInteger('page_start');
                $table->unsignedSmallInteger('line_start');
                $table->unsignedSmallInteger('page_end')->nullable();
                $table->unsignedSmallInteger('line_end')->nullable();
                $table->timestamps();

                $table->unique(['surah', 'ayah']);
                $table->index(['page_start', 'line_start']);
            });
        }

        foreach ([
            'baris_start' => fn (Blueprint $table) => $table->unsignedSmallInteger('baris_start')->nullable()->after('halaman_start'),
            'baris_end' => fn (Blueprint $table) => $table->unsignedSmallInteger('baris_end')->nullable()->after('halaman_end'),
        ] as $column => $definition) {
            if (! Schema::hasColumn('tahfidz_setorans', $column)) {
                Schema::table('tahfidz_setorans', $definition);
            }
        }
    }

    public function down(): void
    {
        foreach (['baris_start', 'baris_end'] as $column) {
            if (Schema::hasColumn('tahfidz_setorans', $column)) {
                Schema::table('tahfidz_setorans', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }

        Schema::dropIfExists('tahfidz_ayat_positions');
    }
};
