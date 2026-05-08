<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_admin_books', function (Blueprint $table) {
            $table->decimal('nr_final_weight_rs', 4, 1)->default(50.0)->after('kktp_id')
                ->comment('Bobot RS terhadap NR Final (dalam persen, default 50)');
            $table->decimal('nr_final_weight_sts', 4, 1)->default(25.0)->after('nr_final_weight_rs')
                ->comment('Bobot STS terhadap NR Final (dalam persen, default 25)');
            $table->decimal('nr_final_weight_sas', 4, 1)->default(25.0)->after('nr_final_weight_sts')
                ->comment('Bobot SAS terhadap NR Final (dalam persen, default 25)');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_admin_books', function (Blueprint $table) {
            $table->dropColumn(['nr_final_weight_rs', 'nr_final_weight_sts', 'nr_final_weight_sas']);
        });
    }
};