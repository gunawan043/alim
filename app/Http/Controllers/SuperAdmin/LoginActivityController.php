<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FailedLoginAttempt;
use App\Models\LoginActivity;
use App\Models\User;
use App\Services\Security\LoginActivityLogger;
use App\Services\SecurityPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoginActivityController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->route('userId');
        $activities = $this->filteredQuery($request)
            ->orderByDesc('created_at')
            ->paginate(40)
            ->withQueryString();

        $stats = [
            'today' => LoginActivity::whereDate('created_at', today())->count(),
            'success_today' => LoginActivity::where('event', 'login_success')->whereDate('created_at', today())->count(),
            'failed_today' => LoginActivity::failed()->whereDate('created_at', today())->count(),
            'suspicious_week' => LoginActivity::suspicious()->where('created_at', '>=', now()->subDays(7))->count(),
            'locked_users' => User::locked()->count(),
            'blocked_ips' => FailedLoginAttempt::query()
                ->where('locked_reason', 'ip')
                ->where('locked_until', '>', now())
                ->count(),
        ];

        $lockedUsers = User::locked()
            ->orderBy('locked_until')
            ->limit(10)
            ->get();

        $blockedIps = FailedLoginAttempt::query()
            ->where('locked_reason', 'ip')
            ->where('locked_until', '>', now())
            ->orderBy('locked_until')
            ->limit(10)
            ->get();

        $topFailedIps = LoginActivity::query()
            ->select('ip_address', DB::raw('COUNT(*) as attempts'))
            ->where('event', 'login_failed')
            ->where('created_at', '>=', now()->subDay())
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->orderByDesc('attempts')
            ->limit(5)
            ->get();

        $suspiciousRecent = LoginActivity::with('user')
            ->suspicious()
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $users = User::orderBy('name')->get();
        $events = LoginActivity::EVENT_LABELS;

        return view('super-admin.login-activities.index', compact(
            'activities',
            'stats',
            'lockedUsers',
            'blockedIps',
            'topFailedIps',
            'suspiciousRecent',
            'users',
            'events',
            'userId',
        ));
    }

    public function show(Request $request, string $id)
    {
        $userId = $request->route('userId');
        $activity = LoginActivity::with('user')->findOrFail($id);

        return view('super-admin.login-activities.show', compact('activity', 'userId'));
    }

    public function unlockUser(Request $request, string $userId, string $targetId)
    {
        $target = User::findOrFail($targetId);

        $target->forceFill([
            'failed_login_attempts' => 0,
            'locked_at' => null,
            'locked_until' => null,
        ])->save();

        $this->audit($request, 'LOGIN_ACCOUNT_UNLOCKED', $target->id, [
            'target_user' => $target->email,
        ]);

        app(LoginActivityLogger::class)->record(
            'account_unlocked',
            $request,
            $target,
            $target->email,
            'Dibuka oleh Super Admin',
        );

        return back()->with('success', 'Akun '.$target->name.' berhasil dibuka.');
    }

    public function blockIp(Request $request, string $userId)
    {
        $validated = $request->validate([
            'ip_address' => ['required', 'ip'],
            'minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $record = FailedLoginAttempt::query()
            ->where('ip_address', $validated['ip_address'])
            ->first() ?? new FailedLoginAttempt(['ip_address' => $validated['ip_address']]);

        $record->forceFill([
            'attempts' => max((int) $record->attempts, SecurityPolicy::maxLoginAttempts()),
            'last_attempt_at' => now(),
            'locked_until' => now()->addMinutes((int) $validated['minutes']),
            'locked_reason' => 'ip',
        ])->save();

        $this->audit($request, 'LOGIN_IP_BLOCKED', null, [
            'ip_address' => $validated['ip_address'],
            'minutes' => $validated['minutes'],
            'reason' => $validated['reason'] ?? null,
        ]);

        app(LoginActivityLogger::class)->record(
            'ip_blocked',
            $request,
            null,
            null,
            $validated['reason'] ?: 'Diblokir manual oleh Super Admin',
            ['minutes' => (int) $validated['minutes'], 'ip_address' => $validated['ip_address']],
        );

        return back()->with('success', 'IP '.$validated['ip_address'].' diblokir selama '.$validated['minutes'].' menit.');
    }

    public function unblockIp(Request $request, string $userId)
    {
        $validated = $request->validate([
            'ip_address' => ['required', 'ip'],
        ]);

        $deleted = FailedLoginAttempt::query()
            ->where('ip_address', $validated['ip_address'])
            ->delete();

        $this->audit($request, 'LOGIN_IP_UNBLOCKED', null, [
            'ip_address' => $validated['ip_address'],
            'deleted_rows' => $deleted,
        ]);

        app(LoginActivityLogger::class)->record(
            'ip_unblocked',
            $request,
            null,
            null,
            'Dibuka blokirnya oleh Super Admin',
            ['ip_address' => $validated['ip_address']],
        );

        return back()->with(
            $deleted ? 'success' : 'warning',
            $deleted
                ? 'Blokir IP '.$validated['ip_address'].' berhasil dibuka.'
                : 'Tidak ada blokir aktif untuk IP '.$validated['ip_address'].'.',
        );
    }

    public function export(Request $request)
    {
        $activities = $this->filteredQuery($request)
            ->orderByDesc('created_at')
            ->limit(5000)
            ->get();

        $filename = 'login-activities-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($activities) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Waktu', 'User', 'Email', 'Identity', 'Peristiwa', 'Alasan', 'IP', 'Perangkat', 'Mencurigakan']);

            foreach ($activities as $activity) {
                fputcsv($handle, [
                    $activity->created_at?->format('Y-m-d H:i:s'),
                    $activity->user?->name ?? '-',
                    $activity->email ?? $activity->user?->email ?? '-',
                    $activity->identity ?? '-',
                    $activity->event_label,
                    $activity->reason ?? '-',
                    $activity->ip_address ?? '-',
                    $activity->device,
                    $activity->is_suspicious ? 'Ya' : 'Tidak',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function filteredQuery(Request $request)
    {
        $query = LoginActivity::with('user');

        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->search).'%';
            $query->where(function ($q) use ($search) {
                $q->where('identity', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('ip_address', 'like', $search)
                    ->orWhere('reason', 'like', $search)
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search);
                    });
            });
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->boolean('suspicious')) {
            $query->where('is_suspicious', true);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        return $query;
    }

    private function audit(Request $request, string $action, ?string $recordId = null, array $metadata = []): void
    {
        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'table_name' => 'login_activities',
            'record_id' => $recordId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata ?: null,
        ]);
    }
}
