<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Tahap 1 — Fondasi Kebijakan: Rumpun Mata Pelajaran sebagai entitas resmi.
 *
 * - `subject_groups` : master rumpun (Umum, Agama, Hadits, Bahasa Arab, Tahfidz)
 *   beserta pola pengenalan nama mapel dan nama tugas tambahan koordinator.
 * - `subjects.subject_group_id` : pemetaan resmi mapel → rumpun (queryable),
 *   diisi otomatis oleh SubjectGroupResolver/observer dan dapat ditimpa admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subject_groups')) {
            Schema::create('subject_groups', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code', 50)->unique();
                $table->string('name', 100);
                $table->string('coordinator_task_name', 150)->nullable();
                $table->json('patterns')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('subjects', 'subject_group_id')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->uuid('subject_group_id')->nullable()->after('name')->index();
            });
        }

        $now = now();

        // Urutan penting: rumpun yang lebih spesifik dicek lebih dulu;
        // 'umum' adalah fallback (pola '*').
        $groups = [
            [
                'code' => 'tahfidz',
                'name' => 'Tahfidz',
                'coordinator_task_name' => 'Koordinator Guru Tahfidz',
                'patterns' => ['tahfidz', 'tahfiz', 'hifzh', 'hafalan', 'quran', "qur'an", 'tajwid'],
                'sort_order' => 10,
            ],
            [
                'code' => 'hadits',
                'name' => 'Hadits',
                'coordinator_task_name' => 'Koordinator Guru Hadits',
                'patterns' => ['hadits', 'hadist'],
                'sort_order' => 20,
            ],
            [
                'code' => 'bahasa_arab',
                'name' => 'Bahasa Arab',
                'coordinator_task_name' => 'Koordinator Guru Bahasa Arab',
                'patterns' => ['bahasa arab', 'b. arab', 'b.arab', 'qowaid', 'sharaf', 'tashrif', 'imla', 'muthalaah', 'insya', 'balaghah', 'arab'],
                'sort_order' => 30,
            ],
            [
                'code' => 'agama',
                'name' => 'Agama',
                'coordinator_task_name' => 'Koordinator Guru Agama',
                'patterns' => ['aqidah', 'akidah', 'adab', 'fiqih', 'fikih', 'tarikh', 'pendidikan agama', 'pai', 'ski'],
                'sort_order' => 40,
            ],
            [
                'code' => 'umum',
                'name' => 'Umum',
                'coordinator_task_name' => 'Koordinator Guru Umum',
                'patterns' => ['*'],
                'sort_order' => 999,
            ],
        ];

        foreach ($groups as $group) {
            DB::table('subject_groups')->updateOrInsert(
                ['code' => $group['code']],
                [
                    'id' => DB::table('subject_groups')->where('code', $group['code'])->value('id') ?: (string) Str::uuid(),
                    'name' => $group['name'],
                    'coordinator_task_name' => $group['coordinator_task_name'],
                    'patterns' => json_encode($group['patterns']),
                    'sort_order' => $group['sort_order'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        // Backfill mapel existing (tanpa model, aman lintas driver).
        $patternGroups = DB::table('subject_groups')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'patterns']);

        $subjects = DB::table('subjects')->whereNull('subject_group_id')->get(['id', 'name']);

        foreach ($subjects as $subject) {
            $normalized = mb_strtolower(trim((string) $subject->name));

            foreach ($patternGroups as $group) {
                $patterns = json_decode((string) $group->patterns, true) ?: [];
                foreach ($patterns as $pattern) {
                    $pattern = mb_strtolower(trim((string) $pattern));

                    if ($pattern === '') {
                        continue;
                    }

                    if ($pattern === '*' || str_contains($normalized, $pattern)) {
                        DB::table('subjects')->where('id', $subject->id)->update(['subject_group_id' => $group->id]);

                        continue 3;
                    }
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subjects', 'subject_group_id')) {
            Schema::table('subjects', function (Blueprint $table) {
                try {
                    $table->dropIndex(['subject_group_id']);
                } catch (\Throwable) {
                }
                $table->dropColumn('subject_group_id');
            });
        }

        Schema::dropIfExists('subject_groups');
    }
};
