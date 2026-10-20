<?php

declare(strict_types=1);

namespace App\Services\Sarpras;

use App\Models\Supervisi;
use App\Models\SupervisiInstrumen;
use App\Models\SupervisiPengaturan;
use App\Models\SupervisiSk;

class SupervisiWorkflowService
{
    /**
     * @return array<string, mixed>
     */
    public function status(string $schoolId, ?string $yearId, int $semester = 1): array
    {
        $sk = SupervisiSk::with('anggota')
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $yearId)
            ->where('semester', $semester)
            ->first();

        $roles = $sk?->anggota->pluck('peran')->unique()->all() ?? [];
        $teamComplete = $sk && in_array('ksp', $roles, true)
            && in_array('wksp', $roles, true)
            && in_array('guru', $roles, true);

        $instrumenCount = SupervisiInstrumen::where('is_active', true)
            ->where(function ($q) use ($schoolId) {
                $q->whereNull('school_id')->orWhere('school_id', $schoolId);
            })
            ->count();

        $settingsCount = SupervisiPengaturan::where('school_id', $schoolId)
            ->where('academic_year_id', $yearId)
            ->where('is_active', true)
            ->count();

        $scheduleQuery = Supervisi::where('school_id', $schoolId)
            ->where('academic_year_id', $yearId)
            ->where('semester', $semester);

        $scheduleCount = (clone $scheduleQuery)->count();
        $distributedCount = (clone $scheduleQuery)->whereNotNull('distributed_at')->count();
        $assessedCount = (clone $scheduleQuery)->whereNotNull('nilai_akhir')->count();
        $reportedCount = (clone $scheduleQuery)->whereNotNull('reported_to_wadir_at')->count();

        $steps = [
            [
                'key' => 'team',
                'label' => 'Tim & SK',
                'done' => $teamComplete,
                'detail' => $sk
                    ? 'SK '.$sk->nomor.' · '.count($roles).'/3 peran'
                    : 'Belum ada SK tim',
                'route' => 'waka.supervisi-sk.index',
            ],
            [
                'key' => 'instrument',
                'label' => 'Instrumen',
                'done' => $instrumenCount > 0,
                'detail' => $instrumenCount.' instrumen aktif',
                'route' => 'waka.supervisi-instrumen.index',
            ],
            [
                'key' => 'schedule',
                'label' => 'Jadwal',
                'done' => $scheduleCount > 0,
                'detail' => $settingsCount.' pasangan · '.$scheduleCount.' jadwal',
                'route' => 'waka.supervisi.index',
            ],
            [
                'key' => 'distribution',
                'label' => 'Distribusi',
                'done' => $distributedCount > 0,
                'detail' => $distributedCount.' jadwal/instrumen didistribusikan',
                'route' => 'waka.supervisi.index',
            ],
            [
                'key' => 'assessment',
                'label' => 'Penilaian',
                'done' => $assessedCount > 0,
                'detail' => $assessedCount.' jadwal dinilai',
                'route' => 'waka.supervisi.index',
            ],
            [
                'key' => 'report',
                'label' => 'Laporan & Refleksi',
                'done' => $reportedCount > 0,
                'detail' => $reportedCount.' laporan ke Wadir',
                'route' => 'waka.supervisi.index',
            ],
        ];

        $nextStep = collect($steps)->firstWhere('done', false);

        return [
            'semester' => $semester,
            'steps' => $steps,
            'next_step' => $nextStep,
            'sk' => $sk,
            'instrumen_count' => $instrumenCount,
            'settings_count' => $settingsCount,
            'schedule_count' => $scheduleCount,
            'distributed_count' => $distributedCount,
            'assessed_count' => $assessedCount,
            'reported_count' => $reportedCount,
        ];
    }
}
