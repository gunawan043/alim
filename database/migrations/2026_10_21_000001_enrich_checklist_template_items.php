<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checklist_template_items', function (Blueprint $table) {
            if (! Schema::hasColumn('checklist_template_items', 'group_label')) {
                $table->string('group_label', 120)->nullable()->after('sequence');
            }
            if (! Schema::hasColumn('checklist_template_items', 'standard')) {
                $table->text('standard')->nullable()->after('label');
            }
            if (! Schema::hasColumn('checklist_template_items', 'method')) {
                $table->string('method', 60)->nullable()->after('standard');
            }
            if (! Schema::hasColumn('checklist_template_items', 'frequency')) {
                $table->string('frequency', 60)->nullable()->after('method');
            }
        });
    }

    public function down(): void
    {
        Schema::table('checklist_template_items', function (Blueprint $table) {
            $table->dropColumn(['group_label', 'standard', 'method', 'frequency']);
        });
    }
};
