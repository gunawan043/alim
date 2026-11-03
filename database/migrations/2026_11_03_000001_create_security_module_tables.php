<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Skema modul Satuan Keamanan.
 *
 * Semua tabel baru (tidak mengubah tabel lama) supaya aman dijalankan di
 * produksi yang memakai SQLite. Tidak memakai enum/JSON khas MySQL agar
 * portabel.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── ZONA AREA ────────────────────────────────────────────────────
        if (! Schema::hasTable('security_zones')) {
            Schema::create('security_zones', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code', 30)->unique();
                $table->string('name', 100);
                $table->string('risk_level', 20)->default('rendah');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // ── POS JAGA ─────────────────────────────────────────────────────
        if (! Schema::hasTable('security_posts')) {
            Schema::create('security_posts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('zone_id')->nullable();
                $table->string('code', 30)->unique();
                $table->string('name', 100);
                $table->string('location', 191)->nullable();
                $table->string('phone', 30)->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('zone_id')->references('id')->on('security_zones')->nullOnDelete();
                $table->index(['is_active']);
            });
        }

        // ── SHIFT ────────────────────────────────────────────────────────
        if (! Schema::hasTable('security_shifts')) {
            Schema::create('security_shifts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code', 30)->unique();
                $table->string('name', 60);
                $table->string('start_time', 5);
                $table->string('end_time', 5);
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // ── REGU ─────────────────────────────────────────────────────────
        if (! Schema::hasTable('security_teams')) {
            Schema::create('security_teams', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('post_id')->nullable();
                $table->uuid('shift_id')->nullable();
                $table->uuid('leader_id')->nullable();
                $table->string('code', 30)->unique();
                $table->string('name', 100);
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('post_id')->references('id')->on('security_posts')->nullOnDelete();
                $table->foreign('shift_id')->references('id')->on('security_shifts')->nullOnDelete();
                $table->foreign('leader_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('security_team_members')) {
            Schema::create('security_team_members', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('team_id');
                $table->uuid('user_id');
                $table->string('position', 30)->default('anggota');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('team_id')->references('id')->on('security_teams')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->unique(['team_id', 'user_id']);
                $table->index(['user_id', 'is_active']);
            });
        }

        // ── LOG TAMU (POS GERBANG) ───────────────────────────────────────
        if (! Schema::hasTable('security_guest_logs')) {
            Schema::create('security_guest_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('post_id')->nullable();
                $table->uuid('dormitory_id')->nullable();
                $table->uuid('student_id')->nullable();
                $table->string('code', 40)->unique();
                $table->string('visitor_name', 150);
                $table->string('id_type', 30)->default('ktp');
                $table->string('id_number', 50)->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('origin', 150)->nullable();
                $table->string('institution', 150)->nullable();
                $table->string('purpose', 191);
                $table->string('person_to_meet', 150)->nullable();
                $table->string('relationship', 60)->nullable();
                $table->unsignedSmallInteger('visitor_count')->default(1);
                $table->string('vehicle_plate', 20)->nullable();
                $table->string('card_number', 30)->nullable();
                $table->timestamp('badge_printed_at')->nullable();
                $table->timestamp('check_in_at');
                $table->timestamp('check_out_at')->nullable();
                $table->string('status', 20)->default('di_dalam');
                $table->text('notes')->nullable();
                $table->uuid('recorded_by')->nullable();
                $table->uuid('checked_out_by')->nullable();
                $table->timestamps();

                $table->foreign('post_id')->references('id')->on('security_posts')->nullOnDelete();
                $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('checked_out_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['status', 'check_in_at']);
                $table->index(['check_in_at']);
            });
        }

        // ── LOG KENDARAAN ────────────────────────────────────────────────
        if (! Schema::hasTable('security_vehicle_logs')) {
            Schema::create('security_vehicle_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('post_id')->nullable();
                $table->string('code', 40)->unique();
                $table->string('plate_number', 20);
                $table->string('vehicle_type', 40)->default('mobil');
                $table->string('driver_name', 150);
                $table->string('driver_phone', 30)->nullable();
                $table->string('purpose', 191);
                $table->unsignedSmallInteger('passenger_count')->default(1);
                $table->timestamp('check_in_at');
                $table->timestamp('check_out_at')->nullable();
                $table->string('status', 20)->default('di_dalam');
                $table->text('notes')->nullable();
                $table->uuid('recorded_by')->nullable();
                $table->uuid('checked_out_by')->nullable();
                $table->timestamps();

                $table->foreign('post_id')->references('id')->on('security_posts')->nullOnDelete();
                $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['status', 'check_in_at']);
            });
        }

        // ── CHECKPOINT PATROLI ───────────────────────────────────────────
        if (! Schema::hasTable('security_checkpoints')) {
            Schema::create('security_checkpoints', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('post_id')->nullable();
                $table->string('code', 30)->unique();
                $table->string('name', 120);
                $table->string('qr_token', 64)->unique();
                $table->string('location', 191)->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('post_id')->references('id')->on('security_posts')->nullOnDelete();
            });
        }

        // ── JADWAL PATROLI ───────────────────────────────────────────────
        if (! Schema::hasTable('security_patrol_schedules')) {
            Schema::create('security_patrol_schedules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('team_id')->nullable();
                $table->uuid('shift_id')->nullable();
                $table->uuid('post_id')->nullable();
                $table->string('code', 40)->unique();
                $table->date('patrol_date');
                $table->string('status', 20)->default('dijadwalkan');
                $table->text('notes')->nullable();
                $table->uuid('created_by')->nullable();
                $table->timestamps();

                $table->foreign('team_id')->references('id')->on('security_teams')->nullOnDelete();
                $table->foreign('shift_id')->references('id')->on('security_shifts')->nullOnDelete();
                $table->foreign('post_id')->references('id')->on('security_posts')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['patrol_date', 'status']);
            });
        }

        if (! Schema::hasTable('security_patrol_logs')) {
            Schema::create('security_patrol_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('schedule_id');
                $table->uuid('checkpoint_id');
                $table->uuid('scanned_by')->nullable();
                $table->timestamp('scanned_at');
                $table->string('condition', 20)->default('aman');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('schedule_id')->references('id')->on('security_patrol_schedules')->cascadeOnDelete();
                $table->foreign('checkpoint_id')->references('id')->on('security_checkpoints')->cascadeOnDelete();
                $table->foreign('scanned_by')->references('id')->on('users')->nullOnDelete();
                $table->unique(['schedule_id', 'checkpoint_id']);
            });
        }

        // ── INSIDEN ──────────────────────────────────────────────────────
        if (! Schema::hasTable('security_incident_types')) {
            Schema::create('security_incident_types', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code', 30)->unique();
                $table->string('name', 100);
                $table->string('default_severity', 20)->default('sedang');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('security_incidents')) {
            Schema::create('security_incidents', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('incident_type_id')->nullable();
                $table->uuid('post_id')->nullable();
                $table->uuid('zone_id')->nullable();
                $table->uuid('reported_by')->nullable();
                $table->uuid('assigned_to')->nullable();
                $table->uuid('closed_by')->nullable();
                $table->string('code', 40)->unique();
                $table->string('title', 191);
                $table->text('description');
                $table->string('location', 191)->nullable();
                $table->timestamp('occurred_at');
                $table->string('severity', 20)->default('sedang');
                $table->string('status', 20)->default('baru');
                $table->text('resolution')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();

                $table->foreign('incident_type_id')->references('id')->on('security_incident_types')->nullOnDelete();
                $table->foreign('post_id')->references('id')->on('security_posts')->nullOnDelete();
                $table->foreign('zone_id')->references('id')->on('security_zones')->nullOnDelete();
                $table->foreign('reported_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
                $table->foreign('closed_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['status', 'occurred_at']);
            });
        }

        if (! Schema::hasTable('security_incident_actions')) {
            Schema::create('security_incident_actions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('incident_id');
                $table->uuid('actor_id')->nullable();
                $table->string('action', 100);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('incident_id')->references('id')->on('security_incidents')->cascadeOnDelete();
                $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        // ── KONTAK DARURAT ───────────────────────────────────────────────
        if (! Schema::hasTable('security_contacts')) {
            Schema::create('security_contacts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name', 120);
                $table->string('role', 100)->nullable();
                $table->string('phone', 30);
                $table->string('category', 40)->default('internal');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'security_incident_actions',
            'security_incidents',
            'security_incident_types',
            'security_patrol_logs',
            'security_patrol_schedules',
            'security_checkpoints',
            'security_vehicle_logs',
            'security_guest_logs',
            'security_team_members',
            'security_teams',
            'security_shifts',
            'security_posts',
            'security_zones',
            'security_contacts',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
