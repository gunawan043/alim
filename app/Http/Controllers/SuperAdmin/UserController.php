<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private const SA_ROLE_ID = '3ae73b1d-2513-44e7-a4ca-6c6650658cd1';

    public function index(Request $request)
    {
        $userId = $request->route('userId');
        $query = User::with('roles');

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('role') && $request->role) {
            $query->whereHas('roles', fn($q) => $q->where('roles.id', $request->role));
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status);
        }

        $users = $query->orderBy('name')->paginate(20);
        $roles = Role::orderBy('name')->get();

        return view('super-admin.users.index', compact('users', 'roles', 'userId'));
    }

    public function create(Request $request)
    {
        $userId = $request->route('userId');
        $roles = Role::orderBy('name')->get();
        return view('super-admin.users.create', compact('roles', 'userId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:8|confirmed',
            'roles'     => 'array|min:1',
            'roles.*'   => 'uuid|exists:roles,id',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (!empty($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return redirect()->route('user.sa.users.index', ['userId' => $request->user()->id])
            ->with('success', 'User berhasil dibuat.');
    }

    public function edit(Request $request, string $id)
    {
        $userId = $request->route('userId');
        $user = User::with('roles')->findOrFail($id);
        $roles = Role::orderBy('name')->get();
        return view('super-admin.users.edit', compact('user', 'roles', 'userId'));
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $rules = [
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,' . $id,
            'roles'     => 'array|min:1',
            'roles.*'   => 'uuid|exists:roles,id',
            'is_active' => 'boolean',
        ];

        if ($request->password) {
            $rules['password'] = 'min:8|confirmed';
        }

        $validated = $request->validate($rules);

        $user->update([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'is_active' => $validated['is_active'] ?? $user->is_active,
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->syncRoles($validated['roles'] ?? []);

        return redirect()->route('user.sa.users.index', ['userId' => $request->user()->id])
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id)
    {
        if (auth()->id() === $id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('user.sa.users.index', ['userId' => $request->user()->id])
            ->with('success', 'User berhasil dihapus.');
    }

    public function toggleStatus(string $id)
    {
        $user = User::findOrFail($id);

        if (auth()->id() === $id) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat menonaktifkan akun sendiri.']);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'success' => true,
            'message' => $user->is_active ? 'User diaktifkan.' : 'User dinonaktifkan.',
            'is_active' => $user->is_active,
        ]);
    }

    /**
     * Quick-assign roles to a user via AJAX (from user index table).
     */
    public function assignRoles(Request $request, string $id)
    {
        $validated = $request->validate([
            'roles' => 'array',
            'roles.*' => 'uuid|exists:roles,id',
        ]);

        $user = User::findOrFail($id);
        $user->syncRoles($validated['roles'] ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Role user berhasil diperbarui.',
            'roles' => $user->fresh()->roles->pluck('name'),
        ]);
    }
}
