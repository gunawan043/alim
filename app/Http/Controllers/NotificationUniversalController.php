<?php

namespace App\Http\Controllers;

use App\Services\NotificationUniversalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\NotificationUniversal;

class NotificationUniversalController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationUniversalService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get user notifications
     */
    public function index(Request $request)
    {
        $notifications = $this->notificationService->getUserNotifications(
            Auth::id(),
            $request->only(['module', 'type', 'is_read', 'priority', 'per_page'])
        );

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $this->notificationService->getUnreadCount(Auth::id())
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $notification = $this->notificationService->markAsRead($id, Auth::id());

        return response()->json([
            'success' => true,
            'data' => $notification
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $count = $this->notificationService->markAllAsRead(Auth::id());

        return response()->json([
            'success' => true,
            'message' => "{$count} notifications marked as read"
        ]);
    }

    /**
     * Archive notification
     */
    public function archive($id)
    {
        $notification = $this->notificationService->archive($id, Auth::id());

        return response()->json([
            'success' => true,
            'data' => $notification
        ]);
    }

    /**
     * Get unread count
     */
    public function unreadCount()
    {
        $count = $this->notificationService->getUnreadCount(Auth::id());

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy($id)
    {
        $notification = NotificationUniversal::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted'
        ]);
    }
}