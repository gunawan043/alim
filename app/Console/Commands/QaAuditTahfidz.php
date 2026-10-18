<?php

namespace App\Console\Commands;

use App\Models\TahfidzCertificate;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzIntervention;
use App\Models\TahfidzSetoran;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class QaAuditTahfidz extends Command
{
    protected $signature = 'tahfidz:qa-audit {--strict : Gagal jika ada temuan}';

    protected $description = 'Audit route, tabel, dan data Tahfidz yang berpotensi menyebabkan 403/404';

    public function handle(): int
    {
        $routes = [
            'user.tahfidz.muqorror.index',
            'user.tahfidz.rpp.index',
            'user.tahfidz.halaqah.index',
            'user.tahfidz.setoran.index',
            'user.tahfidz.mutabaah.index',
            'user.tahfidz.tasmi.index',
            'user.tahfidz.monitoring.index',
            'user.tahfidz.input.index',
            'user.tahfidz.santri.show',
            'user.tahfidz.audit.index',
            'user.tahfidz.consistency.index',
            'user.tahfidz.executive.index',
            'user.tahfidz.interventions.index',
        ];
        $tables = [
            'tahfidz_groups', 'tahfidz_group_members', 'tahfidz_setorans',
            'tahfidz_juz_progress', 'tahfidz_progress_recaps', 'tahfidz_mutabaah',
            'tahfidz_student_targets', 'tahfidz_tasmian_sessions',
            'tahfidz_tasmian_participants', 'tahfidz_tasmian_scores',
            'tahfidz_certificates', 'tahfidz_interventions',
        ];

        $missingRoutes = collect($routes)->reject(fn ($name) => Route::has($name))->values();
        $missingTables = collect($tables)->reject(fn ($table) => Schema::hasTable($table))->values();

        $checks = [
            ['Route Tahfidz terdaftar', $missingRoutes->isEmpty(), $missingRoutes->count(), $missingRoutes->implode(', ')],
            ['Tabel Tahfidz tersedia', $missingTables->isEmpty(), $missingTables->count(), $missingTables->implode(', ')],
            ['Setoran pending', true, TahfidzSetoran::where('verification_status', 'pending')->count(), 'Gunakan auto-verify bila disengaja'],
            ['Halaqah tanpa guru', true, TahfidzGroup::whereNull('teacher_id')->count(), 'Perbaiki penugasan guru'],
            ['Anggota di halaqah nonaktif', true, TahfidzGroupMember::where('status', 'aktif')->whereHas('group', fn ($q) => $q->where('is_active', false))->count(), 'Pindahkan/nonaktifkan anggota'],
            ['Syahadah tanpa peserta', true, TahfidzCertificate::whereDoesntHave('participant')->count(), 'Periksa relasi sertifikat'],
            ['Syahadah peserta tanpa nilai', true, TahfidzCertificate::whereHas('participant', fn ($q) => $q->whereDoesntHave('score'))->count(), 'Lengkapi nilai tasmi'],
            ['Tindak lanjut terlambat', true, TahfidzIntervention::where('status', 'open')->whereDate('due_date', '<', now()->toDateString())->count(), 'Tindak lanjuti atau tutup'],
        ];

        $this->table(
            ['Pemeriksaan', 'Status', 'Jumlah', 'Catatan'],
            collect($checks)->map(fn ($row) => [
                $row[0],
                $row[1] ? 'OK' : 'GAGAL',
                $row[2],
                $row[3],
            ])->all()
        );

        $critical = ! $missingRoutes->isEmpty() || ! $missingTables->isEmpty();
        $hasFindings = $critical || collect($checks)->contains(fn ($row) => ! $row[1] || ($row[0] !== 'Setoran pending' && $row[2] > 0));

        if ($this->option('strict') && $hasFindings) {
            $this->error('Audit menemukan masalah yang perlu diperbaiki.');

            return self::FAILURE;
        }

        $this->info('Audit Tahfidz selesai.');

        return self::SUCCESS;
    }
}
