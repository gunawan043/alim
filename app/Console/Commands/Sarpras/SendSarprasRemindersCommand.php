<?php

namespace App\Console\Commands\Sarpras;

use App\Models\AssetMaintenanceSchedule;
use App\Models\CleaningProgram;
use App\Models\CleaningReport;
use App\Models\NotificationUniversal;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WorkUnitSarprasOfficer;
use App\Services\NotificationUniversalService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SendSarprasRemindersCommand extends Command
{
    protected $signature = 'sarpras:send-reminders
                            {--dry-run : Hitung pengingat tanpa mengirim notifikasi}';

    protected $description = 'Kirim pengingat kebersihan, program tahunan, jadwal perawatan, dan perbaikan tertunda';

    public function __construct(protected NotificationUniversalService $notifier)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $counts = [
            'cleaning_reports' => 0,
            'cleaning_programs' => 0,
            'maintenance' => 0,
            'repairs' => 0,
        ];

        $central = $this->centralUserIds();

        // Laporan kebersihan submitted lebih dari 24 jam.
        CleaningReport::with('area')
            ->where('status', 'submitted')
            ->where('created_at', '<=', now()->subHours(24))
            ->chunkById(100, function (Collection $reports) use ($dryRun, $central, &$counts) {
                foreach ($reports as $report) {
                    $recipients = $this->recipients($report->area?->work_unit_id, $central, [$report->area?->coordinator_id]);
                    if ($recipients === []) {
                        continue;
                    }

                    $counts['cleaning_reports']++;
                    if ($dryRun) {
                        continue;
                    }

                    $this->sendOnce($recipients, 'cleaning.report.pending', $report->id, [
                        'module' => 'sarpras',
                        'type' => 'warning',
                        'priority' => 'high',
                        'reference_type' => 'cleaning_report',
                        'reference_id' => $report->id,
                        'reference_code' => 'LAP-'.\Illuminate\Support\Str::limit($report->id, 8, ''),
                        'title' => 'Laporan kebersihan menunggu verifikasi',
                        'message' => ($report->area?->name ?? 'Area kebersihan').' — laporan '.$report->report_date?->format('d/m/Y').' belum diverifikasi.',
                        'action_text' => 'Verifikasi Laporan',
                        'url_builder' => fn (string $userId) => route('sarpras.kebersihan.report.show', [
                            'userId' => $userId,
                            'reportId' => $report->id,
                        ]),
                    ]);
                }
            });

        // Program tahunan yang belum dicentang pada bulan berjalan (mulai tanggal 15).
        if (now()->day >= 15) {
            CleaningProgram::with('area')
                ->where('is_active', true)
                ->chunkById(100, function (Collection $programs) use ($dryRun, $central, &$counts) {
                    foreach ($programs as $program) {
                        $months = array_map('strval', $program->months ?? []);
                        if (! in_array((string) now()->month, $months, true)) {
                            continue;
                        }

                        $isDone = $program->checks()
                            ->where('year', now()->year)
                            ->where('month', now()->month)
                            ->where('is_done', true)
                            ->exists();
                        if ($isDone) {
                            continue;
                        }

                        $recipients = $this->recipients($program->area?->work_unit_id, $central, [$program->assigned_to]);
                        if ($recipients === []) {
                            continue;
                        }

                        $counts['cleaning_programs']++;
                        if ($dryRun) {
                            continue;
                        }

                        $this->sendOnce($recipients, 'cleaning.program.pending', $program->id, [
                            'module' => 'sarpras',
                            'type' => 'warning',
                            'priority' => 'medium',
                            'reference_type' => 'cleaning_program',
                            'reference_id' => $program->id,
                            'reference_code' => $program->title,
                            'title' => 'Program kebersihan belum dicentang',
                            'message' => $program->title.' — pelaksanaan '.now()->translatedFormat('F Y').' belum ditandai.',
                            'action_text' => 'Buka Program',
                            'url_builder' => fn (string $userId) => route('sarpras.kebersihan.programs', [
                                'userId' => $userId,
                                'year' => now()->year,
                            ]),
                        ], 7 * 24);
                    }
                });
        }

        // Jadwal perawatan dalam 7 hari atau sudah lewat.
        AssetMaintenanceSchedule::with(['asset', 'workUnit'])
            ->where('is_active', true)
            ->whereNotNull('next_maintenance_date')
            ->whereDate('next_maintenance_date', '<=', today()->addDays(7))
            ->chunkById(100, function (Collection $schedules) use ($dryRun, $central, &$counts) {
                foreach ($schedules as $schedule) {
                    $recipients = $this->recipients($schedule->work_unit_id, $central, [$schedule->responsible_user_id, $schedule->created_by]);
                    if ($recipients === []) {
                        continue;
                    }

                    $counts['maintenance']++;
                    if ($dryRun) {
                        continue;
                    }

                    $due = $schedule->next_maintenance_date?->format('d/m/Y');
                    $overdue = $schedule->next_maintenance_date?->isPast();
                    $this->sendOnce($recipients, 'maintenance.due', $schedule->id, [
                        'module' => 'sarpras',
                        'type' => $overdue ? 'danger' : 'warning',
                        'priority' => $overdue ? 'high' : 'medium',
                        'reference_type' => 'maintenance_schedule',
                        'reference_id' => $schedule->id,
                        'reference_code' => $schedule->maintenance_type,
                        'title' => $overdue ? 'Jadwal perawatan terlewat' : 'Jadwal perawatan mendekat',
                        'message' => ($schedule->asset?->asset_name ?? $schedule->maintenance_type).' — jadwal '.$due.'.',
                        'action_text' => 'Lihat Jadwal',
                        'url_builder' => fn (string $userId) => route('sarpras.pemeliharaan.schedule.show', [
                            'userId' => $userId,
                            'id' => $schedule->id,
                        ]),
                    ]);
                }
            });

        // Permintaan perbaikan menunggu verifikasi lebih dari 24 jam.
        RepairRequest::with('asset')
            ->whereIn('status', [
                RepairRequest::STATUS_VERIFICATION_PENDING,
                RepairRequest::STATUS_APPROVAL_PENDING,
            ])
            ->where('created_at', '<=', now()->subHours(24))
            ->chunkById(100, function (Collection $repairs) use ($dryRun, $central, &$counts) {
                foreach ($repairs as $repair) {
                    $recipients = $this->recipients($repair->asset?->work_unit_id, $central, [$repair->assigned_to]);
                    if ($recipients === []) {
                        continue;
                    }

                    $counts['repairs']++;
                    if ($dryRun) {
                        continue;
                    }

                    $this->sendOnce($recipients, 'repair.pending', $repair->id, [
                        'module' => 'sarpras',
                        'type' => 'warning',
                        'priority' => 'high',
                        'reference_type' => 'repair_request',
                        'reference_id' => $repair->id,
                        'reference_code' => $repair->request_number,
                        'title' => 'Permintaan perbaikan menunggu tindakan',
                        'message' => $repair->request_number.' — '.$repair->title,
                        'action_text' => 'Buka Perbaikan',
                        'url_builder' => fn (string $userId) => route('sarpras.repair.show', [
                            'userId' => $userId,
                            'repairId' => $repair->id,
                        ]),
                    ]);
                }
            });

        $this->info(sprintf(
            'Pengingat Sarpras%s: laporan=%d, program=%d, perawatan=%d, perbaikan=%d',
            $dryRun ? ' (dry-run)' : '',
            $counts['cleaning_reports'],
            $counts['cleaning_programs'],
            $counts['maintenance'],
            $counts['repairs']
        ));

        return self::SUCCESS;
    }

    private function centralUserIds(): array
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Unit Rumah Tangga', 'Admin Sarpras', 'Admin Tata Usaha']))
            ->pluck('id')
            ->all();
    }

    private function recipients(?string $workUnitId, array $central, array $extra = []): array
    {
        $officers = $workUnitId
            ? WorkUnitSarprasOfficer::query()->active()->where('work_unit_id', $workUnitId)->pluck('user_id')->all()
            : [];

        return array_values(array_unique(array_filter(array_merge($central, $officers, $extra))));
    }

    private function sendOnce(array $userIds, string $action, string $referenceId, array $payload, int $dedupeHours = 24): void
    {
        foreach ($userIds as $userId) {
            $alreadySent = NotificationUniversal::query()
                ->where('user_id', $userId)
                ->where('action', $action)
                ->where('reference_id', $referenceId)
                ->where('created_at', '>=', now()->subHours($dedupeHours))
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $urlBuilder = $payload['url_builder'] ?? null;
            unset($payload['url_builder']);

            $data = array_merge($payload, [
                'action' => $action,
                'action_url' => $urlBuilder ? $urlBuilder((string) $userId) : null,
            ]);

            $this->notifier->send($userId, $data);
        }
    }
}
