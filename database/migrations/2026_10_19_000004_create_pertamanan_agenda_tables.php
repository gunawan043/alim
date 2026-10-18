<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pertamanan_agendas')) {
            Schema::create('pertamanan_agendas', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->date('agenda_date')->unique();
                $table->string('academic_year', 20);
                $table->uuid('officer_id')->nullable();
                $table->text('notes')->nullable();
                $table->uuid('created_by')->nullable();
                $table->timestamps();

                $table->foreign('officer_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('pertamanan_agenda_items')) {
            Schema::create('pertamanan_agenda_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('agenda_id');
                $table->string('section', 40)->default('pemeliharaan');
                $table->string('group_title')->nullable();
                $table->string('title');
                $table->string('status', 20)->default('belum');
                $table->uuid('officer_id')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->uuid('created_by')->nullable();
                $table->timestamps();

                $table->foreign('agenda_id')->references('id')->on('pertamanan_agendas')->cascadeOnDelete();
                $table->foreign('officer_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['agenda_id', 'section', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pertamanan_agenda_items');
        Schema::dropIfExists('pertamanan_agendas');
    }
};
