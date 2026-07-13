<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->date('warranty_start_date')->nullable()->after('last_condition_update');
            $table->date('warranty_end_date')->nullable()->after('warranty_start_date');
            $table->string('warranty_provider')->nullable()->after('warranty_end_date');
            $table->text('warranty_terms')->nullable()->after('warranty_provider');
            $table->json('warranty_documents')->nullable()->after('warranty_terms'); // doc names/paths
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['warranty_start_date', 'warranty_end_date', 'warranty_provider', 'warranty_terms', 'warranty_documents']);
        });
    }
};
