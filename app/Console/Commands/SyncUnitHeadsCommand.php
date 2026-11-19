<?php

namespace App\Console\Commands;

use App\Models\Dormitory;
use App\Models\School;
use App\Models\User;
use App\Models\WorkUnit;
use App\Support\GtkUnitHeadAssignment;
use App\Support\UnitHeadAccess;
use Illuminate\Console\Command;

/**
 * Terapkan penetapan kepala/wakil yang sudah tersimpan di metadata work unit
 * ke jabatan, role, dan penempatan GTK.
 *
 * Dibutuhkan untuk penetapan yang dibuat sebelum halaman Kepala & Wakil
 * ikut menyinkronkan jabatan.
 */
class SyncUnitHeadsCommand extends Command
{
    protected $signature = 'gtk:sync-unit-heads
        {--apply : Simpan perubahan (tanpa ini hanya menampilkan laporan)}
        {--work-unit= : Batasi ke satu work unit id}';

    protected $description = 'Sinkronkan penetapan kepala/wakil satuan kerja ke jabatan dan role GTK.';

    public function handle(GtkUnitHeadAssignment $assignment): int
    {
        $apply = (bool) $this->option('apply');

        $this->syncWorkUnitMetadata($assignment, $apply);
        $this->syncMasterHeads($apply);

        return self::SUCCESS;
    }

    /**
     * Kepala yang ditetapkan lewat data induk (schools.principal_user_id,
     * dormitories.head_id) wajib memiliki role yang sesuai. Satu GTK dapat
     * memegang keduanya (mis. Kepala PPS Diniyah sekaligus Kepala Asramanya);
     * role ditambahkan, bukan diganti.
     */
    private function syncMasterHeads(bool $apply): void
    {
        $rows = [];
        $changed = 0;

        $schools = School::query()
            ->whereNotNull('principal_user_id')
            ->when($this->option('work-unit'), fn ($q, $id) => $q->where('work_unit_id', $id))
            ->orderBy('name')
            ->get(['id', 'name', 'principal_user_id']);

        foreach ($schools as $school) {
            $user = User::find($school->principal_user_id);

            if (! $user) {
                $rows[] = [$school->name, 'Kepala Satuan Pendidikan', '(user tidak ditemukan)', '-'];
                continue;
            }

            $hasRole = $user->hasRole('Satuan Pendidikan');
            $rows[] = [$school->name, 'Kepala Satuan Pendidikan', $user->name, $hasRole ? 'sudah' : 'ditambahkan'];

            if ($apply && ! $hasRole) {
                UnitHeadAccess::ensureSatuanPendidikanRole($user);
                $changed++;
            }
        }

        $dormitories = Dormitory::query()
            ->whereNotNull('head_id')
            ->when($this->option('work-unit'), fn ($q, $id) => $q->where('work_unit_id', $id))
            ->orderBy('name')
            ->get(['id', 'name', 'head_id']);

        foreach ($dormitories as $dormitory) {
            $user = User::find($dormitory->head_id);

            if (! $user) {
                $rows[] = [$dormitory->name, 'Kepala Asrama', '(user tidak ditemukan)', '-'];
                continue;
            }

            $hasRole = $user->hasRole('Asrama');
            $rows[] = [$dormitory->name, 'Kepala Asrama', $user->name, $hasRole ? 'sudah' : 'ditambahkan'];

            if ($apply && ! $hasRole) {
                UnitHeadAccess::ensureAsramaRole($user);
                $changed++;
            }
        }

        if ($rows === []) {
            $this->info('Tidak ada kepala satuan pendidikan/asrama pada data induk.');

            return;
        }

        $this->table(['Satuan', 'Peran', 'GTK', 'Role'], $rows);

        if ($apply) {
            $this->info("{$changed} role kepala ditambahkan.");
        } else {
            $this->warn('Laporan saja: role kepala belum ditambahkan.');
            $this->line('Jalankan ulang dengan --apply untuk menyimpan.');
        }
    }

    private function syncWorkUnitMetadata(GtkUnitHeadAssignment $assignment, bool $apply): void
    {
        $query = WorkUnit::query()
            ->where(function ($q) {
                $q->whereNotNull('metadata->head_user_id')
                    ->orWhereNotNull('metadata->vice_head_user_id');
            })
            ->when($this->option('work-unit'), fn ($q, $id) => $q->where('id', $id))
            ->orderBy('name');

        $units = $query->get();

        if ($units->isEmpty()) {
            $this->info('Tidak ada penetapan kepala/wakil yang tersimpan.');

            return;
        }

        $rows = [];
        $changed = 0;

        foreach ($units as $unit) {
            $metadata = $unit->metadata ?? [];
            $updated = $metadata;

            foreach ([
                GtkUnitHeadAssignment::HEAD => $metadata['head_user_id'] ?? null,
                GtkUnitHeadAssignment::VICE => $metadata['vice_head_user_id'] ?? null,
            ] as $type => $userId) {
                if (! $userId) {
                    continue;
                }

                $user = User::find($userId);

                if (! $user) {
                    $rows[] = [$unit->name, $type === GtkUnitHeadAssignment::HEAD ? 'Kepala' : 'Wakil', '(user tidak ditemukan)', '-'];
                    continue;
                }

                $position = $assignment->positionFor($unit, $type);
                $title = $position?->name ?? $assignment->titleFor($unit, $type);

                $rows[] = [
                    $unit->name,
                    $type === GtkUnitHeadAssignment::HEAD ? 'Kepala' : 'Wakil',
                    $user->name,
                    $title,
                ];

                if (! $apply) {
                    continue;
                }

                $snapshotKey = $type === GtkUnitHeadAssignment::HEAD ? 'head_previous' : 'vice_previous';
                $previous = $assignment->assign($user, $unit, $type);

                if (! isset($updated[$snapshotKey])) {
                    $updated[$snapshotKey] = $previous;
                }

                $changed++;
            }

            if ($apply && $updated !== $metadata) {
                $unit->update(['metadata' => $updated]);
            }
        }

        $this->table(['Satuan Kerja', 'Peran', 'GTK', 'Jabatan'], $rows);

        if ($apply) {
            $this->info("{$changed} penetapan disinkronkan.");

            return;
        }

        $this->warn('Laporan saja: '.count($rows).' penetapan belum disinkronkan.');
        $this->line('Jalankan ulang dengan --apply untuk menyimpan.');
    }
}
