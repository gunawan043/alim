<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rapor_predicate_notes')) {
            return;
        }

        Schema::create('rapor_predicate_notes', function (Blueprint $table) {
            $table->id();
            $table->char('school_id', 36)->nullable()->index();
            $table->char('study_group_id', 36)->index();
            $table->char('academic_year_id', 36)->index();
            $table->string('semester', 10);
            $table->string('predicate', 40);
            $table->text('note')->nullable();
            $table->char('updated_by', 36)->nullable();
            $table->timestamps();

            $table->unique(
                ['study_group_id', 'academic_year_id', 'semester', 'predicate'],
                'rapor_predicate_notes_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapor_predicate_notes');
    }
};
