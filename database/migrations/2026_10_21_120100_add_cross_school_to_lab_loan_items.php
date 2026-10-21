<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_loan_items', function (Blueprint $table) {
            if (! Schema::hasColumn('lab_loan_items', 'asset_school_id')) {
                $table->foreignUuid('asset_school_id')->nullable()->after('asset_id')
                    ->constrained('schools')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('lab_loan_items', function (Blueprint $table) {
            if (Schema::hasColumn('lab_loan_items', 'asset_school_id')) {
                $table->dropForeign(['asset_school_id']);
                $table->dropColumn('asset_school_id');
            }
        });
    }
};
