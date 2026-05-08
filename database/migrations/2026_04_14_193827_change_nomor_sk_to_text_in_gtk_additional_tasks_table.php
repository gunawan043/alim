<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtk_additional_tasks', function (Blueprint $table) {
            $table->text('nomor_sk')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('gtk_additional_tasks', function (Blueprint $table) {
            $table->string('nomor_sk', 100)->nullable()->change();
        });
    }
};
