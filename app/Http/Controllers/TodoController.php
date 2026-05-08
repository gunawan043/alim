<?php

namespace App\Http\Controllers;

use App\Http\Requests\TodoRequest;
use App\Models\Todo;
use App\Models\TodoList;
use App\Models\TodoSubtask;
use App\Models\TodoComment;
use App\Models\TodoAttachment;
use App\Models\TodoWatcher;
use App\Models\User;
use App\Services\NotificationUniversalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TodoController extends Controller
{
    protected NotificationUniversalService $notificationService;

    public function __construct(NotificationUniversalService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Wraps Todo::findOrFail with a JSON 404 response.
     * Returns null on failure (caller must return early).
     */
    protected function findTodoOrFail(string $id): ?Todo
    {
        try {
            return Todo::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return null;
        }
    }

    // ─── Main Views ──────────────────────────────────────────────

    public function index(Request $request)
    {
        $userId = auth()->id();

        // Get all todo lists for sidebar
        $todoLists = TodoList::forUser($userId)
            ->ordered()
            ->withCount(['todos' => fn ($q) => $q->whereNull('deleted_at')])
            ->get();

        // Ensure default list exists
        $defaultList = TodoList::getOrCreateDefault($userId);

        // Stats for the selected list
        $listId = $request->get('list_id', $defaultList->id);
        $tab = $request->get('tab', 'my');

        $stats = $this->getStats($userId, $listId, $tab);

        // Get todos based on tab and filters
        $todos = $this->getTodos($userId, $tab, $request);

        // AJAX: return JSON for partial table updates
        if ($request->expectsJson()) {
            return response()->json([
                'todoLists' => $todoLists,
                'stats'     => $stats,
                'todos'     => $todos->items(),
                'currentPage' => $todos->currentPage(),
                'lastPage'  => $todos->lastPage(),
            ]);
        }

        return view('todos.index', [
            'todoLists' => $todoLists,
            'defaultList' => $defaultList,
            'selectedListId' => $listId,
            'activeTab' => $tab,
            'stats' => $stats,
            'todos' => $todos,
            'filters' => [
                'status'   => $request->get('status', ''),
                'priority' => $request->get('priority', ''),
                'search'   => $request->get('search', ''),
                'sort_by'  => $request->get('sort_by', 'sort_order'),
                'sort_dir' => $request->get('sort_dir', 'asc'),
            ],
            'userOptions' => User::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]),
        ]);
    }

    /**
     * Return a single todo with full relations (for detail modal).
     */
    public function show(string $id): JsonResponse
    {
        try {
            $todo = Todo::with([
                'owner',
                'delegatedByUser',
                'createdByUser',
                'subtasks.completedByUser',
                'comments.user',
                'attachments.uploadedByUser',
                'watchers.user',
                'todoList',
            ])->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Tugas tidak ditemukan.'], 404);
        }

        $userId = auth()->id();

        // Authorization: owner, creator, delegator, or watcher can view
        $canView = in_array($userId, [
            $todo->owner_id,
            $todo->created_by,
            $todo->delegated_by,
        ]) || $todo->watchers->contains('user_id', $userId);

        if (!$canView && $todo->is_private) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => array_merge($todo->toArray(), [
                'status_badge_class'   => $todo->status_badge_class,
                'priority_badge_class' => $todo->priority_badge_class,
            ]),
        ]);
    }

    // ─── CRUD ────────────────────────────────────────────────────

    public function store(TodoRequest $request): JsonResponse
    {
        $userId = auth()->id();
        $data = $request->validated();

        DB::beginTransaction();
        try {
            // Auto-assign to default list if not specified
            if (empty($data['todo_list_id'])) {
                $defaultList = TodoList::getOrCreateDefault($userId);
                $data['todo_list_id'] = $defaultList->id;
            }

            // Set creator & default owner to current user if not specified
            $data['created_by'] = $userId;
            if (empty($data['owner_id'])) {
                $data['owner_id'] = $userId;
            }

            // If owner is different from creator, it's delegated
            if (!empty($data['owner_id']) && $data['owner_id'] !== $userId) {
                $data['delegated_by'] = $userId;
                $data['delegated_at'] = now();
            }

            // Set timezone
            $data['created_at_timezone'] = config('app.timezone');

            // Convert boolean-like values to integer (checkbox unchecked = key not sent, default to 0)
            $data['is_pinned']   = isset($data['is_pinned'])   ? 1 : 0;
            $data['is_private'] = isset($data['is_private']) ? 1 : 0;

            $todo = Todo::create($data);

            // Create subtasks if provided (form sends subtasks[N][title])
            if (!empty($request->subtasks) && is_array($request->subtasks)) {
                foreach ($request->subtasks as $index => $st) {
                    $title = is_array($st) ? ($st['title'] ?? '') : ($st ?? '');
                    if (!empty(trim($title))) {
                        TodoSubtask::create([
                            'todo_id'    => $todo->id,
                            'title'      => trim($title),
                            'sort_order' => $index,
                        ]);
                    }
                }
            }

            $todo->load(['owner', 'delegatedByUser', 'subtasks']);

            DB::commit();

            // Send notification if delegated
            if ($todo->delegated_by) {
                $this->notifyDelegated($todo);
            }

            return response()->json([
                'success' => true,
                'message'  => 'Tugas berhasil dibuat.',
                'data'    => $todo,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TodoController@store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat tugas: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(TodoRequest $request, string $id): JsonResponse
    {
        $todo = $this->findTodoOrFail($id);
        if (!$todo) {
            return response()->json(['success' => false, 'message' => 'Tugas tidak ditemukan.'], 404);
        }
        $userId = auth()->id();

        // Authorization: only owner, creator, or delegator can update
        if (!in_array($userId, [$todo->owner_id, $todo->created_by, $todo->delegated_by])) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $data = $request->validated();
        $previousStatus = $todo->status;

        DB::beginTransaction();
        try {
            // If delegating to someone else
            if (!empty($data['owner_id']) && $data['owner_id'] !== $todo->owner_id && $data['owner_id'] !== $todo->created_by) {
                $data['delegated_by'] = $userId;
                $data['delegated_at'] = now();
            }

            // If marked as completed
            if (($data['status'] ?? '') === 'selesai' && $previousStatus !== 'selesai') {
                $data['completed_at'] = now();
                $data['progress_percent'] = 100;
            }

            // If unmarked as completed
            if (($data['status'] ?? '') !== 'selesai' && $previousStatus === 'selesai') {
                $data['completed_at'] = null;
            }

            $todo->update($data);
            $todo->load(['owner', 'delegatedByUser', 'subtasks']);

            DB::commit();

            // Notify if just completed
            if ($previousStatus !== 'selesai' && $todo->status === 'selesai') {
                $this->notifyCompleted($todo);
            }

            return response()->json([
                'success' => true,
                'message'  => 'Tugas berhasil diperbarui.',
                'data'    => $todo,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TodoController@update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui tugas: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $todo = $this->findTodoOrFail($id);
        if (!$todo) {
            return response()->json(['success' => false, 'message' => 'Tugas tidak ditemukan.'], 404);
        }
        $userId = auth()->id();

        if (!in_array($userId, [$todo->owner_id, $todo->created_by, $todo->delegated_by])) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $todo->delete();

        return response()->json([
            'success' => true,
            'message'  => 'Tugas berhasil dihapus.',
        ]);
    }

    // ─── Subtasks ────────────────────────────────────────────────

    public function subtaskStore(Request $request, string $todoId): JsonResponse
    {
        $todo = $this->findTodoOrFail($todoId);
        if (!$todo) {
            return response()->json(['success' => false, 'message' => 'Tugas tidak ditemukan.'], 404);
        }
        $data = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $subtask = TodoSubtask::create([
            'todo_id'    => $todo->id,
            'title'      => $data['title'],
            'sort_order' => $todo->subtasks()->max('sort_order') + 1,
        ]);

        // Recalculate progress
        $todo->recalculateProgress();

        return response()->json([
            'success'  => true,
            'message'  => 'Subtask berhasil ditambahkan.',
            'data'     => $subtask,
            'progress' => $todo->fresh()->progress_percent,
        ], 201);
    }

    public function subtaskToggle(string $todoId, string $subtaskId): JsonResponse
    {
        $todo = $this->findTodoOrFail($todoId);
        if (!$todo) {
            return response()->json(['success' => false, 'message' => 'Tugas tidak ditemukan.'], 404);
        }
        $subtask = $todo->subtasks()->findOrFail($subtaskId);

        $subtask->update([
            'is_completed' => $subtask->is_completed ? 0 : 1,
            'completed_at' => $subtask->is_completed ? null : now(),
            'completed_by' => $subtask->is_completed ? null : auth()->id(),
        ]);

        $todo->recalculateProgress();

        return response()->json([
            'success'  => true,
            'message'  => $subtask->is_completed ? 'Subtask selesai.' : 'Subtask dibatalkan.',
            'data'     => $subtask->fresh(),
            'progress' => $todo->fresh()->progress_percent,
        ]);
    }

    public function subtaskDestroy(string $todoId, string $subtaskId): JsonResponse
    {
        $todo = $this->findTodoOrFail($todoId);
        if (!$todo) {
            return response()->json(['success' => false, 'message' => 'Tugas tidak ditemukan.'], 404);
        }
        $subtask = $todo->subtasks()->findOrFail($subtaskId);
        $subtask->delete();

        $todo->recalculateProgress();

        return response()->json([
            'success'  => true,
            'message'  => 'Subtask berhasil dihapus.',
            'progress' => $todo->fresh()->progress_percent,
        ]);
    }

    // ─── Comments ───────────────────────────────────────────────

    public function commentStore(Request $request, string $todoId): JsonResponse
    {
        $todo = $this->findTodoOrFail($todoId);
        if (!$todo) {
            return response()->json(['success' => false, 'message' => 'Tugas tidak ditemukan.'], 404);
        }
        $data = $request->validate([
            'comment'          => 'required|string|max:5000',
            'parent_comment_id' => 'nullable|uuid|exists:todo_comments,id',
        ]);

        $comment = TodoComment::create([
            'todo_id'          => $todo->id,
            'user_id'          => auth()->id(),
            'comment'          => $data['comment'],
            'parent_comment_id' => $data['parent_comment_id'] ?? null,
        ]);

        $comment->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Komentar berhasil ditambahkan.',
            'data'    => $comment,
        ], 201);
    }

    public function commentDestroy(string $todoId, string $commentId): JsonResponse
    {
        $comment = TodoComment::whereHas('todo', fn ($q) => $q->where('id', $todoId))
            ->findOrFail($commentId);

        if ($comment->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message'  => 'Komentar berhasil dihapus.',
        ]);
    }

    // ─── Attachments ─────────────────────────────────────────────

    public function attachmentStore(Request $request, string $todoId): JsonResponse
    {
        $todo = $this->findTodoOrFail($todoId);
        if (!$todo) {
            return response()->json(['success' => false, 'message' => 'Tugas tidak ditemukan.'], 404);
        }

        $data = $request->validate([
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,zip,rar',
        ]);

        $file = $request->file('file');
        $path = $file->store('attachments/todos', 'public');

        $attachment = TodoAttachment::create([
            'todo_id'     => $todo->id,
            'file_name'   => $file->getClientOriginalName(),
            'file_path'   => $path,
            'file_size'   => $file->getSize(),
            'file_type'   => $file->getMimeType(),
            'uploaded_by' => auth()->id(),
        ]);

        $attachment->load('uploadedByUser');

        return response()->json([
            'success' => true,
            'message' => 'Lampiran berhasil diunggah.',
            'data'    => $attachment,
        ], 201);
    }

    public function attachmentDestroy(string $todoId, string $attachmentId): JsonResponse
    {
        $attachment = TodoAttachment::where('todo_id', $todoId)->findOrFail($attachmentId);

        if ($attachment->uploaded_by !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $attachment->delete();

        return response()->json([
            'success' => true,
            'message'  => 'Lampiran berhasil dihapus.',
        ]);
    }

    // ─── Watchers ────────────────────────────────────────────────

    public function watcherAdd(Request $request, string $todoId): JsonResponse
    {
        $todo = $this->findTodoOrFail($todoId);
        if (!$todo) {
            return response()->json(['success' => false, 'message' => 'Tugas tidak ditemukan.'], 404);
        }

        $data = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
        ]);

        if (TodoWatcher::isWatching($todoId, $data['user_id'])) {
            return response()->json([
                'success' => false,
                'message'  => 'Pengguna sudah menjadi pengamat.',
            ], 422);
        }

        $watcher = TodoWatcher::create([
            'todo_id' => $todo->id,
            'user_id' => $data['user_id'],
            'added_by' => auth()->id(),
        ]);

        $watcher->load('user');

        // Notify the new watcher
        $this->notificationService->send($data['user_id'], [
            'module'     => 'todo',
            'type'       => 'info',
            'title'      => 'Anda ditambahkan sebagai pengamat',
            'message'    => 'Anda sekarang mengamati tugas: ' . $todo->title,
            'action'     => 'view',
            'action_url' => route('user.todos.index', ['userId' => $todo->owner_id]),
            'priority'   => 'low',
        ]);

        return response()->json([
            'success' => true,
            'message'  => 'Pengamat berhasil ditambahkan.',
            'data'    => $watcher,
        ], 201);
    }

    public function watcherRemove(string $todoId, string $watcherId): JsonResponse
    {
        $watcher = TodoWatcher::where('todo_id', $todoId)->findOrFail($watcherId);

        // Owner, creator, delegator, or the watcher themselves can remove
        $userId = auth()->id();
        $todo = $this->findTodoOrFail($todoId);
        if (!$todo) {
            return response()->json(['success' => false, 'message' => 'Tugas tidak ditemukan.'], 404);
        }

        if (!in_array($userId, [$todo->owner_id, $todo->created_by, $todo->delegated_by, $watcher->user_id])) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $watcher->delete();

        return response()->json([
            'success' => true,
            'message'  => 'Pengamat berhasil dihapus.',
        ]);
    }

    // ─── Helpers ────────────────────────────────────────────────

    protected function getStats(string $userId, string $listId, string $tab): array
    {
        $query = Todo::withFilters([
            'list_id' => $listId,
        ]);

        $baseQuery = match ($tab) {
            'delegated' => $query->delegatedBy($userId),
            'watched'   => $query->watchedBy($userId)->notPrivate(),
            default     => $query->where(fn ($q) => $q
                ->ownedBy($userId)
                ->orWhere(fn ($q2) => $q2->delegatedBy($userId)->where('owner_id', $userId))
            ),
        };

        return [
            'total'      => (clone $baseQuery)->count(),
            'completed'  => (clone $baseQuery)->byStatus('selesai')->count(),
            'in_progress'=> (clone $baseQuery)->byStatus('sedang_berjalan')->count(),
            'overdue'    => (clone $baseQuery)->overdue()->count(),
        ];
    }

    protected function getTodos(string $userId, string $tab, Request $request)
    {
        $filters = [
            'status'   => $request->get('status'),
            'priority' => $request->get('priority'),
            'search'   => $request->get('search'),
            'sort_by'  => $request->get('sort_by', 'sort_order'),
            'sort_dir' => $request->get('sort_dir', 'asc'),
            'list_id'  => $request->get('list_id'),
        ];

        $query = Todo::withFilters($filters)
            ->with(['owner', 'delegatedByUser', 'subtasks']);

        return match ($tab) {
            'delegated' => $query->delegatedBy($userId)->paginate(20)->withQueryString(),
            'watched'   => $query->watchedBy($userId)->notPrivate()->paginate(20)->withQueryString(),
            default     => $query
                ->where(fn ($q) => $q
                    ->where('owner_id', $userId)
                    ->orWhere(fn ($q2) => $q2
                        ->where('delegated_by', $userId)
                        ->where('owner_id', '!=', $userId)
                    )
                )
                ->paginate(20)
                ->withQueryString(),
        };
    }

    protected function notifyDelegated(Todo $todo): void
    {
        $delegator = auth()->user();

        // Notify owner
        $this->notificationService->send($todo->owner_id, [
            'module'     => 'todo',
            'type'       => 'info',
            'title'      => 'Tugas baru didelegasikan',
            'message'    => $delegator->name . ' telah mendelegasikan tugas "' . $todo->title . '" kepada Anda. Batas waktu: ' . ($todo->due_date?->format('d/m/Y') ?? '-'),
            'action'     => 'view',
            'action_url' => route('user.todos.index', ['userId' => $todo->owner_id]),
            'priority'   => $todo->priority === 'mendesak' ? 'high' : 'medium',
        ]);

        // Notify watchers
        $watcherIds = $todo->watchers()->pluck('user_id')->toArray();
        if (!empty($watcherIds)) {
            $this->notificationService->sendToMany($watcherIds, [
                'module'  => 'todo',
                'type'    => 'info',
                'title'   => 'Tugas baru di-Amati',
                'message' => 'Tugas "' . $todo->title . '" telah dibuat dan Anda mengamati tugas ini.',
                'action'  => 'view',
                'action_url' => route('user.todos.index', ['userId' => $todo->owner_id]),
                'priority' => 'low',
            ]);
        }
    }

    protected function notifyCompleted(Todo $todo): void
    {
        $completer = auth()->user();

        // Notify delegator
        if ($todo->delegated_by) {
            $this->notificationService->send($todo->delegated_by, [
                'module'     => 'todo',
                'type'       => 'success',
                'title'      => 'Tugas selesai',
                'message'    => $completer->name . ' telah menyelesaikan tugas "' . $todo->title . '".',
                'action'     => 'view',
                'action_url' => route('user.todos.index', ['userId' => $todo->owner_id]),
                'priority'   => 'low',
            ]);
        }

        // Notify all watchers
        $watcherIds = $todo->watchers()->pluck('user_id')->toArray();
        if (!empty($watcherIds)) {
            $this->notificationService->sendToMany($watcherIds, [
                'module'     => 'todo',
                'type'       => 'success',
                'title'      => 'Tugas selesai',
                'message'    => 'Tugas "' . $todo->title . '" yang Anda amati telah selesai.',
                'action'     => 'view',
                'action_url' => route('user.todos.index', ['userId' => $todo->owner_id]),
                'priority'   => 'low',
            ]);
        }
    }
}
