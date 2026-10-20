<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $profileTables = [
        'capaian_pembelajaran',
        'tujuan_pembelajaran',
        'alur_tujuan_pembelajaran',
        'perangkat_pembelajaran',
        'prota',
        'prosem',
    ];

    public function up(): void
    {
        foreach ($this->profileTables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'curriculum_profile_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->uuid('curriculum_profile_id')->nullable()->after('school_id');
                $table->index('curriculum_profile_id', $tableName.'_profile_idx');
            });
        }

        // Fondasi KBC: pilar cinta dan indikator pada TP.
        if (Schema::hasTable('tujuan_pembelajaran')) {
            Schema::table('tujuan_pembelajaran', function (Blueprint $table) {
                if (! Schema::hasColumn('tujuan_pembelajaran', 'kbc_values')) {
                    $table->json('kbc_values')->nullable()->after('elemen')
                        ->comment('Pilar Kurikulum Berbasis Cinta yang diintegrasikan');
                }
                if (! Schema::hasColumn('tujuan_pembelajaran', 'kbc_indicator')) {
                    $table->text('kbc_indicator')->nullable()->after('kbc_values')
                        ->comment('Indikator ketercapaian nilai cinta');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tujuan_pembelajaran')) {
            Schema::table('tujuan_pembelajaran', function (Blueprint $table) {
                if (Schema::hasColumn('tujuan_pembelajaran', 'kbc_indicator')) {
                    $table->dropColumn('kbc_indicator');
                }
                if (Schema::hasColumn('tujuan_pembelajaran', 'kbc_values')) {
                    $table->dropColumn('kbc_values');
                }
            });
        }

        foreach ($this->profileTables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'curriculum_profile_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex($tableName.'_profile_idx');
                $table->dropColumn('curriculum_profile_id');
            });
        }
    }
};
