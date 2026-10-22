<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uks_treatments', function (Blueprint $table) {
            $table->text('subjective')->nullable()->after('patient_id');
            $table->text('objective')->nullable()->after('subjective');
            $table->text('assessment')->nullable()->after('diagnosis');
            $table->text('plan')->nullable()->after('treatment');
            $table->text('physical_exam')->nullable()->after('vitals');
            $table->string('icd10_code', 10)->nullable()->after('diagnosis');
            $table->string('icd10_name', 191)->nullable()->after('icd10_code');
            $table->string('disposition', 30)->nullable()->after('plan');
            $table->date('follow_up_date')->nullable()->after('disposition');
            $table->boolean('is_locked')->default(false)->after('follow_up_date');
        });
    }

    public function down(): void
    {
        Schema::table('uks_treatments', function (Blueprint $table) {
            $table->dropColumn([
                'subjective',
                'objective',
                'assessment',
                'plan',
                'physical_exam',
                'icd10_code',
                'icd10_name',
                'disposition',
                'follow_up_date',
                'is_locked',
            ]);
        });
    }
};
