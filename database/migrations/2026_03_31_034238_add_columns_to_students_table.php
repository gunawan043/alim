<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('photo_path', 255)->nullable()->after('email')
                ->comment('Foto profil santri (terpisah dari user avatar)');
            $table->year('alumni_year')->nullable()->after('graduation_date')
                ->comment('Tahun lulus sebagai alumni, NULL jika masih aktif');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'alumni_year']);
        });
    }
};
