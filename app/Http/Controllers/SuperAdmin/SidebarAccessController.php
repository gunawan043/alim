<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\SidebarAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SidebarAccessController extends Controller
{
    public function index(): View
    {
        $accesses = SidebarAccess::listAll();
        $roles = Role::where('guard_name', 'web')
            ->whereNotIn('name', ['Super Admin', 'System Admin'])
            ->orderBy('name')
            ->get();

        return view('super-admin.sidebar-access.index', compact('accesses', 'roles'));
    }

    public function update(Request $request, string $menuKey): RedirectResponse
    {
        $validated = $request->validate([
            'allowed_roles' => 'nullable|array',
            'allowed_roles.*' => 'exists:roles,name',
        ]);

        $access = SidebarAccess::where('menu_key', $menuKey)->firstOrFail();
        $access->assignRoles($validated['allowed_roles'] ?? []);

        return redirect()->back()->with('success', 'Menu access updated for '.$access->display_name);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'menu_key' => 'required|string|max:255|unique:sidebar_accesses,menu_key',
            'display_name' => 'required|string|max:255',
            'allowed_roles' => 'nullable|array',
            'allowed_roles.*' => 'exists:roles,name',
        ]);

        SidebarAccess::create([
            'menu_key' => $validated['menu_key'],
            'display_name' => $validated['display_name'],
            'allowed_roles' => $validated['allowed_roles'] ?? [],
        ]);

        return redirect()->route('super-admin.sidebar-access.index')
            ->with('success', 'Menu access created for '.$validated['display_name']);
    }

    public function destroy(string $menuKey): RedirectResponse
    {
        $access = SidebarAccess::where('menu_key', $menuKey)->firstOrFail();
        $access->delete();

        return redirect()->route('super-admin.sidebar-access.index')
            ->with('success', 'Menu access deleted for '.$access->display_name);
    }
}
