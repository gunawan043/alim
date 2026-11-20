<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pembagian tugas staf asrama memakai master jabatan & master tugas tambahan,
 * bukan daftar konstanta. Kolom `position` lama tetap disimpan sebagai salinan
 * nama jabatan agar halaman lain yang sudah membacanya tidak rusak.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dormitory_staff_assignments')) {
            return;
        }

        Schema::table('dormitory_staff_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('dormitory_staff_assignments', 'position_id')) {
                $table->foreignUuid('position_id')->nullable()->after('position')
                    ->constrained('structural_positions')->nullOnDelete();
            }

            if (! Schema::hasColumn('dormitory_staff_assignments', 'nomor_sk')) {
                $table->string('nomor_sk', 100)->nullable()->after('duties');
            }

            if (! Schema::hasColumn('dormitory_staff_assignments', 'tmt')) {
                $table->date('tmt')->nullable()->after('nomor_sk');
            }

            if (! Schema::hasColumn('dormitory_staff_assignments', 'tst')) {
                $table->date('tst')->nullable()->after('tmt');
            }
        });

        if (! Schema::hasTable('dormitory_staff_assignment_tasks')) {
            Schema::create('dormitory_staff_assignment_tasks', function (Blueprint $table) {
                // `id` auto-increment: belongsToMany()->sync() tidak mengisi
                // primary key, jadi kolom uuid tanpa default akan gagal insert.
                $table->id();
                $table->foreignUuid('assignment_id')
                    ->constrained('dormitory_staff_assignments')->cascadeOnDelete();
                $table->foreignUuid('additional_task_type_id')
                    ->constrained('additional_task_types')->cascadeOnDelete();
                $table->unsignedSmallInteger('hours_per_week')->nullable();
                $table->timestamps();

                $table->unique(['assignment_id', 'additional_task_type_id'], 'dsat_assignment_type_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_staff_assignment_tasks');

        if (! Schema::hasTable('dormitory_staff_assignments')) {
            return;
        }

        Schema::table('dormitory_staff_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('dormitory_staff_assignments', 'position_id')) {
                $table->dropConstrainedForeignId('position_id');
            }

            foreach (['tst', 'tmt', 'nomor_sk'] as $column) {
                if (Schema::hasColumn('dormitory_staff_assignments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
