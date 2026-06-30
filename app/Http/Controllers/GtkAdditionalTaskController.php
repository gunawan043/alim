<?php

namespace App\Http\Controllers;

use App\Models\GtkAdditionalTask;
use App\Models\InstitutionDecree;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GtkAdditionalTaskController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $query = GtkAdditionalTask::with(['user', 'decree']);

        $schoolId = $request->attributes->get('schoolContextId');
        $currentUser = auth()->user();
        $isGlobal = $currentUser->hasRole('Super Admin')
            || $currentUser->hasRole('Administrator')
            || $currentUser->hasRole('Wadir 1')
            || $currentUser->hasRole('Mudir');

        // Filter by teacher if specified
        if ($request->filled('teacher_id')) {
            $query->where('user_id', $request->teacher_id);
        }

        // Filter by decree if specified
        if ($request->filled('decree_id')) {
            $query->where('decree_id', $request->decree_id);
        }

        $tasks = $query->orderBy('user_id')->orderBy('nama_tugas')->paginate(20)->withQueryString();

        // Teachers filter by school context (gtk_employments)
        $teachers = User::role(['Guru Umum', 'Guru Agama', 'Guru Hadits', 'Guru Tahfidz', 'GTK', 'Coordinator Guru', 'Wakil Kepala Sekolah'])
            ->when($schoolId, fn($q) => $q->whereHas('employments', fn($eq) => $eq->where('school_id', $schoolId)))
            ->orderBy('name')->get();

        $decrees = InstitutionDecree::where('decree_type', 'SK Pembagian Tugas')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderByDesc('issued_date')->get();

        return view('gtk-additional-tasks.index', compact('tasks', 'teachers', 'decrees', 'userId'));
    }

    public function create(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        // Teachers filtered by school from gtk_employments
        $teachers = User::role(['Guru Umum', 'Guru Agama', 'Guru Hadits', 'Guru Tahfidz', 'GTK', 'Coordinator Guru', 'Wakil Kepala Sekolah'])
            ->when($schoolId, fn($q) => $q->whereHas('employments', fn($eq) => $eq->where('school_id', $schoolId)))
            ->orderBy('name')->get();

        $decrees = InstitutionDecree::where('decree_type', 'SK Pembagian Tugas')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderByDesc('issued_date')->get();

        // Default task names
        $defaultTasks = [
            'Kesiswawan',
            'Coordinator Guru Agama',
            'Coordinator Guru Tahfidz',
            'Coordinator Guru Umum',
            'Waka Kurikulum',
            'Waka Kesiswaan',
            'Waka Sarana & Prasarana',
            'Coordinator Bahasa Arab',
            'Coordinator OSIS',
            'Coordinator Ekstrakurikuler',
            'Wali Kelas',
            'Guru BK',
            'Koordinator Laboratorium',
            'Koordinator Perpustakaan',
        ];

        return view('gtk-additional-tasks.create', compact('teachers', 'decrees', 'defaultTasks', 'userId'));
    }

    public function store(Request $request, string $userId)
    {
        $data = $request->validate([
            'user_id'       => 'required|exists:users,id',
            'decree_id'     => 'nullable|exists:institution_decrees,id',
            'nama_tugas'    => 'required|string|max:150',
            'hours_per_week'=> 'nullable|integer|min:0|max:40',
            'nomor_sk'      => 'nullable|string|max:100',
            'tmt'           => 'nullable|date',
            'tst'           => 'nullable|date|after_or_equal:tmt',
        ]);

        GtkAdditionalTask::create($data);

        return redirect()->route('user.gtk-additional-tasks.index', ['userId' => $userId])
            ->with('success', 'Tugas tambahan berhasil disimpan.');
    }

    public function show(string $userId, string $id)
    {
        $task = GtkAdditionalTask::with(['user', 'decree'])->findOrFail($id);

        $schoolId = request()->attributes->get('schoolContextId');
        $currentUser = auth()->user();
        $isGlobal = $currentUser->hasRole('Super Admin')
            || $currentUser->hasRole('Administrator')
            || $currentUser->hasRole('Wadir 1')
            || $currentUser->hasRole('Mudir');

        if (!$isGlobal && $task->decree && $schoolId && $task->decree->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        return view('gtk-additional-tasks.show', compact('task', 'userId'));
    }

    public function edit(string $userId, string $id)
    {
        $task = GtkAdditionalTask::findOrFail($id);

        $schoolId = request()->attributes->get('schoolContextId');
        $currentUser = auth()->user();
        $isGlobal = $currentUser->hasRole('Super Admin')
            || $currentUser->hasRole('Administrator')
            || $currentUser->hasRole('Wadir 1')
            || $currentUser->hasRole('Mudir');

        if (!$isGlobal && $task->decree && $schoolId && $task->decree->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $teachers = User::role(['Guru Umum', 'Guru Agama', 'Guru Hadits', 'Guru Tahfidz', 'GTK', 'Coordinator Guru', 'Wakil Kepala Sekolah'])
            ->when($schoolId, fn($q) => $q->whereHas('employments', fn($eq) => $eq->where('school_id', $schoolId)))
            ->orderBy('name')->get();

        $decrees = InstitutionDecree::where('decree_type', 'SK Pembagian Tugas')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderByDesc('issued_date')->get();

        return view('gtk-additional-tasks.edit', compact('task', 'teachers', 'decrees', 'userId'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $task = GtkAdditionalTask::findOrFail($id);

        $data = $request->validate([
            'user_id'       => 'required|exists:users,id',
            'decree_id'     => 'nullable|exists:institution_decrees,id',
            'nama_tugas'    => 'required|string|max:150',
            'hours_per_week'=> 'nullable|integer|min:0|max:40',
            'nomor_sk'      => 'nullable|string|max:100',
            'tmt'           => 'nullable|date',
            'tst'           => 'nullable|date|after_or_equal:tmt',
        ]);

        $task->update($data);

        return redirect()->route('user.gtk-additional-tasks.index', ['userId' => $userId])
            ->with('success', 'Tugas tambahan berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $task = GtkAdditionalTask::findOrFail($id);
        $task->delete();

        return redirect()->route('user.gtk-additional-tasks.index', ['userId' => $userId])
            ->with('success', 'Tugas tambahan berhasil dihapus.');
    }
}
