<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Jadwal KBM = single-slot schedule (menggantikan pola
     * ClassSchedule → ClassScheduleSlot → TimeSlot yang terlalu dalam).
     *
     * Constraint unique mencegah bentrok jadwal.
     * Trigger MySQL mencegah guru/rombel ganda di waktu sama (lebih efisien
     * daripada validasi di PHP karena menjebak race condition di saat
     * import CSV atau concurrent request).
     */
    public function up(): void
    {
        Schema::create('jadwal_kbms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools');
            $table->foreignUuid('academic_year_id')->constrained('academic_years');
            $table->foreignUuid('study_group_id')->constrained('study_groups');
            $table->foreignUuid('subject_id')->constrained('subjects');
            $table->foreignUuid('teacher_id')->constrained('users');
            $table->tinyInteger('day_of_week')->unsigned()->comment('1=Sen..7=Min');
            $table->smallInteger('slot_index')->unsigned()->comment('1,2,3,... di hari yang sama');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Satu jadwal = unik (study_group × subject × day × slot_index)
            $table->unique(
                ['study_group_id', 'subject_id', 'day_of_week', 'slot_index'],
                'uk_jadwal_rsm_dmn_sl'
            );

            // Unique: satu guru × hari × slot — prevent double-teaching
            $table->unique(
                ['teacher_id', 'day_of_week', 'slot_index'],
                'uk_jadwal_guru_hari_sl'
            );

            // Indexes untuk conflict detection
            $table->index(['teacher_id', 'day_of_week']);
            $table->index(['study_group_id', 'day_of_week']);
        });

        // ── MySQL trigger: deteksi guru bentrok saat INSERT/UPDATE ──
        $checkGuruConflict = <<<'SQL'
            DECLARE dup INT DEFAULT 0;
            SELECT COUNT(*) INTO dup
            FROM jadwal_kbms
            WHERE teacher_id = NEW.teacher_id
              AND day_of_week = NEW.day_of_week
              AND slot_index != NEW.slot_index
              AND NEW.start_time < waktu_akhir(jadwal_kbms.start_time, jadwal_kbms.end_time)
              AND waktu_awal(jadwal_kbms.start_time, jadwal_kbms.end_time) < NEW.end_time;
            IF dup > 0 THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Guru sudah mengajar kelas lain pada jam yang sama';
            END IF;
        SQL;

        $createTriggerGuruInsert = "
            DROP TRIGGER IF EXISTS trg_jadwal_guru_check_ins;
            CREATE TRIGGER trg_jadwal_guru_check_ins
            BEFORE INSERT ON jadwal_kbms
            FOR EACH ROW
            BEGIN
                DECLARE dup INT DEFAULT 0;
                SELECT COUNT(*) INTO dup
                FROM jadwal_kbms
                WHERE teacher_id = NEW.teacher_id
                  AND day_of_week = NEW.day_of_week
                  AND waktu_akhir(NEW.start_time, NEW.end_time) > jadwal_kbms.start_time
                  AND waktu_awal(NEW.start_time, NEW.end_time) < jadwal_kbms.end_time;
                IF dup > 0 THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Guru sudah mengajar pada jam yang sama';
                END IF;
            END;
        ";

        $createTriggerGuruUpdate = "
            DROP TRIGGER IF EXISTS trg_jadwal_guru_check_upd;
            CREATE TRIGGER trg_jadwal_guru_check_upd
            BEFORE UPDATE ON jadwal_kbms
            FOR EACH ROW
            BEGIN
                DECLARE dup INT DEFAULT 0;
                SELECT COUNT(*) INTO dup
                FROM jadwal_kbms
                WHERE teacher_id = NEW.teacher_id
                  AND day_of_week = NEW.day_of_week
                  AND id != NEW.id
                  AND waktu_akhir(NEW.start_time, NEW.end_time) > jadwal_kbms.start_time
                  AND waktu_awal(NEW.start_time, NEW.end_time) < jadwal_kbms.end_time;
                IF dup > 0 THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Guru sudah mengajar pada jam yang sama';
                END IF;
            END;
        ";

        $createTriggerRomelConflict = "
            DROP TRIGGER IF EXISTS trg_jadwal_rsm_check_ins;
            CREATE TRIGGER trg_jadwal_rsm_check_ins
            BEFORE INSERT ON jadwal_kbms
            FOR EACH ROW
            BEGIN
                DECLARE dup INT DEFAULT 0;
                SELECT COUNT(*) INTO dup
                FROM jadwal_kbms
                WHERE study_group_id = NEW.study_group_id
                  AND day_of_week = NEW.day_of_week
                  AND id != NEW.id
                  AND waktu_akhir(NEW.start_time, NEW.end_time) > jadwal_kbms.start_time
                  AND waktu_awal(NEW.start_time, NEW.end_time) < jadwal_kbms.end_time;
                IF dup > 0 THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Rombel sudah menerima mata pelajaran pada jam yang sama';
                END IF;
            END;
        ";

        $createTriggerRomelUpdate = "
            DROP TRIGGER IF EXISTS trg_jadwal_rsm_check_upd;
            CREATE TRIGGER trg_jadwal_rsm_check_upd
            BEFORE UPDATE ON jadwal_kbms
            FOR EACH ROW
            BEGIN
                DECLARE dup INT DEFAULT 0;
                SELECT COUNT(*) INTO dup
                FROM jadwal_kbms
                WHERE study_group_id = NEW.study_group_id
                  AND day_of_week = NEW.day_of_week
                  AND id != NEW.id
                  AND waktu_akhir(NEW.start_time, NEW.end_time) > jadwal_kbms.start_time
                  AND waktu_awal(NEW.start_time, NEW.end_time) < jadwal_kbms.end_time;
                IF dup > 0 THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Rombel sudah menerima mata pelajaran pada jam yang sama';
                END IF;
            END;
        ";

        // Helper functions untuk overlap
        $createTimeSlotHelpers = <<<'SQL'
            DROP FUNCTION IF EXISTS waktu_akhir;
            CREATE FUNCTION waktu_akhir(waktu_awal TIME, waktu_akhir TIME) RETURNS DATETIME
            BEGIN
                DECLARE dt DATETIME;
                SET dt = DATE(waktu_awal);
                RETURN dt;
            END;

            DROP FUNCTION IF EXISTS waktu_awal;
            CREATE FUNCTION waktu_awal(waktu_awal TIME, waktu_akhir TIME) RETURNS DATETIME
            BEGIN
                DECLARE dt DATETIME;
                SET dt = DATE(waktu_akhir);
                RETURN dt;
            END;
        SQL;

        // Trigger untuk overlap time — gunakan comparison yang lebih sederhana
        $simpleGuruInsert = "
            DROP TRIGGER IF EXISTS trg_jadwal_guru_check_ins;
            CREATE TRIGGER trg_jadwal_guru_check_ins
            BEFORE INSERT ON jadwal_kbms
            FOR EACH ROW
            BEGIN
                DECLARE cnt INT DEFAULT 0;
                SELECT COUNT(*) INTO cnt FROM jadwal_kbms
                WHERE teacher_id = NEW.teacher_id
                  AND day_of_week = NEW.day_of_week
                  AND start_time < NEW.end_time
                  AND end_time > NEW.start_time;
                IF cnt > 0 THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Guru sudah mengajar pada jam yang sama';
                END IF;
            END;
        ";

        $simpleGuruUpdate = "
            DROP TRIGGER IF EXISTS trg_jadwal_guru_check_upd;
            CREATE TRIGGER trg_jadwal_guru_check_upd
            BEFORE UPDATE ON jadwal_kbms
            FOR EACH ROW
            BEGIN
                DECLARE cnt INT DEFAULT 0;
                SELECT COUNT(*) INTO cnt FROM jadwal_kbms
                WHERE teacher_id = NEW.teacher_id
                  AND day_of_week = NEW.day_of_week
                  AND id != NEW.id
                  AND start_time < NEW.end_time
                  AND end_time > NEW.start_time;
                IF cnt > 0 THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Guru sudah mengajar pada jam yang sama';
                END IF;
            END;
        ";

        $simpleRomelInsert = "
            DROP TRIGGER IF EXISTS trg_jadwal_rsm_check_ins;
            CREATE TRIGGER trg_jadwal_rsm_check_ins
            BEFORE INSERT ON jadwal_kbms
            FOR EACH ROW
            BEGIN
                DECLARE cnt INT DEFAULT 0;
                SELECT COUNT(*) INTO cnt FROM jadwal_kbms
                WHERE study_group_id = NEW.study_group_id
                  AND day_of_week = NEW.day_of_week
                  AND start_time < NEW.end_time
                  AND end_time > NEW.start_time;
                IF cnt > 0 THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Rombel sudah menerima mata pelajaran pada jam yang sama';
                END IF;
            END;
        ";

        $simpleRomelUpdate = "
            DROP TRIGGER IF EXISTS trg_jadwal_rsm_check_upd;
            CREATE TRIGGER trg_jadwal_rsm_check_upd
            BEFORE UPDATE ON jadwal_kbms
            FOR EACH ROW
            BEGIN
                DECLARE cnt INT DEFAULT 0;
                SELECT COUNT(*) INTO cnt FROM jadwal_kbms
                WHERE study_group_id = NEW.study_group_id
                  AND day_of_week = NEW.day_of_week
                  AND id != NEW.id
                  AND start_time < NEW.end_time
                  AND end_time > NEW.start_time;
                IF cnt > 0 THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Rombel sudah menerima mata pelajaran pada jam yang sama';
                END IF;
            END;
        ";

        // Run triggers directly via DB connection
        try {
            DB::unprepared($simpleGuruInsert);
            DB::unprepared($simpleGuruUpdate);
            DB::unprepared($simpleRomelInsert);
            DB::unprepared($simpleRomelUpdate);
        } catch (\Exception $e) {
            // Triggers may fail on non-MySQL connections — continue silently
            DB::rollback();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('DROP TRIGGER IF EXISTS trg_jadwal_guru_check_ins');
            DB::statement('DROP TRIGGER IF EXISTS trg_jadwal_guru_check_upd');
            DB::statement('DROP TRIGGER IF EXISTS trg_jadwal_rsm_check_ins');
            DB::statement('DROP TRIGGER IF EXISTS trg_jadwal_rsm_check_upd');
        } catch (\Exception $e) {
            // Ignore
        }

        Schema::dropIfExists('jadwal_kbms');
    }
};
