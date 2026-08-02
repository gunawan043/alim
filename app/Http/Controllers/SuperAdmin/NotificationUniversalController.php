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

        $notifications = $query->orderBy('created_at', 'desc')->paginate(20);
        $users = User::orderBy('name')->get();
        $modules = NotificationUniversal::distinct()->whereNotNull('module')->pluck('module');

        return view('super-admin.notifications.index', compact('notifications', 'users', 'modules', 'userId'));
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
        $notification = NotificationUniversal::findOrFail($id);
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function markAllRead()
    {
        NotificationUniversal::where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, string $id)
    {
        $userId = $request->route('userId');
        $notification = NotificationUniversal::findOrFail($id);
        $notification->delete();

        return redirect()->route('user.sa.notifications.index', ['userId' => $userId])
            ->with('success', 'Notifikasi berhasil dihapus.');
    }
}
