<?php

namespace App\Http\Controllers;

use App\Models\TodoList;
use App\Models\Todo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TodoListController extends Controller
{
    /**
     * Get all todo lists for the authenticated user (used via AJAX).
     */
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $lists = TodoList::forUser($userId)
            ->withCount(['todos as todo_count' => fn ($q) => $q->whereNull('deleted_at')])
            ->ordered()
            ->get()
            ->map(fn ($list) => [
                'id'         => $list->id,
                'name'       => $list->name,
                'color'      => $list->color,
                'is_default' => (bool) $list->is_default,
                'sort_order' => $list->sort_order,
                'todo_count' => $list->todo_count,
            ]);

        return response()->json(['success' => true, 'data' => $lists]);
    }

    /**
     * Create a new todo list.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'color'      => 'nullable|string|max:20',
            'is_default' => 'nullable|integer|in:0,1',
        ]);

        $userId = auth()->id();

        // If setting as default, unset other defaults first
        if (!empty($data['is_default'])) {
            TodoList::forUser($userId)->update(['is_default' => 0]);
        }

        $maxOrder = TodoList::forUser($userId)->max('sort_order') ?? 0;

        $list = TodoList::create([
            'user_id'    => $userId,
            'name'       => $data['name'],
            'color'      => $data['color'] ?? '#adb5bd',
            'is_default' => $data['is_default'] ?? 0,
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json([
            'success' => true,
            'message'  => 'Daftar todo berhasil dibuat.',
            'data'    => $list,
        ], 201);
    }

    /**
     * Update a todo list (rename or change color).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $list = TodoList::where('user_id', auth()->id())->findOrFail($id);

        $data = $request->validate([
            'name'       => 'sometimes|required|string|max:100',
            'color'      => 'nullable|string|max:20',
            'is_default' => 'nullable|integer|in:0,1',
        ]);

        // If setting as default, unset other defaults first
        if (!empty($data['is_default'])) {
            TodoList::forUser(auth()->id())
                ->where('id', '!=', $id)
                ->update(['is_default' => 0]);
        }

        $list->update($data);

        return response()->json([
            'success' => true,
            'message'  => 'Daftar todo berhasil diperbarui.',
            'data'    => $list->fresh(),
        ]);
    }

    /**
     * Delete a todo list.
     */
    public function destroy(string $id): JsonResponse
    {
        $list = TodoList::where('user_id', auth()->id())->findOrFail($id);

        if ($list->is_default) {
            return response()->json([
                'success' => false,
                'message'  => 'Daftar default tidak dapat dihapus.',
            ], 422);
        }

        DB::transaction(function () use ($list) {
            // Soft delete all todos in this list
            Todo::where('todo_list_id', $list->id)->delete();
            // Delete the list
            $list->delete();
        });

        return response()->json([
            'success' => true,
            'message'  => 'Daftar todo berhasil dihapus.',
        ]);
    }

    /**
     * Set a todo list as the default.
     */
    public function setDefault(string $id): JsonResponse
    {
        $list = TodoList::where('user_id', auth()->id())->findOrFail($id);

        DB::transaction(function () use ($list) {
            TodoList::forUser(auth()->id())->update(['is_default' => 0]);
            $list->update(['is_default' => 1]);
        });

        return response()->json([
            'success' => true,
            'message'  => 'Daftar default berhasil diubah.',
        ]);
    }

    /**
     * Reorder todo lists via drag-and-drop or explicit sort_order.
     */
    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order' => 'required|array',
            'order.*' => 'uuid|exists:todo_lists,id',
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['order'] as $index => $listId) {
                TodoList::where('id', $listId)
                    ->where('user_id', auth()->id())
                    ->update(['sort_order' => $index]);
            }
        });

        return response()->json([
            'success' => true,
            'message'  => 'Urutan berhasil diperbarui.',
        ]);
    }
}
