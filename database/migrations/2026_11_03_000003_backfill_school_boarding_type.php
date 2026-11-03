<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Satuan pendidikan yang memiliki asrama tetapi bertipe `non_boarding`
 * membuat middleware EnsureBoardingSchool menolak (403) seluruh modul
 * asrama untuk pengguna yang ter-scope ke sekolah tersebut.
 *
 * Migration ini menyelaraskan tipe sekolah dengan data asrama yang nyata:
 * ada baris di `dormitories` → sekolah mendukung modul boarding.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('schools') || ! Schema::hasTable('dormitories')) {
            return;
        }

        if (! Schema::hasColumn('schools', 'boarding_type')) {
            return;
        }

        $schoolIds = DB::table('dormitories')
            ->whereNotNull('school_id')
            ->distinct()
            ->pluck('school_id')
            ->all();

        if ($schoolIds === []) {
            return;
        }

        DB::table('schools')
            ->whereIn('id', $schoolIds)
            ->where(function ($query) {
                $query->whereNull('boarding_type')
                    ->orWhere('boarding_type', 'non_boarding');
            })
            ->update([
                'boarding_type' => 'boarding',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Sengaja tidak dikembalikan: nilai baru mencerminkan data asrama
        // yang sebenarnya, bukan nilai sementara.
    }
};
