<?php

namespace App\Http\Controllers\Perpustakaan;

use App\Http\Controllers\Controller;
use App\Models\LibraryUnit;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentOperatorAssignment;
use App\Models\User;
use App\Services\LibraryAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Permission;
use App\Models\Role;

class LibraryStudentOperatorController extends Controller
{
    public function __construct(private readonly LibraryAccess $access) {}

    public function index(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $units = $this->access->unitsFor($request->user());
        $unit = $units->firstWhere('id', $request->input('unit_id')) ?? $units->first();
        abort_unless($unit, 404, 'Belum ada unit perpustakaan.');

        $assignments = StudentOperatorAssignment::with(['student:id,name,nis,gender', 'user:id,name,username,last_login_at', 'assigner:id,name'])
            ->where('operator_type', 'library')->where('library_unit_id', $unit->id)
            ->latest()->get();
        $stats = [
            'total' => $assignments->count(),
            'active' => $assignments->where('is_active', true)->count(),
            'inactive' => $assignments->where('is_active', false)->count(),
        ];

        return view('perpustakaan.operators.index', compact('userId', 'units', 'unit', 'assignments', 'stats'));
    }

    public function store(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $validated = $request->validate([
            'library_unit_id' => 'required|exists:library_units,id',
            'student_id' => 'required|exists:students,id',
            'notes' => 'nullable|string|max:2000',
        ]);
        $unit = LibraryUnit::findOrFail($validated['library_unit_id']);
        abort_unless($this->access->canManageUnit($request->user(), $unit), 403);
        $student = Student::findOrFail($validated['student_id']);
        $this->assertEligible($student, $unit);

        abort_if(StudentOperatorAssignment::where([
            'student_id' => $student->id,
            'operator_type' => 'library',
            'library_unit_id' => $unit->id,
            'is_active' => true,
        ])->exists(), 422, 'Santri sudah menjadi petugas aktif pada unit ini.');

        $username = trim((string) ($student->nis ?: $student->nisn));
        abort_if($username === '', 422, 'Santri belum memiliki NIS/NISN untuk login.');
        $initialPassword = $username;

        $user = DB::transaction(function () use ($student, $unit, $request, $username, $initialPassword, $validated) {
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
                    'password' => Hash::make($initialPassword),
                    'is_active' => true,
                    'must_change_password' => true,
                ]);
                $student->update(['user_id' => $user->id]);
            } else {
                $user->update([
                    'name' => $student->name,
                    'username' => $user->username ?: $username,
                    'password' => Hash::make($initialPassword),
                    'is_active' => true,
                    'must_change_password' => true,
                ]);
            }

            $role = Role::where('name', 'Operator Perpustakaan Santri')->where('guard_name', 'web')->first();
            if (! $role) {
                $role = new Role();
                $role->forceFill([
                    'id' => (string) Str::uuid(),
                    'name' => 'Operator Perpustakaan Santri',
                    'guard_name' => 'web',
                    'level' => 20,
                    'description' => 'Operator perpustakaan dari santri; akses terbatas.',
                ])->save();
            }
            $permission = Permission::where('name', 'menu-perpustakaan-sidebar')->first();
            if ($permission) {
                $role->givePermissionTo($permission);
            }
            $user->assignRole($role);

            return StudentOperatorAssignment::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'operator_type' => 'library',
                    'library_unit_id' => $unit->id,
                ],
                [
                    'user_id' => $user->id,
                    'is_active' => true,
                    'assigned_by' => $request->user()->id,
                    'assigned_at' => now(),
                    'ended_at' => null,
                    'notes' => $validated['notes'] ?? null,
                ]
            );
        });

        return back()
            ->with('success', 'Petugas santri berhasil ditambahkan.')
            ->with('operator_credentials', [
                'name' => $student->name,
                'username' => $user->username,
                'password' => $initialPassword,
                'unit' => $unit->name,
            ]);
    }

    public function toggle(Request $request, string $userId, string $id)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $assignment = StudentOperatorAssignment::with('unit')->findOrFail($id);
        abort_unless($this->access->canManageUnit($request->user(), $assignment->unit), 403);
        $assignment->update([
            'is_active' => ! $assignment->is_active,
            'ended_at' => $assignment->is_active ? now() : null,
        ]);

        return back()->with('success', 'Status petugas santri diperbarui.');
    }

    public function resetPassword(Request $request, string $userId, string $id)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $assignment = StudentOperatorAssignment::with(['unit', 'student', 'user'])->findOrFail($id);
        abort_unless($this->access->canManageUnit($request->user(), $assignment->unit), 403);
        $student = $assignment->student;
        $password = trim((string) ($student?->nis ?: $student?->nisn));
        abort_if($password === '', 422, 'Santri belum memiliki NIS/NISN.');
        $assignment->user?->update([
            'password' => Hash::make($password),
            'must_change_password' => true,
        ]);

        return back()->with('success', 'Password petugas direset ke NIS/NISN. Wajib diganti saat login.');
    }

    private function assertEligible(Student $student, LibraryUnit $unit): void
    {
        if ($unit->gender_scope !== 'mixed' && strtoupper((string) $student->gender) !== strtoupper((string) $unit->gender_scope)) {
            abort(422, 'Gender santri tidak sesuai dengan unit perpustakaan.');
        }
        $history = StudentClassHistory::with('studyGroup.gradeLevel')->where('student_id', $student->id)->latest()->first();
        $level = $history?->studyGroup?->gradeLevel?->level;
        if ($unit->level_scope === 'sd' && $level && $level > 6) {
            abort(422, 'Unit perpustakaan khusus jenjang SD.');
        }
        if ($unit->level_scope === 'smp' && $level && ($level < 7 || $level > 9)) {
            abort(422, 'Unit perpustakaan khusus jenjang SMP.');
        }
        if ($unit->level_scope === 'sd_smp' && $level && $level > 9) {
            abort(422, 'Unit perpustakaan khusus jenjang SD–SMP.');
        }
    }
}
