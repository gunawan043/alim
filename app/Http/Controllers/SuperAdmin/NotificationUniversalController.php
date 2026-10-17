<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\NotificationUniversal;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationUniversalController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->route('userId');
        $query = NotificationUniversal::with('user');

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                    ->orWhere('message', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('module') && $request->module) {
            $query->where('module', $request->module);
        }

        if ($request->has('priority') && $request->priority) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('is_read') && $request->is_read !== '') {
            $query->where('is_read', $request->is_read);
        }

        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // ── Topbar (AJAX): notifikasi MILIK pengguna aktif + scope satuan kerja ──
        if ($request->wantsJson() || $request->ajax()) {
            $workUnitId = $this->userWorkUnitId();

            $scoped = \App\Models\NotificationUniversal::query()
                ->where('user_id', auth()->id())
                ->when($workUnitId, fn ($q) => $q->where(fn ($qq) => $qq->whereNull('work_unit_id')->orWhere('work_unit_id', $workUnitId)))
                ->where('is_archived', false)
                ->notExpired()
                ->orderByDesc('created_at')
                ->limit(30)
                ->get();

            $items = $scoped->map(fn ($n) => [
                    'id' => $n->id,
                    'title' => $n->title,
                    'message' => $n->message,
                    'module' => $n->module,
                    'type' => $n->type,
                    'priority' => $n->priority,
                    'is_read' => (bool) $n->is_read,
                    'is_deleted' => (bool) $n->is_archived,
                    'action_url' => $n->action_url,
                    'action_text' => $n->action_text,
                    'created_at' => optional($n->created_at)->toIso8601String(),
                ])->values();

            return response()->json([
                'success' => true,
                'notifications' => $items,
                'data' => ['data' => $items],
                'unread_count' => $scoped->where('is_read', false)->count(),
            ]);
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(20);
        $users = User::orderBy('name')->get();
        $modules = NotificationUniversal::distinct()->whereNotNull('module')->pluck('module');

        $stats = [
            'total' => NotificationUniversal::count(),
            'today' => NotificationUniversal::whereDate('created_at', today())->count(),
            'unread' => NotificationUniversal::where('is_read', false)->count(),
            'high' => NotificationUniversal::where('priority', 'high')->count(),
        ];

        return view('super-admin.notifications.index', compact('notifications', 'users', 'modules', 'userId', 'stats'));
    }

    public function create(Request $request)
    {
        $userId = $request->route('userId');
        $users = User::orderBy('name')->get();

        return view('super-admin.notifications.create', compact('users', 'userId'));
    }

    public function store(Request $request)
    {
        $userId = $request->route('userId');

        $validated = $request->validate([
            'user_id' => 'nullable|uuid|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'module' => 'nullable|string|max:100',
            'type' => 'nullable|string|max:50',
            'action' => 'nullable|string|max:100',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'action_url' => 'nullable|url|max:500',
            'action_text' => 'nullable|string|max:100',
            'expires_at' => 'nullable|date|after:now',
            'send_email' => 'boolean',
        ]);

        NotificationUniversal::create([
            'user_id' => $validated['user_id'] ?? null,
            'title' => $validated['title'],
            'message' => $validated['message'],
            'module' => $validated['module'] ?: 'system',
            'type' => $validated['type'] ?? 'info',
            'priority' => $validated['priority'] ?? 'medium',
            'action' => $validated['action'] ?? 'system',
            'action_url' => $validated['action_url'] ?? null,
            'action_text' => $validated['action_text'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_read' => false,
        ]);

        return redirect()->route('user.sa.notifications.index', ['userId' => $userId])
            ->with('success', 'Notifikasi berhasil dikirim.');
    }

    public function markAsRead(string $id)
    {
        // Hanya pemilik notifikasi yang dapat menandai dibaca.
        $notification = NotificationUniversal::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if (! $notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function markAllRead()
    {
        // PER AKUN: hanya notifikasi milik pengguna aktif yang ditandai.
        $updated = app(\App\Services\NotificationUniversalService::class)
            ->markAllAsRead(auth()->id());

        return response()->json(['success' => true, 'updated' => $updated]);
    }

    public function unreadCount()
    {
        $count = app(\App\Services\NotificationUniversalService::class)
            ->getUnreadCount(auth()->id(), $this->userWorkUnitId());

        return response()->json(['success' => true, 'count' => $count, 'unread_count' => $count]);
    }

    public function destroy(Request $request, string $id)
    {
        $userId = $request->route('userId');
        $notification = NotificationUniversal::findOrFail($id);
        $notification->delete();

        return redirect()->route('user.sa.notifications.index', ['userId' => $userId])
            ->with('success', 'Notifikasi berhasil dihapus.');
    }

    /**
     * Satuan kerja pengguna aktif (scope notifikasi).
     */
    private function userWorkUnitId(): ?string
    {
        $user = auth()->user();

        return $user?->gtkWorkUnits()?->where('is_primary', true)->value('work_unit_id')
            ?? $user?->gtkWorkUnits()?->value('work_unit_id');
    }
}
