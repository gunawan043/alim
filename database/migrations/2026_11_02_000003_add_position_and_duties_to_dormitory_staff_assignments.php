<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dormitory_staff_assignments')) {
            return;
        }

        Schema::table('dormitory_staff_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('dormitory_staff_assignments', 'position')) {
                $table->string('position', 50)->nullable()->after('status')->index();
            }
            if (! Schema::hasColumn('dormitory_staff_assignments', 'shift')) {
                $table->string('shift', 20)->nullable()->after('position');
            }
            if (! Schema::hasColumn('dormitory_staff_assignments', 'duties')) {
                $table->json('duties')->nullable()->after('shift');
            }
            if (! Schema::hasColumn('dormitory_staff_assignments', 'updated_by_id')) {
                $table->foreignUuid('updated_by_id')->nullable()->after('assigned_by_id')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('dormitory_staff_assignments')) {
            return;
        }

        Schema::table('dormitory_staff_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('dormitory_staff_assignments', 'updated_by_id')) {
                $table->dropConstrainedForeignId('updated_by_id');
            }
            foreach (['duties', 'shift', 'position'] as $column) {
                if (Schema::hasColumn('dormitory_staff_assignments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
