<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uks_patients', function (Blueprint $table) {
            $table->string('medical_record_no', 30)->nullable()->after('id');
            $table->string('queue_number', 20)->nullable()->after('medical_record_no');
            $table->date('queue_date')->nullable()->after('queue_number');
            $table->string('queue_status', 20)->default('menunggu')->after('queue_date');
            $table->string('triage_level', 5)->nullable()->after('queue_status');
            $table->timestamp('arrived_at')->nullable()->after('triage_level');
            $table->timestamp('called_at')->nullable()->after('arrived_at');
            $table->timestamp('screened_at')->nullable()->after('called_at');
            $table->timestamp('completed_at')->nullable()->after('screened_at');
            $table->text('allergies')->nullable()->after('completed_at');
            $table->date('follow_up_date')->nullable()->after('allergies');

            $table->index(['school_id', 'queue_date', 'queue_status'], 'uks_patients_queue_idx');
            $table->index('medical_record_no', 'uks_patients_mr_idx');
        });

        // Pasien lama bukan bagian dari antrean klinik baru.
        DB::table('uks_patients')->update(['queue_status' => 'selesai']);
    }

    public function down(): void
    {
        Schema::table('uks_patients', function (Blueprint $table) {
            $table->dropIndex('uks_patients_queue_idx');
            $table->dropIndex('uks_patients_mr_idx');
            $table->dropColumn([
                'medical_record_no',
                'queue_number',
                'queue_date',
                'queue_status',
                'triage_level',
                'arrived_at',
                'called_at',
                'screened_at',
                'completed_at',
                'allergies',
                'follow_up_date',
            ]);
        });
    }
};
