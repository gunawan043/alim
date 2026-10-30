<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryPointCatalog;
use App\Models\DormitoryReward;
use App\Models\DormitoryRewardRequest;
use App\Models\StudentAchievement;
use App\Services\Boarding\DormitoryPolicyAccess;
use App\Services\Dormitory\DormitoryPointService;
use App\Services\DormitoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class DormitoryRewardApprovalController extends Controller
{
    public function __construct(
        private readonly DormitoryPointService $pointService,
        private readonly DormitoryPolicyAccess $access,
    ) {}

    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $this->authorizeApprover($asramaUuid);

        $status = $request->get('status', DormitoryRewardRequest::STATUS_PENDING);
        if (! array_key_exists($status, DormitoryRewardRequest::statuses()) && $status !== 'all') {
            $status = DormitoryRewardRequest::STATUS_PENDING;
        }

        $query = DormitoryRewardRequest::with([
            'student:id,name,nisn',
            'achievement:id,event_name,level,position,event_date,certificate_path',
            'pointCatalog',
            'requestedBy:id,name',
        ])->where('dormitory_id', $asramaUuid);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->latest()->paginate(15)->withQueryString();

        $counts = DormitoryRewardRequest::query()
            ->where('dormitory_id', $asramaUuid)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $catalogs = DormitoryPointCatalog::query()
            ->active()
            ->kind('reward')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        return view('dormitory.rewards.approvals.index', compact(
            'dormitory', 'requests', 'counts', 'status', 'catalogs', 'userId', 'asramaUuid'
        ));
    }

    public function show(string $userId, string $asramaUuid, string $requestUuid)
    {
        $this->authorizeApprover($asramaUuid);
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $rewardRequest = DormitoryRewardRequest::with([
            'student',
            'achievement',
            'pointCatalog',
            'requestedBy',
            'reviewedBy',
            'reward',
        ])
            ->where('dormitory_id', $asramaUuid)
            ->findOrFail($requestUuid);

        $catalogs = DormitoryPointCatalog::query()
            ->active()
            ->kind('reward')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        return view('dormitory.rewards.approvals.show', compact(
            'rewardRequest', 'dormitory', 'catalogs', 'userId', 'asramaUuid'
        ));
    }

    public function approve(Request $request, string $userId, string $asramaUuid, string $requestUuid)
    {
        $this->authorizeApprover($asramaUuid);

        $validated = $request->validate([
            'point_catalog_id' => [
                'required',
                'uuid',
                Rule::exists('dormitory_point_catalogs', 'id')->where(
                    fn ($query) => $query->where('is_active', true)->where('kind', 'reward')
                ),
            ],
            'awarded_date' => 'nullable|date',
            'send_notification' => 'nullable|boolean',
        ]);

        $rewardRequest = DormitoryRewardRequest::query()
            ->where('dormitory_id', $asramaUuid)
            ->where('status', DormitoryRewardRequest::STATUS_PENDING)
            ->findOrFail($requestUuid);

        $catalog = DormitoryPointCatalog::query()
            ->active()
            ->kind('reward')
            ->findOrFail($validated['point_catalog_id']);

        $reward = DB::transaction(function () use ($rewardRequest, $catalog, $validated, $userId) {
            $achievementExists = $rewardRequest->student_achievement_id
                && StudentAchievement::query()->whereKey($rewardRequest->student_achievement_id)->exists();

            $reward = DormitoryReward::create([
                'student_id' => $rewardRequest->student_id,
                'dormitory_id' => $rewardRequest->dormitory_id,
                'academic_year_id' => $rewardRequest->academic_year_id,
                'title' => $rewardRequest->title,
                'category' => $catalog->rewardCategoryValue(),
                'description' => $rewardRequest->description,
                'level' => $catalog->rewardLevelValue(),
                'point_catalog_id' => $catalog->id,
                'points' => (int) $catalog->points_min,
                'given_by' => $userId,
                'awarded_date' => $validated['awarded_date'] ?? now()->toDateString(),
                'source_achievement_id' => $rewardRequest->student_achievement_id,
                'source_label' => $rewardRequest->title,
                'source_deleted_at' => $achievementExists ? null : now(),
            ]);

            $this->pointService->recordReward($reward);

            $rewardRequest->update([
                'status' => DormitoryRewardRequest::STATUS_APPROVED,
                'reviewed_by' => $userId,
                'reviewed_at' => now(),
                'rejection_reason' => null,
                'dormitory_reward_id' => $reward->id,
                'point_catalog_id' => $catalog->id,
                'category' => $catalog->rewardCategoryValue(),
                'level' => $catalog->rewardLevelValue(),
                'points' => (int) $catalog->points_min,
            ]);

            return $reward;
        });

        if ($request->boolean('send_notification', true)) {
            try {
                app(DormitoryService::class)->notifyMahromOnReward($reward->fresh('student'));
            } catch (\Throwable $e) {
                Log::warning('Notifikasi wali penghargaan asrama gagal: '.$e->getMessage());
            }
        }

        return redirect()
            ->route('user.asrama.rewards.approvals.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid,
            ])
            ->with('success', 'Pengajuan penghargaan disetujui dan poin asrama telah dicatat.');
    }

    public function reject(Request $request, string $userId, string $asramaUuid, string $requestUuid)
    {
        $this->authorizeApprover($asramaUuid);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $rewardRequest = DormitoryRewardRequest::query()
            ->where('dormitory_id', $asramaUuid)
            ->where('status', DormitoryRewardRequest::STATUS_PENDING)
            ->findOrFail($requestUuid);

        $rewardRequest->update([
            'status' => DormitoryRewardRequest::STATUS_REJECTED,
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return redirect()
            ->route('user.asrama.rewards.approvals.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid,
            ])
            ->with('success', 'Pengajuan penghargaan ditolak.');
    }

    private function authorizeApprover(string $asramaUuid): void
    {
        $user = auth()->user();

        abort_unless(
            $user && $this->access->canApproveReward($user, $asramaUuid),
            403,
            'Hanya Kepala Asrama atau Wakil Kepala Asrama yang dapat menyetujui penghargaan.'
        );
    }
}
