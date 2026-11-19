<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\GtkRoleResolver;
use Illuminate\Console\Command;

/**
 * Lengkapi role akun GTK yang belum punya role.
 *
 * Akun tanpa role dianggap belum terverifikasi dan diarahkan ke halaman
 * "Verifikasi Akun" saat pertama login, walaupun data GTK-nya sudah ada.
 */
class SyncGtkRolesCommand extends Command
{
    protected $signature = 'gtk:sync-roles
        {--apply : Simpan perubahan (tanpa ini hanya menampilkan laporan)}
        {--limit=0 : Batasi jumlah akun yang diproses}';

    protected $description = 'Isi role akun GTK yang masih kosong supaya bisa login tanpa verifikasi manual.';

    public function handle(): int
    {
        $query = User::query()
            ->whereHas('employment')
            ->whereDoesntHave('roles')
            ->with(['employment', 'gtkWorkUnits'])
            ->orderBy('name');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('Semua akun GTK sudah punya role.');

            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $apply = (bool) $this->option('apply');
        $rows = [];
        $changed = 0;

        foreach ($query->cursor() as $user) {
            $placement = $user->gtkWorkUnits->firstWhere('is_primary', true)
                ?? $user->gtkWorkUnits->first();

            $role = GtkRoleResolver::resolve(
                $user->employment?->jabatan,
                $user->employment?->jabatan_id,
                $placement?->work_unit_id,
            ) ?? '-';

            $rows[] = [
                $user->name,
                $user->employment?->nupy ?? '-',
                $user->employment?->jabatan ?? '-',
                $placement?->work_unit_id ? (\App\Models\WorkUnit::where('id', $placement->work_unit_id)->value('name') ?? '-') : '-',
                $role,
            ];

            if ($apply && \App\Models\Role::where('name', $role)->exists()) {
                $user->syncRoles([$role]);
                $changed++;
            }
        }

        $this->table(['Nama', 'NUPY', 'Jabatan', 'Satuan Kerja', 'Role'], $rows);

        if ($apply) {
            $this->info("{$changed} akun GTK diberi role.");

            return self::SUCCESS;
        }

        $this->warn("Laporan saja: {$total} akun GTK belum punya role.");
        $this->line('Jalankan ulang dengan --apply untuk menyimpan.');

        return self::SUCCESS;
    }
}
