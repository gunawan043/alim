<?php

namespace Database\Seeders;

use App\Models\Todo;
use App\Models\TodoList;
use App\Models\TodoSubtask;
use App\Models\User;
use Illuminate\Database\Seeder;

class TodoSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil user pertama untuk demo
        $user = User::first();

        if (!$user) {
            $this->command->warn('TodoSeeder: Tidak ada user ditemukan. Lewati.');
            return;
        }

        $now = now();

        // ── Daftar Todo Lists ──────────────────────────────────────────
        $listKerja = TodoList::create([
            'user_id'    => $user->id,
            'name'       => 'Pekerjaan Kantor',
            'color'      => '#0ab39c',
            'is_default' => 0,
            'sort_order' => 1,
        ]);

        $listPribadi = TodoList::create([
            'user_id'    => $user->id,
            'name'       => 'Tugas Pribadi',
            'color'      => '#40539d',
            'is_default' => 0,
            'sort_order' => 2,
        ]);

        $listGTK = TodoList::create([
            'user_id'    => $user->id,
            'name'       => 'Manajemen GTK',
            'color'      => '#f7b84b',
            'is_default' => 1,
            'sort_order' => 3,
        ]);

        // ── Todos ──────────────────────────────────────────────────────

        $todo1 = Todo::create([
            'todo_list_id'      => $listGTK->id,
            'owner_id'          => $user->id,
            'created_by'        => $user->id,
            'title'             => 'Verifikasi data GTK baru bulan April',
            'description'       => 'Lakukan verifikasi terhadap 15 GTK baru yang baru join bulan April. Pastikan semua dokumen pendukung sudah lengkap.',
            'priority'          => 'tinggi',
            'status'            => 'sedang_berjalan',
            'due_date'          => $now->copy()->addDays(5)->toDateString(),
            'due_time'          => '16:00',
            'reminder_at'       => $now->copy()->addDays(3)->toDateTimeString(),
            'tags'              => 'gtk,verifikasi,urgent',
            'progress_percent'  => 40,
            'is_pinned'         => 1,
            'is_private'        => 0,
        ]);

        TodoSubtask::create(['todo_id' => $todo1->id, 'title' => 'Cek kelengkapan dokumen NIK', 'is_completed' => 1, 'sort_order' => 1]);
        TodoSubtask::create(['todo_id' => $todo1->id, 'title' => 'Verifikasi ijazah asli', 'is_completed' => 1, 'sort_order' => 2]);
        TodoSubtask::create(['todo_id' => $todo1->id, 'title' => 'Input ke sistem Dapodik', 'is_completed' => 0, 'sort_order' => 3]);
        TodoSubtask::create(['todo_id' => $todo1->id, 'title' => 'Approve oleh kepala sekolah', 'is_completed' => 0, 'sort_order' => 4]);

        $todo2 = Todo::create([
            'todo_list_id'      => $listGTK->id,
            'owner_id'          => $user->id,
            'created_by'        => $user->id,
            'title'             => 'Update status kepegawaian GTK honorer',
            'description'       => 'Perbarui status kepegawaian 8 GTK honorer yang akan habis kontrak dalam 2 bulan.',
            'priority'          => 'mendesak',
            'status'            => 'belum_mulai',
            'due_date'          => $now->copy()->addDays(2)->toDateString(),
            'due_time'          => '12:00',
            'reminder_at'       => $now->copy()->addDay()->toDateTimeString(),
            'tags'              => 'gtk,honorer,kontrak',
            'progress_percent'   => 0,
            'is_pinned'         => 0,
            'is_private'        => 0,
        ]);

        $todo3 = Todo::create([
            'todo_list_id'      => $listKerja->id,
            'owner_id'          => $user->id,
            'created_by'        => $user->id,
            'title'             => 'Buat laporan kegiatan bulanan',
            'description'       => 'Susun laporan kegiatan bulanan untuk dilaporkan ke kepala sekolah.',
            'priority'          => 'sedang',
            'status'            => 'sedang_berjalan',
            'due_date'          => $now->copy()->addDays(10)->toDateString(),
            'tags'              => 'laporan,bulanan',
            'progress_percent'  => 60,
            'is_pinned'         => 0,
            'is_private'        => 0,
        ]);

        TodoSubtask::create(['todo_id' => $todo3->id, 'title' => 'Kumpulkan data kehadiran GTK', 'is_completed' => 1, 'sort_order' => 1]);
        TodoSubtask::create(['todo_id' => $todo3->id, 'title' => 'Rekap absensi siswa', 'is_completed' => 1, 'sort_order' => 2]);
        TodoSubtask::create(['todo_id' => $todo3->id, 'title' => 'Tulis narrative report', 'is_completed' => 0, 'sort_order' => 3]);

        $todo4 = Todo::create([
            'todo_list_id'      => $listKerja->id,
            'owner_id'          => $user->id,
            'created_by'        => $user->id,
            'title'             => 'Arsipkan dokumen GTK pensiun 2025',
            'description'       => 'Siapkan folder arsip untuk GTK yang pensiun per 31 Desember 2025.',
            'priority'          => 'rendah',
            'status'            => 'ditunda',
            'due_date'          => $now->copy()->addDays(30)->toDateString(),
            'tags'              => 'arsip,pensiun',
            'progress_percent'  => 0,
            'is_pinned'         => 0,
            'is_private'        => 0,
            'cancelled_reason'  => null,
        ]);

        $todo5 = Todo::create([
            'todo_list_id'      => $listPribadi->id,
            'owner_id'          => $user->id,
            'created_by'        => $user->id,
            'title'             => 'Ikut pelatihan kurikulum merdeka',
            'description'       => 'Daftar dan ikuti pelatihan kurikulum merdeka batch 3 yang diselenggarakan secara online.',
            'priority'          => 'sedang',
            'status'            => 'selesai',
            'due_date'         => $now->copy()->subDays(3)->toDateString(),
            'completed_at'      => $now->copy()->subDays(3)->toDateTimeString(),
            'tags'              => 'pelatihan,kurikulum',
            'progress_percent' => 100,
            'is_pinned'         => 0,
            'is_private'       => 1,
        ]);

        $todo6 = Todo::create([
            'todo_list_id'      => $listGTK->id,
            'owner_id'          => $user->id,
            'created_by'        => $user->id,
            'title'             => 'Koordinasi jadwal supervisi akademik',
            'description'       => 'Koordinasikan dengan waka kurikulum terkait jadwal supervisi mengajar semester genap.',
            'priority'          => 'tinggi',
            'status'            => 'belum_mulai',
            'due_date'          => $now->copy()->addDays(7)->toDateString(),
            'tags'              => 'supervisi,akademik',
            'progress_percent'  => 0,
            'is_pinned'         => 0,
            'is_private'        => 0,
        ]);

        // ── Todo yang sudah overdue ────────────────────────────────────
        Todo::create([
            'todo_list_id'      => $listKerja->id,
            'owner_id'          => $user->id,
            'created_by'        => $user->id,
            'title'             => 'Submit laporan keuangan triwulan',
            'description'       => 'Laporan keuangan triwulan harus submitted ke kantor pusat.',
            'priority'          => 'tinggi',
            'status'            => 'sedang_berjalan',
            'due_date'          => $now->copy()->subDays(2)->toDateString(),
            'due_time'          => '15:00',
            'tags'              => 'keuangan,laporan',
            'progress_percent'  => 75,
            'is_pinned'         => 0,
            'is_private'        => 0,
        ]);

        $this->command->info('TodoSeeder: Sample data created.');
        $this->command->info("  - {$user->name} ({$user->email})");
        $this->command->info('  - 3 Todo Lists, 7 Todos, 7 Subtasks');
    }
}
