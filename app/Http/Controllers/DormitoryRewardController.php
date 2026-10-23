<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Dormitory\StoreRewardRequest;
use App\Http\Requests\Dormitory\UpdateRewardRequest;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryPointCatalog;
use App\Models\DormitoryPointLedger;
use App\Models\DormitoryResident;
use App\Models\DormitoryReward;
use App\Services\Dormitory\DormitoryPointService;
use Illuminate\Http\Request;

class DormitoryRewardController extends Controller
{
    public function __construct(private readonly DormitoryPointService $pointService)
    {
    }

    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $query = DormitoryReward::with(['student:id,name,nisn', 'dormitory:id,name', 'givenBy:id,name'])
            ->where('dormitory_id', $asramaUuid);

        if ($activeYear) {
            $query->where(function ($q) use ($activeYear) {
                $q->whereNull('academic_year_id')
                    ->orWhere('academic_year_id', $activeYear->id);
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sq) use ($q) {
                $sq->whereHas('student', fn ($st) => $st
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('nisn', 'like', "%{$q}%"));
            });
        }

        $rewards = $query->orderByDesc('awarded_date')->paginate(20);

        $stats = [
            'total' => DormitoryReward::where('dormitory_id', $asramaUuid)->count(),
            'thisMonth' => DormitoryReward::where('dormitory_id', $asramaUuid)
                ->whereMonth('awarded_date', now()->month)
                ->whereYear('awarded_date', now()->year)
                ->count(),
        ];

        $dormitories = Dormitory::where('is_active', true)->get();

        return view('dormitory.rewards.index', compact('dormitory', 'rewards', 'stats', 'dormitories', 'userId'));
    }

    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        // Hanya santri yang aktif tinggal di asrama ini saja yang bisa mendapat penghargaan.
        $activeYear = AcademicYear::where('is_active', true)->first();
        $students = DormitoryResident::with('student:id,name,nisn,nis,gender')
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id)
            ->where('is_active', true)
            ->orderBy('bed_number')
            ->get()
            ->pluck('student')
            ->filter()
            ->unique('id')
            ->values();

        $catalogs = $this->rewardCatalogs();

        return view('dormitory.rewards.create', compact('dormitory', 'students', 'userId', 'catalogs'));
    }

    public function store(StoreRewardRequest $request, string $userId, string $asramaUuid)
    {
        $validated = $request->validated();
        $dormitory = Dormitory::findOrFail($asramaUuid);

        if ($request->hasFile('proof_path')) {
            $validated['proof_path'] = $request->file('proof_path')->store('dormitory/rewards', 'public');
        }

        $validated['dormitory_id'] = $dormitory->id;
        $validated['given_by'] = $request->user()->id;
        $validated['semester'] = $this->pointService->currentSemester();

        $this->applyCatalog($validated);

        $reward = DormitoryReward::create($validated);
        $this->pointService->recordReward($reward);

        return redirect()->route('user.asrama.violations.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Penghargaan berhasil ditambahkan.');
    }

    public function show(string $userId, string $asramaUuid, string $rewardUuid)
    {
        $reward = DormitoryReward::with(['student', 'dormitory', 'givenBy', 'academicYear'])
            ->findOrFail($rewardUuid);

        return view('dormitory.rewards.show', compact('reward', 'userId', 'asramaUuid'));
    }

    public function edit(string $userId, string $asramaUuid, string $rewardUuid)
    {
        $reward = DormitoryReward::findOrFail($rewardUuid);
        // Hanya santri yang aktif tinggal di asrama ini saja.
        $activeYear = AcademicYear::where('is_active', true)->first();
        $students = DormitoryResident::with('student:id,name,nisn,nis,gender')
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id)
            ->where('is_active', true)
            ->orderBy('bed_number')
            ->get()
            ->pluck('student')
            ->filter()
            ->unique('id')
            ->values();

        $catalogs = $this->rewardCatalogs();

        return view('dormitory.rewards.edit', compact('reward', 'students', 'userId', 'asramaUuid', 'catalogs'));
    }

    public function update(UpdateRewardRequest $request, string $userId, string $asramaUuid, string $rewardUuid)
    {
        $reward = DormitoryReward::findOrFail($rewardUuid);
        $validated = $request->validated();

        if ($request->hasFile('proof_path')) {
            if ($reward->proof_path) {
                \Storage::disk('public')->delete($reward->proof_path);
            }
            $validated['proof_path'] = $request->file('proof_path')->store('dormitory/rewards', 'public');
        }

        if (! empty($validated['point_catalog_id'])) {
            $validated['semester'] = $this->pointService->currentSemester();
            $this->applyCatalog($validated);
        }

        $reward->update($validated);

        if (! empty($validated['point_catalog_id'])) {
            $this->pointService->recordReward($reward->fresh());
        }

        return redirect()->route('user.asrama.violations.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Penghargaan berhasil diperbarui.');
    }

    private function rewardCatalogs()
    {
        return DormitoryPointCatalog::query()
            ->active()
            ->kind('reward')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
    }

    private function applyCatalog(array &$validated): void
    {
        if (empty($validated['point_catalog_id'])) {
            return;
        }

        $catalog = DormitoryPointCatalog::query()
            ->active()
            ->kind('reward')
            ->findOrFail($validated['point_catalog_id']);

        $validated['title'] = $catalog->name;
        $validated['category'] = $catalog->rewardCategoryValue();
        $validated['level'] = $catalog->rewardLevelValue();
        $validated['points'] = (int) $catalog->points_min;
    }

    public function destroy(string $userId, string $asramaUuid, string $rewardUuid)
    {
        $reward = DormitoryReward::findOrFail($rewardUuid);

        if ($reward->proof_path) {
            \Storage::disk('public')->delete($reward->proof_path);
        }

        DormitoryPointLedger::query()
            ->where('reference_type', 'dormitory_reward')
            ->where('reference_id', $reward->id)
            ->delete();

        $reward->delete();

        return redirect()->route('user.asrama.violations.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Penghargaan berhasil dihapus.');
    }
}
