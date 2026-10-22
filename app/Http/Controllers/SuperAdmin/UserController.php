<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Mail\PasswordChangedMail;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\SecurityNotificationService;
use App\Services\SecurityPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const SA_ROLE_ID = '3ae73b1d-2513-44e7-a4ca-6c6650658cd1';

    public function index(Request $request)
    {
        $userId = $request->route('userId');
        $query = User::with(['roles', 'employment']);

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('role') && $request->role) {
            $query->whereHas('roles', fn ($q) => $q->where('roles.id', $request->role));
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:'.SecurityPolicy::passwordMinLength().'|confirmed',
            'roles' => 'array|min:1',
            'roles.*' => 'uuid|exists:roles,id',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (! empty($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return redirect()->route('user.sa.users.index', ['userId' => $request->user()->id])
            ->with('success', 'User berhasil dibuat.');
    }

    public function edit(Request $request)
    {
        $userId = $request->route('userId');
        $id = (string) $request->route('id');
        $user = User::with('roles')->findOrFail($id);
        $roles = Role::orderBy('name')->get();

        return view('super-admin.users.edit', compact('user', 'roles', 'userId'));
    }

    public function update(Request $request)
    {
        $id = (string) $request->route('id');
        $user = User::findOrFail($id);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$id,
            'roles' => 'array|min:1',
            'roles.*' => 'uuid|exists:roles,id',
            'is_active' => 'boolean',
        ];

        if ($request->password) {
            $rules['password'] = 'min:'.SecurityPolicy::passwordMinLength().'|confirmed';
        }

        $validated = $request->validate($rules);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $validated['is_active'] ?? $user->is_active,
        ]);

        if (! empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->syncRoles($validated['roles'] ?? []);

        return redirect()->route('user.sa.users.index', ['userId' => $request->user()->id])
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request)
    {
        $id = (string) $request->route('id');

        if (auth()->id() === $id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('user.sa.users.index', ['userId' => $request->user()->id])
            ->with('success', 'User berhasil dihapus.');
    }

    public function resetPassword(Request $request)
    {
        $admin = $request->user();
        $id = (string) $request->route('id');
        $target = User::findOrFail($id);

        if ((string) $target->id === (string) $admin->id) {
            return back()->with('error', 'Gunakan halaman profil untuk mengganti password akun sendiri.');
        }

        $validated = $request->validate([
            'mode' => ['required', Rule::in(['nupy', 'generate', 'manual'])],
            'password' => ['nullable', 'required_if:mode,manual', 'confirmed', 'min:'.SecurityPolicy::passwordMinLength()],
        ]);

        $generatedPassword = null;

        if ($validated['mode'] === 'nupy') {
            $nupy = trim((string) $target->employment?->nupy);

            if ($nupy === '') {
                return back()->with('error', 'User ini tidak memiliki NUPY. Gunakan password acak atau password manual.');
            }

            $plainPassword = '@'.$nupy;

            if (mb_strlen($plainPassword) < SecurityPolicy::passwordMinLength()) {
                return back()->with('error', 'Password @NUPY terlalu pendek. Gunakan password acak atau password manual.');
            }

            $generatedPassword = $plainPassword;
        } elseif ($validated['mode'] === 'generate') {
            $plainPassword = Str::password(16, true, true, true, false);
            $generatedPassword = $plainPassword;
        } else {
            $plainPassword = (string) $validated['password'];
        }

        DB::transaction(function () use ($target, $plainPassword) {
            $target->forceFill([
                'password' => Hash::make($plainPassword),
                'must_change_password' => true,
                'remember_token' => Str::random(60),
                'failed_login_attempts' => 0,
                'locked_until' => null,
                'locked_at' => null,
            ])->save();

            if (DB::getSchemaBuilder()->hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $target->id)->delete();
            }

            if (method_exists($target, 'tokens')) {
                $target->tokens()->delete();
            }
        });

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'ADMIN_PASSWORD_RESET',
            'table_name' => 'users',
            'record_id' => $target->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            Mail::to($target->email)->send(new PasswordChangedMail(
                $target->name,
                now()->locale('id')->translatedFormat('d F Y H:i'),
                $request->ip(),
            ));
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim email reset password oleh admin', [
                'target_user_id' => $target->id,
                'error' => $e->getMessage(),
            ]);
        }

        app(SecurityNotificationService::class)->passwordReset($target, $request);

        $successMessage = match ($validated['mode']) {
            'nupy' => 'Password user '.$target->name.' direset ke @NUPY. Pengguna wajib mengganti password saat login berikutnya.',
            'generate' => 'Password sementara user '.$target->name.' berhasil dibuat. Lihat password pada dialog yang tampil.',
            default => 'Password user '.$target->name.' berhasil direset sesuai password yang Anda tentukan. Pengguna wajib mengganti password saat login berikutnya.',
        };

        $redirect = back()->with('success', $successMessage);

        if ($generatedPassword !== null) {
            $redirect->with('generated_password', $generatedPassword)
                ->with('generated_password_for', $target->name);
        }

        return $redirect;
    }

    public function toggleStatus(Request $request)
    {
        $id = (string) $request->route('id');
        $user = User::findOrFail($id);

        if (auth()->id() === $id) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat menonaktifkan akun sendiri.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return response()->json([
            'success' => true,
            'message' => $user->is_active ? 'User diaktifkan.' : 'User dinonaktifkan.',
            'is_active' => $user->is_active,
        ]);
    }

    /**
     * Quick-assign roles to a user via AJAX (from user index table).
     */
    public function assignRoles(Request $request)
    {
        $id = (string) $request->route('id');
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
