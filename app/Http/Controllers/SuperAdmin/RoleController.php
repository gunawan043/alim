<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->route('userId');
        $query = Role::with('permissions');

        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                ->orWhere('description', 'like', "%{$request->search}%");
        }

        $roles = $query->orderBy('level', 'desc')->paginate(20);
        $permissions = Permission::orderBy('group')->orderBy('name')->get();

        // Group permissions by group field
        $groupedPermissions = $permissions->groupBy(fn($p) => $p->group ?? 'Lainnya');

        return view('super-admin.roles.index', compact('roles', 'groupedPermissions', 'userId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:500',
            'level'       => 'required|integer|min:0|max:100',
            'permissions' => 'array',
            'permissions.*' => 'uuid|exists:permissions,id',
        ]);

        $role = Role::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'level'       => $validated['level'],
        ]);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('super-admin.roles.index')
            ->with('success', 'Role berhasil dibuat.');
    }

    public function update(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:roles,name,' . $id,
            'description' => 'nullable|string|max:500',
            'level'       => 'required|integer|min:0|max:100',
            'permissions' => 'array',
            'permissions.*' => 'uuid|exists:permissions,id',
        ]);

        $role->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'level'       => $validated['level'],
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('super-admin.roles.index')
            ->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);

        // Jangan hapus Super Admin role
        if (strtolower($role->name) === 'super admin') {
            return back()->with('error', 'Role Super Admin tidak dapat dihapus.');
        }

        // Cek apakah ada user dengan role ini
        if ($role->users()->count() > 0) {
            return back()->with('error', "Role digunakan oleh {$role->users()->count()} user. Lepaskan terlebih dahulu.");
        }

        $role->delete();

        return redirect()->route('super-admin.roles.index')
            ->with('success', 'Role berhasil dihapus.');
    }
}
