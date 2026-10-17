<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AiChatConversation;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Monitoring riwayat chat AI seluruh pengguna (Super Admin).
 */
class AiChatMonitorController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeSuper($request);

        $search = trim((string) $request->query('search', ''));
        $schoolId = $request->query('school_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $conversations = AiChatConversation::query()
            ->with(['user:id,name,email', 'school:id,name'])
            ->withCount('messages')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
                });
            })
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $schools = School::query()->orderBy('name')->get(['id', 'name']);

        return view('super-admin.ai-chat.index', compact(
            'conversations',
            'schools',
            'search',
            'schoolId',
            'dateFrom',
            'dateTo',
        ));
    }

    public function show(Request $request, string $userId, string $conversationId)
    {
        $this->authorizeSuper($request);

        $conversation = AiChatConversation::with(['user:id,name,email', 'school:id,name'])->findOrFail($conversationId);

        $messages = $conversation->messages()->get(['id', 'role', 'content', 'metadata', 'created_at']);

        return view('super-admin.ai-chat.show', compact('conversation', 'messages'));
    }

    public function destroy(Request $request, string $userId, string $conversationId)
    {
        $this->authorizeSuper($request);

        $conversation = AiChatConversation::findOrFail($conversationId);

        DB::transaction(function () use ($conversation) {
            $conversation->messages()->delete();
            $conversation->delete();
        });

        return redirect()
            ->route('user.sa.ai-chat.index', ['userId' => $userId])
            ->with('success', 'Riwayat chat dihapus.');
    }

    private function authorizeSuper(Request $request): void
    {
        abort_unless(canPermission('super-admin-only'), 403, 'Hanya Super Admin yang dapat memantau chat AI.');
    }
}
