<?php

namespace App\Http\Controllers;

use App\Models\NotificationUniversal;
use App\Services\NotificationUniversalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationPageController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationUniversalService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Tampilkan halaman notifikasi lengkap
     */
    public function index(Request $request)
    {
        $filters = $request->only(['module', 'type', 'priority', 'is_read']);
        $userId = Auth::id();

        $notifications = $this->buildQuery($userId, $filters)
            ->paginate(20);

        $stats = [
            'total'      => NotificationUniversal::where('user_id', $userId)->notArchived()->count(),
            'unread'     => $this->notificationService->getUnreadCount($userId),
            'by_module'  => $this->getModuleStats($userId),
        ];

        return view('notifications.index', [
            'notifications' => $notifications,
            'filters'      => $filters,
            'stats'        => $stats,
        ]);
    }

    /**
     * Build query dengan filter
     */
    protected function buildQuery($userId, array $filters)
    {
        $query = NotificationUniversal::with('user')
            ->where('user_id', $userId)
            ->notArchived()
            ->notExpired();

        if (!empty($filters['module'])) {
            $query->byModule($filters['module']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['priority'])) {
            $query->byPriority($filters['priority']);
        }

        if (isset($filters['is_read'])) {
            if ($filters['is_read'] === 'true') {
                $query->read();
            } elseif ($filters['is_read'] === 'false') {
                $query->unread();
            }
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get statistik per module
     */
    protected function getModuleStats($userId)
    {
        return NotificationUniversal::where('user_id', $userId)
            ->notArchived()
            ->selectRaw('module, COUNT(*) as total, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread')
            ->groupBy('module')
            ->get()
            ->keyBy('module');
    }
}
