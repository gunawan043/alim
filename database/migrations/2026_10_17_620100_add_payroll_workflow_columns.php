<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll')) {
            return;
        }

        Schema::table('payroll', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll', 'calculation_meta')) {
                $table->json('calculation_meta')->nullable();
            }
            if (! Schema::hasColumn('payroll', 'published_at')) {
                $table->timestamp('published_at')->nullable();
            }
            if (! Schema::hasColumn('payroll', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
            if (! Schema::hasColumn('payroll', 'paid_by')) {
                $table->foreignUuid('paid_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payroll')) {
            return;
        }

        Schema::table('payroll', function (Blueprint $table) {
            if (Schema::hasColumn('payroll', 'paid_by')) {
                $table->dropConstrainedForeignId('paid_by');
            }
            foreach (['published_at', 'paid_at', 'calculation_meta'] as $column) {
                if (Schema::hasColumn('payroll', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
