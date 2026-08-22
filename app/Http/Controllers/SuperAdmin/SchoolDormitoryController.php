<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\Dormitory;
use App\Models\School;
use App\Models\WorkUnit;
use App\Models\User;
use Illuminate\Http\Request;

class DormitoryController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->route('userId');
        $query = Dormitory::with(['school', 'workUnit', 'head']);

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('gender') && $request->gender) {
            $query->where('gender', $request->gender);
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status === '1');
        }

        $dormitories = $query->orderBy('name')->paginate(20);
        $schools = School::active()->orderBy('name')->get();
        $users = User::where('is_active', true)->orderBy('name')->get();

        return view('super-admin.dormitories.index', compact('dormitories', 'schools', 'users', 'userId'));
    }

    public function create(Request $request)
    {
        $userId = $request->route('userId');
        $schools = School::active()->orderBy('name')->get();
        $workUnits = WorkUnit::orderBy('name')->get();
        $users = User::where('is_active', true)->orderBy('name')->get();

        return view('super-admin.dormitories.create', compact('schools', 'workUnits', 'users', 'userId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'work_unit_id' => 'required|exists:work_units,id',
            'school_id' => 'required|exists:schools,id',
            'code' => 'required|string|max:20|unique:dormitories,code',
            'name' => 'required|string|max:191',
            'gender' => 'required|in:putra,putri,campuran',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'capacity' => 'required|integer|min:1',
            'total_rooms' => 'nullable|integer|min:0',
            'total_wings' => 'nullable|integer|min:0',
            'head_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'logo_path' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'notes' => 'nullable|string',
        ]);

        $dormitory = Dormitory::create($validated);

        if ($request->hasFile('logo_path')) {
            $dormitory->update(['logo_path' => $request->file('logo_path')->store('dormitories', 'public')]);
        }

        return redirect()->route('user.sa.dormitories.index', ['userId' => $request->user()->id])
            ->with('success', 'Asrama berhasil ditambahkan.');
    }

    public function edit(Request $request, string $id)
    {
        $userId = $request->route('userId');
        $dormitory = Dormitory::findOrFail($id);
        $schools = School::active()->orderBy('name')->get();
        $workUnits = WorkUnit::orderBy('name')->get();
        $users = User::where('is_active', true)->orderBy('name')->get();

        return view('super-admin.dormitories.edit', compact('dormitory', 'schools', 'workUnits', 'users', 'userId'));
    }

    public function update(Request $request, string $id)
    {
        $dormitory = Dormitory::findOrFail($id);

        $validated = $request->validate([
            'work_unit_id' => 'required|exists:work_units,id',
            'school_id' => 'required|exists:schools,id',
            'code' => 'required|string|max:20|unique:dormitories,code,' . $id,
            'name' => 'required|string|max:191',
            'gender' => 'required|in:putra,putri,campuran',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'capacity' => 'required|integer|min:1',
            'total_rooms' => 'nullable|integer|min:0',
            'total_wings' => 'nullable|integer|min:0',
            'head_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'logo_path' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'notes' => 'nullable|string',
        ]);

        $dormitory->update($validated);

        if ($request->hasFile('logo_path')) {
            $dormitory->update(['logo_path' => $request->file('logo_path')->store('dormitories', 'public')]);
        }

        return redirect()->route('user.sa.dormitories.index', ['userId' => $request->user()->id])
            ->with('success', 'Asrama berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id)
    {
        $dormitory = Dormitory::findOrFail($id);
        $dormitory->delete();

        return redirect()->route('user.sa.dormitories.index', ['userId' => $request->user()->id])
            ->with('success', 'Asrama berhasil dihapus.');
    }

    public function toggleStatus(Request $request, string $id)
    {
        $dormitory = Dormitory::findOrFail($id);
        $dormitory->update(['is_active' => !$dormitory->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $dormitory->is_active,
            'message' => $dormitory->is_active ? 'Asrama diaktifkan.' : 'Asrama dinonaktifkan.',
        ]);
    }
}
