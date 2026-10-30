<?php

declare(strict_types=1);

namespace App\Services\Dormitory;

use App\Models\Dormitory;
use App\Models\DormitoryReward;
use App\Models\DormitoryRewardRequest;
use App\Models\StudentAchievement;
use App\Models\User;
use App\Services\NotificationUniversalService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DormitoryRewardRequestService
{
    public function __construct(
        private readonly DormitoryRewardSuggestionService $suggestions,
        private readonly NotificationUniversalService $notifications,
    ) {}

    public function createFromAchievement(StudentAchievement $achievement, bool $notifyApprovers = true): ?DormitoryRewardRequest
    {
        $existing = DormitoryRewardRequest::query()
            ->where('student_achievement_id', $achievement->id)
            ->first();

        if ($existing) {
            if ($existing->status === DormitoryRewardRequest::STATUS_PENDING) {
                $this->syncFromAchievement($achievement);
            }

            return $existing;
        }

        $resident = $achievement->student?->activeDormitoryResident;
        if (! $resident?->dormitory_id) {
            return null;
        }

        $request = DormitoryRewardRequest::create(
            $this->payload($achievement, $resident->dormitory_id, $notifyApprovers) + [
                'status' => DormitoryRewardRequest::STATUS_PENDING,
            ]
        );

        if ($notifyApprovers) {
            $this->notifyApprovers($request);
        }

        return $request;
    }

    /**
     * Sinkronkan pengajuan yang masih pending ketika data prestasi berubah.
     * Pengajuan yang sudah approved/rejected tidak diubah agar menjadi riwayat.
     */
    public function syncFromAchievement(StudentAchievement $achievement): void
    {
        $request = DormitoryRewardRequest::query()
            ->where('student_achievement_id', $achievement->id)
            ->where('status', DormitoryRewardRequest::STATUS_PENDING)
            ->first();

        if (! $request) {
            return;
        }

        $resident = $achievement->student?->activeDormitoryResident;
        if (! $resident?->dormitory_id) {
            $this->cancel($request, 'Santri tidak lagi tercatat sebagai penghuni asrama aktif.');

            return;
        }

        $payload = $this->payload(
            $achievement,
            $resident->dormitory_id,
            $request->notify_approvers
        );
        unset($payload['requested_by']);

        $request->update($payload);
    }

    /**
     * Prestasi dihapus: batalkan pengajuan pending, atau tandai sumber
     * penghargaan yang sudah approved sebagai sudah dihapus.
     */
    public function handleAchievementDeleting(StudentAchievement $achievement): void
    {
        $requests = DormitoryRewardRequest::query()
            ->where('student_achievement_id', $achievement->id)
            ->get();

        foreach ($requests as $request) {
            if ($request->status === DormitoryRewardRequest::STATUS_PENDING) {
                $this->cancel($request, 'Prestasi sumber dihapus oleh satuan pendidikan.');

                continue;
            }

            if ($request->status === DormitoryRewardRequest::STATUS_APPROVED && $request->dormitory_reward_id) {
                DormitoryReward::query()
                    ->whereKey($request->dormitory_reward_id)
                    ->update(['source_deleted_at' => now()]);
            }
        }
    }

    public function cancel(DormitoryRewardRequest $request, string $reason): void
    {
        if ($request->status !== DormitoryRewardRequest::STATUS_PENDING) {
            return;
        }

        $request->update([
            'status' => DormitoryRewardRequest::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Notifikasi pengajuan ke asrama cukup in-app + badge sidebar.
     * Tidak ada pengiriman WhatsApp ke Kepala/Wakil Asrama.
     */
    public function notifyApprovers(DormitoryRewardRequest $request): void
    {
        $dormitory = $request->dormitory;

        if (! $dormitory) {
            return;
        }

        $recipientIds = $this->approverIds($dormitory);
        if ($recipientIds->isEmpty()) {
            return;
        }

        $studentName = $request->student?->name ?? 'Santri';
        $catalogName = $request->pointCatalog?->name ?? $request->title;
        $points = (int) $request->points;

        foreach ($recipientIds as $recipientId) {
            try {
                $url = route('user.asrama.rewards.approvals.show', [
                    'userId' => $recipientId,
                    'asramaUuid' => $dormitory->id,
                    'requestUuid' => $request->id,
                ]);
            } catch (\Throwable) {
                $url = null;
            }

            try {
                $this->notifications->sendToMany([$recipientId], [
                    'module' => 'dormitory',
                    'reference_type' => 'dormitory_reward_request',
                    'reference_id' => $request->id,
                    'type' => 'warning',
                    'action' => 'dormitory_reward_request_pending',
                    'title' => "Prestasi Santri — {$studentName}",
                    'message' => "Assalamu'alaikum. Alhamdulillahilladzi bini'matihi tatimmush sholihaat. "
                        ."Satuan pendidikan mengajukan prestasi ananda {$studentName}: \"{$request->title}\" "
                        ."untuk penghargaan asrama. Usulan: {$catalogName} ({$points} poin). "
                        .'Menunggu persetujuan Kepala/Wakil Asrama.',
                    'action_url' => $url,
                    'action_text' => 'Tinjau Pengajuan',
                    'priority' => 'medium',
                    'data' => [
                        'request_id' => $request->id,
                        'student_id' => $request->student_id,
                        'dormitory_id' => $request->dormitory_id,
                        'achievement_id' => $request->student_achievement_id,
                        'points' => $points,
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::warning('Notifikasi pengajuan penghargaan asrama gagal: '.$e->getMessage());
            }
        }
    }

    /**
     * @return Collection<int, string>
     */
    public function approverIds(Dormitory $dormitory): Collection
    {
        $ids = collect();

        if ($dormitory->head_id) {
            $ids->push((string) $dormitory->head_id);
        }

        $keywordFilter = function ($query) {
            $query->where(function ($q) {
                $q->where('jabatan', 'like', '%Kepala Asrama%')
                    ->orWhere('jabatan', 'like', '%Wakil Kepala Asrama%')
                    ->orWhere('jabatan', 'like', '%Wakil Asrama%');
            });
        };

        $viaEmployment = User::query()
            ->where('is_active', true)
            ->whereHas('employment', function ($query) use ($dormitory, $keywordFilter) {
                $query->where('school_id', $dormitory->school_id);
                $keywordFilter($query);
            })
            ->pluck('id')
            ->map(fn ($id) => (string) $id);

        $viaWorkUnit = User::query()
            ->where('is_active', true)
            ->whereHas('gtkWorkUnits', function ($query) use ($dormitory, $keywordFilter) {
                $query->where('work_unit_id', $dormitory->work_unit_id);
                $keywordFilter($query);
            })
            ->pluck('id')
            ->map(fn ($id) => (string) $id);

        return $ids->merge($viaEmployment)->merge($viaWorkUnit)->unique()->values();
    }

    private function payload(StudentAchievement $achievement, string $dormitoryId, bool $notifyApprovers): array
    {
        $catalog = $this->suggestions->suggest($achievement);

        return [
            'student_achievement_id' => $achievement->id,
            'student_id' => $achievement->student_id,
            'dormitory_id' => $dormitoryId,
            'academic_year_id' => $achievement->academic_year_id,
            'title' => $achievement->event_name,
            'description' => $this->description($achievement),
            'category' => $catalog?->rewardCategoryValue() ?? DormitoryReward::CAT_PRESTASI,
            'level' => $catalog?->rewardLevelValue() ?? DormitoryReward::LEVEL_BIASA,
            'point_catalog_id' => $catalog?->id,
            'points' => (int) ($catalog?->points_min ?? 0),
            'notify_approvers' => $notifyApprovers,
            'requested_by' => auth()->id() ?? $achievement->created_by,
            'source_snapshot' => [
                'achievement_type' => $achievement->achievement_type,
                'hafalan_category' => $achievement->hafalan_category,
                'event_name' => $achievement->event_name,
                'organizer' => $achievement->organizer,
                'level' => $achievement->level,
                'position' => $achievement->position,
                'position_detail' => $achievement->position_detail,
                'event_date' => $achievement->event_date?->toDateString(),
                'event_location' => $achievement->event_location,
                'certificate_path' => $achievement->certificate_path,
                'notes' => $achievement->notes,
            ],
        ];
    }

    private function description(StudentAchievement $achievement): string
    {
        return collect([
            'Diajukan otomatis dari Prestasi Santri.',
            'Tingkat: '.$achievement->level_label.'.',
            'Peringkat: '.$achievement->position_label.'.',
            $achievement->organizer ? 'Penyelenggara: '.$achievement->organizer.'.' : null,
        ])->filter()->implode(' ');
    }
}
