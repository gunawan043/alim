<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SidebarMenu;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SidebarMenuManagementController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->route('userId');
        $query = SidebarMenu::with(['roles', 'parent']);

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('route', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }

        $menus = $query->orderBy('order')->paginate(30);
        $roles = Role::orderBy('name')->get();

        // Build tree structure
        $parentMenus = SidebarMenu::whereNull('parent_id')
            ->with('children')
            ->orderBy('order')
            ->get();

        return view('super-admin.sidebar-menus.index', compact('menus', 'roles', 'parentMenus', 'userId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'route'           => 'nullable|string|max:255',
            'url'             => 'nullable|string|max:500',
            'icon'            => 'nullable|string|max:100',
            'parent_id'       => 'nullable|uuid|exists:sidebar_menus,id',
            'order'           => 'nullable|integer|min:0',
            'is_group_header' => 'boolean',
            'is_active'       => 'boolean',
            'roles'           => 'array',
            'roles.*'         => 'uuid|exists:roles,id',
        ]);

        $menu = SidebarMenu::create([
            'name'            => $validated['name'],
            'route'           => $validated['route'] ?? null,
            'url'             => $validated['url'] ?? null,
            'icon'            => $validated['icon'] ?? null,
            'parent_id'       => $validated['parent_id'] ?? null,
            'order'           => $validated['order'] ?? 0,
            'is_group_header' => $validated['is_group_header'] ?? false,
            'is_active'       => $validated['is_active'] ?? true,
        ]);

        if (!empty($validated['roles'])) {
            $menu->roles()->attach($validated['roles']);
        }

        return redirect()->route('user.sa.sidebar-menus.index', ['userId' => $request->route('userId')])
            ->with('success', 'Menu berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $menu = SidebarMenu::findOrFail($id);

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'route'           => 'nullable|string|max:255',
            'url'             => 'nullable|string|max:500',
            'icon'            => 'nullable|string|max:100',
            'parent_id'       => 'nullable|uuid|exists:sidebar_menus,id',
            'order'           => 'nullable|integer|min:0',
            'is_group_header' => 'boolean',
            'is_active'       => 'boolean',
            'roles'           => 'array',
            'roles.*'         => 'uuid|exists:roles,id',
        ]);

        // Prevent self-parenting
        if (isset($validated['parent_id']) && $validated['parent_id'] === $id) {
            return back()->with('error', 'Menu tidak bisa menjadi parent dari dirinya sendiri.');
        }

        $menu->update([
            'name'            => $validated['name'],
            'route'           => $validated['route'] ?? null,
            'url'             => $validated['url'] ?? null,
            'icon'            => $validated['icon'] ?? null,
            'parent_id'       => $validated['parent_id'] ?? null,
            'order'           => $validated['order'] ?? 0,
            'is_group_header' => $validated['is_group_header'] ?? false,
            'is_active'       => $validated['is_active'] ?? true,
        ]);

        $menu->roles()->sync($validated['roles'] ?? []);

        return redirect()->route('user.sa.sidebar-menus.index', ['userId' => $request->route('userId')])
            ->with('success', 'Menu berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $menu = SidebarMenu::findOrFail($id);

        // Cascade delete children
        SidebarMenu::where('parent_id', $id)->update(['parent_id' => null]);

        $menu->roles()->detach();
        $menu->delete();

        return redirect()->route('user.sa.sidebar-menus.index', ['userId' => $request->route('userId')])
            ->with('success', 'Menu berhasil dihapus.');
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'items' => 'array',
            'items.*' => 'uuid|exists:sidebar_menus,id',
        ]);

        foreach ($validated['items'] as $index => $id) {
            SidebarMenu::where('id', $id)->update(['order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
