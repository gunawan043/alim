<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_loan_items', function (Blueprint $table) {
            if (! Schema::hasColumn('lab_loan_items', 'origin_room_id')) {
                $table->foreignUuid('origin_room_id')->nullable()->after('asset_school_id')
                    ->constrained('asset_rooms')->nullOnDelete();
            }
            if (! Schema::hasColumn('lab_loan_items', 'loan_location')) {
                $table->string('loan_location', 191)->nullable()->after('notes');
            }
            if (! Schema::hasColumn('lab_loan_items', 'return_location')) {
                $table->string('return_location', 191)->nullable()->after('loan_location');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lab_loan_items', function (Blueprint $table) {
            foreach (['return_location', 'loan_location'] as $column) {
                if (Schema::hasColumn('lab_loan_items', $column)) {
                    $table->dropColumn($column);
                }
            }
            if (Schema::hasColumn('lab_loan_items', 'origin_room_id')) {
                $table->dropForeign(['origin_room_id']);
                $table->dropColumn('origin_room_id');
            }
        });
    }
};
