<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('library_loans')) {
            Schema::table('library_loans', function (Blueprint $table) {
                if (! Schema::hasColumn('library_loans', 'renewals_count')) {
                    $table->unsignedSmallInteger('renewals_count')->default(0)->after('status');
                }
                if (! Schema::hasColumn('library_loans', 'last_renewed_at')) {
                    $table->timestamp('last_renewed_at')->nullable()->after('renewals_count');
                }
                if (! Schema::hasColumn('library_loans', 'fine_amount')) {
                    $table->decimal('fine_amount', 12, 2)->default(0)->after('last_renewed_at');
                }
                if (! Schema::hasColumn('library_loans', 'fine_paid_at')) {
                    $table->timestamp('fine_paid_at')->nullable()->after('fine_amount');
                }
                if (! Schema::hasColumn('library_loans', 'fine_waived_at')) {
                    $table->timestamp('fine_waived_at')->nullable()->after('fine_paid_at');
                }
                if (! Schema::hasColumn('library_loans', 'fine_notes')) {
                    $table->text('fine_notes')->nullable()->after('fine_waived_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('library_loans')) {
            Schema::table('library_loans', function (Blueprint $table) {
                foreach (['fine_notes', 'fine_waived_at', 'fine_paid_at', 'fine_amount', 'last_renewed_at', 'renewals_count'] as $column) {
                    if (Schema::hasColumn('library_loans', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
