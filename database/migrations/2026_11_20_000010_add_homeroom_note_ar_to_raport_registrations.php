<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('raport_registrations')) {
            return;
        }

        Schema::table('raport_registrations', function (Blueprint $table) {
            if (! Schema::hasColumn('raport_registrations', 'homeroom_note_ar')) {
                $table->text('homeroom_note_ar')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('raport_registrations')) {
            return;
        }

        Schema::table('raport_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('raport_registrations', 'homeroom_note_ar')) {
                $table->dropColumn('homeroom_note_ar');
            }
        });
    }
};
