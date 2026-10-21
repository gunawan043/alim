<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentOperatorAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DormitoryStudentOperatorController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $user = $request->user();
        $dormitories = $this->dormitoriesFor($user);
        abort_if($dormitories->isEmpty(), 403, 'Tidak ada asrama yang dapat dikelola.');
        $dormitory = $dormitories->firstWhere('id', $request->input('dormitory_id')) ?? $dormitories->first();
        abort_unless($user->canAccessDormitory($dormitory->id), 403);

        $assignments = StudentOperatorAssignment::with(['student:id,name,nis', 'user:id,name,username,last_login_at'])
            ->where('operator_type', 'asrama')->where('dormitory_id', $dormitory->id)->latest()->get();
        $stats = [
            'total' => $assignments->count(),
            'active' => $assignments->where('is_active', true)->count(),
            'inactive' => $assignments->where('is_active', false)->count(),
        ];

        return view('dormitory.operators.index', compact('userId', 'dormitories', 'dormitory', 'assignments', 'stats'));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'dormitory_id' => 'required|exists:dormitories,id',
            'student_id' => 'required|exists:students,id',
            'notes' => 'nullable|string|max:2000',
        ]);
        $dormitory = Dormitory::findOrFail($validated['dormitory_id']);
        abort_unless($request->user()->canAccessDormitory($dormitory->id), 403);
        $student = Student::findOrFail($validated['student_id']);
        abort_unless(DormitoryResident::where('dormitory_id', $dormitory->id)->where('student_id', $student->id)->where('is_active', true)->exists(), 422, 'Santri bukan penghuni aktif asrama ini.');
        abort_if(StudentOperatorAssignment::where(['student_id' => $student->id, 'operator_type' => 'asrama', 'dormitory_id' => $dormitory->id, 'is_active' => true])->exists(), 422, 'Santri sudah menjadi petugas aktif asrama ini.');

        $username = trim((string) ($student->nis ?: $student->nisn));
        abort_if($username === '', 422, 'Santri belum memiliki NIS/NISN untuk login.');

        $user = DB::transaction(function () use ($student, $dormitory, $request, $username, $validated) {
            $user = $student->user_id ? User::find($student->user_id) : null;
            if (! $user) {
                $existing = User::where('username', $username)->first();
                if ($existing && (string) $existing->studentProfile?->id !== (string) $student->id) {
                    $username .= '-'.$student->id;
                }
                $user = User::create([
                    'name' => $student->name,
                    'username' => $username,
                    'email' => 'student-'.$student->id.'@student.alim.local',
                    'password' => Hash::make($username),
                    'is_active' => true,
                    'must_change_password' => true,
                ]);
                $student->update(['user_id' => $user->id]);
            } else {
                $user->update(['name' => $student->name, 'username' => $user->username ?: $username, 'password' => Hash::make($username), 'is_active' => true, 'must_change_password' => true]);
            }

            $role = Role::where('name', 'Operator Asrama Santri')->where('guard_name', 'web')->first();
            if (! $role) {
                $role = new Role();
                $role->forceFill([
                    'id' => (string) Str::uuid(),
                    'name' => 'Operator Asrama Santri',
                    'guard_name' => 'web',
                    'level' => 21,
                    'description' => 'Operator asrama dari santri; akses terbatas.',
                ])->save();
            }
            $permission = Permission::where('name', 'menu-asrama-sidebar')->first();
            if ($permission) {
                $role->givePermissionTo($permission);
            }
            $user->assignRole($role);

            return StudentOperatorAssignment::updateOrCreate(
                ['student_id' => $student->id, 'operator_type' => 'asrama', 'dormitory_id' => $dormitory->id],
                ['user_id' => $user->id, 'is_active' => true, 'assigned_by' => $request->user()->id, 'assigned_at' => now(), 'ended_at' => null, 'notes' => $validated['notes'] ?? null]
            );
        });

        return back()->with('success', 'Petugas asrama berhasil ditambahkan.')->with('operator_credentials', [
            'name' => $student->name,
            'username' => $user->username,
            'password' => $username,
            'unit' => $dormitory->name,
        ]);
    }

    public function toggle(Request $request, string $userId, string $id)
    {
        $assignment = StudentOperatorAssignment::with('dormitory')->findOrFail($id);
        abort_unless($request->user()->canAccessDormitory($assignment->dormitory_id), 403);
        $assignment->update(['is_active' => ! $assignment->is_active, 'ended_at' => $assignment->is_active ? now() : null]);

        return back()->with('success', 'Status petugas asrama diperbarui.');
    }

    public function resetPassword(Request $request, string $userId, string $id)
    {
        $assignment = StudentOperatorAssignment::with(['student', 'user'])->findOrFail($id);
        abort_unless($request->user()->canAccessDormitory($assignment->dormitory_id), 403);
        $password = trim((string) ($assignment->student?->nis ?: $assignment->student?->nisn));
        abort_if($password === '', 422, 'Santri belum memiliki NIS/NISN.');
        $assignment->user?->update(['password' => Hash::make($password), 'must_change_password' => true]);

        return back()->with('success', 'Password petugas direset ke NIS/NISN dan wajib diganti.');
    }

    public function searchStudents(Request $request, string $userId)
    {
        $dormitory = Dormitory::findOrFail($request->input('dormitory_id'));
        abort_unless($request->user()->canAccessDormitory($dormitory->id), 403);
        $search = trim((string) $request->input('q', ''));
        $students = Student::query()
            ->whereIn('id', DormitoryResident::where('dormitory_id', $dormitory->id)->where('is_active', true)->pluck('student_id'))
            ->when($search !== '', fn ($q) => $q->where(fn ($inner) => $inner->where('name', 'like', '%'.$search.'%')->orWhere('nis', 'like', '%'.$search.'%')->orWhere('nisn', 'like', '%'.$search.'%')))
            ->orderBy('name')->limit(50)->get(['id', 'name', 'nis', 'nisn']);

        return response()->json(['results' => $students->map(fn ($student) => [
            'id' => $student->id,
            'text' => trim(($student->nis ?: $student->nisn ?: '—').' — '.$student->name),
        ])->values()]);
    }

    private function dormitoriesFor(User $user)
    {
        if ($user->isSystemAdmin() || $user->isSuperAdmin()) {
            return Dormitory::where('is_active', true)->orderBy('name')->get();
        }

        $ids = $user->accessibleDormitoryIds();
        return Dormitory::where('is_active', true)
            ->where(fn ($q) => $q->where('head_id', $user->id)->orWhereIn('id', $ids))
            ->orderBy('name')->get();
    }
}
