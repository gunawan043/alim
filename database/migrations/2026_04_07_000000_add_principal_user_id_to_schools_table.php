<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (! Schema::hasColumn('schools', 'principal_user_id')) {
                $table->string('principal_user_id', 36)->nullable()->after('principal_nip');
                $table->foreign('principal_user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (Schema::hasColumn('schools', 'principal_user_id')) {
                $table->dropForeign(['principal_user_id']);
                $table->dropColumn('principal_user_id');
            }
        });
    }
};
