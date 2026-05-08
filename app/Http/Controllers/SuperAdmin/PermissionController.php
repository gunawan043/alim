<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->route('userId');
        $query = Permission::query();

        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->has('group') && $request->group) {
            $query->where('group', $request->group);
        }

        $permissions = $query->orderBy('group')->orderBy('name')->paginate(30);
        $groups = Permission::whereNotNull('group')->distinct()->pluck('group');

        return view('super-admin.permissions.index', compact('permissions', 'groups', 'userId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
            'group'       => 'nullable|string|max:100',
        ]);

        Permission::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'group'       => $validated['group'] ?? null,
            'guard_name'  => 'web',
        ]);

        return redirect()->route('super-admin.permissions.index')
            ->with('success', 'Permission berhasil dibuat.');
    }

    public function update(Request $request, string $id)
    {
        $permission = Permission::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:permissions,name,' . $id,
            'description' => 'nullable|string|max:500',
            'group'       => 'nullable|string|max:100',
        ]);

        $permission->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'group'       => $validated['group'] ?? null,
        ]);

        return redirect()->route('super-admin.permissions.index')
            ->with('success', 'Permission berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $permission = Permission::findOrFail($id);

        // Cek apakah ada role yang pakai permission ini
        if ($permission->roles()->count() > 0) {
            return back()->with('error', 'Permission digunakan oleh ' . $permission->roles()->count() . ' role. Lepaskan terlebih dahulu.');
        }

        $permission->delete();

        return redirect()->route('super-admin.permissions.index')
            ->with('success', 'Permission berhasil dihapus.');
    }
}
