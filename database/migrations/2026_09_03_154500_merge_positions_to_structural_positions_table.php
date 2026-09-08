<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Merge `positions` table into `structural_positions`.
     *
     * Strategy:
     * 1. Add missing columns to structural_positions (matches positions structure).
     * 2. Migrate data from positions → structural_positions.
     * 3. Swap foreign key references in gtk_employments & gtk_position_proposals.
     * 4. Drop FK from gtk_employments.jabatan_id to positions.
     * 5. Update all code to use StructuralPosition instead of Position.
     */
    public function up(): void
    {
        // ─── Step 1: Add missing columns to structural_positions ───
        Schema::table('structural_positions', function (Blueprint $table) {
            $table->uuid('jenis_gtk_id')->nullable()->after('id');
            $table->uuid('role_id')->nullable()->after('jenis_gtk_id');
            $table->string('kategori', 50)->nullable()->after('role_id');
            $table->unsignedTinyInteger('urutan')->default(0)->after('kategori');
            $table->string('description')->nullable()->change();

            $table->foreign('jenis_gtk_id')
                ->references('id')
                ->on('jenis_gtk')
                ->onDelete('set null');

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('set null');
        });

        // ─── Step 2: Migrate data from positions → structural_positions ───
        $positions = DB::table('positions')->get();

        foreach ($positions as $pos) {
            // Skip if already exists in structural_positions (by name)
            $exists = DB::table('structural_positions')
                ->where('name', $pos->nama)
                ->exists();

            if ($exists) {
                continue;
            }

            // Generate code from name (slug-style)
            $code = Str::slug($pos->nama, '_');

            // Determine hierarchy_level based on kategori
            $hierarchyLevel = 10; // default为中位
            if ($pos->kategori === 'Tugas Tambahan') {
                $hierarchyLevel = 20;
            } elseif ($pos->kategori === 'Jabatan') {
                $hierarchyLevel = 5;
            }

            DB::table('structural_positions')->insert([
                'id' => (string) Str::uuid(),
                'code' => $code,
                'name' => $pos->nama,
                'level' => 'pondok', // default level
                'hierarchy_level' => $hierarchyLevel,
                'jenis_gtk_id' => $pos->jenis_gtk_id,
                'role_id' => $pos->role_id,
                'kategori' => $pos->kategori,
                'urutan' => $pos->urutan,
                'description' => $pos->deskripsi,
                'is_active' => $pos->is_active,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ─── Step 3: Map old positions IDs to new structural_positions IDs ───
        $idMap = [];
        $positions = DB::table('positions')->get(['id', 'nama']);
        foreach ($positions as $pos) {
            $spId = DB::table('structural_positions')
                ->where('name', $pos->nama)
                ->value('id');

            if ($spId) {
                $idMap[$pos->id] = $spId;
            }
        }

        // ─── Step 4: Update foreign keys ───
        // Update gtk_employments.jabatan_id
        foreach ($idMap as $oldId => $newId) {
            DB::table('gtk_employments')
                ->where('jabatan_id', $oldId)
                ->update(['jabatan_id' => $newId]);
        }

        // Update gtk_position_proposals.proposed_position_id
        foreach ($idMap as $oldId => $newId) {
            DB::table('gtk_position_proposals')
                ->where('proposed_position_id', $oldId)
                ->update(['proposed_position_id' => $newId]);
        }

        // ─── Step 5: Drop old FK constraints referencing positions ───
        $tablesToFix = [
            ['table' => 'gtk_employments', 'column' => 'jabatan_id', 'newFk' => 'gtk_employments_jabatan_id_structural_foreign'],
            ['table' => 'gtk_position_proposals', 'column' => 'proposed_position_id', 'newFk' => 'gtk_position_proposals_proposed_position_id_structural_foreign'],
        ];

        foreach ($tablesToFix as $config) {
            if (! Schema::hasTable($config['table']) || ! Schema::hasColumn($config['table'], $config['column'])) {
                continue;
            }

            try {
                // Get FK constraint names using KEY_COLUMN_USAGE (has COLUMN_NAME)
                $existingFks = DB::select('
                    SELECT kcus.CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE kcus
                    JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                        ON rc.CONSTRAINT_SCHEMA = DATABASE()
                        AND rc.TABLE_NAME = ?
                        AND rc.CONSTRAINT_NAME = kcus.CONSTRAINT_NAME
                    WHERE kcus.TABLE_SCHEMA = DATABASE()
                        AND kcus.TABLE_NAME = ?
                        AND kcus.COLUMN_NAME = ?
                ', [$config['table'], $config['table'], $config['column']]);

                foreach ($existingFks as $fk) {
                    DB::statement("ALTER TABLE `{$config['table']}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                }

                DB::statement("
                    ALTER TABLE `{$config['table']}`
                    ADD CONSTRAINT `{$config['newFk']}`
                    FOREIGN KEY (`{$config['column']}`) REFERENCES `structural_positions`(`id`) ON DELETE SET NULL
                ");
            } catch (Exception $e) {
                Log::warning("FK adjustment skipped for {$config['table']}.{$config['column']}: ".$e->getMessage());
            }
        }

        // Drop old unique constraint on positions if exists
        if (Schema::hasTable('positions')) {
            try {
                DB::statement('ALTER TABLE `positions` DROP INDEX `positions_jenis_gtk_id_nama_unique`');
            } catch (Exception $e) {
                Log::warning('Index drop skipped: '.$e->getMessage());
            }
        }

        // Drop the old positions table
        if (Schema::hasTable('positions')) {
            Schema::dropIfExists('positions');
        }
    }

    public function down(): void
    {
        // Recreate positions table if it doesn't exist (for FK restoration)
        if (! Schema::hasTable('positions')) {
            Schema::create('positions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('jenis_gtk_id')->nullable();
                $table->uuid('role_id')->nullable();
                $table->string('nama', 150);
                $table->string('kategori', 50)->nullable();
                $table->text('deskripsi')->nullable();
                $table->unsignedTinyInteger('urutan')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('jenis_gtk_id')
                    ->references('id')
                    ->on('jenis_gtk')
                    ->onDelete('set null');

                $table->foreign('role_id')
                    ->references('id')
                    ->on('roles')
                    ->onDelete('set null');

                $table->unique(['jenis_gtk_id', 'nama']);
            });
        }

        // Restore positional data: structural_positions -> positions
        $structuralPositions = DB::table('structural_positions')
            ->whereNotNull('jenis_gtk_id')
            ->orWhereNotNull('role_id')
            ->get();

        foreach ($structuralPositions as $sp) {
            $exists = DB::table('positions')->where('id', $sp->id)->exists();
            if (! $exists) {
                DB::table('positions')->insert([
                    'id' => $sp->id,
                    'jenis_gtk_id' => $sp->jenis_gtk_id,
                    'role_id' => $sp->role_id,
                    'nama' => $sp->name,
                    'kategori' => $sp->kategori,
                    'deskripsi' => $sp->description,
                    'urutan' => $sp->urutan,
                    'is_active' => $sp->is_active,
                    'created_at' => $sp->created_at,
                    'updated_at' => $sp->updated_at,
                ]);
            }
        }

        // Restore FK references to positions
        $tablesToRestore = [
            ['table' => 'gtk_employments', 'column' => 'jabatan_id'],
            ['table' => 'gtk_position_proposals', 'column' => 'proposed_position_id'],
        ];

        foreach ($tablesToRestore as $config) {
            if (! Schema::hasTable($config['table']) || ! Schema::hasColumn($config['table'], $config['column'])) {
                continue;
            }

            try {
                // Get FK constraint names using KEY_COLUMN_USAGE (has COLUMN_NAME)
                $existingFks = DB::select('
                    SELECT kcus.CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE kcus
                    JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                        ON rc.CONSTRAINT_SCHEMA = DATABASE()
                        AND rc.TABLE_NAME = ?
                        AND rc.CONSTRAINT_NAME = kcus.CONSTRAINT_NAME
                    WHERE kcus.TABLE_SCHEMA = DATABASE()
                        AND kcus.TABLE_NAME = ?
                        AND kcus.COLUMN_NAME = ?
                ', [$config['table'], $config['table'], $config['column']]);

                foreach ($existingFks as $fk) {
                    DB::statement("ALTER TABLE `{$config['table']}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                }

                DB::statement("
                    ALTER TABLE `{$config['table']}`
                    ADD CONSTRAINT `{$config['table']}_{$config['column']}_foreign`
                    FOREIGN KEY (`{$config['column']}`) REFERENCES `positions`(`id`) ON DELETE SET NULL
                ");
            } catch (Exception $e) {
                Log::warning("Down FK restore skipped for {$config['table']}.{$config['column']}: ".$e->getMessage());
            }
        }

        // Drop new columns from structural_positions
        Schema::table('structural_positions', function (Blueprint $table) {
            $table->dropForeign(['jenis_gtk_id']);
            $table->dropForeign(['role_id']);
            $table->dropColumn(['jenis_gtk_id', 'role_id', 'kategori', 'urutan']);
        });
    }
};
