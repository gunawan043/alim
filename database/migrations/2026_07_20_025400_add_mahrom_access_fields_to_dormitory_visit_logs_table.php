<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dormitory_visit_logs', function (Blueprint $table) {
            // Auto-match visitor to student's mahrom record
            $table->uuid('mahrom_id')->nullable()->after('visitor_id_number');
            $table->foreign('mahrom_id')->references('id')->on('student_mahroms')->nullOnDelete();

            // Visitor access rights (JSON)
            // {
            //   "can_stay_overnight": false,
            //   "can_visit_rooms_only": true,
            //   "restricted_areas": [],
            //   "max_visitor_count": 3,
            //   "guardian_supervision_required": true
            // }
            $table->json('visitor_access_rights')->nullable()->after('visitor_phone');
        });

        Schema::table('dormitory_visit_logs', function (Blueprint $table) {
            $table->index('mahrom_id');
        });
    }

    public function down(): void
    {
        Schema::table('dormitory_visit_logs', function (Blueprint $table) {
            $table->dropForeign(['mahrom_id']);
            $table->dropColumn('mahrom_id', 'visitor_access_rights');
        });
    }
};
