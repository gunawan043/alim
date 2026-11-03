<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Security\SecurityCheckpoint;
use App\Models\Security\SecurityPatrolLog;
use App\Models\Security\SecurityPatrolSchedule;
use App\Models\Security\SecurityPost;
use App\Models\Security\SecurityShift;
use App\Models\Security\SecurityTeam;
use App\Services\Security\SecurityCodeService;
use App\Support\SecurityAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SecurityPatrolController extends Controller
{
    public function __construct(private readonly SecurityCodeService $codes) {}

    // ── JADWAL ──────────────────────────────────────────────────────────

    public function index(Request $request, string $userId)
    {
        $query = SecurityPatrolSchedule::query()
            ->with(['team', 'shift', 'post'])
            ->withCount('logs');

        if ($date = $request->query('date')) {
            $query->whereDate('patrol_date', $date);
        } else {
            $query->whereDate('patrol_date', '>=', today()->subDays(7));
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($postId = $request->query('post_id')) {
            $query->where('post_id', $postId);
        }

        $schedules = $query->orderByDesc('patrol_date')->orderBy('code')->paginate(20)->withQueryString();

        $stats = [
            'today' => SecurityPatrolSchedule::query()->today()->count(),
            'done' => SecurityPatrolSchedule::query()->today()
                ->where('status', SecurityPatrolSchedule::STATUS_DONE)->count(),
            'running' => SecurityPatrolSchedule::query()
                ->where('status', SecurityPatrolSchedule::STATUS_RUNNING)->count(),
            'missed' => SecurityPatrolSchedule::query()
                ->where('status', SecurityPatrolSchedule::STATUS_MISSED)->count(),
        ];

        $teams = SecurityTeam::query()->where('is_active', true)->orderBy('name')->get();
        $shifts = SecurityShift::query()->where('is_active', true)->orderBy('start_time')->get();
        $posts = SecurityPost::query()->where('is_active', true)->orderBy('name')->get();

        return view('security.patrols.index', compact(
            'userId', 'schedules', 'stats', 'teams', 'shifts', 'posts'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $data = $request->validate([
            'patrol_date' => ['required', 'date'],
            'team_id' => ['nullable', 'uuid', 'exists:security_teams,id'],
            'shift_id' => ['nullable', 'uuid', 'exists:security_shifts,id'],
            'post_id' => ['nullable', 'uuid', 'exists:security_posts,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'patrol_date.required' => 'Tanggal patroli wajib diisi.',
        ]);

        $team = $data['team_id'] ? SecurityTeam::find($data['team_id']) : null;

        $schedule = SecurityPatrolSchedule::create([
            'team_id' => $data['team_id'] ?? null,
            'shift_id' => $data['shift_id'] ?? $team?->shift_id,
            'post_id' => $data['post_id'] ?? $team?->post_id,
            'code' => $this->codes->patrol(),
            'patrol_date' => $data['patrol_date'],
            'status' => SecurityPatrolSchedule::STATUS_PLANNED,
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('user.keamanan.patrol.show', ['userId' => $userId, 'schedule' => $schedule->id])
            ->with('success', 'Jadwal patroli '.$schedule->code.' dibuat.');
    }

    public function show(Request $request, string $userId, string $schedule)
    {
        $schedule = SecurityPatrolSchedule::query()
            ->with(['team.leader', 'team.members.user', 'shift', 'post', 'logs.checkpoint', 'logs.scanner'])
            ->findOrFail($schedule);

        $checkpoints = $this->checkpointsFor($schedule);
        $loggedIds = $schedule->logs->pluck('checkpoint_id')->all();

        return view('security.patrols.show', compact('userId', 'schedule', 'checkpoints', 'loggedIds'));
    }

    public function start(Request $request, string $userId, string $schedule)
    {
        $schedule = SecurityPatrolSchedule::query()->findOrFail($schedule);

        if ($schedule->status === SecurityPatrolSchedule::STATUS_DONE) {
            return back()->with('error', 'Patroli ini sudah selesai.');
        }

        $schedule->update(['status' => SecurityPatrolSchedule::STATUS_RUNNING]);

        return back()->with('success', 'Patroli '.$schedule->code.' dimulai.');
    }

    public function finish(Request $request, string $userId, string $schedule)
    {
        $schedule = SecurityPatrolSchedule::query()->findOrFail($schedule);

        $schedule->update(['status' => SecurityPatrolSchedule::STATUS_DONE]);

        return back()->with('success', 'Patroli '.$schedule->code.' ditandai selesai.');
    }

    // ── SCAN CHECKPOINT ─────────────────────────────────────────────────

    public function scan(Request $request, string $userId, string $token)
    {
        $checkpoint = SecurityCheckpoint::query()
            ->with('post')
            ->where('qr_token', $token)
            ->where('is_active', true)
            ->first();

        if (! $checkpoint) {
            return view('security.patrols.scan-invalid', compact('userId', 'token'));
        }

        $schedules = SecurityPatrolSchedule::query()
            ->with(['team', 'shift'])
            ->whereDate('patrol_date', today())
            ->whereIn('status', [
                SecurityPatrolSchedule::STATUS_PLANNED,
                SecurityPatrolSchedule::STATUS_RUNNING,
            ])
            ->when(SecurityAccess::postIdFor($request->user()), function ($query, $postId) {
                $query->where(function ($q) use ($postId) {
                    $q->whereNull('post_id')->orWhere('post_id', $postId);
                });
            })
            ->orderBy('code')
            ->get();

        $loggedScheduleIds = SecurityPatrolLog::query()
            ->where('checkpoint_id', $checkpoint->id)
            ->whereIn('schedule_id', $schedules->pluck('id'))
            ->pluck('schedule_id')
            ->all();

        return view('security.patrols.scan', compact(
            'userId', 'checkpoint', 'schedules', 'loggedScheduleIds'
        ));
    }

    public function scanStore(Request $request, string $userId)
    {
        $data = $request->validate([
            'schedule_id' => ['required', 'uuid', 'exists:security_patrol_schedules,id'],
            'checkpoint_id' => ['required', 'uuid', 'exists:security_checkpoints,id'],
            'condition' => ['required', 'in:aman,temuan'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'schedule_id.required' => 'Pilih jadwal patroli terlebih dahulu.',
        ]);

        $schedule = SecurityPatrolSchedule::query()->findOrFail($data['schedule_id']);

        if ($schedule->status === SecurityPatrolSchedule::STATUS_DONE) {
            return back()->with('error', 'Patroli ini sudah selesai.');
        }

        $existing = SecurityPatrolLog::query()
            ->where('schedule_id', $schedule->id)
            ->where('checkpoint_id', $data['checkpoint_id'])
            ->exists();

        if ($existing) {
            return redirect()
                ->route('user.keamanan.patrol.show', ['userId' => $userId, 'schedule' => $schedule->id])
                ->with('error', 'Checkpoint ini sudah dipindai untuk jadwal tersebut.');
        }

        DB::transaction(function () use ($data, $schedule, $request) {
            SecurityPatrolLog::create([
                'schedule_id' => $schedule->id,
                'checkpoint_id' => $data['checkpoint_id'],
                'scanned_by' => $request->user()->id,
                'scanned_at' => now(),
                'condition' => $data['condition'],
                'notes' => $data['notes'] ?? null,
            ]);

            if ($schedule->status === SecurityPatrolSchedule::STATUS_PLANNED) {
                $schedule->status = SecurityPatrolSchedule::STATUS_RUNNING;
                $schedule->save();
            }

            $total = $this->checkpointsFor($schedule)->count();
            $done = $schedule->logs()->count();

            if ($total > 0 && $done >= $total) {
                $schedule->status = SecurityPatrolSchedule::STATUS_DONE;
                $schedule->save();
            }
        });

        return redirect()
            ->route('user.keamanan.patrol.show', ['userId' => $userId, 'schedule' => $schedule->id])
            ->with('success', 'Checkpoint tercatat.');
    }

    // ── CHECKPOINT CRUD ─────────────────────────────────────────────────

    public function checkpoints(Request $request, string $userId)
    {
        $checkpoints = SecurityCheckpoint::query()
            ->with('post')
            ->withCount('logs')
            ->orderBy('code')
            ->get();

        $posts = SecurityPost::query()->where('is_active', true)->orderBy('name')->get();

        return view('security.patrols.checkpoints', compact('userId', 'checkpoints', 'posts'));
    }

    public function storeCheckpoint(Request $request, string $userId)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:security_checkpoints,code'],
            'name' => ['required', 'string', 'max:120'],
            'post_id' => ['nullable', 'uuid', 'exists:security_posts,id'],
            'location' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'code.unique' => 'Kode checkpoint sudah dipakai.',
        ]);

        SecurityCheckpoint::create($data + ['is_active' => true]);

        return back()->with('success', 'Checkpoint ditambahkan.');
    }

    public function updateCheckpoint(Request $request, string $userId, string $checkpoint)
    {
        $checkpoint = SecurityCheckpoint::query()->findOrFail($checkpoint);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:security_checkpoints,code,'.$checkpoint->id],
            'name' => ['required', 'string', 'max:120'],
            'post_id' => ['nullable', 'uuid', 'exists:security_posts,id'],
            'location' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $checkpoint->update($data + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Checkpoint diperbarui.');
    }

    public function destroyCheckpoint(Request $request, string $userId, string $checkpoint)
    {
        $checkpoint = SecurityCheckpoint::query()->findOrFail($checkpoint);
        $checkpoint->delete();

        return back()->with('success', 'Checkpoint dihapus.');
    }

    public function checkpointQr(Request $request, string $userId, string $checkpoint)
    {
        $checkpoint = SecurityCheckpoint::query()->findOrFail($checkpoint);
        $size = (int) $request->query('size', 240);

        $svg = QrCode::format('svg')
            ->size(max(80, min(600, $size)))
            ->margin(1)
            ->generate($checkpoint->scanUrl());

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Checkpoint yang harus dipindai pada sebuah jadwal.
     */
    private function checkpointsFor(SecurityPatrolSchedule $schedule)
    {
        return SecurityCheckpoint::query()
            ->where('is_active', true)
            ->when($schedule->post_id, function ($query, $postId) {
                $query->where(function ($q) use ($postId) {
                    $q->whereNull('post_id')->orWhere('post_id', $postId);
                });
            })
            ->orderBy('code')
            ->get();
    }
}
