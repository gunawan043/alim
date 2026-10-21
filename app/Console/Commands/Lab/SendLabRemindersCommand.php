<?php

namespace App\Console\Commands\Lab;

use App\Models\Lab\LabCalibration;
use App\Models\Lab\LabIncident;
use App\Models\Lab\LabInspection;
use App\Models\Lab\LabProfile;
use App\Models\Lab\LabSafetyInspection;
use App\Models\NotificationUniversal;
use App\Services\NotificationUniversalService;
use Illuminate\Console\Command;

class SendLabRemindersCommand extends Command
{
    protected $signature = 'lab:send-reminders {--dry-run : Hitung pengingat tanpa mengirim}';

    protected $description = 'Kirim pengingat kalibrasi, pemeriksaan, K3, dan insiden laboratorium';

    public function handle(NotificationUniversalService $notifier): int
    {
        $dry = (bool) $this->option('dry-run');
        $count = 0;

        LabProfile::with(['coordinator', 'head'])->where('is_active', true)->each(function (LabProfile $lab) use ($notifier, $dry, &$count) {
            $recipients = collect([$lab->coordinator_user_id, $lab->head_user_id])->filter()->unique()->values()->all();
            if ($recipients === []) {
                return;
            }

            $calibrations = LabCalibration::with('asset')
                ->where('lab_id', $lab->id)
                ->whereNotNull('next_calibration_date')
                ->whereDate('next_calibration_date', '<=', today()->addDays(30))
                ->get();

            foreach ($calibrations as $calibration) {
                $overdue = $calibration->next_calibration_date->isPast();
                $count += $this->send($notifier, $recipients, $dry, 'lab.calibration.due', $calibration->id, [
                    'module' => 'lab',
                    'type' => $overdue ? 'danger' : 'warning',
                    'priority' => $overdue ? 'high' : 'medium',
                    'reference_type' => 'lab_calibration',
                    'reference_id' => $calibration->id,
                    'reference_code' => $calibration->asset?->asset_code,
                    'title' => $overdue ? 'Kalibrasi laboratorium terlewat' : 'Jadwal kalibrasi mendekat',
                    'message' => ($calibration->asset?->asset_name ?? 'Alat').' — jadwal '.$calibration->next_calibration_date->format('d/m/Y').'.',
                    'action_text' => 'Buka Kalibrasi',
                    'action_url' => route('lab.calibration.index', ['userId' => $lab->coordinator_user_id ?? $lab->head_user_id, 'lab_id' => $lab->id]),
                ]);
            }

            $inspections = LabInspection::where('lab_id', $lab->id)
                ->whereIn('status', ['scheduled', 'overdue'])
                ->whereDate('scheduled_date', '<=', today()->addDays(7))
                ->get();
            foreach ($inspections as $inspection) {
                $count += $this->send($notifier, $recipients, $dry, 'lab.inspection.due', $inspection->id, [
                    'module' => 'lab',
                    'type' => 'warning',
                    'priority' => 'medium',
                    'reference_type' => 'lab_inspection',
                    'reference_id' => $inspection->id,
                    'title' => 'Pemeriksaan laboratorium mendekat',
                    'message' => $lab->name.' — pemeriksaan dijadwalkan '.($inspection->scheduled_date?->format('d/m/Y') ?? '-').'.',
                    'action_text' => 'Buka Pemeriksaan',
                    'action_url' => route('lab.inspection.index', ['userId' => $lab->coordinator_user_id ?? $lab->head_user_id, 'lab_id' => $lab->id]),
                ]);
            }

            LabSafetyInspection::where('lab_id', $lab->id)
                ->whereNotNull('follow_up_date')
                ->whereDate('follow_up_date', '<=', today()->addDays(7))
                ->get()
                ->each(function (LabSafetyInspection $inspection) use ($notifier, $recipients, $dry, $lab, &$count) {
                    $count += $this->send($notifier, $recipients, $dry, 'lab.safety.followup', $inspection->id, [
                        'module' => 'lab',
                        'type' => 'warning',
                        'priority' => 'high',
                        'reference_type' => 'lab_safety_inspection',
                        'reference_id' => $inspection->id,
                        'title' => 'Tindak lanjut inspeksi K3 laboratorium',
                        'message' => $lab->name.' — tindak lanjut jatuh tempo '.$inspection->follow_up_date->format('d/m/Y').'.',
                        'action_text' => 'Buka Inspeksi',
                        'action_url' => route('lab.safety.index', ['userId' => $lab->coordinator_user_id ?? $lab->head_user_id, 'lab_id' => $lab->id]),
                    ]);
                });

            LabIncident::where('lab_id', $lab->id)->whereIn('status', ['reported', 'investigating', 'action_required'])->get()
                ->each(function (LabIncident $incident) use ($notifier, $recipients, $dry, $lab, &$count) {
                    $count += $this->send($notifier, $recipients, $dry, 'lab.incident.open', $incident->id, [
                        'module' => 'lab',
                        'type' => 'danger',
                        'priority' => 'high',
                        'reference_type' => 'lab_incident',
                        'reference_id' => $incident->id,
                        'title' => 'Insiden laboratorium belum selesai',
                        'message' => $lab->name.' — insiden '.$incident->incident_date?->format('d/m/Y').' masih berstatus '.$incident->status.'.',
                        'action_text' => 'Buka Insiden',
                        'action_url' => route('lab.incident.show', ['userId' => $lab->coordinator_user_id ?? $lab->head_user_id, 'incident' => $incident->id]),
                    ]);
                });
        });

        $this->info(($dry ? '[dry-run] ' : '').$count.' pengingat laboratorium diproses.');

        return self::SUCCESS;
    }

    private function send(NotificationUniversalService $notifier, array $recipients, bool $dry, string $action, string $referenceId, array $payload): int
    {
        $sent = 0;
        foreach ($recipients as $userId) {
            if ($dry) {
                $sent++;
                continue;
            }
            $exists = NotificationUniversal::where('user_id', $userId)
                ->where('action', $action)
                ->where('reference_id', $referenceId)
                ->where('created_at', '>=', now()->subDay())
                ->exists();
            if ($exists) {
                continue;
            }
            $notifier->send($userId, array_merge($payload, ['action' => $action]));
            $sent++;
        }

        return $sent;
    }
}
