<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Update gtk_requests ──────────────────────────────────────────
        Schema::table('gtk_requests', function (Blueprint $table) {
            // type: procurement (analisis kebutuhan), trial (pengangkatan percobaan), status_increase (kenaikan status)
            if (! Schema::hasColumn('gtk_requests', 'type')) {
                $table->string('type', 50)->default('procurement')->after('requested_by');
            }
            // Procurement fields
            if (! Schema::hasColumn('gtk_requests', 'academic_year_id')) {
                $table->string('academic_year_id', 36)->nullable()->after('type');
            }
            if (! Schema::hasColumn('gtk_requests', 'notes')) {
                $table->text('notes')->nullable()->after('jabatan');
            }
            // Letter fields (trial & status_increase)
            if (! Schema::hasColumn('gtk_requests', 'letter_number')) {
                $table->string('letter_number', 100)->nullable()->after('notes');
            }
            if (! Schema::hasColumn('gtk_requests', 'letter_subject')) {
                $table->string('letter_subject', 255)->nullable()->after('letter_number');
            }
            if (! Schema::hasColumn('gtk_requests', 'letter_attachment')) {
                $table->string('letter_attachment', 100)->nullable()->after('letter_subject');
            }
            if (! Schema::hasColumn('gtk_requests', 'established_city')) {
                $table->string('established_city', 100)->nullable()->after('letter_attachment');
            }
            if (! Schema::hasColumn('gtk_requests', 'established_date')) {
                $table->date('established_date')->nullable()->after('established_city');
            }
            // Drop old unused columns
            if (Schema::hasColumn('gtk_requests', 'jabatan')) {
                $table->dropColumn('jabatan');
            }
            if (Schema::hasColumn('gtk_requests', 'jumlah')) {
                $table->dropColumn('jumlah');
            }
            if (Schema::hasColumn('gtk_requests', 'alasan')) {
                $table->dropColumn('alasan');
            }
        });

        // ── Create gtk_request_items ─────────────────────────────────────
        if (! Schema::hasTable('gtk_request_items')) {
            Schema::create('gtk_request_items', function (Blueprint $table) {
                $table->id();
                $table->char('gtk_request_id', 36);
                $table->string('item_type', 50)->comment('procurement|trial|status_increase');
                // Procurement fields
                $table->string('jabatan', 150)->nullable();
                $table->integer('kebutuhan_ideal')->default(0)->nullable();
                $table->integer('gtk_yang_ada')->default(0)->nullable();
                $table->text('kualifikasi_minimal')->nullable();
                $table->integer('kebutuhan_tambahan')->default(0)->nullable();
                $table->text('keterangan')->nullable();
                // Trial & Status Increase fields
                $table->string('nupy', 50)->nullable();
                $table->string('nama', 150)->nullable();
                $table->string('tugas', 150)->nullable();
                $table->string('lembaga', 150)->nullable();
                $table->string('status_gtk', 50)->nullable();
                $table->date('tmt')->nullable();
                $table->integer('order')->default(0);
                $table->timestamps();
                $table->foreign('gtk_request_id')->references('id')->on('gtk_requests')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_request_items');
        Schema::table('gtk_requests', function (Blueprint $table) {
            $table->string('jabatan', 191)->nullable();
            $table->tinyInteger('jumlah')->unsigned()->default(1);
            $table->text('alasan')->nullable();
            $table->dropColumn([
                'type', 'academic_year_id', 'notes',
                'letter_number', 'letter_subject', 'letter_attachment',
                'established_city', 'established_date',
            ]);
        });
    }
};
