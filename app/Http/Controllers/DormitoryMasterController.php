<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\WorkUnit;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DormitoryMasterController extends Controller
{
    private function validateAccess(string $userId): void
    {
        $user = auth()->user();
        abort_unless($user && $user->id === $userId, 403, 'Akses ditolak.');
        abort_unless(
            $user->hasRole(['Super Admin', 'Administrator']) || $user->can('view_global_school_data'),
            403,
            'Hanya Super Admin dan Administrator yang dapat mengakses halaman ini.'
        );
    }

    private function validateWriteAccess(string $userId): void
    {
        $user = auth()->user();
        abort_unless($user && $user->id === $userId, 403, 'Akses ditolak.');
        abort_unless(
            $user->hasRole(['Super Admin', 'Administrator']),
            403,
            'Hanya Super Admin dan Administrator yang dapat mengelola data asrama.'
        );
    }

    public function index(Request $request, string $userId)
    {
        $this->validateAccess($userId);

        $query = Dormitory::with(['workUnit', 'school', 'head'])
            ->withCount(['residents as total_residents' => fn($q) => $q->where('is_active', true)]);

        if ($request->filled('search')) {
            $query->where(fn($sq) => $sq
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%")
            );
        }
        if ($request->filled('work_unit_id')) {
            $query->where('work_unit_id', $request->work_unit_id);
        }
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $dormitories = $query->orderBy('name')->paginate(12)->withQueryString();
        // Work units yang berkaitan dengan Pengasuhan
        $workUnits = WorkUnit::where('name', 'like', '%Pengasuhan%')
            ->orderBy('name')->get();

        $stats = [
            'total'  => Dormitory::count(),
            'active' => Dormitory::where('is_active', true)->count(),
            'putra'  => Dormitory::where('gender', 'putra')->where('is_active', true)->count(),
            'putri'  => Dormitory::where('gender', 'putri')->where('is_active', true)->count(),
        ];

        return view('dormitory.master.index', compact('dormitories', 'workUnits', 'stats', 'userId'));
    }

    public function create(Request $request, string $userId)
    {
        $this->validateWriteAccess($userId);

        // Work units yang berkaitan dengan Pengasuhan
        $workUnits = WorkUnit::where('name', 'like', '%Pengasuhan%')
            ->orderBy('name')->get();

        $schools = School::orderBy('name')->get();
        $heads = User::whereHas('employment')
            ->whereHas('gtkWorkUnits.workUnit', fn($q) => $q->where('type', 'Unsur Pimpinan'))
            ->with(['gtkWorkUnits.workUnit' => fn($q) => $q->where('type', 'Unsur Pimpinan')])
            ->orderBy('name')->get();

        return view('dormitory.master.create', compact('workUnits', 'schools', 'heads', 'userId'));
    }

    public function store(Request $request, string $userId)
    {
        $this->validateWriteAccess($userId);

        $data = $request->validate([
            'work_unit_id' => 'nullable|exists:work_units,id',
            'school_id'    => 'nullable|exists:schools,id',
            'code'         => 'nullable|string|max:20|unique:dormitories,code',
            'gender'       => 'required|in:putra,putri',
            'address'      => 'nullable|string',
            'phone'        => 'nullable|string|max:20',
            'capacity'     => 'nullable|integer|min:1',
            'head_id'      => 'nullable|exists:users,id',
            'is_active'    => 'boolean',
            'notes'        => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        // Auto-fill name & code dari WorkUnit
        if (!empty($data['work_unit_id'])) {
            $wu = WorkUnit::find($data['work_unit_id']);
            if ($wu) {
                // Ganti "Pengasuhan" → "Asrama" di nama
                $data['name'] = str_replace('Pengasuhan', 'Asrama', $wu->name);
            }
        }

        // Handle logo upload
        if ($request->hasFile('logo_path')) {
            $path = $request->file('logo_path')->store('dormitories/logos', 'public');
            $data['logo_path'] = $path;
        }

        $dormitory = Dormitory::create($data);

        return redirect()->route('user.dormitory-master.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id])
            ->with('success', 'Data asrama berhasil disimpan.');
    }

    public function show(string $userId, string $asramaUuid)
    {
        $this->validateAccess($userId);

        $dormitory = Dormitory::with(['workUnit', 'school', 'head', 'wings', 'rooms'])
            ->withCount(['residents as total_residents' => fn($q) => $q->where('is_active', true)])
            ->findOrFail($asramaUuid);

        $stats = [
            'total_residents' => $dormitory->total_residents,
            'total_capacity' => $dormitory->capacity,
            'occupancy_rate' => $dormitory->capacity > 0
                ? round($dormitory->total_residents / $dormitory->capacity * 100, 1)
                : 0,
            'total_rooms' => $dormitory->rooms->count(),
            'total_wings' => $dormitory->wings->count(),
        ];

        return view('dormitory.master.show', compact('dormitory', 'stats', 'userId'));
    }

    public function edit(string $userId, string $asramaUuid)
    {
        $this->validateWriteAccess($userId);

        $dormitory = Dormitory::findOrFail($asramaUuid);
        $workUnits = WorkUnit::where('name', 'like', '%Pengasuhan%')
            ->orderBy('name')->get();
        $schools = School::orderBy('name')->get();
        $heads = User::whereHas('employment')
            ->whereHas('gtkWorkUnits.workUnit', fn($q) => $q->where('type', 'Unsur Pimpinan'))
            ->with(['gtkWorkUnits.workUnit' => fn($q) => $q->where('type', 'Unsur Pimpinan')])
            ->orderBy('name')->get();

        return view('dormitory.master.edit', compact('dormitory', 'workUnits', 'schools', 'heads', 'userId'));
    }

    public function update(Request $request, string $userId, string $asramaUuid)
    {
        $this->validateWriteAccess($userId);

        $dormitory = Dormitory::findOrFail($asramaUuid);

        $data = $request->validate([
            'work_unit_id' => 'nullable|exists:work_units,id',
            'school_id'    => 'nullable|exists:schools,id',
            'code'         => 'required|string|max:20|unique:dormitories,code,' . $asramaUuid,
            'gender'       => 'required|in:putra,putri',
            'address'      => 'nullable|string',
            'phone'        => 'nullable|string|max:20',
            'capacity'     => 'nullable|integer|min:1',
            'head_id'      => 'nullable|exists:users,id',
            'is_active'    => 'boolean',
            'notes'        => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('logo_path')) {
            if ($dormitory->logo_path) {
                Storage::disk('public')->delete($dormitory->logo_path);
            }
            $path = $request->file('logo_path')->store('dormitories/logos', 'public');
            $data['logo_path'] = $path;
        }
        if ($request->boolean('remove_logo')) {
            if ($dormitory->logo_path) {
                Storage::disk('public')->delete($dormitory->logo_path);
            }
            $data['logo_path'] = null;
        }

        $dormitory->update($data);

        return redirect()->route('user.dormitory-master.show', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Data asrama berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $asramaUuid)
    {
        $this->validateWriteAccess($userId);

        $dormitory = Dormitory::findOrFail($asramaUuid);

        if ($dormitory->logo_path) {
            Storage::disk('public')->delete($dormitory->logo_path);
        }

        $dormitory->delete();

        return redirect()->route('user.dormitory-master.index', ['userId' => $userId])
            ->with('success', 'Asrama berhasil dihapus.');
    }
}
