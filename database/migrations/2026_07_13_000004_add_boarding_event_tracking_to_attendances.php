<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dormitory_attendances', function (Blueprint $table) {
            $table->uuid('timeline_event_id')->nullable()
                ->after('notes')
                ->comment('The boarding_timeline_event record that created/auto-updated this attendance.');

            $table->uuid('source_permitted_record_id')->nullable()
                ->comment('permit_id or visit_id that triggered this attendance update.');

            $table->index('timeline_event_id');
            $table->index('source_permitted_record_id');
        });
    }

    public function down(): void
    {
        Schema::table('dormitory_attendances', function (Blueprint $table) {
            $table->dropColumn('timeline_event_id', 'source_permitted_record_id');
        });
    }
};
