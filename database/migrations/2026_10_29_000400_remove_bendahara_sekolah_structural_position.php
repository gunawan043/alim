<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $position = DB::table('structural_positions')
            ->where('name', 'Bendahara Sekolah')
            ->first();

        if (! $position) {
            return;
        }

        $taskTypeId = DB::table('additional_task_types')
            ->where('kode', 'bendahara-sekolah')
            ->value('id');

        $userIds = DB::table('gtk_employments')
            ->where('jabatan_id', $position->id)
            ->pluck('user_id')
            ->filter()
            ->unique();

        if ($taskTypeId && $userIds->isNotEmpty()) {
            foreach ($userIds as $userId) {
                $exists = DB::table('gtk_additional_tasks')
                    ->where('user_id', $userId)
                    ->where('nama_tugas', 'Bendahara Sekolah')
                    ->exists();

                if (! $exists) {
                    DB::table('gtk_additional_tasks')->insert([
                        'id' => (string) Str::uuid(),
                        'user_id' => $userId,
                        'additional_task_type_id' => $taskTypeId,
                        'nama_tugas' => 'Bendahara Sekolah',
                        'tmt' => now()->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        if ($userIds->isNotEmpty()) {
            // Referensi lama dipertahankan agar data kepegawaian tidak rusak,
            // tetapi tidak lagi ditampilkan sebagai jabatan aktif.
            DB::table('structural_positions')
                ->where('id', $position->id)
                ->update(['is_active' => 0, 'updated_at' => now()]);

            return;
        }

        DB::table('structural_positions')->where('id', $position->id)->delete();
    }

    public function down(): void
    {
        // Tidak dibuat ulang: Bendahara Sekolah sekarang master jenis tugas tambahan.
    }
};
