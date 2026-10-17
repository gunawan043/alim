<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dormitory_permits', function (Blueprint $table) {
            $table->boolean('companion_is_mahrom')->default(true)->after('companion_phone');
            $table->uuid('mahrom_id')->nullable()->after('companion_is_mahrom');
            $table->string('secondary_status', 50)->nullable()->after('status')
                ->comment('Contoh: keluarga|acara|dll untuk izin, tidak_berizin|kabur untuk alpa');
            $table->integer('overdue_notified_count')->default(0)->after('approval_note');
            $table->timestamp('overdue_notified_at')->nullable()->after('overdue_notified_count');
            $table->timestamp('escalation_triggered_at')->nullable()->after('overdue_notified_at');

            $table->foreign('mahrom_id')->references('id')->on('student_mahroms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dormitory_permits', function (Blueprint $table) {
            $table->dropForeign(['mahrom_id']);
            $table->dropColumn([
                'companion_is_mahrom', 'mahrom_id', 'secondary_status',
                'overdue_notified_count', 'overdue_notified_at', 'escalation_triggered_at',
            ]);
        });
    }
};
