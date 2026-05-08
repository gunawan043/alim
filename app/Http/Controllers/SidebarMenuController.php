<?php

namespace App\Http\Controllers;

use App\Models\SidebarMenu;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SidebarMenuController extends Controller
{
    public function index()
    {
        $menus = SidebarMenu::with('roles')
            ->orderByRaw('COALESCE(parent_id, id), "order"')
            ->get()
            ->groupBy(fn ($m) => $m->parent_id ?? 'root');

        $roles = Role::orderBy('name')->get();
        $allMenus = SidebarMenu::with('roles')->orderBy('order')->get();

        return view('admin.sidebar-menu.index', compact('menus', 'roles', 'allMenus'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:100',
            'icon'            => 'nullable|string|max:50',
            'route'           => 'nullable|string|max:200',
            'url'             => 'nullable|string|max:200',
            'parent_id'       => 'nullable|exists:sidebar_menus,id',
            'order'           => 'nullable|integer|min:0',
            'is_group_header' => 'boolean',
            'is_active'       => 'boolean',
            'roles'           => 'array',
            'roles.*'         => 'exists:roles,id',
        ]);

        if (empty($data['route']) && empty($data['url'])) {
            $data['route'] = null;
            $data['url']   = null;
        }

        DB::beginTransaction();
        try {
            $menu = SidebarMenu::create([
                'id'             => (string) Str::uuid(),
                'name'           => $data['name'],
                'icon'           => $data['icon'] ?? null,
                'route'          => $data['route'] ?? null,
                'url'            => $data['url'] ?? null,
                'parent_id'      => $data['parent_id'] ?? null,
                'order'          => $data['order'] ?? 0,
                'is_group_header'=> $data['is_group_header'] ?? false,
                'is_active'      => $data['is_active'] ?? true,
            ]);

            if (!empty($data['roles'])) {
                $menu->roles()->attach($data['roles']);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Menu berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function update(Request $request, string $id)
    {
        $menu = SidebarMenu::findOrFail($id);

        $data = $request->validate([
            'name'            => 'required|string|max:100',
            'icon'            => 'nullable|string|max:50',
            'route'           => 'nullable|string|max:200',
            'url'             => 'nullable|string|max:200',
            'parent_id'       => ['nullable', function ($attr, $val, $fail) use ($id) {
                if ($val === $id) $fail('Menu tidak bisa menjadi induk dari dirinya sendiri.');
            }],
            'order'           => 'nullable|integer|min:0',
            'is_group_header' => 'boolean',
            'is_active'       => 'boolean',
            'roles'           => 'array',
            'roles.*'         => 'exists:roles,id',
        ]);

        if (empty($data['route']) && empty($data['url'])) {
            $data['route'] = null;
            $data['url']   = null;
        }

        DB::beginTransaction();
        try {
            $menu->update([
                'name'           => $data['name'],
                'icon'           => $data['icon'] ?? null,
                'route'          => $data['route'] ?? null,
                'url'            => $data['url'] ?? null,
                'parent_id'      => $data['parent_id'] ?? null,
                'order'          => $data['order'] ?? 0,
                'is_group_header'=> $data['is_group_header'] ?? false,
                'is_active'      => $data['is_active'] ?? true,
            ]);

            $menu->roles()->sync($data['roles'] ?? []);

            DB::commit();
            return redirect()->back()->with('success', 'Menu berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function destroy(string $id)
    {
        $menu = SidebarMenu::findOrFail($id);

        // Jangan hapus group header yang punya anak
        if (!$menu->is_group_header && SidebarMenu::where('parent_id', $id)->exists()) {
            return redirect()->back()->with('error', 'Hapus sub-menu terlebih dahulu.');
        }

        $menu->delete();
        return redirect()->back()->with('success', 'Menu berhasil dihapus.');
    }
}
